<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';

requireLogin();

$tabs = ['' => 'Semua', 'menunggu' => 'Menunggu', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
$status = (string) ($_GET['status'] ?? '');
if (!array_key_exists($status, $tabs)) {
    $status = '';
}
$orders = getProviderOrders(currentUserId(), $status);

$activeNav = 'incoming';
$pageTitle = 'Pesanan Masuk';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Pesanan Masuk</h1>
        <p class="page-sub">Pesanan yang sudah dibayar pembeli untuk jasa kamu.</p>

        <div class="tabs">
            <?php foreach ($tabs as $key => $label): ?>
                <a href="?status=<?= $key ?>" class="<?= $status === $key ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <div class="card order-list">
            <?php if (!$orders): ?>
                <div class="empty-box">Belum ada pesanan<?= $status ? ' dengan status ini' : '' ?>.</div>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <div class="order-row">
                    <div class="order-thumb"><?= serviceThumbHtml($o['thumbnail'], $o['kategori_slug']) ?></div>
                    <div>
                        <div class="order-title"><?= clean($o['judul']) ?> <?= $o['jumlah'] > 1 ? '× ' . (int) $o['jumlah'] : '' ?></div>
                        <div class="order-sub">Dari <?= clean($o['buyer_nama']) ?> · <?= clean($o['kode_pesanan']) ?> · <?= formatTanggalIndo($o['created_at']) ?></div>
                        <?php if ($o['catatan']): ?><div class="order-note">📝 <?= clean($o['catatan']) ?></div><?php endif; ?>
                    </div>
                    <div><?= orderStatusBadge($o['status_pesanan']) ?></div>
                    <div class="order-price"><?= formatRupiah($o['total_harga']) ?></div>

                    <div class="order-actions">
                        <?php
                        $buttons = [];
                        if ($o['status_pesanan'] === 'menunggu') {
                            $buttons[] = ['terima', 'Terima & Proses', 'btn-primary', ''];
                            $buttons[] = ['tolak', 'Tolak', 'btn-danger', 'Tolak pesanan ini? Pembeli perlu di-refund secara manual.'];
                        } elseif ($o['status_pesanan'] === 'diproses') {
                            $buttons[] = ['selesai', 'Tandai Selesai', 'btn-success', 'Tandai pesanan ini selesai?'];
                        }
                        foreach ($buttons as [$act, $label, $cls, $confirm]): ?>
                            <form class="inline-form" method="POST" action="<?= BASE_URL ?>/pages/order_action.php" <?= $confirm ? 'data-confirm="' . clean($confirm) . '"' : '' ?>>
                                <?= csrfField() ?>
                                <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                <input type="hidden" name="action" value="<?= $act ?>">
                                <button class="btn <?= $cls ?> btn-sm"><?= $label ?></button>
                            </form>
                        <?php endforeach; ?>
                        <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/pages/chat.php?user=<?= (int) $o['buyer_id'] ?>&jasa=<?= (int) $o['jasa_id'] ?>">💬 Chat Pembeli</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
