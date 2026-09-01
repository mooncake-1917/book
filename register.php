<?php
declare(strict_types=1);

require __DIR__ . '/TOOLS/security.php';
require __DIR__ . '/TOOLS/mail.php';

sec_session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$success = false;
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string)($_POST['username'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $message = '请填写所有必填项';
    } elseif (!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]{3,32}$/u', $name)) {
        $message = '用户名需为 3-32 位中英文、数字或下划线';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '邮箱格式不正确';
    } elseif (strlen($password) < 8) {
        $message = '密码至少 8 位';
    } elseif ($password !== $confirm) {
        $message = '两次输入的密码不一致';
    } else {
        $mysqli = db_connect();
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE name = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $name, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = '用户名或邮箱已被注册';
        } else {
            $stmt->close();
            $token = bin2hex(random_bytes(32));
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO users (name, email, password, role, status, email_token) VALUES (?, ?, ?, \'user\', \'pending\', ?)');
            $stmt->bind_param('ssss', $name, $email, $hash, $token);
            if ($stmt->execute()) {
                $verifyUrl = site_base_url() . '/verify.php?token=' . rawurlencode($token);
                $html = '<h2>欢迎注册「实用技术知识库」</h2>'
                      . '<p>请点击下方按钮验证邮箱：</p>'
                      . '<p><a href="' . e($verifyUrl) . '" style="display:inline-block;padding:10px 18px;background:#6d7993;color:#fff;border-radius:8px;text-decoration:none">验证邮箱</a></p>'
                      . '<p>如果按钮无法点击，请复制以下链接到浏览器：<br>' . e($verifyUrl) . '</p>'
                      . '<p>验证后需管理员审核通过方可登录。</p>';
                [$ok, $err] = send_email_via_resend($email, '请验证您的邮箱 - 实用技术知识库', $html);
                if ($ok) {
                    $message = '注册成功！验证邮件已发送，请查收邮箱。验证后等待管理员审核。';
                } else {
                    $message = '注册成功，但验证邮件发送失败（' . e($err) . '）。请联系管理员。';
                }
                $success = true;
            } else {
                $message = '注册失败，请稍后重试';
            }
        }
        $stmt->close();
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>注册 - 实用技术知识库</title>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/AUTH.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/APP.css?v=<?php echo time(); ?>" />
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>注册账号</h1>
            <p>注册后需邮箱验证，并由管理员审核通过</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>"><?php echo e($message); ?></div>
        <?php endif; ?>

        <form class="login-form" method="post" action="">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <input type="text" name="username" placeholder="用户名（3-32 位中英文/数字/下划线）" value="<?php echo e($name); ?>" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="邮箱" value="<?php echo e($email); ?>" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码（至少 8 位）" required>
            </div>
            <div class="form-group">
                <input type="password" name="password_confirm" placeholder="确认密码" required>
            </div>
            <button type="submit">注册</button>
        </form>
        <p class="auth-switch">已有账号？<a href="login.php">去登录</a></p>
    </div>
</body>
</html>
