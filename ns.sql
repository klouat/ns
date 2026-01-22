-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table ninjasage.attendance_rewards
CREATE TABLE IF NOT EXISTS `attendance_rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `price` int NOT NULL,
  `item` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.characters
CREATE TABLE IF NOT EXISTS `characters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `level` int NOT NULL DEFAULT '1',
  `xp` int NOT NULL DEFAULT '0',
  `gender` int NOT NULL DEFAULT '0',
  `hair_style` varchar(255) DEFAULT NULL,
  `hair_color` varchar(255) DEFAULT NULL,
  `skin_color` varchar(255) DEFAULT NULL,
  `rank` varchar(255) NOT NULL DEFAULT 'Genin',
  `senjutsu` varchar(255) DEFAULT NULL,
  `gold` int NOT NULL DEFAULT '1000',
  `claimed_welcome_rewards` text,
  `tp` int NOT NULL DEFAULT '0',
  `prestige` int NOT NULL DEFAULT '0',
  `element_1` int NOT NULL DEFAULT '0',
  `element_2` int NOT NULL DEFAULT '0',
  `element_3` int NOT NULL DEFAULT '0',
  `talent_1` varchar(255) DEFAULT NULL,
  `talent_2` varchar(255) DEFAULT NULL,
  `talent_3` varchar(255) DEFAULT NULL,
  `point_wind` int NOT NULL DEFAULT '0',
  `point_fire` int NOT NULL DEFAULT '0',
  `point_lightning` int NOT NULL DEFAULT '0',
  `point_water` int NOT NULL DEFAULT '0',
  `point_earth` int NOT NULL DEFAULT '0',
  `point_free` int NOT NULL DEFAULT '0',
  `equipment_weapon` varchar(255) DEFAULT NULL,
  `equipment_back` varchar(255) DEFAULT NULL,
  `equipment_clothing` varchar(255) DEFAULT NULL,
  `equipment_accessory` varchar(255) DEFAULT NULL,
  `equipment_skills` text,
  `is_recruitable` tinyint(1) NOT NULL DEFAULT '1',
  `equipped_pet_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `character_ss` int NOT NULL DEFAULT '0',
  `equipped_senjutsu_skills` text,
  PRIMARY KEY (`id`),
  KEY `characters_user_id_foreign` (`user_id`),
  CONSTRAINT `characters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_attendance
CREATE TABLE IF NOT EXISTS `character_attendance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `attendance_days` json DEFAULT NULL,
  `claimed_milestones` json DEFAULT NULL,
  `last_token_claim` date DEFAULT NULL,
  `last_xp_claim` date DEFAULT NULL,
  `last_scroll_claim` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_attendance_character_id_foreign` (`character_id`),
  CONSTRAINT `character_attendance_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_daily_roulette
CREATE TABLE IF NOT EXISTS `character_daily_roulette` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `consecutive_days` int NOT NULL DEFAULT '1',
  `last_spin_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_daily_roulette_character_id_foreign` (`character_id`),
  CONSTRAINT `character_daily_roulette_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_daily_scratch
CREATE TABLE IF NOT EXISTS `character_daily_scratch` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `tickets` int NOT NULL DEFAULT '1',
  `consecutive_days` int NOT NULL DEFAULT '1',
  `last_scratch_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_daily_scratch_character_id_foreign` (`character_id`),
  CONSTRAINT `character_daily_scratch_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_gear_presets
CREATE TABLE IF NOT EXISTS `character_gear_presets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New Preset',
  `weapon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clothing` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hair` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_item` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accessory` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hair_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_gear_presets_character_id_foreign` (`character_id`),
  CONSTRAINT `character_gear_presets_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_items
CREATE TABLE IF NOT EXISTS `character_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `item_id` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `category` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_items_character_id_foreign` (`character_id`),
  CONSTRAINT `character_items_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4912 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_pets
CREATE TABLE IF NOT EXISTS `character_pets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `pet_swf` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pet_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pet_level` int NOT NULL DEFAULT '1',
  `pet_xp` int NOT NULL DEFAULT '0',
  `pet_mp` int NOT NULL DEFAULT '100',
  `pet_skills` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0,0,0,0,0,0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_senjutsu_skills
CREATE TABLE IF NOT EXISTS `character_senjutsu_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `skill_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL DEFAULT '0',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_senjutsu_skills_character_id_foreign` (`character_id`),
  CONSTRAINT `character_senjutsu_skills_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_skills
CREATE TABLE IF NOT EXISTS `character_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `skill_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_skills_character_id_foreign` (`character_id`),
  CONSTRAINT `character_skills_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2259 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_skill_sets
CREATE TABLE IF NOT EXISTS `character_skill_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `preset_index` int NOT NULL DEFAULT '0',
  `skills` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_skill_sets_character_id_foreign` (`character_id`),
  CONSTRAINT `character_skill_sets_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_talent_skills
CREATE TABLE IF NOT EXISTS `character_talent_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `skill_id` varchar(255) NOT NULL,
  `talent_id` varchar(255) DEFAULT NULL,
  `level` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_welcome_logins
CREATE TABLE IF NOT EXISTS `character_welcome_logins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `login_count` int NOT NULL DEFAULT '1',
  `last_login_date` date DEFAULT NULL,
  `claimed_days` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_welcome_logins_character_id_foreign` (`character_id`),
  CONSTRAINT `character_welcome_logins_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.friends
CREATE TABLE IF NOT EXISTS `friends` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `friend_id` bigint unsigned NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `is_favorite` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `friends_character_id_friend_id_unique` (`character_id`,`friend_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.job_batches
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.mails
CREATE TABLE IF NOT EXISTS `mails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int NOT NULL DEFAULT '1',
  `rewards` text COLLATE utf8mb4_unicode_ci,
  `is_viewed` tinyint(1) NOT NULL DEFAULT '0',
  `is_claimed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mails_character_id_foreign` (`character_id`),
  CONSTRAINT `mails_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tokens` int NOT NULL DEFAULT '0',
  `account_type` int NOT NULL DEFAULT '0',
  `emblem_duration` int NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
