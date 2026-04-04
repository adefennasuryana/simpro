<?php
/**
 * PO: edit.php — Edit PO (hanya status draft)
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin','purchasing']);

$id = (int)($_GET['id'] ?? 0);
$po = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM po WHERE id_po=$id LIMIT 1"));
if (!$po || $po['status_po'] !== 'draft') {
    set_flash('danger','PO tidak ditemukan atau tidak bisa diedit.');
    redirect(BASE_URL.'/po/index.php');
}

$pageTitle = 'Edit PO: ' . $po['nomor_po'];
$errors    = [];

// Data dropdown
$proyek_list   = mysqli_query($koneksi,"SELECT id_proyek,kode_proyek,nama_proyek FROM proyek WHERE status='aktif' ORDER BY nama_proyek");
$supplier_list = mysqli_query($koneksi,"SELECT id_supplier,nama_supplier FROM supplier WHERE status='aktif' ORDER BY nama_supplier");
$bahan_list    = mysqli_query($koneksi,"SELECT id_bahan,kode_bahan,nama_bahan,satuan,harga_default FROM bahan_baku WHERE status='aktif' ORDER BY nama_bahan");

// Item yang sudah ada
$existing_items = [];
$res_items = mysqli_query($koneksi,
    "SELECT d.*, b.kode_bahan, b.nama_bahan, b.satuan as satuan_bahan
     FROM po_detail d LEFT JOIN bahan_baku b ON b.id_bahan=d.id_bahan
     WHERE d.id_po=$id ORDER BY d.id_detail");
while ($i = mysqli_fetch_assoc($res_items)) $existing_items[] = $i;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl_po      = $_POST['tanggal_po']    ?? $po['tanggal_po'];
    $id_proyek   = (int)($_POST['id_proyek']   ?? 0);
    $id_supplier = (int)($_POST['id_supplier']  ?? 0);
    $keterangan  = trim($_POST['keterangan']   ?? '');
    $id_bahans   = $_POST['id_bahan']          ?? [];
    $qtys        = $_POST['qty_pesan']          ?? [];
    $hargas      = $_POST['harga']              ?? [];
    $subtotals   = $_POST['subtotal']           ?? [];
    $ket_items   = $_POST['keterangan_item']   ?? [];

    if ($id_proyek  === 0) $errors[] = 'Proyek wajib dipilih.';
    if ($id_supplier === 0) $errors[] = 'Supplier wajib dipilih.';
    if (empty($id_bahans))  $errors[] = 'Minimal 1 item bahan baku.';

    if (empty($errors)) {
        $total = array_sum($subtotals);

        // Update header
        $stmt = mysqli_prepare($koneksi,
            "UPDATE po SET tanggal_po=?,id_proyek=?,id_supplier=?,total=?,keterangan=? WHERE id_po=?");
        mysqli_stmt_bind_param($stmt,'siidsi',$tgl_po,$id_proyek,$id_supplier,$total,$keterangan,$id);
        mysqli_stmt_execute($stmt);

        // Hapus detail lama, insert baru
        mysqli_query($koneksi,"DELETE FROM po_detail WHERE id_po=$id");
        $stmt2 = mysqli_prepare($koneksi,
            "INSERT INTO po_detail (id_po,id_bahan,qty_pesan,harga,subtotal,keterangan) VALUES (?,?,?,?,?,?)");
        foreach ($id_bahans as $i => $ib) {
            $id_bahan = (int)$ib;
            $qty      = (float)($qtys[$i] ?? 0);
            $harga    = (float)($hargas[$i] ?? 0);
            $sub      = (float)($subtotals[$i] ?? 0);
            $ket_i    = $ket_items[$i] ?? '';
            mysqli_stmt_bind_param($stmt2,'iiddds',$id,$id_bahan,$qty,$harga,$sub,$ket_i);
            mysqli_stmt_execute($stmt2);
        }

        set_flash('success',"PO <strong>{$po['nomor_po']}</strong> berhasil diperbarui.");
        redirect(BASE_URL . '/po/detail.php?id=' . $id);
    }
}

// Build bahan options
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
    <h4><i class="fas fa-edit me-2 text-warning"></i>Edit PO: <?= e($po['nomor_po']) ?></h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">PO</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol></nav>
</div>
<?php foreach($errors as $er): ?><div class="alert alert-danger"><?= $er ?></div><?php endforeach; ?>

<form method="POST" novalidate>
    <div class="card mb-3">
        <div class="card-header">Informasi PO</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nomor PO</label>
                    <input type="text" class="form-control" value="<?= e($po['nomor_po']) ?>" readonly>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tanggal PO <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_po" class="form-control"
                           value="<?= e($_POST['tanggal_po'] ?? $po['tanggal_po']) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Proyek <span class="text-danger">*</span></label>
                    <select name="id_proyek" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <?php while($p=mysqli_fetch_assoc($proyek_list)): ?>
                        <option value="<?= $p['id_proyek'] ?>" <?= ($_POST['id_proyek']??$po['id_proyek'])==$p['id_proyek']?'selected':'' ?>>
                            [<?= e($p['kode_proyek']) ?>] <?= e($p['nama_proyek']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="id_supplier" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <?php while($s=mysqli_fetch_assoc($supplier_list)): ?>
                        <option value="<?= $s['id_supplier'] ?>" <?= ($_POST['id_supplier']??$po['id_supplier'])==$s['id_supplier']?'selected':'' ?>>
                            <?= e($s['nama_supplier']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="2"><?= e($_POST['keterangan'] ?? $po['keterangan']) ?></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Item Bahan Baku</span>
            <button type="button" class="btn btn-sm btn-success" onclick="tambahBaris()"><i class="fas fa-plus me-1"></i>Tambah Item</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="tabel-items">
                <thead class="table-light">
                <tr><th>#</th><th>Bahan Baku</th><th>Satuan</th><th>Qty Pesan</th><th>Harga</th><th>Subtotal</th><th>Keterangan</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach($existing_items as $ei): ?>
                <tr class="row-item">
                    <td><?= $ei['id_detail'] ?></td>
                    <td>
                        <select name="id_bahan[]" class="form-select form-select-sm sel-bahan" required onchange="isiHarga(this)">
                            <option value="">-- Pilih --</option>
                            <?php
                            $bahan_list2 = mysqli_query($koneksi,"SELECT id_bahan,kode_bahan,nama_bahan,satuan,harga_default FROM bahan_baku WHERE status='aktif' ORDER BY nama_bahan");
                            while ($b=mysqli_fetch_assoc($bahan_list2)):
                            ?>
                            <option value="<?= $b['id_bahan'] ?>"
                                    data-satuan="<?= e($b['satuan']) ?>"
                                    data-harga="<?= $b['harga_default'] ?>"
                                    <?= $b['id_bahan']==$ei['id_bahan']?'selected':'' ?>>
                                [<?= e($b['kode_bahan']) ?>] <?= e($b['nama_bahan']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                    <td><input type="text" name="satuan[]" class="form-control form-control-sm inp-satuan" value="<?= e($ei['satuan_bahan']) ?>" readonly></td>
                    <td><input type="number" name="qty_pesan[]" class="form-control form-control-sm inp-qty" value="<?= $ei['qty_pesan'] ?>" min="0.01" step="0.01" oninput="hitungTotal()"></td>
                    <td><input type="number" name="harga[]" class="form-control form-control-sm inp-harga" value="<?= $ei['harga'] ?>" min="0" step="1" oninput="hitungTotal()"></td>
                    <td><span class="txt-subtotal"><?= rupiah($ei['subtotal']) ?></span><input type="hidden" name="subtotal[]" class="inp-subtotal" value="<?= $ei['subtotal'] ?>"></td>
                    <td><input type="text" name="keterangan_item[]" class="form-control form-control-sm" value="<?= e($ei['keterangan']) ?>"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBaris(this)"><i class="fas fa-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end align-items-center gap-3">
            <span class="text-muted">Total PO:</span>
            <span id="txt-total" class="fw-bold fs-5 text-primary"><?= rupiah($po['total']) ?></span>
            <input type="hidden" name="total" id="inp-total" value="<?= $po['total'] ?>">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update PO</button>
        <a href="detail.php?id=<?= $id ?>" class="btn btn-secondary">Batal</a>
    </div>
</form>

<script>
window.bahanOptions = <?= json_encode($bahan_options) ?>;
document.addEventListener('DOMContentLoaded', hitungTotal);
</script>

<?php require_once '../template/footer.php'; ?>
