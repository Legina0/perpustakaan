-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 15, 2026 at 03:55 AM
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
-- Database: `db_perpustakaan`
--

-- --------------------------------------------------------

--
-- Table structure for table `anggota`
--

CREATE TABLE `anggota` (
  `kd_anggota` int(11) NOT NULL,
  `nm_anggota` varchar(150) NOT NULL,
  `jenis_kelamin` char(1) DEFAULT 'L',
  `alamat` text DEFAULT NULL,
  `no_telp` varchar(30) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tgl_daftar` date DEFAULT NULL,
  `user` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status_aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`kd_anggota`, `nm_anggota`, `jenis_kelamin`, `alamat`, `no_telp`, `foto`, `tgl_daftar`, `user`, `password`, `status_aktif`) VALUES
(1, 'budiman sudirman', 'L', 'Jl. Mawar No. 1', '081300000001', NULL, '2026-07-13', 'anggota1', '$2b$10$my/CM4dLagbbfMt1W1/QPuT4t2D2gPjfFdAnHIZlvYqP0exAfv04W', 1),
(2, 'Siti Aminah', 'P', 'Jl. Kenanga No. 5', '081300000002', NULL, '2026-07-13', 'anggota2', '$2b$10$my/CM4dLagbbfMt1W1/QPuT4t2D2gPjfFdAnHIZlvYqP0exAfv04W', 1),
(3, 'Mochamad Azka Nugraha', 'L', 'cipada', '085864745792', NULL, '2026-07-13', 'azka123', '$2y$10$.sIFoBgagIYgXREcUneiHuTQuNvV9JyAAMVBW7FLkdDMoF98C572S', 1);

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `kd_buku` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kd_penerbit` int(11) DEFAULT NULL,
  `kd_klasifikasi` int(11) DEFAULT NULL,
  `kd_pengarang` int(11) DEFAULT NULL,
  `thn_terbit` int(11) DEFAULT NULL,
  `bahasa` varchar(50) DEFAULT NULL,
  `edisi` varchar(50) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `jumlah` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`kd_buku`, `judul`, `kd_penerbit`, `kd_klasifikasi`, `kd_pengarang`, `thn_terbit`, `bahasa`, `edisi`, `isbn`, `jumlah`) VALUES
(1, 'Algoritma Pemrograman', 1, 1, 1, 2020, 'Indonesia', '1', '978-602-1234-01-1', 5),
(2, 'Laskar Pelangi', 2, 2, 2, 2005, 'Indonesia', '1', '978-602-1234-02-2', 3),
(3, 'Bumi', 3, 2, 3, 2014, 'Indonesia', '1', '978-602-1234-03-3', 4),
(4, 'buku harian', 4, 4, 2, 2000, 'indonesia', 'terbaru', '978-602-03-1234-5', 10),
(5, 'bumi manusia', 4, 4, 2, 1980, 'indonesia', 'terbaru', '978-602-03-1234-5', 1);

-- --------------------------------------------------------

--
-- Table structure for table `denda`
--

CREATE TABLE `denda` (
  `id_denda` int(11) NOT NULL,
  `id_detpinjam` int(11) NOT NULL,
  `tgl_denda` date NOT NULL,
  `jmlh_denda` decimal(12,2) DEFAULT 0.00,
  `lunas` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detpinjam`
--

CREATE TABLE `detpinjam` (
  `id_detpinjam` int(11) NOT NULL,
  `no_pinjam` int(11) NOT NULL,
  `no_inventaris` int(11) NOT NULL,
  `tgl_pinjam` date NOT NULL,
  `tgl_kembali` date DEFAULT NULL,
  `status_pinjam` enum('Dipinjam','Kembali') DEFAULT 'Dipinjam'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detpinjam`
--

INSERT INTO `detpinjam` (`id_detpinjam`, `no_pinjam`, `no_inventaris`, `tgl_pinjam`, `tgl_kembali`, `status_pinjam`) VALUES
(1, 1, 5, '2026-07-13', '2026-07-13', 'Kembali'),
(2, 2, 1, '2026-07-13', NULL, 'Dipinjam'),
(3, 3, 2, '2026-07-15', NULL, 'Dipinjam');

-- --------------------------------------------------------

--
-- Table structure for table `inventaris`
--

CREATE TABLE `inventaris` (
  `no_inventaris` int(11) NOT NULL,
  `kd_buku` int(11) NOT NULL,
  `no_buku` varchar(50) DEFAULT NULL,
  `tgl_masuk` date DEFAULT NULL,
  `status` enum('Tersedia','Dipinjam','Rusak','Hilang') DEFAULT 'Tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventaris`
--

INSERT INTO `inventaris` (`no_inventaris`, `kd_buku`, `no_buku`, `tgl_masuk`, `status`) VALUES
(1, 1, 'B001-1', '2024-01-01', 'Dipinjam'),
(2, 1, 'B001-2', '2024-01-01', 'Dipinjam'),
(3, 2, 'B002-1', '2024-01-05', 'Tersedia'),
(4, 3, 'B003-1', '2024-02-01', 'Tersedia'),
(5, 4, 'B004-1', '2026-07-13', 'Tersedia'),
(6, 4, 'B004-2', '2026-07-13', 'Tersedia'),
(7, 4, 'B004-3', '2026-07-13', 'Tersedia'),
(8, 4, 'B004-4', '2026-07-13', 'Tersedia'),
(9, 4, 'B004-5', '2026-07-13', 'Tersedia'),
(10, 4, 'B004-6', '2026-07-13', 'Tersedia'),
(11, 4, 'B004-7', '2026-07-13', 'Tersedia'),
(12, 4, 'B004-8', '2026-07-13', 'Tersedia'),
(13, 4, 'B004-9', '2026-07-13', 'Tersedia'),
(14, 4, 'B004-10', '2026-07-13', 'Tersedia'),
(15, 5, 'B005-1', '2026-07-14', 'Tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `klasifikasi`
--

CREATE TABLE `klasifikasi` (
  `kd_klasifikasi` int(11) NOT NULL,
  `nm_klasifikasi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `klasifikasi`
--

INSERT INTO `klasifikasi` (`kd_klasifikasi`, `nm_klasifikasi`) VALUES
(1, 'Teknologi'),
(2, 'Fiksi'),
(3, 'Sains'),
(4, 'Sejarah');

-- --------------------------------------------------------

--
-- Table structure for table `konfigurasi`
--

CREATE TABLE `konfigurasi` (
  `key` varchar(50) NOT NULL,
  `value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `konfigurasi`
--

INSERT INTO `konfigurasi` (`key`, `value`) VALUES
('lama_pinjam_hari', '1'),
('tarif_denda_per_hari', '1000');

-- --------------------------------------------------------

--
-- Table structure for table `penerbit`
--

CREATE TABLE `penerbit` (
  `kd_penerbit` int(11) NOT NULL,
  `nm_penerbit` varchar(150) NOT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penerbit`
--

INSERT INTO `penerbit` (`kd_penerbit`, `nm_penerbit`, `alamat`) VALUES
(1, 'Informatika', 'Bandung'),
(2, 'Gramedia', 'Jakarta'),
(3, 'Erlangga', 'Jakarta'),
(4, 'azka', 'cipada');

-- --------------------------------------------------------

--
-- Table structure for table `pengarang`
--

CREATE TABLE `pengarang` (
  `kd_pengarang` int(11) NOT NULL,
  `nm_pengarang` varchar(150) NOT NULL,
  `jenis_kelamin` char(1) DEFAULT 'L'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengarang`
--

INSERT INTO `pengarang` (`kd_pengarang`, `nm_pengarang`, `jenis_kelamin`) VALUES
(1, 'Rinaldi Munir', 'L'),
(2, 'Andrea Hirata', 'L'),
(3, 'Tere Liye', 'L');

-- --------------------------------------------------------

--
-- Table structure for table `petugas`
--

CREATE TABLE `petugas` (
  `kd_petugas` int(11) NOT NULL,
  `nm_petugas` varchar(150) NOT NULL,
  `jenis_kelamin` char(1) DEFAULT 'L',
  `alamat` text DEFAULT NULL,
  `telp` varchar(30) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `user` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas') DEFAULT 'petugas',
  `status_aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `petugas`
--

INSERT INTO `petugas` (`kd_petugas`, `nm_petugas`, `jenis_kelamin`, `alamat`, `telp`, `foto`, `user`, `password`, `role`, `status_aktif`) VALUES
(1, 'Administrator', 'L', 'Kantor Perpustakaan', '081200000001', 'foto_6a56e684e994f2.81774784.webp', 'admin', '$2b$10$my/CM4dLagbbfMt1W1/QPuT4t2D2gPjfFdAnHIZlvYqP0exAfv04W', 'admin', 1),
(2, 'Budi Petugas', 'L', 'Jl. Melati No. 2', '081200000002', NULL, 'petugas1', '$2b$10$my/CM4dLagbbfMt1W1/QPuT4t2D2gPjfFdAnHIZlvYqP0exAfv04W', 'petugas', 1),
(3, 'mustafa', 'L', 'cihideng', 'vuvu', 'foto_6a56e4d70ccf42.66120975.jpg', 'petugassigma', '$2y$10$q.ITU5INUYPu464yABuH/ebEv7BFxcK3sto6SzFqlayd3oT0ccwvu', 'petugas', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pinjam`
--

CREATE TABLE `pinjam` (
  `no_pinjam` int(11) NOT NULL,
  `kd_anggota` int(11) NOT NULL,
  `kd_petugas` int(11) NOT NULL,
  `tgl_pinjam` date NOT NULL,
  `tgl_harus_kembali` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pinjam`
--

INSERT INTO `pinjam` (`no_pinjam`, `kd_anggota`, `kd_petugas`, `tgl_pinjam`, `tgl_harus_kembali`) VALUES
(1, 3, 1, '2026-07-13', '2026-07-20'),
(2, 3, 1, '2026-07-13', '2026-07-20'),
(3, 3, 1, '2026-07-15', '2026-07-16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`kd_anggota`),
  ADD UNIQUE KEY `user` (`user`);

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`kd_buku`),
  ADD KEY `kd_penerbit` (`kd_penerbit`),
  ADD KEY `kd_klasifikasi` (`kd_klasifikasi`),
  ADD KEY `kd_pengarang` (`kd_pengarang`);

--
-- Indexes for table `denda`
--
ALTER TABLE `denda`
  ADD PRIMARY KEY (`id_denda`),
  ADD KEY `id_detpinjam` (`id_detpinjam`);

--
-- Indexes for table `detpinjam`
--
ALTER TABLE `detpinjam`
  ADD PRIMARY KEY (`id_detpinjam`),
  ADD KEY `no_pinjam` (`no_pinjam`),
  ADD KEY `no_inventaris` (`no_inventaris`);

--
-- Indexes for table `inventaris`
--
ALTER TABLE `inventaris`
  ADD PRIMARY KEY (`no_inventaris`),
  ADD KEY `kd_buku` (`kd_buku`);

--
-- Indexes for table `klasifikasi`
--
ALTER TABLE `klasifikasi`
  ADD PRIMARY KEY (`kd_klasifikasi`);

--
-- Indexes for table `konfigurasi`
--
ALTER TABLE `konfigurasi`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `penerbit`
--
ALTER TABLE `penerbit`
  ADD PRIMARY KEY (`kd_penerbit`);

--
-- Indexes for table `pengarang`
--
ALTER TABLE `pengarang`
  ADD PRIMARY KEY (`kd_pengarang`);

--
-- Indexes for table `petugas`
--
ALTER TABLE `petugas`
  ADD PRIMARY KEY (`kd_petugas`),
  ADD UNIQUE KEY `user` (`user`);

--
-- Indexes for table `pinjam`
--
ALTER TABLE `pinjam`
  ADD PRIMARY KEY (`no_pinjam`),
  ADD KEY `kd_anggota` (`kd_anggota`),
  ADD KEY `kd_petugas` (`kd_petugas`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anggota`
--
ALTER TABLE `anggota`
  MODIFY `kd_anggota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `kd_buku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `denda`
--
ALTER TABLE `denda`
  MODIFY `id_denda` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detpinjam`
--
ALTER TABLE `detpinjam`
  MODIFY `id_detpinjam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventaris`
--
ALTER TABLE `inventaris`
  MODIFY `no_inventaris` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `klasifikasi`
--
ALTER TABLE `klasifikasi`
  MODIFY `kd_klasifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `penerbit`
--
ALTER TABLE `penerbit`
  MODIFY `kd_penerbit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pengarang`
--
ALTER TABLE `pengarang`
  MODIFY `kd_pengarang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `petugas`
--
ALTER TABLE `petugas`
  MODIFY `kd_petugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pinjam`
--
ALTER TABLE `pinjam`
  MODIFY `no_pinjam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `buku_ibfk_1` FOREIGN KEY (`kd_penerbit`) REFERENCES `penerbit` (`kd_penerbit`) ON DELETE SET NULL,
  ADD CONSTRAINT `buku_ibfk_2` FOREIGN KEY (`kd_klasifikasi`) REFERENCES `klasifikasi` (`kd_klasifikasi`) ON DELETE SET NULL,
  ADD CONSTRAINT `buku_ibfk_3` FOREIGN KEY (`kd_pengarang`) REFERENCES `pengarang` (`kd_pengarang`) ON DELETE SET NULL;

--
-- Constraints for table `denda`
--
ALTER TABLE `denda`
  ADD CONSTRAINT `denda_ibfk_1` FOREIGN KEY (`id_detpinjam`) REFERENCES `detpinjam` (`id_detpinjam`) ON DELETE CASCADE;

--
-- Constraints for table `detpinjam`
--
ALTER TABLE `detpinjam`
  ADD CONSTRAINT `detpinjam_ibfk_1` FOREIGN KEY (`no_pinjam`) REFERENCES `pinjam` (`no_pinjam`) ON DELETE CASCADE,
  ADD CONSTRAINT `detpinjam_ibfk_2` FOREIGN KEY (`no_inventaris`) REFERENCES `inventaris` (`no_inventaris`);

--
-- Constraints for table `inventaris`
--
ALTER TABLE `inventaris`
  ADD CONSTRAINT `inventaris_ibfk_1` FOREIGN KEY (`kd_buku`) REFERENCES `buku` (`kd_buku`) ON DELETE CASCADE;

--
-- Constraints for table `pinjam`
--
ALTER TABLE `pinjam`
  ADD CONSTRAINT `pinjam_ibfk_1` FOREIGN KEY (`kd_anggota`) REFERENCES `anggota` (`kd_anggota`),
  ADD CONSTRAINT `pinjam_ibfk_2` FOREIGN KEY (`kd_petugas`) REFERENCES `petugas` (`kd_petugas`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
