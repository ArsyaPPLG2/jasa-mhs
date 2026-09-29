<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/review_functions.php';

requireLogin();
$uid = currentUserId();
$stat = getProviderStats($uid);
$dist = getRatingDistribution($uid);
$reviews = getProviderReviews($uid);
$total = (int) $stat['review_count'];

$activeNav = 'reviews';
$pageTitle = 'Ulasan & Rating';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Ulasan & Rating</h1>
        <p class="page-sub">Penilaian dari pembeli untuk jasa kamu.</p>

        <div class="rating-summary">
            <div class="card rating-big">
                <div class="num"><span>★</span> <?= $total ? number_format((float) $stat['avg_rating'], 1) : '-' ?></div>
                <div style="color:var(--text-muted);font-size:13px;">dari <?= $total ?> ulasan</div>
            </div>
            <div class="card">
                <?php foreach ($dist as $star => $count): $pct = $total ? round($count / $total * 100) : 0; ?>
                    <div class="dist-row">
                        <span><?= $star ?> <span class="stars">★</span></span>
                        <div class="dist-bar"><span style="width:<?= $pct ?>%"></span></div>
                        <span style="text-align:right;"><?= $pct ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h2 class="section-title">Ulasan Terbaru</h2>
        <div class="card">
            <?php if (!$reviews): ?><div class="empty-box">Belum ada ulasan. Selesaikan pesanan pertamamu!</div><?php endif; ?>
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
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
