CREATE DATABASE  IF NOT EXISTS `prism` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `prism`;
-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: prism
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `approval_logs_table`
--

DROP TABLE IF EXISTS `approval_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_logs_table` (
  `approval_log_id` bigint NOT NULL AUTO_INCREMENT,
  `approval_log_reference_type` varchar(255) DEFAULT NULL,
  `approval_log_reference_id` bigint DEFAULT NULL,
  `approval_log_approved_by` bigint DEFAULT NULL,
  `approval_log_approval_status` enum('Approved','Rejected') DEFAULT NULL,
  `approval_log_approval_remarks` text,
  `approval_log_approved_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_log_id`),
  KEY `approval_log_approved_by` (`approval_log_approved_by`),
  CONSTRAINT `approval_logs_table_ibfk_1` FOREIGN KEY (`approval_log_approved_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_logs_table`
--

LOCK TABLES `approval_logs_table` WRITE;
/*!40000 ALTER TABLE `approval_logs_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `approval_logs_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attachments_table`
--

DROP TABLE IF EXISTS `attachments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments_table` (
  `attachment_id` bigint NOT NULL AUTO_INCREMENT,
  `attachment_reference_type` varchar(255) DEFAULT NULL,
  `attachment_reference_id` bigint DEFAULT NULL,
  `attachment_file_name` varchar(255) DEFAULT NULL,
  `attachment_file_path` text,
  `attachment_uploaded_by` bigint DEFAULT NULL,
  `attachment_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `attachment_uploaded_by` (`attachment_uploaded_by`),
  CONSTRAINT `attachments_table_ibfk_1` FOREIGN KEY (`attachment_uploaded_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments_table`
--

LOCK TABLES `attachments_table` WRITE;
/*!40000 ALTER TABLE `attachments_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `attachments_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs_table`
--

DROP TABLE IF EXISTS `audit_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs_table` (
  `audit_log_id` bigint NOT NULL AUTO_INCREMENT,
  `audit_log_user_id` bigint DEFAULT NULL,
  `audit_log_action` varchar(255) DEFAULT NULL,
  `audit_log_module` varchar(255) DEFAULT NULL,
  `audit_log_table_name` varchar(255) DEFAULT NULL,
  `audit_log_reference_id` bigint DEFAULT NULL,
  `audit_log_description` text,
  `audit_log_ip_address` varchar(255) DEFAULT NULL,
  `audit_log_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_log_id`),
  KEY `audit_log_user_id` (`audit_log_user_id`),
  CONSTRAINT `audit_logs_table_ibfk_1` FOREIGN KEY (`audit_log_user_id`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs_table`
--

LOCK TABLES `audit_logs_table` WRITE;
/*!40000 ALTER TABLE `audit_logs_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authority_to_purchase_items_table`
--

DROP TABLE IF EXISTS `authority_to_purchase_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authority_to_purchase_items_table` (
  `atp_item_id` bigint NOT NULL AUTO_INCREMENT,
  `authority_purchase_id` bigint DEFAULT NULL,
  `atp_quantity` int DEFAULT NULL,
  `atp_unit` varchar(50) DEFAULT NULL,
  `atp_description` text,
  `atp_unit_price` decimal(12,2) DEFAULT NULL,
  `atp_amount` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`atp_item_id`),
  KEY `authority_purchase_id` (`authority_purchase_id`),
  CONSTRAINT `authority_to_purchase_items_table_ibfk_1` FOREIGN KEY (`authority_purchase_id`) REFERENCES `authority_to_purchase_table` (`authority_purchase_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authority_to_purchase_items_table`
--

LOCK TABLES `authority_to_purchase_items_table` WRITE;
/*!40000 ALTER TABLE `authority_to_purchase_items_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `authority_to_purchase_items_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authority_to_purchase_table`
--

DROP TABLE IF EXISTS `authority_to_purchase_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authority_to_purchase_table` (
  `authority_purchase_id` bigint NOT NULL AUTO_INCREMENT,
  `authority_purchase_ris_id` bigint DEFAULT NULL,
  `authority_purchase_form_number` varchar(100) DEFAULT NULL,
  `authority_purchase_supplier_id` bigint DEFAULT NULL,
  `authority_purchase_date` date DEFAULT NULL,
  `authority_purchase_received_by_name` varchar(255) DEFAULT NULL,
  `authority_purchase_reference_po_no` varchar(100) DEFAULT NULL,
  `authority_purchase_authorized_by_signature` text,
  `authority_purchase_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `authority_purchase_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`authority_purchase_id`),
  KEY `authority_purchase_ris_id` (`authority_purchase_ris_id`),
  KEY `authority_purchase_supplier_id` (`authority_purchase_supplier_id`),
  CONSTRAINT `authority_to_purchase_table_ibfk_1` FOREIGN KEY (`authority_purchase_ris_id`) REFERENCES `requisition_issue_slip_table` (`ris_id`),
  CONSTRAINT `authority_to_purchase_table_ibfk_2` FOREIGN KEY (`authority_purchase_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authority_to_purchase_table`
--

LOCK TABLES `authority_to_purchase_table` WRITE;
/*!40000 ALTER TABLE `authority_to_purchase_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `authority_to_purchase_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `borrowing_records_table`
--

DROP TABLE IF EXISTS `borrowing_records_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `borrowing_records_table` (
  `borrowing_record_id` bigint NOT NULL AUTO_INCREMENT,
  `borrowing_equipment_id` bigint DEFAULT NULL,
  `borrowing_borrower_name` varchar(255) DEFAULT NULL,
  `borrowing_borrower_department` varchar(255) DEFAULT NULL,
  `borrowing_quantity` int DEFAULT NULL,
  `borrowing_equipment_condition` varchar(255) DEFAULT NULL,
  `borrowing_date` date DEFAULT NULL,
  `borrowing_expected_return_date` date DEFAULT NULL,
  `borrowing_actual_return_date` date DEFAULT NULL,
  `borrowing_purpose` text,
  `borrowing_destination_location` varchar(255) DEFAULT NULL,
  `borrowing_authorized_by` varchar(255) DEFAULT NULL,
  `borrowing_remarks` text,
  `borrowing_status` enum('Borrowed','Returned','Overdue') DEFAULT 'Borrowed',
  `borrowing_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`borrowing_record_id`),
  KEY `borrowing_equipment_id` (`borrowing_equipment_id`),
  CONSTRAINT `borrowing_records_table_ibfk_1` FOREIGN KEY (`borrowing_equipment_id`) REFERENCES `equipment_table` (`equipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `borrowing_records_table`
--

LOCK TABLES `borrowing_records_table` WRITE;
/*!40000 ALTER TABLE `borrowing_records_table` DISABLE KEYS */;
INSERT INTO `borrowing_records_table` VALUES (1,4,'Edzel A. Lampidas','WLC',1,'Good','2026-07-10','2026-07-22',NULL,'Use in pina competition','Brgy. Sn Pedro Ormoc City Leyte','Mrs. Sheena Joy Muyuela',NULL,'Borrowed','2026-07-09 15:40:17'),(2,5,'Rachel G. Gumabon','EVSU',1,'Good','2026-07-11','2026-07-15',NULL,'to be use in filming','EVSU Court','Mrs. Rona Mira L. Lucanas','need assesment','Borrowed','2026-07-09 16:22:11');
/*!40000 ALTER TABLE `borrowing_records_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buildings_table`
--

DROP TABLE IF EXISTS `buildings_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buildings_table` (
  `building_id` bigint NOT NULL AUTO_INCREMENT,
  `building_name` varchar(255) DEFAULT NULL,
  `building_logo` varchar(255) DEFAULT NULL,
  `building_address` text,
  `building_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `building_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`building_id`),
  UNIQUE KEY `uq_single_building_name` (`building_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buildings_table`
--

LOCK TABLES `buildings_table` WRITE;
/*!40000 ALTER TABLE `buildings_table` DISABLE KEYS */;
INSERT INTO `buildings_table` VALUES (1,'STI College Ormoc',NULL,NULL,'2026-06-10 11:08:48','2026-06-26 06:29:53');
/*!40000 ALTER TABLE `buildings_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campus_setup_settings_table`
--

DROP TABLE IF EXISTS `campus_setup_settings_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campus_setup_settings_table` (
  `campus_setup_setting_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campus_setup_pin_hash` text COLLATE utf8mb4_unicode_ci,
  `campus_setup_pin_updated_by` bigint unsigned DEFAULT NULL,
  `campus_setup_pin_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`campus_setup_setting_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campus_setup_settings_table`
--

LOCK TABLES `campus_setup_settings_table` WRITE;
/*!40000 ALTER TABLE `campus_setup_settings_table` DISABLE KEYS */;
INSERT INTO `campus_setup_settings_table` VALUES (1,'$2y$12$BZaTkOR7Yt26iO4xUqV4Y.Fz3U/QieyUKnwOqzWAknIgcb3i0YGxC',2,'2026-07-03 11:23:49');
/*!40000 ALTER TABLE `campus_setup_settings_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disposal_records_table`
--

DROP TABLE IF EXISTS `disposal_records_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `disposal_records_table` (
  `disposal_record_id` bigint NOT NULL AUTO_INCREMENT,
  `disposal_equipment_id` bigint DEFAULT NULL,
  `disposal_reason` text,
  `disposal_area_location` varchar(255) DEFAULT NULL,
  `disposal_approved_by` bigint DEFAULT NULL,
  `disposal_disposed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`disposal_record_id`),
  KEY `disposal_equipment_id` (`disposal_equipment_id`),
  KEY `disposal_approved_by` (`disposal_approved_by`),
  CONSTRAINT `disposal_records_table_ibfk_1` FOREIGN KEY (`disposal_equipment_id`) REFERENCES `equipment_table` (`equipment_id`),
  CONSTRAINT `disposal_records_table_ibfk_2` FOREIGN KEY (`disposal_approved_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disposal_records_table`
--

LOCK TABLES `disposal_records_table` WRITE;
/*!40000 ALTER TABLE `disposal_records_table` DISABLE KEYS */;
INSERT INTO `disposal_records_table` VALUES (2,14,'Obsolete','Storage',1,'2026-07-07 21:48:42');
/*!40000 ALTER TABLE `disposal_records_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_categories_table`
--

DROP TABLE IF EXISTS `equipment_categories_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_categories_table` (
  `equipment_category_id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_category_name` varchar(255) DEFAULT NULL,
  `equipment_category_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`equipment_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_categories_table`
--

LOCK TABLES `equipment_categories_table` WRITE;
/*!40000 ALTER TABLE `equipment_categories_table` DISABLE KEYS */;
INSERT INTO `equipment_categories_table` VALUES (1,'Computer','2026-06-10 11:11:40'),(2,'Projector','2026-06-10 11:11:40'),(3,'Air Conditioner','2026-06-10 11:11:40'),(4,'Printer','2026-06-10 11:11:40'),(5,'TV','2026-07-09 15:17:26');
/*!40000 ALTER TABLE `equipment_categories_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_maintenance_history_table`
--

DROP TABLE IF EXISTS `equipment_maintenance_history_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_maintenance_history_table` (
  `equipment_maintenance_history_id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_maintenance_equipment_id` bigint DEFAULT NULL,
  `equipment_maintenance_report_id` bigint DEFAULT NULL,
  `equipment_maintenance_personnel_id` bigint DEFAULT NULL,
  `equipment_maintenance_findings` text,
  `equipment_maintenance_repair_action` text,
  `equipment_maintenance_replacement_remarks` text,
  `equipment_maintenance_status` enum('Pending','Processing','Resolved','For Replacement') DEFAULT NULL,
  `equipment_maintenance_completed_at` datetime DEFAULT NULL,
  `equipment_maintenance_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `equipment_maintenance_proof_image` text,
  PRIMARY KEY (`equipment_maintenance_history_id`),
  KEY `equipment_maintenance_equipment_id` (`equipment_maintenance_equipment_id`),
  KEY `equipment_maintenance_report_id` (`equipment_maintenance_report_id`),
  KEY `equipment_maintenance_personnel_id` (`equipment_maintenance_personnel_id`),
  CONSTRAINT `equipment_maintenance_history_table_ibfk_1` FOREIGN KEY (`equipment_maintenance_equipment_id`) REFERENCES `equipment_table` (`equipment_id`),
  CONSTRAINT `equipment_maintenance_history_table_ibfk_2` FOREIGN KEY (`equipment_maintenance_report_id`) REFERENCES `reports_table` (`report_id`),
  CONSTRAINT `equipment_maintenance_history_table_ibfk_3` FOREIGN KEY (`equipment_maintenance_personnel_id`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_maintenance_history_table`
--

LOCK TABLES `equipment_maintenance_history_table` WRITE;
/*!40000 ALTER TABLE `equipment_maintenance_history_table` DISABLE KEYS */;
INSERT INTO `equipment_maintenance_history_table` VALUES (1,4,NULL,NULL,'Loose HDMI Port','HDMI Port Replaced',NULL,'Resolved',NULL,'2026-06-23 09:22:56',NULL),(2,6,NULL,1,'Need maintenance','Resolved',NULL,'Resolved','2026-06-24 00:12:42','2026-06-24 00:12:42','maintenance-proofs/ig4UcdG2yYCDeS4dkK2hGVr5GNprHLv9ll5vKuIn.png');
/*!40000 ALTER TABLE `equipment_maintenance_history_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_table`
--

DROP TABLE IF EXISTS `equipment_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_table` (
  `equipment_id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_category_id` bigint DEFAULT NULL,
  `equipment_room_id` bigint DEFAULT NULL,
  `workstation_slot_id` bigint unsigned DEFAULT NULL,
  `workstation_id` bigint DEFAULT NULL,
  `workstation_template_slot_id` bigint DEFAULT NULL,
  `equipment_supplier_id` bigint DEFAULT NULL,
  `equipment_qr_code` text,
  `equipment_image` varchar(255) DEFAULT NULL,
  `equipment_asset_tag` varchar(255) DEFAULT NULL,
  `equipment_name` varchar(255) DEFAULT NULL,
  `equipment_brand_name` varchar(255) DEFAULT NULL,
  `equipment_model` varchar(255) DEFAULT NULL,
  `equipment_serial_number` varchar(255) DEFAULT NULL,
  `equipment_quantity` int DEFAULT '1',
  `equipment_tracking_mode` enum('Bulk','Individual') NOT NULL DEFAULT 'Bulk',
  `equipment_condition_status` enum('Good','Damaged','Under Maintenance','Disposed') DEFAULT 'Good',
  `equipment_inventory_status` enum('Active','Under Maintenance','Borrowed','For Replacement','Disposed') DEFAULT 'Active',
  `equipment_purchase_date` date DEFAULT NULL,
  `equipment_purchase_cost` decimal(12,2) DEFAULT NULL,
  `equipment_acquired_date` date DEFAULT NULL,
  `equipment_warranty_expiration` date DEFAULT NULL,
  `equipment_current_location` varchar(255) DEFAULT NULL,
  `equipment_placement_zone` varchar(50) DEFAULT NULL,
  `equipment_position_x` tinyint unsigned DEFAULT NULL,
  `equipment_position_y` tinyint unsigned DEFAULT NULL,
  `equipment_width` int NOT NULL DEFAULT '120',
  `equipment_height` int NOT NULL DEFAULT '96',
  `equipment_rotation` smallint NOT NULL DEFAULT '0',
  `equipment_is_borrowable` tinyint(1) DEFAULT '1',
  `equipment_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`equipment_id`),
  KEY `equipment_category_id` (`equipment_category_id`),
  KEY `equipment_room_id` (`equipment_room_id`),
  KEY `fk_equipment_supplier` (`equipment_supplier_id`),
  KEY `idx_equipment_workstation_id` (`workstation_id`),
  KEY `idx_equipment_workstation_slot_id` (`workstation_template_slot_id`),
  KEY `equipment_table_workstation_slot_id_foreign` (`workstation_slot_id`),
  CONSTRAINT `equipment_table_ibfk_1` FOREIGN KEY (`equipment_category_id`) REFERENCES `equipment_categories_table` (`equipment_category_id`),
  CONSTRAINT `equipment_table_ibfk_2` FOREIGN KEY (`equipment_room_id`) REFERENCES `rooms_table` (`room_id`),
  CONSTRAINT `equipment_table_workstation_slot_id_foreign` FOREIGN KEY (`workstation_slot_id`) REFERENCES `workstation_slots_table` (`workstation_slot_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_equipment_supplier` FOREIGN KEY (`equipment_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`),
  CONSTRAINT `fk_equipment_workstation` FOREIGN KEY (`workstation_id`) REFERENCES `workstations_table` (`workstation_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_equipment_workstation_slot` FOREIGN KEY (`workstation_template_slot_id`) REFERENCES `workstation_template_slots_table` (`workstation_template_slot_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_table`
--

LOCK TABLES `equipment_table` WRITE;
/*!40000 ALTER TABLE `equipment_table` DISABLE KEYS */;
INSERT INTO `equipment_table` VALUES (4,1,8,NULL,NULL,NULL,NULL,'QR-000004',NULL,NULL,'Desktop Computer','Acer',NULL,NULL,20,'Bulk','Good','Borrowed',NULL,NULL,NULL,NULL,'Left Row Pods','Left Row Pods',18,50,80,80,0,1,'2026-07-08 17:20:19'),(5,2,8,NULL,NULL,NULL,NULL,'QR-000005',NULL,NULL,'LCD Projector','Epson',NULL,NULL,1,'Bulk','Good','Borrowed',NULL,NULL,NULL,NULL,'Center Ceiling','Center Ceiling',50,48,50,80,0,1,'2026-07-08 17:20:19'),(6,3,6,NULL,NULL,NULL,NULL,'QR-000006',NULL,NULL,'Split Type Air Conditioner','Samsung',NULL,NULL,1,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Center Ceiling','Center Ceiling',50,48,50,80,0,0,'2026-07-08 17:20:19'),(7,2,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'LCD Projector',NULL,NULL,NULL,1,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Center Ceiling','Center Ceiling',50,48,120,96,0,0,'2026-07-08 17:20:19'),(8,3,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Split Type Air Conditioner',NULL,NULL,NULL,1,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Rear Wall','Rear Wall',50,85,120,96,0,0,'2026-07-08 17:20:19'),(9,1,12,NULL,NULL,NULL,NULL,'QR-000009',NULL,NULL,'Desktop Computer',NULL,NULL,NULL,20,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Front Wall','Front Wall',50,15,120,96,0,0,'2026-07-08 17:20:19'),(10,NULL,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'TV',NULL,NULL,NULL,1,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Left Row Pods','Left Row Pods',18,50,100,148,0,0,'2026-07-08 17:20:19'),(11,NULL,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'WhiteBoard',NULL,NULL,NULL,1,'Individual','Good','Active',NULL,NULL,NULL,NULL,'Right Row Pods','Right Row Pods',82,50,120,96,0,0,'2026-07-08 17:20:19'),(12,1,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'TV',NULL,NULL,NULL,1,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Front Wall','Front Wall',50,15,120,96,0,0,'2026-07-08 17:20:19'),(13,1,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'TV',NULL,NULL,NULL,1,'Individual','Good','Active',NULL,NULL,NULL,NULL,'Center Ceiling','Center Ceiling',50,48,120,96,0,0,'2026-07-08 17:20:19'),(14,1,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Keyboard',NULL,NULL,NULL,1,'Individual','Good','Disposed',NULL,NULL,NULL,NULL,'Front Wall','Front Wall',50,15,50,80,0,0,'2026-07-08 17:20:19'),(15,1,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Keyboard',NULL,NULL,NULL,1,'Individual','Good','Active',NULL,NULL,NULL,NULL,'Front Wall','Front Wall',50,15,82,80,0,1,'2026-07-08 17:20:19'),(16,1,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Mouse',NULL,NULL,NULL,40,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Left Row Pods','Left Row Pods',18,50,63,80,0,0,'2026-07-08 17:20:19'),(17,1,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'TV',NULL,NULL,NULL,1,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Front Wall','Front Wall',50,15,120,96,0,0,'2026-07-08 17:20:19'),(18,3,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'TV',NULL,NULL,NULL,1,'Bulk','Good','Active',NULL,NULL,NULL,NULL,'Front Wall','Front Wall',50,15,120,96,0,0,'2026-07-08 17:20:19');
/*!40000 ALTER TABLE `equipment_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_transfer_history_table`
--

DROP TABLE IF EXISTS `equipment_transfer_history_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_transfer_history_table` (
  `transfer_id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_id` bigint NOT NULL,
  `from_room_id` bigint DEFAULT NULL,
  `to_room_id` bigint NOT NULL,
  `transferred_by` bigint DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` bigint DEFAULT NULL,
  PRIMARY KEY (`transfer_id`),
  KEY `fk_transfer_equipment` (`equipment_id`),
  KEY `fk_transfer_from_room` (`from_room_id`),
  KEY `fk_transfer_to_room` (`to_room_id`),
  CONSTRAINT `fk_transfer_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_table` (`equipment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transfer_from_room` FOREIGN KEY (`from_room_id`) REFERENCES `rooms_table` (`room_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transfer_to_room` FOREIGN KEY (`to_room_id`) REFERENCES `rooms_table` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_transfer_history_table`
--

LOCK TABLES `equipment_transfer_history_table` WRITE;
/*!40000 ALTER TABLE `equipment_transfer_history_table` DISABLE KEYS */;
INSERT INTO `equipment_transfer_history_table` VALUES (1,4,8,5,NULL,NULL,'2026-06-23 01:18:15',NULL),(2,4,5,8,NULL,NULL,'2026-06-23 09:38:47',NULL);
/*!40000 ALTER TABLE `equipment_transfer_history_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `floors_table`
--

DROP TABLE IF EXISTS `floors_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `floors_table` (
  `floor_id` bigint NOT NULL AUTO_INCREMENT,
  `floor_building_id` bigint DEFAULT NULL,
  `floor_level` varchar(50) NOT NULL,
  `floor_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`floor_id`),
  KEY `floor_building_id` (`floor_building_id`),
  CONSTRAINT `floors_table_ibfk_1` FOREIGN KEY (`floor_building_id`) REFERENCES `buildings_table` (`building_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `floors_table`
--

LOCK TABLES `floors_table` WRITE;
/*!40000 ALTER TABLE `floors_table` DISABLE KEYS */;
INSERT INTO `floors_table` VALUES (1,1,'2nd Floor','2026-06-10 11:10:33'),(2,1,'3rd Floor','2026-06-10 11:10:33');
/*!40000 ALTER TABLE `floors_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issue_templates_table`
--

DROP TABLE IF EXISTS `issue_templates_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `issue_templates_table` (
  `issue_template_id` bigint NOT NULL AUTO_INCREMENT,
  `issue_template_category_id` bigint DEFAULT NULL,
  `issue_template_name` varchar(255) DEFAULT NULL,
  `issue_template_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`issue_template_id`),
  KEY `issue_template_category_id` (`issue_template_category_id`),
  CONSTRAINT `issue_templates_table_ibfk_1` FOREIGN KEY (`issue_template_category_id`) REFERENCES `equipment_categories_table` (`equipment_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issue_templates_table`
--

LOCK TABLES `issue_templates_table` WRITE;
/*!40000 ALTER TABLE `issue_templates_table` DISABLE KEYS */;
INSERT INTO `issue_templates_table` VALUES (1,1,'No Power','2026-06-11 15:32:59'),(2,1,'Broken Monitor','2026-06-11 15:32:59'),(3,1,'Keyboard Not Working','2026-06-11 15:32:59'),(4,1,'Mouse Defective','2026-06-11 15:32:59'),(5,1,'Slow Performance','2026-06-11 15:32:59'),(6,1,'Cannot Login','2026-06-11 15:32:59'),(7,1,'Network Connection Lost','2026-06-11 15:32:59'),(8,2,'No Display','2026-06-11 15:32:59'),(9,2,'HDMI Not Detected','2026-06-11 15:32:59'),(10,2,'Projector Flickering','2026-06-11 15:32:59'),(11,2,'Blurry Projection','2026-06-11 15:32:59'),(12,2,'Projector Overheating','2026-06-11 15:32:59'),(13,3,'Aircon Not Cooling','2026-06-11 15:32:59'),(14,3,'Water Leakage','2026-06-11 15:32:59'),(15,3,'Strange Noise','2026-06-11 15:32:59'),(16,3,'Aircon Not Turning On','2026-06-11 15:32:59'),(17,3,'Remote Control Not Working','2026-06-11 15:32:59'),(18,4,'Paper Jam','2026-06-11 15:32:59'),(19,4,'Ink Cartridge Issue','2026-06-11 15:32:59'),(20,4,'Printer Offline','2026-06-11 15:32:59'),(21,4,'Not Printing','2026-06-11 15:32:59'),(22,4,'Poor Print Quality','2026-06-11 15:32:59');
/*!40000 ALTER TABLE `issue_templates_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `liquidation_report_items_table`
--

DROP TABLE IF EXISTS `liquidation_report_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `liquidation_report_items_table` (
  `liquidation_item_id` bigint NOT NULL AUTO_INCREMENT,
  `liquidation_report_id` bigint DEFAULT NULL,
  `liquidation_item_particulars` text,
  `liquidation_item_particulars_amount` decimal(12,2) DEFAULT NULL,
  `liquidation_item_actual_breakdown_amount` decimal(12,2) DEFAULT NULL,
  `liquidation_item_actual_total_amount` decimal(12,2) DEFAULT NULL,
  `liquidation_item_variance` decimal(12,2) DEFAULT '0.00',
  `liquidation_item_ref_no` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`liquidation_item_id`),
  KEY `liquidation_report_id` (`liquidation_report_id`),
  CONSTRAINT `liquidation_report_items_table_ibfk_1` FOREIGN KEY (`liquidation_report_id`) REFERENCES `liquidation_reports_table` (`liquidation_report_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `liquidation_report_items_table`
--

LOCK TABLES `liquidation_report_items_table` WRITE;
/*!40000 ALTER TABLE `liquidation_report_items_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `liquidation_report_items_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `liquidation_reports_table`
--

DROP TABLE IF EXISTS `liquidation_reports_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `liquidation_reports_table` (
  `liquidation_report_id` bigint NOT NULL AUTO_INCREMENT,
  `liquidation_report_procurement_request_id` bigint DEFAULT NULL,
  `liquidation_report_employee_name` varchar(255) DEFAULT NULL,
  `liquidation_report_cheque_number` varchar(100) DEFAULT NULL,
  `liquidation_report_purpose` text,
  `liquidation_report_amount_advance` decimal(12,2) DEFAULT NULL,
  `liquidation_report_date_released` date DEFAULT NULL,
  `liquidation_report_activity_end_date` date DEFAULT NULL,
  `liquidation_report_submission_deadline` date DEFAULT NULL,
  `liquidation_report_date_submitted` date DEFAULT NULL,
  `liquidation_report_days_lapse` int DEFAULT NULL,
  `liquidation_report_summary_amt_advanced` decimal(12,2) DEFAULT NULL,
  `liquidation_report_summary_actual_expense` decimal(12,2) DEFAULT NULL,
  `liquidation_report_summary_balance` decimal(12,2) DEFAULT NULL,
  `liquidation_report_cash_returned_or_no` varchar(100) DEFAULT NULL,
  `liquidation_report_submitted_by_signature` text,
  `liquidation_report_submitted_by_date` date DEFAULT NULL,
  `liquidation_report_checked_by_accountant` text,
  `liquidation_report_checked_by_date` date DEFAULT NULL,
  `liquidation_report_indorsed_by_supervisor` text,
  `liquidation_report_indorsed_by_date` date DEFAULT NULL,
  `liquidation_report_recommending_approval` text,
  `liquidation_report_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `liquidation_report_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`liquidation_report_id`),
  KEY `liquidation_report_procurement_request_id` (`liquidation_report_procurement_request_id`),
  CONSTRAINT `liquidation_reports_table_ibfk_1` FOREIGN KEY (`liquidation_report_procurement_request_id`) REFERENCES `procurement_requests_table` (`procurement_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `liquidation_reports_table`
--

LOCK TABLES `liquidation_reports_table` WRITE;
/*!40000 ALTER TABLE `liquidation_reports_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `liquidation_reports_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_schedules_table`
--

DROP TABLE IF EXISTS `maintenance_schedules_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_schedules_table` (
  `maintenance_schedule_id` bigint NOT NULL AUTO_INCREMENT,
  `maintenance_schedule_equipment_id` bigint DEFAULT NULL,
  `maintenance_schedule_title` varchar(255) DEFAULT NULL,
  `maintenance_schedule_description` text,
  `maintenance_schedule_frequency` varchar(100) DEFAULT NULL,
  `maintenance_schedule_next_date` date DEFAULT NULL,
  `maintenance_schedule_last_date` date DEFAULT NULL,
  `maintenance_schedule_status` enum('Active','Completed','Overdue') DEFAULT 'Active',
  `maintenance_schedule_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maintenance_schedule_id`),
  KEY `maintenance_schedule_equipment_id` (`maintenance_schedule_equipment_id`),
  CONSTRAINT `maintenance_schedules_table_ibfk_1` FOREIGN KEY (`maintenance_schedule_equipment_id`) REFERENCES `equipment_table` (`equipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_schedules_table`
--

LOCK TABLES `maintenance_schedules_table` WRITE;
/*!40000 ALTER TABLE `maintenance_schedules_table` DISABLE KEYS */;
INSERT INTO `maintenance_schedules_table` VALUES (2,6,'3 months maintenance','need maintenance condition','Monthly','2026-10-16',NULL,'Active','2026-06-25 06:56:27'),(3,12,'Malfunctioning','Wont turn on, even there\'s electricity','Monthly','2026-07-13',NULL,'Active','2026-07-08 19:03:52');
/*!40000 ALTER TABLE `maintenance_schedules_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_24_172926_add_room_coordinates_to_rooms_table',2),(5,'2026_06_24_190000_add_spatial_placement_to_equipment_table',3),(6,'2026_06_25_120000_add_archive_fields_to_rooms_table',4),(7,'2026_06_27_220456_add_tracking_mode_to_equipment_table',5),(8,'2026_07_03_230000_create_campus_setup_settings_table',6),(9,'2026_07_03_000001_add_workstation_layout_tables',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_reads_table`
--

DROP TABLE IF EXISTS `notification_reads_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_reads_table` (
  `notification_read_id` bigint NOT NULL AUTO_INCREMENT,
  `notification_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `notification_read_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_read_id`),
  UNIQUE KEY `uq_notification_user_read` (`notification_id`,`user_id`),
  KEY `fk_notification_read_user` (`user_id`),
  CONSTRAINT `fk_notification_read_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications_table` (`notification_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_read_user` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_reads_table`
--

LOCK TABLES `notification_reads_table` WRITE;
/*!40000 ALTER TABLE `notification_reads_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_reads_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications_table`
--

DROP TABLE IF EXISTS `notifications_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications_table` (
  `notification_id` bigint NOT NULL AUTO_INCREMENT,
  `notification_user_id` bigint DEFAULT NULL,
  `notification_target_role` varchar(100) DEFAULT NULL,
  `notification_title` varchar(255) DEFAULT NULL,
  `notification_message` text,
  `notification_type` varchar(100) DEFAULT NULL,
  `notification_category` varchar(100) DEFAULT NULL,
  `notification_reference_type` varchar(100) DEFAULT NULL,
  `notification_reference_id` bigint DEFAULT NULL,
  `notification_url` varchar(500) DEFAULT NULL,
  `notification_event_key` varchar(255) DEFAULT NULL,
  `notification_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  UNIQUE KEY `uq_notification_event_key` (`notification_event_key`),
  KEY `notification_user_id` (`notification_user_id`),
  CONSTRAINT `notifications_table_ibfk_1` FOREIGN KEY (`notification_user_id`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications_table`
--

LOCK TABLES `notifications_table` WRITE;
/*!40000 ALTER TABLE `notifications_table` DISABLE KEYS */;
INSERT INTO `notifications_table` VALUES (1,3,NULL,'Replacement Request','Report #19 requires replacement.','replacement',NULL,NULL,NULL,NULL,NULL,'2026-06-21 10:44:06'),(2,3,NULL,'Replacement Request','Report #32 requires replacement.','replacement',NULL,NULL,NULL,NULL,NULL,'2026-07-11 18:20:23'),(3,3,NULL,'Replacement Request','Report #24 requires replacement.','replacement',NULL,NULL,NULL,NULL,NULL,'2026-07-11 19:26:23');
/*!40000 ALTER TABLE `notifications_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `online_suppliers_table`
--

DROP TABLE IF EXISTS `online_suppliers_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `online_suppliers_table` (
  `online_id` bigint NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint DEFAULT NULL,
  `app_used` varchar(100) DEFAULT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`online_id`),
  UNIQUE KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `online_suppliers_table_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers_table` (`supplier_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `online_suppliers_table`
--

LOCK TABLES `online_suppliers_table` WRITE;
/*!40000 ALTER TABLE `online_suppliers_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `online_suppliers_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physical_suppliers_table`
--

DROP TABLE IF EXISTS `physical_suppliers_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `physical_suppliers_table` (
  `physical_id` bigint NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `company_address` text,
  PRIMARY KEY (`physical_id`),
  UNIQUE KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `physical_suppliers_table_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers_table` (`supplier_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physical_suppliers_table`
--

LOCK TABLES `physical_suppliers_table` WRITE;
/*!40000 ALTER TABLE `physical_suppliers_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `physical_suppliers_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procurement_requests_table`
--

DROP TABLE IF EXISTS `procurement_requests_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `procurement_requests_table` (
  `procurement_request_id` bigint NOT NULL AUTO_INCREMENT,
  `procurement_request_report_id` bigint DEFAULT NULL,
  `procurement_request_supplier_id` bigint DEFAULT NULL,
  `procurement_request_status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `procurement_request_created_by` bigint DEFAULT NULL,
  `procurement_request_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`procurement_request_id`),
  KEY `procurement_request_report_id` (`procurement_request_report_id`),
  KEY `procurement_request_supplier_id` (`procurement_request_supplier_id`),
  KEY `procurement_request_created_by` (`procurement_request_created_by`),
  CONSTRAINT `procurement_requests_table_ibfk_1` FOREIGN KEY (`procurement_request_report_id`) REFERENCES `reports_table` (`report_id`),
  CONSTRAINT `procurement_requests_table_ibfk_2` FOREIGN KEY (`procurement_request_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`),
  CONSTRAINT `procurement_requests_table_ibfk_3` FOREIGN KEY (`procurement_request_created_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procurement_requests_table`
--

LOCK TABLES `procurement_requests_table` WRITE;
/*!40000 ALTER TABLE `procurement_requests_table` DISABLE KEYS */;
INSERT INTO `procurement_requests_table` VALUES (1,19,NULL,'Pending',1,'2026-06-21 18:44:06'),(2,32,NULL,'Pending',1,'2026-07-11 18:20:23'),(3,25,NULL,'Pending',3,'2026-07-11 19:23:16'),(4,24,NULL,'Pending',1,'2026-07-11 19:26:23');
/*!40000 ALTER TABLE `procurement_requests_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qr_code_logs_table`
--

DROP TABLE IF EXISTS `qr_code_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_code_logs_table` (
  `qr_code_log_id` bigint NOT NULL AUTO_INCREMENT,
  `qr_code_equipment_id` bigint DEFAULT NULL,
  `qr_code_scanned_by` bigint DEFAULT NULL,
  `qr_code_scan_location` varchar(255) DEFAULT NULL,
  `qr_code_scan_device` varchar(255) DEFAULT NULL,
  `qr_code_scanned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`qr_code_log_id`),
  KEY `qr_code_equipment_id` (`qr_code_equipment_id`),
  KEY `qr_code_scanned_by` (`qr_code_scanned_by`),
  CONSTRAINT `qr_code_logs_table_ibfk_1` FOREIGN KEY (`qr_code_equipment_id`) REFERENCES `equipment_table` (`equipment_id`),
  CONSTRAINT `qr_code_logs_table_ibfk_2` FOREIGN KEY (`qr_code_scanned_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qr_code_logs_table`
--

LOCK TABLES `qr_code_logs_table` WRITE;
/*!40000 ALTER TABLE `qr_code_logs_table` DISABLE KEYS */;
INSERT INTO `qr_code_logs_table` VALUES (1,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 08:50:37'),(2,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:06:11'),(3,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:07:02'),(4,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:11:21'),(5,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:12:29'),(6,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:18:30'),(7,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:23:03'),(8,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:26:14'),(9,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:28:03'),(10,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:28:31'),(11,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 09:29:58'),(12,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 17:38:06'),(13,4,1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 17:38:54');
/*!40000 ALTER TABLE `qr_code_logs_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receiving_report_items_table`
--

DROP TABLE IF EXISTS `receiving_report_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receiving_report_items_table` (
  `receiving_report_item_id` bigint NOT NULL AUTO_INCREMENT,
  `receiving_report_id` bigint DEFAULT NULL,
  `receiving_report_item_quantity` int DEFAULT NULL,
  `receiving_report_item_unit` varchar(50) DEFAULT NULL,
  `receiving_report_item_article` text,
  PRIMARY KEY (`receiving_report_item_id`),
  KEY `receiving_report_id` (`receiving_report_id`),
  CONSTRAINT `receiving_report_items_table_ibfk_1` FOREIGN KEY (`receiving_report_id`) REFERENCES `receiving_reports_table` (`receiving_report_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receiving_report_items_table`
--

LOCK TABLES `receiving_report_items_table` WRITE;
/*!40000 ALTER TABLE `receiving_report_items_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `receiving_report_items_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receiving_reports_table`
--

DROP TABLE IF EXISTS `receiving_reports_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receiving_reports_table` (
  `receiving_report_id` bigint NOT NULL AUTO_INCREMENT,
  `receiving_report_procurement_request_id` bigint DEFAULT NULL,
  `receiving_report_form_number` varchar(100) DEFAULT NULL,
  `receiving_report_supplier_id` bigint DEFAULT NULL,
  `receiving_report_supplier_address_override` text,
  `receiving_report_date` date DEFAULT NULL,
  `receiving_report_invoice_no` varchar(100) DEFAULT NULL,
  `receiving_report_dr_no` varchar(100) DEFAULT NULL,
  `receiving_report_delivery_date` date DEFAULT NULL,
  `receiving_report_second_count_by` varchar(255) DEFAULT NULL,
  `receiving_report_received_by_signature` text,
  `receiving_report_status` enum('Pending','Completed','Returned') DEFAULT 'Pending',
  `receiving_report_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receiving_report_id`),
  KEY `receiving_report_procurement_request_id` (`receiving_report_procurement_request_id`),
  KEY `receiving_report_supplier_id` (`receiving_report_supplier_id`),
  CONSTRAINT `receiving_reports_table_ibfk_1` FOREIGN KEY (`receiving_report_procurement_request_id`) REFERENCES `procurement_requests_table` (`procurement_request_id`),
  CONSTRAINT `receiving_reports_table_ibfk_2` FOREIGN KEY (`receiving_report_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receiving_reports_table`
--

LOCK TABLES `receiving_reports_table` WRITE;
/*!40000 ALTER TABLE `receiving_reports_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `receiving_reports_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_timeline_logs_table`
--

DROP TABLE IF EXISTS `report_timeline_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_timeline_logs_table` (
  `report_timeline_log_id` bigint NOT NULL AUTO_INCREMENT,
  `report_timeline_report_id` bigint DEFAULT NULL,
  `report_timeline_status_from` varchar(100) DEFAULT NULL,
  `report_timeline_status_to` varchar(100) DEFAULT NULL,
  `report_timeline_updated_by` bigint DEFAULT NULL,
  `report_timeline_remarks` text,
  `report_timeline_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_timeline_log_id`),
  KEY `report_timeline_report_id` (`report_timeline_report_id`),
  KEY `report_timeline_updated_by` (`report_timeline_updated_by`),
  CONSTRAINT `report_timeline_logs_table_ibfk_1` FOREIGN KEY (`report_timeline_report_id`) REFERENCES `reports_table` (`report_id`),
  CONSTRAINT `report_timeline_logs_table_ibfk_2` FOREIGN KEY (`report_timeline_updated_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_timeline_logs_table`
--

LOCK TABLES `report_timeline_logs_table` WRITE;
/*!40000 ALTER TABLE `report_timeline_logs_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_timeline_logs_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reporters_table`
--

DROP TABLE IF EXISTS `reporters_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reporters_table` (
  `reporter_id` bigint NOT NULL AUTO_INCREMENT,
  `reporter_employee_id` varchar(100) DEFAULT NULL,
  `reporter_full_name` varchar(255) DEFAULT NULL,
  `reporter_email_address` varchar(255) DEFAULT NULL,
  `reporter_contact_number` varchar(50) DEFAULT NULL,
  `reporter_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `reporter_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reporter_id`),
  UNIQUE KEY `reporter_employee_id` (`reporter_employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reporters_table`
--

LOCK TABLES `reporters_table` WRITE;
/*!40000 ALTER TABLE `reporters_table` DISABLE KEYS */;
INSERT INTO `reporters_table` VALUES (1,'OMC0127F','Juan Dela Cruz','juan.cruz@gmail.com','09123456789','Inactive','2026-06-10 11:20:55'),(2,'OMC0128F','Maria Santos','maria.santos@gmail.com','09987654321','Active','2026-06-10 11:20:55'),(3,'OMC0129F','Joseph Diaz','joseph.diaz@gmail.com','09203561232','Inactive','2026-06-24 06:37:08');
/*!40000 ALTER TABLE `reporters_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports_table`
--

DROP TABLE IF EXISTS `reports_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports_table` (
  `report_id` bigint NOT NULL AUTO_INCREMENT,
  `report_reporter_employee_id` varchar(100) DEFAULT NULL,
  `report_room_id` bigint DEFAULT NULL,
  `report_equipment_id` bigint DEFAULT NULL,
  `report_unlisted_equipment_name` varchar(255) DEFAULT NULL,
  `report_problem_description` text,
  `report_suggested_issue` varchar(255) DEFAULT NULL,
  `report_urgency_level` enum('Urgent','Non-Urgent') DEFAULT 'Non-Urgent',
  `report_current_status` enum('Pending','Processing','Resolved','For Replacement','Rejected') DEFAULT 'Pending',
  `report_assigned_personnel_id` bigint DEFAULT NULL,
  `report_assigned_purchaser_id` bigint DEFAULT NULL,
  `report_purchaser_assigned_at` datetime DEFAULT NULL,
  `report_uploaded_image` text,
  `report_is_overdue` tinyint(1) DEFAULT '0',
  `report_submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `report_updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `report_is_archived` tinyint(1) DEFAULT '0',
  `report_resolution_notes` text,
  `report_resolution_image` text,
  `report_rejection_notes` text,
  `report_replacement_notes` text,
  `report_replacement_image` text,
  `report_replacement_submitted_to_purchaser` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`report_id`),
  KEY `report_room_id` (`report_room_id`),
  KEY `report_equipment_id` (`report_equipment_id`),
  KEY `report_assigned_personnel_id` (`report_assigned_personnel_id`),
  KEY `idx_report_assigned_purchaser_id` (`report_assigned_purchaser_id`),
  CONSTRAINT `fk_reports_assigned_purchaser` FOREIGN KEY (`report_assigned_purchaser_id`) REFERENCES `users_table` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `reports_table_ibfk_1` FOREIGN KEY (`report_room_id`) REFERENCES `rooms_table` (`room_id`),
  CONSTRAINT `reports_table_ibfk_2` FOREIGN KEY (`report_equipment_id`) REFERENCES `equipment_table` (`equipment_id`),
  CONSTRAINT `reports_table_ibfk_3` FOREIGN KEY (`report_assigned_personnel_id`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports_table`
--

LOCK TABLES `reports_table` WRITE;
/*!40000 ALTER TABLE `reports_table` DISABLE KEYS */;
INSERT INTO `reports_table` VALUES (1,'OMC0127F',6,6,NULL,'Water Leakage',NULL,'Urgent','Pending',NULL,NULL,NULL,'report-images/CaLVewcmPwjQOQTkgH3MUgpP6gdHNTQY0hMQjACY.jpg',0,'2026-06-12 16:06:10','2026-06-12 16:06:10',0,NULL,NULL,NULL,NULL,NULL,0),(2,'OMC0127F',6,6,NULL,'No Display Aircon Not Cooling',NULL,'Non-Urgent','Pending',NULL,NULL,NULL,'report-images/eXrQqeO89QrDtSVawuBJTWb5jCqnhElNSocjbnzh.jpg',0,'2026-06-12 16:39:13','2026-06-12 16:39:13',0,NULL,NULL,NULL,NULL,NULL,0),(3,'OMC0127F',6,6,NULL,'Aircon Not Cooling',NULL,'Urgent','Pending',NULL,NULL,NULL,'report-images/SAktInV9LCmTivQjQKt0Bf3nnmiBA8Op5RmHl4tI.jpg',0,'2026-06-12 17:22:27','2026-06-12 17:22:27',0,NULL,NULL,NULL,NULL,NULL,0),(4,'OMC0128F',6,6,NULL,'Aircon Not Turning On',NULL,'Urgent','Pending',NULL,NULL,NULL,'report-images/BzcCYHK7yUNFC06ccwR5EIXt1aQ8uWGUTK4KQKNw.jpg',0,'2026-06-12 17:47:56','2026-06-12 17:47:56',0,NULL,NULL,NULL,NULL,NULL,0),(5,'OMC0127F',5,5,NULL,'Projector Flickering',NULL,'Urgent','Processing',NULL,3,'2026-07-11 21:27:23',NULL,0,'2026-06-12 18:14:33','2026-07-11 21:27:23',0,NULL,NULL,NULL,NULL,NULL,0),(6,'OMC0127F',8,NULL,'sdsd','Missing Parts',NULL,'Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-12 18:26:11','2026-06-12 18:26:11',0,NULL,NULL,NULL,NULL,NULL,0),(7,'OMC0128F',6,6,NULL,NULL,'Strange Noise','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 13:49:05','2026-06-15 13:49:05',0,NULL,NULL,NULL,NULL,NULL,0),(8,'OMC0128F',8,4,NULL,NULL,'No Power','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 14:00:06','2026-06-15 14:00:06',0,NULL,NULL,NULL,NULL,NULL,0),(9,'OMC0128F',5,5,NULL,NULL,'HDMI Not Detected','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 14:32:59','2026-06-15 14:32:59',0,NULL,NULL,NULL,NULL,NULL,0),(10,'OMC0127F',5,5,NULL,NULL,'Projector Overheating','Non-Urgent','Processing',1,NULL,NULL,NULL,0,'2026-06-15 14:35:09','2026-06-19 16:08:00',0,NULL,NULL,NULL,NULL,NULL,0),(11,'OMC0127F',8,4,NULL,NULL,'Keyboard Not Working','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 14:37:05','2026-06-15 14:37:05',0,NULL,NULL,NULL,NULL,NULL,0),(12,'OMC0127F',6,6,NULL,NULL,'Remote Control Not Working','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 14:38:45','2026-06-15 14:38:45',0,NULL,NULL,NULL,NULL,NULL,0),(13,'OMC0128F',6,6,NULL,NULL,'Water Leakage','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 14:39:29','2026-06-15 14:39:29',0,NULL,NULL,NULL,NULL,NULL,0),(14,'OMC0127F',5,5,NULL,NULL,'HDMI Not Detected','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 14:47:03','2026-06-15 14:47:03',0,NULL,NULL,NULL,NULL,NULL,0),(15,'OMC0127F',8,4,NULL,NULL,'No Power','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 14:48:53','2026-06-15 14:48:53',0,NULL,NULL,NULL,NULL,NULL,0),(16,'OMC0128F',5,5,NULL,NULL,'Projector Flickering','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-06-15 14:53:54','2026-06-15 14:53:54',0,NULL,NULL,NULL,NULL,NULL,0),(17,'OMC0128F',5,5,NULL,NULL,'Projector Overheating','Non-Urgent','Processing',1,NULL,NULL,NULL,0,'2026-06-15 15:05:36','2026-06-21 17:22:48',0,NULL,NULL,NULL,NULL,NULL,0),(18,'OMC0128F',8,4,NULL,NULL,'Cannot Login','Non-Urgent','Processing',1,NULL,NULL,NULL,0,'2026-06-15 15:06:23','2026-06-22 09:02:22',0,NULL,NULL,NULL,NULL,NULL,0),(19,'OMC0127F',8,4,NULL,NULL,'Keyboard Not Working','Non-Urgent','Resolved',1,NULL,NULL,NULL,0,'2026-06-15 15:08:26','2026-07-08 23:15:52',1,'solved','maintenance-proofs/1Cu5qWsFwuRNn9rdP3bxKQu1u6V3l0qcUfe48ry7.png',NULL,NULL,NULL,1),(20,'OMC0128F',6,6,NULL,NULL,'Aircon Not Cooling','Non-Urgent','Rejected',NULL,NULL,NULL,NULL,0,'2026-06-15 15:21:35','2026-07-06 20:30:26',1,NULL,NULL,NULL,NULL,NULL,0),(21,'OMC0128F',6,6,NULL,NULL,'Remote Control Not Working','Non-Urgent','Resolved',NULL,NULL,NULL,NULL,0,'2026-06-15 15:29:47','2026-07-08 17:17:49',1,NULL,NULL,NULL,NULL,NULL,0),(22,'OMC0128F',8,4,NULL,NULL,'Slow Performance','Non-Urgent','For Replacement',1,NULL,NULL,NULL,0,'2026-06-15 15:31:46','2026-07-07 23:01:47',1,NULL,NULL,NULL,NULL,NULL,0),(23,'OMC0127F',11,13,NULL,NULL,'No Power','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-07-09 03:04:48','2026-07-09 03:04:48',0,NULL,NULL,NULL,NULL,NULL,0),(24,'OMC0129F',12,9,NULL,NULL,'Mouse Defective','Urgent','For Replacement',1,NULL,NULL,NULL,0,'2026-07-09 03:11:11','2026-07-11 19:26:23',0,NULL,NULL,NULL,NULL,NULL,1),(25,'OMC0129F',12,9,NULL,NULL,'Keyboard Not Working','Urgent','For Replacement',NULL,3,'2026-07-11 19:01:09',NULL,0,'2026-07-09 20:03:17','2026-07-11 20:03:30',1,NULL,NULL,NULL,'motherboard wont turn on',NULL,1),(26,'OMC0129F',10,8,NULL,NULL,'Strange Noise','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-07-09 21:21:18','2026-07-09 21:21:18',0,NULL,NULL,NULL,NULL,NULL,0),(27,'OMC0129F',6,6,NULL,NULL,'Strange Noise','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-07-09 21:22:09','2026-07-09 21:22:09',0,NULL,NULL,NULL,NULL,NULL,0),(28,'OMC0129F',9,7,NULL,NULL,'Projector Flickering','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-07-09 21:22:27','2026-07-09 21:22:27',0,NULL,NULL,NULL,NULL,NULL,0),(29,'OMC0129F',10,8,NULL,NULL,'Aircon Not Turning On','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-07-09 21:23:25','2026-07-09 21:23:25',0,NULL,NULL,NULL,NULL,NULL,0),(30,'OMC0128F',14,18,NULL,NULL,'Strange Noise','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-07-09 22:27:19','2026-07-09 22:27:19',0,NULL,NULL,NULL,NULL,NULL,0),(31,'OMC0128F',7,12,NULL,NULL,'Broken Monitor','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-07-09 22:30:31','2026-07-09 22:30:31',0,NULL,NULL,NULL,NULL,NULL,0),(32,'OMC0128F',6,6,NULL,NULL,'Aircon Not Turning On','Non-Urgent','For Replacement',1,NULL,NULL,NULL,0,'2026-07-09 22:34:17','2026-07-11 22:25:02',0,NULL,NULL,NULL,'need replacement','maintenance-proofs/DE7SBpFBk83K0Lg19mk1wWwUDGSsVJk7Mw4ZyeDk.jpg',1),(33,'OMC0128F',10,8,NULL,NULL,'Strange Noise','Non-Urgent','Pending',NULL,NULL,NULL,NULL,0,'2026-07-09 22:36:43','2026-07-09 22:36:43',0,NULL,NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `reports_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `request_check_table`
--

DROP TABLE IF EXISTS `request_check_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `request_check_table` (
  `request_check_id` bigint NOT NULL AUTO_INCREMENT,
  `request_check_authority_purchase_id` bigint DEFAULT NULL,
  `request_check_date` date DEFAULT NULL,
  `request_check_payee` varchar(255) DEFAULT NULL,
  `request_check_amount_words` text,
  `request_check_amount_figures` decimal(12,2) DEFAULT NULL,
  `request_check_particulars_purpose` text,
  `request_check_requested_by` varchar(255) DEFAULT NULL,
  `request_check_approved_by_admin` text,
  `request_check_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `request_check_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_check_id`),
  KEY `request_check_authority_purchase_id` (`request_check_authority_purchase_id`),
  CONSTRAINT `request_check_table_ibfk_1` FOREIGN KEY (`request_check_authority_purchase_id`) REFERENCES `authority_to_purchase_table` (`authority_purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `request_check_table`
--

LOCK TABLES `request_check_table` WRITE;
/*!40000 ALTER TABLE `request_check_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `request_check_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisition_issue_slip_items_table`
--

DROP TABLE IF EXISTS `requisition_issue_slip_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `requisition_issue_slip_items_table` (
  `ris_item_id` bigint NOT NULL AUTO_INCREMENT,
  `ris_id` bigint DEFAULT NULL,
  `ris_item_name_description` text,
  `ris_quantity_requested` int DEFAULT NULL,
  `ris_quantity_issued` int DEFAULT '0',
  `ris_unit_cost` decimal(12,2) DEFAULT NULL,
  `ris_total_amount` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`ris_item_id`),
  KEY `ris_id` (`ris_id`),
  CONSTRAINT `requisition_issue_slip_items_table_ibfk_1` FOREIGN KEY (`ris_id`) REFERENCES `requisition_issue_slip_table` (`ris_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_issue_slip_items_table`
--

LOCK TABLES `requisition_issue_slip_items_table` WRITE;
/*!40000 ALTER TABLE `requisition_issue_slip_items_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `requisition_issue_slip_items_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisition_issue_slip_table`
--

DROP TABLE IF EXISTS `requisition_issue_slip_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `requisition_issue_slip_table` (
  `ris_id` bigint NOT NULL AUTO_INCREMENT,
  `ris_procurement_request_id` bigint DEFAULT NULL,
  `ris_form_number` varchar(100) DEFAULT NULL,
  `ris_purpose_description` text,
  `ris_attachment_file` text,
  `ris_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `ris_requested_by_signature` text,
  `ris_requested_by_date` date DEFAULT NULL,
  `ris_approved_by_signature` text,
  `ris_approved_by_date` date DEFAULT NULL,
  `ris_issued_by_signature` text,
  `ris_issued_by_date` date DEFAULT NULL,
  `ris_received_by_signature` text,
  `ris_received_by_date` date DEFAULT NULL,
  `ris_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ris_id`),
  KEY `ris_procurement_request_id` (`ris_procurement_request_id`),
  CONSTRAINT `requisition_issue_slip_table_ibfk_1` FOREIGN KEY (`ris_procurement_request_id`) REFERENCES `procurement_requests_table` (`procurement_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_issue_slip_table`
--

LOCK TABLES `requisition_issue_slip_table` WRITE;
/*!40000 ALTER TABLE `requisition_issue_slip_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `requisition_issue_slip_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles_table`
--

DROP TABLE IF EXISTS `roles_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles_table` (
  `role_id` bigint NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) DEFAULT NULL,
  `role_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles_table`
--

LOCK TABLES `roles_table` WRITE;
/*!40000 ALTER TABLE `roles_table` DISABLE KEYS */;
INSERT INTO `roles_table` VALUES (1,'Admin','2026-06-02 07:01:22'),(2,'Maintenance Personnel','2026-06-02 07:01:22'),(3,'Purchaser','2026-06-02 07:01:22'),(4,'President','2026-06-02 07:01:22'),(5,'Accounting','2026-06-02 07:01:22'),(6,'Receiving Officer','2026-06-02 07:01:22');
/*!40000 ALTER TABLE `roles_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_activity_logs_table`
--

DROP TABLE IF EXISTS `room_activity_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_activity_logs_table` (
  `activity_id` bigint NOT NULL AUTO_INCREMENT,
  `room_id` bigint NOT NULL,
  `equipment_id` bigint DEFAULT NULL,
  `user_id` bigint DEFAULT NULL,
  `activity_type` varchar(80) DEFAULT NULL,
  `activity_title` varchar(255) DEFAULT NULL,
  `activity_description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `room_id` (`room_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `room_activity_logs_table_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms_table` (`room_id`),
  CONSTRAINT `room_activity_logs_table_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_table` (`equipment_id`),
  CONSTRAINT `room_activity_logs_table_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_activity_logs_table`
--

LOCK TABLES `room_activity_logs_table` WRITE;
/*!40000 ALTER TABLE `room_activity_logs_table` DISABLE KEYS */;
INSERT INTO `room_activity_logs_table` VALUES (1,5,5,1,'equipment_transfer_out','Equipment Transferred','LCD Projector moved to another room.','2026-06-27 05:33:34'),(2,8,5,1,'equipment_transfer_in','Equipment Received','LCD Projector transferred into this room.','2026-06-27 05:33:34'),(3,8,5,1,'equipment_transfer_out','Equipment Transferred','LCD Projector moved to another room.','2026-06-27 05:33:37'),(4,8,5,1,'equipment_transfer_in','Equipment Received','LCD Projector transferred into this room.','2026-06-27 05:33:37'),(5,6,10,1,'equipment_added','Equipment Added','TV was added.','2026-06-27 10:50:14'),(6,6,NULL,1,'room_updated','Room Updated','Renamed to Room 305 and status changed to Normal','2026-06-27 15:11:11'),(7,12,NULL,1,'room_updated','Room Updated','Renamed to Computer Laboratory 2 and status changed to Normal','2026-06-27 15:30:09'),(8,7,NULL,1,'room_updated','Room Updated','Renamed to Room 310 and status changed to Normal','2026-06-27 15:32:27'),(9,8,4,1,'equipment_updated','Equipment Updated','Desktop Computer was updated.','2026-06-27 15:35:39'),(10,8,4,1,'equipment_updated','Equipment Updated','Desktop Computer was updated.','2026-06-27 15:38:33'),(11,6,6,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-28 17:46:29'),(12,6,6,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-28 18:42:10'),(13,6,6,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-28 18:51:55'),(14,6,10,1,'equipment_updated','Equipment Updated','TV was updated.','2026-06-28 19:16:55'),(15,6,6,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-28 19:17:27'),(16,12,11,1,'equipment_added','Equipment Added','WhiteBoard was added.','2026-06-28 19:27:14'),(17,7,12,1,'equipment_added','Equipment Added','TV was added.','2026-06-28 19:33:26'),(18,11,13,1,'equipment_added','Equipment Added','TV was added.','2026-06-28 19:42:22'),(19,6,6,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-29 12:55:41'),(20,6,6,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-29 14:10:04'),(21,10,8,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-30 08:42:03'),(22,10,8,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-30 08:47:06'),(23,10,8,1,'equipment_updated','Equipment Updated','Split Type Air Conditioner was updated.','2026-06-30 09:01:40'),(24,5,NULL,1,'room_updated','Room Updated','Renamed to Admission and status changed to Normal','2026-07-02 17:20:07'),(25,5,NULL,1,'room_updated','Room Updated','Renamed to Admission and status changed to Normal','2026-07-02 17:20:14'),(26,9,NULL,1,'room_updated','Room Updated','Renamed to Registrar and status changed to Normal','2026-07-02 17:21:32'),(27,10,NULL,1,'room_updated','Room Updated','Renamed to NewRoom1 and status changed to Normal','2026-07-02 17:23:29'),(28,6,NULL,1,'room_updated','Room Updated','Renamed to NewRoom2 and status changed to Normal','2026-07-02 17:23:51'),(29,10,NULL,1,'room_updated','Room Updated','Renamed to NewRoom1 and status changed to Normal','2026-07-02 17:23:56'),(30,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-03 07:00:18'),(31,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Offices and status changed to Normal','2026-07-03 07:06:53'),(32,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-03 07:07:00'),(33,6,14,1,'equipment_added','Equipment Added','Keyboard was added.','2026-07-03 07:08:20'),(34,8,15,1,'equipment_added','Equipment Added','Keyboard was added.','2026-07-03 07:17:40'),(35,8,16,1,'equipment_added','Equipment Added','Mouse was added.','2026-07-03 07:28:44'),(36,15,NULL,1,'room_archived','Room Archived','exceed','2026-07-03 14:41:51'),(37,9,NULL,1,'room_updated','Room Updated','Renamed to Registrar and status changed to Normal','2026-07-04 09:04:31'),(38,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-04 09:04:52'),(39,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-04 09:05:53'),(40,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-04 12:40:52'),(41,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-04 14:28:16'),(42,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-04 14:28:35'),(43,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-04 14:28:55'),(44,9,7,1,'equipment_updated','Equipment Updated','LCD Projector was updated.','2026-07-05 14:12:07'),(45,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Maintenance Needed','2026-07-05 16:05:16'),(46,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-05 16:10:42'),(47,6,NULL,1,'room_updated','Room Updated','Renamed to NewRoom2 and status changed to Maintenance Needed','2026-07-05 16:50:56'),(48,6,NULL,1,'room_updated','Room Updated','Renamed to NewRoom2 and status changed to Maintenance Needed','2026-07-05 17:03:33'),(49,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Normal','2026-07-05 17:22:01'),(50,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Maintenance Needed','2026-07-05 17:39:05'),(51,9,7,1,'equipment_updated','Equipment Updated','LCD Projector was updated.','2026-07-05 17:39:41'),(52,9,NULL,1,'room_updated','Room Updated','Renamed to Registrar and status changed to Normal','2026-07-05 17:39:59'),(53,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Maintenance Needed','2026-07-05 18:21:55'),(54,5,NULL,1,'room_updated','Room Updated','Renamed to Admission Office and status changed to Maintenance Needed','2026-07-06 08:26:15'),(55,6,14,1,'equipment_updated','Equipment Updated','Keyboard was updated.','2026-07-06 10:06:51');
/*!40000 ALTER TABLE `room_activity_logs_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms_table`
--

DROP TABLE IF EXISTS `rooms_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms_table` (
  `room_id` bigint NOT NULL AUTO_INCREMENT,
  `room_floor_id` bigint DEFAULT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `room_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `room_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `room_x` int NOT NULL DEFAULT '0',
  `room_y` int NOT NULL DEFAULT '0',
  `room_width` int NOT NULL DEFAULT '120',
  `room_height` int NOT NULL DEFAULT '80',
  `room_color` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `room_metadata` json DEFAULT NULL,
  `room_status` enum('Normal','Maintenance Needed','Critical') NOT NULL DEFAULT 'Normal',
  `room_layout_mode` enum('loose_equipment','workstation_grid') NOT NULL DEFAULT 'loose_equipment',
  `room_layout_version` int NOT NULL DEFAULT '1',
  `room_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `room_archived_at` timestamp NULL DEFAULT NULL,
  `room_archived_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`room_id`),
  KEY `room_floor_id` (`room_floor_id`),
  CONSTRAINT `rooms_table_ibfk_1` FOREIGN KEY (`room_floor_id`) REFERENCES `floors_table` (`floor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms_table`
--

LOCK TABLES `rooms_table` WRITE;
/*!40000 ALTER TABLE `rooms_table` DISABLE KEYS */;
INSERT INTO `rooms_table` VALUES (5,1,'Admission Office','2026-06-10 11:10:45','2026-07-09 18:38:56',785,31,177,187,'#3b4197','Office','{\"rotation\": 0, \"wizard_floor_index\": 0}','Maintenance Needed','loose_equipment',1,0,NULL,NULL),(6,1,'NewRoom2','2026-06-10 11:10:45','2026-07-06 07:45:14',159,27,120,160,'#53771d','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 0}','Maintenance Needed','loose_equipment',1,0,NULL,NULL),(7,2,'Room 310','2026-06-10 11:10:45','2026-07-05 13:34:31',1065,34,80,82,'#60A5FA','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 1}','Normal','loose_equipment',1,0,NULL,NULL),(8,2,'Computer Laboratory 1','2026-06-10 11:10:45','2026-07-05 13:34:31',125,29,80,80,'#FFF200','Computer Laboratory','{\"rotation\": 0, \"wizard_floor_index\": 1}','Normal','loose_equipment',1,0,NULL,NULL),(9,1,'Registrar','2026-06-26 06:29:54','2026-07-05 17:39:59',607,499,110,183,'#22C55E','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 0}','Normal','loose_equipment',1,0,NULL,NULL),(10,1,'NewRoom1','2026-06-26 06:29:54','2026-07-05 13:34:31',17,22,120,169,'#84CC16','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 0}','Normal','loose_equipment',1,0,NULL,NULL),(11,2,'Room 301','2026-06-26 06:29:54','2026-07-05 13:34:31',637,611,80,80,'#60A5FA','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 1}','Normal','loose_equipment',1,0,NULL,NULL),(12,2,'Computer Laboratory 2','2026-06-26 06:29:54','2026-07-05 13:34:31',21,24,80,80,'#FFF200','Computer Laboratory','{\"rotation\": 0, \"wizard_floor_index\": 1}','Normal','loose_equipment',1,0,NULL,NULL),(13,2,'Room 312','2026-07-03 12:53:23','2026-07-05 13:34:31',961,33,80,80,'#60A5FA','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 1}','Normal','loose_equipment',1,0,NULL,NULL),(14,2,'Room 313','2026-07-03 13:21:28','2026-07-05 13:34:31',643,47,80,80,'#60A5FA','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 1}','Normal','loose_equipment',1,0,NULL,NULL),(15,1,'Room 314','2026-07-03 14:41:17','2026-07-05 13:34:31',70,80,150,105,'#60A5FA','Lecture Room','{\"archived_snapshot\": {\"room_name\": \"Room 314\", \"room_type\": \"Lecture Room\", \"archived_at\": \"2026-07-03 22:41:51\", \"room_status\": \"Normal\", \"equipment_ids_removed\": []}, \"wizard_floor_index\": 0}','Normal','loose_equipment',1,1,'2026-07-03 14:41:51','exceed'),(16,2,'Room 315','2026-07-03 15:20:28','2026-07-05 13:34:31',786,73,150,105,'#60A5FA','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 1}','Normal','loose_equipment',1,0,NULL,NULL),(17,2,'Room 317','2026-07-03 16:53:29','2026-07-05 13:34:31',438,43,150,105,'#60A5FA','Lecture Room','{\"rotation\": 0, \"wizard_floor_index\": 1}','Normal','loose_equipment',1,0,NULL,NULL);
/*!40000 ALTER TABLE `rooms_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('rWWszmnZxf6ZdB67oQwyeq6kGrDj9RPWhIahE7VP',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaTlndXVoSG9kbmNlRHhGWVVWVTM5Ymh1NlRLc1NIeW40ZmhSUjFWUiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9fQ==',1783782371);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers_table`
--

DROP TABLE IF EXISTS `suppliers_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers_table` (
  `supplier_id` bigint NOT NULL AUTO_INCREMENT,
  `supplier_store_type` enum('Physical Store','Online Store') NOT NULL,
  `supplier_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers_table`
--

LOCK TABLES `suppliers_table` WRITE;
/*!40000 ALTER TABLE `suppliers_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `suppliers_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_login_logs_table`
--

DROP TABLE IF EXISTS `user_login_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_login_logs_table` (
  `user_login_log_id` bigint NOT NULL AUTO_INCREMENT,
  `user_login_user_id` bigint DEFAULT NULL,
  `user_login_ip_address` varchar(255) DEFAULT NULL,
  `user_login_device_information` text,
  `user_login_status` enum('Success','Failed') DEFAULT NULL,
  `user_login_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_login_log_id`),
  KEY `user_login_user_id` (`user_login_user_id`),
  CONSTRAINT `user_login_logs_table_ibfk_1` FOREIGN KEY (`user_login_user_id`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_login_logs_table`
--

LOCK TABLES `user_login_logs_table` WRITE;
/*!40000 ALTER TABLE `user_login_logs_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_login_logs_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_table`
--

DROP TABLE IF EXISTS `users_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_table` (
  `user_id` bigint NOT NULL AUTO_INCREMENT,
  `user_role_id` bigint DEFAULT NULL,
  `user_employee_id` varchar(100) DEFAULT NULL,
  `user_username` varchar(100) DEFAULT NULL,
  `user_full_name` varchar(255) DEFAULT NULL,
  `user_email_address` varchar(255) DEFAULT NULL,
  `user_contact_number` varchar(50) DEFAULT NULL,
  `user_password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_employee_id` (`user_employee_id`),
  UNIQUE KEY `user_username` (`user_username`),
  UNIQUE KEY `user_email_address` (`user_email_address`),
  KEY `user_role_id` (`user_role_id`),
  CONSTRAINT `users_table_ibfk_1` FOREIGN KEY (`user_role_id`) REFERENCES `roles_table` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_table`
--

LOCK TABLES `users_table` WRITE;
/*!40000 ALTER TABLE `users_table` DISABLE KEYS */;
INSERT INTO `users_table` VALUES (1,2,'OMC0123F','maintenancep1','Kenn Mehares','kenn@gmail.com','09104518042','$2y$12$OkO8t85TGuJ67ARMkuadJe2ajJspsnoyqIahpP5zdfvojNM/o9u7S'),(2,1,'ADMIN001','admin','Administrator','admin@gmail.com','09104819041','$2y$12$.xYmByt2klzu93cXAFaKEebAoywVUEqMkbix2jLEpFtdrYy6KxdNi'),(3,3,'OMC0124F','purchaser001','Leo Legitimas','legitimas@gmail.com','09104589012','$2y$12$sAQoZJkg2hijk6n4j2KugOp3qFVFZavgUQTa/kGslXXSGJK/APJi6'),(4,4,'PRESI001','president001','Mr. President','president@gmail.com','09104781921','$2y$12$UTUSO4PeU3wGb28uAVOzHerv5vPSmvScCB387eYY5/HhJSOogpJDu'),(5,5,'OMC0125F','accounting001','Ms. Accounting','accounting@gmail.com','09104529012','$2y$12$3bdrrtknymxKHefE6XBfDu2AKPnBAac.TgWqpxLCxKcq1mM5Phxtu'),(6,6,'OMC0126F','receiving001','Ms. Receiving','receiving@gmail.com','0910423322','$2y$12$FBb1XJyobAn33F7xjDY3We1BjOv5ogEOLbMbYfXRrztzYqNl8/B/.');
/*!40000 ALTER TABLE `users_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workstation_slot_assignments_table`
--

DROP TABLE IF EXISTS `workstation_slot_assignments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workstation_slot_assignments_table` (
  `workstation_slot_assignment_id` bigint NOT NULL AUTO_INCREMENT,
  `workstation_id` bigint NOT NULL,
  `workstation_template_slot_id` bigint NOT NULL,
  `equipment_id` bigint NOT NULL,
  `workstation_slot_assignment_status` enum('Assigned','Missing','Replaced','Transferred') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Assigned',
  `workstation_slot_assignment_notes` text COLLATE utf8mb4_unicode_ci,
  `workstation_slot_assignment_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `workstation_slot_assignment_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`workstation_slot_assignment_id`),
  UNIQUE KEY `uq_workstation_slot_once` (`workstation_id`,`workstation_template_slot_id`),
  UNIQUE KEY `uq_equipment_only_once` (`equipment_id`),
  KEY `idx_assignment_workstation_id` (`workstation_id`),
  KEY `idx_assignment_template_slot_id` (`workstation_template_slot_id`),
  KEY `idx_assignment_equipment_id` (`equipment_id`),
  CONSTRAINT `fk_assignment_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_table` (`equipment_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_template_slot` FOREIGN KEY (`workstation_template_slot_id`) REFERENCES `workstation_template_slots_table` (`workstation_template_slot_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_workstation` FOREIGN KEY (`workstation_id`) REFERENCES `workstations_table` (`workstation_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workstation_slot_assignments_table`
--

LOCK TABLES `workstation_slot_assignments_table` WRITE;
/*!40000 ALTER TABLE `workstation_slot_assignments_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `workstation_slot_assignments_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workstation_slots_table`
--

DROP TABLE IF EXISTS `workstation_slots_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workstation_slots_table` (
  `workstation_slot_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint unsigned NOT NULL,
  `workstation_template_id` bigint unsigned NOT NULL,
  `workstation_slot_label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_slot_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workstation_slot_orientation` enum('north','east','south','west') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'north',
  `workstation_slot_position_x` decimal(6,2) NOT NULL DEFAULT '0.00',
  `workstation_slot_position_y` decimal(6,2) NOT NULL DEFAULT '0.00',
  `workstation_slot_width` int unsigned NOT NULL DEFAULT '140',
  `workstation_slot_height` int unsigned NOT NULL DEFAULT '100',
  `workstation_slot_status` enum('Active','Inactive','Needs Attention') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `workstation_slot_meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`workstation_slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workstation_slots_table`
--

LOCK TABLES `workstation_slots_table` WRITE;
/*!40000 ALTER TABLE `workstation_slots_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `workstation_slots_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workstation_template_slots_table`
--

DROP TABLE IF EXISTS `workstation_template_slots_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workstation_template_slots_table` (
  `workstation_template_slot_id` bigint NOT NULL AUTO_INCREMENT,
  `workstation_template_id` bigint NOT NULL,
  `workstation_template_slot_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_slot_label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_slot_category` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_slot_required` tinyint(1) NOT NULL DEFAULT '1',
  `workstation_template_slot_sort_order` int NOT NULL DEFAULT '0',
  `workstation_template_slot_default_status` enum('Good','Damaged','Under Maintenance','Disposed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workstation_template_slot_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `workstation_template_slot_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`workstation_template_slot_id`),
  KEY `fk_workstation_template_slots_template` (`workstation_template_id`),
  CONSTRAINT `fk_workstation_template_slots_template` FOREIGN KEY (`workstation_template_id`) REFERENCES `workstation_templates_table` (`workstation_template_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workstation_template_slots_table`
--

LOCK TABLES `workstation_template_slots_table` WRITE;
/*!40000 ALTER TABLE `workstation_template_slots_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `workstation_template_slots_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workstation_templates_table`
--

DROP TABLE IF EXISTS `workstation_templates_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workstation_templates_table` (
  `workstation_template_id` bigint NOT NULL AUTO_INCREMENT,
  `workstation_template_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_description` text COLLATE utf8mb4_unicode_ci,
  `workstation_template_default_width` int NOT NULL DEFAULT '140',
  `workstation_template_default_height` int NOT NULL DEFAULT '100',
  `workstation_template_default_orientation` enum('north','east','south','west') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'north',
  `workstation_template_is_active` tinyint(1) NOT NULL DEFAULT '1',
  `workstation_template_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `workstation_template_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`workstation_template_id`),
  UNIQUE KEY `workstation_template_code` (`workstation_template_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workstation_templates_table`
--

LOCK TABLES `workstation_templates_table` WRITE;
/*!40000 ALTER TABLE `workstation_templates_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `workstation_templates_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workstations_table`
--

DROP TABLE IF EXISTS `workstations_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workstations_table` (
  `workstation_id` bigint NOT NULL AUTO_INCREMENT,
  `room_id` bigint NOT NULL,
  `workstation_template_id` bigint NOT NULL,
  `workstation_label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workstation_orientation` enum('north','east','south','west') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'north',
  `workstation_position_x` decimal(6,2) NOT NULL DEFAULT '0.00',
  `workstation_position_y` decimal(6,2) NOT NULL DEFAULT '0.00',
  `workstation_width` int NOT NULL DEFAULT '140',
  `workstation_height` int NOT NULL DEFAULT '100',
  `workstation_status` enum('Active','Inactive','Needs Attention') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `workstation_meta` json DEFAULT NULL,
  `workstation_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `workstation_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`workstation_id`),
  KEY `idx_workstations_room_id` (`room_id`),
  KEY `idx_workstations_template_id` (`workstation_template_id`),
  CONSTRAINT `fk_workstations_room` FOREIGN KEY (`room_id`) REFERENCES `rooms_table` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_workstations_template` FOREIGN KEY (`workstation_template_id`) REFERENCES `workstation_templates_table` (`workstation_template_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workstations_table`
--

LOCK TABLES `workstations_table` WRITE;
/*!40000 ALTER TABLE `workstations_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `workstations_table` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-11 23:30:10
