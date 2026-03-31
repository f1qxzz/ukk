<?php /* petugas/includes/nav.php */
$cp = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-overlay" onclick="this.classList.remove('show');document.querySelector('.sidebar').classList.remove('open')"></div>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-book-open"></i>
        </div>
        <div>
            <div class="brand-name">Aetheria Library</div>
            <div class="brand-role">Petugas</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-section-label">Utama</span>
        <a href="dashboard.php" class="nav-link <?= $cp==='dashboard.php'?'active':'' ?>">
            <i class="fas fa-th-large"></i><span>Dashboard</span>
        </a>

        <span class="nav-section-label">Manajemen</span>
        <a href="anggota.php" class="nav-link <?= $cp==='anggota.php'?'active':'' ?>">
            <i class="fas fa-user-graduate"></i><span>Anggota</span>
        </a>

        <span class="nav-section-label">Koleksi</span>
        <a href="kategori.php" class="nav-link <?= $cp==='kategori.php'?'active':'' ?>">
            <i class="fas fa-tags"></i><span>Kategori</span>
        </a>
        <a href="buku.php" class="nav-link <?= $cp==='buku.php'?'active':'' ?>">
            <i class="fas fa-book"></i><span>Buku</span>
        </a>

        <span class="nav-section-label">Transaksi</span>
        <a href="transaksi.php" class="nav-link <?= $cp==='transaksi.php'?'active':'' ?>">
            <i class="fas fa-exchange-alt"></i><span>Transaksi</span>
        </a>
        <a href="denda.php" class="nav-link <?= $cp==='denda.php'?'active':'' ?>">
            <i class="fas fa-coins"></i><span>Denda</span>
        </a>
        <a href="laporan.php" class="nav-link <?= $cp==='laporan.php'?'active':'' ?>">
            <i class="fas fa-chart-bar"></i><span>Laporan</span>
        </a>

        <span class="nav-section-label">Akun</span>
        <a href="profil.php" class="nav-link <?= $cp==='profil.php'?'active':'' ?>">
            <i class="fas fa-user-circle"></i><span>Profil Saya</span>
        </a>
        <a href="../index.php" class="nav-link">
            <i class="fas fa-globe"></i><span>Beranda</span>
        </a>
    </nav>

    <div class="sidebar-foot">
        <a href="logout.php" class="nav-link logout">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</aside>

<style>
/* ===== MODERN PETUGAS SIDEBAR — dark blended theme ===== */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    border-right: none;
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0; top: 0; bottom: 0;
    z-index: 20;
    box-shadow: 4px 0 24px rgba(0,0,0,0.25);
    transition: transform 0.3s ease;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(3px);
    z-index: 15;
}
.sidebar-overlay.show { display: block; }

/* Brand */
.sidebar-brand {
    padding: 22px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    display: flex;
    align-items: center;
    gap: 13px;
    background: rgba(255,255,255,0.03);
}
.brand-icon {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, #4f46e5 0%, #818cf8 100%);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: white;
    box-shadow: 0 4px 16px rgba(79,70,229,0.4);
    flex-shrink: 0;
}
.brand-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 800;
    color: #f1f5f9;
    line-height: 1.2;
}
.brand-role {
    font-size: 0.6rem;
    color: #6366f1;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    font-weight: 700;
    margin-top: 2px;
}

/* Nav */
.sidebar-nav {
    flex: 1;
    padding: 16px 12px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}
.sidebar-nav::-webkit-scrollbar { width: 4px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

.nav-section-label {
    display: block;
    font-size: 0.58rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: rgba(148,163,184,0.5);
    margin: 18px 0 5px 10px;
}
.nav-section-label:first-of-type { margin-top: 4px; }

.nav-link {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 14px;
    border-radius: 10px;
    color: #94a3b8;
    text-decoration: none;
    transition: all 0.22s ease;
    margin-bottom: 1px;
    font-weight: 500;
    font-size: 0.875rem;
    position: relative;
    overflow: hidden;
}
.nav-link::before {
    content: '';
    position: absolute;
    left: 0; top: 50%;
    transform: translateY(-50%) scaleY(0);
    width: 3px; height: 60%;
    background: #6366f1;
    border-radius: 0 3px 3px 0;
    transition: transform 0.2s ease;
}
.nav-link i {
    width: 20px;
    font-size: 0.95rem;
    color: #475569;
    transition: all 0.22s ease;
    flex-shrink: 0;
    text-align: center;
}
.nav-link:hover {
    background: rgba(99,102,241,0.12);
    color: #c7d2fe;
}
.nav-link:hover i { color: #818cf8; }
.nav-link:hover::before { transform: translateY(-50%) scaleY(1); }

.nav-link.active {
    background: linear-gradient(135deg, rgba(79,70,229,0.25) 0%, rgba(99,102,241,0.15) 100%);
    color: #e0e7ff;
    font-weight: 600;
    border: 1px solid rgba(99,102,241,0.25);
    box-shadow: 0 2px 12px rgba(79,70,229,0.15);
}
.nav-link.active i { color: #818cf8; }
.nav-link.active::before { transform: translateY(-50%) scaleY(1); }

/* Foot / Logout */
.sidebar-foot {
    padding: 8px 12px 18px;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.nav-link.logout {
    margin-top: 8px;
    color: #f87171;
    border-radius: 10px;
}
.nav-link.logout i { color: #ef4444; }
.nav-link.logout:hover {
    background: rgba(239,68,68,0.12);
    color: #fca5a5;
}
.nav-link.logout:hover i { color: #fca5a5; }
.nav-link.logout::before { background: #ef4444; }

@media (max-width: 900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
}
</style>
