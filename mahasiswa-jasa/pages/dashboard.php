<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';
require_once __DIR__ . '/../includes/service_functions.php';

requireLogin();
$uid = currentUserId();
$me = getCurrentUser();

$stats = getProviderStats2($uid);
$rating = getProviderStats($uid);
$recent = getProviderOrders($uid, '', 5);
$services = getProviderServices($uid);

// perubahan pendapatan dibanding bulan lalu
$revNow = (float) $stats['revenue_month'];
$revPrev = (float) $stats['revenue_prev'];
if ($revPrev > 0) {
    $pct = round(($revNow - $revPrev) / $revPrev * 100);
    $revHint = ($pct >= 0 ? '+' : '') . $pct . '% dari bulan lalu';
    $revClass = $pct >= 0 ? 'up' : 'down';
} else {
    $revHint = $revNow > 0 ? 'Bulan lalu belum ada pendapatan' : 'Belum ada pendapatan bulan ini';
    $revClass = '';
}

$activeNav = 'dashboard';
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Halo, <?= clean(explode(' ', $me['nama_lengkap'])[0]) ?>! 👋</h1>
        <p class="page-sub">Berikut ringkasan aktivitas akun kamu.</p>

        <div class="stats-grid">
            <div class="card stat-card">
                <div class="label">Pesanan Masuk</div>
                <div class="value"><?= (int) $stats['total_orders'] ?></div>
                <div class="hint <?= $stats['week_orders'] > 0 ? 'up' : '' ?>">+<?= (int) $stats['week_orders'] ?> dalam 7 hari terakhir</div>
            </div>
            <div class="card stat-card">
                <div class="label">Pendapatan Bulan Ini</div>
                <div class="value"><?= formatRupiah($revNow) ?></div>
                <div class="hint <?= $revClass ?>"><?= clean($revHint) ?></div>
            </div>
            <div class="card stat-card">
                <div class="label">Rating</div>
                <div class="value"><?= $rating['review_count'] > 0 ? number_format((float) $rating['avg_rating'], 1) : '-' ?></div>
                <div class="hint">(<?= (int) $rating['review_count'] ?> ulasan)</div>
            </div>
        </div>

        <h2 class="section-title">Pesanan Terbaru <a href="<?= BASE_URL ?>/pages/orders_incoming.php">Lihat Semua</a></h2>
        <div class="card order-list">
            <?php if (!$recent): ?><div class="empty-box">Belum ada pesanan masuk.</div><?php endif; ?>
            <?php foreach ($recent as $o): ?>
                <div class="order-row" style="grid-template-columns:52px 1fr auto auto;">
                    <div class="order-thumb"><?= serviceThumbHtml($o['thumbnail'], $o['kategori_slug']) ?></div>
                    <div>
                        <div class="order-title"><?= clean($o['judul']) ?></div>
                        <div class="order-sub"><?= clean($o['buyer_nama']) ?> · <?= formatTanggalIndo($o['created_at']) ?></div>
                    </div>
                    <div><?= orderStatusBadge($o['status_pesanan']) ?></div>
                    <div class="order-price"><?= formatRupiah($o['total_harga']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="section-title">Jasa yang Kamu Tawarkan <a class="btn btn-primary btn-sm" style="color:#fff;" href="<?= BASE_URL ?>/pages/service_form.php">+ Tambah Jasa</a></h2>
        <div class="card order-list">
            <?php if (!$services): ?>
                <div class="empty-box">Kamu belum menawarkan jasa apapun. Mulai dari skill yang kamu kuasai!<br><a class="btn btn-primary" href="<?= BASE_URL ?>/pages/service_form.php">Tambah Jasa</a></div>
            <?php endif; ?>
            <?php foreach (array_slice($services, 0, 4) as $s): ?>
                <div class="order-row" style="grid-template-columns:52px 1fr auto auto;">
                    <div class="order-thumb"><?= serviceThumbHtml($s['thumbnail'], $s['kategori_slug']) ?></div>
                    <div>
                        <div class="order-title"><a href="<?= BASE_URL ?>/pages/service_detail.php?id=<?= (int) $s['id'] ?>"><?= clean($s['judul']) ?></a></div>
                        <div class="order-sub"><?= formatRupiah($s['harga']) ?></div>
                    </div>
                    <div><span class="badge <?= $s['status'] === 'aktif' ? 'badge-success' : 'badge-muted' ?>"><?= $s['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?></span></div>
                    <div><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/pages/service_form.php?id=<?= (int) $s['id'] ?>">Edit</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
