<?php
/**
 * Upah Harian: revisi_simpan.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pembayaran = (int)$_POST['id_pembayaran'];
    $tanggal_pembayaran = $_POST['tanggal_pembayaran'];
    $periode_dari = $_POST['periode_dari'];
    $periode_sampai = $_POST['periode_sampai'];
    $keterangan = trim($_POST['keterangan'] ?? '');
    $total_pembayaran = (float)$_POST['total_pembayaran'];
    $id_user = $_SESSION['id_user'];
    
    // Ambil nominal lama sebelum update untuk log
    $qold = mysqli_query($koneksi, "SELECT total_pembayaran FROM pembayaran_upah WHERE id_pembayaran = $id_pembayaran");
    $nominal_lama = mysqli_fetch_array($qold)[0] ?? 0;

    mysqli_begin_transaction($koneksi);
    try {
        // Insert Log Revisi
        $stmt_log = mysqli_prepare($koneksi, "INSERT INTO pembayaran_upah_log (id_pembayaran, nominal_lama, nominal_baru, keterangan_revisi, id_user_revisi) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_log, "idssi", $id_pembayaran, $nominal_lama, $total_pembayaran, $keterangan, $id_user);
        mysqli_stmt_execute($stmt_log);

        // Update Header
        $stmt = mysqli_prepare($koneksi, "UPDATE pembayaran_upah SET tanggal_pembayaran = ?, periode_dari = ?, periode_sampai = ?, keterangan = ?, total_pembayaran = ? WHERE id_pembayaran = ?");
        mysqli_stmt_bind_param($stmt, "ssssdi", $tanggal_pembayaran, $periode_dari, $periode_sampai, $keterangan, $total_pembayaran, $id_pembayaran);
        mysqli_stmt_execute($stmt);
        
        // Hapus detail lama untuk diganti dengan yang baru (Revisi total)
        mysqli_query($koneksi, "DELETE FROM pembayaran_upah_detail WHERE id_pembayaran = $id_pembayaran");

        // Insert Detail Baru
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
        set_flash('success', "Revisi Pembayaran Upah berhasil disimpan.");
        redirect(BASE_URL . "/upah_harian/detail.php?id=$id_pembayaran");
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        set_flash('danger', "Gagal menyimpan revisi: " . $e->getMessage());
        redirect(BASE_URL . "/upah_harian/revisi.php?id=$id_pembayaran");
    }
}
