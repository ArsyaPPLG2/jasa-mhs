<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';

if (isLoggedIn()) {
    redirect('pages/home.php');
}

$errorMsg = '';
$successMsg = isset($_GET['registered']) ? 'Pendaftaran berhasil! Silakan masuk.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errorMsg = 'Email dan password wajib diisi.';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            redirect('pages/home.php');
        } else {
            $errorMsg = $result['message'];
        }
    }
}

$pageTitle = 'Masuk';
$authHeadline = 'Skill kamu bisa jadi peluang';
$authSubtext  = 'Masuk untuk lanjut cari jasa atau kelola jasa yang kamu tawarkan ke sesama mahasiswa.';
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
            <h1>Selamat Datang Kembali</h1>
            <p class="subtitle">Masuk untuk lanjut cari atau kelola jasa kamu.</p>

            <?php if ($errorMsg): ?>
                <div class="alert alert-danger"><?= clean($errorMsg) ?></div>
            <?php endif; ?>
            <?php if ($successMsg): ?>
                <div class="alert alert-success"><?= clean($successMsg) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Password kamu" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>

            <p class="auth-footer">Belum punya akun? <a href="<?= BASE_URL ?>/pages/register.php">Daftar di sini</a></p>
        </div>
    </div>

</div>

<script src="<?= BASE_URL ?>/assets/js/floating-icons.js"></script>
</body>
</html>
