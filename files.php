<?php
declare(strict_types=1);

require __DIR__ . '/TOOLS/security.php';
require_login();

$request_uri = (string)($_SERVER['REQUEST_URI'] ?? '/files/');
$file_path = urldecode(explode('?', $request_uri)[0]);
$file_dir = '';
$file_pdf = '';

$trimmed = rtrim($file_path, '/');
if ($trimmed !== '' && $trimmed !== '/files') {
    $parts = array_values(array_filter(explode('/', $file_path), static function ($p) {
        return $p !== '';
    }));
    if (($parts[0] ?? '') === 'files') {
        $file_dir = (string)($parts[1] ?? '');
        $file_pdf = (string)($parts[2] ?? '');
    }
}

if ($file_pdf !== '' && strtolower(substr($file_pdf, -4)) !== '.pdf') {
    $file_pdf .= '.pdf';
}

$pdfBase = __DIR__ . '/PDFS';
$pdfReal = null;
if ($file_dir !== '' && $file_pdf !== '') {
    $candidate = secure_realpath($pdfBase, $file_dir, $file_pdf);
    if ($candidate !== null && is_file($candidate) && strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) === 'pdf') {
        $pdfReal = $candidate;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>实用技术知识库</title>
    <script src="/PDFObject/pdfobject.min.js?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/CORE.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/ROOT.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/DARK.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/APP.css?v=<?php echo time(); ?>" />
</head>
<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"'; ?>>
    <div id="top">
        <h1>实用技术知识库</h1>
        <div id="side-bool">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="<?php if (isset($_COOKIE["theme"]) && $_COOKIE['theme'] == 'dark') echo '#eac67a'; else echo '#d5d5d5'; ?>" d="M10 15h10v2H10zm-6 4h16v2H4zm6-8h10v2H10zm0-4h10v2H10zM4 3h16v2H4zm0 5v8l4-4z" id="side-bool-ico" /></svg>
        </div>
        <form id="search" action="/RELEASES/search-file.php" method="post" onsubmit="return onSubmit()">
            <input id="search-key" name="search-key" type="input" placeholder="请输入您要搜索的内容" />
            <button id="search-submit" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 48 48"><g style="transition: all 1s;" fill="none" stroke="<?php if (isset($_COOKIE["theme"]) && $_COOKIE['theme'] == 'dark') echo '#eac67a'; else echo '#d5d5d5'; ?>" stroke-linejoin="round" stroke-width="4" id="search-ico" ><path d="M21 38c9.389 0 17-7.611 17-17S30.389 4 21 4S4 11.611 4 21s7.611 17 17 17Z"/><path stroke-linecap="round" d="M26.657 14.343A7.98 7.98 0 0 0 21 12a7.98 7.98 0 0 0-5.657 2.343m17.879 18.879l8.485 8.485"/></g></svg>
            </button>
        </form>
        <input type="checkbox" id="theme" <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'checked'; ?> />
    </div>
    <div id="index">
        <div id="side">
            <ul id="tree">
                <?php
                $pdfBaseReal = realpath($pdfBase);
                $dirs = [];
                if ($pdfBaseReal !== false && is_dir($pdfBaseReal)) {
                    foreach (scandir($pdfBaseReal) as $d) {
                        if ($d === '.' || $d === '..') continue;
                        if (!is_dir($pdfBaseReal . DIRECTORY_SEPARATOR . $d)) continue;
                        $dirs[] = $d;
                    }
                }
                usort($dirs, 'strnatcasecmp');
                foreach ($dirs as $d) {
                    if ($d === $file_dir) {
                        echo "<li class='md-dir fuc'>" . e($d) . "</li>";
                    } else {
                        echo "<li class='md-dir'>" . e($d) . "</li>";
                    }
                }
                ?>
                <li onclick="window.location.href = '/'">文档中心</li>
            </ul>
            <ul id="items">
                <?php
                if ($file_dir !== '') {
                    $pdfDirReal = secure_realpath($pdfBase, $file_dir);
                    $items_list = '';
                    if ($pdfDirReal !== null && is_dir($pdfDirReal)) {
                        $items = [];
                        foreach (scandir($pdfDirReal) as $it) {
                            if ($it === '.' || $it === '..') continue;
                            if (!is_file($pdfDirReal . DIRECTORY_SEPARATOR . $it)) continue;
                            if (strtolower(pathinfo($it, PATHINFO_EXTENSION)) !== 'pdf') continue;
                            $items[] = $it;
                        }
                        usort($items, 'strnatcasecmp');
                        foreach ($items as $it) {
                            $active = ($it === $file_pdf) ? ' fuc' : '';
                            $href = '/download.php?dir=' . rawurlencode($file_dir) . '&file=' . rawurlencode($it);
                            $items_list .= "<li class='md-items" . $active . "'><a href=\"" . e($href) . "\" download>↓&nbsp;" . e($it) . "</a></li>";
                        }
                    }
                    echo $items_list !== '' ? $items_list : "<li>尚未补充</li>";
                }
                ?>
            </ul>
        </div>
        <div id="main">
            <?php
            if ($file_dir !== '') {
                if ($pdfReal !== null) {
                    echo '<div id="ifPdf"></div>';
                } else {
                    echo '<div class="content-card"><h1>文件不存在</h1><p>您访问的 PDF 不存在或已被移除。</p></div>';
                }
            } else {
                echo '<div class="content-card">';
                echo "<h1>实用技术知识库-文件中心</h1>";
                echo "<p>本站旨在为用户提供一些技术知识内容的电子书籍，帮助用户解决一部分问题。</p>";
                echo "<p>本站不享有文件的版权，文件作者保留一切权利，如有侵权，本站将迅速删除。</p>";
                echo '</div>';
            }
            ?>
        </div>
    </div>
    <?php if ($pdfReal !== null): ?>
    <script>
        PDFObject.embed(<?php echo json_encode('/download.php?dir=' . rawurlencode($file_dir) . '&file=' . rawurlencode($file_pdf) . '&inline=1', JSON_UNESCAPED_SLASHES); ?>, "#ifPdf");
    </script>
    <?php endif; ?>
    <div id="user-info">
        <span class="user-name">👤 <?php echo e($_SESSION['username'] ?? ''); ?></span>
        <a href="messages.php">私信</a>
        <a href="upload.php">上传文件</a>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?><a href="admin.php">审核</a><?php endif; ?>
        <a href="logout.php" class="logout" onclick="return confirm('确定要退出登录吗？')">退出</a>
    </div>
    <script type="text/javascript" src="/STATIC/JS/THEME.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="/STATIC/JS/SIDE.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="/STATIC/JS/MARKDOWNSEARCH.js?v=<?php echo time(); ?>"></script>
</body>
</html>
