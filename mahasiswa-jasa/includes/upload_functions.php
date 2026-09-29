<?php
/**
 * Upload gambar yang aman: cek isi file (bukan cuma ekstensi), batasi ukuran,
 * dan simpan dengan nama acak.
 */

/**
 * Simpan satu gambar. Return nama file baru, atau null kalau tidak ada file / gagal.
 * Kalau gagal, pesan error diisi di $error.
 */
function saveUploadedImage(array $file, $destDir, &$error = null) {
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Ukuran gambar terlalu besar (maksimal 2 MB).';
        return null;
    }
    if ($code !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Upload gambar gagal, coba lagi.';
        return null;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        $error = 'Ukuran gambar terlalu besar (maksimal 2 MB).';
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($allowed[$info[2]])) {
        $error = 'Format gambar harus JPG, PNG, atau WEBP.';
        return null;
    }

    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
        $error = 'Folder upload tidak bisa dibuat.';
        return null;
    }

    $name = bin2hex(random_bytes(10)) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], rtrim($destDir, '/\\') . '/' . $name)) {
        $error = 'Gagal menyimpan gambar.';
        return null;
    }
    return $name;
}

/**
 * Ubah struktur $_FILES['x'] (multiple) jadi list file satu-satu.
 */
function normalizeFilesArray(array $files) {
    $out = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $out;
    }
    foreach ($files['name'] as $i => $name) {
        $out[] = [
            'name'     => $name,
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
    }
    return $out;
}

/** Hapus file upload (aman dari path traversal lewat basename) */
function deleteUploadedFile($dir, $filename) {
    if (!$filename) {
        return;
    }
    $path = rtrim($dir, '/\\') . '/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}
