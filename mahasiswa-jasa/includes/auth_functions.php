<?php
/**
 * Fungsi-fungsi terkait autentikasi user
 */

/**
 * Daftarkan user baru.
 * Return: ['success' => bool, 'message' => string]
 */
function registerUser($nama, $email, $password, $universitas = null, $prodi = null) {
    $conn = getDBConnection();

    // Cek email sudah terdaftar atau belum
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Email sudah terdaftar. Silakan login.'];
    }
    $stmt->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        'INSERT INTO users (nama_lengkap, email, password, universitas, prodi) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssss', $nama, $email, $hashedPassword, $universitas, $prodi);

    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Pendaftaran berhasil! Silakan login.'];
    }

    $stmt->close();
    return ['success' => false, 'message' => 'Terjadi kesalahan, coba lagi.'];
}

/**
 * Login user. Kalau berhasil, set session dan return true.
 */
function loginUser($email, $password) {
    $conn = getDBConnection();

    $stmt = $conn->prepare('SELECT id, nama_lengkap, email, password, foto_profil, status FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['success' => false, 'message' => 'Email tidak ditemukan.'];
    }

    if ($user['status'] !== 'aktif') {
        return ['success' => false, 'message' => 'Akun kamu tidak aktif. Hubungi admin.'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Password salah.'];
    }

    // Set session (ganti ID session dulu untuk cegah session fixation)
    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['email']        = $user['email'];
    $_SESSION['foto_profil']  = $user['foto_profil'];

    return ['success' => true, 'message' => 'Login berhasil.'];
}

/**
 * Logout user - hapus semua session
 */
function logoutUser() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Ambil data user yang sedang login (full row dari tabel users)
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}
