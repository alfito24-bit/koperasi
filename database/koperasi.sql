CREATE DATABASE IF NOT EXISTS `koperasi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `koperasi`;

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 20, 2026 at 01:49 AM
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
-- Database: `koperasi`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `harga` int NOT NULL,
  `stok` int NOT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `nama_barang`, `kategori`, `harga`, `stok`, `foto`) VALUES
(1, 'Buku Folio', 'ATK', 20000, 50, 'barang_6a7d539da2a23.jpg'),
(2, 'Buku Big Box', 'ATK', 8000, 30, 'barang_6a7d53bd432a2.jpg'),
(3, 'Buku SIDU isi 58', 'ATK', 6000, 50, 'barang_6a7d549906d36.jpg'),
(4, 'Penggaris Busur', 'ATK', 3000, 25, 'barang_6a7d54adc8b57.jpg'),
(5, 'Bet Kelas', 'Atribut Sekolah', 5000, 200, NULL),
(6, 'Bet Lokasi', 'Atribut Sekolah', 5000, 300, NULL),
(7, 'Gunting', 'ATK', 6000, 30, 'barang_6a7d54c5b775e.jpg'),
(8, 'Isolasi 1/2', 'ATK', 4000, 40, 'barang_6a7d54fbaa872.jpg'),
(9, 'Isolasi Kado', 'ATK', 1000, 30, 'barang_6a7d550d61b66.jpg'),
(10, 'Kertas Folio 1 Lembar', 'ATK', 1000, 80, 'barang_6a7d5526c5366.jpg'),
(11, 'Kertas Manggis', 'ATK', 2000, 90, 'barang_6a7d5562b439f.jpg'),
(12, 'Kertas Kopi', 'ATK', 2000, 45, 'barang_6a7d55e7be1c7.jpg'),
(13, 'Lap Serbet', 'ATK', 5000, 30, 'barang_6a7d562156735.jpg'),
(14, 'Lem Stik 8gr', 'ATK', 4000, 30, 'barang_6a7d564311836.jpg'),
(15, 'Pena Standar AE7', 'ATK', 3000, 87, 'barang_6a7d57002713b.jpg'),
(16, 'Pensil Joyko', 'ATK', 2000, 35, 'barang_6a7d57148b8a8.jpg'),
(17, 'Penghapus Joyko', 'ATK', 2000, 20, 'barang_6a7d57280eae3.jpg'),
(18, 'Pelastik Tangan', 'ATK', 500, 50, 'barang_6a7d573c3bf92.jpg'),
(19, 'Sampul Mika Buku Biasa', 'ATK', 1000, 54, 'barang_6a7d57524c270.jpg'),
(20, 'Sampul Mika Buku Big Box', 'ATK', 1000, 30, 'barang_6a7d576902d17.jpg'),
(21, 'Sampul Mika Buku Folio', 'ATK', 2000, 50, 'barang_6a7d577b4b270.jpg'),
(22, 'Penggaris Butterfly', 'ATK', 4000, 30, 'barang_6a7d57b4de1ea.jpg'),
(23, 'Sampul Kopi Buku Biasa', 'ATK', 500, 34, 'barang_6a7d57ca8b3f2.jpg'),
(24, 'Sampul Kopi Buku Big Box', 'ATK', 1000, 30, 'barang_6a7d57ded7fb0.jpg'),
(25, 'Sendok Plastik', 'Alat Makan', 1000, 30, 'barang_6a7d57f7a4acc.jpg'),
(26, 'Softex Sachet Isi 1', 'BP', 1000, 20, 'barang_6a7d597878a85.jpg'),
(27, 'Softex Sachet Isi 2', 'BP', 2000, 30, NULL),
(28, 'Softex Malam Isi 2', 'BP', 3000, 30, NULL),
(29, 'Tisu Saku', 'ATK', 1000, 30, NULL),
(30, 'Sendok Alumunium', 'Alat Makan', 2000, 30, NULL),
(31, 'Kunyit Asam', 'Minuman', 5000, 20, NULL),
(32, 'Le Mineral 600ML', 'Makanan', 4000, 60, NULL),
(33, 'Lasegar Kaleng 320ML', 'Minuman', 8000, 50, NULL),
(34, 'Pocari Sweat', 'Minuman', 7000, 40, NULL),
(35, 'Susu Real Good Beku', 'Miinuman', 2000, 100, NULL),
(36, 'Susu Ultramilk 125ML', 'Minuman', 4000, 120, NULL),
(37, 'Susu Ultramilk 200ML', 'Minuman', 6000, 120, NULL),
(38, 'tripanca 1,5L', 'Minuman', 5000, 25, NULL),
(39, 'Tripanca 600ML', 'Minuman', 3000, 25, NULL),
(40, 'Al-Masurat', 'ATK', 2000, 150, NULL),
(41, 'Buku Folio', 'ATK', 20000, 50, NULL),
(42, 'Buku Big Box', 'ATK', 8000, 30, NULL),
(43, 'Buku SIDU isi 58', 'ATK', 6000, 50, NULL),
(44, 'Penggaris Busur', 'ATK', 3000, 25, 'barang_6a7d5a146a4bf.jpg'),
(45, 'Bet Kelas', 'Atribut Sekolah', 5000, 200, NULL),
(46, 'Bet Lokasi', 'Atribut Sekolah', 5000, 300, NULL),
(47, 'Gunting', 'ATK', 6000, 30, NULL),
(48, 'Isolasi 1/2', 'ATK', 4000, 40, NULL),
(49, 'Isolasi Kado', 'ATK', 1000, 30, NULL),
(50, 'Kertas Folio 1 Lembar', 'ATK', 1000, 80, NULL),
(51, 'Kertas Manggis', 'ATK', 2000, 90, 'barang_6a7d5a3dce8a1.jpg'),
(52, 'Kertas Kopi', 'ATK', 2000, 45, NULL),
(53, 'Lap Serbet', 'ATK', 5000, 30, NULL),
(54, 'Lem Stik 8gr', 'ATK', 4000, 30, NULL),
(55, 'Pena Standar AE7', 'ATK', 3000, 87, NULL),
(56, 'Pensil Joyko', 'ATK', 2000, 35, NULL),
(57, 'Penghapus Joyko', 'ATK', 2000, 20, NULL),
(58, 'Pelastik Tangan', 'ATK', 500, 50, NULL),
(59, 'Sampul Mika Buku Biasa', 'ATK', 1000, 54, NULL),
(60, 'Sampul Mika Buku Big Box', 'ATK', 1000, 30, NULL),
(61, 'Sampul Mika Buku Folio', 'ATK', 2000, 50, NULL),
(62, 'Penggaris Butterfly', 'ATK', 4000, 30, NULL),
(63, 'Sampul Kopi Buku Biasa', 'ATK', 500, 34, NULL),
(64, 'Sampul Kopi Buku Big Box', 'ATK', 1000, 30, NULL),
(65, 'Sendok Plastik', 'Alat Makan', 1000, 30, NULL),
(66, 'Softex Sachet Isi 1', 'BP', 1000, 20, NULL),
(67, 'Softex Sachet Isi 2', 'BP', 2000, 30, NULL),
(68, 'Softex Malam Isi 2', 'BP', 3000, 30, NULL),
(69, 'Tisu Saku', 'ATK', 1000, 30, NULL),
(70, 'Sendok Alumunium', 'Alat Makan', 2000, 30, NULL),
(71, 'Kunyit Asam', 'Minuman', 5000, 20, NULL),
(72, 'Le Mineral 600ML', 'Makanan', 4000, 60, NULL),
(73, 'Lasegar Kaleng 320ML', 'Minuman', 8000, 50, NULL),
(74, 'Pocari Sweat', 'Minuman', 7000, 40, NULL),
(75, 'Susu Real Good Beku', 'Miinuman', 2000, 100, NULL),
(76, 'Susu Ultramilk 125ML', 'Minuman', 4000, 120, NULL),
(77, 'Susu Ultramilk 200ML', 'Minuman', 6000, 120, NULL),
(78, 'tripanca 1,5L', 'Minuman', 5000, 25, NULL),
(79, 'Tripanca 600ML', 'Minuman', 3000, 25, NULL),
(80, 'Al-Masurat', 'ATK', 2000, 150, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id` int NOT NULL,
  `id_transaksi` int NOT NULL,
  `id_barang` int NOT NULL,
  `jumlah` int NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int NOT NULL,
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nama_pembeli` varchar(100) NOT NULL,
  `id_user` int NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `bayar` decimal(10,2) NOT NULL,
  `kembalian` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kasir') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
(1, 'Admin', 'Admin', '123', 'admin'),
(2, 'kasir', 'kasir', '1234', 'kasir');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detail_transaksi` (`id_transaksi`),
  ADD KEY `fk_detail_barang` (`id_barang`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `fk_transaksi_user` (`id_user`);

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
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `fk_detail_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
