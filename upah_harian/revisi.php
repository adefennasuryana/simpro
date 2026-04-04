<?php
/**
 * Upah Harian: revisi.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$id = (int)$_GET['id'];
$q = mysqli_query($koneksi, "
    SELECT u.*, p.nama_proyek, p.kode_proyek 
    FROM pembayaran_upah u
    LEFT JOIN proyek p ON p.id_proyek = u.id_proyek
    WHERE u.id_pembayaran = $id");
$uh = mysqli_fetch_assoc($q);

if (!$uh) {
    set_flash('danger', 'Data upah harian tidak ditemukan.');
    redirect(BASE_URL . '/upah_harian/index.php');
}

// Hanya boleh revisi jika belum "Dibayar" untuk menjaga konsistensi keuangan, 
// atau sesuai kebijakan bisnis (di sini kita izinkan selama status bukan 'Dibayar').
if ($uh['status_pembayaran'] === 'Dibayar') {
    set_flash('warning', 'Data yang sudah berstatus DIBAYAR tidak dapat direvisi.');
    redirect("detail.php?id=$id");
}

$pageTitle = 'Revisi Pembayaran: ' . $uh['nomor_pembayaran'];
$items = mysqli_query($koneksi, "SELECT * FROM pembayaran_upah_detail WHERE id_pembayaran = $id");

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-edit me-2 text-warning"></i>Revisi Pembayaran Upah</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Upah Harian</a></li>
            <li class="breadcrumb-item active">Revisi <?= e($uh['nomor_pembayaran']) ?></li>
        </ol></nav>
    </div>
    <a href="detail.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Batal</a>
</div>

<?php show_flash(); ?>

<form action="revisi_simpan.php" method="POST" id="formUpah">
    <input type="hidden" name="id_pembayaran" value="<?= $id ?>">
    <div class="row g-4">
        <!-- HEADER -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Informasi Header</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Proyek</label>
                        <input type="text" class="form-control bg-light" value="[<?= e($uh['kode_proyek']) ?>] <?= e($uh['nama_proyek']) ?>" readonly>
                        <input type="hidden" name="id_proyek" value="<?= $uh['id_proyek'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Dokumen</label>
                        <input type="text" class="form-control bg-light" value="<?= e($uh['nomor_pembayaran']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_pembayaran" class="form-control" value="<?= $uh['tanggal_pembayaran'] ?>" required>
                    </div>
                    <div class="mb-3 row g-2">
                        <div class="col-6">
                            <label class="form-label">Periode Dari <span class="text-danger">*</span></label>
                            <input type="date" name="periode_dari" id="periode_dari" class="form-control" value="<?= $uh['periode_dari'] ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Sampai <span class="text-danger">*</span></label>
                            <input type="date" name="periode_sampai" id="periode_sampai" class="form-control" value="<?= $uh['periode_sampai'] ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan Revisi <span class="text-danger">*</span></label>
                        <textarea name="keterangan" rows="3" class="form-control" placeholder="Alasan melakukan revisi..." required></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Bayar (Setelah Revisi)</h6>
                    <h2 class="fw-bold mb-0" id="grand_total_text"><?= rupiah($uh['total_pembayaran']) ?></h2>
                    <input type="hidden" name="total_pembayaran" id="total_pembayaran" value="<?= $uh['total_pembayaran'] ?>">
                </div>
            </div>
        </div>

        <!-- DETAILS -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Detail Pekerja & Kalkulasi Upah</h6>
                    <button type="button" class="btn btn-sm btn-success" id="btnAddItem"><i class="fas fa-plus me-1"></i> Tambah Pekerja</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle" id="tableUpah" style="font-size:0.85rem">
                        <thead class="table-light text-center" style="white-space: nowrap;">
                            <tr>
                                <th style="min-width: 160px;">Nama Pekerja</th>
                                <th style="min-width: 120px;">Jabatan</th>
                                <th style="min-width: 80px;">Masuk (Hari)</th>
                                <th style="min-width: 130px;">Upah/Hari</th>
                                <th style="min-width: 110px;">Lembur</th>
                                <th style="min-width: 110px;">Tambahan</th>
                                <th style="min-width: 110px;">Potongan (-)</th>
                                <th style="min-width: 130px;">Subtotal</th>
                                <th width="50">Hapus</th>
                            </tr>
                        </thead>
                        <tbody id="upah_items">
                            <?php while($item = mysqli_fetch_assoc($items)): ?>
                            <tr>
                                <td><input type="text" name="pekerja[]" class="form-control form-control-sm" value="<?= e($item['nama_pekerja']) ?>" required></td>
                                <td><input type="text" name="jabatan[]" class="form-control form-control-sm" value="<?= e($item['jabatan']) ?>" required></td>
                                <td><input type="number" name="jumlah_hari[]" class="form-control form-control-sm text-center jml_hari" min="0" step="0.5" value="<?= floatval($item['jumlah_hari']) ?>" required></td>
                                <td><input type="number" name="upah_harian[]" class="form-control form-control-sm text-end upah_harian" min="0" value="<?= floatval($item['upah_harian']) ?>" required></td>
                                <td><input type="number" name="lembur[]" class="form-control form-control-sm text-end lembur" min="0" value="<?= floatval($item['lembur']) ?>"></td>
                                <td><input type="number" name="tambahan[]" class="form-control form-control-sm text-end tambahan" min="0" value="<?= floatval($item['tambahan']) ?>"></td>
                                <td><input type="number" name="potongan[]" class="form-control form-control-sm text-end text-danger potongan" min="0" value="<?= floatval($item['potongan']) ?>"></td>
                                <td class="text-end bg-light fw-bold">
                                    <span class="subtotal_text"><?= rupiah($item['subtotal']) ?></span>
                                    <input type="hidden" name="subtotal[]" class="subtotal_val" value="<?= $item['subtotal'] ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="hapusRow(this)"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-end">
                    <button type="submit" class="btn btn-warning px-4 fw-bold"><i class="fas fa-check-circle me-1"></i> Simpan Perubahan Revisi</button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
/* Hilangkan arrow spin box */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}
input[type=number] {
    -moz-appearance: textfield;
}
</style>

<script>
let defaultHari = 1;

document.getElementById('periode_dari').addEventListener('change', hitungHariOtomatis);
document.getElementById('periode_sampai').addEventListener('change', hitungHariOtomatis);

function hitungHariOtomatis() {
    let tglMulai = document.getElementById('periode_dari').value;
    let tglAkhir = document.getElementById('periode_sampai').value;
    
    if (tglMulai && tglAkhir) {
        let d1 = new Date(tglMulai);
        let d2 = new Date(tglAkhir);
        if (d2 >= d1) {
            let diffTime = Math.abs(d2 - d1);
            defaultHari = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            document.querySelectorAll('.jml_hari').forEach(inp => {
                inp.value = defaultHari;
                calculateSubtotal(inp.closest('tr'));
            });
        }
    }
}

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits:0 }).format(number);
}

function calculateSubtotal(row) {
    let jmlHari = parseFloat(row.querySelector('.jml_hari').value) || 0;
    let upahHarian = parseFloat(row.querySelector('.upah_harian').value) || 0;
    let lembur = parseFloat(row.querySelector('.lembur').value) || 0;
    let tambahan = parseFloat(row.querySelector('.tambahan').value) || 0;
    let potongan = parseFloat(row.querySelector('.potongan').value) || 0;
    
    let subtotal = (jmlHari * upahHarian) + lembur + tambahan - potongan;
    if (subtotal < 0) subtotal = 0;
    
    row.querySelector('.subtotal_text').innerText = formatRupiah(subtotal);
    row.querySelector('.subtotal_val').value = subtotal;
    
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.subtotal_val').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('grand_total_text').innerText = formatRupiah(total);
    document.getElementById('total_pembayaran').value = total;
}

function hapusRow(btn) {
    btn.closest('tr').remove();
    calculateGrandTotal();
}

document.getElementById('btnAddItem').addEventListener('click', function() {
    let row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="pekerja[]" class="form-control form-control-sm" required placeholder="Nama Pekerja..."></td>
        <td><input type="text" name="jabatan[]" class="form-control form-control-sm" required placeholder="Tukang / Mandor"></td>
        <td><input type="number" name="jumlah_hari[]" class="form-control form-control-sm text-center jml_hari" min="0" step="0.5" value="${defaultHari}" required></td>
        <td><input type="number" name="upah_harian[]" class="form-control form-control-sm text-end upah_harian" min="0" value="0" required></td>
        <td><input type="number" name="lembur[]" class="form-control form-control-sm text-end lembur" min="0" value="0"></td>
        <td><input type="number" name="tambahan[]" class="form-control form-control-sm text-end tambahan" min="0" value="0"></td>
        <td><input type="number" name="potongan[]" class="form-control form-control-sm text-end text-danger potongan" min="0" value="0"></td>
        <td class="text-end bg-light fw-bold">
            <span class="subtotal_text">Rp 0</span>
            <input type="hidden" name="subtotal[]" class="subtotal_val" value="0">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger" onclick="hapusRow(this)"><i class="fas fa-times"></i></button>
        </td>
    `;
    document.getElementById('upah_items').appendChild(row);
    attachEvents(row);
});

function attachEvents(row) {
    const inputs = row.querySelectorAll('.jml_hari, .upah_harian, .lembur, .tambahan, .potongan');
    inputs.forEach(inp => {
        inp.addEventListener('input', function() {
            calculateSubtotal(row);
        });
    });
}

// Attach events to existing rows
document.querySelectorAll('#upah_items tr').forEach(row => attachEvents(row));
</script>

<?php require_once '../template/footer.php'; ?>
