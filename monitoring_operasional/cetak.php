<?php
/**
 * Monitoring Operasional: cetak.php
 * Format cetak laporan kertas
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
    $periode_str = tgl_indo($filter['tanggal']);
} else {
    $bulans = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $periode_str = $bulans[(int)$filter['bulan']] . ' ' . $filter['tahun'];
}

$proyek_str = 'Semua Proyek';
if (!empty($filter['id_proyek'])) {
    $idp = (int)$filter['id_proyek'];
    $pry = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_proyek FROM proyek WHERE id_proyek = $idp"));
    if ($pry) $proyek_str = $pry['nama_proyek'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Monitoring Operasional</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #000; margin: 20px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        h3, h4, p { margin: 0; padding: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 5px 8px; vertical-align: middle; }
        th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        .mt-4 { margin-top: 40px; }
        .mb-4 { margin-bottom: 20px; }
    </style>
</head>
<body onload="window.print()">

    <div class="text-center mb-4">
        <h3>LAPORAN MONITORING OPERASIONAL TINGKAT PROYEK</h3>
        <h4>PT. SIMPRO KONSTRUKSI NUSANTARA</h4>
        <p>Periode: <?= $periode_str ?></p>
        <p>Proyek: <?= e($proyek_str) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="30">No</th>
                <th rowspan="2">Proyek / Site</th>
                <th rowspan="2">Tanggal MR</th>
                <th rowspan="2">Ref. MR</th>
                <th colspan="2">Material Spesifikasi</th>
                <th colspan="4">Tracking Realisasi Kebutuhan</th>
                <th rowspan="2">Status PO</th>
            </tr>
            <tr>
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
                <tr><td colspan="11" class="text-center">Tidak ada data.</td></tr>
            <?php else: ?>
                <?php 
                $no = 1;
                $last_proyek = '';
                foreach ($data_laporan as $row): 
                    if ($last_proyek !== $row['nama_proyek'] && $no > 1) {
                        echo '<tr><td colspan="11" style="background-color:#000; height:2px; padding:0;"></td></tr>';
                    }
                    $last_proyek = $row['nama_proyek'];
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="font-weight-bold"><?= e($row['nama_proyek']) ?></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal_permintaan'])) ?></td>
                    <td><?= e($row['nomor_permintaan']) ?></td>
                    <td><?= e($row['nama_bahan']) ?></td>
                    <td class="text-center"><?= e($row['satuan']) ?></td>
                    <td class="text-right"><?= floatval($row['req_diminta']) ?></td>
                    <td class="text-right"><?= floatval($row['req_alokasi']) ?></td>
                    <td class="text-right font-weight-bold"><?= floatval($row['req_terpenuhi']) ?></td>
                    <td class="text-right"><?= floatval($row['req_sisa']) ?></td>
                    <td class="text-center"><?= $row['nomor_po'] ? e($row['nomor_po']) . ' ('.strtoupper($row['status_po']).')' : 'Belum Terbit' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table style="width: 100%; border: none; margin-top: 50px;">
        <tr>
            <td style="border: none; text-align: center; width: 33%;">
                Disiapkan Oleh,<br><br><br><br><br>
                ( <?= $_SESSION['nama'] ?> )
            </td>
            <td style="border: none; text-align: center; width: 33%;">
                Diperiksa Oleh,<br><br><br><br><br>
                ( ........................ )
            </td>
            <td style="border: none; text-align: center; width: 33%;">
                Disetujui Oleh,<br><br><br><br><br>
                ( Manajer Proyek )
            </td>
        </tr>
    </table>

</body>
</html>
