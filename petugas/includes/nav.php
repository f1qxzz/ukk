<?php /* petugas/includes/nav.php */
$cp = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-overlay"></div>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">📚</div>
        <div>
            <div class="brand-name">Perpustakaan Digital</div>
            <div class="brand-role">PETUGAS</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-section-label">UTAMA</span>
        <a href="dashboard.php" class="nav-link <?= $cp === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <span class="nav-section-label">MANAJEMEN</span>
        <a href="anggota.php" class="nav-link <?= $cp === 'anggota.php' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i>
            <span>Anggota</span>
        </a>

        <span class="nav-section-label">KOLEKSI</span>
        <a href="kategori.php" class="nav-link <?= $cp === 'kategori.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i>
            <span>Kategori</span>
        </a>
        <a href="buku.php" class="nav-link <?= $cp === 'buku.php' ? 'active' : '' ?>">
            <i class="fas fa-book"></i>
            <span>Buku</span>
        </a>

        <span class="nav-section-label">TRANSAKSI</span>
        <a href="transaksi.php" class="nav-link <?= $cp === 'transaksi.php' ? 'active' : '' ?>">
            <i class="fas fa-exchange-alt"></i>
            <span>Transaksi</span>
        </a>
        <a href="denda.php" class="nav-link <?= $cp === 'denda.php' ? 'active' : '' ?>">
            <i class="fas fa-coins"></i>
            <span>Denda</span>
        </a>
        <a href="laporan.php" class="nav-link <?= $cp === 'laporan.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Laporan</span>
        </a>

        <span class="nav-section-label">AKUN</span>
        <a href="profil.php" class="nav-link <?= $cp === 'profil.php' ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span>Profil Saya</span>
        </a>
        <a href="../index.php" class="nav-link">
            <i class="fas fa-globe"></i>
            <span>Beranda</span>
        </a>
    </nav>

    <div class="sidebar-foot">
        <a href="logout.php" class="nav-link logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<style>
/* ===== SIDEBAR ===== */
.sidebar {
    width: 280px;
    background: #ffffff;
    border-right: 1px solid #e0e7ff;
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 20;
    box-shadow: 2px 0 20px rgba(99,102,241,.08), 4px 0 40px rgba(0,0,0,.04);
    transition: transform 0.3s ease;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(2px);
    z-index: 15;
}

.sidebar-overlay.show {
    display: block;
}

.sidebar-brand {
    padding: 24px 22px;
    border-bottom: 1px solid #eef2ff;
    display: flex;
    align-items: center;
    gap: 13px;
    background: #ffffff;
}

.brand-icon {
    width: 46px;
    height: 46px;
    background: linear-gradient(135deg, #6366f1 0%, #06b6d4 100%);
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: white;
    box-shadow: 0 4px 14px rgba(99,102,241,.3);
    flex-shrink: 0;
}

.brand-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #1e1b4b;
    line-height: 1.25;
}

.brand-role {
    font-size: 0.62rem;
    color: #a5b4fc;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 700;
    margin-top: 1px;
}

.sidebar-nav {
    flex: 1;
    padding: 18px 12px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.nav-section-label {
    display: block;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #c7d2fe;
    margin: 20px 0 6px 10px;
}

.nav-section-label:first-of-type {
    margin-top: 2px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 14px;
    border-radius: 11px;
    color: #6b7280;
    text-decoration: none;
    transition: all 0.25s ease;
    margin-bottom: 2px;
    font-weight: 500;
    font-size: 0.875rem;
}

.nav-link i {
    width: 20px;
    font-size: 1rem;
    color: #a5b4fc;
    transition: all 0.25s ease;
    flex-shrink: 0;
}

.nav-link:hover {
    background: #eef2ff;
    color: #4f46e5;
}

.nav-link:hover i {
    color: #6366f1;
}

.nav-link.active {
    background: linear-gradient(135deg, #eef2ff 0%, #e0f2fe 100%);
    color: #4338ca;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(99,102,241,.12);
    border: 1px solid #c7d2fe;
}

.nav-link.active i {
    color: #6366f1;
}

.nav-link.logout {
    margin-top: 16px;
    border-top: 1px solid #eef2ff;
    padding-top: 16px;
    color: #f87171;
    border-radius: 0;
}

.nav-link.logout i {
    color: #fca5a5;
}

.nav-link.logout:hover {
    background: #fff1f2;
    color: #ef4444;
    border-radius: 11px;
}

.nav-link.logout:hover i {
    color: #ef4444;
}

.sidebar-foot {
    padding: 8px 12px 20px;
    border-top: 1px solid #eef2ff;
}

@media (max-width: 900px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.open {
        transform: translateX(0);
    }
}
</style>