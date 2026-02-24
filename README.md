# 📚 Aplikasi Peminjaman Buku Digital


---

## 🗂️ Struktur Direktori

```
perpus_30/
├── index.php           ← Halaman login & registrasi
├── setup.php           ← Instalasi database
├── perpus_db.sql       ← Skema + data awal
├── .htaccess
├── config/
│   └── database.php    ← Konfigurasi DB
├── includes/
│   └── session.php     ← Helper session
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── admin/              ← Panel Admin
│   ├── dashboard.php
│   ├── pengguna.php    ← CRUD Admin & Petugas
│   ├── anggota.php     ← CRUD + Reset PW Anggota
│   ├── kategori.php    ← CRUD Kategori
│   ├── buku.php        ← CRUD Buku
│   ├── transaksi.php   ← Peminjaman & Pengembalian
│   ├── denda.php       ← Monitoring Denda
│   ├── laporan.php     ← Laporan (Pengguna/Anggota/Buku/Pinjam/Denda)
│   ├── profil.php      ← Edit Profil Pribadi
│   └── logout.php
├── petugas/            ← Panel Petugas (sama dengan admin - tanpa manajemen pengguna)
│   ├── dashboard.php
│   ├── anggota.php
│   ├── kategori.php
│   ├── buku.php
│   ├── transaksi.php
│   ├── denda.php
│   ├── laporan.php
│   ├── profil.php
│   └── logout.php
└── anggota/            ← Panel Anggota
    ├── dashboard.php
    ├── katalog.php     ← Lihat koleksi buku
    ├── pinjam.php      ← Ajukan peminjaman
    ├── kembali.php     ← Pengembalian buku
    ├── riwayat.php     ← Riwayat peminjaman
    ├── ulasan.php      ← Tambah/Hapus ulasan
    ├── profil.php      ← Edit profil pribadi
    └── logout.php
```

---

## ⚙️ Instalasi

### Persyaratan
- PHP 7.4+ atau 8.x
- MySQL 5.7+ / MariaDB 10.x
- Apache dengan mod_rewrite

### Langkah

1. **Copy** folder `perpus_30` ke direktori web server (`htdocs` / `www`)

2. **Edit** `config/database.php` sesuai kredensial database:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'perpus_30');
   ```

3. **Buka browser** → `http://localhost/perpus_new/setup.php`
   Klik **"Inisialisasi Database"**

4. **Login** di `http://localhost/perpus_30/`

---

## 👤 Akun Default

| Role    | Username  | Password    |
|---------|-----------|-------------|
| Admin   | admin     | admin123    |
| Petugas | petugas   | petugas123  |
| Anggota | budi      | budi123     |
| Anggota | siti      | siti123     |

---

## 📋 Fitur 

### Admin
- ✅ Login
- ✅ Tambah/Hapus/Edit Pengguna (Admin & Petugas)
- ✅ Edit Profil Pribadi
- ✅ Reset Password Anggota
- ✅ Tambah/Edit/Hapus Daftar Anggota
- ✅ Tambah/Hapus/Edit Kategori Buku
- ✅ Tambah/Hapus/Edit Data Buku
- ✅ Melakukan Transaksi (Peminjaman & Pengembalian)
- ✅ Memantau Denda Buku
- ✅ Cetak Laporan: Pengguna, Anggota, Buku, Buku Pinjam, Denda

### Petugas
- ✅ Login
- ✅ Edit Profil Pribadi
- ✅ Tambah/Edit/Hapus Daftar Anggota
- ✅ Tambah/Hapus/Edit Kategori Buku
- ✅ Tambah/Hapus/Edit Data Buku
- ✅ Melakukan Transaksi
- ✅ Memantau Denda Buku
- ✅ Cetak Laporan: Anggota, Buku, Buku Pinjam, Denda

### Anggota
- ✅ Login
- ✅ Registrasi
- ✅ Edit Profil Pribadi
- ✅ Melakukan Peminjaman Buku
- ✅ Melakukan Pengembalian Buku
- ✅ Memberikan Ulasan Buku (Tambah & Hapus)

---

## 🗄️ Skema Database

- `pengguna` — Admin & Petugas
- `anggota` — Data anggota perpustakaan
- `kategori` — Kategori buku
- `buku` — Data koleksi buku
- `transaksi` — Peminjaman & pengembalian
- `denda` — Denda keterlambatan (Rp 1.000/hari)
- `ulasan_buku` — Ulasan dan rating buku

---

*Dibuat sesuai Alur Aplikasi Peminjaman Buku*
