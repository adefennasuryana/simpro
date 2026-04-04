<?php
/**
 * Supplier: index.php — Daftar Supplier
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$pageTitle = 'Data Supplier';

// Hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Cek apakah supplier dipakai di PO
    $cek = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM po WHERE id_supplier=$id"));
    if ($cek['c'] > 0) {
        set_flash('danger', 'Supplier tidak bisa dihapus karena sudah digunakan di PO.');
    } else {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM supplier WHERE id_supplier=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        set_flash('success', 'Supplier berhasil dihapus.');
    }
    redirect(BASE_URL . '/supplier/index.php');
}

// Search & filter
$search = trim($_GET['search'] ?? '');
$where  = '';
$params = [];
$types  = '';
if ($search !== '') {
    $where    = "WHERE nama_supplier LIKE ? OR no_telp LIKE ? OR email LIKE ?";
    $s        = "%$search%";
    $params   = [$s, $s, $s];
    $types    = 'sss';
}

$sql    = "SELECT * FROM supplier $where ORDER BY nama_supplier ASC";
$result = db_query($koneksi, $sql, $types, $params);

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-truck me-2 text-primary"></i>Data Supplier</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Supplier</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/supplier/tambah.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Tambah Supplier
    </a>
</div>

<?php show_flash(); ?>

<div class="card table-card">
    <div class="card-header">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Cari supplier..."
                   value="<?= e($search) ?>" style="max-width:280px">
            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
            <?php if ($search): ?>
            <a href="?" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th width="50">#</th>
                <th>Nama Supplier</th>
                <th>No. Telp</th>
                <th>Email</th>
                <th>Alamat</th>
                <th>Status</th>
                <th width="130">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($result) === 0): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data supplier</td></tr>
            <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="fw-600"><?= e($row['nama_supplier']) ?></td>
                <td><?= e($row['no_telp']) ?: '-' ?></td>
                <td><?= e($row['email']) ?: '-' ?></td>
                <td><?= e($row['alamat']) ?: '-' ?></td>
                <td>
                    <?php if ($row['status'] === 'aktif'): ?>
                    <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/supplier/edit.php?id=<?= $row['id_supplier'] ?>"
                       class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="?hapus=<?= $row['id_supplier'] ?>"
                       class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
