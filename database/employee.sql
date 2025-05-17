-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 06:59 PM
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
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employee_id` int(11) NOT NULL,
  `first_name` varchar(30) NOT NULL,
  `middle_name` varchar(30) DEFAULT NULL,
  `last_name` varchar(30) NOT NULL,
  `position` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `contact` varchar(11) NOT NULL,
  `unit_no` varchar(10) DEFAULT NULL,
  `building` varchar(30) DEFAULT NULL,
  `street` varchar(30) NOT NULL,
  `barangay` varchar(30) NOT NULL,
  `city` varchar(30) NOT NULL,
  `country` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`employee_id`, `first_name`, `middle_name`, `last_name`, `position`, `department`, `status`, `contact`, `unit_no`, `building`, `street`, `barangay`, `city`, `country`) VALUES
(1001, 'Juan', NULL, 'Dela Cruz', 'admin', 'admin', 'active', '09876543210', NULL, NULL, 'Tayuman', 'Comembo', 'Makati', 'Philippines'),
(1002, 'Rajhi', NULL, 'Sangcopan', 'Engineer', 'Operations & Project Management', 'active', '09996735172', NULL, NULL, 'Mama', 'Hagonoy', 'Taguig', 'Philippines'),
(1003, 'Sebastian Arvin', NULL, 'Reyes', 'Cybersecurity', 'IT Infrastructure & Cybersecurity', 'active', '09382648261', NULL, NULL, 'Malunggay', 'Chino', 'Manila', 'Philippines'),
(1004, 'Mikayla', NULL, 'Lacanilao', 'Engineer', 'Operations & Project Management Department', 'active', '09474857261', NULL, NULL, 'Hinog', 'Wawa', 'Laguna', 'Philippines');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
