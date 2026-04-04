<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin']);
$pageTitle = 'Data User';

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id === (int)$_SESSION['id_user']) { set_flash('danger','Anda tidak bisa menghapus akun sendiri.'); }
    else {
        $st = mysqli_prepare($koneksi,"DELETE FROM users WHERE id_user=?");
        mysqli_stmt_bind_param($st,'i',$id); mysqli_stmt_execute($st);
        set_flash('success','User berhasil dihapus.');
    }
    redirect(BASE_URL.'/user/index.php');
}

$q = $_GET['q'] ?? '';
$where_sql = "";
if (!empty($q)) {
    $search = mysqli_real_escape_string($koneksi, $q);
    $where_sql = "WHERE nama LIKE '%$search%' OR username LIKE '%$search%'";
}

$result = mysqli_query($koneksi,"SELECT id_user,nama,username,role,status,created_at FROM users $where_sql ORDER BY nama");
require_once '../template/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-users me-2 text-primary"></i>Data User</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">User</li>
        </ol></nav>
    </div>
    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah User</a>
</div>
<?php show_flash(); ?>

<!-- Search Card -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama atau username..." value="<?= e($q) ?>">
                    <button type="submit" class="btn btn-primary px-3 text-white">Cari</button>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php $no=1; while($row=mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="fw-600"><?= e($row['nama']) ?></td>
                <td><code><?= e($row['username']) ?></code></td>
                <td><?php
                    $rc=['admin'=>'danger','purchasing'=>'primary','manajer'=>'info'];
                    echo "<span class='badge bg-".($rc[$row['role']]??'secondary')."'>".ucfirst($row['role'])."</span>";
                ?></td>
                <td><span class="badge bg-<?= $row['status']==='aktif'?'success':'secondary' ?>"><?= ucfirst($row['status']) ?></span></td>
                <td><?= tgl_indo($row['created_at']) ?></td>
                <td>
                    <a href="edit.php?id=<?= $row['id_user'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                    <?php if ($row['id_user'] !== (int)$_SESSION['id_user']): ?>
                    <a href="?hapus=<?= $row['id_user'] ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="fas fa-trash"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../template/footer.php'; ?>
