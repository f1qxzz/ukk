<?php /* petugas/includes/header.php */
$page_title = $page_title ?? 'Dashboard';
$page_sub   = $page_sub   ?? 'Panel Petugas · Perpustakaan Digital';

// Ambil data pengguna untuk foto profil
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';
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
            <svg viewBox="0 0 24 24" width="20" height="20">
                <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" />
                <line x1="3" y1="12" x2="21" y2="12" stroke="currentColor" stroke-width="2" />
                <line x1="3" y1="18" x2="21" y2="18" stroke="currentColor" stroke-width="2" />
            </svg>
        </button>
        <div>
            <div class="page-title"><?= htmlspecialchars($page_title) ?></div>
            <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-date">
            <i class="far fa-calendar-alt"></i> <?= date('d M Y') ?>
        </div>
        <div class="topbar-user">
            <?php if ($fotoPath): ?>
            <div class="topbar-avatar">
                <img src="<?= $fotoPath ?>" alt="Foto Profil">
            </div>
            <?php else: ?>
            <div class="topbar-avatar petugas"><?= $initials ?></div>
            <?php endif; ?>
            <span class="topbar-username"><?= htmlspecialchars(getPenggunaName()) ?></span>
        </div>
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</header>

<style>
/* Header Styles */
.topbar {
    background: white;
    padding: 16px 24px;
    border-bottom: 1px solid var(--neutral-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: var(--shadow-sm);
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.sidebar-toggle {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--neutral-600);
    display: none;
    padding: 4px;
}

.sidebar-toggle svg {
    width: 24px;
    height: 24px;
}

@media (max-width: 768px) {
    .sidebar-toggle {
        display: block;
    }
}

.page-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--neutral-900);
    margin-bottom: 2px;
}

.page-breadcrumb {
    font-size: 0.8rem;
    color: var(--neutral-500);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.topbar-date {
    font-size: 0.9rem;
    color: var(--neutral-600);
    background: var(--neutral-100);
    padding: 8px 16px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    gap: 8px;
}

.topbar-date i {
    color: var(--petugas-500);
}

.topbar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 12px 4px 4px;
    background: var(--neutral-100);
    border-radius: var(--radius-full);
}

.topbar-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--petugas-500), var(--petugas-600));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    overflow: hidden;
}

.topbar-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.topbar-username {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--neutral-700);
}

.btn-logout {
    background: var(--danger-50);
    color: var(--danger-600);
    border: none;
    border-radius: var(--radius-full);
    padding: 8px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: var(--transition);
}

.btn-logout:hover {
    background: var(--danger-100);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

@media (max-width: 768px) {
    .topbar-date span {
        display: none;
    }

    .topbar-date i {
        margin: 0;
    }

    .btn-logout span {
        display: none;
    }

    .btn-logout {
        padding: 8px;
    }
}
</style>