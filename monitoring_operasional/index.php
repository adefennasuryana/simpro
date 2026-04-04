<?php
/**
 * Monitoring Operasional: index.php
 */
session_start();
require_once '../config/koneksi.php';
require_once 'functions.php';

cek_login();
// Bisa diakses semua role yang butuh lihat laporan operasional

$pageTitle = 'Monitoring Operasional Proyek';

// Mengatur parameter filter bawaan
$filter = [
    'id_proyek'     => $_GET['id_proyek'] ?? '',
    'jenis_periode' => $_GET['jenis_periode'] ?? 'bulanan',
    'tanggal'       => $_GET['tanggal'] ?? date('Y-m-d'),
    'bulan'         => $_GET['bulan'] ?? date('m'),
    'tahun'         => $_GET['tahun'] ?? date('Y')
];

$data_laporan = get_monitoring_data($koneksi, $filter);

require_once '../template/header.php';
?>

<div class="page-header">
    <h4><i class="fas fa-chart-line me-2 text-primary"></i>Monitoring Operasional</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Monitoring Operasional</li>
    </ol></nav>
</div>

<!-- Panel Filter -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-white border-bottom fw-bold py-3">
        <i class="fas fa-filter me-2 text-secondary"></i>Filter Parameter Laporan
    </div>
    <div class="card-body bg-light">
        <form method="GET" action="index.php" id="form-filter">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Proyek Spesifik</label>
                    <select name="id_proyek" class="form-select form-select-sm">
                        <option value="">-- Semua Proyek --</option>
                        <?php
                        $list_proyek = mysqli_query($koneksi, "SELECT id_proyek, kode_proyek, nama_proyek FROM proyek ORDER BY nama_proyek");
                        while($p = mysqli_fetch_assoc($list_proyek)):
                        ?>
                        <option value="<?= $p['id_proyek'] ?>" <?= ($filter['id_proyek'] == $p['id_proyek']) ? 'selected' : '' ?>>
                            [<?= e($p['kode_proyek']) ?>] <?= e($p['nama_proyek']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Jenis Periode</label>
                    <select name="jenis_periode" id="tipe_periode" class="form-select form-select-sm" onchange="togglePeriode(this.value)">
                        <option value="harian"  <?= $filter['jenis_periode'] === 'harian' ? 'selected' : '' ?>>Harian</option>
                        <option value="bulanan" <?= $filter['jenis_periode'] === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                    </select>
                </div>
                
                <div class="col-md-2" id="box-tanggal" style="<?= $filter['jenis_periode'] === 'bulanan' ? 'display:none;' : '' ?>">
                    <label class="form-label small fw-semibold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= e($filter['tanggal']) ?>">
                </div>

                <div class="col-md-2" id="box-bulan" style="<?= $filter['jenis_periode'] === 'harian' ? 'display:none;' : '' ?>">
                    <label class="form-label small fw-semibold">Bulan</label>
                    <select name="bulan" class="form-select form-select-sm">
                        <?php 
                        $bulans = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                        foreach($bulans as $num => $nama): 
                        ?>
                        <option value="<?= str_pad($num, 2, '0', STR_PAD_LEFT) ?>" <?= ($filter['bulan'] == $num) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2" id="box-tahun" style="<?= $filter['jenis_periode'] === 'harian' ? 'display:none;' : '' ?>">
                    <label class="form-label small fw-semibold">Tahun</label>
                    <select name="tahun" class="form-select form-select-sm">
                        <?php for($y=date('Y'); $y>=date('Y')-5; $y--): ?>
                        <option value="<?= $y ?>" <?= ($filter['tahun'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="fas fa-search me-1"></i> Tampilkan
                        </button>
                        
                        <div class="dropdown">
                            <button class="btn btn-sm btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download me-1"></i> Unduh
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><button type="submit" formaction="cetak.php" formtarget="_blank" class="dropdown-item"><i class="fas fa-print me-2 text-secondary"></i>Cetak Laporan</button></li>
                                <li><button type="submit" formaction="export_excel.php" class="dropdown-item"><i class="fas fa-file-excel me-2 text-success"></i>Export Excel</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Laporan Kertas Kerja -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0 align-middle" style="font-size: 0.85rem; white-space: nowrap;">
                <thead class="table-dark align-middle text-center">
                    <tr>
                        <th rowspan="2" width="40">No</th>
                        <th rowspan="2">Proyek / Site</th>
                        <th rowspan="2">Tanggal</th>
                        <th rowspan="2">Ref. MR</th>
                        <th colspan="2">Material Spesifikasi</th>
                        <th colspan="4" class="bg-primary border-primary">Tracking Realisasi Kebutuhan</th>
                        <th rowspan="2">Status PO</th>
                    </tr>
                    <tr>
                        <th>Bahan Baku</th>
                        <th>Satuan</th>
                        <th class="bg-primary border-primary">Diminta</th>
                        <th class="bg-info text-dark border-info" title="Dialokasikan dari sisa">Alokasi</th>
                        <th class="bg-primary border-primary">Realisasi (PO)</th>
                        <th class="bg-danger border-danger">Sisa Kekurangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_laporan)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="fas fa-search-minus fa-2x mb-2 d-block"></i>
                            Tidak ada data tracking material operasional pada periode tersebut.
                        </td>
                    </tr>
                    <?php 
                    else: 
                        $no = 1;
                        $last_proyek = '';
                        foreach ($data_laporan as $row): 
                            // Grouping per line separator jika proyek berganti
                            if ($last_proyek !== $row['nama_proyek'] && $no > 1) {
                                echo '<tr class="table-secondary"><td colspan="11" style="height:4px;padding:0;"></td></tr>';
                            }
                            $last_proyek = $row['nama_proyek'];
                    ?>
                    <tr>
                        <td class="text-center text-muted"><?= $no++ ?></td>
                        <td class="fw-bold text-dark">
                            [<?= e($row['kode_proyek']) ?>] <?= e($row['nama_proyek']) ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_permintaan'])) ?></td>
                        <td><a href="../permintaan/detail.php?id=<?= $row['nomor_permintaan'] /* logic fallback */ ?>"><?= e($row['nomor_permintaan']) ?></a></td>
                        
                        <!-- Material -->
                        <td><span class="text-secondary small me-1">[<?= e($row['kode_bahan']) ?>]</span><?= e($row['nama_bahan']) ?></td>
                        <td class="text-center"><?= e($row['satuan']) ?></td>
                        
                        <!-- Hitungan Numerik -->
                        <td class="text-end fw-bold"><?= floatval($row['req_diminta']) ?></td>
                        <td class="text-end text-primary fw-semibold"><?= floatval($row['req_alokasi']) ?></td>
                        <td class="text-end bg-light fw-bold"><?= floatval($row['req_terpenuhi']) ?></td>
                        <td class="text-end text-danger fw-bold"><?= floatval($row['req_sisa']) ?></td>
                        
                        <!-- Status Info -->
                        <td class="text-center">
                            <?php if($row['nomor_po']): ?>
                                <small class="d-block fw-bold"><?= e($row['nomor_po']) ?></small>
                                <span class="badge bg-secondary" style="font-size:.65rem"><?= strtoupper($row['status_po']) ?></span>
                            <?php else: ?>
                                <span class="text-muted fst-italic small">Belum Terbit PO</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endforeach; 
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $data_upah = get_monitoring_upah($koneksi, $filter); ?>
<!-- Laporan Upah -->
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white border-bottom fw-bold py-3 text-success">
        <i class="fas fa-users-cog me-2"></i>Tracking Upah Tenaga Kerja
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0 align-middle" style="font-size: 0.85rem; white-space: nowrap;">
                <thead class="table-success align-middle text-center">
                    <tr>
                        <th width="40">No</th>
                        <th>Proyek / Site</th>
                        <th>Tanggal Bayar</th>
                        <th>Ref. Dokumen</th>
                        <th>Periode Kerja</th>
                        <th class="text-end">Total Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_upah)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Tidak ada data tracking upah operasional pada periode tersebut.
                        </td>
                    </tr>
                    <?php 
                    else: 
                        $no = 1;
                        $total_upah_periode = 0;
                        $last_proyek = '';
                        foreach ($data_upah as $up): 
                            if ($last_proyek !== $up['nama_proyek'] && $no > 1) {
                                echo '<tr class="table-secondary"><td colspan="7" style="height:4px;padding:0;"></td></tr>';
                            }
                            $last_proyek = $up['nama_proyek'];
                            $total_upah_periode += $up['total_pembayaran'];
                    ?>
                    <tr>
                        <td class="text-center text-muted"><?= $no++ ?></td>
                        <td class="fw-bold text-dark">
                            [<?= e($up['kode_proyek']) ?>] <?= e($up['nama_proyek']) ?>
                        </td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($up['tanggal_pembayaran'])) ?></td>
                        <td class="text-center"><a href="../upah_harian/detail.php?id=<?= $up['nomor_pembayaran'] ?>"><?= e($up['nomor_pembayaran']) ?></a></td>
                        <td class="text-center">
                            <?= date('d/m/y', strtotime($up['periode_dari'])) ?> - <?= date('d/m/y', strtotime($up['periode_sampai'])) ?>
                        </td>
                        <td class="text-end fw-bold text-success"><?= rupiah($up['total_pembayaran']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $up['status_pembayaran'] === 'Dibayar' ? 'success' : 'info' ?>"><?= strtoupper($up['status_pembayaran']) ?></span>
                        </td>
                    </tr>
                    <?php 
                        endforeach; 
                    ?>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold text-success">Total Validasi Upah (Periode):</td>
                            <td class="text-end fw-bold text-success fs-6"><?= rupiah($total_upah_periode) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function togglePeriode(val) {
    if (val === 'harian') {
        document.getElementById('box-tanggal').style.display = 'block';
        document.getElementById('box-bulan').style.display = 'none';
        document.getElementById('box-tahun').style.display = 'none';
    } else {
        document.getElementById('box-tanggal').style.display = 'none';
        document.getElementById('box-bulan').style.display = 'block';
        document.getElementById('box-tahun').style.display = 'block';
    }
}
</script>

<?php require_once '../template/footer.php'; ?>
