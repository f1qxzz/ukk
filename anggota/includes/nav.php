<?php /* anggota/includes/nav.php */
$cp = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-overlay"></div>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon anggota">📖</div>
    <div>
      <div class="brand-name">Perpustakaan Digital</div>
      <div class="brand-role">Portal Anggota</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <span class="nav-section-label">Utama</span>
    <a href="dashboard.php" class="nav-link <?= $cp==='dashboard.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <span class="nav-section-label">Buku</span>
    <a href="katalog.php" class="nav-link <?= $cp==='katalog.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Katalog Buku
    </a>
    <a href="pinjam.php" class="nav-link <?= $cp==='pinjam.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
      Ajukan Peminjaman
    </a>
    <a href="kembali.php" class="nav-link <?= $cp==='kembali.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
      Kembalikan Buku
    </a>
    <span class="nav-section-label">Riwayat</span>
    <a href="riwayat.php" class="nav-link <?= $cp==='riwayat.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/><polyline points="3 3 3 7 7 7"/></svg>
      Riwayat Pinjam
    </a>
    <a href="ulasan.php" class="nav-link <?= $cp==='ulasan.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Ulasan Buku
    </a>
    <span class="nav-section-label">Akun</span>
    <a href="profil.php" class="nav-link <?= $cp==='profil.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profil Saya
    </a>
    <a href="../index.php" class="nav-link">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Beranda
    </a>
  </nav>
  <div class="sidebar-foot">
    <a href="logout.php" class="nav-link logout">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>
