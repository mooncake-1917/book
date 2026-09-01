<?php
declare(strict_types=1);

/**
 * 私信未读缓存。
 * 优先使用 Redis 缓存未读数，不可用时回退到数据库查询。
 */

require_once __DIR__ . '/security.php';

function unread_cache_key(int $userId): string
{
    return 'unread:' . $userId;
}

function unread_cache_key_between(int $userId, int $otherId): string
{
    return 'unread:' . $userId . ':' . $otherId;
}

/**
 * 某用户的全部未读私信数（缓存 60 秒）
 */
function unread_total(int $userId): int
{
    $r = redis_client();
    if ($r !== null) {
        $cached = $r->get(unread_cache_key($userId));
        if ($cached !== false && $cached !== null) {
            return (int)$cached;
        }
    }

    $count = 0;
    $mysqli = db_connect();
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND read_at IS NULL');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $mysqli->close();

    if ($r !== null) {
        $r->setex(unread_cache_key($userId), 60, (string)$count);
    }

    return (int)$count;
}

/**
 * userId 收到的、来自 otherId 的未读私信数（缓存 60 秒）
 */
function unread_between(int $userId, int $otherId): int
{
    $r = redis_client();
    if ($r !== null) {
        $cached = $r->get(unread_cache_key_between($userId, $otherId));
        if ($cached !== false && $cached !== null) {
            return (int)$cached;
        }
    }

    $count = 0;
    $mysqli = db_connect();
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM messages WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL');
    $stmt->bind_param('ii', $otherId, $userId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $mysqli->close();

    if ($r !== null) {
        $r->setex(unread_cache_key_between($userId, $otherId), 60, (string)$count);
    }

    return (int)$count;
}

/**
 * 未读数变更后清除相关缓存。
 */
function unread_invalidate(int $userId, ?int $otherId = null): void
{
    $r = redis_client();
    if ($r === null) {
        return;
    }

    $r->del(unread_cache_key($userId));
    if ($otherId !== null) {
        $r->del(unread_cache_key_between($userId, $otherId));
        $r->del(unread_cache_key_between($otherId, $userId));
    }
}
