-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 12, 2026 at 12:13 PM
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
-- Database: `od_ordering_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available` tinyint(1) DEFAULT 1,
  `stock` int(11) DEFAULT 10,
  `category` varchar(50) DEFAULT 'Food',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `name`, `price`, `available`, `stock`, `category`, `image`) VALUES
(1, 'Burger', 5.99, 1, 47, 'Food', NULL),
(2, 'Pizza', 8.99, 1, 13, 'Food', NULL),
(3, 'Pasta', 7.49, 1, 5, 'Food', NULL),
(5, 'cheeseburger', 23.00, 1, 195, 'Food', NULL),
(6, 'milktea', 20.00, 1, 19, 'Beverage', NULL),
(7, 'iced latte', 25.00, 1, 9, 'Beverage', 'item_1789207433_6aa52389150e4.jpg'),
(8, 'Fries', 49.97, 1, 19, 'Food', 'item_1789207789_6aa524ed42b2d.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Processing',
  `group_order_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `item_id`, `item_name`, `price`, `quantity`, `order_date`, `status`, `group_order_id`) VALUES
(1, 1, 2, 'Pizza', 8.99, 1, '2026-09-12 08:16:54', 'Done', 'OLD-1'),
(2, 1, 3, 'Pasta', 7.49, 1, '2026-09-12 08:16:54', 'Done', 'OLD-2'),
(3, 1, 5, 'cheeseburger', 23.00, 1, '2026-09-12 08:16:54', 'Done', 'OLD-3'),
(4, 1, 2, 'Pizza', 8.99, 1, '2026-09-12 08:17:01', 'Done', 'OLD-4'),
(5, 1, 3, 'Pasta', 7.49, 1, '2026-09-12 08:17:01', 'Done', 'OLD-5'),
(6, 1, 1, 'Burger', 5.99, 1, '2026-09-12 08:27:23', 'Done', 'ORD-20260912102723-1'),
(7, 1, 2, 'Pizza', 8.99, 1, '2026-09-12 08:27:23', 'Done', 'ORD-20260912102723-1'),
(8, 1, 3, 'Pasta', 7.49, 1, '2026-09-12 08:27:23', 'Done', 'ORD-20260912102723-1'),
(9, 1, 5, 'cheeseburger', 23.00, 1, '2026-09-12 08:27:23', 'Done', 'ORD-20260912102723-1'),
(10, 1, 6, 'milktea', 20.00, 1, '2026-09-12 08:27:23', 'Done', 'ORD-20260912102723-1'),
(11, 1, 2, 'Pizza', 8.99, 1, '2026-09-12 08:35:55', 'Done', 'ORD-20260912103555-1'),
(12, 1, 5, 'cheeseburger', 23.00, 1, '2026-09-12 08:35:55', 'Done', 'ORD-20260912103555-1'),
(13, 4, 1, 'Burger', 5.99, 1, '2026-09-12 08:48:38', 'Done', 'ORD-20260912104838-4'),
(14, 4, 6, 'milktea', 20.00, 1, '2026-09-12 08:48:38', 'Done', 'ORD-20260912104838-4'),
(15, 4, 5, 'cheeseburger', 23.00, 1, '2026-09-12 08:48:38', 'Done', 'ORD-20260912104838-4'),
(16, 4, 2, 'Pizza', 8.99, 1, '2026-09-12 08:48:38', 'Done', 'ORD-20260912104838-4'),
(17, 1, 8, 'Fries', 49.97, 1, '2026-09-12 10:11:23', 'Done', 'ORD-20260912121123-1'),
(18, 1, 7, 'iced latte', 25.00, 1, '2026-09-12 10:11:23', 'Done', 'ORD-20260912121123-1');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'michael', 'parole', 'customer'),
(2, 'michael', 'parole', 'customer'),
(3, 'admin', 'admin123', 'admin'),
(4, 'myrill', 'michael', 'customer'),
(5, 'anj', '$2y$10$TeqcxgeDg.lSZ0/4YgYWJujgSdtAnsxiZeKqBVVyUlJ1n2dyFA1RO', 'customer'),
(6, 'Anjparole', '$2y$10$vX1oGCKw5iSbBxfGyrsXL.OBAQVzZ7GHsBL/7ViPc9QYdvLzv6Gn2', 'customer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
