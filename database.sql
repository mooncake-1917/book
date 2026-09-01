-- =============================================================
-- book 项目数据库初始化脚本（v2）
-- 适用于 MySQL 5.7+ / MariaDB 10.3+
-- 导入：mysql -u root -p < database.sql
-- 包含：用户（邮箱验证 + 管理员审核）、上传记录、私信
-- =============================================================

CREATE DATABASE IF NOT EXISTS `book`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `book`;

-- ---------------------------------------------------------
-- 用户表
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `name` VARCHAR(64) NOT NULL COMMENT '登录用户名',
  `email` VARCHAR(255) NOT NULL COMMENT '邮箱（Resend 验证）',
  `password` VARCHAR(255) NOT NULL COMMENT '密码哈希（password_hash 生成）',
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user' COMMENT '角色',
  `status` ENUM('pending','active','rejected','disabled') NOT NULL DEFAULT 'pending' COMMENT '账号状态',
  `email_token` VARCHAR(64) DEFAULT NULL COMMENT '邮箱验证令牌（一次性）',
  `email_verified_at` DATETIME DEFAULT NULL COMMENT '邮箱验证时间',
  `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_name` (`name`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ---------------------------------------------------------
-- 上传记录表
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `uploads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` INT UNSIGNED NOT NULL COMMENT '上传用户ID',
  `username` VARCHAR(64) NOT NULL COMMENT '上传用户名（冗余，便于审计）',
  `directory` VARCHAR(255) NOT NULL COMMENT '目标目录名',
  `filename` VARCHAR(255) NOT NULL COMMENT '存储文件名',
  `original_filename` VARCHAR(255) NOT NULL COMMENT '原始文件名',
  `upload_time` DATETIME NOT NULL COMMENT '上传时间',
  `file_type` TINYINT NOT NULL DEFAULT 0 COMMENT '0=Markdown 1=PDF',
  PRIMARY KEY (`id`),
  KEY `idx_uploads_user` (`user_id`),
  KEY `idx_uploads_filename` (`filename`),
  KEY `idx_uploads_time` (`upload_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='上传记录表';

-- ---------------------------------------------------------
-- 私信表
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- 可选：创建最小权限专用账号（请修改密码！）
-- ---------------------------------------------------------
-- CREATE USER IF NOT EXISTS 'book_user'@'localhost' IDENTIFIED BY '请设置强密码';
-- GRANT SELECT, INSERT, DELETE, UPDATE ON `book`.* TO 'book_user'@'localhost';
-- FLUSH PRIVILEGES;

-- ---------------------------------------------------------
-- 创建初始管理员账号
-- 密码哈希请用以下命令生成：
--   php -r "echo password_hash('你的密码', PASSWORD_DEFAULT), PHP_EOL;"
-- 注意：初始管理员默认 status=active、email_verified_at=NOW()
-- ---------------------------------------------------------
-- INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `email_verified_at`)
-- VALUES ('admin', 'admin@your-domain.com', '<PASTE_HASH_HERE>', 'admin', 'active', NOW());
