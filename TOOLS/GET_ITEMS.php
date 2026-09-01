<?php
declare(strict_types=1);

require __DIR__ . '/security.php';
require_login_api();

$dir_name = (string)($_POST['DirName'] ?? '');
$path_start = (string)($_POST['PathStart'] ?? '');

if (!valid_segment($dir_name)) {
    http_response_code(400);
    exit('bad request');
}

$isMarkdown = ($path_start === '/');
$base = __DIR__ . '/../' . ($isMarkdown ? 'MARKDOWN' : 'PDFS');

$dirReal = secure_realpath($base, $dir_name);
if ($dirReal === null || !is_dir($dirReal)) {
    http_response_code(400);
    exit('bad request');
}

$items = [];
foreach (scandir($dirReal) as $it) {
    if ($it === '.' || $it === '..') continue;
    if (!is_file($dirReal . DIRECTORY_SEPARATOR . $it)) continue;
    $items[] = $it;
}
usort($items, 'strnatcasecmp');

$items_list = '';
foreach ($items as $item) {
    if ($isMarkdown) {
        $items_list .= "<li class='md-items'>" . e($item) . "</li>";
    } else {
        $href = '/download.php?dir=' . rawurlencode($dir_name) . '&file=' . rawurlencode($item);
        $items_list .= "<li class='md-items'><a href=\"" . e($href) . "\" download>↓&nbsp;" . e($item) . "</a></li>";
    }
}

echo $items_list !== '' ? $items_list : "<li>尚未补充</li>";
