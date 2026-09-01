<?php
/**
 * book 项目配置示例
 *
 * 使用方法：
 *   1. 将本文件复制为站点根目录下的 config.php
 *   2. 填写真实配置
 *   3. config.php 已加入 .gitignore，不要提交到版本库
 */

return [
    // MySQL 数据库
    'DB_HOST' => 'localhost',
    'DB_USER' => 'book_user',          // 建议使用低权限专用账号，而非 root
    'DB_PASS' => '请替换为强密码',
    'DB_NAME' => 'book',

    // 站点基础 URL（用于生成邮件中的验证链接；留空则自动推断）
    'SITE_URL' => 'https://your-domain.com',

    // Resend 邮件服务（https://resend.com）
    'RESEND_API_KEY' => 're_xxxxxxxxxxxx',
    'RESEND_FROM' => '实用技术知识库 <no-reply@your-domain.com>',

    // Redis（可选；未安装 phpredis 或不可用时会自动回退到文件/会话）
    'REDIS_HOST' => '127.0.0.1',
    'REDIS_PORT' => 6379,
    'REDIS_PASS' => '',
    'REDIS_PREFIX' => 'book:',
];
