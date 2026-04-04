<?php
/**
 * Upah Harian: cetak.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$id = (int)$_GET['id'];
$q = mysqli_query($koneksi, "
    SELECT u.*, p.nama_proyek, u2.nama as nama_input 
    FROM pembayaran_upah u
    LEFT JOIN proyek p ON p.id_proyek = u.id_proyek
    LEFT JOIN users u2 ON u2.id_user = u.id_user_input
    WHERE u.id_pembayaran = $id");
$uh = mysqli_fetch_assoc($q);

if (!$uh) die("Data tidak ditemukan.");

$items = mysqli_query($koneksi, "SELECT * FROM pembayaran_upah_detail WHERE id_pembayaran = $id");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Slip Upah Harian: <?= e($uh['nomor_pembayaran']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #eee; }
        .info-table th, .info-table td { border: none; padding: 2px 5px; text-align: left; background: none; }
    </style>
</head>
<body onload="window.print()">

    <h3 class="text-center" style="margin-bottom:0">LAPORAN PEMBAYARAN UPAH HIARIAN TENAGA KERJA PROYEK</h3>
    <h4 class="text-center" style="margin-top:5px; margin-bottom:20px;">PT. SIMPRO KONSTRUKSI NUSANTARA</h4>

    <table class="info-table" style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td width="15%" class="fw-bold">No. Dokumen</td>
            <td width="35%">: <?= e($uh['nomor_pembayaran']) ?></td>
            <td width="15%" class="fw-bold">Proyek</td>
            <td width="35%">: <?= e($uh['nama_proyek']) ?></td>
        </tr>
        <tr>
            <td class="fw-bold">Tanggal Dibayar</td>
            <td>: <?= tgl_indo($uh['tanggal_pembayaran']) ?></td>
            <td class="fw-bold">Periode Kerja</td>
            <td>: <?= date('d/m/Y', strtotime($uh['periode_dari'])) ?> - <?= date('d/m/Y', strtotime($uh['periode_sampai'])) ?></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Nama Pekerja</th>
                <th>Jabatan</th>
                <th>Masuk (Hr)</th>
                <th>Upah/Hr</th>
                <th>Lembur</th>
                <th>Extra</th>
                <th>Kasbon/Potong</th>
                <th>Total Diterima</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; while($d = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= e($d['nama_pekerja']) ?></td>
                <td><?= e($d['jabatan']) ?></td>
                <td class="text-center"><?= floatval($d['jumlah_hari']) ?></td>
                <td class="text-right"><?= number_format($d['upah_harian'],0,',','.') ?></td>
                <td class="text-right"><?= number_format($d['lembur'],0,',','.') ?></td>
                <td class="text-right"><?= number_format($d['tambahan'],0,',','.') ?></td>
                <td class="text-right"><?= number_format($d['potongan'],0,',','.') ?></td>
                <td class="text-right fw-bold"><?= number_format($d['subtotal'],0,',','.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-right fw-bold">TOTAL PEMBAYARAN KESELURUHAN &nbsp;</td>
                <td class="text-right fw-bold" style="font-size:13px;">Rp <?= number_format($uh['total_pembayaran'],0,',','.') ?></td>
            </tr>
        </tfoot>
    </table>

    <table class="info-table" style="width: 100%; margin-top: 40px; text-align: center;">
        <tr>
            <td width="33%">Disiapkan Oleh,<br><br><br><br>( <?= e($uh['nama_input']) ?> )</td>
            <td width="33%">Diperiksa Oleh,<br><br><br><br>( .......................... )</td>
            <td width="33%">Diketahui/Disetujui,<br><br><br><br>( Manajer Proyek )</td>
        </tr>
    </table>

</body>
</html>
