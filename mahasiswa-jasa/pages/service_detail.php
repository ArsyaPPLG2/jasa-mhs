<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$jasa = $id > 0 ? getServiceDetail($id) : null;

$isOwner = $jasa && isLoggedIn() && (int) $_SESSION['user_id'] === (int) $jasa['user_id'];

// Jasa tidak ada, atau nonaktif dan yang buka bukan pemiliknya
if (!$jasa || ($jasa['status'] !== 'aktif' && !$isOwner)) {
    http_response_code(404);
    $pageTitle = 'Jasa tidak ditemukan';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container" style="padding:60px 24px;text-align:center;">'
       . '<h1>Jasa tidak ditemukan</h1>'
       . '<p style="color:var(--text-muted)">Jasa yang kamu cari mungkin sudah dihapus atau dinonaktifkan.</p>'
       . '<a class="btn btn-primary" href="' . BASE_URL . '/pages/browse.php">Cari jasa lain</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$providerId  = (int) $jasa['user_id'];
$portfolio   = getServicePortfolio($id);
$providerStat = getProviderStats($providerId);
$skills      = getUserSkills($providerId);
$reviews     = getServiceReviews($id, 5);

// Kumpulan gambar galeri: thumbnail dulu, lalu portofolio
$images = [];
if ($jasa['thumbnail']) {
    $images[] = uploadUrl($jasa['thumbnail'], 'portfolio');
}
foreach ($portfolio as $p) {
    $images[] = uploadUrl($p['gambar'], 'portfolio');
}

$orderUrl = BASE_URL . '/pages/checkout.php?jasa=' . $id;
$chatUrl  = BASE_URL . '/pages/chat.php?user=' . $providerId . '&jasa=' . $id;
$initial  = strtoupper(substr($jasa['nama_lengkap'], 0, 1));

$pageTitle = $jasa['judul'];
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/detail.css">

<div class="container detail-wrap">

    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/pages/browse.php">Cari Jasa</a> ›
        <a href="<?= BASE_URL ?>/pages/browse.php?kategori=<?= urlencode($jasa['kategori_slug']) ?>"><?= clean($jasa['nama_kategori']) ?></a> ›
        <?= clean($jasa['judul']) ?>
    </div>

    <div class="detail-top">
        <!-- Galeri -->
        <div>
            <div class="gallery-main" id="galleryMain">
                <?php if ($images): ?>
                    <img src="<?= $images[0] ?>" alt="<?= clean($jasa['judul']) ?>" id="galleryImg">
                <?php else: ?>
                    <?= categoryIcon($jasa['kategori_slug']) ?>
                <?php endif; ?>
            </div>
            <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-src="<?= $img ?>" aria-label="Gambar <?= $i + 1 ?>">
                            <img src="<?= $img ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div>
            <div class="detail-head">
                <h1><?= clean($jasa['judul']) ?></h1>
                <div class="detail-price"><?= formatRupiah($jasa['harga']) ?></div>
            </div>
            <span class="detail-cat"><?= categoryIcon($jasa['kategori_slug']) ?> <?= clean($jasa['nama_kategori']) ?> · <?= clean(ucfirst($jasa['tipe_jasa'])) ?></span>
            <div class="detail-rating"><?= renderRatingText($jasa['avg_rating'], $jasa['review_count']) ?><?= $jasa['review_count'] > 0 ? ' ulasan' : '' ?></div>

            <ul class="detail-facts">
                <?php if ($jasa['estimasi_pengerjaan']): ?>
                    <li>⏱️ <?= clean($jasa['estimasi_pengerjaan']) ?> pengerjaan</li>
                <?php endif; ?>
                <li>🔁 Revisi <?= (int) $jasa['jumlah_revisi'] ?>x</li>
                <?php if ($jasa['format_file']): ?>
                    <li>📄 File: <?= clean($jasa['format_file']) ?></li>
                <?php endif; ?>
            </ul>

            <?php if ($isOwner): ?>
                <div class="own-note">Ini jasa milik kamu<?= $jasa['status'] !== 'aktif' ? ' (saat ini nonaktif, hanya kamu yang bisa melihatnya)' : '' ?>.</div>
            <?php else: ?>
                <div class="detail-actions">
                    <a class="btn btn-primary" href="<?= isLoggedIn() ? $orderUrl : BASE_URL . '/pages/login.php' ?>">Pesan Sekarang</a>
                    <a class="btn btn-outline-blue" href="<?= isLoggedIn() ? $chatUrl : BASE_URL . '/pages/login.php' ?>">Chat Penyedia</a>
                </div>
                <?php if (!isLoggedIn()): ?>
                    <p class="muted-note" style="margin-top:10px;">Masuk dulu untuk memesan atau chat dengan penyedia.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="detail-bottom">
        <div>
            <section class="detail-section">
                <h2>Deskripsi Jasa</h2>
                <p class="desc"><?= nl2br(clean($jasa['deskripsi'])) ?></p>
            </section>

            <section class="detail-section">
                <h2>Portofolio</h2>
                <?php if ($portfolio): ?>
                    <div class="portfolio-grid">
                        <?php foreach ($portfolio as $p): ?>
                            <div class="portfolio-item"><img src="<?= uploadUrl($p['gambar'], 'portfolio') ?>" alt="Contoh hasil kerja" loading="lazy"></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted-note">Penyedia belum menambahkan portofolio.</p>
                <?php endif; ?>
            </section>

            <section class="detail-section">
                <h2>Ulasan</h2>
                <?php if ($reviews): ?>
                    <div class="card">
                        <?php foreach ($reviews as $r): ?>
                            <div class="review-item">
                                <div class="review-top">
                                    <strong><?= clean($r['nama_lengkap']) ?></strong>
                                    <span class="review-date"><?= formatTanggalIndo($r['created_at']) ?></span>
                                </div>
                                <span class="stars"><?= str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) ?></span>
                                <?php if ($r['komentar']): ?>
                                    <p class="review-text"><?= clean($r['komentar']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted-note">Belum ada ulasan untuk jasa ini.</p>
                <?php endif; ?>
            </section>
        </div>

        <aside>
            <div class="card provider-card">
                <h3>Profil Penyedia Jasa</h3>
                <div class="provider-row">
                    <div class="avatar-lg">
                        <?php if ($jasa['foto_profil']): ?>
                            <img src="<?= uploadUrl($jasa['foto_profil'], 'profile') ?>" alt="">
                        <?php else: ?>
                            <?= $initial ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="provider-name"><?= clean($jasa['nama_lengkap']) ?></div>
                        <div class="provider-sub">
                            <?= $jasa['universitas'] ? 'Mahasiswa ' . clean($jasa['universitas']) : 'Mahasiswa' ?>
                        </div>
                    </div>
                </div>
                <div class="provider-meta"><?= renderRatingText($providerStat['avg_rating'], $providerStat['review_count']) ?><?= $providerStat['review_count'] > 0 ? ' ulasan' : '' ?></div>
                <div class="provider-meta">🕐 Bergabung sejak <?= formatTanggalIndo($jasa['provider_joined'], false) ?></div>
                <?php if ($jasa['prodi']): ?>
                    <div class="provider-meta">🎓 <?= clean($jasa['prodi']) ?></div>
                <?php endif; ?>

                <div class="provider-actions">
                    <a class="btn btn-outline-blue" href="<?= BASE_URL ?>/pages/profile.php?id=<?= $providerId ?>">Lihat Profil</a>
                    <?php if (!$isOwner): ?>
                        <a class="btn btn-outline" href="<?= isLoggedIn() ? $chatUrl : BASE_URL . '/pages/login.php' ?>">Chat</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($skills): ?>
                <div class="card">
                    <h3 style="font-size:15px;margin:0 0 12px;">Skill</h3>
                    <div class="skill-tags">
                        <?php foreach ($skills as $sk): ?>
                            <span class="skill-tag"><?= clean($sk) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<script>
// Klik thumbnail -> ganti gambar utama
document.querySelectorAll('.gallery-thumbs button').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('galleryImg').src = btn.dataset.src;
        document.querySelectorAll('.gallery-thumbs button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
