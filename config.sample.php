<?php
/**
 * book 项目数据库配置示例
 *
 * 使用方法：
 *   1. 将本文件复制为站点根目录下的 config.php
 *   2. 填写真实的数据库连接信息
 *   3. 确保 config.php 位于 .gitignore 中，不要提交到版本库
 *   4. 强烈建议为本站单独创建最小权限的 MySQL 账号，并设置强密码
 */

return [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'book_user',          // 建议使用低权限专用账号，而非 root
    'DB_PASS' => '请替换为强密码',
    'DB_NAME' => 'book',
];
