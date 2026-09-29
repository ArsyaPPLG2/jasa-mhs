<?php
/**
 * Fungsi-fungsi bantu umum yang dipakai di berbagai halaman.
 */

/**
 * Emoji ikon untuk tiap kategori jasa, berdasarkan slug di tabel kategori.
 */
function categoryIcon($slug) {
    $icons = [
        'desain'         => '🎨',
        'editing-video'  => '🎥',
        'pemrograman'    => '💻',
        'penulisan'      => '✍️',
        'fotografi'      => '📷',
        'penerjemahan'   => '🌐',
        'lainnya'        => '✨',
    ];
    return $icons[$slug] ?? '📦';
}

/**
 * Ambil semua kategori dari database.
 */
function getAllCategories() {
    $conn = getDBConnection();
    $result = $conn->query('SELECT * FROM kategori ORDER BY id ASC');
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ambil jasa populer (diurutkan dari jumlah review terbanyak, lalu rating tertinggi).
 * $limit: jumlah maksimal jasa yang diambil.
 */
function getPopularServices($limit = 4) {
    $conn = getDBConnection();
    $limit = (int) $limit;

    $sql = "SELECT j.id, j.judul, j.harga, j.thumbnail, u.nama_lengkap, k.slug AS kategori_slug,
                   COALESCE(AVG(r.rating), 0) AS avg_rating,
                   COUNT(r.id) AS review_count
            FROM jasa j
            JOIN users u ON j.user_id = u.id
            JOIN kategori k ON j.kategori_id = k.id
            LEFT JOIN review r ON r.jasa_id = j.id
            WHERE j.status = 'aktif'
            GROUP BY j.id, u.nama_lengkap, k.slug
            ORDER BY review_count DESC, avg_rating DESC, j.created_at DESC
            LIMIT $limit";

    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Cari & filter jasa untuk halaman Browse.
 * $f (semua opsional): q, kategori (slug), harga_min, harga_max, rating, tipe (online/offline), urut
 */
function searchServices(array $f = []) {
    $conn = getDBConnection();

    $sql = "SELECT j.id, j.judul, j.harga, j.thumbnail, j.tipe_jasa, u.nama_lengkap, k.slug AS kategori_slug,
                   COALESCE(AVG(r.rating), 0) AS avg_rating,
                   COUNT(r.id) AS review_count
            FROM jasa j
            JOIN users u ON j.user_id = u.id
            JOIN kategori k ON j.kategori_id = k.id
            LEFT JOIN review r ON r.jasa_id = j.id
            WHERE j.status = 'aktif'";

    $params = [];
    $types  = '';

    if (!empty($f['q'])) {
        $sql .= ' AND (j.judul LIKE ? OR j.deskripsi LIKE ? OR u.nama_lengkap LIKE ?)';
        $like = '%' . $f['q'] . '%';
        array_push($params, $like, $like, $like);
        $types .= 'sss';
    }
    if (!empty($f['kategori'])) {
        $sql .= ' AND k.slug = ?';
        $params[] = $f['kategori'];
        $types .= 's';
    }
    if (!empty($f['tipe']) && in_array($f['tipe'], ['online', 'offline'], true)) {
        $sql .= ' AND j.tipe_jasa = ?';
        $params[] = $f['tipe'];
        $types .= 's';
    }
    if (isset($f['harga_min']) && $f['harga_min'] !== null) {
        $sql .= ' AND j.harga >= ?';
        $params[] = (float) $f['harga_min'];
        $types .= 'd';
    }
    if (isset($f['harga_max']) && $f['harga_max'] !== null) {
        $sql .= ' AND j.harga <= ?';
        $params[] = (float) $f['harga_max'];
        $types .= 'd';
    }

    $sql .= ' GROUP BY j.id, u.nama_lengkap, k.slug';

    if (!empty($f['rating']) && (float) $f['rating'] > 0) {
        $sql .= ' HAVING avg_rating >= ?';
        $params[] = (float) $f['rating'];
        $types .= 'd';
    }

    // whitelist urutan supaya aman dari SQL injection
    $orderMap = [
        'populer'   => 'review_count DESC, avg_rating DESC, j.created_at DESC',
        'terbaru'   => 'j.created_at DESC',
        'termurah'  => 'j.harga ASC',
        'termahal'  => 'j.harga DESC',
        'rating'    => 'avg_rating DESC, review_count DESC',
    ];
    $urut = $f['urut'] ?? 'populer';
    $sql .= ' ORDER BY ' . ($orderMap[$urut] ?? $orderMap['populer']);

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * Render potongan HTML rating bintang + jumlah ulasan.
 * Contoh output: ★ 4.9 (120)
 */
function renderRatingText($avgRating, $reviewCount) {
    if ($reviewCount == 0) {
        return '<span class="stars">☆ Belum ada ulasan</span>';
    }
    return '<span class="stars">★ ' . number_format((float)$avgRating, 1) . '</span> ('
        . (int)$reviewCount . ')';
}

/**
 * Nama bulan Indonesia -> dipakai untuk format tanggal.
 */
function formatTanggalIndo($datetime, $withDay = true) {
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($datetime);
    $out = $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withDay ? date('j', $ts) . ' ' . $out : $out;
}

/**
 * URL file upload (portfolio / profile). Return null kalau file kosong.
 */
function uploadUrl($filename, $folder = 'portfolio') {
    if (!$filename) {
        return null;
    }
    return BASE_URL . '/uploads/' . $folder . '/' . rawurlencode($filename);
}

/**
 * Detail satu jasa + info penyedia + rating. Return null kalau tidak ada.
 */
function getServiceDetail($id) {
    $conn = getDBConnection();
    $sql = "SELECT j.*, k.nama_kategori, k.slug AS kategori_slug,
                   u.nama_lengkap, u.universitas, u.prodi, u.foto_profil, u.created_at AS provider_joined,
                   COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count
            FROM jasa j
            JOIN kategori k ON j.kategori_id = k.id
            JOIN users u ON j.user_id = u.id
            LEFT JOIN review r ON r.jasa_id = j.id
            WHERE j.id = ?
            GROUP BY j.id, k.nama_kategori, k.slug, u.nama_lengkap, u.universitas, u.prodi, u.foto_profil, u.created_at";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Gambar portofolio milik satu jasa.
 */
function getServicePortfolio($jasaId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT id, gambar FROM jasa_portofolio WHERE jasa_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $jasaId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Rating rata-rata & jumlah ulasan untuk SEMUA jasa milik satu penyedia.
 */
function getProviderStats($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS review_count FROM review WHERE provider_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Daftar skill milik user.
 */
function getUserSkills($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT nama_skill FROM skills WHERE user_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return array_column($rows, 'nama_skill');
}

/**
 * Ulasan terbaru untuk satu jasa.
 */
function getServiceReviews($jasaId, $limit = 5) {
    $conn = getDBConnection();
    $limit = (int) $limit;
    $stmt = $conn->prepare("SELECT r.rating, r.komentar, r.created_at, u.nama_lengkap
                            FROM review r JOIN users u ON r.reviewer_id = u.id
                            WHERE r.jasa_id = ? ORDER BY r.created_at DESC LIMIT $limit");
    $stmt->bind_param('i', $jasaId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Ambil user berdasarkan id (tanpa password). */
function getUserById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT id, nama_lengkap, email, no_hp, universitas, prodi, deskripsi, foto_profil, created_at FROM users WHERE id = ? AND status = \'aktif\'');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Gambar thumbnail jasa (atau emoji kategori sebagai cadangan), untuk dipakai di daftar. */
function serviceThumbHtml($thumbnail, $kategoriSlug) {
    if ($thumbnail) {
        return '<img src="' . uploadUrl($thumbnail, 'portfolio') . '" alt="">';
    }
    return categoryIcon($kategoriSlug);
}

/** Render bintang teks, contoh ★★★★☆ */
function starsText($rating) {
    $r = max(0, min(5, (int) round($rating)));
    return str_repeat('★', $r) . str_repeat('☆', 5 - $r);
}

/** Avatar bulat dari foto atau inisial (untuk daftar ulasan). */
function avatarHtml($fotoProfil, $nama) {
    if ($fotoProfil) {
        return '<img src="' . uploadUrl($fotoProfil, 'profile') . '" alt="">';
    }
    return clean(strtoupper(mb_substr($nama, 0, 1)));
}
