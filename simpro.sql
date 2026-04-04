-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 04, 2026 at 04:39 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simpro`
--

-- --------------------------------------------------------

--
-- Table structure for table `bahan_baku`
--

CREATE TABLE `bahan_baku` (
  `id_bahan` int NOT NULL,
  `kode_bahan` varchar(30) NOT NULL,
  `nama_bahan` varchar(150) NOT NULL,
  `spesifikasi` text,
  `satuan` varchar(20) NOT NULL,
  `harga_default` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bahan_baku`
--

INSERT INTO `bahan_baku` (`id_bahan`, `kode_bahan`, `nama_bahan`, `spesifikasi`, `satuan`, `harga_default`, `status`, `created_at`) VALUES
(1, 'BHN-001', 'Semen Portland', 'Tipe I, 50 kg/sak', 'Sak', 85000.00, 'aktif', '2026-04-04 13:12:07'),
(2, 'BHN-002', 'Pasir Beton', 'Pasir halus, bersih', 'm3', 250000.00, 'aktif', '2026-04-04 13:12:07'),
(3, 'BHN-003', 'Batu Split 2/3', 'Ukuran 2-3 cm', 'm3', 320000.00, 'aktif', '2026-04-04 13:12:07'),
(4, 'BHN-004', 'Besi Beton Ø10', 'SNI, panjang 12m', 'Batang', 95000.00, 'aktif', '2026-04-04 13:12:07'),
(5, 'BHN-005', 'Besi Beton Ø12', 'SNI, panjang 12m', 'Batang', 138000.00, 'aktif', '2026-04-04 13:12:07'),
(6, 'BHN-006', 'Kayu Bekisting', 'Kayu mahoni 3cm', 'm3', 2500000.00, 'aktif', '2026-04-04 13:12:07'),
(7, 'BHN-007', 'Triplek 12mm', '1.22 x 2.44 m', 'Lembar', 185000.00, 'aktif', '2026-04-04 13:12:07'),
(8, 'BHN-008', 'Cat Tembok Eksterior', 'Anti jamur, 25 kg', 'Kaleng', 450000.00, 'aktif', '2026-04-04 13:12:07'),
(9, 'BHN-009', 'Baut Anchor M12', 'Galvanis, panjang 15cm', 'Pcs', 12000.00, 'aktif', '2026-04-04 13:12:07'),
(10, 'BHN-010', 'Kawat Bendrat', 'Diameter 1 mm, 30kg/rol', 'Roll', 185000.00, 'aktif', '2026-04-04 13:12:07'),
(11, 'BHN-111', 'SEMEN GRESIK', '', 'Sak', 58000.00, 'aktif', '2026-04-04 13:30:14'),
(12, 'BHN-112', 'SEMEN RENDER', '', 'Sak', 76000.00, 'aktif', '2026-04-04 13:30:55'),
(13, 'BHN-113', 'TRIPLEK 6MM', '', 'Lembar', 75000.00, 'aktif', '2026-04-04 13:31:23');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_upah`
--

CREATE TABLE `pembayaran_upah` (
  `id_pembayaran` int NOT NULL,
  `nomor_pembayaran` varchar(50) NOT NULL,
  `id_proyek` int NOT NULL,
  `tanggal_pembayaran` date NOT NULL,
  `periode_dari` date NOT NULL,
  `periode_sampai` date NOT NULL,
  `keterangan` text,
  `total_pembayaran` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status_pembayaran` enum('Draft','Diajukan','Disetujui','Dibayar') NOT NULL DEFAULT 'Draft',
  `id_user_input` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembayaran_upah`
--

INSERT INTO `pembayaran_upah` (`id_pembayaran`, `nomor_pembayaran`, `id_proyek`, `tanggal_pembayaran`, `periode_dari`, `periode_sampai`, `keterangan`, `total_pembayaran`, `status_pembayaran`, `id_user_input`, `created_at`, `updated_at`) VALUES
(1, 'UH-260404-001', 4, '2026-04-04', '2026-04-01', '2026-04-10', 'test 1', 3100000.00, 'Diajukan', 1, '2026-04-04 22:59:46', '2026-04-04 23:09:15');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_upah_detail`
--

CREATE TABLE `pembayaran_upah_detail` (
  `id_detail_upah` int NOT NULL,
  `id_pembayaran` int NOT NULL,
  `nama_pekerja` varchar(150) NOT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `jumlah_hari` decimal(5,2) NOT NULL DEFAULT '0.00',
  `upah_harian` decimal(15,2) NOT NULL DEFAULT '0.00',
  `lembur` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tambahan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `potongan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembayaran_upah_detail`
--

INSERT INTO `pembayaran_upah_detail` (`id_detail_upah`, `id_pembayaran`, `nama_pekerja`, `jabatan`, `jumlah_hari`, `upah_harian`, `lembur`, `tambahan`, `potongan`, `subtotal`) VALUES
(4, 1, 'fenna', 'developer', 10.00, 300000.00, 100000.00, 0.00, 0.00, 3100000.00);

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_upah_log`
--

CREATE TABLE `pembayaran_upah_log` (
  `id_log` int NOT NULL,
  `id_pembayaran` int NOT NULL,
  `tanggal_revisi` datetime DEFAULT CURRENT_TIMESTAMP,
  `nominal_lama` decimal(15,2) DEFAULT NULL,
  `nominal_baru` decimal(15,2) DEFAULT NULL,
  `keterangan_revisi` text,
  `id_user_revisi` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembayaran_upah_log`
--

INSERT INTO `pembayaran_upah_log` (`id_log`, `id_pembayaran`, `tanggal_revisi`, `nominal_lama`, `nominal_baru`, `keterangan_revisi`, `id_user_revisi`) VALUES
(1, 1, '2026-04-04 23:08:14', 13100000.00, 3100000.00, 'revisi', 1),
(2, 1, '2026-04-04 23:09:15', 3100000.00, 3100000.00, 'test 1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `penerimaan`
--

CREATE TABLE `penerimaan` (
  `id_penerimaan` int NOT NULL,
  `nomor_penerimaan` varchar(30) NOT NULL,
  `tanggal_terima` date NOT NULL,
  `id_po` int NOT NULL,
  `id_user` int NOT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penerimaan`
--

INSERT INTO `penerimaan` (`id_penerimaan`, `nomor_penerimaan`, `tanggal_terima`, `id_po`, `id_user`, `keterangan`, `created_at`) VALUES
(1, 'GRN/2026/04/0001', '2026-04-04', 1, 1, '', '2026-04-04 13:16:26'),
(2, 'GRN/2026/04/0002', '2026-04-04', 1, 1, '', '2026-04-04 13:18:22'),
(3, 'GRN/2026/04/0003', '2026-04-04', 2, 1, '', '2026-04-04 13:34:16'),
(4, 'GRN/2026/04/0004', '2026-04-04', 2, 1, '', '2026-04-04 13:34:46'),
(5, 'GRN/2026/04/0005', '2026-04-04', 3, 1, '', '2026-04-04 14:21:00'),
(6, 'GRN/2026/04/0006', '2026-04-04', 1, 1, '', '2026-04-04 14:40:18'),
(7, 'GRN/2026/04/0007', '2026-04-04', 5, 1, '', '2026-04-04 15:03:10'),
(8, 'GRN/2026/04/0008', '2026-04-04', 6, 1, '', '2026-04-04 15:12:25');

-- --------------------------------------------------------

--
-- Table structure for table `penerimaan_detail`
--

CREATE TABLE `penerimaan_detail` (
  `id_penerimaan_detail` int NOT NULL,
  `id_penerimaan` int NOT NULL,
  `id_detail` int NOT NULL,
  `qty_diterima` decimal(12,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penerimaan_detail`
--

INSERT INTO `penerimaan_detail` (`id_penerimaan_detail`, `id_penerimaan`, `id_detail`, `qty_diterima`) VALUES
(1, 2, 1, 5.00),
(2, 2, 2, 5.00),
(3, 3, 3, 5.00),
(4, 3, 4, 25.00),
(5, 3, 5, 1.00),
(6, 4, 3, 10.00),
(7, 4, 4, 20.00),
(8, 5, 9, 5.00),
(9, 5, 10, 10.00),
(10, 5, 11, 1.00),
(11, 6, 1, 5.00),
(12, 6, 2, 5.00),
(13, 7, 14, 5.00),
(14, 8, 16, 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `permintaan_bahan`
--

CREATE TABLE `permintaan_bahan` (
  `id_permintaan` int NOT NULL,
  `nomor_permintaan` varchar(50) NOT NULL,
  `tanggal_permintaan` date NOT NULL,
  `id_proyek` int NOT NULL,
  `id_user` int NOT NULL,
  `tanggal_dibutuhkan` date DEFAULT NULL,
  `status_permintaan` enum('Draft','Diajukan','Disetujui','Ditolak','Diproses ke PO','Terpenuhi Sebagian','Selesai') NOT NULL DEFAULT 'Draft',
  `keterangan` text,
  `catatan_approval` text,
  `id_user_approval` int DEFAULT NULL,
  `tgl_approval` datetime DEFAULT NULL,
  `id_po` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permintaan_bahan`
--

INSERT INTO `permintaan_bahan` (`id_permintaan`, `nomor_permintaan`, `tanggal_permintaan`, `id_proyek`, `id_user`, `tanggal_dibutuhkan`, `status_permintaan`, `keterangan`, `catatan_approval`, `id_user_approval`, `tgl_approval`, `id_po`, `created_at`, `updated_at`) VALUES
(7, 'MR-20260404-001', '2026-04-04', 4, 1, '2026-04-04', 'Diproses ke PO', '', '', 3, '2026-04-04 14:18:49', 3, '2026-04-04 14:16:46', '2026-04-04 14:19:15'),
(8, 'MR-20260404-002', '2026-04-04', 4, 1, '2026-04-05', 'Diproses ke PO', '', '', 1, '2026-04-04 15:00:43', 5, '2026-04-04 15:00:19', '2026-04-04 15:01:02'),
(9, 'MR-20260404-003', '2026-04-04', 4, 1, '2026-04-04', 'Selesai', '', '', 1, '2026-04-04 15:11:44', 6, '2026-04-04 15:11:35', '2026-04-04 15:22:36'),
(10, 'MR-20260404-004', '2026-04-04', 4, 1, '2026-04-04', 'Disetujui', '', '', 1, '2026-04-04 22:26:58', NULL, '2026-04-04 15:26:35', '2026-04-04 15:26:58');

-- --------------------------------------------------------

--
-- Table structure for table `permintaan_bahan_detail`
--

CREATE TABLE `permintaan_bahan_detail` (
  `id_detail_permintaan` int NOT NULL,
  `id_permintaan` int NOT NULL,
  `id_bahan` int NOT NULL,
  `spesifikasi` varchar(255) DEFAULT NULL,
  `qty_diminta` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty_alokasi_material` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty_po` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty_terpenuhi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty_sisa` decimal(12,2) NOT NULL DEFAULT '0.00',
  `satuan` varchar(50) NOT NULL,
  `estimasi_harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `keperluan` varchar(255) DEFAULT NULL,
  `catatan` text,
  `status_item` enum('Proses','Menunggu','Terpenuhi Sebagian','Selesai') NOT NULL DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permintaan_bahan_detail`
--

INSERT INTO `permintaan_bahan_detail` (`id_detail_permintaan`, `id_permintaan`, `id_bahan`, `spesifikasi`, `qty_diminta`, `qty_alokasi_material`, `qty_po`, `qty_terpenuhi`, `qty_sisa`, `satuan`, `estimasi_harga`, `keperluan`, `catatan`, `status_item`) VALUES
(1, 7, 11, '', 10.00, 0.00, 0.00, 0.00, 0.00, 'Sak', 58000.00, '', '', 'Proses'),
(2, 7, 12, '', 20.00, 0.00, 0.00, 0.00, 0.00, 'Sak', 76000.00, '', '', 'Proses'),
(3, 7, 13, '', 1.00, 0.00, 0.00, 0.00, 0.00, 'Lembar', 75000.00, '', '', 'Proses'),
(4, 8, 1, '', 10.00, 0.00, 0.00, 0.00, 0.00, 'Lembar', 75000.00, '', '', 'Proses'),
(5, 9, 3, '', 10.00, 5.00, 5.00, 10.00, 0.00, 'm3', 320000.00, '', '', 'Selesai'),
(6, 10, 3, '', 50.00, 0.00, 0.00, 0.00, 0.00, 'm3', 320000.00, '', '', 'Menunggu');

-- --------------------------------------------------------

--
-- Table structure for table `po`
--

CREATE TABLE `po` (
  `id_po` int NOT NULL,
  `nomor_po` varchar(30) NOT NULL,
  `tanggal_po` date NOT NULL,
  `id_proyek` int NOT NULL,
  `id_supplier` int NOT NULL,
  `id_user` int NOT NULL,
  `status_po` enum('draft','diajukan','disetujui','ditolak','dikirim_sebagian','selesai','direvisi','final','diproses') NOT NULL DEFAULT 'draft',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `po`
--

INSERT INTO `po` (`id_po`, `nomor_po`, `tanggal_po`, `id_proyek`, `id_supplier`, `id_user`, `status_po`, `total`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'PO/2026/04/0001', '2026-04-04', 3, 2, 1, 'selesai', 3320000.00, '', '2026-04-04 13:14:51', '2026-04-04 14:40:18'),
(2, 'PO/2026/04/0002', '2026-04-04', 4, 5, 1, 'selesai', 6635000.00, '', '2026-04-04 13:33:20', '2026-04-04 13:37:16'),
(3, 'PO/2026/04/0003', '2026-04-04', 4, 5, 1, 'selesai', 1050000.00, 'Generate otomatis dari MR: MR-20260404-001\n[REVISI] 04/04/2026 14:52 : ditemukan beberapa stok sisa material yang dapat dialokasikan digudang\n[REVISI] 04/04/2026 14:52 : asd', '2026-04-04 14:19:15', '2026-04-04 14:54:27'),
(4, 'PO/2026/04/0004', '2026-04-04', 4, 2, 1, 'dikirim_sebagian', 0.00, '[REVISI] 04/04/2026 14:50 : ditemukan sisa material digudang', '2026-04-04 14:48:23', '2026-04-04 14:50:47'),
(5, 'PO/2026/04/0005', '2026-04-04', 4, 2, 1, 'selesai', 375000.00, 'Generate otomatis dari MR: MR-20260404-002\n[REVISI] 04/04/2026 15:03 : ada stok 5 digudang', '2026-04-04 15:01:02', '2026-04-04 15:04:59'),
(6, 'PO/2026/04/0006', '2026-04-04', 4, 2, 1, 'selesai', 1600000.00, 'Generate otomatis dari MR: MR-20260404-003\n[REVISI] 04/04/2026 15:12 : ada sisa 5', '2026-04-04 15:11:53', '2026-04-04 15:22:36');

-- --------------------------------------------------------

--
-- Table structure for table `po_detail`
--

CREATE TABLE `po_detail` (
  `id_detail` int NOT NULL,
  `id_po` int NOT NULL,
  `id_bahan` int NOT NULL,
  `qty_pesan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `keterangan` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `po_detail`
--

INSERT INTO `po_detail` (`id_detail`, `id_po`, `id_bahan`, `qty_pesan`, `harga`, `subtotal`, `keterangan`) VALUES
(1, 1, 9, 10.00, 12000.00, 120000.00, ''),
(2, 1, 3, 10.00, 320000.00, 3200000.00, ''),
(3, 2, 11, 45.00, 58000.00, 2610000.00, ''),
(4, 2, 12, 50.00, 76000.00, 3800000.00, ''),
(5, 2, 13, 3.00, 75000.00, 225000.00, ''),
(9, 3, 11, 5.00, 58000.00, 290000.00, ''),
(10, 3, 12, 10.00, 76000.00, 760000.00, ''),
(11, 3, 13, 0.00, 75000.00, 0.00, ''),
(12, 4, 3, 0.00, 320000.00, 0.00, ''),
(14, 5, 1, 5.00, 75000.00, 375000.00, ''),
(16, 6, 3, 5.00, 320000.00, 1600000.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `po_revisi_log`
--

CREATE TABLE `po_revisi_log` (
  `id_revisi` int NOT NULL,
  `id_po` int NOT NULL,
  `id_detail_po` int NOT NULL,
  `qty_diminta` decimal(12,2) NOT NULL,
  `qty_po_awal` decimal(12,2) NOT NULL,
  `qty_material_tersedia` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty_alokasi_material` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty_po_revisi` decimal(12,2) NOT NULL,
  `alasan_revisi` varchar(255) DEFAULT NULL,
  `tanggal_revisi` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_user_revisi` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `po_revisi_log`
--

INSERT INTO `po_revisi_log` (`id_revisi`, `id_po`, `id_detail_po`, `qty_diminta`, `qty_po_awal`, `qty_material_tersedia`, `qty_alokasi_material`, `qty_po_revisi`, `alasan_revisi`, `tanggal_revisi`, `id_user_revisi`) VALUES
(1, 4, 12, 1.00, 1.00, 1.00, 1.00, 0.00, 'ditemukan sisa material digudang', '2026-04-04 21:50:00', 1),
(2, 3, 9, 10.00, 10.00, 5.00, 5.00, 5.00, 'ditemukan beberapa stok sisa material yang dapat dialokasikan digudang', '2026-04-04 21:52:02', 1),
(3, 3, 10, 20.00, 20.00, 5.00, 5.00, 15.00, 'ditemukan beberapa stok sisa material yang dapat dialokasikan digudang', '2026-04-04 21:52:02', 1),
(4, 3, 11, 1.00, 1.00, 1.00, 1.00, 0.00, 'ditemukan beberapa stok sisa material yang dapat dialokasikan digudang', '2026-04-04 21:52:02', 1),
(5, 3, 9, 10.00, 5.00, 5.00, 5.00, 5.00, 'asd', '2026-04-04 21:52:50', 1),
(6, 3, 10, 20.00, 15.00, 10.00, 10.00, 10.00, 'asd', '2026-04-04 21:52:50', 1),
(7, 3, 11, 1.00, 0.00, 1.00, 1.00, 0.00, 'asd', '2026-04-04 21:52:50', 1),
(8, 5, 14, 10.00, 10.00, 5.00, 5.00, 5.00, 'ada stok 5 digudang', '2026-04-04 22:03:33', 1),
(9, 6, 16, 10.00, 10.00, 5.00, 5.00, 5.00, 'ada sisa 5', '2026-04-04 22:12:52', 1);

-- --------------------------------------------------------

--
-- Table structure for table `proyek`
--

CREATE TABLE `proyek` (
  `id_proyek` int NOT NULL,
  `kode_proyek` varchar(30) NOT NULL,
  `nama_proyek` varchar(200) NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` enum('aktif','selesai','ditangguhkan') NOT NULL DEFAULT 'aktif',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `proyek`
--

INSERT INTO `proyek` (`id_proyek`, `kode_proyek`, `nama_proyek`, `lokasi`, `tanggal_mulai`, `tanggal_selesai`, `status`, `keterangan`, `created_at`) VALUES
(1, 'PRY-2026-001', 'Pembangunan Gedung Kantor Pusat', 'Jakarta Pusat', '2026-01-15', '2026-12-31', 'ditangguhkan', '', '2026-04-04 13:12:07'),
(2, 'PRY-2026-002', 'Renovasi Gudang Bekasi', 'Bekasi, Jawa Barat', '2026-02-01', '2026-08-31', 'selesai', '', '2026-04-04 13:12:07'),
(3, 'PRY-2025-003', 'Jembatan Layang Ciawi', 'Bogor, Jawa Barat', '2025-06-01', '2026-06-30', 'selesai', '', '2026-04-04 13:12:07'),
(4, 'PRY-2026-004', 'RENOVASI GUDANG GONDANGMANIS (PASCA KEBAKARAN)', 'GONDANG MANIS', '2025-07-25', '2026-05-31', 'aktif', '', '2026-04-04 13:27:20');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int NOT NULL,
  `nama_supplier` varchar(150) NOT NULL,
  `alamat` text,
  `no_telp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `alamat`, `no_telp`, `email`, `status`, `created_at`) VALUES
(1, 'PT. Semen Indonesia', 'Jl. Veteran No.1, Gresik, Jawa Timur', '031-3981732', 'info@semenindonesia.com', 'aktif', '2026-04-04 13:12:07'),
(2, 'CV. Baja Mandiri', 'Jl. Industri No.45, Bekasi, Jawa Barat', '021-8899001', 'sales@bajamandiri.co.id', 'aktif', '2026-04-04 13:12:07'),
(3, 'UD. Kayu Jati Berkah', 'Jl. Raya Jati No.12, Blora, Jawa Tengah', '0296-521001', 'kayu@jatiberkah.com', 'aktif', '2026-04-04 13:12:07'),
(4, 'PT. Beton Perkasa', 'Jl. Gatot Subroto Km.5, Jakarta Selatan', '021-5254321', 'order@betonperkasa.com', 'aktif', '2026-04-04 13:12:07'),
(5, 'CV. Ika Jaya', '', '', '', 'aktif', '2026-04-04 13:32:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','purchasing','manajer') NOT NULL DEFAULT 'purchasing',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'aktif', '2026-04-04 13:12:07'),
(2, 'Budi Purchasing', 'purchasing', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'purchasing', 'aktif', '2026-04-04 13:12:07'),
(3, 'Sari Manajer', 'manajer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manajer', 'aktif', '2026-04-04 13:12:07'),
(4, 'Della Andini', 'della', '$2y$10$vfHCr.YVSts9yIBX72nnCeJ5oURgkJccn65RgfWR7zjrhCrC2cRVu', 'admin', 'aktif', '2026-04-04 15:46:18');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_po_item_status`
-- (See below for the actual view)
--
CREATE TABLE `v_po_item_status` (
`id_detail` int
,`id_po` int
,`id_bahan` int
,`kode_bahan` varchar(30)
,`nama_bahan` varchar(150)
,`satuan` varchar(20)
,`qty_pesan` decimal(12,2)
,`harga` decimal(15,2)
,`subtotal` decimal(18,2)
,`ket_item` text
,`total_qty_diterima` decimal(34,2)
,`qty_sisa` decimal(35,2)
,`status_item` varchar(17)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_po_status_penerimaan`
-- (See below for the actual view)
--
CREATE TABLE `v_po_status_penerimaan` (
`id_po` int
,`nomor_po` varchar(30)
,`total_item` bigint
,`item_belum` decimal(23,0)
,`item_sebagian` decimal(23,0)
,`item_selesai` decimal(23,0)
,`status_penerimaan` varchar(16)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bahan_baku`
--
ALTER TABLE `bahan_baku`
  ADD PRIMARY KEY (`id_bahan`),
  ADD UNIQUE KEY `kode_bahan` (`kode_bahan`);

--
-- Indexes for table `pembayaran_upah`
--
ALTER TABLE `pembayaran_upah`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD UNIQUE KEY `nomor_pembayaran` (`nomor_pembayaran`),
  ADD KEY `id_proyek` (`id_proyek`),
  ADD KEY `id_user_input` (`id_user_input`);

--
-- Indexes for table `pembayaran_upah_detail`
--
ALTER TABLE `pembayaran_upah_detail`
  ADD PRIMARY KEY (`id_detail_upah`),
  ADD KEY `id_pembayaran` (`id_pembayaran`);

--
-- Indexes for table `pembayaran_upah_log`
--
ALTER TABLE `pembayaran_upah_log`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_pembayaran` (`id_pembayaran`);

--
-- Indexes for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD PRIMARY KEY (`id_penerimaan`),
  ADD UNIQUE KEY `nomor_penerimaan` (`nomor_penerimaan`),
  ADD KEY `id_po` (`id_po`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `penerimaan_detail`
--
ALTER TABLE `penerimaan_detail`
  ADD PRIMARY KEY (`id_penerimaan_detail`),
  ADD KEY `id_penerimaan` (`id_penerimaan`),
  ADD KEY `id_detail` (`id_detail`);

--
-- Indexes for table `permintaan_bahan`
--
ALTER TABLE `permintaan_bahan`
  ADD PRIMARY KEY (`id_permintaan`),
  ADD UNIQUE KEY `nomor_permintaan` (`nomor_permintaan`),
  ADD KEY `id_proyek` (`id_proyek`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_user_approval` (`id_user_approval`),
  ADD KEY `id_po` (`id_po`);

--
-- Indexes for table `permintaan_bahan_detail`
--
ALTER TABLE `permintaan_bahan_detail`
  ADD PRIMARY KEY (`id_detail_permintaan`),
  ADD KEY `id_permintaan` (`id_permintaan`),
  ADD KEY `id_bahan` (`id_bahan`);

--
-- Indexes for table `po`
--
ALTER TABLE `po`
  ADD PRIMARY KEY (`id_po`),
  ADD UNIQUE KEY `nomor_po` (`nomor_po`),
  ADD KEY `id_proyek` (`id_proyek`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `po_detail`
--
ALTER TABLE `po_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_po` (`id_po`),
  ADD KEY `id_bahan` (`id_bahan`);

--
-- Indexes for table `po_revisi_log`
--
ALTER TABLE `po_revisi_log`
  ADD PRIMARY KEY (`id_revisi`),
  ADD KEY `id_po` (`id_po`),
  ADD KEY `id_detail_po` (`id_detail_po`),
  ADD KEY `id_user_revisi` (`id_user_revisi`);

--
-- Indexes for table `proyek`
--
ALTER TABLE `proyek`
  ADD PRIMARY KEY (`id_proyek`),
  ADD UNIQUE KEY `kode_proyek` (`kode_proyek`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bahan_baku`
--
ALTER TABLE `bahan_baku`
  MODIFY `id_bahan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `pembayaran_upah`
--
ALTER TABLE `pembayaran_upah`
  MODIFY `id_pembayaran` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pembayaran_upah_detail`
--
ALTER TABLE `pembayaran_upah_detail`
  MODIFY `id_detail_upah` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pembayaran_upah_log`
--
ALTER TABLE `pembayaran_upah_log`
  MODIFY `id_log` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penerimaan`
--
ALTER TABLE `penerimaan`
  MODIFY `id_penerimaan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `penerimaan_detail`
--
ALTER TABLE `penerimaan_detail`
  MODIFY `id_penerimaan_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `permintaan_bahan`
--
ALTER TABLE `permintaan_bahan`
  MODIFY `id_permintaan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `permintaan_bahan_detail`
--
ALTER TABLE `permintaan_bahan_detail`
  MODIFY `id_detail_permintaan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `po`
--
ALTER TABLE `po`
  MODIFY `id_po` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `po_detail`
--
ALTER TABLE `po_detail`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `po_revisi_log`
--
ALTER TABLE `po_revisi_log`
  MODIFY `id_revisi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `proyek`
--
ALTER TABLE `proyek`
  MODIFY `id_proyek` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- --------------------------------------------------------

--
-- Structure for view `v_po_item_status`
--
DROP TABLE IF EXISTS `v_po_item_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_po_item_status`  AS SELECT `d`.`id_detail` AS `id_detail`, `d`.`id_po` AS `id_po`, `d`.`id_bahan` AS `id_bahan`, `b`.`kode_bahan` AS `kode_bahan`, `b`.`nama_bahan` AS `nama_bahan`, `b`.`satuan` AS `satuan`, `d`.`qty_pesan` AS `qty_pesan`, `d`.`harga` AS `harga`, `d`.`subtotal` AS `subtotal`, `d`.`keterangan` AS `ket_item`, coalesce(sum(`pd`.`qty_diterima`),0) AS `total_qty_diterima`, (`d`.`qty_pesan` - coalesce(sum(`pd`.`qty_diterima`),0)) AS `qty_sisa`, (case when (coalesce(sum(`pd`.`qty_diterima`),0) = 0) then 'Belum diterima' when (coalesce(sum(`pd`.`qty_diterima`),0) >= `d`.`qty_pesan`) then 'Selesai' else 'Diterima sebagian' end) AS `status_item` FROM ((`po_detail` `d` left join `bahan_baku` `b` on((`b`.`id_bahan` = `d`.`id_bahan`))) left join `penerimaan_detail` `pd` on((`pd`.`id_detail` = `d`.`id_detail`))) GROUP BY `d`.`id_detail` ;

-- --------------------------------------------------------

--
-- Structure for view `v_po_status_penerimaan`
--
DROP TABLE IF EXISTS `v_po_status_penerimaan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_po_status_penerimaan`  AS SELECT `p`.`id_po` AS `id_po`, `p`.`nomor_po` AS `nomor_po`, count(`d`.`id_detail`) AS `total_item`, sum((case when (coalesce(`rcv`.`total_terima`,0) = 0) then 1 else 0 end)) AS `item_belum`, sum((case when ((coalesce(`rcv`.`total_terima`,0) > 0) and (coalesce(`rcv`.`total_terima`,0) < `d`.`qty_pesan`)) then 1 else 0 end)) AS `item_sebagian`, sum((case when (coalesce(`rcv`.`total_terima`,0) >= `d`.`qty_pesan`) then 1 else 0 end)) AS `item_selesai`, (case when (sum((case when (coalesce(`rcv`.`total_terima`,0) >= `d`.`qty_pesan`) then 1 else 0 end)) = count(`d`.`id_detail`)) then 'selesai' when (sum((case when (coalesce(`rcv`.`total_terima`,0) = 0) then 1 else 0 end)) = count(`d`.`id_detail`)) then 'belum_diterima' else 'dikirim_sebagian' end) AS `status_penerimaan` FROM ((`po` `p` left join `po_detail` `d` on((`d`.`id_po` = `p`.`id_po`))) left join (select `penerimaan_detail`.`id_detail` AS `id_detail`,sum(`penerimaan_detail`.`qty_diterima`) AS `total_terima` from `penerimaan_detail` group by `penerimaan_detail`.`id_detail`) `rcv` on((`rcv`.`id_detail` = `d`.`id_detail`))) GROUP BY `p`.`id_po` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pembayaran_upah`
--
ALTER TABLE `pembayaran_upah`
  ADD CONSTRAINT `pembayaran_upah_ibfk_1` FOREIGN KEY (`id_proyek`) REFERENCES `proyek` (`id_proyek`) ON DELETE CASCADE,
  ADD CONSTRAINT `pembayaran_upah_ibfk_2` FOREIGN KEY (`id_user_input`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT;

--
-- Constraints for table `pembayaran_upah_detail`
--
ALTER TABLE `pembayaran_upah_detail`
  ADD CONSTRAINT `pembayaran_upah_detail_ibfk_1` FOREIGN KEY (`id_pembayaran`) REFERENCES `pembayaran_upah` (`id_pembayaran`) ON DELETE CASCADE;

--
-- Constraints for table `pembayaran_upah_log`
--
ALTER TABLE `pembayaran_upah_log`
  ADD CONSTRAINT `pembayaran_upah_log_ibfk_1` FOREIGN KEY (`id_pembayaran`) REFERENCES `pembayaran_upah` (`id_pembayaran`) ON DELETE CASCADE;

--
-- Constraints for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD CONSTRAINT `penerimaan_ibfk_1` FOREIGN KEY (`id_po`) REFERENCES `po` (`id_po`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `penerimaan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `penerimaan_detail`
--
ALTER TABLE `penerimaan_detail`
  ADD CONSTRAINT `penerimaan_detail_ibfk_1` FOREIGN KEY (`id_penerimaan`) REFERENCES `penerimaan` (`id_penerimaan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `penerimaan_detail_ibfk_2` FOREIGN KEY (`id_detail`) REFERENCES `po_detail` (`id_detail`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `permintaan_bahan`
--
ALTER TABLE `permintaan_bahan`
  ADD CONSTRAINT `permintaan_bahan_ibfk_1` FOREIGN KEY (`id_proyek`) REFERENCES `proyek` (`id_proyek`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `permintaan_bahan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `permintaan_bahan_ibfk_3` FOREIGN KEY (`id_user_approval`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `permintaan_bahan_ibfk_4` FOREIGN KEY (`id_po`) REFERENCES `po` (`id_po`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `permintaan_bahan_detail`
--
ALTER TABLE `permintaan_bahan_detail`
  ADD CONSTRAINT `permintaan_bahan_detail_ibfk_1` FOREIGN KEY (`id_permintaan`) REFERENCES `permintaan_bahan` (`id_permintaan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permintaan_bahan_detail_ibfk_2` FOREIGN KEY (`id_bahan`) REFERENCES `bahan_baku` (`id_bahan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `po`
--
ALTER TABLE `po`
  ADD CONSTRAINT `po_ibfk_1` FOREIGN KEY (`id_proyek`) REFERENCES `proyek` (`id_proyek`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `po_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `po_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `po_detail`
--
ALTER TABLE `po_detail`
  ADD CONSTRAINT `po_detail_ibfk_1` FOREIGN KEY (`id_po`) REFERENCES `po` (`id_po`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `po_detail_ibfk_2` FOREIGN KEY (`id_bahan`) REFERENCES `bahan_baku` (`id_bahan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `po_revisi_log`
--
ALTER TABLE `po_revisi_log`
  ADD CONSTRAINT `po_revisi_log_ibfk_1` FOREIGN KEY (`id_po`) REFERENCES `po` (`id_po`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_revisi_log_ibfk_2` FOREIGN KEY (`id_detail_po`) REFERENCES `po_detail` (`id_detail`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_revisi_log_ibfk_3` FOREIGN KEY (`id_user_revisi`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
