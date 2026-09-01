<?php
declare(strict_types=1);

require __DIR__ . '/TOOLS/security.php';

sec_session_start();

// 已登录则跳转
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $login = trim((string)($_POST['login'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $throttleId = strtolower($login) . '|' . (string)($_SERVER['REMOTE_ADDR'] ?? '');

    if ($login === '' || $password === '') {
        $message = '请填写用户名/邮箱和密码';
    } elseif (login_throttle_check($throttleId)) {
        $message = '登录失败次数过多，请 5 分钟后再试';
    } else {
        $mysqli = db_connect();
        $stmt = $mysqli->prepare('SELECT id, name, password, role, status, email_verified_at FROM users WHERE name = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $stmt->bind_result($uid, $uname, $db_password, $role, $status, $verifiedAt);
        $found = $stmt->fetch();
        $stmt->close();
        $mysqli->close();

        if (!$found || !password_verify($password, (string)$db_password)) {
            login_throttle_record($throttleId);
            $message = '用户名/邮箱或密码错误';
        } elseif ($verifiedAt === null) {
            $message = '请先前往邮箱完成验证';
        } elseif ($status === 'pending') {
            $message = '账号待管理员审核，请耐心等待';
        } elseif ($status === 'rejected') {
            $message = '账号注册申请已被拒绝';
        } elseif ($status === 'disabled') {
            $message = '账号已被禁用';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$uid;
            $_SESSION['username'] = (string)$uname;
            $_SESSION['role'] = (string)$role;
            $_SESSION['login_time'] = time();
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>登录 - 实用技术知识库</title>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/AUTH.css?v=<?php echo time(); ?>" />
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>实用技术知识库</h1>
            <p>请登录您的账户</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="message <?php echo strpos($message, '成功') !== false ? 'success' : 'error'; ?>">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="post" action="">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <input type="text" name="login" placeholder="用户名或邮箱" value="<?php echo e($login); ?>" required autocomplete="username">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码" required autocomplete="current-password">
            </div>
            <button type="submit">登录</button>
        </form>
        <p class="auth-switch">还没有账号？<a href="register.php">注册</a></p>
    </div>
</body>
</html>
