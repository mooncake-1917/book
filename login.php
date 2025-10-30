<?php
// 启动会话
session_start();

// 处理表单提交和数据库查询
$message = '';
$name = '';
$password = '';

// 如果用户已经登录，重定向到主页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // 基本验证
    if (empty($name) || empty($password)) {
        $message = '请填写用户名和密码';
    } else {
        $localhost = 'localhost';
        $db_user = 'user';
        $db_pass = '19207572133';
        $db_name = 'book';

        $mysqli = new mysqli($localhost, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_errno) {
            $message = '数据库连接失败: ' . $mysqli->connect_error;
        } else {
            // 使用预处理语句安全地获取用户信息
            $stmt = $mysqli->prepare('SELECT id, password FROM users WHERE name = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $stmt->bind_result($user_id, $db_password);
                if ($stmt->fetch()) {
                    // 验证密码（假设密码已使用password_hash()加密）
                    if (password_verify($password, $db_password)) {
                        // 登录成功，设置会话
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $name;
                        $_SESSION['login_time'] = time();
                        
                        $message = '登录成功！正在跳转...';
                        
                        // 延迟重定向以便显示成功消息
                        header('Refresh: 2; URL=index.php');
                    } else {
                        $message = '密码错误';
                    }
                } else {
                    $message = '用户不存在';
                }
                $stmt->close();
            } else {
                $message = '查询准备失败: ' . $mysqli->error;
            }
            $mysqli->close();
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
        
        /* 移动端优化 */
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
        
        /* 平板优化 */
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
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="post" action="">
            <div class="form-group">
                <input type="text" name="username" placeholder="用户名" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码" required>
            </div>
            <button type="submit">登录</button>
        </form>
    </div>
</body>
</html>
