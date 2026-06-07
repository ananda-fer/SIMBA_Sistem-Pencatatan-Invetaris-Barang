-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 05, 2026 at 03:22 AM
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
-- Database: `simba`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`pbw_dbsimbasimba
--

CREATE TABLE `barang` (
  `id` int NOT NULL,
  `kategori_id` int NOT NULL,
  `kode_barang` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `stok_total` int NOT NULL,
  `stok_tersedia` int NOT NULL,
  `kondisi` enum('baik','rusak_ringan','rusak_berat') COLLATE utf8mb4_general_ci DEFAULT 'baik',
  `status` enum('tersedia','dipinjam','dalam_perbaikan') COLLATE utf8mb4_general_ci DEFAULT 'tersedia',
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `kategori_id`, `kode_barang`, `nama`, `stok_total`, `stok_tersedia`, `kondisi`, `status`, `foto`, `dibuat_pada`) VALUES
(1, 1, 'FKT-ELK-001', 'Proyektor Epson EB-X41', 3, 0, 'baik', 'dipinjam', NULL, '2026-06-03 09:24:10'),
(2, 1, 'FKT-ELK-002', 'Laptop Dell Inspiron', 5, 3, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
(3, 1, 'FKT-ELK-003', 'Laptop Lenovo ThinkPad', 3, 1, 'baik', 'dipinjam', NULL, '2026-06-03 09:24:10'),
(4, 2, 'FKT-FRN-001', 'Meja Lipat Panjang', 10, 7, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
(5, 2, 'FKT-FRN-002', 'Kursi Lipat', 20, 18, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
(6, 2, 'FKT-FRN-003', 'Sofa Ruang Tunggu', 2, 0, 'rusak_ringan', 'dipinjam', NULL, '2026-06-03 09:24:10'),
(7, 3, 'FKT-AKS-001', 'Kabel HDMI 3 Meter', 10, 7, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
(8, 3, 'FKT-AKS-002', 'Kabel VGA', 6, 6, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
(9, 3, 'FKT-AKS-003', 'Extension Kabel Listrik', 8, 3, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
(10, 4, 'FKT-AVS-001', 'Mikrofon Wireless', 4, 2, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
(11, 4, 'FKT-AVS-002', 'Speaker Portable', 3, 1, 'baik', 'dipinjam', NULL, '2026-06-03 09:24:10'),
(12, 4, 'FKT-AVS-003', 'Laser Pointer', 5, 5, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `detail_peminjaman`
--

CREATE TABLE `detail_peminjaman` (
  `id` int NOT NULL,
  `id_peminjaman` int NOT NULL,
  `id_barang` int NOT NULL,
  `jumlah` int NOT NULL,
  `kondisi_saat_pinjam` enum('baik','rusak_ringan') COLLATE utf8mb4_general_ci DEFAULT 'baik',
  `catatan` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_peminjaman`
--

INSERT INTO `detail_peminjaman` (`id`, `id_peminjaman`, `id_barang`, `jumlah`, `kondisi_saat_pinjam`, `catatan`) VALUES
(1, 1, 2, 2, 'baik', NULL),
(2, 1, 7, 2, 'baik', NULL),
(3, 2, 1, 1, 'baik', NULL),
(4, 2, 10, 1, 'baik', NULL),
(5, 3, 1, 1, 'baik', NULL),
(6, 4, 5, 4, 'baik', NULL),
(7, 4, 4, 2, 'baik', NULL),
(8, 5, 11, 1, 'baik', NULL),
(9, 6, 2, 1, 'baik', NULL),
(10, 6, 7, 1, 'baik', NULL),
(11, 7, 9, 1, 'baik', NULL),
(12, 8, 9, 1, 'baik', NULL),
(13, 9, 9, 1, 'baik', NULL),
(14, 9, 7, 1, 'baik', NULL),
(15, 10, 9, 1, 'baik', NULL),
(16, 11, 2, 1, 'baik', NULL),
(17, 11, 3, 1, 'baik', NULL),
(18, 12, 3, 1, 'baik', NULL),
(19, 13, 10, 1, 'baik', NULL),
(20, 13, 11, 1, 'baik', NULL),
(21, 13, 2, 1, 'baik', NULL),
(22, 14, 9, 1, 'baik', NULL),
(23, 15, 9, 1, 'baik', NULL),
(24, 15, 11, 1, 'baik', NULL),
(25, 15, 2, 1, 'baik', NULL),
(26, 15, 1, 1, 'baik', NULL),
(27, 16, 7, 1, 'baik', NULL),
(28, 17, 9, 1, 'baik', NULL),
(29, 18, 4, 1, 'baik', NULL),
(30, 18, 6, 1, 'baik', NULL),
(31, 19, 9, 1, 'baik', NULL),
(32, 19, 7, 1, 'baik', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kode` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `kode`, `deskripsi`) VALUES
(1, 'Elektronik', 'ELK', 'Perangkat elektronik seperti proyektor, laptop, dan speaker'),
(2, 'Furnitur & Mebel', 'FRN', 'Meja, kursi, dan perabot ruangan'),
(3, 'Aksesoris', 'AKS', 'Kabel, adaptor, dan aksesoris pendukung'),
(4, 'Audio Visual', 'AVS', 'Mikrofon, sound system, dan perangkat presentasi');

-- --------------------------------------------------------

--
-- Table structure for table `laporan`
--

CREATE TABLE `laporan` (
  `id` int NOT NULL,
  `judul` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_laporan` enum('peminjaman','pengembalian','inventaris') COLLATE utf8mb4_general_ci NOT NULL,
  `periode_awal` date NOT NULL,
  `periode_akhir` date NOT NULL,
  `dibuat_oleh` int NOT NULL,
  `catatan` text COLLATE utf8mb4_general_ci,
  `file_pdf` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_peminjaman`
--

CREATE TABLE `pengajuan_peminjaman` (
  `id` int NOT NULL,
  `id_peminjam` int NOT NULL,
  `keperluan` text COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `status` enum('menunggu','diverifikasi','disetujui','ditolak','aktif','selesai','dibatalkan') COLLATE utf8mb4_general_ci DEFAULT 'menunggu',
  `diverifikasi_oleh` int DEFAULT NULL,
  `disetujui_oleh` int DEFAULT NULL,
  `catatan_tolak` text COLLATE utf8mb4_general_ci,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `file_surat` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Untuk event himpunan/peminjaman alat berat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_peminjaman`
--

INSERT INTO `pengajuan_peminjaman` (`id`, `id_peminjam`, `keperluan`, `tanggal_pinjam`, `tanggal_kembali`, `status`, `diverifikasi_oleh`, `disetujui_oleh`, `catatan_tolak`, `dibuat_pada`, `diperbarui_pada`, `file_surat`) VALUES
(1, 3, 'Praktikum Jaringan Komputer Semester 4', '2026-05-24', '2026-05-27', 'disetujui', 2, 1, NULL, '2026-06-03 09:24:10', '2026-06-03 09:24:10', NULL),
(2, 4, 'Acara BEM Universitas 2026', '2026-05-25', '2026-05-28', 'disetujui', 2, 1, NULL, '2026-06-03 09:24:10', '2026-06-04 06:08:18', NULL),
(3, 5, 'Workshop Sistem Operasi Kelas B', '2026-05-23', '2026-05-24', 'aktif', 2, 1, NULL, '2026-06-03 09:24:10', '2026-06-03 09:24:10', NULL),
(4, 6, 'Rapat Koordinasi BLM Fakultas', '2026-05-22', '2026-05-22', 'selesai', 2, 1, NULL, '2026-06-03 09:24:10', '2026-06-03 09:24:10', NULL),
(5, 7, 'Lomba PKM Tingkat Nasional', '2026-05-26', '2026-05-28', 'ditolak', 2, 1, 'Barang sedang dalam jadwal pemeliharaan rutin.', '2026-06-03 09:24:10', '2026-06-03 09:24:10', NULL),
(6, 3, 'Ujian Praktikum Basis Data', '2026-06-01', '2026-06-03', 'ditolak', 2, 1, 'gabisa', '2026-06-03 09:24:10', '2026-06-04 06:08:34', NULL),
(7, 3, 'd', '2026-06-03', '2026-06-04', 'ditolak', 2, 1, 'gabisa lagi rusak', '2026-06-03 17:03:24', '2026-06-04 06:06:02', NULL),
(8, 3, 'v', '2026-06-03', '2026-06-04', 'disetujui', NULL, 1, NULL, '2026-06-03 17:03:39', '2026-06-04 06:02:05', NULL),
(9, 3, 'Webinar', '2026-06-04', '2026-06-05', 'disetujui', 2, 1, NULL, '2026-06-04 08:42:08', '2026-06-04 08:44:59', NULL),
(10, 3, 'aWebinar', '2026-06-04', '2026-06-05', 'dibatalkan', NULL, NULL, NULL, '2026-06-04 08:56:31', '2026-06-04 08:56:35', NULL),
(11, 3, 'Seminar', '2026-06-04', '2026-06-05', 'disetujui', 2, 2, NULL, '2026-06-04 09:04:02', '2026-06-04 11:47:07', 'uploads/surat/surat_3_20260604090402_d361f0d8.pdf'),
(12, 3, 'Diesnat', '2026-06-04', '2026-06-05', 'disetujui', 2, 2, NULL, '2026-06-04 09:14:39', '2026-06-04 11:47:02', 'uploads/surat/surat_3_20260604091439_f98d0f5d.pdf'),
(13, 3, 'Webinar', '2026-06-05', '2026-06-06', 'ditolak', 2, 2, 'jelek', '2026-06-04 17:57:04', '2026-06-04 17:57:21', 'uploads/surat/surat_3_20260604175704_13adf77d.pdf'),
(14, 3, 'Diesnat', '2026-06-05', '2026-06-06', 'dibatalkan', NULL, NULL, NULL, '2026-06-04 17:57:56', '2026-06-04 17:58:22', NULL),
(15, 3, 'Seminar', '2026-06-05', '2026-06-06', 'disetujui', 2, 2, NULL, '2026-06-05 01:18:36', '2026-06-05 01:27:54', 'uploads/surat/surat_3_20260605011836_aba07b54.pdf'),
(16, 3, 'Webinar', '2026-06-05', '2026-06-06', 'aktif', 2, 2, NULL, '2026-06-05 01:42:30', '2026-06-05 02:08:21', NULL),
(17, 3, 'Seminar', '2026-06-05', '2026-06-07', 'aktif', 2, 2, NULL, '2026-06-05 01:50:53', '2026-06-05 02:08:16', NULL),
(18, 3, 'Diesnat', '2026-06-05', '2026-06-06', 'aktif', 2, 2, NULL, '2026-06-05 02:17:29', '2026-06-05 02:18:17', 'uploads/surat/surat_3_20260605021729_d74f3bd9.pdf'),
(19, 3, 'kelas', '2026-06-05', '2026-06-06', 'menunggu', NULL, NULL, NULL, '2026-06-05 03:05:28', '2026-06-05 03:05:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id` int NOT NULL,
  `id_peminjaman` int NOT NULL,
  `diterima_oleh` int NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `hari_terlambat` int DEFAULT '0',
  `kondisi_kembali` enum('baik','rusak_ringan','rusak_berat') COLLATE utf8mb4_general_ci NOT NULL,
  `catatan_kerusakan` text COLLATE utf8mb4_general_ci,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengembalian`
--

INSERT INTO `pengembalian` (`id`, `id_peminjaman`, `diterima_oleh`, `tanggal_kembali`, `hari_terlambat`, `kondisi_kembali`, `catatan_kerusakan`, `dibuat_pada`) VALUES
(1, 4, 2, '2026-05-22', 0, 'baik', NULL, '2026-06-03 09:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama_pengguna` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kata_sandi` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `peran` enum('admin','staff_tu','peminjam') COLLATE utf8mb4_general_ci NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_pengguna`, `kata_sandi`, `nama_lengkap`, `email`, `peran`, `aktif`, `dibuat_pada`) VALUES
(1, 'admin', '$2y$10$D2SvnnqoNPMkYtE.I9C7suLnQfOWSvfwNNbPrwuPC7laqq9ZretPu', 'Administrator SIMBA', 'admin@simba.ac.id', 'admin', 1, '2026-06-03 09:24:10'),
(2, 'staff_tu', '$2y$10$8nkp5MxeuZfYBUQNy4unPec7DonKzDDvlQXg04hPpen6JZzp8TYFC', 'M. Rizky Pratama', 'rizky@simba.ac.id', 'staff_tu', 1, '2026-06-03 09:24:10'),
(3, 'fahri', '$2y$10$O0YdVluABBXLB7rffHJeOObNffB0dO1nQyA.1naG.69ScjbCl6EKe', 'Maulana Fahri A.', 'fahri@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
(4, 'sidiq', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sidiq Pramono', 'sidiq@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
(5, 'ake', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ake Permana', 'ake@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
(6, 'ajgab', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmad Jabbar', 'ajgab@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
(7, 'andreas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Andreas Santoso', 'andreas@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_peminjaman` (`id_peminjaman`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dibuat_oleh` (`dibuat_oleh`);

--
-- Indexes for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_peminjam` (`id_peminjam`),
  ADD KEY `diverifikasi_oleh` (`diverifikasi_oleh`),
  ADD KEY `disetujui_oleh` (`disetujui_oleh`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_peminjaman` (`id_peminjaman`),
  ADD KEY `diterima_oleh` (`diterima_oleh`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_pengguna` (`nama_pengguna`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`);

--
-- Constraints for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD CONSTRAINT `detail_peminjaman_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `pengajuan_peminjaman` (`id`),
  ADD CONSTRAINT `detail_peminjaman_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`);

--
-- Constraints for table `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_1` FOREIGN KEY (`id_peminjam`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_2` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_3` FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `pengajuan_peminjaman` (`id`),
  ADD CONSTRAINT `pengembalian_ibfk_2` FOREIGN KEY (`diterima_oleh`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
