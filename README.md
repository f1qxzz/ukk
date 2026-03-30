# 📚 Sistem Perpustakaan Digital (Digital Library System)

## 📖 Project Overview

### 🎯 Nama & Deskripsi

**Perpustakaan Digital** adalah aplikasi web berbasis PHP Native yang dirancang untuk mengelola seluruh operasional perpustakaan modern secara digital dan terintegrasi. Sistem ini menyediakan platform manajemen lengkap mulai dari katalogisasi buku, pengelolaan anggota (member), hingga pemantauan peminjaman dan pengambilan denda secara otomatis.

### 🔍 Masalah & Solusi

**Masalah:**
- Pengelolaan peminjaman buku manual 📋 (rawan error & lambat)
- Kesulitan tracking ketersediaan buku 📖 (informasi tidak akurat)
- Proses denda keterlambatan tidak otomatis ⏰ (merepotkan petugas)
- Tidak ada riwayat peminjaman yang terstruktur 📊 (sulit audit)
- Sistem approval peminjaman manual 👤 (tidak efisien)

**Solusi yang Ditawarkan:**
✅ Sistem peminjaman digital terintegrasi dengan tracking real-time  
✅ Katalog buku online dengan filter kategori & pencarian  
✅ Perhitungan denda otomatis untuk keterlambatan  
✅ Riwayat peminjaman/pengembalian ter-simpan  
✅ Sistem approval bertingkat (Admin → Petugas → Anggota)  
✅ Dashboard analytics untuk monitoring operasional  
✅ Responsive design untuk akses mobile & desktop  

### 💼 Use Cases Utama

| Pengguna | Use Case |
|----------|----------|
| **Admin** | Kelola pengguna, approve peminjaman, lihat laporan sistem |
| **Petugas** | Kelola buku, validasi peminjaman/pengembalian, kelola denda |
| **Anggota** | Cari buku, ajukan peminjaman, lihat riwayat, bayar denda |

---

## 🎯 Features (Fitur Utama)

### 👨‍💼 Feature Pool per Role

#### **Admin Features** 🔐
- 📊 Dashboard dengan statistik real-time (total buku, anggota, peminjaman aktif)
- 👥 Manajemen pengguna (admin, petugas) - CRUD
- 📚 Approval peminjaman (Pending → Setujui/Tolak)
- 💰 Tracking denda keterlambatan
- 📈 Laporan peminjaman & pengembalian
- ⚙️ Setup sistem & konfigurasi

#### **Petugas (Librarian) Features** 📚
- 📚 Manajemen katalog buku lengkap (CRUD)
- 🏷️ Kelola kategori buku
- 👥 Kelola data anggota
- ✅ Validasi peminjaman & pengembalian
- 💳 Track & kelola denda anggota
- 📊 Lihat laporan peminjaman

#### **Anggota (Member) Features** 👤
- 🔍 Cari & browse katalog buku
- ⭐ Lihat detail buku & ulasan
- 📋 Ajukan peminjaman buku
- 🚀 Lihat status peminjaman (Pending/Approved/Rejected)
- 📅 Cek tanggal pengembalian & denda
- 📖 Lihat riwayat peminjaman & pengembalian
- 💬 Tulis ulasan buku
- 👤 Kelola profil pribadi

---

## 🛠 Tech Stack

| Layer | Teknologi | Versi | Fungsi |
|-------|-----------|-------|--------|
| **Backend** | PHP | 7.4+ | Server-side logic, database operations |
| **Database** | MySQL | 5.7+ | Data storage & management |
| **Frontend** | HTML5 | - | Markup & semantic structure |
| **Styling** | CSS3 | - | Layout, responsive design, animations |
| **Scripting** | JavaScript | ES6+ | Client-side interactivity |
| **Icons** | Font Awesome | 6.x | UI icons & symbols |
| **Fonts** | Inter, Plus Jakarta Sans | - | Typography & readability |
| **Server** | XAMPP/Apache | 2.4+ | Local development & production |

### 🔧 Development Environment
- **OS**: Windows (XAMPP), Linux/macOS (Docker optional)
- **Database Client**: phpMyAdmin/MySQL Workbench
- **Code Editor**: VS Code, PHPStorm
- **Version Control**: Git

---

## 📁 Project Structure (Arsitektur Aplikasi)

### 🌳 Struktur Folder

```
perpustakaan-digital/
│
├── 📄 index.php                 ← Landing page / Home page
├── 📄 login.php                 ← Login untuk semua role
├── 📄 register.php              ← Registrasi anggota baru
├── 📄 logout.php                ← Logout handler
├── 📄 api_search.php            ← API search buku (AJAX)
├── 📄 setup.php                 ← Setup awal database
│
├── 📂 config/
│   └── 📄 database.php          ← Database configuration & helpers
│
├── 📂 includes/
│   ├── 📄 session.php           ← Session management & auth functions
│   └── 📄 upload_helper.php     ← File upload utilities
│
├── 📂 admin/                    ← Admin Dashboard & Management
│   ├── 📄 dashboard.php         ← Admin dashboard (stats, quick actions)
│   ├── 📄 pengguna.php          ← CRUD pengguna (admin, petugas)
│   ├── 📄 transaksi.php         ← Approval peminjaman
│   ├── 📄 denda.php             ← Management denda anggota
│   ├── 📄 laporan.php           ← Reports & analytics
│   ├── 📄 profil.php            ← Admin profile settings
│   ├── 📄 logout.php            ← Admin logout handler
│   │
│   └── 📂 includes/             ← Shared partials / components
│       ├── 📄 header.php        ← HTML head & navbar
│       └── 📄 nav.php           ← Sidebar navigation
│
├── 📂 petugas/                  ← Petugas (Librarian) Dashboard
│   ├── 📄 dashboard.php         ← Petugas dashboard
│   ├── 📄 anggota.php           ← Member management
│   ├── 📄 buku.php              ← Book catalog management
│   ├── 📄 kategori.php          ← Book categories
│   ├── 📄 transaksi.php         ← Transactions & validations
│   ├── 📄 denda.php             ← Fine management
│   ├── 📄 laporan.php           ← Reports
│   ├── 📄 profil.php            ← Petugas profile
│   ├── 📄 logout.php            ← Logout handler
│   │
│   └── 📂 includes/
│       ├── 📄 header.php        ← HTML head & navbar
│       └── 📄 nav.php           ← Sidebar navigation
│
├── 📂 anggota/                  ← Anggota (Member) Dashboard
│   ├── 📄 dashboard.php         ← Member dashboard
│   ├── 📄 katalog.php           ← Book catalog (browsing)
│   ├── 📄 pinjam.php            ← Loan request form
│   ├── 📄 riwayat.php           ← Loan history & status
│   ├── 📄 kembali.php           ← Return book requests
│   ├── 📄 ulasan.php            ← Write book reviews
│   ├── 📄 profil.php            ← Member profile
│   ├── 📄 logout.php            ← Logout handler
│   │
│   └── 📂 includes/
│       ├── 📄 header.php        ← HTML head & navbar
│       └── 📄 nav.php           ← Sidebar navigation
│
├── 📂 assets/                   ← Static assets
│   ├── 📂 css/                  ← Stylesheets
│   │   ├── 📄 index.css         ← Homepage styling
│   │   ├── 📄 login.css         ← Login page styling
│   │   ├── 📄 register.css      ← Register page styling
│   │   ├── 📄 style.css         ← Global styles
│   │   ├── 📄 responsive-fix.css ← Responsive utility fixes
│   │   │
│   │   ├── 📂 admin/            ← Admin-specific styles
│   │   │   ├── 📄 admin.css            ← Admin page styles
│   │   │   ├── 📄 dashboard.css        ← Dashboard styling
│   │   │   ├── 📄 anggota.css
│   │   │   ├── 📄 buku.css
│   │   │   ├── 📄 kategori.css
│   │   │   ├── 📄 transaksi.css
│   │   │   ├── 📄 denda.css
│   │   │   ├── 📄 laporan.css
│   │   │   └── 📄 profil.css
│   │   │
│   │   ├── 📂 anggota/          ← Member-specific styles
│   │   │   ├── 📄 dashboard.css
│   │   │   ├── 📄 katalog.css
│   │   │   ├── 📄 pinjam.css
│   │   │   ├── 📄 riwayat.css
│   │   │   ├── 📄 kembali.css
│   │   │   ├── 📄 ulasan.css
│   │   │   └── 📄 profil.css
│   │   │
│   │   └── 📂 petugas/          ← Petugas-specific styles
│   │       ├── 📄 dashboard.css
│   │       ├── 📄 anggota.css
│   │       ├── 📄 buku.css
│   │       ├── 📄 kategori.css
│   │       ├── 📄 transaksi.css
│   │       ├── 📄 denda.css
│   │       ├── 📄 laporan.css
│   │       └── 📄 profil.css
│   │
│   ├── 📂 js/
│   │   └── 📄 script.js         ← Global JavaScript utilities
│   │
│   └── 📂 img/                  ← Images & media files
│
├── 📂 uploads/                  ← Uploaded files (dynamic)
│   ├── 📂 cover/                ← Book cover images
│   ├── 📂 covers/               ← Alt cover storage
│   ├── 📂 foto_anggota/         ← Member profile photos
│   └── 📂 foto_profil/          ← Profile pictures
│
└── 📄 README.md                 ← Documentation (this file)
```

### 📂 Penjelasan Struktur Folder

#### **Root Level Files** 🏠
| File | Fungsi |
|------|--------|
| `index.php` | Landing page & home (gallery buku populer, baru) |
| `login.php` | Authentication page untuk semua role |
| `register.php` | Self-service registration untuk anggota baru |
| `logout.php` | Session termination handler |
| `api_search.php` | REST endpoint untuk AJAX search buku |
| `setup.php` | Database initialization & migration (sekali jalan) |

#### **config/** ⚙️
**Tujuan:** Centralized configuration & database utilities

Berisi:
- Database connection string & credentials
- Helper functions untuk query aman
- Constants (denda/hari, dll)
- Connection management

#### **includes/** 🔧
**Tujuan:** Shared utilities & helpers

Berisi:
- `session.php`: Session initialization & auth checks
- `upload_helper.php`: File validation & upload logic

#### **admin/, petugas/, anggota/** 👥
**Tujuan:** Role-based dashboards & management pages

Struktur identik untuk setiap role:
- `dashboard.php`: Main page dengan stats & quick actions
- Management pages (CRUD): anggota.php, buku.php, kategori.php
- `transaksi.php`: Transaction management & approval
- `denda.php`: Fine tracking & history
- `laporan.php`: Reports & analytics
- `profil.php`: User profile & settings
- `includes/header.php` & `nav.php`: Layout template & navigation

#### **assets/** 🎨
**Tujuan:** All static frontend resources

- **css/**: Stylesheet per page & role (modular)
- **js/**: Client-side scripts (form validation, AJAX, etc)
- **img/**: Images, logos, icons

#### **uploads/** 📤
**Tujuan:** Dynamic uploaded files storage

- `cover/` & `covers/`: Book cover images
- `foto_anggota/`: Member profile pictures
- `foto_profil/`: Alternative profile storage

---

## 📄 FILE-BY-FILE BREAKDOWN (Detail Lengkap)

### 📄 index.php

**📌 Deskripsi:**  
Landing page publik yang menampilkan homepage sistem dengan katalog buku populer & terbaru. Bisa diakses tanpa login.

**⚙️ Fungsi Utama:**
- Fetch data buku dari database (populer & terbaru)
- Render gallery buku dengan pagination
- Provide navigation ke login/register
- Display welcome message untuk user terlogin

**🧠 Logika Utama:**

```php
// 1. Initialize session & check user role
require_once 'includes/session.php';
initSession();

// 2. Connect database
$conn = getConnection();

// 3. Query data buku populer (by transaction count)
$res_pop = safe_query($conn, "
    SELECT b.*, COUNT(t.id_transaksi) as jml_pinjam 
    FROM buku b 
    LEFT JOIN transaksi t ON b.id_buku = t.id_buku
    GROUP BY b.id_buku 
    ORDER BY jml_pinjam DESC 
    LIMIT 6
");

// 4. Query data buku terbaru
$res_baru = safe_query($conn, "
    SELECT b.* FROM buku b 
    ORDER BY b.id_buku DESC 
    LIMIT 10
");

// 5. Loop & render HTML
// 6. Close connection
closeConnection($conn);
```

**🔗 Keterkaitan:**
- Menggunakan `config/database.php` untuk koneksi
- Menggunakan `includes/session.php` untuk session check
- Link ke `login.php`, `register.php`, `anggota/katalog.php`
- Load CSS dari `assets/css/index.css`

**💡 Catatan:**
- Page ini publik (tidak perlu login)
- Menggunakan `get_cover()` function untuk clean image path
- Denda per hari: Rp1.000 (dari constant di `config/database.php`)

---

### 📄 login.php

**📌 Deskripsi:**  
Halaman autentikasi terpusat untuk memberikan akses kepada semua role (Admin, Petugas, Anggota). Handle POST form untuk validasi username/password.

**⚙️ Fungsi Utama:**
- Validasi kredensial user dari database
- Set session variables per role
- Redirect ke dashboard sesuai role
- Display error message untuk kredensial salah

**🧠 Logika Flow:**

```
1. User submit login form (POST)
   ↓
2. Validasi input (tidak kosong)
   ↓
3. Query database: 
   - TABLE pengguna (admin/petugas)
   - TABLE anggota (member)
   ↓
4. Password validation (plain text / hashed check)
   ↓
5. Set $_SESSION dengan data user:
   - $_SESSION['pengguna_logged_in'] = true (for admin/petugas)
   - $_SESSION['anggota_logged_in'] = true (for members)
   - $_SESSION['pengguna_level'] = 'admin'|'petugas'
   ↓
6. Redirect to role dashboard
   - Admin → admin/dashboard.php
   - Petugas → petugas/dashboard.php
   - Anggota → anggota/dashboard.php
```

**🔗 Keterkaitan:**
- Query table `pengguna` & `anggota`
- Set session via `includes/session.php`
- Redirect ke dashboards (`admin/`, `petugas/`, `anggota/`)

**💡 Catatan:**
- Support 3 table: pengguna (admin), pengguna (petugas), anggota
- Password saat ini plaintext (TODO: implement hash)
- Login form memiliki toggle password visibility

---

### 📄 register.php

**📌 Deskripsi:**  
Self-service registration page untuk anggota (member) baru. Hanya public users yang bisa register, bukan admin/petugas.

**⚙️ Fungsi Utama:**
- Render registration form
- Validate input (no SQL injection, email format)
- Insert new member ke table `anggota`
- Generate default password atau send confirmation
- Redirect ke login setelah berhasil

**🧠 Logika Validasi:**

```javascript
// Client-side & Server-side validation:
- Nama: tidak kosong, max 100 chars
- Email: unique check + format validation
- Username: unique check, 4-20 chars alphanumeric
- Password: min 6 chars, confirm match
- No_identitas: unique, numbers only (KTP/SIM)
- Alamat: optional, max 255 chars
- Foto: optional, image file only (.jpg, .png, .gif)
```

**🔗 Keterkaitan:**
- Insert ke table `anggota`
- Upload foto ke `uploads/foto_anggota/`
- File handling via `includes/upload_helper.php`
- Redirect ke `login.php` after success

**💡 Catatan:**
- Foto not required (fallback avatar di dashboard)
- Email unique constraint di database
- Username hanya untuk anggota (berbeda dengan pengguna/petugas)

---

### 📄 config/database.php

**📌 Deskripsi:**  
Central configuration file untuk database connection, constants, dan utility functions yang di-reuse di semua file aplikasi.

**⚙️ Fungsi Utama:**
- Database connection setup (MySQLi)
- Define global constants
- Provide helper functions untuk query aman
- Image path cleaning utility

**🧠 Isi & Struktur:**

```php
// ─── CONNECTION CONSTANTS ───
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'perpus_30');
define('DENDA_PER_HARI', 1000);  // Rp 1.000/hari keterlambatan

// ─── CONNECTION FUNCTIONS ───
function getConnection()  { /* Create MySQLi connection */ }
function closeConnection() { /* Close connection */ }

// ─── QUERY HELPERS ───
function safe_query($conn, $sql)     { /* Execute query safely */ }
function get_val($conn, $sql, $col)  { /* Get single value */ }

// ─── IMAGE PATH CLEANING ───
function get_cover($path)            { /* Remove '../' from path */ }
```

**🔗 Keterkaitan:**
- Included di `index.php` dan semua page penting
- Digunakan oleh session.php, admin/*, petugas/*, anggota/*

**💡 Catatan:**
- Connection charset: utf8mb4 (support emoji & special chars)
- Denda otomatis Rp1.000 per hari
- `safe_query()` untuk prevent fatal errors
- `get_val()` shortcut untuk SELECT COUNT/SUM queries

---

### 📄 includes/session.php

**📌 Deskripsi:**  
Session management helper yang provide functions untuk:
- Initialize session
- Check authentication status
- Get user information
- Require auth untuk protected pages

**⚙️ Fungsi Utama:**

```php
// ─── INITIALIZATION ───
initSession()              // Start session if not started

// ─── PENGGUNA (Admin/Petugas) ───
isPenggunaLoggedIn()       // Check if admin/petugas logged in
isAdmin()                  // Check if current user is admin
isPetugas()                // Check if current user is petugas
getPenggunaId()            // Get pengguna ID
getPenggunaName()          // Get pengguna name
getPenggunaLevel()         // Get 'admin' or 'petugas' level

// ─── ANGGOTA (Member) ───
isAnggotaLoggedIn()        // Check if member logged in
getAnggotaId()             // Get member ID
getAnggotaName()           // Get member name

// ─── PERMISSION CHECKS ───
requireAdmin()             // Redirect jika bukan admin
requirePetugas()           // Redirect jika bukan petugas
requireAnggota()           // Redirect jika bukan anggota

// ─── CLEANUP ───
logout()                   // Unset all session & redirect
```

**🧠 Session Structure:**

```php
// Untuk Pengguna (Admin/Petugas):
$_SESSION['pengguna_logged_in'] = true
$_SESSION['pengguna_id']         = 1
$_SESSION['pengguna_username']   = 'admin123'
$_SESSION['pengguna_nama']       = 'Admin Perpus'
$_SESSION['pengguna_level']      = 'admin'|'petugas'

// Untuk Anggota:
$_SESSION['anggota_logged_in'] = true
$_SESSION['anggota_id']        = 10
$_SESSION['anggota_nama']      = 'John Doe'
$_SESSION['anggota_email']     = 'john@example.com'
```

**🔗 Keterkaitan:**
- Included di semua protected pages (admin/*, petugas/*, anggota/*)
- Used by index.php untuk check user role
- Set oleh login.php setelah validasi

**💡 Catatan:**
- Session timeout: default PHP (24 minutes)
- Dual system: pengguna vs anggota (beda table)
- Require functions do header redirect (harus sebelum output)

---

### 📄 admin/dashboard.php

**📌 Deskripsi:**  
Admin dashboard menampilkan overview statistik sistem, quick actions untuk approval, daftar transaksi terbaru, dan shortcuts ke management pages.

**⚙️ Fungsi Utama:**
- Require admin role (protecthed page)
- Fetch & display system statistics
- Show pending loan approvals
- Quick action buttons (approve/reject)
- Display recent transactions

**🧠 Statistik yang Ditampilkan:**

| Stat | Query | Peran |
|------|-------|-------|
| Total Buku | `SELECT COUNT(*) FROM buku` | Inventory size |
| Total Anggota | `SELECT COUNT(*) FROM anggota` | User base |
| Buku Tersedia | `SELECT COUNT(*) FROM buku WHERE status='tersedia'` | Availability tracking |
| Peminjaman Aktif | `SELECT COUNT(*) FROM transaksi WHERE status_transaksi='Peminjaman'` | Active loans |
| Pengembalian | `SELECT COUNT(*) FROM transaksi WHERE status_transaksi='Pengembalian'` | Returns pending |

**🧠 Approval Logic:**

```
Transaksi dengan status='Pending' ditampilkan
   ↓
Admin click "Setujui" / "Tolak"
   ↓
Update status:
   - Setujui → 'Peminjaman' (book can be taken)
   - Tolak → 'Ditolak' (notify anggota)
   ↓
Send notification / Update booking status
```

**🔗 Keterkaitan:**
- Require `includes/session.php` untuk auth
- Require `config/database.php` untuk queries
- Include `includes/header.php` & `includes/nav.php` untuk layout
- Load `assets/css/admin/dashboard.css` untuk styling

**📥 Input / 📤 Output:**
- **Input**: GET/POST actions (setujui/tolak)
- **Output**: HTML dashboard + JSON responses (AJAX)

**💡 Catatan:**
- Hanya status Pending yang bisa di-approve
- Approve akan ubah status ke 'Peminjaman'
- Reject akan ubah status ke 'Ditolak' & notify user
- Stats auto-update via PHP queries (no cache)

---

### 📄 admin/pengguna.php

**📌 Deskripsi:**  
CRUD management page untuk pengguna (admin & petugas). Admin bisa create, read, update, delete users dengan role assignment.

**⚙️ Fungsi Utama:**
- Display list semua pengguna (admin + petugas)
- Form untuk tambah pengguna baru
- Edit pengguna (change name/password/role)
- Delete pengguna
- Search & filter by role

**🧠 Form Fields:**

```
Tambah/Edit Pengguna:
├─ Username (text, required, unique)
├─ Password (text, required, min 6 chars)
├─ Nama (text, required)
└─ Level (select: admin | petugas)
```

**🔗 Keterkaitan:**
- CRUD ke table `pengguna`
- Requires `requireAdmin()` untuk protect
- Upload foto ke `uploads/foto_profil/` (optional)

**💡 Catatan:**
- Password disimpan plaintext (TODO: use bcrypt hash)
- Username hanya for pengguna, bukan anggota
- Bisa soft-delete atau hard-delete (implement choice)

---

### 📄 admin/transaksi.php

**📌 Deskripsi:**  
Transaction approval & management page untuk admin. Menampilkan semua peminjaman pending, approved, rejected, dan provide action buttons untuk approve/reject/close.

**⚙️ Fungsi Utama:**
- List all transactions dengan status filtering
- Approve pending loans (set status='Peminjaman')
- Reject loans (set status='Ditolak', notify user)
- Mark return requests (set status='Pengembalian'→'Dikembalikan')
- Calculate & apply fines automatically

**🧠 Transaction Status Flow:**

```
┌─────────────────────────────────────────────────────┐
│                 TRANSACTION LIFECYCLE                │
├─────────────────────────────────────────────────────┤
│ Pending                                             │
│   ↓                                                 │
│ ┌─ Admin Approve → Peminjaman (ready to take)      │
│ │   ↓                                               │
│ │ ┌─ Petugas validate return → Pengembalian        │
│ │ │   ↓                                             │
│ │ │ Dikembalikan OR Terlambat (+ fine)             │
│ │                                                   │
│ └─ Admin Reject → Ditolak (cancelled)              │
│                                                     │
│ Auto Penalties:                                     │
│ - If tgl_kembali_rencana < hari ini → fine apply   │
│ - Fine = (hari_terlambat × DENDA_PER_HARI)         │
└─────────────────────────────────────────────────────┘
```

**🔗 Keterkaitan:**
- CRUD ke table `transaksi`, `denda`, `buku`
- Query dari tables `anggota`, `buku` untuk detail
- Update `buku` status (tersedia/dipinjam)
- Create/update records di table `denda`

**💡 Catatan:**
- Quick actions: inline approve/reject buttons
- Status 'Pending' only = actionable
- Late detection: compare tgl_kembali_rencana vs TODAY
- Fine auto-apply: no manual entry needed

---

### 📄 anggota/katalog.php

**📌 Deskripsi:**  
Public book catalog page untuk anggota. Menampilkan semua buku dengan filter kategori, search, dan detail buku.

**⚙️ Fungsi Utama:**
- List all books dengan paging
- Search by title/author/category
- Filter by kategori
- Display book details (cover, judul, pengarang, penerbit, deskripsi)
- Show status (tersedia/dipinjam)
- Link to loan request form

**🧠 Search & Filter Logic:**

```php
// GET parameters:
$search = $_GET['cari'] ?? '';      // Search keyword
$kategori = $_GET['kategori'] ?? '';  // Category filter
$page = $_GET['page'] ?? 1;

// Build query dynamically:
$where = "WHERE status='tersedia'";
if (!empty($search)) {
    $where .= " AND (judul_buku LIKE '%$search%' 
                   OR pengarang LIKE '%$search%')";
}
if (!empty($kategori)) {
    $where .= " AND id_kategori=$kategori";
}

// LIMIT OFFSET pagination
$limit = 12;  // books per page
$offset = ($page - 1) * $limit;
$sql = "SELECT * FROM buku $where ORDER BY id_buku DESC LIMIT $limit OFFSET $offset";
```

**🔗 Keterkaitan:**
- Query table `buku`, `kategori`
- Load kategori dropdown from `kategori` table
- Link to `pinjam.php` untuk buat peminjaman request

**💡 Catatan:**
- Hanya tampilkan buku dengan status='tersedia'
- Paging buat UX lebih baik (50+ buku)
- Search case-insensitive via MySQL LIKE

---

### 📄 anggota/pinjam.php

**📌 Deskripsi:**  
Loan request form page untuk anggota. User pilih buku & submit request yang di-queue untuk admin approval.

**⚙️ Fungsi Utama:**
- Display book details yang dipilih
- Show return deadline (default 7 hari)
- Request loan (create transaction record)
- Set status='Pending' untuk admin approval
- Redirect ke riwayat setelah berhasil

**🧠 Loan Creation Flow:**

```
1. Anggota select buku dari katalog
   ↓
2. Click "Pinjam" → go to pinjam.php?id_buku=X
   ↓
3. Validate:
   - Buku exists & tersedia
   - Anggota tidak punya >3 active loans
   - Anggota tidak ada outstanding fines
   ↓
4. Create record di table transaksi:
   {
     id_anggota: current user
     id_buku: selected book
     tgl_pinjam: TODAY()
     tgl_kembali_rencana: TODAY() + 7 days
     status_transaksi: 'Pending'
   }
   ↓
5. Update buku status: 'tersedia' → 'dipinjam'
   ↓
6. Redirect to anggota/riwayat.php (show pending)
```

**🔗 Keterkaitan:**
- Insert ke table `transaksi`
- Update table `buku` (status)
- Query table `anggota` untuk validasi
- Redirect ke `riwayat.php`

**🧠 Validasi Rules:**

```
✓ Book availability check (status='tersedia')
✓ Max 3 concurrent loans per member
✓ No outstanding fines (check table denda)
✓ Member active status check
✓ Duplicate loan check (prevent double-request)
```

**💡 Catatan:**
- Default return date: 7 hari dari hari ini
- Member bisa extend deadline? (design choice)
- Fine terapply otomatis jika late
- Buku status langsung berubah → updated inventory

---

### 📄 anggota/riwayat.php

**📌 Deskripsi:**  
Transaction history page untuk anggota. Menampilkan semua peminjaman (aktif & selesai) dengan status, due date, fines, dan action buttons.

**⚙️ Fungsi Utama:**
- Display all member transactions
- Show status (Pending/Peminjaman/Pengembalian/Dikembalikan/Ditolak/Terlambat)
- Display due date & days remaining/overdue
- Show applicable fines
- Button untuk return/extend loans

**🧠 Status Display Logic:**

```php
// Determine status display:
if ($status == 'Pending') {
    show: "⏳ Menunggu Persetujuan"
    show action: none (wait for admin)
}
elseif ($status == 'Peminjaman') {
    $days_left = datediff(tgl_kembali_rencana, today);
    if ($days_left > 0) {
        show: "✓ Dipinjam (kembali {$days_left} hari lagi)"
    } else {
        show: "⚠ TERLAMBAT ({$days_left} hari)" + FINE
        show action: "Kembalikan"
    }
}
elseif ($status == 'Ditolak') {
    show: "✕ Ditolak"
}
elseif ($status == 'Dikembalikan') {
    show: "✓ Dikembalikan"
}
```

**🔗 Keterkaitan:**
- Query table `transaksi`, `buku`, `anggota`
- Query table `denda` untuk show fines
- Link ke `kembali.php` untuk return request

**💡 Catatan:**
- Pending status locked (member cannot cancel)
- Late detection: auto calculate days overdue
- Fine display: show calculated amount
- Pagination essential (user mungkin 100+ transactions)

---

### 📄 anggota/kembali.php

**📌 Deskripsi:**  
Return request page. Anggota submit permintaan untuk return buku yang sedang dipinjam. Petugas yang akan validate fisik & update status.

**⚙️ Fungsi Utama:**
- Show current loans (peminjaman status only)
- Request return untuk selected loan
- Create return request record
- Set status='Pengembalian' (pending return validation)
- Calculate fine jika late

**🧠 Return Request Flow:**

```
1. Anggota select dari list loans yang active
   ↓
2. Click "Kembalikan" → go to kembali.php?id_transaksi=X
   ↓
3. Validate:
   - Transaksi exists & status='Peminjaman'
   - Anggota owns this transaction
   ↓
4. Update transaksi record:
   status_transaksi: 'Pengembalian'
   tgl_kembali_aktual: TODAY()
   ↓
5. Calculate fine jika late:
   hari_terlambat = datediff(today, tgl_kembali_rencana)
   if (hari_terlambat > 0) {
      fine = hari_terlambat × DENDA_PER_HARI
      insert into denda {...}
   }
   ↓
6. Redirect to riwayat.php (show return pending)
```

**🔗 Keterkaitan:**
- Update table `transaksi`
- Create/update table `denda` (if late)
- Query table `anggota` untuk validasi

**💡 Catatan:**
- Petugas masih perlu validate fisik di halaman petugas/transaksi.php
- Fine otomatis, tidak perlu manual input
- Status 'Pengembalian' = waiting for librarian verification

---

### 📄 assets/css/admin/dashboard.css

**📌 Deskripsi:**  
Stylesheet khusus untuk admin dashboard. Styling untuk layout (grid, flexbox), cards, stats, buttons, table, dan animations.

**⚙️ Komponen Styling:**

```css
/* ─── CSS Variables (Theme Colors) ─── */
:root {
  --soft-purple: #9b8c9c;
  --soft-lavender: #b8a9c9;
  --neutral-800: #332e3a;
  --success-600: #3e8b63;
  --danger-600: #c2556b;
  --shadow-md: 0 4px 16px rgba(...);
  --radius-lg: 1rem;
}

/* ─── Layout Components ─── */
.app-wrap          { display: flex; main layout }
.main-area         { flex: 1; responsive container }
.content           { padding: 28px; main content area }

/* ─── Welcome Card ─── */
.wb                { backdrop-filter blur effect; padding; box-shadow }
.wb-avatar         { circular image; gradient background }
.wb-name           { heading; color }

/* ─── Stats Grid ─── */
.srow              { display: grid; 3 columns; gap: 20px }
.sc (stat-card)    { background: semi-transparent; hover scale up }
.stat-icon         { 56px × 56px square; gradient background }

/* ─── Recent Transactions Card ─── */
.dc (data-card)    { white semi-transparent; table inside }

/* ─── Status Badge ─── */
.status-badge      { inline-flex; icon + text; rounded }
.status-badge.success   { green styling }
.status-badge.danger    { red styling }
.status-badge.warning   { yellow/orange styling }

/* ─── Table Styling ─── */
table               { border-collapse; width 100% }
tbody tr            { hover effect; subtle bg color }
.cover-thumb       { image thumbnail; max 50px; rounded }

/* ─── Buttons ─── */
.btn-primary       { gradient purple; white text; rounded }
.qa-btn            { quick action; inline; flexible sizing }

/* ─── Responsive ─── */
@media (max-width: 768px) {
  .srow { grid-template-columns: 1fr; }
  .content { padding: 16px; }
}
```

**🔗 Keterkaitan:**
- Used by `admin/dashboard.php` template
- Extend dari global `assets/css/style.css`
- Override dengan role-specific colors & spacing

**💡 Catatan:**
- CSS Variables for maintainability
- Backdrop-filter untuk glassmorphism effect
- Responsive grid: 3 cols → 1 col on mobile
- Transition classes untuk smooth animations

---

### 📄 assets/js/script.js

**📌 Deskripsi:**  
Global JavaScript utilities untuk client-side functionality: form validation, AJAX search, modal dialogs, confirmation boxes, dan DOM manipulations.

**⚙️ Utility Functions:**

```javascript
// ─── Search & Filter ───
function searchBooks(keyword)     // AJAX search di katalog
function filterByCategory(cat_id)  // Load kategori filter

// ─── Form Validation ───
function validateForm(form_id)     // Check required fields
function validateEmail(email)      // Email format check
function validatePassword(pwd)     // Min length & strength

// ─── DOM Manipulation ───
function togglePassword(field_id)  // Show/hide password
function toggleMenu()              // Mobile sidebar toggle
function addToCart(book_id)        // Add wishlist/cart

// ─── AJAX Handlers ───
function submitForm(form_id, url)  // AJAX form submission
function approveTransaction(trans_id) // Quick approve
function rejectTransaction(trans_id)  // Quick reject

// ─── UI Feedback ───
function showMessage(msg, type)    // Success/error alert
function showConfirm(msg, callback) // Confirmation modal
function hideAlert()               // Close alert
```

**🧠 Example: Search Implementation**

```javascript
// User type in search input
document.getElementById('searchInput').addEventListener('keyup', debounce(function(e) {
  let keyword = e.target.value;
  
  // AJAX call to api_search.php
  fetch('api_search.php?q=' + encodeURIComponent(keyword))
    .then(r => r.json())
    .then(data => {
      // Update results div with book list
      renderResults(data);
    });
}, 300));  // 300ms debounce
```

**🔗 Keterkaitan:**
- Loaded di semua page: `<script src="/assets/js/script.js"></script>`
- Works dengan `api_search.php` untuk backend search
- DOM targets: forms, inputs, buttons, alerts

**💡 Catatan:**
- Use debounce untuk search (prevent spam requests)
- Graceful fallback jika JS disabled
- Security: use `encodeURIComponent` untuk URL parameters

---

## ⚙️ Installation Guide

### 📋 Prerequisites

Sebelum mulai, pastikan sudah install:

| Tool | Versi | Fungsi |
|------|-------|--------|
| **XAMPP** | 7.4+ | Apache + PHP + MySQL |
| **Git** | 2.24+ | Version control |
| **Text Editor** | VSCode+ | Code editing |
| **Browser** | Chrome/Firefox | Development browser |

### 🚀 Step-by-Step Installation

#### **1️⃣ Clone Repository**

```bash
# Navigate ke htdocs
cd C:\xampp\htdocs

# Clone project
git clone https://github.com/yourname/perpustakaan-digital.git f1q
cd f1q
```

#### **2️⃣ Start XAMPP Services**

```bash
# Buka XAMPP Control Panel
1. Click "Start" button pada Apache
2. Click "Start" button pada MySQL
3. Tunggu status menjadi "Running"
```

#### **3️⃣ Create Database**

```bash
# Option A: Via phpMyAdmin UI
1. Buka http://localhost/phpmyadmin
2. Create New Database: nama "perpus_30"
3. Charset: utf8mb4_unicode_ci
4. Click Create

# Option B: Via MySQL Command Line
mysql -u root -p
CREATE DATABASE perpus_30 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

#### **4️⃣ Run Setup Script**

```bash
# Buka browser
http://localhost/f1q/setup.php

# Atau lakukan manual SQL import:
1. Buka phpMyAdmin
2. Pilih database "perpus_30"
3. Tab "Import"
4. Upload file database.sql (jika ada)
5. Klik Import
```

#### **5️⃣ Verify Installation**

```bash
# Test endpoints
http://localhost/f1q              # Homepage
http://localhost/f1q/login.php    # Login page
http://localhost/f1q/register.php # Register page
```

---

## 🔐 Environment Configuration

### 📝 File: config/database.php

Edit file `config/database.php` untuk konfigurasi database:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');      // MySQL host
define('DB_USER', 'root');           // MySQL username
define('DB_PASS', '');               // MySQL password (empty for default)
define('DB_NAME', 'perpus_30');      // Database name
define('DENDA_PER_HARI', 1000);      // Fine amount per day (Rp)
?>
```

### 🔑 Configuration Variables

| Variable | Default | Fungsi | Contoh |
|----------|---------|--------|--------|
| `DB_HOST` | localhost | MySQL server address | `localhost` atau `192.168.1.10` |
| `DB_USER` | root | MySQL username | `root` (development) |
| `DB_PASS` | (empty) | MySQL password | `password123` (set for production) |
| `DB_NAME` | perpus_30 | Database name | `perpus_30` |
| `DENDA_PER_HARI` | 1000 | Fine per day (Rp) | `1000` = Rp 1.000/hari |

### 🔧 Konfigurasi untuk Production

```php
// Production config (SECURE)
define('DB_HOST', 'prod-db.example.com');  // External DB server
define('DB_USER', 'app_user');              // Specific DB user
define('DB_PASS', 'super_secure_password'); // Strong password
define('DB_NAME', 'perpus_prod');          // Production DB name

// Add security headers
error_reporting(0);  // Hide error messages dari public
ini_set('display_errors', 0);
```

---

## ▶️ Running the Project

### 🏃 Development Mode

```bash
# 1. Start XAMPP
#    - Open XAMPP Control Panel
#    - Click "Start" Apache & MySQL

# 2. Browse to project
#    http://localhost/f1q

# 3. Login with default credentials
#    Admin:
#    - Username: admin
#    - Password: admin123
#    
#    Anggota (Member):
#    - Daftar baru di registration page
```

### 🚀 Access Dashboards

| Role | URL | Credentials |
|------|-----|-------------|
| **Homepage** | `http://localhost/f1q` | Public (no login) |
| **Login Page** | `http://localhost/f1q/login.php` | All roles |
| **Admin Dashboard** | `http://localhost/f1q/admin/dashboard.php` | admin/admin123 |
| **Petugas Dashboard** | `http://localhost/f1q/petugas/dashboard.php` | petugas/petugas123 |
| **Member Dashboard** | `http://localhost/f1q/anggota/dashboard.php` | (register first) |

### 🎯 Important Scripts

```bash
# Database Initialization
curl http://localhost/f1q/setup.php

# API Search (AJAX)
curl http://localhost/f1q/api_search.php?q=buku

# Logout
curl http://localhost/f1q/logout.php
```

---

## 🔄 Application Flow (Alur Sistem)

### 📊 User Journey Flow

#### **👤 Anggota (Member) Flow**

```
┌─────────────────────────────────────────────────────────┐
│           ANGGOTA (MEMBER) JOURNEY                       │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  1. REGISTER / LOGIN                                    │
│     ├─ New user: register.php                           │
│     │  └─ Form: nama, email, username, password         │
│     │  └─ Submit → insert to table `anggota`            │
│     │  └─ Redirect to login.php                         │
│     │                                                    │
│     └─ Existing user: login.php                         │
│        └─ Form: username, password                      │
│        └─ Validate → set $_SESSION                      │
│        └─ Redirect to anggota/dashboard.php             │
│                                                          │
│  2. BROWSE KATALOG (anggota/katalog.php)               │
│     ├─ View all books with pagination                   │
│     ├─ Search by title/author                           │
│     ├─ Filter by category                               │
│     └─ Click book → see details                         │
│                                                          │
│  3. PINJAM BUKU (anggota/pinjam.php)                   │
│     ├─ Form: select book, set return date               │
│     ├─ Click "Pinjam" button                            │
│     ├─ Create record: transaksi (status='Pending')      │
│     ├─ Update book: status='dipinjam'                   │
│     └─ Redirect to riwayat.php                          │
│                                                          │
│  4. TUNGGU APPROVAL (status='Pending')                 │
│     ├─ Admin review request                             │
│     ├─ Admin click "Setujui" → status='Peminjaman'     │
│     │  └─ Now member can take book from library         │
│     ├─ OR Admin click "Tolak" → status='Ditolak'       │
│     │  └─ Loan cancelled                                │
│     └─ Member see status in anggota/riwayat.php         │
│                                                          │
│  5. PINJAM BUKU DI PERPUSTAKAAN                         │
│     ├─ Petugas scan barcode / validate                  │
│     ├─ Book handed to member                            │
│     └─ Status unchanged in system (already='Peminjaman')│
│                                                          │
│  6. MONITOR DEADLINE                                    │
│     ├─ anggota/riwayat.php show due date                │
│     ├─ Days remaining counter                           │
│     ├─ If today > tgl_kembali_rencana:                  │
│     │  └─ Status change to 'TERLAMBAT' + show fine      │
│     └─ Fine = days_overdue × Rp1.000                    │
│                                                          │
│  7. KEMBALIKAN BUKU (anggota/kembali.php)              │
│     ├─ Form: select from active loans                   │
│     ├─ Click "Kembalikan"                               │
│     ├─ Update transaksi: status='Pengembalian'          │
│     ├─ Calculate fine jika late                         │
│     └─ Redirect to riwayat.php                          │
│                                                          │
│  8. PETUGAS VALIDASI RETURN                             │
│     ├─ Petugas: petugas/transaksi.php                   │
│     ├─ See status='Pengembalian' requests                │
│     ├─ Scan/receive book physical                       │
│     ├─ Click "Terima Kembali" → status='Dikembalikan'   │
│     ├─ Update book: status='tersedia'                   │
│     └─ Fine now payable (if applicable)                 │
│                                                          │
│  9. BAYAR DENDA (if late)                               │
│     ├─ Member see denda amount di riwayat               │
│     ├─ Pay to petugas at counter                        │
│     ├─ Petugas mark as paid in denda.php                │
│     └─ Fine cleared from member record                  │
│                                                          │
│  10. LIHAT RIWAYAT (anggota/riwayat.php)               │
│      ├─ Show all transactions: completed & pending      │
│      ├─ Filter by status                                │
│      ├─ Show fines payable                              │
│      └─ Export history (future feature)                 │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

#### **👨‍💼 Admin Flow**

```
ADMIN WORKFLOW:

Login (login.php)
  ↓
Admin Dashboard (admin/dashboard.php)
  ├─ View stats: total buku, anggota, pinjaman
  ├─ See pending approvals
  ├─ Quick actions: approve/reject loans
  │
  └─ Manage Pages:
     ├─ admin/pengguna.php → CRUD pengguna (admin/petugas)
     ├─ admin/transaksi.php → Approve/reject/close loans
     ├─ admin/laporan.php → View reports & analytics
     └─ admin/profil.php → Edit own profile
```

#### **📚 Petugas Flow**

```
PETUGAS WORKFLOW:

Login (login.php)
  ↓
Petugas Dashboard (petugas/dashboard.php)
  ├─ View today's activities
  │
  └─ Manage Pages:
     ├─ petugas/buku.php → CRUD books, manage inventory
     ├─ petugas/kategori.php → CRUD categories
     ├─ petugas/anggota.php → View members, manage data
     ├─ petugas/transaksi.php → Validate loans & returns
     ├─ petugas/denda.php → Track & collect fines
     ├─ petugas/laporan.php → View reports
     └─ petugas/profil.php → Edit profile
```

### 🔄 Database Flow Diagram

```
┌─────────────────────────────────────────────────────────┐
│                   DATABASE TABLES                        │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  pengguna (admin & petugas users)                       │
│  ├─ id_pengguna (PK)                                    │
│  ├─ username (UNIQUE)                                   │
│  ├─ password                                            │
│  ├─ nama                                                │
│  └─ level: 'admin' | 'petugas'                          │
│                                                          │
│  anggota (library members)                              │
│  ├─ id_anggota (PK)                                     │
│  ├─ nama                                                │
│  ├─ email (UNIQUE)                                      │
│  ├─ username (UNIQUE)                                   │
│  ├─ password                                            │
│  ├─ no_identitas (UNIQUE)                               │
│  ├─ alamat                                              │
│  ├─ foto                                                │
│  └─ tanggal_bergabung                                   │
│                                                          │
│  buku (book catalog)                                    │
│  ├─ id_buku (PK)                                        │
│  ├─ judul_buku                                          │
│  ├─ pengarang                                           │
│  ├─ penerbit                                            │
│  ├─ tahun_terbit                                        │
│  ├─ id_kategori (FK → kategori)                         │
│  ├─ deskripsi                                           │
│  ├─ cover (image path)                                  │
│  └─ status: 'tersedia' | 'dipinjam'                     │
│                                                          │
│  kategori (book categories)                             │
│  ├─ id_kategori (PK)                                    │
│  └─ nama_kategori                                       │
│                                                          │
│  transaksi (loan transactions) - CORE TABLE            │
│  ├─ id_transaksi (PK)                                   │
│  ├─ id_anggota (FK → anggota)                           │
│  ├─ id_buku (FK → buku)                                 │
│  ├─ tgl_pinjam                                          │
│  ├─ tgl_kembali_rencana (return deadline)               │
│  ├─ tgl_kembali_aktual                                  │
│  └─ status_transaksi: 'Pending'|'Peminjaman'|...        │
│                                                          │
│  denda (fines for late returns)                         │
│  ├─ id_denda (PK)                                       │
│  ├─ id_transaksi (FK → transaksi)                       │
│  ├─ nominal_denda                                       │
│  ├─ tanggal_denda                                       │
│  └─ status: 'belum_dibayar' | 'sudah_dibayar'           │
│                                                          │
│  ulasan (book reviews)                                  │
│  ├─ id_ulasan (PK)                                      │
│  ├─ id_buku (FK → buku)                                 │
│  ├─ id_anggota (FK → anggota)                           │
│  ├─ rating (1-5 stars)                                  │
│  ├─ isi_ulasan                                          │
│  └─ tanggal_ulasan                                      │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

---

## 🔌 API Documentation (AJAX Endpoints)

### **Endpoint: GET /api_search.php**

Autocomplete search untuk buku di katalog.

**Deskripsi:**  
Return JSON array dengan results buku yang match query keyword.

**Request:**
```http
GET /api_search.php?q=harry&limit=10

Query Parameters:
- q (string, required): Search keyword (judul/pengarang)
- limit (int, optional): Max results (default: 10)
```

**Response (Success):**
```json
{
  "success": true,
  "results": [
    {
      "id_buku": 1,
      "judul_buku": "Harry Potter Philosopher's Stone",
      "pengarang": "J.K. Rowling",
      "cover": "uploads/cover/book1.jpg"
    },
    {
      "id_buku": 2,
      "judul_buku": "Harry Potter Chamber of Secrets",
      "pengarang": "J.K. Rowling",
      "cover": "uploads/cover/book2.jpg"
    }
  ]
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Invalid search query"
}
```

**Status Codes:**
- `200 OK` - Search berhasil
- `400 Bad Request` - Query parameter invalid
- `500 Internal Server Error` - Database error

---

## 🧪 Testing Guide

### ✅ Manual Testing Checklist

#### **1️⃣ Authentication Flow**

- [ ] Register new anggota
  - [ ] Validate email format
  - [ ] Validate username uniqueness
  - [ ] Check password confirmation
  - [ ] Upload photo (optional)

- [ ] Login dengan berbagai roles
  - [ ] Admin login → redirect admin/dashboard.php
  - [ ] Petugas login → redirect petugas/dashboard.php
  - [ ] Anggota login → redirect anggota/dashboard.php
  - [ ] Invalid credentials → show error

- [ ] Logout
  - [ ] Clear session
  - [ ] Redirect to login.php
  - [ ] Cannot access protected pages tanpa login

#### **2️⃣ Book Loan Workflow**

- [ ] Anggota browse katalog
  - [ ] Display all books correctly
  - [ ] Search functionality works
  - [ ] Filter by category works
  - [ ] Pagination works

- [ ] Request loan
  - [ ] Form validation works
  - [ ] Status='Pending' created
  - [ ] Book status updated to 'dipinjam'
  - [ ] Shown in riwayat.php

- [ ] Admin approval
  - [ ] Pending requests shown in admin/transaksi.php
  - [ ] Click approve → status='Peminjaman'
  - [ ] Click reject → status='Ditolak'
  - [ ] Notification sent/shown to member

- [ ] Return book
  - [ ] Member request return via kembali.php
  - [ ] Status changed to 'Pengembalian'
  - [ ] Petugas validate in petugas/transaksi.php
  - [ ] Click confirm → status='Dikembalikan'
  - [ ] Book status back to 'tersedia'

#### **3️⃣ Fine Calculation**

- [ ] Late fine auto-applies
  - [ ] If return late → fine calculated
  - [ ] Fine = days_late × Rp1.000
  - [ ] Fine shown in denda.php
  - [ ] PayableStatus: 'belum_dibayar' → 'sudah_dibayar'

- [ ] Member see fines
  - [ ] Shown in anggota/riwayat.php
  - [ ] Shown in anggota/profil.php
  - [ ] Cannot borrow until paid

#### **4️⃣ Admin/Petugas Management**

- [ ] CRUD Operations
  - [ ] Create new book/anggota/user
  - [ ] Read list with pagination
  - [ ] Edit existing records
  - [ ] Delete with confirmation

- [ ] Responsive Design
  - [ ] Desktop: Full layout
  - [ ] Tablet: Adjusted columns
  - [ ] Mobile: Stack vertically, hamburger menu

### 🧪 Automated Testing (Unit Tests)

```bash
# Jika ingin implement PHPUnit tests:

mkdir tests/
touch tests/UserTest.php
touch tests/BookTest.php
touch tests/TransactionTest.php

# Run tests:
./vendor/bin/phpunit tests/
```

---

## 📦 Dependencies Breakdown

### 🔧 Core PHP Extensions

| Dependency | Fungsi | Digunakan Di |
|------------|--------|-------------|
| **MySQLi** (built-in) | Database connection & queries | config/database.php |
| **Sessions** (built-in) | User authentication & state | includes/session.php |
| **File Upload** (built-in) | Image upload handling | register.php, petugas/buku.php |
| **Date/Time** (built-in) | Calculate deadlines & fines | admin/transaksi.php, denda.php |

### 🎨 Frontend Libraries

| Library | Version | Fungsi | Digunakan Di |
|---------|---------|--------|-------------|
| **Font Awesome** | 6.x | Icons (check, x, clock, dll) | template files |
| **Inter Font** | - | Typography | assets/css/style.css |
| **Plus Jakarta Sans** | - | Headlines | header.php |

### 🔒 Security Dependencies

**Saat ini:**
- Session-based auth (native PHP)
- MySQLi prepared statements (prevent SQL injection)

**Recommended untuk future:**
- `bcrypt` untuk password hashing: `password_hash($pwd, PASSWORD_BCRYPT)`
- `CSRF tokens` untuk form protection
- `Content Security Policy` headers

---

## ⚠️ Common Issues & Troubleshooting

### ❌ Error: "Koneksi database gagal"

**Penyebab:**
- MySQL service tidak running
- Database credentials salah di `config/database.php`
- Database `perpus_30` belum dibuat

**Solusi:**
```bash
# 1. Check MySQL status
# XAMPP Control Panel → MySQL status = "Running"

# 2. Verify database exists
mysql -u root
SHOW DATABASES;  # Lihat apakah perpus_30 ada

# 3. Create jika belum ada
CREATE DATABASE perpus_30 CHARACTER SET utf8mb4;

# 4. Edit config/database.php dengan credentials benar
# Simpan & refresh browser
```

---

### ❌ Error: "Headers already sent"

**Penyebab:**
- `session_start()` dipanggil setelah ada output (HTML/echo)
- BOM (Byte Order Mark) di PHP file
- Whitespace sebelum `<?php`

**Solusi:**
```php
// File harus dimulai dengan: <?php (tanpa space)
<?php
// Immediately call session
session_start();

// Baru render HTML
?>
```

---

### ❌ Login gagal (invalid credentials)

**Penyebab:**
- Username/password typo
- Database record tidak ada
- Password encrypted tapi code check plaintext

**Solusi:**
```php
// Check database record ada:
mysql -u root perpus_30
SELECT * FROM pengguna WHERE username='admin';
SELECT * FROM anggota WHERE username='john_doe';

// Verify password match:
echo "Password: " . $_POST['password'];  // Debug only
```

---

### ❌ Image/Cover tidak muncul

**Penyebab:**
- File path salah (../ mismatch)
- Folder permissions 777 belum set
- File format tidak didukung

**Solusi:**
```bash
# Set folder permissions
chmod -R 777 uploads/

# Check kalo file exist di folder
ls uploads/cover/
ls uploads/foto_anggota/

# Update file path di database jika perlu
# Pastikan path start dengan "uploads/" bukan "../uploads/"
```

---

### ❌ Fine tidak auto-calculate

**Penyebab:**
- Date comparison logic error
- Query WHERE condition salah
- DENDA_PER_HARI constant not defined

**Solusi:**
```php
// Verify constant exist:
var_dump(DENDA_PER_HARI);  // Should output: 1000

// Manual fine calculation (debug):
$late_days = datediff(today, return_deadline);
if ($late_days > 0) {
  $fine = $late_days * DENDA_PER_HARI;
  echo "Days late: $late_days, Fine: Rp" . $fine;
}
```

---

### ❌ Session / Login tidak persist

**Penyebab:**
- Session timeout (default 24 min)
- Browser tidak accept cookies
- Session file permission issue

**Solusi:**
```php
// Set session timeout lebih lama
session_set_cookie_params([
  'lifetime' => 86400,  // 24 hours
  'path' => '/',
  'samesite' => 'Lax'
]);

// Check cookies enabled di browser:
// Open DevTools → Application → Cookies

// Check session file exist:
// C:\xampp\tmp\sess_* files
```

---

