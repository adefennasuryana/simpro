-- ============================================================
-- SIMPRO - Modul Permintaan Bahan Baku (Material Request)
-- Tambahkan ke database: simpro
-- ============================================================

USE simpro;

-- ============================================================
-- Tabel: permintaan_bahan (Header)
-- ============================================================
CREATE TABLE IF NOT EXISTS permintaan_bahan (
  id_permintaan      INT AUTO_INCREMENT PRIMARY KEY,
  nomor_permintaan   VARCHAR(50)  NOT NULL UNIQUE,
  tanggal_permintaan DATE         NOT NULL,
  id_proyek          INT          NOT NULL,
  id_user            INT          NOT NULL,
  tanggal_dibutuhkan DATE,
  status_permintaan  ENUM('Draft','Diajukan','Disetujui','Ditolak','Diproses ke PO')
                     NOT NULL DEFAULT 'Draft',
  keterangan         TEXT,
  catatan_approval   TEXT,
  id_user_approval   INT,
  tgl_approval       DATETIME,
  id_po              INT          DEFAULT NULL,
  created_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_proyek)       REFERENCES proyek(id_proyek)   ON UPDATE CASCADE ON DELETE RESTRICT,
  FOREIGN KEY (id_user)         REFERENCES users(id_user)       ON UPDATE CASCADE ON DELETE RESTRICT,
  FOREIGN KEY (id_user_approval) REFERENCES users(id_user)      ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (id_po)           REFERENCES po(id_po)            ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Tabel: permintaan_bahan_detail (Item)
-- ============================================================
CREATE TABLE IF NOT EXISTS permintaan_bahan_detail (
  id_detail_permintaan INT AUTO_INCREMENT PRIMARY KEY,
  id_permintaan        INT            NOT NULL,
  id_bahan             INT            NOT NULL,
  spesifikasi          VARCHAR(255),
  qty_diminta          DECIMAL(12,2)  NOT NULL DEFAULT 0,
  satuan               VARCHAR(50)    NOT NULL,
  estimasi_harga       DECIMAL(15,2)  NOT NULL DEFAULT 0,
  keperluan            VARCHAR(255),
  catatan              TEXT,
  FOREIGN KEY (id_permintaan) REFERENCES permintaan_bahan(id_permintaan) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_bahan)      REFERENCES bahan_baku(id_bahan)             ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
