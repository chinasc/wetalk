CREATE DATABASE IF NOT EXISTS `wetalk` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `wetalk`;

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像路径',
  `role` enum('admin','customer_service','user') NOT NULL DEFAULT 'user' COMMENT '角色',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `verify_code` varchar(10) NOT NULL DEFAULT '' COMMENT '一次性验证码',
  `verify_code_used` tinyint NOT NULL DEFAULT 0 COMMENT '验证码是否已使用：0否 1是',
  `password_set` tinyint NOT NULL DEFAULT 0 COMMENT '是否已设置密码：0否 1是',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';

-- 会话表
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '用户 ID',
  `customer_service_id` int unsigned NOT NULL COMMENT '客服 ID',
  `status` enum('active','closed') NOT NULL DEFAULT 'active' COMMENT '会话状态',
  `user_last_read_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '用户最后已读消息ID',
  `cs_last_read_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '客服最后已读消息ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_cs_id` (`customer_service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='会话表';

-- 消息表
CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int unsigned NOT NULL COMMENT '会话 ID',
  `sender_id` int unsigned NOT NULL COMMENT '发送者 ID',
  `sender_type` enum('user','customer_service') NOT NULL COMMENT '发送者角色',
  `type` enum('text','image','album','system') NOT NULL DEFAULT 'text' COMMENT '消息类型',
  `content` text COMMENT '消息内容',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='消息表';

-- 相册集表
CREATE TABLE IF NOT EXISTS `albums` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '相册集名称',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图路径',
  `sort_order` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='相册集表';

-- 相册图片表
CREATE TABLE IF NOT EXISTS `album_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int unsigned NOT NULL COMMENT '所属相册集 ID',
  `image_path` varchar(255) NOT NULL COMMENT '图片路径',
  `thumbnail_path` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图路径',
  `remark` text COMMENT '图片备注',
  `sort_order` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_album_id` (`album_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='相册图片表';

-- 游戏表
CREATE TABLE IF NOT EXISTS `games` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '游戏名称',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图路径',
  `description` text COMMENT '游戏简介',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏页面路径',
  `sort_order` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1上线 0下线',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='游戏表';

-- 系统设置表
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL COMMENT '配置键',
  `value` text COMMENT '配置值',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系统设置表';

-- 管理员账号密码: admin / admin123
INSERT INTO `users` (`username`, `password`, `nickname`, `role`, `status`, `password_set`, `created_at`, `updated_at`)
VALUES ('admin', '$2y$10$NkXjvCsJ4zTAMdfIPvT9hOZjhgAgfm7tqgCdhTA0vO5sDPmaZf/sK', '管理员', 'admin', 1, 1, NOW(), NOW());

-- 常用语表
CREATE TABLE IF NOT EXISTS `quick_replies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '常用语标题',
  `content` text NOT NULL COMMENT '常用语内容',
  `sort_order` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='常用语表';

-- 预置系统配置
INSERT INTO `settings` (`key`, `value`) VALUES
('chat_access_code', '8888'),
('upload_max_size', '20');
