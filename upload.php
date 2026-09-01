<?php
declare(strict_types=1);

require __DIR__ . '/TOOLS/security.php';
require_login();

$message = '';
$upload_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    verify_csrf();

    $selected_dir = (string)($_POST['dir'] ?? '');
    $file = $_FILES['file'] ?? null;

    if ($selected_dir === '' || strlen($selected_dir) < 2) {
        $message = '请选择上传目录';
    } elseif (!$file || ((int)$file['error']) !== UPLOAD_ERR_OK) {
        $message = '请选择有效的文件';
    } else {
        $dir_type = $selected_dir[0];
        $dir_name = substr($selected_dir, 1);

        $allowed_types = ['md', 'txt', 'pdf'];
        $max_size = 10 * 1024 * 1024;

        $file_extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types, true)) {
            $message = '只允许上传 .md, .txt, .pdf 格式的文件';
        } elseif ((int)$file['size'] > $max_size) {
            $message = '文件大小不能超过10MB';
        } elseif (($dir_type !== '0' && $dir_type !== '1') || !valid_segment($dir_name)) {
            $message = '无效的目录';
        } else {
            $base = ($dir_type === '0') ? 'MARKDOWN' : 'PDFS';
            $dirReal = secure_realpath(__DIR__ . '/' . $base, $dir_name);

            if ($dirReal === null || !is_dir($dirReal)) {
                $message = '目标目录不存在';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = (string)$finfo->file($file['tmp_name']);

                $mimeOk = false;
                if ($file_extension === 'pdf') {
                    $mimeOk = ($mime === 'application/pdf');
                } else {
                    $mimeOk = (strpos($mime, 'text/') === 0 || in_array($mime, ['application/x-empty', 'inode/x-empty'], true));
                }

                if (!$mimeOk) {
                    $message = '文件内容与扩展名不匹配，已拒绝上传';
                } else {
                    $original = (string)$file['name'];
                    $safe_filename = preg_replace('/[^\p{L}\p{N}._-]/u', '_', $original);
                    if ($safe_filename === null || $safe_filename === '' || $safe_filename === '.' || $safe_filename === '..') {
                        $safe_filename = 'file';
                    }
                    if (strtolower(pathinfo($safe_filename, PATHINFO_EXTENSION)) !== $file_extension) {
                        $safe_filename .= '.' . $file_extension;
                    }
                    $safe_filename = time() . '_' . $safe_filename;

                    $target_path = $dirReal . DIRECTORY_SEPARATOR . $safe_filename;

                    $mysqli = db_connect();
                    $upload_type = ($dir_type === '0') ? 0 : 1;
                    $stmt = $mysqli->prepare('INSERT INTO uploads (user_id, username, directory, filename, original_filename, upload_time, file_type) VALUES (?, ?, ?, ?, ?, NOW(), ?)');
                    if ($stmt) {
                        $stmt->bind_param('issssi', $_SESSION['user_id'], $_SESSION['username'], $dir_name, $safe_filename, $original, $upload_type);

                        if ($stmt->execute()) {
                            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                chmod($target_path, 0644);
                                $message = '文件上传成功！';
                                $upload_success = true;
                            } else {
                                $message = '文件移动失败，请检查目录权限';
                                $del = $mysqli->prepare('DELETE FROM uploads WHERE filename = ?');
                                if ($del) {
                                    $del->bind_param('s', $safe_filename);
                                    $del->execute();
                                    $del->close();
                                }
                            }
                        } else {
                            $message = '数据库记录失败，请稍后重试';
                        }
                        $stmt->close();
                    } else {
                        $message = '数据库准备失败，请稍后重试';
                    }
                    $mysqli->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>文件上传 - <?php echo e($_SESSION['username'] ?? ''); ?></title>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/APP.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/UPLOAD.css?v=<?php echo time(); ?>" />
</head>
<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"'; ?>>
    <div class="container">
        <div class="user-info">
            <span>当前用户: <strong><?php echo e($_SESSION['username'] ?? ''); ?></strong></span>
            <a href="logout.php" class="logout">退出登录</a>
        </div>

        <div class="upload-card">
            <h2>文件上传</h2>

            <?php if ($message !== ''): ?>
                <div class="message <?php echo $upload_success ? 'success' : 'error'; ?>">
                    <?php echo e($message); ?>
                </div>
            <?php endif; ?>

            <form action="upload.php" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="directory-section">
                    <h3>Markdown目录:</h3>
                    <?php
                    $md_base = __DIR__ . '/MARKDOWN';
                    $md_dirs = [];
                    if (is_dir($md_base)) {
                        foreach (scandir($md_base) as $d) {
                            if ($d === '.' || $d === '..' || !is_dir($md_base . DIRECTORY_SEPARATOR . $d)) continue;
                            $md_dirs[] = $d;
                        }
                    }
                    usort($md_dirs, 'strnatcasecmp');
                    foreach ($md_dirs as $d) {
                        echo "<label><input type='radio' name='dir' value='0" . e($d) . "' required> " . e($d) . "</label><br>";
                    }
                    if (empty($md_dirs)) {
                        echo "<p>暂无Markdown目录</p>";
                    }
                    ?>
                </div>

                <div class="directory-section">
                    <h3>PDF目录:</h3>
                    <?php
                    $pdf_base = __DIR__ . '/PDFS';
                    $pdf_dirs = [];
                    if (is_dir($pdf_base)) {
                        foreach (scandir($pdf_base) as $d) {
                            if ($d === '.' || $d === '..' || !is_dir($pdf_base . DIRECTORY_SEPARATOR . $d)) continue;
                            $pdf_dirs[] = $d;
                        }
                    }
                    usort($pdf_dirs, 'strnatcasecmp');
                    foreach ($pdf_dirs as $d) {
                        echo "<label><input type='radio' name='dir' value='1" . e($d) . "' required> " . e($d) . "</label><br>";
                    }
                    if (empty($pdf_dirs)) {
                        echo "<p>暂无PDF目录</p>";
                    }
                    ?>
                </div>

                <div class="file-input">
                    <label for="file">选择文件 (支持 .md, .txt, .pdf, 最大10MB):</label><br>
                    <input type="file" name="file" id="file" accept=".md,.txt,.pdf" required>
                </div>

                <input type="submit" name="submit" value="上传文件" class="submit-btn">
            </form>
        </div>
    </div>
</body>
</html>
