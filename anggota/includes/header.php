<?php /* anggota/includes/header.php */
$page_title = $page_title ?? 'Dashboard';
$page_sub   = $page_sub   ?? 'Portal Anggota · Aetheria Library';

// Ambil data anggota untuk foto profil
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';
$conn = getConnection();
$anggotaId = getAnggotaId();
$stmt = $conn->prepare("SELECT foto, nama_anggota FROM anggota WHERE id_anggota = ?");
$stmt->bind_param("i", $anggotaId);
$stmt->execute();
$anggotaData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Inisial untuk fallback avatar
$initials = '';
$nama = $anggotaData['nama_anggota'] ?? getAnggotaName();
foreach (explode(' ', trim($nama)) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}

// Cek apakah foto tersedia
$fotoPath = (!empty($anggotaData['foto']) && file_exists(dirname(__DIR__, 2) . '/' . $anggotaData['foto'])) 
            ? '../' . htmlspecialchars($anggotaData['foto']) 
            : null;
?>
<header class="topbar no-print">
    <div class="topbar-left">
        <button class="sidebar-toggle"
            onclick="document.querySelector('.sidebar').classList.toggle('open');document.querySelector('.sidebar-overlay').classList.toggle('show')">
            <svg viewBox="0 0 24 24">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <div>
            <div class="page-title"><?= htmlspecialchars($page_title) ?></div>
            <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-date">
            <?php date_default_timezone_set('Asia/Jakarta'); echo date('d M Y'); ?>
        </div>
        <div class="topbar-user">
            <?php if ($fotoPath): ?>
            <div class="topbar-avatar">
                <img src="<?= $fotoPath ?>" alt="Foto Profil"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            </div>
            <?php else: ?>
            <div class="topbar-avatar anggota"><?= $initials ?></div>
            <?php endif; ?>
            <span class="topbar-username"><?= htmlspecialchars(getAnggotaName()) ?></span>
        </div>
        <a href="logout.php" class="btn btn-ghost btn-sm no-print" style="color:var(--danger)">
            <svg viewBox="0 0 24 24"
                style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            Logout
        </a>
    </div>
</header>

<style>
/* Style untuk avatar di header anggota */
.topbar-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    margin-right: 8px;
}

.topbar-avatar.anggota {
    background: linear-gradient(135deg, #2c4f7c 0%, #3a6ea5 100%);
    color: white;
}

.topbar-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* GLOBAL RESPONSIVE LAYOUT (semua role) */
.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: none;
    opacity: 0;
    z-index: 999;
    transition: opacity .25s ease;
}
.sidebar-overlay.show {
    display: block;
    opacity: 1;
}

@media (max-width: 1024px) {
    .sidebar {
        position: fixed !important;
        top: 0;
        left: 0;
        transform: translateX(-100%) !important;
        width: 280px !important;
        height: 100vh !important;
        z-index: 1000 !important;
        box-shadow: 0 10px 28px rgba(0,0,0,0.2) !important;
    }

    .sidebar.open {
        transform: translateX(0) !important;
    }

    .main-area {
        margin-left: 0 !important;
    }

    .content {
        padding: 16px !important;
    }

    .topbar {
        padding: 12px 14px !important;
    }

    .page-title {
        font-size: 1rem !important;
    }

    .page-breadcrumb {
        font-size: 0.74rem !important;
    }

    .topbar-right {
        gap: 10px !important;
    }

    .stats-grid,
    .srow,
    .rpt-stats {
        grid-template-columns: repeat(2, 1fr) !important;
    }

    .rpt-table,
    .table-responsive {
        width: 100% !important;
        overflow-x: auto !important;
    }

    .report-document,
    .report-container {
        margin: 12px !important;
        padding: 0 !important;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 100% !important;
    }

    .sidebar-toggle {
        display: inline-flex !important;
    }

    .topbar {
        flex-wrap: wrap !important;
        justify-content: space-between !important;
    }

    .topbar-left,
    .topbar-right {
        width: 100% !important;
        justify-content: space-between !important;
    }

    .topbar-date,
    .topbar-user .topbar-username {
        display: none !important;
    }

    .btn-logout,
    .modern-btn-logout {
        padding: 8px 10px !important;
        font-size: 0.75rem !important;
    }

    .stats-grid,
    .srow,
    .rpt-stats {
        grid-template-columns: 1fr !important;
    }

    .wb,
    .page-header,
    .rpt-letterhead,
    .rpt-title-band {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 8px !important;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 0.92rem !important;
    }

    .nav-link,
    .topbar-date,
    .topbar-user,
    .modern-btn-logout,
    .btn-logout {
        font-size: 0.8rem !important;
    }

    .content {
        padding: 12px !important;
    }

    .rpt-table th,
    .rpt-table td {
        padding: 8px !important;
        font-size: 0.75rem !important;
    }

    .rpt-stats,
    .stat-card,
    .sc {
        padding: 10px !important;
    }
}
</style>

<script>
// Toggle sidebar from hamburger button (fallback if script.js belum dipanggil)
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('show');
    });

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
        }
    });
});
</script>