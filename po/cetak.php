<?php
/**
 * PO: cetak.php — Print-friendly PO
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$id = (int)($_GET['id'] ?? 0);
$po = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT p.*, pr.nama_proyek, pr.kode_proyek, pr.lokasi,
            s.nama_supplier, s.alamat as alamat_supplier, s.no_telp, s.email,
            u.nama as nama_user
     FROM po p
     LEFT JOIN proyek pr ON pr.id_proyek = p.id_proyek
     LEFT JOIN supplier s ON s.id_supplier = p.id_supplier
     LEFT JOIN users u ON u.id_user = p.id_user
     WHERE p.id_po = $id LIMIT 1"));
if (!$po) die('PO tidak ditemukan.');

$items = mysqli_query($koneksi,
    "SELECT d.*, b.kode_bahan, b.nama_bahan, b.satuan
     FROM po_detail d
     LEFT JOIN bahan_baku b ON b.id_bahan = d.id_bahan
     WHERE d.id_po = $id ORDER BY d.id_detail");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak PO <?= e($po['nomor_po']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 20px; }
        .header-po { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .company-name { font-size: 20px; font-weight: bold; color: #1a56db; }
        .badge-status { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; background: #e2e8f0; }
        .po-number { font-size: 16px; font-weight: bold; margin: 10px 0 4px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-box { border: 1px solid #ddd; border-radius: 6px; padding: 12px; }
        .info-box h6 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; color: #64748b; }
        .info-row { display: flex; margin-bottom: 4px; }
        .info-label { width: 110px; color: #666; flex-shrink: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f1f5f9; padding: 8px; text-align: left; border: 1px solid #ddd; font-size: 11px; }
        td { padding: 7px 8px; border: 1px solid #ddd; vertical-align: top; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; background: #f8fafc; }
        .sign-section { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 30px; }
        .sign-box { text-align: center; border: 1px solid #ddd; border-radius: 6px; padding: 12px; }
        .sign-box .sign-line { border-top: 1px solid #aaa; margin-top: 60px; padding-top: 6px; }
        @media print {
            body { margin: 0; padding: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="no-print" style="margin-bottom:15px">
    <button onclick="window.print()" style="background:#1a56db;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px">
        🖨️ Cetak / Simpan PDF
    </button>
    <a href="detail.php?id=<?= $id ?>" style="margin-left:8px;color:#666;text-decoration:none;font-size:13px">← Kembali</a>
</div>

<div class="header-po">
    <div>
        <div class="company-name">🏗️ SIMPRO</div>
        <div style="color:#666;font-size:11px">Sistem Informasi Manajemen Proyek</div>
    </div>
    <div style="text-align:right">
        <div class="po-number">PURCHASE ORDER</div>
        <div class="po-number" style="color:#1a56db"><?= e($po['nomor_po']) ?></div>
        <div class="badge-status"><?= strtoupper(str_replace('_',' ',$po['status_po'])) ?></div>
    </div>
</div>

<div class="info-grid">
    <div class="info-box">
        <h6>Informasi PO</h6>
        <div class="info-row"><span class="info-label">Tanggal PO</span><strong><?= tgl_indo($po['tanggal_po']) ?></strong></div>
        <div class="info-row"><span class="info-label">Proyek</span><?= e($po['nama_proyek']) ?></div>
        <div class="info-row"><span class="info-label">Kode Proyek</span><?= e($po['kode_proyek']) ?></div>
        <div class="info-row"><span class="info-label">Lokasi</span><?= e($po['lokasi']) ?></div>
        <div class="info-row"><span class="info-label">Dibuat Oleh</span><?= e($po['nama_user']) ?></div>
    </div>
    <div class="info-box">
        <h6>Data Supplier</h6>
        <div class="info-row"><span class="info-label">Nama</span><strong><?= e($po['nama_supplier']) ?></strong></div>
        <div class="info-row"><span class="info-label">Alamat</span><?= e($po['alamat_supplier']) ?></div>
        <div class="info-row"><span class="info-label">Telepon</span><?= e($po['no_telp']) ?></div>
        <div class="info-row"><span class="info-label">Email</span><?= e($po['email']) ?></div>
    </div>
</div>

<?php if ($po['keterangan']): ?>
<div style="margin-bottom:15px;padding:8px 12px;background:#f8fafc;border-left:3px solid #1a56db;border-radius:4px">
    <strong>Keterangan:</strong> <?= e($po['keterangan']) ?>
</div>
<?php endif; ?>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Kode</th>
        <th>Nama Bahan</th>
        <th>Satuan</th>
        <th class="text-right">Qty Pesan</th>
        <th class="text-right">Harga</th>
        <th class="text-right">Subtotal</th>
        <th>Keterangan</th>
    </tr>
    </thead>
    <tbody>
    <?php $no = 1; while ($item = mysqli_fetch_assoc($items)): ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= e($item['kode_bahan']) ?></td>
        <td><?= e($item['nama_bahan']) ?></td>
        <td><?= e($item['satuan']) ?></td>
        <td class="text-right"><?= number_format($item['qty_pesan'],2,',','.') ?></td>
        <td class="text-right"><?= rupiah($item['harga']) ?></td>
        <td class="text-right"><?= rupiah($item['subtotal']) ?></td>
        <td><?= e($item['keterangan']) ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td colspan="6" class="text-right">TOTAL</td>
        <td class="text-right" style="color:#1a56db"><?= rupiah($po['total']) ?></td>
        <td></td>
    </tr>
    </tfoot>
</table>

<div class="sign-section">
    <div class="sign-box">
        <div>Dibuat Oleh</div>
        <div class="sign-line"><?= e($po['nama_user']) ?></div>
    </div>
    <div class="sign-box">
        <div>Disetujui Oleh</div>
        <div class="sign-line">Manajer Proyek</div>
    </div>
    <div class="sign-box">
        <div>Diterima Oleh</div>
        <div class="sign-line">Supplier</div>
    </div>
</div>

<div style="text-align:center;margin-top:20px;color:#999;font-size:10px">
    Dicetak pada: <?= date('d/m/Y H:i') ?> &mdash; SIMPRO &copy; <?= date('Y') ?>
</div>
</body>
</html>
