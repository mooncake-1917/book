<?php
// 启动会话
session_start();

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 处理上传逻辑
$message = '';
$upload_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $selected_dir = isset($_POST['dir']) ? $_POST['dir'] : '';
    $file = isset($_FILES['file']) ? $_FILES['file'] : null;
    
    // 验证输入
    if (empty($selected_dir)) {
        $message = '请选择上传目录';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $message = '请选择有效的文件';
    } else {
        // 提取目录类型和名称
        $dir_type = substr($selected_dir, 0, 1);
        $dir_name = substr($selected_dir, 1);
        
        // 数据库配置 - 使用与login.php相同的配置
        $localhost = 'localhost';
        $db_user = 'user';
        $db_pass = '19207572133';
        $db_name = 'book';
        
        $mysqli = new mysqli($localhost, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_errno) {
            $message = '数据库连接失败: ' . $mysqli->connect_error;
        } else {
            // 获取当前登录用户信息
            $user_id = $_SESSION['user_id'];
            $username = $_SESSION['username'];
            
            // 验证文件类型和大小
            $allowed_types = ['md', 'txt', 'pdf'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_types)) {
                $message = '只允许上传 .md, .txt, .pdf 格式的文件';
            } elseif ($file['size'] > $max_size) {
                $message = '文件大小不能超过10MB';
            } else {
                // 安全处理文件名
                $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                $safe_filename = time() . '_' . $safe_filename; // 添加时间戳避免重名
                
                // 确定目标目录
                if ($dir_type == '0') {
                    $target_dir = "./MARKDOWN/" . $dir_name . "/";
                    $upload_type = 0;
                } elseif ($dir_type == '1') {
                    $target_dir = "./PDFS/" . $dir_name . "/";
                    $upload_type = 1;
                } else {
                    $message = '无效的目录类型';
                    $mysqli->close();
                    exit;
                }
                
                // 确保目标目录存在
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                
                $target_path = $target_dir . $safe_filename;
                
                // 使用预处理语句记录上传信息
                $stmt = $mysqli->prepare('INSERT INTO uploads (user_id, username, directory, filename, original_filename, upload_time, file_type) VALUES (?, ?, ?, ?, ?, NOW(), ?)');
                if ($stmt) {
                    $stmt->bind_param('issssi', $user_id, $username, $dir_name, $safe_filename, $file['name'], $upload_type);
                    
                    if ($stmt->execute()) {
                        // 移动上传的文件
                        if (move_uploaded_file($file['tmp_name'], $target_path)) {
                            $message = '文件上传成功！';
                            $upload_success = true;
                            
                            // 设置文件权限
                            chmod($target_path, 0644);
                        } else {
                            $message = '文件移动失败，请检查目录权限';
                            // 删除数据库记录
                            $mysqli->query("DELETE FROM uploads WHERE filename = '$safe_filename'");
                        }
                    } else {
                        $message = '数据库记录失败: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $message = '数据库准备失败: ' . $mysqli->error;
                }
            }
            $mysqli->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>文件上传 - <?php echo htmlspecialchars($_SESSION['username']); ?></title>
    <style>
        /* 基础样式保持不变，添加移动端优化 */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px;
            }
            
            .directory-section {
                padding: 10px;
                margin: 10px 0;
            }
            
            .file-input input[type="file"] {
                width: 100%;
                padding: 10px;
            }
            
            .submit-btn {
                width: 100%;
                padding: 15px;
                font-size: 16px;
            }
            
            .user-info {
                padding: 8px;
                font-size: 14px;
            }
        }
        
        /* 平板优化 */
        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                max-width: 600px;
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            当前用户: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <a href="logout.php" class="logout">退出登录</a>
        </div>
        
        <h2>文件上传</h2>
        
        <?php if ($message !== ''): ?>
            <div class="message <?php echo $upload_success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <div class="directory-section">
                <h3>Markdown目录:</h3>
                <?php
                $md_tree = dir("./MARKDOWN");
                $md_dirs = array();
                while (($md_dir = $md_tree->read()) !== false) {
                    if ($md_dir != "." && $md_dir != "..") {
                        $md_dirs[] = $md_dir;
                    }
                }
                $md_tree->close();
                sort($md_dirs);
                
                foreach ($md_dirs as $dir) {
                    echo "<label><input type='radio' name='dir' value='0$dir' required> $dir</label><br>";
                }
                if (empty($md_dirs)) {
                    echo "<p>暂无Markdown目录</p>";
                }
                ?>
            </div>
            
            <div class="directory-section">
                <h3>PDF目录:</h3>
                <?php
                $pdf_tree = dir("./PDFS");
                $pdf_dirs = array();
                while (($pdf_dir = $pdf_tree->read()) !== false) {
                    if ($pdf_dir != "." && $pdf_dir != "..") {
                        $pdf_dirs[] = $pdf_dir;
                    }
                }
                $pdf_tree->close();
                sort($pdf_dirs);
                
                foreach ($pdf_dirs as $dir) {
                    echo "<label><input type='radio' name='dir' value='1$dir' required> $dir</label><br>";
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
</body>
</html>
