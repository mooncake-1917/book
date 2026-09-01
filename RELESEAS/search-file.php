<?php
declare(strict_types=1);

require __DIR__ . '/../TOOLS/security.php';
require_login();

$search_key = trim((string)($_POST['search-key'] ?? ''));
$esc_key = e($search_key);
$hl = '<span class="search-item-text-key">' . $esc_key . '</span>';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>搜索文件-<?php echo $esc_key; ?></title>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/SEARCH.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/APP.css?v=<?php echo time(); ?>" />
</head>
<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"'; ?>>
    <div id="search">
        <input id="search-text" type="text" value="<?php echo $esc_key; ?>" placeholder="请输入您要搜索的内容" />
        <button id="search-submit">搜索</button>
        <input type="checkbox" id="theme" <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'checked'; ?> />
    </div>
    <div id="main">
        <?php
        if ($search_key === '' || strlen($search_key) > 200) {
            echo "<div class=\"search-item\">";
            echo "<div class=\"search-item-title\">请输入搜索关键词</div>";
            echo "</div>";
        } else {
            $base = __DIR__ . '/../PDFS';
            $dirs = [];
            if (is_dir($base)) {
                foreach (scandir($base) as $d) {
                    if ($d === '.' || $d === '..' || !is_dir($base . DIRECTORY_SEPARATOR . $d)) continue;
                    $dirs[] = $d;
                }
            }
            usort($dirs, 'strnatcasecmp');

            $search_bool = false;

            foreach ($dirs as $dir) {
                $dirReal = secure_realpath($base, $dir);
                if ($dirReal === null || !is_dir($dirReal)) continue;

                $items = [];
                foreach (scandir($dirReal) as $it) {
                    if ($it === '.' || $it === '..' || !is_file($dirReal . DIRECTORY_SEPARATOR . $it)) continue;
                    if (strtolower(pathinfo($it, PATHINFO_EXTENSION)) !== 'pdf') continue;
                    $items[] = $it;
                }
                usort($items, 'strnatcasecmp');

                foreach ($items as $item) {
                    if (strpos($item, $search_key) === false) continue;

                    $search_bool = true;
                    $href = '/download.php?dir=' . rawurlencode($dir) . '&file=' . rawurlencode($item);
                    echo "<div class=\"search-item\">";
                    echo "<div class=\"search-item-title\">" . str_replace($esc_key, $hl, e($item)) . "</div>";
                    echo "<br />";
                    echo "<div class=\"search-item-link\"><a href=\"" . e($href) . "\" download>↓&nbsp;" . e($dir) . "&nbsp;&gt;&nbsp;" . e($item) . "</a></div>";
                    echo "<div class=\"search-item-tree\">" . e($dir) . "</div>";
                    echo "</div>";
                }
            }

            if (!$search_bool) {
                echo "<div class=\"search-item\">";
                echo "<div class=\"search-item-title\">搜索不到相关内容！</div>";
                echo "</div>";
            }
        }
        ?>
    </div>
    <script type="text/javascript" src="/STATIC/JS/THEME.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="/STATIC/JS/SEARCH.js?v=<?php echo time(); ?>"></script>
</body>
</html>
