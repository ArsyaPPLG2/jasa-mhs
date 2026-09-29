<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload_functions.php';

requireLogin();
$uid = currentUserId();
$me = getCurrentUser();
$errors = [];
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $conn = getDBConnection();

    if (($_POST['form'] ?? '') === 'password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($old, $me['password'])) {
            $pwErrors[] = 'Password lama salah.';
        } elseif (strlen($new) < 6) {
            $pwErrors[] = 'Password baru minimal 6 karakter.';
        } elseif ($new !== ($_POST['confirm_password'] ?? '')) {
            $pwErrors[] = 'Konfirmasi password baru tidak cocok.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $uid);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Password berhasil diganti.');
            redirect('pages/profile_edit.php');
        }
    } else {
        $nama  = trim($_POST['nama_lengkap'] ?? '');
        $hp    = trim($_POST['no_hp'] ?? '');
        $univ  = trim($_POST['universitas'] ?? '');
        $prodi = trim($_POST['prodi'] ?? '');
        $desk  = trim($_POST['deskripsi'] ?? '');

        if ($nama === '' || mb_strlen($nama) > 150) { $errors[] = 'Nama wajib diisi (maksimal 150 karakter).'; }
        if ($hp !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $hp)) { $errors[] = 'Nomor HP tidak valid.'; }
        if (mb_strlen($univ) > 150 || mb_strlen($prodi) > 150) { $errors[] = 'Universitas atau prodi terlalu panjang.'; }
        if (mb_strlen($desk) > 1000) { $errors[] = 'Deskripsi maksimal 1000 karakter.'; }

        // skill: dipisah koma, maks 10, unik
        $skills = [];
        foreach (explode(',', $_POST['skills'] ?? '') as $sk) {
            $sk = trim($sk);
            if ($sk !== '' && mb_strlen($sk) <= 50 && !in_array(mb_strtolower($sk), array_map('mb_strtolower', $skills), true)) {
                $skills[] = $sk;
            }
        }
        $skills = array_slice($skills, 0, 10);

        $newPhoto = null;
        if (!$errors) {
            $err = null;
            $newPhoto = saveUploadedImage($_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE], UPLOAD_PROFIL, $err);
            if ($err) { $errors[] = 'Foto: ' . $err; }
        }

        if (!$errors) {
            $foto = $me['foto_profil'];
            if ($newPhoto) {
                deleteUploadedFile(UPLOAD_PROFIL, $me['foto_profil']);
                $foto = $newPhoto;
            }
            $stmt = $conn->prepare('UPDATE users SET nama_lengkap = ?, no_hp = ?, universitas = ?, prodi = ?, deskripsi = ?, foto_profil = ? WHERE id = ?');
            $stmt->bind_param('ssssssi', $nama, $hp, $univ, $prodi, $desk, $foto, $uid);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('DELETE FROM skills WHERE user_id = ?');
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $stmt->close();
            foreach ($skills as $sk) {
                $stmt = $conn->prepare('INSERT INTO skills (user_id, nama_skill) VALUES (?, ?)');
                $stmt->bind_param('is', $uid, $sk);
                $stmt->execute();
                $stmt->close();
            }
            setFlash('success', 'Profil berhasil diperbarui.');
            redirect('pages/profile.php');
        }
        // simpan input agar form tidak kosong lagi
        $me = array_merge($me, ['nama_lengkap' => $nama, 'no_hp' => $hp, 'universitas' => $univ, 'prodi' => $prodi, 'deskripsi' => $desk]);
        $skillsText = implode(', ', $skills);
    }
}
$skillsText = $skillsText ?? implode(', ', getUserSkills($uid));

$activeNav = 'profile';
$pageTitle = 'Edit Profil';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Edit Profil</h1>
        <p class="page-sub">Perbarui informasi yang tampil di profil kamu.</p>

        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="card form-card" id="foto">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label">Foto Profil</label>
                <div class="profile-head" style="margin-bottom:8px;">
                    <div class="profile-photo" style="width:72px;height:72px;font-size:28px;"><?= avatarHtml($me['foto_profil'], $me['nama_lengkap']) ?></div>
                    <input class="form-control" type="file" name="foto" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="form-hint">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input class="form-control" type="text" name="nama_lengkap" maxlength="150" required value="<?= clean($me['nama_lengkap']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" value="<?= clean($me['email']) ?>" disabled>
                <div class="form-hint">Email tidak bisa diubah.</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nomor HP</label>
                    <input class="form-control" type="text" name="no_hp" maxlength="20" placeholder="0812 3456 7890" value="<?= clean($me['no_hp']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Universitas</label>
                    <input class="form-control" type="text" name="universitas" maxlength="150" value="<?= clean($me['universitas']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Program Studi</label>
                <input class="form-control" type="text" name="prodi" maxlength="150" value="<?= clean($me['prodi']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Skill</label>
                <input class="form-control" type="text" name="skills" placeholder="Desain Grafis, Photoshop, Canva" value="<?= clean($skillsText) ?>">
                <div class="form-hint">Pisahkan dengan koma. Maksimal 10 skill.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea class="form-control" name="deskripsi" rows="4" maxlength="1000" placeholder="Ceritakan singkat tentang kamu dan keahlianmu."><?= clean($me['deskripsi']) ?></textarea>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Simpan Profil</button>
                <a class="btn btn-outline" href="<?= BASE_URL ?>/pages/profile.php">Batal</a>
            </div>
        </form>

        <h2 class="section-title">Ganti Password</h2>
        <?php foreach ($pwErrors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>
        <form method="POST" action="" class="card form-card">
            <?= csrfField() ?>
            <input type="hidden" name="form" value="password">
            <div class="form-group">
                <label class="form-label">Password Lama</label>
                <input class="form-control" type="password" name="old_password" required autocomplete="current-password">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input class="form-control" type="password" name="new_password" minlength="6" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input class="form-control" type="password" name="confirm_password" minlength="6" required autocomplete="new-password">
                </div>
            </div>
            <button class="btn btn-outline" type="submit">Ganti Password</button>
        </form>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
