<?php
/**
 * Permintaan Bahan: approval.php
 * Khusus role manajer untuk menyetujui / menolak MR
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin', 'manajer']);

$id = (int)($_GET['id'] ?? 0);

$mr = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT pb.*, pr.nama_proyek, u.nama as nama_pemohon 
     FROM permintaan_bahan pb
     LEFT JOIN proyek pr ON pr.id_proyek = pb.id_proyek
     LEFT JOIN users u ON u.id_user = pb.id_user
     WHERE pb.id_permintaan = $id LIMIT 1"
));

if (!$mr) {
    set_flash('danger', 'Data permintaan tidak ditemukan.');
    redirect(BASE_URL . '/permintaan/index.php');
}

if ($mr['status_permintaan'] !== 'Diajukan') {
    set_flash('warning', "Status permintaan ini bukan 'Diajukan', melainkan '{$mr['status_permintaan']}'.");
    redirect(BASE_URL . '/permintaan/detail.php?id=' . $id);
}

$pageTitle = 'Approval Permintaan: ' . $mr['nomor_permintaan'];
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keputusan = $_POST['keputusan'] ?? ''; // 'Disetujui' atau 'Ditolak'
    $catatan   = trim($_POST['catatan_approval'] ?? '');
    
    if (!in_array($keputusan, ['Disetujui', 'Ditolak'])) {
        $errors[] = "Pilih keputusan persetujuan yang valid.";
    }

    if (empty($errors)) {
        $id_user_approval = $_SESSION['id_user'];
        $tgl_approval     = date('Y-m-d H:i:s');
        
        $stmt = mysqli_prepare($koneksi, 
            "UPDATE permintaan_bahan 
             SET status_permintaan = ?, catatan_approval = ?, id_user_approval = ?, tgl_approval = ? 
             WHERE id_permintaan = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ssisi', $keputusan, $catatan, $id_user_approval, $tgl_approval, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $warna = $keputusan === 'Disetujui' ? 'success' : 'danger';
            set_flash($warna, "Permintaan <strong>{$mr['nomor_permintaan']}</strong> berhasil di-<strong>{$keputusan}</strong>.");
            redirect(BASE_URL . '/permintaan/detail.php?id=' . $id);
        } else {
            $errors[] = "Terjadi kesalahan saat menyimpan approval.";
        }
    }
}

require_once '../template/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-check-double me-2 text-success"></i>Approval Permintaan Bahan</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Permintaan Bahan</a></li>
            <li class="breadcrumb-item"><a href="detail.php?id=<?= $id ?>"><?= e($mr['nomor_permintaan']) ?></a></li>
            <li class="breadcrumb-item active">Approval</li>
        </ol></nav>
    </div>
</div>

<?php foreach ($errors as $er): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $er ?></div>
<?php endforeach; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-light">Ringkasan Permintaan</div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted" width="140">Nomor MR</td><td class="fw-bold"><?= e($mr['nomor_permintaan']) ?></td></tr>
                    <tr><td class="text-muted">Proyek</td><td><?= e($mr['nama_proyek']) ?></td></tr>
                    <tr><td class="text-muted">Pemohon</td><td><?= e($mr['nama_pemohon']) ?></td></tr>
                    <tr><td class="text-muted">Total Item</td>
                        <td>
                            <?php 
                            $c = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM permintaan_bahan_detail WHERE id_permintaan=$id"));
                            echo $c['c'] . ' Item';
                            ?>
                        </td>
                    </tr>
                </table>
                <div class="mt-2 text-end">
                    <a href="detail.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-external-link-alt me-1"></i>Lihat Detail Lengkap di Tab Baru
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-primary shadow-sm">
            <div class="card-header bg-primary text-white">Formulir Keputusan</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Keputusan <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 mt-1">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="keputusan" id="k_setuju" value="Disetujui" required>
                                <label class="form-check-label text-success fw-bold" for="k_setuju">
                                    <i class="fas fa-check-circle me-1"></i>Setujui
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="keputusan" id="k_tolak" value="Ditolak" required>
                                <label class="form-check-label text-danger fw-bold" for="k_tolak">
                                    <i class="fas fa-times-circle me-1"></i>Tolak
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Catatan Approval</label>
                        <textarea name="catatan_approval" class="form-control" rows="4" placeholder="Berikan catatan, alasan penolakan, atau instruksi (opsional)"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-status-confirm" data-msg="Simpan keputusan approval ini?">
                            <i class="fas fa-save me-1"></i>Simpan Keputusan
                        </button>
                        <a href="detail.php?id=<?= $id ?>" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
