<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

$conn = getConnection();

// Ambil data user untuk header
$userId = getPenggunaId();
$userStmt = $conn->prepare("SELECT foto, nama_pengguna FROM pengguna WHERE id_pengguna = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Inisial untuk avatar
$initials = '';
foreach (explode(' ', trim($userData['nama_pengguna'] ?? getPenggunaName())) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}
$fotoPath = (!empty($userData['foto']) && file_exists('../' . $userData['foto'])) 
            ? '../' . htmlspecialchars($userData['foto']) 
            : null;

// Fungsi untuk menghitung data
function cnt($c, $q, $f = 'c') {
    return $c->query($q)->fetch_assoc()[$f] ?? 0;
}

$tb = cnt($conn, "SELECT COUNT(*) c FROM buku");
$ts = cnt($conn, "SELECT COUNT(*) c FROM buku WHERE status='tersedia'");
$ap = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'");
$ta = cnt($conn, "SELECT COUNT(*) c FROM anggota");
$td = cnt($conn, "SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'", 's');
$tl = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < NOW()");
$tp = cnt($conn, "SELECT COUNT(*) c FROM pengguna");
$kh = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Pengembalian' AND DATE(tgl_kembali_aktual) = CURDATE()");

$rows = $conn->query("SELECT t.*, a.nama_anggota, b.judul_buku, b.cover 
                      FROM transaksi t 
                      JOIN anggota a ON t.id_anggota = a.id_anggota 
                      JOIN buku b ON t.id_buku = b.id_buku 
                      ORDER BY t.tgl_pinjam DESC LIMIT 8");

$page_title = 'Dashboard';
$page_sub = 'Admin Panel · Perpustakaan Digital';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-50: #eef2ff;
        --primary-100: #e0e7ff;
        --primary-200: #c7d2fe;
        --primary-300: #a5b4fc;
        --primary-400: #818cf8;
        --primary-500: #6366f1;
        --primary-600: #4f46e5;
        --primary-700: #4338ca;
        --primary-800: #3730a3;
        --primary-900: #312e81;

        --neutral-50: #f9fafb;
        --neutral-100: #f3f4f6;
        --neutral-200: #e5e7eb;
        --neutral-300: #d1d5db;
        --neutral-400: #9ca3af;
        --neutral-500: #6b7280;
        --neutral-600: #4b5563;
        --neutral-700: #374151;
        --neutral-800: #1f2937;
        --neutral-900: #111827;

        --success-50: #ecfdf5;
        --success-500: #10b981;
        --success-600: #059669;

        --warning-50: #fffbeb;
        --warning-500: #f59e0b;
        --warning-600: #d97706;

        --danger-50: #fef2f2;
        --danger-500: #ef4444;
        --danger-600: #dc2626;

        --info-50: #eff6ff;
        --info-500: #3b82f6;

        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);

        --radius-sm: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
        --radius-2xl: 1.5rem;
        --radius-3xl: 2rem;
        --radius-full: 9999px;

        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .app-wrap {
        display: flex;
        min-height: 100vh;
    }

    /* ===== SIDEBAR ===== */
    .sidebar {
        width: 280px;
        background: white;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        position: relative;
        z-index: 10;
    }

    .sidebar-brand {
        padding: 24px;
        border-bottom: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .brand-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
    }

    .brand-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--neutral-900);
        line-height: 1.3;
    }

    .brand-role {
        font-size: 0.7rem;
        color: var(--neutral-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
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
        color: var(--neutral-400);
        margin: 20px 0 8px 12px;
    }

    .nav-section-label:first-of-type {
        margin-top: 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: var(--radius-lg);
        color: var(--neutral-600);
        text-decoration: none;
        transition: var(--transition);
        margin-bottom: 2px;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .nav-link i {
        width: 20px;
        font-size: 1rem;
        color: var(--neutral-400);
        transition: var(--transition);
    }

    .nav-link:hover {
        background: var(--primary-50);
        color: var(--primary-600);
    }

    .nav-link:hover i {
        color: var(--primary-500);
    }

    .nav-link.active {
        background: var(--primary-50);
        color: var(--primary-700);
        font-weight: 600;
    }

    .nav-link.active i {
        color: var(--primary-600);
    }

    .nav-link.logout {
        margin-top: 20px;
        border-top: 1px solid var(--neutral-200);
        padding-top: 20px;
        color: var(--danger-500);
    }

    .nav-link.logout i {
        color: var(--danger-400);
    }

    .nav-link.logout:hover {
        background: var(--danger-50);
    }

    .sidebar-foot {
        padding: 20px 16px;
        border-top: 1px solid var(--neutral-200);
    }

    /* ===== MAIN AREA ===== */
    .main-area {
        flex: 1;
        background: var(--neutral-50);
        display: flex;
        flex-direction: column;
    }

    /* Header */
    .topbar {
        background: white;
        padding: 16px 24px;
        border-bottom: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow-sm);
    }

    .page-info {
        display: flex;
        flex-direction: column;
    }

    .page-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--neutral-900);
        margin-bottom: 4px;
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
        padding: 6px 12px;
        border-radius: var(--radius-full);
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
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
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
    }

    /* Content */
    .content {
        padding: 24px;
        overflow-y: auto;
    }

    /* Welcome Box */
    .wb {
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
        border-radius: var(--radius-xl);
        padding: 24px;
        margin-bottom: 24px;
        color: white;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: var(--shadow-lg);
    }

    .wb-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .wb-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .wb-name {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .wb-desc {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .wb-actions {
        margin-left: auto;
        display: flex;
        gap: 12px;
    }

    .wb-btn1,
    .wb-btn2 {
        padding: 10px 20px;
        border-radius: var(--radius-lg);
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: var(--transition);
    }

    .wb-btn1 {
        background: white;
        color: var(--primary-700);
    }

    .wb-btn1:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .wb-btn2 {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .wb-btn2:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 20px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }

    .stat-info h3 {
        font-size: 0.85rem;
        color: var(--neutral-500);
        font-weight: 600;
        margin-bottom: 4px;
    }

    .stat-number {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        color: var(--neutral-900);
    }

    .stat-desc {
        font-size: 0.7rem;
        color: var(--neutral-500);
        margin-top: 4px;
    }

    .stat-desc.success {
        color: var(--success-600);
    }

    .stat-desc.warning {
        color: var(--warning-600);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-icon.blue {
        background: var(--primary-50);
        color: var(--primary-600);
    }

    .stat-icon.red {
        background: var(--danger-50);
        color: var(--danger-600);
    }

    .stat-icon.green {
        background: var(--success-50);
        color: var(--success-600);
    }

    .stat-icon.amber {
        background: var(--warning-50);
        color: var(--warning-600);
    }

    /* Recent Transactions */
    .recent-card {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        border: 1px solid var(--neutral-200);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .recent-header {
        padding: 20px;
        border-bottom: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .recent-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .recent-title i {
        font-size: 1.2rem;
        color: var(--primary-600);
    }

    .recent-title h2 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--neutral-800);
    }

    .recent-link {
        color: var(--primary-600);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: var(--transition);
    }

    .recent-link:hover {
        gap: 8px;
        color: var(--primary-700);
    }

    .table-responsive {
        overflow-x: auto;
        padding: 0 20px 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead tr {
        background: var(--neutral-50);
    }

    th {
        padding: 12px;
        text-align: left;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--neutral-600);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid var(--neutral-200);
        color: var(--neutral-700);
    }

    .book-cover-cell {
        width: 50px;
    }

    .cover-thumb {
        width: 40px;
        height: 55px;
        object-fit: cover;
        border-radius: var(--radius-sm);
        box-shadow: var(--shadow-sm);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: var(--radius-full);
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-badge.success {
        background: var(--success-50);
        color: var(--success-600);
    }

    .status-badge.danger {
        background: var(--danger-50);
        color: var(--danger-600);
    }

    .status-badge.warning {
        background: var(--warning-50);
        color: var(--warning-600);
    }

    /* Mini Stats */
    .mini-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .mini-card {
        background: white;
        border-radius: var(--radius-lg);
        padding: 16px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mini-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .mini-icon.rust {
        background: rgba(184, 74, 44, 0.1);
        color: #b84a2c;
    }

    .mini-icon.green {
        background: rgba(73, 102, 64, 0.1);
        color: #496640;
    }

    .mini-icon.amber {
        background: rgba(196, 138, 32, 0.1);
        color: #c48a20;
    }

    .mini-info h4 {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--neutral-900);
        line-height: 1;
    }

    .mini-info p {
        font-size: 0.75rem;
        color: var(--neutral-500);
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .wb {
            flex-direction: column;
            text-align: center;
        }

        .wb-actions {
            margin-left: 0;
        }
    }
    </style>
</head>

<body>
    <div class="app-wrap">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">📚</div>
                <div>
                    <div class="brand-name">Perpustakaan Digital</div>
                    <div class="brand-role">ADMINISTRATOR</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-section-label">UTAMA</span>
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <span class="nav-section-label">MANAJEMEN</span>
                <a href="pengguna.php" class="nav-link">
                    <i class="fas fa-users-cog"></i>
                    <span>Pengguna</span>
                </a>
                <a href="anggota.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i>
                    <span>Anggota</span>
                </a>

                <span class="nav-section-label">KOLEKSI</span>
                <a href="kategori.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
                <a href="buku.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>Buku</span>
                </a>

                <span class="nav-section-label">TRANSAKSI</span>
                <a href="transaksi.php" class="nav-link">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transaksi</span>
                </a>
                <a href="denda.php" class="nav-link">
                    <i class="fas fa-coins"></i>
                    <span>Denda</span>
                </a>
                <a href="laporan.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>

                <span class="nav-section-label">AKUN</span>
                <a href="profil.php" class="nav-link">
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

        <!-- MAIN AREA -->
        <div class="main-area">
            <!-- HEADER -->
            <header class="topbar">
                <div class="page-info">
                    <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
                    <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date">
                        <i class="far fa-calendar-alt"></i> <?= date('d M Y') ?>
                    </div>
                    <div class="topbar-user">
                        <div class="topbar-avatar">
                            <?php if ($fotoPath): ?>
                            <img src="<?= $fotoPath ?>" alt="Foto">
                            <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                            <?php endif; ?>
                        </div>
                        <span class="topbar-username"><?= htmlspecialchars(getPenggunaName()) ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </header>

            <!-- CONTENT -->
            <main class="content">
                <!-- Welcome Box -->
                <div class="wb">
                    <div class="wb-avatar">
                        <?php if ($fotoPath): ?>
                        <img src="<?= $fotoPath ?>" alt="Foto">
                        <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="wb-name">Selamat Datang, <?= htmlspecialchars(getPenggunaName()) ?></div>
                        <div class="wb-desc">Kelola seluruh sistem perpustakaan dari satu tempat · Panel Admin</div>
                    </div>
                    <div class="wb-actions">
                        <a href="buku.php" class="wb-btn1"><i class="fas fa-plus"></i> Tambah Buku</a>
                        <a href="laporan.php" class="wb-btn2"><i class="fas fa-chart-bar"></i> Lihat Laporan</a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Total Buku</h3>
                            <div class="stat-number"><?= $tb ?></div>
                            <div class="stat-desc success"><i class="fas fa-arrow-up"></i> <?= $ts ?> tersedia</div>
                        </div>
                        <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Aktif Dipinjam</h3>
                            <div class="stat-number"><?= $ap ?></div>
                            <div class="stat-desc <?= $tl > 0 ? 'warning' : '' ?>"><?= $tl ?> terlambat</div>
                        </div>
                        <div class="stat-icon red"><i class="fas fa-exchange-alt"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Total Anggota</h3>
                            <div class="stat-number"><?= $ta ?></div>
                            <div class="stat-desc"><?= $tp ?> pengguna sistem</div>
                        </div>
                        <div class="stat-icon green"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Denda Belum Bayar</h3>
                            <div class="stat-number">Rp <?= number_format($td, 0, ',', '.') ?></div>
                            <div class="stat-desc warning">perlu diselesaikan</div>
                        </div>
                        <div class="stat-icon amber"><i class="fas fa-coins"></i></div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="recent-card">
                    <div class="recent-header">
                        <div class="recent-title">
                            <i class="fas fa-history"></i>
                            <h2>Transaksi Terbaru</h2>
                        </div>
                        <a href="transaksi.php" class="recent-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($rows && $rows->num_rows > 0): while($r = $rows->fetch_assoc()):
                                    $late = $r['status_transaksi'] === 'Peminjaman' && strtotime($r['tgl_kembali_rencana']) < time();
                                    if ($r['status_transaksi'] === 'Pengembalian') {
                                        $statusClass = 'success';
                                        $statusText = '✓ Kembali';
                                    } elseif ($late) {
                                        $statusClass = 'danger';
                                        $statusText = '⚠ Terlambat';
                                    } else {
                                        $statusClass = 'warning';
                                        $statusText = '⇄ Dipinjam';
                                    }
                                ?>
                                <tr>
                                    <td class="book-cover-cell">
                                        <?php if (!empty($r['cover']) && file_exists('../'.$r['cover'])): ?>
                                        <img class="cover-thumb" src="../<?= htmlspecialchars($r['cover']) ?>" alt="">
                                        <?php else: ?>
                                        <div class="cover-thumb"
                                            style="background: var(--neutral-100); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-book" style="color: var(--neutral-400);"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="fw-600"><?= htmlspecialchars($r['nama_anggota']) ?></span></td>
                                    <td><?= htmlspecialchars(mb_strimwidth($r['judul_buku'], 0, 34, '…')) ?></td>
                                    <td><?= date('d M Y', strtotime($r['tgl_pinjam'])) ?></td>
                                    <td><?= date('d M Y', strtotime($r['tgl_kembali_rencana'])) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6"
                                        style="text-align: center; padding: 40px; color: var(--neutral-500);">Belum ada
                                        transaksi</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mini Stats -->
                <div class="mini-stats">
                    <div class="mini-card">
                        <div class="mini-icon rust"><i class="fas fa-book"></i></div>
                        <div class="mini-info">
                            <h4><?= $tb - $ts ?></h4>
                            <p>Buku Sedang Dipinjam</p>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="mini-icon green"><i class="fas fa-undo-alt"></i></div>
                        <div class="mini-info">
                            <h4><?= $kh ?></h4>
                            <p>Pengembalian Hari Ini</p>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="mini-icon amber"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="mini-info">
                            <h4><?= $tl ?></h4>
                            <p>Keterlambatan Aktif</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="../assets/js/script.js"></script>
</body>

</html>