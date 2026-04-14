-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 03:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `homeservices`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `verified_phone` varchar(20) DEFAULT NULL,
  `status` enum('pending','accepted','in_progress','completed','cancelled') DEFAULT 'pending',
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `provider_id`, `scheduled_date`, `scheduled_time`, `address`, `notes`, `verified_phone`, `status`, `cancellation_reason`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '2026-03-12', '08:00:00', 'Songadh Taluka, Tapi, Gujarat, India', 'cncncn', NULL, 'completed', NULL, '2026-03-05 13:59:54', '2026-03-05 14:01:16'),
(2, 2, 1, '2026-03-20', '10:00:00', 'Daman, Valsad, Dadra and Nagar Haveli and Daman and Diu, 396210, India', 'hdhdh', NULL, 'completed', NULL, '2026-03-05 15:25:34', '2026-03-05 15:26:05'),
(3, 2, 1, '2026-03-13', '11:00:00', 'Thaltej, Ghatlodiya Taluka, Ahmedabad, Gujarat, 380059, India', 'test1', NULL, 'completed', NULL, '2026-03-05 16:45:19', '2026-03-05 16:45:51'),
(4, 2, 1, '2026-03-19', '09:00:00', 'Surat, Katargam Taluka, Surat, Gujarat, 395004, India', '', NULL, 'completed', NULL, '2026-03-07 13:52:54', '2026-03-07 13:53:17'),
(5, 2, 1, '2026-03-13', '13:00:00', 'Daman, Valsad, Dadra and Nagar Haveli and Daman and Diu, 396210, India', '', NULL, 'completed', NULL, '2026-03-07 16:41:58', '2026-03-07 16:42:23'),
(6, 2, 1, '2026-03-19', '14:00:00', 'Daman, Valsad, Dadra and Nagar Haveli and Daman and Diu, 396210, India', '', NULL, 'completed', NULL, '2026-03-07 16:54:06', '2026-03-07 16:54:25'),
(7, 2, 1, '2026-03-12', '13:00:00', 'Thaltej, Ghatlodiya Taluka, Ahmedabad, Gujarat, 380059, India', '', NULL, 'completed', NULL, '2026-03-08 14:14:29', '2026-03-08 14:14:52'),
(8, 2, 1, '2026-03-12', '09:00:00', 'Thaltej, Ghatlodiya Taluka, Ahmedabad, Gujarat, 380059, India', '', NULL, 'completed', NULL, '2026-03-08 14:23:22', '2026-03-08 14:23:46'),
(9, 2, 1, '2026-03-10', '14:00:00', 'SH15, Bilimora, Gandevi Taluka, Navsari, Gujarat, 396380, India', 'test1', NULL, 'completed', NULL, '2026-03-09 07:26:03', '2026-03-09 07:29:47'),
(10, 2, 1, '2026-03-11', '12:00:00', 'test', '', NULL, 'completed', NULL, '2026-03-09 07:48:04', '2026-03-09 07:49:16'),
(11, 7, 5, '2026-03-12', '18:00:00', 'Bilimora, Gandevi Taluka, Navsari, Gujarat, 396321, India', '', NULL, 'accepted', NULL, '2026-03-11 13:00:28', '2026-03-11 13:00:54'),
(12, 7, 5, '2026-03-12', '18:00:00', 'Bilimora, Gandevi Taluka, Navsari, Gujarat, 396321, India', 'test', NULL, 'cancelled', '', '2026-03-12 15:16:43', '2026-03-12 17:24:56'),
(13, 7, 6, '2026-03-13', '10:00:00', 'Bilimora, Gandevi Taluka, Navsari, Gujarat, 396321, India', 'test', NULL, 'completed', NULL, '2026-03-12 17:24:45', '2026-03-12 17:25:59'),
(14, 7, 6, '2026-03-14', '12:00:00', 'LMP School/College, SH15, Bilimora, Gandevi Taluka, Navsari, Gujarat, 396380, India', 'test final', NULL, 'completed', NULL, '2026-03-13 09:01:47', '2026-03-13 09:02:06'),
(15, 7, 6, '2026-03-14', '16:00:00', 'Jantanagar -Ramol, Maninagar Taluka, Ahmedabad, Gujarat, 382418, India', 'test', NULL, 'completed', NULL, '2026-03-14 08:00:06', '2026-03-14 08:39:06'),
(16, 7, 6, '2026-03-14', '18:00:00', 'Jantanagar -Ramol, Maninagar Taluka, Ahmedabad, Gujarat, 382418, India', 'test', NULL, 'cancelled', 'Rejected by provider', '2026-03-14 08:45:38', '2026-03-14 08:45:58'),
(17, 7, 6, '2026-03-19', '18:00:00', 'Manek Chowk, Ahmedabad, Gujarat, 380001, India', 'test vidhen', NULL, 'completed', NULL, '2026-03-15 09:09:41', '2026-03-15 09:10:15'),
(18, 7, 6, '2026-03-17', '15:00:00', 'SH15, Bilimora, Gandevi Taluka, Navsari, Gujarat, 396380, India', 'TEST', NULL, 'completed', NULL, '2026-03-16 08:29:25', '2026-03-16 08:31:03'),
(19, 7, 6, '2026-04-01', '10:00:00', 'Bilimora, Gandevi Taluka, Navsari, Gujarat, 396321, India', 'testing', NULL, 'completed', NULL, '2026-03-26 06:19:28', '2026-03-26 06:27:01'),
(20, 7, 6, '2026-04-04', '10:00:00', 'Bilimora, Gandevi Taluka, Navsari, Gujarat, 396321, India', 'test v2', NULL, 'cancelled', 'test v2', '2026-04-03 09:20:18', '2026-04-03 09:27:51'),
(21, 7, 6, '2026-04-04', '10:00:00', 'Shahibaug, Asarva Taluka, Ahmedabad, Gujarat, 380004, India', 'test v2', NULL, 'in_progress', NULL, '2026-04-03 09:28:26', '2026-04-03 09:36:17');

-- --------------------------------------------------------

--
-- Table structure for table `booking_services`
--

CREATE TABLE `booking_services` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `service_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`service_price` * `quantity`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_services`
--

INSERT INTO `booking_services` (`id`, `booking_id`, `service_id`, `service_name`, `service_price`, `quantity`) VALUES
(2, 1, 3, 'Drain Unclogging', 800.00, 1),
(4, 2, 3, 'Drain Unclogging', 800.00, 1),
(6, 3, 3, 'Drain Unclogging', 800.00, 1),
(8, 4, 3, 'Drain Unclogging', 800.00, 1),
(10, 5, 3, 'Drain Unclogging', 800.00, 1),
(12, 6, 3, 'Drain Unclogging', 1.00, 1),
(14, 7, 3, 'Drain Unclogging', 1.00, 1),
(16, 8, 3, 'Drain Unclogging', 1.00, 1),
(18, 9, 3, 'Drain Unclogging', 1.00, 1),
(19, 9, 1, 'test', 0.10, 1),
(21, 10, 3, 'Drain Unclogging', 1.00, 1),
(22, 11, 3, 'Drain Unclogging', 1.00, 1),
(23, 12, 3, 'Drain Unclogging', 1.00, 1),
(25, 13, 3, 'Drain Unclogging', 1.00, 1),
(27, 14, 3, 'Drain Unclogging', 1.00, 1),
(29, 15, 3, 'Drain Unclogging', 1.00, 1),
(30, 16, 3, 'Drain Unclogging', 1.00, 1),
(32, 17, 3, 'Drain Unclogging', 1.00, 1),
(34, 18, 3, 'Drain Unclogging', 1.00, 1),
(36, 19, 3, 'Drain Unclogging', 1.00, 1),
(37, 19, 1, 'test of additional services*', 1.00, 1),
(38, 20, 3, 'Drain Unclogging', 1.00, 1),
(39, 21, 3, 'Drain Unclogging', 1.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bi-tools',
  `color` varchar(20) DEFAULT '#0d6efd',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `color`, `status`, `created_at`) VALUES
(1, 'Plumbing', 'Pipe repairs, installations, and maintenance', 'bi-droplet-fill', '#0d6efd', 'active', '2026-03-05 13:50:57'),
(2, 'Electrical', 'Wiring, installations, and repairs', 'bi-lightning-charge-fill', '#ffc107', 'active', '2026-03-05 13:50:57'),
(3, 'Cleaning', 'Home and office cleaning services', 'bi-stars', '#20c997', 'active', '2026-03-05 13:50:57'),
(4, 'Carpentry', 'Furniture, cabinets, and woodwork', 'bi-hammer', '#6f42c1', 'active', '2026-03-05 13:50:57'),
(5, 'Painting', 'Interior and exterior painting', 'bi-brush-fill', '#fd7e14', 'active', '2026-03-05 13:50:57'),
(6, 'Gardening', 'Lawn care and landscaping', 'bi-flower1', '#198754', 'active', '2026-03-05 13:50:57'),
(7, 'AC Repair', 'Air conditioning installation and repair', 'bi-wind', '#0dcaf0', 'active', '2026-03-05 13:50:57'),
(8, 'Security', 'CCTV, locks, and security systems', 'bi-shield-lock-fill', '#dc3545', 'active', '2026-03-05 13:50:57');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `invoice_number` varchar(30) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL,
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `booking_id`, `invoice_number`, `subtotal`, `tax_rate`, `tax_amount`, `grand_total`, `payment_status`, `generated_at`) VALUES
(1, 1, 'INV-69A98CAC777B0', 800.00, 16.00, 128.00, 928.00, 'unpaid', '2026-03-05 14:01:16'),
(2, 2, 'INV-69A9A08D6F7C2', 800.00, 16.00, 128.00, 928.00, 'unpaid', '2026-03-05 15:26:05'),
(3, 3, 'INV-69A9B33FF3C73', 800.00, 16.00, 128.00, 928.00, 'unpaid', '2026-03-05 16:45:51'),
(4, 4, 'INV-69AC2DCD08509', 800.00, 16.00, 128.00, 928.00, 'unpaid', '2026-03-07 13:53:17'),
(5, 5, 'INV-69AC556F20A08', 800.00, 16.00, 128.00, 928.00, 'unpaid', '2026-03-07 16:42:23'),
(6, 6, 'INV-69AC58410C7E7', 1.00, 16.00, 0.16, 1.16, 'paid', '2026-03-07 16:54:25'),
(7, 7, 'INV-69AD845C30574', 1.00, 0.00, 0.00, 1.00, 'paid', '2026-03-08 14:14:52'),
(8, 8, 'INV-69AD867260532', 1.00, 0.00, 0.00, 1.00, 'paid', '2026-03-08 14:23:46'),
(9, 9, 'INV-69AE76EB1F67D', 1.10, 0.00, 0.00, 1.10, 'paid', '2026-03-09 07:29:47'),
(10, 10, 'INV-69AE7B7C93743', 1.00, 0.00, 0.00, 1.00, 'paid', '2026-03-09 07:49:16'),
(11, 13, 'INV-69B2F727DBB7A', 1.00, 0.00, 0.00, 1.00, 'paid', '2026-03-12 17:25:59'),
(12, 14, 'INV-69B3D28E50372', 1.00, 0.00, 0.00, 1.00, 'paid', '2026-03-13 09:02:06'),
(13, 15, 'INV-69B51EAA41E04', 1.00, 0.00, 0.00, 1.00, 'unpaid', '2026-03-14 08:39:06'),
(14, 17, 'INV-69B67777DCFB6', 1.00, 0.00, 0.00, 1.00, 'unpaid', '2026-03-15 09:10:15'),
(15, 18, 'INV-69B7BFC724875', 1.00, 0.00, 0.00, 1.00, 'unpaid', '2026-03-16 08:31:03'),
(16, 19, 'INV-69C4D1B53F640', 2.00, 0.00, 0.00, 2.00, 'paid', '2026-03-26 06:27:01');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `transaction_id`, `amount`, `status`, `verified_at`, `created_at`) VALUES
(1, 6, '109334304879', 1.16, 'verified', '2026-03-07 17:04:08', '2026-03-07 17:03:43'),
(3, 8, '26295485396806514', 1.00, 'verified', '2026-03-08 14:24:09', '2026-03-08 14:24:09'),
(5, 10, '643469390104', 1.00, 'verified', '2026-03-09 07:50:51', '2026-03-09 07:50:51'),
(7, 14, '607157684880', 1.00, 'verified', '2026-03-13 09:03:54', '2026-03-13 09:03:54'),
(8, 19, '608595731569', 2.00, 'verified', '2026-03-26 06:33:34', '2026-03-26 06:33:34');

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `business_name` varchar(150) NOT NULL,
  `bio` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `availability_status` enum('available','busy','offline') DEFAULT 'available',
  `approval_status` enum('pending','approved','suspended') DEFAULT 'pending',
  `profile_photo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `user_id`, `category_id`, `business_name`, `bio`, `experience_years`, `base_price`, `availability_status`, `approval_status`, `profile_photo`, `address`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'Kamau Plumbing Services', 'Professional plumber with 8 years experience in residential and commercial plumbing.', 8, 1500.00, 'available', 'approved', 'provider_1_1772728829.png', '', '2026-03-05 13:50:57', '2026-03-05 16:40:29'),
(2, 5, 3, 'Sparkle Cleaning Co.', 'We deliver spotless results every time with eco-friendly products.', 5, 2000.00, 'available', 'approved', NULL, NULL, '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(3, 6, 2, 'Otieno Electricals', 'Licensed electrician specializing in residential installations and repairs.', 10, 1200.00, 'available', 'approved', NULL, NULL, '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(5, 9, 1, 'Test Store', 'test plumbing', 0, 1.00, 'available', 'approved', NULL, 'Bilimora', '2026-03-09 08:56:50', '2026-03-09 09:05:34'),
(6, 10, 1, 'Necro Services', 'Test', 1, 300.00, 'available', 'approved', 'provider_6_1775027567.jpeg', 'Bilimora', '2026-03-11 13:37:33', '2026-04-01 08:40:15'),
(7, 11, 3, 'TaShya Holland', 'Ex deleniti temporib', 4, 964.00, 'available', 'pending', NULL, 'Adipisicing ad labor', '2026-03-11 13:41:39', '2026-03-11 13:41:39'),
(8, 12, 7, 'Robert Silva', 'Aspernatur et aliqua', 4, 929.00, 'available', 'pending', NULL, 'Quidem nesciunt lab', '2026-03-11 13:42:58', '2026-03-11 13:42:58'),
(9, 13, 1, 'test provider', 'test', 1, 10.00, 'available', 'approved', NULL, 'pushpa ka dil', '2026-03-12 15:15:26', '2026-03-12 15:15:49'),
(10, 14, 1, 'test provider', '', 1, 10.00, 'available', 'approved', NULL, 'pushpa ka dil', '2026-03-16 08:27:38', '2026-03-16 08:28:21');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `booking_id`, `customer_id`, `provider_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 3, 2, 1, 3, '', '2026-03-05 16:46:11'),
(2, 13, 7, 6, 5, 'good', '2026-03-12 17:26:43'),
(3, 14, 7, 6, 1, 'test bad rateing', '2026-03-14 08:39:44'),
(4, 18, 7, 6, 1, 'TWST', '2026-03-16 08:31:31'),
(5, 19, 7, 6, 5, 'testing best ranting!', '2026-03-26 06:53:06');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'per visit',
  `price` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category_id`, `name`, `description`, `unit`, `price`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Pipe Leak Repair', 'Fix leaking pipes and joints', 'per job', 1500.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(2, 1, 'Toilet Installation', 'Install new toilet unit', 'per unit', 3500.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(3, 1, 'Drain Unclogging', 'Clear blocked drains', 'per drain', 1.00, 'active', '2026-03-05 13:50:57', '2026-03-07 16:52:58'),
(4, 2, 'Socket Installation', 'Install electrical sockets', 'per socket', 500.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(5, 2, 'Lighting Setup', 'Install ceiling and wall lights', 'per light', 600.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(6, 2, 'Wiring Inspection', 'Full electrical wiring check', 'per room', 1200.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(7, 3, 'Standard Cleaning', 'Regular home cleaning', 'per session', 2000.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(8, 3, 'Deep Cleaning', 'Thorough deep clean including appliances', 'per session', 4500.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(9, 3, 'Carpet Cleaning', 'Professional carpet steam clean', 'per room', 1500.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(10, 4, 'Cabinet Repair', 'Fix broken cabinet doors/drawers', 'per unit', 800.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(11, 4, 'Furniture Assembly', 'Assemble flat-pack furniture', 'per item', 600.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(12, 5, 'Interior Painting', 'Paint interior walls', 'per room', 5000.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(13, 5, 'Exterior Painting', 'Paint exterior walls', 'per sq meter', 200.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(14, 6, 'Lawn Mowing', 'Mow and edge lawn', 'per session', 1000.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(15, 6, 'Tree Trimming', 'Trim overgrown trees', 'per tree', 1500.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(16, 7, 'AC Service', 'Clean and service AC unit', 'per unit', 2500.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(17, 7, 'AC Installation', 'Install new AC unit', 'per unit', 8000.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(18, 8, 'CCTV Installation', 'Install security cameras', 'per camera', 3000.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(19, 8, 'Lock Change', 'Replace door locks', 'per lock', 1200.00, 'active', '2026-03-05 13:50:57', '2026-03-05 13:50:57');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `category` enum('booking_issue','payment_issue','account_issue','provider_complaint','general_inquiry','other') NOT NULL DEFAULT 'general_inquiry',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `status` enum('active','suspended') DEFAULT 'active',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `status`, `avatar`, `created_at`, `updated_at`) VALUES
(1, 'System Admin', 'admin@homeservices.com', '0700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NULL, '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(2, 'John Mwangi', 'john@example.com', '9328366460', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active', NULL, '2026-03-05 13:50:57', '2026-03-09 07:52:20'),
(3, 'Jane Wanjiku', 'jane@example.com', '0723456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active', NULL, '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(4, 'Peter Kamau', 'peter@provider.com', '0734567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active', NULL, '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(5, 'Mary Achieng', 'mary@provider.com', '0745678901', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active', NULL, '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(6, 'James Otieno', 'james@provider.com', '0756789012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active', NULL, '2026-03-05 13:50:57', '2026-03-05 13:50:57'),
(7, 'Pushpendra Damor', 'pushpendramdamor@gmail.com', '9328366460', '$2y$10$OVa.Rif/He46NAWKAX/u9OQhsNFK8SZ/EaSiQwc/zYSZjXOdcpDaG', 'customer', 'active', NULL, '2026-03-09 08:42:59', '2026-03-09 08:42:59'),
(9, 'Dishant Kukna', 'dishantkukna@gmail.com', '9974171087', '$2y$10$hA6XcPyYmeF.9xMRKlJxg.6NvKgxp19JXyWRWvA2vF3r5MZ0Lt9W.', 'customer', 'active', NULL, '2026-03-09 08:56:50', '2026-03-09 08:56:50'),
(10, 'Dishant Kukna', 'necrotec69@gmail.com', '9512206090', '$2y$10$QM46JUQ.3RjZhlKTvpkzRObDThm/WPBN.Zl/CCyWZ3626amFMjqm6', 'customer', 'active', NULL, '2026-03-11 13:37:33', '2026-04-01 08:40:15'),
(11, 'Rosalyn Hill', 'madovymo@mailinator.com', '1286907651', '$2y$10$aNbVoPm0/7Fa1f5zjjv2aeaWGG5Jch/aSmugTmYxgoQFaa5Uv5Rna', 'customer', 'active', NULL, '2026-03-11 13:41:39', '2026-03-11 13:41:39'),
(12, 'Dillon Strickland', 'mymaly@mailinator.com', '1938264439', '$2y$10$HCR5h0nzKvJM7Ft8GMxfpORTt8GpKh184sNBC0NoKefNXYw.95Ile', 'customer', 'active', NULL, '2026-03-11 13:42:58', '2026-03-11 13:42:58'),
(13, 'test', 'test@gamil.com', '9974171087', '$2y$10$oqgG1FEdg701gz72PR1RRO49FX0VrkJxuArvXkcGq2i2gTue0IsHS', 'customer', 'active', NULL, '2026-03-12 15:15:26', '2026-03-12 15:15:26'),
(14, 'test', 'test01@gamil.com', '9974171087', '$2y$10$oGW7DZUr36ib5oEDYp24IuWQTL.CKKAyBcgsCcP2y13uLd.p0nDby', 'customer', 'active', NULL, '2026-03-16 08:27:38', '2026-03-16 08:27:38'),
(16, 'test', 'test02@gamil.com', '9974171087', '$2y$10$pAhQVaBkaWpJUZfV/JFaku5oWNhQRzKoNdGtMhywlriv99qA8hjqW', 'customer', 'active', NULL, '2026-03-26 06:59:09', '2026-03-26 06:59:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bookings_customer_id` (`customer_id`),
  ADD KEY `idx_bookings_provider_id` (`provider_id`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `idx_bookings_created_at` (`created_at`);

--
-- Indexes for table `booking_services`
--
ALTER TABLE `booking_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bs_booking` (`booking_id`),
  ADD KEY `fk_bs_service` (`service_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `idx_invoices_booking_id` (`booking_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_providers_category_id` (`category_id`),
  ADD KEY `idx_providers_approval` (`approval_status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `idx_reviews_provider_id` (`provider_id`),
  ADD KEY `idx_reviews_booking_id` (`booking_id`),
  ADD KEY `fk_reviews_customer` (`customer_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_services_category_id` (`category_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_support_user_id` (`user_id`),
  ADD KEY `idx_support_status` (`status`),
  ADD KEY `idx_support_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `booking_services`
--
ALTER TABLE `booking_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
  ADD CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_bookings_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`);

--
-- Constraints for table `booking_services`
--
ALTER TABLE `booking_services`
  ADD CONSTRAINT `booking_services_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `fk_bs_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `providers`
--
ALTER TABLE `providers`
  ADD CONSTRAINT `fk_providers_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_providers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `providers_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
