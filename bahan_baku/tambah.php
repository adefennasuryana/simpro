<?php
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin', 'purchasing']);

$pageTitle = 'Tambah Bahan Baku';
$errors    = [];

// Generate kode otomatis saat halaman pertama dibuka
$kode_auto = generate_kode_bahan($koneksi);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode  = trim($_POST['kode_bahan']       ?? '');
    $nama  = trim($_POST['nama_bahan']        ?? '');
    $spec  = trim($_POST['spesifikasi']       ?? '');
    $sat   = trim($_POST['satuan']            ?? '');
    $harga = (float)($_POST['harga_default']  ?? 0);
    $st    = $_POST['status']                 ?? 'aktif';

    if ($kode === '') $errors[] = 'Kode bahan wajib diisi.';
    if ($nama === '') $errors[] = 'Nama bahan wajib diisi.';
    if ($sat  === '') $errors[] = 'Satuan wajib diisi.';

    // Cek duplikat kode
    if ($kode !== '') {
        $ck = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT COUNT(*) c FROM bahan_baku WHERE kode_bahan = '" .
            mysqli_real_escape_string($koneksi, $kode) . "'"
        ));
        if ($ck['c'] > 0) {
            $errors[] = 'Kode bahan <strong>' . e($kode) . '</strong> sudah digunakan.';
        }
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO bahan_baku (kode_bahan, nama_bahan, spesifikasi, satuan, harga_default, status)
             VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssds', $kode, $nama, $spec, $sat, $harga, $st);
        if (mysqli_stmt_execute($stmt)) {
            set_flash('success',
                "Bahan baku <strong>" . e($nama) . "</strong> " .
                "(<code>" . e($kode) . "</code>) berhasil ditambahkan."
            );
            redirect(BASE_URL . '/bahan_baku/index.php');
        } else {
            $errors[] = 'Gagal menyimpan. ' . mysqli_error($koneksi);
        }
    }

    // Tampilkan kembali kode yang di-submit jika ada error
    $kode_auto = $kode;
}

require_once '../template/header.php';
?>

<div class="page-header">
    <h4><i class="fas fa-plus-circle me-2 text-primary"></i>Tambah Bahan Baku</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Bahan Baku</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol>
    </nav>
</div>

<?php foreach ($errors as $er): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $er ?></div>
<?php endforeach; ?>

<div class="card" style="max-width:700px">
    <div class="card-header">Form Tambah Bahan Baku</div>
    <div class="card-body">
        <form method="POST" novalidate>

            <div class="row">
                <!-- Kode Bahan: auto-generate dari sistem -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Kode Bahan <span class="text-danger">*</span>
                        <span class="badge bg-success ms-1" style="font-size:.65rem">
                            <i class="fas fa-magic"></i> Auto
                        </span>
                    </label>
                    <div class="input-group">
                        <input type="text"
                               name="kode_bahan"
                               id="kode_bahan"
                               class="form-control fw-bold"
                               value="<?= e($kode_auto) ?>"
                               placeholder="BHN-011"
                               required
                               style="font-family: monospace; letter-spacing: .05rem">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                onclick="refreshKode()"
                                title="Generate ulang kode otomatis">
                            <i class="fas fa-sync-alt" id="icon-refresh"></i>
                        </button>
                    </div>
                    <div class="form-text">
                        <i class="fas fa-info-circle text-primary"></i>
                        Kode otomatis. Bisa diubah manual jika perlu.
                    </div>
                </div>

                <div class="col-md-8 mb-3">
                    <label class="form-label">Nama Bahan <span class="text-danger">*</span></label>
                    <input type="text"
                           name="nama_bahan"
                           class="form-control"
                           value="<?= e($_POST['nama_bahan'] ?? '') ?>"
                           placeholder="Misal: Semen Portland, Besi Beton Ø10 ..."
                           required
                           autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Spesifikasi</label>
                <textarea name="spesifikasi"
                          class="form-control"
                          rows="2"
                          placeholder="Misal: Tipe I, 50 kg/sak, SNI ..."><?= e($_POST['spesifikasi'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <input type="text"
                           name="satuan"
                           class="form-control"
                           value="<?= e($_POST['satuan'] ?? '') ?>"
                           placeholder="Sak / m3 / Kg / Btg ..."
                           list="list-satuan"
                           required>
                    <!-- Autocomplete satuan umum -->
                    <datalist id="list-satuan">
                        <option value="Sak">
                        <option value="Kg">
                        <option value="Ton">
                        <option value="m3">
                        <option value="m2">
                        <option value="Meter">
                        <option value="Batang">
                        <option value="Lembar">
                        <option value="Pcs">
                        <option value="Roll">
                        <option value="Kaleng">
                        <option value="Liter">
                        <option value="Dus">
                        <option value="Set">
                        <option value="Galon">
                    </datalist>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Harga Default (Rp)</label>
                    <div class="input-group">
                        <span class="input-group-text text-muted small">Rp</span>
                        <input type="number"
                               name="harga_default"
                               class="form-control"
                               value="<?= e($_POST['harga_default'] ?? 0) ?>"
                               min="0"
                               step="100"
                               placeholder="0">
                    </div>
                    <div class="form-text">Harga satuan per item</div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif"
                            <?= ($_POST['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>
                            ✅ Aktif
                        </option>
                        <option value="nonaktif"
                            <?= ($_POST['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>
                            ⛔ Nonaktif
                        </option>
                    </select>
                </div>
            </div>

            <hr class="my-3">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Simpan Bahan Baku
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Batal
                </a>
            </div>

        </form>
    </div>
</div>

<script>
/**
 * Ambil kode baru dari server via AJAX lalu isi ke input
 */
function refreshKode() {
    const icon  = document.getElementById('icon-refresh');
    const input = document.getElementById('kode_bahan');

    icon.classList.add('fa-spin');
    input.disabled = true;

    fetch('get_kode_auto.php')
        .then(r => r.json())
        .then(data => {
            if (data.kode) {
                input.value = data.kode;
            } else {
                alert('Gagal mendapatkan kode baru.');
            }
        })
        .catch(() => {
            alert('Koneksi gagal. Silakan isi kode manual.');
        })
        .finally(() => {
            icon.classList.remove('fa-spin');
            input.disabled = false;
            input.focus();
        });
}
</script>

<?php require_once '../template/footer.php'; ?>
