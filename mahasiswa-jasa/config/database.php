<?php
/**
 * Koneksi Database - MahasiswaJasa
 * Ganti kredensial di bawah sesuai environment kamu (XAMPP/Laragon/hosting).
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mahasiswa_jasa');

function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die('Koneksi database gagal: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
        $conn->query("SET time_zone = '+07:00'"); // samakan dengan Asia/Jakarta di PHP
    }

    return $conn;
}
