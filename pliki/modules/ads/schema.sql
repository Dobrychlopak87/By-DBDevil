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
) ENGINE=InnoDB AUTO_INCREMENT=10479 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

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
  CONSTRAINT `1` FOREIGN KEY (`category_id`) REFERENCES `ad_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `ad_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ad_id` (`ad_id`),
  CONSTRAINT `1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
