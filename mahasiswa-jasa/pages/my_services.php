<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';
require_once __DIR__ . '/../includes/upload_functions.php';
require_once __DIR__ . '/../includes/service_functions.php';

requireLogin();
$uid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        toggleServiceStatus($id, $uid) ? setFlash('success', 'Status jasa diperbarui.') : setFlash('danger', 'Jasa tidak ditemukan.');
    } elseif ($action === 'delete') {
        $res = deleteService($id, $uid);
        if ($res === 'ok') {
            setFlash('success', 'Jasa dihapus.');
        } elseif ($res === 'has_orders') {
            setFlash('danger', 'Jasa ini sudah punya pesanan sehingga tidak bisa dihapus. Nonaktifkan saja agar tidak tampil di pencarian.');
        } else {
            setFlash('danger', 'Jasa tidak ditemukan.');
        }
    }
    redirect('pages/my_services.php');
}

$services = getProviderServices($uid);

$activeNav = 'services';
$pageTitle = 'Jasa Saya';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div><h1 class="page-title">Jasa Saya</h1><p class="page-sub">Kelola jasa yang kamu tawarkan.</p></div>
            <a class="btn btn-primary" href="<?= BASE_URL ?>/pages/service_form.php">+ Tambah Jasa</a>
        </div>

        <div class="card order-list">
            <?php if (!$services): ?>
                <div class="empty-box">Kamu belum punya jasa.<br><a class="btn btn-primary" href="<?= BASE_URL ?>/pages/service_form.php">Tambah Jasa Pertama</a></div>
            <?php endif; ?>
            <?php foreach ($services as $s): ?>
                <div class="my-service">
                    <div class="order-thumb"><?= serviceThumbHtml($s['thumbnail'], $s['kategori_slug']) ?></div>
                    <div>
                        <div class="order-title"><a href="<?= BASE_URL ?>/pages/service_detail.php?id=<?= (int) $s['id'] ?>"><?= clean($s['judul']) ?></a></div>
                        <div class="order-sub"><?= clean($s['nama_kategori']) ?> · <?= clean(ucfirst($s['tipe_jasa'])) ?> · <?= renderRatingText($s['avg_rating'], $s['review_count']) ?></div>
                        <div class="order-price" style="text-align:left;margin-top:4px;"><?= formatRupiah($s['harga']) ?></div>
                    </div>
                    <div class="my-service-actions">
                        <span class="badge <?= $s['status'] === 'aktif' ? 'badge-success' : 'badge-muted' ?>"><?= $s['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?></span>
                        <form class="inline-form" method="POST" action="">
                            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="toggle">
                            <button class="btn btn-outline btn-sm"><?= $s['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>
                        <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/pages/service_form.php?id=<?= (int) $s['id'] ?>">Edit</a>
                        <form class="inline-form" method="POST" action="" data-confirm="Hapus jasa ini beserta gambar portofolionya?">
                            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="delete">
                            <button class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
