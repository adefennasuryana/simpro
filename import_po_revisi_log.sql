-- ============================================================
-- SQL Import: Tambahan Fitur Revisi PO
-- Silakan import file ini melalui menu "Import" di phpMyAdmin
-- ============================================================

USE simpro;

-- 1. Tambah status baru pada ENUM tabel PO agar kita bisa membedakan PO yang telah direvisi
ALTER TABLE po MODIFY status_po 
    ENUM('draft','diajukan','disetujui','ditolak','dikirim_sebagian','selesai','direvisi','final','diproses') 
    NOT NULL DEFAULT 'draft';

-- 2. Buat tabel Log Revisi (po_revisi_log)
CREATE TABLE IF NOT EXISTS po_revisi_log (
  id_revisi INT AUTO_INCREMENT PRIMARY KEY,
  id_po INT NOT NULL,
  id_detail_po INT NOT NULL,
  qty_diminta DECIMAL(12,2) NOT NULL,
  qty_po_awal DECIMAL(12,2) NOT NULL,
  qty_material_tersedia DECIMAL(12,2) NOT NULL DEFAULT 0,
  qty_alokasi_material DECIMAL(12,2) NOT NULL DEFAULT 0,
  qty_po_revisi DECIMAL(12,2) NOT NULL,
  alasan_revisi VARCHAR(255),
  tanggal_revisi DATETIME DEFAULT CURRENT_TIMESTAMP,
  id_user_revisi INT NOT NULL,
  FOREIGN KEY (id_po) REFERENCES po(id_po) ON DELETE CASCADE,
  FOREIGN KEY (id_detail_po) REFERENCES po_detail(id_detail) ON DELETE CASCADE,
  FOREIGN KEY (id_user_revisi) REFERENCES users(id_user) ON DELETE RESTRICT
);
