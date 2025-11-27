-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Generation Time: Nov 24, 2025 at 03:14 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ejawatan_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE  PROCEDURE `create_application_reference` (OUT `ref_number` VARCHAR(50))   BEGIN
    DECLARE year_prefix CHAR(4);
    DECLARE month_prefix CHAR(2);
    DECLARE day_prefix CHAR(2);
    DECLARE random_suffix CHAR(6);
    
    -- Get current date components
    SET year_prefix = DATE_FORMAT(NOW(), '%Y');
    SET month_prefix = DATE_FORMAT(NOW(), '%m');
    SET day_prefix = DATE_FORMAT(NOW(), '%d');
    
    -- Generate random suffix (6 digits)
    SET random_suffix = LPAD(FLOOR(RAND() * 1000000), 6, '0');
    
    -- Combine to create reference number: APP-YYYYMMDD-XXXXXX
    SET ref_number = CONCAT('APP-', year_prefix, month_prefix, day_prefix, '-', random_suffix);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `action_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `action_type`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 6.945528030395508,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=4&ref=APP-2025-0F4298F9\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 12:31:38'),
(2, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 6.961964130401611,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=4&ref=APP-2025-0F4298F9\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-21 12:31:38'),
(3, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 14:40:09'),
(4, 1, 'Database backup initiated', 'CREATE', 'backup', NULL, '{\"database\":\"ejawatan_db\",\"initiated_at\":\"2025-10-22 22:40:21\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 14:40:21'),
(5, 1, 'Database backup created successfully: backup_ejawatan_db_2025-10-22_22-40-21.zip', 'CREATE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-10-22_22-40-21.zip\",\"size\":8244,\"method\":\"PDO\",\"database\":\"ejawatan_db\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 14:40:22'),
(6, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 1.9927911758422852,\n    \"memory_current\": 4194304,\n    \"memory_peak\": 4194304,\n    \"url\": \"\\/preview-application.php?app_id=13&ref=APP-20251024-3B3FF3A6\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 11:19:03'),
(7, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.009857177734375,\n    \"memory_current\": 4194304,\n    \"memory_peak\": 4194304,\n    \"url\": \"\\/preview-application.php?app_id=13&ref=APP-20251024-3B3FF3A6\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 11:19:03'),
(8, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-25 22:58:19'),
(9, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-25 23:46:53'),
(10, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:08:51'),
(11, 1, 'Database backup deleted: backup_ejawatan_db_2025-10-22_22-40-21.zip', 'DELETE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-10-22_22-40-21.zip\",\"filepath\":\"..\\/backups\\/backup_ejawatan_db_2025-10-22_22-40-21.zip\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:08:59'),
(12, 1, 'Database backup deleted: backup_ejawatan_db_2025-10-19_09-49-02.zip', 'DELETE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-10-19_09-49-02.zip\",\"filepath\":\"..\\/backups\\/backup_ejawatan_db_2025-10-19_09-49-02.zip\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:01'),
(13, 1, 'Database backup initiated', 'CREATE', 'backup', NULL, '{\"database\":\"ejawatan_db\",\"initiated_at\":\"2025-10-26 08:09:03\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:03'),
(14, 1, 'Database backup created successfully: backup_ejawatan_db_2025-10-26_08-09-03.zip', 'CREATE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-10-26_08-09-03.zip\",\"size\":11239,\"method\":\"PDO\",\"database\":\"ejawatan_db\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:03'),
(15, 1, 'Database backup initiated', 'CREATE', 'backup', NULL, '{\"database\":\"ejawatan_db\",\"initiated_at\":\"2025-10-26 08:09:11\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:11'),
(16, 1, 'Database backup created successfully: backup_ejawatan_db_2025-10-26_08-09-11.zip', 'CREATE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-10-26_08-09-11.zip\",\"size\":11282,\"method\":\"PDO\",\"database\":\"ejawatan_db\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:11'),
(17, 1, 'Database backup deleted: backup_ejawatan_db_2025-10-26_08-09-11.zip', 'DELETE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-10-26_08-09-11.zip\",\"filepath\":\"..\\/backups\\/backup_ejawatan_db_2025-10-26_08-09-11.zip\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:23'),
(18, 1, 'Database backup deleted: backup_ejawatan_db_2025-10-26_08-09-03.zip', 'DELETE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-10-26_08-09-03.zip\",\"filepath\":\"..\\/backups\\/backup_ejawatan_db_2025-10-26_08-09-03.zip\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:25'),
(19, 1, 'Database backup initiated', 'CREATE', 'backup', NULL, '{\"database\":\"ejawatan_db\",\"initiated_at\":\"2025-10-26 08:09:30\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:30'),
(20, 1, 'Database backup created successfully: backup_ejawatan_db_2025-10-26_08-09-30.zip', 'CREATE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-10-26_08-09-30.zip\",\"size\":11360,\"method\":\"PDO\",\"database\":\"ejawatan_db\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 00:09:30'),
(21, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 1.3205690383911133,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=26&ref=APP-20251102-C7081A51\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-02 16:27:08'),
(22, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.1452670097351074,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=26&ref=APP-20251102-C7081A51\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-02 16:27:09'),
(23, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 1.3684940338134766,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=26&ref=APP-20251102-C7081A51\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-02 16:27:16'),
(24, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 1.7333250045776367,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=26&ref=APP-20251102-C7081A51\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-02 16:27:16'),
(25, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.3846781253814697,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=26&ref=APP-20251102-C7081A51\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-02 22:39:41'),
(26, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.8434250354766846,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=26&ref=APP-20251102-C7081A51\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-02 22:39:41'),
(27, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-09 08:53:14'),
(28, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.1079108715057373,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=9&ref=APP-20251113-B171F56E\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 23:17:58'),
(29, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.5133750438690186,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=9&ref=APP-20251113-B171F56E\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 23:17:58'),
(30, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.4795501232147217,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=12&ref=APP-20251114-5325E30B\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:57:12'),
(31, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 12.726927995681763,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=12&ref=APP-20251114-5325E30B\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:57:23'),
(32, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.248995065689087,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=10&ref=APP-20251114-8392FB34\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 13:03:24'),
(33, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.8242220878601074,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?app_id=10&ref=APP-20251114-8392FB34\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 13:03:25'),
(34, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 1.638875961303711,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:37:44'),
(35, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.2117271423339844,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 05:37:45'),
(36, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.32646107673645,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:35:17'),
(37, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.835944175720215,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:35:18'),
(38, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.122183084487915,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:36:47'),
(39, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.631247043609619,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:36:47'),
(40, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 3.043632984161377,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:38:43'),
(41, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 3.8104348182678223,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:38:43'),
(42, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.0656681060791016,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:40:00'),
(43, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.657809019088745,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:40:01'),
(44, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.8979380130767822,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:40:56'),
(45, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 3.4011011123657227,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:40:57'),
(46, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.1086580753326416,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:45:52'),
(47, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 3.128049850463867,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:45:53'),
(48, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 1.6822569370269775,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:09:03'),
(49, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 2.2050890922546387,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:09:04'),
(50, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 2.895030975341797,\n    \"memory_current\": 4194304,\n    \"memory_peak\": 4194304,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 03:32:49'),
(51, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 3.5844218730926514,\n    \"memory_current\": 4194304,\n    \"memory_peak\": 4194304,\n    \"url\": \"\\/preview-application.php?ref=APP-2025-8235-D5224E2D\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 03:32:50'),
(52, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:27:04'),
(53, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:27:31'),
(54, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:30:52'),
(55, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:31:07'),
(56, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:31:20'),
(57, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:31:41'),
(58, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 53.13539004325867,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/draft-applications.php\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:50:02'),
(59, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 53.16567397117615,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/draft-applications.php\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:50:02'),
(60, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 27.0815691947937,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/index.php\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:50:02'),
(61, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:50:02'),
(62, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 27.122748136520386,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/index.php\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:50:02'),
(63, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 26.049113988876343,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/index.php\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:50:02'),
(64, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:50:02'),
(65, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 26.07874894142151,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/index.php\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:50:02'),
(66, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 1.531637191772461,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/application-view.php?id=62\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 14:34:19'),
(67, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 1.7310600280761719,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/application-view.php?id=62\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 14:34:19'),
(68, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 14:38:48'),
(69, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 14:38:55'),
(70, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:03:24'),
(71, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:06:38'),
(72, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:09:37'),
(73, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:12:23'),
(74, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:13:33'),
(75, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:14:24'),
(76, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:14:35'),
(77, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:19:48'),
(78, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:20:43'),
(79, 1, 'Database backup initiated', 'CREATE', 'backup', NULL, '{\"database\":\"ejawatan_db\",\"initiated_at\":\"2025-11-17 07:12:57\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 23:12:57'),
(80, 1, 'Database backup created successfully: backup_ejawatan_db_2025-11-17_07-12-57.zip', 'CREATE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-11-17_07-12-57.zip\",\"size\":16920,\"method\":\"PDO\",\"database\":\"ejawatan_db\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 23:12:58'),
(81, 1, 'Viewed activity logs', 'OTHER', 'logs', NULL, '[]', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 23:13:18'),
(82, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 23:13:49'),
(83, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 23:14:29'),
(84, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 23:14:47'),
(85, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 23:15:29'),
(86, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 15:11:16'),
(87, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 16:30:20'),
(88, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 1.573019027709961,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/application-view.php?id=64\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 17:25:50'),
(89, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 1.6392619609832764,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/application-view.php?id=64\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 17:25:50'),
(90, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 1.006040096282959,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/application-view.php?id=64\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 17:26:02'),
(91, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 1.1628799438476562,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/application-view.php?id=64\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 17:26:02'),
(92, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 14:32:58'),
(93, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 14:49:15'),
(94, 1, 'Database backup initiated', 'CREATE', 'backup', NULL, '{\"database\":\"ejawatan_db\",\"initiated_at\":\"2025-11-20 22:49:25\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 14:49:25'),
(95, 1, 'Database backup created successfully: backup_ejawatan_db_2025-11-20_22-49-25.zip', 'CREATE', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-11-20_22-49-25.zip\",\"size\":19015,\"method\":\"PDO\",\"database\":\"ejawatan_db\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 14:49:25'),
(96, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-20 14:51:16'),
(97, 1, 'Database backup downloaded: backup_ejawatan_db_2025-11-20_22-49-25.zip', 'READ', 'backup', NULL, '{\"filename\":\"backup_ejawatan_db_2025-11-20_22-49-25.zip\",\"filesize\":19015,\"admin_id\":1}', '172.18.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:145.0) Gecko/20100101 Firefox/145.0', '2025-11-20 14:51:39'),
(98, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 23:06:17'),
(99, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 23:10:28'),
(100, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 23:18:25'),
(101, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 23:19:57'),
(102, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 23:34:06'),
(103, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:29:30'),
(104, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:29:51'),
(105, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:30:17'),
(106, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:30:39'),
(107, 1, 'Viewed activity logs', 'OTHER', 'logs', NULL, '[]', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:30:45'),
(108, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:31:21'),
(109, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:31:31'),
(110, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:35:09'),
(111, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:36:53'),
(112, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:39:16'),
(113, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:40:07'),
(114, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:40:22'),
(115, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 03:47:45'),
(116, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 04:06:41'),
(117, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 04:07:12'),
(118, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 04:07:15'),
(119, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 04:08:50'),
(120, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 04:12:19'),
(121, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 04:17:40'),
(122, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 06:07:08'),
(123, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 06:32:35'),
(124, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 00:55:22'),
(125, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 00:57:12'),
(126, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 00:57:26'),
(127, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 00:57:26'),
(128, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:15:24'),
(129, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:17:32'),
(130, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:17:35'),
(131, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:18:20'),
(132, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:18:23'),
(133, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:18:31'),
(134, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:18:59'),
(135, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:18:59'),
(136, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:19:10'),
(137, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:19:12'),
(138, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:19:19'),
(139, 1, 'Viewed job details', 'OTHER', 'job', 3, '{\"job_id\":\"JOB-000003\",\"job_title\":\"PEMANDU KENDERAAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:19:22'),
(140, 1, 'Viewed job details', 'OTHER', 'job', 3, '{\"job_id\":\"JOB-000003\",\"job_title\":\"PEMANDU KENDERAAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:20:44'),
(141, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:25:35'),
(142, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:25:38'),
(143, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:25:49'),
(144, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:27:10'),
(145, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:28:52'),
(146, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:28:54');
INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `action_type`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(147, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:29:23'),
(148, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:30:49'),
(149, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:31:39'),
(150, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:32:04'),
(151, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:32:08'),
(152, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:32:25'),
(153, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:34:37'),
(154, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:34:41'),
(155, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:34:59'),
(156, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:34:59'),
(157, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:35:03'),
(158, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:36:06'),
(159, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:36:08'),
(160, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:40:31'),
(161, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:41:15'),
(162, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:41:48'),
(163, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:47:35'),
(164, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:49:41'),
(165, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:50:46'),
(166, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:51:30'),
(167, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:52:01'),
(168, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 23:52:11'),
(169, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:02:57'),
(170, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:05:19'),
(171, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:09:21'),
(172, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:10:05'),
(173, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:10:05'),
(174, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:10:07'),
(175, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:14:23'),
(176, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:14:26'),
(177, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:14:38'),
(178, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:14:38'),
(179, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:18:53'),
(180, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:18:53'),
(181, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"JOB-000003\",\"changes\":{\"job_title\":{\"from\":\"PEMANDU KENDERAAN\",\"to\":\"PEMANDU KENDERAAN\"},\"ad_date\":{\"from\":\"2024-06-29\",\"to\":\"2024-06-29\"},\"ad_close_date\":{\"from\":\"2025-12-31\",\"to\":\"2025-12-31\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"R3\",\"to\":\"R3\"},\"salary_min\":{\"from\":\"1200.00\",\"to\":\"1200.00\"},\"salary_max\":{\"from\":\"1800.00\",\"to\":\"1800.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:24:02'),
(182, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:24:03'),
(183, 1, 'Viewed job details', 'OTHER', 'job', 3, '{\"job_id\":\"JOB-000003\",\"job_title\":\"PEMANDU KENDERAAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:24:13'),
(184, 1, 'Updated job posting', 'UPDATE', 'job', 3, '{\"job_code\":\"JOB-000003\",\"changes\":{\"job_title\":{\"from\":\"PEMANDU KENDERAAN\",\"to\":\"PEMANDU KENDERAAN\"},\"ad_date\":{\"from\":\"2024-06-29\",\"to\":\"2024-06-29\"},\"ad_close_date\":{\"from\":\"2025-12-31\",\"to\":\"2025-12-31\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"R3\",\"to\":\"R3\"},\"salary_min\":{\"from\":\"1200.00\",\"to\":\"1200.00\"},\"salary_max\":{\"from\":\"1800.00\",\"to\":\"1800.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:24:24'),
(185, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:24:24'),
(186, 1, 'Viewed job details', 'OTHER', 'job', 3, '{\"job_id\":\"JOB-000003\",\"job_title\":\"PEMANDU KENDERAAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 00:24:27'),
(187, 1, 'Updated job posting', 'UPDATE', 'job', 3, '{\"job_code\":\"JOB-000003\",\"changes\":{\"job_title\":{\"from\":\"PEMANDU KENDERAAN\",\"to\":\"PEMANDU KENDERAAN\"},\"ad_date\":{\"from\":\"2024-06-29\",\"to\":\"2024-06-29\"},\"ad_close_date\":{\"from\":\"2025-12-31\",\"to\":\"2025-12-31\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"R3\",\"to\":\"R3\"},\"salary_min\":{\"from\":\"1200.00\",\"to\":\"1200.00\"},\"salary_max\":{\"from\":\"1800.00\",\"to\":\"1800.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:07:52'),
(188, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:07:52'),
(189, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:09:17'),
(190, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:09:17'),
(191, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:09:26'),
(192, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:09:39'),
(193, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:09:39'),
(194, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:09:42'),
(195, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:11:33'),
(196, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:11:33'),
(197, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:11:37'),
(198, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:13:54'),
(199, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:13:54'),
(200, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:23:20'),
(201, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:23:20'),
(202, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:23:51'),
(203, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:23:51'),
(204, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:24:06'),
(205, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:26:09'),
(206, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:26:09'),
(207, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:26:25'),
(208, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:26:25'),
(209, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:26:39'),
(210, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:26:39'),
(211, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 01:28:47'),
(212, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:45:50'),
(213, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:49:35'),
(214, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:52:17'),
(215, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:52:25'),
(216, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:52:25'),
(217, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:52:33'),
(218, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:52:33'),
(219, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:53:06'),
(220, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:53:37'),
(221, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:53:37'),
(222, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:53:45'),
(223, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:53:45'),
(224, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:54:55'),
(225, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:54:57'),
(226, NULL, 'Performance checkpoint: request_start', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_start\",\n    \"execution_time\": 25.032413005828857,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/job-list.php\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:56:52'),
(227, NULL, 'Performance checkpoint: request_end', 'OTHER', 'system', NULL, '{\n    \"checkpoint\": \"request_end\",\n    \"execution_time\": 25.087389945983887,\n    \"memory_current\": 2097152,\n    \"memory_peak\": 2097152,\n    \"url\": \"\\/admin\\/job-list.php\"\n}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 11:56:52'),
(228, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 11:59:59'),
(229, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:00:03'),
(230, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:05:14'),
(231, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:05:14'),
(232, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:05:39'),
(233, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:05:39'),
(234, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:05:46'),
(235, 1, 'Updated job posting', 'UPDATE', 'job', NULL, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:06:01'),
(236, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:06:01'),
(237, 1, 'Updated job posting', 'UPDATE', 'job', 10, '{\"job_code\":\"EJMPHS20251021001825\",\"changes\":{\"job_title\":{\"from\":\"PEGAWAI PENGUATUASA JALAN\",\"to\":\"PEGAWAI PENGUATUASA JALAN\"},\"ad_date\":{\"from\":\"2025-10-21\",\"to\":\"2025-10-21\"},\"ad_close_date\":{\"from\":\"2025-12-05\",\"to\":\"2025-12-05\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"N22\",\"to\":\"N22\"},\"salary_min\":{\"from\":\"4400.00\",\"to\":\"4400.00\"},\"salary_max\":{\"from\":\"5500.00\",\"to\":\"5500.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:08:52'),
(238, 1, 'Viewed job details', 'OTHER', 'job', 10, '{\"job_id\":\"EJMPHS20251021001825\",\"job_title\":\"PEGAWAI PENGUATUASA JALAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:08:52'),
(239, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:09:44'),
(240, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:31:56'),
(241, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:31:59'),
(242, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:32:13'),
(243, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:32:36'),
(244, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:32:48'),
(245, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:32:51'),
(246, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:33:02'),
(247, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:33:11'),
(248, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:33:17'),
(249, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:44:05'),
(250, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:46:15'),
(251, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 12:46:19'),
(252, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 14:33:04'),
(253, 1, 'Updated job posting', 'UPDATE', 'job', 3, '{\"job_code\":\"JOB-000003\",\"changes\":{\"job_title\":{\"from\":\"PEMANDU KENDERAAN\",\"to\":\"PEMANDU KENDERAAN\"},\"ad_date\":{\"from\":\"2024-06-29\",\"to\":\"2024-06-29\"},\"ad_close_date\":{\"from\":\"2025-12-31\",\"to\":\"2025-12-31\"},\"edaran_iklan\":{\"from\":\"DALAMAN SAHAJA (MPHS)\",\"to\":\"DALAMAN SAHAJA (MPHS)\"},\"kod_gred\":{\"from\":\"R3\",\"to\":\"R3\"},\"salary_min\":{\"from\":\"1200.00\",\"to\":\"1200.00\"},\"salary_max\":{\"from\":\"1800.00\",\"to\":\"1800.00\"}}}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 14:33:38'),
(254, 1, 'Viewed job details', 'OTHER', 'job', 3, '{\"job_id\":\"JOB-000003\",\"job_title\":\"PEMANDU KENDERAAN\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 14:33:38'),
(255, 1, 'Viewed job listings', 'OTHER', 'job_list', NULL, '{\"published_count\":3,\"upcoming_count\":0,\"recently_closed_count\":0,\"expired_count\":1}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 14:33:44'),
(256, 1, 'Admin dashboard accessed', 'OTHER', 'admin', 1, '{\"page\":\"dashboard\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-24 14:38:24');

-- --------------------------------------------------------

--
-- Table structure for table `application_application_main`
--

CREATE TABLE `application_application_main` (
  `id` bigint UNSIGNED NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_id` int NOT NULL,
  `job_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pengistiharan` tinyint(1) DEFAULT '0',
  `jawatan_dipohon` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_penuh` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombor_ic` char(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombor_surat_beranak` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jantina` enum('Lelaki','Perempuan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tarikh_lahir` date NOT NULL,
  `umur` tinyint UNSIGNED NOT NULL,
  `agama` enum('Islam','Buddha','Hindu','Kristian','Lain-lain') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `taraf_perkahwinan` enum('Bujang','Berkahwin','Duda','Janda','Balu') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombor_telefon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `negeri_kelahiran` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bangsa` enum('Melayu','Cina','India','Kadazan','Lain-lain') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `warganegara` enum('Warganegara Malaysia','Penduduk Tetap','Bukan Warganegara','Pelancong') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tempoh_bermastautin_selangor` tinyint UNSIGNED NOT NULL,
  `alamat_tetap` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `poskod_tetap` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bandar_tetap` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `negeri_tetap` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat_surat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poskod_surat` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bandar_surat` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `negeri_surat` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lesen_memandu_set` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tarikh_tamat_lesen` date DEFAULT NULL,
  `gambar_passport_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salinan_ic_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salinan_surat_beranak_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salinan_lesen_memandu_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_pasangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon_pasangan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bilangan_anak` int DEFAULT NULL,
  `status_pasangan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_pasangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_majikan_pasangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon_pejabat_pasangan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_majikan_pasangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bandar_majikan_pasangan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `negeri_majikan_pasangan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poskod_majikan_pasangan` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submission_locked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_id` int DEFAULT NULL,
  `status` enum('PENDING','SHORTLISTED','INTERVIEWED','OFFERED','ACCEPTED','REJECTED','PROCESSING') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `status_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reviewed_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `application_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `alamat_surat_sama` tinyint(1) DEFAULT '0' COMMENT 'Same as permanent address flag',
  `submission_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT 'Application submission status : Draft, PENDING , REVIEWED , APPROVED',
  `ada_pengalaman_kerja` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerja_perkhidmatan_awam` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerja_perkhidmatan_awam_nyatakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pertalian_kakitangan` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pertalian_kakitangan_nyatakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `nama_kakitangan_pertalian` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pernah_bekerja_mphs` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pernah_bekerja_mphs_nyatakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tindakan_tatatertib` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tindakan_tatatertib_nyatakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kesalahan_undangundang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kesalahan_undangundang_nyatakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `muflis` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `muflis_nyatakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_application_main`
--

INSERT INTO `application_application_main` (`id`, `application_reference`, `job_id`, `job_code`, `payment_reference`, `pengistiharan`, `jawatan_dipohon`, `nama_penuh`, `nombor_ic`, `nombor_surat_beranak`, `jantina`, `tarikh_lahir`, `umur`, `agama`, `taraf_perkahwinan`, `email`, `nombor_telefon`, `negeri_kelahiran`, `bangsa`, `warganegara`, `tempoh_bermastautin_selangor`, `alamat_tetap`, `poskod_tetap`, `bandar_tetap`, `negeri_tetap`, `alamat_surat`, `poskod_surat`, `bandar_surat`, `negeri_surat`, `lesen_memandu_set`, `tarikh_tamat_lesen`, `gambar_passport_path`, `salinan_ic_path`, `salinan_surat_beranak_path`, `salinan_lesen_memandu_path`, `nama_pasangan`, `telefon_pasangan`, `bilangan_anak`, `status_pasangan`, `pekerjaan_pasangan`, `nama_majikan_pasangan`, `telefon_pejabat_pasangan`, `alamat_majikan_pasangan`, `bandar_majikan_pasangan`, `negeri_majikan_pasangan`, `poskod_majikan_pasangan`, `submission_locked`, `created_at`, `updated_at`, `status_id`, `status`, `status_notes`, `reviewed_at`, `approved_at`, `reviewed_by`, `approved_by`, `application_date`, `alamat_surat_sama`, `submission_status`, `ada_pengalaman_kerja`, `pekerja_perkhidmatan_awam`, `pekerja_perkhidmatan_awam_nyatakan`, `pertalian_kakitangan`, `pertalian_kakitangan_nyatakan`, `nama_kakitangan_pertalian`, `pernah_bekerja_mphs`, `pernah_bekerja_mphs_nyatakan`, `tindakan_tatatertib`, `tindakan_tatatertib_nyatakan`, `kesalahan_undangundang`, `kesalahan_undangundang_nyatakan`, `muflis`, `muflis_nyatakan`) VALUES
(62, 'APP-2025-8235-D5224E2D', 10, 'EJMPHS20251021001825', NULL, 1, 'PEGAWAI PENGUATUASA JALAN', 'NEFIZON BIN RUMYANIS', '801119-14-5427', 'HB134656', 'Lelaki', '1980-11-19', 44, 'Islam', 'Berkahwin', 'nefizon@gmail.com', '0169281036', 'WILAYAH PERSEKUTUAN KUALA LUMPUR', 'Melayu', 'Warganegara Malaysia', 22, '27 JALAN ADENIUM 4, BUKIT BERUNTUNG', '48300', 'RAWANG', 'SELANGOR', '27 JALAN ADENIUM 4, BUKIT BERUNTUNG', '48300', 'RAWANG', 'SELANGOR', 'B2,D', '2025-11-06', 'uploads/applications/2025/APP-2025-8235-D5224E2D/gambar_passport_1763301914_6919da1aec71f.jpg', 'uploads/applications/2025/APP-2025-8235-D5224E2D/salinan_ic_1763282038_69198c76e4de8.jpg', 'uploads/applications/2025/APP-2025-8235-D5224E2D/salinan_surat_beranak_1763279520_691982a00eee0.jpg', 'uploads/applications/2025/APP-2025-8235-D5224E2D/salinan_lesen_memandu_1763282038_69198c76efb73.jpg', 'ROZILAWATI BINTI MAT SAID', '09090909', 2, 'BEKERJA SENDIRI', 'BERNIAGA', 'SYARIKAT SENDIRI', '09090909', '27 JALAN ADENIUM 4, BUKIT BERUNTUNG', 'RAWANG', 'SELANGOR', '48900', 1, '2025-11-15 08:03:43', '2025-11-17 16:57:31', 2, 'PENDING', 'perubahan maklumat', NULL, NULL, NULL, NULL, '2025-11-15 00:03:43', 0, 'PENDING', 'YA', 'YA', 'TEST 1', 'YA', NULL, 'TEST 2', 'YA', 'TEST 3', 'YA', 'TEST 4', 'TIDAK', NULL, 'TIDAK', NULL),
(63, 'APP-2025-8725-1368C26E', 10, 'EJMPHS20251021001825', NULL, 1, 'PEGAWAI PENGUATUASA JALAN', 'NORLIANA BINTI ABDULLAH', '830522-09-6564', 'HB67566377', 'Perempuan', '1983-05-22', 42, 'Islam', 'Bujang', 'nefizon@gmail.com', '0169281033', 'PULAU PINANG', 'Melayu', 'Warganegara Malaysia', 12, '6 JALAN INDAH PERMAI', '48000', 'KUALA KUBU BHARU', 'SELANGOR', '6 JALAN INDAH PERMAI', '48000', 'KUALA KUBU BHARU', 'SELANGOR', 'TIADA', NULL, 'uploads/applications/2025/APP-2025-8725-1368C26E/gambar_passport_1763294068_6919bb74aeb4b.jpg', 'uploads/applications/2025/APP-2025-8725-1368C26E/salinan_ic_1763294068_6919bb74b4a48.jpg', 'uploads/applications/2025/APP-2025-8725-1368C26E/salinan_surat_beranak_1763294068_6919bb74ba6c8.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-11-16 19:54:28', '2025-11-16 13:19:07', 2, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2025-11-16 11:54:28', 1, 'draft', NULL, 'YA', 'KASIM BIN SELAMAT', 'YA', NULL, 'ABDUL HAKIM', 'TIDAK', NULL, 'TIDAK', NULL, 'TIDAK', NULL, 'TIDAK', NULL),
(64, 'APP-2025-6855-0D3CA457', 10, 'EJMPHS20251021001825', NULL, 1, 'PEGAWAI PENGUATUASA JALAN', 'ROSLI BIN ANAS', '820911-07-4533', '820911-07-4533', 'Lelaki', '1982-09-11', 43, 'Islam', 'Bujang', 'test@hostnefi.my', '23423424', 'PERAK', 'Melayu', 'Warganegara Malaysia', 11, '27 JALAN ADENIUM 4, BUKIT BERUNTUNG', '48300', 'RAWANG', 'SELANGOR', '27 JALAN ADENIUM 4, BUKIT BERUNTUNG', '48300', 'RAWANG', 'SELANGOR', 'B2', '2025-11-15', 'uploads/applications/2025/APP-2025-6855-0D3CA457/gambar_passport_1763296778_6919c60a53069.jpg', 'uploads/applications/2025/APP-2025-6855-0D3CA457/salinan_ic_1763296778_6919c60a5be39.jpg', 'uploads/applications/2025/APP-2025-6855-0D3CA457/salinan_surat_beranak_1763296778_6919c60a62a31.jpg', 'uploads/applications/2025/APP-2025-6855-0D3CA457/salinan_lesen_memandu_1763296778_6919c60a6aaa5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-16 20:39:38', '2025-11-17 17:32:28', 2, 'PENDING', 'tungu keputusan', '2025-11-17 16:05:00', NULL, NULL, NULL, '2025-11-16 12:39:38', 1, 'AWAITING_RESULT', NULL, 'TIDAK', NULL, 'TIDAK', NULL, NULL, 'TIDAK', NULL, 'TIDAK', NULL, 'TIDAK', NULL, 'TIDAK', NULL),
(68, 'APP-2025-8472-D95DC7B0', 3, 'JOB-000003', NULL, 1, 'PEMANDU KENDERAAN', 'AMEERA BINTI SAMSUDIN', '800101-14-1234', 'SB123456', 'Perempuan', '1980-01-01', 45, 'Islam', 'Bujang', 'nefizon@gmail.com', '0169281036', 'WILAYAH PERSEKUTUAN KUALA LUMPUR', 'Melayu', 'Warganegara Malaysia', 2, 'NO 27 JLN ADENIUM 4', '43000', 'RAWANG', 'SELANGOR', 'NO 27 JLN ADENIUM 4', '43000', 'RAWANG', 'SELANGOR', 'B', '2025-12-06', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/gambar_passport_1763863080_69226a28d7927.jpg', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/salinan_ic_1763863081_69226a29160ce.jpg', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/salinan_surat_beranak_1763863081_69226a292c7d9.jpg', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/salinan_lesen_memandu_1763863081_69226a293bb5c.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-23 09:58:00', '2025-11-24 11:55:51', 2, 'PENDING', 'Semakkan selanjutnya', NULL, NULL, NULL, NULL, '2025-11-23 01:58:00', 0, 'SCREENING', 'YA', 'YA', 'COMPREHENSIVE DOCUMENTATION', 'YA', NULL, 'COMPREHENSIVE DOCUMENTATION', 'YA', 'COMPREHENSIVE DOCUMENTATION', 'YA', 'COMPREHENSIVE DOCUMENTATION', 'YA', 'COMPREHENSIVE DOCUMENTATION', 'YA', 'COMPREHENSIVE DOCUMENTATION');

-- --------------------------------------------------------

--
-- Table structure for table `application_computer_skills`
--

CREATE TABLE `application_computer_skills` (
  `id` int NOT NULL,
  `application_reference` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `application_id` int DEFAULT NULL,
  `nama_perisian` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahap_kemahiran` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_computer_skills`
--

INSERT INTO `application_computer_skills` (`id`, `application_reference`, `application_id`, `nama_perisian`, `tahap_kemahiran`, `created_at`) VALUES
(178, 'APP-2025-8725-1368C26E', 63, 'MICROSOFT WORD', 'MAHIR', '2025-11-16 11:54:28'),
(179, 'APP-2025-8725-1368C26E', 63, 'MICROSOFT EXCEL', 'SEDERHANA', '2025-11-16 11:54:28'),
(180, 'APP-2025-6855-0D3CA457', 64, 'WINDOWS OS', 'MAHIR', '2025-11-16 12:39:38'),
(184, 'APP-2025-8235-D5224E2D', 62, 'WINDOWS OS', 'MAHIR', '2025-11-16 14:18:53'),
(185, 'APP-2025-8235-D5224E2D', 62, 'OFFICE SOFTWARE', 'MAHIR', '2025-11-16 14:18:53'),
(186, 'APP-2025-8235-D5224E2D', 62, 'ADEBE SOFTWARE', 'SEDERHANA', '2025-11-16 14:18:53'),
(233, 'APP-2025-8472-D95DC7B0', 68, 'MICROSOFT OFFICE', 'SEDERHANA', '2025-11-23 22:04:02'),
(234, 'APP-2025-8472-D95DC7B0', 68, 'PERCUBAAN', 'SEDERHANA', '2025-11-23 22:04:02'),
(235, 'APP-2025-8472-D95DC7B0', 68, 'OFFICE', 'MAHIR', '2025-11-23 22:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `application_education`
--

CREATE TABLE `application_education` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_institusi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dari_tahun` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hingga_tahun` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelayakan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pangkat_gred_cgpa` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sijil_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sijil_tambahan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `application_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_education`
--

INSERT INTO `application_education` (`id`, `application_reference`, `nama_institusi`, `dari_tahun`, `hingga_tahun`, `kelayakan`, `pangkat_gred_cgpa`, `sijil_path`, `sijil_tambahan`, `created_at`, `application_id`) VALUES
(83, 'APP-2025-8725-1368C26E', 'INSTITUTE KEMAHIRAN MALAYSIA', '2000', '2001', 'SIJIL', '3.44', 'uploads/applications/2025/APP-2025-8725-1368C26E/persekolahan_0_1763294068_6919bb74c6853.jpg', NULL, '2025-11-16 11:54:28', 63),
(84, 'APP-2025-8725-1368C26E', 'KOLEJ MAKTAB SAINS MALAYSIA', '2001', '2003', 'DIPLOMA', '3.44', 'uploads/applications/2025/APP-2025-8725-1368C26E/persekolahan_1_1763294068_6919bb74cab93.jpg', NULL, '2025-11-16 11:54:28', 63),
(85, 'APP-2025-6855-0D3CA457', 'UNIVERSITI TEKNOLOGI MARA', '1998', '2000', 'DIPLOMA', '3.6', 'uploads/applications/2025/APP-2025-6855-0D3CA457/persekolahan_0_1763296778_6919c60a797e5.jpg', NULL, '2025-11-16 12:39:38', 64),
(87, 'APP-2025-8235-D5224E2D', 'UNIVERSITI TEKNOLOGI MARA', '1998', '2000', 'IJAZAH', '3.6', 'uploads/applications/2025/APP-2025-8235-D5224E2D/persekolahan_0_1763279520_691982a024b2a.jpg', NULL, '2025-11-16 14:18:53', 62),
(121, 'APP-2025-8472-D95DC7B0', 'PERCUBAAN', '1980', '1999', 'DIPLOMA', '22', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/persekolahan_0_1763863081_69226a29847a9.pdf', NULL, '2025-11-23 22:04:02', 68),
(122, 'APP-2025-8472-D95DC7B0', 'KOLEJ AMAL', '2008', '2009', 'DIPLOMA', '23', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/persekolahan_1_1763863877_69226d4510f5e.jpg', NULL, '2025-11-23 22:04:02', 68);

-- --------------------------------------------------------

--
-- Table structure for table `application_extracurricular`
--

CREATE TABLE `application_extracurricular` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sukan_persatuan_kelab` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jawatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `peringkat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahap` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarikh_sijil` date DEFAULT NULL,
  `salinan_sijil_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `application_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_extracurricular`
--

INSERT INTO `application_extracurricular` (`id`, `application_reference`, `sukan_persatuan_kelab`, `jawatan`, `peringkat`, `tahun`, `tahap`, `tarikh_sijil`, `salinan_sijil_filename`, `created_at`, `application_id`) VALUES
(37, 'APP-2025-8725-1368C26E', 'KELAB SUKAN SEKOLAH', 'SETIAUSAHA', 'SEKOLAH', NULL, NULL, NULL, 'uploads/applications/2025/APP-2025-8725-1368C26E/kegiatan_luar_0_1763294068_6919bb74d5216.jpg', '2025-11-16 11:54:28', 63),
(39, 'APP-2025-8235-D5224E2D', 'KEDET REMAJA SEKOLAH', 'SETIAUSAHA', 'DAERAH', NULL, NULL, NULL, 'uploads/applications/2025/APP-2025-8235-D5224E2D/kegiatan_luar_0_1763279520_691982a02ed8e.jpg', '2025-11-16 14:18:53', 62),
(44, 'APP-2025-8472-D95DC7B0', 'PERCUBAAN', 'PERCUBAAN', 'DAERAH', '2025', NULL, '2025-11-20', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/kegiatan_luar_0_1763934761_6923822954805.jpg', '2025-11-23 22:04:02', 68),
(45, 'APP-2025-8472-D95DC7B0', 'SSSADASDASD', 'ASDASDASD', 'SEKOLAH', '2025', NULL, '2025-11-06', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/kegiatan_luar_1_1763934876_6923829c9c978.pdf', '2025-11-23 22:04:02', 68);

-- --------------------------------------------------------

--
-- Table structure for table `application_family_members`
--

CREATE TABLE `application_family_members` (
  `id` int NOT NULL,
  `application_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` int DEFAULT NULL,
  `hubungan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pekerjaan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kewarganegaraan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_family_members`
--

INSERT INTO `application_family_members` (`id`, `application_reference`, `application_id`, `hubungan`, `nama`, `pekerjaan`, `telefon`, `kewarganegaraan`, `created_at`) VALUES
(65, 'APP-2025-8725-1368C26E', 63, 'AYAH', 'KAMAL BIN IBRAHIM', 'PESARA', '0123436576', 'WARGANEGARA MALAYSIA', '2025-11-16 11:54:28'),
(66, 'APP-2025-8725-1368C26E', 63, 'IBU', 'AZLINA BINTI SULAIMAN', 'SURI RUMAH', '0198987765', 'WARGANEGARA MALAYSIA', '2025-11-16 11:54:28'),
(67, 'APP-2025-6855-0D3CA457', 64, 'AYAH', 'TIDAK BERKENAAN', 'TIDAK BERKENAAN', '09090909', 'WARGANEGARA MALAYSIA', '2025-11-16 12:39:38'),
(68, 'APP-2025-6855-0D3CA457', 64, 'IBU', 'TIDAK BERKENAAN', 'TIDAK BERKENAAN', '0169281036', 'WARGANEGARA MALAYSIA', '2025-11-16 12:39:38'),
(71, 'APP-2025-8235-D5224E2D', 62, 'AYAH', 'TIDAK BERKENAAN', 'TIDAK BERKENAAN', '09090909', 'PENDUDUK TETAP', '2025-11-16 14:18:53'),
(72, 'APP-2025-8235-D5224E2D', 62, 'IBU', 'TIDAK BERKENAAN', 'TIDAK BERKENAAN', '000090090', 'PENDUDUK TETAP', '2025-11-16 14:18:53'),
(105, 'APP-2025-8472-D95DC7B0', 68, 'AYAH', 'PERCUBAAN', 'PERCUBAAN', '0169281036', 'WARGANEGARA MALAYSIA', '2025-11-23 22:04:02'),
(106, 'APP-2025-8472-D95DC7B0', 68, 'IBU', 'PERCUBAAN', 'PERCUBAAN', '0169281036', 'PENDUDUK TETAP', '2025-11-23 22:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `application_health`
--

CREATE TABLE `application_health` (
  `id` bigint UNSIGNED NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` int DEFAULT NULL,
  `job_id` bigint UNSIGNED DEFAULT NULL,
  `darah_tinggi` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kencing_manis` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `penyakit_buah_pinggang` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `penyakit_jantung` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batuk_kering_tibi` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kanser` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aids` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `penagih_dadah` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `perokok` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `penyakit_lain` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `penyakit_lain_nyatakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pemegang_kad_oku` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_oku` json DEFAULT NULL,
  `salinan_kad_oku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memakai_cermin_mata` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_rabun` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `berat_kg` decimal(5,1) NOT NULL,
  `tinggi_cm` smallint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_health`
--

INSERT INTO `application_health` (`id`, `application_reference`, `application_id`, `job_id`, `darah_tinggi`, `kencing_manis`, `penyakit_buah_pinggang`, `penyakit_jantung`, `batuk_kering_tibi`, `kanser`, `aids`, `penagih_dadah`, `perokok`, `penyakit_lain`, `penyakit_lain_nyatakan`, `pemegang_kad_oku`, `jenis_oku`, `salinan_kad_oku`, `memakai_cermin_mata`, `jenis_rabun`, `berat_kg`, `tinggi_cm`, `created_at`, `updated_at`) VALUES
(44, 'APP-2025-8725-1368C26E', 63, NULL, 'Ya', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Ya', 'Ya', 'SAKIT SENANG HATI', 'Tidak', NULL, NULL, 'Ya', 'RABUN JAUH', 65.0, 177, '2025-11-16 11:54:28', '2025-11-16 11:54:28'),
(45, 'APP-2025-6855-0D3CA457', 64, NULL, 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', NULL, 'Tidak', NULL, NULL, 'Tidak', NULL, 77.0, 180, '2025-11-16 12:39:38', '2025-11-16 12:39:38'),
(47, 'APP-2025-8235-D5224E2D', 62, NULL, 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Tidak', 'Ya', 'TIADA MAKLUMAT', 'Ya', NULL, NULL, 'Ya', 'RABUN DEKAT', 78.0, 180, '2025-11-16 14:18:53', '2025-11-16 14:18:53'),
(64, 'APP-2025-8472-D95DC7B0', 68, NULL, 'Ya', 'Ya', 'Ya', 'Ya', 'Ya', 'Ya', 'Ya', 'Ya', 'Ya', 'Ya', 'PERCUBAAN', 'Ya', '[\"OKU PENDENGARAN\", \"OKU FIZIKAL\", \"LAIN-LAIN\"]', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/salinan_kad_oku_1763867818_69227caadd4f5.pdf', 'Ya', 'RABUN DEKAT', 66.0, 157, '2025-11-23 22:04:02', '2025-11-23 22:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `application_language_skills`
--

CREATE TABLE `application_language_skills` (
  `id` int NOT NULL,
  `application_reference` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `application_id` bigint UNSIGNED DEFAULT NULL,
  `bahasa` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahap_lisan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahap_penulisan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gred_spm` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_language_skills`
--

INSERT INTO `application_language_skills` (`id`, `application_reference`, `application_id`, `bahasa`, `tahap_lisan`, `tahap_penulisan`, `gred_spm`, `created_at`) VALUES
(169, 'APP-2025-8725-1368C26E', 63, 'MALAYU', 'BAIK', 'BAIK', NULL, '2025-11-16 11:54:28'),
(170, 'APP-2025-8725-1368C26E', 63, 'INGGERIS', 'SEDERHANA', 'SEDERHANA', NULL, '2025-11-16 11:54:28'),
(171, 'APP-2025-6855-0D3CA457', 64, 'MALAYSIA', 'SEDERHANA', 'BAIK', NULL, '2025-11-16 12:39:38'),
(175, 'APP-2025-8235-D5224E2D', 62, 'MALAYSIA', 'BAIK', 'BAIK', NULL, '2025-11-16 14:18:53'),
(176, 'APP-2025-8235-D5224E2D', 62, 'MANDARIN', 'SEDERHANA', 'BAIK', NULL, '2025-11-16 14:18:53'),
(177, 'APP-2025-8235-D5224E2D', 62, 'INGGERIS', 'BAIK', 'BAIK', NULL, '2025-11-16 14:18:53'),
(225, 'APP-2025-8472-D95DC7B0', 68, 'BAHASA MALAYSIA', 'BAIK', 'BAIK', NULL, '2025-11-23 22:04:02'),
(226, 'APP-2025-8472-D95DC7B0', 68, 'PERCUBAAN', 'BAIK', 'LEMAH', NULL, '2025-11-23 22:04:02'),
(227, 'APP-2025-8472-D95DC7B0', 68, 'PERCUBAAN', 'BAIK', 'SEDERHANA', NULL, '2025-11-23 22:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `application_matapelajaran_lain`
--

CREATE TABLE `application_matapelajaran_lain` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` bigint UNSIGNED DEFAULT NULL,
  `spm_index` int NOT NULL,
  `subjek` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gred` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_notifications`
--

CREATE TABLE `application_notifications` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` bigint UNSIGNED DEFAULT NULL,
  `notification_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_professional_bodies`
--

CREATE TABLE `application_professional_bodies` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` bigint UNSIGNED DEFAULT NULL,
  `nama_lembaga` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sijil_diperoleh` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_ahli` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarikh_sijil` date DEFAULT NULL,
  `salinan_sijil_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_professional_bodies`
--

INSERT INTO `application_professional_bodies` (`id`, `application_reference`, `application_id`, `nama_lembaga`, `sijil_diperoleh`, `no_ahli`, `tahun`, `tarikh_sijil`, `salinan_sijil_filename`, `created_at`) VALUES
(38, 'APP-2025-8725-1368C26E', 63, 'BADAN PERTUBUHAN AMAL', '989878', '-', NULL, NULL, 'uploads/applications/2025/APP-2025-8725-1368C26E/badan_profesional_0_1763294068_6919bb74cfef5.jpg', '2025-11-16 11:54:28'),
(40, 'APP-2025-8235-D5224E2D', 62, 'BADAN PROFESSIONAL 1', 'SIJIL KEAHLIAN', '09909', NULL, NULL, 'uploads/applications/2025/APP-2025-8235-D5224E2D/badan_profesional_0_1763279520_691982a02a3ea.jpg', '2025-11-16 14:18:53'),
(47, 'APP-2025-8472-D95DC7B0', 68, 'BADAN PROFESIONAL', 'BADAN PROFESIONAL', 'PERCUBAAN', '2025', '2025-10-28', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/badan_profesional_0_1763912852_69232c949fa7a.pdf', '2025-11-23 22:04:02'),
(48, 'APP-2025-8472-D95DC7B0', 68, 'PERCUBAAN', 'PERCUBAAN', 'PERCUBAAN', '2025', '2025-10-28', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/badan_profesional_1_1763934876_6923829c954b9.pdf', '2025-11-23 22:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `application_references`
--

CREATE TABLE `application_references` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` bigint UNSIGNED DEFAULT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_telefon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tempoh_mengenali` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jawatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_references`
--

INSERT INTO `application_references` (`id`, `application_reference`, `application_id`, `nama`, `no_telefon`, `tempoh_mengenali`, `jawatan`, `alamat`, `created_at`) VALUES
(69, 'APP-2025-8725-1368C26E', 63, 'KASIM SELAMAT', '09189388', '22', NULL, NULL, '2025-11-16 11:54:28'),
(70, 'APP-2025-8725-1368C26E', 63, 'SULAIMAN', '01677878787', '2', NULL, NULL, '2025-11-16 11:54:28'),
(71, 'APP-2025-6855-0D3CA457', 64, 'RUJUKKAN SAYA 1', '0169281036', '3', NULL, NULL, '2025-11-16 12:39:38'),
(72, 'APP-2025-6855-0D3CA457', 64, 'RUJUKKAN SAYA 2', '0169281036', '2', NULL, NULL, '2025-11-16 12:39:38'),
(75, 'APP-2025-8235-D5224E2D', 62, 'RUJUKKAN SAYA 1', '0169281036', '3', NULL, NULL, '2025-11-16 14:18:53'),
(76, 'APP-2025-8235-D5224E2D', 62, 'RUJUKKAN SAYA 2', '09090909', '33', NULL, NULL, '2025-11-16 14:18:53'),
(109, 'APP-2025-8472-D95DC7B0', 68, 'JOHN DOE', '0169281036', '5', NULL, NULL, '2025-11-23 22:04:02'),
(110, 'APP-2025-8472-D95DC7B0', 68, 'JANE DOE', '0169281036', '3', NULL, NULL, '2025-11-23 22:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `application_settings`
--

CREATE TABLE `application_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_type` enum('string','integer','boolean','json') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_settings`
--

INSERT INTO `application_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'max_applications_per_job', '1', 'integer', 'Maximum number of applications allowed per job per user', '2025-08-23 14:57:53', '2025-08-23 14:57:53'),
(2, 'payment_gateway_enabled', 'false', 'boolean', 'Enable or disable payment gateway integration', '2025-08-23 14:57:53', '2025-08-23 14:57:53'),
(3, 'default_application_fee', '8.00', 'string', 'Default application fee in MYR', '2025-08-23 14:57:53', '2025-08-23 14:57:53'),
(4, 'email_notifications_enabled', 'true', 'boolean', 'Enable email notifications for applications', '2025-08-23 14:57:53', '2025-08-23 14:57:53'),
(5, 'max_file_upload_size_mb', '10', 'integer', 'Maximum file upload size in MB', '2025-08-23 14:57:53', '2025-08-23 14:57:53');

-- --------------------------------------------------------

--
-- Table structure for table `application_spm_additional_subjects`
--

CREATE TABLE `application_spm_additional_subjects` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` int DEFAULT NULL,
  `tahun` year NOT NULL,
  `angka_giliran` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subjek` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gred` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `salinan_sijil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_spm_additional_subjects`
--

INSERT INTO `application_spm_additional_subjects` (`id`, `application_reference`, `application_id`, `tahun`, `angka_giliran`, `subjek`, `gred`, `salinan_sijil`, `created_at`) VALUES
(354, 'APP-2025-8725-1368C26E', 63, '1999', 'BH022A033', 'SAINS', 'B', NULL, '2025-11-16 11:54:28'),
(355, 'APP-2025-8725-1368C26E', 63, '1999', 'BH022A033', 'SEJARAN', 'C', NULL, '2025-11-16 11:54:28'),
(356, 'APP-2025-8725-1368C26E', 63, '1999', 'BH022A033', 'SENI', 'D', NULL, '2025-11-16 11:54:28'),
(357, 'APP-2025-8725-1368C26E', 63, '1999', 'BH022A033', 'PERDAGANGAN', 'D', NULL, '2025-11-16 11:54:28'),
(361, 'APP-2025-8235-D5224E2D', 62, '1997', 'BG010-004', 'MATEMATIK', 'C', NULL, '2025-11-16 14:18:53'),
(362, 'APP-2025-8235-D5224E2D', 62, '1997', 'BG010-004', 'SEJARAH', 'C', NULL, '2025-11-16 14:18:53'),
(363, 'APP-2025-8235-D5224E2D', 62, '1997', 'BG010-004', 'KEMAHIRAN HIDUP', 'B', NULL, '2025-11-16 14:18:53'),
(381, 'APP-2025-8472-D95DC7B0', 68, '1988', '123456789012', 'PERCUBAAN', 'C', NULL, '2025-11-23 22:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `application_spm_results`
--

CREATE TABLE `application_spm_results` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` bigint UNSIGNED DEFAULT NULL,
  `tahun` int DEFAULT NULL,
  `gred_keseluruhan` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `angka_giliran` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bahasa_malaysia` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bahasa_inggeris` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matematik` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sejarah` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subjek_lain` text COLLATE utf8mb4_unicode_ci,
  `gred_subjek_lain` text COLLATE utf8mb4_unicode_ci,
  `salinan_sijil_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_spm_results`
--

INSERT INTO `application_spm_results` (`id`, `application_reference`, `application_id`, `tahun`, `gred_keseluruhan`, `angka_giliran`, `bahasa_malaysia`, `bahasa_inggeris`, `matematik`, `sejarah`, `subjek_lain`, `gred_subjek_lain`, `salinan_sijil_filename`) VALUES
(81, 'APP-2025-8725-1368C26E', 63, 1999, '3A2B1C2D', 'BH022A033', 'A', 'A', 'A', 'B', 'SAINS,SEJARAN,SENI,PERDAGANGAN', 'B,C,D,D', 'uploads/applications/2025/APP-2025-8725-1368C26E/spm_salinan_sijil_1763294068_6919bb74c0765.jpg'),
(82, 'APP-2025-6855-0D3CA457', 64, 2022, '3A, 5B', 'BG010-004', 'A', 'B', 'A', 'A', NULL, NULL, 'uploads/applications/2025/APP-2025-6855-0D3CA457/spm_salinan_sijil_1763296778_6919c60a719ed.jpg'),
(84, 'APP-2025-8235-D5224E2D', 62, 1997, '3A, 5B', 'BG010-004', 'B', 'B', 'C', 'B', 'MATEMATIK,SEJARAH,KEMAHIRAN HIDUP', 'C,C,B', 'uploads/applications/2025/APP-2025-8235-D5224E2D/spm_salinan_sijil_1763279520_691982a01d9d5.jpg'),
(105, 'APP-2025-8472-D95DC7B0', 68, 1988, '5A 2B', '123456789012', 'C', 'C', 'S', NULL, 'PERCUBAAN', 'C', 'uploads/applications/2025/APP-2025-8472-D95DC7B0/spm_salinan_sijil_1763863081_69226a2966fdc.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `application_spm_subjects`
--

CREATE TABLE `application_spm_subjects` (
  `id` int NOT NULL,
  `application_id` bigint UNSIGNED NOT NULL,
  `mata_pelajaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gred` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_statuses`
--

CREATE TABLE `application_statuses` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_template_subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_template_body` mediumtext COLLATE utf8mb4_unicode_ci,
  `email_template_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_statuses`
--

INSERT INTO `application_statuses` (`id`, `code`, `name`, `email_template_subject`, `email_template_body`, `email_template_enabled`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'PENDING', 'Permohonan Diterima', 'Makluman Status Permohonan: {STATUS_NAME} ({STATUS_CODE})', '\r\n<div class=\"container\">\r\n  <p>YBhg. {APPLICANT_NAME},</p>\r\n  <p>Tuan/Puan,</p>\r\n  <p>Permohonan tuan/puan bagi jawatan <strong>{JOB_TITLE} ({KOD_GRED})</strong> dengan nombor rujukan \r\n  <strong>{APPLICATION_REFERENCE}</strong> telah diterima dan direkodkan dalam sistem kami.</p>\r\n\r\n  <div class=\"status-box\">\r\n    <strong>Status Terkini:</strong><br>\r\n    {STATUS_NAME} ({STATUS_CODE})\r\n  </div>\r\n\r\n  <p>{NOTES}</p>\r\n  <p>Untuk menyemak perkembangan terkini permohonan, sila layari pautan berikut:</p>\r\n  <p><a href=\"{BASE_URL}\">{BASE_URL}</a></p>\r\n\r\n  <p class=\"footer\">Sekian, terima kasih.<br>Unit Pengurusan Sumber Manusia</p>\r\n</div>', 1, 'Permohonan anda telah dihantar dan direkodkan dalam sistem.', 10, 1, '2025-11-17 16:27:18', '2025-11-17 22:26:18'),
(2, 'SCREENING', 'Sedang Ditapis', 'Makluman Status Permohonan: {STATUS_NAME} ({STATUS_CODE})', '\r\n<div class=\"container\">\r\n  <p>YBhg. {APPLICANT_NAME},</p>\r\n  <p>Tuan/Puan,</p>\r\n  <p>Permohonan tuan/puan bagi jawatan <strong>{JOB_TITLE} ({KOD_GRED})</strong> sedang menjalani proses tapisan awal dan semakan kelayakan.</p>\r\n\r\n  <div class=\"status-box\">\r\n    <strong>Status Terkini:</strong><br>\r\n    {STATUS_NAME} ({STATUS_CODE})\r\n  </div>\r\n\r\n  <p>{NOTES}</p>\r\n  <p>Sila rujuk pautan di bawah untuk semakan berkala:</p>\r\n  <p><a href=\"{BASE_URL}\">{BASE_URL}</a></p>\r\n\r\n  <p class=\"footer\">Sekian, terima kasih.<br>Unit Pengurusan Sumber Manusia</p>\r\n</div>', 1, 'Permohonan anda sedang disemak untuk kelayakan dan tapisan awal.', 20, 1, '2025-11-17 16:27:18', '2025-11-17 22:26:18'),
(3, 'TEST_INTERVIEW', 'Dipanggil Ujian / Temu Duga', 'Makluman Status Permohonan: {STATUS_NAME} ({STATUS_CODE})', '\r\n<div class=\"container\">\r\n  <p>YBhg. {APPLICANT_NAME},</p>\r\n  <p>Tuan/Puan,</p>\r\n\r\n  <p>Tahniah, permohonan tuan/puan bagi jawatan <strong>{JOB_TITLE} ({KOD_GRED})</strong> \r\n  telah berjaya melepasi tapisan awal dan tuan/puan dijemput untuk menghadiri \r\n  <strong>ujian/temu duga</strong> seperti yang dijadualkan.</p>\r\n\r\n  <div class=\"status-box\">\r\n    <strong>Status Terkini:</strong><br>\r\n    {STATUS_NAME} ({STATUS_CODE})\r\n  </div>\r\n\r\n  <p>{NOTES}</p>\r\n  <p>Maklumat lanjut boleh disemak melalui pautan berikut:</p>\r\n  <p><a href=\"{BASE_URL}\">{BASE_URL}</a></p>\r\n\r\n  <p class=\"footer\">Sekian, terima kasih.<br>Unit Pengurusan Sumber Manusia</p>\r\n</div>', 1, 'Anda telah terpilih ke peringkat seterusnya dan dijadualkan untuk ujian/temu duga.', 30, 1, '2025-11-17 16:27:18', '2025-11-17 22:26:18'),
(4, 'AWAITING_RESULT', 'Menunggu Keputusan', 'Makluman Status Permohonan: {STATUS_NAME} ({STATUS_CODE})', '\r\n<div class=\"container\">\r\n  <p>YBhg. {APPLICANT_NAME},</p>\r\n  <p>Tuan/Puan,</p>\r\n\r\n  <p>Permohonan tuan/puan bagi jawatan <strong>{JOB_TITLE} ({KOD_GRED})</strong> \r\n  kini sedang melalui proses penilaian akhir. Keputusan rasmi akan dimaklumkan \r\n  sebaik sahaja ia dikeluarkan.</p>\r\n\r\n  <div class=\"status-box\">\r\n    <strong>Status Terkini:</strong><br>\r\n    {STATUS_NAME} ({STATUS_CODE})\r\n  </div>\r\n\r\n  <p>{NOTES}</p>\r\n  <p>Semakan boleh dibuat dari semasa ke semasa melalui pautan berikut:</p>\r\n  <p><a href=\"{BASE_URL}\">{BASE_URL}</a></p>\r\n\r\n  <p class=\"footer\">Sekian, terima kasih.<br>Unit Pengurusan Sumber Manusia</p>\r\n</div>', 1, 'Permohonan anda telah diterima dan dinilai. Kami sedang menunggu keputusan akhir. Anda akan dimaklumkan sebaik sahaja keputusan dikeluarkan.', 40, 1, '2025-11-17 16:27:18', '2025-11-17 22:26:18'),
(5, 'PASSED_INTERVIEW', 'Lulus Temu Duga', 'Makluman Status Permohonan: {STATUS_NAME} ({STATUS_CODE})', '\r\n<div class=\"container\">\r\n  <p>YBhg. {APPLICANT_NAME},</p>\r\n  <p>Tuan/Puan,</p>\r\n\r\n  <p>Tahniah, tuan/puan telah <strong>lulus temu duga</strong> bagi jawatan \r\n  <strong>{JOB_TITLE} ({KOD_GRED})</strong>. Permohonan tuan/puan kini dalam proses \r\n  semakan akhir sebelum tawaran pelantikan dikeluarkan.</p>\r\n\r\n  <div class=\"status-box\">\r\n    <strong>Status Terkini:</strong><br>\r\n    {STATUS_NAME} ({STATUS_CODE})\r\n  </div>\r\n\r\n  <p>{NOTES}</p>\r\n  <p>Untuk maklumat lanjut, sila layari pautan berikut:</p>\r\n  <p><a href=\"{BASE_URL}\">{BASE_URL}</a></p>\r\n\r\n  <p class=\"footer\">Sekian, terima kasih.<br>Unit Pengurusan Sumber Manusia</p>\r\n</div>', 1, 'Permohonan anda kini berada dalam proses semakan akhir sebelum tawaran pelantikan dikeluarkan.', 50, 1, '2025-11-17 16:27:18', '2025-11-17 22:26:18'),
(6, 'OFFER_APPOINTMENT', 'Tawaran Pelantikan', 'Makluman Status Permohonan: {STATUS_NAME} ({STATUS_CODE})', '\r\n<div class=\"container\">\r\n  <p>YBhg. {APPLICANT_NAME},</p>\r\n  <p>Tuan/Puan,</p>\r\n\r\n  <p>Dengan sukacitanya dimaklumkan bahawa tuan/puan telah berjaya dan sedang \r\n  dipertimbangkan untuk <strong>tawaran pelantikan</strong> bagi jawatan \r\n  <strong>{JOB_TITLE} ({KOD_GRED})</strong>. Dokumen tawaran rasmi sedang diproses.</p>\r\n\r\n  <div class=\"status-box\">\r\n    <strong>Status Terkini:</strong><br>\r\n    {STATUS_NAME} ({STATUS_CODE})\r\n  </div>\r\n\r\n  <p>{NOTES}</p>\r\n  <p>Pautan rujukan:</p>\r\n  <p><a href=\"{BASE_URL}\">{BASE_URL}</a></p>\r\n\r\n  <p class=\"footer\">Sekian, terima kasih.<br>Unit Pengurusan Sumber Manusia</p>\r\n</div>', 1, 'Permohonan anda telah berjaya dan pihak jabatan sedang memproses surat atau dokumen tawaran pelantikan. Anda akan menerima makluman rasmi sebaik sahaja proses ini selesai.', 60, 1, '2025-11-17 16:27:18', '2025-11-17 22:26:18'),
(7, 'APPOINTED', 'Dilantik', 'Makluman Status Permohonan: {STATUS_NAME} ({STATUS_CODE})', '\r\n<div class=\"container\">\r\n  <p>YBhg. {APPLICANT_NAME},</p>\r\n  <p>Tuan/Puan,</p>\r\n\r\n  <p>Setinggi-tinggi tahniah diucapkan. Tuan/puan telah <strong>disahkan dilantik</strong> \r\n  ke jawatan <strong>{JOB_TITLE} ({KOD_GRED})</strong>. Selamat menjalankan tugas \r\n  dan semoga berjaya dalam perkhidmatan.</p>\r\n\r\n  <div class=\"status-box\">\r\n    <strong>Status Terkini:</strong><br>\r\n    {STATUS_NAME} ({STATUS_CODE})\r\n  </div>\r\n\r\n  <p>{NOTES}</p>\r\n  <p>Untuk rujukan lanjut, sila layari:</p>\r\n  <p><a href=\"{BASE_URL}\">{BASE_URL}</a></p>\r\n\r\n  <p class=\"footer\">Sekian, terima kasih.<br>Unit Pengurusan Sumber Manusia</p>\r\n</div>', 1, 'Pelantikan disahkan', 70, 1, '2025-11-17 16:27:18', '2025-11-17 22:26:18');

-- --------------------------------------------------------

--
-- Table structure for table `application_status_history`
--

CREATE TABLE `application_status_history` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_id` tinyint(1) NOT NULL,
  `status_description` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_status_history`
--

INSERT INTO `application_status_history` (`id`, `application_reference`, `status_id`, `status_description`, `changed_by`, `changed_at`, `notes`) VALUES
(1, 'APP-2025-6855-0D3CA457', 2, 'Sedang Ditapis', 1, '2025-11-17 16:36:37', NULL),
(2, 'APP-2025-6855-0D3CA457', 1, 'Permohonan Diterima', 1, '2025-11-17 16:43:21', NULL),
(3, 'APP-2025-6855-0D3CA457', 2, 'Sedang Ditapis', 1, '2025-11-17 16:46:15', NULL),
(4, 'APP-2025-6855-0D3CA457', 4, 'Menunggu Keputusan', 1, '2025-11-17 16:48:40', NULL),
(5, 'APP-2025-8235-D5224E2D', 4, 'Menunggu Keputusan', 1, '2025-11-17 16:48:40', NULL),
(6, 'APP-2025-6855-0D3CA457', 5, 'Lulus Temu Duga', 1, '2025-11-17 16:50:09', ''),
(7, 'APP-2025-8235-D5224E2D', 5, 'Lulus Temu Duga', 1, '2025-11-17 16:50:09', ''),
(8, 'APP-2025-6855-0D3CA457', 1, 'Permohonan Diterima', 1, '2025-11-17 16:57:31', 'perubahan maklumat'),
(9, 'APP-2025-8235-D5224E2D', 1, 'Permohonan Diterima', 1, '2025-11-17 16:57:31', 'perubahan maklumat'),
(10, 'APP-2025-6855-0D3CA457', 2, 'Sedang Ditapis', 1, '2025-11-17 17:14:28', 'test'),
(11, 'APP-2025-6855-0D3CA457', 3, 'Dipanggil Ujian / Temu Duga', 1, '2025-11-17 17:19:49', ''),
(12, 'APP-2025-6855-0D3CA457', 4, 'Menunggu Keputusan', 1, '2025-11-17 17:32:28', 'tungu keputusan'),
(13, 'APP-2025-8472-D95DC7B0', 2, 'Sedang Ditapis', 1, '2025-11-24 11:55:51', 'Semakkan selanjutnya');

-- --------------------------------------------------------

--
-- Table structure for table `application_work_experience`
--

CREATE TABLE `application_work_experience` (
  `id` int NOT NULL,
  `application_reference` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_syarikat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mula_berkhidmat` date DEFAULT NULL,
  `tamat_berkhidmat` date DEFAULT NULL,
  `jawatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_bahagian` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gred` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gaji` decimal(10,2) DEFAULT NULL,
  `taraf_jawatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bidang_tugas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `alasan_berhenti` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `application_id` bigint UNSIGNED NOT NULL,
  `dari_bulan` int DEFAULT NULL,
  `dari_tahun` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hingga_bulan` int DEFAULT NULL,
  `hingga_tahun` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_work_experience`
--

INSERT INTO `application_work_experience` (`id`, `application_reference`, `nama_syarikat`, `mula_berkhidmat`, `tamat_berkhidmat`, `jawatan`, `unit_bahagian`, `gred`, `gaji`, `taraf_jawatan`, `bidang_tugas`, `alasan_berhenti`, `created_at`, `application_id`, `dari_bulan`, `dari_tahun`, `hingga_bulan`, `hingga_tahun`) VALUES
(56, 'APP-2025-8725-1368C26E', 'KFC KKB', '2019-02-01', '2020-02-01', 'CASHIER', 'PEMBANTU KEDAI', '-', 1500.00, 'TETAP', '- MEMBANTU PELANGAN UNTUK AMBIL ORDER\r\n- MEMBERSIHKAN RUANGAN RESTORAN', 'TAWARAN KERJA LAIN', '2025-11-16 11:54:28', 63, NULL, NULL, NULL, NULL),
(57, 'APP-2025-8725-1368C26E', 'WAN TWO 3', '2021-06-01', '2023-06-01', 'KERANI', 'HUMAN RESOURCE', 'M12', 2300.00, 'KONTRAK', 'MEMBANTU PENGURUSAN SYARIKAT UNTUK MEMBUAT ORDER', 'OFFER BETTER', '2025-11-16 11:54:28', 63, NULL, NULL, NULL, NULL),
(58, 'APP-2025-6855-0D3CA457', 'MACROTREND SDN BHD', '2025-12-01', '2025-10-01', 'TEKNIKAL SUPPORT', 'PENBANTU TEKNIKAL', '-', NULL, NULL, NULL, NULL, '2025-11-16 12:39:38', 64, NULL, NULL, NULL, NULL),
(61, 'APP-2025-8235-D5224E2D', 'MACROTREND SDN BHD', '2025-01-01', '2025-02-01', 'TEKNIKAL SUPPORT', 'PENBANTU TEKNIKAL', NULL, 1200.00, NULL, 'AS ASDA DASD ASDASD ASDA ASD ASDASDAS DADADD\r\nASASDASD ASDADA', 'SAJE JEE', '2025-11-16 14:18:53', 62, NULL, NULL, NULL, NULL),
(62, 'APP-2025-8235-D5224E2D', 'SCOPE INTERNATIONAL', '2025-01-01', '2025-04-01', 'HR RESOURCES', 'KMFSDFKDSFSSDFSDF', NULL, 5000.00, NULL, 'KAJD[ASKJASLKDJ\r\nASKDJNAKSDJAS\r\nAKSDJKAJDAJD\r\nA\r\nS;LDA;LDKA;LKDAS\r\nAS;LDKA;LDKA;LDK', 'MASIH BEKERJA', '2025-11-16 14:18:53', 62, NULL, NULL, NULL, NULL),
(79, 'APP-2025-8472-D95DC7B0', 'PERCUBAAN', '2021-03-01', '2022-06-01', 'PERCUBAAN', 'PERCUBAAN', NULL, 5000.00, NULL, 'GOOD! THE CLONING SCRIPT ALREADY HANDLES SELECT ELEMENTS (LINE 757), SO IT SHOULD WORK PROPERLY. NOW LET ME UPDATE THE CHANGELOG:\r\nGOOD! THE CLONING SCRIPT ALREADY HANDLES SELECT ELEMENTS (LINE 757), SO IT SHOULD WORK PROPERLY. NOW LET ME UPDATE THE CHANGELOG:\r\n\r\nGOOD! THE CLONING SCRIPT ALREADY HANDLES SELECT ELEMENTS (LINE 757), SO IT SHOULD WORK PROPERLY. NOW LET ME UPDATE THE CHANGELOG:\r\n\r\nGOOD! THE CLONING SCRIPT ALREADY HANDLES SELECT ELEMENTS (LINE 757), SO IT SHOULD WORK PROPERLY. NOW LET ME UPDATE THE CHANGELOG:\r\nGOOD! THE CLONING SCRIPT ALREADY HANDLES SELECT ELEMENTS (LINE 757), SO IT SHOULD WORK PROPERLY. NOW LET ME UPDATE THE CHANGELOG:\r\n\r\nGOOD! THE CLONING SCRIPT ALREADY HANDLES SELECT ELEMENTS (LINE 757), SO IT SHOULD WORK PROPERLY. NOW LET ME UPDATE THE CHANGELOG:GOOD! THE CLONING SCRIPT ALREADY HANDLES SELECT ELEMENTS (LINE 757), SO IT SHOULD WORK PROPERLY. NOW LET ME UPDATE THE CHANGELOG:', 'GOOD! THE CLONING SCRIPT ALREADY HANDLES SELECT ELEMENTS (LINE 757), SO IT SHOULD WORK PROPERLY. NOW LET ME UPDATE THE CHANGELOG:', '2025-11-23 22:04:02', 68, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` int NOT NULL,
  `content_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`id`, `content_key`, `content_title`, `content_value`, `created_at`, `updated_at`) VALUES
(1, 'home_welcome', 'Welcome Message', '<h2>Welcome to MPHS</h2><p>Welcome to the official website of MPHS. We are committed to providing excellent service to our community.</p>', '2025-08-24 12:56:06', '2025-08-24 12:56:06'),
(2, 'about_us', 'About Us', '<h2>About MPHS</h2><p>MPHS is dedicated to serving the community with integrity and excellence.</p>', '2025-08-24 12:56:06', '2025-08-24 12:56:06'),
(3, 'contact_info', 'Contact Information', '<h2>Contact Us</h2><p>Email: info@mphs.gov.my<br>Phone: +60 12-345-6789<br>Address: MPHS Building, Main Street, City</p>', '2025-08-24 12:56:06', '2025-08-24 12:56:06');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int NOT NULL,
  `job_id` int NOT NULL,
  `application_reference` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `application_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status_id` int DEFAULT NULL,
  `status` enum('PENDING','SHORTLISTED','INTERVIEWED','OFFERED','ACCEPTED','REJECTED','PROCESSING') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `status_notes` text COLLATE utf8mb4_unicode_ci,
  `pengistiharan` tinyint(1) DEFAULT '1',
  `jawatan_dipohon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gambar_passport` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salinan_ic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salinan_surat_beranak` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_penuh` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombor_surat_beranak` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombor_ic` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agama` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taraf_perkahwinan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jantina` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarikh_lahir` date DEFAULT NULL,
  `umur` int DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `negeri_kelahiran` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bangsa` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warganegara` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempoh_bermastautin_selangor` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombor_telefon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_tetap` text COLLATE utf8mb4_unicode_ci,
  `bandar_tetap` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `negeri_tetap` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poskod_tetap` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_surat_sama` tinyint(1) DEFAULT '0',
  `alamat_surat` text COLLATE utf8mb4_unicode_ci,
  `bandar_surat` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `negeri_surat` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poskod_surat` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lesen_memandu` json DEFAULT NULL,
  `tarikh_tamat_lesen` date DEFAULT NULL,
  `ahli_keluarga` json DEFAULT NULL,
  `kemahiran_bahasa` json DEFAULT NULL,
  `kemahiran_komputer` json DEFAULT NULL,
  `maklumat_kegiatan_luar` json DEFAULT NULL,
  `darah_tinggi` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kencing_manis` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penyakit_buah_pinggang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penyakit_jantung` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batuk_kering_tibi` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kanser` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aids` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penagih_dadah` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penyakit_lain` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perokok` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `berat_kg` decimal(5,2) DEFAULT NULL,
  `tinggi_cm` decimal(5,2) DEFAULT NULL,
  `pemegang_kad_oku` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penyakit_lain_nyatakan` text COLLATE utf8mb4_unicode_ci,
  `memakai_cermin_mata` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_rabun` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_oku` json DEFAULT NULL,
  `kecacatan_anggota` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kecacatan_penglihatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kecacatan_pendengaran` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kanta` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maklumat_persekolahan` json DEFAULT NULL,
  `kelulusan_dimiliki` json DEFAULT NULL,
  `ada_pengalaman_kerja` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pengalaman_kerja` json DEFAULT NULL,
  `pekerja_perkhidmatan_awam` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerja_perkhidmatan_awam_nyatakan` text COLLATE utf8mb4_unicode_ci,
  `pertalian_kakitangan` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pertalian_kakitangan_nyatakan` text COLLATE utf8mb4_unicode_ci,
  `pernah_bekerja_mphs` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pernah_bekerja_mphs_nyatakan` text COLLATE utf8mb4_unicode_ci,
  `tindakan_tatatertib` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tindakan_tatatertib_nyatakan` text COLLATE utf8mb4_unicode_ci,
  `kesalahan_undangundang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kesalahan_undangundang_nyatakan` text COLLATE utf8mb4_unicode_ci,
  `muflis` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `muflis_nyatakan` text COLLATE utf8mb4_unicode_ci,
  `rujukan` json DEFAULT NULL,
  `nama_pasangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon_pasangan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bilangan_anak` int DEFAULT NULL,
  `status_pasangan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_pasangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_majikan_pasangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon_pejabat_pasangan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_majikan_pasangan` text COLLATE utf8mb4_unicode_ci,
  `poskod_majikan_pasangan` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bandar_majikan_pasangan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `negeri_majikan_pasangan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `submission_locked` tinyint(1) DEFAULT '0' COMMENT '1 = locked (cannot edit), 0 = editable',
  `salinan_lesen_memandu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gambar_passport_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salinan_ic_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salinan_surat_beranak_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salinan_lesen_memandu_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm_tahun` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm_gred_keseluruhan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm_angka_giliran` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm_bahasa_malaysia` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm_bahasa_inggeris` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm_matematik` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm_sejarah` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm_subjek_lain` json DEFAULT NULL,
  `spm_salinan_sijil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rujukan_1_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rujukan_1_telefon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rujukan_1_tempoh` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rujukan_2_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rujukan_2_telefon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rujukan_2_tempoh` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pengisytiharan_pengesahan` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_kakitangan_pertalian` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `id` int NOT NULL,
  `job_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `edaran_iklan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ad_date` date NOT NULL,
  `ad_close_date` date NOT NULL,
  `job_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kod_gred` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `salary_min` decimal(10,2) NOT NULL,
  `salary_max` decimal(10,2) NOT NULL,
  `requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(1) DEFAULT '1',
  `job_requirements` json DEFAULT NULL COMMENT 'Structured requirements for candidate filtering'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`id`, `job_code`, `edaran_iklan`, `ad_date`, `ad_close_date`, `job_title`, `kod_gred`, `salary_min`, `salary_max`, `requirements`, `created_at`, `updated_at`, `status`, `job_requirements`) VALUES
(1, 'JOB-000001', 'IKLAN TERBUKA', '2024-01-01', '2024-12-31', 'Pembantu Tadbir', 'N17', 1500.00, 2500.00, '<h4>Kelayakan Minimum:</h4><ul><li>Warganegara Malaysia</li><li>Berumur tidak kurang dari 18 tahun</li><li>Lulus SPM dengan sekurang-kurangnya 3 kepujian</li></ul>', '2025-08-23 14:57:53', '2025-08-24 11:23:29', 1, NULL),
(2, 'JOB-000002', 'DALAMAN SAHAJA (MPHS)', '2024-01-15', '2025-11-30', 'PENOLONG PEGAWAI KESIHATAN PERSEKITARAN', 'U29', 2200.00, 3500.00, '<h4>Kelayakan Minimum:</h4><ul><li>Warganegara Malaysia</li><li>Berumur tidak kurang dari 18 tahun</li><li>Lulus STPM atau Diploma</li></ul>', '2025-08-23 14:57:53', '2025-08-27 11:18:55', 1, NULL),
(3, 'JOB-000003', 'DALAMAN SAHAJA (MPHS)', '2024-06-29', '2025-12-31', 'PEMANDU KENDERAAN', 'R3', 1200.00, 1800.00, '<h4>Kelayakan Minimum:</h4><ul><li>Warganegara Malaysia</li><li>Berumur tidak kurang dari 21 tahun</li><li>Lesen Memandu yang sah</li></ul>', '2025-08-23 14:57:53', '2025-11-24 14:33:38', 1, '{\"bangsa\": \"\", \"gender\": \"\", \"license\": [\"B\", \"B2\", \"D\"], \"birth_state\": \"\", \"nationality\": \"Warganegara Malaysia\", \"min_education\": \"\", \"min_selangor_years\": \"\"}'),
(10, 'EJMPHS20251021001825', 'DALAMAN SAHAJA (MPHS)', '2025-10-21', '2025-12-05', 'PEGAWAI PENGUATUASA JALAN', 'N22', 4400.00, 5500.00, '<p>test jawatan yagn di sanding pada sistem</p><p>kini system berjaya</p><ul><li>masukkan maklumat </li><li>perengan ke 2 telah dimasukkan</li></ul>', '2025-10-20 16:18:25', '2025-11-24 12:08:52', 1, '{\"bangsa\": \"\", \"gender\": \"\", \"license\": [\"B2\", \"D\"], \"birth_state\": \"Wilayah Persekutuan Kuala Lumpur\", \"nationality\": \"Warganegara Malaysia\", \"min_education\": \"STPM\", \"min_selangor_years\": \"\"}');

-- --------------------------------------------------------

--
-- Table structure for table `page_content`
--

CREATE TABLE `page_content` (
  `id` int NOT NULL,
  `content_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `page_content`
--

INSERT INTO `page_content` (`id`, `content_key`, `content_title`, `content_value`, `created_at`, `updated_at`) VALUES
(1, 'pengistiharan_terms', 'Pengistiharan Permohonan Jawatan', '<ol><li>Saya akan memilih opsyen <strong>“Tidak Berkenaan”</strong> bagi mana-mana ruangan yang tidak berkaitan.</li><li>Saya akan memuat naik semua dokumen yang diperlukan dalam format <strong>PDF atau JPEG</strong> (saiz maksimum 2MB setiap fail), termasuk Kad Pengenalan, sijil akademik, transkrip/ijazah/diploma/sijil, surat kelulusan KPSL (jika ada), rekod perkhidmatan (jika berkaitan) serta dokumen lain yang relevan.</li><li>Sekiranya saya sedang berkhidmat dengan <strong>Majlis Perbandaran Hulu Selangor</strong> atau mana-mana agensi kerajaan, saya akan menghantar permohonan ini melalui sistem dengan <strong>pengesahan Ketua Jabatan</strong>.</li><li>Saya faham bahawa permohonan yang tidak lengkap atau gagal memuat naik dokumen yang diperlukan akan <strong>ditolak secara automatik</strong>.</li><li>Saya hanya dibenarkan mengemukakan <strong>satu permohonan jawatan sahaja</strong>, dan saya bertanggungjawab memastikan Gred &amp; Nama Jawatan yang dipilih adalah betul.</li><li>Jika saya merupakan lepasan institusi swasta atau luar negara, saya akan memuat naik salinan <strong>surat pengiktirafan kelayakan</strong> daripada Jabatan Perkhidmatan Awam.</li><li>Saya faham bahawa semua permohonan ini diproses melalui sistem atas talian, dan sebarang makluman atau pengesahan akan dihantar ke <strong>e-mel berdaftar saya</strong>.</li></ol>', '2025-08-23 14:57:53', '2025-08-24 14:19:33'),
(2, 'application_instructions', 'Arahan Permohonan', '<ol><li>Baca iklan &amp; syarat kelayakan – Pastikan anda memenuhi semua syarat yang dinyatakan.</li><li>Sediakan dokumen dalam bentuk soft copy – contoh: Kad Pengenalan, sijil akademik, dan dokumen sokongan lain.</li><li>Isi borang permohonan online – Lengkapkan semua maklumat wajib dengan betul dan terkini.</li><li>Muat naik dokumen sokongan – Pastikan semua dokumen jelas dan dalam format yang diterima (contoh: PDF, JPEG, PNG).</li><li>Semak maklumat sebelum hantar – Pastikan tiada kesilapan ejaan atau maklumat yang tidak tepat.</li><li>Hantar permohonan – Klik butang <em>Hantar</em> dan simpan/cetak bukti penghantaran untuk rujukan.</li></ol><p><br></p><h3><strong>Notis Penting</strong></h3><ul><li>Setiap calon hanya boleh menghantar 1 permohonan sahaja.</li><li>Maklumat tidak benar atau dokumen palsu akan menyebabkan permohonan ditolak serta-merta.</li><li>Permohonan yang tidak lengkap atau lewat tidak akan dipertimbangkan.</li><li>Pastikan semua maklumat adalah tepat dan dokumen yang dimuat naik adalah jelas.</li></ul><p><br></p>', '2025-08-23 14:57:53', '2025-08-24 14:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int NOT NULL,
  `job_id` int NOT NULL,
  `payment_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bill_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toyyibpay_bill_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applicant_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `applicant_nric` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `applicant_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `applicant_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','expired','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `callback_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `transaction_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_id` int DEFAULT '0' COMMENT '-1=Failed, 0=Pending, 1=Paid/Success',
  `toyyibpay_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'logo', 'uploads/default-logo.png', '2025-08-23 12:55:38'),
(2, 'background', 'uploads/default-bg.jpg', '2025-08-23 12:55:38'),
(3, 'favicon', 'uploads/favicon.ico', '2025-08-23 12:55:38'),
(4, 'copyright', 'Majlis Perbandaran Hulu Selangor', '2025-08-23 12:55:38'),
(5, 'application_name', 'eJawatan', '2025-08-23 12:55:38'),
(6, 'system_name', 'eJawatan', '2025-08-23 12:55:38'),
(7, 'maintenance_mode', '0', '2025-08-23 12:55:38'),
(8, 'max_upload_size', '10MB', '2025-08-23 12:55:38'),
(9, 'allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx', '2025-08-23 12:55:38'),
(10, 'session_timeout', '7200', '2025-08-23 12:55:38'),
(11, 'max_login_attempts', '5', '2025-08-23 12:55:38'),
(12, 'lockout_duration', '900', '2025-08-23 12:55:38');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','superadmin','editor','viewer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `status` tinyint(1) DEFAULT '1',
  `reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `reset_token`, `reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@ejawatan.local', '$2y$10$cGz8uVr84MD3ZNR5/QygaOo8SxeAZ1rlKTS5pE6BQcteKQCYljhqa', 'System Administrator', 'admin', 1, NULL, NULL, '2025-08-23 12:55:38', '2025-08-23 12:59:18'),
(2, 'nefizon', 'nefizon@gmail.com', '$2y$10$mU4EFy8m1pJYG/9OD8AExOq/AQWAZEHpcQF01GwaIWUsViAR35lQu', 'nefizon', 'admin', 1, NULL, NULL, '2025-08-24 09:32:05', '2025-08-24 09:32:05'),
(4, 'nefi', 'nefizon@outlook.com', '$2y$10$cgfDHdktG7joYkBQ9BzkuOqNkf2uNA9ynN2hbrJJVi5fs6nkdLOKK', 'Nefi Outlook', 'superadmin', 1, NULL, NULL, '2025-08-29 00:43:26', '2025-08-29 00:43:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Session token/ID',
  `user_id` int DEFAULT NULL COMMENT 'User ID if authenticated user',
  `application_id` int DEFAULT NULL COMMENT 'Application ID for edit sessions',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Client IP address',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT 'Client user agent string',
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last activity timestamp',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Session creation timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User sessions for authentication and edit tokens';

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `application_id`, `ip_address`, `user_agent`, `last_activity`, `created_at`) VALUES
('09f70a0e3cac2eb70227f708cc5fb86fab2bb131e128892af79e4d862ca2e97f', NULL, 63, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 13:59:32', '2025-11-16 13:59:32'),
('0e802fc3f5b52992c663ceb1b8fbdfc6ca7de4f64b39a3535342004bbf2b4f9c', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:54:48', '2025-11-15 12:54:48'),
('11358ec7e5f8bbfbd5f516ee001409d122ec4e229b9acf495a5f58e2f4b1dab0', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 05:38:57', '2025-11-16 05:38:57'),
('14147d3f85eff028c4c3110b746332ffe692ba231e7a92cc1938e4066d2cc4c5', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:49:25', '2025-11-15 12:49:25'),
('153a0cd5c8aeee130d00d792b3baf0c7d26940c2927e619e556c4325fcf78f03', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 01:51:05', '2025-11-15 01:51:05'),
('181b4bc10f3d701bc1cb315ee8c60c6e13d404a3b765fb8cbd03bc74f50d5622', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:49:40', '2025-11-15 12:49:40'),
('18f6133d7aecad8753646330b28208e1586085ccd969a2092d14ccadca888e82', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 05:56:54', '2025-11-16 05:56:54'),
('1ab867b44ec8e33a393788d81f38378fbc3d755d5d341f12980d236fa909644d', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 02:14:55', '2025-11-23 02:14:55'),
('1b212d1decc0fd133ac58b6917e02d4ca9fb5d38ca00945fd93dab31bfae821a', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 14:48:29', '2025-11-23 14:48:29'),
('1b97bd5177ef7e100e1805ebd8a0d884e35ed9a085cb6b2a3d455ef8ab3b9ec4', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 21:51:48', '2025-11-23 21:51:48'),
('1d9e1c1e30d356621952962917ae9b62b8454f92bd9b002af40e04c082b779f6', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 07:48:29', '2025-11-16 07:48:29'),
('288516bdaf34c0e52124146f50ce60e2fbe1c5a5d190b3b34baf082b7bbce2f2', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 14:48:39', '2025-11-23 14:48:39'),
('2e16bd746426c41ee78eaf6e01f029daf2171801964b9ba79068113414b80166', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 15:48:01', '2025-11-23 15:48:01'),
('2e1fd6ab59b2b9f4ab593edf40ff5dd619ae8c8a066190a5bd3b9d9b2d978192', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 07:14:31', '2025-11-23 07:14:31'),
('4820dc944c2199569775f0d44bc227cdd68b7f500115f6d956ba992670a8c5aa', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 02:09:53', '2025-11-23 02:09:53'),
('49920b26d2d237fd00c4164ace636dabc4566cce9660618a73f468220c667281', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:06:07', '2025-11-16 06:06:07'),
('4ba52e58d6c0458197d9d00fa941591898a6234fdb179c64f1b7940283df5daa', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 07:07:20', '2025-11-16 07:07:20'),
('4bebf770c1dea5d457a1f73e4505f86b0c1a593a86d67a0c1fe78ce10b60900a', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 08:33:14', '2025-11-16 08:33:14'),
('51d0d58b10f55d2fd4cffa52054a0b34b30220c0501920883f67d99aa286c44b', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:47:04', '2025-11-15 12:47:04'),
('57079c0b34267251f493b3bd56cde3a22136c766dc525f930b7581d9a75e86af', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:01:59', '2025-11-16 06:01:59'),
('57fad6715de3155d7131b594eb93aa936e6597c7e45dbc754e32327e3b7edd1c', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 03:38:58', '2025-11-15 03:38:58'),
('58f87f4e604082da3dcb072426bec0093e56112df3009932fa49bde0041801f4', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 01:18:21', '2025-11-16 01:18:21'),
('59194a42e0117598fe6bb9bc8119d1d986c532c4af96cd23e339ad2dcbecd2f4', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:55:52', '2025-11-15 12:55:52'),
('5975927a82e92a6fbb72e24acd162b7a4ace83116855c0cd7481cedcdc432f37', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 07:39:37', '2025-11-16 07:39:37'),
('59eed94461aa85d44d26720d5d5465ef85591ec9f660d1472ac6ca4a08ea6bfc', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:42:44', '2025-11-16 06:42:44'),
('5bd076aafd96b54f7a57d9380e3b410e5f79208dae88d1c7a75befa76739c8c0', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:48:57', '2025-11-15 12:48:57'),
('5d4decbdf0ca81e7ec4091fe9c67c73306d54bf9fc660e909bd85e1d9c0f5631', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 21:53:33', '2025-11-23 21:53:33'),
('5ec9b182bc8daba885ff8ceb9e1f979233721b6564ab07eb8c78e80b5e6cea61', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 14:03:17', '2025-11-16 14:03:17'),
('67275fcd410878eab2115756b7a59633595ae83f7e5f58151a33a67628c2aba5', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 01:00:45', '2025-11-15 01:00:45'),
('68cbbd127327b1342c2cd3b8e911370ad1c74f70757511ffac09ad39e2b028bc', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 07:49:59', '2025-11-23 07:49:59'),
('69adf1c0605c626baac71154fa717d40c0d25936663f09830409408fde9eaa8c', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:43:32', '2025-11-15 12:43:32'),
('69f3b22280bd916b555da84343ba093e8eff0994b0e90a94f3f4fe0e2aeb3367', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 02:26:33', '2025-11-23 02:26:33'),
('7d294bc7fc1116dbafc75d6167845db1b2f94a964d2ae970a0d7544b08846f01', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 04:39:49', '2025-11-23 04:39:49'),
('7e66d4266b8848ee8b7d679611ccbef7ab9af63084158d7355ab20445f6f161a', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 05:10:10', '2025-11-16 05:10:10'),
('81011f09960d4ea5bdf1d90b520c63cb4cb4d52a887184cc287400b7d17531ae', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 01:58:55', '2025-11-23 01:58:55'),
('8147e91b30ed639b3b54421fe0173b6fdee96cf48c83eb0084b61fbe92c64017', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 03:21:49', '2025-11-23 03:21:49'),
('815b717419fbb31415528ae2468cce37736b818ce8446f10974ee5bdf5b00bff', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 03:23:44', '2025-11-23 03:23:44'),
('89052d293fd1d68c90941a7e12817d5b610bd2eb8ee5ae2c89f15107e2333850', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:43:34', '2025-11-16 06:43:34'),
('8e65f759c16cd2756f6a09dbafcebf508c37f90476cfac36a988e69655e75463', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 05:41:45', '2025-11-16 05:41:45'),
('9401f0bc04cae4dfee5af355de9ba4882ddc3a78d3aa79d3190cc8ebc9cda94b', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 15:11:30', '2025-11-23 15:11:30'),
('945faf9cbf9fd4ba088908874133e4786436e7ba0ca842c3bc85ba4a385280eb', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 09:08:20', '2025-11-16 09:08:20'),
('94df52238e072453da62899ccaa0857519f5006696b66f0e4ca97f384b983517', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 11:41:12', '2025-11-15 11:41:12'),
('9b735ba092e38de2aca2ca6ceec5b8dc2bf2390a09179798325d21a92a119b3f', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:29:13', '2025-11-16 06:29:13'),
('a187c008d97d436129b5bd3e26f1c8ed88d52514ca4853378f4aad1a85f2abac', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 02:30:08', '2025-11-23 02:30:08'),
('a20f0b1e55fe2827f265e1edea113d2c7699fdddc3d3af38840139ba52c021d5', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 07:35:12', '2025-11-16 07:35:12'),
('a8e20857dda6c5841fda720bff2013933cb47284a8dea6398ee0a43965138347', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 01:21:08', '2025-11-16 01:21:08'),
('ae0fa201d83a1857e1aa363bd2459e294a5354a5b3f272c90d09220390ad9382', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 00:04:21', '2025-11-15 00:04:21'),
('b56c12c89927be7b6397524b8c018172179f6d801dfcfec4a8e32b19cd9bb796', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 06:00:42', '2025-11-16 06:00:42'),
('b7e973a7303039bd0bf7e9fc0e2a2659a87c53e3a61c508542325a7fef471023', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:31:19', '2025-11-16 04:31:19'),
('b8fb9c67cd63ba6db806adbde3743413890514820c47ed393f4c6fed96deead2', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:49:22', '2025-11-15 12:49:22'),
('bb77a623e707f2b017fe0f0d0a07dcd1b025c9146694f4728e32b9b866561c5a', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:51:36', '2025-11-16 04:51:36'),
('c4ced690d60f671aad4bde7f2a1981bbf2b81e819f7dd74ef00e4ced2d49bfe2', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 12:54:51', '2025-11-15 12:54:51'),
('c5bfecbe246a9964095555f3b5aec626e8ff30d7de20950473e455fe155b0d66', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 05:57:42', '2025-11-23 05:57:42'),
('dc63982e9853b72fa8983c9640a14addde6afe9731c8b62194f45287929fabb9', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 14:05:27', '2025-11-16 14:05:27'),
('de75faf3ced61c28f7fe0ceb34528adaba4abe449fb04d67938845c9f1fa65cf', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 00:47:56', '2025-11-16 00:47:56'),
('e31d7b12820e1ba35bcb523e9b80c71938da0db7ee2f05abb3f4d5a246205a76', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 15:35:33', '2025-11-23 15:35:33'),
('e4e8bb4ae1ecadf4f7b09084f7c3ae45ab9e41759985342264fe47132ba1274b', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 02:44:24', '2025-11-23 02:44:24'),
('e4f71f0552d458baccb24dde7ddc46022dd4b86d810504e430a9f3f9e322b9b9', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-15 00:08:09', '2025-11-15 00:08:09'),
('f17de1cb2778baaf6885a16feb23548e8f9fa810164ad6e0b45599398c10a68b', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 05:40:37', '2025-11-16 05:40:37'),
('f1ccabb7e6a9fbc4d7a65963d465bcd5be91844079ef8a2782956e32e7dcb96b', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 08:12:14', '2025-11-16 08:12:14'),
('f33af38e041c5a2631c85e1fbcc63bb1088462e8e5fe599e8938058e3b15830c', NULL, 68, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 21:57:16', '2025-11-23 21:57:16'),
('f7b466c6eda0bba4ae9aea7f88d0d870476e35a6f4c66ae08ac32f7c8501abec', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 08:32:32', '2025-11-16 08:32:32'),
('fbc1149d83bd40928af428da6cba5df3a75dd64c557cb40f72ebfcc10f705fd4', NULL, 62, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 08:39:52', '2025-11-16 08:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions_backup`
--

CREATE TABLE `user_sessions_backup` (
  `id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `application_id` int DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `session_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Store additional session data as JSON'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `application_application_main`
--
ALTER TABLE `application_application_main`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_reference` (`application_reference`),
  ADD UNIQUE KEY `idx_unique_app_reference` (`application_reference`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_payment_ref` (`payment_reference`),
  ADD KEY `idx_nama_penuh` (`nama_penuh`),
  ADD KEY `idx_nombor_ic` (`nombor_ic`),
  ADD KEY `idx_nombor_surat_beranak` (`nombor_surat_beranak`),
  ADD KEY `idx_application_application_main_status` (`status`),
  ADD KEY `idx_job_code` (`job_code`);

--
-- Indexes for table `application_computer_skills`
--
ALTER TABLE `application_computer_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_reference` (`application_reference`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `application_education`
--
ALTER TABLE `application_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_reference`),
  ADD KEY `idx_app_ref` (`application_reference`),
  ADD KEY `idx_app_id` (`application_id`);

--
-- Indexes for table `application_extracurricular`
--
ALTER TABLE `application_extracurricular`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_reference`),
  ADD KEY `idx_extra_app_id` (`application_id`);

--
-- Indexes for table `application_family_members`
--
ALTER TABLE `application_family_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_reference` (`application_reference`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `application_health`
--
ALTER TABLE `application_health`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_health_appref` (`application_reference`),
  ADD KEY `idx_health_app_id` (`application_id`);

--
-- Indexes for table `application_language_skills`
--
ALTER TABLE `application_language_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_reference` (`application_reference`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `application_matapelajaran_lain`
--
ALTER TABLE `application_matapelajaran_lain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_reference`),
  ADD KEY `idx_matapelajaran_lain_app_id` (`application_id`);

--
-- Indexes for table `application_notifications`
--
ALTER TABLE `application_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_app_notifications_app_id` (`application_reference`),
  ADD KEY `idx_notifications_app_id` (`application_id`);

--
-- Indexes for table `application_professional_bodies`
--
ALTER TABLE `application_professional_bodies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_reference`),
  ADD KEY `idx_professional_bodies_app_id` (`application_id`);

--
-- Indexes for table `application_references`
--
ALTER TABLE `application_references`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_reference`),
  ADD KEY `idx_references_app_id` (`application_id`);

--
-- Indexes for table `application_settings`
--
ALTER TABLE `application_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `application_spm_additional_subjects`
--
ALTER TABLE `application_spm_additional_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_reference`);

--
-- Indexes for table `application_spm_results`
--
ALTER TABLE `application_spm_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_reference`),
  ADD KEY `idx_spm_results_app_id` (`application_id`);

--
-- Indexes for table `application_spm_subjects`
--
ALTER TABLE `application_spm_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `application_statuses`
--
ALTER TABLE `application_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_application_statuses_code` (`code`),
  ADD UNIQUE KEY `uq_application_statuses_name` (`name`);

--
-- Indexes for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_reference`),
  ADD KEY `idx_status_id` (`status_id`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `application_work_experience`
--
ALTER TABLE `application_work_experience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_reference`),
  ADD KEY `idx_work_app_id` (`application_id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_key` (`content_key`),
  ADD KEY `content_key_2` (`content_key`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_job_ic` (`job_id`,`nombor_ic`),
  ADD UNIQUE KEY `application_reference` (`application_reference`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `application_date` (`application_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_job_postings_job_code` (`job_code`);

--
-- Indexes for table `page_content`
--
ALTER TABLE `page_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_key` (`content_key`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_reference` (`payment_reference`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_payment_reference` (`payment_reference`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_applicant_nric` (`applicant_nric`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status_id` (`status_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_application_id` (`application_id`),
  ADD KEY `idx_last_activity` (`last_activity`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `application_application_main`
--
ALTER TABLE `application_application_main`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `application_computer_skills`
--
ALTER TABLE `application_computer_skills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT for table `application_education`
--
ALTER TABLE `application_education`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `application_extracurricular`
--
ALTER TABLE `application_extracurricular`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `application_family_members`
--
ALTER TABLE `application_family_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `application_health`
--
ALTER TABLE `application_health`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `application_language_skills`
--
ALTER TABLE `application_language_skills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `application_matapelajaran_lain`
--
ALTER TABLE `application_matapelajaran_lain`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_notifications`
--
ALTER TABLE `application_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_professional_bodies`
--
ALTER TABLE `application_professional_bodies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `application_references`
--
ALTER TABLE `application_references`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `application_settings`
--
ALTER TABLE `application_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `application_spm_additional_subjects`
--
ALTER TABLE `application_spm_additional_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=382;

--
-- AUTO_INCREMENT for table `application_spm_results`
--
ALTER TABLE `application_spm_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `application_spm_subjects`
--
ALTER TABLE `application_spm_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_statuses`
--
ALTER TABLE `application_statuses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `application_status_history`
--
ALTER TABLE `application_status_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `application_work_experience`
--
ALTER TABLE `application_work_experience`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `page_content`
--
ALTER TABLE `page_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `application_application_main`
--
ALTER TABLE `application_application_main`
  ADD CONSTRAINT `fk_application_job` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_application_job_code` FOREIGN KEY (`job_code`) REFERENCES `job_postings` (`job_code`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_application_main_payment` FOREIGN KEY (`payment_reference`) REFERENCES `payment_transactions` (`payment_reference`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `application_computer_skills`
--
ALTER TABLE `application_computer_skills`
  ADD CONSTRAINT `fk_computer_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_education`
--
ALTER TABLE `application_education`
  ADD CONSTRAINT `fk_edu_app` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_education_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_extracurricular`
--
ALTER TABLE `application_extracurricular`
  ADD CONSTRAINT `fk_extra_app` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_extracurricular_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_family_members`
--
ALTER TABLE `application_family_members`
  ADD CONSTRAINT `fk_family_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_health`
--
ALTER TABLE `application_health`
  ADD CONSTRAINT `fk_health_app_main` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_health_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_language_skills`
--
ALTER TABLE `application_language_skills`
  ADD CONSTRAINT `fk_language_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_matapelajaran_lain`
--
ALTER TABLE `application_matapelajaran_lain`
  ADD CONSTRAINT `fk_matapelajaran_lain_app_id` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_matapelajaran_lain_app_main` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_matapelajaran_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_notifications`
--
ALTER TABLE `application_notifications`
  ADD CONSTRAINT `fk_notifications_app_id` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_app_main` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_professional_bodies`
--
ALTER TABLE `application_professional_bodies`
  ADD CONSTRAINT `fk_prof_app` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prof_bodies_app_main` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_professional_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_references`
--
ALTER TABLE `application_references`
  ADD CONSTRAINT `fk_references_app_id` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_references_app_main` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_references_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_spm_additional_subjects`
--
ALTER TABLE `application_spm_additional_subjects`
  ADD CONSTRAINT `fk_spm_additional_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_spm_results`
--
ALTER TABLE `application_spm_results`
  ADD CONSTRAINT `fk_spm_app` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spm_results_app_main` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spm_results_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_spm_subjects`
--
ALTER TABLE `application_spm_subjects`
  ADD CONSTRAINT `fk_spm_subjects_app_main` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD CONSTRAINT `fk_status_history_app_main` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;

--
-- Constraints for table `application_work_experience`
--
ALTER TABLE `application_work_experience`
  ADD CONSTRAINT `fk_work_app` FOREIGN KEY (`application_id`) REFERENCES `application_application_main` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_work_experience_ref` FOREIGN KEY (`application_reference`) REFERENCES `application_application_main` (`application_reference`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
