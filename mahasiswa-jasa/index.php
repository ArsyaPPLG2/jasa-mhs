<?php
/**
 * Pintu masuk utama situs.
 * Cukup akses domain/folder project ini (tanpa perlu ketik /pages/home.php),
 * dan halaman yang tampil adalah Homepage.
 *
 * File ini hanya "memanggil" pages/home.php apa adanya -- semua logic,
 * query, dan tampilan tetap ada di sana, jadi tidak ada kode yang dobel.
 */
require __DIR__ . '/pages/home.php';
