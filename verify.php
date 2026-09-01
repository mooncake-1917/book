<?php
declare(strict_types=1);

require __DIR__ . '/TOOLS/security.php';
sec_session_start();

$token = (string)($_GET['token'] ?? '');
$msg = '链接无效。';
$ok = false;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE email_token = ? LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($uid);
    if ($stmt->fetch()) {
        $stmt->close();
        $stmt = $mysqli->prepare('UPDATE users SET email_verified_at = NOW(), email_token = NULL WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $ok = true;
        $msg = '邮箱验证成功！请等待管理员审核通过后登录。';
    } else {
        $msg = '验证链接无效或已被使用。';
    }
    $stmt->close();
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮箱验证 - 实用技术知识库</title>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/AUTH.css?v=<?php echo time(); ?>" />
</head>
<body>
    <div class="login-container">
        <div class="login-header"><h1>邮箱验证</h1></div>
        <div class="message <?php echo $ok ? 'success' : 'error'; ?>"><?php echo e($msg); ?></div>
        <p class="auth-switch"><a href="login.php">返回登录</a></p>
    </div>
</body>
</html>
