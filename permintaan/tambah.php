<?php
/**
 * Permintaan Bahan: tambah.php
 * Digunakan untuk: tambah baru & edit (mode edit via ?edit=id)
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin', 'purchasing']);

$pageTitle   = 'Buat Permintaan Bahan Baku';
$errors      = [];
$mode        = 'tambah';
$mr          = null;
$detail_list = [];

// ========== Mode Edit ==========
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0) {
    $mr = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT * FROM permintaan_bahan WHERE id_permintaan = $edit_id LIMIT 1"));
    if (!$mr) {
        set_flash('danger', 'Data permintaan tidak ditemukan.');
        redirect(BASE_URL . '/permintaan/index.php');
    }
    if (!in_array($mr['status_permintaan'], ['Draft', 'Ditolak'])) {
        set_flash('warning', 'Permintaan tidak bisa diedit karena sudah diajukan/disetujui.');
        redirect(BASE_URL . '/permintaan/detail.php?id=' . $edit_id);
    }
    $mode      = 'edit';
    $pageTitle = 'Edit Permintaan: ' . $mr['nomor_permintaan'];

    $res_detail = mysqli_query($koneksi,
        "SELECT pbd.*, b.kode_bahan, b.nama_bahan, b.satuan AS satuan_default, b.harga_default
         FROM permintaan_bahan_detail pbd
         LEFT JOIN bahan_baku b ON b.id_bahan = pbd.id_bahan
         WHERE pbd.id_permintaan = $edit_id ORDER BY pbd.id_detail_permintaan");
    while ($d = mysqli_fetch_assoc($res_detail)) $detail_list[] = $d;
}

// Nomor MR otomatis (hanya untuk tambah)
$nomor_mr = ($mode === 'tambah') ? generate_nomor_mr($koneksi) : ($mr['nomor_permintaan'] ?? '');

// Data dropdown
$proyek_list = mysqli_query($koneksi,
    "SELECT id_proyek, kode_proyek, nama_proyek FROM proyek WHERE status='aktif' ORDER BY nama_proyek");
$bahan_list  = mysqli_query($koneksi,
    "SELECT id_bahan, kode_bahan, nama_bahan, satuan, harga_default
     FROM bahan_baku WHERE status='aktif' ORDER BY nama_bahan");

// Build bahan options HTML
ob_start();
while ($b = mysqli_fetch_assoc($bahan_list)) {
    printf(
        "<option value='%d' data-satuan='%s' data-harga='%s' data-kode='%s'>[%s] %s</option>",
        $b['id_bahan'], e($b['satuan']), $b['harga_default'],
        e($b['kode_bahan']), e($b['kode_bahan']), e($b['nama_bahan'])
    );
}
$bahan_options_html = ob_get_clean();

// ========== Proses POST ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proyek        = (int)($_POST['id_proyek']           ?? 0);
    $tgl_permintaan   = trim($_POST['tanggal_permintaan']   ?? date('Y-m-d'));
    $tgl_dibutuhkan   = trim($_POST['tanggal_dibutuhkan']   ?? '');
    $keterangan       = trim($_POST['keterangan']           ?? '');
    $aksi_submit      = $_POST['aksi']                      ?? 'draft'; // 'draft' atau 'ajukan'
    $id_bahans        = $_POST['id_bahan']                   ?? [];
    $spesifikasis     = $_POST['spesifikasi']                ?? [];
    $qtys             = $_POST['qty_diminta']                ?? [];
    $satuans          = $_POST['satuan']                     ?? [];
    $est_hargas       = $_POST['estimasi_harga']             ?? [];
    $keperluan_arr    = $_POST['keperluan']                  ?? [];
    $catatan_arr      = $_POST['catatan_item']               ?? [];

    if ($id_proyek === 0) $errors[] = 'Proyek wajib dipilih.';
    if (empty($id_bahans)) $errors[] = 'Minimal tambahkan 1 item bahan baku.';
    foreach ($id_bahans as $i => $ib) {
        if ((int)$ib === 0)               $errors[] = "Baris " . ($i+1) . ": bahan baku belum dipilih.";
        if ((float)($qtys[$i]??0) <= 0)  $errors[] = "Baris " . ($i+1) . ": qty harus lebih dari 0.";
    }

    if (empty($errors)) {
        $status_baru = ($aksi_submit === 'ajukan') ? 'Diajukan' : 'Draft';
        $id_user     = (int)$_SESSION['id_user'];
        $tgl_dibutuhkan_val = $tgl_dibutuhkan ?: null;

        if ($mode === 'tambah') {
            // INSERT header
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO permintaan_bahan
                 (nomor_permintaan, tanggal_permintaan, id_proyek, id_user,
                  tanggal_dibutuhkan, status_permintaan, keterangan)
                 VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssiisss',
                $nomor_mr, $tgl_permintaan, $id_proyek, $id_user,
                $tgl_dibutuhkan_val, $status_baru, $keterangan);
            mysqli_stmt_execute($stmt);
            $id_permintaan = mysqli_insert_id($koneksi);
        } else {
            // UPDATE header
            $id_permintaan = $edit_id;
            $stmt = mysqli_prepare($koneksi,
                "UPDATE permintaan_bahan
                 SET tanggal_permintaan=?, id_proyek=?, tanggal_dibutuhkan=?,
                     status_permintaan=?, keterangan=?
                 WHERE id_permintaan=?");
            mysqli_stmt_bind_param($stmt, 'siissi',
                $tgl_permintaan, $id_proyek, $tgl_dibutuhkan_val,
                $status_baru, $keterangan, $id_permintaan);
            mysqli_stmt_execute($stmt);
            // Hapus detail lama
            mysqli_query($koneksi,
                "DELETE FROM permintaan_bahan_detail WHERE id_permintaan=$id_permintaan");
        }

        // INSERT detail items
        $stmt2 = mysqli_prepare($koneksi,
            "INSERT INTO permintaan_bahan_detail
             (id_permintaan, id_bahan, spesifikasi, qty_diminta, satuan,
              estimasi_harga, keperluan, catatan)
             VALUES (?,?,?,?,?,?,?,?)");
        foreach ($id_bahans as $i => $ib) {
            $id_bahan   = (int)$ib;
            $spec       = $spesifikasis[$i]  ?? '';
            $qty        = (float)($qtys[$i] ?? 0);
            $sat        = $satuans[$i]       ?? '';
            $est        = (float)($est_hargas[$i] ?? 0);
            $kep        = $keperluan_arr[$i] ?? '';
            $cat        = $catatan_arr[$i]   ?? '';
            
            mysqli_stmt_bind_param($stmt2, 'iisdsdss',
                $id_permintaan, $id_bahan, $spec, $qty, $sat, $est, $kep, $cat);
            mysqli_stmt_execute($stmt2);
        }

        $label = $status_baru === 'Diajukan' ? 'diajukan' : 'disimpan sebagai draft';
        set_flash('success',
            "Permintaan <strong>$nomor_mr</strong> berhasil $label.");
        redirect(BASE_URL . '/permintaan/detail.php?id=' . $id_permintaan);
    }
}

require_once '../template/header.php';
?>

<div class="page-header">
    <h4>
        <i class="fas fa-<?= $mode==='tambah'?'plus-circle':'edit' ?> me-2 text-primary"></i>
        <?= $mode === 'tambah' ? 'Buat Permintaan Bahan Baku' : ('Edit: ' . e($mr['nomor_permintaan'])) ?>
    </h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Permintaan Bahan</a></li>
        <li class="breadcrumb-item active"><?= $mode === 'tambah' ? 'Buat Baru' : 'Edit' ?></li>
    </ol></nav>
</div>

<?php foreach ($errors as $er): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $er ?></div>
<?php endforeach; ?>

<form method="POST" action="" novalidate id="form-mr">

    <!-- Header MR -->
    <div class="card mb-3">
        <div class="card-header">Informasi Permintaan</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nomor MR</label>
                    <input type="text" class="form-control" value="<?= e($nomor_mr) ?>" readonly
                           style="font-family:monospace;font-weight:700;color:var(--primary)">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tanggal Permintaan <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_permintaan" class="form-control" required
                           value="<?= e($_POST['tanggal_permintaan'] ?? ($mr['tanggal_permintaan'] ?? date('Y-m-d'))) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Proyek <span class="text-danger">*</span></label>
                    <select name="id_proyek" class="form-select" required>
                        <option value="">-- Pilih Proyek --</option>
                        <?php while ($p = mysqli_fetch_assoc($proyek_list)): ?>
                        <option value="<?= $p['id_proyek'] ?>"
                            <?= (($_POST['id_proyek'] ?? $mr['id_proyek'] ?? '') == $p['id_proyek']) ? 'selected' : '' ?>>
                            [<?= e($p['kode_proyek']) ?>] <?= e($p['nama_proyek']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tanggal Dibutuhkan</label>
                    <input type="date" name="tanggal_dibutuhkan" class="form-control"
                           value="<?= e($_POST['tanggal_dibutuhkan'] ?? ($mr['tanggal_dibutuhkan'] ?? '')) ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="mb-0">
                <label class="form-label">Keterangan / Keperluan Umum</label>
                <textarea name="keterangan" class="form-control" rows="2"
                          placeholder="Keterangan umum permintaan ini..."><?= e($_POST['keterangan'] ?? ($mr['keterangan'] ?? '')) ?></textarea>
            </div>
        </div>
    </div>

    <!-- Detail Item Bahan -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2 text-primary"></i>Daftar Item Bahan Baku</span>
            <button type="button" class="btn btn-sm btn-success" onclick="tambahItemMR()">
                <i class="fas fa-plus me-1"></i>Tambah Item
            </button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle" id="tabel-mr-items">
                <thead class="table-light" style="font-size:.8rem">
                <tr>
                    <th width="40">#</th>
                    <th width="220">Bahan Baku <span class="text-danger">*</span></th>
                    <th width="180">Spesifikasi Khusus</th>
                    <th width="100">Qty <span class="text-danger">*</span></th>
                    <th width="90">Satuan</th>
                    <th width="140">Estimasi Harga</th>
                    <th width="150">Keperluan</th>
                    <th width="140">Catatan</th>
                    <th width="50"></th>
                </tr>
                </thead>
                <tbody id="tbody-mr">
                <?php if ($mode === 'edit' && count($detail_list) > 0):
                    foreach ($detail_list as $i => $d): ?>
                <tr class="mr-item-row">
                    <td class="row-no text-muted"><?= $i + 1 ?></td>
                    <td>
                        <select name="id_bahan[]" class="form-select form-select-sm sel-bahan-mr"
                                required onchange="onBahanChangeMR(this)">
                            <option value="">-- Pilih --</option>
                            <?php
                            $bl2 = mysqli_query($koneksi,
                                "SELECT id_bahan,kode_bahan,nama_bahan,satuan,harga_default
                                 FROM bahan_baku WHERE status='aktif' ORDER BY nama_bahan");
                            while ($b2 = mysqli_fetch_assoc($bl2)):
                            ?>
                            <option value="<?= $b2['id_bahan'] ?>"
                                    data-satuan="<?= e($b2['satuan']) ?>"
                                    data-harga="<?= $b2['harga_default'] ?>"
                                    <?= $b2['id_bahan'] == $d['id_bahan'] ? 'selected' : '' ?>>
                                [<?= e($b2['kode_bahan']) ?>] <?= e($b2['nama_bahan']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                    <td><input type="text" name="spesifikasi[]" class="form-control form-control-sm"
                               value="<?= e($d['spesifikasi']) ?>" placeholder="Opsional"></td>
                    <td><input type="number" name="qty_diminta[]" class="form-control form-control-sm inp-qty-mr"
                               value="<?= $d['qty_diminta'] ?>" min="0.01" step="0.01" required
                               oninput="hitungEstimasi(this)"></td>
                    <td><input type="text" name="satuan[]" class="form-control form-control-sm inp-sat-mr"
                               value="<?= e($d['satuan']) ?>" placeholder="Sak/m3..."></td>
                    <td><input type="number" name="estimasi_harga[]" class="form-control form-control-sm inp-est-mr"
                               value="<?= $d['estimasi_harga'] ?>" min="0" step="100"
                               oninput="hitungEstimasi(this)"></td>
                    <td><input type="text" name="keperluan[]" class="form-control form-control-sm"
                               value="<?= e($d['keperluan']) ?>" placeholder="Untuk apa?"></td>
                    <td><input type="text" name="catatan_item[]" class="form-control form-control-sm"
                               value="<?= e($d['catatan']) ?>"></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="hapusItemMR(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach;
                else: ?>
                <tr class="mr-item-row">
                    <td class="row-no text-muted">1</td>
                    <td>
                        <select name="id_bahan[]" class="form-select form-select-sm sel-bahan-mr"
                                required onchange="onBahanChangeMR(this)">
                            <option value="">-- Pilih --</option>
                            <?= $bahan_options_html ?>
                        </select>
                    </td>
                    <td><input type="text" name="spesifikasi[]" class="form-control form-control-sm"
                               placeholder="Opsional"></td>
                    <td><input type="number" name="qty_diminta[]"
                               class="form-control form-control-sm inp-qty-mr"
                               min="0.01" step="0.01" required oninput="hitungEstimasi(this)"></td>
                    <td><input type="text" name="satuan[]"
                               class="form-control form-control-sm inp-sat-mr"
                               placeholder="Sak/m3..."></td>
                    <td><input type="number" name="estimasi_harga[]"
                               class="form-control form-control-sm inp-est-mr"
                               min="0" step="100" oninput="hitungEstimasi(this)"></td>
                    <td><input type="text" name="keperluan[]"
                               class="form-control form-control-sm" placeholder="Untuk apa?"></td>
                    <td><input type="text" name="catatan_item[]"
                               class="form-control form-control-sm"></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="hapusItemMR(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end align-items-center gap-3">
            <span class="text-muted small">Total Estimasi:</span>
            <strong id="txt-total-mr" class="text-primary fs-6">Rp 0</strong>
        </div>
    </div>

    <!-- Tombol -->
    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" name="aksi" value="draft" class="btn btn-secondary">
            <i class="fas fa-save me-1"></i>Simpan Draft
        </button>
        <button type="submit" name="aksi" value="ajukan" class="btn btn-primary">
            <i class="fas fa-paper-plane me-1"></i>Ajukan Permintaan
        </button>
        <a href="index.php" class="btn btn-light border">Batal</a>
    </div>
</form>

<script>
const bahanOptionsHTML = `<?= addslashes($bahan_options_html) ?>`;

function tambahItemMR() {
    const tbody = document.getElementById('tbody-mr');
    const no    = tbody.querySelectorAll('tr.mr-item-row').length + 1;
    const html  = `<tr class="mr-item-row">
        <td class="row-no text-muted">${no}</td>
        <td>
            <select name="id_bahan[]" class="form-select form-select-sm sel-bahan-mr"
                    required onchange="onBahanChangeMR(this)">
                <option value="">-- Pilih --</option>
                ${bahanOptionsHTML}
            </select>
        </td>
        <td><input type="text" name="spesifikasi[]" class="form-control form-control-sm" placeholder="Opsional"></td>
        <td><input type="number" name="qty_diminta[]" class="form-control form-control-sm inp-qty-mr"
                   min="0.01" step="0.01" required oninput="hitungEstimasi(this)"></td>
        <td><input type="text" name="satuan[]" class="form-control form-control-sm inp-sat-mr" placeholder="Sak/m3..."></td>
        <td><input type="number" name="estimasi_harga[]" class="form-control form-control-sm inp-est-mr"
                   min="0" step="100" oninput="hitungEstimasi(this)"></td>
        <td><input type="text" name="keperluan[]" class="form-control form-control-sm" placeholder="Untuk apa?"></td>
        <td><input type="text" name="catatan_item[]" class="form-control form-control-sm"></td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusItemMR(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>`;
    tbody.insertAdjacentHTML('beforeend', html);
    renumberMR();
}

function hapusItemMR(btn) {
    const rows = document.querySelectorAll('.mr-item-row');
    if (rows.length <= 1) { alert('Minimal harus ada 1 item.'); return; }
    btn.closest('tr').remove();
    renumberMR();
    hitungTotalMR();
}

function renumberMR() {
    document.querySelectorAll('.mr-item-row').forEach((r, i) => {
        r.querySelector('.row-no').textContent = i + 1;
    });
}

function onBahanChangeMR(sel) {
    const opt = sel.options[sel.selectedIndex];
    const row = sel.closest('tr');
    const sat = opt.dataset.satuan || '';
    const hrg = opt.dataset.harga  || 0;
    const inpSat = row.querySelector('.inp-sat-mr');
    const inpEst = row.querySelector('.inp-est-mr');
    if (inpSat && !inpSat.value) inpSat.value = sat;
    if (inpEst && !inpEst.value) inpEst.value = hrg;
    hitungTotalMR();
}

function hitungEstimasi(inp) { hitungTotalMR(); }

function hitungTotalMR() {
    let total = 0;
    document.querySelectorAll('.mr-item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.inp-qty-mr')?.value || 0);
        const est = parseFloat(row.querySelector('.inp-est-mr')?.value || 0);
        total += qty * est;
    });
    document.getElementById('txt-total-mr').textContent =
        'Rp ' + total.toLocaleString('id-ID', {minimumFractionDigits:0});
}

document.addEventListener('DOMContentLoaded', hitungTotalMR);
</script>

<?php require_once '../template/footer.php'; ?>
