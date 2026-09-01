<?php
declare(strict_types=1);

require __DIR__ . '/TOOLS/security.php';
require __DIR__ . '/TOOLS/mail.php';
require_login();

$me = current_user_id();
$meName = (string)($_SESSION['username'] ?? '');
$message = '';
$viewWith = (int)($_GET['with'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $recipient = trim((string)($_POST['recipient'] ?? ''));
    $body = trim((string)($_POST['body'] ?? ''));
    $tooLong = function_exists('mb_strlen') ? mb_strlen($body, 'UTF-8') > 2000 : strlen($body) > 6000;

    if ($recipient === '' || $body === '') {
        $message = '请填写收件人和内容';
    } elseif ($tooLong) {
        $message = '内容过长（最多 2000 字）';
    } else {
        $mysqli = db_connect();
        $stmt = $mysqli->prepare('SELECT id, email FROM users WHERE name = ? LIMIT 1');
        $stmt->bind_param('s', $recipient);
        $stmt->execute();
        $stmt->bind_result($rid, $remail);
        if (!$stmt->fetch()) {
            $message = '收件人不存在';
        } elseif ($rid === $me) {
            $message = '不能给自己发私信';
        } else {
            $stmt->close();
            $stmt = $mysqli->prepare('INSERT INTO messages (sender_id, recipient_id, body) VALUES (?, ?, ?)');
            $stmt->bind_param('iis', $me, $rid, $body);
            if ($stmt->execute()) {
                $message = '私信已发送';
                $viewWith = (int)$rid;

                // 邮件通知（若 Resend 已配置）
                $html = '<h2>您收到一条新私信</h2>'
                      . '<p>来自：' . e($meName) . '</p>'
                      . '<p style="white-space:pre-wrap">' . nl2br(e($body)) . '</p>'
                      . '<p><a href="' . e(site_base_url() . '/messages.php?with=' . $me) . '">查看私信</a></p>';
                send_email_via_resend((string)$remail, '您在知识库收到一条私信', $html);
            } else {
                $message = '发送失败，请稍后重试';
            }
        }
        $stmt->close();
        $mysqli->close();
    }
}

// 会话列表
$conversations = [];
$mysqli = db_connect();
$stmt = $mysqli->prepare(
    'SELECT CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END AS other_id,
            MAX(created_at) AS last_time
       FROM messages
      WHERE sender_id = ? OR recipient_id = ?
      GROUP BY other_id
      ORDER BY last_time DESC'
);
$stmt->bind_param('iii', $me, $me, $me);
$stmt->execute();
$res = $stmt->get_result();
$otherIds = [];
while ($row = $res->fetch_assoc()) {
    $otherIds[] = (int)$row['other_id'];
}
$stmt->close();

foreach ($otherIds as $oid) {
    $name = '用户#' . $oid;
    $stmt = $mysqli->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->bind_param('i', $oid);
    $stmt->execute();
    $stmt->bind_result($uname);
    if ($stmt->fetch()) {
        $name = (string)$uname;
    }
    $stmt->close();

    $lastBody = '';
    $lastTime = '';
    $stmt = $mysqli->prepare(
        'SELECT body, created_at FROM messages
          WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
          ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->bind_param('iiii', $me, $oid, $oid, $me);
    $stmt->execute();
    $stmt->bind_result($lbody, $ltime);
    if ($stmt->fetch()) {
        $lastBody = (string)$lbody;
        $lastTime = (string)$ltime;
    }
    $stmt->close();

    $unread = 0;
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM messages WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL');
    $stmt->bind_param('ii', $oid, $me);
    $stmt->execute();
    $stmt->bind_result($unread);
    $stmt->fetch();
    $stmt->close();

    $conversations[] = [
        'id' => $oid,
        'name' => $name,
        'last_body' => $lastBody,
        'last_time' => $lastTime,
        'unread' => (int)$unread,
    ];
}
$mysqli->close();

// 会话详情
$thread = [];
$otherName = '';
if ($viewWith > 0 && $viewWith !== $me) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->bind_param('i', $viewWith);
    $stmt->execute();
    $stmt->bind_result($oname);
    if ($stmt->fetch()) {
        $otherName = (string)$oname;
    }
    $stmt->close();

    // 标记已读
    $stmt = $mysqli->prepare('UPDATE messages SET read_at = NOW() WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL');
    $stmt->bind_param('ii', $viewWith, $me);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare(
        'SELECT sender_id, recipient_id, body, created_at, read_at
           FROM messages
          WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
          ORDER BY created_at ASC
          LIMIT 200'
    );
    $stmt->bind_param('iiii', $me, $viewWith, $viewWith, $me);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $thread[] = $row;
    }
    $stmt->close();
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>私信 - 实用技术知识库</title>
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/APP.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" type="text/css" href="/STATIC/CSS/ACCOUNT.css?v=<?php echo time(); ?>" />
</head>
<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"'; ?>>
<div class="account-container">
    <div class="account-card">
        <h1>私信</h1>
        <?php if ($message !== ''): ?>
            <div class="message <?php echo (strpos($message, '已发送') !== false) ? 'success' : 'error'; ?>"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if ($viewWith > 0 && $otherName !== ''): ?>
            <h2>与 <?php echo e($otherName); ?> 的对话</h2>
            <div class="thread">
                <?php foreach ($thread as $m): ?>
                    <?php $mine = ((int)$m['sender_id'] === $me); ?>
                    <div class="msg <?php echo $mine ? 'me' : 'other'; ?>">
                        <?php echo nl2br(e($m['body'])); ?>
                        <span class="meta"><?php echo e($m['created_at']); ?><?php echo $mine && $m['read_at'] !== null ? ' · 已读' : ''; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" style="margin-top:16px">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="recipient" value="<?php echo e($otherName); ?>">
                <textarea class="field" name="body" placeholder="回复内容（最多 2000 字）" required></textarea>
                <button class="btn" type="submit" style="margin-top:10px">发送回复</button>
            </form>
            <p style="margin-top:14px"><a class="btn ghost" href="messages.php">← 返回私信列表</a></p>
        <?php else: ?>
            <h2>我的会话</h2>
            <?php if (empty($conversations)): ?>
                <p>暂无私信</p>
            <?php else: ?>
                <?php foreach ($conversations as $c): ?>
                    <?php $preview = function_exists('mb_strimwidth') ? mb_strimwidth((string)$c['last_body'], 0, 60, '…') : substr((string)$c['last_body'], 0, 60); ?>
                    <a class="inbox-item" href="messages.php?with=<?php echo (int)$c['id']; ?>">
                        <span>
                            <strong><?php echo e($c['name']); ?></strong>
                            <br>
                            <small><?php echo e($preview); ?></small>
                        </span>
                        <?php if ($c['unread'] > 0): ?><span class="badge"><?php echo (int)$c['unread']; ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <h2 style="margin-top:24px">发私信</h2>
            <form method="post">
                <?php echo csrf_field(); ?>
                <div class="form-row">
                    <label>收件人用户名</label>
                    <input class="field" type="text" name="recipient" required>
                </div>
                <div class="form-row">
                    <label>内容</label>
                    <textarea class="field" name="body" required></textarea>
                </div>
                <button class="btn" type="submit">发送</button>
            </form>
        <?php endif; ?>
    </div>
    <p><a class="btn ghost" href="index.php">← 返回首页</a></p>
</div>
</body>
</html>
