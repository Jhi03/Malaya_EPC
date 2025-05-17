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
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `vendor_id` int(9) NOT NULL,
  `vendor_name` varchar(70) NOT NULL,
  `vendor_type` varchar(80) NOT NULL,
  `contact_person` varchar(80) NOT NULL,
  `vendor_email` varchar(80) DEFAULT NULL,
  `contact_no` int(11) NOT NULL,
  `telephone` int(15) DEFAULT NULL,
  `vendor_address` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`vendor_id`, `vendor_name`, `vendor_type`, `contact_person`, `vendor_email`, `contact_no`, `telephone`, `vendor_address`) VALUES
(1, 'Tung Tung Woodworks', 'Wood', 'Tung Tung Tung Tung Sahur', 'tung.sahur@gmail.com', 965872143, NULL, 'Sinapak St., Bentong City, Malaysia'),
(2, 'Tralalello Tropahan', 'Tropahan', 'Tralalello Tropa Lang', 'tralalello.tropa@gmail.com', 2147483647, 0, 'Tinropa St., Kuala Lumpur, Mali Sya'),
(3, 'test', 'test', 'test', 'test@gmail.com', 2147483647, 0, 'test'),
(4, 'te', 'te', 'te', 'te@gmail.com', 2147483647, 0, 'te'),
(5, 'te', 'te', 'te', 'te@hotmail.com', 987654321, 0, 'tr');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`vendor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `vendor_id` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
