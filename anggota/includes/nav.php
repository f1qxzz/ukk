<?php /* anggota/includes/nav.php */
$cp = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon anggota">🎓</div>
    <div class="brand-text">
      <div class="brand-name">Perpustakaan Digital</div>
      <div class="brand-role">Anggota</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <span class="nav-section-label">Utama</span>

    <a href="dashboard.php" class="nav-link role-anggota <?= $cp==='dashboard.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>

    <span class="nav-section-label">Perpustakaan</span>

    <a href="katalog.php" class="nav-link role-anggota <?= $cp==='katalog.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Katalog Buku
    </a>

    <a href="pinjam.php" class="nav-link role-anggota <?= $cp==='pinjam.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
      Pinjam Buku
    </a>

    <a href="kembali.php" class="nav-link role-anggota <?= $cp==='kembali.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
      Kembalikan
    </a>

    <span class="nav-section-label">Aktivitas</span>

    <a href="riwayat.php" class="nav-link role-anggota <?= $cp==='riwayat.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/><polyline points="3 3 3 7 7 7"/></svg>
      Riwayat Pinjam
    </a>

    <a href="ulasan.php" class="nav-link role-anggota <?= $cp==='ulasan.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Ulasan Saya
    </a>

    <span class="nav-section-label">Akun</span>

    <a href="profil.php" class="nav-link role-anggota <?= $cp==='profil.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profil Saya
    </a>

    <a href="../index.php" class="nav-link role-anggota">
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
