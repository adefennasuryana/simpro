<?php
/**
 * Permintaan Bahan: proses_po.php
 * Meng-konversi MR (Disetujui) menjadi Draft PO
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin', 'purchasing']);

$id = (int)($_GET['id'] ?? 0);

$mr = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT * FROM permintaan_bahan WHERE id_permintaan = $id LIMIT 1"
));

if (!$mr) {
    set_flash('danger', 'Data permintaan tidak ditemukan.');
    redirect(BASE_URL . '/permintaan/index.php');
}

if ($mr['status_permintaan'] !== 'Disetujui') {
    set_flash('warning', "Hanya permintaan berstatus 'Disetujui' yang bisa diproses ke PO.");
    redirect(BASE_URL . '/permintaan/detail.php?id=' . $id);
}

$pageTitle = 'Proses PO dari Permintaan: ' . $mr['nomor_permintaan'];
$errors    = [];

// Ambil daftar supplier
$supplier_list = mysqli_query($koneksi, "SELECT id_supplier, nama_supplier FROM supplier WHERE status='aktif' ORDER BY nama_supplier");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_supplier = (int)($_POST['id_supplier'] ?? 0);
    
    if ($id_supplier === 0) {
        $errors[] = "Pilih supplier terlebih dahulu.";
    }

    if (empty($errors)) {
        // Mulai proses generate PO
        $koneksi->begin_transaction();
        
        try {
            // 1. Generate Nomor PO
            $nomor_po = generate_nomor($koneksi, 'po', 'nomor_po', 'PO');
            $tgl_po   = date('Y-m-d');
            $id_user  = $_SESSION['id_user'];
            $id_proyek = $mr['id_proyek'];
            
            // 2. Hitung total dari MR detail
            $res_items = mysqli_query($koneksi, "SELECT * FROM permintaan_bahan_detail WHERE id_permintaan = $id");
            $total_po = 0;
            $items_to_insert = [];
            while ($item = mysqli_fetch_assoc($res_items)) {
                $subtotal = $item['qty_diminta'] * $item['estimasi_harga'];
                $total_po += $subtotal;
                $items_to_insert[] = [
                    'id_bahan'   => $item['id_bahan'],
                    'qty_pesan'  => $item['qty_diminta'],
                    'harga'      => $item['estimasi_harga'],
                    'subtotal'   => $subtotal,
                    'keterangan' => $item['catatan'] ? $item['catatan'] : $item['keperluan']
                ];
            }
            
            $ket_po = "Generate otomatis dari MR: " . $mr['nomor_permintaan'] . ($mr['keterangan'] ? " | " . $mr['keterangan'] : "");
            
            // 3. Insert ke Tabel PO (Header)
            $stmt_po = mysqli_prepare($koneksi, 
                "INSERT INTO po (nomor_po, tanggal_po, id_proyek, id_supplier, id_user, total, status_po, keterangan) 
                 VALUES (?, ?, ?, ?, ?, ?, 'draft', ?)"
            );
            mysqli_stmt_bind_param($stmt_po, 'ssiiids', $nomor_po, $tgl_po, $id_proyek, $id_supplier, $id_user, $total_po, $ket_po);
            mysqli_stmt_execute($stmt_po);
            $id_po = mysqli_insert_id($koneksi);
            
            // 4. Insert ke Tabel PO_Detail
            $stmt_po_det = mysqli_prepare($koneksi, 
                "INSERT INTO po_detail (id_po, id_bahan, qty_pesan, harga, subtotal, keterangan) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($items_to_insert as $it) {
                mysqli_stmt_bind_param($stmt_po_det, 'iiddds', 
                    $id_po, $it['id_bahan'], $it['qty_pesan'], $it['harga'], $it['subtotal'], $it['keterangan']
                );
                mysqli_stmt_execute($stmt_po_det);
            }
            
            // 5. Update status MR
            $stmt_mr = mysqli_prepare($koneksi, 
                "UPDATE permintaan_bahan SET status_permintaan = 'Diproses ke PO', id_po = ? WHERE id_permintaan = ?"
            );
            mysqli_stmt_bind_param($stmt_mr, 'ii', $id_po, $id);
            mysqli_stmt_execute($stmt_mr);
            
            // Commit transaksi
            $koneksi->commit();
            
            set_flash('success', "Permintaan <strong>{$mr['nomor_permintaan']}</strong> telah berhasil di-generate menjadi Draft PO. Silakan periksa atau edit detail harganya.");
            
            // Arahkan langsung ke halaman edit PO agar user bisa menyesuaikan harga/item
            redirect(BASE_URL . '/po/edit.php?id=' . $id_po);
            
        } catch (Exception $e) {
            $koneksi->rollback();
            $errors[] = "Gagal memproses PO: " . $e->getMessage();
        }
    }
}

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-magic me-2 text-primary"></i>Buat PO dari Permintaan</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Permintaan Bahan</a></li>
            <li class="breadcrumb-item"><a href="detail.php?id=<?= $id ?>"><?= e($mr['nomor_permintaan']) ?></a></li>
            <li class="breadcrumb-item active">Proses PO</li>
        </ol></nav>
    </div>
</div>

<?php foreach ($errors as $er): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $er ?></div>
<?php endforeach; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3 border-primary shadow-sm">
            <div class="card-header bg-primary text-white">Langkah 1: Pilih Supplier</div>
            <div class="card-body">
                <div class="alert alert-light border small text-muted">
                    <i class="fas fa-info-circle text-primary"></i> 
                    Sistem akan menyalin semua item bahan baku dari permintaan ini ke dalam dokumen Purchase Order. 
                    Anda harus memilih Supplier tujuan terlebih dahulu. Dokumen PO akan tersimpan dalam status <strong>Draft</strong>.
                </div>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Pilih Supplier Pembelian <span class="text-danger">*</span></label>
                        <select name="id_supplier" class="form-select" required>
                            <option value="">-- Pilih Supplier yang terdaftar --</option>
                            <?php while($s = mysqli_fetch_assoc($supplier_list)): ?>
                                <option value="<?= $s['id_supplier'] ?>"><?= e($s['nama_supplier']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 btn-status-confirm" data-msg="Buat PO untuk Supplier terpilih?">
                        <i class="fas fa-file-invoice me-1"></i>Generate Draft PO Sekarang
                    </button>
                    <div class="text-center mt-3">
                        <a href="detail.php?id=<?= $id ?>" class="text-secondary small text-decoration-none border-bottom">Batalkan proses</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">Data akan dimasukkan ke Proyek:</div>
            <div class="card-body">
                <?php 
                $pry = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM proyek WHERE id_proyek = {$mr['id_proyek']}"));
                ?>
                <h5 class="fw-bold mb-1"><?= e($pry['nama_proyek']) ?></h5>
                <p class="text-muted small mb-0">[<?= e($pry['kode_proyek']) ?>] <?= e($pry['lokasi']) ?></p>
                <hr>
                <div class="d-flex justify-content-between text-muted small">
                    <span>Sumber: <?= e($mr['nomor_permintaan']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
