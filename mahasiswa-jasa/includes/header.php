<?php
/**
 * Navbar - dipakai di semua halaman publik & dashboard
 * Pastikan config.php & auth_functions.php sudah di-require sebelum include file ini.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$user = isLoggedIn() ? getCurrentUser() : null;
$initial = $user ? strtoupper(substr($user['nama_lengkap'], 0, 1)) : '';
$flash = popFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? clean($pageTitle) . ' - ' : '' ?>MahasiswaJasa</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <a href="<?= BASE_URL ?>/pages/home.php" class="navbar-brand">🎓 MahasiswaJasa</a>
        <ul class="navbar-links">
            <li><a href="<?= BASE_URL ?>/pages/home.php" class="<?= $currentPage === 'home.php' ? 'active' : '' ?>">Beranda</a></li>
            <li><a href="<?= BASE_URL ?>/pages/browse.php" class="<?= $currentPage === 'browse.php' ? 'active' : '' ?>">Cari Jasa</a></li>
            <li><a href="<?= BASE_URL ?>/pages/browse.php#kategori">Kategori</a></li>
            <li><a href="<?= BASE_URL ?>/pages/about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">Tentang Kami</a></li>
        </ul>
        <div class="navbar-actions">
            <?php if ($user): ?>
                <details class="user-menu">
                    <summary class="avatar" title="<?= clean($user['nama_lengkap']) ?>">
                        <?php if ($user['foto_profil']): ?>
                            <img src="<?= BASE_URL ?>/uploads/profile/<?= rawurlencode($user['foto_profil']) ?>" alt="">
                        <?php else: ?>
                            <?= $initial ?>
                        <?php endif; ?>
                    </summary>
                    <div class="user-menu-list">
                        <div class="user-menu-name"><?= clean($user['nama_lengkap']) ?></div>
                        <a href="<?= BASE_URL ?>/pages/dashboard.php">🏠 Dashboard</a>
                        <a href="<?= BASE_URL ?>/pages/my_orders.php">🛒 Pesanan Saya</a>
                        <a href="<?= BASE_URL ?>/pages/chat.php">💬 Chat</a>
                        <a href="<?= BASE_URL ?>/pages/profile.php">👤 Profil</a>
                        <a href="<?= BASE_URL ?>/pages/logout.php">🚪 Keluar</a>
                    </div>
                </details>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-outline" style="color:#fff;border-color:#3B4A6B;background:transparent;">Masuk</a>
                <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php if ($flash): ?>
    <div class="container" style="margin-top:16px;">
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="margin-bottom:0;"><?= clean($flash['message']) ?></div>
    </div>
<?php endif; ?>
