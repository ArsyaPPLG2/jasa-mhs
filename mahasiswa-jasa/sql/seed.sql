-- =====================================================
-- seed.sql : DATA CONTOH (opsional) - untuk melihat semua halaman langsung terisi.
-- Import HANYA ke database yang baru dibuat dari schema.sql (kosong).
-- Semua akun contoh memakai password: demo1234
-- =====================================================
USE mahasiswa_jasa;

INSERT INTO users (id, nama_lengkap, email, password, no_hp, universitas, prodi, deskripsi, is_provider) VALUES
(1, "Rizky Andhika",   "rizky@demo.com",  "$2y$10$tnPM/oFou2GLC.OqdCcE0.8tlUZT4DhX5Ou6m0lcF0MFxhRkelBOS", "0821 2345 6789", "UNSIKA", "Informatika", "Mahasiswa informatika yang memiliki minat di bidang desain grafis. Siap membantu kebutuhan desain kamu dengan hasil yang kreatif dan berkualitas.", 1),
(2, "Dinda Lestari",   "dinda@demo.com",  "$2y$10$tnPM/oFou2GLC.OqdCcE0.8tlUZT4DhX5Ou6m0lcF0MFxhRkelBOS", "0813 1111 2222", "UNSIKA", "Sistem Informasi", "Suka bikin konten dan video pendek.", 1),
(3, "Aulia Rahma",     "aulia@demo.com",  "$2y$10$tnPM/oFou2GLC.OqdCcE0.8tlUZT4DhX5Ou6m0lcF0MFxhRkelBOS", "0812 3333 4444", "Universitas Indonesia", "Ilmu Komputer", "Web developer freelance sejak semester 3.", 1),
(4, "Salsabila Putri", "salsa@demo.com",  "$2y$10$tnPM/oFou2GLC.OqdCcE0.8tlUZT4DhX5Ou6m0lcF0MFxhRkelBOS", "0857 5555 6666", "UNSIKA", "Manajemen", "Desain presentasi dan fotografi produk.", 1),
(5, "Farhan Maulana",  "farhan@demo.com", "$2y$10$tnPM/oFou2GLC.OqdCcE0.8tlUZT4DhX5Ou6m0lcF0MFxhRkelBOS", "0896 7777 8888", "UNSIKA", "Teknik Industri", NULL, 0);

INSERT INTO skills (user_id, nama_skill) VALUES
(1,"Desain Grafis"),(1,"Photoshop"),(1,"Illustrator"),(1,"Canva"),
(2,"Video Editing"),(2,"Premiere Pro"),(2,"CapCut"),
(3,"PHP"),(3,"JavaScript"),(3,"MySQL"),
(4,"PowerPoint"),(4,"Fotografi");

INSERT INTO jasa (id, user_id, kategori_id, judul, deskripsi, harga, estimasi_pengerjaan, jumlah_revisi, format_file, tipe_jasa, status) VALUES
(1, 1, 1, "Desain Poster", "Saya menyediakan jasa desain poster untuk berbagai kebutuhan seperti promosi makanan, event, produk, dan lainnya. Desain dibuat dengan kreatif, menarik, dan sesuai dengan permintaan.", 25000, "1-2 hari", 2, "JPG/PNG/PDF", "online", "aktif"),
(2, 1, 1, "Desain Logo", "Logo sederhana dan modern untuk brand, komunitas, atau UMKM kamu.", 40000, "2-3 hari", 3, "PNG/SVG/PDF", "online", "aktif"),
(3, 2, 2, "Edit Video TikTok", "Edit video pendek untuk TikTok/Reels: potong, subtitle, transisi, dan musik.", 30000, "1 hari", 2, "MP4", "online", "aktif"),
(4, 3, 3, "Jasa Coding Web", "Pembuatan website sederhana (landing page, company profile) dengan HTML, CSS, JavaScript, dan PHP.", 75000, "3-5 hari", 2, "Source code", "online", "aktif"),
(5, 3, 3, "Jasa Coding Bot", "Bot Telegram/Discord sesuai kebutuhan kamu.", 80000, "3-5 hari", 1, "Source code", "online", "aktif"),
(6, 4, 1, "Desain PPT", "Slide presentasi yang rapi dan menarik untuk tugas kuliah, seminar, atau pitching.", 20000, "1-2 hari", 2, "PPTX", "online", "aktif"),
(7, 4, 5, "Fotografi Produk", "Foto produk UMKM dengan pencahayaan yang baik. Area Karawang dan sekitarnya.", 50000, "1 hari", 1, "JPG", "offline", "aktif"),
(8, 2, 6, "Terjemahan Inggris-Indo", "Terjemahan dokumen umum dan abstrak jurnal.", 35000, "1-2 hari", 1, "DOCX", "online", "aktif");

INSERT INTO pesanan (id, kode_pesanan, jasa_id, buyer_id, provider_id, jumlah, catatan, total_harga, metode_pembayaran, status_pembayaran, status_pesanan, created_at, updated_at) VALUES
(1, "MJSEED0001", 1, 5, 1, 1, "Poster promosi makanan, ukuran A4", 25000, "qris", "dibayar", "selesai", NOW() - INTERVAL 40 DAY, NOW() - INTERVAL 38 DAY),
(2, "MJSEED0002", 1, 4, 1, 1, NULL, 25000, "transfer_bank", "dibayar", "selesai", NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 8 DAY),
(3, "MJSEED0003", 2, 5, 1, 1, "Logo untuk kedai kopi", 40000, "e_wallet", "dibayar", "selesai", NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 3 DAY),
(4, "MJSEED0004", 3, 1, 2, 1, "Video 30 detik", 30000, "qris", "dibayar", "diproses", NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(5, "MJSEED0005", 1, 2, 1, 1, "Warna dominan biru", 25000, "qris", "dibayar", "menunggu", NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY),
(6, "MJSEED0006", 4, 5, 3, 1, "Landing page organisasi", 75000, "transfer_bank", "dibayar", "selesai", NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 15 DAY),
(7, "MJSEED0007", 1, 3, 1, 1, NULL, 25000, "qris", "menunggu", "menunggu", NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 1 HOUR);

INSERT INTO review (pesanan_id, jasa_id, reviewer_id, provider_id, rating, komentar, created_at) VALUES
(1, 1, 5, 1, 5, "Hasilnya bagus banget, sesuai request. Revisi juga cepat. Recommended!", NOW() - INTERVAL 37 DAY),
(2, 1, 4, 1, 5, "Cepat dan rapi, makasih ya!", NOW() - INTERVAL 7 DAY),
(3, 2, 5, 1, 4, "Logonya bagus, prosesnya sedikit lebih lama dari estimasi.", NOW() - INTERVAL 2 DAY),
(6, 4, 5, 3, 5, "Pengerjaan cepat dan hasilnya memuaskan. Makasih!", NOW() - INTERVAL 14 DAY);

INSERT INTO chat_room (id, user_1, user_2, jasa_id) VALUES (1, 1, 5, 1);
INSERT INTO chat_message (room_id, sender_id, pesan, is_read, created_at) VALUES
(1, 5, "Halo, saya tertarik dengan jasa desain poster kamu. Apakah masih tersedia?", 1, NOW() - INTERVAL 30 MINUTE),
(1, 1, "Halo! Masih tersedia kak. Mau desain seperti apa ya?", 1, NOW() - INTERVAL 28 MINUTE),
(1, 5, "Saya butuh poster untuk promosi makanan, ukurannya A4. Bisa revisi 2x ya?", 1, NOW() - INTERVAL 25 MINUTE),
(1, 1, "Bisa kak, nanti saya buatkan contoh dulu ya sebelum final.", 0, NOW() - INTERVAL 2 MINUTE);
