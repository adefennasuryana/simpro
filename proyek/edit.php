<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin','purchasing','manajer']);
$pageTitle = 'Edit Proyek';
$id        = (int)($_GET['id']??0);
$row       = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM proyek WHERE id_proyek=$id LIMIT 1"));
if (!$row) { set_flash('danger','Data tidak ditemukan.'); redirect(BASE_URL.'/proyek/index.php'); }
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode   = trim($_POST['kode_proyek']??'');
    $nama   = trim($_POST['nama_proyek']??'');
    $lokasi = trim($_POST['lokasi']??'');
    $tgl_m  = $_POST['tanggal_mulai']??'';
    $tgl_s  = $_POST['tanggal_selesai']??'';
    $status = $_POST['status']??'aktif';
    $ket    = trim($_POST['keterangan']??'');
    if ($kode==='') $errors[]='Kode proyek wajib diisi.';
    if ($nama==='') $errors[]='Nama proyek wajib diisi.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($koneksi,
            "UPDATE proyek SET kode_proyek=?,nama_proyek=?,lokasi=?,tanggal_mulai=?,tanggal_selesai=?,status=?,keterangan=? WHERE id_proyek=?");
        $tgl_m=$tgl_m?:null; $tgl_s=$tgl_s?:null;
        mysqli_stmt_bind_param($stmt,'sssssssi',$kode,$nama,$lokasi,$tgl_m,$tgl_s,$status,$ket,$id);
        if (mysqli_stmt_execute($stmt)) { set_flash('success','Proyek berhasil diperbarui.'); redirect(BASE_URL.'/proyek/index.php'); }
        else $errors[]='Gagal update.';
    }
    $row = array_merge($row, $_POST);
}
require_once '../template/header.php';
?>
<div class="page-header">
    <h4><i class="fas fa-edit me-2 text-warning"></i>Edit Proyek</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Proyek</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol></nav>
</div>
<?php foreach($errors as $er): ?><div class="alert alert-danger"><?= $er ?></div><?php endforeach; ?>
<div class="card" style="max-width:680px"><div class="card-header">Form Edit Proyek</div>
<div class="card-body">
<form method="POST" novalidate>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Kode Proyek <span class="text-danger">*</span></label>
            <input type="text" name="kode_proyek" class="form-control" value="<?= e($row['kode_proyek']) ?>" required>
        </div>
        <div class="col-md-8 mb-3">
            <label class="form-label">Nama Proyek <span class="text-danger">*</span></label>
            <input type="text" name="nama_proyek" class="form-control" value="<?= e($row['nama_proyek']) ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Lokasi</label>
        <input type="text" name="lokasi" class="form-control" value="<?= e($row['lokasi']) ?>">
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control" value="<?= e($row['tanggal_mulai']) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" class="form-control" value="<?= e($row['tanggal_selesai']) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach(['aktif'=>'Aktif','selesai'=>'Selesai','ditangguhkan'=>'Ditangguhkan'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $row['status']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2"><?= e($row['keterangan']) ?></textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
</form></div></div>
<?php require_once '../template/footer.php'; ?>
