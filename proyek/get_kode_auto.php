<?php
/**
 * AJAX endpoint: get_kode_auto.php (folder: proyek)
 * Mengembalikan JSON kode proyek berikutnya
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

header('Content-Type: application/json');

$kode = generate_kode_proyek($koneksi);

echo json_encode([
    'kode'    => $kode,
    'success' => true,
]);
