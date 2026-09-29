<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';

$pageTitle = 'Tentang Kami';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">

<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <h1>Skill mahasiswa, terpusat di satu tempat.</h1>
            <p>MahasiswaJasa mempertemukan mahasiswa yang punya keahlian (desain, coding, editing, fotografi, dan lainnya) dengan mahasiswa yang membutuhkannya, tanpa lagi mencari lewat chat berantai.</p>
        </div>
    </div>
</section>

<div class="container">
    <section class="section">
        <div class="section-header"><h2>Cara Kerja</h2></div>
        <div class="category-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="category-item"><div class="category-icon-circle">🔎</div><span class="label">1. Cari jasa</span><p style="font-size:13px;color:var(--text-muted);margin:8px 0 0;">Telusuri berdasarkan kategori, bandingkan harga, portofolio, dan rating.</p></div>
            <div class="category-item"><div class="category-icon-circle">💬</div><span class="label">2. Chat & pesan</span><p style="font-size:13px;color:var(--text-muted);margin:8px 0 0;">Diskusikan kebutuhanmu dengan penyedia, lalu pesan dan bayar.</p></div>
            <div class="category-item"><div class="category-icon-circle">⭐</div><span class="label">3. Terima & beri ulasan</span><p style="font-size:13px;color:var(--text-muted);margin:8px 0 0;">Setelah selesai, beri ulasan agar mahasiswa lain lebih yakin.</p></div>
        </div>
    </section>

    <section class="section" style="text-align:center;">
        <h2 style="margin-top:0;">Punya skill? Mulai tawarkan jasamu.</h2>
        <a class="btn btn-primary" href="<?= isLoggedIn() ? BASE_URL . '/pages/service_form.php' : BASE_URL . '/pages/register.php' ?>"><?= isLoggedIn() ? 'Tambah Jasa' : 'Daftar Gratis' ?></a>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
