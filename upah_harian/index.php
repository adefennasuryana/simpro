<?php
/**
 * Upah Harian: index.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$pageTitle = 'Data Pembayaran Upah Harian';
$role = $_SESSION['role'];

$q = $_GET['q'] ?? '';
$id_proyek = $_GET['id_proyek'] ?? '';

$where = [];
if (!empty($q)) {
    $search = mysqli_real_escape_string($koneksi, $q);
    // Cari nomor dokumen atau nama pekerja (melalui subquery detail)
    $where[] = "(u.nomor_pembayaran LIKE '%$search%' OR u.id_pembayaran IN (SELECT id_pembayaran FROM pembayaran_upah_detail WHERE nama_pekerja LIKE '%$search%'))";
}
if (!empty($id_proyek)) {
    $where[] = "u.id_proyek = " . (int)$id_proyek;
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT u.*, p.nama_proyek, u.nomor_pembayaran 
      FROM pembayaran_upah u
      LEFT JOIN proyek p ON p.id_proyek = u.id_proyek
      $where_sql
      ORDER BY u.created_at DESC";
$result = mysqli_query($koneksi, $query);

require_once '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-users-cog me-2 text-primary"></i>Upah Tenaga Kerja</h4>
    <?php if (in_array($role, ['admin', 'manajer', 'purchasing'])): ?>
    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Catat Pembayaran</a>
    <?php endif; ?>
</div>

<?php show_flash(); ?>

<!-- Filter & Search -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cari No. Dokumen / Nama Pekerja</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Ketik pencarian..." value="<?= e($q) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Filter Proyek</label>
                <select name="id_proyek" class="form-select form-select-sm">
                    <option value="">-- Semua Proyek --</option>
                    <?php 
                    $py = mysqli_query($koneksi, "SELECT * FROM proyek ORDER BY nama_proyek");
                    while($p = mysqli_fetch_assoc($py)):
                    ?>
                    <option value="<?= $p['id_proyek'] ?>" <?= $id_proyek == $p['id_proyek'] ? 'selected' : '' ?>><?= e($p['nama_proyek']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-primary px-3"><i class="fas fa-filter"></i> Filter</button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Dokumen</th>
                    <th>Proyek</th>
                    <th>Tanggal Bayar</th>
                    <th>Periode Kerja</th>
                    <th class="text-end">Total Nominal</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) === 0): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data pembayaran upah harian.</td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td class="fw-bold"><?= e($row['nomor_pembayaran']) ?></td>
                    <td><?= e($row['nama_proyek']) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal_pembayaran'])) ?></td>
                    <td class="small text-muted">
                        <?= date('d/m/y', strtotime($row['periode_dari'])) ?> s/d <?= date('d/m/y', strtotime($row['periode_sampai'])) ?>
                    </td>
                    <td class="text-end fw-bold text-success"><?= rupiah($row['total_pembayaran']) ?></td>
                    <td class="text-center">
                        <?php
                        $s = $row['status_pembayaran'];
                        $bdg = $s === 'Draft' ? 'secondary' : ($s === 'Diajukan' ? 'warning' : ($s === 'Disetujui' ? 'info' : 'success'));
                        echo "<span class='badge bg-$bdg'>$s</span>";
                        ?>
                    </td>
                    <td class="text-center">
                        <a href="detail.php?id=<?= $row['id_pembayaran'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> Detail</a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
