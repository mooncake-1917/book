<?php
declare(strict_types=1);

require __DIR__ . '/TOOLS/security.php';
require_login();

// ---------- 解析请求路径 ----------
$request_uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$page_path = urldecode(explode('?', $request_uri)[0]);
$page_dir = '';
$page_md = '';

if (rtrim($page_path, '/') !== '') {
    $parts = array_values(array_filter(explode('/', $page_path), static function ($p) {
        return $p !== '';
    }));
    // 形如 /目录名/文件名.md
    if (($parts[0] ?? '') !== 'index.php') {
        $page_dir = (string)($parts[0] ?? '');
        $page_md = (string)($parts[1] ?? '');
    }
}

if ($page_md !== '' && strpos($page_md, '.md') === false) {
    $page_md .= '.md';
}

// ---------- 读取并渲染 Markdown ----------
require __DIR__ . '/TOOLS/Parsedown.php';

$markdownBase = __DIR__ . '/MARKDOWN';
$rendered = '';

if ($page_dir === '' || $page_md === '') {
    $hello = __DIR__ . '/hello.md';
    $content = is_file($hello) ? (string)file_get_contents($hello) : '';
    $Parsedown = new Parsedown();
    $Parsedown->setMarkupEscaped(true); // 防止 markdown 中的原始 HTML 造成 XSS
    $rendered = $Parsedown->text($content);
} else {
    $mdFile = secure_realpath($markdownBase, $page_dir, $page_md);
    if ($mdFile === null || !is_file($mdFile) || strtolower(pathinfo($mdFile, PATHINFO_EXTENSION)) !== 'md') {
        $rendered = '<h1>页面未找到</h1><p>您访问的文档不存在，<a href="/">返回首页</a></p>';
    } else {
        $Parsedown = new Parsedown();
        $Parsedown->setMarkupEscaped(true);
        $rendered = $Parsedown->text((string)file_get_contents($mdFile));
    }
}
?>
<!DOCTYPE html>
<html lang="zh_CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>实用技术知识库</title>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/ROOT.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/CORE.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/DARK.css?v=<?php echo time(); ?>" />
</head>
<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"'; ?>>
    <div id="top">
        <h1>实用技术知识库</h1>
        <div id="side-bool">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="<?php if (isset($_COOKIE["theme"]) && $_COOKIE['theme'] == 'dark') echo '#eac67a'; else echo '#d5d5d5'; ?>" d="M10 15h10v2H10zm-6 4h16v2H4zm6-8h10v2H10zm0-4h10v2H10zM4 3h16v2H4zm0 5v8l4-4z" id="side-bool-ico" /></svg>
        </div>
        <form id="search" action="/RELEASES/search.php" method="post" onsubmit="return onSubmit()">
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
                $markdownBaseReal = realpath($markdownBase);
                $dirs = [];
                if ($markdownBaseReal !== false && is_dir($markdownBaseReal)) {
                    foreach (scandir($markdownBaseReal) as $d) {
                        if ($d === '.' || $d === '..') continue;
                        if (!is_dir($markdownBaseReal . DIRECTORY_SEPARATOR . $d)) continue;
                        $dirs[] = $d;
                    }
                }
                usort($dirs, 'strnatcasecmp');
                foreach ($dirs as $d) {
                    if ($d === $page_dir) {
                        echo "<li class='md-dir fuc'>" . e($d) . "</li>";
                    } else {
                        echo "<li class='md-dir'>" . e($d) . "</li>";
                    }
                }
                ?>
                <li onclick="window.location.href = '/files/'">文件中心</li>
            </ul>
            <ul id="items">
                <?php
                if ($page_dir !== '') {
                    $pageDirReal = secure_realpath($markdownBase, $page_dir);
                    $items_list = '';
                    if ($pageDirReal !== null && is_dir($pageDirReal)) {
                        $items = [];
                        foreach (scandir($pageDirReal) as $it) {
                            if ($it === '.' || $it === '..') continue;
                            if (!is_file($pageDirReal . DIRECTORY_SEPARATOR . $it)) continue;
                            $items[] = $it;
                        }
                        usort($items, 'strnatcasecmp');
                        foreach ($items as $it) {
                            $items_list .= "<li class='md-items'>" . e($it) . "</li>";
                        }
                    }
                    echo $items_list !== '' ? $items_list : "<li>尚未补充</li>";
                }
                ?>
            </ul>
        </div>
        <div id="main">
            <?php echo $rendered; ?>
        </div>
    </div>
    <script type="text/javascript" src="/STATIC/JS/THEME.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="/STATIC/JS/SIDE.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="/STATIC/JS/MARKDOWNSEARCH.js?v=<?php echo time(); ?>"></script>
</body>
</html>
<div id="user-info">
    <span>欢迎，<?php echo e($_SESSION['username'] ?? ''); ?></span>
    <a href="logout.php" onclick="return confirm('确定要退出登录吗？')">退出</a>
</div>
