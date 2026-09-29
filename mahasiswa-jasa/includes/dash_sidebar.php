<?php
/** Sidebar dashboard. Set $activeNav sebelum include. */
$activeNav = $activeNav ?? '';
$navItems = [
    'dashboard' => ['dashboard.php',       '🏠', 'Dashboard'],
    'incoming'  => ['orders_incoming.php', '📥', 'Pesanan Masuk'],
    'services'  => ['my_services.php',     '🧰', 'Jasa Saya'],
    'earnings'  => ['earnings.php',        '💰', 'Pendapatan'],
    'reviews'   => ['reviews.php',         '⭐', 'Ulasan'],
    'profile'   => ['profile.php',         '👤', 'Profil'],
];
$navBuyer = [
    'myorders'  => ['my_orders.php',       '🛒', 'Pesanan Saya'],
    'chat'      => ['chat.php',            '💬', 'Chat'],
];
?>
<aside class="sidebar">
    <?php foreach ($navItems as $key => [$file, $icon, $label]): ?>
        <a href="<?= BASE_URL ?>/pages/<?= $file ?>" class="<?= $activeNav === $key ? 'active' : '' ?>"><span><?= $icon ?></span> <?= $label ?></a>
    <?php endforeach; ?>
    <div class="sidebar-divider"></div>
    <?php foreach ($navBuyer as $key => [$file, $icon, $label]): ?>
        <a href="<?= BASE_URL ?>/pages/<?= $file ?>" class="<?= $activeNav === $key ? 'active' : '' ?>"><span><?= $icon ?></span> <?= $label ?></a>
    <?php endforeach; ?>
    <div class="sidebar-divider"></div>
    <a href="<?= BASE_URL ?>/pages/logout.php"><span>🚪</span> Keluar</a>
</aside>
