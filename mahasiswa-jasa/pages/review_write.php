<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/review_functions.php';

requireLogin();
$orderId = (int) ($_GET['pesanan'] ?? $_POST['pesanan'] ?? 0);
$order = getReviewableOrder($orderId, currentUserId());

if (!$order || $order['status_pesanan'] !== 'selesai') {
    setFlash('danger', 'Pesanan tidak ditemukan atau belum selesai.');
    redirect('pages/my_orders.php');
}
if ($order['review_id']) {
    setFlash('danger', 'Kamu sudah memberi ulasan untuk pesanan ini.');
    redirect('pages/my_orders.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $res = createReview($orderId, currentUserId(), $_POST['rating'] ?? 0, $_POST['komentar'] ?? '');
    if ($res === true) {
        setFlash('success', 'Terima kasih! Ulasanmu sudah terkirim.');
        redirect('pages/my_orders.php');
    }
    $error = $res;
}

$activeNav = 'myorders';
$pageTitle = 'Tulis Ulasan';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Tulis Ulasan</h1>
        <p class="page-sub"><?= clean($order['judul']) ?> · oleh <?= clean($order['provider_nama']) ?></p>

        <?php if ($error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endif; ?>

        <form method="POST" action="" class="card form-card">
            <?= csrfField() ?>
            <input type="hidden" name="pesanan" value="<?= $orderId ?>">

            <div class="form-group">
                <label class="form-label">Rating</label>
                <div class="star-input">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= (int) ($_POST['rating'] ?? 0) === $i ? 'checked' : '' ?> required>
                        <label for="star<?= $i ?>" title="<?= $i ?> bintang">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Ulasan</label>
                <textarea class="form-control" name="komentar" rows="5" maxlength="1000" placeholder="Bagikan pengalaman kamu..."><?= clean($_POST['komentar'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Kirim Ulasan</button>
                <a class="btn btn-outline" href="<?= BASE_URL ?>/pages/my_orders.php">Batal</a>
            </div>
        </form>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
