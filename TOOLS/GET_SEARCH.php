<?php
declare(strict_types=1);

require __DIR__ . '/security.php';
require_login_api();

$search_key = trim((string)($_POST['search-key'] ?? ''));
$search_type = (string)($_POST['search-type'] ?? 'page');

if ($search_key === '' || strlen($search_key) > 200) {
    http_response_code(400);
    exit('bad request');
}

$isPage = ($search_type === 'page');
$base = __DIR__ . '/../' . ($isPage ? 'MARKDOWN' : 'PDFS');

$esc_key = e($search_key);
$hl = '<span class="search-item-text-key">' . $esc_key . '</span>';

$dirs = [];
if (is_dir($base)) {
    foreach (scandir($base) as $d) {
        if ($d === '.' || $d === '..' || !is_dir($base . DIRECTORY_SEPARATOR . $d)) continue;
        $dirs[] = $d;
    }
}
usort($dirs, 'strnatcasecmp');

$RETURN_TEXT = '';
$search_bool = false;

foreach ($dirs as $dir) {
    $dirReal = secure_realpath($base, $dir);
    if ($dirReal === null || !is_dir($dirReal)) continue;

    $items = [];
    foreach (scandir($dirReal) as $it) {
        if ($it === '.' || $it === '..') continue;
        if (!is_file($dirReal . DIRECTORY_SEPARATOR . $it)) continue;
        $items[] = $it;
    }
    usort($items, 'strnatcasecmp');

    foreach ($items as $item) {
        if ($isPage) {
            if (strtolower(pathinfo($item, PATHINFO_EXTENSION)) !== 'md') continue;

            $content = (string)file_get_contents($dirReal . DIRECTORY_SEPARATOR . $item);
            if (strpos($content, $search_key) === false) continue;

            $search_bool = true;
            $RETURN_TEXT .= "<div class=\"search-item\">";
            $RETURN_TEXT .= "<div class=\"search-item-title\">" . e($item) . "</div>";
            $RETURN_TEXT .= "<div class=\"search-item-link\">&gt;&gt;&nbsp;" . e($dir) . "&nbsp;&gt;&nbsp;" . e($item) . "</div>";
            $RETURN_TEXT .= "<div class=\"search-item-text\">";

            $lines = preg_split('/\R/', $content);
            $shown = 0;
            foreach ($lines as $line) {
                if (strpos($line, $search_key) === false) continue;
                if ($shown >= 3) break;
                $shown++;
                $esc_line = e($line);
                $RETURN_TEXT .= "<p>" . str_replace($esc_key, $hl, $esc_line) . "</p>";
            }

            $RETURN_TEXT .= "</div>";
            $RETURN_TEXT .= "<div class=\"search-item-tree\">" . e($dir) . "</div>";
            $RETURN_TEXT .= "</div>";
        } else {
            if (strpos($item, $search_key) === false) continue;

            $search_bool = true;
            $href = '/download.php?dir=' . rawurlencode($dir) . '&file=' . rawurlencode($item);
            $RETURN_TEXT .= "<div class=\"search-item\">";
            $RETURN_TEXT .= "<div class=\"search-item-title\">" . str_replace($esc_key, $hl, e($item)) . "</div>";
            $RETURN_TEXT .= "<br />";
            $RETURN_TEXT .= "<div class=\"search-item-link\"><a href=\"" . e($href) . "\" download>↓&nbsp;" . e($dir) . "&nbsp;&gt;&nbsp;" . e($item) . "</a></div>";
            $RETURN_TEXT .= "<div class=\"search-item-tree\">" . e($dir) . "</div>";
            $RETURN_TEXT .= "</div>";
        }
    }
}

if (!$search_bool) {
    $RETURN_TEXT .= "<div class=\"search-item\">";
    $RETURN_TEXT .= "<div class=\"search-item-title\">搜索不到相关内容！</div>";
    $RETURN_TEXT .= "</div>";
}

echo $RETURN_TEXT;
