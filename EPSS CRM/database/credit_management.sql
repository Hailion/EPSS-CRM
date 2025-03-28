-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2025 at 06:55 AM
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
(40003441, 'Alelesubula Health Center', 'Afar', 'Semera', 'Ewa', 'Zone 4', 'Health Center', 40003441, 7400.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003445, 'Fokissa Health Center', 'Afar', 'Semera', 'Gulina', 'Zone 4', 'Health Center', 40003445, 9600.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003449, 'Abaala Health Center', 'Afar', 'Semera', 'Abaala Town', 'Zone 2', 'Health Center', 40003449, 8900.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003450, 'Abaala hospital', 'Afar', 'Semera', 'Abaala', 'Zone 2', 'Primary Hospital', 40003450, 29000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003451, 'Abaala Woreda', 'Afar', 'Semera', 'Abaala', 'Zone 2', 'Woreda Health Office', 40003451, 17500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003452, 'Adea Health Center', 'Afar', 'Semera', 'Berahle', 'Zone 2', 'Health Center', 40003452, 9400.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003454, 'Adaar Woreda', 'Afar', 'Semera', 'Adaar', 'Zone 1', 'Woreda Health Office', 40003454, 15000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003455, 'Adaitu health center', 'Afar', 'Semera', 'mile', 'Zone 1', 'Health Center', 40003455, 8300.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003457, 'Afambo Health Center', 'Afar', 'Semera', 'Afambo', 'Zone 1', 'Health Center', 40003457, 12300.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003465, 'Afdera Woreda Health Office', 'Afar', 'Semera', 'Afdera', 'Zone 2', 'Woreda Health Office', 40003465, 18800.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003469, 'Aligenda Health Center', 'Afar', 'Semera', 'Afdera', 'Zone 2', 'Health Center', 40003469, 10100.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003470, 'Alasabolo Health Center', 'Afar', 'Semera', 'Afambo', 'Zone 1', 'Health Center', 40003470, 9750.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003474, 'Asaita hospital', 'Afar', 'Semera', 'Asaita', 'Zone 1', 'Primary Hospital', 40003474, 41000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003475, 'Aysaita Woreda Health Office', 'Afar', 'Semera', 'Asayita', 'Zone 1', 'Woreda Health Office', 40003475, 18000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003480, 'Awra Woreda', 'Afar', 'Semera', 'Awra', 'Zone 4', 'Woreda Health Office', 40003480, 15700.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003486, 'Berahle hospital', 'Afar', 'Semera', 'Berahle', 'Zone 2', 'Primary Hospital', 40003486, 34000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003487, 'Berahlea Woreda', 'Afar', 'Semera', 'Berahle', 'Zone 2', 'Woreda Health Office', 40003487, 20500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003488, 'Berga Health Center', 'Afar', 'Semera', 'Asaita Town', 'Zone 1', 'Health Center', 40003488, 11200.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003489, 'Bidu Woreda', 'Afar', 'Semera', 'Bidu', 'Zone 2', 'Woreda Health Office', 40003489, 16200.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003491, 'Briforo Health Center', 'Afar', 'Semera', 'Abida', 'Zone 3', 'Health Center', 40003491, 10500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003492, 'Burea Health Center', 'Afar', 'Semera', 'Elidear', 'Zone 1', 'Health Center', 40003492, 14500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003493, 'Chifra hospital', 'Afar', 'Semera', 'Chifra', 'Zone 1', 'Primary Hospital', 40003493, 35000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003496, 'Dabal Health Center', 'Afar', 'Semera', 'Gelealo', 'Zone 3', 'Health Center', 40003496, 8200.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003497, 'Dalfage hospital', 'Afar', 'Semera', 'Dalfage', 'Zone 5', 'Primary Hospital', 40003497, 37000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003498, 'Dalfage woreda', 'Afar', 'Semera', 'dalfage', 'Zone 5', 'Woreda Health Office', 40003498, 16500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003500, 'Dersagita Health Center', 'Afar', 'Semera', 'Adaar', 'Zone 1', 'Health Center', 40003500, 8500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003501, 'Dewe Woreda', 'Afar', 'Semera', 'Dewe', 'Zone 5', 'Woreda Health Office', 40003501, 18900.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003502, 'Deraytu Health Center', 'Afar', 'Semera', 'Awra', 'Zone 4', 'Health Center', 40003502, 9100.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003504, 'Dibina Health Center', 'Afar', 'Semera', 'Yallo', 'Zone 4', 'Health Center', 40003504, 10300.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003505, 'Dicheto Health Center', 'Afar', 'Semera', 'Elidear', 'Zone 1', 'Health Center', 40003505, 10200.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003508, 'Manada Health Center', 'Afar', 'Semera', 'Elidear', 'Zone 1', 'Health Center', 40003508, 12700.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003509, 'Deroesi Health Center', 'Afar', 'Semera', 'Telalak', 'Zone 5', 'Health Center', 40003509, 9300.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003510, 'Chifra hospital', 'Afar', 'Semera', 'Dubti', 'Zone 1', 'General Hospital', 40003510, 45000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003512, 'Dubti Health Center', 'Afar', 'Semera', 'Dubti Town', 'Zone 1', 'Health Center', 40003512, 7800.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003514, 'Dubti Woreda Health Office', 'Afar', 'Semera', 'Dubti', 'Zone 1', 'Woreda Health Office', 40003514, 21000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003516, 'Elidear Health Center', 'Afar', 'Semera', 'Elidear', 'Zone 1', 'Health Center', 40003516, 8800.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003518, 'Eliwuha health center', 'Afar', 'Semera', 'Adaar', 'Zone 1', 'Health Center', 40003518, 7200.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003521, 'Erebti Woreda', 'Afar', 'Semera', 'Erebti', 'Zone 2', 'Woreda Health Office', 40003521, 19200.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003523, 'Ewa Woreda', 'Afar', 'Semera', 'Ewa', 'Zone 4', 'Woreda Health Office', 40003523, 16900.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003525, 'Semera Family Guidance', 'Afar', 'Semera', 'Semera/Logia City', 'Zone 1', 'Non Govermental Organization', 40003525, 5000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003531, 'Galikoma Health Center', 'Afar', 'Semera', 'Gulina', 'Zone 4', 'Health Center', 40003531, 11900.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003532, 'Gelaalo Health Center', 'Afar', 'Semera', 'Gelealo', 'Zone 3', 'Health Center', 40003532, 12400.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003538, 'Gewane hospital', 'Afar', 'Semera', 'Gewane', 'Zone 3', 'Primary Hospital', 40003538, 32000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003541, 'Guyah Health Center', 'Afar', 'Semera', 'Kori', 'Zone 1', 'Health Center', 40003541, 9500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003542, 'Gubidora Health Center', 'Afar', 'Semera', 'Yallo', 'Zone 4', 'Health Center', 40003542, 8600.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003545, 'Hamedelela Health Center', 'Afar', 'Semera', 'Berahle', 'Zone 2', 'Health Center', 40003545, 11300.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003547, 'Harsis Health center', 'Afar', 'Semera', 'Mille', 'Zone 1', 'Health Center', 40003547, 7600.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003549, 'Hida Health Center', 'Afar', 'Semera', 'Awra', 'Zone 4', 'Health Center', 40003549, 10800.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003552, 'Jara dalifage (amuli) Health Center', 'Afar', 'Semera', 'Dalifage', 'Zone 5', 'Health Center', 40003552, 7900.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003553, 'Chifra jara Health Center', 'Afar', 'Semera', 'Chifra', 'Zone 1', 'Health Center', 40003553, 6800.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003554, 'Kelwan hospital', 'Afar', 'Semera', 'Kelwan', 'Zone 4', 'Primary Hospital', 40003554, 38000.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003558, 'Kori Health Center', 'Afar', 'Semera', 'Kori', 'Zone 1', 'Health Center', 40003558, 11500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003561, 'Kunoba Woreda', 'Afar', 'Semera', 'Koneba', 'Zone 2', 'Woreda Health Office', 40003561, 16700.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003566, 'Logiya Health center', 'Afar', 'Semera', 'Semera/Logia City', 'Zone 1', 'Health Center', 40003566, 12500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003569, 'Megale Woreda', 'Afar', 'Semera', 'Megale', 'Zone 2', 'Woreda Health Office', 40003569, 18300.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003570, 'Megenta Health Center', 'Afar', 'Semera', 'Gereni', 'Zone 6', 'Health Center', 40003570, 8400.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003572, 'Mesgid Health Center', 'Afar', 'Semera', 'Chifra', 'Zone 1', 'Health Center', 40003572, 15300.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003573, 'Mille Health Center', 'Afar', 'Semera', 'Mille Town', 'Zone 1', 'Health Center', 40003573, 10700.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003574, 'Mille Woreda Health Office', 'Afar', 'Semera', 'Mille', 'Zone 1', 'Woreda Health Office', 40003574, 19500.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003584, 'Semera Health Center', 'Afar', 'Semera', 'Semera/Logia City', 'Zone 1', 'Health Center', 40003584, 14200.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003588, 'Shahigubi Health Center', 'Afar', 'Semera', 'Berahle', 'Zone 2', 'Health Center', 40003588, 8700.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003592, 'Telalak Health Center', 'Afar', 'Semera', 'Telalak', 'Zone 5', 'Health Center', 40003592, 11100.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003593, 'Telalak Woreda', 'Afar', 'Semera', 'Telalak', 'Zone 5', 'Woreda Health Office', 40003593, 17600.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003594, 'Teru Woreda', 'Afar', 'Semera', 'Teru', 'Zone 4', 'Woreda Health Office', 40003594, 18100.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003597, 'Waama Health Center', 'Afar', 'Semera', 'Chifra', 'Zone 1', 'Health Center', 40003597, 9200.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003598, 'Wassama Health Center', 'Afar', 'Semera', 'Wasama', 'Zone 2', 'Health Center', 40003598, 9900.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003600, 'Wedragea Health Center', 'Afar', 'Semera', 'Dawe', 'Zone 5', 'Health Center', 40003600, 11700.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40003601, 'Yallo Woreda', 'Afar', 'Semera', 'Yallo', 'Zone 4', 'Woreda Health Office', 40003601, 17400.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL),
(40010016, 'Dalol Woreda', 'Afar', 'Semera', 'Dalol', 'Zone 2', 'Woreda Health Office', 40010016, 17800.00, 'active', '2025-03-28 05:49:57', '2025-03-28 05:49:57', NULL);

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
(260, 1, 40003454, 15000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(261, 1, 40003500, 8500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(262, 1, 40003518, 7200.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(263, 1, 40003457, 12300.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(264, 1, 40003470, 9750.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(265, 1, 40003475, 18000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(266, 1, 40003488, 11200.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(267, 1, 40003553, 6800.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(268, 1, 40003572, 15300.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(269, 1, 40003597, 9200.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(270, 1, 40003514, 21000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(271, 1, 40003512, 7800.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(272, 1, 40003492, 14500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(273, 1, 40003505, 10200.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(274, 1, 40003516, 8800.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(275, 1, 40003508, 12700.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(276, 1, 40003541, 9500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(277, 1, 40003558, 11500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(278, 1, 40003455, 8300.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(279, 1, 40003574, 19500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(280, 1, 40003573, 10700.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(281, 1, 40003547, 7600.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(282, 1, 40003584, 14200.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(283, 1, 40003451, 17500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(284, 1, 40003449, 8900.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(285, 1, 40003465, 18800.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(286, 1, 40003469, 10100.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(287, 1, 40003452, 9400.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(288, 1, 40003487, 20500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(289, 1, 40003545, 11300.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(290, 1, 40003588, 8700.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(291, 1, 40003489, 16200.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(292, 1, 40010016, 17800.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(293, 1, 40003521, 19200.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(294, 1, 40003561, 16700.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(295, 1, 40003569, 18300.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(296, 1, 40003598, 9900.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(297, 1, 40003491, 10500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(298, 1, 40003496, 8200.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(299, 1, 40003532, 12400.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(300, 1, 40003480, 15700.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(301, 1, 40003502, 9100.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(302, 1, 40003549, 10800.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(303, 1, 40003441, 7400.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(304, 1, 40003523, 16900.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(305, 1, 40003445, 9600.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(306, 1, 40003531, 11900.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(307, 1, 40003594, 18100.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(308, 1, 40003504, 10300.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(309, 1, 40003542, 8600.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(310, 1, 40003601, 17400.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(311, 1, 40003498, 16500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(312, 1, 40003552, 7900.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(313, 1, 40003600, 11700.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(314, 1, 40003501, 18900.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(315, 1, 40003509, 9300.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(316, 1, 40003592, 11100.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(317, 1, 40003593, 17600.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(318, 1, 40003570, 8400.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(319, 1, 40003493, 35000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(320, 1, 40003538, 32000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(321, 1, 40003554, 38000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(322, 1, 40003450, 29000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(323, 1, 40003474, 41000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(324, 1, 40003497, 37000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(325, 1, 40003486, 34000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(326, 1, 40003566, 12500.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(327, 1, 40003525, 5000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57'),
(328, 1, 40003510, 45000.00, 'credit', 'Initial credit import', 'pending', '0000-00-00', '0000-00-00', NULL, NULL, '2025-03-28 05:49:57', '2025-03-28 05:49:57');

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
(1, 'admin', 'admin@example.com', '$2y$10$PkUfWGTtSX8PxcMCfuKBdu4ya0VbZFlBXUw2xaN.IGvS9yN9UZtki', 'admin', 0.00, '2025-03-23 21:06:11', '2025-03-27 23:35:32'),
(2, 'endalk', 'admin@gmail.com', '$2y$10$u8CHfOB9NZNv1QlbXjsl6ulv5dcS5er8tZ/P/fputW82QC9sUjD66', 'user', 0.00, '2025-03-23 22:48:59', '2025-03-24 05:38:53'),
(3, 'testUser', 'testUser@gmail.com', '$2y$10$oy2H9Au5dA.2yOvXBj8/vegDqXk7eIQD1zx/bc0ajOghnQmF51vDO', 'user', 0.00, '2025-03-24 05:50:17', '2025-03-24 05:50:39');

-- --------------------------------------------------------

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
(1, 1, 'light', 1, 'en', '2025-03-23 21:55:44', '2025-03-28 02:09:56'),
(2, 2, 'light', 1, 'en', '2025-03-23 22:49:21', '2025-03-23 22:49:21'),
(966, 3, 'light', 1, 'en', '2025-03-24 05:50:39', '2025-03-24 05:50:39');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40010017;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=329;

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
