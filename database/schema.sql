-- 66600.PL portable schema — structure only, no production rows.
-- Generated from verified module schemas during Etap 4.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- SOURCE: core/schema.sql
-- Generated from the verified production schema. Run before deployment.

CREATE TABLE IF NOT EXISTS `public_menu_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_key` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_key` (`menu_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `polls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(160) NOT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `show_results_after_vote` tinyint(1) NOT NULL DEFAULT 1,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_polls_public` (`is_active`,`starts_at`,`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `poll_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` int(10) unsigned NOT NULL,
  `option_text` varchar(80) NOT NULL,
  `display_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_poll_options_order` (`poll_id`,`display_order`),
  CONSTRAINT `fk_poll_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `poll_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` int(10) unsigned NOT NULL,
  `poll_option_id` int(10) unsigned NOT NULL,
  `voter_hash` char(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_poll_votes_voter` (`poll_id`,`voter_hash`),
  KEY `idx_poll_votes_option` (`poll_option_id`),
  CONSTRAINT `fk_poll_votes_option` FOREIGN KEY (`poll_option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poll_votes_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `pulse_notices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message` varchar(160) NOT NULL,
  `signature` varchar(80) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `author_source` varchar(16) NOT NULL DEFAULT 'community',
  `status` varchar(16) NOT NULL DEFAULT 'published',
  `ip_hash` char(64) DEFAULT NULL,
  `published_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pulse_public` (`status`,`expires_at`,`published_at`),
  KEY `idx_pulse_rate` (`ip_hash`,`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `site_visit_stats` (
  `id` tinyint(3) unsigned NOT NULL,
  `total_visits` bigint(20) unsigned NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','editor','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Public accounts are kept separate from the existing administrator users.
-- See auth/schema.sql for the complete auth migration.
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

-- SOURCE: modules/ads/schema.sql
-- Generated from the verified production schema. Run before deployment.

CREATE TABLE IF NOT EXISTS `ad_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_ad_categories_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT 4.5,
  `is_featured` tinyint(1) DEFAULT 0,
  `homepage_position` tinyint(3) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `submission_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `link` varchar(500) DEFAULT NULL,
  `detail_layout` enum('legacy','profile') NOT NULL DEFAULT 'profile',
  `profile_subtitle` varchar(255) DEFAULT NULL,
  `profile_tags` text DEFAULT NULL,
  `contact_url` varchar(500) DEFAULT NULL,
  `is_city_pride` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ads_owner_id` (`owner_id`),
  UNIQUE KEY `uq_ads_homepage_position` (`homepage_position`),
  KEY `category_id` (`category_id`),
  KEY `idx_ads_homepage_public` (`is_active`,`submission_status`,`homepage_position`,`created_at`),
  CONSTRAINT `fk_ads_category` FOREIGN KEY (`category_id`) REFERENCES `ad_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `ad_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ad_id` (`ad_id`),
  CONSTRAINT `fk_ad_gallery_ad` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- SOURCE: modules/chronicle/schema.sql
-- Generated from the verified production schema. Run before deployment.

CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `author_signature` varchar(120) DEFAULT NULL,
  `author_source` varchar(16) NOT NULL DEFAULT 'community',
  `source_type` enum('chronicle','calendar') NOT NULL DEFAULT 'chronicle',
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `submission_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_blog_posts_owner_id` (`owner_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `idx_blog_posts_public` (`is_active`,`submission_status`,`created_at`),
  CONSTRAINT `fk_blog_posts_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `blog_post_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `fk_blog_gallery_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `chronicle_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `author_name` varchar(80) NOT NULL,
  `content` text NOT NULL,
  `commenter_hash` char(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chronicle_comments_post_created` (`post_id`,`created_at`,`id`),
  KEY `idx_chronicle_comments_author_rate` (`commenter_hash`,`created_at`),
  CONSTRAINT `fk_chronicle_comments_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chronicle_post_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `voter_hash` char(64) NOT NULL,
  `vote` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chronicle_post_vote_voter` (`post_id`,`voter_hash`),
  KEY `idx_chronicle_post_votes_post_vote` (`post_id`,`vote`),
  CONSTRAINT `fk_chronicle_post_votes_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chronicle_comment_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL,
  `voter_hash` char(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chronicle_comment_like_voter` (`comment_id`,`voter_hash`),
  KEY `idx_chronicle_comment_likes_comment` (`comment_id`),
  CONSTRAINT `fk_chronicle_comment_likes_comment` FOREIGN KEY (`comment_id`) REFERENCES `chronicle_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SOURCE: chatroom — struktura odtworzona z kodu aplikacji (Etap 7).
-- Tabele chatroomu powstały w bazie produkcyjnej poza starym zrzutem SQL;
-- definicje wynikają wyłącznie ze zweryfikowanych zapytań modułu chatroom.

CREATE TABLE IF NOT EXISTS `chat_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_hash` char(64) DEFAULT NULL,
  `nickname` varchar(32) NOT NULL,
  `nickname_key` varchar(160) NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `role` enum('guest','moderator','admin') NOT NULL DEFAULT 'guest',
  `owner_admin_user_id` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `moderator_until` datetime DEFAULT NULL,
  `muted_until` datetime DEFAULT NULL,
  `last_seen` datetime NOT NULL DEFAULT current_timestamp(),
  `last_message_at` datetime DEFAULT NULL,
  `terms_accepted_at` datetime DEFAULT NULL,
  `last_read_public_message_id` bigint unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_sessions_token` (`token_hash`),
  UNIQUE KEY `uq_chat_sessions_nickname_key` (`nickname_key`),
  KEY `idx_chat_sessions_active_seen` (`is_active`,`last_seen`),
  KEY `idx_chat_sessions_account` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `author_session_id` bigint unsigned NOT NULL,
  `author_role` enum('guest','moderator','admin') NOT NULL DEFAULT 'guest',
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by_session_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_messages_created` (`created_at`),
  KEY `idx_chat_messages_author` (`author_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_message_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint unsigned NOT NULL,
  `storage_name` varchar(64) DEFAULT NULL,
  `mime_type` varchar(80) NOT NULL,
  `file_size` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_message_images_message` (`message_id`),
  KEY `idx_chat_message_images_expiry` (`deleted_at`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_private_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sender_session_id` bigint unsigned NOT NULL,
  `recipient_session_id` bigint unsigned NOT NULL,
  `admin_user_id` int unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_private_sender` (`sender_session_id`),
  KEY `idx_chat_private_recipient` (`recipient_session_id`),
  KEY `idx_chat_private_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_nick_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(32) NOT NULL,
  `nickname_key` varchar(160) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `terms_version` varchar(40) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_nick_accounts_key` (`nickname_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_nick_claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(32) NOT NULL,
  `nickname_key` varchar(160) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `approved_by_admin_user_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_nick_claims_key` (`nickname_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_admin_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_session_id` bigint unsigned DEFAULT NULL,
  `target_session_id` bigint unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_admin_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SOURCE: database/migrations/0001_security_and_runtime_tables.sql
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

-- SOURCE: database/migrations/0002_schema_version_and_owners.sql
-- Etap 4: wersjonowanie migracji i właścicielstwo treści.

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(120) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- W instalacjach utworzonych z modules/ads/schema.sql i
-- modules/chronicle/schema.sql kolumny owner_id są już częścią schematu.
-- Dla starszych instalacji poniższe ALTER-y należy wykonać jako osobną,
-- wcześniej zweryfikowaną migrację zależną od stanu INFORMATION_SCHEMA.
SET FOREIGN_KEY_CHECKS = 1;
