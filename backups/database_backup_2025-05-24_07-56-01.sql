-- Database Backup
-- Generated on: 2025-05-24 07:56:01
-- Database: malayasol


-- Table structure for `assets`
DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
  `asset_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) DEFAULT NULL,
  `asset_description` varchar(50) NOT NULL,
  `asset_img` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(20) DEFAULT NULL,
  `edit_date` timestamp NULL DEFAULT NULL,
  `edited_by` int(20) DEFAULT NULL,
  PRIMARY KEY (`asset_id`),
  KEY `record_id` (`record_id`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `expense` (`record_id`) ON DELETE CASCADE,
  CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `assets_ibfk_3` FOREIGN KEY (`edited_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `assets`
INSERT INTO `assets` VALUES ('1', '50', 'Camera', NULL, '', '', '', '2025-05-17', '2025-05-17 16:57:21', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('8', '63', 'test', NULL, NULL, NULL, NULL, NULL, '2025-05-18 04:44:09', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('20', '213', 'add record test', NULL, NULL, NULL, NULL, NULL, '2025-05-19 08:29:09', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('21', '215', 'test activity log', NULL, NULL, NULL, NULL, NULL, '2025-05-22 10:29:08', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('22', '217', 'test log and session', NULL, NULL, NULL, NULL, NULL, '2025-05-22 10:33:37', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('28', NULL, 'test asset', NULL, 'test asset', 'test asset', 'test asset', '0000-00-00', '2025-05-23 08:43:03', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('29', NULL, 'test img asset', 'uploads/assets/682fc4b779d97.jpg', '', '', '', '0000-00-00', '2025-05-23 08:43:35', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('30', NULL, 'overflow test 01', NULL, '', '', '', '0000-00-00', '2025-05-23 08:44:22', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('31', NULL, 'overflow test 02', NULL, '', '', '', '0000-00-00', '2025-05-23 08:44:28', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('32', NULL, 'overflow test 03', NULL, '', '', '', '0000-00-00', '2025-05-23 08:44:35', '1001', NULL, NULL);
INSERT INTO `assets` VALUES ('33', NULL, 'asd', NULL, '', '', '', '0000-00-00', '2025-05-23 11:47:57', '1001', NULL, NULL);


-- Table structure for `categories`
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(40) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `categories`
INSERT INTO `categories` VALUES ('1', 'OPEX');
INSERT INTO `categories` VALUES ('2', 'CAPEX');
INSERT INTO `categories` VALUES ('3', 'ASSET');


-- Table structure for `employee`
DROP TABLE IF EXISTS `employee`;
CREATE TABLE `employee` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(30) NOT NULL,
  `middle_name` varchar(30) DEFAULT NULL,
  `last_name` varchar(30) NOT NULL,
  `position` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `employment_status` varchar(20) NOT NULL DEFAULT 'active',
  `contact` varchar(11) NOT NULL,
  `unit_no` varchar(10) DEFAULT NULL,
  `building` varchar(30) DEFAULT NULL,
  `street` varchar(30) NOT NULL,
  `barangay` varchar(30) NOT NULL,
  `city` varchar(30) NOT NULL,
  `country` varchar(30) NOT NULL,
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1011 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `employee`
INSERT INTO `employee` VALUES ('1001', 'Juan', NULL, 'Dela Cruz', 'superadmin', 'IT Infrastructure & Cybersecurity Division', 'active', '09876543210', NULL, NULL, 'Tayuman', 'Comembo', 'Makati', 'Philippines');
INSERT INTO `employee` VALUES ('1003', 'Sebastian Arvin', NULL, 'Reyes', 'admin', 'IT Infrastructure & Cybersecurity', 'active', '09382648261', NULL, NULL, 'Malunggay', 'Chino', 'Manila', 'Philippines');
INSERT INTO `employee` VALUES ('1004', 'Mikayla', NULL, 'Lacanilao', 'user', 'Operations & Project Management Department', 'active', '09474857261', NULL, NULL, 'Hinog', 'Wawa', 'Laguna', 'Philippines');
INSERT INTO `employee` VALUES ('1006', 'Chantel', '', 'Reblando', 'Manager', 'Operations & Project Management Department', 'active', '9372836182', '', '', 'Kalibong', 'Marisa', 'Makati', 'Philippines');
INSERT INTO `employee` VALUES ('1007', 'Jhon Sherwin', '', 'Jayme', 'Accountant', 'Finance & Digital Accounting Department', 'active', '9576746886', '', '', 'Sampaloc', 'Maliban', 'Manila', 'Philippines');


-- Table structure for `expense`
DROP TABLE IF EXISTS `expense`;
CREATE TABLE `expense` (
  `record_id` int(9) NOT NULL AUTO_INCREMENT,
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
  `edited_by` int(20) DEFAULT NULL,
  PRIMARY KEY (`record_id`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `expense_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `expense_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=225 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `expense`
INSERT INTO `expense` VALUES ('1', '1001', '11', 'ASSET', '', 'Sapatosss', '10000', '0', 'Lolaaa', 'No', '0', '10000', '0', '', 'wao', 'No', 'No', NULL, '2025-05-16', '2025-05-16 14:51:00', '1001', '2025-05-17 14:53:42', '1001');
INSERT INTO `expense` VALUES ('3', '1001', '1', 'ASSET', '', 'bag', '0', '1000', 'sadfg', 'No', '0', '-1000', '0', '', '', 'No', 'No', NULL, '2025-05-16', '2025-05-16 19:27:56', '1001', '2025-05-16 19:28:15', '1001');
INSERT INTO `expense` VALUES ('4', '1001', '11', 'OPEX', 'Food', 'Marimar Joll', '10000', '9880', 'Maria Juana', 'No', '0', '120', '0', '0', '', 'No', 'No', NULL, '2025-05-16', '2025-05-16 20:21:46', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('5', '1001', '11', 'OPEX', 'Food', 'Team lunch at site', '1000', '850', 'Jollibee Corp', 'No', NULL, '150', '102', NULL, 'Paid by team lead', 'No', 'No', NULL, '2025-01-05', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('6', '1001', '11', 'OPEX', 'Gas', 'Fuel refill - site visit', '1500', '1450', 'Shell Gasoline', 'No', NULL, '50', '174', NULL, NULL, 'No', 'No', NULL, '2025-01-08', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('7', '1001', '11', 'OPEX', 'Toll', 'Expressway toll - Taguig', NULL, '250', 'NLEX Toll Services', 'No', NULL, NULL, '30', NULL, NULL, 'No', 'No', NULL, '2025-01-09', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('8', '1001', '11', 'OPEX', 'Parking', 'Mall parking for meeting', '300', '200', 'SM Parking', 'No', NULL, '100', '24', NULL, NULL, 'No', 'No', NULL, '2025-01-11', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('9', '1001', '11', 'CAPEX', 'Materials', 'Cement purchase', '5000', '4890', 'Wilcon Depot', 'No', NULL, '110', '586', NULL, 'Bulk discount applied', 'No', 'No', NULL, '2025-01-13', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('10', '1001', '11', 'CAPEX', 'Labors', 'Skilled mason fee', '7000', '7000', 'Juan Dela Cruz', 'No', NULL, '0', '840', NULL, NULL, 'No', 'No', NULL, '2025-01-15', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('11', '1001', '11', 'OPEX', 'Food', 'Breakfast supplies', '500', '450', '7-Eleven', 'No', NULL, '50', '54', NULL, NULL, 'No', 'No', NULL, '2025-01-17', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('12', '1001', '11', 'OPEX', 'Gas', 'Fuel - Makati trip', NULL, '1300', 'Caltex', 'No', NULL, NULL, '156', NULL, NULL, 'No', 'No', NULL, '2025-01-19', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('13', '1001', '11', 'CAPEX', 'Materials', 'Rebar purchase', '9000', '8800', 'Builders Warehouse', 'No', NULL, '200', '1056', NULL, 'Site stock replenishment', 'No', 'No', NULL, '2025-01-22', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('14', '1001', '11', 'CAPEX', 'Labors', 'Painter salary', NULL, '4500', 'Pedro Santos', 'No', NULL, NULL, '540', NULL, NULL, 'No', 'No', NULL, '2025-01-25', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('15', '1001', '11', 'OPEX', 'Food', 'Snacks for 3 days', '800', '720', 'Mini Stop', 'No', NULL, '80', '86', NULL, NULL, 'No', 'No', NULL, '2025-02-02', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('16', '1001', '11', 'OPEX', 'Toll', 'South toll trip', NULL, '310', 'SLEX Corp', 'No', NULL, NULL, '37', NULL, NULL, 'No', 'No', NULL, '2025-02-03', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('17', '1001', '11', 'OPEX', 'Parking', 'City hall parking', '200', '180', 'LGU Parking', 'No', NULL, '20', '22', NULL, NULL, 'No', 'No', NULL, '2025-02-05', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('18', '1001', '11', 'CAPEX', 'Materials', 'Tile adhesives', '3500', '3450', 'Handyman Depot', 'No', NULL, '50', '414', NULL, NULL, 'No', 'No', NULL, '2025-02-06', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('19', '1001', '11', 'CAPEX', 'Labors', 'Welder wage', '6000', '6000', 'Ramon Aguirre', 'No', NULL, '0', '720', NULL, 'Per contract', 'No', 'No', NULL, '2025-02-08', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('20', '1001', '11', 'OPEX', 'Gas', 'Diesel refill', '1700', '1600', 'Phoenix Petroleum', 'No', NULL, '100', '192', NULL, NULL, 'No', 'No', NULL, '2025-02-10', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('21', '1001', '11', 'OPEX', 'Food', 'On-site food delivery', NULL, '950', 'GrabFood', 'No', NULL, NULL, '114', NULL, NULL, 'No', 'No', NULL, '2025-02-12', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('22', '1001', '11', 'CAPEX', 'Materials', 'Sand & gravel', '4500', '4390', 'Construction Hub', 'No', NULL, '110', '527', NULL, NULL, 'No', 'No', NULL, '2025-02-15', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('23', '1001', '11', 'CAPEX', 'Labors', 'Loader operation fee', '3000', '2950', 'ABC Equipment Rentals', 'No', '1500', '50', '354', NULL, NULL, 'No', 'No', NULL, '2025-02-17', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('24', '1001', '11', 'OPEX', 'Toll', 'Intercity toll', NULL, '220', 'Tollways Inc', 'No', NULL, NULL, '26', NULL, NULL, 'No', 'No', NULL, '2025-02-18', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('25', '1001', '11', 'OPEX', 'Parking', 'Meeting - paid parking', NULL, '100', 'Robinsons Parking', 'No', NULL, NULL, '12', NULL, NULL, 'No', 'No', NULL, '2025-03-01', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('26', '1001', '11', 'CAPEX', 'Materials', 'Plywood purchase', '2500', '2400', 'Matimco', 'No', NULL, '100', '288', NULL, NULL, 'No', 'No', NULL, '2025-03-03', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('27', '1001', '11', 'OPEX', 'Food', 'Team snacks', '300', '280', 'Local Store', 'No', NULL, '20', '34', NULL, NULL, 'No', 'No', NULL, '2025-03-04', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('28', '1001', '11', 'CAPEX', 'Labors', 'Plumber service', NULL, '4000', 'Mario Bros Plumbing', 'No', NULL, NULL, '480', NULL, NULL, 'No', 'No', NULL, '2025-03-05', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('29', '1001', '11', 'OPEX', 'Gas', 'Fuel - weekend site check', '1400', '1300', 'Unioil', 'No', NULL, '100', '156', NULL, NULL, 'No', 'No', NULL, '2025-03-07', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('30', '1001', '11', 'CAPEX', 'Materials', 'Hollow blocks', '3000', '2950', 'MegaBlocks Co.', 'No', NULL, '50', '354', NULL, NULL, 'No', 'No', NULL, '2025-03-10', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('31', '1001', '11', 'CAPEX', 'Labors', 'Concrete mixer operator', NULL, '3200', 'Mix Pro Services', 'No', '2000', NULL, '384', NULL, NULL, 'No', 'No', NULL, '2025-03-11', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('32', '1001', '11', 'OPEX', 'Toll', 'Expressway fees', '400', '360', 'SLEX Express', 'No', NULL, '40', '43', NULL, NULL, 'No', 'No', NULL, '2025-03-12', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('33', '1001', '11', 'OPEX', 'Parking', 'Airport parking', NULL, '450', 'NAIA Parking', 'No', NULL, NULL, '54', NULL, NULL, 'No', 'No', NULL, '2025-03-14', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('34', '1001', '11', 'CAPEX', 'Materials', 'Paints & rollers', '2000', '1980', 'Paint Center', 'No', NULL, '20', '237', NULL, NULL, 'No', 'No', NULL, '2025-03-15', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('35', '1001', '11', 'OPEX', 'Food', 'Weekend overtime meal', NULL, '700', 'Food Panda', 'No', NULL, NULL, '84', NULL, NULL, 'No', 'No', NULL, '2025-04-02', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('36', '1001', '11', 'OPEX', 'Gas', 'Refuel van', NULL, '1600', 'Petron', 'No', NULL, NULL, '192', NULL, NULL, 'No', 'No', NULL, '2025-04-03', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('37', '1001', '11', 'CAPEX', 'Materials', 'Bricks and mortar', '6000', '5890', 'Masonry Supplies', 'No', NULL, '110', '706', NULL, NULL, 'No', 'No', NULL, '2025-04-04', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('38', '1001', '11', 'CAPEX', 'Labors', 'Electrical works', '8000', '8000', 'Volt Experts', 'No', NULL, '0', '960', NULL, NULL, 'No', 'No', NULL, '2025-04-05', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('39', '1001', '11', 'OPEX', 'Toll', 'E-tag reload', NULL, '500', 'Autosweep', 'No', NULL, NULL, '60', NULL, NULL, 'No', 'No', NULL, '2025-04-06', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('40', '1001', '11', 'OPEX', 'Parking', 'Hotel basement parking', '300', '280', 'Hotel XYZ', 'No', NULL, '20', '34', NULL, NULL, 'No', 'No', NULL, '2025-04-07', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('41', '1001', '11', 'CAPEX', 'Materials', 'Glass and fittings', '4500', '4480', 'Glass Solutions', 'No', NULL, '20', '538', NULL, NULL, 'No', 'No', NULL, '2025-04-09', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('42', '1001', '11', 'CAPEX', 'Labors', 'Tiling work', NULL, '5000', 'TileMaster Inc.', 'No', NULL, NULL, '600', NULL, NULL, 'No', 'No', NULL, '2025-04-10', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('43', '1001', '11', 'OPEX', 'Food', 'Catering for event', '2000', '1900', 'KitchenWorks', 'No', NULL, '100', '228', NULL, NULL, 'No', 'No', NULL, '2025-04-12', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('44', '1001', '11', 'OPEX', 'Gas', 'Long trip diesel', '2200', '2100', 'Seaoil', 'No', NULL, '100', '252', NULL, NULL, 'No', 'No', NULL, '2025-04-13', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('45', '1001', '11', 'CAPEX', 'Materials', 'Steel bars', '9000', '8850', 'SteelWorks PH', 'No', NULL, '150', '1062', NULL, NULL, 'No', 'No', NULL, '2025-05-01', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('46', '1001', '11', 'CAPEX', 'Labors', 'Scaffolder fee', '4000', '4000', 'SafeHeights Labor', 'No', NULL, '0', '480', NULL, NULL, 'No', 'No', NULL, '2025-05-02', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('47', '1001', '11', 'OPEX', 'Toll', 'NLEX southbound', NULL, '270', 'Toll Corp', 'No', NULL, NULL, '32', NULL, NULL, 'No', 'No', NULL, '2025-05-03', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('48', '1001', '11', 'OPEX', 'Parking', 'Short-term parking', NULL, '180', 'Glorietta Parking', 'No', NULL, NULL, '22', NULL, NULL, 'No', 'No', NULL, '2025-05-04', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('49', '1001', '11', 'OPEX', 'Food', 'Sandwiches for workers', '500', '480', 'BakeShoppe', 'No', NULL, '20', '58', NULL, NULL, 'No', 'No', NULL, '2025-05-06', '2025-05-17 04:47:42', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('50', '1001', '0', 'ASSET', NULL, 'Camera', NULL, '900', '', 'No', '0', NULL, '0', NULL, '', 'No', 'No', NULL, '2025-05-01', '2025-05-17 16:57:21', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('61', '1001', '1', 'ASSET', '', 'test bill', '0', '0', 'test bill', 'Yes', '1000', '-1000', '0', '', '', 'Yes', 'No', NULL, '2025-05-17', '2025-05-18 04:38:25', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('63', '1001', '14', 'ASSET', '', 'test', '100', '0', 'test', 'Yes', '1000', '-900', '0', '', '', 'No', 'No', NULL, '2025-05-17', '2025-05-18 04:44:09', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('67', '1001', '1', 'ASSET', '', 'Scenario 2', '0', '1000', 'Scenario 2', 'No', '0', '-1000', '0', '', '', 'No', 'Yes', '66', '2025-05-17', '2025-05-18 06:23:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('69', '1001', '1', 'ASSET', '', 'Scenario 3', '500', '0', 'Scenario 3', 'Yes', '1000', '-500', '0', '', '', 'No', 'Yes', '68', '2025-05-17', '2025-05-18 06:23:58', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('71', '1001', '1', 'ASSET', '', 'Scenario 4', '500', '0', 'Scenario 4', 'Yes', '1000', '-500', '0', '', '', 'Yes', 'No', '70', '2025-05-17', '2025-05-18 06:24:43', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('73', '1001', '1', 'ASSET', '', 'asset test', '0', '500', 'asset test', 'No', '0', '-500', '0', '', '', 'No', 'Yes', '72', '2025-05-17', '2025-05-18 06:30:46', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('77', '1001', '1', 'ASSET', '', 'asd', '0', '213', 'asd', 'No', '0', '-213', '0', '', '', 'Yes', 'No', '76', '2025-05-17', '2025-05-18 06:32:33', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('79', '1001', '1', 'ASSET', '', 'asdg', '0', '100', 'test deletion', 'No', '0', '-100', '0', '', '', 'No', 'Yes', '78', '2025-05-17', '2025-05-18 06:59:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('90', '1001', '14', 'ASSET', NULL, 'Sample record 1', NULL, '1714', 'Payee 1', 'No', NULL, '1714', '206', 'INV-0001', 'Remark for record 1', 'No', 'Yes', NULL, '2024-09-24', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('91', '1001', '14', 'CAPEX', 'Labor', 'Sample record 4', '2930', '2344', 'Payee 4', 'No', NULL, '-587', '281', 'INV-0004', 'Remark for record 4', 'No', 'No', NULL, '2024-11-04', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('92', '1001', '14', 'CAPEX', 'Labor', 'Sample record 6', NULL, '2902', 'Payee 6', 'No', NULL, '2902', '348', 'INV-0006', 'Remark for record 6', 'No', 'Yes', NULL, '2024-10-04', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('93', '1001', '14', 'OPEX', 'Food', 'Sample record 12', '661', '3337', 'Payee 12', 'No', NULL, '2676', '400', 'INV-0012', 'Remark for record 12', 'Yes', 'No', NULL, '2024-06-05', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('94', '1001', '14', 'OPEX', 'Toll', 'Sample record 13', '4414', '1541', 'Payee 13', 'No', NULL, '-2873', '185', 'INV-0013', 'Remark for record 13', 'No', 'No', NULL, '2025-03-08', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('95', '1001', '14', 'OPEX', 'Food', 'Sample record 15', NULL, '4463', 'Payee 15', 'No', NULL, '4463', '536', 'INV-0015', 'Remark for record 15', 'Yes', 'No', NULL, '2025-04-21', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('96', '1001', '14', 'ASSET', NULL, 'Sample record 16', '2532', '1507', 'Payee 16', 'No', NULL, '-1024', '181', 'INV-0016', 'Remark for record 16', 'No', 'No', NULL, '2024-08-02', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('97', '1001', '14', 'OPEX', 'Parking', 'Sample record 18', '914', '4826', 'Payee 18', 'No', NULL, '3912', '579', 'INV-0018', 'Remark for record 18', 'Yes', 'No', NULL, '2025-01-01', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('98', '1001', '14', 'OPEX', 'Parking', 'Sample record 20', NULL, '693', 'Payee 20', 'No', NULL, '693', '83', 'INV-0020', 'Remark for record 20', 'Yes', 'No', NULL, '2025-02-11', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('99', '1001', '14', 'ASSET', NULL, 'Sample record 23', '2035', '4701', 'Payee 23', 'No', NULL, '2666', '564', 'INV-0023', 'Remark for record 23', 'Yes', 'No', NULL, '2025-04-25', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('100', '1001', '14', 'OPEX', 'Gas', 'Sample record 29', NULL, '2589', 'Payee 29', 'No', NULL, '2589', '311', 'INV-0029', 'Remark for record 29', 'No', 'Yes', NULL, '2024-09-03', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('101', '1001', '14', 'ASSET', NULL, 'Sample record 30', '3791', '401', 'Payee 30', 'No', NULL, '-3390', '48', 'INV-0030', 'Remark for record 30', 'No', 'No', NULL, '2024-10-21', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('102', '1001', '14', 'OPEX', 'Toll', 'Sample record 32', NULL, '888', 'Payee 32', 'No', NULL, '888', '107', 'INV-0032', 'Remark for record 32', 'Yes', 'No', NULL, '2024-06-20', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('103', '1001', '14', 'CAPEX', 'Materials', 'Sample record 35', NULL, '2191', 'Payee 35', 'No', NULL, '2191', '263', 'INV-0035', 'Remark for record 35', 'Yes', 'No', NULL, '2025-01-27', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('104', '1001', '14', 'ASSET', NULL, 'Sample record 36', '385', '4532', 'Payee 36', 'No', NULL, '4146', '544', 'INV-0036', 'Remark for record 36', 'Yes', 'No', NULL, '2024-05-23', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('105', '1001', '14', 'OPEX', 'Parking', 'Sample record 39', '4542', '4447', 'Payee 39', 'No', NULL, '-95', '534', 'INV-0039', 'Remark for record 39', 'No', 'No', NULL, '2024-08-01', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('106', '1001', '14', 'CAPEX', 'Materials', 'Sample record 42', NULL, '163', 'Payee 42', 'No', NULL, '163', '20', 'INV-0042', 'Remark for record 42', 'Yes', 'No', NULL, '2024-08-17', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('107', '1001', '14', 'OPEX', 'Food', 'Sample record 44', '676', '3001', 'Payee 44', 'No', NULL, '2325', '360', 'INV-0044', 'Remark for record 44', 'No', 'Yes', NULL, '2025-03-07', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('108', '1001', '14', 'OPEX', 'Toll', 'Sample record 46', NULL, '961', 'Payee 46', 'No', NULL, '961', '115', 'INV-0046', 'Remark for record 46', 'No', 'Yes', NULL, '2025-01-09', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('109', '1001', '14', 'ASSET', NULL, 'Sample record 47', NULL, '744', 'Payee 47', 'No', NULL, '744', '89', 'INV-0047', 'Remark for record 47', 'Yes', 'No', NULL, '2025-03-23', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('110', '1001', '14', 'ASSET', NULL, 'Sample record 49', '1312', '4432', 'Payee 49', 'No', NULL, '3120', '532', 'INV-0049', 'Remark for record 49', 'Yes', 'No', NULL, '2024-11-30', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('111', '1001', '14', 'ASSET', NULL, 'Sample record 50', NULL, '355', 'Payee 50', 'No', NULL, '355', '43', 'INV-0050', 'Remark for record 50', 'Yes', 'No', NULL, '2025-03-07', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('112', '1001', '14', 'CAPEX', 'Materials', 'Sample record 51', NULL, '2053', 'Payee 51', 'No', NULL, '2053', '246', 'INV-0051', 'Remark for record 51', 'No', 'Yes', NULL, '2024-12-05', '2025-05-18 04:30:30', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('113', '1001', '14', 'OPEX', 'Food', 'Team Lunch', '1000', '1200', 'Restaurant A', 'No', '0', '200', '144', 'INV-001', NULL, 'Yes', 'No', NULL, '2024-05-01', '2024-05-01 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('114', '1001', '14', 'OPEX', 'Gas', 'Fuel refill', '500', '450', 'Gas Station B', 'No', '0', '-50', '54', 'INV-002', NULL, 'No', 'No', NULL, '2024-05-03', '2024-05-03 11:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('115', '1001', '14', 'CAPEX', 'Materials', 'Construction materials', '5000', '5000', 'Hardware C', 'No', '0', '0', '600', 'INV-003', NULL, 'No', 'No', NULL, '2024-05-05', '2024-05-05 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('116', '1001', '14', 'OPEX', 'Parking', 'Parking fee', '100', '120', 'Parking Lot D', 'No', '0', '20', '14', 'INV-004', NULL, 'No', 'Yes', NULL, '2024-05-07', '2024-05-07 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('117', '1001', '14', 'OPEX', 'Toll', 'Toll fee', '200', '250', 'Toll Plaza E', 'No', '0', '50', '30', 'INV-005', NULL, 'Yes', 'No', NULL, '2024-05-09', '2024-05-09 16:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('118', '1001', '14', 'ASSET', NULL, 'New Laptop', '0', '25000', 'Electronics F', 'No', '0', '25000', '3000', 'INV-006', NULL, 'No', 'No', NULL, '2024-05-11', '2024-05-11 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('119', '1001', '14', 'CAPEX', 'Labor', 'Contractor Services', '10000', '9500', 'Contractor G', 'No', '0', '-500', '1140', 'INV-007', NULL, 'No', 'No', NULL, '2024-05-13', '2024-05-13 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('120', '1001', '14', 'OPEX', 'Food', 'Client Dinner', '1500', '1800', 'Restaurant H', 'No', '0', '300', '216', 'INV-008', NULL, 'Yes', 'No', NULL, '2024-05-15', '2024-05-15 19:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('121', '1001', '14', 'OPEX', 'Gas', 'Company Van Fuel', '600', '600', 'Gas Station I', 'No', '0', '0', '72', 'INV-009', NULL, 'No', 'No', NULL, '2024-05-17', '2024-05-17 08:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('122', '1001', '14', 'OPEX', 'Toll', 'Expressway Toll', '150', '180', 'Toll Gate J', 'No', '0', '30', '22', 'INV-010', NULL, 'No', 'Yes', NULL, '2024-05-19', '2024-05-19 15:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('123', '1001', '14', 'OPEX', 'Parking', 'Event Parking', '80', '100', 'Event Venue K', 'No', '0', '20', '12', 'INV-011', NULL, 'Yes', 'No', NULL, '2024-05-21', '2024-05-21 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('124', '1001', '14', 'CAPEX', 'Labor', 'Subcontractor Payroll', '8000', '8500', 'Subcontractor L', 'No', '0', '500', '1020', 'INV-012', NULL, 'No', 'No', NULL, '2024-05-23', '2024-05-23 14:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('125', '1001', '14', 'ASSET', NULL, 'Project Camera Rental', '0', '0', 'Rental Company M', 'Yes', '500', '500', '60', 'INV-013', NULL, 'No', 'No', NULL, '2024-05-25', '2024-05-25 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('126', '1001', '14', 'OPEX', 'Food', 'Office Supplies', '700', '750', 'Supplier N', 'No', '0', '50', '90', 'INV-014', NULL, 'Yes', 'No', NULL, '2024-05-27', '2024-05-27 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('127', '1001', '14', 'OPEX', 'Gas', 'Generator Fuel', '400', '380', 'Fuel Depot O', 'No', '0', '-20', '46', 'INV-015', NULL, 'No', 'No', NULL, '2024-05-29', '2024-05-29 16:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('128', '1001', '14', 'CAPEX', 'Materials', 'Plumbing Fixtures', '3000', '3200', 'Plumbing Store P', 'No', '0', '200', '384', 'INV-016', NULL, 'No', 'Yes', NULL, '2024-05-31', '2024-05-31 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('129', '1001', '14', 'OPEX', 'Parking', 'Site Visit Parking', '120', '150', 'Parking Q', 'No', '0', '30', '18', 'INV-017', NULL, 'Yes', 'No', NULL, '2024-06-02', '2024-06-02 09:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('130', '1001', '14', 'OPEX', 'Toll', 'Bridge Toll', '90', '110', 'Toll Booth R', 'No', '0', '20', '13', 'INV-018', NULL, 'No', 'Yes', NULL, '2024-06-04', '2024-06-04 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('131', '1001', '14', 'CAPEX', 'Labor', 'Electrician Services', '7000', '7000', 'Electrician S', 'No', '0', '0', '840', 'INV-019', NULL, 'No', 'No', NULL, '2024-06-06', '2024-06-06 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('132', '1001', '14', 'OPEX', 'Food', 'Team Snacks', '300', '280', 'Grocery Store T', 'No', '0', '-20', '34', 'INV-020', NULL, 'No', 'No', NULL, '2024-06-08', '2024-06-08 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('133', '1001', '14', 'ASSET', NULL, 'Heavy Equipment Rental', '0', '0', 'Equipment Co. U', 'Yes', '1500', '1500', '180', 'INV-021', NULL, 'Yes', 'No', NULL, '2024-06-10', '2024-06-10 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('134', '1001', '14', 'OPEX', 'Gas', 'Company Car Fuel', '550', '600', 'Gas Station V', 'No', '0', '50', '72', 'INV-022', NULL, 'No', 'Yes', NULL, '2024-06-12', '2024-06-12 08:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('135', '1001', '14', 'CAPEX', 'Materials', 'Paint Supplies', '1200', '1300', 'Paint Store W', 'No', '0', '100', '156', 'INV-023', NULL, 'Yes', 'No', NULL, '2024-06-14', '2024-06-14 15:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('136', '1001', '14', 'OPEX', 'Parking', 'Meeting Parking', '70', '80', 'Parking Lot X', 'No', '0', '10', '10', 'INV-024', NULL, 'No', 'Yes', NULL, '2024-06-16', '2024-06-16 10:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('137', '1001', '14', 'OPEX', 'Toll', 'Highway Toll', '220', '200', 'Toll Road Y', 'No', '0', '-20', '24', 'INV-025', NULL, 'No', 'No', NULL, '2024-06-18', '2024-06-18 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('138', '1001', '14', 'CAPEX', 'Labor', 'Plumbing Contractor', '9000', '9200', 'Plumber Z', 'No', '0', '200', '1104', 'INV-026', NULL, 'Yes', 'No', NULL, '2024-06-20', '2024-06-20 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('139', '1001', '14', 'OPEX', 'Food', 'Workshop Catering', '2000', '2100', 'Caterer AA', 'No', '0', '100', '252', 'INV-027', NULL, 'No', 'Yes', NULL, '2024-06-22', '2024-06-22 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('140', '1001', '14', 'ASSET', NULL, 'Scaffolding Rental', '0', '0', 'Rental Firm BB', 'Yes', '800', '800', '96', 'INV-028', NULL, 'No', 'No', NULL, '2024-06-24', '2024-06-24 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('141', '1001', '14', 'OPEX', 'Gas', 'Delivery Van Fuel', '480', '500', 'Gas Station CC', 'No', '0', '20', '60', 'INV-029', NULL, 'Yes', 'No', NULL, '2024-06-26', '2024-06-26 16:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('142', '1001', '14', 'CAPEX', 'Materials', 'Roofing Supplies', '6000', '5800', 'Roofing Supplier DD', 'No', '0', '-200', '696', 'INV-030', NULL, 'No', 'No', NULL, '2024-06-28', '2024-06-28 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('143', '1001', '14', 'OPEX', 'Parking', 'Courier Parking', '50', '60', 'Parking EE', 'No', '0', '10', '7', 'INV-031', NULL, 'No', 'Yes', NULL, '2024-06-30', '2024-06-30 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('144', '1001', '14', 'OPEX', 'Toll', 'Turnpike Toll', '180', '200', 'Turnpike FF', 'No', '0', '20', '24', 'INV-032', NULL, 'Yes', 'No', NULL, '2024-07-02', '2024-07-02 11:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('145', '1001', '14', 'CAPEX', 'Labor', 'HVAC Installation', '12000', '12500', 'HVAC Services GG', 'No', '0', '500', '1500', 'INV-033', NULL, 'No', 'No', NULL, '2024-07-04', '2024-07-04 15:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('146', '1001', '14', 'OPEX', 'Food', 'Team Coffee Break', '400', '450', 'Coffee Shop HH', 'No', '0', '50', '54', 'INV-034', NULL, 'Yes', 'No', NULL, '2024-07-06', '2024-07-06 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('147', '1001', '14', 'ASSET', NULL, 'Forklift Rental', '0', '0', 'Forklift Rentals II', 'Yes', '1000', '1000', '120', 'INV-035', NULL, 'No', 'Yes', NULL, '2024-07-08', '2024-07-08 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('148', '1001', '14', 'OPEX', 'Gas', 'Business Trip Fuel', '700', '650', 'Gas Station JJ', 'No', '0', '-50', '78', 'INV-036', NULL, 'No', 'No', NULL, '2024-07-10', '2024-07-10 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('149', '1001', '14', 'CAPEX', 'Materials', 'Electrical Wiring', '2500', '2700', 'Electrical Supply KK', 'No', '0', '200', '324', 'INV-037', NULL, 'Yes', 'No', NULL, '2024-07-12', '2024-07-12 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('150', '1001', '14', 'OPEX', 'Parking', 'Delivery Parking', '90', '100', 'Parking LL', 'No', '0', '10', '12', 'INV-038', NULL, 'No', 'Yes', NULL, '2024-07-14', '2024-07-14 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('151', '1001', '14', 'OPEX', 'Toll', 'Skyway Toll', '130', '150', 'Skyway MM', 'No', '0', '20', '18', 'INV-039', NULL, 'Yes', 'No', NULL, '2024-07-16', '2024-07-16 16:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('152', '1001', '14', 'CAPEX', 'Labor', 'Painting Crew', '5000', '5200', 'Painting Co. NN', 'No', '0', '200', '624', 'INV-040', NULL, 'No', 'No', NULL, '2024-07-18', '2024-07-18 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('153', '1001', '14', 'OPEX', 'Food', 'Client Luncheon', '1800', '1900', 'Restaurant OO', 'No', '0', '100', '228', 'INV-041', NULL, 'Yes', 'No', NULL, '2024-07-20', '2024-07-20 12:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('154', '1001', '14', 'ASSET', NULL, 'Generator Rental', '0', '0', 'Power Rentals PP', 'Yes', '700', '700', '84', 'INV-042', NULL, 'No', 'No', NULL, '2024-07-22', '2024-07-22 09:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('155', '1001', '14', 'OPEX', 'Gas', 'Fleet Vehicle Fuel', '800', '750', 'Gas Station QQ', 'No', '0', '-50', '90', 'INV-043', NULL, 'No', 'No', NULL, '2024-07-24', '2024-07-24 08:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('156', '1001', '14', 'CAPEX', 'Materials', 'Wood Planks', '4000', '4200', 'Lumber Yard RR', 'No', '0', '200', '504', 'INV-044', NULL, 'No', 'Yes', NULL, '2024-07-26', '2024-07-26 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('157', '1001', '14', 'OPEX', 'Parking', 'Supplier Meeting Parking', '60', '70', 'Parking SS', 'No', '0', '10', '8', 'INV-045', NULL, 'Yes', 'No', NULL, '2024-07-28', '2024-07-28 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('158', '1001', '14', 'OPEX', 'Toll', 'Expressway Toll', '110', '120', 'Expressway TT', 'No', '0', '10', '14', 'INV-046', NULL, 'No', 'Yes', NULL, '2024-07-30', '2024-07-30 15:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('159', '1001', '14', 'CAPEX', 'Labor', 'Roofing Crew', '7500', '7800', 'Roofers UU', 'No', '0', '300', '936', 'INV-047', NULL, 'Yes', 'No', NULL, '2024-08-01', '2024-08-01 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('160', '1001', '14', 'OPEX', 'Food', 'Staff Refreshments', '350', '320', 'Cafe VV', 'No', '0', '-30', '38', 'INV-048', NULL, 'No', 'No', NULL, '2024-08-03', '2024-08-03 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('161', '1001', '14', 'ASSET', NULL, 'Power Tool Rental', '0', '0', 'Tool Rentals WW', 'Yes', '400', '400', '48', 'INV-049', NULL, 'No', 'No', NULL, '2024-08-05', '2024-08-05 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('162', '1001', '14', 'OPEX', 'Gas', 'Field Trip Fuel', '650', '680', 'Gas Station XX', 'No', '0', '30', '82', 'INV-050', NULL, 'Yes', 'No', NULL, '2024-08-07', '2024-08-07 08:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('163', '1001', '14', 'CAPEX', 'Materials', 'Insulation Materials', '3500', '3700', 'Insulation Supplier YY', 'No', '0', '200', '444', 'INV-051', NULL, 'No', 'Yes', NULL, '2024-08-09', '2024-08-09 15:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('164', '1001', '14', 'OPEX', 'Parking', 'Vendor Visit Parking', '40', '50', 'Parking ZZ', 'No', '0', '10', '6', 'INV-052', NULL, 'Yes', 'No', NULL, '2024-08-11', '2024-08-11 10:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('165', '1001', '14', 'OPEX', 'Toll', 'City Toll', '100', '110', 'City Toll AAA', 'No', '0', '10', '13', 'INV-053', NULL, 'No', 'Yes', NULL, '2024-08-13', '2024-08-13 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('166', '1001', '14', 'CAPEX', 'Labor', 'Landscaping Services', '4000', '4200', 'Landscapers BBB', 'No', '0', '200', '504', 'INV-054', NULL, 'No', 'No', NULL, '2024-08-15', '2024-08-15 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('167', '1001', '14', 'OPEX', 'Food', 'Team Dinner', '1000', '950', 'Restaurant CCC', 'No', '0', '-50', '114', 'INV-055', NULL, 'No', 'No', NULL, '2024-08-17', '2024-08-17 19:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('168', '1001', '14', 'ASSET', NULL, 'Projector Rental', '0', '0', 'AV Rentals DDD', 'Yes', '300', '300', '36', 'INV-056', NULL, 'No', 'No', NULL, '2024-08-19', '2024-08-19 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('169', '1001', '14', 'OPEX', 'Gas', 'Company Car Fuel', '500', '520', 'Gas Station EEE', 'No', '0', '20', '62', 'INV-057', NULL, 'Yes', 'No', NULL, '2024-08-21', '2024-08-21 08:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('170', '1001', '14', 'CAPEX', 'Materials', 'Flooring Materials', '7000', '7300', 'Flooring Supplier FFF', 'No', '0', '300', '876', 'INV-058', NULL, 'No', 'Yes', NULL, '2024-08-23', '2024-08-23 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('171', '1001', '14', 'OPEX', 'Parking', 'Site Delivery Parking', '80', '90', 'Parking GGG', 'No', '0', '10', '11', 'INV-059', NULL, 'Yes', 'No', NULL, '2024-08-25', '2024-08-25 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('172', '1001', '14', 'OPEX', 'Toll', 'Ring Road Toll', '160', '170', 'Ring Road HHH', 'No', '0', '10', '20', 'INV-060', NULL, 'No', 'Yes', NULL, '2024-08-27', '2024-08-27 16:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('173', '1001', '14', 'CAPEX', 'Labor', 'Demolition Crew', '6000', '6200', 'Demolition III', 'No', '0', '200', '744', 'INV-061', NULL, 'Yes', 'No', NULL, '2024-08-29', '2024-08-29 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('174', '1001', '14', 'OPEX', 'Food', 'Team Building Lunch', '2500', '2600', 'Caterer JJJ', 'No', '0', '100', '312', 'INV-062', NULL, 'No', 'Yes', NULL, '2024-08-31', '2024-08-31 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('175', '1001', '14', 'ASSET', NULL, 'Sound System Rental', '0', '0', 'Audio Visual KKK', 'Yes', '600', '600', '72', 'INV-063', NULL, 'No', 'No', NULL, '2024-09-02', '2024-09-02 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('176', '1001', '14', 'OPEX', 'Gas', 'Project Vehicle Fuel', '450', '430', 'Gas Station LLL', 'No', '0', '-20', '52', 'INV-064', NULL, 'No', 'No', NULL, '2024-09-04', '2024-09-04 16:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('177', '1001', '14', 'CAPEX', 'Materials', 'Window Installation', '8000', '8200', 'Window Supplier MMM', 'No', '0', '200', '984', 'INV-065', NULL, 'Yes', 'No', NULL, '2024-09-06', '2024-09-06 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('178', '1001', '14', 'OPEX', 'Parking', 'Office Visitor Parking', '30', '40', 'Parking NNN', 'No', '0', '10', '5', 'INV-066', NULL, 'No', 'Yes', NULL, '2024-09-08', '2024-09-08 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('179', '1001', '14', 'OPEX', 'Toll', 'Express Toll', '200', '220', 'Express Toll OOO', 'No', '0', '20', '26', 'INV-067', NULL, 'Yes', 'No', NULL, '2024-09-10', '2024-09-10 11:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('180', '1001', '14', 'CAPEX', 'Labor', 'Drywall Installation', '5500', '5800', 'Drywall Installers PPP', 'No', '0', '300', '696', 'INV-068', NULL, 'No', 'No', NULL, '2024-09-12', '2024-09-12 15:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('181', '1001', '14', 'OPEX', 'Food', 'Client Reception', '3000', '3100', 'Hotel QQQ', 'No', '0', '100', '372', 'INV-069', NULL, 'Yes', 'No', NULL, '2024-09-14', '2024-09-14 18:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('182', '1001', '14', 'ASSET', NULL, 'Lighting Equipment Rental', '0', '0', 'Light Rentals RRR', 'Yes', '900', '900', '108', 'INV-070', NULL, 'No', 'Yes', NULL, '2024-09-16', '2024-09-16 09:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('183', '1001', '14', 'OPEX', 'Gas', 'Rental Car Fuel', '350', '380', 'Gas Station SSS', 'No', '0', '30', '46', 'INV-071', NULL, 'No', 'No', NULL, '2024-09-18', '2024-09-18 08:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('184', '1001', '14', 'CAPEX', 'Materials', 'Door Frames', '1800', '1700', 'Door Supplier TTT', 'No', '0', '-100', '204', 'INV-072', NULL, 'No', 'No', NULL, '2024-09-20', '2024-09-20 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('185', '1001', '14', 'OPEX', 'Parking', 'Airport Parking', '200', '220', 'Airport Parking UUU', 'No', '0', '20', '26', 'INV-073', NULL, 'Yes', 'No', NULL, '2024-09-22', '2024-09-22 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('186', '1001', '14', 'OPEX', 'Toll', 'Suburban Toll', '80', '90', 'Suburban Toll VVV', 'No', '0', '10', '11', 'INV-074', NULL, 'No', 'Yes', NULL, '2024-09-24', '2024-09-24 15:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('187', '1001', '14', 'CAPEX', 'Labor', 'Tile Installation', '4500', '4800', 'Tile Layers WWW', 'No', '0', '300', '576', 'INV-075', NULL, 'Yes', 'No', NULL, '2024-09-26', '2024-09-26 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('188', '1001', '14', 'OPEX', 'Food', 'Office Party Food', '1500', '1600', 'Party Caterer XXX', 'No', '0', '100', '192', 'INV-076', NULL, 'No', 'Yes', NULL, '2024-09-28', '2024-09-28 17:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('189', '1001', '14', 'ASSET', NULL, 'Vehicle Rental', '0', '0', 'Car Rental YYY', 'Yes', '1200', '1200', '144', 'INV-077', NULL, 'No', 'No', NULL, '2024-09-30', '2024-09-30 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('190', '1001', '14', 'OPEX', 'Gas', 'Executive Car Fuel', '600', '630', 'Gas Station ZZZ', 'No', '0', '30', '76', 'INV-078', NULL, 'Yes', 'No', NULL, '2024-10-02', '2024-10-02 08:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('191', '1001', '14', 'CAPEX', 'Materials', 'Plasterboard', '2800', '2700', 'Building Supply AAAA', 'No', '0', '-100', '324', 'INV-079', NULL, 'No', 'No', NULL, '2024-10-04', '2024-10-04 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('192', '1001', '14', 'OPEX', 'Parking', 'Site Inspector Parking', '70', '80', 'Parking BBBB', 'No', '0', '10', '10', 'INV-080', NULL, 'No', 'Yes', NULL, '2024-10-06', '2024-10-06 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('193', '1001', '14', 'OPEX', 'Toll', 'Expressway Toll', '140', '160', 'Expressway CCCC', 'No', '0', '20', '19', 'INV-081', NULL, 'Yes', 'No', NULL, '2024-10-08', '2024-10-08 16:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('194', '1001', '14', 'CAPEX', 'Labor', 'Security Guard Services', '3000', '3200', 'Security Firm DDDD', 'No', '0', '200', '384', 'INV-082', NULL, 'No', 'No', NULL, '2024-10-10', '2024-10-10 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('195', '1001', '14', 'OPEX', 'Food', 'Client Meeting Snacks', '500', '520', 'Cafe EEEE', 'No', '0', '20', '62', 'INV-083', NULL, 'Yes', 'No', NULL, '2024-10-12', '2024-10-12 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('196', '1001', '14', 'ASSET', NULL, 'Crane Rental', '0', '0', 'Crane Hire FFFF', 'Yes', '2000', '2000', '240', 'INV-084', NULL, 'No', 'No', NULL, '2024-10-14', '2024-10-14 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('197', '1001', '14', 'OPEX', 'Gas', 'Site Generator Fuel', '400', '390', 'Fuel Supplier GGGG', 'No', '0', '-10', '47', 'INV-085', NULL, 'No', 'No', NULL, '2024-10-16', '2024-10-16 08:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('198', '1001', '14', 'CAPEX', 'Materials', 'Concrete Mix', '5000', '5300', 'Concrete Supply HHHH', 'No', '0', '300', '636', 'INV-086', NULL, 'No', 'Yes', NULL, '2024-10-18', '2024-10-18 15:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('199', '1001', '14', 'OPEX', 'Parking', 'Event Staff Parking', '100', '110', 'Event Parking IIII', 'No', '0', '10', '13', 'INV-087', NULL, 'Yes', 'No', NULL, '2024-10-20', '2024-10-20 10:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('200', '1001', '14', 'OPEX', 'Toll', 'Bridge Toll', '120', '130', 'Toll Plaza JJJJ', 'No', '0', '10', '16', 'INV-088', NULL, 'No', 'Yes', NULL, '2024-10-22', '2024-10-22 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('201', '1001', '14', 'CAPEX', 'Labor', 'Cleaning Services', '2000', '2100', 'Cleaning Co. KKKK', 'No', '0', '100', '252', 'INV-089', NULL, 'No', 'No', NULL, '2024-10-24', '2024-10-24 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('202', '1001', '14', 'OPEX', 'Food', 'Office Pantry Stock', '800', '750', 'Grocery Store LLLL', 'No', '0', '-50', '90', 'INV-090', NULL, 'No', 'No', NULL, '2024-10-26', '2024-10-26 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('203', '1001', '14', 'ASSET', NULL, 'Air Compressor Rental', '0', '0', 'Compressor Rentals MMMM', 'Yes', '500', '500', '60', 'INV-091', NULL, 'Yes', 'No', NULL, '2024-10-28', '2024-10-28 09:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('204', '1001', '14', 'OPEX', 'Gas', 'Company Van Fuel', '550', '580', 'Gas Station NNNN', 'No', '0', '30', '70', 'INV-092', NULL, 'No', 'Yes', NULL, '2024-10-30', '2024-10-30 08:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('205', '1001', '14', 'CAPEX', 'Materials', 'Safety Equipment', '1000', '1200', 'Safety Supply OOOO', 'No', '0', '200', '144', 'INV-093', NULL, 'Yes', 'No', NULL, '2024-11-01', '2024-11-01 13:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('206', '1001', '14', 'OPEX', 'Parking', 'Courier Delivery Parking', '40', '50', 'Parking PPPP', 'No', '0', '10', '6', 'INV-094', NULL, 'No', 'Yes', NULL, '2024-11-03', '2024-11-03 10:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('207', '1001', '14', 'OPEX', 'Toll', 'City Bypass Toll', '90', '100', 'Bypass QQQQ', 'No', '0', '10', '12', 'INV-095', NULL, 'Yes', 'No', NULL, '2024-11-05', '2024-11-05 16:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('208', '1001', '14', 'CAPEX', 'Labor', 'Consulting Services', '15000', '15500', 'Consulting Firm RRRR', 'No', '0', '500', '1860', 'INV-096', NULL, 'No', 'No', NULL, '2024-11-07', '2024-11-07 11:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('209', '1001', '14', 'OPEX', 'Food', 'Team Lunch', '900', '850', 'Restaurant SSSS', 'No', '0', '-50', '102', 'INV-097', NULL, 'No', 'No', NULL, '2024-11-09', '2024-11-09 12:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('210', '1001', '14', 'ASSET', NULL, 'Concrete Mixer Rental', '0', '0', 'Mixer Rentals TTTT', 'Yes', '700', '700', '84', 'INV-098', NULL, 'No', 'No', NULL, '2024-11-11', '2024-11-11 09:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('211', '1001', '14', 'OPEX', 'Gas', 'Business Travel Fuel', '600', '620', 'Gas Station UUUU', 'No', '0', '20', '74', 'INV-099', NULL, 'Yes', 'No', NULL, '2024-11-13', '2024-11-13 08:30:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('212', '1001', '14', 'CAPEX', 'Materials', 'Plumbing Pipes', '3200', '3000', 'Plumbing Supply VVVV', 'No', '0', '-200', '360', 'INV-100', NULL, 'No', 'No', NULL, '2024-11-15', '2024-11-15 14:00:00', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('213', '1001', '0', 'ASSET', '', 'add record test', '1000', '0', 'add record test', 'Yes', '1500', '-500', '0', '', '', 'No', 'Yes', '213', '2025-05-19', '2025-05-19 08:29:09', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('214', '1001', '1', 'ASSET', '', 'add record test', '1000', '0', 'add record test', 'Yes', '1500', '-500', '0', '', '', 'No', 'Yes', '213', '2025-05-19', '2025-05-19 08:29:09', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('215', '1001', '1', 'ASSET', '', 'test activity log', '0', '0', 'test activity log', 'Yes', '1000', '-1000', '0', '', 'test activity log', 'No', 'Yes', '215', '2025-05-22', '2025-05-22 10:29:08', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('216', '1001', '1', 'ASSET', '', 'test activity log', '0', '0', 'test activity log', 'Yes', '1000', '-1000', '0', '', 'test activity log', 'No', 'Yes', '215', '2025-05-22', '2025-05-22 10:29:08', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('217', '1001', '11', 'ASSET', '', 'test log and session', '0', '0', 'test log and session', 'Yes', '100', '-100', '0', '', 'test log and session', 'No', 'Yes', '217', '2025-05-22', '2025-05-22 10:33:37', '1001', NULL, NULL);
INSERT INTO `expense` VALUES ('218', '1001', '1', 'ASSET', '', 'test log and session', '0', '0', 'test log and session', 'Yes', '100', '-100', '0', '', 'test log and session', 'No', 'Yes', '217', '2025-05-22', '2025-05-22 10:33:37', '1001', NULL, NULL);


-- Table structure for `login_attempts`
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `attempt_time` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `login_attempts`
INSERT INTO `login_attempts` VALUES ('9', '1001', '2025-05-19 12:45:52', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('10', '1001', '2025-05-20 08:10:47', '::1', '1', 'Login successful');
INSERT INTO `login_attempts` VALUES ('11', '1001', '2025-05-20 08:14:31', '::1', '1', 'Login successful');
INSERT INTO `login_attempts` VALUES ('12', '1001', '2025-05-20 08:46:39', '::1', '1', 'Login successful');
INSERT INTO `login_attempts` VALUES ('13', '1001', '2025-05-20 09:17:45', '::1', '0', 'Account inactive');
INSERT INTO `login_attempts` VALUES ('14', '1001', '2025-05-20 09:17:49', '::1', '0', 'Account inactive');
INSERT INTO `login_attempts` VALUES ('15', '1001', '2025-05-20 09:18:39', '::1', '1', 'Login successful');
INSERT INTO `login_attempts` VALUES ('16', '1001', '2025-05-20 09:56:05', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('17', '1001', '2025-05-20 10:01:56', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('18', '1001', '2025-05-20 10:02:30', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('19', '1001', '2025-05-20 10:02:46', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('21', '1001', '2025-05-20 10:03:00', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('26', '1001', '2025-05-20 14:02:07', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('27', '1001', '2025-05-20 14:18:44', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('28', '1003', '2025-05-20 15:15:10', '::1', '1', '2FA setup completed');
INSERT INTO `login_attempts` VALUES ('29', '1003', '2025-05-20 15:15:54', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('30', '1003', '2025-05-20 15:16:17', '::1', '0', 'Failed password');
INSERT INTO `login_attempts` VALUES ('31', '1003', '2025-05-20 15:16:23', '::1', '0', 'Failed password');
INSERT INTO `login_attempts` VALUES ('32', '1003', '2025-05-20 15:16:29', '::1', '0', 'Account locked - too many failed attempts');
INSERT INTO `login_attempts` VALUES ('33', '1003', '2025-05-20 15:19:29', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('34', '1003', '2025-05-20 15:19:53', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('35', '1003', '2025-05-20 15:20:07', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('36', '1003', '2025-05-20 15:41:00', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('37', '1001', '2025-05-20 15:41:32', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('38', '1001', '2025-05-20 15:43:21', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('39', '1001', '2025-05-20 15:43:27', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('40', '1001', '2025-05-20 15:45:24', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('41', '1004', '2025-05-20 15:59:20', '::1', '1', '2FA setup completed');
INSERT INTO `login_attempts` VALUES ('42', '1001', '2025-05-20 16:00:01', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('43', '1001', '2025-05-21 07:36:41', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('44', '1001', '2025-05-21 08:02:59', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('45', '1001', '2025-05-21 08:03:31', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('46', '1001', '2025-05-21 08:10:50', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('47', '1001', '2025-05-21 08:10:55', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('48', '1001', '2025-05-21 08:56:06', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('49', '1001', '2025-05-21 08:56:22', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('50', '1001', '2025-05-22 10:20:25', '::1', '1', 'User logged out');
INSERT INTO `login_attempts` VALUES ('51', '1001', '2025-05-22 10:28:42', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('52', '1001', '2025-05-22 10:33:07', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('53', '1003', '2025-05-22 10:34:55', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('54', '1001', '2025-05-22 14:09:38', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('55', '1001', '2025-05-22 17:18:46', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('56', '1001', '2025-05-23 07:24:22', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('57', '1001', '2025-05-23 15:24:07', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('58', '1001', '2025-05-23 18:41:20', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('59', '1001', '2025-05-23 18:54:29', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('60', '1001', '2025-05-23 18:55:43', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('61', '1001', '2025-05-23 18:57:56', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('62', '1001', '2025-05-23 18:59:53', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('63', '1001', '2025-05-23 19:00:49', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('64', '1001', '2025-05-23 22:43:44', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('65', '1001', '2025-05-24 04:09:42', '::1', '1', 'Admin login (2FA skipped)');
INSERT INTO `login_attempts` VALUES ('66', '1006', '2025-05-24 04:12:57', '::1', '1', '2FA setup completed');
INSERT INTO `login_attempts` VALUES ('67', '1003', '2025-05-24 05:05:54', '::1', '0', 'Failed password');
INSERT INTO `login_attempts` VALUES ('68', '1003', '2025-05-24 05:06:05', '::1', '0', 'Failed password');
INSERT INTO `login_attempts` VALUES ('69', '1003', '2025-05-24 05:06:32', '::1', '0', 'Account locked - too many failed attempts');
INSERT INTO `login_attempts` VALUES ('70', '1003', '2025-05-24 05:07:01', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('71', '1003', '2025-05-24 05:07:48', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('72', '1001', '2025-05-24 06:32:58', '::1', '1', 'Superadmin login (2FA bypassed)');
INSERT INTO `login_attempts` VALUES ('73', '1006', '2025-05-24 06:34:35', '::1', '1', '2FA setup completed');
INSERT INTO `login_attempts` VALUES ('74', '1006', '2025-05-24 06:35:24', '::1', '1', '2FA completed: authenticator');
INSERT INTO `login_attempts` VALUES ('75', '1006', '2025-05-24 06:35:46', '::1', '0', 'Failed password');
INSERT INTO `login_attempts` VALUES ('76', '1006', '2025-05-24 06:35:52', '::1', '0', 'Failed password');
INSERT INTO `login_attempts` VALUES ('77', '1006', '2025-05-24 06:36:13', '::1', '0', 'Account locked - too many failed attempts');
INSERT INTO `login_attempts` VALUES ('78', '1006', '2025-05-24 06:36:30', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('79', '1006', '2025-05-24 06:36:53', '::1', '0', 'Account locked');
INSERT INTO `login_attempts` VALUES ('80', '1006', '2025-05-24 06:37:01', '::1', '1', 'Account unlocked via security question');
INSERT INTO `login_attempts` VALUES ('81', '1006', '2025-05-24 06:37:21', '::1', '1', '2FA completed: authenticator');
INSERT INTO `login_attempts` VALUES ('82', '1001', '2025-05-24 06:38:06', '::1', '1', 'Superadmin login (2FA bypassed)');
INSERT INTO `login_attempts` VALUES ('83', '1001', '2025-05-24 07:59:43', '::1', '1', 'Superadmin login (2FA bypassed)');
INSERT INTO `login_attempts` VALUES ('84', '1006', '2025-05-24 08:10:45', '::1', '1', '2FA completed: authenticator');
INSERT INTO `login_attempts` VALUES ('85', '1001', '2025-05-24 08:12:02', '::1', '1', 'Superadmin login (2FA bypassed)');
INSERT INTO `login_attempts` VALUES ('86', '1001', '2025-05-24 10:03:57', '::1', '1', 'Superadmin login (2FA bypassed)');
INSERT INTO `login_attempts` VALUES ('87', '1001', '2025-05-24 13:54:41', '::1', '1', 'Superadmin login (2FA bypassed)');


-- Table structure for `payroll`
DROP TABLE IF EXISTS `payroll`;
CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `date_generated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`payroll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `payroll`
INSERT INTO `payroll` VALUES ('1', '1001', '2025-04-01', '2025-04-15', '35000.00', '28000.00', '2000.00', '1500.00', '1000.00', '1200.00', '400.00', '100.00', '0.00', '50.00', '1750.00', '3000.00', '30250.00', 'Bank Transfer', 'Good performance bonus included', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('2', '1001', '2025-04-16', '2025-04-30', '34000.00', '27000.00', '1500.00', '1400.00', '600.00', '1200.00', '400.00', '100.00', '200.00', '100.00', '2000.00', '2800.00', '31200.00', 'Cash', 'Overtime reduced due to holiday', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('3', '1001', '2025-05-01', '2025-05-15', '36000.00', '28000.00', '2500.00', '1600.00', '900.00', '1200.00', '400.00', '100.00', '100.00', '150.00', '1750.00', '3200.00', '30850.00', 'Bank Transfer', 'Included mid-month bonus', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('4', '1001', '2025-05-16', '2025-05-31', '35500.00', '28000.00', '1800.00', '1300.00', '400.00', '1200.00', '400.00', '100.00', '0.00', '200.00', '1700.00', '3100.00', '30600.00', 'Cash', 'No remarks', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('5', '1002', '2025-04-01', '2025-04-15', '28000.00', '23000.00', '1000.00', '1200.00', '800.00', '1100.00', '350.00', '90.00', '0.00', '30.00', '1470.00', '2500.00', '26030.00', 'Bank Transfer', 'Consistent attendance', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('6', '1002', '2025-04-16', '2025-04-30', '27500.00', '22500.00', '900.00', '1100.00', '0.00', '1100.00', '350.00', '90.00', '0.00', '40.00', '1480.00', '2450.00', '26070.00', 'Cash', 'No bonus this period', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('8', '1002', '2025-05-16', '2025-05-31', '28500.00', '23000.00', '1100.00', '1250.00', '500.00', '1100.00', '350.00', '90.00', '0.00', '50.00', '1490.00', '2550.00', '26910.00', 'Cash', 'Slightly reduced overtime', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('9', '1003', '2025-04-01', '2025-04-15', '42000.00', '35000.00', '3000.00', '2000.00', '1500.00', '1400.00', '450.00', '120.00', '0.00', '60.00', '2030.00', '3500.00', '38470.00', 'Bank Transfer', 'High performance bonus awarded', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('10', '1003', '2025-04-16', '2025-04-30', '41000.00', '34500.00', '2800.00', '1900.00', '1300.00', '1400.00', '450.00', '120.00', '200.00', '100.00', '2270.00', '3300.00', '37330.00', 'Cash', 'Loan deducted this period', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('11', '1003', '2025-05-01', '2025-05-15', '43000.00', '35000.00', '3200.00', '2100.00', '1600.00', '1400.00', '450.00', '120.00', '150.00', '80.00', '2100.00', '3600.00', '38900.00', 'Bank Transfer', 'Includes special project bonus', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('12', '1003', '2025-05-16', '2025-05-31', '42500.00', '35000.00', '3100.00', '2050.00', '1400.00', '1400.00', '450.00', '120.00', '0.00', '120.00', '1970.00', '3550.00', '38980.00', 'Cash', 'Regular period', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('13', '1004', '2025-04-01', '2025-04-15', '25000.00', '21000.00', '1000.00', '900.00', '600.00', '1000.00', '300.00', '80.00', '0.00', '20.00', '1400.00', '2200.00', '23600.00', 'Bank Transfer', 'Good attendance', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('14', '1004', '2025-04-16', '2025-04-30', '24500.00', '20500.00', '900.00', '850.00', '400.00', '1000.00', '300.00', '80.00', '100.00', '30.00', '1510.00', '2150.00', '23340.00', 'Cash', 'Loan deducted', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('15', '1004', '2025-05-01', '2025-05-15', '25500.00', '21000.00', '1100.00', '950.00', '700.00', '1000.00', '300.00', '80.00', '50.00', '40.00', '1370.00', '2300.00', '24130.00', 'Bank Transfer', 'Bonus given', '2025-05-16 07:08:13');
INSERT INTO `payroll` VALUES ('16', '1004', '2025-05-16', '2025-05-31', '25200.00', '21000.00', '1000.00', '900.00', '500.00', '1000.00', '300.00', '80.00', '0.00', '50.00', '1430.00', '2250.00', '23920.00', 'Cash', 'Regular payment', '2025-05-16 07:08:13');


-- Table structure for `projects`
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `edited_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `project_id` (`project_code`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `projects`
INSERT INTO `projects` VALUES ('1', 'CORPORATE', 'Corporate', 'Carlo', 'Geraga', 'Malaya Solar Energies', 'Expense Tracker', '999999999', 'carlo.geraga@gmail.com', NULL, NULL, NULL, NULL, NULL, '0', '2025-05-16 00:36:12', '1', '2025-05-16 00:36:12', NULL);
INSERT INTO `projects` VALUES ('11', 'MEGA', 'CAMSUR', 'Apullo', 'Quibbs', 'Cardboard Co.', 'Cardboard manufacturing plant', '2147483647', 'camsur@gmail.com', '', '', '', '', '', '0', '2025-05-14 00:00:00', '1001', '2025-05-16 22:17:44', '1001');
INSERT INTO `projects` VALUES ('13', 'ERN', 'ELRON', 'Tung', 'Sahur', 'Elron Incorporated', 'Okay', '2147483647', 'tung.sahur@gmail.com', NULL, NULL, NULL, NULL, NULL, '0', '2025-05-15 00:00:00', '1001', '2025-05-15 19:19:48', NULL);
INSERT INTO `projects` VALUES ('14', 'BAD', 'bading', 'Aiz', 'Limit', 'Kaba-klaan', 'k fine', '2147483647', 'aiz.limit@bading.com', NULL, NULL, NULL, NULL, NULL, '0', '2025-05-16 00:00:00', '1001', '2025-05-16 23:17:47', NULL);


-- Table structure for `security_questions`
DROP TABLE IF EXISTS `security_questions`;
CREATE TABLE `security_questions` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_text` varchar(255) NOT NULL,
  PRIMARY KEY (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `security_questions`
INSERT INTO `security_questions` VALUES ('1', 'What was the name of your first pet?');
INSERT INTO `security_questions` VALUES ('2', 'What is your mother\'s maiden name?');
INSERT INTO `security_questions` VALUES ('3', 'What was the name of your favorite character?');
INSERT INTO `security_questions` VALUES ('4', 'In what city were you born?');
INSERT INTO `security_questions` VALUES ('5', 'What is the name of your favorite childhood teacher');
INSERT INTO `security_questions` VALUES ('6', 'What was the name of your first school?');
INSERT INTO `security_questions` VALUES ('7', 'What is your favorite book?');
INSERT INTO `security_questions` VALUES ('8', 'What is your favorite color?');


-- Table structure for `subcategories`
DROP TABLE IF EXISTS `subcategories`;
CREATE TABLE `subcategories` (
  `subcategory_id` int(11) NOT NULL AUTO_INCREMENT,
  `subcategory_name` varchar(40) NOT NULL,
  `category_name` varchar(40) NOT NULL,
  PRIMARY KEY (`subcategory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `subcategories`
INSERT INTO `subcategories` VALUES ('1', 'Gas', 'OPEX');
INSERT INTO `subcategories` VALUES ('2', 'Food', 'OPEX');
INSERT INTO `subcategories` VALUES ('3', 'Toll', 'OPEX');
INSERT INTO `subcategories` VALUES ('4', 'Parking', 'OPEX');
INSERT INTO `subcategories` VALUES ('5', 'Materials', 'CAPEX');
INSERT INTO `subcategories` VALUES ('6', 'Labors', 'CAPEX');


-- Table structure for `user_activity_log`
DROP TABLE IF EXISTS `user_activity_log`;
CREATE TABLE `user_activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `page` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_activity_timestamp` (`user_id`,`timestamp`),
  KEY `idx_user_activity_action` (`action`),
  KEY `idx_user_activity_page` (`page`),
  CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=360 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `user_activity_log`
INSERT INTO `user_activity_log` VALUES ('1', '1001', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:00:46');
INSERT INTO `user_activity_log` VALUES ('2', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:00:50');
INSERT INTO `user_activity_log` VALUES ('3', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:00:50');
INSERT INTO `user_activity_log` VALUES ('4', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:01:32');
INSERT INTO `user_activity_log` VALUES ('5', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:42:45');
INSERT INTO `user_activity_log` VALUES ('6', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:42:46');
INSERT INTO `user_activity_log` VALUES ('7', '1001', 'add', 'ms_project.php', 'add new project', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:04');
INSERT INTO `user_activity_log` VALUES ('8', '1001', 'edit', 'ms_project.php', 'edit project record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:14');
INSERT INTO `user_activity_log` VALUES ('9', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:23');
INSERT INTO `user_activity_log` VALUES ('10', '1001', 'edit', 'ms_records.php', 'edit project details', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:28');
INSERT INTO `user_activity_log` VALUES ('11', '1001', 'delete', 'ms_records.php', 'delete project', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:33');
INSERT INTO `user_activity_log` VALUES ('12', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:34');
INSERT INTO `user_activity_log` VALUES ('13', '1001', 'add', 'ms_project.php', 'add new project', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:52');
INSERT INTO `user_activity_log` VALUES ('14', '1001', 'delete', 'ms_project.php', 'delete project record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:54');
INSERT INTO `user_activity_log` VALUES ('15', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:44:56');
INSERT INTO `user_activity_log` VALUES ('16', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:45:01');
INSERT INTO `user_activity_log` VALUES ('17', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:45:02');
INSERT INTO `user_activity_log` VALUES ('18', '1001', 'add', 'ms_records.php', 'add expense record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:45:31');
INSERT INTO `user_activity_log` VALUES ('19', '1001', 'edit', 'ms_records.php', 'update loss record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:45:31');
INSERT INTO `user_activity_log` VALUES ('20', '1001', 'add', 'ms_records.php', 'add loss record to corporate', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:45:32');
INSERT INTO `user_activity_log` VALUES ('21', '1001', 'edit', 'ms_records.php', 'edit record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:45:44');
INSERT INTO `user_activity_log` VALUES ('22', '1001', 'delete', 'ms_records.php', 'delete loss record from corporate', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:45:48');
INSERT INTO `user_activity_log` VALUES ('23', '1001', 'delete', 'ms_records.php', 'delete expense record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:45:48');
INSERT INTO `user_activity_log` VALUES ('24', '1001', 'add', 'ms_records.php', 'add expense record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:17');
INSERT INTO `user_activity_log` VALUES ('25', '1001', 'edit', 'ms_records.php', 'update loss record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:17');
INSERT INTO `user_activity_log` VALUES ('26', '1001', 'add', 'ms_records.php', 'add loss record to corporate', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:17');
INSERT INTO `user_activity_log` VALUES ('27', '1001', 'add', 'ms_records.php', 'add expense record to assets', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:17');
INSERT INTO `user_activity_log` VALUES ('28', '1001', 'delete', 'ms_records.php', 'delete loss record from corporate', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:20');
INSERT INTO `user_activity_log` VALUES ('29', '1001', 'delete', 'ms_records.php', 'delete expense record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:20');
INSERT INTO `user_activity_log` VALUES ('30', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:31');
INSERT INTO `user_activity_log` VALUES ('31', '1001', 'add', 'ms_assets.php', 'add untracked asset', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:45');
INSERT INTO `user_activity_log` VALUES ('32', '1001', 'edit', 'ms_assets.php', 'edit asset record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:46:56');
INSERT INTO `user_activity_log` VALUES ('33', '1001', 'delete', 'ms_assets.php', 'delete asset record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:47:16');
INSERT INTO `user_activity_log` VALUES ('34', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:47:19');
INSERT INTO `user_activity_log` VALUES ('35', '1001', 'add', 'ms_records.php', 'add expense record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:47:50');
INSERT INTO `user_activity_log` VALUES ('36', '1001', 'edit', 'ms_records.php', 'update loss record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:47:50');
INSERT INTO `user_activity_log` VALUES ('37', '1001', 'add', 'ms_records.php', 'add loss record to corporate', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:47:50');
INSERT INTO `user_activity_log` VALUES ('38', '1001', 'edit', 'ms_records.php', 'edit record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:48:05');
INSERT INTO `user_activity_log` VALUES ('39', '1001', 'delete', 'ms_records.php', 'delete loss record from corporate', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:48:11');
INSERT INTO `user_activity_log` VALUES ('40', '1001', 'delete', 'ms_records.php', 'delete expense record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:48:11');
INSERT INTO `user_activity_log` VALUES ('41', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:48:13');
INSERT INTO `user_activity_log` VALUES ('42', '1001', 'add', 'ms_workforce.php', 'add employee', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:48:59');
INSERT INTO `user_activity_log` VALUES ('43', '1001', 'add', 'ms_workforce.php', 'add user', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:49:17');
INSERT INTO `user_activity_log` VALUES ('44', '1001', 'access', 'get_account_details.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:49:22');
INSERT INTO `user_activity_log` VALUES ('45', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:49:30');
INSERT INTO `user_activity_log` VALUES ('46', '1001', 'access', 'ms_payroll.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:49:35');
INSERT INTO `user_activity_log` VALUES ('47', '1001', 'edit', 'ms_payroll.php', 'edit payroll record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:50:50');
INSERT INTO `user_activity_log` VALUES ('48', '1001', 'delete', 'ms_payroll.php', 'delete payroll record', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:51:15');
INSERT INTO `user_activity_log` VALUES ('49', '1001', 'access', 'ms_vendors.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:51:17');
INSERT INTO `user_activity_log` VALUES ('50', '1001', 'add', 'ms_vendor.php', 'Add record ', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:13');
INSERT INTO `user_activity_log` VALUES ('51', '1001', 'edit', 'ms_vendors.php', 'Edit record: 11', '11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:25');
INSERT INTO `user_activity_log` VALUES ('52', '1001', 'delete', 'ms_vendors.php', 'delete record: ', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:38');
INSERT INTO `user_activity_log` VALUES ('53', '1001', 'access', 'ms_reports.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:40');
INSERT INTO `user_activity_log` VALUES ('54', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:48');
INSERT INTO `user_activity_log` VALUES ('55', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:50');
INSERT INTO `user_activity_log` VALUES ('56', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:51');
INSERT INTO `user_activity_log` VALUES ('57', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:52');
INSERT INTO `user_activity_log` VALUES ('58', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:54');
INSERT INTO `user_activity_log` VALUES ('59', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:55');
INSERT INTO `user_activity_log` VALUES ('60', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:52:59');
INSERT INTO `user_activity_log` VALUES ('61', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:04');
INSERT INTO `user_activity_log` VALUES ('62', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:12');
INSERT INTO `user_activity_log` VALUES ('63', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:15');
INSERT INTO `user_activity_log` VALUES ('64', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:17');
INSERT INTO `user_activity_log` VALUES ('65', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:22');
INSERT INTO `user_activity_log` VALUES ('66', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:24');
INSERT INTO `user_activity_log` VALUES ('67', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:24');
INSERT INTO `user_activity_log` VALUES ('68', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:25');
INSERT INTO `user_activity_log` VALUES ('69', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:53:41');
INSERT INTO `user_activity_log` VALUES ('70', '1001', 'export', 'ms_reports.php', 'export report', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:54:02');
INSERT INTO `user_activity_log` VALUES ('71', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:55:19');
INSERT INTO `user_activity_log` VALUES ('72', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:55:20');
INSERT INTO `user_activity_log` VALUES ('73', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:55:21');
INSERT INTO `user_activity_log` VALUES ('74', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:55:22');
INSERT INTO `user_activity_log` VALUES ('75', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:55:22');
INSERT INTO `user_activity_log` VALUES ('76', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:55:23');
INSERT INTO `user_activity_log` VALUES ('77', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 19:55:24');
INSERT INTO `user_activity_log` VALUES ('78', '1001', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:43:27');
INSERT INTO `user_activity_log` VALUES ('79', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:43:44');
INSERT INTO `user_activity_log` VALUES ('80', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:43:44');
INSERT INTO `user_activity_log` VALUES ('81', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:43:47');
INSERT INTO `user_activity_log` VALUES ('82', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:43:49');
INSERT INTO `user_activity_log` VALUES ('83', '1001', 'access', 'ms_vendors.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:43:52');
INSERT INTO `user_activity_log` VALUES ('84', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:43:56');
INSERT INTO `user_activity_log` VALUES ('85', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:20');
INSERT INTO `user_activity_log` VALUES ('86', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:23');
INSERT INTO `user_activity_log` VALUES ('87', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:24');
INSERT INTO `user_activity_log` VALUES ('88', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:24');
INSERT INTO `user_activity_log` VALUES ('89', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:25');
INSERT INTO `user_activity_log` VALUES ('90', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:26');
INSERT INTO `user_activity_log` VALUES ('91', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:26');
INSERT INTO `user_activity_log` VALUES ('92', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:27');
INSERT INTO `user_activity_log` VALUES ('93', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:27');
INSERT INTO `user_activity_log` VALUES ('94', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:28');
INSERT INTO `user_activity_log` VALUES ('95', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:44:29');
INSERT INTO `user_activity_log` VALUES ('96', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-23 22:48:57');
INSERT INTO `user_activity_log` VALUES ('97', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:11');
INSERT INTO `user_activity_log` VALUES ('98', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:15');
INSERT INTO `user_activity_log` VALUES ('99', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:17');
INSERT INTO `user_activity_log` VALUES ('100', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:17');
INSERT INTO `user_activity_log` VALUES ('101', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:18');
INSERT INTO `user_activity_log` VALUES ('102', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:19');
INSERT INTO `user_activity_log` VALUES ('103', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:20');
INSERT INTO `user_activity_log` VALUES ('104', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:21');
INSERT INTO `user_activity_log` VALUES ('105', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:24');
INSERT INTO `user_activity_log` VALUES ('106', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:26');
INSERT INTO `user_activity_log` VALUES ('107', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:42');
INSERT INTO `user_activity_log` VALUES ('108', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:43');
INSERT INTO `user_activity_log` VALUES ('109', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:43');
INSERT INTO `user_activity_log` VALUES ('110', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:44');
INSERT INTO `user_activity_log` VALUES ('111', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:45');
INSERT INTO `user_activity_log` VALUES ('112', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:45');
INSERT INTO `user_activity_log` VALUES ('113', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:46');
INSERT INTO `user_activity_log` VALUES ('114', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:47');
INSERT INTO `user_activity_log` VALUES ('115', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:48');
INSERT INTO `user_activity_log` VALUES ('116', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:49');
INSERT INTO `user_activity_log` VALUES ('117', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:50');
INSERT INTO `user_activity_log` VALUES ('118', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:51');
INSERT INTO `user_activity_log` VALUES ('119', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:52');
INSERT INTO `user_activity_log` VALUES ('120', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:54');
INSERT INTO `user_activity_log` VALUES ('121', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:56');
INSERT INTO `user_activity_log` VALUES ('122', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:57');
INSERT INTO `user_activity_log` VALUES ('123', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:53:58');
INSERT INTO `user_activity_log` VALUES ('124', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:54:05');
INSERT INTO `user_activity_log` VALUES ('125', '1001', 'access', 'ms_vendors.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:54:07');
INSERT INTO `user_activity_log` VALUES ('126', '1001', 'access', 'ms_payroll.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:54:08');
INSERT INTO `user_activity_log` VALUES ('127', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:54:10');
INSERT INTO `user_activity_log` VALUES ('128', '1001', 'access', 'ms_payroll.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:54:12');
INSERT INTO `user_activity_log` VALUES ('129', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:54:15');
INSERT INTO `user_activity_log` VALUES ('130', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 03:58:04');
INSERT INTO `user_activity_log` VALUES ('131', '1001', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 04:05:25');
INSERT INTO `user_activity_log` VALUES ('132', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 04:09:42');
INSERT INTO `user_activity_log` VALUES ('133', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 04:09:42');
INSERT INTO `user_activity_log` VALUES ('134', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 04:09:45');
INSERT INTO `user_activity_log` VALUES ('135', '1001', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 04:12:14');
INSERT INTO `user_activity_log` VALUES ('136', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:32:58');
INSERT INTO `user_activity_log` VALUES ('137', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:32:58');
INSERT INTO `user_activity_log` VALUES ('138', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:00');
INSERT INTO `user_activity_log` VALUES ('139', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:03');
INSERT INTO `user_activity_log` VALUES ('140', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:04');
INSERT INTO `user_activity_log` VALUES ('141', '1001', 'access', 'ms_payroll.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:05');
INSERT INTO `user_activity_log` VALUES ('142', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:06');
INSERT INTO `user_activity_log` VALUES ('143', '1001', 'access', 'ms_payroll.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:13');
INSERT INTO `user_activity_log` VALUES ('144', '1001', 'access', 'ms_vendors.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:15');
INSERT INTO `user_activity_log` VALUES ('145', '1001', 'access', 'ms_payroll.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:17');
INSERT INTO `user_activity_log` VALUES ('146', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:17');
INSERT INTO `user_activity_log` VALUES ('147', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:18');
INSERT INTO `user_activity_log` VALUES ('148', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:19');
INSERT INTO `user_activity_log` VALUES ('149', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:20');
INSERT INTO `user_activity_log` VALUES ('150', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:21');
INSERT INTO `user_activity_log` VALUES ('151', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:22');
INSERT INTO `user_activity_log` VALUES ('152', '1001', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:33:42');
INSERT INTO `user_activity_log` VALUES ('153', '1006', 'login', 'ms_index.php', 'Account setup completed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:35:04');
INSERT INTO `user_activity_log` VALUES ('154', '1006', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:35:04');
INSERT INTO `user_activity_log` VALUES ('155', '1006', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:35:09');
INSERT INTO `user_activity_log` VALUES ('156', '1006', 'login', 'ms_index.php', '2FA authentication successful', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:35:24');
INSERT INTO `user_activity_log` VALUES ('157', '1006', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:35:24');
INSERT INTO `user_activity_log` VALUES ('158', '1006', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:35:27');
INSERT INTO `user_activity_log` VALUES ('159', '1006', 'login', 'ms_index.php', '2FA authentication successful', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:21');
INSERT INTO `user_activity_log` VALUES ('160', '1006', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:21');
INSERT INTO `user_activity_log` VALUES ('161', '1006', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:23');
INSERT INTO `user_activity_log` VALUES ('162', '1006', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:34');
INSERT INTO `user_activity_log` VALUES ('163', '1006', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:38');
INSERT INTO `user_activity_log` VALUES ('164', '1006', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:40');
INSERT INTO `user_activity_log` VALUES ('165', '1006', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:41');
INSERT INTO `user_activity_log` VALUES ('166', '1006', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:46');
INSERT INTO `user_activity_log` VALUES ('167', '1006', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:37:48');
INSERT INTO `user_activity_log` VALUES ('168', '1006', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:03');
INSERT INTO `user_activity_log` VALUES ('169', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:06');
INSERT INTO `user_activity_log` VALUES ('170', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:06');
INSERT INTO `user_activity_log` VALUES ('171', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:08');
INSERT INTO `user_activity_log` VALUES ('172', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:08');
INSERT INTO `user_activity_log` VALUES ('173', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:09');
INSERT INTO `user_activity_log` VALUES ('174', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:10');
INSERT INTO `user_activity_log` VALUES ('175', '1001', 'access', 'ms_reports.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:46');
INSERT INTO `user_activity_log` VALUES ('176', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:38:53');
INSERT INTO `user_activity_log` VALUES ('177', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:39:01');
INSERT INTO `user_activity_log` VALUES ('178', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:39:02');
INSERT INTO `user_activity_log` VALUES ('179', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:39:03');
INSERT INTO `user_activity_log` VALUES ('180', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:39:06');
INSERT INTO `user_activity_log` VALUES ('181', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:39:07');
INSERT INTO `user_activity_log` VALUES ('182', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:39:08');
INSERT INTO `user_activity_log` VALUES ('183', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:39:09');
INSERT INTO `user_activity_log` VALUES ('184', '1001', 'add', 'ms_workforce.php', 'add employee', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:44:05');
INSERT INTO `user_activity_log` VALUES ('185', '1001', 'add', 'ms_workforce.php', 'add user', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:44:05');
INSERT INTO `user_activity_log` VALUES ('186', '1001', 'add', 'ms_workforce.php', 'add employee', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:46:45');
INSERT INTO `user_activity_log` VALUES ('187', '1001', 'add', 'ms_workforce.php', 'add user', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:46:45');
INSERT INTO `user_activity_log` VALUES ('188', '1001', 'add', 'ms_workforce.php', 'add employee', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:47:43');
INSERT INTO `user_activity_log` VALUES ('189', '1001', 'add', 'ms_workforce.php', 'add user', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:47:43');
INSERT INTO `user_activity_log` VALUES ('190', '1001', 'access', 'get_account_details.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:49:47');
INSERT INTO `user_activity_log` VALUES ('191', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:49:51');
INSERT INTO `user_activity_log` VALUES ('192', '1001', 'access', 'get_account_details.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:49:54');
INSERT INTO `user_activity_log` VALUES ('193', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:50:12');
INSERT INTO `user_activity_log` VALUES ('194', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:50:25');
INSERT INTO `user_activity_log` VALUES ('195', '1001', 'access', 'ms_payroll.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:50:25');
INSERT INTO `user_activity_log` VALUES ('196', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:50:26');
INSERT INTO `user_activity_log` VALUES ('197', '1001', 'access', 'get_account_details.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:50:29');
INSERT INTO `user_activity_log` VALUES ('198', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:50:32');
INSERT INTO `user_activity_log` VALUES ('199', '1001', 'access', 'get_account_details.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:50:35');
INSERT INTO `user_activity_log` VALUES ('200', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:51:29');
INSERT INTO `user_activity_log` VALUES ('201', '1001', 'access', 'get_account_details.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:55:50');
INSERT INTO `user_activity_log` VALUES ('202', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 06:57:05');
INSERT INTO `user_activity_log` VALUES ('203', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 07:50:39');
INSERT INTO `user_activity_log` VALUES ('204', '1001', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 07:59:40');
INSERT INTO `user_activity_log` VALUES ('205', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 07:59:43');
INSERT INTO `user_activity_log` VALUES ('206', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 07:59:43');
INSERT INTO `user_activity_log` VALUES ('207', '1001', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:09:52');
INSERT INTO `user_activity_log` VALUES ('208', '1006', 'login', 'ms_index.php', '2FA authentication successful', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:10:45');
INSERT INTO `user_activity_log` VALUES ('209', '1006', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:10:45');
INSERT INTO `user_activity_log` VALUES ('210', '1006', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:10:51');
INSERT INTO `user_activity_log` VALUES ('211', '1006', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:10:58');
INSERT INTO `user_activity_log` VALUES ('212', '1006', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:00');
INSERT INTO `user_activity_log` VALUES ('213', '1006', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:01');
INSERT INTO `user_activity_log` VALUES ('214', '1006', 'access', 'ms_vendors.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:01');
INSERT INTO `user_activity_log` VALUES ('215', '1006', 'access', 'ms_reports.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:02');
INSERT INTO `user_activity_log` VALUES ('216', '1006', 'access', 'ms_vendors.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:07');
INSERT INTO `user_activity_log` VALUES ('217', '1006', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:08');
INSERT INTO `user_activity_log` VALUES ('218', '1006', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:09');
INSERT INTO `user_activity_log` VALUES ('219', '1006', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:10');
INSERT INTO `user_activity_log` VALUES ('220', '1006', 'access_denied', 'ms_payroll.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:35');
INSERT INTO `user_activity_log` VALUES ('221', '1006', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:55');
INSERT INTO `user_activity_log` VALUES ('222', '1006', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:11:59');
INSERT INTO `user_activity_log` VALUES ('223', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:02');
INSERT INTO `user_activity_log` VALUES ('224', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:02');
INSERT INTO `user_activity_log` VALUES ('225', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:04');
INSERT INTO `user_activity_log` VALUES ('226', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:05');
INSERT INTO `user_activity_log` VALUES ('227', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:06');
INSERT INTO `user_activity_log` VALUES ('228', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:06');
INSERT INTO `user_activity_log` VALUES ('229', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:07');
INSERT INTO `user_activity_log` VALUES ('230', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:09');
INSERT INTO `user_activity_log` VALUES ('231', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:13');
INSERT INTO `user_activity_log` VALUES ('232', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:12:15');
INSERT INTO `user_activity_log` VALUES ('233', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:13:11');
INSERT INTO `user_activity_log` VALUES ('234', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:13:12');
INSERT INTO `user_activity_log` VALUES ('235', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:14:42');
INSERT INTO `user_activity_log` VALUES ('236', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:14:43');
INSERT INTO `user_activity_log` VALUES ('237', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:14:44');
INSERT INTO `user_activity_log` VALUES ('238', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:14:45');
INSERT INTO `user_activity_log` VALUES ('239', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:20:13');
INSERT INTO `user_activity_log` VALUES ('240', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:21:53');
INSERT INTO `user_activity_log` VALUES ('241', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:21:55');
INSERT INTO `user_activity_log` VALUES ('242', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:22:02');
INSERT INTO `user_activity_log` VALUES ('243', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:22:03');
INSERT INTO `user_activity_log` VALUES ('244', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:22:49');
INSERT INTO `user_activity_log` VALUES ('245', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:22:52');
INSERT INTO `user_activity_log` VALUES ('246', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:22:54');
INSERT INTO `user_activity_log` VALUES ('247', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:23:04');
INSERT INTO `user_activity_log` VALUES ('248', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:39:43');
INSERT INTO `user_activity_log` VALUES ('249', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:39:44');
INSERT INTO `user_activity_log` VALUES ('250', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:39:57');
INSERT INTO `user_activity_log` VALUES ('251', '1001', 'access', 'ms_vendors.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:39:59');
INSERT INTO `user_activity_log` VALUES ('252', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:40:01');
INSERT INTO `user_activity_log` VALUES ('253', '1001', 'access', 'ms_vendors.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:40:02');
INSERT INTO `user_activity_log` VALUES ('254', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:40:05');
INSERT INTO `user_activity_log` VALUES ('255', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:40:06');
INSERT INTO `user_activity_log` VALUES ('256', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:40:07');
INSERT INTO `user_activity_log` VALUES ('257', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:12');
INSERT INTO `user_activity_log` VALUES ('258', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:13');
INSERT INTO `user_activity_log` VALUES ('259', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:13');
INSERT INTO `user_activity_log` VALUES ('260', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:16');
INSERT INTO `user_activity_log` VALUES ('261', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:17');
INSERT INTO `user_activity_log` VALUES ('262', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:18');
INSERT INTO `user_activity_log` VALUES ('263', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:18');
INSERT INTO `user_activity_log` VALUES ('264', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:20');
INSERT INTO `user_activity_log` VALUES ('265', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:42:21');
INSERT INTO `user_activity_log` VALUES ('266', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:55:26');
INSERT INTO `user_activity_log` VALUES ('267', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 08:55:27');
INSERT INTO `user_activity_log` VALUES ('268', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:16');
INSERT INTO `user_activity_log` VALUES ('269', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:17');
INSERT INTO `user_activity_log` VALUES ('270', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:18');
INSERT INTO `user_activity_log` VALUES ('271', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:18');
INSERT INTO `user_activity_log` VALUES ('272', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:20');
INSERT INTO `user_activity_log` VALUES ('273', '1001', 'access', 'ms_records.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:20');
INSERT INTO `user_activity_log` VALUES ('274', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:21');
INSERT INTO `user_activity_log` VALUES ('275', '1001', 'access', 'ms_workforce.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:21');
INSERT INTO `user_activity_log` VALUES ('276', '1001', 'access', 'ms_payroll.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:22');
INSERT INTO `user_activity_log` VALUES ('277', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:24');
INSERT INTO `user_activity_log` VALUES ('278', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:01:24');
INSERT INTO `user_activity_log` VALUES ('279', '1001', 'access_denied', 'ms_settings.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:48:53');
INSERT INTO `user_activity_log` VALUES ('280', '1001', 'access_denied', 'ms_settings.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:57:02');
INSERT INTO `user_activity_log` VALUES ('281', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:57:20');
INSERT INTO `user_activity_log` VALUES ('282', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:57:25');
INSERT INTO `user_activity_log` VALUES ('283', '1001', 'access_denied', 'ms_settings.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:57:30');
INSERT INTO `user_activity_log` VALUES ('284', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:57:35');
INSERT INTO `user_activity_log` VALUES ('285', '1001', 'access_denied', 'ms_settings.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:58:15');
INSERT INTO `user_activity_log` VALUES ('286', '1001', 'access_denied', 'ms_settings.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:59:29');
INSERT INTO `user_activity_log` VALUES ('287', '1001', 'access_denied', 'ms_settings.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 09:59:35');
INSERT INTO `user_activity_log` VALUES ('288', '1001', 'access_denied', 'ms_settings.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:00:01');
INSERT INTO `user_activity_log` VALUES ('289', '1001', 'logout', 'ms_logout.php', 'User initiated logout', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:00:12');
INSERT INTO `user_activity_log` VALUES ('290', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:03:57');
INSERT INTO `user_activity_log` VALUES ('291', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:03:57');
INSERT INTO `user_activity_log` VALUES ('292', '1001', 'access_denied', 'ms_settings.php', 'Unauthorized access attempt', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:04:01');
INSERT INTO `user_activity_log` VALUES ('293', '1001', 'access', 'ms_settings.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:07:31');
INSERT INTO `user_activity_log` VALUES ('294', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:07:31');
INSERT INTO `user_activity_log` VALUES ('295', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:12:55');
INSERT INTO `user_activity_log` VALUES ('296', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:16:01');
INSERT INTO `user_activity_log` VALUES ('297', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:16:04');
INSERT INTO `user_activity_log` VALUES ('298', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:16:09');
INSERT INTO `user_activity_log` VALUES ('299', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:16:34');
INSERT INTO `user_activity_log` VALUES ('300', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:17:14');
INSERT INTO `user_activity_log` VALUES ('301', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:17:18');
INSERT INTO `user_activity_log` VALUES ('302', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:17:21');
INSERT INTO `user_activity_log` VALUES ('303', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:18:42');
INSERT INTO `user_activity_log` VALUES ('304', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:18:44');
INSERT INTO `user_activity_log` VALUES ('305', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:18:46');
INSERT INTO `user_activity_log` VALUES ('306', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:19:05');
INSERT INTO `user_activity_log` VALUES ('307', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:19:07');
INSERT INTO `user_activity_log` VALUES ('308', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:19:08');
INSERT INTO `user_activity_log` VALUES ('309', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:19:10');
INSERT INTO `user_activity_log` VALUES ('310', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:19:11');
INSERT INTO `user_activity_log` VALUES ('311', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:19:21');
INSERT INTO `user_activity_log` VALUES ('312', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:07');
INSERT INTO `user_activity_log` VALUES ('313', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:09');
INSERT INTO `user_activity_log` VALUES ('314', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:11');
INSERT INTO `user_activity_log` VALUES ('315', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:13');
INSERT INTO `user_activity_log` VALUES ('316', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:14');
INSERT INTO `user_activity_log` VALUES ('317', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:15');
INSERT INTO `user_activity_log` VALUES ('318', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:16');
INSERT INTO `user_activity_log` VALUES ('319', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:17');
INSERT INTO `user_activity_log` VALUES ('320', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:20');
INSERT INTO `user_activity_log` VALUES ('321', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:21');
INSERT INTO `user_activity_log` VALUES ('322', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:23');
INSERT INTO `user_activity_log` VALUES ('323', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:25');
INSERT INTO `user_activity_log` VALUES ('324', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:20:26');
INSERT INTO `user_activity_log` VALUES ('325', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:21');
INSERT INTO `user_activity_log` VALUES ('326', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:23');
INSERT INTO `user_activity_log` VALUES ('327', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:25');
INSERT INTO `user_activity_log` VALUES ('328', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:26');
INSERT INTO `user_activity_log` VALUES ('329', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:27');
INSERT INTO `user_activity_log` VALUES ('330', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:28');
INSERT INTO `user_activity_log` VALUES ('331', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:32');
INSERT INTO `user_activity_log` VALUES ('332', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:51');
INSERT INTO `user_activity_log` VALUES ('333', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:52');
INSERT INTO `user_activity_log` VALUES ('334', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:52');
INSERT INTO `user_activity_log` VALUES ('335', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:21:53');
INSERT INTO `user_activity_log` VALUES ('336', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:22:13');
INSERT INTO `user_activity_log` VALUES ('337', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:24:46');
INSERT INTO `user_activity_log` VALUES ('338', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:24:58');
INSERT INTO `user_activity_log` VALUES ('339', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:25:00');
INSERT INTO `user_activity_log` VALUES ('340', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:25:09');
INSERT INTO `user_activity_log` VALUES ('341', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:28:46');
INSERT INTO `user_activity_log` VALUES ('342', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:28:50');
INSERT INTO `user_activity_log` VALUES ('343', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:30:25');
INSERT INTO `user_activity_log` VALUES ('344', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:33:45');
INSERT INTO `user_activity_log` VALUES ('345', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:33:56');
INSERT INTO `user_activity_log` VALUES ('346', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:33:56');
INSERT INTO `user_activity_log` VALUES ('347', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:34:10');
INSERT INTO `user_activity_log` VALUES ('348', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:34:32');
INSERT INTO `user_activity_log` VALUES ('349', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:34:35');
INSERT INTO `user_activity_log` VALUES ('350', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:36:43');
INSERT INTO `user_activity_log` VALUES ('351', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:37:08');
INSERT INTO `user_activity_log` VALUES ('352', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:37:35');
INSERT INTO `user_activity_log` VALUES ('353', '1001', 'access', 'ms_assets.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 10:37:56');
INSERT INTO `user_activity_log` VALUES ('354', '1001', 'login', 'ms_index.php', 'Superadmin login (2FA bypassed)', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 13:54:41');
INSERT INTO `user_activity_log` VALUES ('355', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 13:54:41');
INSERT INTO `user_activity_log` VALUES ('356', '1001', 'access', 'ms_projects.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 13:54:44');
INSERT INTO `user_activity_log` VALUES ('357', '1001', 'access', 'ms_dashboard.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 13:54:45');
INSERT INTO `user_activity_log` VALUES ('358', '1001', 'access', 'ms_settings.php', 'Page accessed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 13:54:49');
INSERT INTO `user_activity_log` VALUES ('359', '1001', 'access', 'ms_settings.php', 'Settings accessed by superadmin from IT Infrastructure & Cybersecurity Division', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-24 13:54:49');


-- Table structure for `user_security_answers`
DROP TABLE IF EXISTS `user_security_answers`;
CREATE TABLE `user_security_answers` (
  `answer_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`answer_id`),
  KEY `user_id` (`user_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `security_questions` (`question_id`),
  CONSTRAINT `fk_answers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `user_security_answers`
INSERT INTO `user_security_answers` VALUES ('7', '1006', '1', '$2y$10$YJM3ZEWdK74n8V9ig0VTT.ZcUnUDH14T3opM1sa9.HCQ5x78R3mYu');
INSERT INTO `user_security_answers` VALUES ('8', '1006', '8', '$2y$10$yvibo/CAK1dY7MOQ9dLI3u1lVJNOeudo4QImCcqPxtM6ZMDhiFy0a');


-- Table structure for `user_session_log`
DROP TABLE IF EXISTS `user_session_log`;
CREATE TABLE `user_session_log` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  PRIMARY KEY (`session_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_session_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `user_session_log`
INSERT INTO `user_session_log` VALUES ('1', '1001', '2025-05-23 19:00:50', '2025-05-23 22:43:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('2', '1001', '2025-05-23 22:43:44', '2025-05-24 04:05:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('3', '1001', '2025-05-24 04:09:42', '2025-05-24 04:12:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('4', '1001', '2025-05-24 06:32:58', '2025-05-24 06:33:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('5', '1006', '2025-05-24 06:35:04', '2025-05-24 06:35:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('6', '1006', '2025-05-24 06:35:24', '2025-05-24 06:35:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('7', '1006', '2025-05-24 06:37:21', '2025-05-24 06:38:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('8', '1001', '2025-05-24 06:38:06', '2025-05-24 07:59:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('9', '1001', '2025-05-24 07:59:43', '2025-05-24 08:09:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('10', '1006', '2025-05-24 08:10:45', '2025-05-24 08:11:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('11', '1001', '2025-05-24 08:12:02', '2025-05-24 10:00:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'closed');
INSERT INTO `user_session_log` VALUES ('12', '1001', '2025-05-24 10:03:57', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'active');
INSERT INTO `user_session_log` VALUES ('13', '1001', '2025-05-24 13:54:41', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', 'active');


-- Table structure for `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `username` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `account_status` enum('new','active','locked','disabled') NOT NULL DEFAULT 'active',
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `authenticator_secret` varchar(100) DEFAULT NULL,
  `preferred_2fa` enum('authenticator') DEFAULT NULL,
  `security_question_1` int(11) DEFAULT NULL COMMENT 'First security question preference',
  `security_question_2` int(11) DEFAULT NULL COMMENT 'Second security question preference',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_users_employee` (`employee_id`),
  KEY `fk_users_security_question_2` (`security_question_2`),
  KEY `idx_users_security_questions` (`security_question_1`,`security_question_2`),
  CONSTRAINT `fk_users_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`),
  CONSTRAINT `fk_users_security_question_1` FOREIGN KEY (`security_question_1`) REFERENCES `security_questions` (`question_id`),
  CONSTRAINT `fk_users_security_question_2` FOREIGN KEY (`security_question_2`) REFERENCES `security_questions` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1011 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES ('1001', '1001', 'admin', 'admin@malayaenergies.com', '$2y$10$6f6ehPLuNpmYAJK0cYrUQ.t5r3dneQHW.xyDc096s2vkoOmyr5ctm', 'superadmin', 'active', '0', '2025-05-19 12:45:52', NULL, NULL, NULL, NULL);
INSERT INTO `users` VALUES ('1003', '1003', 'arvin.reyes', 'arvin.reyes@malayaenegies.com', '$2y$10$mfjN..Ku9JqZ3EQE6P3BVOkqQXbL6zJ0tBihf.iix9OQWIxTH3Xuy', 'admin', 'locked', '3', NULL, NULL, '', NULL, NULL);
INSERT INTO `users` VALUES ('1004', '1004', 'mikayla.lacanilao', 'mikayla.lacanilao@malayaenegies.com', '$2y$10$ibg3LOEllEFzyJgGfldSIOTk3cDDiBCPvRf1P1WXF99jX4rHGRKni', 'admin', 'new', '0', NULL, '', 'authenticator', NULL, NULL);
INSERT INTO `users` VALUES ('1006', '1006', 'chantel.reblando', 'chantel.reblando@malayaenegies.com', '$2y$10$HzEnDJ3FTaoY.NV1YqRrAO0x2CSVTDdozwWXxGD0rmK2sUr1aSA1.', 'manager', 'active', '0', '2025-05-24 08:10:45', 'CCJGZ7EJ6ZHZVLGH', 'authenticator', '1', '8');
INSERT INTO `users` VALUES ('1008', '1007', '', '', '$2y$10$/K7Sw2hD6mWhDrO7bvIKhOFvcgkT8/Ey8/xp9jFXBMCttxoVPZ.kq', 'user', 'active', '0', NULL, NULL, NULL, NULL, NULL);


-- Table structure for `vendors`
DROP TABLE IF EXISTS `vendors`;
CREATE TABLE `vendors` (
  `vendor_id` int(9) NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(70) NOT NULL,
  `vendor_type` varchar(80) NOT NULL,
  `contact_person` varchar(80) NOT NULL,
  `vendor_email` varchar(80) DEFAULT NULL,
  `contact_no` varchar(11) NOT NULL,
  `telephone` varchar(15) DEFAULT NULL,
  `vendor_unit_bldg_no` varchar(50) DEFAULT NULL,
  `vendor_street` varchar(100) DEFAULT NULL,
  `vendor_city` varchar(50) DEFAULT NULL,
  `vendor_country` varchar(50) DEFAULT NULL,
  `vendor_remarks` text DEFAULT NULL,
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `vendors`
INSERT INTO `vendors` VALUES ('1', 'Tung Tung Woodworks', 'Wood Supplier', 'Tung Tung Sahur', 'tung.sahur@gmail.com', '9658721430', NULL, 'Unit 5', 'Sinapak St.', 'Bentong City', 'Malaysia', 'Reliable supplier for wood materials');
INSERT INTO `vendors` VALUES ('2', 'Tralalello Tropahan', 'Construction Equipment', 'Tralalello Tropa Lang', 'tralalello.tropa@gmail.com', '9123456789', '028765432', 'Building 10', 'Tinropa St.', 'Kuala Lumpur', 'Malaysia', 'Equipment rentals available on short notice');
INSERT INTO `vendors` VALUES ('3', 'Metro Hardware', 'Hardware Supplier', 'Juan Dela Cruz', 'juan@metrohardware.com', '9876543210', '027654321', '123', 'Main Avenue', 'Manila', 'Philippines', 'Offers bulk discounts for large orders');
INSERT INTO `vendors` VALUES ('4', 'Solar Tech Solutions', 'Solar Panels', 'Maria Santos', 'maria@solartech.com', '9765432109', NULL, 'Unit 42', 'Green Energy Blvd.', 'Makati', 'Philippines', 'Premium solar panel provider with warranty');
INSERT INTO `vendors` VALUES ('5', 'Global Electronics', 'Electronics', 'Robert Kim', 'robert@globalelec.com', '9567890123', '025678901', 'Tower 3', 'Circuit Road', 'Singapore', 'Singapore', 'Specialized in high-efficiency inverters');
INSERT INTO `vendors` VALUES ('9', 'test vendor', 'test type', 'test contact', 'test.email@gmail.com', '09876543222', '', '', '', '', '', '');

