-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 15, 2025 at 01:33 PM
-- Server version: 8.0.42-0ubuntu0.24.04.2
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dashboard_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `patient_name` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
  `patient_mobile` varchar(20) COLLATE utf8mb4_persian_ci NOT NULL,
  `patient_national_code` varchar(11) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `appointment_time` datetime NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_persian_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `doctor_id`, `patient_name`, `patient_mobile`, `patient_national_code`, `service_id`, `appointment_time`, `status`, `created_at`) VALUES
(1, 1, 'علی بهاری 3', '09015255027', NULL, NULL, '1404-04-26 00:00:00', 'completed', '2025-07-14 06:51:41'),
(2, 1, 'علی بهاری 4', '09015255027', '2284066070', 5, '2025-07-17 00:00:00', 'completed', '2025-07-14 07:21:33'),
(3, 2, 'علی بهاری 55', '09015255039', '2284014011', 5, '2025-07-14 14:30:00', 'completed', '2025-07-14 07:57:55'),
(5, 2, 'علی بهاری 3', '09015255047', '2284014012', 1, '2025-07-14 00:00:00', 'completed', '2025-07-14 09:16:46'),
(6, 1, 'شهریار بیضایی', '09015255027', '2284014013', 3, '2025-07-15 10:30:00', 'completed', '2025-07-14 09:45:44'),
(7, 1, 'علی بهاری 3', '09015255039', '2284066080', 3, '2025-07-15 12:00:00', 'completed', '2025-07-14 12:28:34'),
(8, 2, 'علی بهاری 56', '09015255039', '2284066088', 1, '2025-07-15 12:30:00', 'arrived', '2025-07-14 13:17:11'),
(9, 1, 'علی بهاری 4', '09015255027', '2284014016', 1, '2025-07-16 11:30:00', 'booked', '2025-07-14 13:25:41');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `specialty` varchar(100) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `profile_image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `full_name`, `is_active`, `specialty`, `address`, `profile_image_path`) VALUES
(1, 'علی بهاری', 1, 'تست', 'شیراز', ''),
(2, 'علی بهاری فرد', 1, 'برنامه نویس', 'شیراز', '');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

CREATE TABLE `doctor_availability` (
  `id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `slot_duration` int NOT NULL COMMENT 'مدت زمان به دقیقه'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int NOT NULL DEFAULT '30'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_weekly_schedule`
--

CREATE TABLE `doctor_weekly_schedule` (
  `id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `day_of_week` int NOT NULL COMMENT '0=شنبه, 1=یکشنبه, ..., 6=جمعه',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int NOT NULL DEFAULT '20' COMMENT 'مدت زمان هر نوبت به دقیقه',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `doctor_weekly_schedule`
--

INSERT INTO `doctor_weekly_schedule` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration`, `is_active`) VALUES
(9, 2, 0, '01:15:00', '14:20:00', 8, 1),
(10, 2, 5, '14:03:00', '00:14:00', 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `national_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `marital_status` enum('single','married') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'single',
  `email` varchar(191) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `service_type` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `photo_path1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'img/profile.png',
  `photo_path2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'img/profile.png',
  `photo_path3` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'img/profile.png',
  `photo_path4` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'img/profile.png',
  `photo_path5` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'img/profile.png',
  `photo_path6` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'img/profile.png',
  `note` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `birth_date`, `national_code`, `marital_status`, `email`, `service_type`, `mobile`, `photo_path1`, `photo_path2`, `photo_path3`, `photo_path4`, `photo_path5`, `photo_path6`, `note`, `created_at`, `updated_at`) VALUES
(1, 'علی', 'بهاری', '1404-04-22', '2284066070', 'single', 'alibahari1400@gmail.com', 'ژل لب', '09015255027', 'uploads/2284066070/1752327332_558e0f16-dc23-4b82-af91-d85d7e804a3d.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', '', '2025-07-12 13:08:14', '2025-07-12 13:35:32'),
(6, 'علی', 'بهاری 4', '2025-07-17', '2284066075', 'single', NULL, NULL, '09015255027', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'پرونده تشکیل شده از نوبت با دکتر علی بهاری', '2025-07-14 08:16:44', '2025-07-14 08:16:44'),
(10, 'علی', 'بهاری 55', '2025-06-26', '2284014011', 'single', NULL, NULL, '09015255039', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'پرونده تشکیل شده از نوبت با دکتر علی بهاری فرد', '2025-07-14 11:56:33', '2025-07-14 11:56:33'),
(11, 'علی', 'بهاری 3', '2025-06-24', '2284066080', 'single', NULL, NULL, '09015255039', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'پرونده تشکیل شده از نوبت با دکتر علی بهاری', '2025-07-14 12:34:31', '2025-07-14 12:34:31'),
(12, 'علی', 'بهاری', '2025-06-22', '2284066090', 'single', NULL, NULL, '09015255027', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'img/profile.png', 'پزشک معالج: علی بهاری فرد\nسلام ', '2025-07-14 12:36:24', '2025-07-14 12:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `employee_costs`
--

CREATE TABLE `employee_costs` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(13,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_costs`
--

INSERT INTO `employee_costs` (`id`, `employee_id`, `item_name`, `quantity`, `price`, `created_at`) VALUES
(5, 1, 'پانسمان', 1, 225000.00, '2025-07-13 13:01:08'),
(6, 1, 'بخیه', 1, 30000.00, '2025-07-13 13:01:15'),
(7, 6, 'بوتاکس کف دست', 1, 8000000.00, '2025-07-14 08:16:44'),
(13, 11, 'مزوتراپی مو', 1, 15500000.00, '2025-07-14 12:34:31'),
(14, 12, 'بوتاکس پیشانی', 1, 1500000.00, '2025-07-14 12:36:24'),
(15, 12, 'فیلر لب (هر سی‌سی)', 1, 2500000.00, '2025-07-14 12:36:24'),
(16, 12, 'بخیه', 1, 1745664948.00, '2025-07-14 12:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `ip_address`, `login_time`) VALUES
(3, 3, '127.0.0.1', '2025-07-12 10:49:38'),
(4, 3, '127.0.0.1', '2025-07-12 10:50:57'),
(5, 3, '127.0.0.1', '2025-07-12 10:54:01'),
(6, 3, '127.0.0.1', '2025-07-12 10:56:46'),
(7, 3, '127.0.0.1', '2025-07-12 11:33:34'),
(8, 3, '127.0.0.1', '2025-07-13 05:41:42'),
(9, 3, '127.0.0.1', '2025-07-13 12:00:25'),
(10, 3, '127.0.0.1', '2025-07-13 12:23:29'),
(11, 3, '127.0.0.1', '2025-07-13 12:25:05'),
(12, 3, '127.0.0.1', '2025-07-13 12:28:43'),
(13, 3, '127.0.0.1', '2025-07-13 12:29:13'),
(14, 3, '127.0.0.1', '2025-07-13 12:42:53'),
(15, 3, '127.0.0.1', '2025-07-13 13:03:02'),
(16, 3, '127.0.0.1', '2025-07-14 04:51:53'),
(17, 3, '127.0.0.1', '2025-07-14 10:56:42'),
(18, 3, '127.0.0.1', '2025-07-14 11:53:38'),
(19, 3, '192.168.1.135', '2025-07-14 12:27:57'),
(20, 3, '127.0.0.1', '2025-07-15 04:43:22'),
(21, 3, '127.0.0.1', '2025-07-15 05:46:14');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `default_price` decimal(10,0) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `default_price`, `is_active`, `created_at`) VALUES
(1, 'بوتاکس پیشانی', 1500000, 1, '2025-07-13 07:10:46'),
(2, 'فیلر لب (هر سی‌سی)', 2500000, 1, '2025-07-13 07:10:46'),
(3, 'مزوتراپی مو', 15500000, 1, '2025-07-13 07:10:46');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('clinic_name', 'کلینیک روزا'),
('clinic_phone', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `full_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profile_picture_url` varchar(2048) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `registration_date`, `full_name`, `profile_picture_url`) VALUES
(3, 'alibahari', '$2y$10$jmriXQVHMffCAimzqHAn9O3b7jaiNqzpc3jr0.FgTp8he00N4eD6e', '2025-07-12 10:47:50', '2025-07-12 15:27:10', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_appointment` (`doctor_id`,`appointment_time`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_weekly_schedule`
--
ALTER TABLE `doctor_weekly_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `national_code` (`national_code`);

--
-- Indexes for table `employee_costs`
--
ALTER TABLE `employee_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_weekly_schedule`
--
ALTER TABLE `doctor_weekly_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `employee_costs`
--
ALTER TABLE `employee_costs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_weekly_schedule`
--
ALTER TABLE `doctor_weekly_schedule`
  ADD CONSTRAINT `doctor_weekly_schedule_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_costs`
--
ALTER TABLE `employee_costs`
  ADD CONSTRAINT `employee_costs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
