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
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // 简单的暴力破解防护：同一会话 5 次失败后锁定 5 分钟
    $attempts = (int)($_SESSION['login_attempts'] ?? 0);
    $lastAttempt = (int)($_SESSION['login_last_attempt'] ?? 0);

    if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
        $message = '登录失败次数过多，请 5 分钟后再试';
    } elseif ($name === '' || $password === '') {
        $message = '请填写用户名和密码';
    } else {
        $mysqli = db_connect();
        $stmt = $mysqli->prepare('SELECT id, password FROM users WHERE name = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $stmt->bind_result($user_id, $db_password);
            $ok = $stmt->fetch() && password_verify($password, (string)$db_password);
            $stmt->close();
            $mysqli->close();

            if ($ok) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user_id;
                $_SESSION['username'] = $name;
                $_SESSION['login_time'] = time();
                unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
                header('Location: index.php');
                exit;
            }

            $_SESSION['login_attempts'] = $attempts + 1;
            $_SESSION['login_last_attempt'] = time();
            $message = '用户名或密码错误';
        } else {
            $mysqli->close();
            error_log('[book] 登录查询准备失败');
            $message = '服务器暂时不可用，请稍后再试';
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Source Han Sans SC Medium", Arial, sans-serif;
            background: linear-gradient(135deg, #6d7993 0%, #96858f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #6d7993;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-form .form-group {
            margin-bottom: 20px;
        }

        .login-form input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .login-form input:focus {
            outline: none;
            border-color: #6d7993;
            box-shadow: 0 0 0 3px rgba(109, 121, 147, 0.1);
        }

        .login-form button {
            width: 100%;
            padding: 15px;
            background: #6d7993;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .login-form button:hover {
            background: #5a6780;
            transform: translateY(-2px);
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .login-form input, .login-form button {
                padding: 12px;
                font-size: 16px;
            }
        }

        @media (min-width: 768px) and (max-width: 1024px) {
            .login-container {
                max-width: 450px;
                padding: 50px;
            }
        }
    </style>
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
                <input type="text" name="username" placeholder="用户名" value="<?php echo e($name); ?>" required autocomplete="username">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码" required autocomplete="current-password">
            </div>
            <button type="submit">登录</button>
        </form>
    </div>
</body>
</html>
