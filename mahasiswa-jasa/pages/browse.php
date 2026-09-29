<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';

// ---- Ambil & rapikan parameter dari URL ----
$q         = trim($_GET['q'] ?? '');
$kategori  = trim($_GET['kategori'] ?? '');
$tipe      = in_array($_GET['tipe'] ?? '', ['online', 'offline'], true) ? $_GET['tipe'] : '';
$ratingMin = in_array($_GET['rating'] ?? '', ['3.5', '4', '4.5'], true) ? $_GET['rating'] : '';
$urut      = $_GET['urut'] ?? 'populer';

$hargaMin = (isset($_GET['harga_min']) && $_GET['harga_min'] !== '') ? max(0, (int) $_GET['harga_min']) : null;
$hargaMax = (isset($_GET['harga_max']) && $_GET['harga_max'] !== '') ? max(0, (int) $_GET['harga_max']) : null;

$categories = getAllCategories();
$services = searchServices([
    'q'         => $q,
    'kategori'  => $kategori,
    'tipe'      => $tipe,
    'rating'    => $ratingMin,
    'harga_min' => $hargaMin,
    'harga_max' => $hargaMax,
    'urut'      => $urut,
]);

$pageTitle = 'Cari Jasa';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/browse.css">

<form method="GET" action="" id="filterForm">
<div class="layout">

    <aside class="sidebar browse-sidebar">
        <div class="block" id="kategori">
            <h3>Kategori</h3>
            <label class="cat-option">
                <input type="radio" name="kategori" value="" <?= $kategori === '' ? 'checked' : '' ?>>
                <span>📋</span> Semua
            </label>
            <?php foreach ($categories as $cat): ?>
                <label class="cat-option">
                    <input type="radio" name="kategori" value="<?= clean($cat['slug']) ?>" <?= $kategori === $cat['slug'] ? 'checked' : '' ?>>
                    <span><?= categoryIcon($cat['slug']) ?></span> <?= clean($cat['nama_kategori']) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-divider"></div>

        <div class="block">
            <div class="filter-head">
                <h3>Filter</h3>
                <a href="<?= BASE_URL ?>/pages/browse.php">Reset</a>
            </div>

            <h4>Harga (Rp)</h4>
            <div class="price-row">
                <input type="number" class="form-control" name="harga_min" min="0" step="1000" placeholder="Min"
                       value="<?= $hargaMin !== null ? $hargaMin : '' ?>">
                <input type="number" class="form-control" name="harga_max" min="0" step="1000" placeholder="Max"
                       value="<?= $hargaMax !== null ? $hargaMax : '' ?>">
            </div>

            <h4>Rating</h4>
            <?php foreach (['' => 'Semua', '4.5' => '4.5+ ⭐', '4' => '4.0+ ⭐', '3.5' => '3.5+ ⭐'] as $val => $label): ?>
                <label class="radio-row">
                    <input type="radio" name="rating" value="<?= $val ?>" <?= $ratingMin === (string)$val ? 'checked' : '' ?>>
                    <?= $label ?>
                </label>
            <?php endforeach; ?>

            <h4>Tipe Jasa</h4>
            <?php foreach (['' => 'Semua', 'online' => 'Online', 'offline' => 'Offline'] as $val => $label): ?>
                <label class="radio-row">
                    <input type="radio" name="tipe" value="<?= $val ?>" <?= $tipe === $val ? 'checked' : '' ?>>
                    <?= $label ?>
                </label>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary btn-block">Terapkan Filter</button>
        </div>
    </aside>

    <main class="main-content">
        <div class="browse-toolbar">
            <input type="text" name="q" placeholder="Cari jasa, contoh: desain, editing, coding..." value="<?= clean($q) ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
        </div>

        <div class="browse-meta">
            <span><?= count($services) ?> jasa ditemukan</span>
            <label>Urutkan:
                <select name="urut" id="urutSelect">
                    <?php
                    $urutOptions = [
                        'populer'  => 'Terpopuler',
                        'terbaru'  => 'Terbaru',
                        'rating'   => 'Rating Tertinggi',
                        'termurah' => 'Harga Terendah',
                        'termahal' => 'Harga Tertinggi',
                    ];
                    foreach ($urutOptions as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $urut === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <?php if (empty($services)): ?>
            <div class="empty-state">
                Belum ada jasa yang cocok dengan pencarian kamu. Coba ubah kata kunci atau reset filter.
            </div>
        <?php else: ?>
            <div class="service-grid browse-grid">
                <?php foreach ($services as $s): ?>
                    <a class="service-card" href="<?= BASE_URL ?>/pages/service_detail.php?id=<?= (int)$s['id'] ?>">
                        <div class="service-thumb" style="background: linear-gradient(135deg, var(--navy), var(--navy-light));">
                            <?= serviceThumbHtml($s['thumbnail'], $s['kategori_slug']) ?>
                        </div>
                        <div class="service-info">
                            <span class="tipe-badge"><?= clean($s['tipe_jasa']) ?></span>
                            <p class="service-title"><?= clean($s['judul']) ?></p>
                            <p class="service-provider"><?= clean($s['nama_lengkap']) ?></p>
                            <div class="service-rating"><?= renderRatingText($s['avg_rating'], $s['review_count']) ?></div>
                            <div class="service-price"><?= formatRupiah($s['harga']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</div>
</form>

<script>
// Kategori, rating, tipe, dan urutan langsung diterapkan saat dipilih
document.querySelectorAll('#filterForm input[type="radio"], #urutSelect').forEach(function (el) {
    el.addEventListener('change', function () {
        document.getElementById('filterForm').submit();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
