<?php
/** Fungsi ulasan & rating. */

/** Ulasan yang diterima seorang penyedia (semua jasanya). */
function getProviderReviews($providerId, $limit = 0) {
    $conn = getDBConnection();
    $sql = "SELECT r.rating, r.komentar, r.created_at, u.nama_lengkap, u.foto_profil, j.judul
            FROM review r
            JOIN users u ON u.id = r.reviewer_id
            JOIN jasa j ON j.id = r.jasa_id
            WHERE r.provider_id = ?
            ORDER BY r.created_at DESC" . ($limit > 0 ? ' LIMIT ' . (int) $limit : '');
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Jumlah ulasan per bintang: [5 => n, 4 => n, ... 1 => n] */
function getRatingDistribution($providerId) {
    $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT rating, COUNT(*) AS c FROM review WHERE provider_id = ? GROUP BY rating');
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $dist[(int) $row['rating']] = (int) $row['c'];
    }
    $stmt->close();
    return $dist;
}

/** Pesanan yang boleh diulas oleh pembeli ini (selesai & belum diulas). */
function getReviewableOrder($orderId, $buyerId) {
    $conn = getDBConnection();
    $sql = "SELECT p.*, j.judul, pr.nama_lengkap AS provider_nama, rv.id AS review_id
            FROM pesanan p
            JOIN jasa j ON j.id = p.jasa_id
            JOIN users pr ON pr.id = p.provider_id
            LEFT JOIN review rv ON rv.pesanan_id = p.id
            WHERE p.id = ? AND p.buyer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $orderId, $buyerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Simpan ulasan. Return true, atau string pesan error. */
function createReview($orderId, $buyerId, $rating, $komentar) {
    $order = getReviewableOrder($orderId, $buyerId);
    if (!$order) {
        return 'Pesanan tidak ditemukan.';
    }
    if ($order['status_pesanan'] !== 'selesai') {
        return 'Ulasan hanya bisa diberikan setelah pesanan selesai.';
    }
    if ($order['review_id']) {
        return 'Kamu sudah memberi ulasan untuk pesanan ini.';
    }
    $rating = (int) $rating;
    if ($rating < 1 || $rating > 5) {
        return 'Pilih rating 1 sampai 5 bintang.';
    }
    $komentar = mb_substr(trim($komentar), 0, 1000);

    $conn = getDBConnection();
    $stmt = $conn->prepare('INSERT INTO review (pesanan_id, jasa_id, reviewer_id, provider_id, rating, komentar) VALUES (?, ?, ?, ?, ?, ?)');
    $jasaId = (int) $order['jasa_id'];
    $providerId = (int) $order['provider_id'];
    $stmt->bind_param('iiiiis', $orderId, $jasaId, $buyerId, $providerId, $rating, $komentar);
    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        $stmt->close();
        return 'Gagal menyimpan ulasan (mungkin sudah pernah diulas).';
    }
    $stmt->close();
    return true;
}
