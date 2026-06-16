-- ============================================================
-- DATABASE: Sistem Informasi Laundry
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_laundry;

-- Tabel: users (admin login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel: pelanggan
CREATE TABLE IF NOT EXISTS pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel: membership (kuota per pelanggan)
CREATE TABLE IF NOT EXISTS membership (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id INT NOT NULL,
    kuota_awal DECIMAL(10,2) NOT NULL DEFAULT 30,
    kuota_terpakai DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);

-- Tabel: transaksi
CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(20) NOT NULL UNIQUE,
    pelanggan_id INT NOT NULL,
    berat DECIMAL(10,2) NOT NULL,
    jenis_layanan ENUM('ambil_sendiri','antar','jemput_antar') NOT NULL,
    harga_per_kg DECIMAL(10,2) NOT NULL DEFAULT 7000,
    total_harga DECIMAL(10,2) NOT NULL,
    status ENUM('menunggu_dijemput','sudah_dijemput','diproses','selesai','sedang_diantar','selesai_diantar') NOT NULL DEFAULT 'diproses',
    alamat_pengiriman TEXT,
    catatan TEXT,
    gunakan_membership TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE RESTRICT
);

-- ============================================================
-- DATA AWAL (Seed)
-- ============================================================

-- Admin default: username=admin, password=admin123
INSERT INTO users (nama, username, password) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Pelanggan contoh
INSERT INTO pelanggan (nama, no_hp, alamat) VALUES
('Budi Santoso', '081234567890', 'Jl. Merdeka No. 12, Medan'),
('Siti Rahma', '082345678901', 'Jl. Pahlawan No. 5, Medan Kota'),
('Ahmad Fauzi', '083456789012', 'Jl. Sudirman No. 88, Medan Baru');

-- Membership untuk pelanggan
INSERT INTO membership (pelanggan_id, kuota_awal, kuota_terpakai) VALUES
(1, 30, 5),
(2, 20, 0),
(3, 50, 12);

-- Transaksi contoh
INSERT INTO transaksi (kode_transaksi, pelanggan_id, berat, jenis_layanan, harga_per_kg, total_harga, status, alamat_pengiriman) VALUES
('TRX-20250001', 1, 3.5, 'antar', 7000, 24500, 'selesai_diantar', 'Jl. Merdeka No. 12, Medan'),
('TRX-20250002', 2, 2.0, 'ambil_sendiri', 7000, 14000, 'selesai', NULL),
('TRX-20250003', 3, 5.0, 'jemput_antar', 7000, 35000, 'diproses', 'Jl. Sudirman No. 88, Medan Baru'),
('TRX-20250004', 1, 1.5, 'ambil_sendiri', 7000, 10500, 'menunggu_dijemput', NULL);
