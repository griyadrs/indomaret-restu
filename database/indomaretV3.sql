-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 30, 2025 at 11:09 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `indomaret`
--

-- --------------------------------------------------------

--
-- Table structure for table `cashier_shifts`
--

CREATE TABLE `cashier_shifts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` char(3) NOT NULL,
  `shift_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `shift_end` timestamp NULL DEFAULT NULL,
  `starting_cash` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ending_cash` decimal(10,2) DEFAULT NULL,
  `system_sales_cash` decimal(10,2) DEFAULT NULL,
  `difference` decimal(10,2) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cashier_shifts`
--

INSERT INTO `cashier_shifts` (`id`, `user_id`, `shift_start`, `shift_end`, `starting_cash`, `ending_cash`, `system_sales_cash`, `difference`, `status`) VALUES
(1, 'c02', '2025-10-30 01:00:00', '2025-10-30 09:01:00', 300000.00, 2300000.00, 2000000.00, 0.00, 'closed'),
(2, 'c03', '2025-11-02 07:00:00', NULL, 300000.00, NULL, NULL, NULL, 'open'),
(3, 'c02', '2025-10-31 01:00:00', '2025-10-31 09:00:00', 300000.00, 1795000.00, 1500000.00, -5000.00, 'closed'),
(4, 'K01', '2025-11-30 03:07:13', NULL, 10000.00, NULL, NULL, NULL, 'open');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `points` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone_number`, `points`, `created_at`) VALUES
(1, 'Budi Santoso', '081234567890', 150, '2025-11-02 06:55:50'),
(2, 'Dewi Lestari', '085712345678', 0, '2025-11-02 06:55:50'),
(3, 'Agus Wijaya', '081999888777', 45, '2025-11-02 06:55:50'),
(4, 'Siti Aminah', '087811223344', 320, '2025-11-02 06:55:50'),
(5, 'Siti Member', '08123456789', 50, '2025-11-30 02:44:55');

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales_summary`
--

CREATE TABLE `daily_sales_summary` (
  `summary_date` date NOT NULL,
  `total_sales` bigint UNSIGNED NOT NULL,
  `transaction_count` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` smallint UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `price` mediumint DEFAULT NULL,
  `cost_price` mediumint UNSIGNED DEFAULT '0',
  `stock` smallint DEFAULT '0'
) ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `cost_price`, `stock`) VALUES
(1, 'abc orange 525ml', 13500, 0, 998),
(2, 'i/f bisc.wndrlnd 300', 20900, 0, 66),
(3, 'lexus sandw cokl 190', 26800, 0, 80),
(4, 'luwak wht orgl 20x20', 25400, 0, 921),
(5, 'kopiko 78c 240ml', 19800, 0, 121),
(6, 'susu indmlk 1lt', 24900, 0, 49),
(7, 'indomie ach', 3000, 0, 1198),
(8, 'Sari Roti Tawar', 16000, 14500, 50),
(9, 'Aqua 600ml', 3500, 3000, 238),
(10, 'Chitato Sapi Panggang 68g', 11500, 10200, 149),
(11, 'Pocari Sweat 500ml', 9000, 7800, 120),
(12, 'Silverqueen 62g', 14000, 12500, 74),
(13, 'Kopi Susu', 15000, 0, 97),
(14, 'Roti Bakar', 12000, 0, 50),
(15, 'Es Teh Manis', 5000, 0, 194),
(16, 'Mie Goreng', 10000, 0, 72),
(17, 'fiesta', 31000, 0, 99);

-- --------------------------------------------------------

--
-- Table structure for table `product_voucher`
--

CREATE TABLE `product_voucher` (
  `product_id` smallint UNSIGNED NOT NULL,
  `voucher_id` char(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_voucher`
--

INSERT INTO `product_voucher` (`product_id`, `voucher_id`) VALUES
(1, 'vo-001'),
(2, 'vo-002');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint UNSIGNED NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
  `purchase_date` date NOT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `supplier_id`, `purchase_date`, `notes`) VALUES
(1, 4, '2025-10-28', 'Pemesanan stok Indomie dan Chitato'),
(2, 3, '2025-10-30', 'Restock minuman ringan');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_details`
--

CREATE TABLE `purchase_order_details` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_order_id` bigint UNSIGNED NOT NULL,
  `product_id` smallint UNSIGNED NOT NULL,
  `quantity` smallint UNSIGNED NOT NULL,
  `cost_price_at_purchase` mediumint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_order_details`
--

INSERT INTO `purchase_order_details` (`id`, `purchase_order_id`, `product_id`, `quantity`, `cost_price_at_purchase`) VALUES
(1, 1, 7, 200, 2800),
(2, 1, 10, 50, 10200),
(3, 2, 9, 100, 3000),
(4, 2, 11, 70, 7800);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(50) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `address`, `created_at`) VALUES
(1, 'PT. Sinar Jaya Abadi', 'Bapak Hermawan', '021-4567890', 'Jl. Raya Cipinang No. 45, Jakarta Timur', '2025-11-02 06:55:41'),
(2, 'CV. Tiga Roti', 'Ibu Susan', '0361-789012', 'Jl. Sunset Road No. 12, Kuta, Bali', '2025-11-02 06:55:41'),
(3, 'UD. Minuman Segar', 'Agus', '08123456789', 'Jl. Gatot Subroto No. 100, Denpasar', '2025-11-02 06:55:41'),
(4, 'PT. Indofood Sukses Makmur Tbk', 'Distribusi Center', '021-57958822', 'Sudirman Plaza, Jl. Jend. Sudirman, Jakarta', '2025-11-02 06:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `cashier_id` char(3) NOT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `discount_amount` int UNSIGNED NOT NULL DEFAULT '0',
  `discount_points_used` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `cashier_id`, `customer_id`, `discount_amount`, `discount_points_used`, `created_at`) VALUES
(4, 'c02', 1, 10000, 100, '2025-10-30 03:15:00'),
(5, 'c02', 1, 10000, 100, '2025-10-30 03:15:00'),
(6, 'c02', 1, 10000, 100, '2025-10-30 03:15:00'),
(7, 'c03', NULL, 0, 0, '2025-11-02 07:30:00'),
(8, 'c03', 4, 0, 0, '2025-11-02 07:45:00'),
(9, 'K01', NULL, 0, 0, '2025-11-30 03:12:41'),
(10, 'K01', NULL, 0, 0, '2025-11-30 04:04:25'),
(11, 'K01', NULL, 0, 0, '2025-11-30 04:09:17'),
(12, 'K01', NULL, 0, 0, '2025-11-30 04:09:35'),
(13, 'K01', NULL, 0, 0, '2025-11-30 04:55:02'),
(14, 'K01', NULL, 1500, 0, '2025-11-30 05:02:12'),
(15, 'K01', NULL, 1500, 0, '2025-11-30 05:07:24'),
(16, 'K01', NULL, 0, 0, '2025-11-30 10:10:12'),
(17, 'K01', NULL, 0, 0, '2025-11-30 10:10:20'),
(18, 'K01', NULL, 0, 0, '2025-11-30 10:13:41'),
(19, 'K01', NULL, 0, 0, '2025-11-30 10:14:16'),
(20, 'K01', NULL, 0, 0, '2025-11-30 10:14:39'),
(21, 'K01', NULL, 0, 0, '2025-11-30 10:14:43'),
(22, 'K01', NULL, 0, 0, '2025-11-30 10:14:53'),
(23, 'K01', NULL, 0, 0, '2025-11-30 10:15:01'),
(24, 'K01', NULL, 0, 0, '2025-11-30 10:15:05'),
(25, 'K01', NULL, 0, 0, '2025-11-30 10:15:30'),
(26, 'K01', NULL, 0, 0, '2025-11-30 10:15:43'),
(27, 'K01', NULL, 0, 0, '2025-11-30 10:17:07'),
(28, 'K01', NULL, 0, 0, '2025-11-30 10:17:09'),
(29, 'K01', NULL, 0, 0, '2025-11-30 10:17:20');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_details`
--

CREATE TABLE `transaction_details` (
  `transaction_id` bigint UNSIGNED NOT NULL,
  `product_id` smallint UNSIGNED NOT NULL,
  `qty` smallint UNSIGNED NOT NULL,
  `price_at_transaction` bigint UNSIGNED DEFAULT NULL
) ;

--
-- Dumping data for table `transaction_details`
--

INSERT INTO `transaction_details` (`transaction_id`, `product_id`, `qty`, `price_at_transaction`) VALUES
(6, 7, 5, 3000),
(6, 8, 1, 16000),
(6, 10, 2, 11500),
(7, 9, 2, 3500),
(7, 12, 1, 14000),
(8, 11, 2, 9000),
(9, 1, 1, 13500),
(10, 10, 1, 11500),
(11, 6, 1, 24900),
(12, 1, 1, 13500),
(12, 2, 1, 20900),
(12, 4, 1, 25400),
(12, 5, 1, 19800),
(13, 12, 1, 14000),
(13, 16, 1, 10000),
(14, 16, 1, 10000),
(15, 12, 1, 14000),
(15, 13, 1, 15000),
(15, 15, 1, 5000),
(15, 16, 1, 10000),
(16, 15, 1, 5000),
(17, 15, 1, 5000),
(18, 15, 1, 5000),
(19, 7, 1, 3000),
(20, 15, 1, 5000),
(21, 13, 1, 15000),
(22, 17, 1, 31000),
(23, 13, 1, 15000),
(24, 9, 1, 3500),
(24, 12, 1, 14000),
(25, 12, 1, 14000),
(26, 6, 1, 24900),
(26, 7, 1, 3000),
(26, 9, 1, 3500),
(27, 15, 1, 5000),
(28, 12, 1, 14000),
(29, 12, 1, 14000);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` char(3) NOT NULL,
  `first_name` varchar(15) NOT NULL,
  `last_name` varchar(15) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL DEFAULT 'cashier',
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `password`, `role`, `is_active`) VALUES
('c01', 'Wira', 'Admin', 'wira_admin', 'admin123', 'admin', 1),
('c02', 'Ratih', 'Kasir', 'ratih_kasir', '$2y$10$k1wXJ8.m.k.q.q.E.H.u.n.T.K.o.c.W.U.G.I.R.B.q.y', 'cashier', 1),
('c03', 'Made', 'Restu', 'made_kasir', '$2y$10$O.J.S.E.M.i.P.o.P.u.l.a.r.P.w.D.C.r.y.p.t.O.G', 'cashier', 1),
('c04', 'Giopang', 'Wong', 'gio_kasir', '$2y$10$v.q.P.P.O.I.U.Y.T.R.E.W.Q.A.S.D.F.G.H.J.K.L/M', 'cashier', 0),
('K01', 'Budi', 'Kairi', 'kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 1);

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` char(6) NOT NULL,
  `name` varchar(35) NOT NULL,
  `amount` decimal(3,2) NOT NULL,
  `max_discount` int DEFAULT '0',
  `expiry_date` date NOT NULL,
  `status` enum('active','expired') DEFAULT 'active'
) ;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `name`, `amount`, `max_discount`, `expiry_date`, `status`) VALUES
('vo-001', 'voucher abc orange squash', 0.25, 0, '2025-12-31', 'active'),
('vo-002', 'voucher indofood wonderland', 0.51, 0, '2025-01-30', 'expired'),
('VO-003', 'Promo Kemerdekaan', 0.10, 0, '2025-11-30', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_UserShift` (`user_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone_number_unique` (`phone_number`);

--
-- Indexes for table `daily_sales_summary`
--
ALTER TABLE `daily_sales_summary`
  ADD PRIMARY KEY (`summary_date`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `product_voucher`
--
ALTER TABLE `product_voucher`
  ADD PRIMARY KEY (`product_id`,`voucher_id`),
  ADD KEY `FK_VoucherProduct` (`voucher_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_SupplierPurchase` (`supplier_id`);

--
-- Indexes for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_PurchaseOrder` (`purchase_order_id`),
  ADD KEY `FK_ProductPurchase` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_CashierTransaction` (`cashier_id`),
  ADD KEY `FK_CustomerTransaction` (`customer_id`);

--
-- Indexes for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`transaction_id`,`product_id`),
  ADD KEY `FK_ProductTransaction` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_unique` (`username`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` smallint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD CONSTRAINT `FK_UserShift` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `product_voucher`
--
ALTER TABLE `product_voucher`
  ADD CONSTRAINT `FK_ProductVoucher` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `FK_VoucherProduct` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `FK_SupplierPurchase` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD CONSTRAINT `FK_ProductPurchase` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `FK_PurchaseOrder` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `FK_CashierTransaction` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `FK_CustomerTransaction` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `FK_ProductTransaction` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `FK_TransactionDetail` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
