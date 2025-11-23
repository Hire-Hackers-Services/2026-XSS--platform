-- 蓝莲花XSS在线平台 数据库初始化脚本
-- 版本: 2.0.8
-- 创建日期: 2024-11-23

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 表结构: users (用户表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码哈希',
  `role` VARCHAR(20) DEFAULT 'user' COMMENT '用户角色: admin/user',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `status` VARCHAR(20) DEFAULT 'active' COMMENT '状态: active/banned',
  `banned_reason` TEXT NULL COMMENT '封禁原因',
  `banned_at` TIMESTAMP NULL COMMENT '封禁时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ----------------------------
-- 表结构: payloads (Payload表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `payloads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
  `code` TEXT NOT NULL COMMENT 'Payload代码',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '描述',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payload表';

-- ----------------------------
-- 表结构: logs (日志表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_id` VARCHAR(50) NOT NULL COMMENT '日志ID',
  `user_id` INT UNSIGNED NULL COMMENT '用户ID',
  `data` LONGTEXT NOT NULL COMMENT '回传数据',
  `ip` VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User-Agent',
  `referer` VARCHAR(500) DEFAULT NULL COMMENT 'Referer',
  `content_type` VARCHAR(50) DEFAULT NULL COMMENT '内容类型',
  `is_gov_site` TINYINT(1) DEFAULT 0 COMMENT '是否为政府网站: 0=否 1=是',
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_log_id` (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_is_gov_site` (`is_gov_site`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日志表';

-- ----------------------------
-- 表结构: settings (系统设置表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
  `value` TEXT NULL COMMENT '配置值',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '描述',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

-- ----------------------------
-- 表结构: login_attempts (登录尝试记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL COMMENT 'IP地址',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `success` TINYINT(1) DEFAULT 0 COMMENT '是否成功: 0=失败 1=成功',
  `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '尝试时间',
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_username` (`username`),
  KEY `idx_attempt_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录尝试记录表';

-- ----------------------------
-- 表结构: temp_ip_ban (临时IP封禁表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `temp_ip_ban` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL UNIQUE COMMENT 'IP地址',
  `reason` VARCHAR(255) DEFAULT NULL COMMENT '封禁原因',
  `ban_until` TIMESTAMP NOT NULL COMMENT '封禁至',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ip` (`ip`),
  KEY `idx_ban_until` (`ban_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='临时IP封禁表';

-- ----------------------------
-- 表结构: ip_blacklist (IP黑名单表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ip_blacklist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL UNIQUE COMMENT 'IP地址',
  `reason` VARCHAR(255) DEFAULT NULL COMMENT '封禁原因',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IP黑名单表';

-- ----------------------------
-- 插入默认管理员账号
-- 用户名: admin
-- 密码: Admin@123
-- ----------------------------
INSERT IGNORE INTO `users` (`username`, `password`, `role`, `email`, `status`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@example.com', 'active');

-- ----------------------------
-- 插入默认系统设置
-- ----------------------------
INSERT IGNORE INTO `settings` (`key`, `value`, `description`) VALUES
('site_name', '蓝莲花XSS在线平台', '网站名称'),
('site_url', 'https://xss.li', '网站URL'),
('ip_whitelist_enabled', '0', 'IP白名单启用状态: 0=禁用 1=启用'),
('allowed_ips', '', '允许的IP列表（每行一个）'),
('max_login_attempts', '5', '最大登录尝试次数'),
('ban_duration', '3600', '临时封禁时长（秒）'),
('enable_registration', '1', '是否开放注册: 0=关闭 1=开放'),
('session_timeout', '3600', '会话超时时间（秒）');

SET FOREIGN_KEY_CHECKS = 1;

-- 初始化完成提示
SELECT '数据库初始化完成！默认管理员账号: admin / Admin@123' AS message;
