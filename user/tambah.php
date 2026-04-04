<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin']);
$pageTitle='Tambah User';
$errors=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nama  = trim($_POST['nama']??'');
    $uname = trim($_POST['username']??'');
    $pass  = trim($_POST['password']??'');
    $role  = $_POST['role']??'purchasing';
    $st    = $_POST['status']??'aktif';
    if ($nama==='')  $errors[]='Nama wajib diisi.';
    if ($uname==='') $errors[]='Username wajib diisi.';
    if ($pass==='')  $errors[]='Password wajib diisi.';
    if (strlen($pass)<6) $errors[]='Password minimal 6 karakter.';

    if (empty($errors)) {
        // cek duplikat username
        $ck=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM users WHERE username='".mysqli_real_escape_string($koneksi,$uname)."'"));
        if ($ck['c']>0) $errors[]='Username sudah digunakan.';
    }

    if (empty($errors)) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi,"INSERT INTO users (nama,username,password,role,status) VALUES (?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt,'sssss',$nama,$uname,$hash,$role,$st);
        if (mysqli_stmt_execute($stmt)) { set_flash('success','User berhasil ditambahkan.'); redirect(BASE_URL.'/user/index.php'); }
        else $errors[]='Gagal menyimpan.';
    }
}
require_once '../template/header.php';
?>
<div class="page-header">
    <h4><i class="fas fa-user-plus me-2 text-primary"></i>Tambah User</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">User</a></li>
        <li class="breadcrumb-item active">Tambah</li>
    </ol></nav>
</div>
<?php foreach($errors as $er): ?><div class="alert alert-danger"><?= $er ?></div><?php endforeach; ?>
<div class="card" style="max-width:580px"><div class="card-header">Form Tambah User</div>
<div class="card-body">
<form method="POST" novalidate>
    <div class="mb-3">
        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" value="<?= e($_POST['nama']??'') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" class="form-control" value="<?= e($_POST['username']??'') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Password <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <?php foreach(['admin'=>'Admin','purchasing'=>'Purchasing','manajer'=>'Manajer'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($_POST['role']??'purchasing')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="aktif" <?= ($_POST['status']??'aktif')==='aktif'?'selected':'' ?>>Aktif</option>
                <option value="nonaktif" <?= ($_POST['status']??'')==='nonaktif'?'selected':'' ?>>Nonaktif</option>
            </select>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
</form></div></div>
<?php require_once '../template/footer.php'; ?>
