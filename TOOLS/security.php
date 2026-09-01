<?php
declare(strict_types=1);

/**
 * book 项目集中安全工具
 * 包含：数据库配置、会话安全、登录校验、CSRF 防护、路径穿越防护、输出转义
 */

/**
 * 读取数据库配置。优先读取站点根目录 config.php（返回数组），
 * 其次读取环境变量。config.php 不应提交到版本库（见 .gitignore）。
 */
function db_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $values = [];
    $configFile = dirname(__DIR__) . '/config.php';
    if (is_file($configFile)) {
        $loaded = require $configFile;
        if (is_array($loaded)) {
            $values = $loaded;
        }
    }

    $config = [
        'host' => $values['DB_HOST'] ?? (getenv('DB_HOST') ?: 'localhost'),
        'user' => $values['DB_USER'] ?? (getenv('DB_USER') ?: ''),
        'pass' => $values['DB_PASS'] ?? (getenv('DB_PASS') ?: ''),
        'name' => $values['DB_NAME'] ?? (getenv('DB_NAME') ?: 'book'),
    ];

    if ($config['user'] === '' || $config['pass'] === '') {
        error_log('[book] 数据库配置缺失，请复制 config.sample.php 为 config.php 并填写');
        http_response_code(500);
        exit('服务器配置错误：数据库未配置');
    }

    return $config;
}

function db_connect(): mysqli
{
    $cfg = db_config();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $mysqli = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    } catch (mysqli_sql_exception $e) {
        error_log('[book] 数据库连接失败: ' . $e->getMessage());
        http_response_code(500);
        exit('服务器暂时不可用，请稍后再试');
    }
}

/**
 * 安全地开启会话：设置 HttpOnly / SameSite / Secure(HTTPS 时) Cookie
 */
function sec_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name('BOOKSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * 要求已登录，否则 302 跳转到登录页
 */
function require_login(string $redirect = 'login.php'): void
{
    sec_session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * 供 AJAX 接口使用：未登录返回 401 JSON
 */
function require_login_api(): void
{
    sec_session_start();
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
}

/**
 * 生成/获取当前会话的 CSRF Token
 */
function csrf_token(): string
{
    sec_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 输出隐藏域，用于表单
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * 校验 POST 或 X-CSRF-Token 头中的 Token
 */
function verify_csrf(): void
{
    sec_session_start();
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('CSRF 校验失败，请返回重试');
    }
}

/**
 * HTML 输出转义
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * 判断单个路径段是否安全（禁止目录穿越与空字节）
 */
function valid_segment(string $segment): bool
{
    return $segment !== ''
        && $segment !== '.'
        && $segment !== '..'
        && strpos($segment, '/') === false
        && strpos($segment, '\\') === false
        && strpos($segment, "\0") === false;
}

/**
 * 安全解析站点内相对路径，防止目录穿越。
 * 成功返回规范化后的绝对路径，失败返回 null。
 */
function secure_realpath(string $base, string ...$segments): ?string
{
    foreach ($segments as $segment) {
        if (!valid_segment($segment)) {
            return null;
        }
    }

    $baseReal = realpath($base);
    if ($baseReal === false) {
        return null;
    }

    $full = realpath($baseReal . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments));
    if ($full === false) {
        return null;
    }

    // 确保解析后的路径仍位于基目录内
    if ($full !== $baseReal && strpos($full, $baseReal . DIRECTORY_SEPARATOR) !== 0) {
        return null;
    }

    return $full;
}
