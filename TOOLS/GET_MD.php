<?php
declare(strict_types=1);

require __DIR__ . '/security.php';
require_login_api();

$dir_name = (string)($_POST['DirName'] ?? '');
$md_name = (string)($_POST['MdName'] ?? '');

if (!valid_segment($dir_name) || !valid_segment($md_name)) {
    http_response_code(400);
    exit('bad request');
}

$file = secure_realpath(__DIR__ . '/../MARKDOWN', $dir_name, $md_name . '.md');
if ($file === null || !is_file($file)) {
    http_response_code(404);
    exit('not found');
}

header('Content-Type: text/plain; charset=utf-8');
readfile($file);
