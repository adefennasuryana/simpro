<?php
/**
 * Permintaan Bahan: detail.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];

// Ambil data header
$sql = "SELECT pb.*, pr.nama_proyek, pr.kode_proyek, pr.lokasi, u.nama AS nama_pemohon,
               ua.nama AS nama_approval, po.nomor_po
        FROM permintaan_bahan pb
        LEFT JOIN proyek pr ON pr.id_proyek = pb.id_proyek
        LEFT JOIN users  u  ON u.id_user    = pb.id_user
        LEFT JOIN users  ua ON ua.id_user   = pb.id_user_approval
        LEFT JOIN po     po ON po.id_po     = pb.id_po
        WHERE pb.id_permintaan = $id LIMIT 1";
$mr = mysqli_fetch_assoc(mysqli_query($koneksi, $sql));

if (!$mr) {
    set_flash('danger', 'Detail permintaan tidak ditemukan.');
    redirect(BASE_URL . '/permintaan/index.php');
}

$pageTitle = 'Detail Permintaan: ' . $mr['nomor_permintaan'];

// Action Ajukan (jika status masih Draft)
if (isset($_GET['ajukan']) && $_GET['ajukan'] == 1 && $mr['status_permintaan'] === 'Draft' && in_array($role, ['admin', 'purchasing'])) {
    mysqli_query($koneksi, "UPDATE permintaan_bahan SET status_permintaan = 'Diajukan' WHERE id_permintaan = $id");
    set_flash('success', "Permintaan berhasil diajukan ke Manajer.");
    redirect("detail.php?id=$id");
}

// Ambil item detail
$items = mysqli_query($koneksi,
    "SELECT pbd.*, b.kode_bahan, b.nama_bahan
     FROM permintaan_bahan_detail pbd
     LEFT JOIN bahan_baku b ON b.id_bahan = pbd.id_bahan
     WHERE pbd.id_permintaan = $id ORDER BY pbd.id_detail_permintaan");

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><i class="fas fa-file-alt me-2 text-primary"></i><?= e($mr['nomor_permintaan']) ?></h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Permintaan Bahan</a></li>
            <li class="breadcrumb-item active"><?= e($mr['nomor_permintaan']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
        
        <?php if ($mr['status_permintaan'] === 'Draft' && in_array($role, ['admin', 'purchasing'])): ?>
            <a href="tambah.php?edit=<?= $id ?>" class="btn btn-warning">
                <i class="fas fa-edit me-1"></i>Edit Permintaan
            </a>
            <a href="?id=<?= $id ?>&ajukan=1" class="btn btn-primary btn-status-confirm" data-msg="Ajukan permintaan ini ke Manajer untuk disetujui?">
                <i class="fas fa-paper-plane me-1"></i>Ajukan ke Manajer
            </a>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'manajer']) && $mr['status_permintaan'] === 'Diajukan'): ?>
            <a href="approval.php?id=<?= $id ?>" class="btn btn-success">
                <i class="fas fa-check-double me-1"></i>Proses Approval
            </a>
        <?php endif; ?>

        <?php if ($mr['status_permintaan'] === 'Disetujui' && in_array($role, ['admin', 'purchasing'])): ?>
            <a href="proses_po.php?id=<?= $id ?>" class="btn btn-primary">
                <i class="fas fa-file-invoice me-1"></i>Buat PO dari Permintaan Ini
            </a>
        <?php endif; ?>
    </div>
</div>

<?php show_flash(); ?>

<div class="row g-3">
    <!-- Panel Informasi -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Informasi Permintaan</span>
                <?= badge_mr($mr['status_permintaan']) ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="160">Nomor MR</td><td class="fw-bold"><?= e($mr['nomor_permintaan']) ?></td></tr>
                            <tr><td class="text-muted">Tanggal Request</td><td><?= tgl_indo($mr['tanggal_permintaan']) ?></td></tr>
                            <tr><td class="text-muted">Pemohon</td><td><?= e($mr['nama_pemohon']) ?></td></tr>
                            <tr>
                                <td class="text-muted">Tanggal Dibutuhkan</td>
                                <td class="fw-bold text-danger"><?= $mr['tanggal_dibutuhkan'] ? tgl_indo($mr['tanggal_dibutuhkan']) : '-' ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="120">Proyek</td><td class="fw-bold"><span class="badge bg-light text-dark border"><?= e($mr['kode_proyek']) ?></span> <br> <?= e($mr['nama_proyek']) ?></td></tr>
                            <tr><td class="text-muted">Lokasi Proyek</td><td><?= e($mr['lokasi']) ?></td></tr>
                        </table>
                    </div>
                    
                    <?php if ($mr['keterangan']): ?>
                    <div class="col-12 mt-3">
                        <div class="alert alert-light mb-0 py-2 border">
                            <small class="text-muted d-block">Keterangan / Keperluan Umum:</small>
                            <?= nl2br(e($mr['keterangan'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>

        <!-- Tabel Item Bahan -->
        <div class="card">
            <div class="card-header"><i class="fas fa-list me-2"></i>Daftar Bahan Baku yang Diminta</div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Bahan Baku</th>
                            <th class="text-end">Qty Diminta</th>
                            <th class="text-end text-muted">Alokasi Material</th>
                            <th class="text-end text-muted">Qty PO</th>
                            <th class="text-end text-success">Qty Terpenuhi</th>
                            <th class="text-end text-danger">Qty Sisa</th>
                            <th>Status Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        $total_estimasi = 0;
                        while ($it = mysqli_fetch_assoc($items)): 
                            $subtotal = $it['qty_diminta'] * $it['estimasi_harga'];
                            $total_estimasi += $subtotal;
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold"><?= e($it['nama_bahan']) ?></div>
                                <div class="small text-muted">[<?= e($it['kode_bahan']) ?>]</div>
                                <?php if($it['spesifikasi']): ?><div class="small mt-1 text-primary">Spec: <?= e($it['spesifikasi']) ?></div><?php endif; ?>
                                <?php if($it['keperluan'] || $it['catatan']): ?>
                                    <div class="small mt-1 mt-1 text-muted">
                                        <?= $it['keperluan'] ? "Kep: " . e($it['keperluan']) : "" ?> 
                                        <?= $it['catatan'] ? " | Cat: " . e($it['catatan']) : "" ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold"><?= number_format($it['qty_diminta'], 2, ',', '.') ?> <?= e($it['satuan']) ?></td>
                            <td class="text-end text-muted"><?= number_format($it['qty_alokasi_material'], 2, ',', '.') ?></td>
                            <td class="text-end text-muted"><?= number_format($it['qty_po'], 2, ',', '.') ?></td>
                            <td class="text-end text-success fw-bold"><?= number_format($it['qty_terpenuhi'], 2, ',', '.') ?></td>
                            <td class="text-end text-danger"><?= number_format($it['qty_sisa'], 2, ',', '.') ?></td>
                            <td>
                                <?php
                                $si = $it['status_item'];
                                $bdg = $si === 'Selesai' ? 'success' : ($si === 'Terpenuhi Sebagian' ? 'warning' : 'secondary');
                                echo "<span class='badge bg-$bdg'>$si</span>";
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="8" class="text-end fw-bold text-primary">Tabel pelacakan realisasi MR. (Estimasi Total Awal: <?= rupiah($total_estimasi) ?>)</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar Info / Approval -->
    <div class="col-lg-4">
        
        <?php if (in_array($mr['status_permintaan'], ['Diproses ke PO', 'Terpenuhi Sebagian', 'Selesai']) && $mr['id_po']): ?>
        <div class="card mb-3 border-primary">
            <div class="card-header bg-primary text-white"><i class="fas fa-link me-2"></i>Tautan ke PO</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Permintaan ini sudah diproses dan terhubung dengan dokumen Purchase Order:</p>
                <div class="d-grid">
                    <a href="../po/detail.php?id=<?= $mr['id_po'] ?>" class="btn btn-outline-primary fw-bold">
                        <?= e($mr['nomor_po']) ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-user-check me-2"></i>Status Approval</div>
            <div class="card-body">
                <?php if ($mr['status_permintaan'] === 'Draft'): ?>
                    <p class="text-muted small mb-0"><i class="fas fa-info-circle"></i> Masih dalam bentuk Draft. Silakan klik Ajukan agar diproses oleh Manajer.</p>
                <?php elseif ($mr['status_permintaan'] === 'Diajukan'): ?>
                    <p class="text-warning small fw-bold mb-0"><i class="fas fa-clock"></i> Menunggu persetujuan Manajer Proyek.</p>
                <?php else: ?>
                    <div class="mb-2">
                        <span class="text-muted small">Keputusan oleh:</span><br>
                        <strong><?= e($mr['nama_approval'] ?? '-') ?></strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Waktu Approval:</span><br>
                        <?= $mr['tgl_approval'] ? date('d M Y H:i', strtotime($mr['tgl_approval'])) : '-' ?>
                    </div>
                    <div>
                        <span class="text-muted small">Catatan Manajer:</span><br>
                        <div class="p-2 bg-light border rounded mt-1 small">
                            <?= $mr['catatan_approval'] ? nl2br(e($mr['catatan_approval'])) : '<i>Tidak ada catatan</i>' ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
