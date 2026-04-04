-- ============================================
-- SIMPRO - Sistem Informasi Manajemen Proyek
-- Database: simpro
-- Created: 2026-04-04
-- ============================================

CREATE DATABASE IF NOT EXISTS simpro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE simpro;

-- ============================================
-- Tabel: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
  id_user     INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','purchasing','manajer') NOT NULL DEFAULT 'purchasing',
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Tabel: supplier
-- ============================================
CREATE TABLE IF NOT EXISTS supplier (
  id_supplier    INT AUTO_INCREMENT PRIMARY KEY,
  nama_supplier  VARCHAR(150) NOT NULL,
  alamat         TEXT,
  no_telp        VARCHAR(20),
  email          VARCHAR(100),
  status         ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Tabel: proyek
-- ============================================
CREATE TABLE IF NOT EXISTS proyek (
  id_proyek       INT AUTO_INCREMENT PRIMARY KEY,
  kode_proyek     VARCHAR(30) NOT NULL UNIQUE,
  nama_proyek     VARCHAR(200) NOT NULL,
  lokasi          VARCHAR(255),
  tanggal_mulai   DATE,
  tanggal_selesai DATE,
  status          ENUM('aktif','selesai','ditangguhkan') NOT NULL DEFAULT 'aktif',
  keterangan      TEXT,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Tabel: bahan_baku
-- ============================================
CREATE TABLE IF NOT EXISTS bahan_baku (
  id_bahan      INT AUTO_INCREMENT PRIMARY KEY,
  kode_bahan    VARCHAR(30) NOT NULL UNIQUE,
  nama_bahan    VARCHAR(150) NOT NULL,
  spesifikasi   TEXT,
  satuan        VARCHAR(20) NOT NULL,
  harga_default DECIMAL(15,2) NOT NULL DEFAULT 0,
  status        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Tabel: po (Purchase Order Header)
-- ============================================
CREATE TABLE IF NOT EXISTS po (
  id_po        INT AUTO_INCREMENT PRIMARY KEY,
  nomor_po     VARCHAR(30) NOT NULL UNIQUE,
  tanggal_po   DATE NOT NULL,
  id_proyek    INT NOT NULL,
  id_supplier  INT NOT NULL,
  id_user      INT NOT NULL,
  status_po    ENUM('draft','diajukan','disetujui','ditolak','dikirim_sebagian','selesai') NOT NULL DEFAULT 'draft',
  total        DECIMAL(18,2) NOT NULL DEFAULT 0,
  keterangan   TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_proyek)   REFERENCES proyek(id_proyek)   ON UPDATE CASCADE ON DELETE RESTRICT,
  FOREIGN KEY (id_supplier) REFERENCES supplier(id_supplier) ON UPDATE CASCADE ON DELETE RESTRICT,
  FOREIGN KEY (id_user)     REFERENCES users(id_user)       ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- Tabel: po_detail (Purchase Order Detail)
-- ============================================
CREATE TABLE IF NOT EXISTS po_detail (
  id_detail   INT AUTO_INCREMENT PRIMARY KEY,
  id_po       INT NOT NULL,
  id_bahan    INT NOT NULL,
  qty_pesan   DECIMAL(12,2) NOT NULL DEFAULT 0,
  harga       DECIMAL(15,2) NOT NULL DEFAULT 0,
  subtotal    DECIMAL(18,2) NOT NULL DEFAULT 0,
  keterangan  TEXT,
  FOREIGN KEY (id_po)    REFERENCES po(id_po)           ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_bahan) REFERENCES bahan_baku(id_bahan) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- Tabel: penerimaan (Goods Receipt Header)
-- ============================================
CREATE TABLE IF NOT EXISTS penerimaan (
  id_penerimaan    INT AUTO_INCREMENT PRIMARY KEY,
  nomor_penerimaan VARCHAR(30) NOT NULL UNIQUE,
  tanggal_terima   DATE NOT NULL,
  id_po            INT NOT NULL,
  id_user          INT NOT NULL,
  keterangan       TEXT,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_po)   REFERENCES po(id_po)       ON UPDATE CASCADE ON DELETE RESTRICT,
  FOREIGN KEY (id_user) REFERENCES users(id_user)  ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- Tabel: penerimaan_detail (Goods Receipt Detail)
-- ============================================
CREATE TABLE IF NOT EXISTS penerimaan_detail (
  id_penerimaan_detail INT AUTO_INCREMENT PRIMARY KEY,
  id_penerimaan        INT NOT NULL,
  id_detail            INT NOT NULL,
  qty_diterima         DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (id_penerimaan) REFERENCES penerimaan(id_penerimaan) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_detail)     REFERENCES po_detail(id_detail)       ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- VIEW: v_po_item_status
-- Menghitung total diterima dan sisa per item PO
-- ============================================
CREATE OR REPLACE VIEW v_po_item_status AS
SELECT
  d.id_detail,
  d.id_po,
  d.id_bahan,
  b.kode_bahan,
  b.nama_bahan,
  b.satuan,
  d.qty_pesan,
  d.harga,
  d.subtotal,
  d.keterangan        AS ket_item,
  COALESCE(SUM(pd.qty_diterima), 0)                          AS total_qty_diterima,
  d.qty_pesan - COALESCE(SUM(pd.qty_diterima), 0)            AS qty_sisa,
  CASE
    WHEN COALESCE(SUM(pd.qty_diterima), 0) = 0
      THEN 'Belum diterima'
    WHEN COALESCE(SUM(pd.qty_diterima), 0) >= d.qty_pesan
      THEN 'Selesai'
    ELSE 'Diterima sebagian'
  END AS status_item
FROM po_detail d
LEFT JOIN bahan_baku b ON b.id_bahan = d.id_bahan
LEFT JOIN penerimaan_detail pd ON pd.id_detail = d.id_detail
GROUP BY d.id_detail;

-- ============================================
-- VIEW: v_po_status_penerimaan
-- Status penerimaan otomatis per PO
-- ============================================
CREATE OR REPLACE VIEW v_po_status_penerimaan AS
SELECT
  p.id_po,
  p.nomor_po,
  COUNT(d.id_detail) AS total_item,
  SUM(CASE WHEN COALESCE(rcv.total_terima, 0) = 0 THEN 1 ELSE 0 END)               AS item_belum,
  SUM(CASE WHEN COALESCE(rcv.total_terima, 0) > 0
            AND COALESCE(rcv.total_terima, 0) < d.qty_pesan THEN 1 ELSE 0 END)      AS item_sebagian,
  SUM(CASE WHEN COALESCE(rcv.total_terima, 0) >= d.qty_pesan THEN 1 ELSE 0 END)    AS item_selesai,
  CASE
    WHEN SUM(CASE WHEN COALESCE(rcv.total_terima,0) >= d.qty_pesan THEN 1 ELSE 0 END) = COUNT(d.id_detail)
      THEN 'selesai'
    WHEN SUM(CASE WHEN COALESCE(rcv.total_terima,0) = 0 THEN 1 ELSE 0 END) = COUNT(d.id_detail)
      THEN 'belum_diterima'
    ELSE 'dikirim_sebagian'
  END AS status_penerimaan
FROM po p
LEFT JOIN po_detail d ON d.id_po = p.id_po
LEFT JOIN (
  SELECT id_detail, SUM(qty_diterima) AS total_terima
  FROM penerimaan_detail
  GROUP BY id_detail
) rcv ON rcv.id_detail = d.id_detail
GROUP BY p.id_po;

-- ============================================
-- Data Awal: Users
-- Password: admin123 (bcrypt hash)
-- ============================================
INSERT INTO users (nama, username, password, role, status) VALUES
('Administrator', 'admin',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',      'aktif'),
('Budi Purchasing', 'purchasing', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'purchasing', 'aktif'),
('Sari Manajer',    'manajer',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manajer',    'aktif');

-- ============================================
-- Data Awal: Supplier
-- ============================================
INSERT INTO supplier (nama_supplier, alamat, no_telp, email) VALUES
('PT. Semen Indonesia',    'Jl. Veteran No.1, Gresik, Jawa Timur',          '031-3981732', 'info@semenindonesia.com'),
('CV. Baja Mandiri',       'Jl. Industri No.45, Bekasi, Jawa Barat',         '021-8899001', 'sales@bajamandiri.co.id'),
('UD. Kayu Jati Berkah',   'Jl. Raya Jati No.12, Blora, Jawa Tengah',        '0296-521001', 'kayu@jatiberkah.com'),
('PT. Beton Perkasa',      'Jl. Gatot Subroto Km.5, Jakarta Selatan',        '021-5254321', 'order@betonperkasa.com');

-- ============================================
-- Data Awal: Proyek
-- ============================================
INSERT INTO proyek (kode_proyek, nama_proyek, lokasi, tanggal_mulai, tanggal_selesai, status) VALUES
('PRY-2026-001', 'Pembangunan Gedung Kantor Pusat',   'Jakarta Pusat',     '2026-01-15', '2026-12-31', 'aktif'),
('PRY-2026-002', 'Renovasi Gudang Bekasi',             'Bekasi, Jawa Barat','2026-02-01', '2026-08-31', 'aktif'),
('PRY-2025-003', 'Jembatan Layang Ciawi',              'Bogor, Jawa Barat', '2025-06-01', '2026-06-30', 'aktif');

-- ============================================
-- Data Awal: Bahan Baku
-- ============================================
INSERT INTO bahan_baku (kode_bahan, nama_bahan, spesifikasi, satuan, harga_default) VALUES
('BHN-001', 'Semen Portland',      'Tipe I, 50 kg/sak',      'Sak',   85000),
('BHN-002', 'Pasir Beton',         'Pasir halus, bersih',    'm3',    250000),
('BHN-003', 'Batu Split 2/3',      'Ukuran 2-3 cm',          'm3',    320000),
('BHN-004', 'Besi Beton Ø10',      'SNI, panjang 12m',       'Batang', 95000),
('BHN-005', 'Besi Beton Ø12',      'SNI, panjang 12m',       'Batang',138000),
('BHN-006', 'Kayu Bekisting',      'Kayu mahoni 3cm',        'm3',    2500000),
('BHN-007', 'Triplek 12mm',        '1.22 x 2.44 m',          'Lembar', 185000),
('BHN-008', 'Cat Tembok Eksterior','Anti jamur, 25 kg',      'Kaleng', 450000),
('BHN-009', 'Baut Anchor M12',     'Galvanis, panjang 15cm', 'Pcs',    12000),
('BHN-010', 'Kawat Bendrat',       'Diameter 1 mm, 30kg/rol','Roll',   185000);
