-- =============================================================
-- book v2 增量迁移脚本（用于已有 v1 数据库）
-- 适用：已部署上一版（users / uploads 已存在）的实例
-- 导入：mysql -u root -p book < database_migration_v2.sql
-- =============================================================

USE `book`;

-- 1. users 表新增邮箱、状态、验证字段
ALTER TABLE `users`
  ADD COLUMN `email` VARCHAR(255) NULL COMMENT '邮箱' AFTER `name`,
  ADD COLUMN `status` ENUM('pending','active','rejected','disabled') NOT NULL DEFAULT 'active' COMMENT '账号状态' AFTER `role`,
  ADD COLUMN `email_token` VARCHAR(64) DEFAULT NULL COMMENT '邮箱验证令牌' AFTER `status`,
  ADD COLUMN `email_verified_at` DATETIME DEFAULT NULL COMMENT '邮箱验证时间' AFTER `email_token`,
  ADD COLUMN `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间' AFTER `email_verified_at`,
  ADD UNIQUE KEY `uk_users_email` (`email`);

-- 已有用户视为已验证并启用，避免影响正常登录
UPDATE `users` SET `email_verified_at` = NOW() WHERE `email_verified_at` IS NULL;

-- 2. 新增私信表
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '消息ID',
  `sender_id` INT UNSIGNED NOT NULL COMMENT '发送者用户ID',
  `recipient_id` INT UNSIGNED NOT NULL COMMENT '接收者用户ID',
  `body` TEXT NOT NULL COMMENT '消息内容',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
  `read_at` DATETIME DEFAULT NULL COMMENT '阅读时间（NULL=未读）',
  PRIMARY KEY (`id`),
  KEY `idx_messages_sender` (`sender_id`),
  KEY `idx_messages_recipient` (`recipient_id`),
  KEY `idx_messages_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信表';
