-- =============================================================
-- book 项目数据库初始化脚本
-- 适用于 MySQL 5.7+ / MariaDB 10.3+
-- 导入：mysql -u root -p < database.sql
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
  `password` VARCHAR(255) NOT NULL COMMENT '密码哈希（password_hash 生成）',
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user' COMMENT '角色',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_name` (`name`)
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
-- 可选：创建最小权限专用账号（请修改密码！）
-- ---------------------------------------------------------
-- CREATE USER IF NOT EXISTS 'book_user'@'localhost' IDENTIFIED BY '请设置强密码';
-- GRANT SELECT, INSERT, DELETE, UPDATE ON `book`.* TO 'book_user'@'localhost';
-- FLUSH PRIVILEGES;

-- ---------------------------------------------------------
-- 创建初始管理员账号
-- 密码哈希请用以下命令生成：
--   php -r "echo password_hash('你的密码', PASSWORD_DEFAULT), PHP_EOL;"
-- 然后替换下面的 <PASTE_HASH_HERE>
-- ---------------------------------------------------------
-- INSERT INTO `users` (`name`, `password`, `role`)
-- VALUES ('admin', '<PASTE_HASH_HERE>', 'admin');
