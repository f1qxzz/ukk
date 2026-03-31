<?php /* admin/includes/header.php */
$page_title = $page_title ?? 'Dashboard';
$page_sub   = $page_sub   ?? 'Admin Panel · Cozy-Library';

// Ambil data pengguna untuk foto profil
require_once dirname(__DIR__, 2) . '/config/database.php';
$conn = getConnection();
$userId = getPenggunaId();
$stmt = $conn->prepare("SELECT foto, nama_pengguna FROM pengguna WHERE id_pengguna = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Inisial untuk fallback avatar
$initials = '';
$nama = $userData['nama_pengguna'] ?? getPenggunaName();
foreach (explode(' ', trim($nama)) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}

// Cek apakah foto tersedia
$fotoPath = (!empty($userData['foto']) && file_exists(dirname(__DIR__, 2) . '/' . $userData['foto'])) 
            ? '../' . htmlspecialchars($userData['foto']) 
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
    
    <div class="topbar-right modern-topbar">
        <div class="topbar-date modern-date">
            <svg viewBox="0 0 24 24" class="icon-sm">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <?php date_default_timezone_set('Asia/Jakarta'); echo date('d M Y'); ?>
        </div>
        
        <div class="topbar-user modern-user">
            <?php if ($fotoPath): ?>
            <div class="topbar-avatar">
                <img src="<?= $fotoPath ?>" alt="Foto Profil" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            </div>
            <?php else: ?>
            <div class="topbar-avatar admin"><?= $initials ?></div>
            <?php endif; ?>
            <span class="topbar-username"><?= htmlspecialchars(getPenggunaName()) ?></span>
        </div>
        
        <a href="logout.php" class="modern-btn-logout no-print">
            <svg viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            <span>Logout</span>
        </a>
    </div>
</header>

<style>
/* ── PENYESUAIAN LAYOUT TOPBAR KANAN ── */
.modern-topbar {
    display: flex;
    align-items: center;
    gap: 16px; /* Jarak yang konsisten antar elemen */
}

/* ── DESAIN TANGGAL MODERN (KAPSUL) ── */
.modern-date {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f3e8ff; /* Soft purple */
    color: #6d28d9; /* Deep purple */
    padding: 6px 14px;
    border-radius: 999px; /* Bentuk kapsul sempurna */
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.modern-date .icon-sm {
    width: 14px;
    height: 14px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* ── DESAIN USER INFO ── */
.modern-user {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 12px 4px 4px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
}

/* Style untuk avatar di header */
.topbar-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    margin-right: 0 !important; /* Reset margin bawaan */
}

.topbar-avatar.admin {
    background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
    color: white;
    box-shadow: 0 2px 5px rgba(168,85,247,0.3);
}

.topbar-username {
    font-weight: 600;
    font-size: 0.85rem;
    color: #334155;
}

/* ── DESAIN TOMBOL LOGOUT MODERN ── */
.modern-btn-logout {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #fff1f2; /* Soft red */
    color: #e11d48; /* Red */
    padding: 8px 16px;
    border-radius: 999px; /* Bentuk kapsul */
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.modern-btn-logout:hover {
    background: #ffe4e6;
    border-color: #fecdd3;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.1);
}

.modern-btn-logout svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* Responsif untuk layar kecil */
@media (max-width: 768px) {
    .modern-date {
        display: none; /* Sembunyikan tanggal di HP agar tidak penuh */
    }
    .modern-user {
        border: none;
        background: transparent;
        padding: 0;
    }
    .topbar-username {
        display: none; /* Sembunyikan nama di HP */
    }
    .modern-btn-logout span {
        display: none; /* Hanya tampilkan icon logout di HP */
    }
    .modern-btn-logout {
        padding: 8px;
    }
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
}
</style>