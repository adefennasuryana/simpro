<?php
/**
 * Dashboard: index.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$pageTitle = 'Dashboard Operasional';
$role = $_SESSION['role'] ?? '';
$tgl_hari_ini = date('Y-m-d');

// ==========================================
// 1. STATISIK UTAMA
// ==========================================
$jml_proyek = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM proyek WHERE status='aktif'"));
$jml_mr     = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM permintaan_bahan"));
$jml_po     = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM po"));
$jml_terima = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM penerimaan"));
$jml_upah   = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM pembayaran_upah"));
$nilai_upah = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_pembayaran), 0) FROM pembayaran_upah WHERE status_pembayaran IN ('Disetujui', 'Dibayar')"));

// ==========================================
// 2. CARD STATUS PENTING
// ==========================================
$mr_menunggu = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM permintaan_bahan WHERE status_permintaan='Diajukan'"));
$mr_sebagian = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM permintaan_bahan WHERE status_permintaan='Terpenuhi Sebagian'"));
$mr_diproses = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM permintaan_bahan WHERE status_permintaan='Diproses ke PO'"));
$po_selesai  = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COUNT(*) FROM po WHERE status_po='selesai'"));

// ==========================================
// 3. RINGKASAN HARI INI
// ==========================================
$transaksi_hari_ini = mysqli_fetch_column(mysqli_query($koneksi, "SELECT (SELECT COUNT(*) FROM po WHERE tanggal_po = '$tgl_hari_ini') + (SELECT COUNT(*) FROM permintaan_bahan WHERE tanggal_permintaan = '$tgl_hari_ini') + (SELECT COUNT(*) FROM penerimaan WHERE tanggal_terima = '$tgl_hari_ini')"));
$nominal_hari_ini = mysqli_fetch_column(mysqli_query($koneksi, "SELECT COALESCE(SUM(total), 0) FROM po WHERE tanggal_po = '$tgl_hari_ini'"));

// ==========================================
// 4. DATA TABEL TERBARU
// ==========================================
// MR Terbaru
$mr_terbaru = mysqli_query($koneksi, "SELECT pb.id_permintaan, pb.nomor_permintaan, pb.tanggal_permintaan, pb.status_permintaan, pr.nama_proyek, u.nama AS pemohon FROM permintaan_bahan pb LEFT JOIN proyek pr ON pr.id_proyek = pb.id_proyek LEFT JOIN users u ON u.id_user = pb.id_user ORDER BY pb.created_at DESC LIMIT 5");

// PO Terbaru
$po_terbaru = mysqli_query($koneksi, "SELECT p.id_po, p.nomor_po, p.tanggal_po, p.total, p.status_po, pr.nama_proyek, s.nama_supplier FROM po p LEFT JOIN proyek pr ON pr.id_proyek = p.id_proyek LEFT JOIN supplier s ON s.id_supplier = p.id_supplier ORDER BY p.created_at DESC LIMIT 5");

// Proyek Aktif
$proyek_aktif = mysqli_query($koneksi, "SELECT * FROM proyek WHERE status = 'aktif' ORDER BY tanggal_mulai DESC LIMIT 5");
?>
<style>
    .card-hover-effect {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .card-hover-effect:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
</style>
<?php require_once '../template/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <div>
        <h3 class="fw-bold mb-1">Halo, <?= e($_SESSION['nama']) ?>! 👋</h3>
        <p class="text-muted mb-0">Selamat datang di sistem manajemen proyek dan *supply chain* Anda.</p>
    </div>
    <div class="text-end">
        <div class="text-muted small">Waktu Sistem</div>
        <div class="fw-bold"><i class="far fa-calendar-alt text-primary me-1"></i> <?= tgl_indo($tgl_hari_ini) ?></div>
    </div>
</div>

<?php show_flash(); ?>

<!-- 1. Statistik Utama (Top Cards) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm bg-primary text-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 text-white-50">Total Proyek Aktif</h6>
                    <h2 class="fw-bold mb-0"><?= $jml_proyek ?></h2>
                </div>
                <div class="display-5 opacity-50"><i class="fas fa-project-diagram"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm bg-warning text-dark h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 text-dark" style="opacity: 0.7;">Permintaan Bahan</h6>
                    <h2 class="fw-bold mb-0"><?= $jml_mr ?></h2>
                </div>
                <div class="display-5 opacity-50"><i class="fas fa-clipboard-list"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm bg-info text-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 text-white-50">Purchase Order (PO)</h6>
                    <h2 class="fw-bold mb-0"><?= $jml_po ?></h2>
                </div>
                <div class="display-5 opacity-50"><i class="fas fa-file-invoice-dollar"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm bg-success text-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 text-white-50">Penerimaan Barang</h6>
                    <h2 class="fw-bold mb-0"><?= $jml_terima ?></h2>
                </div>
                <div class="display-5 opacity-50"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 col-xl">
        <div class="card border-0 shadow-sm bg-dark text-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 text-white-50">Pembayaran Upah</h6>
                    <h2 class="fw-bold mb-0"><?= $jml_upah ?></h2>
                </div>
                <div class="display-5 opacity-50"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- 2. Status Menunggu Tindakan (Card Status Workflow) -->
    <div class="col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-tasks me-2 text-primary"></i>Status Realisasi & Workflow</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 h-100">
                    <div class="col-md-6 col-xl-3">
                        <div class="p-3 border rounded shadow-sm text-center h-100 d-flex flex-column justify-content-center position-relative card-hover-effect <?= $mr_menunggu > 0 ? 'border-warning bg-warning bg-opacity-10' : 'bg-light' ?>">
                            <div class="display-6 fw-bold text-warning mb-1"><?= $mr_menunggu ?></div>
                            <div class="small fw-semibold text-muted text-uppercase">Permintaan Bahan Menunggu Approval</div>
                            <a href="../permintaan/index.php?status=Diajukan" class="stretched-link"></a>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="p-3 border rounded shadow-sm text-center h-100 d-flex flex-column justify-content-center position-relative card-hover-effect border-secondary bg-light">
                            <div class="display-6 fw-bold text-secondary mb-1"><?= $mr_sebagian ?></div>
                            <div class="small fw-semibold text-muted text-uppercase">Permintaan Bahan Terpenuhi Sebagian</div>
                            <a href="../permintaan/index.php?status=Terpenuhi Sebagian" class="stretched-link"></a>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="p-3 border rounded shadow-sm text-center h-100 d-flex flex-column justify-content-center position-relative card-hover-effect <?= $mr_diproses > 0 ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' ?>">
                            <div class="display-6 fw-bold text-primary mb-1"><?= $mr_diproses ?></div>
                            <div class="small fw-semibold text-muted text-uppercase">Permintaan Bahan Diproses ke PO</div>
                            <a href="../permintaan/index.php?status=Diproses ke PO" class="stretched-link"></a>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="p-3 border rounded shadow-sm text-center h-100 d-flex flex-column justify-content-center position-relative card-hover-effect border-success bg-success bg-opacity-10">
                            <div class="display-6 fw-bold text-success mb-1"><?= $po_selesai ?></div>
                            <div class="small fw-semibold text-success text-uppercase">PO Selesai / Ditutup</div>
                            <a href="../po/index.php?status=selesai" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 3. Ringkasan Hari Ini -->
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100 bg-dark text-white" style="background-image: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
            <div class="card-body d-flex flex-column justify-content-center text-center p-4">
                <i class="fas fa-bolt fa-3x text-warning mb-3"></i>
                <h5 class="fw-bold mb-1">Aktivitas Hari Ini</h5>
                <p class="text-white-50 small mb-4">Volume pencatatan transaksi masuk hari ini</p>
                
                <div class="d-flex justify-content-center gap-4 text-start">
                    <div>
                        <div class="text-white-50 small">Dokumen</div>
                        <h3 class="fw-bold mb-0"><?= $transaksi_hari_ini ?></h3>
                    </div>
                    <div class="border-start border-secondary mb-2 mt-2"></div>
                    <div>
                        <div class="text-white-50 small">Nilai Pembelian</div>
                        <h4 class="fw-bold mb-0 text-success"><?= rupiah($nominal_hari_ini) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 4. Tabel Permintaan Bahan Terbaru -->
    <div class="col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-clipboard me-2 text-warning"></i>Permintaan Bahan Terbaru</h6>
                <a href="../permintaan/index.php" class="btn btn-sm btn-light">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th>No. MR</th>
                            <th>Tanggal</th>
                            <th>Proyek</th>
                            <th>Pemohon</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($mr_terbaru) === 0): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada permintaan.</td></tr>
                        <?php else: while ($m = mysqli_fetch_assoc($mr_terbaru)): ?>
                        <tr>
                            <td class="fw-semibold"><a href="../permintaan/detail.php?id=<?= $m['id_permintaan'] ?>"><?= e($m['nomor_permintaan']) ?></a></td>
                            <td class="small"><?= date('d/m/Y', strtotime($m['tanggal_permintaan'])) ?></td>
                            <td class="small text-truncate" style="max-width: 150px;" title="<?= e($m['nama_proyek']) ?>"><?= e($m['nama_proyek']) ?></td>
                            <td class="small"><?= e($m['pemohon']) ?></td>
                            <td class="text-center"><?= badge_mr($m['status_permintaan']) ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5. Tabel PO Terbaru -->
    <div class="col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Purchase Order Terbaru</h6>
                <a href="../po/index.php" class="btn btn-sm btn-light">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th>No. PO</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($po_terbaru) === 0): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada PO tercatat.</td></tr>
                        <?php else: while ($p = mysqli_fetch_assoc($po_terbaru)): ?>
                        <tr>
                            <td class="fw-semibold"><a href="../po/detail.php?id=<?= $p['id_po'] ?>"><?= e($p['nomor_po']) ?></a></td>
                            <td class="small"><?= date('d/m/Y', strtotime($p['tanggal_po'])) ?></td>
                            <td class="small text-truncate" style="max-width: 150px;" title="<?= e($p['nama_supplier']) ?>"><?= e($p['nama_supplier']) ?></td>
                            <td class="small text-end fw-bold"><?= rupiah($p['total']) ?></td>
                            <td class="text-center"><?= badge_po_status($p['status_po']) ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 6. Tabel Proyek Aktif (Full Width Below) -->
    <div class="col-12 mt-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-building me-2 text-success"></i>Daftar Proyek Aktif Teratas</h6>
                <a href="../proyek/index.php" class="btn btn-sm btn-outline-success">Kelola Proyek</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="150">Kode</th>
                            <th>Nama Proyek</th>
                            <th>Lokasi</th>
                            <th width="200">Rentang Waktu</th>
                            <th class="text-center" width="120">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($proyek_aktif) === 0): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data proyek aktif.</td></tr>
                        <?php else: while ($pry = mysqli_fetch_assoc($proyek_aktif)): ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= e($pry['kode_proyek']) ?></td>
                            <td class="fw-semibold"><?= e($pry['nama_proyek']) ?></td>
                            <td><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= e($pry['lokasi']) ?></td>
                            <td class="small text-muted">
                                <?= date('d/m/y', strtotime($pry['tanggal_mulai'])) ?> s/d <?= date('d/m/y', strtotime($pry['tanggal_selesai'])) ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success rounded-pill px-3">Aktif</span>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
