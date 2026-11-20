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
-- Database: `doan2`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `type` enum('thongbao','khuyenmai') NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT NULL,
  `kind` varchar(30) DEFAULT NULL,
  `applies` varchar(255) DEFAULT NULL,
  `image` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `type`, `title`, `content`, `created_at`, `category`, `kind`, `applies`, `image`) VALUES
(1, 'thongbao', 'Giờ mở cửa', 'Cửa hàng mở cửa 8:00 – 21:00 mỗi ngày, kể cả Chủ nhật.', '2026-10-14 18:51:45', 'Cửa hàng', '', NULL, NULL),
(2, 'thongbao', 'Cập nhật bảng giá', 'Bảng giá được cập nhật vào thứ Hai hằng tuần. Tải bảng giá mới nhất ở mục Bảng giá.', '2026-10-14 18:51:45', 'Bảng giá', '', NULL, NULL),
(3, 'thongbao', 'Bảo hành', 'Laptop và PC chính hãng bảo hành tối thiểu 12 tháng, 1 đổi 1 trong 30 ngày đầu.', '2026-10-14 18:51:45', 'Bảo hành', '', NULL, NULL),
(4, 'khuyenmai', 'Mùa tựu trường: giảm đến 2.000.000đ', 'Áp dụng cho laptop Dell, HP và ASUS khi mua kèm balo.', '2026-10-14 18:51:45', 'Laptop', 'Giảm giá', NULL, NULL),
(5, 'khuyenmai', 'Tặng chuột + bàn di cho đơn laptop gaming', 'Số lượng có hạn, áp dụng đến hết tháng.', '2026-10-14 18:51:45', 'Laptop', 'Quà tặng', NULL, NULL),
(6, 'khuyenmai', 'Trả góp 0% qua thẻ tín dụng', 'Kỳ hạn 6 – 12 tháng, không phí chuyển đổi.', '2026-10-14 18:51:45', 'Ưu đãi chung', 'Trả góp', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`) VALUES
(1, 'Dell'),
(2, 'ASUS'),
(3, 'HP'),
(4, 'Lenovo'),
(5, 'Acer'),
(6, 'Logitech'),
(7, 'Apple'),
(8, 'MSI'),
(9, 'Samsung'),
(10, 'LG'),
(11, 'Gigabyte'),
(12, 'Kingston');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Laptop'),
(2, 'Máy tính để bàn'),
(3, 'Màn hình'),
(4, 'Linh kiện'),
(5, 'Phụ kiện');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `password_hash`, `reset_hash`, `reset_exp`, `created_at`) VALUES
(1, 'user 1', 'user1@gmail.com', NULL, '$2y$10$ob5ETMNdLmDxK6/Mexa7q.aC28eOLNerJDM5NA4e8w95WMqg4nrYK', NULL, NULL, '2026-10-17 10:58:08'),
(2, 'user 2', 'user2@gmail.com', NULL, '$2y$10$hN4GJLZi7MlRzdO4otEQvOP8zicJhI1og9chHJyfB4nlg0x1lM57y', NULL, NULL, '2026-10-17 11:14:47');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `customer` varchar(120) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('moi','daxuly','cho_tt','huy') NOT NULL DEFAULT 'moi',
  `created_at` datetime DEFAULT current_timestamp(),
  `customer_id` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `receive` varchar(20) DEFAULT NULL,
  `ship` varchar(40) DEFAULT NULL,
  `pay` varchar(20) DEFAULT NULL,
  `extras` varchar(255) DEFAULT NULL,
  `invoice` tinyint(1) NOT NULL DEFAULT 0,
  `stock_deducted` tinyint(1) NOT NULL DEFAULT 0,
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `product_id`, `qty`, `customer`, `phone`, `email`, `address`, `note`, `status`, `created_at`, `customer_id`, `total`, `receive`, `ship`, `pay`, `extras`, `invoice`, `stock_deducted`, `paid_at`) VALUES
(1, 10, 1, 'a', '0123456789', 'a@gmail.com', 'a', 'a', 'daxuly', '2026-10-17 11:44:02', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL),
(2, 24, 1, 'user 1', '0123456789', 'user1@gmail.com', 'nhà ma', 'm tuổi', 'daxuly', '2026-10-17 17:58:49', 1, 1990000, 'home', 'standard', 'bank', 'Hỗ trợ kỹ thuật', 0, 0, NULL),
(3, 23, 1, 'user10', '0123456891', 'user10@gmail.com', 'Nhận tại cửa hàng ZeroNightComputer', '', 'cho_tt', '2026-11-11 22:47:57', 5, 11780000, 'store', NULL, 'bank', 'Hỗ trợ lắp đặt/cài đặt; Nhờ người khác nhận hàng; Hỗ trợ kỹ thuật', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `old_price` int(11) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `qty`, `price`, `old_price`, `name`) VALUES
(1, 2, 24, 1, 1990000, NULL, NULL),
(2, 3, 23, 1, 5290000, 5790000, 'Mainboard Gigabyte B650 AORUS Elite AX'),
(3, 3, 22, 1, 6490000, NULL, 'LG UltraGear 27GS75Q 27\" 180Hz');

-- --------------------------------------------------------

--
-- Table structure for table `pricelists`
--

CREATE TABLE `pricelists` (
  `id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(100) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pricelists`
--

INSERT INTO `pricelists` (`id`, `original_name`, `stored_name`, `uploaded_by`, `uploaded_at`) VALUES
(1, 'bang-gia-doan2.csv', 'afe96d820f77ae76.csv', 1, '2026-10-17 18:06:08'),
(2, 'bang-gia-doan2.csv', '96769c7004abf4a0.csv', 1, '2026-10-17 18:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `old_price` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_new` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `stock` int(11) NOT NULL DEFAULT 20
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `brand_id`, `price`, `old_price`, `description`, `is_new`, `created_at`, `stock`) VALUES
(1, 'Dell Inspiron 15 3530', 1, 1, 13990000, 15490000, 'Core i5-1335U, RAM 16GB, SSD 512GB, màn hình 15.6 inch FHD 120Hz.', 1, '2026-10-14 18:51:45', 20),
(2, 'ASUS Vivobook 14 OLED', 1, 2, 16490000, NULL, 'Core i5-13500H, RAM 16GB, SSD 512GB, màn hình OLED 14 inch 2.8K.', 1, '2026-10-14 18:51:45', 20),
(3, 'HP Pavilion 15', 1, 3, 18900000, 20900000, 'Core i7-1355U, RAM 16GB, SSD 512GB, pin 8 giờ, vỏ kim loại.', 0, '2026-10-14 18:51:45', 20),
(4, 'Lenovo LOQ 15 Gaming', 1, 4, 24990000, NULL, 'Ryzen 7 7840HS, RTX 4050 6GB, RAM 16GB, SSD 512GB, 144Hz.', 1, '2026-10-14 18:51:45', 20),
(5, 'Acer Nitro V 15', 1, 5, 22490000, 24990000, 'Core i5-13420H, RTX 4050, RAM 16GB, SSD 512GB.', 0, '2026-10-14 18:51:45', 20),
(6, 'Dell OptiPlex 7010 SFF', 2, 1, 9800000, NULL, 'Core i5-13500, RAM 8GB, SSD 256GB, nhỏ gọn cho văn phòng.', 0, '2026-10-14 18:51:45', 20),
(7, 'HP ProDesk 400 G9', 2, 3, 11900000, 12900000, 'Core i5-12500, RAM 16GB, SSD 512GB, Windows 11 bản quyền.', 0, '2026-10-14 18:51:45', 20),
(8, 'Lenovo ThinkCentre M70s', 2, 4, 10500000, NULL, 'Core i3-13100, RAM 8GB, SSD 256GB, bảo hành 36 tháng.', 1, '2026-10-14 18:51:45', 20),
(9, 'Dell UltraSharp U2723QE 27\"', 3, 1, 14500000, 16500000, '4K IPS Black, USB-C 90W, phủ 98% DCI-P3.', 0, '2026-10-14 18:51:45', 20),
(10, 'ASUS TUF VG249Q3A 24\"', 3, 2, 4390000, 4990000, 'FHD IPS 180Hz, 1ms, FreeSync Premium.', 1, '2026-10-14 18:51:45', 20),
(11, 'Acer Nitro XV272U 27\"', 3, 5, 6290000, NULL, '2K IPS 170Hz, HDR400, loa tích hợp.', 0, '2026-10-14 18:51:45', 20),
(12, 'SSD ASUS ROG Strix 1TB NVMe', 4, 2, 2490000, 2890000, 'PCIe 4.0 x4, đọc 7.000MB/s, ghi 6.500MB/s.', 0, '2026-10-14 18:51:45', 20),
(13, 'RAM Acer Predator 16GB DDR5', 4, 5, 1390000, NULL, 'Bus 6000MHz, tản nhiệt nhôm, kit 1x16GB.', 1, '2026-10-14 18:51:45', 20),
(14, 'Chuột Logitech MX Master 3S', 5, 6, 2290000, 2590000, '8000 DPI, yên lặng, cuộn MagSpeed, kết nối 3 thiết bị.', 0, '2026-10-14 18:51:45', 20),
(15, 'Bàn phím Logitech MX Keys S', 5, 6, 2390000, NULL, 'Phím cắt cầu, đèn nền thông minh, sạc USB-C.', 1, '2026-10-14 18:51:45', 20),
(16, 'Tai nghe Logitech G435', 5, 6, 1190000, 1390000, 'Không dây Lightspeed, nhẹ 165g, micro tích hợp.', 0, '2026-10-14 18:51:45', 20),
(17, 'MacBook Air 15 M5 2026 10CPU/10GPU/32GB/4TB', 1, 7, 92990000, 102490000, 'Chip M5, RAM 32GB, SSD 4TB, màn hình Liquid Retina 15.3 inch, sạc USB-C 70W.', 1, '2026-10-14 18:51:46', 20),
(18, 'MacBook Air 13 M4 16GB/256GB', 1, 7, 24990000, 26990000, 'Chip M4, RAM 16GB, SSD 256GB, pin đến 18 giờ.', 0, '2026-10-14 18:51:46', 20),
(19, 'MSI Prestige 14 AI+ D3MG', 1, 8, 44490000, 47990000, 'Core Ultra 7, RAM 32GB, SSD 1TB, màn hình OLED 14 inch, vỏ kim loại.', 1, '2026-10-14 18:51:46', 20),
(20, 'MSI Thin 15 B13VE', 1, 8, 19990000, NULL, 'Core i5-13420H, RTX 4050, RAM 16GB, SSD 512GB, 144Hz.', 0, '2026-10-14 18:51:46', 20),
(21, 'Samsung Odyssey G5 27\" 2K 165Hz', 3, 9, 6990000, 7990000, 'Tấm nền VA cong 1000R, 2K, 165Hz, 1ms, HDR10.', 1, '2026-10-14 18:51:46', 20),
(22, 'LG UltraGear 27GS75Q 27\" 180Hz', 3, 10, 6490000, NULL, 'IPS 2K 180Hz, 1ms, HDR10, G-Sync tương thích.', 0, '2026-10-14 18:51:46', 20),
(23, 'Mainboard Gigabyte B650 AORUS Elite AX', 4, 11, 5290000, 5790000, 'Socket AM5, DDR5, PCIe 5.0, WiFi 6E.', 0, '2026-10-14 18:51:46', 20),
(24, 'SSD Kingston NV3 1TB NVMe', 4, 12, 1990000, NULL, 'PCIe 4.0 x4, đọc 6.000MB/s, ghi 5.000MB/s.', 1, '2026-10-14 18:51:46', 20);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `k` varchar(50) NOT NULL,
  `v` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `pricelists`
--
ALTER TABLE `pricelists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`k`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pricelists`
--
ALTER TABLE `pricelists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
