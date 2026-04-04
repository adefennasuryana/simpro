<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
$pageTitle = 'Data Proyek';

if (isset($_GET['hapus'])) {
    $id  = (int)$_GET['hapus'];
    $cek = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM po WHERE id_proyek=$id"));
    if ($cek['c'] > 0) {
        set_flash('danger','Proyek tidak bisa dihapus karena sudah memiliki PO.');
    } else {
        $st = mysqli_prepare($koneksi,"DELETE FROM proyek WHERE id_proyek=?");
        mysqli_stmt_bind_param($st,'i',$id); mysqli_stmt_execute($st);
        set_flash('success','Proyek berhasil dihapus.');
    }
    redirect(BASE_URL.'/proyek/index.php');
}

$search = trim($_GET['search'] ?? '');
$sql    = $search
    ? "SELECT * FROM proyek WHERE nama_proyek LIKE ? OR kode_proyek LIKE ? ORDER BY created_at DESC"
    : "SELECT * FROM proyek ORDER BY created_at DESC";

if ($search) {
    $s = "%$search%";
    $result = db_query($koneksi, $sql, 'ss', [$s, $s]);
} else {
    $result = mysqli_query($koneksi, $sql);
}

require_once '../template/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-project-diagram me-2 text-primary"></i>Data Proyek</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Proyek</li>
            </ol>
        </nav>
    </div>
    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Proyek</a>
</div>
<?php show_flash(); ?>
<div class="card table-card">
    <div class="card-header">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Cari proyek..." value="<?= e($search) ?>" style="max-width:280px">
            <button class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
            <?php if ($search): ?><a href="?" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Kode</th><th>Nama Proyek</th><th>Lokasi</th><th>Tgl Mulai</th><th>Tgl Selesai</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php $no=1; if(mysqli_num_rows($result)==0): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data proyek</td></tr>
            <?php else: while($row=mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><span class="badge bg-light text-dark border"><?= e($row['kode_proyek']) ?></span></td>
                <td class="fw-600"><?= e($row['nama_proyek']) ?></td>
                <td><?= e($row['lokasi']) ?></td>
                <td><?= tgl_indo($row['tanggal_mulai']) ?></td>
                <td><?= tgl_indo($row['tanggal_selesai']) ?></td>
                <td><?php
                    $sc = ['aktif'=>'success','selesai'=>'secondary','ditangguhkan'=>'warning'];
                    $st = $row['status'];
                    echo "<span class='badge bg-".($sc[$st]??'secondary')."'>".ucfirst($st)."</span>";
                ?></td>
                <td>
                    <a href="edit.php?id=<?= $row['id_proyek'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                    <a href="?hapus=<?= $row['id_proyek'] ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../template/footer.php'; ?>
