<?php
/**
 * Upah Harian: simpan.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proyek = (int)$_POST['id_proyek'];
    $tanggal_pembayaran = $_POST['tanggal_pembayaran'];
    $periode_dari = $_POST['periode_dari'];
    $periode_sampai = $_POST['periode_sampai'];
    $keterangan = trim($_POST['keterangan'] ?? '');
    $total_pembayaran = (float)$_POST['total_pembayaran'];
    $id_user = $_SESSION['id_user'];
    
    // Auto generate nomor: UH-YYMMDD-XXX
    $tgl_code = date('ymd', strtotime($tanggal_pembayaran));
    $prefix = "UH-$tgl_code-";
    $qmax = mysqli_query($koneksi, "SELECT nomor_pembayaran FROM pembayaran_upah WHERE nomor_pembayaran LIKE '$prefix%' ORDER BY id_pembayaran DESC LIMIT 1");
    if (mysqli_num_rows($qmax) > 0) {
        $last = mysqli_fetch_array($qmax)[0];
        $urut = (int)substr($last, -3) + 1;
    } else {
        $urut = 1;
    }
    $nomor_pembayaran = $prefix . str_pad($urut, 3, '0', STR_PAD_LEFT);

    mysqli_begin_transaction($koneksi);
    try {
        // Insert Header
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pembayaran_upah (nomor_pembayaran, id_proyek, tanggal_pembayaran, periode_dari, periode_sampai, keterangan, total_pembayaran, id_user_input) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sissssdi", $nomor_pembayaran, $id_proyek, $tanggal_pembayaran, $periode_dari, $periode_sampai, $keterangan, $total_pembayaran, $id_user);
        mysqli_stmt_execute($stmt);
        
        $id_pembayaran = mysqli_insert_id($koneksi);

        // Insert Detail
        $pekerjas = $_POST['pekerja'] ?? [];
        $jabatans = $_POST['jabatan'] ?? [];
        $hari     = $_POST['jumlah_hari'] ?? [];
        $upah     = $_POST['upah_harian'] ?? [];
        $lembur   = $_POST['lembur'] ?? [];
        $tambahan = $_POST['tambahan'] ?? [];
        $potongan = $_POST['potongan'] ?? [];
        $subtotal = $_POST['subtotal'] ?? [];

        $stmt_det = mysqli_prepare($koneksi, "INSERT INTO pembayaran_upah_detail (id_pembayaran, nama_pekerja, jabatan, jumlah_hari, upah_harian, lembur, tambahan, potongan, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        for ($i=0; $i<count($pekerjas); $i++) {
            $nm = trim($pekerjas[$i]);
            if (empty($nm)) continue;
            $jb = trim($jabatans[$i]);
            $jh = (float)$hari[$i];
            $uh = (float)$upah[$i];
            $lb = (float)$lembur[$i];
            $tb = (float)$tambahan[$i];
            $pt = (float)$potongan[$i];
            $st = (float)$subtotal[$i];
            
            mysqli_stmt_bind_param($stmt_det, "issdddddd", $id_pembayaran, $nm, $jb, $jh, $uh, $lb, $tb, $pt, $st);
            mysqli_stmt_execute($stmt_det);
        }

        mysqli_commit($koneksi);
        set_flash('success', "Pembayaran Upah $nomor_pembayaran berhasil disimpan (Draft).");
        redirect(BASE_URL . '/upah_harian/index.php');
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        set_flash('danger', "Gagal menyimpan data: " . $e->getMessage());
        redirect(BASE_URL . '/upah_harian/tambah.php');
    }
}
