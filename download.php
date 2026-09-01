<?php
declare(strict_types=1);

/**
 * 受登录保护的 PDF 下载/预览代理。
 * 使用方式：/download.php?dir=目录名&file=文件名.pdf[&inline=1]
 */

require __DIR__ . '/TOOLS/security.php';
require_login();

$dir = (string)($_GET['dir'] ?? '');
$file = (string)($_GET['file'] ?? '');
$inline = (($_GET['inline'] ?? '') === '1');

if ($file !== '' && strtolower(substr($file, -4)) !== '.pdf') {
    $file .= '.pdf';
}

$real = secure_realpath(__DIR__ . '/PDFS', $dir, $file);
if ($real === null || !is_file($real) || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(404);
    exit('文件不存在');
}

$name = basename($real);
$size = filesize($real);
if ($size < 1) {
    http_response_code(404);
    exit('文件不存在');
}

// 简单断点续传支持
$start = 0;
$end = $size - 1;
$length = $size;
$range = $_SERVER['HTTP_RANGE'] ?? '';

if ($range !== '' && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
    if ($m[1] === '' && $m[2] !== '') {
        $start = max(0, $size - (int)$m[2]);
    } else {
        $start = (int)$m[1];
        if ($m[2] !== '') {
            $end = min((int)$m[2], $size - 1);
        }
    }
    if ($start < 0) {
        $start = 0;
    }
    if ($start > $end || $start >= $size) {
        header('HTTP/1.1 416 Range Not Satisfiable');
        header('Content-Range: bytes */' . $size);
        exit;
    }
    $length = $end - $start + 1;
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
} else {
    header('HTTP/1.1 200 OK');
}

header('Accept-Ranges: bytes');
header('Content-Type: application/pdf');
header('Content-Length: ' . $length);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($name));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0');

$fp = fopen($real, 'rb');
if ($fp === false) {
    http_response_code(500);
    exit('文件读取失败');
}
fseek($fp, $start);
$remaining = $length;
while ($remaining > 0 && !feof($fp)) {
    $chunk = fread($fp, min(8192, $remaining));
    if ($chunk === false) {
        break;
    }
    echo $chunk;
    $remaining -= strlen($chunk);
}
fclose($fp);
exit;
