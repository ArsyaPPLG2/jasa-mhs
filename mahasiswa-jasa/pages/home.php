<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';

$keyword    = clean($_GET['q'] ?? '');
$categories = getAllCategories();
$popular    = getPopularServices(8);

$pageTitle = 'Beranda';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">

<section class="hero icon-float-zone" id="heroVisual">
    <div class="container">
        <div class="hero-inner">
            <h1>Jasa Mahasiswa, Solusi Kebutuhanmu!</h1>
            <p>Temukan berbagai jasa mahasiswa dengan mudah, aman, dan terpercaya. Dari desain, editing, coding, hingga tugas akademik, semua ada di sini.</p>
            <form class="hero-search" action="<?= BASE_URL ?>/pages/browse.php" method="GET">
                <input type="text" name="q" placeholder="Cari jasa, contoh: desain, editing, coding..." value="<?= clean($keyword) ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
            </form>
        </div>
    </div>

    <div class="float-wrap" style="left:72%; top:14%; animation-delay:0s;">
        <span class="float-icon" data-depth="16" title="Desain">🎨</span>
    </div>
    <div class="float-wrap" style="left:88%; top:38%; animation-delay:.5s;">
        <span class="float-icon" data-depth="24" title="Editing Video">🎥</span>
    </div>
    <div class="float-wrap" style="left:78%; top:66%; animation-delay:1s;">
        <span class="float-icon" data-depth="18" title="Pemrograman">💻</span>
    </div>
</section>

<div class="container">

    <section class="section" id="kategori">
        <div class="section-header">
            <h2>Kategori Jasa</h2>
            <a href="<?= BASE_URL ?>/pages/browse.php">Lihat Semua</a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a class="category-item" href="<?= BASE_URL ?>/pages/browse.php?kategori=<?= urlencode($cat['slug']) ?>">
                    <div class="category-icon-circle"><?= categoryIcon($cat['slug']) ?></div>
                    <span class="label"><?= clean($cat['nama_kategori']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Jasa Populer</h2>
            <a href="<?= BASE_URL ?>/pages/browse.php">Lihat Semua</a>
        </div>

        <?php if (empty($popular)): ?>
            <div class="empty-state">
                Belum ada jasa yang tersedia. Jadilah yang pertama menawarkan jasa kamu!
            </div>
        <?php else: ?>
            <div class="service-grid">
                <?php foreach ($popular as $s): ?>
                    <a class="service-card" href="<?= BASE_URL ?>/pages/service_detail.php?id=<?= (int)$s['id'] ?>">
                        <div class="service-thumb" style="background: linear-gradient(135deg, var(--navy), var(--navy-light));">
                            <?= serviceThumbHtml($s['thumbnail'], $s['kategori_slug']) ?>
                        </div>
                        <div class="service-info">
                            <p class="service-title"><?= clean($s['judul']) ?></p>
                            <p class="service-provider"><?= clean($s['nama_lengkap']) ?></p>
                            <div class="service-rating"><?= renderRatingText($s['avg_rating'], $s['review_count']) ?></div>
                            <div class="service-price"><?= formatRupiah($s['harga']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<script src="<?= BASE_URL ?>/assets/js/floating-icons.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
