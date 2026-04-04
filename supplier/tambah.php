<?php
/**
 * Supplier: tambah.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin', 'purchasing']);

$pageTitle = 'Tambah Supplier';
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama_supplier'] ?? '');
    $alamat = trim($_POST['alamat']        ?? '');
    $telp   = trim($_POST['no_telp']       ?? '');
    $email  = trim($_POST['email']         ?? '');
    $status = $_POST['status'] ?? 'aktif';

    if ($nama === '') $errors[] = 'Nama supplier wajib diisi.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Format email tidak valid.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO supplier (nama_supplier, alamat, no_telp, email, status) VALUES (?,?,?,?,?)"
        );
        mysqli_stmt_bind_param($stmt, 'sssss', $nama, $alamat, $telp, $email, $status);
        if (mysqli_stmt_execute($stmt)) {
            set_flash('success', "Supplier <strong>" . e($nama) . "</strong> berhasil ditambahkan.");
            redirect(BASE_URL . '/supplier/index.php');
        } else {
            $errors[] = 'Gagal menyimpan data.';
        }
    }
}

require_once '../template/header.php';
?>

<div class="page-header">
    <h4><i class="fas fa-plus-circle me-2 text-primary"></i>Tambah Supplier</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Supplier</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol>
    </nav>
</div>

<?php foreach ($errors as $e): ?>
<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i><?= $e ?></div>
<?php endforeach; ?>

<div class="card" style="max-width:680px">
    <div class="card-header">Form Tambah Supplier</div>
    <div class="card-body">
        <form method="POST" action="" novalidate>
            <div class="mb-3">
                <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                <input type="text" name="nama_supplier" class="form-control"
                       value="<?= e($_POST['nama_supplier'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="3"><?= e($_POST['alamat'] ?? '') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="no_telp" class="form-control"
                           value="<?= e($_POST['no_telp'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="aktif"    <?= ($_POST['status'] ?? 'aktif') === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= ($_POST['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Simpan
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
