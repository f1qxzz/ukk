<?php /* anggota/includes/nav.php */
$cp = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">📚</div>
        <div>
            <div class="brand-name">Aetheria Library</div>
            <div class="brand-role">ANGGOTA</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-section-label">UTAMA</span>
        <a href="dashboard.php" class="nav-link <?= $cp === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <span class="nav-section-label">KATALOG</span>
        <a href="katalog.php" class="nav-link <?= $cp === 'katalog.php' ? 'active' : '' ?>">
            <i class="fas fa-search"></i>
            <span>Katalog Buku</span>
        </a>

        <span class="nav-section-label">TRANSAKSI</span>
        <a href="pinjam.php" class="nav-link <?= $cp === 'pinjam.php' ? 'active' : '' ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Pinjam Buku</span>
        </a>
        <a href="kembali.php" class="nav-link <?= $cp === 'kembali.php' ? 'active' : '' ?>">
            <i class="fas fa-undo-alt"></i>
            <span>Kembalikan Buku</span>
        </a>

        <span class="nav-section-label">RIWAYAT</span>
        <a href="riwayat.php" class="nav-link <?= $cp === 'riwayat.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i>
            <span>Riwayat Pinjam</span>
        </a>
        <a href="ulasan.php" class="nav-link <?= $cp === 'ulasan.php' ? 'active' : '' ?>">
            <i class="fas fa-star"></i>
            <span>Ulasan Buku</span>
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
/* ===== SIDEBAR STYLES ===== */
.sidebar {
    width: 280px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-right: 1px solid rgba(255, 255, 255, 0.5);
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 20;
    box-shadow: 0 8px 24px rgba(142, 126, 150, 0.15);
    transition: transform 0.3s ease;
}

.sidebar-brand {
    padding: 28px 24px;
    border-bottom: 1px solid rgba(158, 142, 168, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.5);
}

.brand-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #9b8c9c, #b8a9c9);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    box-shadow: 0 6px 12px rgba(146, 115, 156, 0.2);
}

.brand-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #332e3a;
    line-height: 1.3;
}

.brand-role {
    font-size: 0.7rem;
    color: #9b8c9c;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

.sidebar-nav {
    flex: 1;
    padding: 20px 16px;
    overflow-y: auto;
}

.nav-section-label {
    display: block;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #b8b0c0;
    margin: 24px 0 8px 12px;
}

.nav-section-label:first-of-type {
    margin-top: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    color: #6b6475;
    text-decoration: none;
    transition: all 0.3s ease;
    margin-bottom: 2px;
    font-weight: 500;
    font-size: 0.9rem;
}

.nav-link i {
    width: 20px;
    font-size: 1rem;
    color: #b8b0c0;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: rgba(181, 138, 186, 0.1);
    color: #9b8c9c;
}

.nav-link:hover i {
    color: #9b8c9c;
}

.nav-link.active {
    background: rgba(181, 138, 186, 0.15);
    color: #9b8c9c;
    font-weight: 600;
}

.nav-link.active i {
    color: #9b8c9c;
}

.nav-link.logout {
    margin-top: 20px;
    border-top: 1px solid rgba(158, 142, 168, 0.15);
    padding-top: 20px;
    color: #c2556b;
}

.nav-link.logout i {
    color: #c2556b;
}

.nav-link.logout:hover {
    background: #fde9ec;
}

.sidebar-foot {
    padding: 20px 16px;
    border-top: 1px solid rgba(158, 142, 168, 0.15);
}

/* Sidebar Overlay */
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

/* Responsive */
@media (max-width: 900px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.open {
        transform: translateX(0);
    }
}

/* Scrollbar */
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 10px;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: #b5a7b6;
    border-radius: 10px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: #9b8c9c;
}
</style>

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            this.classList.remove('show');
        });
    }

    // Close sidebar with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        }
    });
});
</script>