-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 16, 2026 at 02:46 PM
-- Server version: 10.6.25-MariaDB-log
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `readycr_readycrm`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`readycr`@`localhost` PROCEDURE `rsc_add_column_if_missing` (IN `p_table` VARCHAR(64), IN `p_column` VARCHAR(64), IN `p_def` LONGTEXT)   BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND COLUMN_NAME = p_column
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_def);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE DEFINER=`readycr`@`localhost` PROCEDURE `rsc_add_fk_if_missing` (IN `p_table` VARCHAR(64), IN `p_fk_name` VARCHAR(64), IN `p_sql` LONGTEXT)   BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND CONSTRAINT_NAME = p_fk_name
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    SET @sql = p_sql;
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE DEFINER=`readycr`@`localhost` PROCEDURE `rsc_add_index_if_missing` (IN `p_table` VARCHAR(64), IN `p_index` VARCHAR(64), IN `p_sql` LONGTEXT)   BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND INDEX_NAME = p_index
  ) THEN
    SET @sql = p_sql;
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE DEFINER=`readycr`@`localhost` PROCEDURE `rsc_add_pk_if_missing` (IN `p_table` VARCHAR(64), IN `p_pk_sql` LONGTEXT)   BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
  ) THEN
    SET @sql = p_pk_sql;
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE DEFINER=`readycr`@`localhost` PROCEDURE `rsc_drop_fk_if_exists` (IN `p_table` VARCHAR(64), IN `p_fk_name` VARCHAR(64))   BEGIN
  IF EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND CONSTRAINT_NAME = p_fk_name
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` DROP FOREIGN KEY `', p_fk_name, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

$$

$$

$$

$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(6, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.126.233.146', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-02-01 12:00:37'),
(7, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 10:00:34'),
(8, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 10:55:05'),
(9, 1, 'create_user', 'users', 5, '{\"name\":\"احسان ثابت مشمول\",\"email\":\"ehsanfast@gmail.com\",\"role\":\"manager\"}', NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 10:56:40'),
(10, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 11:00:08'),
(11, 1, 'logout', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 11:23:02'),
(12, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 11:28:48'),
(13, 1, 'update_email_settings', 'settings', NULL, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 12:54:17'),
(14, 1, 'update_settings', 'settings', NULL, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 12:54:45'),
(15, 1, 'database_backup', NULL, NULL, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 12:54:48'),
(16, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.125.237.44', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-03 13:05:37'),
(17, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.126.130.172', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 14:12:23'),
(18, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.126.130.172', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 15:28:54'),
(19, 1, 'login', 'users', 1, NULL, NULL, NULL, '104.28.225.119', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-02-05 00:34:10'),
(20, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.125.245.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:55:56'),
(21, 1, 'update_user', 'users', 1, '{\"name\":\"فاضل قائمی\",\"email\":\"ghaemipm@gmail.com\",\"role\":\"admin\"}', NULL, NULL, '5.125.245.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:58:13'),
(22, 1, 'login', 'users', 1, NULL, NULL, NULL, '45.155.195.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-06 14:36:37'),
(23, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:37:56'),
(24, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 14:19:30'),
(25, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 09:01:30'),
(26, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:145.0) Gecko/20100101 Firefox/145.0', '2026-02-09 09:06:46'),
(27, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 14:04:14'),
(28, 1, 'logout', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 14:16:59'),
(29, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:10:18'),
(30, 1, 'create_sale', 'sales', 4, '{\"sale_number\":\"S-20260210-2325\",\"customer_id\":1,\"final_amount\":5242000}', NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:11:33'),
(31, 1, 'create_task', 'tasks', 6, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:12:05'),
(32, 1, 'create_task', 'tasks', 7, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:12:20'),
(33, 1, 'create_task', 'tasks', 8, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:12:31'),
(34, 1, 'login', 'users', 1, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:32:54'),
(35, 1, 'login', 'users', 1, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 18:18:44'),
(36, NULL, 'auth', 'login', 0, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 18:18:44'),
(37, 1, 'update_chatbot_settings', 'settings', NULL, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 18:19:32'),
(38, 1, 'clear_logs', NULL, NULL, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 18:59:26'),
(39, 1, 'clear_logs', NULL, NULL, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 18:59:34'),
(40, 1, 'login', 'users', 1, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 19:36:35'),
(41, NULL, 'auth', 'login', 0, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 19:36:35'),
(42, 1, 'login', 'users', 1, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 21:16:01'),
(43, NULL, 'auth', 'login', 0, NULL, NULL, NULL, '195.201.148.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 21:16:01'),
(44, 1, 'login', 'users', 1, NULL, NULL, NULL, '104.28.225.116', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-02-11 21:25:32'),
(45, NULL, 'auth', 'login', 0, NULL, NULL, NULL, '104.28.225.116', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-02-11 21:25:32'),
(46, 1, 'login', 'users', 1, NULL, NULL, NULL, '37.120.213.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 11:21:50'),
(47, 1, 'login_failed', 'users', 1, '\"تلاش ناموفق برای ورود\"', NULL, NULL, '37.120.213.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 12:00:32'),
(48, 1, 'login', 'users', 1, '\"ورود موفقیت‌آمیز به سیستم\"', NULL, NULL, '37.120.213.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 12:03:13'),
(49, 1, 'login', 'users', 1, '\"ورود موفقیت‌آمیز به سیستم\"', NULL, NULL, '37.120.213.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 12:05:40'),
(50, 1, 'login', 'users', 1, NULL, NULL, NULL, '37.120.213.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 12:09:32'),
(51, 1, 'create_project', 'projects', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 14:45:26'),
(52, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.125.56.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-12 17:25:51'),
(53, 1, 'login', 'users', 1, NULL, NULL, NULL, '77.237.184.146', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 14:39:15'),
(54, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 08:26:06'),
(55, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.125.56.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-14 10:33:39'),
(56, 1, 'login', 'users', 1, NULL, NULL, NULL, '185.246.152.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 13:39:09'),
(57, 1, 'update_chatbot_settings', 'settings', NULL, NULL, NULL, NULL, '185.246.152.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 13:53:14'),
(58, 1, 'update_chatbot_settings', 'settings', NULL, NULL, NULL, NULL, '185.246.152.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 13:53:42'),
(59, 1, 'update_chatbot_settings', 'settings', NULL, NULL, NULL, NULL, '185.246.152.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 13:54:02'),
(60, 1, 'update_chatbot_settings', 'settings', NULL, NULL, NULL, NULL, '185.246.152.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 13:54:35'),
(61, 1, 'login', 'users', 1, NULL, NULL, NULL, '95.173.219.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 14:43:21'),
(62, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.125.56.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-14 15:37:25'),
(63, 1, 'login', 'users', 1, NULL, NULL, NULL, '206.217.143.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 20:13:40'),
(64, 1, 'login', 'users', 1, NULL, NULL, NULL, '206.217.143.133', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 20:31:54'),
(65, 1, 'login', 'users', 1, NULL, NULL, NULL, '104.28.241.110', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 23:04:09'),
(66, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.125.56.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-14 23:27:41'),
(67, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-15 06:02:33'),
(68, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-15 07:07:44'),
(69, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.126.126.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-15 14:37:15'),
(70, 1, 'login', 'users', 1, NULL, NULL, NULL, '5.126.126.165', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-15 16:15:20'),
(71, 1, 'login', 'users', 1, NULL, NULL, NULL, '2.180.39.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 11:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_conversations`
--

CREATE TABLE `chatbot_conversations` (
  `id` int(11) NOT NULL,
  `session_id` varchar(64) NOT NULL COMMENT 'شناسه یکتا session',
  `user_id` int(11) NOT NULL COMMENT 'شناسه کاربر',
  `message_number` int(11) DEFAULT 1 COMMENT 'شماره پیام در session',
  `role` enum('user','assistant','system') NOT NULL COMMENT 'نقش فرستنده',
  `content` text NOT NULL COMMENT 'محتوای پیام',
  `intent` varchar(50) DEFAULT NULL COMMENT 'تشخیص نیت کاربر',
  `context_data` longtext DEFAULT NULL COMMENT 'داده‌های Context استفاده شده (JSON)',
  `model_used` varchar(100) DEFAULT NULL COMMENT 'مدل مورد استفاده',
  `tokens_used` int(11) DEFAULT 0 COMMENT 'تعداد توکن مصرف شده',
  `response_time` decimal(6,3) DEFAULT NULL COMMENT 'زمان پاسخ (ثانیه)',
  `feedback` tinyint(1) DEFAULT NULL COMMENT 'بازخورد کاربر (1=مثبت, -1=منفی)',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تاریخچه مکالمات و پیام‌های چت‌بات';

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_index`
--

CREATE TABLE `chatbot_index` (
  `id` int(11) NOT NULL,
  `source_table` varchar(50) NOT NULL COMMENT 'جدول مبدا (customers, leads, ...)',
  `source_id` int(11) NOT NULL COMMENT 'شناسه رکورد در جدول مبدا',
  `content_type` varchar(50) NOT NULL COMMENT 'نوع محتوا (profile, activity, stats, ...)',
  `indexed_content` text NOT NULL COMMENT 'محتوای ایندکس شده',
  `metadata` longtext DEFAULT NULL COMMENT 'متادیتا (JSON)',
  `search_keywords` text DEFAULT NULL COMMENT 'کلمات کلیدی جستجو',
  `priority` tinyint(1) DEFAULT 5 COMMENT 'اولویت (1-10)',
  `is_active` tinyint(1) DEFAULT 1,
  `indexed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ایندکس داده‌های CRM برای RAG';

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_settings`
--

CREATE TABLE `chatbot_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0 COMMENT 'آیا مقدار رمزنگاری شده است',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تنظیمات سیستم AI Chatbot';

--
-- Dumping data for table `chatbot_settings`
--

INSERT INTO `chatbot_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_encrypted`, `created_at`, `updated_at`) VALUES
(1, 'chatbot_enabled', '1', 'boolean', 'فعال/غیرفعال بودن چت‌بات', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12'),
(2, 'sk-hbMtHNYeVjNbgMQp4IxH8ZgydqvKH8wjDCAFYLJze2I9VLXn', '', 'string', 'کلید API سرویس GapGPT', 1, '2026-02-11 18:34:12', '2026-02-14 13:53:38'),
(3, 'chatbot_model', 'deepseek-r1-671b', 'string', 'مدل پیش‌فرض AI', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12'),
(4, 'chatbot_temperature', '0', 'string', 'Temperature (0.0 - 1.0)', 0, '2026-02-11 18:34:12', '2026-02-14 13:54:13'),
(5, 'chatbot_max_tokens', '2048', 'integer', 'حداکثر توکن پاسخ', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12'),
(6, 'chatbot_system_prompt', 'شما یک دستیار هوشمند CRM هستید که به فارسی پاسخ می‌دهید. وظیفه شما کمک به کاربران در تحلیل داده‌ها، گزارش‌گیری، مدیریت مشتریان و لیدها است. همیشه پاسخ‌های دقیق و کاربردی بدهید.', 'string', 'پرامپت سیستمی چت‌بات', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12'),
(7, 'chatbot_role_access', 'admin,manager', 'string', 'نقش‌های مجاز (CSV)', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12'),
(8, 'chatbot_session_timeout', '3600', 'integer', 'مدت اعتبار session (ثانیه)', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12'),
(9, 'chatbot_max_history', '50', 'integer', 'حداکثر تعداد پیام در تاریخچه', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12'),
(10, 'chatbot_index_auto_rebuild', '0', 'boolean', 'بازسازی خودکار ایندکس', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12'),
(11, 'chatbot_index_last_build', NULL, 'string', 'آخرین زمان بازسازی ایندکس', 0, '2026-02-11 18:34:12', '2026-02-11 18:34:12');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `related_type` enum('task','lead','customer','sale','project') NOT NULL,
  `related_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(20) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `industry` varchar(50) DEFAULT NULL,
  `customer_type` enum('individual','company') DEFAULT 'individual',
  `status` enum('active','inactive','prospect') DEFAULT 'prospect',
  `source` varchar(50) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `company_name`, `first_name`, `last_name`, `email`, `phone`, `mobile`, `address`, `city`, `state`, `postal_code`, `website`, `industry`, `customer_type`, `status`, `source`, `assigned_to`, `tags`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CUS001', NULL, 'احمد', 'رضایی', 'ahmad@email.com', '02112345678', '09123456789', 'خیابان ولیعصر، پلاک 123', 'تهران', NULL, '1234567890', NULL, NULL, 'individual', 'active', NULL, NULL, NULL, NULL, 1, '2025-11-28 10:07:43', '2026-01-12 10:07:43'),
(2, 'CUS002', NULL, 'فاطمه', 'علوی', 'fateme@email.com', '02187654321', '09198765432', 'خیابان انقلاب، پلاک 456', 'تهران', NULL, '0987654321', NULL, NULL, 'individual', 'active', NULL, NULL, NULL, NULL, 1, '2025-12-13 10:07:43', '2026-01-12 10:07:43'),
(3, 'CUS003', 'شرکت فناوری پارس', 'شرکت', 'فناوری پارس', 'info@parstech.ir', '02155667788', '09155667788', 'خیابان شریعتی، برج میلاد', 'تهران', NULL, '1122334455', NULL, NULL, 'company', 'active', NULL, NULL, NULL, NULL, 1, '2025-12-18 10:07:43', '2026-01-12 10:07:43'),
(4, 'CUS004', NULL, 'حسن', 'کریمی', 'hassan@email.com', '02133445566', '09133445566', 'خیابان کریمخان، پلاک 789', 'تهران', NULL, '5566778899', NULL, NULL, 'individual', 'active', NULL, NULL, NULL, NULL, 1, '2025-12-23 10:07:43', '2026-01-12 10:07:43'),
(5, 'CUS005', 'شرکت بازرگانی آریا', 'شرکت', 'بازرگانی آریا', 'contact@arya.com', '02144556677', '09144556677', 'میدان آزادی، ساختمان تجاری', 'تهران', NULL, '6677889900', NULL, NULL, 'company', 'active', NULL, NULL, NULL, NULL, 1, '2025-12-28 10:07:43', '2026-01-12 10:07:43');

-- --------------------------------------------------------

--
-- Table structure for table `customer_activities`
--

CREATE TABLE `customer_activities` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `activity_type` enum('call','email','meeting','note','purchase','support') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `activity_date` datetime DEFAULT current_timestamp(),
  `duration` int(11) DEFAULT 0,
  `outcome` varchar(100) DEFAULT NULL,
  `next_action` varchar(200) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dm_pairs`
--

CREATE TABLE `dm_pairs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_a` int(11) NOT NULL,
  `user_b` int(11) NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `related_type` enum('customer','lead','task','sale','user','project') NOT NULL,
  `related_id` int(11) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `status` enum('new','contacted','qualified','proposal','negotiation','won','lost') DEFAULT 'new',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `value` decimal(15,2) DEFAULT 0.00,
  `probability` int(11) DEFAULT 0,
  `expected_close_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `title`, `first_name`, `last_name`, `email`, `phone`, `company`, `position`, `source`, `status`, `priority`, `value`, `probability`, `expected_close_date`, `assigned_to`, `description`, `notes`, `tags`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'مدیر فروش', 'مهدی', 'نوری', 'mehdi@novin.com', '09121112233', 'شرکت تکنولوژی نوین', NULL, 'website', 'new', 'high', 0.00, 0, NULL, 3, 'علاقه‌مند به خرید سیستم CRM', NULL, NULL, 1, '2025-12-28 10:07:43', '2026-01-12 10:07:43'),
(2, 'کارشناس IT', 'زهرا', 'صادقی', 'zahra@dataproc.ir', '09134445566', 'شرکت داده پردازی', NULL, 'phone', 'contacted', 'medium', 0.00, 0, NULL, 3, 'نیاز به راهکار مدیریت مشتری', NULL, NULL, 1, '2026-01-02 10:07:43', '2026-01-12 10:07:43'),
(3, 'مدیر عامل', 'کامران', 'احمدی', 'kamran@alborz.com', '09167778899', 'گروه صنعتی البرز', NULL, 'email', 'qualified', 'high', 0.00, 0, NULL, 3, 'درخواست دمو محصول', NULL, NULL, 1, '2025-12-23 10:07:43', '2026-01-12 10:07:43'),
(4, 'مدیر بازاریابی', 'لیلا', 'محمدی', 'leila@pars.com', '09155443322', 'شرکت بازرگانی پارس', NULL, 'social', 'proposal', 'medium', 0.00, 0, NULL, 3, 'جلسه برای ارائه قیمت', NULL, NULL, 1, '2026-01-04 10:07:43', '2026-01-12 10:07:43'),
(5, 'مدیر فنی', 'امین', 'کریمی', 'amin@rayan.ir', '09188776655', 'شرکت نرم‌افزاری رایان', NULL, 'referral', 'won', 'low', 0.00, 0, NULL, 3, 'قرارداد منعقد شده', NULL, NULL, 1, '2026-01-07 10:07:43', '2026-01-12 10:07:43');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `message_type` enum('text','system','notice','instruction','warning') NOT NULL DEFAULT 'text',
  `upload_id` bigint(20) UNSIGNED DEFAULT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `room_id`, `user_id`, `body`, `message_type`, `upload_id`, `meta_json`, `created_at`, `edited_at`, `deleted_at`, `deleted_by`) VALUES
(1, 1, 1, 'تست', 'text', NULL, NULL, '2026-02-14 15:21:29', NULL, NULL, NULL),
(2, 1, 1, 'سلام دوستان', 'text', NULL, NULL, '2026-02-14 20:40:40', NULL, NULL, NULL),
(3, 1, 1, 'یو یو', 'text', NULL, NULL, '2026-02-14 21:10:23', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `msgway_config`
--

CREATE TABLE `msgway_config` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 CHECK (`id` = 1),
  `api_key_encrypted` text NOT NULL COMMENT 'کلید API رمزنگاری‌شده با AES-256-CBC',
  `encryption_iv` varchar(24) NOT NULL COMMENT 'IV برای AES (Base64 encoded 16 bytes)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=غیرفعال، 1=فعال',
  `balance` decimal(15,2) DEFAULT 0.00 COMMENT 'آخرین موجودی شناخته‌شده (تومان)',
  `last_balance_check` datetime DEFAULT NULL COMMENT 'آخرین زمان sync موجودی',
  `default_sms_provider` tinyint(3) UNSIGNED DEFAULT 1 COMMENT 'اپراتور پیش‌فرض SMS (1=Magfa, 2=Atieh, 3=AsiaTek, 5=Armaghan)',
  `default_messenger_provider` tinyint(3) UNSIGNED DEFAULT 2 COMMENT 'پیام‌رسان پیش‌فرض (2=Gap, 8=iGap, 9=Ita, 10=Bale, 12=Rubika)',
  `send_mode` enum('live','test') DEFAULT 'live' COMMENT 'حالت ارسال',
  `daily_limit` int(10) UNSIGNED DEFAULT 0 COMMENT 'محدودیت ارسال روزانه (0=نامحدود)',
  `sender_name` varchar(50) DEFAULT NULL COMMENT 'نام فرستنده (اختیاری)',
  `webhook_url` varchar(255) DEFAULT NULL COMMENT 'URL برای Webhook (آینده)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تنظیمات کلی MessageWay (Single-Row)';

--
-- Dumping data for table `msgway_config`
--

INSERT INTO `msgway_config` (`id`, `api_key_encrypted`, `encryption_iv`, `is_active`, `balance`, `last_balance_check`, `default_sms_provider`, `default_messenger_provider`, `send_mode`, `daily_limit`, `sender_name`, `webhook_url`, `created_at`, `updated_at`) VALUES
(1, '', '', 0, 0.00, NULL, 1, 2, 'live', 0, NULL, NULL, '2026-02-13 19:06:57', '2026-02-13 19:06:57');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_number` varchar(20) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','card','transfer','cheque','other') NOT NULL,
  `payment_date` date NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','failed','cancelled') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_number`, `sale_id`, `amount`, `payment_method`, `payment_date`, `reference_number`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PAY001', 1, 6050000.00, 'transfer', '2026-01-12', 'TXN123456789', 'پرداخت کامل فاکتور', 'confirmed', 3, '2026-01-12 10:07:43', '2026-01-12 10:07:43'),
(2, 'PAY002', 2, 8220000.00, 'transfer', '2026-01-09', 'CARD987654321', 'پرداخت کامل', 'confirmed', 3, '2026-01-09 10:07:43', '2026-01-12 10:07:43'),
(3, 'PAY003', 3, 1635000.00, 'cash', '2026-01-11', NULL, 'پرداخت نقدی', 'confirmed', 3, '2026-01-11 10:07:43', '2026-01-12 10:07:43');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT 0.00,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `sku` varchar(100) NOT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `stock_quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `unit` varchar(50) DEFAULT 'عدد',
  `barcode` varchar(100) DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT 0.000,
  `dimensions` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `category`, `price`, `cost_price`, `sku`, `status`, `stock_quantity`, `min_stock_level`, `unit`, `barcode`, `weight`, `dimensions`, `tags`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'سیستم CRM حرفه‌ای', 'سیستم مدیریت ارتباط با مشتری برای شرکت‌های بزرگ', 'نرم‌افزار', 5000000.00, 2500000.00, 'CRM-PRO-001', 'active', 9, 2, 'لایسنس', '1234567890123', 0.000, 'دیجیتال', 'CRM,مدیریت,مشتری', 'محصول پرفروش', 1, '2026-01-12 10:07:43', '2026-02-10 12:11:33'),
(2, 'ماژول گزارش‌گیری', 'ماژول گزارش‌گیری پیشرفته برای CRM', 'ماژول', 1500000.00, 750000.00, 'RPT-MOD-002', 'active', 25, 5, 'لایسنس', '2345678901234', 0.000, 'دیجیتال', 'گزارش,تحلیل', 'ماژول اضافی', 1, '2026-01-12 10:07:43', '2026-01-12 10:07:43'),
(3, 'خدمات پیاده‌سازی', 'خدمات پیاده‌سازی و راه‌اندازی سیستم', 'خدمات', 3000000.00, 1200000.00, 'SRV-IMP-003', 'active', 100, 10, 'ساعت', '3456789012345', 0.000, 'خدماتی', 'پیاده‌سازی,راه‌اندازی', 'خدمات تخصصی', 1, '2026-01-12 10:07:43', '2026-01-12 10:07:43'),
(4, 'پشتیبانی سالانه', 'پشتیبانی و نگهداری سالانه سیستم', 'پشتیبانی', 800000.00, 300000.00, 'SUP-YRL-004', 'active', 50, 5, 'قرارداد', '4567890123456', 0.000, 'خدماتی', 'پشتیبانی,نگهداری', 'قرارداد سالانه', 1, '2026-01-12 10:07:43', '2026-01-12 10:07:43'),
(5, 'آموزش کاربران', 'دوره آموزشی کاربران سیستم CRM', 'آموزش', 1200000.00, 400000.00, 'TRN-USR-005', 'active', 20, 3, 'دوره', '5678901234567', 0.000, 'آموزشی', 'آموزش,کاربران', 'آموزش عملی', 1, '2026-01-12 10:07:43', '2026-01-12 10:07:43');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_code` varchar(20) NOT NULL COMMENT 'کد اختصاصی پروژه مثل PRJ-1001',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL COMMENT 'مشتری مرتبط',
  `manager_id` int(11) NOT NULL COMMENT 'مدیر پروژه',
  `status` enum('not_started','in_progress','on_hold','cancelled','completed') DEFAULT 'not_started',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `budget` decimal(15,2) DEFAULT 0.00 COMMENT 'بودجه تعریف شده',
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `real_end_date` date DEFAULT NULL,
  `progress` int(3) DEFAULT 0 COMMENT 'درصد پیشرفت دستی یا محاسباتی',
  `tags` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_code`, `title`, `description`, `customer_id`, `manager_id`, `status`, `priority`, `budget`, `start_date`, `deadline`, `real_end_date`, `progress`, `tags`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '1100', 'تأمین کننده طباطبایی', 'تامین محصولات آرایشی و بهداشتی', 3, 1, 'in_progress', 'high', 0.00, '2026-02-12', NULL, NULL, 50, NULL, 1, '2026-02-12 14:45:26', '2026-02-12 14:45:26');

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

CREATE TABLE `project_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'member' COMMENT 'نقش در پروژه مثلا طراح، برنامه نویس',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_members`
--

INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 3, 'member', '2026-02-12 14:45:26'),
(2, 1, 1, 'manager', '2026-02-12 14:45:26');

-- --------------------------------------------------------

--
-- Table structure for table `project_milestones`
--

CREATE TABLE `project_milestones` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` varchar(240) NOT NULL DEFAULT '',
  `avatar_path` varchar(255) NOT NULL DEFAULT '',
  `type` enum('group','channel','dm') NOT NULL DEFAULT 'group',
  `visibility` enum('public','private') NOT NULL DEFAULT 'public',
  `is_readonly` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `description`, `avatar_path`, `type`, `visibility`, `is_readonly`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'گروه عمومی', '', '', 'group', 'public', 0, NULL, '2026-02-14 13:40:22', NULL),
(2, 'کانال اطلاع‌رسانی', '', '', 'channel', 'public', 1, NULL, '2026-02-14 13:40:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `room_members`
--

CREATE TABLE `room_members` (
  `room_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `member_role` enum('owner','admin','member') NOT NULL DEFAULT 'member',
  `is_muted` tinyint(1) NOT NULL DEFAULT 0,
  `mute_until` datetime DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `added_by` int(11) DEFAULT NULL,
  `last_read_message_id` bigint(20) DEFAULT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsc_admin_audit_logs`
--

CREATE TABLE `rsc_admin_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(40) NOT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsc_chat_settings`
--

CREATE TABLE `rsc_chat_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `max_upload_mb` int(10) UNSIGNED NOT NULL DEFAULT 20,
  `enable_channels` tinyint(1) NOT NULL DEFAULT 1,
  `enable_dm` tinyint(1) NOT NULL DEFAULT 1,
  `retention_days` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=نامحدود',
  `allow_group_create_roles_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`allow_group_create_roles_json`)),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rsc_chat_settings`
--

INSERT INTO `rsc_chat_settings` (`id`, `max_upload_mb`, `enable_channels`, `enable_dm`, `retention_days`, `allow_group_create_roles_json`, `updated_by`, `updated_at`) VALUES
(1, 20, 1, 1, 0, '[\"admin\", \"manager\"]', NULL, '2026-02-14 22:24:16');

-- --------------------------------------------------------

--
-- Table structure for table `rsc_message_templates`
--

CREATE TABLE `rsc_message_templates` (
  `id` int(11) NOT NULL,
  `type` enum('notice','instruction','warning','system') NOT NULL DEFAULT 'notice',
  `title` varchar(120) NOT NULL,
  `body` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsc_permissions`
--

CREATE TABLE `rsc_permissions` (
  `id` int(11) NOT NULL,
  `key` varchar(80) NOT NULL,
  `title` varchar(120) NOT NULL,
  `group_key` varchar(60) NOT NULL DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rsc_permissions`
--

INSERT INTO `rsc_permissions` (`id`, `key`, `title`, `group_key`, `created_at`) VALUES
(1, 'chat.view', 'مشاهده پیام‌رسان', 'core', '2026-02-14 22:24:16'),
(2, 'chat.dm.start', 'شروع چت جدید', 'core', '2026-02-14 22:24:16'),
(3, 'chat.users.list', 'مشاهده لیست کاربران', 'core', '2026-02-14 22:24:16'),
(4, 'chat.profile.update', 'ویرایش پروفایل/آواتار', 'core', '2026-02-14 22:24:16'),
(5, 'room.create', 'ساخت گروه/اتاق', 'rooms', '2026-02-14 22:24:16'),
(6, 'room.update', 'ویرایش اطلاعات گروه/اتاق', 'rooms', '2026-02-14 22:24:16'),
(7, 'room.delete', 'حذف گروه/اتاق', 'rooms', '2026-02-14 22:24:16'),
(8, 'room.members.list', 'مشاهده اعضای گروه', 'rooms', '2026-02-14 22:24:16'),
(9, 'room.members.add', 'افزودن عضو به گروه', 'rooms', '2026-02-14 22:24:16'),
(10, 'room.members.remove', 'حذف عضو از گروه', 'rooms', '2026-02-14 22:24:16'),
(11, 'room.visibility.set', 'تنظیم عمومی/خصوصی', 'rooms', '2026-02-14 22:24:16'),
(12, 'room.desc.set', 'ثبت/ویرایش توضیحات', 'rooms', '2026-02-14 22:24:16'),
(13, 'admin.stats.view', 'مشاهده آمار پیام‌رسان', 'admin', '2026-02-14 22:24:16'),
(14, 'admin.settings.manage', 'مدیریت تنظیمات پیام‌رسان', 'admin', '2026-02-14 22:24:16'),
(15, 'admin.templates.manage', 'مدیریت قالب‌های پیام', 'admin', '2026-02-14 22:24:16'),
(16, 'admin.users.manage', 'مدیریت کاربران پیام‌رسان', 'admin', '2026-02-14 22:24:16'),
(17, 'admin.rooms.manage', 'مدیریت اتاق‌ها', 'admin', '2026-02-14 22:24:16');

-- --------------------------------------------------------

--
-- Table structure for table `rsc_roles`
--

CREATE TABLE `rsc_roles` (
  `id` int(11) NOT NULL,
  `key` varchar(50) NOT NULL,
  `title` varchar(80) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsc_role_permissions`
--

CREATE TABLE `rsc_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsc_user_presence`
--

CREATE TABLE `rsc_user_presence` (
  `user_id` int(11) NOT NULL,
  `status` enum('online','away','busy','offline') NOT NULL DEFAULT 'offline',
  `last_seen_at` datetime NOT NULL,
  `last_ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsc_user_roles`
--

CREATE TABLE `rsc_user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `sale_number` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `shipping_amount` decimal(15,2) DEFAULT 0.00,
  `final_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','pending','confirmed','processing','shipped','delivered','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `payment_method` enum('cash','card','transfer','cheque','installment') DEFAULT NULL,
  `sale_date` datetime NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_number`, `customer_id`, `lead_id`, `subtotal`, `total_amount`, `discount_amount`, `tax_amount`, `shipping_amount`, `final_amount`, `status`, `payment_status`, `payment_method`, `sale_date`, `delivery_date`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'S240001', 1, NULL, 5800000.00, 5800000.00, 200000.00, 450000.00, 0.00, 6050000.00, 'confirmed', 'paid', 'transfer', '2026-01-10 13:37:43', NULL, 'پرداخت کامل انجام شده', 3, '2026-01-10 10:07:43', '2026-01-12 10:07:43'),
(2, 'S240002', 3, 3, 8000000.00, 8000000.00, 500000.00, 720000.00, 0.00, 8220000.00, 'confirmed', 'paid', 'transfer', '2026-01-07 13:37:43', NULL, 'فروش بزرگ', 3, '2026-01-07 10:07:43', '2026-01-12 10:07:43'),
(3, 'S240003', 2, NULL, 1500000.00, 1500000.00, 0.00, 135000.00, 0.00, 1635000.00, 'delivered', 'paid', 'cash', '2026-01-04 13:37:43', NULL, 'تحویل کامل', 3, '2026-01-04 10:07:43', '2026-01-12 10:07:43'),
(4, 'S-20260210-2325', 1, 5, 5000000.00, 5242000.00, 3200.00, 100000.00, 145200.00, 5242000.00, 'confirmed', 'paid', 'cash', '2026-02-10 15:40:00', NULL, '', 1, '2026-02-10 12:11:33', '2026-02-10 12:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT 1.000,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `discount_percent`, `created_at`) VALUES
(1, 1, 1, 1.000, 5000000.00, 5000000.00, 0.00, '2026-01-12 10:07:43'),
(2, 1, 4, 1.000, 800000.00, 800000.00, 0.00, '2026-01-12 10:07:43'),
(3, 2, 1, 1.000, 5000000.00, 5000000.00, 0.00, '2026-01-12 10:07:43'),
(4, 2, 3, 1.000, 3000000.00, 3000000.00, 0.00, '2026-01-12 10:07:43'),
(5, 3, 2, 1.000, 1500000.00, 1500000.00, 0.00, '2026-01-12 10:07:43'),
(6, 4, 1, 1.000, 5000000.00, 5000000.00, 0.00, '2026-02-10 12:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'ردی استودیو', 'string', 'نام شرکت', '2026-01-12 10:07:43', '2026-02-03 12:54:45'),
(2, 'company_phone', '05136161', 'string', 'تلفن شرکت', '2026-01-12 10:07:43', '2026-02-03 12:54:45'),
(3, 'company_email', 'hi@entekhabhome.ir', 'string', 'ایمیل شرکت', '2026-01-12 10:07:43', '2026-02-03 12:54:45'),
(4, 'company_address', 'ایران، مشهد، استقلال 9', 'string', 'آدرس شرکت', '2026-01-12 10:07:43', '2026-02-03 12:54:45'),
(5, 'tax_rate', '10', 'integer', 'نرخ مالیات (درصد)', '2026-01-12 10:07:43', '2026-02-03 12:54:45'),
(6, 'currency', 'تومان', 'string', 'واحد پول', '2026-01-12 10:07:43', '2026-01-12 10:07:43'),
(7, 'records_per_page', '50', 'integer', 'تعداد رکورد در هر صفحه', '2026-01-12 10:07:43', '2026-02-03 12:54:45'),
(8, 'mail_host', 'mail.entekhabhome.ir', 'string', NULL, '2026-02-03 12:54:17', '2026-02-03 12:54:17'),
(9, 'mail_port', '465', 'string', NULL, '2026-02-03 12:54:17', '2026-02-03 12:54:17'),
(10, 'mail_username', 'hi@entekhabhome.ir', 'string', NULL, '2026-02-03 12:54:17', '2026-02-03 12:54:17'),
(11, 'mail_password', 'c#GGrf+)IL,Is8GP.c', 'string', NULL, '2026-02-03 12:54:17', '2026-02-03 12:54:17'),
(12, 'mail_from_email', 'hi@entekhabhome.ir', 'string', NULL, '2026-02-03 12:54:17', '2026-02-03 12:54:17'),
(13, 'mail_from_name', 'سی آر ام انتخاب', 'string', NULL, '2026-02-03 12:54:17', '2026-02-03 12:54:17'),
(14, 'mail_encryption', 'tls', 'string', NULL, '2026-02-03 12:54:17', '2026-02-03 12:54:17'),
(22, 'chatbot_api_key', 'sk-hbMtHNYeVjNbgMQp4IxH8ZgydqvKH8wjDCAFYLJze2I9VLXn', 'string', NULL, '2026-02-11 18:19:32', '2026-02-11 18:19:32'),
(23, 'chatbot_model', 'deepseek-v3', 'string', NULL, '2026-02-11 18:19:32', '2026-02-14 13:53:14'),
(24, 'chatbot_enabled', '1', 'string', NULL, '2026-02-11 18:19:32', '2026-02-11 18:19:32'),
(25, 'chatbot_temperature', '0', 'string', NULL, '2026-02-11 18:19:32', '2026-02-14 13:53:14'),
(26, 'chatbot_max_tokens', '1200', 'string', NULL, '2026-02-11 18:19:32', '2026-02-14 13:53:14'),
(27, 'chatbot_context_messages', '50', 'string', NULL, '2026-02-11 18:19:32', '2026-02-14 13:53:14'),
(28, 'chatbot_role_admin', '1', 'string', NULL, '2026-02-11 18:19:32', '2026-02-11 18:19:32'),
(29, 'chatbot_role_manager', '1', 'string', NULL, '2026-02-11 18:19:32', '2026-02-11 18:19:32'),
(30, 'chatbot_role_user', '1', 'string', NULL, '2026-02-11 18:19:32', '2026-02-11 18:19:32'),
(42, 'chatbot_system_prompt', 'شما یک دستیار هوشمند CRM هستید که به فارسی پاسخ می‌دهید. وظیفه شما کمک به کاربران در تحلیل داده‌ها، گزارش‌گیری، مدیریت مشتریان و لیدها است. همیشه پاسخ‌های دقیق و کاربردی بدهید.', 'string', NULL, '2026-02-14 13:53:14', '2026-02-14 13:53:14');

-- --------------------------------------------------------

--
-- Table structure for table `sms_campaigns`
--

CREATE TABLE `sms_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'نام کمپین',
  `template_id` int(11) NOT NULL COMMENT 'الگوی استفاده‌شده',
  `audience_type` enum('all_customers','segment','manual','upload') DEFAULT 'manual' COMMENT 'نوع مخاطب',
  `audience_filter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'فیلتر مخاطبان (برای segment)' CHECK (json_valid(`audience_filter`)),
  `status` enum('draft','pending','processing','completed','failed','cancelled','archived') DEFAULT 'draft' COMMENT 'وضعیت کمپین',
  `scheduled_at` datetime DEFAULT NULL COMMENT 'زمان ارسال (NULL=فوری)',
  `send_method` enum('sms','ivr','smart','messenger') DEFAULT 'sms' COMMENT 'روش ارسال',
  `provider_id` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'اپراتور یا پیام‌رسان',
  `total_recipients` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد کل مخاطبان',
  `sent_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد ارسال موفق',
  `delivered_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد تحویل موفق',
  `failed_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد ناموفق',
  `estimated_cost` decimal(15,2) DEFAULT 0.00 COMMENT 'هزینه تخمینی (تومان)',
  `actual_cost` decimal(15,2) DEFAULT 0.00 COMMENT 'هزینه واقعی (تومان)',
  `lock_token` varchar(64) DEFAULT NULL COMMENT 'توکن قفل برای Job Processing',
  `locked_at` datetime DEFAULT NULL COMMENT 'زمان قفل‌شدن',
  `retry_count` tinyint(3) UNSIGNED DEFAULT 0 COMMENT 'تعداد تلاش‌های مجدد',
  `last_error` text DEFAULT NULL COMMENT 'آخرین خطای رخ‌داده',
  `completed_at` datetime DEFAULT NULL COMMENT 'زمان تکمیل کمپین',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='کمپین‌های پیامکی';

-- --------------------------------------------------------

--
-- Table structure for table `sms_campaign_recipients`
--

CREATE TABLE `sms_campaign_recipients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `mobile` varchar(20) NOT NULL COMMENT 'شماره موبایل (نرمال‌شده: 09...)',
  `name` varchar(100) DEFAULT NULL COMMENT 'نام مخاطب (اختیاری)',
  `params` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'پارامترهای شخصی‌سازی‌شده' CHECK (json_valid(`params`)),
  `status` enum('pending','sent','delivered','failed','cancelled') DEFAULT 'pending' COMMENT 'وضعیت ارسال',
  `retry_count` tinyint(3) UNSIGNED DEFAULT 0,
  `msgway_message_id` varchar(100) DEFAULT NULL COMMENT 'OTPReferenceID از MsgWay',
  `error_message` text DEFAULT NULL COMMENT 'پیام خطا در صورت ناموفق بودن',
  `sent_at` datetime DEFAULT NULL COMMENT 'زمان ارسال',
  `last_attempt_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL COMMENT 'زمان تحویل',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='مخاطبان هر کمپین';

-- --------------------------------------------------------

--
-- Table structure for table `sms_daily_stats`
--

CREATE TABLE `sms_daily_stats` (
  `id` int(11) NOT NULL,
  `stat_date` date NOT NULL COMMENT 'تاریخ',
  `total_sent` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد کل ارسال',
  `total_delivered` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد تحویل موفق',
  `total_failed` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد ناموفق',
  `total_cost` decimal(15,2) DEFAULT 0.00 COMMENT 'هزینه کل روز',
  `sms_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد SMS',
  `ivr_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد IVR',
  `messenger_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد Messenger',
  `otp_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد OTP',
  `campaign_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'تعداد کمپین‌های تکمیل‌شده',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='آمار روزانه ارسال پیامک';

-- --------------------------------------------------------

--
-- Table structure for table `sms_event_settings`
--

CREATE TABLE `sms_event_settings` (
  `id` int(11) NOT NULL,
  `event_key` varchar(50) NOT NULL COMMENT 'مانند: user_login, new_sale, task_reminder',
  `template_id` int(11) DEFAULT NULL COMMENT 'آیدی الگو از جدول sms_templates',
  `is_enabled` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_event_settings`
--

INSERT INTO `sms_event_settings` (`id`, `event_key`, `template_id`, `is_enabled`, `updated_at`) VALUES
(1, 'otp_login', 1, 1, '2026-02-16 11:16:05'),
(2, 'customer_welcome', 3, 1, '2026-02-16 11:16:05'),
(3, 'task_reminder', 4, 1, '2026-02-16 11:16:05'),
(4, 'invoice_issued', 5, 1, '2026-02-16 11:16:05');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mobile` varchar(20) NOT NULL COMMENT 'شماره موبایل مقصد',
  `customer_id` int(11) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL COMMENT 'الگوی استفاده‌شده',
  `campaign_id` int(11) DEFAULT NULL COMMENT 'کمپین مرتبط (در صورت وجود)',
  `msgway_message_id` varchar(100) DEFAULT NULL COMMENT 'OTPReferenceID',
  `send_method` enum('sms','ivr','smart','messenger') DEFAULT 'sms',
  `provider_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `status` enum('pending','sent','delivered','failed') DEFAULT 'pending',
  `cost` decimal(15,2) DEFAULT 0.00,
  `error_message` text DEFAULT NULL,
  `api_response` longtext DEFAULT NULL COMMENT 'پاسخ خام API (JSON)',
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='لاگ تمام پیامک‌های ارسالی';

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'نام داخلی الگو',
  `remote_template_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'شناسه الگو در MsgWay',
  `content` text NOT NULL COMMENT 'متن الگو با پارامترها (مثلاً: {1} و {2})',
  `params_count` tinyint(3) UNSIGNED DEFAULT 0 COMMENT 'تعداد پارامترهای الگو',
  `params_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`params_map`)),
  `template_type` enum('otp','notification','marketing','transactional','custom') DEFAULT 'notification' COMMENT 'نوع الگو',
  `method` enum('sms','ivr','smart','messenger') DEFAULT 'sms' COMMENT 'روش ارسال',
  `status` enum('draft','pending','active','rejected','inactive') DEFAULT 'draft' COMMENT 'وضعیت تایید الگو',
  `is_system` tinyint(1) DEFAULT 0 COMMENT '1=الگوی سیستمی (غیرقابل حذف)',
  `notes` text DEFAULT NULL COMMENT 'یادداشت‌ها',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='الگوهای پیامک';

--
-- Dumping data for table `sms_templates`
--

INSERT INTO `sms_templates` (`id`, `name`, `remote_template_id`, `content`, `params_count`, `params_map`, `template_type`, `method`, `status`, `is_system`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'کد تایید (OTP)', NULL, 'کد تایید شما: {1}', 1, '{\"1\": \"کد تایید\"}', 'otp', 'sms', 'active', 1, NULL, NULL, '2026-02-13 19:06:57', '2026-02-16 14:45:36'),
(2, 'تماس صوتی (IVR)', 2, 'کد تایید شما از طریق تماس صوتی اعلام می‌شود', 0, NULL, 'otp', 'ivr', 'active', 1, NULL, NULL, '2026-02-13 19:06:57', '2026-02-13 19:06:57'),
(3, 'پیام خوش‌آمدگویی', NULL, 'سلام {1} عزیز، به سیستم CRM خوش آمدید', 1, '{\"1\": \"نام مشتری\"}', 'notification', 'sms', 'active', 1, NULL, NULL, '2026-02-13 19:06:57', '2026-02-16 14:45:36'),
(4, 'یادآور جلسه', NULL, '{1} عزیز، یادآوری جلسه شما در تاریخ {2}', 2, '{\"1\": \"نام\", \"2\": \"تاریخ جلسه\"}', 'notification', 'sms', 'active', 1, NULL, NULL, '2026-02-13 19:06:57', '2026-02-16 14:45:36'),
(5, 'اطلاع‌رسانی فاکتور', NULL, '{1} عزیز، فاکتور شماره {2} به مبلغ {3} ریال صادر شد', 3, '{\"1\": \"نام\", \"2\": \"شماره فاکتور\", \"3\": \"مبلغ\"}', 'transactional', 'sms', 'active', 1, NULL, NULL, '2026-02-13 19:06:57', '2026-02-16 14:45:36');

--
-- Triggers `sms_templates`
--
DELIMITER $$
CREATE TRIGGER `trg_prevent_system_template_delete` BEFORE DELETE ON `sms_templates` FOR EACH ROW BEGIN
    IF OLD.is_system = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'امکان حذف الگوهای سیستمی وجود ندارد';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('call','email','meeting','follow_up','other') DEFAULT 'other',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `due_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `related_type` enum('customer','lead','user') DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `reminder_datetime` datetime DEFAULT NULL,
  `is_reminder_sent` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `time_spent` int(11) DEFAULT 0 COMMENT 'زمان به ثانیه',
  `tags` varchar(255) DEFAULT NULL COMMENT 'تگ‌های جدا شده با کاما'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `type`, `status`, `priority`, `due_date`, `completed_at`, `assigned_to`, `related_type`, `related_id`, `project_id`, `reminder_datetime`, `is_reminder_sent`, `created_by`, `created_at`, `updated_at`, `time_spent`, `tags`) VALUES
(1, 'تماس با مشتری جدید', 'تماس اولیه با مشتری برای شناخت نیازها', 'other', 'pending', 'high', '2026-01-14 13:37:43', NULL, 3, 'customer', 1, NULL, NULL, 0, 1, '2026-01-12 10:07:43', '2026-01-12 10:07:43', 0, NULL),
(2, 'ارسال پیشنهاد قیمت', 'تهیه و ارسال پیشنهاد قیمت برای پروژه CRM', 'other', 'in_progress', 'medium', '2026-01-17 13:37:43', NULL, 3, 'lead', 2, NULL, NULL, 0, 1, '2026-01-12 10:07:43', '2026-01-12 10:07:43', 0, NULL),
(3, 'دمو محصول', 'برگزاری جلسه دمو برای نمایش امکانات', 'other', 'pending', 'high', '2026-01-15 13:37:43', NULL, 2, 'lead', 3, NULL, NULL, 0, 1, '2026-01-12 10:07:43', '2026-01-12 10:07:43', 0, NULL),
(4, 'پیگیری قرارداد', 'پیگیری وضعیت امضای قرارداد', 'other', 'completed', 'medium', '2026-01-11 13:37:43', '2026-01-11 13:37:43', 3, 'customer', 3, NULL, NULL, 0, 1, '2026-01-07 10:07:43', '2026-01-12 10:07:43', 0, NULL),
(5, 'بررسی نیازمندی‌ها', 'تحلیل دقیق نیازمندی‌های فنی مشتری', 'other', 'in_progress', 'low', '2026-01-19 13:37:43', NULL, 4, 'lead', 4, NULL, NULL, 0, 1, '2026-01-09 10:07:43', '2026-01-12 10:07:43', 0, NULL),
(6, 'ساخت پاپ آپ پنل پیامکی + 50.000 شارژ هدیه', NULL, 'other', 'pending', 'medium', '2026-02-12 15:41:00', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, '2026-02-10 12:12:05', '2026-02-10 12:12:05', 0, NULL),
(7, 'تماس با chitalk.net', NULL, 'other', 'pending', 'medium', '2026-02-10 15:42:00', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, '2026-02-10 12:12:20', '2026-02-10 12:12:20', 0, NULL),
(8, 'تکمیل کردن بخش سوالات متداول', NULL, 'other', 'pending', 'medium', '2026-02-10 15:42:00', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, '2026-02-10 12:12:31', '2026-02-10 12:12:31', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `task_subtasks`
--

CREATE TABLE `task_subtasks` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `stored_name` varchar(128) NOT NULL,
  `storage_path` varchar(255) NOT NULL DEFAULT '',
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(96) NOT NULL,
  `size_bytes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sha256` char(64) NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('admin','manager','sales','user') DEFAULT 'user',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `mobile`, `phone`, `avatar`, `role`, `status`, `department`, `position`, `hire_date`, `salary`, `address`, `notes`, `last_login`, `failed_login_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'ghaemipm@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'فاضل', 'قائمی', '09159040610', '05136161122', NULL, 'admin', 'active', 'مدیریت', 'مدیر عامل', '2020-01-15', 25000000.00, 'ایران، مشهد', 'مدیر کل سیستم', '2026-02-16 14:33:31', 0, NULL, '2026-01-12 10:07:43', '2026-02-16 11:03:31'),
(2, 'manager', 'manager@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'سارا', 'محمدی', '09129876543', '02188776656', NULL, 'manager', 'active', 'فروش', 'مدیر فروش', '2021-03-10', 18000000.00, 'تهران، انقلاب، پلاک 456', 'مدیر بخش فروش', NULL, 0, NULL, '2026-01-12 10:07:43', '2026-01-12 10:07:43'),
(3, 'sales1', 'sales@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'رضا', 'کریمی', '09112345678', '02188776657', NULL, 'sales', 'active', 'فروش', 'کارشناس فروش', '2022-06-20', 12000000.00, 'تهران، کریمخان، پلاک 789', 'کارشناس فروش ارشد', NULL, 0, NULL, '2026-01-12 10:07:43', '2026-01-12 10:07:43'),
(4, 'user1', 'user@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مریم', 'حسینی', '09198765432', '02188776658', NULL, 'user', 'active', 'پشتیبانی', 'کارشناس پشتیبانی', '2023-01-05', 10000000.00, 'تهران، شریعتی، پلاک 321', 'کارشناس پشتیبانی مشتریان', NULL, 0, NULL, '2026-01-12 10:07:43', '2026-01-12 10:07:43'),
(5, 'ehsanfast', 'ehsanfast@gmail.com', '$2y$10$NJllN9ycdwABWHE7NojEUOX/fpBy4wgEFxCXrMzoirAZxklhIw7Ua', 'احسان', 'ثابت مشمول', '09356167766', '', NULL, 'manager', 'active', 'محصول', 'مدیر محصول', '2026-02-03', 50000000.00, 'ایران، مشهد', '', NULL, 0, NULL, '2026-02-03 10:56:40', '2026-02-03 10:56:40');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `display_name` varchar(64) NOT NULL DEFAULT '',
  `bio` varchar(160) NOT NULL DEFAULT '',
  `avatar_path` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `display_name`, `bio`, `avatar_path`, `updated_at`) VALUES
(1, 'فاضل قائمی', '', '', '2026-02-14 14:43:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user_id` (`user_id`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`);

--
-- Indexes for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_intent` (`intent`),
  ADD KEY `idx_session_user` (`session_id`,`user_id`);

--
-- Indexes for table `chatbot_index`
--
ALTER TABLE `chatbot_index`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_source` (`source_table`,`source_id`),
  ADD KEY `idx_content_type` (`content_type`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_updated_at` (`updated_at`);
ALTER TABLE `chatbot_index` ADD FULLTEXT KEY `ft_indexed_content` (`indexed_content`);
ALTER TABLE `chatbot_index` ADD FULLTEXT KEY `ft_search_keywords` (`search_keywords`);

--
-- Indexes for table `chatbot_settings`
--
ALTER TABLE `chatbot_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_chatbot_setting_key` (`setting_key`),
  ADD KEY `idx_chatbot_setting_type` (`setting_type`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `related_index` (`related_type`,`related_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_customers_email` (`email`),
  ADD KEY `idx_customers_phone` (`phone`),
  ADD KEY `idx_customers_assigned_to` (`assigned_to`),
  ADD KEY `idx_customers_status` (`status`);

--
-- Indexes for table `customer_activities`
--
ALTER TABLE `customer_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `dm_pairs`
--
ALTER TABLE `dm_pairs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pair` (`user_a`,`user_b`),
  ADD KEY `user_b` (`user_b`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_leads_email` (`email`),
  ADD KEY `idx_leads_phone` (`phone`),
  ADD KEY `idx_leads_status` (`status`),
  ADD KEY `idx_leads_assigned_to` (`assigned_to`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_room_created` (`room_id`,`created_at`),
  ADD KEY `idx_messages_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_messages_upload` (`upload_id`),
  ADD KEY `idx_messages_deleted_at` (`deleted_at`);

--
-- Indexes for table `msgway_config`
--
ALTER TABLE `msgway_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_number` (`payment_number`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_manager` (`manager_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`project_id`,`user_id`),
  ADD KEY `fk_pm_user` (`user_id`);

--
-- Indexes for table `project_milestones`
--
ALTER TABLE `project_milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_milestone_project` (`project_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `idx_rooms_visibility` (`visibility`),
  ADD KEY `idx_rooms_created_by` (`created_by`),
  ADD KEY `idx_rooms_type_visibility` (`type`,`visibility`);

--
-- Indexes for table `room_members`
--
ALTER TABLE `room_members`
  ADD PRIMARY KEY (`room_id`,`user_id`),
  ADD KEY `idx_rm_user` (`user_id`),
  ADD KEY `idx_rm_room` (`room_id`),
  ADD KEY `idx_rm_role` (`member_role`),
  ADD KEY `idx_rm_last_read` (`room_id`,`last_read_message_id`),
  ADD KEY `idx_rm_added_by` (`added_by`),
  ADD KEY `idx_rm_lastread` (`room_id`,`last_read_message_id`);

--
-- Indexes for table `rsc_admin_audit_logs`
--
ALTER TABLE `rsc_admin_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rsc_audit_actor` (`actor_user_id`),
  ADD KEY `idx_rsc_audit_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `rsc_chat_settings`
--
ALTER TABLE `rsc_chat_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rsc_settings_updated_by` (`updated_by`);

--
-- Indexes for table `rsc_message_templates`
--
ALTER TABLE `rsc_message_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rsc_tpl_type` (`type`),
  ADD KEY `idx_rsc_tpl_active` (`is_active`),
  ADD KEY `fk_rsc_tpl_created_by` (`created_by`);

--
-- Indexes for table `rsc_permissions`
--
ALTER TABLE `rsc_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rsc_permissions_key` (`key`),
  ADD KEY `idx_rsc_permissions_group` (`group_key`);

--
-- Indexes for table `rsc_roles`
--
ALTER TABLE `rsc_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rsc_roles_key` (`key`);

--
-- Indexes for table `rsc_role_permissions`
--
ALTER TABLE `rsc_role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_rsc_rp_perm` (`permission_id`);

--
-- Indexes for table `rsc_user_presence`
--
ALTER TABLE `rsc_user_presence`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_rsc_presence_status` (`status`),
  ADD KEY `idx_rsc_presence_last_seen` (`last_seen_at`);

--
-- Indexes for table `rsc_user_roles`
--
ALTER TABLE `rsc_user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_rsc_ur_role` (`role_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sales_customer_id` (`customer_id`),
  ADD KEY `idx_sales_status` (`status`),
  ADD KEY `idx_sales_sale_date` (`sale_date`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign_status` (`status`,`scheduled_at`,`locked_at`),
  ADD KEY `idx_campaign_dates` (`scheduled_at`,`completed_at`),
  ADD KEY `idx_campaign_creator` (`created_by`),
  ADD KEY `fk_campaign_template` (`template_id`);

--
-- Indexes for table `sms_campaign_recipients`
--
ALTER TABLE `sms_campaign_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_campaign_mobile` (`campaign_id`,`mobile`),
  ADD KEY `idx_recipient_status` (`status`,`sent_at`),
  ADD KEY `idx_recipient_msgway` (`msgway_message_id`),
  ADD KEY `idx_recipient_mobile` (`mobile`),
  ADD KEY `idx_recipients_retry_status` (`status`,`retry_count`);

--
-- Indexes for table `sms_daily_stats`
--
ALTER TABLE `sms_daily_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_stat_date` (`stat_date`);

--
-- Indexes for table `sms_event_settings`
--
ALTER TABLE `sms_event_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_event_key` (`event_key`),
  ADD KEY `fk_event_template` (`template_id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_mobile` (`mobile`),
  ADD KEY `idx_log_status` (`status`,`sent_at`),
  ADD KEY `idx_log_msgway` (`msgway_message_id`),
  ADD KEY `idx_log_template` (`template_id`),
  ADD KEY `idx_log_campaign` (`campaign_id`),
  ADD KEY `idx_sms_logs_customer_date` (`customer_id`,`created_at`);

--
-- Indexes for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_remote_template` (`remote_template_id`),
  ADD KEY `idx_template_status` (`status`),
  ADD KEY `idx_template_type` (`template_type`),
  ADD KEY `idx_is_system` (`is_system`),
  ADD KEY `fk_sms_template_creator` (`created_by`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_tasks_assigned_to` (`assigned_to`),
  ADD KEY `idx_tasks_status` (`status`),
  ADD KEY `idx_tasks_due_date` (`due_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_assigned` (`assigned_to`),
  ADD KEY `fk_task_project` (`project_id`);

--
-- Indexes for table `task_subtasks`
--
ALTER TABLE `task_subtasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploads_room_created` (`room_id`,`created_at`),
  ADD KEY `idx_uploads_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_index`
--
ALTER TABLE `chatbot_index`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_settings`
--
ALTER TABLE `chatbot_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer_activities`
--
ALTER TABLE `customer_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dm_pairs`
--
ALTER TABLE `dm_pairs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_milestones`
--
ALTER TABLE `project_milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rsc_admin_audit_logs`
--
ALTER TABLE `rsc_admin_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rsc_message_templates`
--
ALTER TABLE `rsc_message_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rsc_permissions`
--
ALTER TABLE `rsc_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `rsc_roles`
--
ALTER TABLE `rsc_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_campaign_recipients`
--
ALTER TABLE `sms_campaign_recipients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_daily_stats`
--
ALTER TABLE `sms_daily_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_event_settings`
--
ALTER TABLE `sms_event_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `task_subtasks`
--
ALTER TABLE `task_subtasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  ADD CONSTRAINT `fk_chatbot_conv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_activities`
--
ALTER TABLE `customer_activities`
  ADD CONSTRAINT `customer_activities_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_activities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dm_pairs`
--
ALTER TABLE `dm_pairs`
  ADD CONSTRAINT `dm_pairs_ibfk_1` FOREIGN KEY (`user_a`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dm_pairs_ibfk_2` FOREIGN KEY (`user_b`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dm_pairs_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_upload` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_milestones`
--
ALTER TABLE `project_milestones`
  ADD CONSTRAINT `fk_milestone_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_members`
--
ALTER TABLE `room_members`
  ADD CONSTRAINT `fk_rm_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rm_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rsc_admin_audit_logs`
--
ALTER TABLE `rsc_admin_audit_logs`
  ADD CONSTRAINT `fk_rsc_audit_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `rsc_chat_settings`
--
ALTER TABLE `rsc_chat_settings`
  ADD CONSTRAINT `fk_rsc_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `rsc_message_templates`
--
ALTER TABLE `rsc_message_templates`
  ADD CONSTRAINT `fk_rsc_tpl_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `rsc_role_permissions`
--
ALTER TABLE `rsc_role_permissions`
  ADD CONSTRAINT `fk_rsc_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `rsc_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rsc_rp_role` FOREIGN KEY (`role_id`) REFERENCES `rsc_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rsc_user_presence`
--
ALTER TABLE `rsc_user_presence`
  ADD CONSTRAINT `fk_rsc_presence_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rsc_user_roles`
--
ALTER TABLE `rsc_user_roles`
  ADD CONSTRAINT `fk_rsc_ur_role` FOREIGN KEY (`role_id`) REFERENCES `rsc_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rsc_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD CONSTRAINT `fk_campaign_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_campaign_template` FOREIGN KEY (`template_id`) REFERENCES `sms_templates` (`id`);

--
-- Constraints for table `sms_campaign_recipients`
--
ALTER TABLE `sms_campaign_recipients`
  ADD CONSTRAINT `fk_recipient_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `sms_campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_event_settings`
--
ALTER TABLE `sms_event_settings`
  ADD CONSTRAINT `fk_event_template` FOREIGN KEY (`template_id`) REFERENCES `sms_templates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD CONSTRAINT `fk_log_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `sms_campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_template` FOREIGN KEY (`template_id`) REFERENCES `sms_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sms_log_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD CONSTRAINT `fk_sms_template_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_task_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `task_subtasks`
--
ALTER TABLE `task_subtasks`
  ADD CONSTRAINT `task_subtasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
