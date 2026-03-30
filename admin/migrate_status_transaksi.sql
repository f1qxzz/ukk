-- ============================================================
-- Migrasi: Tambah status baru ke tabel transaksi
-- Jalankan SEKALI di database sebelum deploy
-- ============================================================

-- ALUR STATUS BARU:
--   Peminjaman (lama)  → butuh konfirmasi admin (Setujui/Tolak)
--   Setelah Setujui    → Dipinjam
--   Setelah Tolak      → Ditolak
--   Setelah Kembalikan → Dikembalikan
--
-- Data lama (Peminjaman/Pengembalian) tetap berfungsi normal.

-- Jika status_transaksi bertipe ENUM, jalankan ini:
ALTER TABLE transaksi
  MODIFY COLUMN status_transaksi
    ENUM('Pending','Peminjaman','Dipinjam','Pengembalian','Dikembalikan','Ditolak')
    NOT NULL DEFAULT 'Peminjaman';

-- Jika bertipe VARCHAR, tidak perlu ALTER di atas.

-- PEMETAAN LOGIKA:
--  Pending / Peminjaman  → [Setujui] [Tolak]
--  Dipinjam              → [Kembalikan]
--  Dikembalikan          → Selesai
--  Pengembalian (lama)   → Selesai
--  Ditolak               → —
