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
            <div class="topbar-avatar petugas"><?= $initials ?></div>
            <?php endif; ?>
            <span class="topbar-username"><?= htmlspecialchars(getPenggunaName()) ?></span>
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
/* Style untuk avatar di header petugas */
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

.topbar-avatar.petugas {
    background: linear-gradient(135deg, #2c4f7c 0%, #3a6ea5 100%);
    color: white;
}

.topbar-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>