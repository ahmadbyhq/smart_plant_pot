-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2026 at 07:36 PM
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
-- Database: `smart_plant_pot`
--

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_logs`
--

CREATE TABLE `monitoring_logs` (
  `id` bigint(20) NOT NULL,
  `temperature` float NOT NULL,
  `humidity` float NOT NULL,
  `soil_raw` int(11) NOT NULL,
  `soil_percent` int(11) NOT NULL,
  `water_raw` int(11) NOT NULL,
  `water_percent` int(11) NOT NULL,
  `soil_alert` varchar(20) NOT NULL,
  `water_alert` varchar(20) NOT NULL,
  `temp_alert` varchar(20) NOT NULL,
  `hum_alert` varchar(20) NOT NULL,
  `pump_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `alert_logs` (
  `id` bigint(20) NOT NULL,
  `alert_type` varchar(20) NOT NULL,
  `alert_level` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Dumping data for table `monitoring_logs`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `monitoring_logs`
--
ALTER TABLE `monitoring_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`);

ALTER TABLE `alert_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `monitoring_logs`
--
ALTER TABLE `monitoring_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

ALTER TABLE `alert_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
