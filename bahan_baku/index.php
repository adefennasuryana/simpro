<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
$pageTitle = 'Bahan Baku';

if (isset($_GET['hapus'])) {
    $id  = (int)$_GET['hapus'];
    $cek = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM po_detail WHERE id_bahan=$id"));
    if ($cek['c'] > 0) set_flash('danger','Bahan baku tidak bisa dihapus, sudah digunakan di PO.');
    else {
        $st = mysqli_prepare($koneksi,"DELETE FROM bahan_baku WHERE id_bahan=?");
        mysqli_stmt_bind_param($st,'i',$id); mysqli_stmt_execute($st);
        set_flash('success','Bahan baku berhasil dihapus.');
    }
    redirect(BASE_URL.'/bahan_baku/index.php');
}

$search = trim($_GET['search']??'');
$sql    = $search
    ? "SELECT * FROM bahan_baku WHERE nama_bahan LIKE ? OR kode_bahan LIKE ? ORDER BY nama_bahan"
    : "SELECT * FROM bahan_baku ORDER BY nama_bahan";
$result = $search ? db_query($koneksi,$sql,'ss',["%$search%","%$search%"]) : mysqli_query($koneksi,$sql);

require_once '../template/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-boxes me-2 text-primary"></i>Data Bahan Baku</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Bahan Baku</li>
        </ol></nav>
    </div>
    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Bahan Baku</a>
</div>
<?php show_flash(); ?>
<div class="card table-card">
    <div class="card-header">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Cari bahan baku..." value="<?= e($search) ?>" style="max-width:280px">
            <button class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
            <?php if ($search): ?><a href="?" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Kode</th><th>Nama Bahan</th><th>Spesifikasi</th><th>Satuan</th><th>Harga Default</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php $no=1; if(mysqli_num_rows($result)==0): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data</td></tr>
            <?php else: while($row=mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><span class="badge bg-light text-dark border"><?= e($row['kode_bahan']) ?></span></td>
                <td class="fw-600"><?= e($row['nama_bahan']) ?></td>
                <td><?= e($row['spesifikasi']) ?: '-' ?></td>
                <td><?= e($row['satuan']) ?></td>
                <td><?= rupiah($row['harga_default']) ?></td>
                <td><span class="badge bg-<?= $row['status']==='aktif'?'success':'secondary' ?>"><?= ucfirst($row['status']) ?></span></td>
                <td>
                    <a href="edit.php?id=<?= $row['id_bahan'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                    <a href="?hapus=<?= $row['id_bahan'] ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../template/footer.php'; ?>
