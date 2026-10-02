-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 15, 2026 at 09:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aqualarion_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$8QiRHOWBi3YsEuSW5ML/UOjaT4Bi6kNiRvydrccSew3GxJASd6Wtm', '2026-07-15 01:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `water_diagnostic_id` int(11) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `details` text DEFAULT NULL,
  `severity` enum('warning','danger') NOT NULL DEFAULT 'warning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `water_diagnostic_id`, `title`, `details`, `severity`, `created_at`) VALUES
(1, 5, 'Unsafe water detected', 'pH 5.80 below safe range. Turbidity 7.20 NTU above threshold.', 'danger', '2026-07-15 01:05:49'),
(2, 4, 'Low battery warning', 'Battery dropped to 18% — solar output reduced due to overcast.', 'warning', '2026-07-15 01:05:49');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('in_progress','done') NOT NULL DEFAULT 'in_progress',
  `scheduled_for` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`id`, `title`, `details`, `status`, `scheduled_for`, `created_at`) VALUES
(2, 'Sensor calibration', 'Re-calibrate pH and turbidity sensors after firmware update.', 'done', '2026-07-17 09:05:49', '2026-07-15 01:05:49'),
(3, 'Solar panel cleaning', 'Clean dust and debris from solar panels on rooftop.', 'done', '2026-07-22 09:05:49', '2026-07-15 01:05:49'),
(4, 'Water Filtration Replacement', 'Replace old water filters to ensure clean, safe water flow.', 'in_progress', '2026-07-14 09:44:00', '2026-07-15 01:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `severity` enum('info','warning','danger') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `student_id`, `title`, `message`, `severity`, `is_read`, `created_at`) VALUES
(1, 1, 'Water quality update', 'Latest diagnostics show all parameters are within safe range.', 'info', 0, '2026-07-15 01:05:49'),
(2, 1, 'Unsafe water alert', 'Water was flagged as UNSAFE on the latest reading. Avoid drinking until cleared.', 'danger', 0, '2026-07-15 01:05:49'),
(3, 2, 'System maintenance', 'Scheduled sensor calibration on July 12. Readings may be temporarily unavailable.', 'warning', 0, '2026-07-15 01:05:49'),
(4, 1, 'Water cleared', 'Water quality has returned to safe levels after maintenance.', 'info', 0, '2026-07-15 01:05:49');

-- --------------------------------------------------------

--
-- Table structure for table `solar_battery`
--

CREATE TABLE `solar_battery` (
  `id` int(11) NOT NULL,
  `solar_voltage` decimal(5,2) DEFAULT NULL,
  `solar_current` decimal(5,2) DEFAULT NULL,
  `solar_power_w` decimal(7,2) DEFAULT NULL,
  `battery_level` tinyint(3) UNSIGNED DEFAULT NULL,
  `battery_voltage` decimal(5,2) DEFAULT NULL,
  `battery_status` enum('normal','low','critical','charging','full') NOT NULL DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `solar_battery`
--

INSERT INTO `solar_battery` (`id`, `solar_voltage`, `solar_current`, `solar_power_w`, `battery_level`, `battery_voltage`, `battery_status`, `created_at`) VALUES
(1, 18.50, 2.10, 38.85, 76, 12.40, 'normal', '2026-07-12 01:05:49'),
(2, 17.80, 1.90, 33.82, 65, 12.10, 'normal', '2026-07-13 01:05:49'),
(3, 19.20, 2.30, 44.16, 82, 12.60, 'normal', '2026-07-14 01:05:49'),
(4, 12.50, 0.80, 10.00, 18, 11.20, 'low', '2026-07-14 19:05:49'),
(5, 20.10, 2.50, 50.25, 90, 13.00, 'charging', '2026-07-15 01:05:49');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `sr_code` varchar(64) NOT NULL,
  `portal_code` varchar(64) NOT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `school_name` varchar(120) DEFAULT 'Batangas State University - Nasugbu Campus',
  `password_hash` varchar(255) NOT NULL,
  `google_email` varchar(180) DEFAULT NULL,
  `google_id` varchar(64) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `sr_code`, `portal_code`, `full_name`, `school_name`, `password_hash`, `google_email`, `google_id`, `avatar_url`, `is_active`, `created_at`) VALUES
(1, '23-79538', '23-79538', 'Rea Casinillo', 'Batangas State University - Nasugbu Campus', '$2y$10$kW0Js7CCXx7IK1iLOUZkN.vXhLxq5YPx1Z6yOE0zPTO8jKdHood42', NULL, NULL, NULL, 1, '2026-07-15 01:05:48'),
(2, 'SR-1002', 'PORT-2002', 'Maria Santos', 'Batangas State University - Nasugbu Campus', '$2y$10$kW0Js7CCXx7IK1iLOUZkN.vXhLxq5YPx1Z6yOE0zPTO8jKdHood42', NULL, NULL, NULL, 1, '2026-07-15 01:05:48'),
(3, 'SR-1003', 'PORT-2003', 'Pedro Reyes', 'Batangas State University - Nasugbu Campus', '$2y$10$kW0Js7CCXx7IK1iLOUZkN.vXhLxq5YPx1Z6yOE0zPTO8jKdHood42', NULL, NULL, NULL, 0, '2026-07-15 01:05:48'),
(5, '23-71069', '23-71069', 'Angelito Calisnao', 'Batangas State University - Nasugbu Campus', 'placeholder_hash', NULL, NULL, NULL, 1, '2026-07-15 02:23:17'),
(6, '23-78570', '23-78570', 'Francis Cupo', 'Batangas State University - Nasugbu Campus', '$2y$10$kW0Js7CCXx7IK1iLOUZkN.vXhLxq5YPx1Z6yOE0zPTO...', NULL, NULL, NULL, 1, '2026-07-15 02:55:57');

-- --------------------------------------------------------

--
-- Table structure for table `water_diagnostics`
--

CREATE TABLE `water_diagnostics` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `ph` decimal(5,2) DEFAULT NULL,
  `turbidity` decimal(6,2) DEFAULT NULL,
  `tds` decimal(8,2) DEFAULT NULL,
  `temperature_c` decimal(5,2) DEFAULT NULL,
  `fluorescence` decimal(8,2) DEFAULT NULL,
  `safe_status` enum('safe','unsafe') NOT NULL DEFAULT 'safe',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `water_diagnostics`
--

INSERT INTO `water_diagnostics` (`id`, `student_id`, `ph`, `turbidity`, `tds`, `temperature_c`, `fluorescence`, `safe_status`, `created_at`) VALUES
(1, 6, 7.20, 1.80, 320.00, 26.50, 450.00, 'safe', '2026-07-10 01:05:48'),
(2, 1, 7.50, 2.10, 340.00, 27.00, 480.00, 'safe', '2026-07-11 01:05:48'),
(3, 1, 6.80, 3.50, 410.00, 25.80, 520.00, 'safe', '2026-07-12 01:05:48'),
(4, 2, 7.10, 1.50, 290.00, 26.00, 400.00, 'safe', '2026-07-13 01:05:48'),
(5, 5, 9.80, 7.20, 620.00, 28.50, 1850.00, 'unsafe', '2026-07-14 01:05:48'),
(6, 2, 7.30, 2.00, 310.00, 26.20, 430.00, 'safe', '2026-07-15 01:05:48'),
(7, 1, 7.20, 1.80, 320.00, 26.50, 450.00, 'safe', '2026-07-15 02:15:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alert_severity` (`severity`),
  ADD KEY `idx_alert_created` (`created_at`),
  ADD KEY `fk_alert_diag` (`water_diagnostic_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_maint_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_student` (`student_id`),
  ADD KEY `idx_notif_created` (`created_at`);

--
-- Indexes for table `solar_battery`
--
ALTER TABLE `solar_battery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solar_created` (`created_at`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sr_code` (`sr_code`),
  ADD UNIQUE KEY `portal_code` (`portal_code`),
  ADD UNIQUE KEY `google_email` (`google_email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_students_active` (`is_active`),
  ADD KEY `idx_students_google` (`google_email`);

--
-- Indexes for table `water_diagnostics`
--
ALTER TABLE `water_diagnostics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diag_student` (`student_id`),
  ADD KEY `idx_diag_status` (`safe_status`),
  ADD KEY `idx_diag_created` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `solar_battery`
--
ALTER TABLE `solar_battery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `water_diagnostics`
--
ALTER TABLE `water_diagnostics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `fk_alert_diag` FOREIGN KEY (`water_diagnostic_id`) REFERENCES `water_diagnostics` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `water_diagnostics`
--
ALTER TABLE `water_diagnostics`
  ADD CONSTRAINT `fk_water_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
