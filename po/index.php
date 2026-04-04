<?php
/**
 * PO: index.php — Daftar Purchase Order
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$pageTitle = 'Purchase Order';

// Filter
$f_status = $_GET['status'] ?? '';
$f_search = trim($_GET['search'] ?? '');
$where  = [];
$params = [];
$types  = '';

if ($f_status !== '') { $where[] = "p.status_po = ?"; $params[] = $f_status; $types .= 's'; }
if ($f_search !== '') {
    $where[] = "(p.nomor_po LIKE ? OR pr.nama_proyek LIKE ? OR s.nama_supplier LIKE ?)";
    $s = "%$f_search%";
    $params = array_merge($params, [$s, $s, $s]); $types .= 'sss';
}
$wclause = $where ? 'WHERE '.implode(' AND ', $where) : '';

$sql = "SELECT p.*, pr.nama_proyek, s.nama_supplier, u.nama as nama_user
        FROM po p
        LEFT JOIN proyek pr ON pr.id_proyek = p.id_proyek
        LEFT JOIN supplier s ON s.id_supplier = p.id_supplier
        LEFT JOIN users u ON u.id_user = p.id_user
        $wclause
        ORDER BY p.created_at DESC";

$result = $params ? db_query($koneksi, $sql, $types, $params) : mysqli_query($koneksi, $sql);

require_once '../template/header.php';

function badge_po($status) {
    $map = ['draft'=>['secondary','Draft'],'diajukan'=>['warning','Diajukan'],'disetujui'=>['info','Disetujui'],
            'ditolak'=>['danger','Ditolak'],'dikirim_sebagian'=>['primary','Dikirim Sebagian'],'selesai'=>['success','Selesai']];
    $d = $map[$status] ?? ['secondary', ucfirst($status)];
    return "<span class='badge bg-{$d[0]}'>{$d[1]}</span>";
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-file-invoice me-2 text-primary"></i>Purchase Order</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Purchase Order</li>
        </ol></nav>
    </div>
    <?php if (in_array($_SESSION['role'],['admin','purchasing'])): ?>
    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Buat PO Baru</a>
    <?php endif; ?>
</div>

<?php show_flash(); ?>

<!-- Filter Bar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-auto">
                <input type="text" name="search" class="form-control" placeholder="Cari nomor PO / proyek / supplier..."
                       value="<?= e($f_search) ?>" style="width:280px">
            </div>
            <div class="col-auto">
                <select name="status" class="form-select">
                    <option value="">-- Semua Status --</option>
                    <?php foreach(['draft'=>'Draft','diajukan'=>'Diajukan','disetujui'=>'Disetujui','ditolak'=>'Ditolak','dikirim_sebagian'=>'Dikirim Sebagian','selesai'=>'Selesai'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $f_status===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="?" class="btn btn-outline-secondary">Reset</a>
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
                <th>Nomor PO</th>
                <th>Tanggal</th>
                <th>Proyek</th>
                <th>Supplier</th>
                <th>Total</th>
                <th>Status</th>
                <th>Dibuat Oleh</th>
                <th width="140">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php $no = 1;
            if (mysqli_num_rows($result) === 0): ?>
            <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data PO</td></tr>
            <?php else: while ($po = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><a href="detail.php?id=<?= $po['id_po'] ?>" class="fw-600 text-decoration-none"><?= e($po['nomor_po']) ?></a></td>
                <td><?= tgl_indo($po['tanggal_po']) ?></td>
                <td><?= e($po['nama_proyek']) ?></td>
                <td><?= e($po['nama_supplier']) ?></td>
                <td class="fw-600"><?= rupiah($po['total']) ?></td>
                <td><?= badge_po($po['status_po']) ?></td>
                <td><?= e($po['nama_user']) ?></td>
                <td>
                    <a href="detail.php?id=<?= $po['id_po'] ?>" class="btn btn-sm btn-outline-info" title="Detail"><i class="fas fa-eye"></i></a>
                    <a href="cetak.php?id=<?= $po['id_po'] ?>" class="btn btn-sm btn-outline-secondary" title="Cetak" target="_blank"><i class="fas fa-print"></i></a>
                    <?php if (in_array($_SESSION['role'],['admin','purchasing']) && in_array($po['status_po'],['draft'])): ?>
                    <a href="edit.php?id=<?= $po['id_po'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
