-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 07:07 PM
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
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL,
  `basic_pay` decimal(10,2) NOT NULL,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `sss` decimal(10,2) NOT NULL,
  `philhealth` decimal(10,2) NOT NULL,
  `pagibig` decimal(10,2) NOT NULL,
  `loans` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `net_pay` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Bank Transfer',
  `remarks` text DEFAULT NULL,
  `date_generated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`payroll_id`, `employee_id`, `period_start`, `period_end`, `gross_pay`, `basic_pay`, `overtime_pay`, `allowances`, `bonus`, `sss`, `philhealth`, `pagibig`, `loans`, `other_deductions`, `total_deductions`, `tax`, `net_pay`, `payment_method`, `remarks`, `date_generated`) VALUES
(1, 1001, '2025-04-01', '2025-04-15', 35000.00, 28000.00, 2000.00, 1500.00, 1000.00, 1200.00, 400.00, 100.00, 0.00, 50.00, 1750.00, 3000.00, 30250.00, 'Bank Transfer', 'Good performance bonus included', '2025-05-15 23:08:13'),
(2, 1001, '2025-04-16', '2025-04-30', 34000.00, 27000.00, 1500.00, 1400.00, 600.00, 1200.00, 400.00, 100.00, 200.00, 100.00, 2000.00, 2800.00, 31200.00, 'Cash', 'Overtime reduced due to holiday', '2025-05-15 23:08:13'),
(3, 1001, '2025-05-01', '2025-05-15', 36000.00, 28000.00, 2500.00, 1600.00, 900.00, 1200.00, 400.00, 100.00, 100.00, 150.00, 1750.00, 3200.00, 30850.00, 'Bank Transfer', 'Included mid-month bonus', '2025-05-15 23:08:13'),
(4, 1001, '2025-05-16', '2025-05-31', 35500.00, 28000.00, 1800.00, 1300.00, 400.00, 1200.00, 400.00, 100.00, 0.00, 200.00, 1700.00, 3100.00, 30600.00, 'Cash', 'No remarks', '2025-05-15 23:08:13'),
(5, 1002, '2025-04-01', '2025-04-15', 28000.00, 23000.00, 1000.00, 1200.00, 800.00, 1100.00, 350.00, 90.00, 0.00, 30.00, 1470.00, 2500.00, 26030.00, 'Bank Transfer', 'Consistent attendance', '2025-05-15 23:08:13'),
(6, 1002, '2025-04-16', '2025-04-30', 27500.00, 22500.00, 900.00, 1100.00, 0.00, 1100.00, 350.00, 90.00, 0.00, 40.00, 1480.00, 2450.00, 26070.00, 'Cash', 'No bonus this period', '2025-05-15 23:08:13'),
(7, 1002, '2025-05-01', '2025-05-15', 29000.00, 23000.00, 1200.00, 1300.00, 600.00, 1100.00, 350.00, 90.00, 100.00, 70.00, 1510.00, 2600.00, 26990.00, 'Bank Transfer', 'Bonus after project completion', '2025-05-15 23:08:13'),
(8, 1002, '2025-05-16', '2025-05-31', 28500.00, 23000.00, 1100.00, 1250.00, 500.00, 1100.00, 350.00, 90.00, 0.00, 50.00, 1490.00, 2550.00, 26910.00, 'Cash', 'Slightly reduced overtime', '2025-05-15 23:08:13'),
(9, 1003, '2025-04-01', '2025-04-15', 42000.00, 35000.00, 3000.00, 2000.00, 1500.00, 1400.00, 450.00, 120.00, 0.00, 60.00, 2030.00, 3500.00, 38470.00, 'Bank Transfer', 'High performance bonus awarded', '2025-05-15 23:08:13'),
(10, 1003, '2025-04-16', '2025-04-30', 41000.00, 34500.00, 2800.00, 1900.00, 1300.00, 1400.00, 450.00, 120.00, 200.00, 100.00, 2270.00, 3300.00, 37330.00, 'Cash', 'Loan deducted this period', '2025-05-15 23:08:13'),
(11, 1003, '2025-05-01', '2025-05-15', 43000.00, 35000.00, 3200.00, 2100.00, 1600.00, 1400.00, 450.00, 120.00, 150.00, 80.00, 2100.00, 3600.00, 38900.00, 'Bank Transfer', 'Includes special project bonus', '2025-05-15 23:08:13'),
(12, 1003, '2025-05-16', '2025-05-31', 42500.00, 35000.00, 3100.00, 2050.00, 1400.00, 1400.00, 450.00, 120.00, 0.00, 120.00, 1970.00, 3550.00, 38980.00, 'Cash', 'Regular period', '2025-05-15 23:08:13'),
(13, 1004, '2025-04-01', '2025-04-15', 25000.00, 21000.00, 1000.00, 900.00, 600.00, 1000.00, 300.00, 80.00, 0.00, 20.00, 1400.00, 2200.00, 23600.00, 'Bank Transfer', 'Good attendance', '2025-05-15 23:08:13'),
(14, 1004, '2025-04-16', '2025-04-30', 24500.00, 20500.00, 900.00, 850.00, 400.00, 1000.00, 300.00, 80.00, 100.00, 30.00, 1510.00, 2150.00, 23340.00, 'Cash', 'Loan deducted', '2025-05-15 23:08:13'),
(15, 1004, '2025-05-01', '2025-05-15', 25500.00, 21000.00, 1100.00, 950.00, 700.00, 1000.00, 300.00, 80.00, 50.00, 40.00, 1370.00, 2300.00, 24130.00, 'Bank Transfer', 'Bonus given', '2025-05-15 23:08:13'),
(16, 1004, '2025-05-16', '2025-05-31', 25200.00, 21000.00, 1000.00, 900.00, 500.00, 1000.00, 300.00, 80.00, 0.00, 50.00, 1430.00, 2250.00, 23920.00, 'Cash', 'Regular payment', '2025-05-15 23:08:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
