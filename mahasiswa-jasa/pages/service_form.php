<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/order_functions.php';
require_once __DIR__ . '/../includes/upload_functions.php';
require_once __DIR__ . '/../includes/service_functions.php';

requireLogin();
$uid = currentUserId();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$existing = null;
if ($id > 0) {
    $existing = getOwnedService($id, $uid);
    if (!$existing) {
        setFlash('danger', 'Jasa tidak ditemukan.');
        redirect('pages/my_services.php');
    }
}
$isEdit = $existing !== null;
$categories = getAllCategories();
$portfolio = $isEdit ? getServicePortfolio($id) : [];
$errors = [];

// nilai awal form
$form = $existing ?: ['judul' => '', 'kategori_id' => '', 'deskripsi' => '', 'harga' => '', 'estimasi_pengerjaan' => '',
                      'jumlah_revisi' => 2, 'format_file' => '', 'tipe_jasa' => 'online', 'status' => 'aktif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    [$data, $errors] = validateServiceInput($_POST);
    $form = array_merge($form, $data);

    // upload gambar (thumbnail + portofolio)
    $newThumb = null;
    $newPortfolio = [];
    if (!$errors) {
        $err = null;
        $newThumb = saveUploadedImage($_FILES['thumbnail'] ?? ['error' => UPLOAD_ERR_NO_FILE], UPLOAD_PORTOFOLIO, $err);
        if ($err) { $errors[] = 'Thumbnail: ' . $err; }

        $remaining = MAX_PORTFOLIO - count($portfolio) + count($_POST['hapus_gambar'] ?? []);
        foreach (normalizeFilesArray($_FILES['portofolio'] ?? []) as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) { continue; }
            if (count($newPortfolio) >= $remaining) {
                $errors[] = 'Maksimal ' . MAX_PORTFOLIO . ' gambar portofolio per jasa.';
                break;
            }
            $err = null;
            $name = saveUploadedImage($file, UPLOAD_PORTOFOLIO, $err);
            if ($err) { $errors[] = 'Portofolio: ' . $err; }
            elseif ($name) { $newPortfolio[] = $name; }
        }
    }

    if (!$errors) {
        if ($isEdit) {
            $thumb = $existing['thumbnail'];
            if ($newThumb) {
                deleteUploadedFile(UPLOAD_PORTOFOLIO, $existing['thumbnail']);
                $thumb = $newThumb;
            }
            updateService($id, $uid, $data, $thumb);
            deletePortfolioImages($id, (array) ($_POST['hapus_gambar'] ?? []));
            $jasaId = $id;
        } else {
            $jasaId = insertService($uid, $data, $newThumb);
        }
        foreach ($newPortfolio as $name) {
            addPortfolioImage($jasaId, $name);
        }
        setFlash('success', $isEdit ? 'Jasa berhasil diperbarui.' : 'Jasa berhasil ditambahkan.');
        redirect('pages/my_services.php');
    }

    // gagal: buang file yang sempat terupload supaya tidak jadi sampah
    deleteUploadedFile(UPLOAD_PORTOFOLIO, $newThumb);
    foreach ($newPortfolio as $n) { deleteUploadedFile(UPLOAD_PORTOFOLIO, $n); }
}

$activeNav = 'services';
$pageTitle = $isEdit ? 'Edit Jasa' : 'Tambah Jasa';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
    <?php require __DIR__ . '/../includes/dash_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title"><?= $isEdit ? 'Edit Jasa' : 'Tambah Jasa' ?></h1>
        <p class="page-sub">Isi detail jasa yang kamu tawarkan agar mudah ditemukan pembeli.</p>

        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="card form-card">
            <?= csrfField() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

            <div class="form-group">
                <label class="form-label">Judul Jasa</label>
                <input class="form-control" type="text" name="judul" maxlength="150" required placeholder="Contoh: Desain Poster" value="<?= clean($form['judul']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select class="form-control" name="kategori_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $form['kategori_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= clean($c['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Harga (Rp)</label>
                    <input class="form-control" type="number" name="harga" min="1000" step="500" required placeholder="25000" value="<?= clean($form['harga']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea class="form-control" name="deskripsi" rows="5" required placeholder="Jelaskan apa yang kamu tawarkan, apa saja yang termasuk, dan cara kerjamu."><?= clean($form['deskripsi']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Estimasi Pengerjaan</label>
                    <input class="form-control" type="text" name="estimasi_pengerjaan" maxlength="50" placeholder="Contoh: 1-2 hari" value="<?= clean($form['estimasi_pengerjaan']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Revisi</label>
                    <input class="form-control" type="number" name="jumlah_revisi" min="0" max="50" value="<?= (int) $form['jumlah_revisi'] ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Format File Hasil</label>
                    <input class="form-control" type="text" name="format_file" maxlength="100" placeholder="Contoh: JPG/PNG/PDF" value="<?= clean($form['format_file']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Jasa</label>
                    <select class="form-control" name="tipe_jasa">
                        <option value="online" <?= $form['tipe_jasa'] === 'online' ? 'selected' : '' ?>>Online</option>
                        <option value="offline" <?= $form['tipe_jasa'] === 'offline' ? 'selected' : '' ?>>Offline</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Gambar Utama (thumbnail)</label>
                <?php if ($isEdit && $existing['thumbnail']): ?>
                    <div class="current-images"><div class="current-image"><img src="<?= uploadUrl($existing['thumbnail']) ?>" alt="">Saat ini</div></div>
                <?php endif; ?>
                <input class="form-control" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp">
                <div class="form-hint">JPG, PNG, atau WEBP. Maksimal 2 MB.<?= $isEdit ? ' Kosongkan jika tidak ingin mengganti.' : '' ?></div>
            </div>

            <div class="form-group">
                <label class="form-label">Portofolio (maksimal <?= MAX_PORTFOLIO ?> gambar)</label>
                <?php if ($portfolio): ?>
                    <div class="current-images">
                        <?php foreach ($portfolio as $p): ?>
                            <div class="current-image">
                                <img src="<?= uploadUrl($p['gambar']) ?>" alt="">
                                <label class="check-row" style="justify-content:center;font-size:12px;"><input type="checkbox" name="hapus_gambar[]" value="<?= (int) $p['id'] ?>"> Hapus</label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <input class="form-control" type="file" name="portofolio[]" multiple accept="image/jpeg,image/png,image/webp">
                <div class="form-hint">Bisa pilih beberapa gambar sekaligus.</div>
            </div>

            <div class="form-group">
                <label class="check-row"><input type="checkbox" name="aktif" value="1" <?= $form['status'] === 'aktif' ? 'checked' : '' ?>> Tampilkan jasa ini di pencarian (aktif)</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Jasa' ?></button>
                <a class="btn btn-outline" href="<?= BASE_URL ?>/pages/my_services.php">Batal</a>
            </div>
        </form>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
