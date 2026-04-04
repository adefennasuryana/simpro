<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin','purchasing','manajer']);
$pageTitle = 'Tambah Proyek';
$errors    = [];

// Generate kode proyek otomatis
$kode_auto_proyek = generate_kode_proyek($koneksi);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode     = trim($_POST['kode_proyek']     ?? '');
    $nama     = trim($_POST['nama_proyek']     ?? '');
    $lokasi   = trim($_POST['lokasi']          ?? '');
    $tgl_m    = $_POST['tanggal_mulai']        ?? '';
    $tgl_s    = $_POST['tanggal_selesai']      ?? '';
    $status   = $_POST['status']              ?? 'aktif';
    $ket      = trim($_POST['keterangan']      ?? '');

    if ($kode === '') $errors[] = 'Kode proyek wajib diisi.';
    if ($nama === '') $errors[] = 'Nama proyek wajib diisi.';
    // Cek duplikat kode
    if ($kode !== '') {
        $ck = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM proyek WHERE kode_proyek='".mysqli_real_escape_string($koneksi,$kode)."'"));
        if ($ck['c'] > 0) $errors[] = 'Kode proyek sudah digunakan.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO proyek (kode_proyek,nama_proyek,lokasi,tanggal_mulai,tanggal_selesai,status,keterangan) VALUES (?,?,?,?,?,?,?)");
        $tgl_m = $tgl_m ?: null; $tgl_s = $tgl_s ?: null;
        mysqli_stmt_bind_param($stmt,'sssssss',$kode,$nama,$lokasi,$tgl_m,$tgl_s,$status,$ket);
        if (mysqli_stmt_execute($stmt)) {
            set_flash('success',"Proyek <strong>".e($nama)."</strong> berhasil ditambahkan.");
            redirect(BASE_URL.'/proyek/index.php');
        } else $errors[] = 'Gagal menyimpan.';
    }
}
require_once '../template/header.php';
?>
<div class="page-header">
    <h4><i class="fas fa-plus-circle me-2 text-primary"></i>Tambah Proyek</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Proyek</a></li>
        <li class="breadcrumb-item active">Tambah</li>
    </ol></nav>
</div>
<?php foreach($errors as $er): ?><div class="alert alert-danger"><?= $er ?></div><?php endforeach; ?>
<div class="card" style="max-width:680px"><div class="card-header">Form Tambah Proyek</div>
<div class="card-body">
<form method="POST" novalidate>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">
                Kode Proyek <span class="text-danger">*</span>
                <span class="badge bg-success ms-1" style="font-size:.65rem">
                    <i class="fas fa-magic"></i> Auto
                </span>
            </label>
            <div class="input-group">
                <input type="text"
                       name="kode_proyek"
                       id="kode_proyek"
                       class="form-control fw-bold"
                       value="<?= e($_POST['kode_proyek'] ?? $kode_auto_proyek) ?>"
                       placeholder="PRY-2026-004"
                       required
                       style="font-family:monospace">
                <button type="button"
                        class="btn btn-outline-secondary"
                        onclick="refreshKodeProyek()"
                        title="Generate ulang kode">
                    <i class="fas fa-sync-alt" id="icon-refresh-pry"></i>
                </button>
            </div>
            <div class="form-text text-muted">
                <i class="fas fa-info-circle text-primary"></i>
                Kode otomatis. Bisa diubah manual.
            </div>
        </div>
        <div class="col-md-8 mb-3">
            <label class="form-label">Nama Proyek <span class="text-danger">*</span></label>
            <input type="text" name="nama_proyek" class="form-control" value="<?= e($_POST['nama_proyek']??'') ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Lokasi</label>
        <input type="text" name="lokasi" class="form-control" value="<?= e($_POST['lokasi']??'') ?>">
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control" value="<?= e($_POST['tanggal_mulai']??'') ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" class="form-control" value="<?= e($_POST['tanggal_selesai']??'') ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach(['aktif'=>'Aktif','selesai'=>'Selesai','ditangguhkan'=>'Ditangguhkan'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($_POST['status']??'aktif')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2"><?= e($_POST['keterangan']??'') ?></textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
</form></div></div>

<script>
function refreshKodeProyek() {
    const icon  = document.getElementById('icon-refresh-pry');
    const input = document.getElementById('kode_proyek');
    icon.classList.add('fa-spin');
    input.disabled = true;
    fetch('get_kode_auto.php')
        .then(r => r.json())
        .then(data => { if (data.kode) input.value = data.kode; })
        .catch(() => alert('Gagal. Isi manual.'))
        .finally(() => { icon.classList.remove('fa-spin'); input.disabled = false; input.focus(); });
}
</script>

<?php require_once '../template/footer.php'; ?>
