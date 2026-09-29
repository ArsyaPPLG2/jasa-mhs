<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';

requireLogin();
$orders = getBuyerOrders(currentUserId());

$activeNav = 'myorders';
$pageTitle = 'Pesanan Saya';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Pesanan Saya</h1>
        <p class="page-sub">Jasa yang kamu pesan dari mahasiswa lain.</p>

        <div class="card order-list">
            <?php if (!$orders): ?>
                <div class="empty-box">Kamu belum pernah memesan jasa.<br><a class="btn btn-primary" href="<?= BASE_URL ?>/pages/browse.php">Cari Jasa</a></div>
            <?php endif; ?>
            <?php foreach ($orders as $o):
                $unpaid = $o['status_pembayaran'] === 'menunggu' && $o['status_pesanan'] === 'menunggu';
            ?>
                <div class="order-row">
                    <div class="order-thumb"><?= serviceThumbHtml($o['thumbnail'], $o['kategori_slug']) ?></div>
                    <div>
                        <div class="order-title"><?= clean($o['judul']) ?> <?= $o['jumlah'] > 1 ? '× ' . (int) $o['jumlah'] : '' ?></div>
                        <div class="order-sub"><?= clean($o['provider_nama']) ?> · <?= clean($o['kode_pesanan']) ?> · <?= formatTanggalIndo($o['created_at']) ?></div>
                    </div>
                    <div><?= $unpaid ? '<span class="badge badge-warning">Belum dibayar</span>' : orderStatusBadge($o['status_pesanan']) ?></div>
                    <div class="order-price"><?= formatRupiah($o['total_harga']) ?></div>

                    <div class="order-actions">
                        <?php if ($unpaid): ?>
                            <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/pages/checkout_pay.php?kode=<?= urlencode($o['kode_pesanan']) ?>">Bayar Sekarang</a>
                            <form class="inline-form" method="POST" action="<?= BASE_URL ?>/pages/order_action.php" data-confirm="Batalkan pesanan ini?">
                                <?= csrfField() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>"><input type="hidden" name="action" value="batal">
                                <button class="btn btn-danger btn-sm">Batalkan</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($o['status_pesanan'] === 'selesai'): ?>
                            <?php if ($o['review_id']): ?>
                                <span class="badge badge-success">✓ Sudah diulas</span>
                            <?php else: ?>
                                <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/pages/review_write.php?pesanan=<?= (int) $o['id'] ?>">⭐ Beri Ulasan</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/pages/chat.php?user=<?= (int) $o['provider_id'] ?>&jasa=<?= (int) $o['jasa_id'] ?>">💬 Chat</a>
                        <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/pages/service_detail.php?id=<?= (int) $o['jasa_id'] ?>">Lihat Jasa</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
