<?php
/**
 * Permintaan Bahan: index.php — Daftar Permintaan
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$pageTitle = 'Permintaan Bahan Baku';

// Filter
$f_status  = $_GET['status']     ?? '';
$f_proyek  = (int)($_GET['id_proyek'] ?? 0);
$f_search  = trim($_GET['search'] ?? '');

// Hak akses: purchasing & admin lihat semua; manajer lihat semua; user lain hanya milik sendiri
$id_user_sesi = (int)$_SESSION['id_user'];
$role         = $_SESSION['role'];

$where  = [];
$params = [];
$types  = '';

// Purchasing hanya lihat yang Disetujui ke atas (untuk proses PO), kecuali admin
// Semua role bisa lihat semua untuk kemudahan, filter per role bisa dikembangkan
if ($f_status !== '') {
    $where[]  = "pb.status_permintaan = ?";
    $params[] = $f_status;
    $types   .= 's';
}
if ($f_proyek > 0) {
    $where[]  = "pb.id_proyek = ?";
    $params[] = $f_proyek;
    $types   .= 'i';
}
if ($f_search !== '') {
    $where[]  = "(pb.nomor_permintaan LIKE ? OR pr.nama_proyek LIKE ? OR u.nama LIKE ?)";
    $s         = "%$f_search%";
    $params    = array_merge($params, [$s, $s, $s]);
    $types    .= 'sss';
}

$wclause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT pb.*, pr.nama_proyek, pr.kode_proyek, u.nama AS nama_user
        FROM permintaan_bahan pb
        LEFT JOIN proyek pr ON pr.id_proyek = pb.id_proyek
        LEFT JOIN users  u  ON u.id_user    = pb.id_user
        $wclause
        ORDER BY pb.created_at DESC";

$result  = $params
    ? db_query($koneksi, $sql, $types, $params)
    : mysqli_query($koneksi, $sql);

$proyeks = mysqli_query($koneksi, "SELECT id_proyek,kode_proyek,nama_proyek FROM proyek ORDER BY nama_proyek");

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><i class="fas fa-clipboard-list me-2 text-primary"></i>Permintaan Bahan Baku</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Permintaan Bahan</li>
        </ol></nav>
    </div>
    <?php if (in_array($role, ['admin', 'purchasing'])): ?>
    <a href="tambah.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Buat Permintaan
    </a>
    <?php endif; ?>
</div>

<?php show_flash(); ?>

<!-- Filter Bar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Cari nomor / proyek / pemohon..."
                       value="<?= e($f_search) ?>">
            </div>
            <div class="col-md-3">
                <select name="id_proyek" class="form-select form-select-sm">
                    <option value="">Semua Proyek</option>
                    <?php while ($pr = mysqli_fetch_assoc($proyeks)): ?>
                    <option value="<?= $pr['id_proyek'] ?>" <?= $f_proyek == $pr['id_proyek'] ? 'selected' : '' ?>>
                        <?= e($pr['nama_proyek']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php foreach (['Draft','Diajukan','Disetujui','Ditolak','Diproses ke PO'] as $s): ?>
                    <option value="<?= $s ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-primary flex-fill">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="?" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>#</th>
                <th>Nomor MR</th>
                <th>Tanggal</th>
                <th>Proyek</th>
                <th>Pemohon</th>
                <th>Dibutuhkan</th>
                <th>Status</th>
                <th width="160">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php $no = 1;
            if (mysqli_num_rows($result) === 0): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">
                <i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                Belum ada permintaan bahan baku
            </td></tr>
            <?php else: while ($r = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>
                    <a href="detail.php?id=<?= $r['id_permintaan'] ?>"
                       class="fw-bold text-decoration-none">
                        <?= e($r['nomor_permintaan']) ?>
                    </a>
                </td>
                <td><?= tgl_indo($r['tanggal_permintaan']) ?></td>
                <td>
                    <span class="badge bg-light text-dark border small">
                        <?= e($r['kode_proyek']) ?>
                    </span>
                    <?= e($r['nama_proyek']) ?>
                </td>
                <td><?= e($r['nama_user']) ?></td>
                <td>
                    <?php if ($r['tanggal_dibutuhkan']): ?>
                    <?php
                    $sisa = (int)ceil((strtotime($r['tanggal_dibutuhkan']) - time()) / 86400);
                    $cls  = $sisa < 0 ? 'text-danger' : ($sisa <= 3 ? 'text-warning fw-bold' : '');
                    ?>
                    <span class="<?= $cls ?>"><?= tgl_indo($r['tanggal_dibutuhkan']) ?></span>
                    <?php if ($sisa < 0): ?>
                        <small class="text-danger d-block">Terlewat <?= abs($sisa) ?> hari</small>
                    <?php endif; ?>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td><?= badge_mr($r['status_permintaan']) ?></td>
                <td>
                    <a href="detail.php?id=<?= $r['id_permintaan'] ?>"
                       class="btn btn-sm btn-outline-info" title="Detail">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php if (in_array($role, ['admin','purchasing']) && in_array($r['status_permintaan'], ['Draft','Ditolak'])): ?>
                    <a href="tambah.php?edit=<?= $r['id_permintaan'] ?>"
                       class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="hapus.php?id=<?= $r['id_permintaan'] ?>"
                       class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin', 'manajer']) && $r['status_permintaan'] === 'Diajukan'): ?>
                    <a href="approval.php?id=<?= $r['id_permintaan'] ?>"
                       class="btn btn-sm btn-outline-success" title="Approval">
                        <i class="fas fa-check-double"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin','purchasing']) && $r['status_permintaan'] === 'Disetujui'): ?>
                    <a href="proses_po.php?id=<?= $r['id_permintaan'] ?>"
                       class="btn btn-sm btn-success" title="Proses ke PO">
                        <i class="fas fa-file-invoice"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
