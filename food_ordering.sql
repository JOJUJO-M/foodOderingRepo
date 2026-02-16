-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 13, 2026 at 01:26 PM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.4.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `food_ordering`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rider_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `total_price` decimal(10,2) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `rider_id`, `status`, `total_price`, `delivery_address`, `created_at`) VALUES
(1, 4, 6, 'delivered', '62000.00', 'Bahari beach 0687786648', '2026-02-05 08:36:18'),
(2, 5, 3, 'delivered', '2000.00', 'Mbezi One 071425648', '2026-02-05 08:50:05'),
(3, 4, 3, 'delivered', '30000.00', 'Bahari beach 0687786648', '2026-02-05 09:02:53'),
(4, 4, 18, 'picked_up', '2000.00', 'Test Address', '2026-02-11 16:43:25'),
(5, 19, NULL, 'pending', '2000.00', 'kimara', '2026-02-11 16:51:17'),
(6, 19, NULL, 'rejected', '2000.00', 'Kimara, tembon', '2026-02-11 16:54:40');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 2, 2, '30000.00'),
(2, 1, 1, 1, '2000.00'),
(3, 2, 1, 1, '2000.00'),
(4, 3, 2, 1, '30000.00'),
(5, 4, 1, 1, '2000.00'),
(6, 5, 1, 1, '2000.00'),
(7, 6, 1, 1, '2000.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `category_id`) VALUES
(1, 'Wali Maharage V.I.P', 'Wali maharage na Nazi', '2000.00', 'assets/uploads/698455b3e5abe_wali maharage.png', NULL),
(2, 'Chips Samaki', 'Spice chips with Fried Fish', '30000.00', 'assets/uploads/698455ef15f55_WhatsApp Image 2026-01-16 at 19.36.31.jpeg', NULL),
(3, 'NdiziKuku', 'maharage na nyanya chungu ni tele', '7000.00', 'assets/uploads/698c484e79917_RL_thumbnail.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin','rider') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@food.com', '$2y$10$MoNlv6uSOctvB0DPvOYz/.hpbNDWoMcFWJyUAY5fPe1gnoT5w457G', 'admin', '2026-02-05 08:02:42'),
(2, 'IQRA daudi', 'iqra@gmail.com', '123456', 'customer', '2026-02-05 08:26:23'),
(3, 'Faridi Mkilwa', 'rider@gmail.com', '$2y$10$ey2dzmDVb0Y8FRCuO/aYTe.lXM/PTQbIHGpzJZLPWmoKDKGQ3uqAe', 'rider', '2026-02-05 08:34:20'),
(4, 'Nasha Mkilwa', 'customer@gmail.com', '123456', 'customer', '2026-02-05 08:34:44'),
(5, 'Iqra Daudi', 'customer1@gmail.com', '123456', 'customer', '2026-02-05 08:35:02'),
(6, 'Twaha Mkilwa', 'rider1@gmail.com', '123456', 'rider', '2026-02-05 08:35:43'),
(7, 'Daudi mkilwa', 'daudimkilwa@gmail.com', '$2y$10$MwU0aKwwvbjby2Hwx2eCRee9gZ3lZ02fNkfDyK.qN8k.J3aNrG0Jy', 'admin', '2026-02-05 09:14:25'),
(8, 'Juma Kapuya', 'juma@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(9, 'Neema Mollel', 'neema@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(10, 'Daudi Kilwa', 'daudi@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(11, 'Fatma Hassan', 'fatma@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(12, 'Bakari Mwinyi', 'bakari@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(13, 'Zawadi Khalfan', 'zawadi@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(14, 'Amon Mmasa', 'amon@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(15, 'Rehema Simon', 'rehema@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(16, 'Said Khatibu', 'said@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(17, 'Zuchu Ibrahim', 'zuchu@example.tz', '$2y$10$M8bKcjT15A949FeWPLjjVe8lVhq/G04QJ4ikfzqQfRYsqM/XcyDmy', 'customer', '2026-02-11 09:17:49'),
(18, 'Joji', 'jojujom@mail.com', '$2y$10$.XsvMRHqN0MBxx.7V9bueOxjbLALSqw3lgkhYMSW12WNiLCuxH3um', 'rider', '2026-02-11 16:29:15'),
(19, 'Raphael', 'rltesha@mail.com', '$2y$10$UBoqzxk0CkVxxZqVAtvzEOwAZ2HziNPSq7hYRgKjfeDzX4mkChDq6', 'customer', '2026-02-11 16:31:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `rider_id` (`rider_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`rider_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
