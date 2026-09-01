<?php
declare(strict_types=1);

/**
 * book 项目集中安全与基础设施工具
 * 包含：配置读取、数据库、会话（可 Redis）、登录校验、CSRF、路径穿越防护、限流
 */

require_once __DIR__ . '/redis.php';

function site_config(): array
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
        'DB_HOST' => (string)($values['DB_HOST'] ?? (getenv('DB_HOST') ?: 'localhost')),
        'DB_USER' => (string)($values['DB_USER'] ?? (getenv('DB_USER') ?: '')),
        'DB_PASS' => (string)($values['DB_PASS'] ?? (getenv('DB_PASS') ?: '')),
        'DB_NAME' => (string)($values['DB_NAME'] ?? (getenv('DB_NAME') ?: 'book')),
        'SITE_URL' => rtrim((string)($values['SITE_URL'] ?? (getenv('SITE_URL') ?: '')), '/'),
        'RESEND_API_KEY' => (string)($values['RESEND_API_KEY'] ?? (getenv('RESEND_API_KEY') ?: '')),
        'RESEND_FROM' => (string)($values['RESEND_FROM'] ?? (getenv('RESEND_FROM') ?: '')),
        'REDIS_HOST' => (string)($values['REDIS_HOST'] ?? (getenv('REDIS_HOST') ?: '127.0.0.1')),
        'REDIS_PORT' => (int)($values['REDIS_PORT'] ?? (getenv('REDIS_PORT') ?: 6379)),
        'REDIS_PASS' => (string)($values['REDIS_PASS'] ?? (getenv('REDIS_PASS') ?: '')),
        'REDIS_PREFIX' => (string)($values['REDIS_PREFIX'] ?? (getenv('REDIS_PREFIX') ?: 'book:')),
    ];

    return $config;
}

function site_base_url(): string
{
    $c = site_config();
    if ($c['SITE_URL'] !== '') {
        return $c['SITE_URL'];
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host;
}

function db_config(): array
{
    $c = site_config();
    if ($c['DB_USER'] === '' || $c['DB_PASS'] === '') {
        error_log('[book] 数据库配置缺失，请复制 config.sample.php 为 config.php 并填写');
        http_response_code(500);
        exit('服务器配置错误：数据库未配置');
    }

    return [
        'host' => $c['DB_HOST'],
        'user' => $c['DB_USER'],
        'pass' => $c['DB_PASS'],
        'name' => $c['DB_NAME'],
    ];
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

    // 若 Redis 可用，使用 Redis 存储会话；否则自动回退到文件会话
    redis_session_setup();

    session_start();
}

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function require_login(string $redirect = 'login.php'): void
{
    sec_session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('需要管理员权限');
    }
}

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

function csrf_token(): string
{
    sec_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    sec_session_start();
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('CSRF 校验失败，请返回重试');
    }
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function valid_segment(string $segment): bool
{
    return $segment !== ''
        && $segment !== '.'
        && $segment !== '..'
        && strpos($segment, '/') === false
        && strpos($segment, '\\') === false
        && strpos($segment, "\0") === false;
}

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

    if ($full !== $baseReal && strpos($full, $baseReal . DIRECTORY_SEPARATOR) !== 0) {
        return null;
    }

    return $full;
}

/**
 * 登录限流：优先使用 Redis（全局），不可用时回退到会话计数。
 */
function login_throttle_check(string $identifier): bool
{
    $r = redis_client();
    if ($r !== null) {
        return (int)$r->get('login:' . md5($identifier)) >= 5;
    }

    sec_session_start();
    $attempts = (int)($_SESSION['login_attempts'] ?? 0);
    $last = (int)($_SESSION['login_last_attempt'] ?? 0);
    return $attempts >= 5 && (time() - $last) < 300;
}

function login_throttle_record(string $identifier): void
{
    $r = redis_client();
    if ($r !== null) {
        $key = 'login:' . md5($identifier);
        $count = $r->incr($key);
        if ($count === 1) {
            $r->expire($key, 300);
        }
        return;
    }

    sec_session_start();
    $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_last_attempt'] = time();
}
