-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2025 at 11:41 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
-- Owner : Haileamlak Gashaw March 28th, 2025

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `credit_management` 
--

-- --------------------------------------------------------

--
-- Table structure for table `credit_limits`
--

CREATE TABLE `credit_limits` (
  `id` int(11) NOT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_payments`
--

CREATE TABLE `credit_payments` (
  `id` int(11) NOT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_reminders`
--

CREATE TABLE `credit_reminders` (
  `id` int(11) NOT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL,
  `hub` varchar(255) NOT NULL,
  `woreda` varchar(255) NOT NULL,
  `Zone` varchar(255) NOT NULL,
  `level` varchar(255) NOT NULL,
  `credit_invoice_number` int(11) NOT NULL,
  `credit_balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `facility_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `region`, `hub`, `woreda`, `Zone`, `level`, `credit_invoice_number`, `credit_balance`, `status`, `created_at`, `updated_at`, `facility_id`) VALUES
(40003454, 'Adaar Woreda\r\n', 'Afar\r\n', 'Semera\r\n', 'Adaar\r\n', 'Zone 1\r\n', 'Woreda Health Office\r\n', 12345, 200000.00, 'active', '2025-03-26 04:31:30', '2025-03-26 05:03:34', NULL),
(40003477, 'Atana Anbose Health c', '', '', '', '', '', 300143611, 672064.00, 'active', '2025-03-26 04:21:08', '2025-03-26 04:21:08', NULL),
(40003491, 'Bieriforo Health center', '', '', '', '', '', 300149232, 633761.00, 'active', '2025-03-26 04:21:08', '2025-03-26 04:21:08', NULL),
(40003495, 'Dabageso Health center', '', '', '', '', '', 300143613, 672063.00, 'active', '2025-03-26 04:21:08', '2025-03-26 04:21:08', NULL),
(40003510, 'Dubti General Hospital', '', '', '', '', '', 95071023, 0.00, 'active', '2025-03-26 04:21:08', '2025-03-26 04:21:08', NULL),
(40003556, 'Kilelu Health center', '', '', '', '', '', 300157886, 707908.00, 'active', '2025-03-26 04:21:08', '2025-03-26 04:21:08', NULL),
(40003576, 'Mohammed Akle Hospital', '', '', '', '', '', 300169822, 0.00, 'active', '2025-03-26 04:21:08', '2025-03-26 04:21:08', NULL),
(40007127, 'Geleha Health Center', '', '', '', '', '', 300137135, 599978.84, 'active', '2025-03-26 03:59:09', '2025-03-26 03:59:09', NULL),
(40007131, 'Jara health center', '', '', '', '', '', 95077093, 0.00, 'active', '2025-03-26 04:06:06', '2025-03-26 04:06:06', NULL),
(40007132, 'Adaitu Health Center', '', '', '', '', '', 300138466, 599977.00, 'active', '2025-03-26 04:21:08', '2025-03-26 04:21:08', NULL),
(40007133, 'Arsis Health center', '', '', '', '', '', 300139310, 599983.00, 'active', '2025-03-26 04:21:08', '2025-03-26 04:21:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_notes`
--

CREATE TABLE `customer_notes` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(50) DEFAULT 'general',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `healthcare_facilities`
--

CREATE TABLE `healthcare_facilities` (
  `id` int(11) NOT NULL,
  `hub_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `facility_type` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hubs`
--

CREATE TABLE `hubs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','other') DEFAULT 'cash',
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `reminder_date` date NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `credit_date` date NOT NULL,
  `due_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `customer_id`, `amount`, `type`, `description`, `status`, `credit_date`, `due_date`, `notes`, `payment_date`, `created_at`, `updated_at`) VALUES
(164, 1, 40007127, 64581.02, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 03:59:09', '2025-03-26 03:59:09'),
(165, 1, 40007127, 0.00, 'credit', NULL, 'paid', '2025-01-11', '0000-00-00', NULL, NULL, '2025-03-26 03:59:09', '2025-03-26 03:59:09'),
(172, 1, 40007131, 42328.55, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:06:06', '2025-03-26 04:06:06'),
(173, 1, 40007131, 0.00, 'credit', NULL, 'paid', '2025-03-14', '0000-00-00', NULL, NULL, '2025-03-26 04:06:06', '2025-03-26 04:06:06'),
(174, 1, 40007132, 57959.02, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(175, 1, 40007132, 0.00, 'credit', NULL, 'paid', '2025-01-11', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(176, 1, 40007133, 72115.27, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(177, 1, 40007133, 0.00, 'credit', NULL, 'paid', '2025-01-11', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(178, 1, 40003576, 348560.09, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(179, 1, 40003576, 0.00, 'credit', NULL, 'paid', '2025-02-18', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(180, 1, 40003510, 145818.67, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(181, 1, 40003510, 0.00, 'credit', NULL, 'paid', '2025-02-20', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(182, 1, 40003491, 65271.78, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(183, 1, 40003491, 0.00, 'credit', NULL, 'paid', '2025-01-28', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(184, 1, 40003556, 63920.03, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(185, 1, 40003556, 0.00, 'credit', NULL, 'paid', '2025-02-08', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(186, 1, 40003495, 46068.38, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(187, 1, 40003495, 0.00, 'credit', NULL, 'paid', '2025-01-17', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(188, 1, 40003477, 40813.05, 'credit', NULL, 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(189, 1, 40003477, 0.00, 'credit', NULL, 'paid', '2025-01-17', '0000-00-00', NULL, NULL, '2025-03-26 04:21:08', '2025-03-26 04:21:08'),
(190, 1, 40003454, 200000.00, 'credit', 'Just a mock credit', 'pending', '2025-03-26', '2025-03-31', NULL, NULL, '2025-03-26 05:03:34', '2025-03-26 05:03:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `credit_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `credit_balance`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$PkUfWGTtSX8PxcMCfuKBdu4ya0VbZFlBXUw2xaN.IGvS9yN9UZtki', 'admin', 0.00, '2025-03-23 21:06:11', '2025-03-26 22:04:12'),
(2, 'testUser', 'testUser@gmail.com', '$2y$10$oy2H9Au5dA.2yOvXBj8/vegDqXk7eIQD1zx/bc0ajOghnQmF51vDO', 'user', 0.00, '2025-03-24 05:50:17', '2025-03-24 05:50:39');-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `theme` varchar(10) DEFAULT 'light',
  `notifications` tinyint(1) DEFAULT 1,
  `language` varchar(2) DEFAULT 'en',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `theme`, `notifications`, `language`, `created_at`, `updated_at`) VALUES
(1, 1, 'light', 1, 'en', '2025-03-23 21:55:44', '2025-03-24 18:36:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `credit_limits`
--
ALTER TABLE `credit_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facility_id` (`facility_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `credit_payments`
--
ALTER TABLE `credit_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_facility_id` (`facility_id`);

--
-- Indexes for table `credit_reminders`
--
ALTER TABLE `credit_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_facility_id` (`facility_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_notes`
--
ALTER TABLE `customer_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `healthcare_facilities`
--
ALTER TABLE `healthcare_facilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hub_id` (`hub_id`);

--
-- Indexes for table `hubs`
--
ALTER TABLE `hubs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_settings` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `credit_limits`
--
ALTER TABLE `credit_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `credit_payments`
--
ALTER TABLE `credit_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `credit_reminders`
--
ALTER TABLE `credit_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40007134;

--
-- AUTO_INCREMENT for table `customer_notes`
--
ALTER TABLE `customer_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `healthcare_facilities`
--
ALTER TABLE `healthcare_facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hubs`
--
ALTER TABLE `hubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=967;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `credit_limits`
--
ALTER TABLE `credit_limits`
  ADD CONSTRAINT `credit_limits_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `healthcare_facilities` (`id`),
  ADD CONSTRAINT `credit_limits_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `credit_payments`
--
ALTER TABLE `credit_payments`
  ADD CONSTRAINT `credit_payments_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `healthcare_facilities` (`id`);

--
-- Constraints for table `credit_reminders`
--
ALTER TABLE `credit_reminders`
  ADD CONSTRAINT `credit_reminders_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `healthcare_facilities` (`id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `healthcare_facilities` (`id`);

--
-- Constraints for table `customer_notes`
--
ALTER TABLE `customer_notes`
  ADD CONSTRAINT `customer_notes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `healthcare_facilities`
--
ALTER TABLE `healthcare_facilities`
  ADD CONSTRAINT `healthcare_facilities_ibfk_1` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
