<?php
/**
 * Penerimaan: detail.php — Detail Penerimaan Barang
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$id  = (int)($_GET['id'] ?? 0);
$pnm = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT pn.*, p.nomor_po, p.id_po, pr.nama_proyek, s.nama_supplier, u.nama as nama_user
     FROM penerimaan pn
     LEFT JOIN po p ON p.id_po = pn.id_po
     LEFT JOIN proyek pr ON pr.id_proyek = p.id_proyek
     LEFT JOIN supplier s ON s.id_supplier = p.id_supplier
     LEFT JOIN users u ON u.id_user = pn.id_user
     WHERE pn.id_penerimaan = $id LIMIT 1"));
if (!$pnm) { set_flash('danger','Data tidak ditemukan.'); redirect(BASE_URL.'/penerimaan/index.php'); }

$pageTitle = 'Detail Penerimaan: ' . $pnm['nomor_penerimaan'];

$items = mysqli_query($koneksi,
    "SELECT pd.qty_diterima, d.qty_pesan, b.kode_bahan, b.nama_bahan, b.satuan
     FROM penerimaan_detail pd
     LEFT JOIN po_detail d ON d.id_detail = pd.id_detail
     LEFT JOIN bahan_baku b ON b.id_bahan = d.id_bahan
     WHERE pd.id_penerimaan = $id");

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-box-open me-2 text-primary"></i><?= e($pnm['nomor_penerimaan']) ?></h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Penerimaan</a></li>
            <li class="breadcrumb-item active"><?= e($pnm['nomor_penerimaan']) ?></li>
        </ol></nav>
    </div>
    <a href="../po/detail.php?id=<?= $pnm['id_po'] ?>" class="btn btn-outline-primary">
        <i class="fas fa-file-invoice me-1"></i>Lihat PO
    </a>
</div>

<?php show_flash(); ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Informasi Penerimaan</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="150">No. Penerimaan</td><td class="fw-600"><?= e($pnm['nomor_penerimaan']) ?></td></tr>
                            <tr><td class="text-muted">Tanggal Terima</td><td><?= tgl_indo($pnm['tanggal_terima']) ?></td></tr>
                            <tr><td class="text-muted">Diterima Oleh</td><td><?= e($pnm['nama_user']) ?></td></tr>
                            <?php if ($pnm['keterangan']): ?><tr><td class="text-muted">Keterangan</td><td><?= e($pnm['keterangan']) ?></td></tr><?php endif; ?>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="120">No. PO</td><td class="fw-600"><?= e($pnm['nomor_po']) ?></td></tr>
                            <tr><td class="text-muted">Proyek</td><td><?= e($pnm['nama_proyek']) ?></td></tr>
                            <tr><td class="text-muted">Supplier</td><td><?= e($pnm['nama_supplier']) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Item yang Diterima</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                    <tr><th>#</th><th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th class="text-end">Qty Diterima</th></tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($item = mysqli_fetch_assoc($items)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($item['kode_bahan']) ?></span></td>
                        <td class="fw-600"><?= e($item['nama_bahan']) ?></td>
                        <td><?= e($item['satuan']) ?></td>
                        <td class="text-end fw-bold text-success"><?= number_format($item['qty_diterima'],2,',','.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
