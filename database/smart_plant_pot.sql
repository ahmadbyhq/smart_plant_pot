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
  `pump_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monitoring_logs`
--

INSERT INTO `monitoring_logs` (`id`, `temperature`, `humidity`, `soil_raw`, `soil_percent`, `water_raw`, `water_percent`, `soil_alert`, `water_alert`, `temp_alert`, `pump_status`, `created_at`) VALUES
(1, 32, 77.1, 2446, 2, 1400, 57, 'CRITICAL', 'NORMAL', 'NORMAL', 1, '2026-06-05 16:37:07'),
(2, 32, 75, 2400, 10, 1300, 50, 'CRITICAL', 'NORMAL', 'NORMAL', 1, '2026-06-05 17:12:13'),
(3, 31.4, 76.4, 2459, 1, 0, 0, 'CRITICAL', 'CRITICAL', 'NORMAL', 0, '2026-06-05 17:33:29'),
(4, 31.5, 76.6, 2384, 6, 1318, 79, 'CRITICAL', 'NORMAL', 'NORMAL', 1, '2026-06-05 17:34:50'),
(5, 31.5, 76.5, 2419, 4, 1560, 100, 'CRITICAL', 'NORMAL', 'NORMAL', 1, '2026-06-05 17:34:52'),
(6, 31.5, 76.4, 2382, 6, 156, 0, 'CRITICAL', 'CRITICAL', 'NORMAL', 0, '2026-06-05 17:35:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `monitoring_logs`
--
ALTER TABLE `monitoring_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `monitoring_logs`
--
ALTER TABLE `monitoring_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
