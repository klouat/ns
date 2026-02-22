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
  `item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.blacksmith_items
CREATE TABLE IF NOT EXISTS `blacksmith_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `materials` json NOT NULL,
  `quantities` json NOT NULL,
  `gold_price` int NOT NULL,
  `token_price` int NOT NULL,
  `req_weapon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Dumping structure for table ninjasage.castles
CREATE TABLE IF NOT EXISTS `castles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_crew_id` bigint unsigned NOT NULL DEFAULT '0',
  `wall_hp` int unsigned NOT NULL DEFAULT '100',
  `defender_hp` int unsigned NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senjutsu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `recruits` json DEFAULT NULL,
  `recruiters` json DEFAULT NULL,
  `equipped_pet_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `character_ss` int NOT NULL DEFAULT '0',
  `equipped_senjutsu_skills` text,
  `name_color` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_confronting_death
CREATE TABLE IF NOT EXISTS `character_confronting_death` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `energy` int NOT NULL DEFAULT '8',
  `battles_won` int NOT NULL DEFAULT '0',
  `claimed_milestones` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_confronting_death_character_id_foreign` (`character_id`),
  CONSTRAINT `character_confronting_death_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_exotic_purchases
CREATE TABLE IF NOT EXISTS `character_exotic_purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `package_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchased_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `character_exotic_purchases_character_id_package_id_unique` (`character_id`,`package_id`),
  CONSTRAINT `character_exotic_purchases_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_gear_presets
CREATE TABLE IF NOT EXISTS `character_gear_presets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New Preset',
  `weapon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clothing` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hair` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accessory` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hair_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_gear_presets_character_id_foreign` (`character_id`),
  CONSTRAINT `character_gear_presets_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_giveaways
CREATE TABLE IF NOT EXISTS `character_giveaways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `giveaway_id` bigint unsigned NOT NULL,
  `joined_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `character_giveaways_character_id_giveaway_id_unique` (`character_id`,`giveaway_id`),
  KEY `character_giveaways_giveaway_id_foreign` (`giveaway_id`),
  CONSTRAINT `character_giveaways_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `character_giveaways_giveaway_id_foreign` FOREIGN KEY (`giveaway_id`) REFERENCES `giveaways` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_hunting_house
CREATE TABLE IF NOT EXISTS `character_hunting_house` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `last_daily_claim_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_hunting_house_character_id_foreign` (`character_id`),
  CONSTRAINT `character_hunting_house_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=5100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_limited_stores
CREATE TABLE IF NOT EXISTS `character_limited_stores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `items` json DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `refresh_count` int NOT NULL DEFAULT '0',
  `discount` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_limited_stores_character_id_foreign` (`character_id`),
  CONSTRAINT `character_limited_stores_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_monster_hunters
CREATE TABLE IF NOT EXISTS `character_monster_hunters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `energy` int NOT NULL DEFAULT '100',
  `last_energy_reset` date DEFAULT NULL,
  `boss_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'boss_1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_moyai_gacha
CREATE TABLE IF NOT EXISTS `character_moyai_gacha` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `total_spins` int NOT NULL DEFAULT '0',
  `claimed_bonuses` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `character_moyai_gacha_character_id_unique` (`character_id`),
  CONSTRAINT `character_moyai_gacha_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.character_pets
CREATE TABLE IF NOT EXISTS `character_pets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `pet_swf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pet_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pet_level` int NOT NULL DEFAULT '1',
  `pet_xp` int NOT NULL DEFAULT '0',
  `pet_mp` int NOT NULL DEFAULT '100',
  `pet_skills` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0,0,0,0,0,0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=1136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

-- Dumping structure for table ninjasage.crews
CREATE TABLE IF NOT EXISTS `crews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `master_id` bigint unsigned NOT NULL,
  `elder_id` bigint unsigned NOT NULL DEFAULT '0',
  `level` int unsigned NOT NULL DEFAULT '1',
  `golds` bigint unsigned NOT NULL DEFAULT '0',
  `tokens` bigint unsigned NOT NULL DEFAULT '0',
  `kushi_dango` int unsigned NOT NULL DEFAULT '1',
  `tea_house` int unsigned NOT NULL DEFAULT '1',
  `bath_house` int unsigned NOT NULL DEFAULT '1',
  `training_centre` int unsigned NOT NULL DEFAULT '1',
  `max_members` int unsigned NOT NULL DEFAULT '20',
  `announcement` text COLLATE utf8mb4_unicode_ci,
  `last_renamed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crews_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_bosses
CREATE TABLE IF NOT EXISTS `crew_bosses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `mission_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `levels` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_castle_stats
CREATE TABLE IF NOT EXISTS `crew_castle_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crew_id` bigint unsigned NOT NULL,
  `castle_id` int unsigned NOT NULL,
  `boss_kills` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crew_castle_stats_crew_id_castle_id_unique` (`crew_id`,`castle_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_history_logs
CREATE TABLE IF NOT EXISTS `crew_history_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crew_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_members
CREATE TABLE IF NOT EXISTS `crew_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crew_id` bigint unsigned NOT NULL,
  `char_id` bigint unsigned NOT NULL,
  `role` tinyint unsigned NOT NULL DEFAULT '3',
  `contribution` int unsigned NOT NULL DEFAULT '0',
  `gold_donated` bigint unsigned NOT NULL DEFAULT '0',
  `token_donated` bigint unsigned NOT NULL DEFAULT '0',
  `stamina` int unsigned NOT NULL DEFAULT '100',
  `last_stamina_regen` timestamp NULL DEFAULT NULL,
  `max_stamina` int unsigned NOT NULL DEFAULT '100',
  `merit` int unsigned NOT NULL DEFAULT '0',
  `damage` bigint unsigned NOT NULL DEFAULT '0',
  `boss_kill` int unsigned NOT NULL DEFAULT '0',
  `mini_game_energy` int unsigned NOT NULL DEFAULT '5',
  `last_mini_game_energy_refill` timestamp NULL DEFAULT NULL,
  `role_switch_cooldown` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crew_members_crew_id_char_id_unique` (`crew_id`,`char_id`),
  UNIQUE KEY `crew_members_char_id_unique` (`char_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_minigame_rewards
CREATE TABLE IF NOT EXISTS `crew_minigame_rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'material',
  `quantity` int NOT NULL DEFAULT '1',
  `probability` int NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_requests
CREATE TABLE IF NOT EXISTS `crew_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crew_id` bigint unsigned NOT NULL,
  `char_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crew_requests_crew_id_char_id_unique` (`crew_id`,`char_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_seasons
CREATE TABLE IF NOT EXISTS `crew_seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `season_identifier` int NOT NULL DEFAULT '1',
  `phase1_start_at` timestamp NULL DEFAULT NULL,
  `phase1_end_at` timestamp NULL DEFAULT NULL,
  `phase2_end_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_season_rankings
CREATE TABLE IF NOT EXISTS `crew_season_rankings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `season_id` bigint unsigned NOT NULL,
  `crew_id` bigint unsigned NOT NULL,
  `rank` int unsigned NOT NULL,
  `damage` bigint unsigned NOT NULL,
  `merit` int unsigned NOT NULL,
  `tokens_won` bigint unsigned NOT NULL,
  `members_count` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crew_season_rankings_season_id_crew_id_unique` (`season_id`,`crew_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_season_rewards
CREATE TABLE IF NOT EXISTS `crew_season_rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crew_season_id` bigint unsigned NOT NULL,
  `phase` int NOT NULL,
  `reward_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'item',
  `reward_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crew_season_rewards_crew_season_id_foreign` (`crew_season_id`),
  CONSTRAINT `crew_season_rewards_crew_season_id_foreign` FOREIGN KEY (`crew_season_id`) REFERENCES `crew_seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.crew_settings
CREATE TABLE IF NOT EXISTS `crew_settings` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.dragon_gacha_histories
CREATE TABLE IF NOT EXISTS `dragon_gacha_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `character_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL,
  `reward` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spin_count` int NOT NULL,
  `obtained_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.exotic_packages
CREATE TABLE IF NOT EXISTS `exotic_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_tokens` int NOT NULL,
  `items` json NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exotic_packages_package_id_unique` (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Dumping structure for table ninjasage.friendship_shop_items
CREATE TABLE IF NOT EXISTS `friendship_shop_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.giveaways
CREATE TABLE IF NOT EXISTS `giveaways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `prizes` json NOT NULL,
  `requirements` json NOT NULL,
  `start_at` timestamp NOT NULL,
  `end_at` timestamp NOT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.giveaway_winners
CREATE TABLE IF NOT EXISTS `giveaway_winners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `giveaway_id` bigint unsigned NOT NULL,
  `character_id` bigint unsigned NOT NULL,
  `character_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prize_won` json NOT NULL,
  `won_at` timestamp NOT NULL,
  `claimed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `giveaway_winners_giveaway_id_foreign` (`giveaway_id`),
  KEY `giveaway_winners_character_id_foreign` (`character_id`),
  CONSTRAINT `giveaway_winners_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `giveaway_winners_giveaway_id_foreign` FOREIGN KEY (`giveaway_id`) REFERENCES `giveaways` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.hunting_house_items
CREATE TABLE IF NOT EXISTS `hunting_house_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `materials` json NOT NULL,
  `quantities` json NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `expires_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Dumping structure for table ninjasage.limited_store_items
CREATE TABLE IF NOT EXISTS `limited_store_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_token` int NOT NULL DEFAULT '0',
  `price_emblem` int DEFAULT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'skill',
  `group_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `limited_store_items_item_id_unique` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.mails
CREATE TABLE IF NOT EXISTS `mails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `sender_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int NOT NULL DEFAULT '1',
  `rewards` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_viewed` tinyint(1) NOT NULL DEFAULT '0',
  `is_claimed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mails_character_id_foreign` (`character_id`),
  CONSTRAINT `mails_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.material_market_items
CREATE TABLE IF NOT EXISTS `material_market_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `materials` json NOT NULL,
  `quantities` json NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `expires_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.monster_hunter_bosses
CREATE TABLE IF NOT EXISTS `monster_hunter_bosses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `boss_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `xp` int NOT NULL DEFAULT '0',
  `gold` int NOT NULL DEFAULT '0',
  `rewards` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monster_hunter_bosses_boss_id_unique` (`boss_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.moyai_gacha_bonuses
CREATE TABLE IF NOT EXISTS `moyai_gacha_bonuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `requirement` int NOT NULL,
  `reward_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.moyai_gacha_history
CREATE TABLE IF NOT EXISTS `moyai_gacha_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `character_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `character_level` int NOT NULL,
  `reward_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spin_count` int NOT NULL,
  `currency` tinyint NOT NULL,
  `obtained_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `moyai_gacha_history_character_id_foreign` (`character_id`),
  KEY `moyai_gacha_history_obtained_at_index` (`obtained_at`),
  CONSTRAINT `moyai_gacha_history_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=256 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.moyai_gacha_rewards
CREATE TABLE IF NOT EXISTS `moyai_gacha_rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reward_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tier` enum('top','mid','common') COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.pvp_battles
CREATE TABLE IF NOT EXISTS `pvp_battles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `host_id` bigint unsigned NOT NULL,
  `enemy_id` bigint unsigned NOT NULL,
  `winner_id` bigint unsigned DEFAULT NULL,
  `log` json DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'casual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pvp_battles_host_id_foreign` (`host_id`),
  KEY `pvp_battles_enemy_id_foreign` (`enemy_id`),
  CONSTRAINT `pvp_battles_enemy_id_foreign` FOREIGN KEY (`enemy_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pvp_battles_host_id_foreign` FOREIGN KEY (`host_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.pvp_stats
CREATE TABLE IF NOT EXISTS `pvp_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `rank` int NOT NULL DEFAULT '0',
  `trophies` int NOT NULL DEFAULT '0',
  `points` int NOT NULL DEFAULT '0',
  `wins` int NOT NULL DEFAULT '0',
  `losses` int NOT NULL DEFAULT '0',
  `flee` int NOT NULL DEFAULT '0',
  `streak` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pvp_stats_character_id_unique` (`character_id`),
  CONSTRAINT `pvp_stats_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Dumping structure for table ninjasage.shadow_war_battles
CREATE TABLE IF NOT EXISTS `shadow_war_battles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `battle_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attacker_id` bigint unsigned NOT NULL,
  `defender_id` bigint unsigned NOT NULL,
  `season_id` bigint unsigned NOT NULL,
  `trophies_change` int NOT NULL DEFAULT '0',
  `is_finished` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shadow_war_battles_battle_code_unique` (`battle_code`),
  KEY `shadow_war_battles_attacker_id_foreign` (`attacker_id`),
  KEY `shadow_war_battles_defender_id_foreign` (`defender_id`),
  KEY `shadow_war_battles_season_id_foreign` (`season_id`),
  KEY `shadow_war_battles_battle_code_index` (`battle_code`),
  CONSTRAINT `shadow_war_battles_attacker_id_foreign` FOREIGN KEY (`attacker_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shadow_war_battles_defender_id_foreign` FOREIGN KEY (`defender_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shadow_war_battles_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `shadow_war_seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.shadow_war_enemy_cache
CREATE TABLE IF NOT EXISTS `shadow_war_enemy_cache` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `season_id` bigint unsigned NOT NULL,
  `enemies` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shadow_war_enemy_cache_character_id_season_id_unique` (`character_id`,`season_id`),
  KEY `shadow_war_enemy_cache_season_id_foreign` (`season_id`),
  CONSTRAINT `shadow_war_enemy_cache_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shadow_war_enemy_cache_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `shadow_war_seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.shadow_war_players
CREATE TABLE IF NOT EXISTS `shadow_war_players` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `season_id` bigint unsigned NOT NULL,
  `squad` int NOT NULL DEFAULT '0',
  `trophy` int NOT NULL DEFAULT '0',
  `rank` int NOT NULL DEFAULT '0',
  `energy` int NOT NULL DEFAULT '100',
  `show_profile` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shadow_war_players_character_id_season_id_unique` (`character_id`,`season_id`),
  KEY `shadow_war_players_season_id_trophy_index` (`season_id`,`trophy`),
  KEY `shadow_war_players_season_id_squad_trophy_index` (`season_id`,`squad`,`trophy`),
  CONSTRAINT `shadow_war_players_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shadow_war_players_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `shadow_war_seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.shadow_war_presets
CREATE TABLE IF NOT EXISTS `shadow_war_presets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `character_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Preset',
  `weapon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clothing` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hair` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_item` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accessory` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hair_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skin_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skills` text COLLATE utf8mb4_unicode_ci,
  `pet_swf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pet_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shadow_war_presets_character_id_index` (`character_id`),
  CONSTRAINT `shadow_war_presets_character_id_foreign` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.shadow_war_seasons
CREATE TABLE IF NOT EXISTS `shadow_war_seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `num` int NOT NULL DEFAULT '1',
  `date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.special_deals
CREATE TABLE IF NOT EXISTS `special_deals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `price` int NOT NULL DEFAULT '0',
  `rewards` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `claimed_scroll` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table ninjasage.user_energies
CREATE TABLE IF NOT EXISTS `user_energies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `energy_grade_s` int NOT NULL DEFAULT '100',
  `max_energy_grade_s` int NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_energies_user_id_foreign` (`user_id`),
  CONSTRAINT `user_energies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
