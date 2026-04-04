<?php
/**
 * PO: tambah.php — Buat PO Baru
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin','purchasing']);

$pageTitle = 'Buat Purchase Order';
$errors    = [];

// Data untuk dropdown
$proyek_list   = mysqli_query($koneksi,"SELECT id_proyek,kode_proyek,nama_proyek FROM proyek WHERE status='aktif' ORDER BY nama_proyek");
$supplier_list = mysqli_query($koneksi,"SELECT id_supplier,nama_supplier FROM supplier WHERE status='aktif' ORDER BY nama_supplier");
$bahan_list    = mysqli_query($koneksi,"SELECT id_bahan,kode_bahan,nama_bahan,satuan,harga_default FROM bahan_baku WHERE status='aktif' ORDER BY nama_bahan");

// Generate nomor PO otomatis
$nomor_po = generate_nomor($koneksi, 'po', 'nomor_po', 'PO');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl_po     = $_POST['tanggal_po']   ?? date('Y-m-d');
    $id_proyek  = (int)($_POST['id_proyek']  ?? 0);
    $id_supplier= (int)($_POST['id_supplier'] ?? 0);
    $keterangan = trim($_POST['keterangan']   ?? '');
    $id_bahans  = $_POST['id_bahan']          ?? [];
    $qtys       = $_POST['qty_pesan']         ?? [];
    $hargas     = $_POST['harga']             ?? [];
    $subtotals  = $_POST['subtotal']          ?? [];
    $ket_items  = $_POST['keterangan_item']   ?? [];

    if ($id_proyek === 0)   $errors[] = 'Proyek wajib dipilih.';
    if ($id_supplier === 0) $errors[] = 'Supplier wajib dipilih.';
    if (empty($id_bahans))  $errors[] = 'Minimal tambahkan 1 item bahan baku.';

    // Validasi item
    foreach ($id_bahans as $i => $ib) {
        if ((int)$ib === 0)           $errors[] = "Baris " . ($i+1) . ": bahan baku belum dipilih.";
        if ((float)($qtys[$i]??0) <= 0) $errors[] = "Baris " . ($i+1) . ": qty harus lebih dari 0.";
        if ((float)($hargas[$i]??0) <= 0) $errors[] = "Baris " . ($i+1) . ": harga harus lebih dari 0.";
    }

    if (empty($errors)) {
        $total = array_sum($subtotals);
        $id_user = $_SESSION['id_user'];

        // Simpan header PO
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO po (nomor_po,tanggal_po,id_proyek,id_supplier,id_user,status_po,total,keterangan)
             VALUES (?,?,?,?,?,'draft',?,?)");
        mysqli_stmt_bind_param($stmt,'ssiiids',$nomor_po,$tgl_po,$id_proyek,$id_supplier,$id_user,$total,$keterangan);

        if (mysqli_stmt_execute($stmt)) {
            $id_po = mysqli_insert_id($koneksi);

            // Simpan detail PO
            $stmt2 = mysqli_prepare($koneksi,
                "INSERT INTO po_detail (id_po,id_bahan,qty_pesan,harga,subtotal,keterangan) VALUES (?,?,?,?,?,?)");
            foreach ($id_bahans as $i => $ib) {
                $id_bahan  = (int)$ib;
                $qty       = (float)($qtys[$i] ?? 0);
                $harga     = (float)($hargas[$i] ?? 0);
                $sub       = (float)($subtotals[$i] ?? 0);
                $ket_i     = $ket_items[$i] ?? '';
                mysqli_stmt_bind_param($stmt2,'iiddds',$id_po,$id_bahan,$qty,$harga,$sub,$ket_i);
                mysqli_stmt_execute($stmt2);
            }
            set_flash('success', "PO <strong>$nomor_po</strong> berhasil dibuat.");
            redirect(BASE_URL . '/po/detail.php?id=' . $id_po);
        } else {
            $errors[] = 'Gagal menyimpan PO: ' . mysqli_error($koneksi);
        }
    }
}

// Build bahan options for JS
ob_start();
mysqli_data_seek($bahan_list, 0);
while ($b = mysqli_fetch_assoc($bahan_list)) {
    echo "<option value='{$b['id_bahan']}' data-satuan='".e($b['satuan'])."' data-harga='{$b['harga_default']}'>
            [{$b['kode_bahan']}] {$b['nama_bahan']}
          </option>";
}
$bahan_options = ob_get_clean();

require_once '../template/header.php';
?>

<div class="page-header">
    <h4><i class="fas fa-plus-circle me-2 text-primary"></i>Buat Purchase Order</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">PO</a></li>
        <li class="breadcrumb-item active">Buat PO</li>
    </ol></nav>
</div>

<?php foreach ($errors as $er): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $er ?></div>
<?php endforeach; ?>

<form method="POST" action="" id="form-po" novalidate>
    <!-- Header PO -->
    <div class="card mb-3">
        <div class="card-header">Informasi Purchase Order</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nomor PO</label>
                    <input type="text" class="form-control" value="<?= e($nomor_po) ?>" readonly>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tanggal PO <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_po" class="form-control"
                           value="<?= e($_POST['tanggal_po'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Proyek <span class="text-danger">*</span></label>
                    <select name="id_proyek" class="form-select" required>
                        <option value="">-- Pilih Proyek --</option>
                        <?php mysqli_data_seek($proyek_list,0); while($p=mysqli_fetch_assoc($proyek_list)): ?>
                        <option value="<?= $p['id_proyek'] ?>" <?= ($_POST['id_proyek']??'')==$p['id_proyek']?'selected':'' ?>>
                            [<?= e($p['kode_proyek']) ?>] <?= e($p['nama_proyek']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="id_supplier" class="form-select" required>
                        <option value="">-- Pilih Supplier --</option>
                        <?php mysqli_data_seek($supplier_list,0); while($s=mysqli_fetch_assoc($supplier_list)): ?>
                        <option value="<?= $s['id_supplier'] ?>" <?= ($_POST['id_supplier']??'')==$s['id_supplier']?'selected':'' ?>>
                            <?= e($s['nama_supplier']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="mb-0">
                <label class="form-label">Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="2"><?= e($_POST['keterangan']??'') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Detail Item -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>Item Bahan Baku</span>
            <button type="button" class="btn btn-sm btn-success" onclick="tambahBaris()">
                <i class="fas fa-plus me-1"></i>Tambah Item
            </button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="tabel-items">
                <thead class="table-light">
                <tr>
                    <th width="40">#</th>
                    <th>Bahan Baku</th>
                    <th width="80">Satuan</th>
                    <th width="100">Qty Pesan</th>
                    <th width="140">Harga (Rp)</th>
                    <th width="150">Subtotal</th>
                    <th>Keterangan</th>
                    <th width="50"></th>
                </tr>
                </thead>
                <tbody>
                <tr class="row-item">
                    <td>1</td>
                    <td>
                        <select name="id_bahan[]" class="form-select form-select-sm sel-bahan" required onchange="isiHarga(this)">
                            <option value="">-- Pilih --</option>
                            <?= $bahan_options ?>
                        </select>
                    </td>
                    <td><input type="text" name="satuan[]" class="form-control form-control-sm inp-satuan" readonly></td>
                    <td><input type="number" name="qty_pesan[]" class="form-control form-control-sm inp-qty" min="0.01" step="0.01" required oninput="hitungTotal()"></td>
                    <td><input type="number" name="harga[]" class="form-control form-control-sm inp-harga" min="0" step="1" required oninput="hitungTotal()"></td>
                    <td><span class="txt-subtotal">Rp 0</span><input type="hidden" name="subtotal[]" class="inp-subtotal"></td>
                    <td><input type="text" name="keterangan_item[]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBaris(this)"><i class="fas fa-trash"></i></button></td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end align-items-center gap-3">
            <span class="text-muted">Total PO:</span>
            <span id="txt-total" class="fw-bold fs-5 text-primary">Rp 0</span>
            <input type="hidden" name="total" id="inp-total" value="0">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Simpan PO
        </button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
</form>

<script>
// Expose bahan options to app.js
window.bahanOptions = <?= json_encode($bahan_options) ?>;
</script>

<?php require_once '../template/footer.php'; ?>
