/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `approval_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_logs_table` (
  `approval_log_id` bigint NOT NULL AUTO_INCREMENT,
  `approval_log_reference_type` varchar(255) DEFAULT NULL,
  `approval_log_reference_id` bigint DEFAULT NULL,
  `approval_log_level` enum('Admin','President','Accounting','Receiving','Admin Approval','Admin Co-sign','Admin Return') NOT NULL,
  `approval_log_approved_by` bigint DEFAULT NULL,
  `approval_log_approval_status` enum('Approved','Rejected','Directly Approved','Co-signed','Submitted','Under Review','Resubmitted','Pending','Accepted','Forwarded to President','Minor Revision','Admin Approved') NOT NULL,
  `approval_log_approval_remarks` text,
  `approval_log_approved_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_log_id`),
  KEY `approval_log_approved_by` (`approval_log_approved_by`),
  CONSTRAINT `approval_logs_table_ibfk_1` FOREIGN KEY (`approval_log_approved_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attachments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
DROP TABLE IF EXISTS `audit_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs_table` (
  `audit_log_id` bigint NOT NULL AUTO_INCREMENT,
  `audit_log_user_id` bigint DEFAULT NULL,
  `audit_log_action` varchar(255) DEFAULT NULL,
  `audit_log_module` varchar(100) DEFAULT NULL,
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
DROP TABLE IF EXISTS `authority_to_purchase_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authority_to_purchase_items_table` (
  `atp_item_id` bigint NOT NULL AUTO_INCREMENT,
  `authority_purchase_id` bigint DEFAULT NULL,
  `atp_quantity` int DEFAULT NULL,
  `atp_supplier_stock` int unsigned DEFAULT NULL,
  `atp_back_order_qty` int unsigned DEFAULT NULL,
  `atp_unit` varchar(50) DEFAULT NULL,
  `atp_description` text,
  `atp_unit_price` decimal(12,2) DEFAULT NULL,
  `atp_amount` decimal(16,2) DEFAULT NULL,
  PRIMARY KEY (`atp_item_id`),
  KEY `authority_purchase_id` (`authority_purchase_id`),
  CONSTRAINT `authority_to_purchase_items_table_ibfk_1` FOREIGN KEY (`authority_purchase_id`) REFERENCES `authority_to_purchase_table` (`authority_purchase_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `authority_to_purchase_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authority_to_purchase_table` (
  `authority_purchase_id` bigint NOT NULL AUTO_INCREMENT,
  `authority_purchase_ris_id` bigint DEFAULT NULL,
  `authority_purchase_form_number` varchar(100) DEFAULT NULL,
  `authority_purchase_supplier_id` bigint DEFAULT NULL,
  `authority_purchase_created_by` bigint DEFAULT NULL,
  `authority_purchase_date` date DEFAULT NULL,
  `authority_purchase_received_by_name` varchar(255) DEFAULT NULL,
  `authority_purchase_received_by_signature` longtext,
  `authority_purchase_reference_po_no` varchar(100) DEFAULT NULL,
  `authority_purchase_authorized_by_signature` text,
  `authority_purchase_authorized_by` bigint unsigned DEFAULT NULL,
  `authority_purchase_authorized_by_name` varchar(255) DEFAULT NULL,
  `authority_purchase_submitted_by` bigint DEFAULT NULL,
  `authority_purchase_submitted_at` datetime DEFAULT NULL,
  `authority_purchase_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `authority_purchase_payment_path` varchar(30) DEFAULT NULL,
  `authority_purchase_payment_path_chosen_at` datetime DEFAULT NULL,
  `authority_purchase_rejection_reason` text,
  `authority_purchase_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `authority_purchase_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `authority_purchase_updated_at` datetime DEFAULT NULL,
  `authority_purchase_assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`authority_purchase_id`),
  KEY `authority_purchase_ris_id` (`authority_purchase_ris_id`),
  KEY `authority_purchase_supplier_id` (`authority_purchase_supplier_id`),
  CONSTRAINT `authority_to_purchase_table_ibfk_1` FOREIGN KEY (`authority_purchase_ris_id`) REFERENCES `requisition_issue_slip_table` (`ris_id`),
  CONSTRAINT `authority_to_purchase_table_ibfk_2` FOREIGN KEY (`authority_purchase_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `back_orders_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `back_orders_table` (
  `back_order_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `back_order_receiving_report_id` bigint unsigned NOT NULL,
  `back_order_receiving_report_item_id` bigint unsigned DEFAULT NULL,
  `back_order_root_receiving_report_id` bigint unsigned NOT NULL,
  `back_order_atp_id` bigint unsigned DEFAULT NULL,
  `back_order_request_check_id` bigint unsigned DEFAULT NULL,
  `back_order_payment_path` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_order_supplier_id` bigint unsigned DEFAULT NULL,
  `back_order_supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_order_article` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `back_order_unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_order_unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `back_order_quantity` int unsigned NOT NULL,
  `back_order_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'short',
  `back_order_reason` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_order_remarks` text COLLATE utf8mb4_unicode_ci,
  `back_order_images` text COLLATE utf8mb4_unicode_ci,
  `back_order_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `back_order_replacement_supplier_id` bigint unsigned DEFAULT NULL,
  `back_order_replacement_supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_order_refund_amount` decimal(15,2) DEFAULT NULL,
  `back_order_refund_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_order_refund_images` text COLLATE utf8mb4_unicode_ci,
  `back_order_updated_by` bigint unsigned DEFAULT NULL,
  `back_order_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `back_order_updated_at` datetime DEFAULT NULL,
  `back_order_resolved_at` datetime DEFAULT NULL,
  `back_order_replacement_item_id` bigint unsigned DEFAULT NULL,
  `back_order_replacement_quantity` int unsigned DEFAULT NULL,
  `back_order_replacement_unit_price` decimal(15,2) DEFAULT NULL,
  `back_order_replacement_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `back_order_replacement_files` text COLLATE utf8mb4_unicode_ci,
  `back_order_cash_difference` decimal(15,2) DEFAULT NULL,
  `back_order_cash_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`back_order_id`),
  KEY `bo_rr_idx` (`back_order_receiving_report_id`),
  KEY `bo_root_rr_idx` (`back_order_root_receiving_report_id`),
  KEY `bo_status_idx` (`back_order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `borrowing_records_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brands_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands_table` (
  `brand_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `brand_created_at` datetime DEFAULT NULL,
  `brand_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`brand_id`),
  UNIQUE KEY `brands_table_brand_name_unique` (`brand_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `buildings_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buildings_table` (
  `building_id` bigint NOT NULL AUTO_INCREMENT,
  `building_name` varchar(255) DEFAULT NULL,
  `building_logo` varchar(255) DEFAULT NULL,
  `building_address` text,
  `building_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `building_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`building_id`),
  UNIQUE KEY `uq_single_building_name` (`building_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calls` (
  `call_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `call_uuid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversation_id` bigint unsigned NOT NULL,
  `caller_id` bigint NOT NULL,
  `receiver_id` bigint NOT NULL,
  `call_type` enum('audio','video') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('calling','ringing','accepted','declined','ended','missed','busy') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'calling',
  `started_at` timestamp NULL DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`call_id`),
  UNIQUE KEY `calls_call_uuid_unique` (`call_uuid`),
  KEY `calls_conversation_id_foreign` (`conversation_id`),
  KEY `calls_caller_id_foreign` (`caller_id`),
  KEY `calls_receiver_id_foreign` (`receiver_id`),
  CONSTRAINT `calls_caller_id_foreign` FOREIGN KEY (`caller_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `calls_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `calls_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campus_setup_settings_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campus_setup_settings_table` (
  `campus_setup_setting_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campus_setup_pin_hash` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `campus_setup_pin_updated_by` bigint unsigned DEFAULT NULL,
  `campus_setup_pin_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`campus_setup_setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_events` (
  `conversation_event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `actor_user_id` bigint DEFAULT NULL,
  `target_user_id` bigint DEFAULT NULL,
  `event_type` enum('group_created','member_added','member_left') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_event_id`),
  KEY `fk_conversation_event_conversation` (`conversation_id`),
  KEY `fk_conversation_event_actor` (`actor_user_id`),
  KEY `fk_conversation_event_target` (`target_user_id`),
  CONSTRAINT `fk_conversation_event_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users_table` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conversation_event_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversation_event_target` FOREIGN KEY (`target_user_id`) REFERENCES `users_table` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_participants` (
  `conversation_participant_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `user_id` bigint NOT NULL,
  `is_muted` tinyint(1) NOT NULL DEFAULT '0',
  `last_read_at` timestamp NULL DEFAULT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `pinned_message_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`conversation_participant_id`),
  UNIQUE KEY `unique_conversation_user` (`conversation_id`,`user_id`),
  KEY `idx_participant_user` (`user_id`),
  KEY `idx_conversation_participants_pinned_message` (`pinned_message_id`),
  CONSTRAINT `fk_participant_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participant_user` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation_pinned_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_pinned_messages` (
  `conversation_pinned_message_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `message_id` bigint unsigned NOT NULL,
  `user_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_pinned_message_id`),
  UNIQUE KEY `unique_user_pinned_message` (`conversation_id`,`message_id`,`user_id`),
  KEY `idx_pinned_conversation` (`conversation_id`),
  KEY `idx_pinned_message` (`message_id`),
  KEY `idx_pinned_user` (`user_id`),
  CONSTRAINT `fk_pinned_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pinned_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pinned_user` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `conversation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_type` enum('direct','group') NOT NULL DEFAULT 'direct',
  `conversation_name` varchar(255) DEFAULT NULL,
  `conversation_image` varchar(500) DEFAULT NULL,
  `last_message_id` bigint unsigned DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`conversation_id`),
  KEY `idx_conversations_last_message_at` (`last_message_at`),
  KEY `fk_conversation_last_message` (`last_message_id`),
  CONSTRAINT `fk_conversation_last_message` FOREIGN KEY (`last_message_id`) REFERENCES `messages` (`message_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custodians_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custodians_table` (
  `custodian_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custodian_reporter_id` bigint unsigned DEFAULT NULL,
  `custodian_employee_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custodian_full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `custodian_first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custodian_middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custodian_last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custodian_position` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custodian_department_id` bigint unsigned DEFAULT NULL,
  `custodian_room_id` bigint unsigned DEFAULT NULL,
  `custodian_email_address` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custodian_contact_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custodian_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `custodian_notes` text COLLATE utf8mb4_unicode_ci,
  `custodian_created_by` bigint unsigned DEFAULT NULL,
  `custodian_created_at` timestamp NULL DEFAULT NULL,
  `custodian_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`custodian_id`),
  KEY `cust_reporter_idx` (`custodian_reporter_id`),
  KEY `cust_employee_idx` (`custodian_employee_id`),
  KEY `cust_room_idx` (`custodian_room_id`),
  KEY `cust_department_idx` (`custodian_department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments_table` (
  `department_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `department_created_by` bigint unsigned DEFAULT NULL,
  `department_created_at` timestamp NULL DEFAULT NULL,
  `department_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `dept_name_unique` (`department_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `disposal_records_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disposal_records_table` (
  `disposal_record_id` bigint NOT NULL AUTO_INCREMENT,
  `disposal_equipment_id` bigint DEFAULT NULL,
  `disposal_reason` text,
  `disposal_method` varchar(80) DEFAULT NULL,
  `disposal_area_location` varchar(255) DEFAULT NULL,
  `disposal_approved_by` bigint DEFAULT NULL,
  `disposal_disposed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `disposal_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `disposal_residual_value` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`disposal_record_id`),
  KEY `disposal_equipment_id` (`disposal_equipment_id`),
  KEY `disposal_approved_by` (`disposal_approved_by`),
  CONSTRAINT `disposal_records_table_ibfk_1` FOREIGN KEY (`disposal_equipment_id`) REFERENCES `equipment_table` (`equipment_id`),
  CONSTRAINT `disposal_records_table_ibfk_2` FOREIGN KEY (`disposal_approved_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_handovers_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_handovers_table` (
  `handover_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handover_document_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `handover_document_id` bigint unsigned NOT NULL,
  `handover_from_user_id` bigint unsigned NOT NULL,
  `handover_to_user_id` bigint unsigned NOT NULL,
  `handover_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `handover_note` text COLLATE utf8mb4_unicode_ci,
  `handover_response_note` text COLLATE utf8mb4_unicode_ci,
  `handover_created_at` timestamp NULL DEFAULT NULL,
  `handover_responded_at` timestamp NULL DEFAULT NULL,
  `handover_sender_dismissed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`handover_id`),
  KEY `handover_doc_status_idx` (`handover_document_type`,`handover_document_id`,`handover_status`),
  KEY `handover_to_status_idx` (`handover_to_user_id`,`handover_status`),
  KEY `handover_from_status_idx` (`handover_from_user_id`,`handover_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_revision_notes_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_revision_notes_table` (
  `document_revision_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_id` bigint unsigned NOT NULL,
  `revision_kind` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'revision',
  `revision_remarks` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `revision_images` text COLLATE utf8mb4_unicode_ci,
  `revision_requested_by` bigint unsigned DEFAULT NULL,
  `revision_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_revision_id`),
  KEY `doc_revision_lookup_idx` (`document_type`,`document_id`,`revision_kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_categories_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment_categories_table` (
  `equipment_category_id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_category_name` varchar(255) DEFAULT NULL,
  `equipment_category_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`equipment_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_condition_history_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment_condition_history_table` (
  `condition_history_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` bigint unsigned NOT NULL,
  `condition_from` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_to` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `change_source` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`condition_history_id`),
  KEY `equipment_condition_history_table_equipment_id_index` (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_maintenance_history_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `equipment_maintenance_parts_used` varchar(500) DEFAULT NULL,
  `equipment_maintenance_repair_cost` decimal(12,2) DEFAULT NULL,
  `equipment_maintenance_downtime_hours` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`equipment_maintenance_history_id`),
  KEY `equipment_maintenance_equipment_id` (`equipment_maintenance_equipment_id`),
  KEY `equipment_maintenance_report_id` (`equipment_maintenance_report_id`),
  KEY `equipment_maintenance_personnel_id` (`equipment_maintenance_personnel_id`),
  CONSTRAINT `equipment_maintenance_history_table_ibfk_1` FOREIGN KEY (`equipment_maintenance_equipment_id`) REFERENCES `equipment_table` (`equipment_id`),
  CONSTRAINT `equipment_maintenance_history_table_ibfk_2` FOREIGN KEY (`equipment_maintenance_report_id`) REFERENCES `reports_table` (`report_id`),
  CONSTRAINT `equipment_maintenance_history_table_ibfk_3` FOREIGN KEY (`equipment_maintenance_personnel_id`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment_table` (
  `equipment_id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_category_id` bigint DEFAULT NULL,
  `equipment_room_id` bigint DEFAULT NULL,
  `workstation_slot_id` bigint unsigned DEFAULT NULL,
  `workstation_id` bigint DEFAULT NULL,
  `workstation_template_slot_id` bigint DEFAULT NULL,
  `equipment_supplier_id` bigint DEFAULT NULL,
  `equipment_acquisition_source` varchar(40) DEFAULT NULL,
  `equipment_supplier_name` varchar(255) DEFAULT NULL,
  `equipment_reference_number` varchar(120) DEFAULT NULL,
  `equipment_acquisition_notes` varchar(500) DEFAULT NULL,
  `equipment_qr_code` text,
  `equipment_qr_issued_at` timestamp NULL DEFAULT NULL,
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
  `equipment_stocked_by` bigint unsigned DEFAULT NULL,
  `equipment_warranty_expiration` date DEFAULT NULL,
  `equipment_useful_life_years` tinyint unsigned DEFAULT NULL COMMENT 'Expected useful lifespan in years; null uses system default (5)',
  `equipment_current_location` varchar(255) DEFAULT NULL,
  `equipment_placement_zone` varchar(50) DEFAULT NULL,
  `equipment_position_x` tinyint unsigned DEFAULT NULL,
  `equipment_position_y` tinyint unsigned DEFAULT NULL,
  `equipment_width` int NOT NULL DEFAULT '120',
  `equipment_height` int NOT NULL DEFAULT '96',
  `equipment_rotation` smallint NOT NULL DEFAULT '0',
  `equipment_is_borrowable` tinyint(1) DEFAULT '1',
  `equipment_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `equipment_receiving_report_item_id` bigint unsigned DEFAULT NULL,
  `equipment_stock_lot_code` varchar(80) DEFAULT NULL,
  `equipment_replaces_id` bigint unsigned DEFAULT NULL,
  `equipment_replaced_by_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`equipment_id`),
  KEY `equipment_category_id` (`equipment_category_id`),
  KEY `equipment_room_id` (`equipment_room_id`),
  KEY `fk_equipment_supplier` (`equipment_supplier_id`),
  KEY `idx_equipment_workstation_id` (`workstation_id`),
  KEY `idx_equipment_workstation_slot_id` (`workstation_template_slot_id`),
  KEY `equipment_table_workstation_slot_id_foreign` (`workstation_slot_id`),
  KEY `equipment_table_equipment_receiving_report_item_id_index` (`equipment_receiving_report_item_id`),
  KEY `equipment_table_equipment_stock_lot_code_index` (`equipment_stock_lot_code`),
  KEY `equipment_table_equipment_replaces_id_index` (`equipment_replaces_id`),
  KEY `equipment_table_equipment_replaced_by_id_index` (`equipment_replaced_by_id`),
  CONSTRAINT `equipment_table_ibfk_1` FOREIGN KEY (`equipment_category_id`) REFERENCES `equipment_categories_table` (`equipment_category_id`),
  CONSTRAINT `equipment_table_ibfk_2` FOREIGN KEY (`equipment_room_id`) REFERENCES `rooms_table` (`room_id`),
  CONSTRAINT `equipment_table_workstation_slot_id_foreign` FOREIGN KEY (`workstation_slot_id`) REFERENCES `workstation_slots_table` (`workstation_slot_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_equipment_supplier` FOREIGN KEY (`equipment_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`),
  CONSTRAINT `fk_equipment_workstation` FOREIGN KEY (`workstation_id`) REFERENCES `workstations_table` (`workstation_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_equipment_workstation_slot` FOREIGN KEY (`workstation_template_slot_id`) REFERENCES `workstation_template_slots_table` (`workstation_template_slot_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_transfer_history_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `floors_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `floors_table` (
  `floor_id` bigint NOT NULL AUTO_INCREMENT,
  `floor_building_id` bigint DEFAULT NULL,
  `floor_level` varchar(50) NOT NULL,
  `floor_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`floor_id`),
  KEY `floor_building_id` (`floor_building_id`),
  CONSTRAINT `floors_table_ibfk_1` FOREIGN KEY (`floor_building_id`) REFERENCES `buildings_table` (`building_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issue_templates_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issue_templates_table` (
  `issue_template_id` bigint NOT NULL AUTO_INCREMENT,
  `issue_template_category_id` bigint DEFAULT NULL,
  `issue_template_component` varchar(64) DEFAULT NULL,
  `issue_template_name` varchar(255) DEFAULT NULL,
  `issue_template_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`issue_template_id`),
  KEY `issue_template_category_id` (`issue_template_category_id`),
  CONSTRAINT `issue_templates_table_ibfk_1` FOREIGN KEY (`issue_template_category_id`) REFERENCES `equipment_categories_table` (`equipment_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_categories_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_categories_table` (
  `item_category_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_category_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_category_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_category_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `item_category_created_at` datetime DEFAULT NULL,
  `item_category_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`item_category_id`),
  UNIQUE KEY `item_categories_table_item_category_name_unique` (`item_category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_subcategories_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_subcategories_table` (
  `item_subcategory_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_category_id` bigint unsigned NOT NULL,
  `item_subcategory_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_subcategory_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_subcategory_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `item_subcategory_created_at` datetime DEFAULT NULL,
  `item_subcategory_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`item_subcategory_id`),
  UNIQUE KEY `item_subcategories_category_name_unique` (`item_category_id`,`item_subcategory_name`),
  CONSTRAINT `item_subcategories_category_fk` FOREIGN KEY (`item_category_id`) REFERENCES `item_categories_table` (`item_category_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `liquidation_report_attachments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `liquidation_report_attachments_table` (
  `liquidation_attachment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `liquidation_report_id` bigint unsigned NOT NULL,
  `liquidation_attachment_original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `liquidation_attachment_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `liquidation_attachment_mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `liquidation_attachment_size` bigint unsigned DEFAULT NULL,
  `liquidation_attachment_uploaded_by` bigint DEFAULT NULL,
  `liquidation_attachment_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`liquidation_attachment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `liquidation_report_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
DROP TABLE IF EXISTS `liquidation_reports_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `liquidation_reports_table` (
  `liquidation_report_id` bigint NOT NULL AUTO_INCREMENT,
  `liquidation_report_form_number` varchar(100) DEFAULT NULL,
  `liquidation_report_procurement_request_id` bigint DEFAULT NULL,
  `liquidation_report_receiving_report_id` bigint unsigned DEFAULT NULL,
  `liquidation_report_employee_name` varchar(255) DEFAULT NULL,
  `liquidation_report_cheque_number` varchar(100) DEFAULT NULL,
  `liquidation_report_purpose` text,
  `liquidation_report_amount_advance` decimal(12,2) DEFAULT NULL,
  `liquidation_report_date_released` date DEFAULT NULL,
  `liquidation_report_charge_to_account` varchar(255) DEFAULT NULL,
  `liquidation_report_activity_end_date` date DEFAULT NULL,
  `liquidation_report_submission_deadline` date DEFAULT NULL,
  `liquidation_report_date_submitted` date DEFAULT NULL,
  `liquidation_report_days_lapse` int DEFAULT NULL,
  `liquidation_report_other_income` decimal(12,2) DEFAULT NULL,
  `liquidation_report_summary_amt_advanced` decimal(12,2) DEFAULT NULL,
  `liquidation_report_summary_actual_expense` decimal(12,2) DEFAULT NULL,
  `liquidation_report_summary_balance` decimal(12,2) DEFAULT NULL,
  `liquidation_report_cash_returned_or_no` varchar(100) DEFAULT NULL,
  `liquidation_report_submitted_by_signature` text,
  `liquidation_report_submitted_by_date` date DEFAULT NULL,
  `liquidation_report_submitted_by` bigint DEFAULT NULL,
  `liquidation_report_submitted_at` datetime DEFAULT NULL,
  `liquidation_report_checked_by_accountant` text,
  `liquidation_report_checked_by_date` date DEFAULT NULL,
  `liquidation_report_indorsed_by_supervisor` text,
  `liquidation_report_indorsed_by_date` date DEFAULT NULL,
  `liquidation_report_recommending_approval` text,
  `liquidation_report_status` enum('Draft','Submitted','Under Review','Minor Revision','Resubmitted','Pending Admin Approval','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `liquidation_report_review_stage` varchar(20) DEFAULT NULL,
  `liquidation_report_rejection_reason` text,
  `liquidation_report_revision_notes` text,
  `liquidation_report_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `liquidation_report_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `liquidation_report_updated_at` datetime DEFAULT NULL,
  `liquidation_report_created_by` bigint DEFAULT NULL,
  `liquidation_report_assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`liquidation_report_id`),
  KEY `liquidation_report_procurement_request_id` (`liquidation_report_procurement_request_id`),
  KEY `liq_assigned_reviewer_idx` (`liquidation_report_assigned_reviewer_id`),
  CONSTRAINT `liquidation_reports_table_ibfk_1` FOREIGN KEY (`liquidation_report_procurement_request_id`) REFERENCES `procurement_requests_table` (`procurement_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_schedules_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_attachments` (
  `message_attachment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint unsigned NOT NULL,
  `attachment_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `attachment_type` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_extension` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_size` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`message_attachment_id`),
  KEY `idx_message_attachments_message_id` (`message_id`),
  CONSTRAINT `fk_message_attachments_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_hidden_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_hidden_users` (
  `message_hidden_user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint unsigned NOT NULL,
  `user_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`message_hidden_user_id`),
  UNIQUE KEY `unique_message_hidden_user` (`message_id`,`user_id`),
  KEY `idx_message_hidden_message` (`message_id`),
  KEY `idx_message_hidden_user` (`user_id`),
  CONSTRAINT `fk_message_hidden_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_message_hidden_user` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_reactions` (
  `message_reaction_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint unsigned NOT NULL,
  `user_id` bigint NOT NULL,
  `reaction` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`message_reaction_id`),
  UNIQUE KEY `unique_message_user_reaction` (`message_id`,`user_id`),
  KEY `message_reactions_user_id_foreign` (`user_id`),
  CONSTRAINT `message_reactions_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE,
  CONSTRAINT `message_reactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `message_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `sender_id` bigint NOT NULL,
  `reply_to_message_id` bigint unsigned DEFAULT NULL,
  `forwarded_from_message_id` bigint unsigned DEFAULT NULL,
  `message_content` text NOT NULL,
  `message_type` enum('text','call') NOT NULL DEFAULT 'text',
  `call_id` bigint unsigned DEFAULT NULL,
  `is_unsent` tinyint(1) NOT NULL DEFAULT '0',
  `unsent_at` timestamp NULL DEFAULT NULL,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  `edited_at` timestamp NULL DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `idx_messages_conversation` (`conversation_id`),
  KEY `idx_messages_sender` (`sender_id`),
  KEY `idx_messages_created` (`created_at`),
  KEY `messages_reply_to_message_id_foreign` (`reply_to_message_id`),
  KEY `idx_messages_forwarded_from` (`forwarded_from_message_id`),
  KEY `messages_call_id_foreign` (`call_id`),
  CONSTRAINT `fk_message_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_forwarded_from` FOREIGN KEY (`forwarded_from_message_id`) REFERENCES `messages` (`message_id`) ON DELETE SET NULL,
  CONSTRAINT `messages_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`call_id`) ON DELETE SET NULL,
  CONSTRAINT `messages_reply_to_message_id_foreign` FOREIGN KEY (`reply_to_message_id`) REFERENCES `messages` (`message_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_reads_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
DROP TABLE IF EXISTS `notifications_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `online_suppliers_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `online_suppliers_table` (
  `online_id` bigint NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint DEFAULT NULL,
  `app_used` varchar(100) DEFAULT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `store_url` varchar(500) DEFAULT NULL,
  `seller_id` varchar(100) DEFAULT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`online_id`),
  UNIQUE KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `online_suppliers_table_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers_table` (`supplier_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personnel_directory_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personnel_directory_table` (
  `personnel_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `personnel_employee_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personnel_employee_number` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personnel_first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personnel_middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personnel_last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personnel_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personnel_department_id` bigint unsigned DEFAULT NULL,
  `personnel_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personnel_contact` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personnel_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `personnel_source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `personnel_created_by` bigint unsigned DEFAULT NULL,
  `personnel_created_at` timestamp NULL DEFAULT NULL,
  `personnel_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`personnel_id`),
  UNIQUE KEY `pd_employee_id_unique` (`personnel_employee_id`),
  KEY `pd_employee_number_idx` (`personnel_employee_number`),
  KEY `pd_department_idx` (`personnel_department_id`),
  KEY `pd_email_idx` (`personnel_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `physical_suppliers_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physical_suppliers_table` (
  `physical_id` bigint NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `landline_number` varchar(50) DEFAULT NULL,
  `company_address` text,
  PRIMARY KEY (`physical_id`),
  UNIQUE KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `physical_suppliers_table_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers_table` (`supplier_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procurement_record_packages_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procurement_record_packages_table` (
  `package_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_authority_purchase_id` bigint unsigned DEFAULT NULL,
  `package_payment_path` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ready',
  `package_checklist` json DEFAULT NULL,
  `package_submitted_by` bigint unsigned DEFAULT NULL,
  `package_submitted_to_accounting_at` datetime DEFAULT NULL,
  `package_forwarded_by` bigint unsigned DEFAULT NULL,
  `package_forwarded_to_president_at` datetime DEFAULT NULL,
  `package_notes` text COLLATE utf8mb4_unicode_ci,
  `package_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `package_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `package_updated_at` datetime DEFAULT NULL,
  `package_assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`package_id`),
  KEY `pkg_assigned_reviewer_idx` (`package_assigned_reviewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procurement_request_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procurement_request_items_table` (
  `procurement_request_item_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `procurement_request_id` bigint unsigned NOT NULL,
  `report_id` bigint unsigned NOT NULL,
  `report_item_id` bigint unsigned DEFAULT NULL,
  `equipment_id` bigint unsigned DEFAULT NULL,
  `unlisted_equipment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`procurement_request_item_id`),
  UNIQUE KEY `pri_report_item_unique` (`report_item_id`),
  KEY `pri_request_id_idx` (`procurement_request_id`),
  KEY `pri_report_id_idx` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procurement_requests_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procurement_requests_table` (
  `procurement_request_id` bigint NOT NULL AUTO_INCREMENT,
  `procurement_request_report_id` bigint DEFAULT NULL,
  `procurement_request_supplier_id` bigint DEFAULT NULL,
  `procurement_request_status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `procurement_request_created_by` bigint DEFAULT NULL,
  `procurement_request_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `procurement_request_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `procurement_request_archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`procurement_request_id`),
  KEY `procurement_request_report_id` (`procurement_request_report_id`),
  KEY `procurement_request_supplier_id` (`procurement_request_supplier_id`),
  KEY `procurement_request_created_by` (`procurement_request_created_by`),
  CONSTRAINT `procurement_requests_table_ibfk_1` FOREIGN KEY (`procurement_request_report_id`) REFERENCES `reports_table` (`report_id`),
  CONSTRAINT `procurement_requests_table_ibfk_2` FOREIGN KEY (`procurement_request_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`),
  CONSTRAINT `procurement_requests_table_ibfk_3` FOREIGN KEY (`procurement_request_created_by`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_assignments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_assignments_table` (
  `assignment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_equipment_id` bigint unsigned NOT NULL,
  `assignment_custodian_id` bigint unsigned DEFAULT NULL,
  `assignment_room_id` bigint unsigned DEFAULT NULL,
  `assignment_slot_id` bigint unsigned DEFAULT NULL,
  `assignment_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `assignment_document_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignment_notes` text COLLATE utf8mb4_unicode_ci,
  `assignment_issued_by` bigint unsigned DEFAULT NULL,
  `assignment_issued_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assignment_acknowledged_at` timestamp NULL DEFAULT NULL,
  `assignment_signature_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignment_returned_by` bigint unsigned DEFAULT NULL,
  `assignment_returned_at` timestamp NULL DEFAULT NULL,
  `assignment_return_condition` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignment_return_notes` text COLLATE utf8mb4_unicode_ci,
  `assignment_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assignment_updated_at` timestamp NULL DEFAULT NULL,
  `assignment_verified_at` timestamp NULL DEFAULT NULL,
  `assignment_verified_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`assignment_id`),
  KEY `pa_equipment_status_idx` (`assignment_equipment_id`,`assignment_status`),
  KEY `pa_room_idx` (`assignment_room_id`),
  KEY `pa_slot_idx` (`assignment_slot_id`),
  KEY `pa_custodian_status_idx` (`assignment_custodian_id`,`assignment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_atps_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_atps_table` (
  `purchase_order_atp_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `authority_purchase_id` bigint unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`purchase_order_atp_id`),
  KEY `po_atp_po_id_idx` (`purchase_order_id`),
  KEY `po_atp_atp_idx` (`authority_purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_orders_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders_table` (
  `purchase_order_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_order_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `purchase_order_created_by` bigint unsigned DEFAULT NULL,
  `purchase_order_submitted_by` bigint unsigned DEFAULT NULL,
  `purchase_order_submitted_at` datetime DEFAULT NULL,
  `purchase_order_approved_by` bigint unsigned DEFAULT NULL,
  `purchase_order_approved_at` datetime DEFAULT NULL,
  `purchase_order_revision_reason` text COLLATE utf8mb4_unicode_ci,
  `purchase_order_is_archived` tinyint unsigned NOT NULL DEFAULT '0',
  `purchase_order_created_at` datetime DEFAULT NULL,
  `purchase_order_updated_at` datetime DEFAULT NULL,
  `purchase_order_assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`purchase_order_id`),
  UNIQUE KEY `po_number_unique` (`purchase_order_number`),
  KEY `po_status_idx` (`purchase_order_status`),
  KEY `po_created_by_idx` (`purchase_order_created_by`),
  KEY `po_assigned_reviewer_idx` (`purchase_order_assigned_reviewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qr_code_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `receiving_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receiving_logs_table` (
  `receiving_log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receiving_report_id` bigint unsigned DEFAULT NULL,
  `receiving_log_atp_id` bigint unsigned DEFAULT NULL,
  `receiving_log_action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiving_log_status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiving_log_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `receiving_log_officer_id` bigint unsigned DEFAULT NULL,
  `receiving_log_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receiving_log_id`),
  KEY `receiving_logs_table_receiving_report_id_index` (`receiving_report_id`),
  KEY `receiving_logs_table_receiving_log_atp_id_index` (`receiving_log_atp_id`),
  KEY `receiving_logs_table_receiving_log_status_index` (`receiving_log_status`),
  KEY `receiving_logs_table_receiving_log_officer_id_index` (`receiving_log_officer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `receiving_report_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receiving_report_items_table` (
  `receiving_report_item_id` bigint NOT NULL AUTO_INCREMENT,
  `receiving_report_id` bigint DEFAULT NULL,
  `receiving_report_item_quantity` int DEFAULT NULL,
  `receiving_report_item_ordered_qty` int unsigned DEFAULT NULL,
  `receiving_report_item_condition` varchar(20) DEFAULT NULL,
  `receiving_report_item_condition_remarks` varchar(500) DEFAULT NULL,
  `receiving_report_item_unit` varchar(50) DEFAULT NULL,
  `receiving_report_item_article` text,
  `receiving_report_item_supplier_id` bigint unsigned DEFAULT NULL,
  `receiving_report_item_supplier_name` varchar(255) DEFAULT NULL,
  `receiving_report_item_unit_price` decimal(12,2) DEFAULT NULL,
  `receiving_report_item_amount` decimal(12,2) DEFAULT NULL,
  `receiving_report_item_equipment_id` bigint unsigned DEFAULT NULL,
  `receiving_report_item_damaged_qty` int unsigned NOT NULL DEFAULT '0',
  `receiving_report_item_damage_remarks` varchar(500) DEFAULT NULL,
  `receiving_report_item_back_order_id` bigint unsigned DEFAULT NULL,
  `receiving_report_item_verified` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`receiving_report_item_id`),
  KEY `receiving_report_id` (`receiving_report_id`),
  CONSTRAINT `receiving_report_items_table_ibfk_1` FOREIGN KEY (`receiving_report_id`) REFERENCES `receiving_reports_table` (`receiving_report_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `receiving_reports_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receiving_reports_table` (
  `receiving_report_id` bigint NOT NULL AUTO_INCREMENT,
  `receiving_report_procurement_request_id` bigint DEFAULT NULL,
  `receiving_report_request_check_id` bigint unsigned DEFAULT NULL,
  `receiving_report_form_number` varchar(100) DEFAULT NULL,
  `receiving_report_supplier_id` bigint DEFAULT NULL,
  `receiving_report_received_from` varchar(255) DEFAULT NULL,
  `receiving_report_supplier_address_override` text,
  `receiving_report_date` date DEFAULT NULL,
  `receiving_report_invoice_no` varchar(100) DEFAULT NULL,
  `receiving_report_dr_no` varchar(100) DEFAULT NULL,
  `receiving_report_delivery_date` date DEFAULT NULL,
  `receiving_report_received_by_name` varchar(255) DEFAULT NULL,
  `receiving_report_second_count_by` varchar(255) DEFAULT NULL,
  `receiving_report_second_count_by_user_id` bigint DEFAULT NULL,
  `receiving_report_second_count_at` datetime DEFAULT NULL,
  `receiving_report_second_count_signature` longtext,
  `receiving_report_received_by_signature` longtext,
  `receiving_report_submitted_by` bigint DEFAULT NULL,
  `receiving_report_submitted_at` datetime DEFAULT NULL,
  `receiving_report_status` enum('Draft','Submitted','Under Review','Minor Revision','Resubmitted','Incomplete','Completed','Returned') NOT NULL DEFAULT 'Draft',
  `receiving_report_revision_notes` text,
  `receiving_report_return_reason` text,
  `receiving_report_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `receiving_report_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `receiving_report_updated_at` datetime DEFAULT NULL,
  `receiving_report_created_by` bigint DEFAULT NULL,
  `receiving_report_verification_photos` json DEFAULT NULL,
  `receiving_report_assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  `receiving_report_atp_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`receiving_report_id`),
  KEY `receiving_report_procurement_request_id` (`receiving_report_procurement_request_id`),
  KEY `receiving_report_supplier_id` (`receiving_report_supplier_id`),
  KEY `rr_assigned_reviewer_idx` (`receiving_report_assigned_reviewer_id`),
  KEY `rr_atp_idx` (`receiving_report_atp_id`),
  CONSTRAINT `receiving_reports_table_ibfk_1` FOREIGN KEY (`receiving_report_procurement_request_id`) REFERENCES `procurement_requests_table` (`procurement_request_id`),
  CONSTRAINT `receiving_reports_table_ibfk_2` FOREIGN KEY (`receiving_report_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_items_table` (
  `report_item_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `report_item_equipment_id` bigint unsigned DEFAULT NULL,
  `report_item_unlisted_equipment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_item_suggested_issue` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_item_problem_description` text COLLATE utf8mb4_unicode_ci,
  `report_item_uploaded_image` text COLLATE utf8mb4_unicode_ci,
  `report_item_status` enum('Pending','Processing','Resolved','For Replacement','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `report_item_resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `report_item_resolution_image` text COLLATE utf8mb4_unicode_ci,
  `report_item_replacement_notes` text COLLATE utf8mb4_unicode_ci,
  `report_item_replacement_image` text COLLATE utf8mb4_unicode_ci,
  `report_item_rejection_notes` text COLLATE utf8mb4_unicode_ci,
  `report_item_created_at` datetime DEFAULT NULL,
  `report_item_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`report_item_id`),
  KEY `report_items_table_report_id_index` (`report_id`),
  KEY `report_items_table_report_item_equipment_id_index` (`report_item_equipment_id`),
  KEY `report_items_table_report_item_status_index` (`report_item_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_timeline_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
DROP TABLE IF EXISTS `reporter_approval_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reporter_approval_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employment_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invite_id` bigint unsigned DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `directory_personnel_id` bigint unsigned DEFAULT NULL,
  `directory_verdict` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `directory_checks` text COLLATE utf8mb4_unicode_ci,
  `override_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejection_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reporter_approval_requests_employee_id_index` (`employee_id`),
  KEY `reporter_approval_requests_email_index` (`email`),
  KEY `reporter_approval_requests_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reporter_registration_invites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reporter_registration_invites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reporter_registration_invites_token_hash_unique` (`token_hash`),
  KEY `reporter_registration_invites_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reporters_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reporters_table` (
  `reporter_id` bigint NOT NULL AUTO_INCREMENT,
  `reporter_employee_id` varchar(100) DEFAULT NULL,
  `reporter_first_name` varchar(100) DEFAULT NULL,
  `reporter_middle_name` varchar(100) DEFAULT NULL,
  `reporter_last_name` varchar(100) DEFAULT NULL,
  `reporter_full_name` varchar(255) DEFAULT NULL,
  `reporter_employment_type` varchar(50) DEFAULT NULL,
  `reporter_email_address` varchar(255) DEFAULT NULL,
  `reporter_contact_number` varchar(50) DEFAULT NULL,
  `reporter_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `reporter_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reporter_id`),
  UNIQUE KEY `reporter_employee_id` (`reporter_employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reports_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports_table` (
  `report_id` bigint NOT NULL AUTO_INCREMENT,
  `report_reporter_employee_id` varchar(100) DEFAULT NULL,
  `report_logged_by` bigint unsigned DEFAULT NULL,
  `report_room_id` bigint DEFAULT NULL,
  `report_equipment_id` bigint DEFAULT NULL,
  `report_unlisted_equipment_name` varchar(255) DEFAULT NULL,
  `report_problem_description` text,
  `report_suggested_issue` varchar(255) DEFAULT NULL,
  `report_urgency_level` enum('Urgent','Non-Urgent') DEFAULT 'Non-Urgent',
  `report_preferred_action_date` date DEFAULT NULL,
  `report_current_status` enum('Pending','Processing','Resolved','For Replacement','Rejected') DEFAULT 'Pending',
  `report_assigned_personnel_id` bigint DEFAULT NULL,
  `report_assigned_purchaser_id` bigint DEFAULT NULL,
  `report_purchaser_assigned_at` datetime DEFAULT NULL,
  `report_uploaded_image` text,
  `report_is_overdue` tinyint(1) DEFAULT '0',
  `report_submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `report_last_reported_at` timestamp NULL DEFAULT NULL,
  `report_updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `report_is_archived` tinyint(1) DEFAULT '0',
  `report_related_count` int unsigned NOT NULL DEFAULT '1',
  `report_related_notes` text,
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
  KEY `reports_table_report_last_reported_at_index` (`report_last_reported_at`),
  CONSTRAINT `fk_reports_assigned_purchaser` FOREIGN KEY (`report_assigned_purchaser_id`) REFERENCES `users_table` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `reports_table_ibfk_1` FOREIGN KEY (`report_room_id`) REFERENCES `rooms_table` (`room_id`),
  CONSTRAINT `reports_table_ibfk_2` FOREIGN KEY (`report_equipment_id`) REFERENCES `equipment_table` (`equipment_id`),
  CONSTRAINT `reports_table_ibfk_3` FOREIGN KEY (`report_assigned_personnel_id`) REFERENCES `users_table` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `request_check_atps_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request_check_atps_table` (
  `request_check_atp_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_check_id` bigint unsigned NOT NULL,
  `authority_purchase_id` bigint unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`request_check_atp_id`),
  UNIQUE KEY `rfc_atp_pair_unique` (`request_check_id`,`authority_purchase_id`),
  KEY `rfc_atp_atp_idx` (`authority_purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `request_check_attachments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request_check_attachments_table` (
  `request_check_attachment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_check_id` bigint unsigned NOT NULL,
  `request_check_attachment_original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_check_attachment_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_check_attachment_mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_check_attachment_size` bigint unsigned DEFAULT NULL,
  `request_check_attachment_uploaded_by` bigint DEFAULT NULL,
  `request_check_attachment_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_check_attachment_id`),
  KEY `idx_rfc_attachment_rfc_id` (`request_check_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `request_check_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request_check_table` (
  `request_check_id` bigint NOT NULL AUTO_INCREMENT,
  `request_check_form_number` varchar(100) DEFAULT NULL,
  `request_check_authority_purchase_id` bigint DEFAULT NULL,
  `request_check_funding_type` varchar(30) NOT NULL DEFAULT 'request_for_check',
  `request_check_receiving_report_id` bigint unsigned DEFAULT NULL,
  `request_check_date` date DEFAULT NULL,
  `request_check_payee` varchar(255) DEFAULT NULL,
  `request_check_amount_words` text,
  `request_check_amount_figures` decimal(12,2) DEFAULT NULL,
  `request_check_particulars_purpose` text,
  `request_check_requested_by` varchar(255) DEFAULT NULL,
  `request_check_requested_by_signature` longtext,
  `request_check_requested_by_user_id` bigint DEFAULT NULL,
  `request_check_submitted_by` bigint DEFAULT NULL,
  `request_check_submitted_at` datetime DEFAULT NULL,
  `request_check_approved_by_admin` text,
  `request_check_approved_by_user_id` bigint DEFAULT NULL,
  `request_check_approved_at` datetime DEFAULT NULL,
  `request_check_funds_released_at` datetime DEFAULT NULL,
  `request_check_funds_released_by` bigint DEFAULT NULL,
  `request_check_approved_by_signature` text,
  `request_check_rejection_reason` text,
  `request_check_revision_notes` text,
  `request_check_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `request_check_status` enum('Draft','Submitted','Under Review','Minor Revision','Resubmitted','Pending Admin Approval','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `request_check_review_stage` varchar(20) DEFAULT NULL,
  `request_check_accounting_verified_by` bigint DEFAULT NULL,
  `request_check_accounting_verified_at` datetime DEFAULT NULL,
  `request_check_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `request_check_updated_at` datetime DEFAULT NULL,
  `request_check_assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  `request_check_purchase_order_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`request_check_id`),
  KEY `request_check_authority_purchase_id` (`request_check_authority_purchase_id`),
  KEY `rfc_assigned_reviewer_idx` (`request_check_assigned_reviewer_id`),
  KEY `rfc_po_idx` (`request_check_purchase_order_id`),
  CONSTRAINT `request_check_table_ibfk_1` FOREIGN KEY (`request_check_authority_purchase_id`) REFERENCES `authority_to_purchase_table` (`authority_purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `requisition_issue_slip_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisition_issue_slip_items_table` (
  `ris_item_id` bigint NOT NULL AUTO_INCREMENT,
  `ris_id` bigint DEFAULT NULL,
  `ris_item_name_description` text,
  `ris_item_brand_id` bigint unsigned DEFAULT NULL,
  `ris_item_supplier_id` bigint DEFAULT NULL,
  `ris_item_uom_id` bigint unsigned DEFAULT NULL,
  `ris_quantity_requested` int DEFAULT NULL,
  `ris_quantity_issued` int DEFAULT '0',
  `ris_unit_cost` decimal(12,2) DEFAULT NULL,
  `ris_total_amount` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`ris_item_id`),
  KEY `ris_id` (`ris_id`),
  KEY `ris_items_uom_fk` (`ris_item_uom_id`),
  KEY `ris_items_supplier_fk` (`ris_item_supplier_id`),
  KEY `requisition_issue_slip_items_table_ris_item_brand_id_foreign` (`ris_item_brand_id`),
  CONSTRAINT `requisition_issue_slip_items_table_ibfk_1` FOREIGN KEY (`ris_id`) REFERENCES `requisition_issue_slip_table` (`ris_id`) ON DELETE CASCADE,
  CONSTRAINT `requisition_issue_slip_items_table_ris_item_brand_id_foreign` FOREIGN KEY (`ris_item_brand_id`) REFERENCES `brands_table` (`brand_id`) ON DELETE SET NULL,
  CONSTRAINT `ris_items_supplier_fk` FOREIGN KEY (`ris_item_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`) ON DELETE SET NULL,
  CONSTRAINT `ris_items_uom_fk` FOREIGN KEY (`ris_item_uom_id`) REFERENCES `uom_table` (`uom_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `requisition_issue_slip_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisition_issue_slip_table` (
  `ris_id` bigint NOT NULL AUTO_INCREMENT,
  `ris_copied_from_id` bigint unsigned DEFAULT NULL,
  `ris_procurement_request_id` bigint DEFAULT NULL,
  `ris_supplier_id` bigint DEFAULT NULL,
  `ris_form_number` varchar(100) DEFAULT NULL,
  `ris_purpose_description` text,
  `ris_attachment_file` text,
  `ris_status` enum('Draft','Submitted','Under Review','Minor Revision','Resubmitted','Accepted','Approved','Forwarded to President','Approved by the President','Directly Approved','Rejected','Rejected by President','Rejected by the President','Archived','Pending') NOT NULL DEFAULT 'Draft',
  `ris_requested_by_signature` text,
  `ris_requested_by_signature_image` longtext,
  `ris_requested_by_date` date DEFAULT NULL,
  `ris_approved_by_signature` text,
  `ris_approved_by_date` date DEFAULT NULL,
  `ris_rejection_reason` text,
  `ris_direct_approval_reason` text,
  `ris_direct_approval_proof_path` varchar(500) DEFAULT NULL,
  `ris_direct_approval_proof_name` varchar(255) DEFAULT NULL,
  `ris_direct_approval_at` timestamp NULL DEFAULT NULL,
  `ris_direct_approval_by` bigint unsigned DEFAULT NULL,
  `ris_forward_details` text,
  `ris_forward_attachment_path` varchar(500) DEFAULT NULL,
  `ris_forward_attachment_name` varchar(255) DEFAULT NULL,
  `ris_issued_by_signature` text,
  `ris_issued_by_date` date DEFAULT NULL,
  `ris_received_by_signature` text,
  `ris_received_by_date` date DEFAULT NULL,
  `ris_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `ris_request_type` enum('Replacement Procurement','New Procurement') NOT NULL DEFAULT 'Replacement Procurement',
  `ris_urgency` varchar(20) NOT NULL DEFAULT 'Non-Urgent',
  `ris_manual_title` varchar(255) DEFAULT NULL,
  `ris_manual_description` text,
  `ris_manual_requested_for` varchar(255) DEFAULT NULL,
  `ris_created_by` bigint unsigned DEFAULT NULL,
  `ris_updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `ris_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `ris_submitted_by` bigint DEFAULT NULL,
  `ris_submitted_at` datetime DEFAULT NULL,
  `ris_assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`ris_id`),
  KEY `ris_procurement_request_id` (`ris_procurement_request_id`),
  KEY `fk_ris_submitted_by` (`ris_submitted_by`),
  KEY `idx_ris_status` (`ris_status`),
  KEY `idx_ris_submitted_at` (`ris_submitted_at`),
  KEY `idx_ris_supplier_id` (`ris_supplier_id`),
  KEY `requisition_issue_slip_table_ris_assigned_reviewer_id_index` (`ris_assigned_reviewer_id`),
  CONSTRAINT `fk_ris_submitted_by` FOREIGN KEY (`ris_submitted_by`) REFERENCES `users_table` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ris_supplier` FOREIGN KEY (`ris_supplier_id`) REFERENCES `suppliers_table` (`supplier_id`) ON DELETE SET NULL,
  CONSTRAINT `requisition_issue_slip_table_ibfk_1` FOREIGN KEY (`ris_procurement_request_id`) REFERENCES `procurement_requests_table` (`procurement_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ris_attachments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ris_attachments_table` (
  `ris_attachment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ris_id` bigint DEFAULT NULL,
  `ris_attachment_original_name` varchar(255) NOT NULL,
  `ris_attachment_path` varchar(500) NOT NULL,
  `ris_attachment_mime_type` varchar(255) DEFAULT NULL,
  `ris_attachment_size` bigint unsigned DEFAULT NULL,
  `ris_attachment_uploaded_by` bigint DEFAULT NULL,
  `ris_attachment_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ris_attachment_id`),
  KEY `idx_ris_attachments_ris_id` (`ris_id`),
  KEY `idx_ris_attachments_uploaded_by` (`ris_attachment_uploaded_by`),
  CONSTRAINT `fk_ris_attachment_ris` FOREIGN KEY (`ris_id`) REFERENCES `requisition_issue_slip_table` (`ris_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ris_attachment_uploaded_by` FOREIGN KEY (`ris_attachment_uploaded_by`) REFERENCES `users_table` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ris_revision_notes_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ris_revision_notes_table` (
  `ris_revision_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ris_id` bigint NOT NULL,
  `ris_revision_requested_by` bigint DEFAULT NULL,
  `ris_revision_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Minor Revision',
  `ris_revision_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ris_revision_images` text COLLATE utf8mb4_unicode_ci,
  `ris_revision_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ris_revision_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`ris_revision_id`),
  KEY `idx_ris_revision_ris_id` (`ris_id`),
  KEY `idx_ris_revision_requested_by` (`ris_revision_requested_by`),
  CONSTRAINT `fk_ris_revision_requested_by` FOREIGN KEY (`ris_revision_requested_by`) REFERENCES `users_table` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ris_revision_ris` FOREIGN KEY (`ris_id`) REFERENCES `requisition_issue_slip_table` (`ris_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles_table` (
  `role_id` bigint NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) DEFAULT NULL,
  `role_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_activity_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rooms_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `semester_inspection_campaigns_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `semester_inspection_campaigns_table` (
  `campaign_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campaign_academic_year` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `campaign_semester` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campaign_start_date` date DEFAULT NULL,
  `campaign_due_date` date NOT NULL,
  `campaign_scope_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'campus',
  `campaign_scope_building_id` bigint unsigned DEFAULT NULL,
  `campaign_scope_floor_id` bigint unsigned DEFAULT NULL,
  `campaign_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `campaign_notes` text COLLATE utf8mb4_unicode_ci,
  `campaign_created_by` bigint unsigned DEFAULT NULL,
  `campaign_completed_at` timestamp NULL DEFAULT NULL,
  `campaign_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `campaign_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`campaign_id`),
  KEY `sic_status_due_idx` (`campaign_status`,`campaign_due_date`),
  KEY `sic_building_idx` (`campaign_scope_building_id`),
  KEY `sic_floor_idx` (`campaign_scope_floor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `semester_inspection_items_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `semester_inspection_items_table` (
  `item_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_campaign_id` bigint unsigned NOT NULL,
  `item_equipment_id` bigint unsigned NOT NULL,
  `item_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `item_condition` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_findings` text COLLATE utf8mb4_unicode_ci,
  `item_action_taken` text COLLATE utf8mb4_unicode_ci,
  `item_proof_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_inventory_status_applied` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_condition_status_applied` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_inspected_by` bigint unsigned DEFAULT NULL,
  `item_inspected_at` timestamp NULL DEFAULT NULL,
  `item_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `item_updated_at` timestamp NULL DEFAULT NULL,
  `item_custodian_verified` tinyint(1) DEFAULT NULL,
  `item_custodian_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `sii_campaign_equipment_unique` (`item_campaign_id`,`item_equipment_id`),
  KEY `sii_campaign_status_idx` (`item_campaign_id`,`item_status`),
  KEY `sii_equipment_idx` (`item_equipment_id`),
  KEY `sii_condition_idx` (`item_condition`),
  KEY `sii_custodian_person_idx` (`item_custodian_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_notes_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_notes_table` (
  `supplier_note_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint unsigned NOT NULL,
  `supplier_note_user_id` bigint unsigned DEFAULT NULL,
  `supplier_note_type` enum('note','blacklist','unblacklist') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'note',
  `supplier_note_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_note_id`),
  KEY `idx_supplier_notes_supplier_id` (`supplier_id`),
  KEY `idx_supplier_notes_type` (`supplier_note_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers_table` (
  `supplier_id` bigint NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(40) DEFAULT NULL,
  `supplier_store_type` enum('Physical Store','Online Store') NOT NULL,
  `operating_hours` varchar(255) DEFAULT NULL,
  `supplier_is_active` tinyint(1) NOT NULL DEFAULT '1',
  `supplier_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `supplier_is_blacklisted` tinyint(1) NOT NULL DEFAULT '0',
  `supplier_blacklist_reason` text,
  `supplier_blacklisted_at` timestamp NULL DEFAULT NULL,
  `supplier_blacklisted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`supplier_id`),
  KEY `idx_supplier_is_active` (`supplier_is_active`),
  KEY `idx_supplier_is_blacklisted` (`supplier_is_blacklisted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uom_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uom_table` (
  `uom_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uom_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `uom_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uom_created_at` datetime DEFAULT NULL,
  `uom_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`uom_id`),
  UNIQUE KEY `uom_table_uom_name_unique` (`uom_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_login_logs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
DROP TABLE IF EXISTS `user_roles_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles_table` (
  `user_role_row_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_role_row_id`),
  UNIQUE KEY `user_roles_table_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `user_roles_table_role_id_index` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_signatures_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_signatures_table` (
  `user_signature_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `user_signature_label` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_signature_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_signature_is_default` tinyint(1) NOT NULL DEFAULT '0',
  `user_signature_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_signature_id`),
  KEY `user_signatures_table_user_id_index` (`user_id`),
  CONSTRAINT `user_signatures_table_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_table` (
  `user_id` bigint NOT NULL AUTO_INCREMENT,
  `user_role_id` bigint DEFAULT NULL,
  `user_can_procurement` tinyint(1) NOT NULL DEFAULT '0',
  `user_employee_id` varchar(100) DEFAULT NULL,
  `user_username` varchar(100) DEFAULT NULL,
  `user_full_name` varchar(255) DEFAULT NULL,
  `user_email_address` varchar(255) DEFAULT NULL,
  `user_contact_number` varchar(50) DEFAULT NULL,
  `user_profile_picture` varchar(500) DEFAULT NULL,
  `user_password` varchar(255) DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_employee_id` (`user_employee_id`),
  UNIQUE KEY `user_username` (`user_username`),
  UNIQUE KEY `user_email_address` (`user_email_address`),
  KEY `user_role_id` (`user_role_id`),
  CONSTRAINT `users_table_ibfk_1` FOREIGN KEY (`user_role_id`) REFERENCES `roles_table` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workstation_slot_assignments_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workstation_slot_assignments_table` (
  `workstation_slot_assignment_id` bigint NOT NULL AUTO_INCREMENT,
  `workstation_id` bigint NOT NULL,
  `workstation_template_slot_id` bigint NOT NULL,
  `equipment_id` bigint NOT NULL,
  `workstation_slot_assignment_status` enum('Assigned','Missing','Replaced','Transferred') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Assigned',
  `workstation_slot_assignment_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
DROP TABLE IF EXISTS `workstation_slots_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workstation_slots_table` (
  `workstation_slot_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint unsigned NOT NULL,
  `workstation_template_id` bigint unsigned NOT NULL,
  `workstation_slot_label` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_slot_code` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workstation_slot_orientation` enum('north','east','south','west') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'north',
  `workstation_slot_position_x` decimal(6,2) NOT NULL DEFAULT '0.00',
  `workstation_slot_position_y` decimal(6,2) NOT NULL DEFAULT '0.00',
  `workstation_slot_width` int unsigned NOT NULL DEFAULT '140',
  `workstation_slot_height` int unsigned NOT NULL DEFAULT '100',
  `workstation_slot_status` enum('Active','Inactive','Needs Attention') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `workstation_slot_meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`workstation_slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workstation_template_slots_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workstation_template_slots_table` (
  `workstation_template_slot_id` bigint NOT NULL AUTO_INCREMENT,
  `workstation_template_id` bigint NOT NULL,
  `workstation_template_slot_key` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_slot_label` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_slot_category` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_slot_required` tinyint(1) NOT NULL DEFAULT '1',
  `workstation_template_slot_sort_order` int NOT NULL DEFAULT '0',
  `workstation_template_slot_default_status` enum('Good','Damaged','Under Maintenance','Disposed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workstation_template_slot_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `workstation_template_slot_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`workstation_template_slot_id`),
  KEY `fk_workstation_template_slots_template` (`workstation_template_id`),
  CONSTRAINT `fk_workstation_template_slots_template` FOREIGN KEY (`workstation_template_id`) REFERENCES `workstation_templates_table` (`workstation_template_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workstation_templates_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workstation_templates_table` (
  `workstation_template_id` bigint NOT NULL AUTO_INCREMENT,
  `workstation_template_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_template_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `workstation_template_default_width` int NOT NULL DEFAULT '140',
  `workstation_template_default_height` int NOT NULL DEFAULT '100',
  `workstation_template_default_orientation` enum('north','east','south','west') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'north',
  `workstation_template_is_active` tinyint(1) NOT NULL DEFAULT '1',
  `workstation_template_created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `workstation_template_updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`workstation_template_id`),
  UNIQUE KEY `workstation_template_code` (`workstation_template_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workstations_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workstations_table` (
  `workstation_id` bigint NOT NULL AUTO_INCREMENT,
  `room_id` bigint NOT NULL,
  `workstation_template_id` bigint NOT NULL,
  `workstation_label` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `workstation_code` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workstation_orientation` enum('north','east','south','west') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'north',
  `workstation_position_x` decimal(6,2) NOT NULL DEFAULT '0.00',
  `workstation_position_y` decimal(6,2) NOT NULL DEFAULT '0.00',
  `workstation_width` int NOT NULL DEFAULT '140',
  `workstation_height` int NOT NULL DEFAULT '100',
  `workstation_status` enum('Active','Inactive','Needs Attention') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_06_24_172926_add_room_coordinates_to_rooms_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_06_24_190000_add_spatial_placement_to_equipment_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_06_25_120000_add_archive_fields_to_rooms_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_06_27_220456_add_tracking_mode_to_equipment_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_07_03_230000_create_campus_setup_settings_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_07_03_000001_add_workstation_layout_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_07_24_223734_add_last_active_at_to_users_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_07_25_014831_add_delivered_at_to_messages_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_07_26_010558_add_reply_to_message_id_to_messages_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_07_26_022811_create_message_reactions_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_07_17_000001_update_ris_for_manual_procurement_and_attachments',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_07_17_000002_add_archive_fields_to_procurement_requests_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_07_17_000002_update_authority_to_purchase_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_07_19_000001_add_supplier_status_and_ris_supplier',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_07_22_000001_create_conversations_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_07_22_155232_update_ris_workflow_and_create_revision_notes_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_07_30_211249_create_calls_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_07_30_213506_add_call_uuid_to_calls_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_07_31_002201_add_call_columns_to_messages_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_08_04_233545_create_personal_access_tokens_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_08_14_070000_add_report_grouping_columns_to_reports_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_08_14_180000_add_employment_type_to_reporters_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_08_14_183000_add_name_parts_to_reporters_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_08_14_224000_split_computer_set_categories',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_08_14_224500_add_issue_template_component_and_computer_set',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_08_14_225000_apply_survey_equipment_categories',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_08_16_100000_add_is_hidden_to_conversation_participants_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_08_17_020000_consolidate_equipment_categories_and_property_qr',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_08_02_203346_add_is_archived_to_authority_to_purchase_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_08_02_211916_add_submission_fields_to_authority_to_purchase_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_08_14_014800_add_directly_approved_status_to_ris',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_08_15_000001_expand_request_check_module',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_08_15_000002_expand_receiving_reports_module',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_08_15_000003_expand_liquidation_reports_module',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_08_15_010300_add_rejected_by_president_status_to_ris',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_08_15_120000_add_president_decision_statuses_to_ris',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_08_16_000001_add_funds_released_to_request_check_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_08_16_000002_create_purchaser_file_maintenance_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_08_16_000003_add_ris_item_supplier_id',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_08_17_000001_expand_approval_log_enums_for_workflow',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_08_18_013800_create_reporter_registration_invites_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_08_18_221600_add_preferred_action_date_to_reports_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_08_18_234800_create_reporter_approval_requests_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_08_26_004600_add_user_profile_picture_to_users_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_09_01_012000_create_report_items_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_09_02_000001_add_report_logged_by_to_reports_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_08_18_000001_cleanup_forwarded_ris_approved_by_pollution',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_08_18_000002_repair_plain_text_president_approved_ris',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_08_18_000003_add_receiving_report_item_equipment_id',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_08_20_173000_add_audit_log_module_to_audit_logs_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_08_25_000001_add_created_by_to_rr_and_liq',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_08_25_000002_add_supplier_notes_and_blacklist',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_08_25_120000_add_verification_photos_to_receiving_reports',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_08_25_233000_create_receiving_logs_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_09_02_100000_add_procurement_payment_path_workflow',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_09_04_000001_add_landline_number_to_physical_suppliers',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_09_04_000002_add_store_url_and_seller_id_to_online_suppliers',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_09_04_000003_add_contact_fields_to_online_suppliers',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_09_04_000004_add_supplier_code_to_suppliers_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_09_04_000005_add_operating_hours_to_suppliers_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_09_04_100000_add_accepted_status_to_ris',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_09_04_110000_add_direct_approval_reason_and_proof_to_ris',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_09_04_120000_add_forward_details_to_ris',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_09_04_130000_add_ris_item_brand_id_to_ris_items_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_09_05_001100_create_user_signatures_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_09_04_220000_add_ris_requested_by_signature_image',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_09_06_050000_add_atp_authorized_by_name',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_09_06_140000_add_purchaser_document_signature_columns',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_09_06_150000_widen_receiving_report_signature_columns',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_09_06_150000_add_user_can_procurement_to_users_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_09_07_020000_normalize_atp_form_numbers_to_4_digits',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_09_10_200000_create_user_roles_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_09_15_190000_add_useful_life_years_to_equipment_table',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_09_15_200000_normalize_ris_form_numbers_to_ym_sequence',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_09_15_210000_add_ris_urgency_to_requisition_issue_slip_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_09_15_220000_clear_draft_ris_form_numbers_and_add_copied_from',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_09_15_230000_create_procurement_request_items_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_09_16_020000_create_purchase_orders_tables',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_09_16_030000_add_receiving_item_condition_fields',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_09_16_040000_normalize_atp_form_numbers_to_ym_sequence',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_09_18_150000_add_disposal_is_archived_to_disposal_records_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_09_19_010000_create_semester_inspection_tables',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_09_20_013500_add_ris_is_archived_to_requisition_issue_slip_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_09_20_023000_add_assigned_reviewer_to_procurement_documents',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_09_20_031500_rename_admin_role_to_administrator',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_09_20_190000_normalize_rfc_form_numbers_to_ym_sequence',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_09_20_192100_normalize_rr_form_numbers_to_ym_sequence',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_09_20_192500_normalize_lr_form_numbers_to_ym_sequence',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_09_20_193000_clear_draft_procurement_form_numbers',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_09_26_130000_normalize_employee_ids_to_omc_5_digits',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_09_27_030000_add_equipment_lifecycle_phase1_fields',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_09_27_040000_lifecycle_pack_v2_fields',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_09_27_050000_add_multi_atp_funding_requests',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_09_27_050100_add_receiving_report_atp_id',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_09_27_060000_create_document_handovers_table',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_09_27_070000_add_handover_sender_dismissed_at',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_09_27_080000_add_report_last_reported_at',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_09_27_090000_create_property_assignments_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_09_27_100000_add_property_assignment_inspection_fields',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_09_27_110000_create_custodians_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_09_27_120000_create_departments_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_09_27_130000_add_equipment_acquisition_fields',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_09_28_010000_create_personnel_directory_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_09_28_020000_add_name_parts_to_custodians_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_09_28_030000_add_images_to_ris_revision_notes_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_09_28_040000_create_document_revision_notes_table',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_09_28_050000_create_back_orders_table',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_09_28_060000_add_replacement_rows_to_back_orders',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_09_28_070000_widen_atp_item_amount_column',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_09_28_080000_add_incomplete_to_receiving_report_status',86);
