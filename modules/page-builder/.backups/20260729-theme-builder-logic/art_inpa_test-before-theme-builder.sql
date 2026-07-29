-- MySQL dump 10.13  Distrib 8.4.10, for Linux (x86_64)
--
-- Host: localhost    Database: art_inpa_test
-- ------------------------------------------------------
-- Server version	8.4.10

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `properties_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_action_index` (`action`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_conversations`
--

DROP TABLE IF EXISTS `ai_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `plugin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_conversations_user_id_index` (`user_id`),
  KEY `ai_conversations_plugin_index` (`plugin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_conversations`
--

LOCK TABLES `ai_conversations` WRITE;
/*!40000 ALTER TABLE `ai_conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_messages`
--

DROP TABLE IF EXISTS `ai_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `attachments` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_messages_conversation_id_foreign` (`conversation_id`),
  KEY `ai_messages_user_id_index` (`user_id`),
  KEY `ai_messages_intent_index` (`intent`),
  CONSTRAINT `ai_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_messages`
--

LOCK TABLES `ai_messages` WRITE;
/*!40000 ALTER TABLE `ai_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_tool_audit_logs`
--

DROP TABLE IF EXISTS `ai_tool_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_tool_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `tool_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '0',
  `denied_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_summary` json DEFAULT NULL,
  `result_count` int unsigned DEFAULT NULL,
  `ip_address` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_tool_audit_logs_user_id_index` (`user_id`),
  KEY `ai_tool_audit_logs_tool_name_index` (`tool_name`),
  KEY `ai_tool_audit_logs_intent_index` (`intent`),
  KEY `ai_tool_audit_logs_allowed_index` (`allowed`),
  KEY `ai_tool_audit_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_tool_audit_logs`
--

LOCK TABLES `ai_tool_audit_logs` WRITE;
/*!40000 ALTER TABLE `ai_tool_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_tool_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_usage_logs`
--

DROP TABLE IF EXISTS `ai_usage_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_usage_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `intent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plugin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tokens_used` int unsigned DEFAULT NULL,
  `cost_units` decimal(12,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_usage_logs_user_id_index` (`user_id`),
  KEY `ai_usage_logs_intent_index` (`intent`),
  KEY `ai_usage_logs_plugin_index` (`plugin`),
  KEY `ai_usage_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_usage_logs`
--

LOCK TABLES `ai_usage_logs` WRITE;
/*!40000 ALTER TABLE `ai_usage_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_usage_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_checkpoints`
--

DROP TABLE IF EXISTS `backup_checkpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_checkpoints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operation_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkpoint_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backup_checkpoints_created_by_foreign` (`created_by`),
  KEY `backup_checkpoints_operation_type_status_index` (`operation_type`,`status`),
  KEY `backup_checkpoints_target_type_target_slug_index` (`target_type`,`target_slug`),
  CONSTRAINT `backup_checkpoints_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_checkpoints`
--

LOCK TABLES `backup_checkpoints` WRITE;
/*!40000 ALTER TABLE `backup_checkpoints` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_checkpoints` ENABLE KEYS */;
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
-- Table structure for table `documentation_tasks`
--

DROP TABLE IF EXISTS `documentation_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentation_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentation_tasks`
--

LOCK TABLES `documentation_tasks` WRITE;
/*!40000 ALTER TABLE `documentation_tasks` DISABLE KEYS */;
INSERT INTO `documentation_tasks` VALUES (1,'بناء PluginManager كامل لتحميل Service Providers للبلجنات النشطة','الهدف أن البلجنات active فقط تحمل routes, views, migrations, hooks, menus, permissions.',10,NULL,'2026-07-29 06:29:59','2026-07-29 06:29:59'),(2,'تشغيل migrations الخاصة بالبلجن عند التثبيت','بعد فحص ZIP وتسجيل البلجن، يجب تشغيل migrations من modules/{slug}/database/migrations بشكل مضبوط.',20,NULL,'2026-07-29 06:29:59','2026-07-29 06:29:59'),(3,'تسجيل permissions و menus و hooks من ملفات البلجن','قراءة permissions.php و menus.php و hooks.php وربطها بالنظام الأساسي.',30,NULL,'2026-07-29 06:29:59','2026-07-29 06:29:59'),(4,'إنشاء صفحة uninstall آمنة للبلجن','uninstall يكون منفصل عن deactivate ولا يعمل إلا بتأكيد صريح.',40,NULL,'2026-07-29 06:29:59','2026-07-29 06:29:59'),(5,'إنشاء Demo Plugin للاختبار','بلجن بسيط يحتوي module.json و ServiceProvider و route و view لاختبار دورة الحياة.',50,NULL,'2026-07-29 06:29:59','2026-07-29 06:29:59'),(6,'تفعيل Reverse Proxy النهائي للدومين store.z4rank.com','بعد ربط DNS، يوجه OPNsense/Caddy إلى 10.10.0.20:80.',60,NULL,'2026-07-29 06:29:59','2026-07-29 06:29:59'),(7,'إضافة backup قبل install/update/uninstall','نسخة قاعدة بيانات ونسخة مجلد البلجن قبل أي عملية حساسة.',70,NULL,'2026-07-29 06:29:59','2026-07-29 06:29:59'),(8,'تقييد صفحات الإدارة بصلاحيات فعلية','استخدام middleware مثل permission:plugins.install و users.manage بدل auth فقط.',80,NULL,'2026-07-29 06:30:00','2026-07-29 06:30:00'),(9,'إضافة Activity Logs لكل عمليات الإدارة','تسجيل من رفع بلجن، فعله، عطله، أضاف مستخدم، أو عدل صلاحيات.',90,NULL,'2026-07-29 06:30:00','2026-07-29 06:30:00');
/*!40000 ALTER TABLE `documentation_tasks` ENABLE KEYS */;
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
-- Table structure for table `licenses`
--

DROP TABLE IF EXISTS `licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `licenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `license_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  `expires_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `licenses_license_key_unique` (`license_key`),
  KEY `licenses_product_type_product_slug_index` (`product_type`,`product_slug`),
  KEY `licenses_status_expires_at_index` (`status`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licenses`
--

LOCK TABLES `licenses` WRITE;
/*!40000 ALTER TABLE `licenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `licenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `plugin_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'link',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_params` json DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permission` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_menu_id_foreign` (`menu_id`),
  KEY `menu_items_parent_id_foreign` (`parent_id`),
  KEY `menu_items_plugin_id_foreign` (`plugin_id`),
  KEY `menu_items_type_index` (`type`),
  KEY `menu_items_permission_index` (`permission`),
  KEY `menu_items_is_active_index` (`is_active`),
  KEY `menu_items_sort_order_index` (`sort_order`),
  CONSTRAINT `menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_plugin_id_foreign` FOREIGN KEY (`plugin_id`) REFERENCES `plugins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,1,NULL,NULL,'Home',NULL,'route',NULL,'front.home',NULL,'H',NULL,NULL,NULL,1,10,'2026-07-29 06:33:26','2026-07-29 06:33:26'),(2,1,NULL,NULL,'My Account',NULL,'route',NULL,'front.account',NULL,'A',NULL,NULL,NULL,1,20,'2026-07-29 06:33:26','2026-07-29 06:33:26'),(3,2,NULL,NULL,'Dashboard',NULL,'route',NULL,'dashboard',NULL,'D',NULL,NULL,NULL,1,10,'2026-07-29 06:33:27','2026-07-29 06:33:27'),(4,2,NULL,NULL,'Documentation',NULL,'route',NULL,'admin.documentation.index',NULL,'O',NULL,'documentation.manage',NULL,1,15,'2026-07-29 06:33:27','2026-07-29 06:33:27'),(5,2,NULL,NULL,'Platform Registry',NULL,'route',NULL,'admin.platform-registry.index',NULL,'A',NULL,'platform-registry.view',NULL,1,20,'2026-07-29 06:33:27','2026-07-29 06:33:27'),(6,2,NULL,NULL,'Menus',NULL,'route',NULL,'admin.menus.index',NULL,'N',NULL,'menus.manage',NULL,1,25,'2026-07-29 06:33:27','2026-07-29 06:33:27'),(7,2,NULL,NULL,'Media',NULL,'route',NULL,'admin.media.index',NULL,'M',NULL,'media.manage',NULL,1,40,'2026-07-29 06:33:27','2026-07-29 06:33:27'),(8,2,NULL,NULL,'Page Builder',NULL,'route',NULL,'admin.pages.index',NULL,'G',NULL,'pages.manage',NULL,1,30,'2026-07-29 06:33:27','2026-07-29 06:33:27'),(9,2,NULL,NULL,'Theme Builder',NULL,'route',NULL,'admin.theme-builder.index',NULL,'T',NULL,'theme-builder.manage',NULL,1,55,'2026-07-29 06:33:28','2026-07-29 06:33:28'),(10,2,NULL,NULL,'Settings',NULL,'route',NULL,'admin.settings.index',NULL,'S',NULL,'settings.manage',NULL,1,60,'2026-07-29 06:33:28','2026-07-29 06:33:28'),(11,2,NULL,NULL,'Plugins',NULL,'route',NULL,'admin.plugins.index',NULL,'P',NULL,'plugins.view',NULL,1,70,'2026-07-29 06:33:28','2026-07-29 06:33:28'),(12,2,NULL,NULL,'Users',NULL,'route',NULL,'admin.users.index',NULL,'U',NULL,'users.manage',NULL,1,80,'2026-07-29 06:33:28','2026-07-29 06:33:28'),(13,2,NULL,NULL,'Roles',NULL,'route',NULL,'admin.roles.index',NULL,'L',NULL,'roles.manage',NULL,1,90,'2026-07-29 06:33:28','2026-07-29 06:33:28'),(14,2,NULL,NULL,'Permissions',NULL,'route',NULL,'admin.permissions.index',NULL,'K',NULL,'permissions.manage',NULL,1,100,'2026-07-29 06:33:28','2026-07-29 06:33:28'),(15,2,NULL,NULL,'Install Plugin','Install Plugin','route',NULL,'admin.plugins.create',NULL,'I','_self','plugins.install','{\"admin_group\": \"Platform\", \"admin_sort_order\": 30}',1,75,'2026-07-29 06:33:28','2026-07-29 06:33:28');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plugin_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_key_location_unique` (`key`,`location`),
  KEY `menus_plugin_id_foreign` (`plugin_id`),
  KEY `menus_location_index` (`location`),
  KEY `menus_source_index` (`source`),
  KEY `menus_is_active_index` (`is_active`),
  KEY `menus_sort_order_index` (`sort_order`),
  CONSTRAINT `menus_plugin_id_foreign` FOREIGN KEY (`plugin_id`) REFERENCES `plugins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES (1,'platform.frontend','Frontend Menu','frontend','Editable frontend navigation menu.','platform',NULL,1,0,'2026-07-29 06:33:26','2026-07-29 06:33:26'),(2,'platform.admin','Admin Menu','admin','Editable admin sidebar menu.','platform',NULL,1,0,'2026-07-29 06:33:26','2026-07-29 06:33:26');
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_05_25_000001_create_core_plugin_tables',1),(5,'2026_05_25_043430_create_permission_tables',1),(6,'2026_05_25_162100_create_documentation_tasks_table',1),(7,'2026_06_21_000001_create_plugins_table',1),(8,'2026_06_21_000002_create_plugin_updates_table',1),(9,'2026_06_21_000003_create_menus_tables',1),(10,'2026_06_21_000006_create_licenses_table',1),(11,'2026_06_21_000007_create_operation_logs_table',1),(12,'2026_06_21_000008_create_backup_checkpoints_table',1),(13,'2026_06_23_000001_create_platform_settings_table',1),(14,'2026_06_25_000001_create_platform_pages_table',1),(15,'2026_06_26_000001_create_platform_media_metadata_table',1),(16,'2026_06_26_000002_extend_platform_settings_registry_columns',1),(17,'2026_06_26_000003_create_platform_plugin_registry_entries_table',1),(18,'2026_06_26_000004_add_builder_columns_to_platform_pages_table',1),(19,'2026_06_26_000005_add_content_type_to_platform_pages_table',1),(20,'2026_06_26_000006_create_platform_registry_entries_table',1),(21,'2026_06_28_060001_create_ai_conversations_table',1),(22,'2026_06_28_060002_create_ai_messages_table',1),(23,'2026_06_28_060003_create_ai_usage_logs_table',1),(24,'2026_06_28_060004_create_ai_tool_audit_logs_table',1),(25,'2026_06_29_000001_create_platform_page_revisions_table',1),(26,'2026_07_01_000001_create_platform_theme_builder_conditions_table',1),(27,'2026_07_02_000001_create_platform_theme_builder_templates_table',1),(28,'2026_07_26_000001_bootstrap_default_super_admin_for_empty_database',1),(29,'2026_07_26_000002_rename_default_admin_theme_plugin',1),(30,'2026_07_27_000001_verify_initial_super_admin',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(1,'App\\Models\\User',2);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operation_logs`
--

DROP TABLE IF EXISTS `operation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operation_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `context` json DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operation_logs_created_by_foreign` (`created_by`),
  KEY `operation_logs_operation_type_status_index` (`operation_type`,`status`),
  KEY `operation_logs_target_type_target_slug_index` (`target_type`,`target_slug`),
  CONSTRAINT `operation_logs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operation_logs`
--

LOCK TABLES `operation_logs` WRITE;
/*!40000 ALTER TABLE `operation_logs` DISABLE KEYS */;
INSERT INTO `operation_logs` VALUES (1,'platform.setting.update','platform-setting','general.site_title','success','Platform setting updated.','{\"key\": \"general.site_title\", \"source\": \"platform.installer\", \"new_value\": \"art-inpa\", \"old_value\": null, \"timestamp\": \"2026-07-29 09:33:39\", \"changed_by\": 2}','2026-07-29 06:33:39','2026-07-29 06:33:39',2,'2026-07-29 06:33:39','2026-07-29 06:33:39'),(2,'platform.setting.update','platform-setting','general.wordpress_address_url','success','Platform setting updated.','{\"key\": \"general.wordpress_address_url\", \"source\": \"platform.installer\", \"new_value\": \"http://127.0.0.1:8051\", \"old_value\": null, \"timestamp\": \"2026-07-29 09:33:39\", \"changed_by\": 2}','2026-07-29 06:33:39','2026-07-29 06:33:39',2,'2026-07-29 06:33:39','2026-07-29 06:33:39'),(3,'platform.setting.update','platform-setting','general.site_address_url','success','Platform setting updated.','{\"key\": \"general.site_address_url\", \"source\": \"platform.installer\", \"new_value\": \"http://127.0.0.1:8051\", \"old_value\": null, \"timestamp\": \"2026-07-29 09:33:39\", \"changed_by\": 2}','2026-07-29 06:33:39','2026-07-29 06:33:39',2,'2026-07-29 06:33:39','2026-07-29 06:33:39'),(4,'platform.setting.update','platform-setting','general.admin_email','success','Platform setting updated.','{\"key\": \"general.admin_email\", \"source\": \"platform.installer\", \"new_value\": \"ziad.mansor@gmail.com\", \"old_value\": null, \"timestamp\": \"2026-07-29 09:33:39\", \"changed_by\": 2}','2026-07-29 06:33:39','2026-07-29 06:33:39',2,'2026-07-29 06:33:39','2026-07-29 06:33:39');
/*!40000 ALTER TABLE `operation_logs` ENABLE KEYS */;
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
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'documentation.manage','web','2026-07-29 06:33:17','2026-07-29 06:33:17'),(2,'settings.manage','web','2026-07-29 06:33:18','2026-07-29 06:33:18'),(3,'media.manage','web','2026-07-29 06:33:18','2026-07-29 06:33:18'),(4,'menus.manage','web','2026-07-29 06:33:18','2026-07-29 06:33:18'),(5,'pages.manage','web','2026-07-29 06:33:18','2026-07-29 06:33:18'),(6,'theme-builder.manage','web','2026-07-29 06:33:18','2026-07-29 06:33:18'),(7,'plugins.view','web','2026-07-29 06:33:18','2026-07-29 06:33:18'),(8,'plugins.install','web','2026-07-29 06:33:18','2026-07-29 06:33:18'),(9,'plugins.activate','web','2026-07-29 06:33:19','2026-07-29 06:33:19'),(10,'users.manage','web','2026-07-29 06:33:19','2026-07-29 06:33:19'),(11,'roles.manage','web','2026-07-29 06:33:19','2026-07-29 06:33:19'),(12,'permissions.manage','web','2026-07-29 06:33:19','2026-07-29 06:33:19'),(13,'platform-registry.view','web','2026-07-29 06:33:20','2026-07-29 06:33:20');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_media_metadata`
--

DROP TABLE IF EXISTS `platform_media_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_media_metadata` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `caption` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_media_metadata_url_unique` (`url`),
  KEY `platform_media_metadata_url_index` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_media_metadata`
--

LOCK TABLES `platform_media_metadata` WRITE;
/*!40000 ALTER TABLE `platform_media_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_media_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_page_revisions`
--

DROP TABLE IF EXISTS `platform_page_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_page_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `html` longtext COLLATE utf8mb4_unicode_ci,
  `css` longtext COLLATE utf8mb4_unicode_ci,
  `page_builder_json` longtext COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `platform_page_revisions_created_by_foreign` (`created_by`),
  KEY `platform_page_revisions_page_id_created_at_index` (`page_id`,`created_at`),
  CONSTRAINT `platform_page_revisions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `platform_page_revisions_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `platform_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_page_revisions`
--

LOCK TABLES `platform_page_revisions` WRITE;
/*!40000 ALTER TABLE `platform_page_revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_page_revisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_pages`
--

DROP TABLE IF EXISTS `platform_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'page',
  `block_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `page_builder_json` longtext COLLATE utf8mb4_unicode_ci,
  `html` longtext COLLATE utf8mb4_unicode_ci,
  `css` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `sort_order` int NOT NULL DEFAULT '0',
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_pages_slug_unique` (`slug`),
  KEY `platform_pages_status_index` (`status`),
  KEY `platform_pages_content_type_index` (`content_type`),
  KEY `platform_pages_block_key_index` (`block_key`),
  KEY `platform_pages_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_pages`
--

LOCK TABLES `platform_pages` WRITE;
/*!40000 ALTER TABLE `platform_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_plugin_registry_entries`
--

DROP TABLE IF EXISTS `platform_plugin_registry_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_plugin_registry_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `registry_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plugin_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ppre_type_slug_unique` (`registry_type`,`plugin_slug`),
  KEY `ppre_type_index` (`registry_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_plugin_registry_entries`
--

LOCK TABLES `platform_plugin_registry_entries` WRITE;
/*!40000 ALTER TABLE `platform_plugin_registry_entries` DISABLE KEYS */;
INSERT INTO `platform_plugin_registry_entries` VALUES (1,'runtime','admin-theme','{\"runtime_enabled\": true}','2026-07-29 10:14:01','2026-07-29 10:14:01'),(2,'runtime','page-builder','{\"runtime_enabled\": true}','2026-07-29 10:14:00','2026-07-29 10:14:00');
/*!40000 ALTER TABLE `platform_plugin_registry_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_registry_entries`
--

DROP TABLE IF EXISTS `platform_registry_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_registry_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `registry_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registry_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pre_type_key_unique` (`registry_type`,`registry_key`),
  KEY `pre_type_index` (`registry_type`),
  KEY `platform_registry_entries_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_registry_entries`
--

LOCK TABLES `platform_registry_entries` WRITE;
/*!40000 ALTER TABLE `platform_registry_entries` DISABLE KEYS */;
INSERT INTO `platform_registry_entries` VALUES (1,'routes','pages.show','{\"uri\": \"pages/{slug}\", \"module\": \"core\", \"status\": \"active\", \"methods\": [\"GET\", \"HEAD\"], \"description\": \"Render published platform page builder content\"}','active','2026-07-29 06:32:42','2026-07-29 06:32:42');
/*!40000 ALTER TABLE `platform_registry_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json DEFAULT NULL,
  `default_value` json DEFAULT NULL,
  `options` json DEFAULT NULL,
  `help_text` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `validation_rules` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core',
  `visibility_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `admin_access_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `sensitive_flag` tinyint(1) NOT NULL DEFAULT '0',
  `public_exposure_allowed` tinyint(1) NOT NULL DEFAULT '0',
  `frontend_available` tinyint(1) NOT NULL DEFAULT '0',
  `cache_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `cache_ttl` int unsigned DEFAULT NULL,
  `ui_component` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ui_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allowed_values` json DEFAULT NULL,
  `min_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depends_on` json DEFAULT NULL,
  `restart_required` tinyint(1) NOT NULL DEFAULT '0',
  `approval_required` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_settings_group_key_setting_key_unique` (`group_key`,`setting_key`),
  KEY `platform_settings_group_key_sort_order_index` (`group_key`,`sort_order`),
  KEY `platform_settings_is_public_index` (`is_public`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
INSERT INTO `platform_settings` VALUES (1,'general','site_title','Site Title','text','\"art-inpa\"','\"Z4Rank\"',NULL,NULL,10,1,NULL,NULL,'general','core','public','manage_settings',1,0,0,1,1,1,NULL,'text','Site Title',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:29','2026-07-29 06:33:38'),(2,'general','tagline','Tagline','text',NULL,'\"القمة لتصدر نتائج البحث\"',NULL,NULL,20,1,NULL,NULL,'general','core','public','manage_settings',1,0,0,1,1,1,NULL,'text','Tagline',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:29','2026-07-29 06:33:29'),(3,'general','site_logo','Site Logo','file',NULL,'\"/storage/settings/Ob6BqGzoNd4zjfHezzskEj03aMQ365pNi8gTeXe9.png\"',NULL,'Upload a PNG, JPG, or WEBP logo image. Recommended width: 240 pixels or larger.',25,1,NULL,'Upload a PNG, JPG, or WEBP logo image. Recommended width: 240 pixels or larger.','general','core','public','manage_settings',1,0,0,1,1,1,NULL,'file','Site Logo',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:30','2026-07-29 06:33:30'),(4,'general','site_icon','Site Icon','file',NULL,'\"/storage/settings/Ob6BqGzoNd4zjfHezzskEj03aMQ365pNi8gTeXe9.png\"',NULL,'Upload a square PNG, JPG, WEBP, or ICO image. Recommended size: 512 x 512 pixels.',30,1,NULL,'Upload a square PNG, JPG, WEBP, or ICO image. Recommended size: 512 x 512 pixels.','general','core','public','manage_settings',1,0,0,1,1,1,NULL,'file','Site Icon',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:30','2026-07-29 06:33:30'),(5,'general','wordpress_address_url','Application Address URL','url','\"http://127.0.0.1:8051\"','\"http://10.0.0.20\"',NULL,NULL,40,1,NULL,NULL,'general','core','public','manage_settings',1,0,0,1,1,1,NULL,'url','Application Address URL',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:30','2026-07-29 06:33:39'),(6,'general','site_address_url','Site Address URL','url','\"http://127.0.0.1:8051\"','\"http://10.0.0.20\"',NULL,NULL,50,1,NULL,NULL,'general','core','public','manage_settings',1,0,0,1,1,1,NULL,'url','Site Address URL',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:30','2026-07-29 06:33:39'),(7,'general','admin_email','Administration Email Address','email','\"ziad.mansor@gmail.com\"','\"admin@z4rank.com\"',NULL,NULL,60,0,NULL,NULL,'general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'email','Administration Email Address',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:31','2026-07-29 06:33:39'),(8,'general','membership_enabled','Membership','boolean',NULL,'true',NULL,'Allow anyone to register.',70,0,NULL,'Allow anyone to register.','general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'boolean','Membership',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:31','2026-07-29 06:33:31'),(9,'general','default_user_role','New User Default Role','select',NULL,'\"user\"',NULL,NULL,80,0,NULL,NULL,'general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'select','New User Default Role',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:31','2026-07-29 06:33:31'),(10,'general','site_language','Site Language','select',NULL,'\"ar\"','{\"ar\": \"Arabic\", \"en\": \"English\"}',NULL,90,1,NULL,NULL,'general','core','public','manage_settings',1,0,0,1,1,1,NULL,'select','Site Language','{\"ar\": \"Arabic\", \"en\": \"English\"}',NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:31','2026-07-29 06:33:31'),(11,'general','timezone','Timezone','select',NULL,'\"Asia/Amman\"','{\"UTC\": \"UTC\", \"Asia/Amman\": \"Asia/Amman\", \"Asia/Riyadh\": \"Asia/Riyadh\", \"Europe/London\": \"Europe/London\", \"America/New_York\": \"America/New_York\"}',NULL,100,0,NULL,NULL,'general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'select','Timezone','{\"UTC\": \"UTC\", \"Asia/Amman\": \"Asia/Amman\", \"Asia/Riyadh\": \"Asia/Riyadh\", \"Europe/London\": \"Europe/London\", \"America/New_York\": \"America/New_York\"}',NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:32','2026-07-29 10:07:27'),(12,'general','date_format','Date Format','radio',NULL,'\"F j, Y\"','{\"Y-m-d\": \"2026-06-23\", \"d.m.Y\": \"23.06.2026\", \"d/m/Y\": \"23/06/2026\", \"m/d/Y\": \"06/23/2026\", \"F j, Y\": \"June 23, 2026\", \"custom\": \"Custom\"}',NULL,110,0,NULL,NULL,'general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'radio','Date Format','{\"Y-m-d\": \"2026-06-23\", \"d.m.Y\": \"23.06.2026\", \"d/m/Y\": \"23/06/2026\", \"m/d/Y\": \"06/23/2026\", \"F j, Y\": \"June 23, 2026\", \"custom\": \"Custom\"}',NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:32','2026-07-29 10:07:27'),(13,'general','custom_date_format','Custom Date Format','text',NULL,'\"F j, Y\"',NULL,NULL,120,0,NULL,NULL,'general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'text','Custom Date Format',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:32','2026-07-29 06:33:32'),(14,'general','time_format','Time Format','radio',NULL,'\"g:i a\"','{\"H:i\": \"13:14\", \"g:i A\": \"1:14 PM\", \"g:i a\": \"1:14 pm\", \"custom\": \"Custom\"}',NULL,130,0,NULL,NULL,'general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'radio','Time Format','{\"H:i\": \"13:14\", \"g:i A\": \"1:14 PM\", \"g:i a\": \"1:14 pm\", \"custom\": \"Custom\"}',NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:32','2026-07-29 10:07:27'),(15,'general','custom_time_format','Custom Time Format','text',NULL,'\"g:i a\"',NULL,NULL,140,0,NULL,NULL,'general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'text','Custom Time Format',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:32','2026-07-29 06:33:32'),(16,'general','week_starts_on','Week Starts On','select',NULL,'\"monday\"','{\"monday\": \"Monday\", \"sunday\": \"Sunday\", \"saturday\": \"Saturday\"}',NULL,150,0,NULL,NULL,'general','core','admin','manage_settings',1,0,0,0,0,1,NULL,'select','Week Starts On','{\"monday\": \"Monday\", \"sunday\": \"Sunday\", \"saturday\": \"Saturday\"}',NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:32','2026-07-29 10:07:27'),(17,'seo','seo_title','Default SEO Title','text',NULL,'\"Z4Rank\"',NULL,NULL,10,1,NULL,NULL,'seo','core','public','manage_settings',1,0,0,1,1,1,NULL,'text','Default SEO Title',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:33','2026-07-29 06:33:33'),(18,'seo','seo_description','Default Meta Description','textarea',NULL,'\"Z4Rank modular platform.\"',NULL,NULL,20,1,NULL,NULL,'seo','core','public','manage_settings',1,0,0,1,1,1,NULL,'textarea','Default Meta Description',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:33','2026-07-29 06:33:33'),(19,'seo','seo_keywords','Default Meta Keywords','text',NULL,'\"z4rank, seo, platform\"',NULL,NULL,30,1,NULL,NULL,'seo','core','public','manage_settings',1,0,0,1,1,1,NULL,'text','Default Meta Keywords',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:33','2026-07-29 06:33:33'),(20,'seo','robots_index','Allow Search Engines To Index','boolean',NULL,'true',NULL,NULL,40,1,NULL,NULL,'seo','core','public','manage_settings',1,0,0,1,1,1,NULL,'boolean','Allow Search Engines To Index',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:33','2026-07-29 06:33:33'),(21,'seo','robots_follow','Allow Search Engines To Follow Links','boolean',NULL,'true',NULL,NULL,50,1,NULL,NULL,'seo','core','public','manage_settings',1,0,0,1,1,1,NULL,'boolean','Allow Search Engines To Follow Links',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:33','2026-07-29 06:33:33'),(22,'seo','open_graph_title','Open Graph Title','text',NULL,'\"Z4Rank\"',NULL,NULL,60,1,NULL,NULL,'seo','core','public','manage_settings',1,0,0,1,1,1,NULL,'text','Open Graph Title',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:33','2026-07-29 06:33:33'),(23,'seo','open_graph_description','Open Graph Description','textarea',NULL,'\"Z4Rank modular platform.\"',NULL,NULL,70,1,NULL,NULL,'seo','core','public','manage_settings',1,0,0,1,1,1,NULL,'textarea','Open Graph Description',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:34','2026-07-29 06:33:34'),(24,'seo','open_graph_image','Open Graph Image','file',NULL,NULL,NULL,'Recommended image size: 1200 x 630 pixels.',80,1,NULL,'Recommended image size: 1200 x 630 pixels.','seo','core','public','manage_settings',1,0,0,1,1,1,NULL,'file','Open Graph Image',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:34','2026-07-29 06:33:34'),(25,'front_page','front_page_mode','Homepage Displays','radio',NULL,'\"default\"','{\"static\": \"A selected page\", \"default\": \"Default application home\"}',NULL,10,1,NULL,NULL,'front_page','core','public','manage_settings',1,0,0,1,1,1,NULL,'radio','Homepage Displays','{\"static\": \"A selected page\", \"default\": \"Default application home\"}',NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:34','2026-07-29 10:07:28'),(26,'front_page','front_page','Front Page','select',NULL,'\"front.home\"',NULL,'Published platform pages appear here automatically.',20,1,NULL,'Published platform pages appear here automatically.','front_page','core','public','manage_settings',1,0,0,1,1,1,NULL,'select','Front Page',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:34','2026-07-29 06:33:34'),(27,'theme','light_background','Light Background','color',NULL,'\"#ffffff\"',NULL,NULL,10,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Light Background',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:34','2026-07-29 06:33:34'),(28,'theme','light_surface','Light Surface','color',NULL,'\"#ffffff\"',NULL,NULL,20,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Light Surface',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:34','2026-07-29 06:33:34'),(29,'theme','light_text','Light Text','color',NULL,'\"#111827\"',NULL,NULL,30,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Light Text',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:35','2026-07-29 06:33:35'),(30,'theme','light_muted_text','Light Muted Text','color',NULL,'\"#4b5563\"',NULL,NULL,40,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Light Muted Text',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:35','2026-07-29 06:33:35'),(31,'theme','dark_background','Dark Background','color',NULL,'\"#0f172a\"',NULL,NULL,50,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Dark Background',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:35','2026-07-29 06:33:35'),(32,'theme','dark_surface','Dark Surface','color',NULL,'\"#111827\"',NULL,NULL,60,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Dark Surface',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:35','2026-07-29 06:33:35'),(33,'theme','dark_text','Dark Text','color',NULL,'\"#f9fafb\"',NULL,NULL,70,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Dark Text',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:35','2026-07-29 06:33:35'),(34,'theme','dark_muted_text','Dark Muted Text','color',NULL,'\"#cbd5e1\"',NULL,NULL,80,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Dark Muted Text',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:35','2026-07-29 06:33:35'),(35,'theme','accent_color','Accent Color','color',NULL,'\"#df0000\"',NULL,NULL,90,1,NULL,NULL,'theme','core','public','manage_settings',1,0,0,1,1,1,NULL,'color','Accent Color',NULL,NULL,NULL,NULL,NULL,0,0,'active',1,'2026-07-29 06:33:36','2026-07-29 06:33:36');
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_theme_builder_conditions`
--

DROP TABLE IF EXISTS `platform_theme_builder_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_theme_builder_conditions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint unsigned NOT NULL,
  `operator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'include',
  `scope` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'entire_site',
  `target_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_theme_builder_conditions_page_id_unique` (`page_id`),
  KEY `platform_theme_builder_conditions_operator_scope_index` (`operator`,`scope`),
  CONSTRAINT `platform_theme_builder_conditions_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `platform_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_theme_builder_conditions`
--

LOCK TABLES `platform_theme_builder_conditions` WRITE;
/*!40000 ALTER TABLE `platform_theme_builder_conditions` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_theme_builder_conditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_theme_builder_template_conditions`
--

DROP TABLE IF EXISTS `platform_theme_builder_template_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_theme_builder_template_conditions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned NOT NULL,
  `operator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'include',
  `scope` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'entire_site',
  `target_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_theme_builder_template_conditions_template_id_unique` (`template_id`),
  KEY `platform_theme_builder_template_conditions_operator_scope_index` (`operator`,`scope`),
  CONSTRAINT `platform_theme_builder_template_conditions_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `platform_theme_builder_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_theme_builder_template_conditions`
--

LOCK TABLES `platform_theme_builder_template_conditions` WRITE;
/*!40000 ALTER TABLE `platform_theme_builder_template_conditions` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_theme_builder_template_conditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_theme_builder_templates`
--

DROP TABLE IF EXISTS `platform_theme_builder_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_theme_builder_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'blank',
  `html` longtext COLLATE utf8mb4_unicode_ci,
  `css` longtext COLLATE utf8mb4_unicode_ci,
  `page_builder_json` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_theme_builder_templates_slug_unique` (`slug`),
  KEY `platform_theme_builder_templates_created_by_foreign` (`created_by`),
  KEY `platform_theme_builder_templates_template_type_status_index` (`template_type`,`status`),
  CONSTRAINT `platform_theme_builder_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_theme_builder_templates`
--

LOCK TABLES `platform_theme_builder_templates` WRITE;
/*!40000 ALTER TABLE `platform_theme_builder_templates` DISABLE KEYS */;
INSERT INTO `platform_theme_builder_templates` VALUES (1,'header','MAin Hedar','main-hedar',NULL,'published','blank','','',NULL,'{\"created_from\": \"blank\"}',2,'2026-07-29 07:14:03','2026-07-29 07:14:03');
/*!40000 ALTER TABLE `platform_theme_builder_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_updates`
--

DROP TABLE IF EXISTS `plugin_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `plugin_id` bigint unsigned DEFAULT NULL,
  `current_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `available_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changelog` json DEFAULT NULL,
  `package_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL,
  `installed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plugin_updates_plugin_slug_version_unique` (`plugin_slug`,`version`),
  KEY `plugin_updates_plugin_slug_index` (`plugin_slug`),
  KEY `plugin_updates_plugin_id_foreign` (`plugin_id`),
  CONSTRAINT `plugin_updates_plugin_id_foreign` FOREIGN KEY (`plugin_id`) REFERENCES `plugins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_updates`
--

LOCK TABLES `plugin_updates` WRITE;
/*!40000 ALTER TABLE `plugin_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugins`
--

DROP TABLE IF EXISTS `plugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uploaded',
  `source_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `installed_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settings_json` json DEFAULT NULL,
  `installed_at` timestamp NULL DEFAULT NULL,
  `enabled_at` timestamp NULL DEFAULT NULL,
  `disabled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manifest` json DEFAULT NULL,
  `dependencies` json DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plugins_slug_unique` (`slug`),
  KEY `plugins_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugins`
--

LOCK TABLES `plugins` WRITE;
/*!40000 ALTER TABLE `plugins` DISABLE KEYS */;
INSERT INTO `plugins` VALUES (1,'Admin Theme','admin-theme','1.1.0','Modules\\ArtInpaAdminProTheme\\ArtInpaAdminProThemeServiceProvider','active',NULL,NULL,NULL,'2026-07-29 06:33:47',NULL,NULL,'2026-07-29 06:33:47','2026-07-29 10:14:01','Professional light admin dashboard theme using the Art INPA red and orange visual identity.','Art INPA','/var/www/html/modules/admin-theme','{\"docs\": \"docs/plugin.md\", \"name\": \"Admin Theme\", \"slug\": \"admin-theme\", \"type\": \"theme\", \"hooks\": {\"admin-theme.loaded\": {\"type\": \"action\", \"description\": \"Fires when the default admin theme plugin is available.\"}}, \"theme\": {\"scope\": \"admin\", \"default\": true}, \"author\": \"Art INPA\", \"routes\": {\"admin\": {\"file\": \"routes/admin.php\", \"name\": \"\", \"prefix\": \"\", \"middleware\": [\"web\", \"auth\", \"staff\"]}}, \"version\": \"1.1.0\", \"provider\": \"Modules\\\\ArtInpaAdminProTheme\\\\ArtInpaAdminProThemeServiceProvider\", \"settings\": {\"label\": \"Theme settings\", \"route\": \"admin.plugins.admin-theme.settings\"}, \"functions\": {\"admin-theme.assets.inject\": {\"description\": \"Inject the default admin theme stylesheet into the active admin dashboard layout.\"}}, \"uninstall\": {\"tables\": [], \"columns\": [], \"records\": [], \"settings\": [], \"storage_paths\": [], \"operation_target_prefixes\": []}, \"description\": \"Professional light admin dashboard theme using the Art INPA red and orange visual identity.\", \"dependencies\": [], \"provider_file\": \"src/ArtInpaAdminProThemeServiceProvider.php\", \"platform_version\": \">=2.5.0 <3.0.0\"}','[]','2026-07-29 06:33:47'),(2,'Page Builder','page-builder','2.0.4','Modules\\PageBuilder\\PageBuilderServiceProvider','active',NULL,NULL,NULL,'2026-07-29 06:33:47',NULL,NULL,'2026-07-29 06:33:47','2026-07-29 10:14:00','Protected visual page builder combining the platform page system with Front Builder hierarchy, menu and publishing capabilities.','Art INPA','/var/www/html/modules/page-builder','{\"core\": true, \"name\": \"Page Builder\", \"slug\": \"page-builder\", \"type\": \"feature\", \"menus\": [{\"key\": \"page-builder.admin\", \"name\": \"Page Builder\", \"items\": [{\"icon\": \"layout\", \"order\": 10, \"route\": \"admin.pages.index\", \"title\": \"Page Builder\", \"permission\": \"pages.manage\"}], \"location\": \"admin\"}], \"author\": \"Art INPA\", \"routes\": {\"web\": {\"file\": \"routes/web.php\", \"name\": \"\", \"prefix\": \"\", \"middleware\": [\"web\"]}, \"admin\": {\"file\": \"routes/admin.php\", \"name\": \"\", \"prefix\": \"\", \"middleware\": [\"web\", \"auth\", \"staff\"]}}, \"version\": \"2.0.4\", \"provider\": \"Modules\\\\PageBuilder\\\\PageBuilderServiceProvider\", \"lifecycle\": {\"file\": \"src/PageBuilderLifecycle.php\", \"install\": \"Modules\\\\PageBuilder\\\\PageBuilderLifecycle@consolidate\", \"activate\": \"Modules\\\\PageBuilder\\\\PageBuilderLifecycle@consolidate\", \"reactivate\": \"Modules\\\\PageBuilder\\\\PageBuilderLifecycle@consolidate\"}, \"uninstall\": {\"tables\": [], \"columns\": [{\"table\": \"platform_pages\", \"columns\": [\"parent_id\", \"category\", \"menu_label\", \"show_in_menu\"]}], \"records\": [], \"settings\": [], \"storage_paths\": [], \"operation_target_prefixes\": [\"platform-page\", \"page-builder\", \"front-builder\"]}, \"migrations\": \"database/migrations\", \"description\": \"Protected visual page builder combining the platform page system with Front Builder hierarchy, menu and publishing capabilities.\", \"permissions\": [{\"name\": \"pages.manage\", \"description\": \"Manage Page Builder pages, templates and revisions.\"}, {\"name\": \"front-builder.manage\", \"description\": \"Legacy alias retained for existing role assignments.\"}], \"dependencies\": [], \"provider_file\": \"src/PageBuilderServiceProvider.php\", \"platform_version\": \">=2.5.0 <3.0.0\"}','[]','2026-07-29 06:33:47');
/*!40000 ALTER TABLE `plugins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(1,3),(2,3),(5,3),(7,3),(1,4);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super-admin','web','2026-07-29 06:33:20','2026-07-29 06:33:20'),(2,'admin','web','2026-07-29 06:33:20','2026-07-29 06:33:20'),(3,'staff','web','2026-07-29 06:33:21','2026-07-29 06:33:21'),(4,'employee','web','2026-07-29 06:33:21','2026-07-29 06:33:21'),(5,'user','web','2026-07-29 06:33:21','2026-07-29 06:33:21');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
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
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'ziad.mansor','ziad.mansor@gmail.com','2026-07-29 06:33:24','$2y$12$njpoy9Syt47Ct56sLfRfBOxY8L6gGX/Iw7KKiw62XJp2GNRP0BNpS',NULL,'2026-07-29 06:33:24','2026-07-29 06:33:24');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'art_inpa_test'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-29 16:14:02
