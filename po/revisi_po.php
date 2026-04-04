<?php
/**
 * Purchase Order: revisi_po.php
 * Form alokasi ketersediaan barang di gudang/proyek untuk memotong jumlah PO.
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin', 'purchasing']);

$id = (int)($_GET['id'] ?? 0);

$po = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT po.*, p.nama_proyek, s.nama_supplier 
     FROM po 
     LEFT JOIN proyek p ON p.id_proyek = po.id_proyek
     LEFT JOIN supplier s ON s.id_supplier = po.id_supplier
     WHERE po.id_po = $id LIMIT 1"
));

if (!$po) {
    set_flash('danger', 'Detail PO tidak ditemukan.');
    redirect(BASE_URL . '/po/index.php');
}

$pageTitle = 'Revisi PO: ' . $po['nomor_po'];
$errors = [];

// Tarik data item PO & log lama (jika sebelumnya sudah pernah direvisi)
$items = mysqli_query($koneksi,
    "SELECT pd.*, b.kode_bahan, b.nama_bahan, b.satuan,
            (SELECT qty_diminta FROM po_revisi_log WHERE id_detail_po = pd.id_detail ORDER BY id_revisi DESC LIMIT 1) as histori_qty_diminta,
            (SELECT qty_alokasi_material FROM po_revisi_log WHERE id_detail_po = pd.id_detail ORDER BY id_revisi DESC LIMIT 1) as histori_alokasi
     FROM po_detail pd
     LEFT JOIN bahan_baku b ON b.id_bahan = pd.id_bahan
     WHERE pd.id_po = $id ORDER BY pd.id_detail");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alasan_revisi = trim($_POST['alasan_revisi'] ?? '');
    $id_details    = $_POST['id_detail_po'] ?? [];
    $alokasis      = $_POST['qty_alokasi'] ?? [];
    
    if ($alasan_revisi === '') {
        $errors[] = 'Alasan revisi wajib diisi untuk dokumentasi.';
    }

    if (empty($errors)) {
        $koneksi->begin_transaction();
        try {
            $total_po_baru = 0;
            $id_user = $_SESSION['id_user'];
            
            // Loop semua item yang direvisi
            foreach ($id_details as $i => $id_det) {
                $id_det = (int)$id_det;
                $alokasi = (float)($alokasis[$i] ?? 0);
                
                // Cari data detail asli
                $det = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM po_detail WHERE id_detail = $id_det"));
                if (!$det) continue;

                // Tentukan jumlah asli yang diminta (bisa dari histori lama, atau dari detail saat ini)
                $qty_pesan_sekarang = (float)$det['qty_pesan'];
                
                // Cari histori original permintaan jika pernah direvisi (mengambil nilai qty_diminta pada revisi pertama)
                $hx = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT qty_diminta FROM po_revisi_log WHERE id_detail_po = $id_det ORDER BY id_revisi ASC LIMIT 1"));
                $qty_diminta_asli = $hx ? (float)$hx['qty_diminta'] : $qty_pesan_sekarang; // Jika belum pernah log, maka qty saat ini yang menjadi dasar. Tapi jika ini revisi ke-N, base-nya adalah histori original.
                
                // Pada form ini, "qty pesan" yang tampil di form adalah $qty_diminta_asli.
                // User mengisi alokasi, maka:
                $qty_revisi = $qty_diminta_asli - $alokasi;
                if ($qty_revisi < 0) $qty_revisi = 0;
                
                // Catat Log Revisi jika alokasinya berbeda atau pertama kali direvisi
                // Walau tidak berubah, catat saja proses revisi massalnya.
                $stmt_log = mysqli_prepare($koneksi, 
                    "INSERT INTO po_revisi_log 
                     (id_po, id_detail_po, qty_diminta, qty_po_awal, qty_material_tersedia, qty_alokasi_material, qty_po_revisi, alasan_revisi, id_user_revisi)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $qty_material_tersedia = $alokasi; // Anggap yg terinput sebagai material tersedia yang dialokasi
                mysqli_stmt_bind_param($stmt_log, 'iidddddsi', 
                    $id, $id_det, $qty_diminta_asli, $qty_pesan_sekarang, $qty_material_tersedia, $alokasi, $qty_revisi, $alasan_revisi, $id_user
                );
                mysqli_stmt_execute($stmt_log);
                
                // Terapkan Qty Baru ke Detail PO & Hitung Subtotal Baru
                $subtotal_baru = $qty_revisi * $det['harga'];
                mysqli_query($koneksi, "UPDATE po_detail SET qty_pesan = $qty_revisi, subtotal = $subtotal_baru WHERE id_detail = $id_det");
                
                $total_po_baru += $subtotal_baru;
            }
            
            // Tambahkan catatan di header PO
            $ket_lama = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT keterangan FROM po WHERE id_po = $id"))['keterangan'];
            $ket_baru = trim($ket_lama . "\n[REVISI] " . date('d/m/Y H:i') . " : " . $alasan_revisi);
            
            // Perbarui header PO
            mysqli_query($koneksi, "UPDATE po SET total = $total_po_baru, status_po = 'direvisi', keterangan = '" . mysqli_real_escape_string($koneksi, $ket_baru) . "' WHERE id_po = $id");
            
            $koneksi->commit();
            set_flash('success', "PO <strong>{$po['nomor_po']}</strong> berhasil direvisi dan dialokasikan ke Material Tersedia.");
            redirect(BASE_URL . '/po/detail.php?id=' . $id);
            
        } catch (Exception $e) {
            $koneksi->rollback();
            $errors[] = "Sistem gagal menyimpan revisi: " . $e->getMessage();
        }
    }
}

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-sliders-h me-2 text-primary"></i>Revisi & Alokasi Internal</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Purchase Order</a></li>
            <li class="breadcrumb-item"><a href="detail.php?id=<?= $id ?>"><?= e($po['nomor_po']) ?></a></li>
            <li class="breadcrumb-item active">Revisi PO</li>
        </ol></nav>
    </div>
</div>

<?php foreach ($errors as $er): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $er ?></div>
<?php endforeach; ?>

<div class="alert alert-warning border-warning shadow-sm">
    <h5><i class="fas fa-exclamation-triangle me-2"></i>Penyesuaian Kebutuhan (Material Sisa)</h5>
    Gunakan fitur ini apabila proyek memiliki sisa material (stok lokal) yang masih bisa dialokasikan.<br>
    Sistem akan mengurangi jumlah di dalam PO berdasarkan alokasi yang Anda tetapkan, dan merekam jejak hitungannya.
</div>

<form method="POST">
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light"><i class="fas fa-list me-2"></i>Detail Material dan Kalkulasi</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="40">#</th>
                        <th>Kode & Item Bahan Baku</th>
                        <th class="text-center" width="130" title="Kebutuhan asli yang diminta awal">Kebutuhan Asli<br><small>(Qty Diminta)</small></th>
                        <th class="text-center" width="130" title="Jumlah pesanan di dalam PO saat ini (Sebelum direvisi)">Qty PO Saat Ini</th>
                        <th width="150" class="text-center" title="Sisa material di proyek">Alokasi Tersedia<br><small>(Material Sisa)</small></th>
                        <th class="text-center bg-danger text-white" width="140">Qty Re-order (Revisi PO)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($it = mysqli_fetch_assoc($items)): 
                        // Tentukan qty dasar asli (qty diminta)
                        $kebutuhan_asli = $it['histori_qty_diminta'] !== null ? $it['histori_qty_diminta'] : $it['qty_pesan'];
                        $alokasi_sebelumnya = $it['histori_alokasi'] !== null ? $it['histori_alokasi'] : 0;
                    ?>
                    <tr>
                        <td class="text-muted"><?= $no++ ?></td>
                        <td>
                            <div class="fw-bold"><?= e($it['nama_bahan']) ?></div>
                            <small class="text-muted">[<?= e($it['kode_bahan']) ?>]</small>
                            <input type="hidden" name="id_detail_po[]" value="<?= $it['id_detail'] ?>">
                        </td>
                        <td class="text-center bg-light fw-bold">
                            <!-- Field Dummy (Reference Only) -->
                            <span class="fs-5 txt-diminta"><?= floatval($kebutuhan_asli) ?></span> <?= e($it['satuan']) ?>
                        </td>
                        <td class="text-center">
                            <?= floatval($it['qty_pesan']) ?> <?= e($it['satuan']) ?>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" name="qty_alokasi[]" class="form-control text-center fw-bold inp-alokasi"
                                       value="<?= floatval($alokasi_sebelumnya) ?>" min="0" step="0.01" oninput="kalkulasiRevisi(this)">
                                <span class="input-group-text"><?= e($it['satuan']) ?></span>
                            </div>
                        </td>
                        <td class="text-center fs-5 text-danger fw-bold bg-light">
                            <span class="txt-revisi"><?= floatval($it['qty_pesan']) ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-header bg-primary text-white"><i class="fas fa-pen-nib me-2"></i>Catatan dan Simpan</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-bold">Alasan Revisi / Pengurangan <span class="text-danger">*</span></label>
                <textarea name="alasan_revisi" class="form-control" rows="3" required placeholder="Contoh: Ditemukan sisa material semen 5 sak di gudang B, maka pemesanan dikurangi."></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger btn-status-confirm" data-msg="Anda yakin ingin menyimpan revisi ini? Ini akan mengubah jumlah pesanan pada supplier.">
                    <i class="fas fa-save me-1"></i>Eksekusi Revisi PO
                </button>
                <a href="detail.php?id=<?= $id ?>" class="btn btn-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>

<script>
function kalkulasiRevisi(input) {
    const baris   = input.closest('tr');
    const diminta = parseFloat(baris.querySelector('.txt-diminta').textContent) || 0;
    let alokasi   = parseFloat(input.value) || 0;
    
    if (alokasi < 0) alokasi = 0;
    if (alokasi > diminta) {
        alert('Set alokasi tidak boleh melebihi jumlah permintaan awal!');
        input.value = diminta;
        alokasi = diminta;
    }
    
    let hasilRevisi = diminta - alokasi;
    baris.querySelector('.txt-revisi').textContent = hasilRevisi;
}
</script>

<?php require_once '../template/footer.php'; ?>
