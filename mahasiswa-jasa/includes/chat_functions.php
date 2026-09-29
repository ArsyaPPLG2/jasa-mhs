<?php
/** Fungsi chat. 1 room = 2 user (+ opsional 1 jasa sebagai konteks). Semua fungsi cek keanggotaan room. */

/**
 * Ambil room antara dua user (buat jika belum ada). Return id room, atau null kalau tidak valid.
 */
function getOrCreateRoom($me, $other, $jasaId = 0) {
    $me = (int) $me; $other = (int) $other; $jasaId = (int) $jasaId;
    if ($other <= 0 || $other === $me || !getUserById($other)) {
        return null;
    }
    $conn = getDBConnection();

    // konteks jasa hanya dipakai jika jasa itu memang milik salah satu dari kedua user
    $jasa = null;
    if ($jasaId > 0) {
        $stmt = $conn->prepare('SELECT id FROM jasa WHERE id = ? AND user_id IN (?, ?)');
        $stmt->bind_param('iii', $jasaId, $me, $other);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $jasa = $row ? (int) $row['id'] : null;
    }

    $a = min($me, $other);
    $b = max($me, $other);

    $stmt = $conn->prepare('SELECT id FROM chat_room WHERE user_1 = ? AND user_2 = ? AND jasa_id <=> ?');
    $stmt->bind_param('iii', $a, $b, $jasa);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        return (int) $row['id'];
    }

    $stmt = $conn->prepare('INSERT INTO chat_room (user_1, user_2, jasa_id) VALUES (?, ?, ?)');
    $stmt->bind_param('iii', $a, $b, $jasa);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return (int) $id;
}

/** Daftar room milik user, terurut dari aktivitas terbaru. */
function getUserRooms($me) {
    $conn = getDBConnection();
    $sql = "SELECT cr.id, cr.jasa_id, cr.created_at, j.judul,
                   u.id AS other_id, u.nama_lengkap AS other_nama, u.foto_profil AS other_foto,
                   (SELECT pesan FROM chat_message WHERE room_id = cr.id ORDER BY id DESC LIMIT 1) AS last_msg,
                   (SELECT created_at FROM chat_message WHERE room_id = cr.id ORDER BY id DESC LIMIT 1) AS last_at,
                   (SELECT COUNT(*) FROM chat_message WHERE room_id = cr.id AND sender_id <> ? AND is_read = 0) AS unread
            FROM chat_room cr
            JOIN users u ON u.id = IF(cr.user_1 = ?, cr.user_2, cr.user_1)
            LEFT JOIN jasa j ON j.id = cr.jasa_id
            WHERE cr.user_1 = ? OR cr.user_2 = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiii', $me, $me, $me, $me);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // hanya tampilkan room yang sudah ada pesan, kecuali room baru (agar bisa mulai ngobrol)
    usort($rows, function ($x, $y) {
        return strcmp($y['last_at'] ?? $y['created_at'], $x['last_at'] ?? $x['created_at']);
    });
    return $rows;
}

/** Room + info lawan bicara, hanya jika $me anggota room. */
function getRoomForUser($roomId, $me) {
    foreach (getUserRooms($me) as $r) {
        if ((int) $r['id'] === (int) $roomId) {
            return $r;
        }
    }
    return null;
}

function getRoomMessages($roomId, $afterId = 0, $limit = 200) {
    $conn = getDBConnection();
    $limit = (int) $limit;
    $stmt = $conn->prepare("SELECT id, sender_id, pesan, created_at FROM chat_message WHERE room_id = ? AND id > ? ORDER BY id ASC LIMIT $limit");
    $stmt->bind_param('ii', $roomId, $afterId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function markRoomRead($roomId, $me) {
    $conn = getDBConnection();
    $stmt = $conn->prepare('UPDATE chat_message SET is_read = 1 WHERE room_id = ? AND sender_id <> ? AND is_read = 0');
    $stmt->bind_param('ii', $roomId, $me);
    $stmt->execute();
    $stmt->close();
}

/** Kirim pesan. Return array pesan baru, atau null kalau tidak valid. */
function sendChatMessage($roomId, $me, $text) {
    $text = trim($text);
    if ($text === '' || mb_strlen($text) > 2000) {
        return null;
    }
    $conn = getDBConnection();
    $stmt = $conn->prepare('INSERT INTO chat_message (room_id, sender_id, pesan) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $roomId, $me, $text);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    $rows = getRoomMessages($roomId, $id - 1, 1);
    return $rows[0] ?? null;
}

/** Bentuk pesan untuk dikirim ke browser sebagai JSON. */
function chatMessageToJson(array $m, $me) {
    return [
        'id'   => (int) $m['id'],
        'mine' => (int) $m['sender_id'] === (int) $me,
        'text' => $m['pesan'],
        'time' => date('H:i', strtotime($m['created_at'])),
    ];
}

/** "2 menit lalu", "1 jam lalu", atau tanggal. */
function timeAgoIndo($datetime) {
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 86400 * 7) return floor($diff / 86400) . ' hari lalu';
    return formatTanggalIndo($datetime);
}
