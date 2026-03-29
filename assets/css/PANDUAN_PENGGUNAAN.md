# 📋 Panduan Penggunaan CSS — Perpustakaan Digital

## Struktur Folder CSS

```
assets/css/
├── style.css              ← Global base (wajib di semua halaman)
├── index.css              ← Halaman landing/beranda
├── login.css              ← Halaman login
├── register.css           ← Halaman register
├── print.css              ← Untuk cetak
│
├── admin/
│   ├── main.css           ← Variabel warna #561C24, reset, typography
│   ├── layout.css         ← Sidebar, topbar, app-wrap
│   ├── components.css     ← Card, button, table, badge, form, modal
│   └── pages.css          ← Style khusus: dashboard, buku, transaksi, dll
│
├── petugas/
│   ├── main.css           ← Variabel warna #6D2932, reset, typography
│   ├── layout.css         ← Sidebar, topbar, app-wrap
│   ├── components.css     ← Card, button, table, badge, form, modal
│   └── pages.css          ← Style khusus: dashboard, buku, transaksi, dll
│
└── anggota/
    ├── main.css           ← Variabel warna #C7B7A3, reset, typography
    ├── layout.css         ← Sidebar, topbar, app-wrap (glassmorphism)
    ├── components.css     ← Card, button, table, badge, form, modal
    └── pages.css          ← Style khusus: katalog, pinjam, riwayat, dll
```

---

## 🔗 Cara Penggunaan Link CSS

### File berada di root (misal: `admin/dashboard.php`)
```html
<link rel="stylesheet" href="../assets/css/admin/main.css">
<link rel="stylesheet" href="../assets/css/admin/layout.css">
<link rel="stylesheet" href="../assets/css/admin/components.css">
<link rel="stylesheet" href="../assets/css/admin/pages.css">
```

### File berada di root langsung (misal: `index.php`)
```html
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/index.css">
```

---

## 📄 Template per Role

### ADMIN (halaman dalam folder `admin/`)
```html
<head>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/admin/main.css">
  <link rel="stylesheet" href="../assets/css/admin/layout.css">
  <link rel="stylesheet" href="../assets/css/admin/components.css">
  <link rel="stylesheet" href="../assets/css/admin/pages.css">
</head>
```

### PETUGAS (halaman dalam folder `petugas/`)
```html
<head>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/petugas/main.css">
  <link rel="stylesheet" href="../assets/css/petugas/layout.css">
  <link rel="stylesheet" href="../assets/css/petugas/components.css">
  <link rel="stylesheet" href="../assets/css/petugas/pages.css">
</head>
```

### ANGGOTA (halaman dalam folder `anggota/`)
```html
<head>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/anggota/main.css">
  <link rel="stylesheet" href="../assets/css/anggota/layout.css">
  <link rel="stylesheet" href="../assets/css/anggota/components.css">
  <link rel="stylesheet" href="../assets/css/anggota/pages.css">
</head>
```

### HALAMAN PUBLIK (index, login, register — di root)
```html
<head>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/login.css">       <!-- atau index.css / register.css -->
</head>
```

---

## 🎨 Palet Warna per Role

| Role    | Warna Utama | Hex       |
|---------|-------------|-----------|
| Admin   | Maroon Gelap | `#561C24` |
| Petugas | Maroon Petugas | `#6D2932` |
| Anggota | Beige/Lavender | `#C7B7A3` |

---

## 📦 CSS Variables Utama (semua role)

```css
--primary          /* Warna utama role */
--primary-dark     /* Lebih gelap */
--primary-mid      /* Tengah */
--primary-light    /* Lebih terang */
--primary-soft     /* Background ringan */
--bg-base          /* Background halaman */
--bg-surface       /* Background card/putih */
--bg-card          /* Card dengan opacity */
--sidebar-w        /* Lebar sidebar: 260px */
--topbar-h         /* Tinggi topbar: 64px */
--transition       /* Animasi default */
--font-sans        /* DM Sans */
--font-display     /* Plus Jakarta Sans */
--shadow-sm/md/lg  /* Shadow bertahap */
--radius-sm/lg/xl  /* Border radius */
```

---

## ✅ Class Reusable Penting

### Layout
- `.app-wrap` — flex wrapper utama
- `.main-area` — area konten (kanan sidebar)
- `.content` — padding konten dalam
- `.sidebar` — sidebar navigasi
- `.topbar` — header sticky

### Komponen
- `.card`, `.card-header`, `.card-body` — card container
- `.stat-card`, `.stats-grid` — kartu statistik
- `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-sm` — tombol
- `.badge`, `.badge-success`, `.badge-danger`, `.badge-warning` — label
- `.form-control`, `.form-label`, `.form-group` — form
- `.filter-bar`, `.search-input` — filter dan pencarian
- `.modal-overlay`, `.modal`, `.modal-header` — modal dialog
- `.pagination`, `.page-btn` — pagination
- `.wb`, `.wb-avatar`, `.wb-name` — welcome box
- `.action-btn.edit`, `.action-btn.delete`, `.action-btn.view` — tombol aksi tabel
