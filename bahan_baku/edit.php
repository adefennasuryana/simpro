<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin','purchasing']);
$pageTitle='Edit Bahan Baku';
$id  = (int)($_GET['id']??0);
$row = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM bahan_baku WHERE id_bahan=$id LIMIT 1"));
if (!$row) { set_flash('danger','Data tidak ditemukan.'); redirect(BASE_URL.'/bahan_baku/index.php'); }
$errors=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $kode  = trim($_POST['kode_bahan']??'');
    $nama  = trim($_POST['nama_bahan']??'');
    $spec  = trim($_POST['spesifikasi']??'');
    $sat   = trim($_POST['satuan']??'');
    $harga = (float)($_POST['harga_default']??0);
    $st    = $_POST['status']??'aktif';
    if ($kode==='') $errors[]='Kode bahan wajib diisi.';
    if ($nama==='') $errors[]='Nama bahan wajib diisi.';
    if ($sat==='')  $errors[]='Satuan wajib diisi.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($koneksi,
            "UPDATE bahan_baku SET kode_bahan=?,nama_bahan=?,spesifikasi=?,satuan=?,harga_default=?,status=? WHERE id_bahan=?");
        mysqli_stmt_bind_param($stmt,'ssssdsi',$kode,$nama,$spec,$sat,$harga,$st,$id);
        if (mysqli_stmt_execute($stmt)) {
            set_flash('success','Bahan baku berhasil diperbarui.');
            redirect(BASE_URL.'/bahan_baku/index.php');
        } else $errors[]='Gagal update. '.mysqli_error($koneksi);
    }
    $row = array_merge($row,$_POST);
}
require_once '../template/header.php';
?>
<div class="page-header">
    <h4><i class="fas fa-edit me-2 text-warning"></i>Edit Bahan Baku</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Bahan Baku</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol></nav>
</div>
<?php foreach($errors as $er): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $er ?></div><?php endforeach; ?>
<div class="card" style="max-width:680px"><div class="card-header">Form Edit Bahan Baku</div>
<div class="card-body">
<form method="POST" novalidate>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Kode Bahan <span class="text-danger">*</span></label>
            <input type="text" name="kode_bahan" class="form-control" value="<?= e($row['kode_bahan']) ?>" required>
        </div>
        <div class="col-md-8 mb-3">
            <label class="form-label">Nama Bahan <span class="text-danger">*</span></label>
            <input type="text" name="nama_bahan" class="form-control" value="<?= e($row['nama_bahan']) ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Spesifikasi</label>
        <textarea name="spesifikasi" class="form-control" rows="2"><?= e($row['spesifikasi']) ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Satuan <span class="text-danger">*</span></label>
            <input type="text" name="satuan" class="form-control" value="<?= e($row['satuan']) ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Harga Default (Rp)</label>
            <input type="number" name="harga_default" class="form-control" value="<?= e($row['harga_default']) ?>" min="0">
        </div>
        <div class="col-md-4 mb-3">
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
