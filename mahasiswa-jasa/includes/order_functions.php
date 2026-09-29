<?php
/**
 * Fungsi-fungsi pesanan. Harga SELALU dihitung ulang di server dari tabel jasa.
 *
 * Alur status:
 *   dibuat -> pembayaran: menunggu / pesanan: menunggu
 *   pembeli konfirmasi bayar -> pembayaran: dibayar
 *   penyedia terima -> diproses -> selesai   (atau tolak -> dibatalkan)
 *
 * CATATAN: konfirmasi pembayaran di sini masih manual/simulasi (pembeli klik "Saya sudah bayar").
 * Untuk produksi, sambungkan ke payment gateway (Midtrans/Xendit) atau verifikasi admin.
 */

const PAYMENT_METHODS = [
    'transfer_bank' => 'Transfer Bank',
    'e_wallet'      => 'E-Wallet (OVO, GoPay, DANA)',
    'qris'          => 'QRIS',
];

function generateOrderCode() {
    return 'MJ' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Buat pesanan baru. Return kode pesanan, atau string error dalam array ['error' => '...'].
 */
function createOrder($jasaId, $buyerId, $jumlah, $catatan, $metode) {
    $conn = getDBConnection();

    if (!isset(PAYMENT_METHODS[$metode])) {
        return ['error' => 'Metode pembayaran tidak valid.'];
    }
    $jumlah = (int) $jumlah;
    if ($jumlah < 1 || $jumlah > 20) {
        return ['error' => 'Jumlah pesanan harus antara 1 sampai 20.'];
    }
    $catatan = mb_substr(trim($catatan), 0, 1000);

    $stmt = $conn->prepare('SELECT id, user_id, harga, status FROM jasa WHERE id = ?');
    $stmt->bind_param('i', $jasaId);
    $stmt->execute();
    $jasa = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$jasa || $jasa['status'] !== 'aktif') {
        return ['error' => 'Jasa tidak tersedia.'];
    }
    if ((int) $jasa['user_id'] === (int) $buyerId) {
        return ['error' => 'Kamu tidak bisa memesan jasa milik sendiri.'];
    }

    $providerId = (int) $jasa['user_id'];
    $total = (float) $jasa['harga'] * $jumlah;

    for ($try = 0; $try < 3; $try++) {
        $kode = generateOrderCode();
        $stmt = $conn->prepare(
            'INSERT INTO pesanan (kode_pesanan, jasa_id, buyer_id, provider_id, jumlah, catatan, total_harga, metode_pembayaran)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('siiiisds', $kode, $jasaId, $buyerId, $providerId, $jumlah, $catatan, $total, $metode);
        try {
            $ok = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            $ok = false; // kode bentrok -> coba kode lain
        }
        $stmt->close();
        if ($ok) {
            return $kode;
        }
    }
    return ['error' => 'Gagal membuat pesanan, coba lagi.'];
}

/** Ambil pesanan lengkap berdasarkan kode. */
function getOrderByCode($kode) {
    $conn = getDBConnection();
    $sql = "SELECT p.*, j.judul, j.thumbnail, k.slug AS kategori_slug,
                   b.nama_lengkap AS buyer_nama, pr.nama_lengkap AS provider_nama
            FROM pesanan p
            JOIN jasa j ON j.id = p.jasa_id
            JOIN kategori k ON k.id = j.kategori_id
            JOIN users b ON b.id = p.buyer_id
            JOIN users pr ON pr.id = p.provider_id
            WHERE p.kode_pesanan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Pesanan masuk untuk penyedia (hanya yang sudah dibayar). $status kosong = semua. */
function getProviderOrders($providerId, $status = '', $limit = 0) {
    $conn = getDBConnection();
    $sql = "SELECT p.*, j.judul, j.thumbnail, k.slug AS kategori_slug, b.nama_lengkap AS buyer_nama
            FROM pesanan p
            JOIN jasa j ON j.id = p.jasa_id
            JOIN kategori k ON k.id = j.kategori_id
            JOIN users b ON b.id = p.buyer_id
            WHERE p.provider_id = ? AND p.status_pembayaran = 'dibayar'";
    $types = 'i';
    $params = [$providerId];
    if ($status !== '') {
        $sql .= ' AND p.status_pesanan = ?';
        $types .= 's';
        $params[] = $status;
    }
    $sql .= ' ORDER BY p.created_at DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Pesanan yang dibuat oleh pembeli, termasuk status ulasan. */
function getBuyerOrders($buyerId) {
    $conn = getDBConnection();
    $sql = "SELECT p.*, j.judul, j.thumbnail, k.slug AS kategori_slug,
                   pr.nama_lengkap AS provider_nama, rv.id AS review_id
            FROM pesanan p
            JOIN jasa j ON j.id = p.jasa_id
            JOIN kategori k ON k.id = j.kategori_id
            JOIN users pr ON pr.id = p.provider_id
            LEFT JOIN review rv ON rv.pesanan_id = p.id
            WHERE p.buyer_id = ?
            ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $buyerId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Pembeli konfirmasi sudah bayar. */
function markOrderPaid($orderId, $buyerId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE pesanan SET status_pembayaran = 'dibayar'
                            WHERE id = ? AND buyer_id = ? AND status_pembayaran = 'menunggu' AND status_pesanan = 'menunggu'");
    $stmt->bind_param('ii', $orderId, $buyerId);
    $stmt->execute();
    $ok = $stmt->affected_rows === 1;
    $stmt->close();
    return $ok;
}

/** Pembeli membatalkan pesanan yang belum dibayar. */
function buyerCancelOrder($orderId, $buyerId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = 'dibatalkan'
                            WHERE id = ? AND buyer_id = ? AND status_pesanan = 'menunggu' AND status_pembayaran = 'menunggu'");
    $stmt->bind_param('ii', $orderId, $buyerId);
    $stmt->execute();
    $ok = $stmt->affected_rows === 1;
    $stmt->close();
    return $ok;
}

/** Penyedia mengubah status: terima | selesai | tolak. Hanya untuk pesanan yang sudah dibayar. */
function providerUpdateOrder($orderId, $providerId, $action) {
    $map = [
        'terima'  => ['menunggu', 'diproses'],
        'selesai' => ['diproses', 'selesai'],
        'tolak'   => ['menunggu', 'dibatalkan'],
    ];
    if (!isset($map[$action])) {
        return false;
    }
    [$from, $to] = $map[$action];
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = ?
                            WHERE id = ? AND provider_id = ? AND status_pesanan = ? AND status_pembayaran = 'dibayar'");
    $stmt->bind_param('siis', $to, $orderId, $providerId, $from);
    $stmt->execute();
    $ok = $stmt->affected_rows === 1;
    $stmt->close();
    return $ok;
}

/** Statistik untuk dashboard & halaman pendapatan penyedia. */
function getProviderStats2($providerId) {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) AS total_orders,
                   COALESCE(SUM(created_at >= NOW() - INTERVAL 7 DAY), 0) AS week_orders,
                   COALESCE(SUM(CASE WHEN status_pesanan = 'selesai' THEN total_harga END), 0) AS revenue_total,
                   COALESCE(SUM(CASE WHEN status_pesanan = 'selesai'
                                      AND updated_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN total_harga END), 0) AS revenue_month,
                   COALESCE(SUM(CASE WHEN status_pesanan = 'selesai'
                                      AND updated_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01')
                                      AND updated_at <  DATE_FORMAT(NOW(), '%Y-%m-01') THEN total_harga END), 0) AS revenue_prev,
                   COALESCE(SUM(CASE WHEN status_pesanan IN ('menunggu','diproses') THEN total_harga END), 0) AS pending_total,
                   COALESCE(SUM(status_pesanan = 'selesai'), 0) AS done_orders
            FROM pesanan
            WHERE provider_id = ? AND status_pembayaran = 'dibayar' AND status_pesanan <> 'dibatalkan'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

/** Label + kelas badge untuk status pesanan. */
function orderStatusBadge($status) {
    $map = [
        'menunggu'   => ['badge-warning', 'Menunggu'],
        'diproses'   => ['badge-info',    'Diproses'],
        'selesai'    => ['badge-success', 'Selesai'],
        'dibatalkan' => ['badge-danger',  'Dibatalkan'],
    ];
    [$class, $label] = $map[$status] ?? ['badge-muted', ucfirst($status)];
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}
