-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 04:39 PM
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
-- Database: `doan2_accounts`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `reset_hash` varchar(255) DEFAULT NULL,
  `reset_exp` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `google_sub` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `password_hash`, `reset_hash`, `reset_exp`, `created_at`, `google_sub`) VALUES
(1, 'user 1', 'user1@gmail.com', NULL, '$2y$10$ob5ETMNdLmDxK6/Mexa7q.aC28eOLNerJDM5NA4e8w95WMqg4nrYK', NULL, NULL, '2026-10-17 10:58:08', NULL),
(2, 'user 2', 'user2@gmail.com', NULL, '$2y$10$hN4GJLZi7MlRzdO4otEQvOP8zicJhI1og9chHJyfB4nlg0x1lM57y', NULL, NULL, '2026-10-17 11:14:47', NULL),
(3, 'Bất Vãn', 'lobatvan@gmail.com', NULL, '$2y$10$vgXdlGKn1KVAqrdCqtpg8e.xkbGDas9AgI210E0VpqS3bf.gkslBK', NULL, NULL, '2026-11-11 15:44:04', '103497699195109549494'),
(4, 'Seo Nari', 'kaslana2712@gmail.com', NULL, '$2y$10$yDMtX9GgoU3bddClR3JJ2eawRGYbgHde7TXZDFwyoFvmp0CPjb0Pa', NULL, NULL, '2026-11-11 15:44:35', '114139153103903868949'),
(5, 'user10', 'user10@gmail.com', NULL, '$2y$10$yG35rmD4Yn2ppo1T8Uj5RemOkJt.PnJU4MRjY8p.HN7AQ8EcTorfu', NULL, NULL, '2026-11-11 15:46:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `reset_hash` varchar(255) DEFAULT NULL,
  `reset_exp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `reset_hash`, `reset_exp`) VALUES
(1, 'admin', '$2y$10$5y3uJEqoU3RJof.Z3Zvw8O7nV/hBp.S6XzohxE0N.whUHjbV9QIpm', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `google_sub` (`google_sub`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
