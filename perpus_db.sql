-- =====================================================
-- DATABASE: perpus_db
-- Aplikasi Peminjaman Buku - Full Schema
-- Sesuai Alur: Admin | Petugas | Anggota
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `perpus_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `perpus_db`;

-- -------------------------------------------------------
-- Tabel: pengguna (Admin & Petugas)
-- -------------------------------------------------------
CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL AUTO_INCREMENT,
  `username`    varchar(50)  NOT NULL,
  `password`    varchar(255) NOT NULL,
  `nama_pengguna` varchar(100) NOT NULL,
  `email`       varchar(100) DEFAULT NULL,
  `level`       enum('admin','petugas') NOT NULL DEFAULT 'petugas',
  `foto`        varchar(255) DEFAULT NULL,
  `created_at`  datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabel: anggota
-- -------------------------------------------------------
CREATE TABLE `anggota` (
  `id_anggota`    int(11) NOT NULL AUTO_INCREMENT,
  `nis`           varchar(20)  NOT NULL,
  `nama_anggota`  varchar(100) NOT NULL,
  `username`      varchar(50)  NOT NULL,
  `password`      varchar(255) NOT NULL,
  `email`         varchar(100) DEFAULT NULL,
  `kelas`         varchar(20)  NOT NULL,
  `alamat`        text DEFAULT NULL,
  `no_telepon`    varchar(20)  DEFAULT NULL,
  `foto`          varchar(255) DEFAULT NULL,
  `status`        enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at`    datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_anggota`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `nis` (`nis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabel: kategori
-- -------------------------------------------------------
CREATE TABLE `kategori` (
  `id_kategori`   int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi`     text DEFAULT NULL,
  `created_at`    datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabel: buku
-- -------------------------------------------------------
CREATE TABLE `buku` (
  `id_buku`       int(11) NOT NULL AUTO_INCREMENT,
  `id_kategori`   int(11) DEFAULT NULL,
  `judul_buku`    varchar(200) NOT NULL,
  `pengarang`     varchar(100) NOT NULL,
  `penerbit`      varchar(100) NOT NULL,
  `tahun_terbit`  year(4) NOT NULL,
  `isbn`          varchar(30)  DEFAULT NULL,
  `deskripsi`     text DEFAULT NULL,
  `stok`          int(11) NOT NULL DEFAULT 1,
  `status`        enum('tersedia','tidak') NOT NULL DEFAULT 'tersedia',
  `cover`         varchar(255) DEFAULT NULL,
  `created_at`    datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_buku`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `buku_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabel: transaksi (peminjaman & pengembalian)
-- -------------------------------------------------------
CREATE TABLE `transaksi` (
  `id_transaksi`      int(11) NOT NULL AUTO_INCREMENT,
  `id_anggota`        int(11) NOT NULL,
  `id_buku`           int(11) NOT NULL,
  `id_petugas`        int(11) DEFAULT NULL,
  `tgl_pinjam`        datetime NOT NULL,
  `tgl_kembali_rencana` datetime NOT NULL,
  `tgl_kembali_aktual`  datetime DEFAULT NULL,
  `status_transaksi`  enum('Peminjaman','Pengembalian') NOT NULL DEFAULT 'Peminjaman',
  `catatan`           text DEFAULT NULL,
  `created_at`        datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  KEY `id_anggota` (`id_anggota`),
  KEY `id_buku` (`id_buku`),
  KEY `id_petugas` (`id_petugas`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON UPDATE CASCADE,
  CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON UPDATE CASCADE,
  CONSTRAINT `transaksi_ibfk_3` FOREIGN KEY (`id_petugas`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabel: denda
-- -------------------------------------------------------
CREATE TABLE `denda` (
  `id_denda`      int(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi`  int(11) NOT NULL,
  `jumlah_hari`   int(11) NOT NULL DEFAULT 0,
  `tarif_per_hari` int(11) NOT NULL DEFAULT 1000,
  `total_denda`   int(11) NOT NULL DEFAULT 0,
  `status_bayar`  enum('belum','sudah') NOT NULL DEFAULT 'belum',
  `tgl_bayar`     datetime DEFAULT NULL,
  `created_at`    datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_denda`),
  KEY `id_transaksi` (`id_transaksi`),
  CONSTRAINT `denda_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabel: ulasan_buku
-- -------------------------------------------------------
CREATE TABLE `ulasan_buku` (
  `id_ulasan`   int(11) NOT NULL AUTO_INCREMENT,
  `id_anggota`  int(11) NOT NULL,
  `id_buku`     int(11) NOT NULL,
  `rating`      tinyint(1) NOT NULL DEFAULT 5,
  `ulasan`      text NOT NULL,
  `created_at`  datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ulasan`),
  KEY `id_anggota` (`id_anggota`),
  KEY `id_buku` (`id_buku`),
  CONSTRAINT `ulasan_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON UPDATE CASCADE,
  CONSTRAINT `ulasan_ibfk_2` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DATA AWAL (Seed Data)
-- =====================================================

-- Pengguna default (admin & petugas)
INSERT INTO `pengguna` (`username`, `password`, `nama_pengguna`, `email`, `level`) VALUES
('admin',   'admin123',   'Administrator',  'admin@perpus.com',   'admin'),
('petugas', 'petugas123', 'Petugas Perpustakaan', 'petugas@perpus.com', 'petugas');

-- Anggota default
INSERT INTO `anggota` (`nis`, `nama_anggota`, `username`, `password`, `email`, `kelas`) VALUES
('001', 'Budi Santoso',   'budi',   'budi123',   'budi@email.com',   'XII RPL'),
('002', 'Siti Rahayu',    'siti',   'siti123',   'siti@email.com',   'XI TKJ'),
('003', 'Andi Pratama',   'andi',   'andi123',   'andi@email.com',   'X MM');

-- Kategori buku
INSERT INTO `kategori` (`nama_kategori`, `deskripsi`) VALUES
('Fiksi',           'Novel, cerpen, dan karya fiksi lainnya'),
('Non-Fiksi',       'Buku pengetahuan dan ilmu umum'),
('Pelajaran',       'Buku teks dan pelajaran sekolah'),
('Referensi',       'Kamus, ensiklopedia, dan referensi'),
('Teknologi',       'Buku pemrograman dan teknologi informasi'),
('Sains',           'Buku ilmu pengetahuan alam');

-- Buku contoh
INSERT INTO `buku` (`id_kategori`, `judul_buku`, `pengarang`, `penerbit`, `tahun_terbit`, `isbn`, `deskripsi`, `stok`, `status`) VALUES
(1, 'Laskar Pelangi',          'Andrea Hirata',   'Bentang Pustaka', 2005, '978-979-1478-21-8', 'Novel tentang anak-anak di Belitung', 3, 'tersedia'),
(1, 'Bumi Manusia',            'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, '978-979-661-714-4', 'Tetralogi Buru', 2, 'tersedia'),
(2, 'Sejarah Indonesia Modern','M.C. Ricklefs',   'Serambi',         2008, '978-979-024-067-5', 'Sejarah Indonesia dari 1200 sampai sekarang', 2, 'tersedia'),
(3, 'Matematika Kelas XII',    'Kemendikbud',     'Kemendikbud',     2020, NULL,                'Buku teks matematika kelas XII', 5, 'tersedia'),
(5, 'Pemrograman PHP Modern',  'Rizky Abdulah',   'Informatika',     2022, '978-602-02-7680-3', 'Panduan lengkap PHP dan MySQL', 2, 'tersedia'),
(6, 'Fisika Dasar',            'Halliday & Resnick','Erlangga',       2018, '978-979-095-879-3', 'Fisika untuk mahasiswa dan pelajar', 2, 'tersedia');

COMMIT;
