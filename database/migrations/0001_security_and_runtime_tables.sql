-- Etap 4: tabele wymagane przed uruchomieniem aplikacji.
-- Uruchamiane przez operatora migracji, nigdy podczas żądania HTTP.

CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
  `ip_hash` char(64) NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT 0,
  `window_started_at` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ip_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_date` date NOT NULL,
  `event_time` varchar(20) NOT NULL DEFAULT '',
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(180) DEFAULT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'Mieszkańcy',
  `chronicle_post_id` int DEFAULT NULL,
  `created_ip_hash` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_calendar_date` (`event_date`),
  KEY `idx_calendar_active` (`event_date`,`id`),
  KEY `idx_calendar_chronicle` (`chronicle_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_nickname_blocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(32) NOT NULL,
  `nickname_key` varchar(160) NOT NULL,
  `blocked_until` datetime NOT NULL,
  `blocked_by_session_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_nickname_blocks_key` (`nickname_key`),
  KEY `idx_chat_nickname_blocks_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
