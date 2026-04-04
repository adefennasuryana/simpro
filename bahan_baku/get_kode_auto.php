<?php
/**
 * AJAX endpoint: get_kode_auto.php
 * Mengembalikan JSON kode bahan baku berikutnya
 * Dipanggil oleh tombol refresh di form tambah bahan baku
 */
session_start();
require_once '../config/koneksi.php';
cek_login(); // Pastikan hanya user login yang bisa akses

header('Content-Type: application/json');

$kode = generate_kode_bahan($koneksi);

echo json_encode([
    'kode'    => $kode,
    'success' => true,
]);
