<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/review_functions.php';
require_once __DIR__ . '/../includes/service_functions.php';
require_once __DIR__ . '/../includes/order_functions.php';

$viewId = (int) ($_GET['id'] ?? 0);
$isOwn = !$viewId || (isLoggedIn() && $viewId === currentUserId());

if ($isOwn) {
    requireLogin();
    $viewId = currentUserId();
}

$profile = getUserById($viewId);
if (!$profile) {
    http_response_code(404);
    setFlash('danger', 'Profil tidak ditemukan.');
    redirect('pages/browse.php');
}

$skills = getUserSkills($viewId);
$stat = getProviderStats($viewId);

$pageTitle = $isOwn ? 'Profil Saya' : $profile['nama_lengkap'];
if ($isOwn) { $activeNav = 'profile'; }
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">

<?php if ($isOwn): ?><div class="layout"><?php require __DIR__ . '/../includes/dash_sidebar.php'; ?><main class="main-content">
<?php else: ?><div class="container" style="padding:28px 24px 48px;">
<?php endif; ?>

    <h1 class="page-title"><?= $isOwn ? 'Profil Saya' : 'Profil Penyedia' ?></h1>
    <p class="page-sub"><?= $isOwn ? 'Informasi akun yang dilihat pembeli.' : '' ?></p>

    <div class="profile-grid">
        <div>
            <div class="card">
                <div class="profile-head">
                    <div class="profile-photo"><?= avatarHtml($profile['foto_profil'], $profile['nama_lengkap']) ?></div>
                    <div>
                        <h2><?= clean($profile['nama_lengkap']) ?></h2>
                        <div style="color:var(--text-muted);font-size:14px;"><?= $profile['universitas'] ? 'Mahasiswa ' . clean($profile['universitas']) : 'Mahasiswa' ?></div>
                        <div style="font-size:14px;margin-top:6px;"><?= renderRatingText($stat['avg_rating'], $stat['review_count']) ?><?= $stat['review_count'] > 0 ? ' ulasan' : '' ?></div>
                        <div class="profile-actions">
                            <?php if ($isOwn): ?>
                                <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/pages/profile_edit.php#foto">Ubah Foto</a>
                                <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/pages/profile_edit.php">Edit Profil</a>
                            <?php elseif (isLoggedIn()): ?>
                                <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/pages/chat.php?user=<?= $viewId ?>">💬 Chat</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title">Skill</h3>
                <?php if ($skills): ?>
                    <div class="skill-tags">
                        <?php foreach ($skills as $sk): ?><span class="skill-tag"><?= clean($sk) ?></span><?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted-note" style="margin:0;font-size:14px;color:var(--text-muted);"><?= $isOwn ? 'Kamu belum menambahkan skill. Tambahkan lewat Edit Profil.' : 'Belum ada skill yang ditambahkan.' ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Informasi <?= $isOwn ? 'Pribadi' : 'Penyedia' ?></h3>
            <table class="info-table">
                <tr><td>Nama Lengkap</td><td><?= clean($profile['nama_lengkap']) ?></td></tr>
                <?php if ($isOwn): ?>
                    <tr><td>Email</td><td><?= clean($profile['email']) ?></td></tr>
                    <tr><td>Nomor HP</td><td><?= $profile['no_hp'] ? clean($profile['no_hp']) : '-' ?></td></tr>
                <?php endif; ?>
                <tr><td>Universitas</td><td><?= $profile['universitas'] ? clean($profile['universitas']) : '-' ?></td></tr>
                <tr><td>Prodi</td><td><?= $profile['prodi'] ? clean($profile['prodi']) : '-' ?></td></tr>
                <tr><td>Bergabung</td><td><?= formatTanggalIndo($profile['created_at'], false) ?></td></tr>
                <tr><td>Deskripsi</td><td><?= $profile['deskripsi'] ? nl2br(clean($profile['deskripsi'])) : '-' ?></td></tr>
            </table>
        </div>
    </div>

    <?php if (!$isOwn):
        $svcs = array_filter(getProviderServices($viewId), function ($s) { return $s['status'] === 'aktif'; });
        $reviews = getProviderReviews($viewId, 5);
    ?>
        <h2 class="section-title">Jasa dari <?= clean(explode(' ', $profile['nama_lengkap'])[0]) ?></h2>
        <?php if (!$svcs): ?>
            <div class="empty-state">Belum ada jasa aktif.</div>
        <?php else: ?>
            <div class="service-grid">
                <?php foreach ($svcs as $s): ?>
                    <a class="service-card" href="<?= BASE_URL ?>/pages/service_detail.php?id=<?= (int) $s['id'] ?>">
                        <div class="service-thumb" style="background:linear-gradient(135deg,var(--navy),var(--navy-light));overflow:hidden;"><?= serviceThumbHtml($s['thumbnail'], $s['kategori_slug']) ?></div>
                        <div class="service-info">
                            <p class="service-title"><?= clean($s['judul']) ?></p>
                            <div class="service-rating"><?= renderRatingText($s['avg_rating'], $s['review_count']) ?></div>
                            <div class="service-price"><?= formatRupiah($s['harga']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Ulasan Terbaru</h2>
        <div class="card">
            <?php if (!$reviews): ?><div class="empty-box">Belum ada ulasan.</div><?php endif; ?>
            <?php foreach ($reviews as $r): ?>
                <div class="review-card">
                    <div class="review-avatar"><?= avatarHtml($r['foto_profil'], $r['nama_lengkap']) ?></div>
                    <div class="review-body">
                        <div class="review-head"><strong><?= clean($r['nama_lengkap']) ?></strong><span class="when"><?= formatTanggalIndo($r['created_at']) ?></span></div>
                        <div class="review-service"><?= clean($r['judul']) ?> · <span class="stars"><?= starsText($r['rating']) ?></span></div>
                        <?php if ($r['komentar']): ?><p class="review-comment"><?= clean($r['komentar']) ?></p><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php if ($isOwn): ?></main></div><?php else: ?></div><?php endif; ?>

<style>
    .skill-tags { display:flex; flex-wrap:wrap; gap:8px; }
    .skill-tag { background:#EFF6FF; color:var(--blue); font-size:13px; padding:6px 12px; border-radius:8px; }
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
