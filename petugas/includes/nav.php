<?php /* petugas/includes/nav.php */
$cp = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon petugas">📋</div>
    <div class="brand-text">
      <div class="brand-name">Perpustakaan Digital</div>
      <div class="brand-role">Petugas</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <span class="nav-section-label">Utama</span>

    <a href="dashboard.php" class="nav-link role-petugas <?= $cp==='dashboard.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>

    <span class="nav-section-label">Koleksi</span>

    <a href="kategori.php" class="nav-link role-petugas <?= $cp==='kategori.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      Kategori
    </a>

    <a href="buku.php" class="nav-link role-petugas <?= $cp==='buku.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Buku
    </a>

    <span class="nav-section-label">Anggota</span>

    <a href="anggota.php" class="nav-link role-petugas <?= $cp==='anggota.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Data Anggota
    </a>

    <span class="nav-section-label">Transaksi</span>

    <a href="transaksi.php" class="nav-link role-petugas <?= $cp==='transaksi.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Transaksi
    </a>

    <a href="denda.php" class="nav-link role-petugas <?= $cp==='denda.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Denda
    </a>

    <a href="laporan.php" class="nav-link role-petugas <?= $cp==='laporan.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Laporan
    </a>

    <span class="nav-section-label">Akun</span>

    <a href="profil.php" class="nav-link role-petugas <?= $cp==='profil.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profil Saya
    </a>

    <a href="../index.php" class="nav-link role-petugas">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
      Halaman Utama
    </a>
  </nav>

  <div class="sidebar-foot">
    <a href="logout.php" class="nav-link logout">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>
