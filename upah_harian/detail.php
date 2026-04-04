<?php
/**
 * Upah Harian: detail.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$id = (int)$_GET['id'];
$q = mysqli_query($koneksi, "
    SELECT u.*, p.nama_proyek, p.kode_proyek, u2.nama as nama_input 
    FROM pembayaran_upah u
    LEFT JOIN proyek p ON p.id_proyek = u.id_proyek
    LEFT JOIN users u2 ON u2.id_user = u.id_user_input
    WHERE u.id_pembayaran = $id");
$uh = mysqli_fetch_assoc($q);

if (!$uh) {
    set_flash('danger', 'Data upah harian tidak ditemukan.');
    redirect(BASE_URL . '/upah_harian/index.php');
}

$pageTitle = 'Detail Pembayaran: ' . $uh['nomor_pembayaran'];
$role = $_SESSION['role'];

// Action Ubah Status
if (isset($_GET['ubah_status']) && in_array($role, ['admin','manajer','purchasing'])) {
    $st = $_GET['ubah_status'];
    if (in_array($st, ['Draft','Diajukan','Disetujui','Dibayar'])) {
        mysqli_query($koneksi, "UPDATE pembayaran_upah SET status_pembayaran = '$st' WHERE id_pembayaran = $id");
        set_flash('success', "Status pembayaran diubah menjadi $st.");
    }
    redirect("detail.php?id=$id");
}

$items = mysqli_query($koneksi, "SELECT * FROM pembayaran_upah_detail WHERE id_pembayaran = $id");

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-file-invoice-dollar me-2 text-primary"></i><?= e($uh['nomor_pembayaran']) ?></h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Upah Harian</a></li>
            <li class="breadcrumb-item active"><?= e($uh['nomor_pembayaran']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
        <?php if ($uh['status_pembayaran'] !== 'Dibayar'): ?>
        <a href="revisi.php?id=<?= $id ?>" class="btn btn-warning"><i class="fas fa-edit me-1"></i>Revisi</a>
        <?php endif; ?>
        <a href="cetak.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-dark"><i class="fas fa-print me-1"></i>Cetak</a>
        
        <div class="dropdown">
            <button class="btn btn-warning dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-sync-alt me-1"></i>Ubah Status</button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?id=<?= $id ?>&ubah_status=Diajukan">Diajukan ke Manajer</a></li>
                <li><a class="dropdown-item" href="?id=<?= $id ?>&ubah_status=Disetujui">Disetujui</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-success fw-bold" href="?id=<?= $id ?>&ubah_status=Dibayar">Tandai Dibayar</a></li>
            </ul>
        </div>
    </div>
</div>

<?php show_flash(); ?>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Informasi Proyek & Pembayaran</span>
                <?php
                $s = $uh['status_pembayaran'];
                $bdg = $s === 'Draft' ? 'secondary' : ($s === 'Diajukan' ? 'warning' : ($s === 'Disetujui' ? 'info' : 'success'));
                echo "<span class='badge bg-$bdg'>$s</span>";
                ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td width="150" class="text-muted">Proyek</td><td class="fw-bold">[<?= e($uh['kode_proyek']) ?>] <br> <?= e($uh['nama_proyek']) ?></td></tr>
                            <tr><td class="text-muted">Tanggal Bayar</td><td><i class="far fa-calendar-check text-primary me-1"></i><?= tgl_indo($uh['tanggal_pembayaran']) ?></td></tr>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td width="120" class="text-muted">Periode Kerja</td><td class="fw-semibold text-danger"><?= date('d/m/Y', strtotime($uh['periode_dari'])) ?> - <?= date('d/m/Y', strtotime($uh['periode_sampai'])) ?></td></tr>
                            <tr><td class="text-muted">Diajukan Oleh</td><td><?= e($uh['nama_input']) ?></td></tr>
                        </table>
                    </div>
                </div>
                <?php if($uh['keterangan']): ?>
                <div class="mt-3 p-3 bg-light border rounded">
                    <small class="text-muted d-block fw-bold mb-1">Catatan</small>
                    <?= nl2br(e($uh['keterangan'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><i class="fas fa-list me-2"></i>Rincian Distribusi Pembayaran Tenaga Kerja</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:0.85rem">
                    <thead class="table-light text-center">
                        <tr>
                            <th>No</th>
                            <th class="text-start">Pekerja</th>
                            <th>Hari</th>
                            <th class="text-end">Upah/Hr</th>
                            <th class="text-end">Lembur</th>
                            <th class="text-end">Extra</th>
                            <th class="text-end text-danger">Potong</th>
                            <th class="text-end fw-bold">Subtotal Diterima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($d = mysqli_fetch_assoc($items)): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold"><?= e($d['nama_pekerja']) ?></div>
                                <div class="text-muted small"><?= e($d['jabatan']) ?></div>
                            </td>
                            <td class="text-center fw-semibold"><?= floatval($d['jumlah_hari']) ?></td>
                            <td class="text-end"><?= rupiah($d['upah_harian']) ?></td>
                            <td class="text-end text-muted"><?= $d['lembur']>0 ? rupiah($d['lembur']) : '-' ?></td>
                            <td class="text-end text-muted"><?= $d['tambahan']>0 ? rupiah($d['tambahan']) : '-' ?></td>
                            <td class="text-end text-danger"><?= $d['potongan']>0 ? rupiah($d['potongan']) : '-' ?></td>
                            <td class="text-end fw-bold text-success"><?= rupiah($d['subtotal']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card bg-success text-white border-0 shadow-sm mb-4">
            <div class="card-body text-center p-4">
                <div class="opacity-75 mb-1"><i class="fas fa-wallet fa-2x"></i></div>
                <h5 class="fw-normal mb-3 text-white-50">Total Nominal Dibayarkan</h5>
                <h2 class="fw-bold mb-0"><?= rupiah($uh['total_pembayaran']) ?></h2>
            </div>
        </div>

        <?php 
        $logs = mysqli_query($koneksi, "SELECT l.*, u.nama FROM pembayaran_upah_log l JOIN users u ON u.id_user = l.id_user_revisi WHERE l.id_pembayaran = $id ORDER BY l.tanggal_revisi DESC");
        if (mysqli_num_rows($logs) > 0):
        ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold small text-uppercase text-muted">History Revisi</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <?php while($log = mysqli_fetch_assoc($logs)): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-primary fw-bold text-truncate" style="max-width: 100px;"><?= e($log['nama']) ?></span>
                            <span class="text-muted" style="font-size:0.75rem"><?= date('d/m/y H:i', strtotime($log['tanggal_revisi'])) ?></span>
                        </div>
                        <div class="mb-1 text-dark" style="font-size:0.8rem">
                            <?= rupiah($log['nominal_lama']) ?> <i class="fas fa-long-arrow-alt-right mx-1 text-muted"></i> <?= rupiah($log['nominal_baru']) ?>
                        </div>
                        <div class="text-muted italic" style="font-size: 0.75rem; border-left: 2px solid #ddd; padding-left: 5px;">
                            "<?= e($log['keterangan_revisi']) ?>"
                        </div>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
