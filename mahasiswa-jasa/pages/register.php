<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';

if (isLoggedIn()) {
    redirect('pages/home.php');
}

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['nama_lengkap'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';
    $universitas = trim($_POST['universitas'] ?? '');
    $prodi       = trim($_POST['prodi'] ?? '');

    if ($nama === '' || $email === '' || $password === '') {
        $errorMsg = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $errorMsg = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $errorMsg = 'Konfirmasi password tidak cocok.';
    } else {
        $result = registerUser($nama, $email, $password, $universitas, $prodi);
        if ($result['success']) {
            redirect('pages/login.php?registered=1');
        } else {
            $errorMsg = $result['message'];
        }
    }
}

$pageTitle = 'Daftar';
$authHeadline = 'Jadikan skill kamu sumber cuan';
$authSubtext  = 'Daftar sebagai mahasiswa dan mulai tawarkan jasa desain, coding, editing, atau apapun keahlianmu.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - MahasiswaJasa</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body>

<div class="auth-split">

    <?php require_once __DIR__ . '/../includes/auth_visual.php'; ?>

    <div class="auth-form-panel">
        <div class="auth-box">
            <h1>Buat Akun Baru</h1>
            <p class="subtitle">Daftar dan mulai tawarkan atau cari jasa mahasiswa.</p>

            <?php if ($errorMsg): ?>
                <div class="alert alert-danger"><?= clean($errorMsg) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap kamu" required
                           value="<?= isset($_POST['nama_lengkap']) ? clean($_POST['nama_lengkap']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@kampus.ac.id" required
                           value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Universitas</label>
                    <input type="text" name="universitas" class="form-control" placeholder="Contoh: UNSIKA">
                </div>
                <div class="form-group">
                    <label class="form-label">Program Studi</label>
                    <input type="text" name="prodi" class="form-control" placeholder="Contoh: Informatika">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            </form>

            <p class="auth-footer">Sudah punya akun? <a href="<?= BASE_URL ?>/pages/login.php">Masuk di sini</a></p>
        </div>
    </div>

</div>

<script src="<?= BASE_URL ?>/assets/js/floating-icons.js"></script>
</body>
</html>
