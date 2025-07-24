-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: activite_ujem
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `course_date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `enrollment_id` (`enrollment_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `beauty_contests`
--

DROP TABLE IF EXISTS `beauty_contests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beauty_contests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('upcoming','ongoing','completed') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `beauty_contests`
--

LOCK TABLES `beauty_contests` WRITE;
/*!40000 ALTER TABLE `beauty_contests` DISABLE KEYS */;
/*!40000 ALTER TABLE `beauty_contests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidate_photos`
--

DROP TABLE IF EXISTS `candidate_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `candidate_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `photo_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `candidate_id` (`candidate_id`),
  CONSTRAINT `candidate_photos_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidate_photos`
--

LOCK TABLES `candidate_photos` WRITE;
/*!40000 ALTER TABLE `candidate_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidate_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidates`
--

DROP TABLE IF EXISTS `candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `contest_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `measurements` varchar(50) DEFAULT NULL,
  `talents` text DEFAULT NULL,
  `candidate_number` int(11) DEFAULT NULL,
  `total_votes` int(11) DEFAULT 0,
  `jury_score` float DEFAULT 0,
  `final_rank` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `contest_id` (`contest_id`),
  CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidates_ibfk_2` FOREIGN KEY (`contest_id`) REFERENCES `beauty_contests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidates`
--

LOCK TABLES `candidates` WRITE;
/*!40000 ALTER TABLE `candidates` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `team` enum('home','away') NOT NULL,
  `player` varchar(255) NOT NULL,
  `card_type` enum('yellow','red','blue') NOT NULL,
  `minute` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cards`
--

LOCK TABLES `cards` WRITE;
/*!40000 ALTER TABLE `cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classement`
--

DROP TABLE IF EXISTS `classement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `saison` varchar(9) NOT NULL,
  `competition` varchar(50) NOT NULL,
  `poule_id` int(11) NOT NULL,
  `nom_equipe` varchar(50) NOT NULL,
  `matchs_joues` int(11) NOT NULL,
  `matchs_gagnes` int(11) NOT NULL,
  `matchs_nuls` int(11) NOT NULL,
  `matchs_perdus` int(11) NOT NULL,
  `buts_pour` int(11) NOT NULL,
  `buts_contre` int(11) NOT NULL,
  `difference_buts` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `forme` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saison` (`saison`,`competition`,`nom_equipe`),
  UNIQUE KEY `unique_team` (`saison`,`competition`,`poule_id`,`nom_equipe`),
  KEY `fk_classement_poule` (`poule_id`),
  CONSTRAINT `fk_classement_poule` FOREIGN KEY (`poule_id`) REFERENCES `poules` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classement`
--

LOCK TABLES `classement` WRITE;
/*!40000 ALTER TABLE `classement` DISABLE KEYS */;
INSERT INTO `classement` VALUES (1,'2024-2025','tournoi',1,'Ehounou FC',3,1,2,0,4,2,2,5,'N,N,V'),(2,'2024-2025','tournoi',1,'Ngalwa FC',3,0,2,1,2,4,-2,2,'N,N,D'),(5,'2024-2025','championnat',1,'Ehounou FC',3,2,1,0,3,0,3,7,'V,N,V'),(6,'2024-2025','championnat',1,'Ngalwa FC',2,0,1,1,0,1,-1,1,'D,N');
/*!40000 ALTER TABLE `classement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codes_membres`
--

DROP TABLE IF EXISTS `codes_membres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codes_membres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `utilise` tinyint(1) DEFAULT 0,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_utilisation` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `used_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codes_membres`
--

LOCK TABLES `codes_membres` WRITE;
/*!40000 ALTER TABLE `codes_membres` DISABLE KEYS */;
/*!40000 ALTER TABLE `codes_membres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `news_id` (`news_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contest_events`
--

DROP TABLE IF EXISTS `contest_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contest_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contest_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('rehearsal','photoshoot','final') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `contest_id` (`contest_id`),
  CONSTRAINT `contest_events_ibfk_1` FOREIGN KEY (`contest_id`) REFERENCES `beauty_contests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contest_events`
--

LOCK TABLES `contest_events` WRITE;
/*!40000 ALTER TABLE `contest_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `contest_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_enrollments`
--

DROP TABLE IF EXISTS `course_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','completed','waived') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `course_enrollments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_enrollments`
--

LOCK TABLES `course_enrollments` WRITE;
/*!40000 ALTER TABLE `course_enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donations`
--

DROP TABLE IF EXISTS `donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_name` varchar(100) DEFAULT NULL,
  `donor_email` varchar(100) DEFAULT NULL,
  `donor_phone` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('paypal','mobile_money','bank_transfer','cash') NOT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `is_anonymous` tinyint(1) DEFAULT 0,
  `message` text DEFAULT NULL,
  `donation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evenements`
--

DROP TABLE IF EXISTS `evenements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evenements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `lieu` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `categorie` enum('sport','culture','education') NOT NULL,
  `statut` enum('actif','annulé','complet') DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evenements`
--

LOCK TABLES `evenements` WRITE;
/*!40000 ALTER TABLE `evenements` DISABLE KEYS */;
INSERT INTO `evenements` VALUES (1,'concours','rien du tout','2025-05-18 17:56:00','2025-05-18 17:56:00','Melekoukro','WhatsApp Image 2025-01-16 à 07.00.15_948b8bbb.jpg','culture','actif','2025-05-18 17:57:56'),(2,'sport','le sport et la vie','2025-05-18 18:34:00','2025-05-20 19:34:00','melekoukro','WhatsApp Image 2025-01-13 à 19.19.38_0f12d64b.jpg','sport','actif','2025-05-18 18:35:29');
/*!40000 ALTER TABLE `evenements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_registrations`
--

DROP TABLE IF EXISTS `event_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `registration_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `evenements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_registrations`
--

LOCK TABLES `event_registrations` WRITE;
/*!40000 ALTER TABLE `event_registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('field','room','gym') NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilities`
--

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_bookings`
--

DROP TABLE IF EXISTS `facility_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facility_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `facility_bookings_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `facility_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_bookings`
--

LOCK TABLES `facility_bookings` WRITE;
/*!40000 ALTER TABLE `facility_bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `facility_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goals`
--

DROP TABLE IF EXISTS `goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `team` enum('home','away') NOT NULL,
  `player` varchar(255) NOT NULL,
  `minute` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goals`
--

LOCK TABLES `goals` WRITE;
/*!40000 ALTER TABLE `goals` DISABLE KEYS */;
INSERT INTO `goals` VALUES (2,18,'away','alain',3),(3,18,'away','alain',18),(4,18,'away','alain',20),(5,18,'home','D',62),(6,19,'away','D',8),(7,19,'home','alain',67);
/*!40000 ALTER TABLE `goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `match_logs`
--

DROP TABLE IF EXISTS `match_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `match_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Référence à l utilisateur qui a effectué l action',
  `action_type` enum('UPDATE_SCORE','ADD_GOAL','ADD_CARD','START_FIRST_HALF','END_FIRST_HALF','START_SECOND_HALF','END_MATCH','SET_EXTRA_TIME','FINALIZE_MATCH','SET_MATCH_DURATION','UPDATE_STANDING','CREATE_STANDING') NOT NULL,
  `action_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Détails spécifiques au format JSON' CHECK (json_valid(`action_details`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `previous_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `match_logs_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `match_logs`
--

LOCK TABLES `match_logs` WRITE;
/*!40000 ALTER TABLE `match_logs` DISABLE KEYS */;
INSERT INTO `match_logs` VALUES (1,19,3,'SET_MATCH_DURATION','{\"message\":\"Durée du match définie\"}','2025-05-22 12:55:23','{\"timer_duration\":3000}','{\"timer_duration\":3000}'),(2,19,3,'START_FIRST_HALF','{\"message\":\"Début de la première mi-temps\"}','2025-05-22 12:55:36','{\"timer_status\":\"not_started\"}','{\"timer_status\":\"first_half\"}'),(3,19,3,'ADD_GOAL','{\"message\":\"But marqué par D à la 8 minute pour l\'équipe \"}','2025-05-22 13:03:48',NULL,'{\"team\":\"away\",\"player\":\"D\",\"minute\":8,\"score_change\":\"+0 - +1\"}'),(4,19,3,'SET_EXTRA_TIME','{\"message\":\"Temps additionnel première mi-temps défini\"}','2025-05-22 13:16:40','{\"first_half_extra\":0}','{\"first_half_extra\":120}'),(5,19,3,'END_FIRST_HALF','{\"message\":\"Fin de la première mi-temps\"}','2025-05-22 13:22:37','{\"timer_status\":\"first_half\"}','{\"timer_status\":\"half_time\",\"elapsed_time\":1620}'),(6,19,3,'START_SECOND_HALF','{\"message\":\"Début de la deuxième mi-temps\"}','2025-05-22 13:53:41','{\"timer_status\":\"half_time\"}','{\"timer_status\":\"second_half\"}'),(7,19,3,'ADD_GOAL','{\"message\":\"But marqué par alain à la 40 minute pour l\'équipe \"}','2025-05-22 14:09:16',NULL,'{\"team\":\"home\",\"player\":\"alain\",\"minute\":67,\"score_change\":\"+1 - +0\"}'),(8,19,3,'END_MATCH','{\"message\":\"Fin du match\"}','2025-05-22 14:22:26','{\"timer_status\":\"second_half\"}','{\"timer_status\":\"ended\",\"elapsed_time\":1500}'),(9,19,3,'FINALIZE_MATCH','{\"message\":\"Match finalisé\"}','2025-05-22 15:50:02','{\"status\":\"ongoing\"}','{\"status\":\"completed\"}'),(10,19,3,'CREATE_STANDING','{\"message\":\"Classement créé: Ehounou FC\"}','2025-05-22 15:50:02',NULL,'{\"matchs_joues\":2,\"matchs_gagnes\":1,\"matchs_nuls\":1,\"matchs_perdus\":0,\"buts_pour\":4,\"buts_contre\":2,\"difference_buts\":2,\"points\":4,\"forme\":\"N,V\"}'),(11,19,3,'CREATE_STANDING','{\"message\":\"Classement créé: Ngalwa FC\"}','2025-05-22 15:50:02',NULL,'{\"matchs_joues\":2,\"matchs_gagnes\":0,\"matchs_nuls\":1,\"matchs_perdus\":1,\"buts_pour\":2,\"buts_contre\":4,\"difference_buts\":-2,\"points\":1,\"forme\":\"N,D\"}'),(12,20,3,'SET_MATCH_DURATION','{\"message\":\"Durée du match définie\"}','2025-05-22 15:51:46','{\"timer_duration\":5400}','{\"timer_duration\":3000}'),(13,20,3,'START_FIRST_HALF','{\"message\":\"Début de la première mi-temps\"}','2025-05-22 15:51:50','{\"timer_status\":\"not_started\"}','{\"timer_status\":\"first_half\"}'),(14,20,3,'END_FIRST_HALF','{\"message\":\"Fin de la première mi-temps\"}','2025-05-22 16:44:45','{\"timer_status\":\"first_half\"}','{\"timer_status\":\"half_time\",\"elapsed_time\":1500}'),(15,20,3,'START_SECOND_HALF','{\"message\":\"Début de la deuxième mi-temps\"}','2025-05-22 16:45:00','{\"timer_status\":\"half_time\"}','{\"timer_status\":\"second_half\"}'),(16,20,3,'END_MATCH','{\"message\":\"Fin du match\"}','2025-05-22 17:10:39','{\"timer_status\":\"second_half\"}','{\"timer_status\":\"ended\",\"elapsed_time\":1500}'),(17,20,3,'UPDATE_SCORE','{\"message\":\"Mise à jour du score\"}','2025-05-22 17:40:54','{\"score_home\":null,\"score_away\":null}','{\"score_home\":0,\"score_away\":0}'),(18,20,3,'FINALIZE_MATCH','{\"message\":\"Match finalisé\"}','2025-05-22 17:41:01','{\"status\":\"ongoing\"}','{\"status\":\"completed\"}'),(19,20,3,'UPDATE_STANDING','{\"message\":\"Classement mis à jour: Ehounou FC\"}','2025-05-22 17:41:01','{\"id\":1,\"saison\":\"2024-2025\",\"competition\":\"tournoi\",\"poule_id\":1,\"nom_equipe\":\"Ehounou FC\",\"matchs_joues\":2,\"matchs_gagnes\":1,\"matchs_nuls\":1,\"matchs_perdus\":0,\"buts_pour\":4,\"buts_contre\":2,\"difference_buts\":2,\"points\":4,\"forme\":\"N,V\"}','{\"matchs_joues\":3,\"matchs_gagnes\":1,\"matchs_nuls\":2,\"matchs_perdus\":0,\"buts_pour\":4,\"buts_contre\":2,\"difference_buts\":2,\"points\":5,\"forme\":\"N,N,V\"}'),(20,20,3,'UPDATE_STANDING','{\"message\":\"Classement mis à jour: Ngalwa FC\"}','2025-05-22 17:41:01','{\"id\":2,\"saison\":\"2024-2025\",\"competition\":\"tournoi\",\"poule_id\":1,\"nom_equipe\":\"Ngalwa FC\",\"matchs_joues\":2,\"matchs_gagnes\":0,\"matchs_nuls\":1,\"matchs_perdus\":1,\"buts_pour\":2,\"buts_contre\":4,\"difference_buts\":-2,\"points\":1,\"forme\":\"N,D\"}','{\"matchs_joues\":3,\"matchs_gagnes\":0,\"matchs_nuls\":2,\"matchs_perdus\":1,\"buts_pour\":2,\"buts_contre\":4,\"difference_buts\":-2,\"points\":2,\"forme\":\"N,N,D\"}');
/*!40000 ALTER TABLE `match_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competition` varchar(20) NOT NULL,
  `phase` varchar(50) NOT NULL,
  `match_date` date NOT NULL,
  `match_time` time NOT NULL,
  `team_home` varchar(50) NOT NULL,
  `team_away` varchar(50) NOT NULL,
  `score_home` int(11) DEFAULT 0,
  `score_away` int(11) DEFAULT 0,
  `venue` varchar(50) NOT NULL,
  `status` enum('pending','ongoing','completed','finished') DEFAULT 'pending',
  `timer_duration` int(11) DEFAULT 5400,
  `timer_start` datetime DEFAULT NULL,
  `timer_elapsed` int(11) DEFAULT 0,
  `first_half_elapsed` int(11) DEFAULT 0,
  `second_half_elapsed` int(11) DEFAULT 0,
  `poule_id` int(11) DEFAULT NULL,
  `timer_status` enum('not_started','first_half','half_time','second_half','ended') DEFAULT 'not_started',
  `timer_paused` tinyint(1) DEFAULT 0,
  `first_half_end` datetime DEFAULT NULL,
  `second_half_start` datetime DEFAULT NULL,
  `half_time_pause_start` datetime DEFAULT NULL,
  `first_half_extra` int(11) DEFAULT 0,
  `second_half_extra` int(11) DEFAULT 0,
  `first_half_duration` int(11) DEFAULT 0,
  `saison` varchar(20) NOT NULL DEFAULT '2024-2025',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
INSERT INTO `matches` VALUES (1,'tournoi','phase de poule','2025-05-14','20:00:00','Aboutou FC','Ehounou FC',NULL,NULL,'melekoukro','completed',5400,NULL,14436,0,0,NULL,'not_started',0,NULL,NULL,NULL,0,0,0,'2024-2025'),(2,'tournoi','phase de poule','2025-05-18','11:05:00','Piment Rouge','Ehounou FC',NULL,NULL,'melekoukro','completed',5400,NULL,9199,0,0,NULL,'not_started',0,NULL,NULL,NULL,0,0,0,'2024-2025'),(16,'tournoi','phase de poule','2025-05-21','10:06:00','Ehounou FC','Ngalwa FC',NULL,NULL,'melekoukro','completed',3000,NULL,1500,0,0,1,'ended',0,NULL,NULL,'2025-05-22 10:32:39',0,0,0,'2024-2025'),(17,'tournoi','phase de poule','2025-05-21','10:44:00','Ehounou FC','Ngalwa FC',NULL,NULL,'melekoukro','completed',3000,NULL,1500,0,0,1,'ended',0,NULL,NULL,NULL,120,0,0,'2024-2025'),(18,'tournoi','phase de poule','2025-05-22','11:34:00','Ngalwa FC','Ehounou FC',1,3,'melekoukro','completed',3000,NULL,1620,0,0,1,'ended',0,NULL,NULL,NULL,0,120,1500,'2024-2025'),(19,'tournoi','phase de poule','2025-05-22','12:37:00','Ehounou FC','Ngalwa FC',1,1,'melekoukro','completed',3000,NULL,1500,0,0,1,'ended',0,NULL,NULL,NULL,120,0,1620,'2024-2025'),(20,'tournoi','phase de poule','2025-05-22','15:52:00','Ehounou FC','Ngalwa FC',0,0,'melekoukro','completed',3000,NULL,1500,0,0,1,'ended',0,NULL,NULL,NULL,0,0,1500,'2024-2025');
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medias_actualites`
--

DROP TABLE IF EXISTS `medias_actualites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medias_actualites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `media_url` varchar(255) NOT NULL,
  `media_type` enum('image','video','document') NOT NULL,
  `categorie` enum('match','concours','cours','general') NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID lié à un match/concours spécifique',
  `statut` enum('brouillon','publie','archive') DEFAULT 'publie',
  `auteur_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `auteur_id` (`auteur_id`),
  CONSTRAINT `medias_actualites_ibfk_1` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medias_actualites`
--

LOCK TABLES `medias_actualites` WRITE;
/*!40000 ALTER TABLE `medias_actualites` DISABLE KEYS */;
INSERT INTO `medias_actualites` VALUES (4,'sport','oui ça va ','682ae352e156a_WhatsAppImage2025-01-1521.40.30_ae99abb7.jpg','image','general',0,'publie',3,'2025-05-19 07:52:50');
/*!40000 ALTER TABLE `medias_actualites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` enum('sport','culture','education','general') NOT NULL,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `notification_type` enum('system','event','message') NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_absences`
--

DROP TABLE IF EXISTS `player_absences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_absences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `player_id` (`player_id`),
  CONSTRAINT `player_absences_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_absences`
--

LOCK TABLES `player_absences` WRITE;
/*!40000 ALTER TABLE `player_absences` DISABLE KEYS */;
/*!40000 ALTER TABLE `player_absences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `position` enum('gardien','defenseur','milieu','attaquant') NOT NULL,
  `jersey_number` int(11) DEFAULT NULL,
  `height` float DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `stats_goals` int(11) DEFAULT 0,
  `stats_assists` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `players_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `players`
--

LOCK TABLES `players` WRITE;
/*!40000 ALTER TABLE `players` DISABLE KEYS */;
INSERT INTO `players` VALUES (9,NULL,15,'',1,NULL,NULL,0,0,'2025-05-13 20:35:46','flyrix',''),(10,NULL,15,'',7,NULL,NULL,0,0,'2025-05-13 20:35:46','alexi',''),(11,NULL,15,'',8,NULL,NULL,0,0,'2025-05-13 20:35:46','belge',''),(12,NULL,15,'',9,NULL,NULL,0,0,'2025-05-13 20:35:46','max',''),(13,NULL,15,'',10,NULL,NULL,0,0,'2025-05-13 20:35:46','ismo',''),(14,NULL,15,'',11,NULL,NULL,0,0,'2025-05-13 20:35:46','marc',''),(15,NULL,15,'',12,NULL,NULL,0,0,'2025-05-13 20:35:46','martin',''),(16,NULL,17,'attaquant',10,NULL,NULL,0,0,'2025-05-13 21:09:07','alain',''),(17,NULL,17,'attaquant',9,NULL,NULL,0,0,'2025-05-13 21:09:07','marc',''),(18,NULL,17,'attaquant',11,NULL,NULL,0,0,'2025-05-13 21:09:07','mix',''),(19,NULL,17,'attaquant',22,NULL,NULL,0,0,'2025-05-13 21:09:07','idriss',''),(20,NULL,17,'attaquant',24,NULL,NULL,0,0,'2025-05-13 21:09:07','dramane',''),(21,NULL,17,'attaquant',12,NULL,NULL,0,0,'2025-05-13 21:09:07','salam',''),(22,NULL,17,'attaquant',26,NULL,NULL,0,0,'2025-05-13 21:09:07','maxim',''),(23,NULL,NULL,'milieu',10,NULL,NULL,0,0,'2025-05-13 21:22:46','Raph',''),(24,NULL,NULL,'attaquant',7,NULL,NULL,0,0,'2025-05-13 21:22:46','griff',''),(25,NULL,NULL,'defenseur',2,NULL,NULL,0,0,'2025-05-13 21:22:46','Cesard',''),(26,NULL,NULL,'gardien',1,NULL,NULL,0,0,'2025-05-13 21:22:46','Charle',''),(27,NULL,NULL,'attaquant',11,NULL,NULL,0,0,'2025-05-13 21:22:48','idriss',''),(28,NULL,NULL,'attaquant',9,NULL,NULL,0,0,'2025-05-13 21:22:48','Marco',''),(29,NULL,NULL,'milieu',23,NULL,NULL,0,0,'2025-05-13 21:22:48','Maxim',''),(30,NULL,NULL,'milieu',10,NULL,NULL,0,0,'2025-05-13 21:24:40','Raph',''),(31,NULL,NULL,'attaquant',7,NULL,NULL,0,0,'2025-05-13 21:24:40','griff',''),(32,NULL,NULL,'defenseur',2,NULL,NULL,0,0,'2025-05-13 21:24:40','Cesard',''),(33,NULL,NULL,'gardien',1,NULL,NULL,0,0,'2025-05-13 21:24:40','Charle',''),(34,NULL,NULL,'attaquant',11,NULL,NULL,0,0,'2025-05-13 21:24:40','idriss',''),(35,NULL,NULL,'attaquant',9,NULL,NULL,0,0,'2025-05-13 21:24:40','Marco',''),(36,NULL,NULL,'milieu',23,NULL,NULL,0,0,'2025-05-13 21:24:40','Maxim',''),(37,NULL,18,'defenseur',10,NULL,NULL,0,0,'2025-05-13 21:28:01','Raph',''),(38,NULL,18,'attaquant',7,NULL,NULL,0,0,'2025-05-13 21:28:01','griff',''),(39,NULL,18,'defenseur',2,NULL,NULL,0,0,'2025-05-13 21:28:01','Cesard',''),(40,NULL,18,'gardien',1,NULL,NULL,0,0,'2025-05-13 21:28:01','Charle',''),(41,NULL,18,'attaquant',11,NULL,NULL,0,0,'2025-05-13 21:28:01','idriss',''),(42,NULL,18,'attaquant',9,NULL,NULL,0,0,'2025-05-13 21:28:01','Marco',''),(43,NULL,18,'milieu',23,NULL,NULL,0,0,'2025-05-13 21:28:01','Maxim',''),(44,NULL,17,'gardien',NULL,NULL,NULL,0,0,'2025-05-14 19:45:22','kacou',''),(52,NULL,21,'defenseur',2,NULL,NULL,0,0,'2025-05-19 19:10:54','A',NULL),(53,NULL,21,'gardien',3,NULL,NULL,0,0,'2025-05-19 19:10:54','B',NULL),(54,NULL,21,'milieu',4,NULL,NULL,0,0,'2025-05-19 19:10:54','C',NULL),(55,NULL,21,'milieu',7,NULL,NULL,0,0,'2025-05-19 19:10:54','D',NULL),(56,NULL,21,'attaquant',9,NULL,NULL,0,0,'2025-05-19 19:10:54','E',NULL),(57,NULL,21,'attaquant',13,NULL,NULL,0,0,'2025-05-19 19:10:54','Kouame','uploads/players/player_21_682b823e24578.jpg'),(58,NULL,21,'milieu',15,NULL,NULL,0,0,'2025-05-19 19:10:54','Fidele','uploads/players/player_21_682b823e8d2ad.jpg');
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poules`
--

DROP TABLE IF EXISTS `poules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `competition` varchar(50) DEFAULT NULL,
  `saison` varchar(9) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poules`
--

LOCK TABLES `poules` WRITE;
/*!40000 ALTER TABLE `poules` DISABLE KEYS */;
INSERT INTO `poules` VALUES (1,'pouleA','senior',NULL,''),(2,'pouleB','senior',NULL,''),(3,'pouleC','senior',NULL,'');
/*!40000 ALTER TABLE `poules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registration_periods`
--

DROP TABLE IF EXISTS `registration_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registration_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_date` datetime NOT NULL COMMENT 'When registration opens',
  `end_date` datetime NOT NULL COMMENT 'When registration closes',
  `category` varchar(50) NOT NULL COMMENT 'Tournament category this applies to',
  `closed_message` text DEFAULT NULL COMMENT 'Message to show when closed',
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Whether this period is active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registration_periods`
--

LOCK TABLES `registration_periods` WRITE;
/*!40000 ALTER TABLE `registration_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `registration_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `resource_type` enum('document','video','link') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resources`
--

LOCK TABLES `resources` WRITE;
/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `standings`
--

DROP TABLE IF EXISTS `standings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `standings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competition` varchar(20) NOT NULL,
  `team_name` varchar(50) NOT NULL,
  `played` int(11) NOT NULL,
  `won` int(11) NOT NULL,
  `drawn` int(11) NOT NULL,
  `lost` int(11) NOT NULL,
  `goals_for` int(11) NOT NULL,
  `goals_against` int(11) NOT NULL,
  `goal_difference` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `form` varchar(5) NOT NULL COMMENT 'W=Win, D=Draw, L=Loss (e.g. WWDLW)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `competition` (`competition`,`team_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `standings`
--

LOCK TABLES `standings` WRITE;
/*!40000 ALTER TABLE `standings` DISABLE KEYS */;
/*!40000 ALTER TABLE `standings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `level` enum('primary','secondary','tertiary') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_name` varchar(100) NOT NULL,
  `category` enum('senior','junior','feminine') NOT NULL,
  `location` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `coach_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `logo_path` varchar(255) DEFAULT NULL,
  `manager_name` varchar(100) NOT NULL,
  `manager_email` varchar(100) NOT NULL,
  `manager_phone` varchar(20) NOT NULL,
  `poule_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coach_id` (`coach_id`),
  CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams`
--

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
INSERT INTO `teams` VALUES (15,'Piment Rouge','senior','melekoukro','',NULL,'2025-05-13 20:35:46',NULL,'','','',2),(17,'Ehounou FC','senior','Adiake','',NULL,'2025-05-13 21:09:07','ehounou-fc.png','Ehounou','adiake@gmail.com','0758345313',1),(18,'Aboutou FC','senior','Aboutou','',NULL,'2025-05-13 21:28:01',NULL,'','','',3),(21,'Ngalwa FC','senior','n&#39;galwa','',NULL,'2025-05-19 19:10:54','ngalwa-fc.png','pele','adiake@gmail.com','1234567890',1);
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_purchases`
--

DROP TABLE IF EXISTS `ticket_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `buyer_name` varchar(100) NOT NULL,
  `buyer_email` varchar(100) NOT NULL,
  `buyer_phone` varchar(20) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('paypal','mobile_money','cash') NOT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_purchases_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_purchases`
--

LOCK TABLES `ticket_purchases` WRITE;
/*!40000 ALTER TABLE `ticket_purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `ticket_type` enum('standard','vip','backstage') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity_available` int(11) NOT NULL,
  `quantity_sold` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `contest_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `photo_profil` varchar(255) DEFAULT NULL,
  `role` enum('admin','utilisateur','membre') DEFAULT 'utilisateur',
  `date_inscription` datetime NOT NULL DEFAULT current_timestamp(),
  `derniere_connexion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `adresse` text DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `centres_interet` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'kacoujunior98@gmail.com','$2y$10$7iyvKHJL2P.McF97SUaP8u1NWFdHJ.O.V2TPK3ray50iKj/vq02ee','kacou','junior','0788192480',NULL,'admin','2025-05-13 15:54:36','2025-05-14 18:14:11','kacoujunior98@gmail.com','2000-05-18','[\"courses\"]'),(4,'kacoujunior68@gmail.com','$2y$10$RyF07nTDJUk4vgSGtJWmUOhypBoMSarqdadIe9zyGQ/cYR2riSFJm','Junior','kacou','0788192480','uploads/profiles/profile_682cd954db3d66.35563112.jpg','utilisateur','2025-05-13 22:30:09','2025-05-20 19:34:44','Yopougon','2000-05-18','[\"football\"]'),(5,'kacoujunior641@gmail.com','$2y$10$eDW0pDe4GbHbAakR7L6c..NMspX9liQmCAx7vczWgONr0UyKwLAlG','Leslie','kacou','0788192480',NULL,'utilisateur','2025-05-13 22:58:50','2025-05-13 22:58:50','kacoujunior98@gmail.com','2005-05-14','[\"miss\"]');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `voter_ip` varchar(45) DEFAULT NULL,
  `voter_id` int(11) DEFAULT NULL,
  `vote_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `candidate_id` (`candidate_id`),
  KEY `voter_id` (`voter_id`),
  CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`voter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votes`
--

LOCK TABLES `votes` WRITE;
/*!40000 ALTER TABLE `votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `votes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-17  0:14:52
