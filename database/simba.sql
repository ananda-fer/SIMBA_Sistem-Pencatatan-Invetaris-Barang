-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for simba
CREATE DATABASE IF NOT EXISTS `simba` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `simba`;

-- Dumping structure for table simba.barang
CREATE TABLE IF NOT EXISTS `barang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kategori_id` int NOT NULL,
  `kode_barang` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `stok_total` int NOT NULL,
  `stok_tersedia` int NOT NULL,
  `kondisi` enum('baik','rusak_ringan','rusak_berat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'baik',
  `status` enum('tersedia','dipinjam','dalam_perbaikan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'tersedia',
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_barang` (`kode_barang`),
  KEY `kategori_id` (`kategori_id`),
  CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table simba.barang: ~12 rows (approximately)
INSERT INTO `barang` (`id`, `kategori_id`, `kode_barang`, `nama`, `stok_total`, `stok_tersedia`, `kondisi`, `status`, `foto`, `dibuat_pada`) VALUES
	(1, 1, 'FKT-ELK-001', 'Proyektor Epson EB-X41', 3, 1, 'rusak_ringan', 'dipinjam', NULL, '2026-06-03 09:24:10'),
	(2, 1, 'FKT-ELK-002', 'Laptop Dell Inspiron', 5, 3, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
	(3, 1, 'FKT-ELK-003', 'Laptop Lenovo ThinkPad', 3, 1, 'baik', 'dipinjam', NULL, '2026-06-03 09:24:10'),
	(4, 2, 'FKT-FRN-001', 'Meja Lipat Panjang', 10, 8, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
	(5, 2, 'FKT-FRN-002', 'Kursi Lipat', 20, 18, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
	(6, 2, 'FKT-FRN-003', 'Sofa Ruang Tunggu', 2, 1, 'baik', 'dipinjam', NULL, '2026-06-03 09:24:10'),
	(7, 3, 'FKT-AKS-001', 'Kabel HDMI 3 Meter', 10, 8, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
	(8, 3, 'FKT-AKS-002', 'Kabel VGA', 6, 6, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
	(9, 3, 'FKT-AKS-003', 'Extension Kabel Listrik', 8, 3, 'baik', 'dipinjam', NULL, '2026-06-03 09:24:10'),
	(10, 4, 'FKT-AVS-001', 'Mikrofon Wireless', 4, 2, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10'),
	(11, 4, 'FKT-AVS-002', 'Speaker Portable', 3, 1, 'baik', 'dipinjam', NULL, '2026-06-03 09:24:10'),
	(12, 4, 'FKT-AVS-003', 'Laser Pointer', 5, 5, 'baik', 'tersedia', NULL, '2026-06-03 09:24:10');

-- Dumping structure for table simba.detail_peminjaman
CREATE TABLE IF NOT EXISTS `detail_peminjaman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_peminjaman` int NOT NULL,
  `id_barang` int NOT NULL,
  `jumlah` int NOT NULL,
  `kondisi_saat_pinjam` enum('baik','rusak_ringan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'baik',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `id_peminjaman` (`id_peminjaman`),
  KEY `id_barang` (`id_barang`),
  CONSTRAINT `detail_peminjaman_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `pengajuan_peminjaman` (`id`),
  CONSTRAINT `detail_peminjaman_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table simba.detail_peminjaman: ~44 rows (approximately)
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
	(32, 19, 7, 1, 'baik', NULL),
	(33, 20, 9, 1, 'baik', NULL),
	(34, 21, 7, 1, 'baik', NULL),
	(35, 22, 9, 1, 'baik', NULL),
	(36, 23, 7, 1, 'baik', NULL),
	(37, 24, 9, 1, 'baik', NULL),
	(38, 24, 7, 1, 'baik', NULL),
	(39, 24, 11, 1, 'baik', NULL),
	(40, 25, 7, 1, 'baik', NULL),
	(41, 26, 9, 1, 'baik', NULL),
	(42, 27, 9, 1, 'baik', NULL),
	(43, 27, 7, 1, 'baik', NULL),
	(44, 27, 8, 1, 'baik', NULL),
	(45, 28, 9, 1, 'baik', NULL),
	(46, 28, 7, 1, 'baik', NULL),
	(47, 29, 9, 1, 'baik', NULL),
	(48, 29, 7, 1, 'baik', NULL),
	(49, 29, 8, 1, 'baik', NULL),
	(50, 30, 9, 1, 'baik', NULL);

-- Dumping structure for table simba.kategori
CREATE TABLE IF NOT EXISTS `kategori` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table simba.kategori: ~4 rows (approximately)
INSERT INTO `kategori` (`id`, `nama`, `kode`, `deskripsi`) VALUES
	(1, 'Elektronik', 'ELK', 'Perangkat elektronik seperti proyektor, laptop, dan speaker'),
	(2, 'Furnitur & Mebel', 'FRN', 'Meja, kursi, dan perabot ruangan'),
	(3, 'Aksesoris', 'AKS', 'Kabel, adaptor, dan aksesoris pendukung'),
	(4, 'Audio Visual', 'AVS', 'Mikrofon, sound system, dan perangkat presentasi');

-- Dumping structure for table simba.laporan
CREATE TABLE IF NOT EXISTS `laporan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_laporan` enum('peminjaman','pengembalian','semua','inventaris') COLLATE utf8mb4_general_ci NOT NULL,
  `periode_awal` date NOT NULL,
  `periode_akhir` date NOT NULL,
  `dibuat_oleh` int NOT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `file_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `dibuat_oleh` (`dibuat_oleh`),
  CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table simba.laporan: ~6 rows (approximately)
INSERT INTO `laporan` (`id`, `judul`, `jenis_laporan`, `periode_awal`, `periode_akhir`, `dibuat_oleh`, `catatan`, `file_pdf`, `dibuat_pada`) VALUES
	(3, 'Laporan Juni 2026', 'peminjaman', '2026-06-07', '2026-06-30', 1, NULL, NULL, '2026-06-07 07:34:52'),
	(5, 'Pengembalian mei 2026', 'pengembalian', '2026-05-01', '2026-06-30', 1, NULL, NULL, '2026-06-07 19:20:03'),
	(6, 'Laporan mei - juni', 'semua', '2026-05-01', '2026-06-30', 1, NULL, NULL, '2026-06-07 19:21:15'),
	(7, 'iventaris juni 2026', 'inventaris', '2026-06-01', '2026-06-30', 1, NULL, NULL, '2026-06-07 19:53:56'),
	(8, 'Laporan juni 2026', 'semua', '2026-06-01', '2026-06-30', 1, NULL, NULL, '2026-06-08 12:04:22'),
	(10, 'juni 5-10', 'semua', '2026-06-05', '2026-06-10', 1, NULL, NULL, '2026-06-08 23:37:35'),
	(12, 'Laporan juni', 'semua', '2026-06-01', '2026-06-30', 1, NULL, NULL, '2026-06-14 23:15:01');

-- Dumping structure for table simba.pengajuan_peminjaman
CREATE TABLE IF NOT EXISTS `pengajuan_peminjaman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_peminjam` int NOT NULL,
  `keperluan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `status` enum('menunggu','diverifikasi','disetujui','ditolak','aktif','selesai','dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'menunggu',
  `diverifikasi_oleh` int DEFAULT NULL,
  `disetujui_oleh` int DEFAULT NULL,
  `catatan_tolak` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `file_surat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Untuk event himpunan/peminjaman alat berat',
  PRIMARY KEY (`id`),
  KEY `id_peminjam` (`id_peminjam`),
  KEY `diverifikasi_oleh` (`diverifikasi_oleh`),
  KEY `disetujui_oleh` (`disetujui_oleh`),
  CONSTRAINT `pengajuan_peminjaman_ibfk_1` FOREIGN KEY (`id_peminjam`) REFERENCES `users` (`id`),
  CONSTRAINT `pengajuan_peminjaman_ibfk_2` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id`),
  CONSTRAINT `pengajuan_peminjaman_ibfk_3` FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table simba.pengajuan_peminjaman: ~29 rows (approximately)
INSERT INTO `pengajuan_peminjaman` (`id`, `id_peminjam`, `keperluan`, `tanggal_pinjam`, `tanggal_kembali`, `status`, `diverifikasi_oleh`, `disetujui_oleh`, `catatan_tolak`, `dibuat_pada`, `diperbarui_pada`, `file_surat`) VALUES
	(1, 3, 'Praktikum Jaringan Komputer Semester 4', '2026-05-24', '2026-05-27', 'disetujui', 2, 1, NULL, '2026-06-03 09:24:10', '2026-06-03 09:24:10', NULL),
	(2, 4, 'Acara BEM Universitas 2026', '2026-05-25', '2026-05-28', 'disetujui', 2, 1, NULL, '2026-06-03 09:24:10', '2026-06-04 06:08:18', NULL),
	(3, 5, 'Workshop Sistem Operasi Kelas B', '2026-05-23', '2026-05-24', 'selesai', 2, 1, NULL, '2026-06-03 09:24:10', '2026-06-07 19:18:39', NULL),
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
	(15, 3, 'Seminar', '2026-06-05', '2026-06-06', 'aktif', 2, 2, NULL, '2026-06-05 01:18:36', '2026-06-08 23:27:53', 'uploads/surat/surat_3_20260605011836_aba07b54.pdf'),
	(16, 3, 'Webinar', '2026-06-05', '2026-06-06', 'selesai', 2, 2, NULL, '2026-06-05 01:42:30', '2026-06-08 04:38:14', NULL),
	(17, 3, 'Seminar', '2026-06-05', '2026-06-07', 'selesai', 2, 2, NULL, '2026-06-05 01:50:53', '2026-06-08 04:38:27', NULL),
	(18, 3, 'Diesnat', '2026-06-05', '2026-06-06', 'selesai', 2, 2, NULL, '2026-06-05 02:17:29', '2026-06-08 04:38:22', 'uploads/surat/surat_3_20260605021729_d74f3bd9.pdf'),
	(19, 3, 'kelas', '2026-06-05', '2026-06-06', 'selesai', 2, 2, NULL, '2026-06-05 03:05:28', '2026-06-08 23:37:00', NULL),
	(20, 3, 'Kelas', '2026-06-07', '2026-07-01', 'selesai', 2, 2, NULL, '2026-06-07 07:06:10', '2026-06-08 23:35:12', NULL),
	(21, 8, 'Kelas', '2026-06-07', '2026-06-17', 'selesai', 2, 2, NULL, '2026-06-07 07:37:51', '2026-06-07 19:48:07', NULL),
	(22, 8, 'Kelas', '2026-06-08', '2026-06-09', 'selesai', 2, 2, NULL, '2026-06-07 19:40:00', '2026-06-07 19:48:02', NULL),
	(23, 3, 'Kelas', '2026-06-08', '2026-06-10', 'selesai', 2, 2, NULL, '2026-06-08 04:32:21', '2026-06-08 04:38:32', NULL),
	(24, 3, 'Kelas', '2026-06-08', '2026-06-09', 'selesai', 2, 2, NULL, '2026-06-08 04:33:33', '2026-06-08 04:38:05', 'uploads/surat/surat_3_20260608043333_b9434a3b.pdf'),
	(25, 3, 'Kelas', '2026-06-08', '2026-06-10', 'selesai', 2, 2, NULL, '2026-06-08 12:03:08', '2026-06-08 12:03:44', NULL),
	(26, 3, 'kelas', '2026-06-09', '2026-06-10', 'selesai', 2, 2, NULL, '2026-06-08 23:29:32', '2026-06-08 23:35:08', NULL),
	(27, 3, 'kelas', '2026-06-09', '2026-06-09', 'selesai', 2, 2, NULL, '2026-06-08 23:33:39', '2026-06-08 23:35:00', NULL),
	(28, 8, 'kelas', '2026-06-15', '2026-06-16', 'selesai', NULL, 2, NULL, '2026-06-14 23:11:53', '2026-06-14 23:14:04', NULL),
	(29, 8, 'kelas', '2026-06-15', '2026-06-15', 'selesai', NULL, 2, NULL, '2026-06-14 23:19:45', '2026-06-14 23:20:05', NULL),
	(30, 8, 'kelas', '2026-06-15', '2026-06-15', 'aktif', NULL, 2, NULL, '2026-06-14 23:24:12', '2026-06-14 23:24:58', NULL);

-- Dumping structure for table simba.pengembalian
CREATE TABLE IF NOT EXISTS `pengembalian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_peminjaman` int NOT NULL,
  `diterima_oleh` int NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `hari_terlambat` int DEFAULT '0',
  `kondisi_kembali` enum('baik','rusak_ringan','rusak_berat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `catatan_kerusakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_peminjaman` (`id_peminjaman`),
  KEY `diterima_oleh` (`diterima_oleh`),
  CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `pengajuan_peminjaman` (`id`),
  CONSTRAINT `pengembalian_ibfk_2` FOREIGN KEY (`diterima_oleh`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table simba.pengembalian: ~12 rows (approximately)
INSERT INTO `pengembalian` (`id`, `id_peminjaman`, `diterima_oleh`, `tanggal_kembali`, `hari_terlambat`, `kondisi_kembali`, `catatan_kerusakan`, `dibuat_pada`) VALUES
	(1, 4, 2, '2026-05-22', 0, 'baik', NULL, '2026-06-03 09:24:10'),
	(2, 3, 1, '2026-06-07', 14, 'rusak_ringan', '', '2026-06-07 19:18:39'),
	(3, 22, 2, '2026-06-07', 0, 'baik', '', '2026-06-07 19:48:02'),
	(4, 21, 2, '2026-06-07', 0, 'baik', '', '2026-06-07 19:48:07'),
	(5, 24, 2, '2026-06-08', 0, 'baik', '', '2026-06-08 04:38:05'),
	(6, 16, 2, '2026-06-08', 2, 'baik', '', '2026-06-08 04:38:14'),
	(7, 18, 2, '2026-06-08', 2, 'baik', '', '2026-06-08 04:38:22'),
	(8, 17, 2, '2026-06-08', 1, 'baik', '', '2026-06-08 04:38:27'),
	(9, 23, 2, '2026-06-08', 0, 'baik', '', '2026-06-08 04:38:32'),
	(10, 25, 2, '2026-06-08', 0, 'baik', '', '2026-06-08 12:03:44'),
	(11, 27, 1, '2026-06-08', 0, 'baik', '', '2026-06-08 23:35:00'),
	(12, 26, 1, '2026-06-08', 0, 'baik', '', '2026-06-08 23:35:08'),
	(13, 20, 1, '2026-06-08', 0, 'baik', '', '2026-06-08 23:35:12'),
	(14, 19, 1, '2026-06-08', 2, 'baik', '', '2026-06-08 23:37:00'),
	(15, 28, 2, '2026-06-14', 0, 'baik', '', '2026-06-14 23:14:04'),
	(16, 29, 2, '2026-06-14', 0, 'baik', '', '2026-06-14 23:20:05');

-- Dumping structure for table simba.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_pengguna` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kata_sandi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_lengkap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `peran` enum('admin','staff_tu','peminjam') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_pengguna` (`nama_pengguna`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table simba.users: ~7 rows (approximately)
INSERT INTO `users` (`id`, `nama_pengguna`, `kata_sandi`, `nama_lengkap`, `email`, `peran`, `aktif`, `dibuat_pada`) VALUES
	(1, 'admin', '$2y$10$D2SvnnqoNPMkYtE.I9C7suLnQfOWSvfwNNbPrwuPC7laqq9ZretPu', 'Administrator SIMBA', 'admin@simba.ac.id', 'admin', 1, '2026-06-03 09:24:10'),
	(2, 'staff_tu', '$2y$10$8nkp5MxeuZfYBUQNy4unPec7DonKzDDvlQXg04hPpen6JZzp8TYFC', 'M. Rizky Pratama', 'rizky@simba.ac.id', 'staff_tu', 1, '2026-06-03 09:24:10'),
	(3, 'fahri', '$2y$10$O0YdVluABBXLB7rffHJeOObNffB0dO1nQyA.1naG.69ScjbCl6EKe', 'Maulana Fahri A.', 'fahri@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
	(4, 'sidiq', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sidiq Pramono', 'sidiq@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
	(5, 'ake', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ake Permana', 'ake@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
	(6, 'ajgab', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmad Jabbar', 'ajgab@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
	(7, 'andreas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Andreas Santoso', 'andreas@mhs.ac.id', 'peminjam', 1, '2026-06-03 09:24:10'),
	(8, 'eka', '$2y$10$LuAuk3ycDqXJLcv2x40CyO45G8H5aMmnWX.EHCWDwXpCH78HVvWEm', 'eka', 'eka@simba.ac.id', 'peminjam', 1, '2026-06-07 07:36:56');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
