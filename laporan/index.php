<?php
/**
 * Laporan: index.php — Hub Laporan
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$pageTitle = 'Laporan';
$jenis     = $_GET['jenis'] ?? 'daftar_po';

// Filter
$f_proyek   = (int)($_GET['id_proyek']  ?? 0);
$f_supplier = (int)($_GET['id_supplier'] ?? 0);
$f_status   = $_GET['status_po']        ?? '';
$f_dari     = $_GET['dari']             ?? date('Y-m-01');
$f_sampai   = $_GET['sampai']           ?? date('Y-m-d');

// Data dropdown filter
$proyeks   = mysqli_query($koneksi,"SELECT id_proyek,kode_proyek,nama_proyek FROM proyek ORDER BY nama_proyek");
$suppliers = mysqli_query($koneksi,"SELECT id_supplier,nama_supplier FROM supplier ORDER BY nama_supplier");

// Build query berdasarkan jenis laporan
$where  = ["p.tanggal_po BETWEEN '$f_dari' AND '$f_sampai'"];
if ($f_proyek)   $where[] = "p.id_proyek   = $f_proyek";
if ($f_supplier) $where[] = "p.id_supplier = $f_supplier";
if ($f_status)   $where[] = "p.status_po   = '$f_status'";

$wclause = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT p.*, pr.nama_proyek, pr.kode_proyek, s.nama_supplier, u.nama as nama_user
        FROM po p
        LEFT JOIN proyek pr ON pr.id_proyek = p.id_proyek
        LEFT JOIN supplier s ON s.id_supplier = p.id_supplier
        LEFT JOIN users u ON u.id_user = p.id_user
        $wclause
        ORDER BY p.tanggal_po DESC, p.nomor_po";

$result = mysqli_query($koneksi, $sql);

// Ringkasan
$sum_sql = "SELECT COUNT(*) as jml, SUM(p.total) as total FROM po p $wclause";
$sum     = mysqli_fetch_assoc(mysqli_query($koneksi, $sum_sql));

// Per proyek
$per_proyek = mysqli_query($koneksi,
    "SELECT pr.nama_proyek, COUNT(p.id_po) jml, SUM(p.total) total
     FROM po p LEFT JOIN proyek pr ON pr.id_proyek=p.id_proyek
     $wclause GROUP BY p.id_proyek ORDER BY total DESC");

// Per supplier
$per_supplier = mysqli_query($koneksi,
    "SELECT s.nama_supplier, COUNT(p.id_po) jml, SUM(p.total) total
     FROM po p LEFT JOIN supplier s ON s.id_supplier=p.id_supplier
     $wclause GROUP BY p.id_supplier ORDER BY total DESC");

// Per status
$per_status = mysqli_query($koneksi,
    "SELECT p.status_po, COUNT(*) jml, SUM(p.total) total
     FROM po p $wclause GROUP BY p.status_po ORDER BY jml DESC");

require_once '../template/header.php';

function badge_po($s) {
    $m=['draft'=>['secondary','Draft'],'diajukan'=>['warning','Diajukan'],'disetujui'=>['info','Disetujui'],
        'ditolak'=>['danger','Ditolak'],'dikirim_sebagian'=>['primary','Dikirim Sebagian'],'selesai'=>['success','Selesai']];
    $d=$m[$s]??['secondary',ucfirst($s)];
    return "<span class='badge bg-{$d[0]}'>{$d[1]}</span>";
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="fas fa-chart-bar me-2 text-primary"></i>Laporan PO</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Laporan</li>
        </ol></nav>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary no-print">
        <i class="fas fa-print me-1"></i>Cetak Laporan
    </button>
</div>

<!-- Filter Panel -->
<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end" method="GET">
            <input type="hidden" name="jenis" value="<?= e($jenis) ?>">
            <div class="col-auto">
                <label class="form-label small mb-1">Dari</label>
                <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($f_dari) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Sampai</label>
                <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($f_sampai) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Proyek</label>
                <select name="id_proyek" class="form-select form-select-sm">
                    <option value="">Semua Proyek</option>
                    <?php mysqli_data_seek($proyeks,0); while($pr=mysqli_fetch_assoc($proyeks)): ?>
                    <option value="<?= $pr['id_proyek'] ?>" <?= $f_proyek==$pr['id_proyek']?'selected':'' ?>>
                        <?= e($pr['nama_proyek']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Supplier</label>
                <select name="id_supplier" class="form-select form-select-sm">
                    <option value="">Semua Supplier</option>
                    <?php mysqli_data_seek($suppliers,0); while($sp=mysqli_fetch_assoc($suppliers)): ?>
                    <option value="<?= $sp['id_supplier'] ?>" <?= $f_supplier==$sp['id_supplier']?'selected':'' ?>>
                        <?= e($sp['nama_supplier']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Status</label>
                <select name="status_po" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php foreach(['draft','diajukan','disetujui','ditolak','dikirim_sebagian','selesai'] as $s): ?>
                    <option value="<?= $s ?>" <?= $f_status===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto pt-1">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="?" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tab Menu -->
<ul class="nav nav-tabs mb-3 no-print" id="laporan-tab">
    <?php foreach(['daftar_po'=>'Daftar PO','per_proyek'=>'Per Proyek','per_supplier'=>'Per Supplier','per_status'=>'Per Status'] as $k=>$v): ?>
    <li class="nav-item">
        <a class="nav-link <?= $jenis===$k?'active':'' ?>"
           href="?jenis=<?= $k ?>&dari=<?= $f_dari ?>&sampai=<?= $f_sampai ?>&id_proyek=<?= $f_proyek ?>&id_supplier=<?= $f_supplier ?>&status_po=<?= $f_status ?>">
            <?= $v ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Ringkasan -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="text-muted small">Total PO ditemukan</div>
            <div class="fw-bold fs-4 text-primary"><?= $sum['jml'] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="text-muted small">Total Nilai PO</div>
            <div class="fw-bold fs-5 text-success"><?= rupiah($sum['total'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="text-muted small">Periode</div>
            <div class="fw-600"><?= tgl_indo($f_dari) ?> &mdash; <?= tgl_indo($f_sampai) ?></div>
        </div>
    </div>
</div>

<!-- DAFTAR PO -->
<?php if ($jenis === 'daftar_po'): ?>
<div class="card table-card">
    <div class="card-header">Daftar Purchase Order</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr><th>#</th><th>Nomor PO</th><th>Tanggal</th><th>Proyek</th><th>Supplier</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php $no=1; if(mysqli_num_rows($result)==0): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data</td></tr>
            <?php else: while($po=mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="fw-600"><a href="../po/detail.php?id=<?= $po['id_po'] ?>" class="text-decoration-none no-print"><?= e($po['nomor_po']) ?></a>
                    <span class="print-only"><?= e($po['nomor_po']) ?></span></td>
                <td><?= tgl_indo($po['tanggal_po']) ?></td>
                <td><?= e($po['nama_proyek']) ?></td>
                <td><?= e($po['nama_supplier']) ?></td>
                <td class="fw-600"><?= rupiah($po['total']) ?></td>
                <td><?= badge_po($po['status_po']) ?></td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
            <tfoot class="table-light">
            <tr>
                <td colspan="5" class="text-end fw-bold">Total:</td>
                <td class="fw-bold text-primary"><?= rupiah($sum['total']??0) ?></td>
                <td></td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- PER PROYEK -->
<?php elseif ($jenis === 'per_proyek'): ?>
<div class="card table-card">
    <div class="card-header">Laporan PO Per Proyek</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Proyek</th><th class="text-end">Jumlah PO</th><th class="text-end">Total Nilai</th></tr></thead>
            <tbody>
            <?php $no=1; while($r=mysqli_fetch_assoc($per_proyek)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="fw-600"><?= e($r['nama_proyek']??'-') ?></td>
                <td class="text-end"><?= $r['jml'] ?></td>
                <td class="text-end fw-bold text-primary"><?= rupiah($r['total']) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot class="table-light"><tr><td colspan="2" class="text-end fw-bold">Total:</td><td class="text-end fw-bold"><?= $sum['jml'] ?></td><td class="text-end fw-bold text-primary"><?= rupiah($sum['total']??0) ?></td></tr></tfoot>
        </table>
    </div>
</div>

<!-- PER SUPPLIER -->
<?php elseif ($jenis === 'per_supplier'): ?>
<div class="card table-card">
    <div class="card-header">Laporan PO Per Supplier</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Supplier</th><th class="text-end">Jumlah PO</th><th class="text-end">Total Nilai</th></tr></thead>
            <tbody>
            <?php $no=1; while($r=mysqli_fetch_assoc($per_supplier)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="fw-600"><?= e($r['nama_supplier']??'-') ?></td>
                <td class="text-end"><?= $r['jml'] ?></td>
                <td class="text-end fw-bold text-primary"><?= rupiah($r['total']) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot class="table-light"><tr><td colspan="2" class="text-end fw-bold">Total:</td><td class="text-end fw-bold"><?= $sum['jml'] ?></td><td class="text-end fw-bold text-primary"><?= rupiah($sum['total']??0) ?></td></tr></tfoot>
        </table>
    </div>
</div>

<!-- PER STATUS -->
<?php elseif ($jenis === 'per_status'): ?>
<div class="card table-card">
    <div class="card-header">Laporan PO Per Status</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Status</th><th class="text-end">Jumlah PO</th><th class="text-end">Total Nilai</th></tr></thead>
            <tbody>
            <?php $no=1; while($r=mysqli_fetch_assoc($per_status)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= badge_po($r['status_po']) ?></td>
                <td class="text-end"><?= $r['jml'] ?></td>
                <td class="text-end fw-bold text-primary"><?= rupiah($r['total']) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot class="table-light"><tr><td colspan="2" class="text-end fw-bold">Total:</td><td class="text-end fw-bold"><?= $sum['jml'] ?></td><td class="text-end fw-bold text-primary"><?= rupiah($sum['total']??0) ?></td></tr></tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
@media print {
    .nav-tabs, .no-print { display: none !important; }
    .print-only { display: inline !important; }
    a { color: inherit !important; text-decoration: none !important; }
}
.print-only { display: none; }
</style>

<?php require_once '../template/footer.php'; ?>
