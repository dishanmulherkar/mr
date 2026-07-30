-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 30, 2026 at 12:27 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mr-soft`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int NOT NULL,
  `admin_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mobile` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('Super Admin','Admin') COLLATE utf8mb4_general_ci DEFAULT 'Admin',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_general_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `admin_name`, `username`, `email`, `mobile`, `password`, `role`, `created_at`, `last_login`, `status`) VALUES
(1, 'admin', 'admin', 'dishmulherkar@gmail.com', '123456789', '123', 'Super Admin', '2026-06-16 12:48:11', NULL, 'Active'),
(2, 'Dish', 'Dishan', 'dishan@gmail.com', '9408162973', '123', 'Admin', '2026-07-06 14:56:22', NULL, 'Active'),
(6, 'Dish', 'Bihar', 'dishmulherkar2@gmail.com', '9408162983', '123', 'Admin', '2026-07-06 14:59:40', NULL, 'Active'),
(8, 'vraj', 'gujarat', 'gujarat@gmail.com', '9408162909', '123', 'Admin', '2026-07-06 15:17:19', NULL, 'Active'),
(11, 'Vivek Bhai', 'Nepal', 'nepal@gmail.com', '9408162909 ', '123', 'Admin', '2026-07-07 12:27:16', NULL, 'Active'),
(12, 'UP', 'UP', 'up@gmail.com', '9408162972', '123', 'Admin', '2026-07-08 15:55:26', NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `admin_state`
--

CREATE TABLE `admin_state` (
  `id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `state_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_state`
--

INSERT INTO `admin_state` (`id`, `admin_id`, `state_id`) VALUES
(15, 2, 0),
(16, 2, 1),
(17, 6, 1),
(20, 8, 0),
(21, 12, 3),
(22, 11, 13);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `c_id` int NOT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_type` enum('Doctor','Chemist') COLLATE utf8mb4_general_ci NOT NULL,
  `qualification` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_img` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mobile` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `district` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hq_id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `pincode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` int DEFAULT '1',
  `created_by` varchar(25) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`c_id`, `customer_name`, `customer_type`, `qualification`, `customer_img`, `mobile`, `email`, `address`, `district`, `state`, `hq_id`, `admin_id`, `pincode`, `created_at`, `status`, `created_by`) VALUES
(1, 'vivek', 'Chemist', 'bams', '1782462987_6a3e3a0bc7904.png', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'rampur', '1', 9, 0, '390011', '2026-06-17 17:47:30', 1, ''),
(2, 'rudra1', 'Doctor', 'bams', '1782463531_6a3e3c2b15b18.png', '8888888888', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'rampur', '1', 11, 0, '390011', '2026-06-18 13:40:36', 1, ''),
(7, 'Abhishak Singh 3', 'Doctor', 'Mbbs2', '1782463377_6a3e3b9182a9b.png', '9998033250', 'abhishak@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'rampur', '1', 10, 0, '390011', '2026-06-19 16:16:33', 1, ''),
(8, 'Abhishak Singh 4', 'Doctor', 'bams', '1781866080_top-view-various-spices-herbs-dry-black-tea-leaves-peppermint-rose-buds-clove-spice-black-peppercorns-glass-jars-black-wood-with-copy-space.jpg', '9408162973', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'vadodara', 'Gujarat (IN)', 10, 0, '390011', '2026-06-19 16:18:00', 1, ''),
(9, 'rudra58', 'Doctor', 'bams', '1781866433_gonablok-200mg-strip-of-10-capsules-front-2-1756902625-non-watermarked.png', '8888888888', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'vadodara', 'Gujarat (IN)', 10, 0, '390011', '2026-06-19 16:23:53', 1, ''),
(10, 'Abhishak 45', 'Doctor', 'Mbbs', '1781866628_cannabis-leaf-oil-bottle-arrangement.jpg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'vadodara', 'Gujarat (IN)', 10, 0, '390011', '2026-06-19 16:27:08', 1, ''),
(11, 'Abhishak 45', 'Doctor', 'Mbbs', '1781866628_cannabis-leaf-oil-bottle-arrangement.jpg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'vadodara', 'Gujarat (IN)', 10, 0, '390011', '2026-06-19 16:27:08', 1, ''),
(12, 'Abhishak Singh 46', 'Doctor', 'bams', '1781867257_2025-06-15.webp', '9408162973', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'vadodara', 'Gujarat (IN)', 10, 0, '390011', '2026-06-19 16:37:37', 1, ''),
(13, 'rudra1', 'Chemist', 'Mbbs', '1781867393_2025-06-15.webp', '88484888', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'vadodara', 'Gujarat (IN)', 10, 0, '390011', '2026-06-19 16:39:53', 1, ''),
(14, 'rudra1', 'Doctor', 'bams', '1781869576_IMG_20260611_180319666_HDR.jpg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'buxer', '1', 10, 0, '390011', '2026-06-19 17:16:16', 1, ''),
(15, 'new q', 'Doctor', 'Mbbs', '1781870096_Screenshot 2026-06-19 172413.jpg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'buxer', '1', 10, 0, '390011', '2026-06-19 17:24:56', 1, ''),
(16, 'rudra1', 'Doctor', 'bams', '1781871876_Screenshot 2026-06-19 172413.jpg', '88484888', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'buxer', '1', 10, 0, '390011', '2026-06-19 17:54:36', 1, 'mr'),
(17, 'dishan', 'Doctor', 'Btech', '1782289609_Untitled design.png ', '9998033250', 'dishan@gmail.com', 'dandiya bazar vadodara', 'rampur', '1', 10, 0, '390011', '2026-06-24 13:56:49', 1, 'mr'),
(18, 'dishan', 'Doctor', 'Mbbs', '1782386697_Product List (2).xlsx', '88484888', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'rampur', '1', 10, 0, '390011', '2026-06-25 16:54:57', 1, 'mr'),
(19, 'dishan 1223', 'Doctor', 'Mbbs', '1782387838_mild-steel-wall-mounted-medica-20240321064509714.jpeg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'rampur', '1', 10, 0, '390011', '2026-06-25 17:13:58', 1, 'mr'),
(20, 'dishan 122333', 'Chemist', 'Mbbs', '1782388209_6a3d15f1ee755.jpg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'Buxar', '1', 10, 0, '390011', '2026-06-25 17:20:09', 0, 'mr'),
(21, 'gdfdsgdg', 'Chemist', 'gfhhfhhhhhg', '1782462907_6a3e39bba1254.png', '9402555555', 'mail2rudradeo@yahoo.co.in', 'Shop No 20 Vrajvenu Complex Gurukul Char Rasta\r\nDabhoi - Waghodia Ring Rd\r\nnear Narayan Vidyalaya Road', 'vadodara', '2', 10, 0, '390025', '2026-06-26 14:05:07', 1, 'mr'),
(22, 'rudra1', 'Doctor', 'Mbbs', '', '8888888888', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'buxer', '1', 10, 0, '390011', '2026-06-27 16:03:13', 1, 'mr'),
(23, 'bhavika', 'Doctor', 'bams', '1782885204_6a44ab54ac6e8.jpeg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur jakat naka manek chawk', 'rampur', '1', 10, 0, '390011', '2026-06-27 16:03:45', 1, 'mr'),
(24, 'Vrajesh', 'Doctor', 'Mbbs', '1782972882_zip-file-i-suggest-you-to-read-how-to-do-it-correctly-holi-115636293935mgpupcquv.png', '9408162975', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '0', '1', 10, 0, '390011', '2026-07-02 11:01:07', 1, 'mr'),
(25, 'rudra ', 'Doctor', '', '1782970912_medicine-concept-various-drugs-pills-top-view-with-copy-space.jpg', '123456789', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'Jehanabad', '1', 9, 0, '390011', '2026-07-02 11:02:29', 1, 'mr'),
(26, 'VRAJ', 'Doctor', 'Mbbs', '1782974456_pexels-charm-andaya-11908308.jpg', '9408162985', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'Kaimur', '1', 9, 0, '390011', '2026-07-02 12:10:56', 1, 'mr'),
(28, 'vivek 540', 'Doctor', 'bams', '1782994653_PURIF - Copy.png', '9408162975', 'mail2rudradeo@yahoo.co.in', 'Shop No 20 Vrajvenu Complex Gurukul Char Rasta\r\nDabhoi - Waghodia Ring Rd\r\nnear Narayan Vidyalaya Road', 'Banka', '1', 10, 0, '390025', '2026-07-02 17:47:33', 1, 'mr'),
(30, 'DISHAN MULHERKAR', 'Doctor', 'bams', '1783316156_10401.jpg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'vadodara', '0', 1, 0, '390011', '2026-07-06 11:05:56', 1, 'mr'),
(31, 'ramesh', 'Doctor', 'bams', '1783490100_4735.jpg', '9408162972', 'rudradeo380@gmail.com', 'thrhrh', '0', '1', 10, NULL, '390001', '2026-07-08 11:25:00', 1, ''),
(32, 'ramesh', 'Doctor', 'bams', '1783490400_1213.png', '9408162972', 'rudradeo380@gmail.com', 'Shop No 20 Vrajvenu Complex Gurukul Char Rasta\r\nDabhoi - Waghodia Ring Rd\r\nnear Narayan Vidyalaya Road', '0', '1', 10, NULL, '390025', '2026-07-08 11:30:00', 1, ''),
(33, 'ramesh', 'Doctor', 'bams', '', '9408162972', 'rudradeo380@gmail.com', 'Shop No 20 Vrajvenu Complex Gurukul Char Rasta\r\nDabhoi - Waghodia Ring Rd\r\nnear Narayan Vidyalaya Road', '0', '1', 10, NULL, '390025', '2026-07-08 11:31:44', 1, ''),
(34, 'testing', 'Doctor', 'MBBS', '1783490780_R-favicon.png', '456985328', '', 'dfgfgfg', 'kathmandu', '13', 15, 11, '789564', '2026-07-08 11:36:20', 1, 'mr'),
(35, 'ramesh1222', 'Doctor', 'bams33', '', '9408162972', 'rudradeo380@gmail.com', 'rwer4tet', '0', '1', 10, NULL, '123333', '2026-07-08 12:00:57', 1, ''),
(36, 'New Test', 'Chemist', 'Bpharm', '1783492691_better-health.png', '7878956325', '', 'dgfgfdg', 'kathmandu', '13', 15, 11, '452169', '2026-07-08 12:08:11', 1, 'mr'),
(37, 'test new', 'Doctor', 'bams33', '1783494712_4474.jpg', '9408162972', 'rudradeo380@gmail.com', 'Shop No 20 Vrajvenu Complex Gurukul Char Rasta\r\nDabhoi - Waghodia Ring Rd\r\nnear Narayan Vidyalaya Road', '0', '1', 10, NULL, '390025', '2026-07-08 12:41:52', 1, ''),
(38, 'test new', 'Doctor', 'bams33', '', '9408162972', 'rudradeo380@gmail.com', 'Shop No 20 Vrajvenu Complex Gurukul Char Rasta\r\nDabhoi - Waghodia Ring Rd\r\nnear Narayan Vidyalaya Road', '0', '1', 10, NULL, '390025', '2026-07-08 12:42:20', 1, ''),
(40, 'alpha', 'Doctor', 'fdgg', '1783495160_8631.png', '7878956325', '', 'yghg', 'kathmandu', '13', 15, NULL, '785632', '2026-07-08 12:49:20', 1, 'mr'),
(41, 'test new79', 'Doctor', 'bams33', '', '9408162979', 'rudradeo380@gmail.com', 'Shop No 20 Vrajvenu Complex', 'Aurangabad', '1', 10, NULL, '390025', '2026-07-08 12:52:08', 0, 'mr'),
(42, 'test new', 'Doctor', 'bams33', '', '9408162972', 'rudradeo380@gmail.com', 'Shop No 20 Vrajvenu Comple', '6', '1', 10, NULL, '390025', '2026-07-08 12:52:26', 1, ''),
(43, 'Vrajesh final', 'Doctor', 'Mbbs', '', '9408162975', 'dishan@gmail', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '31', '1', 10, NULL, '390011', '2026-07-08 12:52:58', 1, 'mr'),
(44, 'Vrajesh', 'Doctor', 'Mbbs', '', '9408162975', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '2', '1', 10, NULL, '390011', '2026-07-08 12:53:50', 1, ''),
(45, 'rajesh', 'Chemist', 'bams', '1783498207_5245.jpeg', '9408162975', 'dishan@gmail.com', 'df', 'Araria', '1', 10, NULL, '390025', '2026-07-08 13:40:07', 0, 'mr'),
(46, 'DISHAN MULHERKAR', 'Doctor', 'bams', '1783572803_5704.jpg', '9408162972', 'dishmulherkar@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'Jehanabad', '1', 10, NULL, '390011', '2026-07-09 10:23:23', 1, ''),
(47, 'dishan', 'Doctor', 'Mbbs', '1783926193_1782388209_6a3d15f1ee755.jpg', '9408162972', 'dishan@gmail.com', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', 'vadodara', '0', 1, NULL, '390011', '2026-07-09 16:19:12', 1, 'mr');

-- --------------------------------------------------------

--
-- Table structure for table `district`
--

CREATE TABLE `district` (
  `district_id` int NOT NULL,
  `state_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `district_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `district_status` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `district`
--

INSERT INTO `district` (`district_id`, `state_id`, `admin_id`, `district_name`, `district_status`) VALUES
(1, 1, 0, 'Araria', 1),
(2, 1, 0, 'Arwal', 1),
(3, 1, 0, 'Aurangabad', 1),
(4, 2, 0, 'vadodara', 1),
(5, 1, 0, 'Banka', 1),
(6, 1, 0, 'Begusarai', 1),
(7, 1, 0, 'Bhagalpur', 1),
(8, 1, 0, 'Bhojpur', 1),
(9, 1, 0, 'Buxar', 1),
(10, 1, 0, 'Darbhanga', 1),
(11, 1, 0, 'East Champaran', 1),
(12, 1, 0, 'Gaya', 0),
(13, 1, 0, 'Gopalganj', 1),
(14, 1, 0, 'Jamui', 1),
(15, 1, 0, 'Jehanabad', 1),
(16, 1, 0, 'Kaimur', 1),
(17, 1, 0, 'Katihar', 1),
(18, 1, 0, 'Khagaria', 1),
(19, 1, 0, 'Kishanganj', 1),
(20, 1, 0, 'Lakhisarai', 1),
(21, 1, 0, 'Madhepura', 1),
(22, 1, 0, 'Madhubani', 1),
(23, 1, 0, 'Munger', 1),
(24, 1, 0, 'Muzaffarpur', 1),
(25, 1, 0, 'Nalanda', 1),
(26, 1, 0, 'Nawada', 1),
(27, 1, 0, 'Patna', 1),
(28, 1, 0, 'Purnea', 1),
(29, 1, 0, 'Rohtas', 1),
(30, 1, 0, 'Saharsa', 1),
(31, 1, 0, 'Samastipur', 1),
(32, 1, 0, 'Saran', 1),
(33, 1, 0, 'Sheikhpura', 1),
(34, 1, 0, 'Sheohar', 1),
(35, 1, 0, 'Sitamarhi', 1),
(36, 1, 0, 'Siwan', 1),
(37, 1, 0, 'Supaul', 1),
(38, 1, 0, 'Vaishali', 1),
(39, 1, 0, 'West Champaran', 1),
(40, 1, 0, 'buxer111', 0),
(41, 1, 0, 'buxer420', 0),
(42, 1, 0, 'vadodara12', 0),
(43, 1, 0, 'Ara West', 0),
(44, 0, 0, 'vadodara', 1),
(45, 14, 0, 'vadodara', 0),
(46, 1, 0, 'dhgh', 1),
(47, 13, 11, 'kathmandu', 1),
(48, 13, 1, 'Bhojpur', 1),
(49, 13, 1, 'Dhankuta', 1),
(50, 13, 1, 'Ilam', 1),
(51, 13, 1, 'Jhapa', 1),
(52, 13, 1, 'Khotang', 1),
(53, 13, 1, 'Morang', 1),
(54, 13, 1, 'Okhaldhunga', 1),
(55, 13, 1, 'Panchthar', 1),
(56, 13, 1, 'Sankhuwasabha', 1),
(57, 13, 1, 'Solukhumbu', 1),
(58, 13, 1, 'Sunsari', 1),
(59, 13, 1, 'Taplejung', 1),
(60, 13, 1, 'Tehrathum', 1),
(61, 13, 1, 'Udayapur', 1),
(62, 13, 1, 'Parsa', 1),
(63, 13, 1, 'Bara', 1),
(64, 13, 1, 'Rautahat', 1),
(65, 13, 1, 'Sarlahi', 1),
(66, 13, 1, 'Dhanusha', 1),
(67, 13, 1, 'Siraha', 1),
(68, 13, 1, 'Mahottari', 1),
(69, 13, 1, 'Saptari', 1),
(70, 13, 1, 'Sindhuli', 1),
(71, 13, 1, 'Ramechhap', 1),
(72, 13, 1, 'Dolakha', 1),
(73, 13, 1, 'Bhaktapur', 1),
(74, 13, 1, 'Dhading', 1),
(75, 13, 1, 'Kavrepalanchok', 1),
(76, 13, 1, 'Lalitpur', 1),
(77, 13, 1, 'Nuwakot', 1),
(78, 13, 1, 'Rasuwa', 1),
(79, 13, 1, 'Sindhupalchok', 1),
(80, 13, 1, 'Chitwan', 1),
(81, 13, 1, 'Makwanpur', 1),
(82, 13, 1, 'Baglung', 1),
(83, 13, 1, 'Gorkha', 1),
(84, 13, 1, 'Kaski', 1),
(85, 13, 1, 'Lamjung', 1),
(86, 13, 1, 'Manang', 1),
(87, 13, 1, 'Mustang', 1),
(88, 13, 1, 'Myagdi', 1),
(89, 13, 1, 'Nawalparasi (East of Bardaghat Susta)', 1),
(90, 13, 1, 'Parbat', 1),
(91, 13, 1, 'Syangja', 1),
(92, 13, 1, 'Tanahun', 1),
(93, 13, 1, 'Kapilvastu', 1),
(94, 13, 1, 'Nawalparasi (West of Bardaghat Susta)', 1),
(95, 13, 1, 'Rupandehi', 1),
(96, 13, 1, 'Arghakhanchi', 1),
(97, 13, 1, 'Gulmi', 1),
(98, 13, 1, 'Palpa', 1),
(99, 13, 1, 'Dang', 1),
(100, 13, 1, 'Pyuthan', 1),
(101, 13, 1, 'Rolpa', 1),
(102, 13, 1, 'Eastern Rukum', 1),
(103, 13, 1, 'Banke', 1),
(104, 13, 1, 'Bardiya', 1),
(105, 13, 1, 'Western Rukum', 1),
(106, 13, 1, 'Salyan', 1),
(107, 13, 1, 'Dolpa', 1),
(108, 13, 1, 'Humla', 1),
(109, 13, 1, 'Jumla', 1),
(110, 13, 1, 'Kalikot', 1),
(111, 13, 1, 'Mugu', 1),
(112, 13, 1, 'Surkhet', 1),
(113, 13, 1, 'Dailekh', 1),
(114, 13, 1, 'Jajarkot', 1),
(115, 13, 1, 'Kailali', 1),
(116, 13, 1, 'Achham', 1),
(117, 13, 1, 'Doti', 1),
(118, 13, 1, 'Bajhang', 1),
(119, 13, 1, 'Bajura', 1),
(120, 13, 1, 'Kanchanpur', 1),
(121, 13, 1, 'Dadeldhura', 1),
(122, 13, 1, 'Baitadi', 1),
(123, 13, 1, 'Darchula', 1),
(124, 15, 1, 'vadodara', 1);

-- --------------------------------------------------------

--
-- Table structure for table `financial_year`
--

CREATE TABLE `financial_year` (
  `fy_id` int NOT NULL,
  `hq_id` int NOT NULL,
  `fy_name` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `target_amount` decimal(12,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `financial_year`
--

INSERT INTO `financial_year` (`fy_id`, `hq_id`, `fy_name`, `start_date`, `end_date`, `target_amount`, `status`, `created_at`) VALUES
(1, 10, 'FY 26-27', '2025-08-01', '2025-10-24', 120000.00, 0, '2026-06-29 07:05:14'),
(2, 11, 'FY 25-26', '2025-12-01', '2026-05-15', 130000.00, 1, '2026-06-29 07:38:41'),
(3, 10, 'FY 25-26', '2026-06-23', '2026-10-02', 52000.00, 0, '2026-06-29 07:49:30'),
(4, 10, 'kjkj', '2026-06-13', '2026-06-29', 0.00, 0, '2026-06-29 08:42:02'),
(5, 10, '26-27', '2026-10-01', '2026-12-31', 0.00, 0, '2026-06-29 08:46:17'),
(6, 10, 'yr 2026', '2026-02-01', '2027-01-06', 1200000.00, 0, '2026-06-29 09:26:02'),
(7, 10, 'yr 2027', '2025-12-01', '2026-05-22', 1500000.00, 0, '2026-06-29 09:30:28'),
(8, 11, 'FY 26-27', '2026-09-01', '2027-03-31', 1455555.00, 0, '2026-06-29 10:31:00'),
(9, 1, '26-200027', '2026-07-09', '2026-07-31', 233.00, 1, '2026-07-01 06:00:33'),
(10, 10, '26*-29', '2026-07-01', '2028-06-08', 280000.00, 1, '2026-07-03 07:57:54'),
(11, 9, 'FY 24-25', '2026-07-01', '2026-07-31', 100000.00, 1, '2026-07-03 09:29:44'),
(12, 15, '2026-2027', '2026-02-01', '2027-01-31', 1200000.00, 1, '2026-07-08 06:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `mr_users`
--

CREATE TABLE `mr_users` (
  `m_id` int NOT NULL,
  `hq_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `mr_name` varchar(250) COLLATE utf8mb4_general_ci NOT NULL,
  `qualification` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mobile` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `admin_id` int NOT NULL,
  `district` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `pincode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mr_users`
--

INSERT INTO `mr_users` (`m_id`, `hq_name`, `mr_name`, `qualification`, `mobile`, `email`, `password`, `state`, `admin_id`, `district`, `pincode`, `address`, `created_at`, `status`) VALUES
(1, 'gdhhth', 'dish', NULL, '1234567890', 'dishan@gmail.com', '1234', '0', 0, 'vadodara', '390011', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '2026-06-17 10:55:18', 1),
(9, 'buxer', 'dishan', NULL, '9408162973', 'dishan@gmail.com', '123', '1', 0, 'rampur', '390011', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '2026-06-17 14:32:31', 1),
(10, 'east vadodara', 'vivek bha5', NULL, '9408162985', 'admin@gmail.com', '123456789', '1', 1, 'Banka', '390005', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, karelibaugh', '2026-06-17 16:21:55', 1),
(11, 'barua', 'dishan Kumar', NULL, '9408162972', 'rudra@gmail.com', '1234', '1', 0, 'Kaimur', '390011', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '2026-06-17 17:03:05', 1),
(14, 'west vadodara', 'dishan Kumar', NULL, '9408162977', 'westVadodar@rudradeo', '123', '1', 1, 'Araria', '390011', 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '2026-07-07 17:41:26', 1),
(15, 'Kathmandu', 'Test Nepal', NULL, '4565893256', 'nepal@gmail.com', '123', '13', 1, 'kathmandu', '235698', 'ydghghh', '2026-07-08 11:34:38', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int NOT NULL,
  `state_id` int DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `send_type` enum('all','selected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `hq_ids` text COLLATE utf8mb4_general_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Active,0=Hidden',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `state_id`, `title`, `message`, `send_type`, `hq_ids`, `status`, `created_at`) VALUES
(1, NULL, 'dishan ', 'mhjhjj', 'selected', '8,10', 0, '2026-06-30 05:47:04'),
(2, NULL, 'tomorrow holiday ', 'due to heavy rainfall tomorrow holiday ', 'all', NULL, 1, '2026-06-30 06:39:43'),
(3, NULL, 'therer', 'gdgdgrd', 'selected', '10', 1, '2026-06-30 06:48:56'),
(4, 1, 'rswfswf', 'grgrger', 'selected', '9,10', 1, '2026-07-02 10:18:21'),
(5, 1, 'Iam happy', 'today is big day', 'selected', '9', 1, '2026-07-06 11:43:02'),
(6, 1, 'Iam happy', 'today is big day', 'selected', '9', 1, '2026-07-06 11:43:02'),
(7, 1, 'this is the day', 'this is the day', 'selected', '9', 1, '2026-07-06 11:44:02'),
(9, 0, 'if there is many hq', 'bcvbhncgb', 'selected', '1', 1, '2026-07-07 04:59:18'),
(11, 1, 'fghbdghd', 'grdgdrg', 'selected', '9', 1, '2026-07-10 05:10:32'),
(12, 0, 'gfgfdg', 'fdgdfg', 'selected', '1', 1, '2026-07-10 06:40:28'),
(13, 0, 'cgnj', 'hfyhyft', 'all', '', 1, '2026-07-11 08:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `notific_seen`
--

CREATE TABLE `notific_seen` (
  `id` int NOT NULL,
  `hq_Id` int NOT NULL,
  `notification_id` int NOT NULL,
  `seen_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notific_seen`
--

INSERT INTO `notific_seen` (`id`, `hq_Id`, `notification_id`, `seen_at`) VALUES
(1, 10, 4, '2026-07-03 10:04:11'),
(2, 10, 3, '2026-07-03 10:04:11'),
(3, 10, 2, '2026-07-03 10:04:11'),
(5, 10, 13, '2026-07-11 11:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `p_id` int NOT NULL,
  `product_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `packing` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Active','Inactive') COLLATE utf8mb4_general_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`p_id`, `product_code`, `product_name`, `packing`, `created_at`, `status`) VALUES
(2, 'P001', 'Acicalm tab (100)', '100 tab', '2026-06-25 11:23:19', 'Active'),
(3, 'P002', 'Acicalm tab (10x10)', '10 x 10 tab', '2026-06-25 11:23:19', 'Active'),
(4, 'P003', 'Arithnol cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(5, 'P004', 'Arnish cap', '100 caps', '2026-06-25 11:23:19', 'Active'),
(6, 'P005', 'Bacto cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(7, 'P006', 'Bebtone syr', '100 ml', '2026-06-25 11:23:19', 'Active'),
(8, 'P007', 'Bleecid cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(9, 'P008', 'Boroceptol pwd', '10 x 10 gms', '2026-06-25 11:23:19', 'Active'),
(10, 'P009', 'Cafevin cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(11, 'P010', 'Diabe cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(12, 'P011', 'Diodine susp.', '60 ml', '2026-06-25 11:23:19', 'Active'),
(13, 'P012', 'Femfit cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(14, 'P013', 'Gasrup cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(15, 'P014', 'Haemeron cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(16, 'P015', 'Heptoliv cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(17, 'P016', 'Heptoliv drops', '60 ml', '2026-06-25 11:23:19', 'Active'),
(18, 'P018', 'Heptoliv syr(200)', '200 ml', '2026-06-25 11:23:19', 'Active'),
(19, 'P017', 'Heptoliv syr(100)', '100 ml', '2026-06-25 11:23:19', 'Active'),
(20, 'P019', 'Kurchinum gran.', '50 gms', '2026-06-25 11:23:19', 'Active'),
(22, 'P020', 'Leucovage tab', '20 tabs', '2026-06-25 11:23:19', 'Active'),
(23, 'P022', 'Madhuri syr.(200)', '200 ml', '2026-06-25 11:23:19', 'Active'),
(24, 'P021', 'Madhuri syr.(100)', '100 ml', '2026-06-25 11:23:19', 'Active'),
(25, 'P023', 'Madhuri syr.(60)', '60 ml', '2026-06-25 11:23:19', 'Active'),
(26, 'P025', 'Maltoferrol (450)', '450 gms', '2026-06-25 11:23:19', 'Active'),
(27, 'P024', 'Maltoferrol (225)', '225 gms', '2026-06-25 11:23:19', 'Active'),
(28, 'P026', 'Mentomin cap', '10 x 2 caps', '2026-06-25 11:23:19', 'Active'),
(29, 'P029', 'Purif syr (450)', '450 ml', '2026-06-25 11:23:19', 'Active'),
(30, 'P028', 'Purif syr (200)', '200 ml', '2026-06-25 11:23:19', 'Active'),
(31, 'P027', 'Purif syr (100)', '100 ml', '2026-06-25 11:23:19', 'Active'),
(32, 'P030', 'R.D.Sol syr', '100 ml', '2026-06-25 11:23:19', 'Active'),
(33, 'P033', 'R.D.Zyme drops', '60 ml', '2026-06-25 11:23:19', 'Active'),
(34, 'P032', 'R.D.Zyme (200)', '200 ml', '2026-06-25 11:23:19', 'Active'),
(35, 'P031', 'R.D.Zyme (100)', '100 ml', '2026-06-25 11:23:19', 'Active'),
(36, 'P035', 'Raclodex syr(200)', '200 ml', '2026-06-25 11:23:19', 'Active'),
(37, 'P034', 'Raclodex syr(100)', '100 ml', '2026-06-25 11:23:19', 'Active'),
(38, 'P036', 'Rechi pwd (100)', '100 gms', '2026-06-25 11:23:19', 'Active'),
(39, 'P037', 'Rechi pwd (3)', '3 gms', '2026-06-25 11:23:19', 'Active'),
(40, 'P038', 'Reserpento cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(41, 'P039', 'Rudanti cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(42, 'P040', 'Rudragen cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(43, 'P041', 'Scabinex oil', '60 ml', '2026-06-25 11:23:19', 'Active'),
(44, 'P042', 'Scabinex pwd', '10 x 10 gms', '2026-06-25 11:23:19', 'Active'),
(45, 'P043', 'Shakti tone syr', '200 ml', '2026-06-25 11:23:19', 'Active'),
(46, 'P044', 'Shaktina cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(47, 'P045', 'Soriton cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(48, 'P046', 'Stri jeewan (450)', '450 ml', '2026-06-25 11:23:19', 'Active'),
(49, 'P047', 'Stri jeewan(200)', '200 ml', '2026-06-25 11:23:19', 'Active'),
(50, 'P048', 'Sulangin cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(51, 'P049', 'Switran tab(100)', '100 tab', '2026-06-25 11:23:19', 'Active'),
(52, 'P050', 'Switran tab(10x10)', '10 x 10 tab', '2026-06-25 11:23:19', 'Active'),
(53, 'P052', 'Utitone syr.(450)', '450 ml', '2026-06-25 11:23:19', 'Active'),
(54, 'P051', 'Utitone syr.(200)', '200 ml', '2026-06-25 11:23:19', 'Active'),
(55, 'P053', 'Vatayani cap', '10 x 10 caps', '2026-06-25 11:23:19', 'Active'),
(56, 'P054', 'Vatraktari gran.', '100 gms', '2026-06-25 11:23:19', 'Active'),
(57, 'P055', 'Winter oil(110)', '110 ml', '2026-06-25 11:23:19', 'Active'),
(58, 'P056', 'Winter oil(60)', '60 ml', '2026-06-25 11:23:19', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `batch_id` int NOT NULL,
  `product_id` int NOT NULL,
  `state_id` int DEFAULT NULL,
  `batch_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `purchase_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `available_qty` int NOT NULL DEFAULT '0',
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_batches`
--

INSERT INTO `product_batches` (`batch_id`, `product_id`, `state_id`, `batch_no`, `status`, `created_at`, `updated_at`, `purchase_rate`, `available_qty`, `expiry_date`) VALUES
(1, 2, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(2, 3, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(3, 4, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(4, 5, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(5, 6, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(6, 7, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(7, 8, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(8, 9, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(9, 10, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(10, 11, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(11, 12, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(12, 13, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(13, 14, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(14, 15, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(15, 16, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(16, 17, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(17, 18, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(18, 19, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(19, 20, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(21, 22, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(22, 23, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(23, 24, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(24, 25, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(25, 26, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(26, 27, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(27, 28, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(28, 29, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(29, 30, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(30, 31, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(31, 32, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(32, 33, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(33, 34, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(34, 35, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(35, 36, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(36, 37, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(37, 38, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(38, 39, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(39, 40, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(40, 41, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(41, 42, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(42, 43, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(43, 44, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(44, 45, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(45, 46, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(46, 47, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(47, 48, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(48, 49, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(49, 50, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(50, 51, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(51, 52, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(52, 53, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(53, 54, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(54, 55, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(55, 56, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(56, 57, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(57, 58, NULL, 'A002', '0', '2026-06-25 05:54:37', '2026-06-29 06:16:56', 0.00, 0, NULL),
(58, 2, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(59, 3, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(60, 4, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(61, 5, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(62, 6, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(63, 7, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(64, 8, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(65, 9, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(66, 10, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(67, 11, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(68, 12, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(69, 13, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(70, 14, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(71, 15, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(72, 16, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(73, 17, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(74, 18, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(75, 19, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(76, 20, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(78, 22, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(79, 23, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(80, 24, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(81, 25, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(82, 26, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(83, 27, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(84, 28, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(85, 29, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(86, 30, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(87, 31, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(88, 32, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(89, 33, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(90, 34, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(91, 35, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(92, 36, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(93, 37, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(94, 38, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(95, 39, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(96, 40, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(97, 41, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(98, 42, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(99, 43, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(100, 44, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(101, 45, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(102, 46, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(103, 47, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(104, 48, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(105, 49, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(106, 50, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(107, 51, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(108, 52, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(109, 53, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(110, 54, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(111, 55, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(112, 56, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(113, 57, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(114, 58, NULL, 'A003', '1', '2026-06-25 05:57:32', '2026-07-03 09:13:37', 0.00, 0, NULL),
(115, 2, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(116, 3, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(117, 4, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(118, 5, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(119, 6, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(120, 7, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(121, 8, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(122, 9, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(123, 10, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(124, 11, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(125, 12, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(126, 13, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(127, 14, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(128, 15, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(129, 16, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(130, 17, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(131, 18, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(132, 19, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(133, 20, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(135, 22, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(136, 23, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(137, 24, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(138, 25, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(139, 26, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(140, 27, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(141, 28, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(142, 29, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(143, 30, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(144, 31, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(145, 32, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(146, 33, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(147, 34, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(148, 35, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(149, 36, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(150, 37, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(151, 38, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(152, 39, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(153, 40, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(154, 41, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(155, 42, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(156, 43, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(157, 44, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(158, 45, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(159, 46, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(160, 47, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(161, 48, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(162, 49, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(163, 50, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(164, 51, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(165, 52, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(166, 53, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(167, 54, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(168, 55, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(169, 56, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(170, 57, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(171, 58, NULL, 'A004', '1', '2026-06-25 06:06:20', '2026-06-29 06:16:40', 0.00, 0, NULL),
(172, 2, NULL, '100 tab', '199.14684', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(173, 3, NULL, '10 x 10 tab', '226.48072000000002', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(174, 4, NULL, '10 x 10 caps', '406.10336000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(175, 5, NULL, '100 caps', '452.96144000000004', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(176, 6, NULL, '10 x 10 caps', '499.81952000000007', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(177, 7, NULL, '100 ml', '83.001659999999987', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(178, 8, NULL, '10 x 10 caps', '452.96144000000004', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(179, 9, NULL, '10 x 10 gms', '406.10336000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(180, 10, NULL, '10 x 10 caps', '406.10336000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(181, 11, NULL, '10 x 10 caps', '452.96144000000004', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(182, 12, NULL, '60 ml', '75.096739999999997', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(183, 13, NULL, '10 x 10 caps', '585.726', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(184, 14, NULL, '10 x 10 caps', '452.96144000000004', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(185, 15, NULL, '10 x 10 caps', '452.96144000000004', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(186, 16, NULL, '10 x 10 caps', '452.96144000000004', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(187, 17, NULL, '60 ml', '75.096739999999997', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(188, 18, NULL, '200 ml', '130.43118000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(189, 19, NULL, '100 ml', '75.096739999999997', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(190, 20, NULL, '50 gms', '82.001639999999995', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(191, 22, NULL, '20 tabs', '70.287120000000002', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(192, 23, NULL, '200 ml', '134.38364000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(193, 24, NULL, '100 ml', '79.049199999999999', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(194, 25, NULL, '60 ml', '51.381979999999999', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(195, 26, NULL, '450 gms', '221.33776', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(196, 27, NULL, '225 gms', '130.43118000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(197, 28, NULL, '10 x 2 caps', '74.191959999999995', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(198, 29, NULL, '450 ml', '213.43284000000003', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(199, 30, NULL, '200 ml', '130.43118000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(200, 31, NULL, '100 ml', '75.096739999999997', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(201, 32, NULL, '100 ml', '75.096739999999997', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(202, 33, NULL, '60 ml', '75.096739999999997', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(203, 34, NULL, '200 ml', '130.43118000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(204, 35, NULL, '100 ml', '75.096739999999997', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(205, 36, NULL, '200 ml', '130.43118000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(206, 37, NULL, '100 ml', '75.096739999999997', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(207, 38, NULL, '100 gms', '102.76396', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(208, 39, NULL, '3 gms', '6.3239359999999998', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(209, 40, NULL, '10 x 10 caps', '452.96144000000004', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(210, 41, NULL, '10 x 10 caps', '499.81952000000007', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(211, 42, NULL, '10 x 10 caps', '499.81952000000007', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(212, 43, NULL, '60 ml', '86.954119999999989', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(213, 44, NULL, '10 x 10 gms', '468.58080000000007', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(214, 45, NULL, '200 ml', '130.43118000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(215, 46, NULL, '10 x 10 caps', '624.77440000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(216, 47, NULL, '10 x 10 caps', '452.96144000000004', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(217, 48, NULL, '450 ml', '229.24268000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(218, 49, NULL, '200 ml', '130.43118000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(219, 50, NULL, '10 x 10 caps', '406.10336000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(220, 51, NULL, '100 tab', '226.48072000000002', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(221, 52, NULL, '10 x 10 tab', '273.33879999999999', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(222, 53, NULL, '450 ml', '229.24268000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(223, 54, NULL, '200 ml', '130.43118000000001', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(224, 55, NULL, '10 x 10 caps', '499.81952000000007', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(225, 56, NULL, '100 gms', '164.00327999999999', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(226, 57, NULL, '110 ml', '158.0984', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(227, 58, NULL, '60 ml', '102.76396', '2026-07-02 09:41:49', '2026-07-02 09:41:49', 0.00, 0, NULL),
(228, 3, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(229, 4, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(230, 5, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(231, 6, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(232, 7, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(233, 8, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(234, 9, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(235, 10, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(236, 11, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(237, 12, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(238, 13, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(239, 14, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(240, 15, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(241, 16, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(242, 17, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(243, 18, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(244, 19, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(245, 20, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(246, 22, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(247, 23, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(248, 24, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(249, 25, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(250, 26, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(251, 27, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(252, 28, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(253, 29, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(254, 30, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(255, 31, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(256, 32, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(257, 33, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(258, 34, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(259, 35, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(260, 36, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(261, 37, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(262, 38, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(263, 39, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(264, 40, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(265, 41, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(266, 42, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(267, 43, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(268, 44, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(269, 45, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(270, 46, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(271, 47, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(272, 48, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(273, 49, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(274, 50, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(275, 51, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(276, 52, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(277, 53, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(278, 54, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(279, 55, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(280, 56, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(281, 57, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(282, 58, NULL, 'A001', '1', '2026-07-02 09:46:14', '2026-07-03 07:00:54', 0.00, 0, NULL),
(283, 3, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(284, 4, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(285, 5, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(286, 6, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(287, 7, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(288, 8, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(289, 9, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(290, 10, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(291, 11, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(292, 12, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(293, 13, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(294, 14, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(295, 15, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(296, 16, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(297, 17, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(298, 18, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(299, 19, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(300, 20, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(301, 22, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(302, 23, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(303, 24, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(304, 25, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(305, 26, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(306, 27, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(307, 28, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(308, 29, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(309, 30, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(310, 31, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(311, 32, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(312, 33, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(313, 34, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(314, 35, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(315, 36, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(316, 37, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(317, 38, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(318, 39, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(319, 40, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(320, 41, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(321, 42, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(322, 43, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(323, 44, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(324, 45, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(325, 46, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(326, 47, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(327, 48, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(328, 49, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(329, 50, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(330, 51, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(331, 52, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(332, 53, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(333, 54, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(334, 55, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(335, 56, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(336, 57, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(337, 58, NULL, 'A005', '0', '2026-07-02 09:47:23', '2026-07-04 10:27:13', 0.00, 0, NULL),
(338, 3, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(339, 4, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(340, 5, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(341, 6, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(342, 7, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(343, 8, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(344, 9, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(345, 10, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(346, 11, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(347, 12, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(348, 13, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(349, 14, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(350, 15, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(351, 16, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(352, 17, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(353, 18, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(354, 19, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(355, 20, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(356, 22, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(357, 23, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(358, 24, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(359, 25, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(360, 26, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(361, 27, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(362, 28, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(363, 29, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(364, 30, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(365, 31, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(366, 32, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(367, 33, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(368, 34, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(369, 35, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(370, 36, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(371, 37, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(372, 38, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(373, 39, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(374, 40, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(375, 41, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(376, 42, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(377, 43, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(378, 44, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(379, 45, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(380, 46, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(381, 47, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(382, 48, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(383, 49, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(384, 50, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(385, 51, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(386, 52, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(387, 53, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(388, 54, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(389, 55, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(390, 56, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(391, 57, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(392, 58, NULL, 'A007', 'Active', '2026-07-04 09:00:00', '2026-07-04 09:00:00', 0.00, 0, NULL),
(393, 3, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(394, 4, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(395, 5, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(396, 6, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(397, 7, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(398, 8, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(399, 9, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(400, 10, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(401, 11, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(402, 12, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(403, 13, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(404, 14, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(405, 15, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(406, 16, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(407, 17, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(408, 19, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(409, 18, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(410, 20, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(411, 22, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(412, 24, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(413, 23, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(414, 25, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(415, 27, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(416, 26, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(417, 28, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(418, 31, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(419, 30, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(420, 29, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(421, 32, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(422, 35, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(423, 34, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(424, 33, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(425, 37, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(426, 36, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(427, 38, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(428, 39, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(429, 40, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(430, 41, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(431, 42, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(432, 43, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(433, 44, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(434, 45, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(435, 46, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(436, 47, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(437, 48, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(438, 49, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(439, 50, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(440, 51, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(441, 52, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(442, 54, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(443, 53, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(444, 55, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(445, 56, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(446, 57, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(447, 58, 1, 'A008', '1', '2026-07-07 10:01:56', '2026-07-07 10:22:33', 0.00, 0, NULL),
(448, 3, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(449, 4, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(450, 5, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(451, 6, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(452, 7, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(453, 8, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(454, 9, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(455, 10, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(456, 11, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(457, 12, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(458, 13, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(459, 14, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(460, 15, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(461, 16, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(462, 17, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(463, 19, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(464, 18, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(465, 20, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(466, 22, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(467, 24, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(468, 23, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(469, 25, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(470, 27, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(471, 26, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(472, 28, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(473, 31, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(474, 30, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(475, 29, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(476, 32, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(477, 35, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(478, 34, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(479, 33, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(480, 37, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(481, 36, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(482, 38, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(483, 39, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(484, 40, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(485, 41, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(486, 42, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(487, 43, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(488, 44, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(489, 45, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(490, 46, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(491, 47, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(492, 48, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(493, 49, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(494, 50, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(495, 51, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(496, 52, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(497, 54, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(498, 53, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(499, 55, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(500, 56, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(501, 57, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(502, 58, 13, 'A009', '0', '2026-07-07 10:15:27', '2026-07-10 11:27:18', 0.00, 0, NULL),
(503, 2, 13, 'A009', '0', '2026-07-07 10:16:59', '2026-07-10 11:27:18', 0.00, 0, NULL),
(504, 2, 1, 'A008', '1', '2026-07-07 10:18:42', '2026-07-07 10:22:33', 0.00, 0, NULL),
(505, 2, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(506, 3, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(507, 4, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(508, 5, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(509, 16, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(510, 17, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(511, 19, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(512, 18, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(513, 20, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(514, 22, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(515, 24, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(516, 23, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(517, 25, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(518, 27, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(519, 26, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(520, 28, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(521, 31, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(522, 30, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(523, 29, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(524, 32, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(525, 35, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(526, 34, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(527, 33, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(528, 37, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(529, 36, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(530, 38, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(531, 39, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(532, 40, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(533, 41, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(534, 42, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(535, 43, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(536, 44, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(537, 45, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(538, 46, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(539, 47, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(540, 48, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(541, 49, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(542, 50, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(543, 51, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(544, 52, 3, 'A010', '1', '2026-07-07 10:21:33', '2026-07-07 10:22:39', 0.00, 0, NULL),
(545, 2, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(546, 3, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(547, 6, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(548, 7, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(549, 8, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(550, 11, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(551, 12, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(552, 14, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(553, 17, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL);
INSERT INTO `product_batches` (`batch_id`, `product_id`, `state_id`, `batch_no`, `status`, `created_at`, `updated_at`, `purchase_rate`, `available_qty`, `expiry_date`) VALUES
(554, 18, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(555, 19, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(556, 22, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(557, 25, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(558, 23, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(559, 27, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(560, 30, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(561, 29, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(562, 32, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(563, 36, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(564, 38, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(565, 40, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(566, 39, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(567, 42, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(568, 43, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(569, 47, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(570, 46, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(571, 49, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(572, 50, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(573, 52, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(574, 54, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(575, 55, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(576, 53, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(577, 56, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL),
(578, 58, 13, 'A011', '1', '2026-07-10 11:23:50', '2026-07-10 11:25:32', 0.00, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_entry`
--

CREATE TABLE `purchase_entry` (
  `purchase_id` int NOT NULL,
  `purchase_no` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `supplier_id` int NOT NULL,
  `invoice_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `purchase_date` date NOT NULL,
  `total_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sub_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gst_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `other_charges` decimal(12,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `remarks` text COLLATE utf8mb4_general_ci,
  `status` enum('Draft','Completed','Cancelled') COLLATE utf8mb4_general_ci DEFAULT 'Completed',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_entry_details`
--

CREATE TABLE `purchase_entry_details` (
  `detail_id` int NOT NULL,
  `purchase_id` int NOT NULL,
  `product_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `batch_no` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `purchase_rate` decimal(12,2) NOT NULL,
  `mrp` decimal(12,2) DEFAULT '0.00',
  `qty` decimal(12,2) NOT NULL,
  `free_qty` decimal(12,2) DEFAULT '0.00',
  `amount` decimal(12,2) NOT NULL,
  `available_qty` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_details`
--

CREATE TABLE `sales_details` (
  `sale_detail_id` int NOT NULL,
  `s_id` int NOT NULL,
  `p_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `qty` int NOT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_details`
--

INSERT INTO `sales_details` (`sale_detail_id`, `s_id`, `p_id`, `batch_id`, `qty`, `rate`, `amount`) VALUES
(1, 1, 2, NULL, 21, 0.00, 0.00),
(2, 2, 2, 58, 50, 190.00, 9500.00),
(3, 2, 2, 115, 40, 250.00, 10000.00),
(4, 3, 2, 58, 291, 190.00, 55290.00),
(5, 4, 2, 1, 100, 190.23, 19023.00),
(6, 4, 2, 115, 25, 250.23, 6255.75),
(7, 5, 58, 171, 126, 93.52, 11783.52),
(8, 5, 57, 170, 26, 143.87, 3740.62),
(9, 6, 2, 58, 25, 190.23, 4755.75),
(10, 6, 3, 59, 23, 250.83, 5769.09),
(11, 7, 2, 1, 156, 190.23, 29675.88),
(12, 8, 2, 1, 2, 190.23, 380.46),
(13, 9, 2, 58, 12, 190.23, 2282.76),
(14, 9, 3, 59, 20, 250.83, 5016.60),
(15, 10, 2, 58, 24, 190.23, 4565.52),
(16, 10, 2, 115, 1, 250.23, 250.23),
(17, 10, 53, 52, 23, 208.61, 4798.03),
(18, 11, 2, 58, 25, 190.23, 4755.75),
(19, 11, 3, 116, 25, 270.83, 6770.75),
(20, 12, 2, 115, 50, 250.23, 12511.50),
(21, 12, 3, 116, 60, 270.83, 16249.80),
(22, 13, 2, 115, 1, 250.23, 250.23),
(23, 14, 2, 115, 1, 250.23, 250.23),
(24, 15, 2, 58, 11, 190.23, 2092.53),
(25, 15, 3, 59, 12, 250.83, 3009.96),
(26, 15, 4, 60, 13, 365.49, 4751.37),
(27, 16, 2, 58, 1, 190.23, 190.23),
(28, 17, 2, 58, 2, 190.23, 380.46),
(29, 17, 3, 116, 1, 270.83, 270.83),
(30, 18, 2, 58, 2, 190.23, 380.46),
(31, 18, 3, 116, 1, 270.83, 270.83),
(32, 19, 2, 58, 2, 190.23, 380.46),
(33, 19, 3, 116, 1, 270.83, 270.83),
(34, 20, 2, 58, 1, 190.23, 190.23),
(35, 20, 3, 116, 1, 270.83, 270.83),
(36, 21, 2, 503, 5, 149.37, 746.85),
(37, 21, 3, 448, 10, 169.87, 1698.70);

-- --------------------------------------------------------

--
-- Table structure for table `sales_entries`
--

CREATE TABLE `sales_entries` (
  `s_id` int NOT NULL,
  `m_id` int NOT NULL,
  `fy_id` int NOT NULL,
  `stockist_id` int NOT NULL,
  `c_id` int NOT NULL,
  `total_amt` decimal(10,2) NOT NULL,
  `sale_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_entries`
--

INSERT INTO `sales_entries` (`s_id`, `m_id`, `fy_id`, `stockist_id`, `c_id`, `total_amt`, `sale_date`) VALUES
(1, 10, 0, 10, 13, 0.00, '2026-06-26 00:00:00'),
(2, 10, 0, 10, 13, 19500.00, '2026-06-25 00:00:00'),
(3, 10, 0, 10, 8, 55290.00, '2026-06-16 00:00:00'),
(4, 10, 0, 10, 13, 25278.75, '2026-06-25 00:00:00'),
(5, 10, 0, 10, 7, 15524.14, '2026-06-25 00:00:00'),
(6, 10, 0, 9, 11, 10524.84, '2026-06-25 00:00:00'),
(7, 10, 0, 10, 20, 29675.88, '2026-06-02 00:00:00'),
(8, 10, 0, 9, 21, 380.46, '2026-06-27 00:00:00'),
(9, 10, 0, 9, 23, 7299.36, '2026-06-27 00:00:00'),
(10, 10, 1, 10, 20, 9613.78, '2026-06-29 00:00:00'),
(11, 11, 2, 5, 2, 11526.50, '2026-06-29 00:00:00'),
(12, 11, 2, 5, 2, 28761.30, '2026-06-29 00:00:00'),
(13, 11, 2, 5, 2, 250.23, '2026-06-29 00:00:00'),
(14, 11, 2, 5, 2, 250.23, '2026-06-29 00:00:00'),
(15, 11, 2, 4, 2, 9853.86, '2026-06-29 00:00:00'),
(16, 10, 10, 10, 21, 190.23, '2026-07-04 00:00:00'),
(17, 10, 10, 10, 21, 651.29, '2026-07-04 00:00:00'),
(18, 10, 10, 10, 21, 651.29, '2026-07-04 00:00:00'),
(19, 10, 10, 10, 21, 651.29, '2026-07-04 00:00:00'),
(20, 10, 10, 10, 21, 461.06, '2026-07-04 00:00:00'),
(21, 15, 12, 17, 36, 2445.55, '2026-07-08 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `state`
--

CREATE TABLE `state` (
  `state_id` int NOT NULL,
  `state_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `state_status` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `state`
--

INSERT INTO `state` (`state_id`, `state_name`, `state_status`) VALUES
(1, 'Bihar', 1),
(3, 'UP', 1),
(8, 'utrakhand', 0),
(9, 'bihari', 0),
(10, 'bihari', 0),
(13, 'Nepal', 1),
(15, 'Gujarat', 1);

-- --------------------------------------------------------

--
-- Table structure for table `stockists`
--

CREATE TABLE `stockists` (
  `stockist_id` int NOT NULL,
  `stockist_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `number` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL,
  `state` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `district` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `pincode` varchar(35) COLLATE utf8mb4_general_ci NOT NULL,
  `hq_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `stockist_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stockists`
--

INSERT INTO `stockists` (`stockist_id`, `stockist_name`, `number`, `status`, `state`, `district`, `pincode`, `hq_id`, `admin_id`, `address`, `stockist_image`) VALUES
(4, 'rambhau', '9408161972', 1, '1', 'rampur', '390011', 11, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1781697190_march-7-quality-assurance-training.jpg'),
(5, 'dishan', '9408161972', 1, '1', 'rampur', '390011', 11, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1781766744_Rath Yatra Vector Hd PNG Images, Happy Rath Yatra Celebration For Lord Jagannath Balabhadra And Subhadra, Iskcon, Vishnu, Rathyatra PNG Image For Free Download.jpg'),
(6, 'rajesh', '9408161972', 1, '1', 'rampur', '390011', 9, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1781766526_IMG_20260611_180319666_HDR.jpg'),
(7, 'dddddf', '9408161972', 1, '1', 'East Champaran', '390011', 9, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1781767292_1000237966.jpg'),
(8, 'rajesh1', '9408161972', 1, '1', 'rampur', '390011', 11, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1781767201_IMG-20231014-WA0016-removebg (1).png'),
(9, 'vinod', '9408161972', 0, '1', 'East Champaran', '390011', 10, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1781933159_Screenshot 2026-06-19 172413.jpg'),
(10, 'dish', '1234567890', 0, '1', 'Banka', '390011', 10, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1782974103_x1080.jfif'),
(11, 'Test', '4456547465', 0, '1', 'Jehanabad', '390011', 11, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1782981919_Company Logo(1).png'),
(12, 'rtgrtyrt', '9402555555', 1, '1', 'Arwal', '390025', 10, 0, 'fyhfyhrtyrtr', '1782994878_PURIF.png'),
(14, 'rajes12', '9408161973', 1, '0', 'vadodara', '390011', 1, 0, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', '1783316183_10401.jpg'),
(15, 'raju', '9408161972', 1, '1', 'Aurangabad', '390011', 11, 1, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', ''),
(16, 'amul medical ', '1234567890', 1, '0', 'vadodara', '390011', 1, 1, 'Monalisa Business Centre, 1st Floor, B 116 -118, B Wing Tower-30, Manjalpur', ''),
(17, 'Nepal test stock', '4565896354', 1, '13', 'kathmandu', '56853', 15, 11, 'dhfghfgh', '1783490715_r-logo.png');

-- --------------------------------------------------------

--
-- Table structure for table `stockist_stock`
--

CREATE TABLE `stockist_stock` (
  `id` int NOT NULL,
  `stockist_id` int NOT NULL,
  `p_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `opening_qty` int DEFAULT '0',
  `current_qty` int DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stockist_stock`
--

INSERT INTO `stockist_stock` (`id`, `stockist_id`, `p_id`, `batch_id`, `opening_qty`, `current_qty`, `updated_at`) VALUES
(1, 10, 2, 58, 250, 118, '2026-07-04 11:23:12'),
(2, 10, 57, 170, 40, 14, '2026-06-25 11:08:17'),
(3, 10, 58, 171, 250, 124, '2026-06-25 11:08:17'),
(4, 10, 2, 115, 100, 245, '2026-07-03 06:42:55'),
(6, 10, 53, 52, 50, 27, '2026-06-29 07:32:54'),
(7, 9, 2, 58, 55, 18, '2026-06-27 10:36:19'),
(8, 9, 3, 59, 220, 177, '2026-06-27 10:36:19'),
(9, 10, 2, 1, 256, 0, '2026-06-26 05:28:04'),
(10, 9, 2, 1, 12, 9, '2026-06-27 08:17:04'),
(11, 5, 2, 58, 25, 0, '2026-06-29 07:38:45'),
(12, 5, 3, 116, 25, 80, '2026-06-29 07:41:02'),
(13, 5, 2, 115, 150, 98, '2026-06-29 07:43:21'),
(14, 10, 3, 116, 60, 56, '2026-07-04 11:23:12'),
(15, 10, 4, 117, 4, 4, '2026-06-29 10:23:34'),
(16, 4, 2, 58, 11, 0, '2026-06-29 10:30:11'),
(17, 4, 3, 59, 12, 0, '2026-06-29 10:30:11'),
(18, 4, 4, 60, 13, 0, '2026-06-29 10:30:11'),
(19, 0, 2, 58, 258, 258, '2026-07-03 05:54:48'),
(20, 0, 3, 59, 100, 100, '2026-07-03 05:54:48'),
(21, 0, 4, 60, 40, 40, '2026-07-03 05:54:48'),
(22, 0, 2, 115, 40, 40, '2026-07-03 05:59:26'),
(23, 0, 3, 116, 60, 60, '2026-07-03 05:59:26'),
(24, 0, 4, 117, 40, 40, '2026-07-03 05:59:26'),
(25, 0, 5, 118, 10, 10, '2026-07-03 05:59:26'),
(26, 9, 2, 115, 40, 40, '2026-07-03 06:04:06'),
(27, 6, 3, 228, 100, 50, '2026-07-03 09:13:18'),
(28, 6, 4, 229, 100, 50, '2026-07-03 09:13:18'),
(29, 8, 2, 504, 25, 25, '2026-07-07 10:42:05'),
(30, 8, 3, 393, 26, 26, '2026-07-07 10:42:05'),
(31, 8, 4, 394, 60, 60, '2026-07-07 10:42:05'),
(32, 10, 2, 504, 55, 50, '2026-07-07 12:20:44'),
(33, 10, 3, 393, 66, 66, '2026-07-07 11:17:03'),
(34, 10, 4, 394, 78, 78, '2026-07-07 11:17:03'),
(35, 10, 5, 395, 78, 78, '2026-07-07 11:17:03'),
(36, 10, 6, 396, 56, 56, '2026-07-07 11:17:03'),
(37, 17, 2, 503, 10, 65, '2026-07-08 09:11:22'),
(38, 17, 3, 448, 20, 70, '2026-07-08 09:11:22'),
(39, 17, 4, 449, 100, 70, '2026-07-08 09:11:22'),
(40, 17, 6, 451, 100, 100, '2026-07-09 05:20:04'),
(41, 17, 10, 455, 100, 100, '2026-07-09 05:20:04'),
(42, 12, 2, 504, 100, 100, '2026-07-11 06:46:56'),
(43, 12, 3, 393, 120, 120, '2026-07-11 06:46:56'),
(44, 12, 4, 394, 120, 120, '2026-07-11 06:46:56'),
(45, 12, 5, 395, 30, 30, '2026-07-11 06:46:56');

-- --------------------------------------------------------

--
-- Table structure for table `stock_inward`
--

CREATE TABLE `stock_inward` (
  `inward_id` int NOT NULL,
  `stockist_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `fy_id` int NOT NULL,
  `inward_date` date NOT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_inward`
--

INSERT INTO `stock_inward` (`inward_id`, `stockist_id`, `admin_id`, `fy_id`, `inward_date`, `remarks`, `created_at`) VALUES
(1, 9, 0, 0, '2026-06-21', '100 acicalm', '2026-06-21 11:30:00'),
(2, 10, 0, 0, '2026-06-21', 'given many tablets ', '2026-06-21 11:30:34'),
(3, 10, 0, 0, '2026-06-18', '250', '2026-06-21 11:32:55'),
(4, 9, 0, 0, '2026-06-22', '22 table', '2026-06-22 11:43:50'),
(5, 10, 0, 0, '2026-06-25', 'batch wise inward added', '2026-06-25 06:27:20'),
(6, 10, 0, 0, '2026-06-25', 'batch wise inward added', '2026-06-25 06:28:20'),
(7, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:38:29'),
(8, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:39:20'),
(9, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:41:14'),
(10, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:41:44'),
(11, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:43:42'),
(12, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:45:06'),
(13, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:45:39'),
(14, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:45:59'),
(15, 10, 0, 0, '2026-06-25', '', '2026-06-25 06:46:37'),
(16, 9, 0, 0, '2026-06-25', '', '2026-06-25 06:53:33'),
(17, 10, 0, 0, '2026-06-25', '', '2026-06-25 07:10:24'),
(18, 10, 0, 0, '2026-06-25', '', '2026-06-25 07:22:47'),
(19, 10, 0, 0, '2026-06-25', '', '2026-06-25 07:23:21'),
(20, 10, 0, 0, '2026-06-15', '', '2026-06-25 09:44:21'),
(21, 10, 0, 0, '2026-06-26', '', '2026-06-26 05:28:51'),
(22, 10, 0, 0, '2026-06-26', 'test 26-06', '2026-06-26 11:01:40'),
(23, 9, 0, 0, '2026-06-26', '', '2026-06-26 11:02:13'),
(24, 9, 0, 0, '2026-06-26', 'second test 26', '2026-06-26 11:06:51'),
(25, 9, 0, 0, '2026-06-27', '', '2026-06-27 08:09:21'),
(26, 5, 0, 0, '2026-06-29', '', '2026-06-29 07:37:15'),
(27, 5, 0, 0, '2026-06-29', '', '2026-06-29 07:40:43'),
(28, 10, 0, 0, '2026-06-29', 'frsgrgtetetet', '2026-06-29 10:23:34'),
(29, 4, 0, 0, '2026-06-29', '', '2026-06-29 10:26:38'),
(30, 0, 0, 0, '2026-07-03', '', '2026-07-03 05:54:48'),
(31, 0, 0, 0, '2026-07-03', 'this updated code', '2026-07-03 05:59:26'),
(32, 9, 0, 0, '2026-07-03', '', '2026-07-03 06:04:06'),
(33, 10, 0, 0, '2026-07-03', '', '2026-07-03 06:42:55'),
(34, 6, 0, 0, '2026-07-03', 'test', '2026-07-03 09:09:10'),
(35, 6, 0, 0, '2026-07-03', '', '2026-07-03 09:13:18'),
(36, 8, 0, 0, '2026-07-07', 'there is inward', '2026-07-07 10:42:05'),
(37, 10, 0, 0, '2026-07-07', '', '2026-07-07 11:17:03'),
(38, 10, 0, 0, '2026-07-07', '', '2026-07-07 11:34:14'),
(39, 10, 0, 0, '2026-07-07', '', '2026-07-07 12:20:44'),
(40, 17, 1, 0, '2026-07-08', '', '2026-07-08 06:44:31'),
(41, 17, 11, 0, '2026-07-08', 'adding', '2026-07-08 06:47:18'),
(42, 17, 11, 0, '2026-07-07', 'invard', '2026-07-08 09:11:22'),
(43, 17, 11, 0, '2026-07-09', '', '2026-07-09 05:20:04'),
(44, 12, 1, 0, '2026-07-11', '', '2026-07-11 06:46:56');

-- --------------------------------------------------------

--
-- Table structure for table `stock_inward_details`
--

CREATE TABLE `stock_inward_details` (
  `detail_id` int NOT NULL,
  `inward_id` int NOT NULL,
  `p_id` int NOT NULL,
  `batch_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qty` int NOT NULL,
  `rate` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_inward_details`
--

INSERT INTO `stock_inward_details` (`detail_id`, `inward_id`, `p_id`, `batch_id`, `qty`, `rate`) VALUES
(1, 1, 7, NULL, 100, 0.00),
(2, 1, 8, NULL, 100, 0.00),
(3, 1, 4, NULL, 100, 0.00),
(4, 2, 11, NULL, 250, 0.00),
(5, 2, 3, NULL, 500, 0.00),
(6, 3, 3, NULL, 50, 0.00),
(7, 4, 7, NULL, 22, 0.00),
(8, 6, 2, '115', 25, 0.00),
(9, 6, 3, '59', 30, 0.00),
(10, 7, 2, '58', 250, 0.00),
(11, 7, 57, '170', 40, 0.00),
(12, 7, 58, '171', 250, 0.00),
(13, 8, 2, '115', 100, 0.00),
(14, 9, 2, '58', 100, 0.00),
(15, 10, 2, '115', 40, 0.00),
(16, 11, 2, '115', 108, 0.00),
(17, 12, 2, '1', 4, 0.00),
(18, 13, 2, '58', 2, 0.00),
(19, 14, 2, '115', 2, 0.00),
(20, 15, 53, '52', 50, 0.00),
(21, 16, 2, '58', 55, 0.00),
(22, 16, 3, '59', 220, 0.00),
(23, 17, 2, '1', 10, 0.00),
(24, 18, 2, '115', 40, 0.00),
(25, 19, 2, '58', 10, 0.00),
(26, 20, 2, '1', 256, 0.00),
(27, 21, 2, '58', 150, 0.00),
(28, 24, 2, '1', 12, 0.00),
(29, 25, 2, '1', 1, 0.00),
(30, 26, 2, '58', 25, 0.00),
(31, 26, 3, '116', 25, 0.00),
(32, 27, 2, '115', 150, 0.00),
(33, 27, 3, '116', 140, 0.00),
(34, 28, 2, '115', 45, 0.00),
(35, 28, 3, '116', 60, 250.23),
(36, 28, 4, '117', 4, 270.83),
(37, 29, 2, '58', 11, 190.23),
(38, 29, 3, '59', 12, 250.83),
(39, 29, 4, '60', 13, 365.49),
(40, 30, 2, '58', 258, 190.23),
(41, 30, 3, '59', 100, 250.83),
(42, 30, 4, '60', 40, 365.49),
(43, 31, 2, '115', 40, 250.23),
(44, 31, 3, '116', 60, 270.83),
(45, 31, 4, '117', 40, 365.49),
(46, 31, 5, '118', 10, 407.67),
(47, 32, 2, '115', 40, 250.23),
(48, 33, 2, '115', 3, 0.00),
(49, 34, 3, '228', 100, 169.87),
(50, 34, 4, '229', 100, 304.60),
(51, 35, 3, '228', 50, 0.00),
(52, 35, 4, '229', 50, 0.00),
(53, 36, 2, '504', 25, 149.37),
(54, 36, 3, '393', 26, 169.87),
(55, 36, 4, '394', 60, 304.60),
(56, 37, 2, '504', 55, 149.37),
(57, 37, 3, '393', 66, 169.87),
(58, 37, 4, '394', 78, 304.60),
(59, 37, 5, '395', 78, 339.75),
(60, 37, 6, '396', 56, 374.89),
(61, 38, 2, '504', 1, 0.00),
(62, 39, 2, '504', 4, 0.00),
(63, 40, 2, '503', 10, 149.37),
(64, 40, 3, '448', 20, 169.87),
(65, 41, 2, '503', 100, 149.37),
(66, 41, 3, '448', 100, 169.87),
(67, 41, 4, '449', 100, 304.60),
(68, 42, 2, '503', 10, 149.37),
(69, 42, 3, '448', 10, 169.87),
(70, 42, 4, '449', 20, 304.60),
(71, 43, 6, '451', 100, 374.89),
(72, 43, 10, '455', 100, 304.60),
(73, 44, 2, '504', 100, 149.37),
(74, 44, 3, '393', 120, 169.87),
(75, 44, 4, '394', 120, 304.60),
(76, 44, 5, '395', 30, 339.75);

-- --------------------------------------------------------

--
-- Table structure for table `stock_ledger`
--

CREATE TABLE `stock_ledger` (
  `ledger_id` int NOT NULL,
  `trans_date` date NOT NULL,
  `trans_datetime` datetime NOT NULL,
  `stockist_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `p_id` int NOT NULL,
  `trans_type` enum('OPENING','INWARD','SALE','ADJUSTMENT') COLLATE utf8mb4_general_ci NOT NULL,
  `qty` int NOT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `reference_id` int DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `batch_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_ledger`
--

INSERT INTO `stock_ledger` (`ledger_id`, `trans_date`, `trans_datetime`, `stockist_id`, `admin_id`, `p_id`, `trans_type`, `qty`, `amount`, `reference_id`, `remarks`, `created_at`, `batch_id`) VALUES
(1, '2026-06-21', '2026-06-21 13:30:00', 9, 0, 7, 'INWARD', 100, 0.00, 1, '100 acicalm', '2026-06-21 11:30:00', NULL),
(2, '2026-06-21', '2026-06-21 13:30:00', 9, 0, 8, 'INWARD', 100, 0.00, 1, '100 acicalm', '2026-06-21 11:30:00', NULL),
(3, '2026-06-21', '2026-06-21 13:30:00', 9, 0, 4, 'INWARD', 100, 0.00, 1, '100 acicalm', '2026-06-21 11:30:00', NULL),
(4, '2026-06-21', '2026-06-21 13:30:34', 10, 0, 11, 'INWARD', 250, 0.00, 2, 'given many tablets ', '2026-06-21 11:30:34', NULL),
(5, '2026-06-21', '2026-06-21 13:30:34', 10, 0, 3, 'INWARD', 500, 0.00, 2, 'given many tablets ', '2026-06-21 11:30:34', NULL),
(6, '2026-06-21', '2026-06-21 13:31:21', 10, 0, 11, 'SALE', 50, 6600.00, 1, '', '2026-06-21 11:31:21', NULL),
(7, '2026-06-21', '2026-06-21 13:31:21', 10, 0, 3, 'SALE', 100, 45000.00, 1, '', '2026-06-21 11:31:21', NULL),
(8, '2026-06-18', '2026-06-18 13:32:55', 10, 0, 3, 'INWARD', 50, 0.00, 3, '250', '2026-06-21 11:32:55', NULL),
(9, '2026-06-21', '2026-06-21 13:36:21', 9, 0, 8, 'SALE', 50, 6000.00, 2, '', '2026-06-21 11:36:21', NULL),
(10, '2026-06-21', '2026-06-21 13:36:21', 9, 0, 7, 'SALE', 50, 8961.50, 2, '', '2026-06-21 11:36:21', NULL),
(11, '2026-06-21', '2026-06-21 13:36:21', 9, 0, 4, 'SALE', 50, 550.00, 2, '', '2026-06-21 11:36:21', NULL),
(12, '2026-06-21', '2026-06-21 14:48:43', 9, 0, 7, 'SALE', 50, 8961.50, 3, '', '2026-06-21 12:48:43', NULL),
(13, '2026-06-21', '2026-06-21 14:48:43', 9, 0, 8, 'SALE', 50, 6000.00, 3, '', '2026-06-21 12:48:43', NULL),
(14, '2026-06-21', '2026-06-21 14:48:43', 9, 0, 4, 'SALE', 50, 550.00, 3, '', '2026-06-21 12:48:43', NULL),
(15, '2026-06-21', '2026-06-21 14:50:55', 10, 0, 11, 'SALE', 20, 2640.00, 4, '', '2026-06-21 12:50:55', NULL),
(16, '2026-06-21', '2026-06-21 14:50:55', 10, 0, 3, 'SALE', 50, 22500.00, 4, '', '2026-06-21 12:50:55', NULL),
(17, '2026-06-22', '2026-06-22 11:43:50', 9, 0, 7, 'INWARD', 22, 3943.06, 4, '22 table', '2026-06-22 11:43:50', NULL),
(18, '2026-06-23', '2026-06-23 09:05:04', 10, 0, 11, 'SALE', 20, 2640.00, 5, '', '2026-06-23 07:05:04', NULL),
(19, '2026-06-23', '2026-06-23 09:05:04', 10, 0, 3, 'SALE', 50, 22500.00, 5, '', '2026-06-23 07:05:04', NULL),
(20, '2026-06-24', '2026-06-24 11:06:20', 10, 0, 11, 'SALE', 26, 3432.00, 6, '', '2026-06-23 09:06:20', NULL),
(21, '2026-06-24', '2026-06-24 11:06:20', 10, 0, 3, 'SALE', 5, 2250.00, 6, '', '2026-06-23 09:06:20', NULL),
(22, '2026-06-25', '2026-06-25 08:28:20', 10, 0, 2, 'INWARD', 25, 6255.75, 6, 'batch wise inward added', '2026-06-25 06:28:20', 115),
(23, '2026-06-25', '2026-06-25 08:28:20', 10, 0, 3, 'INWARD', 30, 7524.90, 6, 'batch wise inward added', '2026-06-25 06:28:20', 59),
(24, '2026-06-25', '2026-06-25 08:38:29', 10, 0, 2, 'INWARD', 250, 47557.50, 7, '', '2026-06-25 06:38:29', 58),
(25, '2026-06-25', '2026-06-25 08:38:29', 10, 0, 57, 'INWARD', 40, 5754.80, 7, '', '2026-06-25 06:38:29', 170),
(26, '2026-06-25', '2026-06-25 08:38:29', 10, 0, 58, 'INWARD', 250, 23380.00, 7, '', '2026-06-25 06:38:29', 171),
(27, '2026-06-25', '2026-06-25 08:39:20', 10, 0, 2, 'INWARD', 100, 25023.00, 8, '', '2026-06-25 06:39:20', 115),
(28, '2026-06-25', '2026-06-25 08:41:14', 10, 0, 2, 'INWARD', 100, 19023.00, 9, '', '2026-06-25 06:41:14', 58),
(29, '2026-06-25', '2026-06-25 08:41:44', 10, 0, 2, 'INWARD', 40, 10009.20, 10, '', '2026-06-25 06:41:44', 115),
(30, '2026-06-25', '2026-06-25 08:43:42', 10, 0, 2, 'INWARD', 108, 27024.84, 11, '', '2026-06-25 06:43:42', 115),
(31, '2026-06-25', '2026-06-25 08:45:06', 10, 0, 2, 'INWARD', 4, 760.92, 12, '', '2026-06-25 06:45:06', 1),
(32, '2026-06-25', '2026-06-25 08:45:39', 10, 0, 2, 'INWARD', 2, 380.46, 13, '', '2026-06-25 06:45:39', 58),
(33, '2026-06-25', '2026-06-25 08:45:59', 10, 0, 2, 'INWARD', 2, 500.46, 14, '', '2026-06-25 06:45:59', 115),
(34, '2026-06-25', '2026-06-25 08:46:37', 10, 0, 53, 'INWARD', 50, 10430.50, 15, '', '2026-06-25 06:46:37', 52),
(35, '2026-06-25', '2026-06-25 08:53:33', 9, 0, 2, 'INWARD', 55, 10462.65, 16, '', '2026-06-25 06:53:33', 58),
(36, '2026-06-25', '2026-06-25 08:53:33', 9, 0, 3, 'INWARD', 220, 55182.60, 16, '', '2026-06-25 06:53:33', 59),
(37, '2026-06-25', '2026-06-25 09:10:24', 10, 0, 2, 'INWARD', 10, 1902.30, 17, '', '2026-06-25 07:10:24', 1),
(38, '2026-06-25', '2026-06-25 09:22:47', 10, 0, 2, 'INWARD', 40, 10009.20, 18, '', '2026-06-25 07:22:47', 115),
(39, '2026-06-25', '2026-06-25 09:23:21', 10, 0, 2, 'INWARD', 10, 1902.30, 19, '', '2026-06-25 07:23:21', 58),
(40, '2026-06-26', '2026-06-26 09:28:10', 10, 0, 2, 'SALE', 21, 0.00, 1, '', '2026-06-25 07:28:10', NULL),
(41, '2026-06-25', '2026-06-25 10:50:15', 10, 0, 2, 'SALE', 50, 9500.00, 2, '', '2026-06-25 08:50:15', 58),
(42, '2026-06-25', '2026-06-25 10:50:15', 10, 0, 2, 'SALE', 40, 10000.00, 2, '', '2026-06-25 08:50:15', 115),
(43, '2026-06-16', '2026-06-25 11:38:50', 10, 0, 2, 'SALE', 291, 55290.00, 3, '', '2026-06-25 09:38:50', 58),
(44, '2026-06-15', '2026-06-15 11:44:21', 10, 0, 2, 'INWARD', 256, 48698.88, 20, '', '2026-06-25 09:44:21', 1),
(45, '2026-06-25', '2026-06-25 13:06:41', 10, 0, 2, 'SALE', 100, 19023.00, 4, '', '2026-06-25 11:06:41', 1),
(46, '2026-06-25', '2026-06-25 13:06:41', 10, 0, 2, 'SALE', 25, 6255.75, 4, '', '2026-06-25 11:06:41', 115),
(47, '2026-06-25', '2026-06-25 13:08:17', 10, 0, 58, 'SALE', 126, 11783.52, 5, '', '2026-06-25 11:08:17', 171),
(48, '2026-06-25', '2026-06-25 13:08:17', 10, 0, 57, 'SALE', 26, 3740.62, 5, '', '2026-06-25 11:08:17', 170),
(49, '2026-06-25', '2026-06-25 13:14:37', 9, 0, 2, 'SALE', 25, 4755.75, 6, '', '2026-06-25 11:14:37', 58),
(50, '2026-06-25', '2026-06-25 13:14:37', 9, 0, 3, 'SALE', 23, 5769.09, 6, '', '2026-06-25 11:14:37', 59),
(51, '2026-06-02', '2026-06-26 07:28:04', 10, 0, 2, 'SALE', 156, 29675.88, 7, '', '2026-06-26 05:28:04', 1),
(52, '2026-06-26', '2026-06-26 07:28:51', 10, 0, 2, 'INWARD', 150, 28534.50, 21, '', '2026-06-26 05:28:51', 58),
(53, '2026-06-26', '2026-06-26 13:06:51', 9, 0, 2, 'INWARD', 12, 2282.76, 24, 'second test 26', '2026-06-26 11:06:51', 1),
(54, '2026-06-27', '2026-06-27 10:09:21', 9, 0, 2, 'ADJUSTMENT', -1, -190.23, 25, '', '2026-06-27 08:09:21', 1),
(55, '2026-06-27', '2026-06-27 10:17:04', 9, 0, 2, 'SALE', 2, 380.46, 8, '', '2026-06-27 08:17:04', 1),
(56, '2026-06-27', '2026-06-27 12:36:19', 9, 0, 2, 'SALE', 12, 2282.76, 9, '', '2026-06-27 10:36:19', 58),
(57, '2026-06-27', '2026-06-27 12:36:19', 9, 0, 3, 'SALE', 20, 5016.60, 9, '', '2026-06-27 10:36:19', 59),
(58, '2026-06-29', '2026-06-29 09:32:54', 10, 0, 2, 'SALE', 24, 4565.52, 10, '', '2026-06-29 07:32:54', 58),
(59, '2026-06-29', '2026-06-29 09:32:54', 10, 0, 2, 'SALE', 1, 250.23, 10, '', '2026-06-29 07:32:54', 115),
(60, '2026-06-29', '2026-06-29 09:32:54', 10, 0, 53, 'SALE', 23, 4798.03, 10, '', '2026-06-29 07:32:54', 52),
(61, '2026-06-29', '2026-06-29 09:37:15', 5, 0, 2, 'INWARD', 25, 4755.75, 26, '', '2026-06-29 07:37:15', 58),
(62, '2026-06-29', '2026-06-29 09:37:15', 5, 0, 3, 'INWARD', 25, 6770.75, 26, '', '2026-06-29 07:37:15', 116),
(63, '2026-06-29', '2026-06-29 09:38:45', 5, 0, 2, 'SALE', 25, 4755.75, 11, '', '2026-06-29 07:38:45', 58),
(64, '2026-06-29', '2026-06-29 09:38:45', 5, 0, 3, 'SALE', 25, 6770.75, 11, '', '2026-06-29 07:38:45', 116),
(65, '2026-06-29', '2026-06-29 09:40:43', 5, 0, 2, 'INWARD', 150, 37534.50, 27, '', '2026-06-29 07:40:43', 115),
(66, '2026-06-29', '2026-06-29 09:40:43', 5, 0, 3, 'INWARD', 140, 37916.20, 27, '', '2026-06-29 07:40:43', 116),
(67, '2026-06-29', '2026-06-29 09:41:02', 5, 0, 2, 'SALE', 50, 12511.50, 12, '', '2026-06-29 07:41:02', 115),
(68, '2026-06-29', '2026-06-29 09:41:02', 5, 0, 3, 'SALE', 60, 16249.80, 12, '', '2026-06-29 07:41:02', 116),
(69, '2026-06-29', '2026-06-29 09:41:48', 5, 0, 2, 'SALE', 1, 250.23, 13, '', '2026-06-29 07:41:48', 115),
(70, '2026-06-29', '2026-06-29 09:43:21', 5, 0, 2, 'SALE', 1, 250.23, 14, '', '2026-06-29 07:43:21', 115),
(71, '2026-06-29', '2026-06-29 12:23:34', 10, 0, 2, 'INWARD', 45, 11260.35, 28, 'frsgrgtetetet', '2026-06-29 10:23:34', 115),
(72, '2026-06-29', '2026-06-29 12:23:34', 10, 0, 3, 'INWARD', 60, 16249.80, 28, 'frsgrgtetetet', '2026-06-29 10:23:34', 116),
(73, '2026-06-29', '2026-06-29 12:23:34', 10, 0, 4, 'INWARD', 4, 1461.96, 28, 'frsgrgtetetet', '2026-06-29 10:23:34', 117),
(74, '2026-06-29', '2026-06-29 12:26:38', 4, 0, 2, 'INWARD', 11, 2092.53, 29, '', '2026-06-29 10:26:38', 58),
(75, '2026-06-29', '2026-06-29 12:26:38', 4, 0, 3, 'INWARD', 12, 3009.96, 29, '', '2026-06-29 10:26:38', 59),
(76, '2026-06-29', '2026-06-29 12:26:38', 4, 0, 4, 'INWARD', 13, 4751.37, 29, '', '2026-06-29 10:26:38', 60),
(77, '2026-06-29', '2026-06-29 12:30:11', 4, 0, 2, 'SALE', 11, 2092.53, 15, '', '2026-06-29 10:30:11', 58),
(78, '2026-06-29', '2026-06-29 12:30:11', 4, 0, 3, 'SALE', 12, 3009.96, 15, '', '2026-06-29 10:30:11', 59),
(79, '2026-06-29', '2026-06-29 12:30:11', 4, 0, 4, 'SALE', 13, 4751.37, 15, '', '2026-06-29 10:30:11', 60),
(80, '2026-07-03', '2026-07-03 11:24:48', 0, 0, 2, 'INWARD', 258, 49079.34, 30, '', '2026-07-03 05:54:48', 58),
(81, '2026-07-03', '2026-07-03 11:24:48', 0, 0, 3, 'INWARD', 100, 25083.00, 30, '', '2026-07-03 05:54:48', 59),
(82, '2026-07-03', '2026-07-03 11:24:48', 0, 0, 4, 'INWARD', 40, 14619.60, 30, '', '2026-07-03 05:54:48', 60),
(83, '2026-07-03', '2026-07-03 11:29:26', 0, 0, 2, 'INWARD', 40, 10009.20, 31, 'this updated code', '2026-07-03 05:59:26', 115),
(84, '2026-07-03', '2026-07-03 11:29:26', 0, 0, 3, 'INWARD', 60, 16249.80, 31, 'this updated code', '2026-07-03 05:59:26', 116),
(85, '2026-07-03', '2026-07-03 11:29:26', 0, 0, 4, 'INWARD', 40, 14619.60, 31, 'this updated code', '2026-07-03 05:59:26', 117),
(86, '2026-07-03', '2026-07-03 11:29:26', 0, 0, 5, 'INWARD', 10, 4076.70, 31, 'this updated code', '2026-07-03 05:59:26', 118),
(87, '2026-07-03', '2026-07-03 11:34:06', 9, 0, 2, 'INWARD', 40, 10009.20, 32, '', '2026-07-03 06:04:06', 115),
(88, '2026-07-03', '2026-07-03 08:42:55', 10, 0, 2, 'ADJUSTMENT', -3, -750.69, 33, '', '2026-07-03 06:42:55', 115),
(89, '2026-07-03', '2026-07-03 14:39:10', 6, 0, 3, 'INWARD', 100, 16987.00, 34, 'test', '2026-07-03 09:09:10', 228),
(90, '2026-07-03', '2026-07-03 14:39:10', 6, 0, 4, 'INWARD', 100, 30460.00, 34, 'test', '2026-07-03 09:09:10', 229),
(91, '2026-07-03', '2026-07-03 11:13:18', 6, 0, 3, 'ADJUSTMENT', -50, -8493.50, 35, '', '2026-07-03 09:13:18', 228),
(92, '2026-07-03', '2026-07-03 11:13:18', 6, 0, 4, 'ADJUSTMENT', -50, -15230.00, 35, '', '2026-07-03 09:13:18', 229),
(93, '2026-07-04', '2026-07-04 13:11:45', 10, 0, 2, 'SALE', 1, 190.23, 16, '', '2026-07-04 11:11:45', 58),
(94, '2026-07-04', '2026-07-04 13:16:26', 10, 0, 2, 'SALE', 2, 380.46, 17, '', '2026-07-04 11:16:26', 58),
(95, '2026-07-04', '2026-07-04 13:16:26', 10, 0, 3, 'SALE', 1, 270.83, 17, '', '2026-07-04 11:16:26', 116),
(96, '2026-07-04', '2026-07-04 13:16:26', 10, 0, 2, 'SALE', 2, 380.46, 18, '', '2026-07-04 11:16:26', 58),
(97, '2026-07-04', '2026-07-04 13:16:26', 10, 0, 3, 'SALE', 1, 270.83, 18, '', '2026-07-04 11:16:26', 116),
(98, '2026-07-04', '2026-07-04 13:22:58', 10, 0, 2, 'SALE', 2, 380.46, 19, '', '2026-07-04 11:22:58', 58),
(99, '2026-07-04', '2026-07-04 13:22:58', 10, 0, 3, 'SALE', 1, 270.83, 19, '', '2026-07-04 11:22:58', 116),
(100, '2026-07-04', '2026-07-04 13:23:12', 10, 0, 2, 'SALE', 1, 190.23, 20, '', '2026-07-04 11:23:12', 58),
(101, '2026-07-04', '2026-07-04 13:23:12', 10, 0, 3, 'SALE', 1, 270.83, 20, '', '2026-07-04 11:23:12', 116),
(102, '2026-07-07', '2026-07-07 16:12:05', 8, 0, 2, 'INWARD', 25, 3734.25, 36, 'there is inward', '2026-07-07 10:42:05', 504),
(103, '2026-07-07', '2026-07-07 16:12:05', 8, 0, 3, 'INWARD', 26, 4416.62, 36, 'there is inward', '2026-07-07 10:42:05', 393),
(104, '2026-07-07', '2026-07-07 16:12:05', 8, 0, 4, 'INWARD', 60, 18276.00, 36, 'there is inward', '2026-07-07 10:42:05', 394),
(105, '2026-07-07', '2026-07-07 16:47:03', 10, 0, 2, 'INWARD', 55, 8215.35, 37, '', '2026-07-07 11:17:03', 504),
(106, '2026-07-07', '2026-07-07 16:47:03', 10, 0, 3, 'INWARD', 66, 11211.42, 37, '', '2026-07-07 11:17:03', 393),
(107, '2026-07-07', '2026-07-07 16:47:03', 10, 0, 4, 'INWARD', 78, 23758.80, 37, '', '2026-07-07 11:17:03', 394),
(108, '2026-07-07', '2026-07-07 16:47:03', 10, 0, 5, 'INWARD', 78, 26500.50, 37, '', '2026-07-07 11:17:03', 395),
(109, '2026-07-07', '2026-07-07 16:47:03', 10, 0, 6, 'INWARD', 56, 20993.84, 37, '', '2026-07-07 11:17:03', 396),
(110, '2026-07-07', '2026-07-07 13:34:14', 10, 0, 2, 'ADJUSTMENT', -1, -149.37, 38, '', '2026-07-07 11:34:14', 504),
(111, '2026-07-07', '2026-07-07 14:20:44', 10, 1, 2, 'ADJUSTMENT', -4, -597.48, 39, '', '2026-07-07 12:20:44', 504),
(112, '2026-07-08', '2026-07-08 12:14:31', 17, 1, 2, 'INWARD', 10, 1493.70, 40, '', '2026-07-08 06:44:31', 503),
(113, '2026-07-08', '2026-07-08 12:14:31', 17, 1, 3, 'INWARD', 20, 3397.40, 40, '', '2026-07-08 06:44:31', 448),
(114, '2026-07-08', '2026-07-08 12:17:18', 17, 11, 2, 'INWARD', 100, 14937.00, 41, 'adding', '2026-07-08 06:47:18', 503),
(115, '2026-07-08', '2026-07-08 12:17:18', 17, 11, 3, 'INWARD', 100, 16987.00, 41, 'adding', '2026-07-08 06:47:18', 448),
(116, '2026-07-08', '2026-07-08 12:17:18', 17, 11, 4, 'INWARD', 100, 30460.00, 41, 'adding', '2026-07-08 06:47:18', 449),
(117, '2026-07-08', '2026-07-08 08:49:41', 17, 11, 2, 'ADJUSTMENT', -50, -7468.50, 0, 'change', '2026-07-08 06:49:41', 503),
(118, '2026-07-08', '2026-07-08 08:49:41', 17, 11, 3, 'ADJUSTMENT', -50, -8493.50, 0, 'change', '2026-07-08 06:49:41', 448),
(119, '2026-07-08', '2026-07-08 08:49:41', 17, 11, 4, 'ADJUSTMENT', -50, -15230.00, 0, 'change', '2026-07-08 06:49:41', 449),
(120, '2026-07-08', '2026-07-08 08:55:34', 17, 0, 2, 'SALE', 5, 746.85, 21, '', '2026-07-08 06:55:34', 503),
(121, '2026-07-08', '2026-07-08 08:55:34', 17, 0, 3, 'SALE', 10, 1698.70, 21, '', '2026-07-08 06:55:34', 448),
(122, '2026-07-07', '2026-07-08 14:41:22', 17, 11, 2, 'INWARD', 10, 1493.70, 42, 'invard', '2026-07-08 09:11:22', 503),
(123, '2026-07-07', '2026-07-08 14:41:22', 17, 11, 3, 'INWARD', 10, 1698.70, 42, 'invard', '2026-07-08 09:11:22', 448),
(124, '2026-07-07', '2026-07-08 14:41:22', 17, 11, 4, 'INWARD', 20, 6092.00, 42, 'invard', '2026-07-08 09:11:22', 449),
(125, '2026-07-09', '2026-07-09 10:50:04', 17, 11, 6, 'INWARD', 100, 37489.00, 43, '', '2026-07-09 05:20:04', 451),
(126, '2026-07-09', '2026-07-09 10:50:04', 17, 11, 10, 'INWARD', 100, 30460.00, 43, '', '2026-07-09 05:20:04', 455),
(127, '2026-07-11', '2026-07-11 12:16:56', 12, 1, 2, 'INWARD', 100, 14937.00, 44, '', '2026-07-11 06:46:56', 504),
(128, '2026-07-11', '2026-07-11 12:16:56', 12, 1, 3, 'INWARD', 120, 20384.40, 44, '', '2026-07-11 06:46:56', 393),
(129, '2026-07-11', '2026-07-11 12:16:56', 12, 1, 4, 'INWARD', 120, 36552.00, 44, '', '2026-07-11 06:46:56', 394),
(130, '2026-07-11', '2026-07-11 12:16:56', 12, 1, 5, 'INWARD', 30, 10192.50, 44, '', '2026-07-11 06:46:56', 395);

-- --------------------------------------------------------

--
-- Table structure for table `super_stockist`
--

CREATE TABLE `super_stockist` (
  `super_stockist_id` int NOT NULL,
  `ss_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `person_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `state` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `district` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `pincode` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `currency` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Rupee, Dollar, Nepalese Rupee, Euro, etc.',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_stockist`
--

INSERT INTO `super_stockist` (`super_stockist_id`, `ss_name`, `person_name`, `country`, `state`, `district`, `pincode`, `currency`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Rudradeo Incorporates, BIHAR', 'rajvi', 'India', '1', 'Patna', '390025', '', 1, '2026-07-15 10:52:58', '2026-07-30 08:15:01'),
(2, 'Sony Ayurvedic distributor, NEPAL', 'sony Ayurved', 'nepal', '13', 'kathmandu', '00000', '', 1, '2026-07-15 11:01:39', '2026-07-30 08:18:04'),
(3, 'AMUL MEDICAL', 'raju', 'India', '15', 'vadodara', '390011', NULL, 1, '2026-07-30 06:39:26', '2026-07-30 12:00:42'),
(4, 'Sony Ayurvetettt', 'rajvi', 'India', '1', 'Araria', '390025', NULL, 1, '2026-07-30 06:49:07', '2026-07-30 07:05:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_state`
--
ALTER TABLE `admin_state`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexes for table `district`
--
ALTER TABLE `district`
  ADD PRIMARY KEY (`district_id`);

--
-- Indexes for table `financial_year`
--
ALTER TABLE `financial_year`
  ADD PRIMARY KEY (`fy_id`),
  ADD KEY `idx_hq` (`hq_id`),
  ADD KEY `idx_hq_status` (`hq_id`,`status`);

--
-- Indexes for table `mr_users`
--
ALTER TABLE `mr_users`
  ADD PRIMARY KEY (`m_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `notific_seen`
--
ALTER TABLE `notific_seen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_hq_notification` (`hq_Id`,`notification_id`),
  ADD KEY `notification_id` (`notification_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`p_id`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_batch` (`batch_no`);

--
-- Indexes for table `purchase_entry`
--
ALTER TABLE `purchase_entry`
  ADD PRIMARY KEY (`purchase_id`),
  ADD UNIQUE KEY `purchase_no` (`purchase_no`);

--
-- Indexes for table `purchase_entry_details`
--
ALTER TABLE `purchase_entry_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `fk_purchase` (`purchase_id`),
  ADD KEY `fk_purchase_product` (`product_id`);

--
-- Indexes for table `sales_details`
--
ALTER TABLE `sales_details`
  ADD PRIMARY KEY (`sale_detail_id`),
  ADD KEY `s_id` (`s_id`),
  ADD KEY `p_id` (`p_id`);

--
-- Indexes for table `sales_entries`
--
ALTER TABLE `sales_entries`
  ADD PRIMARY KEY (`s_id`),
  ADD KEY `m_id` (`m_id`),
  ADD KEY `c_id` (`c_id`);

--
-- Indexes for table `state`
--
ALTER TABLE `state`
  ADD PRIMARY KEY (`state_id`);

--
-- Indexes for table `stockists`
--
ALTER TABLE `stockists`
  ADD PRIMARY KEY (`stockist_id`);

--
-- Indexes for table `stockist_stock`
--
ALTER TABLE `stockist_stock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_inward`
--
ALTER TABLE `stock_inward`
  ADD PRIMARY KEY (`inward_id`);

--
-- Indexes for table `stock_inward_details`
--
ALTER TABLE `stock_inward_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `inward_id` (`inward_id`);

--
-- Indexes for table `stock_ledger`
--
ALTER TABLE `stock_ledger`
  ADD PRIMARY KEY (`ledger_id`),
  ADD KEY `idx_stockist_product_date` (`stockist_id`,`p_id`,`trans_datetime`);

--
-- Indexes for table `super_stockist`
--
ALTER TABLE `super_stockist`
  ADD PRIMARY KEY (`super_stockist_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `admin_state`
--
ALTER TABLE `admin_state`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `c_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `district`
--
ALTER TABLE `district`
  MODIFY `district_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `financial_year`
--
ALTER TABLE `financial_year`
  MODIFY `fy_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `mr_users`
--
ALTER TABLE `mr_users`
  MODIFY `m_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notific_seen`
--
ALTER TABLE `notific_seen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `p_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `batch_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=579;

--
-- AUTO_INCREMENT for table `purchase_entry`
--
ALTER TABLE `purchase_entry`
  MODIFY `purchase_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_entry_details`
--
ALTER TABLE `purchase_entry_details`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_details`
--
ALTER TABLE `sales_details`
  MODIFY `sale_detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `sales_entries`
--
ALTER TABLE `sales_entries`
  MODIFY `s_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `state`
--
ALTER TABLE `state`
  MODIFY `state_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `stockists`
--
ALTER TABLE `stockists`
  MODIFY `stockist_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `stockist_stock`
--
ALTER TABLE `stockist_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `stock_inward`
--
ALTER TABLE `stock_inward`
  MODIFY `inward_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `stock_inward_details`
--
ALTER TABLE `stock_inward_details`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `stock_ledger`
--
ALTER TABLE `stock_ledger`
  MODIFY `ledger_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `super_stockist`
--
ALTER TABLE `super_stockist`
  MODIFY `super_stockist_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notific_seen`
--
ALTER TABLE `notific_seen`
  ADD CONSTRAINT `notific_seen_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `fk_batch_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`p_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_entry_details`
--
ALTER TABLE `purchase_entry_details`
  ADD CONSTRAINT `fk_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchase_entry` (`purchase_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_purchase_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`p_id`);

--
-- Constraints for table `sales_details`
--
ALTER TABLE `sales_details`
  ADD CONSTRAINT `sales_details_ibfk_1` FOREIGN KEY (`s_id`) REFERENCES `sales_entries` (`s_id`),
  ADD CONSTRAINT `sales_details_ibfk_2` FOREIGN KEY (`p_id`) REFERENCES `products` (`p_id`);

--
-- Constraints for table `sales_entries`
--
ALTER TABLE `sales_entries`
  ADD CONSTRAINT `sales_entries_ibfk_1` FOREIGN KEY (`m_id`) REFERENCES `mr_users` (`m_id`),
  ADD CONSTRAINT `sales_entries_ibfk_2` FOREIGN KEY (`c_id`) REFERENCES `customers` (`c_id`);

--
-- Constraints for table `stock_inward_details`
--
ALTER TABLE `stock_inward_details`
  ADD CONSTRAINT `stock_inward_details_ibfk_1` FOREIGN KEY (`inward_id`) REFERENCES `stock_inward` (`inward_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
