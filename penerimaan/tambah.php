<?php
/**
 * Penerimaan: tambah.php — Input Penerimaan Barang
 * Supports partial & multiple receipts per item
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin','purchasing']);

$pageTitle = 'Input Penerimaan Barang';
$errors    = [];

// Pre-select PO dari URL (dari tombol di detail PO)
$preselect_po = (int)($_GET['id_po'] ?? 0);

// Ambil daftar PO yang boleh diterima (disetujui atau dikirim sebagian)
$po_list = mysqli_query($koneksi,
    "SELECT p.id_po, p.nomor_po, pr.nama_proyek, s.nama_supplier
     FROM po p
     LEFT JOIN proyek pr ON pr.id_proyek = p.id_proyek
     LEFT JOIN supplier s ON s.id_supplier = p.id_supplier
     WHERE p.status_po IN ('disetujui','dikirim_sebagian')
     ORDER BY p.nomor_po DESC");

// Generate nomor penerimaan
$nomor_penerimaan = generate_nomor($koneksi, 'penerimaan', 'nomor_penerimaan', 'GRN');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_po       = (int)($_POST['id_po']        ?? 0);
    $tgl_terima  = $_POST['tanggal_terima']     ?? date('Y-m-d');
    $keterangan  = trim($_POST['keterangan']    ?? '');
    $id_details  = $_POST['id_detail']          ?? [];
    $qty_ters    = $_POST['qty_diterima']       ?? [];

    if ($id_po === 0) $errors[] = 'PO wajib dipilih.';
    if (empty($id_details)) $errors[] = 'Minimal 1 item harus diisi.';

    // Validasi qty tidak melebihi sisa
    if ($id_po > 0) {
        foreach ($id_details as $i => $idet) {
            $id_d   = (int)$idet;
            $qty_in = (float)($qty_ters[$i] ?? 0);
            if ($qty_in < 0) { $errors[] = 'Qty diterima tidak boleh negatif.'; break; }

            // Cek sisa
            $sisa_row = mysqli_fetch_assoc(mysqli_query($koneksi,
                "SELECT d.qty_pesan, COALESCE(SUM(pd.qty_diterima),0) as sudah
                 FROM po_detail d
                 LEFT JOIN penerimaan_detail pd ON pd.id_detail=d.id_detail
                 WHERE d.id_detail=$id_d GROUP BY d.id_detail"));
            if ($sisa_row) {
                $sisa = $sisa_row['qty_pesan'] - $sisa_row['sudah'];
                if ($qty_in > $sisa) {
                    $errors[] = "Item baris " . ($i+1) . ": qty diterima ($qty_in) melebihi sisa ($sisa).";
                }
            }
        }
    }

    if (empty($errors)) {
        $id_user = $_SESSION['id_user'];

        // Simpan header penerimaan
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO penerimaan (nomor_penerimaan,tanggal_terima,id_po,id_user,keterangan) VALUES (?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt,'ssiis',$nomor_penerimaan,$tgl_terima,$id_po,$id_user,$keterangan);

        if (mysqli_stmt_execute($stmt)) {
            $id_penerimaan = mysqli_insert_id($koneksi);

            // Simpan detail penerimaan (hanya yang qty > 0)
            $stmt2 = mysqli_prepare($koneksi,
                "INSERT INTO penerimaan_detail (id_penerimaan,id_detail,qty_diterima) VALUES (?,?,?)");
            foreach ($id_details as $i => $idet) {
                $qty_in = (float)($qty_ters[$i] ?? 0);
                if ($qty_in <= 0) continue;
                $id_d = (int)$idet;
                mysqli_stmt_bind_param($stmt2,'iid',$id_penerimaan,$id_d,$qty_in);
                mysqli_stmt_execute($stmt2);
            }

            // Update status PO berdasarkan kondisi penerimaan
            $po_status_view = mysqli_fetch_assoc(mysqli_query($koneksi,
                "SELECT status_penerimaan FROM v_po_status_penerimaan WHERE id_po=$id_po"));
            if ($po_status_view) {
                $new_status_po = match($po_status_view['status_penerimaan']) {
                    'selesai'         => 'selesai',
                    'dikirim_sebagian'=> 'dikirim_sebagian',
                    default           => 'disetujui'
                };
                mysqli_query($koneksi,"UPDATE po SET status_po='$new_status_po' WHERE id_po=$id_po");
            }

            set_flash('success', "Penerimaan <strong>$nomor_penerimaan</strong> berhasil disimpan.");
            redirect(BASE_URL . '/penerimaan/detail.php?id=' . $id_penerimaan);
        } else {
            $errors[] = 'Gagal menyimpan penerimaan.';
        }
    }
    $preselect_po = $id_po;
}

require_once '../template/header.php';
?>

<div class="page-header">
    <h4><i class="fas fa-plus-circle me-2 text-primary"></i>Input Penerimaan Barang</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Penerimaan</a></li>
        <li class="breadcrumb-item active">Input Penerimaan</li>
    </ol></nav>
</div>

<?php foreach ($errors as $er): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $er ?></div>
<?php endforeach; ?>

<form method="POST" action="" id="form-penerimaan" novalidate>
    <!-- Header -->
    <div class="card mb-3">
        <div class="card-header">Informasi Penerimaan</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nomor Penerimaan</label>
                    <input type="text" class="form-control" value="<?= e($nomor_penerimaan) ?>" readonly>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tanggal Terima <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_terima" class="form-control"
                           value="<?= e($_POST['tanggal_terima'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Pilih Nomor PO <span class="text-danger">*</span></label>
                    <select name="id_po" id="sel_po" class="form-select" required onchange="loadItemPO(this.value)">
                        <option value="">-- Pilih PO (Status: Disetujui / Dikirim Sebagian) --</option>
                        <?php while ($po = mysqli_fetch_assoc($po_list)): ?>
                        <option value="<?= $po['id_po'] ?>" <?= $preselect_po == $po['id_po'] ? 'selected' : '' ?>>
                            <?= e($po['nomor_po']) ?> &mdash; <?= e($po['nama_proyek']) ?> / <?= e($po['nama_supplier']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="mb-0">
                <label class="form-label">Keterangan</label>
                <input type="text" name="keterangan" class="form-control"
                       value="<?= e($_POST['keterangan'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- Tabel Item PO -->
    <div class="card mb-3" id="card-items" <?= $preselect_po ? '' : 'style="display:none"' ?>>
        <div class="card-header"><i class="fas fa-list me-2"></i>Item Bahan Baku dalam PO</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Bahan Baku</th>
                    <th class="text-center">Satuan</th>
                    <th class="text-end">Qty Pesan</th>
                    <th class="text-end">Sudah Diterima</th>
                    <th class="text-end">Sisa</th>
                    <th>Status Item</th>
                    <th width="130">Qty Diterima Sekarang</th>
                </tr>
                </thead>
                <tbody id="tbody-items">
                <tr><td colspan="8" class="text-center text-muted py-3">Pilih PO terlebih dahulu</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" id="btn-save" style="display:none">
            <i class="fas fa-save me-1"></i>Simpan Penerimaan
        </button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
</form>

<script>
function loadItemPO(id_po) {
    const card  = document.getElementById('card-items');
    const tbody = document.getElementById('tbody-items');
    const btnSave = document.getElementById('btn-save');

    if (!id_po) {
        card.style.display = 'none';
        btnSave.style.display = 'none';
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Pilih PO terlebih dahulu</td></tr>';
        return;
    }

    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>';
    card.style.display = '';
    btnSave.style.display = '';

    fetch('get_items_po.php?id_po=' + id_po)
        .then(r => r.json())
        .then(data => {
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Semua item sudah selesai diterima</td></tr>';
                btnSave.style.display = 'none';
                return;
            }
            let html = '';
            data.forEach((item, i) => {
                const badge = item.status_item === 'Selesai'
                    ? '<span class="badge bg-success">Selesai</span>'
                    : item.status_item === 'Diterima sebagian'
                    ? '<span class="badge bg-warning">Diterima sebagian</span>'
                    : '<span class="badge bg-secondary">Belum diterima</span>';

                const disabled = item.qty_sisa <= 0 ? 'disabled readonly' : '';
                html += `<tr>
                    <td>${i+1}</td>
                    <td><strong>${item.nama_bahan}</strong><br><small class="text-muted">${item.kode_bahan}</small>
                        <input type="hidden" name="id_detail[]" value="${item.id_detail}">
                    </td>
                    <td class="text-center">${item.satuan}</td>
                    <td class="text-end">${parseFloat(item.qty_pesan).toLocaleString('id-ID')}</td>
                    <td class="text-end text-success fw-bold">${parseFloat(item.total_qty_diterima).toLocaleString('id-ID')}</td>
                    <td class="text-end ${item.qty_sisa > 0 ? 'text-danger' : 'text-success'} fw-bold">${parseFloat(item.qty_sisa).toLocaleString('id-ID')}</td>
                    <td>${badge}</td>
                    <td>
                        <input type="number" name="qty_diterima[]" class="form-control form-control-sm"
                               min="0" max="${item.qty_sisa}" step="0.01" value="0" ${disabled}
                               placeholder="Max: ${item.qty_sisa}">
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-3">Gagal memuat data.</td></tr>';
        });
}

// Autoload jika preselect
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('sel_po');
    if (sel.value) loadItemPO(sel.value);
});
</script>

<?php require_once '../template/footer.php'; ?>
