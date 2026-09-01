<?php
declare(strict_types=1);

/**
 * Resend 邮件发送。
 * 需要先加载 TOOLS/security.php（提供 site_config()）。
 *
 * 返回 [bool 是否成功, string 错误信息]
 */

function send_email_via_resend(string $to, string $subject, string $html): array
{
    $cfg = site_config();
    $apiKey = (string)($cfg['RESEND_API_KEY'] ?? '');
    $from = (string)($cfg['RESEND_FROM'] ?? '');

    if ($apiKey === '' || $from === '') {
        return [false, '邮件服务未配置（RESEND_API_KEY / RESEND_FROM）'];
    }

    $payload = [
        'from' => $from,
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
    ];

    $ch = curl_init('https://api.resend.com/emails');
    if ($ch === false) {
        return [false, 'cURL 初始化失败'];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 10,
    ]);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($err !== '') {
        return [false, $err];
    }

    if ($code >= 200 && $code < 300) {
        return [true, ''];
    }

    return [false, 'Resend HTTP ' . $code . ' ' . (string)$resp];
}
