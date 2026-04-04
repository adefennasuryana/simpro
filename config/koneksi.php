<?php
/**
 * SIMPRO - Koneksi Database Terpusat
 * File: config/koneksi.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'simpro');
define('APP_NAME', 'SIMPRO');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/simpro');

// Koneksi MySQLi
$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    die('<div style="font-family:Arial;padding:20px;color:red;">
        <h3>⚠️ Koneksi Database Gagal</h3>
        <p>' . mysqli_connect_error() . '</p>
    </div>');
}

date_default_timezone_set('Asia/Jakarta');
mysqli_query($koneksi, "SET time_zone = '+07:00'");
mysqli_set_charset($koneksi, 'utf8mb4');

/**
 * Helper: Jalankan query dengan prepared statement
 * Mengembalikan mysqli_result atau true/false
 */
function db_query($koneksi, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        return false;
    }
    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result !== false) {
        return $result;
    }
    return $stmt;
}

/**
 * Helper: Escape string sederhana untuk output
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Helper: Format Rupiah
 */
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Helper: Format tanggal Indonesia
 */
function tgl_indo($tgl) {
    if (!$tgl || $tgl === '0000-00-00') return '-';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $d = date('j', strtotime($tgl));
    $m = date('n', strtotime($tgl));
    $y = date('Y', strtotime($tgl));
    return "$d {$bulan[$m]} $y";
}

/**
 * Helper: Redirect
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Helper: Set flash message di session
 */
function set_flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Helper: Tampilkan flash message
 */
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        $class = $f['type'] === 'success' ? 'success' : ($f['type'] === 'warning' ? 'warning' : 'danger');
        $icon  = $f['type'] === 'success' ? 'check-circle' : ($f['type'] === 'warning' ? 'exclamation-triangle' : 'times-circle');
        echo "<div class='alert alert-{$class} alert-dismissible fade show' role='alert'>
                <i class='fas fa-{$icon} me-2'></i>{$f['msg']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['flash']);
    }
}

/**
 * Helper: Cek apakah user sudah login
 */
function cek_login() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['id_user'])) {
        redirect(BASE_URL . '/auth/login.php');
    }
}

/**
 * Helper: Cek role user
 */
function cek_role($roles = []) {
    if (empty($roles)) return;
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        redirect(BASE_URL . '/dashboard/index.php?err=akses');
    }
}

/**
 * Helper: Generate nomor otomatis
 * Format: PREFIX/YYYY/MM/XXXX
 */
function generate_nomor($koneksi, $tabel, $kolom, $prefix) {
    $tahun = date('Y');
    $bulan = date('m');
    $like  = "$prefix/$tahun/$bulan/%";
    $stmt  = mysqli_prepare($koneksi, "SELECT COUNT(*) as jml FROM $tabel WHERE $kolom LIKE ?");
    mysqli_stmt_bind_param($stmt, 's', $like);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $urut = str_pad(($row['jml'] + 1), 4, '0', STR_PAD_LEFT);
    return "$prefix/$tahun/$bulan/$urut";
}

/**
 * Helper: Generate kode bahan baku otomatis
 * Format: BHN-001, BHN-002, dst.
 * Mengambil angka tertinggi dari kode yang sudah ada, lalu +1
 *
 * @param  mysqli  $koneksi
 * @param  string  $prefix   Default: 'BHN'
 * @param  int     $pad      Lebar angka (default 3 → 001, 002, …)
 * @return string  Kode baru, mis. 'BHN-011'
 */
function generate_kode_bahan($koneksi, $prefix = 'BHN', $pad = 3) {
    $like = $prefix . '-%';
    $row  = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT MAX(CAST(SUBSTRING_INDEX(kode_bahan, '-', -1) AS UNSIGNED)) AS maks
         FROM bahan_baku
         WHERE kode_bahan LIKE '" . mysqli_real_escape_string($koneksi, $like) . "'"
    ));
    $urut = (int)($row['maks'] ?? 0) + 1;
    return $prefix . '-' . str_pad($urut, $pad, '0', STR_PAD_LEFT);
}

/**
 * Helper: Generate kode proyek otomatis
 * Format: PRY-YYYY-XXX (mis. PRY-2026-004)
 */
function generate_kode_proyek($koneksi) {
    $tahun = date('Y');
    $like  = "PRY-$tahun-%";
    $row   = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT MAX(CAST(SUBSTRING_INDEX(kode_proyek, '-', -1) AS UNSIGNED)) AS maks
         FROM proyek
         WHERE kode_proyek LIKE '" . mysqli_real_escape_string($koneksi, $like) . "'"
    ));
    $urut = (int)($row['maks'] ?? 0) + 1;
    return 'PRY-' . $tahun . '-' . str_pad($urut, 3, '0', STR_PAD_LEFT);
}

/**
 * Helper: Generate nomor Material Request otomatis
 * Format: MR-YYYYMMDD-XXX (mis. MR-20260404-001)
 */
function generate_nomor_mr($koneksi) {
    $hari = date('Ymd');
    $like = "MR-$hari-%";
    $row  = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT MAX(CAST(SUBSTRING_INDEX(nomor_permintaan, '-', -1) AS UNSIGNED)) AS maks
         FROM permintaan_bahan
         WHERE nomor_permintaan LIKE '" . mysqli_real_escape_string($koneksi, $like) . "'"
    ));
    $urut = (int)($row['maks'] ?? 0) + 1;
    return 'MR-' . $hari . '-' . str_pad($urut, 3, '0', STR_PAD_LEFT);
}

/**
 * Helper: Badge status permintaan bahan
 */
function badge_mr($status) {
    $map = [
        'Draft'         => ['secondary', 'Draft'],
        'Diajukan'      => ['warning',   'Diajukan'],
        'Disetujui'     => ['success',   'Disetujui'],
        'Ditolak'       => ['danger',    'Ditolak'],
        'Diproses ke PO'=> ['primary',   'Diproses ke PO'],
    ];
    $d = $map[$status] ?? ['secondary', $status];
    return "<span class='badge bg-{$d[0]}'>{$d[1]}</span>";
}

/**
 * Helper: Badge status PO (terpusat)
 */
function badge_po_status($status) {
    $map = [
        'draft'            => ['secondary', 'Draft'],
        'diajukan'         => ['warning',   'Diajukan'],
        'disetujui'        => ['info',      'Disetujui'],
        'ditolak'          => ['danger',    'Ditolak'],
        'dikirim_sebagian' => ['primary',   'Dikirim Sebagian'],
        'selesai'          => ['success',   'Selesai'],
    ];
    $d = $map[$status] ?? ['secondary', ucfirst($status)];
    return "<span class='badge bg-{$d[0]}'>{$d[1]}</span>";
}