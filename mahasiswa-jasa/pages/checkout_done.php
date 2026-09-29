<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';

requireLogin();

$kode = trim($_GET['kode'] ?? '');
$order = $kode !== '' ? getOrderByCode($kode) : null;

if (!$order || (int) $order['buyer_id'] !== currentUserId()) {
    setFlash('danger', 'Pesanan tidak ditemukan.');
    redirect('pages/my_orders.php');
}
if ($order['status_pembayaran'] !== 'dibayar') {
    redirect('pages/checkout_pay.php?kode=' . urlencode($kode));
}

$step = 4; // semua langkah ditandai selesai
$pageTitle = 'Pesanan Berhasil';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/checkout.css">

<div class="checkout-wrap" style="max-width:640px;">
    <?php require __DIR__ . '/../includes/checkout_steps.php'; ?>

    <div class="card">
        <div class="done-hero">
            <div class="check">✅</div>
            <h1>Pesanan berhasil dibuat!</h1>
            <p style="color:var(--text-muted);font-size:14px;margin:0 0 14px;">Penyedia jasa sudah menerima pesananmu dan akan segera memprosesnya.</p>
            <span class="code-pill"><?= clean($order['kode_pesanan']) ?></span>
        </div>

        <table class="info-table" style="margin-top:18px;">
            <tr><td>Jasa</td><td><?= clean($order['judul']) ?></td></tr>
            <tr><td>Penyedia</td><td><?= clean($order['provider_nama']) ?></td></tr>
            <tr><td>Jumlah</td><td><?= (int) $order['jumlah'] ?></td></tr>
            <tr><td>Metode</td><td><?= clean(PAYMENT_METHODS[$order['metode_pembayaran']]) ?></td></tr>
            <tr><td>Total</td><td><b><?= formatRupiah($order['total_harga']) ?></b></td></tr>
        </table>

        <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;">
            <a class="btn btn-primary" href="<?= BASE_URL ?>/pages/chat.php?user=<?= (int) $order['provider_id'] ?>&jasa=<?= (int) $order['jasa_id'] ?>">Chat Penyedia</a>
            <a class="btn btn-outline" href="<?= BASE_URL ?>/pages/my_orders.php">Lihat Pesanan Saya</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
