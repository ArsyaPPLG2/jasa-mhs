<?php
/**
 * CRUD jasa milik penyedia. Semua fungsi memeriksa kepemilikan (user_id).
 */

const MAX_PORTFOLIO = 8;

function getProviderServices($userId) {
    $conn = getDBConnection();
    $sql = "SELECT j.*, k.slug AS kategori_slug, k.nama_kategori,
                   COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count
            FROM jasa j
            JOIN kategori k ON k.id = j.kategori_id
            LEFT JOIN review r ON r.jasa_id = j.id
            WHERE j.user_id = ?
            GROUP BY j.id, k.slug, k.nama_kategori
            ORDER BY j.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function getOwnedService($id, $userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT * FROM jasa WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Validasi input form jasa. Return [data, errors].
 */
function validateServiceInput(array $in) {
    $errors = [];
    $data = [
        'judul'               => trim($in['judul'] ?? ''),
        'kategori_id'         => (int) ($in['kategori_id'] ?? 0),
        'deskripsi'           => trim($in['deskripsi'] ?? ''),
        'harga'               => (int) ($in['harga'] ?? 0),
        'estimasi_pengerjaan' => trim($in['estimasi_pengerjaan'] ?? ''),
        'jumlah_revisi'       => (int) ($in['jumlah_revisi'] ?? 0),
        'format_file'         => trim($in['format_file'] ?? ''),
        'tipe_jasa'           => ($in['tipe_jasa'] ?? '') === 'offline' ? 'offline' : 'online',
        'status'              => !empty($in['aktif']) ? 'aktif' : 'nonaktif',
    ];

    if ($data['judul'] === '' || mb_strlen($data['judul']) > 150) {
        $errors[] = 'Judul wajib diisi (maksimal 150 karakter).';
    }
    if ($data['deskripsi'] === '') {
        $errors[] = 'Deskripsi wajib diisi.';
    }
    if ($data['harga'] < 1000 || $data['harga'] > 100000000) {
        $errors[] = 'Harga minimal Rp 1.000 dan maksimal Rp 100.000.000.';
    }
    if ($data['jumlah_revisi'] < 0 || $data['jumlah_revisi'] > 50) {
        $errors[] = 'Jumlah revisi harus antara 0 sampai 50.';
    }
    if (mb_strlen($data['estimasi_pengerjaan']) > 50 || mb_strlen($data['format_file']) > 100) {
        $errors[] = 'Estimasi pengerjaan atau format file terlalu panjang.';
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT id FROM kategori WHERE id = ?');
    $stmt->bind_param('i', $data['kategori_id']);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $errors[] = 'Pilih kategori yang valid.';
    }
    $stmt->close();

    return [$data, $errors];
}

function insertService($userId, array $d, $thumbnail) {
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'INSERT INTO jasa (user_id, kategori_id, judul, deskripsi, harga, estimasi_pengerjaan, jumlah_revisi, format_file, tipe_jasa, thumbnail, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iissisissss', $userId, $d['kategori_id'], $d['judul'], $d['deskripsi'], $d['harga'],
        $d['estimasi_pengerjaan'], $d['jumlah_revisi'], $d['format_file'], $d['tipe_jasa'], $thumbnail, $d['status']);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    // tandai user sebagai penyedia
    $conn->query('UPDATE users SET is_provider = 1 WHERE id = ' . (int) $userId);
    return $id;
}

function updateService($id, $userId, array $d, $thumbnail) {
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'UPDATE jasa SET kategori_id = ?, judul = ?, deskripsi = ?, harga = ?, estimasi_pengerjaan = ?, jumlah_revisi = ?,
                format_file = ?, tipe_jasa = ?, thumbnail = ?, status = ?
         WHERE id = ? AND user_id = ?'
    );
    $stmt->bind_param('issisissssii', $d['kategori_id'], $d['judul'], $d['deskripsi'], $d['harga'],
        $d['estimasi_pengerjaan'], $d['jumlah_revisi'], $d['format_file'], $d['tipe_jasa'], $thumbnail, $d['status'], $id, $userId);
    $stmt->execute();
    $stmt->close();
}

function toggleServiceStatus($id, $userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE jasa SET status = IF(status = 'aktif', 'nonaktif', 'aktif') WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $ok = $stmt->affected_rows === 1;
    $stmt->close();
    return $ok;
}

/** Return 'ok' | 'not_found' | 'has_orders' */
function deleteService($id, $userId) {
    $svc = getOwnedService($id, $userId);
    if (!$svc) {
        return 'not_found';
    }
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM pesanan WHERE jasa_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    if ($count > 0) {
        return 'has_orders';
    }

    foreach (getServicePortfolio($id) as $p) {
        deleteUploadedFile(UPLOAD_PORTOFOLIO, $p['gambar']);
    }
    deleteUploadedFile(UPLOAD_PORTOFOLIO, $svc['thumbnail']);

    $stmt = $conn->prepare('DELETE FROM jasa WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $stmt->close();
    return 'ok';
}

function addPortfolioImage($jasaId, $filename) {
    $conn = getDBConnection();
    $stmt = $conn->prepare('INSERT INTO jasa_portofolio (jasa_id, gambar) VALUES (?, ?)');
    $stmt->bind_param('is', $jasaId, $filename);
    $stmt->execute();
    $stmt->close();
}

/** Hapus gambar portofolio terpilih (hanya milik jasa tsb). */
function deletePortfolioImages($jasaId, array $ids) {
    $conn = getDBConnection();
    foreach ($ids as $pid) {
        $pid = (int) $pid;
        $stmt = $conn->prepare('SELECT gambar FROM jasa_portofolio WHERE id = ? AND jasa_id = ?');
        $stmt->bind_param('ii', $pid, $jasaId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            deleteUploadedFile(UPLOAD_PORTOFOLIO, $row['gambar']);
            $stmt = $conn->prepare('DELETE FROM jasa_portofolio WHERE id = ? AND jasa_id = ?');
            $stmt->bind_param('ii', $pid, $jasaId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
