<?php
/**
 * Penerimaan: index.php — Daftar Penerimaan Barang
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$pageTitle = 'Penerimaan Barang';

$q = $_GET['q'] ?? '';
$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

$where = [];
if (!empty($q)) {
    $search = mysqli_real_escape_string($koneksi, $q);
    $where[] = "(pn.nomor_penerimaan LIKE '%$search%' OR p.nomor_po LIKE '%$search%' OR pr.nama_proyek LIKE '%$search%' OR s.nama_supplier LIKE '%$search%')";
}
if (!empty($tgl_mulai) && !empty($tgl_akhir)) {
    $where[] = "pn.tanggal_terima BETWEEN '" . mysqli_real_escape_string($koneksi, $tgl_mulai) . "' AND '" . mysqli_real_escape_string($koneksi, $tgl_akhir) . "'";
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$result = mysqli_query($koneksi,
    "SELECT pn.*, p.nomor_po, pr.nama_proyek, s.nama_supplier, u.nama as nama_user
     FROM penerimaan pn
     LEFT JOIN po p ON p.id_po = pn.id_po
     LEFT JOIN proyek pr ON pr.id_proyek = p.id_proyek
     LEFT JOIN supplier s ON s.id_supplier = p.id_supplier
     LEFT JOIN users u ON u.id_user = pn.id_user
     $where_sql
     ORDER BY pn.created_at DESC");

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-boxes-stacked me-2 text-primary"></i>Penerimaan Barang</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Penerimaan</li>
        </ol></nav>
    </div>
    <?php if (in_array($_SESSION['role'],['admin','purchasing'])): ?>
    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Input Penerimaan</a>
    <?php endif; ?>
</div>

<?php show_flash(); ?>

<!-- Search & Date Filter -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-1">Cari No. Terima / PO / Proyek / Supplier</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Ketik kata kunci..." value="<?= e($q) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Periode Terima</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="tgl_mulai" class="form-control" value="<?= e($tgl_mulai) ?>">
                    <span class="input-group-text">s/d</span>
                    <input type="date" name="tgl_akhir" class="form-control" value="<?= e($tgl_akhir) ?>">
                </div>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-primary px-3"><i class="fas fa-filter"></i> Filter</button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>#</th>
                <th>No. Penerimaan</th>
                <th>Tanggal Terima</th>
                <th>No. PO</th>
                <th>Proyek</th>
                <th>Supplier</th>
                <th>Diterima Oleh</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php $no = 1;
            if (mysqli_num_rows($result) === 0): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data penerimaan</td></tr>
            <?php else: while ($r = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="fw-600"><?= e($r['nomor_penerimaan']) ?></td>
                <td><?= tgl_indo($r['tanggal_terima']) ?></td>
                <td><a href="../po/detail.php?id=<?= $r['id_po'] ?>" class="text-decoration-none"><?= e($r['nomor_po']) ?></a></td>
                <td><?= e($r['nama_proyek']) ?></td>
                <td><?= e($r['nama_supplier']) ?></td>
                <td><?= e($r['nama_user']) ?></td>
                <td>
                    <a href="detail.php?id=<?= $r['id_penerimaan'] ?>" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
