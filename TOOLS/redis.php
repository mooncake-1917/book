<?php
declare(strict_types=1);

/**
 * Redis 支持层。
 * 使用 phpredis 扩展（类名为 Redis）。未安装或连接失败时，所有函数安全回退。
 * 需要先加载 TOOLS/security.php（提供 site_config()）。
 */

function redis_client(): ?Redis
{
    static $client = null;
    static $checked = false;

    if ($checked) {
        return $client;
    }
    $checked = true;

    if (!class_exists('Redis')) {
        return null;
    }

    $cfg = site_config();

    try {
        $r = new Redis();
        $host = (string)($cfg['REDIS_HOST'] ?? '127.0.0.1');
        $port = (int)($cfg['REDIS_PORT'] ?? 6379);

        if ($r->connect($host, $port, 0.5) === false) {
            return null;
        }

        if (!empty($cfg['REDIS_PASS'])) {
            if ($r->auth((string)$cfg['REDIS_PASS']) === false) {
                return null;
            }
        }

        $prefix = (string)($cfg['REDIS_PREFIX'] ?? 'book:');
        $r->setOption(Redis::OPT_PREFIX, $prefix);
        $r->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);

        $client = $r;
        return $client;
    } catch (Throwable $e) {
        error_log('[book] Redis 连接失败: ' . $e->getMessage());
        return null;
    }
}

/**
 * 若 Redis 可用，将会话存储切换到 Redis；否则保持 PHP 默认文件会话。
 */
function redis_session_setup(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $r = redis_client();
    if ($r === null) {
        return;
    }

    session_set_save_handler(
        static function (): bool {
            return true;
        },
        static function (): bool {
            return true;
        },
        static function (string $id): string {
            $r = redis_client();
            if ($r === null) {
                return '';
            }
            $data = $r->get('session:' . $id);
            return is_string($data) ? $data : '';
        },
        static function (string $id, string $data): bool {
            $r = redis_client();
            if ($r === null) {
                return true;
            }
            $lifetime = (int)ini_get('session.gc_maxlifetime');
            $r->setex('session:' . $id, max($lifetime, 3600), $data);
            return true;
        },
        static function (string $id): bool {
            $r = redis_client();
            if ($r !== null) {
                $r->del('session:' . $id);
            }
            return true;
        },
        static function (): bool {
            return true;
        }
    );
}
