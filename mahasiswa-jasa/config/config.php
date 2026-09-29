<?php
/**
 * Konfigurasi global aplikasi MahasiswaJasa
 */

// Mulai session di satu tempat saja
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

// Base URL - sesuaikan dengan folder project di server kamu
define('BASE_URL', getenv('MJ_BASE_URL') ?: 'http://localhost/mahasiswa-jasa');

// Path upload
define('UPLOAD_PROFIL', __DIR__ . '/../uploads/profile/');
define('UPLOAD_PORTOFOLIO', __DIR__ . '/../uploads/portfolio/');
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB per gambar

// Info pembayaran yang ditampilkan ke pembeli. GANTI dengan data asli kamu.
define('PAY_BANK_INFO', 'BCA 1234567890 a.n. MahasiswaJasa');
define('PAY_EWALLET_INFO', 'OVO / GoPay / DANA: 0812-0000-0000 a.n. MahasiswaJasa');

// Tampilkan error saat development. MATIKAN (set 0) saat production.
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';

/** Redirect ke halaman lain lalu stop eksekusi */
function redirect($path) {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

/** Escape output untuk ditampilkan di HTML */
function clean($data) {
    return htmlspecialchars(trim((string) $data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/** Wajib login, kalau belum -> ke halaman login */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('pages/login.php');
    }
}

function currentUserId() {
    return isLoggedIn() ? (int) $_SESSION['user_id'] : 0;
}

/** Format angka ke Rupiah, contoh "Rp 25.000" */
function formatRupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/* ---------------- CSRF ---------------- */
function csrfToken() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField() {
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

/** Panggil di awal setiap handler POST */
function verifyCsrf() {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        die('Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
}

/* ---------------- Flash message ---------------- */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function popFlash() {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
