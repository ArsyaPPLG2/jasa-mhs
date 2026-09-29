<?php
/**
 * Panel visual interaktif untuk halaman login/register (pengganti navbar).
 * Set $authHeadline & $authSubtext sebelum include file ini (opsional).
 */
?>
<div class="auth-visual icon-float-zone" id="authVisual">
    <a href="<?= BASE_URL ?>/pages/home.php" class="auth-logo">🎓 MahasiswaJasa</a>

    <div class="auth-visual-text">
        <h2><?= isset($authHeadline) ? clean($authHeadline) : 'Skill kamu bisa jadi peluang' ?></h2>
        <p><?= isset($authSubtext) ? clean($authSubtext) : 'Gabung dan mulai tawarkan atau temukan jasa dari sesama mahasiswa.' ?></p>
    </div>

    <div class="float-wrap" style="left:8%; top:16%; animation-delay:0s;">
        <span class="float-icon" data-depth="18" title="Desain">🎨</span>
    </div>
    <div class="float-wrap" style="left:80%; top:14%; animation-delay:.6s;">
        <span class="float-icon" data-depth="26" title="Editing Video">🎥</span>
    </div>
    <div class="float-wrap" style="left:6%; top:66%; animation-delay:1.2s;">
        <span class="float-icon" data-depth="20" title="Pemrograman">💻</span>
    </div>
    <div class="float-wrap extra" style="left:72%; top:70%; animation-delay:.3s;">
        <span class="float-icon" data-depth="16" title="Penulisan">✍️</span>
    </div>
    <div class="float-wrap extra" style="left:45%; top:82%; animation-delay:.9s;">
        <span class="float-icon" data-depth="30" title="Fotografi">📷</span>
    </div>
    <div class="float-wrap extra" style="left:88%; top:42%; animation-delay:1.5s;">
        <span class="float-icon" data-depth="14" title="Penerjemahan">🌐</span>
    </div>

    <a href="<?= BASE_URL ?>/pages/home.php" class="auth-back">← Kembali ke beranda</a>
</div>
