/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: mariadb123.server629599.nazwa.pl    Database: server629599_poker
-- ------------------------------------------------------
-- Server version	12.3.3-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Current Database: `server629599_poker`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `server629599_poker` /*!40100 DEFAULT CHARACTER SET latin2 COLLATE latin2_general_ci */;

USE `server629599_poker`;

--
-- Table structure for table `hand_history`
--

DROP TABLE IF EXISTS `hand_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hand_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `table_id` int(10) unsigned NOT NULL,
  `hand_no` int(11) NOT NULL,
  `payload_json` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_history_table_hand` (`table_id`,`hand_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hand_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `hand_history` WRITE;
/*!40000 ALTER TABLE `hand_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `hand_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `poker_chat_invites`
--

DROP TABLE IF EXISTS `poker_chat_invites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `poker_chat_invites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `table_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invites_table` (`table_id`,`created_at`),
  KEY `idx_invites_user` (`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poker_chat_invites`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `poker_chat_invites` WRITE;
/*!40000 ALTER TABLE `poker_chat_invites` DISABLE KEYS */;
INSERT INTO `poker_chat_invites` VALUES
(2,1,7,'2026-09-25 14:26:50'),
(3,5,13,'2026-09-25 17:07:06');
/*!40000 ALTER TABLE `poker_chat_invites` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `poker_meta`
--

DROP TABLE IF EXISTS `poker_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `poker_meta` (
  `meta_key` varchar(64) NOT NULL,
  `meta_value` varchar(255) NOT NULL,
  PRIMARY KEY (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poker_meta`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `poker_meta` WRITE;
/*!40000 ALTER TABLE `poker_meta` DISABLE KEYS */;
INSERT INTO `poker_meta` VALUES
('chat_bot_session_id','9');
/*!40000 ALTER TABLE `poker_meta` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `poker_szu_stats`
--

DROP TABLE IF EXISTS `poker_szu_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `poker_szu_stats` (
  `user_id` int(10) unsigned NOT NULL,
  `hands` int(10) unsigned NOT NULL DEFAULT 0,
  `vpip` int(10) unsigned NOT NULL DEFAULT 0,
  `pfr` int(10) unsigned NOT NULL DEFAULT 0,
  `post_aggr` int(10) unsigned NOT NULL DEFAULT 0,
  `post_calls` int(10) unsigned NOT NULL DEFAULT 0,
  `faced_bet` int(10) unsigned NOT NULL DEFAULT 0,
  `fold_to_bet` int(10) unsigned NOT NULL DEFAULT 0,
  `showdowns` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poker_szu_stats`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `poker_szu_stats` WRITE;
/*!40000 ALTER TABLE `poker_szu_stats` DISABLE KEYS */;
INSERT INTO `poker_szu_stats` VALUES
(12,4,3,2,2,0,1,0,0,'2026-09-25 20:04:30'),
(13,4,2,1,3,0,1,0,2,'2026-09-25 19:46:13');
/*!40000 ALTER TABLE `poker_szu_stats` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `poker_tables`
--

DROP TABLE IF EXISTS `poker_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `poker_tables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `status` enum('waiting','playing','showdown','finished') NOT NULL DEFAULT 'waiting',
  `small_blind` int(11) NOT NULL DEFAULT 10,
  `big_blind` int(11) NOT NULL DEFAULT 20,
  `state_json` longtext NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poker_tables`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `poker_tables` WRITE;
/*!40000 ALTER TABLE `poker_tables` DISABLE KEYS */;
INSERT INTO `poker_tables` VALUES
(1,'Stół Warszawa','playing',10,20,'{\"hand_no\":5,\"phase\":\"preflop\",\"dealer_seat\":3,\"turn_user_id\":12,\"turn_started_at\":1790366685,\"turn_deadline_at\":1790366715,\"current_bet\":48,\"min_raise\":28,\"pot\":68,\"board\":[],\"deck\":[\"6h\",\"2h\",\"As\",\"5h\",\"2d\",\"4s\",\"Kd\",\"8d\",\"9h\",\"4h\",\"Td\",\"Ad\",\"6d\",\"5c\",\"Qh\",\"Kc\",\"8s\",\"Ks\",\"8h\",\"6s\",\"Th\",\"Js\",\"8c\",\"Jh\",\"4c\",\"7s\",\"3d\",\"Jc\",\"Kh\",\"9s\",\"3h\",\"7h\",\"3c\",\"7d\",\"Qs\",\"2s\",\"Qd\",\"6c\",\"9d\",\"4d\",\"5d\",\"Qc\",\"Tc\",\"5s\",\"Ac\",\"2c\",\"9c\",\"7c\"],\"acted\":{\"15\":true},\"messages\":[\"Rozdanie #5 rozpoczęte.\",\"Karol wpłaca dużą ciemną.\",\"Wielki Szu wpłaca małą ciemną.\",\"Wielki Szu podbija do 48 pkt.\"],\"last_result\":null,\"next_hand_at\":null,\"log\":[[12,\"preflop\",\"blind\",20,0],[15,\"preflop\",\"blind\",10,0],[15,\"preflop\",\"raise\",48,10]],\"afk\":{\"12\":1},\"paused\":false,\"szu_table\":false,\"bot_act_at\":null}','2026-09-25 20:04:45','2026-09-25 04:25:38'),
(5,'Krosno Odrzańskie','waiting',10,20,'{\"hand_no\":4,\"phase\":\"waiting\",\"dealer_seat\":3,\"turn_user_id\":null,\"turn_started_at\":null,\"turn_deadline_at\":null,\"current_bet\":0,\"min_raise\":20,\"pot\":0,\"board\":[],\"deck\":[],\"acted\":[],\"messages\":[\"Pauza.\"],\"last_result\":null,\"next_hand_at\":null,\"log\":[],\"afk\":{\"13\":2},\"paused\":true,\"szu_table\":false}','2026-09-25 20:02:35','2026-09-25 16:50:36');
/*!40000 ALTER TABLE `poker_tables` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `table_players`
--

DROP TABLE IF EXISTS `table_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `table_players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `table_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `seat` tinyint(3) unsigned NOT NULL,
  `chips` int(11) NOT NULL DEFAULT 5000,
  `in_hand` tinyint(1) NOT NULL DEFAULT 0,
  `folded` tinyint(1) NOT NULL DEFAULT 0,
  `all_in` tinyint(1) NOT NULL DEFAULT 0,
  `current_bet` int(11) NOT NULL DEFAULT 0,
  `total_bet` int(11) NOT NULL DEFAULT 0,
  `hole_cards_json` varchar(64) NOT NULL DEFAULT '[]',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `unique_table_seat` (`table_id`,`seat`),
  CONSTRAINT `fk_players_table` FOREIGN KEY (`table_id`) REFERENCES `poker_tables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_players_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `table_players`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `table_players` WRITE;
/*!40000 ALTER TABLE `table_players` DISABLE KEYS */;
INSERT INTO `table_players` VALUES
(6,1,8,2,0,0,0,0,0,0,'[]'),
(11,1,12,1,5164,1,0,0,20,20,'[\"Ts\",\"Ah\"]'),
(20,1,15,3,4768,1,0,0,48,48,'[\"Jd\",\"3s\"]');
/*!40000 ALTER TABLE `table_players` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `auth_type` enum('guest','reserved','site','bot') NOT NULL DEFAULT 'guest',
  `site_uid` varchar(40) DEFAULT NULL,
  `chips` int(11) NOT NULL DEFAULT 5000,
  `last_refill_at` datetime NOT NULL,
  `last_activity_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uq_users_site_uid` (`site_uid`),
  KEY `idx_users_guest_activity` (`auth_type`,`last_activity_at`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(7,'Karol_7',NULL,'guest',NULL,10000,'2026-09-25 03:03:36','2026-09-25 14:26:57','2026-09-25 05:03:36'),
(8,'Apd',NULL,'guest',NULL,0,'2026-09-25 03:04:38','2026-09-25 03:06:31','2026-09-25 05:04:38'),
(12,'Karol',NULL,'site','a:1',5164,'2026-09-25 14:27:25','2026-09-25 20:05:05','2026-09-25 16:27:25'),
(13,'Papa',NULL,'guest',NULL,146,'2026-09-25 14:50:07','2026-09-25 19:52:04','2026-09-25 16:50:07'),
(15,'Wielki Szu',NULL,'bot','bot:1',4768,'2026-09-25 16:25:55','2026-09-25 20:03:01','2026-09-25 18:25:55'),
(16,'Wielki Szu II',NULL,'bot','bot:2',5140,'2026-09-25 16:25:55','2026-09-25 19:45:04','2026-09-25 18:25:55');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping events for database 'server629599_poker'
--

--
-- Dumping routines for database 'server629599_poker'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-26  0:36:13
