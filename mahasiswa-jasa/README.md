# MahasiswaJasa

Marketplace jasa antar mahasiswa (desain, editing video, coding, dll).
Stack: HTML/CSS/JavaScript + PHP + MySQL.

## Fitur (semua sudah jadi)
- [x] Database & struktur project
- [x] Auth: register, login, logout (password di-hash, session aman)
- [x] Homepage (hero, kategori, jasa populer)
- [x] Browse Service (search, filter kategori/harga/rating/tipe, urutan)
- [x] Service Detail (galeri, portofolio, ulasan, profil penyedia)
- [x] Checkout 3 langkah (detail -> pembayaran -> konfirmasi)
- [x] Pesanan Saya (pembeli) & Pesanan Masuk (penyedia: terima / tolak / selesai)
- [x] Provider Dashboard (statistik, pesanan terbaru, jasa saya)
- [x] Jasa Saya (tambah, edit, hapus, aktif/nonaktif, upload thumbnail & portofolio)
- [x] Pendapatan
- [x] Profil (lihat, edit, ubah foto, skill, ganti password, profil publik penyedia)
- [x] Chat antar user (auto-refresh tiap 3 detik)
- [x] Rating & Review (tulis ulasan setelah pesanan selesai, statistik bintang)

## Cara Setup (XAMPP / Laragon)

1. Taruh folder `mahasiswa-jasa` di `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin -> tab **Import** -> pilih `sql/schema.sql` -> Import.
   (Kalau sudah pernah import versi sebelumnya, TIDAK perlu import ulang: struktur tabel tidak berubah.)
3. (Opsional) Untuk data contoh, import juga `sql/seed.sql`.
   **Hanya ke database yang masih kosong** (baru dari schema.sql). Kalau database sudah berisi akun,
   hapus database `mahasiswa_jasa` dulu lalu import schema.sql + seed.sql.
4. Cek `config/database.php` (user/password MySQL) dan `config/config.php` (`BASE_URL`, mis. `http://localhost/mahasiswa-jasa`).
5. Jalankan Apache & MySQL, buka `http://localhost/mahasiswa-jasa/pages/home.php`.

Kebutuhan PHP: versi 8.0+ dengan ekstensi `mysqli` dan `mbstring` (keduanya aktif default di XAMPP/Laragon).

### Akun contoh (kalau import seed.sql) — password semuanya `demo1234`
| Email | Peran |
|---|---|
| rizky@demo.com | Penyedia desain (punya pesanan & ulasan) |
| dinda@demo.com | Penyedia editing video & terjemahan |
| aulia@demo.com | Penyedia coding |
| salsa@demo.com | Penyedia PPT & fotografi |
| farhan@demo.com | Pembeli |

## Struktur Folder
```
mahasiswa-jasa/
├── config/       koneksi DB & konfigurasi global (CSRF, flash, BASE_URL)
├── includes/     header/footer, sidebar, dan fungsi (auth, order, chat, review, upload, ...)
├── assets/       css, js
├── pages/        semua halaman
├── sql/          schema.sql (wajib), seed.sql (opsional)
└── uploads/      foto profil & portofolio (dilindungi .htaccess: script tidak bisa dieksekusi)
```

## PENTING sebelum dipakai sungguhan
- **Pembayaran masih simulasi.** Pembeli menekan "Saya Sudah Bayar" dan sistem langsung percaya.
  Untuk produksi, sambungkan payment gateway (Midtrans/Xendit) atau tambahkan verifikasi admin.
  Ganti info rekening/e-wallet di `config/config.php` (`PAY_BANK_INFO`, `PAY_EWALLET_INFO`) dan pasang gambar QRIS asli.
- Refund saat penyedia menolak pesanan yang sudah dibayar masih manual (di luar sistem).
- Matikan tampilan error di `config/config.php` (`display_errors` = 0) saat online.
- Belum ada halaman admin, fitur favorit, dan indikator online di chat.

## Keamanan yang sudah diterapkan
- Password: `password_hash` / `password_verify`; session di-regenerate saat login.
- Semua query memakai prepared statement; output di-escape (`clean()`); chat dirender dengan `textContent`.
- Semua form/aksi POST dilindungi token CSRF.
- Harga pesanan dihitung ulang di server, bukan dari input browser.
- Kepemilikan dicek di setiap aksi (pesanan, jasa, room chat), jadi user tidak bisa mengubah data orang lain.
- Upload gambar: isi file diperiksa (bukan cuma ekstensi), maks 2 MB, hanya JPG/PNG/WEBP, nama file diacak.
