<?php
declare(strict_types=1);

require __DIR__ . '/TOOLS/security.php';
require_admin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $uid = (int)($_POST['user_id'] ?? 0);

    if ($uid > 0 && in_array($action, ['approve', 'reject'], true)) {
        $mysqli = db_connect();
        $status = ($action === 'approve') ? 'active' : 'rejected';
        $stmt = $mysqli->prepare('UPDATE users SET status = ? WHERE id = ? AND status = \'pending\'');
        $stmt->bind_param('si', $status, $uid);
        $stmt->execute();
        $stmt->close();
        $mysqli->close();
        $message = '已更新用户状态';
    }
}

$pending = [];
$mysqli = db_connect();
$res = $mysqli->query('SELECT id, name, email, created_at FROM users WHERE status = \'pending\' ORDER BY created_at ASC');
while ($row = $res->fetch_assoc()) {
    $pending[] = $row;
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>用户审核 - 实用技术知识库</title>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/APP.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/ACCOUNT.css?v=<?php echo time(); ?>" />
</head>
<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"'; ?>>
<div class="account-container">
    <div class="account-card">
        <h1>用户审核</h1>
        <?php if ($message !== ''): ?>
            <div class="message success"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if (empty($pending)): ?>
            <p>暂无待审核用户</p>
        <?php else: ?>
            <table class="account-table">
                <tr><th>用户名</th><th>邮箱</th><th>注册时间</th><th>操作</th></tr>
                <?php foreach ($pending as $u): ?>
                <tr>
                    <td><?php echo e($u['name']); ?></td>
                    <td><?php echo e($u['email']); ?></td>
                    <td><?php echo e($u['created_at']); ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                            <button class="btn" type="submit" name="action" value="approve">通过</button>
                            <button class="btn danger" type="submit" name="action" value="reject">拒绝</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <p><a class="btn ghost" href="index.php">← 返回首页</a></p>
</div>
</body>
</html>
