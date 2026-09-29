<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';

requireLogin();

$jasaId = (int) ($_GET['jasa'] ?? $_POST['jasa'] ?? 0);
$jasa = $jasaId > 0 ? getServiceDetail($jasaId) : null;

if (!$jasa || $jasa['status'] !== 'aktif') {
    setFlash('danger', 'Jasa tidak ditemukan atau sudah tidak tersedia.');
    redirect('pages/browse.php');
}
if ((int) $jasa['user_id'] === currentUserId()) {
    setFlash('danger', 'Kamu tidak bisa memesan jasa milik sendiri.');
    redirect('pages/service_detail.php?id=' . $jasaId);
}

$error = '';
$jumlah = max(1, min(20, (int) ($_POST['jumlah'] ?? 1)));
$catatan = $_POST['catatan'] ?? '';
$metode = $_POST['metode'] ?? 'transfer_bank';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $result = createOrder($jasaId, currentUserId(), $jumlah, $catatan, $metode);
    if (is_array($result)) {
        $error = $result['error'];
    } else {
        redirect('pages/checkout_pay.php?kode=' . urlencode($result));
    }
}

$step = 1;
$pageTitle = 'Pemesanan';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/checkout.css">

<div class="checkout-wrap">
    <?php require __DIR__ . '/../includes/checkout_steps.php'; ?>

    <?php if ($error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endif; ?>

    <form method="POST" action="">
        <?= csrfField() ?>
        <input type="hidden" name="jasa" value="<?= $jasaId ?>">
        <div class="checkout-grid">
            <div>
                <div class="card">
                    <h2 style="margin-top:0;font-size:18px;">Detail Pesanan</h2>
                    <div class="co-item">
                        <div class="co-thumb"><?= serviceThumbHtml($jasa['thumbnail'], $jasa['kategori_slug']) ?></div>
                        <div>
                            <h3><?= clean($jasa['judul']) ?></h3>
                            <div class="prov"><?= clean($jasa['nama_lengkap']) ?></div>
                            <div class="price"><?= formatRupiah($jasa['harga']) ?></div>
                        </div>
                    </div>

                    <div class="qty-row">
                        <span>Jumlah</span>
                        <div class="qty">
                            <button type="button" id="qtyMinus" aria-label="Kurangi">−</button>
                            <input type="number" name="jumlah" id="qtyInput" value="<?= $jumlah ?>" min="1" max="20">
                            <button type="button" id="qtyPlus" aria-label="Tambah">+</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="catatan" class="form-control" maxlength="1000" placeholder="Contoh: warna lebih cerah, ukuran A4, dll"><?= clean($catatan) ?></textarea>
                    </div>

                    <div class="total-line"><span>Total Harga</span><span id="totalText"><?= formatRupiah($jasa['harga'] * $jumlah) ?></span></div>
                </div>

                <div class="card">
                    <h2 style="margin-top:0;font-size:18px;">Metode Pembayaran</h2>
                    <?php foreach (PAYMENT_METHODS as $val => $label): ?>
                        <label class="pay-option">
                            <input type="radio" name="metode" value="<?= $val ?>" <?= $metode === $val ? 'checked' : '' ?>>
                            <span><?= clean($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="card">
                <h2 style="margin-top:0;font-size:18px;">Ringkasan</h2>
                <div class="summary-row"><span><?= clean($jasa['judul']) ?> × <b id="sumQty"><?= $jumlah ?></b></span><span id="sumSub"><?= formatRupiah($jasa['harga'] * $jumlah) ?></span></div>
                <div class="summary-total"><span>Total</span><span id="sumTotal"><?= formatRupiah($jasa['harga'] * $jumlah) ?></span></div>
                <button type="submit" class="btn btn-primary btn-block">Lanjut ke Pembayaran</button>
                <p class="secure-note">🔒 Pesanan baru diteruskan ke penyedia setelah pembayaran dikonfirmasi.</p>
            </aside>
        </div>
    </form>
</div>

<script>
(function () {
    var price = <?= (float) $jasa['harga'] ?>;
    var input = document.getElementById('qtyInput');
    function rp(n) { return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function update() {
        var q = Math.max(1, Math.min(20, parseInt(input.value, 10) || 1));
        input.value = q;
        ['totalText', 'sumSub', 'sumTotal'].forEach(function (id) { document.getElementById(id).textContent = rp(price * q); });
        document.getElementById('sumQty').textContent = q;
    }
    document.getElementById('qtyMinus').onclick = function () { input.value = (parseInt(input.value, 10) || 1) - 1; update(); };
    document.getElementById('qtyPlus').onclick = function () { input.value = (parseInt(input.value, 10) || 1) + 1; update(); };
    input.addEventListener('input', update);
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
