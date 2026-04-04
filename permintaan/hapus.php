<?php
/**
 * Permintaan Bahan: hapus.php
 */
session_start();
require_once '../config/koneksi.php';
cek_login();
cek_role(['admin', 'purchasing']);

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Cek dulu apakah data ada dan statusnya mengizinkan untuk dihapus
    $mr = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nomor_permintaan, status_permintaan FROM permintaan_bahan WHERE id_permintaan = $id"));
    
    if ($mr) {
        if (in_array($mr['status_permintaan'], ['Draft', 'Ditolak'])) {
            $stmt = mysqli_prepare($koneksi, "DELETE FROM permintaan_bahan WHERE id_permintaan = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                set_flash('success', "Permintaan <strong>{$mr['nomor_permintaan']}</strong> berhasil dihapus.");
            } else {
                set_flash('danger', "Gagal menghapus permintaan. Pastikan data tidak digunakan di tempat lain.");
            }
        } else {
            set_flash('warning', "Permintaan <strong>{$mr['nomor_permintaan']}</strong> tidak dapat dihapus karena berstatus {$mr['status_permintaan']}.");
        }
    } else {
        set_flash('danger', "Data permintaan tidak ditemukan.");
    }
}

redirect(BASE_URL . '/permintaan/index.php');
