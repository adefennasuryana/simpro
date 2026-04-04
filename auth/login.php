<?php
/**
 * Auth: Login
 * File: auth/login.php
 */
session_start();
require_once '../config/koneksi.php';

// Jika sudah login, langsung ke dashboard
if (!empty($_SESSION['id_user'])) {
    redirect(BASE_URL . '/dashboard/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id_user, nama, username, password, role, status FROM users WHERE username = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'aktif') {
                $error = 'Akun Anda tidak aktif. Hubungi administrator.';
            } else {
                $_SESSION['id_user']  = $user['id_user'];
                $_SESSION['nama']     = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // Update last login (opsional, kolom bisa ditambahkan)
                redirect(BASE_URL . '/dashboard/index.php');
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; SIMPRO</title>
    <meta name="description" content="Login Sistem Informasi Manajemen Proyek - SIMPRO">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: none; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <i class="fas fa-hard-hat"></i>
        </div>
        <h4 class="text-center fw-700 mb-1" style="color:#0f172a;font-weight:700">SIMPRO</h4>
        <p class="text-center text-muted mb-4" style="font-size:.85rem">Sistem Informasi Manajemen Proyek</p>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= e($error) ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="form-login" novalidate>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="Masukkan username" autocomplete="username"
                           value="<?= e($_POST['username'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Masukkan password" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-600" id="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Masuk
            </button>
        </form>

        <div class="mt-4 p-3 rounded" style="background:#f8fafc;font-size:.8rem">
            <div class="text-muted mb-1"><strong>Demo Login:</strong></div>
            <div>Admin: <code>admin</code> / <code>password</code></div>
            <div>Purchasing: <code>purchasing</code> / <code>password</code></div>
            <div>Manajer: <code>manajer</code> / <code>password</code></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click', function() {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>
</body>
</html>
