-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 02:18 PM
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
-- Database: `bote`
--

-- --------------------------------------------------------

--
-- Table structure for table `carousel_slides`
--

CREATE TABLE `carousel_slides` (
  `slide_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_slides`
--

INSERT INTO `carousel_slides` (`slide_id`, `title`, `subtitle`, `image`, `button_text`, `button_link`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to BoteBank', 'Turn your recyclables into rewards and help build a greener community', 'slide_1761136366_68f8ceee40cb0.png', 'Get Started', 'register.php', 0, 1, '2025-10-19 09:28:10', '2025-10-22 12:32:46'),
(2, 'Earn Points for Recycling', 'Every bottle, every can, every recyclable item you contribute earns you valuable points that can be redeemed for exciting rewards!', 'slide_1761136326_68f8cec6b911f.jpg', 'Learn More', '#about', 1, 1, '2025-10-19 09:28:10', '2025-10-22 12:32:06'),
(3, 'Join Our Green Community', 'Be part of a movement that\'s making a real difference. Together, we can build a sustainable future for our barangay.', 'slide_1761136298_68f8ceaa4016f.jpg', 'Register Now', 'register.php', 2, 1, '2025-10-19 09:28:10', '2025-10-22 12:31:38');

-- --------------------------------------------------------

--
-- Table structure for table `collectionroutes`
--

CREATE TABLE `collectionroutes` (
  `route_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `generated_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `route_geometry` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collectionroutes`
--

INSERT INTO `collectionroutes` (`route_id`, `worker_id`, `generated_on`, `route_geometry`) VALUES
(7, 12, '2025-10-09 09:19:56', '{\"type\":\"LineString\",\"coordinates\":[[124.7811843,6.4214082],[124.7809971,6.4216359],[124.78,6.42],[124.781,6.419],[124.782727,6.4192828],[124.7811843,6.4214082]],\"street_geometry\":{\"segments\":[{\"coordinates\":[[124.781242,6.421501],[124.781107,6.421585],[124.781079,6.421535],[124.780974,6.421598]],\"distance\":37.5,\"duration\":13.4,\"is_return\":false,\"from_stop\":\"Starting Point\",\"to_stop\":\"Mabini Gym\"},{\"coordinates\":[[124.780974,6.421598],[124.781079,6.421535],[124.780541,6.420617],[124.780131,6.419924]],\"distance\":220.3,\"duration\":28.8,\"is_return\":false,\"from_stop\":\"Mabini Gym\",\"to_stop\":\"Rizal Market Collection Center\"},{\"coordinates\":[[124.780131,6.419924],[124.779997,6.419697],[124.781009,6.41908],[124.780973,6.419015]],\"distance\":168.5,\"duration\":29.8,\"is_return\":false,\"from_stop\":\"Rizal Market Collection Center\",\"to_stop\":\"Luna Elementary School\"},{\"coordinates\":[[124.780973,6.419015],[124.781009,6.41908],[124.781597,6.420003],[124.78182,6.419876],[124.782647,6.419383],[124.78275,6.419319]],\"distance\":277.6,\"duration\":33.7,\"is_return\":false,\"from_stop\":\"Luna Elementary School\",\"to_stop\":\"Jean Baldove\"},{\"coordinates\":[[124.78275,6.419319],[124.782647,6.419383],[124.78182,6.419876],[124.781597,6.420003],[124.781536,6.420038],[124.782123,6.420955],[124.781242,6.421501]],\"distance\":391.1,\"duration\":50.2,\"is_return\":true,\"from_stop\":\"Jean Baldove\",\"to_stop\":\"Starting Point\"}],\"total_duration\":155.9,\"total_distance\":1095},\"total_distance\":1.095,\"estimated_time\":\"1h 17m\",\"auto_generated\":true,\"routing_method\":\"osrm_street_routing\",\"stops_count\":5,\"pickup_count\":1,\"collection_point_count\":3}'),
(8, 12, '2025-10-15 03:26:46', '{\"type\":\"LineString\",\"coordinates\":[[124.7811843,6.4214082],[124.7809971,6.4216359],[124.78,6.42],[124.78,6.42],[124.781,6.419],[124.782727,6.4192828],[124.7811843,6.4214082]],\"street_geometry\":{\"segments\":[{\"coordinates\":[[124.781242,6.421501],[124.781107,6.421585],[124.781079,6.421535],[124.780974,6.421598]],\"distance\":37.5,\"duration\":13.4,\"is_return\":false,\"from_stop\":\"Starting Point\",\"to_stop\":\"Mabini Gym\"},{\"coordinates\":[[124.780974,6.421598],[124.781079,6.421535],[124.780541,6.420617],[124.780131,6.419924]],\"distance\":220.3,\"duration\":28.8,\"is_return\":false,\"from_stop\":\"Mabini Gym\",\"to_stop\":\"Jean Baldove\"},{\"coordinates\":[[124.780131,6.419924],[124.780131,6.419924]],\"distance\":0,\"duration\":0,\"is_return\":false,\"from_stop\":\"Jean Baldove\",\"to_stop\":\"Rizal Market Collection Center\"},{\"coordinates\":[[124.780131,6.419924],[124.779997,6.419697],[124.781009,6.41908],[124.780973,6.419015]],\"distance\":168.5,\"duration\":29.8,\"is_return\":false,\"from_stop\":\"Rizal Market Collection Center\",\"to_stop\":\"Luna Elementary School\"},{\"coordinates\":[[124.780973,6.419015],[124.781009,6.41908],[124.781597,6.420003],[124.78182,6.419876],[124.782647,6.419383],[124.78275,6.419319]],\"distance\":277.6,\"duration\":33.7,\"is_return\":false,\"from_stop\":\"Luna Elementary School\",\"to_stop\":\"Jean Baldove\"},{\"coordinates\":[[124.78275,6.419319],[124.782647,6.419383],[124.78182,6.419876],[124.781597,6.420003],[124.781536,6.420038],[124.782123,6.420955],[124.781242,6.421501]],\"distance\":391.1,\"duration\":50.2,\"is_return\":true,\"from_stop\":\"Jean Baldove\",\"to_stop\":\"Starting Point\"}],\"total_duration\":155.9,\"total_distance\":1095},\"total_distance\":1.095,\"estimated_time\":\"1h 32m\",\"auto_generated\":true,\"routing_method\":\"osrm_street_routing\",\"stops_count\":6,\"pickup_count\":2,\"collection_point_count\":3}');

-- --------------------------------------------------------

--
-- Table structure for table `collection_points`
--

CREATE TABLE `collection_points` (
  `point_id` int(11) NOT NULL,
  `name` varchar(35) NOT NULL,
  `purok_id` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive','Maintenance') DEFAULT 'Active',
  `operating_hours` varchar(30) DEFAULT '24/7',
  `contact_person` varchar(30) DEFAULT NULL,
  `contact_phone` varchar(13) DEFAULT NULL,
  `capacity_kg` decimal(10,2) DEFAULT NULL,
  `current_load_kg` decimal(10,2) DEFAULT 0.00,
  `last_collection` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collection_points`
--

INSERT INTO `collection_points` (`point_id`, `name`, `purok_id`, `latitude`, `longitude`, `description`, `status`, `operating_hours`, `contact_person`, `contact_phone`, `capacity_kg`, `current_load_kg`, `last_collection`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Mabini Gym', 1, 6.4216359, 124.7809971, 'Gym COllection point', 'Active', '24/7', NULL, NULL, NULL, 0.00, NULL, NULL, '2025-09-26 05:14:06', '2025-09-26 05:14:06'),
(2, 'Rizal Market Collection Center', 2, 6.4200000, 124.7800000, 'Main collection center at Rizal Market', 'Active', '6:00 AM - 8:00 PM', 'Maria Santos', '09123456789', 1000.00, 0.00, NULL, 'Primary collection point for Purok 2', '2025-09-26 05:14:06', '2025-09-26 05:14:06'),
(9, 'Luna Elementary School', 2, 6.4190000, 124.7810000, 'School-based collection point', 'Active', '8:00 AM - 5:00 PM', 'Ana Reyes', '09123456791', 300.00, 0.00, NULL, 'Educational collection point', '2025-09-26 05:14:15', '2025-10-22 04:37:42'),
(11, 'waw', 8, 6.4210686, 124.7796895, '', 'Active', '24/7', '', '', 111.00, 0.00, NULL, '', '2025-10-22 04:40:14', '2025-10-22 04:40:14'),
(12, 'Nagpan Elementary School', 2, 6.3521993, 125.2729512, 'Collection point outside the school gate beside the MRF', 'Active', '8:00 am - 10:00 am', 'Twinkle Hipulan', '09091172396', 150.00, 0.00, NULL, 'Please take care of your recyclables1', '2025-10-22 12:52:55', '2025-10-22 12:52:55'),
(13, 'Dalangdang Basketball Court', 16, 6.3327870, 125.2633232, 'MRF beside Dumang&amp;#039;s residence', 'Active', '1:00 pm - 2:30 pm', 'Philip Cejar', '09925361728', 200.00, 0.00, NULL, '', '2025-10-22 12:55:45', '2025-10-22 12:56:16'),
(14, 'Tolentino Waiting Shed', 1, 6.3629440, 125.2705494, 'Put your recyclables in MRF beside Eudela&#039;s residence', 'Active', '7:00 am - 8:00 am', 'Hemelry Jeirsh', '09464599435', 120.00, 0.00, NULL, '', '2025-10-22 12:59:43', '2025-10-22 12:59:43'),
(15, 'Jecupe Junk Shop', 13, 6.3396864, 125.2772056, 'Please put your recyclables inside the MRF beside Jelly&#039;s store', 'Active', '3:00 pm - 4:00 pm', 'Jelly Jecupe', '09488096661', 100.00, 0.00, NULL, '', '2025-10-22 13:01:57', '2025-10-22 13:01:57');

-- --------------------------------------------------------

--
-- Table structure for table `direct_dropoffs`
--

CREATE TABLE `direct_dropoffs` (
  `dropoff_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `point_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `weight_kg` decimal(8,2) NOT NULL CHECK (`weight_kg` > 0),
  `quantity_pcs` int(11) DEFAULT NULL CHECK (`quantity_pcs` > 0),
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `status` enum('Pending','Verified','Completed','Rejected') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL COMMENT 'Staff member who verified the drop-off',
  `verification_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pickuprequests`
--

CREATE TABLE `pickuprequests` (
  `request_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `weight_kg` decimal(8,2) NOT NULL CHECK (`weight_kg` > 0),
  `preferred_date` datetime NOT NULL,
  `status` enum('Pending','Scheduled','In Progress','Completed') DEFAULT 'Pending',
  `assigned_worker_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity_pcs` int(11) DEFAULT NULL CHECK (`quantity_pcs` > 0),
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `pickup_type` enum('Home','CollectionPoint') NOT NULL DEFAULT 'Home',
  `point_id` int(11) DEFAULT NULL,
  `actual_weight` decimal(8,2) DEFAULT NULL,
  `points_earned` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickuprequests`
--

INSERT INTO `pickuprequests` (`request_id`, `resident_id`, `type_id`, `weight_kg`, `preferred_date`, `status`, `assigned_worker_id`, `created_at`, `quantity_pcs`, `latitude`, `longitude`, `pickup_type`, `point_id`, `actual_weight`, `points_earned`, `updated_at`) VALUES
(1, 15, 1, 1.00, '2025-10-09 23:08:00', 'Completed', 12, '2025-10-09 09:08:29', NULL, 6.4192828, 124.7827270, 'Home', NULL, 1.00, 10, '2025-10-19 04:06:22'),
(2, 15, 7, 0.01, '2025-10-09 17:30:00', 'Completed', 12, '2025-10-09 09:25:55', 1, 6.4200000, 124.7800000, 'CollectionPoint', 2, NULL, 1, '2025-10-19 04:05:00'),
(3, 25, 2, 0.01, '2025-10-24 07:00:00', 'Pending', NULL, '2025-10-22 13:14:42', 36, 6.3629440, 125.2705494, 'CollectionPoint', 14, NULL, NULL, '2025-10-22 13:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `pointsledger`
--

CREATE TABLE `pointsledger` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `dropoff_id` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `transaction_type` enum('Earned','Redeemed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pointsledger`
--

INSERT INTO `pointsledger` (`transaction_id`, `user_id`, `request_id`, `dropoff_id`, `points`, `transaction_type`, `created_at`) VALUES
(1, 15, 2, NULL, 1, 'Earned', '2025-10-19 04:05:00'),
(2, 15, 1, NULL, 10, 'Earned', '2025-10-19 04:06:22');

-- --------------------------------------------------------

--
-- Table structure for table `puroks`
--

CREATE TABLE `puroks` (
  `purok_id` int(11) NOT NULL,
  `name` varchar(35) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `puroks`
--

INSERT INTO `puroks` (`purok_id`, `name`, `description`, `created_at`, `updated_at`, `latitude`, `longitude`) VALUES
(1, 'Purok 1 - Tolentino', '', '2025-08-30 01:10:38', '2025-10-06 23:55:32', 6.4212248, 124.7795016),
(2, 'Purok 2 - Malakas', '', '2025-08-30 01:10:38', '2025-10-06 23:55:49', NULL, NULL),
(4, 'Purok 4 - Pag-asa', '', '2025-08-30 01:10:38', '2025-10-06 23:56:23', NULL, NULL),
(8, 'Purok 3 - Abokado', '', '2025-10-04 04:32:11', '2025-10-06 23:57:25', NULL, NULL),
(13, 'Purok 5 - Jecupe', '', '2025-10-06 23:57:43', '2025-10-06 23:57:43', NULL, NULL),
(14, 'Purok 6 - San Roque', '', '2025-10-06 23:58:00', '2025-10-06 23:58:00', NULL, NULL),
(15, 'Purok 7 - Lao', '', '2025-10-06 23:58:35', '2025-10-06 23:58:35', NULL, NULL),
(16, 'Purok 8 - Dalangdang', '', '2025-10-06 23:58:52', '2025-10-06 23:58:52', NULL, NULL),
(17, 'Purok 9 - Rancho', '', '2025-10-06 23:59:07', '2025-10-06 23:59:07', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recyclabletypes`
--

CREATE TABLE `recyclabletypes` (
  `type_id` int(11) NOT NULL,
  `name` varchar(15) NOT NULL,
  `unit` enum('kg','pcs') NOT NULL DEFAULT 'kg',
  `points_per_kg` decimal(10,2) DEFAULT NULL,
  `points_per_unit` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recyclabletypes`
--

INSERT INTO `recyclabletypes` (`type_id`, `name`, `unit`, `points_per_kg`, `points_per_unit`, `image`, `created_at`, `updated_at`) VALUES
(1, 'Plastic Bottles', 'kg', 10.00, NULL, '../uploads/recyclable_1759819830.jpg', '2025-08-30 15:52:09', '2025-10-07 06:50:30'),
(2, 'Big Glass Bottl', 'pcs', NULL, 2.00, '../uploads/recyclable_1759820211.jpg', '2025-08-30 15:52:09', '2025-10-07 06:56:51'),
(7, 'Small Glass Bot', 'pcs', NULL, 1.00, '../uploads/recyclable_1759820121.jpg', '2025-10-07 06:45:11', '2025-10-07 06:55:21');

-- --------------------------------------------------------

--
-- Table structure for table `rewardredemptions`
--

CREATE TABLE `rewardredemptions` (
  `redemption_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `reward_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `points_required` int(11) NOT NULL CHECK (`points_required` > 0),
  `stock` int(11) NOT NULL CHECK (`stock` >= 0),
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`reward_id`, `name`, `points_required`, `stock`, `description`, `image_url`, `image`, `created_at`, `updated_at`) VALUES
(8, 'Small Grocery Pack', 635, 10, 'The \"Essential Supporter\" Grocery Package!\r\nYou’re making a huge impact on the environment with every bottle and can you recycle! Now, cash in your hard-earned Recycling Points for this practical and valuable Small Grocery Package!\r\n\r\nThis pack is all about supporting your household with key pantry and daily essentials—a great way to turn your green efforts into real, tangible savings.\r\n\r\nWhat\'s Inside Your Reward:\r\nStaple Rice: A bag of \"Great Value\" rice, the foundation of any meal.\r\n\r\nCanned Goods: Two cans of 555 Sardines in Tomato Sauce, a quick and delicious source of protein.\r\n\r\nInstant Noodles: Multiple packs of Lucky Me! Instant Noodles (like Beef Pares and other varieties) for fast and easy meals.\r\n\r\nDaily Hygiene: A bar of Bioderm soap to help keep you and your family fresh and clean.\r\n\r\nOther Essential Mix: A selection of other goods like coffee mix or seasoning packets to round out your pantry.\r\n\r\nYour commitment to recycling deserves to be rewarded. Use your points to redeem this essential grocery pack and feel good about helping the planet while helping your home!', NULL, '../uploads/rewards/reward_1761137153.png', '2025-10-07 00:44:41', '2025-10-22 12:49:24'),
(9, 'Large Grocery Pack', 1230, 10, 'The \"Ultimate Household Support\" Big Grocery Package!\r\nYou are a Recycling Champion! For those who have been consistently dedicated, this premium reward is the ultimate way to convert your Recycling Points into major household savings and essential pantry stocking.\r\n\r\nRedeem your points for the Big Grocery Package—a huge collection of staples and daily necessities for your home!\r\n\r\nWhat\'s Inside Your Big Reward:\r\nPremium Rice Staple: A large sack of \"Super Lahi\" rice, the foundation of countless family meals.\r\n\r\nProtein Power: Three (3) cans of 555 Sardines in Tomato Sauce, providing easy, essential protein.\r\n\r\nQuick Meals: Multiple packs of Lucky Me! Instant Noodles (like Beef Pares) for fast, satisfying snacks or meals.\r\n\r\nDaily Hygiene: Bars of Bioderm soap to keep the whole family clean and fresh.\r\n\r\nExtra Pantry Goodies: A generous mix of other products, including biscuits (like SkyFlakes), other canned goods (like corned beef), and coffee/seasoning packets, ensuring your pantry is well-stocked!\r\n\r\nThis comprehensive package proves that your commitment to a cleaner planet pays off in a big way. Save money, stock your kitchen, and feel great about your environmental impact!', NULL, '../uploads/rewards/reward_1761137195.png', '2025-10-07 00:45:19', '2025-10-22 12:46:35'),
(10, 'Lotion', 270, 1, 'The \"Smooth Finish\" Moisture Reward!\r\nYou\'ve done the hard work of recycling, and now it\'s time to treat yourself! Convert your Recycling Points into this luxurious everyday essential.\r\n\r\nRedeem your points for a full-size bottle of Jergens Original Beauty Cherry Almond Moisturizer!\r\n\r\nReward Details:\r\nProduct: Jergens Original Beauty Moisturizer.\r\n\r\nScent: Classic Cherry Almond Essence—a scent you know and love.\r\n\r\nBenefit: Provides long-lasting moisture to help soften and smooth your skin.\r\n\r\nSize: A generous 500 mL pump bottle for easy, daily use.\r\n\r\nYour dedication to a cleaner planet deserves a reward that leaves you feeling great. Use your points to feel the difference and keep your skin nourished and beautifully scented!', NULL, '../uploads/rewards/reward_1761137075.png', '2025-10-07 00:47:01', '2025-10-22 12:48:40'),
(11, 'Shampoo', 160, 24, 'The \"Fresh Start\" Hygiene Reward Pack!\r\nYou\'ve been diligently turning those empties into Recycling Points—now it\'s time to cash them in for an everyday essential!\r\n\r\nRedeem your points for this convenient pack of 12 pieces of Head & Shoulders Anti-Dandruff Shampoo Sachets in Cool Menthol!\r\n\r\nReward Details:\r\nQuantity: You get a full pack of 12 single-use sachets.\r\n\r\nProduct: Head & Shoulders Cool Menthol Anti-Dandruff Shampoo.\r\n\r\nBonus: Each sachet gives you 30% MORE product, offering better value for your points.\r\n\r\nKeep your hair feeling clean, fresh, and flake-free while staying true to your commitment to the planet. This practical reward is perfect for daily use, travel, or keeping on hand!', NULL, '../uploads/rewards/reward_1761136925.jpg', '2025-10-07 00:47:53', '2025-10-22 12:48:33'),
(12, 'Rice Cooker', 2500, 5, 'The \"Home Chef\" Grand Appliance Reward!\r\nYou\'ve reached a major milestone! Your exceptional hard work in recycling bottles and cans has earned you enough Recycling Points for a premium appliance reward!\r\n\r\nRedeem your points for the Baumann Rice Cooker, a must-have for any kitchen!\r\n\r\nReward Details:\r\nProduct: A sleek, modern Baumann Rice Cooker.\r\n\r\nColor: Stylish matte Black to match any kitchen decor.\r\n\r\nFunctionality: Features simple one-touch operation with both \'Cook\' and \'Warm\' settings.\r\n\r\nConvenience: Includes a clear glass lid so you can easily monitor your meal.\r\n\r\nThis appliance is a huge upgrade and the perfect way to turn your dedication to a cleaner environment into a time-saving, useful piece of equipment for your home. Enjoy perfectly cooked rice every time, thanks to your recycling efforts!', NULL, '../uploads/rewards/reward_1761137290.png', '2025-10-07 00:48:48', '2025-10-22 12:48:10'),
(13, 'Electric Keetle', 1250, 10, 'The \"Quick Brew\" Kitchen Reward!\r\nKeep up the incredible effort! Your dedication to recycling is adding up to big Recycling Points, and this reward is perfect for anyone who values speed and convenience in the kitchen.\r\n\r\nRedeem your points for the Tough Mama Electric Kettle, a fast and efficient way to heat water for coffee, tea, or instant noodles!\r\n\r\nReward Details:\r\nProduct: The reliable Tough Mama Electric Kettle.\r\n\r\nDesign: Durable stainless steel body with black accents, designed to look great on any countertop.\r\n\r\nFunctionality: Boils water faster than a stovetop, featuring an easy-pour spout and a comfortable handle.\r\n\r\nTurn your eco-friendly actions into effortless convenience at home. Whether you need a quick cup of coffee to start your day or boiling water for your favorite instant meal, this kettle is a practical, valuable reward for your hard work!', NULL, '../uploads/rewards/reward_1761137255.png', '2025-10-07 00:49:54', '2025-10-22 12:47:35'),
(14, 'School', 465, 5, 'The \"Eco-Scholar\" School Starter Pack!\r\nYou’ve been working hard to keep our community clean by recycling your bottles—now it’s time to reward your effort!\r\n\r\nTrade your earned Recycling Points for this essential School Starter Pack, perfect for getting a jump start on your studies or tackling that next big project.\r\n\r\nWhat\'s Inside Your Reward:\r\nTwo sturdy spiral-bound Notebooks (for all your notes and brilliant ideas).\r\n\r\nA Graph Paper Notebook labeled \"SCHOOL STARTER PACK\" (great for math, science, or design).\r\n\r\nA sleek Black Pencil Case to hold your gear.\r\n\r\nThree essential Black Pens/Markers (looks like two fine-liners and one Sharpie-style marker) for writing, drawing, and highlighting!\r\n\r\nYour recycling efforts are paying off—literally! Use your points to claim this pack and keep your good work going, both for the planet and in the classroom!', NULL, '../uploads/rewards/reward_1761136972.png', '2025-10-07 00:51:22', '2025-10-22 12:48:27'),
(15, 'Laundry Package', 450, 15, 'The Ultimate \"Clean Sweep\" Laundry Reward Package!\r\nYou\'ve put in the hard work to keep our planet clean by recycling, and now it\'s time to reward yourself with the ultimate in home care!\r\n\r\nTrade in your hard-earned recycling points for this complete Laundry Package and get everything you need for sparkling clean, great-smelling clothes. Think of it as a clean reward for your clean efforts!\r\n\r\nWhat\'s Inside Your Reward Package:\r\nA Stack of High-Quality Detergents: A generous supply of Surf laundry soap powder to tackle dirt, grime, and stains, ensuring your clothes come out bright and fresh. We\'ve included different variants so you\'re ready for any laundry challenge!\r\n\r\nPowerful Disinfectant: A large bottle of Zonrox bleach to keep your whites brilliant and sanitize your laundry loads, giving you true peace of mind.\r\n\r\nLuxurious Fabric Softener: Several packs of Downy fabric conditioner to leave your clothes irresistibly soft, static-free, and smelling fantastic for days.\r\n\r\nThis is more than just laundry supplies—it\'s weeks of essential home needs taken care of, thanks to your dedication to recycling. Keep collecting those bottles and keep earning great rewards!', NULL, '../uploads/rewards/reward_1761136484.png', '2025-10-07 00:54:42', '2025-10-22 13:10:53'),
(16, '5 pcs. Notebook', 180, 30, 'The \"Study Superstar\" Notebook Collection!\r\nYour hard work in recycling bottles is earning big Recycling Points! Put those points to great use and set yourself up for academic success with this essential reward.\r\n\r\nRedeem your points for a Set of Five 5-Subject Notebooks!\r\n\r\nReward Details:\r\nQuantity: A stack of Five vibrant, color-coded notebooks (Pink, Red, Yellow, Blue, and Black).\r\n\r\nCapacity: Each is a 5-Subject Notebook with approximately 150 college-ruled sheets, perfect for dividing up different classes or projects.\r\n\r\nFeatures: They feature durable covers, perforated sheets for clean tear-outs, and sturdy spiral binding.\r\n\r\nKeep your subjects organized and your ideas flowing while supporting a cleaner planet. These notebooks are a high-value, practical reward for anyone hitting the books!', NULL, '../uploads/rewards/reward_1761137027.png', '2025-10-07 00:56:08', '2025-10-22 12:43:47');

-- --------------------------------------------------------

--
-- Table structure for table `routestops`
--

CREATE TABLE `routestops` (
  `stop_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `point_id` int(11) DEFAULT NULL,
  `stop_order` int(11) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routestops`
--

INSERT INTO `routestops` (`stop_id`, `route_id`, `request_id`, `point_id`, `stop_order`, `latitude`, `longitude`) VALUES
(18, 7, NULL, 1, 1, 6.4216359, 124.7809971),
(19, 7, NULL, 2, 2, 6.4200000, 124.7800000),
(20, 7, NULL, 9, 3, 6.4190000, 124.7810000),
(21, 7, 1, NULL, 4, 6.4192828, 124.7827270),
(22, 8, NULL, 1, 1, 6.4216359, 124.7809971),
(23, 8, 2, NULL, 2, 6.4200000, 124.7800000),
(24, 8, NULL, 2, 3, 6.4200000, 124.7800000),
(25, 8, NULL, 9, 4, 6.4190000, 124.7810000),
(26, 8, 1, NULL, 5, 6.4192828, 124.7827270);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(40, 'barangay_name', 'Barangay Nagpan', '2025-08-30 15:06:39', '2025-10-07 00:42:00'),
(41, 'barangay_contact', '', '2025-08-30 15:06:39', '2025-08-30 15:06:39'),
(42, 'barangay_email', '', '2025-08-30 15:06:39', '2025-08-30 15:06:39'),
(43, 'barangay_address', '', '2025-08-30 15:06:39', '2025-08-30 15:06:39'),
(44, 'banner_title', 'Welcome to Brgy. Nagpan', '2025-08-30 15:06:39', '2025-10-07 00:42:00'),
(45, 'banner_subtitle', 'Turn your recyclables into rewards and help build a greener community', '2025-08-30 15:06:39', '2025-08-30 15:06:39'),
(46, 'banner_enabled', '1', '2025-08-30 15:06:39', '2025-08-30 15:06:39'),
(49, 'barangay_logo', 'barangay_logo_1761135330_68f8cae2a09ca.jpg', '2025-08-30 16:39:22', '2025-10-22 12:15:30'),
(50, 'system_banner', 'banner_1761135330_68f8cae2a2e67.jpg', '2025-08-30 16:39:54', '2025-10-22 12:15:30'),
(128, 'barangay_latitude', '', '2025-10-19 09:39:33', '2025-10-19 09:39:33'),
(129, 'barangay_longitude', '', '2025-10-19 09:39:33', '2025-10-19 09:39:33'),
(142, 'login_image', 'system_file_1760866837_68f4b215e6247.png', '2025-10-19 09:40:37', '2025-10-19 09:40:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(15) NOT NULL,
  `last_name` varchar(15) NOT NULL,
  `email` varchar(30) NOT NULL,
  `phone` varchar(13) NOT NULL,
  `address` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role` enum('admin','worker','resident') NOT NULL DEFAULT 'resident',
  `purok_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `password`, `profile_photo`, `role`, `purok_id`, `created_at`, `latitude`, `longitude`) VALUES
(1, 'Michael', 'Torillo', 'admin@gmail.com', '+639513491961', 'Brgy. Nagpan', '$2y$10$2QkTCndjSRYkRCkbQbi5Gu10GgENvgk93nEMPfUr1zL0emXYaHkZ.', 'profile_1757256415_68bd9adf78518.jpg', 'admin', NULL, '2025-08-30 13:10:03', 6.4214082, 124.7811843),
(12, 'Jolo', 'Giducos', 'jolo@gmail.com', '+639513491961', 'tupi, so', '$2y$10$2QkTCndjSRYkRCkbQbi5Gu10GgENvgk93nEMPfUr1zL0emXYaHkZ.', NULL, 'worker', 2, '2025-09-26 03:15:48', NULL, NULL),
(13, 'Tresha', 'Toledo', 'tresha@gmail.com', '+639064956496', 'French Street', '$2y$10$2QkTCndjSRYkRCkbQbi5Gu10GgENvgk93nEMPfUr1zL0emXYaHkZ.', NULL, 'worker', 4, '2025-09-26 04:35:17', NULL, NULL),
(14, 'Hanz', 'Eudela', 'eudelahanzkevin@gmail.com', '+639949103017', 'Zone 1, Purok Tolentino', '$2y$10$2QkTCndjSRYkRCkbQbi5Gu10GgENvgk93nEMPfUr1zL0emXYaHkZ.', NULL, 'resident', 8, '2025-10-04 04:35:04', 6.3602847, 125.2720622),
(15, 'Jean', 'Baldove', 'jean@gmail.com', '+639513491961', 'Yangco Barangay Hall, General P. Santos Street, Halangdon, Yangco, Banga, South Cotabato, Soccsksargen, 9511, Philippines', '$2y$10$2QkTCndjSRYkRCkbQbi5Gu10GgENvgk93nEMPfUr1zL0emXYaHkZ.', NULL, 'resident', 2, '2025-10-07 00:03:39', 6.4192828, 124.7827270),
(25, 'Schleiden', 'Ray', 'ray@gmail.com', '+639952628472', 'Eudela\'s Residence, Zone 2, Tolentino, Nagpan, Malungon, Sarangani', '$2y$10$UueKAeDU74MZj/bGhH0xzeAPYAaN52GY8nWWJYTrsSjfJMvJwbOr6', NULL, 'resident', 1, '2025-10-22 13:12:43', 6.3608301, 125.2715470);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  ADD PRIMARY KEY (`slide_id`);

--
-- Indexes for table `collectionroutes`
--
ALTER TABLE `collectionroutes`
  ADD PRIMARY KEY (`route_id`),
  ADD KEY `worker_id` (`worker_id`);

--
-- Indexes for table `collection_points`
--
ALTER TABLE `collection_points`
  ADD PRIMARY KEY (`point_id`),
  ADD KEY `purok_id` (`purok_id`);

--
-- Indexes for table `direct_dropoffs`
--
ALTER TABLE `direct_dropoffs`
  ADD PRIMARY KEY (`dropoff_id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `point_id` (`point_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `pickuprequests`
--
ALTER TABLE `pickuprequests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `assigned_worker_id` (`assigned_worker_id`),
  ADD KEY `pickuprequests_point_fk` (`point_id`);

--
-- Indexes for table `pointsledger`
--
ALTER TABLE `pointsledger`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `pointsledger_ibfk_3` (`dropoff_id`);

--
-- Indexes for table `puroks`
--
ALTER TABLE `puroks`
  ADD PRIMARY KEY (`purok_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `recyclabletypes`
--
ALTER TABLE `recyclabletypes`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `rewardredemptions`
--
ALTER TABLE `rewardredemptions`
  ADD PRIMARY KEY (`redemption_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`reward_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `routestops`
--
ALTER TABLE `routestops`
  ADD PRIMARY KEY (`stop_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `idx_routestops_point_id` (`point_id`),
  ADD KEY `idx_routestops_route_order` (`route_id`,`stop_order`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `purok_id` (`purok_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  MODIFY `slide_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `collectionroutes`
--
ALTER TABLE `collectionroutes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `collection_points`
--
ALTER TABLE `collection_points`
  MODIFY `point_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `direct_dropoffs`
--
ALTER TABLE `direct_dropoffs`
  MODIFY `dropoff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pickuprequests`
--
ALTER TABLE `pickuprequests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pointsledger`
--
ALTER TABLE `pointsledger`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `puroks`
--
ALTER TABLE `puroks`
  MODIFY `purok_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `recyclabletypes`
--
ALTER TABLE `recyclabletypes`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rewardredemptions`
--
ALTER TABLE `rewardredemptions`
  MODIFY `redemption_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `reward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `routestops`
--
ALTER TABLE `routestops`
  MODIFY `stop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `collectionroutes`
--
ALTER TABLE `collectionroutes`
  ADD CONSTRAINT `collectionroutes_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `collection_points`
--
ALTER TABLE `collection_points`
  ADD CONSTRAINT `collection_points_ibfk_1` FOREIGN KEY (`purok_id`) REFERENCES `puroks` (`purok_id`) ON DELETE CASCADE;

--
-- Constraints for table `direct_dropoffs`
--
ALTER TABLE `direct_dropoffs`
  ADD CONSTRAINT `direct_dropoffs_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `direct_dropoffs_ibfk_2` FOREIGN KEY (`point_id`) REFERENCES `collection_points` (`point_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `direct_dropoffs_ibfk_3` FOREIGN KEY (`type_id`) REFERENCES `recyclabletypes` (`type_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `direct_dropoffs_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `pickuprequests`
--
ALTER TABLE `pickuprequests`
  ADD CONSTRAINT `pickuprequests_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pickuprequests_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `recyclabletypes` (`type_id`),
  ADD CONSTRAINT `pickuprequests_ibfk_3` FOREIGN KEY (`assigned_worker_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `pickuprequests_point_fk` FOREIGN KEY (`point_id`) REFERENCES `collection_points` (`point_id`);

--
-- Constraints for table `pointsledger`
--
ALTER TABLE `pointsledger`
  ADD CONSTRAINT `pointsledger_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `pointsledger_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `pickuprequests` (`request_id`),
  ADD CONSTRAINT `pointsledger_ibfk_3` FOREIGN KEY (`dropoff_id`) REFERENCES `direct_dropoffs` (`dropoff_id`) ON DELETE CASCADE;

--
-- Constraints for table `rewardredemptions`
--
ALTER TABLE `rewardredemptions`
  ADD CONSTRAINT `rewardredemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `rewardredemptions_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`reward_id`);

--
-- Constraints for table `routestops`
--
ALTER TABLE `routestops`
  ADD CONSTRAINT `routestops_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `collectionroutes` (`route_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routestops_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `pickuprequests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routestops_point_fk` FOREIGN KEY (`point_id`) REFERENCES `collection_points` (`point_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`purok_id`) REFERENCES `puroks` (`purok_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
