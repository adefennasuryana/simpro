<?php
/**
 * Template: Header
 * Digunakan di semua halaman yang butuh layout
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &mdash; SIMPRO</title>
    <meta name="description" content="SIMPRO - Sistem Informasi Manajemen Proyek dan Purchase Order">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- SIDEBAR -->
<nav id="sidebar">
    <div class="brand d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-0"><i class="fas fa-hard-hat me-2"></i>SIMPRO</h5>
            <small>Sistem Manajemen Proyek</small>
        </div>
        <button id="sidebarClose" class="btn btn-sm btn-link text-white d-md-none p-0 border-0 shadow-none">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>

    <?php
    $uri     = $_SERVER['REQUEST_URI'] ?? '';
    $isActive = fn($p) => strpos($uri, $p) !== false ? 'active' : '';
    ?>

    <ul class="nav flex-column mt-2">
        <li class="nav-label">Utama</li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/dashboard/index.php" class="<?= $isActive('/dashboard') ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>

        <?php if (in_array($_SESSION['role'] ?? '', ['admin','manajer','purchasing'])): ?>
        <li class="nav-label">Master Data</li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/supplier/index.php" class="<?= $isActive('/supplier') ?>">
                <i class="fas fa-truck"></i> Supplier
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/proyek/index.php" class="<?= $isActive('/proyek') ?>">
                <i class="fas fa-project-diagram"></i> Proyek
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/bahan_baku/index.php" class="<?= $isActive('/bahan_baku') ?>">
                <i class="fas fa-boxes"></i> Bahan Baku
            </a>
        </li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/user/index.php" class="<?= $isActive('/user') ?>">
                <i class="fas fa-users"></i> Data User
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-label">Transaksi</li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/permintaan/index.php" class="<?= $isActive('/permintaan') ?>">
                <i class="fas fa-clipboard-list"></i> Permintaan Bahan
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/po/index.php" class="<?= $isActive('/po') ?>">
                <i class="fas fa-file-invoice"></i> Purchase Order
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/upah_harian/index.php" class="<?= $isActive('/upah_harian') ?>">
                <i class="fas fa-users-cog"></i> Upah Tenaga Kerja
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/penerimaan/index.php" class="<?= $isActive('/penerimaan') ?>">
                <i class="fas fa-boxes-stacked"></i> Penerimaan Barang
            </a>
        </li>

        <li class="nav-label">Laporan</li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/monitoring_operasional/index.php" class="<?= $isActive('/monitoring_operasional') ?>">
                <i class="fas fa-chart-line"></i> Monitoring Operasional
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/laporan/index.php" class="<?= $isActive('/laporan') ?>">
                <i class="fas fa-chart-bar"></i> Laporan Umum
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-label">Akun</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/auth/logout.php">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </li>
    </ul>
</nav>
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<!-- MAIN CONTENT -->
<div id="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm btn-light d-md-none">
                <i class="fas fa-bars"></i>
            </button>
            <h6 class="page-title"><?= e($pageTitle) ?></h6>
        </div>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?></div>
            <div>
                <div style="font-weight:600;font-size:.85rem"><?= e($_SESSION['nama'] ?? '') ?></div>
                <div style="font-size:.75rem;color:#64748b;text-transform:capitalize"><?= e($_SESSION['role'] ?? '') ?></div>
            </div>
        </div>
    </div>

    <!-- PAGE CONTENT -->
    <div class="p-4">
