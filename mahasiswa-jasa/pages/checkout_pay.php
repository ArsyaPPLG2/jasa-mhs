<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';

requireLogin();

$kode = trim($_GET['kode'] ?? $_POST['kode'] ?? '');
$order = $kode !== '' ? getOrderByCode($kode) : null;

// Hanya pembeli yang boleh melihat halaman ini
if (!$order || (int) $order['buyer_id'] !== currentUserId()) {
    setFlash('danger', 'Pesanan tidak ditemukan.');
    redirect('pages/my_orders.php');
}
if ($order['status_pesanan'] === 'dibatalkan') {
    setFlash('danger', 'Pesanan ini sudah dibatalkan.');
    redirect('pages/my_orders.php');
}
if ($order['status_pembayaran'] === 'dibayar') {
    redirect('pages/checkout_done.php?kode=' . urlencode($kode));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'paid' && markOrderPaid((int) $order['id'], currentUserId())) {
        redirect('pages/checkout_done.php?kode=' . urlencode($kode));
    } elseif ($action === 'cancel' && buyerCancelOrder((int) $order['id'], currentUserId())) {
        setFlash('success', 'Pesanan dibatalkan.');
        redirect('pages/my_orders.php');
    }
    setFlash('danger', 'Aksi tidak dapat diproses.');
    redirect('pages/checkout_pay.php?kode=' . urlencode($kode));
}

$step = 2;
$pageTitle = 'Pembayaran';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/checkout.css">

<div class="checkout-wrap">
    <?php require __DIR__ . '/../includes/checkout_steps.php'; ?>

    <div class="checkout-grid">
        <div class="card">
            <h2 style="margin-top:0;font-size:18px;">Pembayaran via <?= clean(PAYMENT_METHODS[$order['metode_pembayaran']]) ?></h2>
            <p style="font-size:14px;color:var(--text-muted);margin:0;">Selesaikan pembayaran sebesar total di bawah, lalu tekan tombol konfirmasi.</p>

            <div class="pay-box">
                <div style="font-size:13px;color:var(--text-muted);">Total yang harus dibayar</div>
                <div class="big"><?= formatRupiah($order['total_harga']) ?></div>
            </div>

            <div class="pay-box">
                <?php if ($order['metode_pembayaran'] === 'transfer_bank'): ?>
                    <div style="font-size:13px;color:var(--text-muted);">Transfer ke rekening</div>
                    <div class="big"><?= clean(PAY_BANK_INFO) ?></div>
                <?php elseif ($order['metode_pembayaran'] === 'e_wallet'): ?>
                    <div style="font-size:13px;color:var(--text-muted);">Kirim ke e-wallet</div>
                    <div class="big"><?= clean(PAY_EWALLET_INFO) ?></div>
                <?php else: ?>
                    <div style="font-size:13px;color:var(--text-muted);text-align:center;">Scan kode QRIS</div>
                    <div class="qris-box">Pasang gambar QRIS kamu di sini</div>
                <?php endif; ?>
                <div style="font-size:13px;color:var(--text-muted);margin-top:8px;">Kode pesanan: <b><?= clean($order['kode_pesanan']) ?></b> (cantumkan sebagai berita transfer)</div>
            </div>

            <form method="POST" action="" style="display:flex;gap:10px;flex-wrap:wrap;">
                <?= csrfField() ?>
                <input type="hidden" name="kode" value="<?= clean($order['kode_pesanan']) ?>">
                <button type="submit" name="action" value="paid" class="btn btn-primary">Saya Sudah Bayar</button>
                <button type="submit" name="action" value="cancel" class="btn btn-danger" formnovalidate
                        onclick="return confirm('Batalkan pesanan ini?');">Batalkan Pesanan</button>
            </form>
        </div>

        <aside class="card">
            <h2 style="margin-top:0;font-size:18px;">Ringkasan</h2>
            <div class="co-item" style="border:none;padding-bottom:0;">
                <div class="co-thumb" style="width:60px;height:60px;font-size:24px;"><?= serviceThumbHtml($order['thumbnail'], $order['kategori_slug']) ?></div>
                <div>
                    <h3 style="font-size:14px;"><?= clean($order['judul']) ?></h3>
                    <div class="prov"><?= clean($order['provider_nama']) ?></div>
                </div>
            </div>
            <div class="summary-row" style="margin-top:14px;"><span>Jumlah</span><span><?= (int) $order['jumlah'] ?></span></div>
            <?php if ($order['catatan']): ?>
                <div class="summary-row"><span>Catatan</span><span style="text-align:right;max-width:180px;"><?= clean($order['catatan']) ?></span></div>
            <?php endif; ?>
            <div class="summary-total"><span>Total</span><span><?= formatRupiah($order['total_harga']) ?></span></div>
        </aside>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
