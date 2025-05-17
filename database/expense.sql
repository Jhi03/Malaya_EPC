-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 01:28 AM
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
-- Table structure for table `expense`
--

CREATE TABLE `expense` (
  `record_id` int(9) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `category` varchar(30) NOT NULL,
  `subcategory` varchar(30) DEFAULT NULL,
  `record_description` varchar(50) NOT NULL,
  `budget` decimal(9,0) DEFAULT NULL,
  `expense` decimal(9,0) NOT NULL,
  `payee` varchar(500) NOT NULL,
  `is_rental` varchar(3) NOT NULL DEFAULT 'No',
  `rental_rate` decimal(9,0) DEFAULT NULL,
  `variance` decimal(9,0) DEFAULT NULL,
  `tax` decimal(9,0) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `bill_to_client` varchar(3) NOT NULL DEFAULT 'No',
  `is_company_loss` varchar(3) NOT NULL DEFAULT 'No',
  `loss_id` int(11) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(20) DEFAULT NULL,
  `edit_date` timestamp NULL DEFAULT NULL,
  `edited_by` int(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense`
--

INSERT INTO `expense` (`record_id`, `user_id`, `project_id`, `category`, `subcategory`, `record_description`, `budget`, `expense`, `payee`, `is_rental`, `rental_rate`, `variance`, `tax`, `invoice_no`, `remarks`, `bill_to_client`, `is_company_loss`, `loss_id`, `purchase_date`, `creation_date`, `created_by`, `edit_date`, `edited_by`) VALUES
(1, 1001, 11, 'ASSET', '', 'Sapatosss', 10000, 0, 'Lolaaa', 'No', 0, 10000, 0, '', 'wao', 'No', 'No', NULL, '2025-05-16', '2025-05-16 06:51:00', 1001, '2025-05-17 06:53:42', 1001),
(3, 1001, 1, 'ASSET', '', 'bag', 0, 1000, 'sadfg', 'No', 0, -1000, 0, '', '', 'No', 'No', NULL, '2025-05-16', '2025-05-16 11:27:56', 1001, '2025-05-16 11:28:15', 1001),
(4, 1001, 11, 'OPEX', 'Food', 'Marimar Joll', 10000, 9880, 'Maria Juana', 'No', 0, 120, 0, '0', '', 'No', 'No', NULL, '2025-05-16', '2025-05-16 12:21:46', 1001, NULL, NULL),
(5, 1001, 11, 'OPEX', 'Food', 'Team lunch at site', 1000, 850, 'Jollibee Corp', 'No', NULL, 150, 102, NULL, 'Paid by team lead', 'No', 'No', NULL, '2025-01-05', '2025-05-16 20:47:42', 1001, NULL, NULL),
(6, 1001, 11, 'OPEX', 'Gas', 'Fuel refill - site visit', 1500, 1450, 'Shell Gasoline', 'No', NULL, 50, 174, NULL, NULL, 'No', 'No', NULL, '2025-01-08', '2025-05-16 20:47:42', 1001, NULL, NULL),
(7, 1001, 11, 'OPEX', 'Toll', 'Expressway toll - Taguig', NULL, 250, 'NLEX Toll Services', 'No', NULL, NULL, 30, NULL, NULL, 'No', 'No', NULL, '2025-01-09', '2025-05-16 20:47:42', 1001, NULL, NULL),
(8, 1001, 11, 'OPEX', 'Parking', 'Mall parking for meeting', 300, 200, 'SM Parking', 'No', NULL, 100, 24, NULL, NULL, 'No', 'No', NULL, '2025-01-11', '2025-05-16 20:47:42', 1001, NULL, NULL),
(9, 1001, 11, 'CAPEX', 'Materials', 'Cement purchase', 5000, 4890, 'Wilcon Depot', 'No', NULL, 110, 586, NULL, 'Bulk discount applied', 'No', 'No', NULL, '2025-01-13', '2025-05-16 20:47:42', 1001, NULL, NULL),
(10, 1001, 11, 'CAPEX', 'Labors', 'Skilled mason fee', 7000, 7000, 'Juan Dela Cruz', 'No', NULL, 0, 840, NULL, NULL, 'No', 'No', NULL, '2025-01-15', '2025-05-16 20:47:42', 1001, NULL, NULL),
(11, 1001, 11, 'OPEX', 'Food', 'Breakfast supplies', 500, 450, '7-Eleven', 'No', NULL, 50, 54, NULL, NULL, 'No', 'No', NULL, '2025-01-17', '2025-05-16 20:47:42', 1001, NULL, NULL),
(12, 1001, 11, 'OPEX', 'Gas', 'Fuel - Makati trip', NULL, 1300, 'Caltex', 'No', NULL, NULL, 156, NULL, NULL, 'No', 'No', NULL, '2025-01-19', '2025-05-16 20:47:42', 1001, NULL, NULL),
(13, 1001, 11, 'CAPEX', 'Materials', 'Rebar purchase', 9000, 8800, 'Builders Warehouse', 'No', NULL, 200, 1056, NULL, 'Site stock replenishment', 'No', 'No', NULL, '2025-01-22', '2025-05-16 20:47:42', 1001, NULL, NULL),
(14, 1001, 11, 'CAPEX', 'Labors', 'Painter salary', NULL, 4500, 'Pedro Santos', 'No', NULL, NULL, 540, NULL, NULL, 'No', 'No', NULL, '2025-01-25', '2025-05-16 20:47:42', 1001, NULL, NULL),
(15, 1001, 11, 'OPEX', 'Food', 'Snacks for 3 days', 800, 720, 'Mini Stop', 'No', NULL, 80, 86, NULL, NULL, 'No', 'No', NULL, '2025-02-02', '2025-05-16 20:47:42', 1001, NULL, NULL),
(16, 1001, 11, 'OPEX', 'Toll', 'South toll trip', NULL, 310, 'SLEX Corp', 'No', NULL, NULL, 37, NULL, NULL, 'No', 'No', NULL, '2025-02-03', '2025-05-16 20:47:42', 1001, NULL, NULL),
(17, 1001, 11, 'OPEX', 'Parking', 'City hall parking', 200, 180, 'LGU Parking', 'No', NULL, 20, 22, NULL, NULL, 'No', 'No', NULL, '2025-02-05', '2025-05-16 20:47:42', 1001, NULL, NULL),
(18, 1001, 11, 'CAPEX', 'Materials', 'Tile adhesives', 3500, 3450, 'Handyman Depot', 'No', NULL, 50, 414, NULL, NULL, 'No', 'No', NULL, '2025-02-06', '2025-05-16 20:47:42', 1001, NULL, NULL),
(19, 1001, 11, 'CAPEX', 'Labors', 'Welder wage', 6000, 6000, 'Ramon Aguirre', 'No', NULL, 0, 720, NULL, 'Per contract', 'No', 'No', NULL, '2025-02-08', '2025-05-16 20:47:42', 1001, NULL, NULL),
(20, 1001, 11, 'OPEX', 'Gas', 'Diesel refill', 1700, 1600, 'Phoenix Petroleum', 'No', NULL, 100, 192, NULL, NULL, 'No', 'No', NULL, '2025-02-10', '2025-05-16 20:47:42', 1001, NULL, NULL),
(21, 1001, 11, 'OPEX', 'Food', 'On-site food delivery', NULL, 950, 'GrabFood', 'No', NULL, NULL, 114, NULL, NULL, 'No', 'No', NULL, '2025-02-12', '2025-05-16 20:47:42', 1001, NULL, NULL),
(22, 1001, 11, 'CAPEX', 'Materials', 'Sand & gravel', 4500, 4390, 'Construction Hub', 'No', NULL, 110, 527, NULL, NULL, 'No', 'No', NULL, '2025-02-15', '2025-05-16 20:47:42', 1001, NULL, NULL),
(23, 1001, 11, 'CAPEX', 'Labors', 'Loader operation fee', 3000, 2950, 'ABC Equipment Rentals', 'No', 1500, 50, 354, NULL, NULL, 'No', 'No', NULL, '2025-02-17', '2025-05-16 20:47:42', 1001, NULL, NULL),
(24, 1001, 11, 'OPEX', 'Toll', 'Intercity toll', NULL, 220, 'Tollways Inc', 'No', NULL, NULL, 26, NULL, NULL, 'No', 'No', NULL, '2025-02-18', '2025-05-16 20:47:42', 1001, NULL, NULL),
(25, 1001, 11, 'OPEX', 'Parking', 'Meeting - paid parking', NULL, 100, 'Robinsons Parking', 'No', NULL, NULL, 12, NULL, NULL, 'No', 'No', NULL, '2025-03-01', '2025-05-16 20:47:42', 1001, NULL, NULL),
(26, 1001, 11, 'CAPEX', 'Materials', 'Plywood purchase', 2500, 2400, 'Matimco', 'No', NULL, 100, 288, NULL, NULL, 'No', 'No', NULL, '2025-03-03', '2025-05-16 20:47:42', 1001, NULL, NULL),
(27, 1001, 11, 'OPEX', 'Food', 'Team snacks', 300, 280, 'Local Store', 'No', NULL, 20, 34, NULL, NULL, 'No', 'No', NULL, '2025-03-04', '2025-05-16 20:47:42', 1001, NULL, NULL),
(28, 1001, 11, 'CAPEX', 'Labors', 'Plumber service', NULL, 4000, 'Mario Bros Plumbing', 'No', NULL, NULL, 480, NULL, NULL, 'No', 'No', NULL, '2025-03-05', '2025-05-16 20:47:42', 1001, NULL, NULL),
(29, 1001, 11, 'OPEX', 'Gas', 'Fuel - weekend site check', 1400, 1300, 'Unioil', 'No', NULL, 100, 156, NULL, NULL, 'No', 'No', NULL, '2025-03-07', '2025-05-16 20:47:42', 1001, NULL, NULL),
(30, 1001, 11, 'CAPEX', 'Materials', 'Hollow blocks', 3000, 2950, 'MegaBlocks Co.', 'No', NULL, 50, 354, NULL, NULL, 'No', 'No', NULL, '2025-03-10', '2025-05-16 20:47:42', 1001, NULL, NULL),
(31, 1001, 11, 'CAPEX', 'Labors', 'Concrete mixer operator', NULL, 3200, 'Mix Pro Services', 'No', 2000, NULL, 384, NULL, NULL, 'No', 'No', NULL, '2025-03-11', '2025-05-16 20:47:42', 1001, NULL, NULL),
(32, 1001, 11, 'OPEX', 'Toll', 'Expressway fees', 400, 360, 'SLEX Express', 'No', NULL, 40, 43, NULL, NULL, 'No', 'No', NULL, '2025-03-12', '2025-05-16 20:47:42', 1001, NULL, NULL),
(33, 1001, 11, 'OPEX', 'Parking', 'Airport parking', NULL, 450, 'NAIA Parking', 'No', NULL, NULL, 54, NULL, NULL, 'No', 'No', NULL, '2025-03-14', '2025-05-16 20:47:42', 1001, NULL, NULL),
(34, 1001, 11, 'CAPEX', 'Materials', 'Paints & rollers', 2000, 1980, 'Paint Center', 'No', NULL, 20, 237, NULL, NULL, 'No', 'No', NULL, '2025-03-15', '2025-05-16 20:47:42', 1001, NULL, NULL),
(35, 1001, 11, 'OPEX', 'Food', 'Weekend overtime meal', NULL, 700, 'Food Panda', 'No', NULL, NULL, 84, NULL, NULL, 'No', 'No', NULL, '2025-04-02', '2025-05-16 20:47:42', 1001, NULL, NULL),
(36, 1001, 11, 'OPEX', 'Gas', 'Refuel van', NULL, 1600, 'Petron', 'No', NULL, NULL, 192, NULL, NULL, 'No', 'No', NULL, '2025-04-03', '2025-05-16 20:47:42', 1001, NULL, NULL),
(37, 1001, 11, 'CAPEX', 'Materials', 'Bricks and mortar', 6000, 5890, 'Masonry Supplies', 'No', NULL, 110, 706, NULL, NULL, 'No', 'No', NULL, '2025-04-04', '2025-05-16 20:47:42', 1001, NULL, NULL),
(38, 1001, 11, 'CAPEX', 'Labors', 'Electrical works', 8000, 8000, 'Volt Experts', 'No', NULL, 0, 960, NULL, NULL, 'No', 'No', NULL, '2025-04-05', '2025-05-16 20:47:42', 1001, NULL, NULL),
(39, 1001, 11, 'OPEX', 'Toll', 'E-tag reload', NULL, 500, 'Autosweep', 'No', NULL, NULL, 60, NULL, NULL, 'No', 'No', NULL, '2025-04-06', '2025-05-16 20:47:42', 1001, NULL, NULL),
(40, 1001, 11, 'OPEX', 'Parking', 'Hotel basement parking', 300, 280, 'Hotel XYZ', 'No', NULL, 20, 34, NULL, NULL, 'No', 'No', NULL, '2025-04-07', '2025-05-16 20:47:42', 1001, NULL, NULL),
(41, 1001, 11, 'CAPEX', 'Materials', 'Glass and fittings', 4500, 4480, 'Glass Solutions', 'No', NULL, 20, 538, NULL, NULL, 'No', 'No', NULL, '2025-04-09', '2025-05-16 20:47:42', 1001, NULL, NULL),
(42, 1001, 11, 'CAPEX', 'Labors', 'Tiling work', NULL, 5000, 'TileMaster Inc.', 'No', NULL, NULL, 600, NULL, NULL, 'No', 'No', NULL, '2025-04-10', '2025-05-16 20:47:42', 1001, NULL, NULL),
(43, 1001, 11, 'OPEX', 'Food', 'Catering for event', 2000, 1900, 'KitchenWorks', 'No', NULL, 100, 228, NULL, NULL, 'No', 'No', NULL, '2025-04-12', '2025-05-16 20:47:42', 1001, NULL, NULL),
(44, 1001, 11, 'OPEX', 'Gas', 'Long trip diesel', 2200, 2100, 'Seaoil', 'No', NULL, 100, 252, NULL, NULL, 'No', 'No', NULL, '2025-04-13', '2025-05-16 20:47:42', 1001, NULL, NULL),
(45, 1001, 11, 'CAPEX', 'Materials', 'Steel bars', 9000, 8850, 'SteelWorks PH', 'No', NULL, 150, 1062, NULL, NULL, 'No', 'No', NULL, '2025-05-01', '2025-05-16 20:47:42', 1001, NULL, NULL),
(46, 1001, 11, 'CAPEX', 'Labors', 'Scaffolder fee', 4000, 4000, 'SafeHeights Labor', 'No', NULL, 0, 480, NULL, NULL, 'No', 'No', NULL, '2025-05-02', '2025-05-16 20:47:42', 1001, NULL, NULL),
(47, 1001, 11, 'OPEX', 'Toll', 'NLEX southbound', NULL, 270, 'Toll Corp', 'No', NULL, NULL, 32, NULL, NULL, 'No', 'No', NULL, '2025-05-03', '2025-05-16 20:47:42', 1001, NULL, NULL),
(48, 1001, 11, 'OPEX', 'Parking', 'Short-term parking', NULL, 180, 'Glorietta Parking', 'No', NULL, NULL, 22, NULL, NULL, 'No', 'No', NULL, '2025-05-04', '2025-05-16 20:47:42', 1001, NULL, NULL),
(49, 1001, 11, 'OPEX', 'Food', 'Sandwiches for workers', 500, 480, 'BakeShoppe', 'No', NULL, 20, 58, NULL, NULL, 'No', 'No', NULL, '2025-05-06', '2025-05-16 20:47:42', 1001, NULL, NULL),
(50, 1001, 0, 'ASSET', NULL, 'Camera', NULL, 900, '', 'No', 0, NULL, 0, NULL, '', 'No', 'No', NULL, '2025-05-01', '2025-05-17 08:57:21', 1001, NULL, NULL),
(61, 1001, 1, 'ASSET', '', 'test bill', 0, 0, 'test bill', 'Yes', 1000, -1000, 0, '', '', 'Yes', 'No', NULL, '2025-05-17', '2025-05-17 20:38:25', 1001, NULL, NULL),
(63, 1001, 14, 'ASSET', '', 'test', 100, 0, 'test', 'Yes', 1000, -900, 0, '', '', 'No', 'No', NULL, '2025-05-17', '2025-05-17 20:44:09', 1001, NULL, NULL),
(67, 1001, 1, 'ASSET', '', 'Scenario 2', 0, 1000, 'Scenario 2', 'No', 0, -1000, 0, '', '', 'No', 'Yes', 66, '2025-05-17', '2025-05-17 22:23:00', 1001, NULL, NULL),
(69, 1001, 1, 'ASSET', '', 'Scenario 3', 500, 0, 'Scenario 3', 'Yes', 1000, -500, 0, '', '', 'No', 'Yes', 68, '2025-05-17', '2025-05-17 22:23:58', 1001, NULL, NULL),
(71, 1001, 1, 'ASSET', '', 'Scenario 4', 500, 0, 'Scenario 4', 'Yes', 1000, -500, 0, '', '', 'Yes', 'No', 70, '2025-05-17', '2025-05-17 22:24:43', 1001, NULL, NULL),
(73, 1001, 1, 'ASSET', '', 'asset test', 0, 500, 'asset test', 'No', 0, -500, 0, '', '', 'No', 'Yes', 72, '2025-05-17', '2025-05-17 22:30:46', 1001, NULL, NULL),
(77, 1001, 1, 'ASSET', '', 'asd', 0, 213, 'asd', 'No', 0, -213, 0, '', '', 'Yes', 'No', 76, '2025-05-17', '2025-05-17 22:32:33', 1001, NULL, NULL),
(79, 1001, 1, 'ASSET', '', 'asdg', 0, 100, 'test deletion', 'No', 0, -100, 0, '', '', 'No', 'Yes', 78, '2025-05-17', '2025-05-17 22:59:30', 1001, NULL, NULL),
(82, 1001, 14, 'ASSET', '', 'update record delete', 0, 1000, 'update record delete', 'No', 0, -1000, 0, '', '', 'No', 'Yes', 82, '2025-05-17', '2025-05-17 23:03:00', 1001, '2025-05-17 23:07:25', 1001),
(84, 1001, 1, 'ASSET', '', 'update record delete', 0, 1000, 'update record delete', 'No', 0, -1000, 0, '', '', 'No', 'Yes', 82, '2025-05-17', '2025-05-17 23:07:25', 1001, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `expense`
--
ALTER TABLE `expense`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `edited_by` (`edited_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expense`
--
ALTER TABLE `expense`
  MODIFY `record_id` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expense`
--
ALTER TABLE `expense`
  ADD CONSTRAINT `expense_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `expense_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
