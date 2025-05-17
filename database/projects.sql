-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 07:00 PM
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
-- Database: `malayasol`
--

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `project_code` varchar(30) NOT NULL,
  `project_name` varchar(30) NOT NULL,
  `first_name` varchar(30) NOT NULL,
  `last_name` varchar(30) NOT NULL,
  `company_name` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `contact` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `unit_building_no` varchar(50) DEFAULT NULL,
  `street` varchar(30) DEFAULT NULL,
  `barangay` varchar(30) DEFAULT NULL,
  `city` varchar(30) DEFAULT NULL,
  `country` varchar(30) DEFAULT NULL,
  `budget` decimal(30,0) NOT NULL,
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `edit_date` timestamp NULL DEFAULT current_timestamp(),
  `edited_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `project_code`, `project_name`, `first_name`, `last_name`, `company_name`, `description`, `contact`, `email`, `unit_building_no`, `street`, `barangay`, `city`, `country`, `budget`, `creation_date`, `created_by`, `edit_date`, `edited_by`) VALUES
(1, 'CORPORATE', 'Corporate', 'Carlo', 'Geraga', 'Malaya Solar Energies', 'Expense Tracker', 999999999, 'carlo.geraga@gmail.com', NULL, NULL, NULL, NULL, NULL, 0, '2025-05-15 16:36:12', 1, '2025-05-15 16:36:12', NULL),
(11, 'MEGA', 'CAMSUR', 'Apullo', 'Quibbs', 'Cardboard Co.', 'Cardboard manufacturing plant', 2147483647, 'camsur@gmail.com', '', '', '', '', '', 0, '2025-05-13 16:00:00', 1001, '2025-05-16 14:17:44', 1001),
(13, 'ERN', 'ELRON', 'Tung', 'Sahur', 'Elron Incorporated', 'Okay', 2147483647, 'tung.sahur@gmail.com', NULL, NULL, NULL, NULL, NULL, 0, '2025-05-14 16:00:00', 1001, '2025-05-15 11:19:48', NULL),
(14, 'BAD', 'bading', 'Aiz', 'Limit', 'Kaba-klaan', 'k fine', 2147483647, 'aiz.limit@bading.com', NULL, NULL, NULL, NULL, NULL, 0, '2025-05-15 16:00:00', 1001, '2025-05-16 15:17:47', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `project_id` (`project_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
