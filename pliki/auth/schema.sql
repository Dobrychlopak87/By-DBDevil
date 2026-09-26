-- Public account module.
-- This table is intentionally separate from the existing `users` table,
-- which is used by the administrator panel and the chatroom moderation.

CREATE TABLE IF NOT EXISTS `auth_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_auth_users_username` (`username`),
  KEY `idx_auth_users_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_rate_limits` (
  `bucket_key` char(64) NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL DEFAULT 0,
  `window_started_at` datetime NOT NULL,
  `locked_until` datetime NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`bucket_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing guest submissions remain NULL. Authenticated submissions receive
-- the public-account id at creation time.
ALTER TABLE `ads`
  ADD COLUMN `owner_id` int(10) unsigned NULL DEFAULT NULL AFTER `id`,
  ADD KEY `idx_ads_owner_id` (`owner_id`);

ALTER TABLE `blog_posts`
  ADD COLUMN `owner_id` int(10) unsigned NULL DEFAULT NULL AFTER `id`,
  ADD KEY `idx_blog_posts_owner_id` (`owner_id`);