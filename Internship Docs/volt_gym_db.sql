-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2026 at 09:20 AM
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
-- Database: `volt_gym`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$s2xlZkV4tqRPah6juJIR0uhWd9lx9NCCC06ZZMpJSMaCZ8chItMqi', '2026-10-12 16:48:45'),
(2, 'admin2', '$2y$10$rNm.KUH0DQFiRNUI23VF1uphLVK4QRS6XQi2HFK5mKgsi9yOFcZzW', '2026-10-16 07:17:09');

-- --------------------------------------------------------

--
-- Table structure for table `admin_read_status`
--

CREATE TABLE `admin_read_status` (
  `submission_type` enum('review','inquiry') NOT NULL,
  `submission_id` bigint(20) UNSIGNED NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_read_status`
--

INSERT INTO `admin_read_status` (`submission_type`, `submission_id`, `read_at`) VALUES
('review', 1, '2026-10-12 17:46:57'),
('inquiry', 1, '2026-10-12 17:37:43'),
('inquiry', 2, '2026-10-12 17:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `email` varchar(254) DEFAULT NULL,
  `goal` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `name`, `email`, `goal`, `message`, `created_at`, `phone`) VALUES
(1, 'Aditya Shukla', 'axite07@gmail.com', 'Building strength', 'askdjfakl', '2026-10-06 07:24:07', NULL),
(2, 'Aditya Shukla', 'axite07@gmail.com', NULL, 'asdfghjkl;', '2026-10-06 07:34:29', NULL),
(3, 'Aditya Shukla', 'axite07@gmail.com', 'Personal training', 'Negotiation Possible on the price ?', '2026-10-12 17:48:50', '9340967299');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `message` varchar(600) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(254) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `name`, `rating`, `message`, `created_at`, `email`, `phone`) VALUES
(1, 'Aditya Shukla', 5, 'Great Gym', '2026-10-06 07:24:39', NULL, NULL),
(2, 'Adi 2', 5, 'Nice Ambiance', '2026-10-12 17:48:30', 'axite07@gmail.com', '+919340967299'),
(9, 'Adiiiii', 5, 'Best gym in the town.', '2026-10-15 07:14:59', 'axite07@gmail.com', '+919340967256');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_username_unique` (`username`);

--
-- Indexes for table `admin_read_status`
--
ALTER TABLE `admin_read_status`
  ADD PRIMARY KEY (`submission_type`,`submission_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
