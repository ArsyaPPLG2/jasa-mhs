-- =====================================================
-- Database: mahasiswa_jasa
-- Marketplace jasa antar mahasiswa
-- =====================================================

CREATE DATABASE IF NOT EXISTS mahasiswa_jasa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mahasiswa_jasa;

-- ---------------------------------------------------
-- Tabel: users
-- Menyimpan data mahasiswa (bisa jadi provider & buyer sekaligus)
-- ---------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,           -- hasil password_hash()
    no_hp VARCHAR(20) DEFAULT NULL,
    universitas VARCHAR(150) DEFAULT NULL,
    prodi VARCHAR(150) DEFAULT NULL,
    deskripsi TEXT DEFAULT NULL,
    foto_profil VARCHAR(255) DEFAULT NULL,
    is_provider TINYINT(1) DEFAULT 0,          -- 1 = pernah pasang jasa
    status VARCHAR(20) DEFAULT 'aktif',        -- aktif / nonaktif / banned
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: skills
-- Skill milik user (Desain Grafis, Photoshop, dll)
-- ---------------------------------------------------
CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_skill VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: kategori
-- ---------------------------------------------------
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,             -- nama icon (lucide/fontawesome)
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO kategori (nama_kategori, icon, slug) VALUES
('Desain', 'palette', 'desain'),
('Editing Video', 'video', 'editing-video'),
('Pemrograman', 'code', 'pemrograman'),
('Penulisan', 'pen-tool', 'penulisan'),
('Fotografi', 'camera', 'fotografi'),
('Penerjemahan', 'languages', 'penerjemahan'),
('Lainnya', 'plus', 'lainnya');

-- ---------------------------------------------------
-- Tabel: jasa
-- Jasa yang ditawarkan provider
-- ---------------------------------------------------
CREATE TABLE jasa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                      -- penyedia jasa
    kategori_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    estimasi_pengerjaan VARCHAR(50) DEFAULT NULL,   -- "1-2 hari"
    jumlah_revisi INT DEFAULT 0,
    format_file VARCHAR(100) DEFAULT NULL,          -- "JPG/PNG/PDF"
    tipe_jasa ENUM('online','offline') DEFAULT 'online',
    thumbnail VARCHAR(255) DEFAULT NULL,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: jasa_portofolio
-- Galeri contoh hasil kerja per jasa
-- ---------------------------------------------------
CREATE TABLE jasa_portofolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jasa_id INT NOT NULL,
    gambar VARCHAR(255) NOT NULL,
    FOREIGN KEY (jasa_id) REFERENCES jasa(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: pesanan
-- ---------------------------------------------------
CREATE TABLE pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pesanan VARCHAR(30) NOT NULL UNIQUE,
    jasa_id INT NOT NULL,
    buyer_id INT NOT NULL,                     -- yang memesan
    provider_id INT NOT NULL,                  -- yang punya jasa
    jumlah INT DEFAULT 1,
    catatan TEXT DEFAULT NULL,
    total_harga DECIMAL(12,2) NOT NULL,
    metode_pembayaran ENUM('transfer_bank','e_wallet','qris') DEFAULT 'transfer_bank',
    status_pembayaran ENUM('menunggu','dibayar','gagal') DEFAULT 'menunggu',
    status_pesanan ENUM('menunggu','diproses','selesai','dibatalkan') DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jasa_id) REFERENCES jasa(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (provider_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: review
-- ---------------------------------------------------
CREATE TABLE review (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT NOT NULL UNIQUE,            -- 1 pesanan = 1 review
    jasa_id INT NOT NULL,
    reviewer_id INT NOT NULL,                  -- buyer yang menulis ulasan
    provider_id INT NOT NULL,                  -- provider yang direview
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    komentar TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (jasa_id) REFERENCES jasa(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    FOREIGN KEY (provider_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: chat_room
-- 1 room = percakapan antara 2 user (opsional terkait 1 jasa)
-- ---------------------------------------------------
CREATE TABLE chat_room (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_1 INT NOT NULL,
    user_2 INT NOT NULL,
    jasa_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_room (user_1, user_2, jasa_id),
    FOREIGN KEY (user_1) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_2) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (jasa_id) REFERENCES jasa(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: chat_message
-- ---------------------------------------------------
CREATE TABLE chat_message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    sender_id INT NOT NULL,
    pesan TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_room(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Index tambahan untuk performa query umum
-- ---------------------------------------------------
CREATE INDEX idx_jasa_kategori ON jasa(kategori_id);
CREATE INDEX idx_jasa_user ON jasa(user_id);
CREATE INDEX idx_pesanan_buyer ON pesanan(buyer_id);
CREATE INDEX idx_pesanan_provider ON pesanan(provider_id);
CREATE INDEX idx_chat_message_room ON chat_message(room_id);
