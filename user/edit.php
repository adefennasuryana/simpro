<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin']);
$pageTitle='Edit User';
$id  = (int)($_GET['id']??0);
$row = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM users WHERE id_user=$id LIMIT 1"));
if (!$row) { set_flash('danger','User tidak ditemukan.'); redirect(BASE_URL.'/user/index.php'); }
$errors=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nama  = trim($_POST['nama']??'');
    $uname = trim($_POST['username']??'');
    $pass  = trim($_POST['password']??'');
    $role  = $_POST['role']??$row['role'];
    $st    = $_POST['status']??$row['status'];
    if ($nama==='')  $errors[]='Nama wajib diisi.';
    if ($uname==='') $errors[]='Username wajib diisi.';
    if ($pass!=='' && strlen($pass)<6) $errors[]='Password minimal 6 karakter.';

    if (empty($errors)) {
        if ($pass!=='') {
            $hash = password_hash($pass,PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi,"UPDATE users SET nama=?,username=?,password=?,role=?,status=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmt,'sssssi',$nama,$uname,$hash,$role,$st,$id);
        } else {
            $stmt = mysqli_prepare($koneksi,"UPDATE users SET nama=?,username=?,role=?,status=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmt,'ssssi',$nama,$uname,$role,$st,$id);
        }
        if (mysqli_stmt_execute($stmt)) { set_flash('success','User berhasil diperbarui.'); redirect(BASE_URL.'/user/index.php'); }
        else $errors[]='Gagal update.';
    }
    $row=array_merge($row,$_POST);
}
require_once '../template/header.php';
?>
<div class="page-header">
    <h4><i class="fas fa-user-edit me-2 text-warning"></i>Edit User</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">User</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol></nav>
</div>
<?php foreach($errors as $er): ?><div class="alert alert-danger"><?= $er ?></div><?php endforeach; ?>
<div class="card" style="max-width:580px"><div class="card-header">Form Edit User</div>
<div class="card-body">
<form method="POST" novalidate>
    <div class="mb-3">
        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" value="<?= e($row['nama']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" class="form-control" value="<?= e($row['username']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Password Baru <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter">
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <?php foreach(['admin'=>'Admin','purchasing'=>'Purchasing','manajer'=>'Manajer'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $row['role']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="aktif" <?= $row['status']==='aktif'?'selected':'' ?>>Aktif</option>
                <option value="nonaktif" <?= $row['status']==='nonaktif'?'selected':'' ?>>Nonaktif</option>
            </select>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
</form></div></div>
<?php require_once '../template/footer.php'; ?>
