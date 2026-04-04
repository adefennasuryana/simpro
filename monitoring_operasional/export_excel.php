<?php
/**
 * Monitoring Operasional: export_excel.php
 * Format export xls laporan operasional
 */
session_start();
require_once '../config/koneksi.php';
require_once 'functions.php';

cek_login();

$filter = [
    'id_proyek'     => $_GET['id_proyek'] ?? '',
    'jenis_periode' => $_GET['jenis_periode'] ?? 'bulanan',
    'tanggal'       => $_GET['tanggal'] ?? date('Y-m-d'),
    'bulan'         => $_GET['bulan'] ?? date('m'),
    'tahun'         => $_GET['tahun'] ?? date('Y')
];

$data_laporan = get_monitoring_data($koneksi, $filter);

$periode_str = '';
if ($filter['jenis_periode'] === 'harian') {
    $periode_str = date('d_m_Y', strtotime($filter['tanggal']));
} else {
    $periode_str = str_pad($filter['bulan'], 2, '0', STR_PAD_LEFT) . '_' . $filter['tahun'];
}

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Monitoring_Operasional_".$periode_str.".xls");
?>

<table border="1">
    <thead>
        <tr>
            <th colspan="11" style="text-align: center; font-size: 14pt; font-weight: bold;">LAPORAN MONITORING OPERASIONAL TINGKAT PROYEK</th>
        </tr>
        <tr>
            <th colspan="11" style="text-align: center;">Periode: <?= $periode_str ?></th>
        </tr>
        <tr>
            <th colspan="11" style="text-align: center; padding-bottom: 20px;">Diekstrak pada: <?= date('d/m/Y H:i') ?></th>
        </tr>
        <tr style="background-color: #d9d9d9;">
            <th rowspan="2">No</th>
            <th rowspan="2">Proyek / Site</th>
            <th rowspan="2">Tanggal MR</th>
            <th rowspan="2">Ref. MR</th>
            <th colspan="2">Material Spesifikasi</th>
            <th colspan="4">Tracking Realisasi Kebutuhan</th>
            <th rowspan="2">Status PO</th>
        </tr>
        <tr style="background-color: #d9d9d9;">
            <th>Bahan Baku</th>
            <th>Satuan</th>
            <th>Diminta</th>
            <th>Alokasi Sisa</th>
            <th>Realisasi (PO)</th>
            <th>Sisa Kekurangan</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($data_laporan)): ?>
            <tr><td colspan="11">Tidak ada data.</td></tr>
        <?php else: ?>
            <?php 
            $no = 1;
            foreach ($data_laporan as $row): 
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= e($row['nama_proyek']) ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal_permintaan'])) ?></td>
                <td><?= e($row['nomor_permintaan']) ?></td>
                <td><?= e($row['nama_bahan']) ?></td>
                <td><?= e($row['satuan']) ?></td>
                <td><?= floatval($row['req_diminta']) ?></td>
                <td><?= floatval($row['req_alokasi']) ?></td>
                <td><?= floatval($row['req_terpenuhi']) ?></td>
                <td><?= floatval($row['req_sisa']) ?></td>
                <td><?= $row['nomor_po'] ? e($row['nomor_po']) . ' ('.strtoupper($row['status_po']).')' : 'Belum Terbit' ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
