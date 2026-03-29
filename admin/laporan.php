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

// ── Date filter ──────────────────────────────────────────────
$dari   = isset($_GET['dari'])   && $_GET['dari']   !== '' ? $_GET['dari']   : null;
$sampai = isset($_GET['sampai']) && $_GET['sampai'] !== '' ? $_GET['sampai'] : null;
$reset  = isset($_GET['reset']);
if ($reset) { $dari = null; $sampai = null; }

// Statistik
$total_buku    = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];
$buku_tersedia = $conn->query("SELECT COUNT(*) c FROM buku WHERE status='tersedia'")->fetch_assoc()['c'];
$total_anggota = $conn->query("SELECT COUNT(*) c FROM anggota")->fetch_assoc()['c'];
$total_pinjam  = $conn->query("SELECT COUNT(*) c FROM transaksi")->fetch_assoc()['c'];
$aktif_pinjam  = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$total_denda   = $conn->query("SELECT COALESCE(SUM(total_denda),0) s FROM denda")->fetch_assoc()['s'];
$denda_belum   = $conn->query("SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'")->fetch_assoc()['s'];

// Build query with date filter
$whereClause = "WHERE 1=1";
$params = []; $types = '';
if ($dari)   { $whereClause .= " AND t.tgl_pinjam >= ?"; $params[] = $dari;   $types .= 's'; }
if ($sampai) { $whereClause .= " AND t.tgl_pinjam <= ?"; $params[] = $sampai; $types .= 's'; }

$sql = "SELECT t.id_transaksi, a.nama_anggota, b.judul_buku,
               t.tgl_pinjam, t.tgl_kembali_rencana, t.tgl_kembali_aktual, t.status_transaksi
        FROM transaksi t
        JOIN anggota a ON t.id_anggota = a.id_anggota
        JOIN buku b    ON t.id_buku    = b.id_buku
        $whereClause
        ORDER BY t.tgl_pinjam DESC";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $trans_all = $stmt->get_result();
} else {
    $trans_all = $conn->query($sql);
}

$rows_cache = [];
if ($trans_all && $trans_all->num_rows > 0) {
    while ($r = $trans_all->fetch_assoc()) $rows_cache[] = $r;
}
$total_data = count($rows_cache);

$page_title = 'Laporan';
$page_sub   = 'Rekap data dan statistik perpustakaan';
$no_laporan = 'RPT-' . date('Ymd') . '-001';
$tgl_cetak  = date('d F Y');
$jam_cetak  = date('H:i') . ' WIB';
$cssVer     = @filemtime('../assets/css/admin_laporan.css') ?: time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan — Admin Perpustakaan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/admin/laporan.css?v=<?= $cssVer ?>">
<style>
/* Extra: filter & print */
.filter-card {
    background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(79,70,229,.07);
    padding:20px 24px; margin-bottom:24px; border:1px solid #e0e7ff;
    display:flex; align-items:flex-end; gap:16px; flex-wrap:wrap;
}
.filter-group { display:flex; flex-direction:column; gap:6px; }
.filter-label { font-size:.78rem; font-weight:600; color:#4b5563; }
.filter-input {
    padding:9px 14px; border:1px solid #d1d5db; border-radius:9px;
    font-size:.875rem; font-family:'Inter',sans-serif; color:#1f2937;
    outline:none; transition:border .2s; background:#f9fafb;
}
.filter-input:focus { border-color:#6366f1; background:#fff; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.btn-filter { padding:9px 20px; background:#4f46e5; color:#fff; border:none; border-radius:9px; font-size:.875rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:7px; transition:background .2s; }
.btn-filter:hover { background:#4338ca; }
.btn-reset { padding:9px 20px; background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb; border-radius:9px; font-size:.875rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:7px; text-decoration:none; transition:background .2s; }
.btn-reset:hover { background:#e5e7eb; }
.btn-print { padding:9px 22px; background:#10b981; color:#fff; border:none; border-radius:9px; font-size:.875rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:7px; transition:background .2s; margin-left:auto; }
.btn-print:hover { background:#059669; }
.period-badge { display:inline-flex; align-items:center; gap:6px; background:#eef2ff; color:#4338ca; padding:4px 12px; border-radius:20px; font-size:.78rem; font-weight:600; }
.report-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(79,70,229,.07); border:1px solid #e0e7ff; overflow:hidden; margin-bottom:24px; }
.report-card-header { padding:18px 24px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.report-card-title { font-size:1rem; font-weight:700; color:#1e1b4b; display:flex; align-items:center; gap:9px; }
.report-card-title i { color:#6366f1; }
.report-table { width:100%; border-collapse:collapse; }
.report-table thead th { background:#f8fafc; padding:11px 16px; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:2px solid #e5e7eb; text-align:left; }
.report-table tbody td { padding:13px 16px; font-size:.875rem; color:#374151; border-bottom:1px solid #f3f4f6; }
.report-table tbody tr:last-child td { border-bottom:none; }
.report-table tbody tr:hover { background:#fafbff; }
.badge-status { display:inline-flex; align-items:center; gap:5px; padding:4px 11px; border-radius:20px; font-size:.72rem; font-weight:700; white-space:nowrap; }
.badge-dipinjam { background:#fef3c7; color:#92400e; }
.badge-kembali  { background:#d1fae5; color:#065f46; }
.badge-terlambat{ background:#fee2e2; color:#991b1b; }
.empty-row td { text-align:center; padding:48px 16px; color:#9ca3af; }
.print-header { display:none; }
.print-footer { display:none; }

/* ================= PRINT STYLES ================= */
@media print {
    @page { margin: 1.5cm 1.5cm; size: A4 portrait; }
    .no-print, .sidebar, .topbar, header, .filter-card, .stats-grid, .page-header { display: none !important; }
    .main-area { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .app-wrap { display: block !important; }
    body { background: white !important; font-family: 'Inter', sans-serif !important; color: #111827 !important; }
    
    /* Modern Print Header */
    .print-header { 
        display: block !important; 
        margin-bottom: 25px; 
        padding-bottom: 15px;
        border-bottom: 2px solid #111827;
    }
    .print-header-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .ph-brand { font-size: 1.4rem; font-weight: 800; color: #111827; letter-spacing: -0.02em; line-height: 1.2; }
    .ph-address { font-size: 0.85rem; color: #4b5563; margin-top: 4px; line-height: 1.5; }
    .ph-doc { text-align: right; }
    .ph-doc-title { font-size: 1.2rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #111827; margin-bottom: 5px; }
    .ph-doc-meta { font-size: 0.8rem; color: #4b5563; margin-top: 3px; }
    
    .ph-summary { display: flex; justify-content: space-between; margin-top: 20px; font-size: 0.85rem; color: #111827; }
    
    /* Table Print Modernization */
    .report-card { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; }
    .report-card-header { display: none !important; } /* Hide dashboard table header */
    .report-table { border-collapse: collapse !important; width: 100% !important; margin-top: 10px; }
    .report-table thead th { 
        background: #f8fafc !important; 
        color: #111827 !important; 
        font-size: 0.75rem !important; 
        font-weight: 700 !important; 
        padding: 10px 8px !important; 
        border-top: 1px solid #111827 !important;
        border-bottom: 2px solid #111827 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .report-table tbody td { 
        border: none !important;
        border-bottom: 1px solid #e5e7eb !important; 
        padding: 10px 8px !important; 
        font-size: 0.85rem !important;
        color: #374151 !important;
        page-break-inside: avoid;
    }
    .report-table tbody tr:last-child td { border-bottom: 2px solid #111827 !important; }
    .report-table tbody tr:hover { background: transparent !important; }
    
    /* Badge Outline Style (Better for B&W Printers) */
    .badge-status { 
        background: transparent !important; 
        padding: 3px 8px !important; 
        border: 1px solid #d1d5db !important; 
        border-radius: 6px !important; 
        font-weight: 600 !important;
    }
    .badge-dipinjam { border-color: #d97706 !important; color: #d97706 !important; }
    .badge-kembali  { border-color: #059669 !important; color: #059669 !important; }
    .badge-terlambat{ border-color: #dc2626 !important; color: #dc2626 !important; }

    /* Modern Print Footer */
    .print-footer { 
        display: flex !important; 
        justify-content: space-between; 
        align-items: flex-end; 
        margin-top: 40px; 
        page-break-inside: avoid;
    }
    .pf-note { font-size: 0.75rem; color: #6b7280; line-height: 1.5; }
    .pf-signature { text-align: center; width: 220px; }
    .pf-sign-title { font-size: 0.85rem; color: #374151; margin-bottom: 60px; }
    .pf-sign-name { font-size: 0.9rem; font-weight: 700; color: #111827; border-bottom: 1px solid #111827; padding-bottom: 4px; margin-bottom: 4px; }
    .pf-sign-role { font-size: 0.75rem; color: #4b5563; }
}
</style>
</head>
<body>
<div class="app-wrap">
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
            <a href="dashboard.php" class="nav-link">
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
            <a href="laporan.php" class="nav-link active">
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

    <div class="main-area">
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

        <main class="content">
        <div class="page-header no-print">
            <div>
                <h1 class="page-header-title">Laporan Peminjaman</h1>
                <p class="page-header-sub">Rekap data dan statistik perpustakaan</p>
            </div>
            <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Cetak Laporan</button>
        </div>

        <div class="stats-grid no-print">
            <div class="stat-card">
                <div class="stat-header"><div class="stat-icon blue"><i class="fas fa-book"></i></div></div>
                <div class="stat-label">Total Buku</div>
                <div class="stat-value"><?= number_format($total_buku) ?></div>
                <div class="stat-sub"><?= $buku_tersedia ?> unit tersedia</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><div class="stat-icon green"><i class="fas fa-users"></i></div></div>
                <div class="stat-label">Anggota</div>
                <div class="stat-value"><?= number_format($total_anggota) ?></div>
                <div class="stat-sub">Terdaftar aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><div class="stat-icon orange"><i class="fas fa-exchange-alt"></i></div></div>
                <div class="stat-label">Transaksi</div>
                <div class="stat-value"><?= number_format($total_pinjam) ?></div>
                <div class="stat-sub"><?= $aktif_pinjam ?> sedang dipinjam</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><div class="stat-icon red"><i class="fas fa-coins"></i></div></div>
                <div class="stat-label">Total Denda</div>
                <div class="stat-value">Rp <?= number_format($total_denda,0,',','.') ?></div>
                <div class="stat-sub">Belum lunas: Rp <?= number_format($denda_belum,0,',','.') ?></div>
            </div>
        </div>

        <form method="GET" class="filter-card no-print">
            <div class="filter-group">
                <label class="filter-label"><i class="fas fa-calendar-alt" style="color:#6366f1"></i> Dari Tanggal</label>
                <input type="date" name="dari" class="filter-input" value="<?= htmlspecialchars($dari ?? '') ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label"><i class="fas fa-calendar-check" style="color:#6366f1"></i> Sampai Tanggal</label>
                <input type="date" name="sampai" class="filter-input" value="<?= htmlspecialchars($sampai ?? '') ?>">
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="laporan.php?reset=1" class="btn-reset"><i class="fas fa-undo"></i> Reset</a>
            <button type="button" class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        </form>

        <div class="print-header">
            <div class="print-header-top">
                <div>
                    <div class="ph-brand">Perpustakaan Digital</div>
                    <div class="ph-address">Jl. Pendidikan No. 1<br>Sistem Manajemen Perpustakaan</div>
                </div>
                <div class="ph-doc">
                    <div class="ph-doc-title">Laporan Peminjaman</div>
                    <div class="ph-doc-meta">No. Dokumen: <strong><?= $no_laporan ?></strong></div>
                    <div class="ph-doc-meta">Dicetak: <?= $tgl_cetak ?>, <?= $jam_cetak ?></div>
                </div>
            </div>
            <div class="ph-summary">
                <div>
                    <strong>Periode Laporan:</strong> 
                    <?php if ($dari || $sampai): ?>
                        <?= $dari ? date('d M Y',strtotime($dari)) : 'Awal' ?> s/d <?= $sampai ? date('d M Y',strtotime($sampai)) : 'Sekarang' ?>
                    <?php else: ?>
                        Semua Periode
                    <?php endif; ?>
                </div>
                <div><strong>Total Transaksi:</strong> <?= $total_data ?> data</div>
            </div>
        </div>

        <div class="report-card">
            <div class="report-card-header">
                <div class="report-card-title">
                    <i class="fas fa-list-alt"></i>
                    Data Transaksi Peminjaman
                    <span class="period-badge">
                        <?php if ($dari || $sampai): ?>
                        <i class="fas fa-calendar"></i>
                        <?= $dari ? date('d M Y',strtotime($dari)) : '—' ?> s/d <?= $sampai ? date('d M Y',strtotime($sampai)) : 'sekarang' ?>
                        <?php else: ?>
                        <i class="fas fa-infinity"></i> Semua Periode
                        <?php endif; ?>
                    </span>
                </div>
                <span style="font-size:.82rem;color:#6b7280;"><?= $total_data ?> data ditemukan</span>
            </div>
            <div style="overflow-x:auto">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Nama Pengguna</th>
                        <th>Judul Buku</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows_cache): $no = 1; foreach ($rows_cache as $r):
                    $late = $r['status_transaksi'] === 'Peminjaman' && strtotime($r['tgl_kembali_rencana']) < time();
                    if ($r['status_transaksi'] === 'Pengembalian') { $bc='badge-kembali'; $bl='<i class="fas fa-check-circle"></i> Dikembalikan'; }
                    elseif ($late) { $bc='badge-terlambat'; $bl='<i class="fas fa-exclamation-circle"></i> Terlambat'; }
                    else { $bc='badge-dipinjam'; $bl='<i class="fas fa-book-reader"></i> Dipinjam'; }
                ?>
                <tr>
                    <td style="color:#9ca3af;font-size:.8rem"><?= $no++ ?></td>
                    <td style="font-weight:600;color:#111827"><?= htmlspecialchars($r['nama_anggota']) ?></td>
                    <td><?= htmlspecialchars($r['judul_buku']) ?></td>
                    <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
                    <td><?= $r['tgl_kembali_aktual'] ? date('d/m/Y',strtotime($r['tgl_kembali_aktual'])) : '<span style="color:#9ca3af">—</span>' ?></td>
                    <td><span class="badge-status <?= $bc ?>"><?= $bl ?></span></td>
                </tr>
                <?php endforeach; else: ?>
                <tr class="empty-row">
                    <td colspan="6">
                        <div style="font-size:2rem;margin-bottom:8px">📋</div>
                        <div style="font-weight:600;color:#374151">Tidak ada data</div>
                        <div style="font-size:.82rem;margin-top:4px">Coba ubah filter tanggal</div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="print-footer">
            <div class="pf-note">
                <strong>Catatan:</strong><br>
                Dokumen ini merupakan hasil cetak otomatis dari Sistem Manajemen Perpustakaan.<br>
                Informasi yang tertera sesuai dengan data pada sistem saat dicetak.
            </div>
            <div class="pf-signature">
                <div class="pf-sign-title">Mengetahui,</div>
                <div class="pf-sign-name">Petugas Perpustakaan</div>
                <div class="pf-sign-role">Penanggung Jawab Laporan</div>
            </div>
        </div>
        </main>
    </div>
</div>
<script>
if (window.history.replaceState) window.history.replaceState(null, null, window.location.href);
</script>
<script src="../assets/js/script.js"></script>
</body>
</html>