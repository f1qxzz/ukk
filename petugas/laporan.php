<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();

$conn = getConnection();

// ── Date filter ──────────────────────────────────────────────
$dari  = isset($_GET['dari'])  && $_GET['dari']  !== '' ? $_GET['dari']  : null;
$sampai = isset($_GET['sampai']) && $_GET['sampai'] !== '' ? $_GET['sampai'] : null;
$reset = isset($_GET['reset']);
if ($reset) { $dari = null; $sampai = null; }

// Statistik
$total_buku    = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];
$buku_tersedia = $conn->query("SELECT COUNT(*) c FROM buku WHERE status='tersedia'")->fetch_assoc()['c'];
$total_anggota = $conn->query("SELECT COUNT(*) c FROM anggota")->fetch_assoc()['c'];
$total_pinjam  = $conn->query("SELECT COUNT(*) c FROM transaksi")->fetch_assoc()['c'];
$aktif_pinjam  = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$total_terlambat = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < CURDATE()")->fetch_assoc()['c'];

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

$total_data = $trans_all ? $trans_all->num_rows : 0;
$rows_cache = [];
if ($trans_all && $trans_all->num_rows > 0) {
    while ($r = $trans_all->fetch_assoc()) $rows_cache[] = $r;
}

$page_title = 'Laporan Peminjaman';
$page_sub   = 'Ringkasan data sirkulasi perpustakaan';
$no_laporan = 'PTG-' . date('Ymd') . '-001';
$tgl_cetak  = date('d F Y');
$jam_cetak  = date('H:i') . ' WIB';
$cssVer     = @filemtime('../assets/css/petugas_laporan.css') ?: time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan — Petugas Perpustakaan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/petugas_laporan.css?v=<?= $cssVer ?>">
<style>
/* ── Extra styles for date-filter + print ── */
.filter-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(79,70,229,.07);
    padding: 20px 24px;
    margin-bottom: 24px;
    border: 1px solid #e0e7ff;
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}
.filter-group { display: flex; flex-direction: column; gap: 6px; }
.filter-label { font-size: 0.78rem; font-weight: 600; color: #4b5563; }
.filter-input {
    padding: 9px 14px;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    font-size: 0.875rem;
    font-family: 'Inter', sans-serif;
    color: #1f2937;
    outline: none;
    transition: border 0.2s;
    background: #f9fafb;
}
.filter-input:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.btn-filter {
    padding: 9px 20px;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 9px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 7px;
    transition: background 0.2s;
}
.btn-filter:hover { background: #4338ca; }
.btn-reset {
    padding: 9px 20px;
    background: #f3f4f6;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    border-radius: 9px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 7px;
    text-decoration: none;
    transition: background 0.2s;
}
.btn-reset:hover { background: #e5e7eb; }
.btn-print {
    padding: 9px 22px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 9px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 7px;
    transition: background 0.2s;
    margin-left: auto;
}
.btn-print:hover { background: #059669; }

.period-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #eef2ff; color: #4338ca;
    padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600;
}

/* ─── PRINT ─── */
@media print {
    .no-print, .sidebar, .topbar, header, .filter-card, .stats-grid,
    .btn-print, .btn-filter, .btn-reset, .page-header { display: none !important; }
    .main-area { margin-left: 0 !important; }
    .app-wrap { display: block !important; }
    body { background: white !important; }
    .print-document { display: block !important; }
    .report-table th, .report-table td { border: 1px solid #d1d5db !important; }
    .badge-dipinjam { background: #fef3c7 !important; color: #92400e !important; }
    .badge-kembali  { background: #d1fae5 !important; color: #065f46 !important; }
    .badge-terlambat{ background: #fee2e2 !important; color: #991b1b !important; }
}

/* Print document (hidden on screen, visible only when printing) */
.print-document { display: none; }

/* Borrowing table card */
.report-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(79,70,229,.07);
    border: 1px solid #e0e7ff;
    overflow: hidden;
    margin-bottom: 24px;
}
.report-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.report-card-title {
    font-size: 1rem; font-weight: 700; color: #1e1b4b;
    display: flex; align-items: center; gap: 9px;
}
.report-card-title i { color: #6366f1; }
.report-table { width: 100%; border-collapse: collapse; }
.report-table thead th {
    background: #f8fafc; padding: 11px 16px;
    font-size: 0.78rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: #6b7280;
    border-bottom: 2px solid #e5e7eb; text-align: left;
}
.report-table tbody td {
    padding: 13px 16px; font-size: 0.875rem; color: #374151;
    border-bottom: 1px solid #f3f4f6;
}
.report-table tbody tr:last-child td { border-bottom: none; }
.report-table tbody tr:hover { background: #fafbff; }
.badge-status {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 700; white-space: nowrap;
}
.badge-dipinjam { background: #fef3c7; color: #92400e; }
.badge-kembali  { background: #d1fae5; color: #065f46; }
.badge-terlambat{ background: #fee2e2; color: #991b1b; }
.empty-row td { text-align: center; padding: 48px 16px; color: #9ca3af; }
.empty-row .empty-ico { font-size: 2.5rem; margin-bottom: 10px; }

/* Print header (only shows in print) */
.print-header {
    display: none;
    text-align: center;
    margin-bottom: 24px;
}
@media print {
    .print-header { display: block; }
    .print-footer { display: flex !important; }
    .report-card { box-shadow: none !important; border: none !important; }
    .report-card-header .period-badge { display: inline-flex !important; }
    .report-table tbody tr:hover { background: white !important; }
}
.print-footer {
    display: none;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 2px solid #374151;
}
</style>
</head>
<body>
<div class="app-wrap">
<?php include 'includes/nav.php'; ?>
<div class="main-area">
<?php include 'includes/header.php'; ?>

<main class="content">
<!-- Page Header -->
<div class="page-header no-print">
    <div>
        <h1 class="page-header-title">Laporan Peminjaman</h1>
        <p class="page-header-sub">Ringkasan data sirkulasi perpustakaan</p>
    </div>
    <button class="btn-print" onclick="window.print()">
        <i class="fas fa-print"></i> Cetak Laporan
    </button>
</div>

<!-- Stats -->
<div class="stats-grid no-print">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-book"></i></div>
        <div class="stat-info"><h3>Total Buku</h3><div class="stat-number"><?= $total_buku ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info"><h3>Anggota</h3><div class="stat-number"><?= $total_anggota ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="stat-info"><h3>Aktif Pinjam</h3><div class="stat-number"><?= $aktif_pinjam ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info"><h3>Terlambat</h3><div class="stat-number"><?= $total_terlambat ?></div></div>
    </div>
</div>

<!-- Date Filter -->
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

<!-- PRINT HEADER (only visible when printing) -->
<div class="print-header">
    <div style="font-size:1.3rem;font-weight:800;color:#111827;">Perpustakaan Digital</div>
    <div style="font-size:0.85rem;color:#6b7280;margin-top:2px;">Jl. Pendidikan No. 1 · Sistem Manajemen Perpustakaan</div>
    <div style="font-size:1.1rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin:12px 0 4px;color:#374151;border-top:2px solid #374151;padding-top:10px;">Laporan Peminjaman Buku</div>
    <?php if ($dari || $sampai): ?>
    <div style="font-size:0.82rem;color:#6b7280;">Periode: <?= $dari ? date('d M Y',strtotime($dari)) : '—' ?> s/d <?= $sampai ? date('d M Y',strtotime($sampai)) : 'sekarang' ?></div>
    <?php else: ?>
    <div style="font-size:0.82rem;color:#6b7280;">Semua Periode</div>
    <?php endif; ?>
    <div style="font-size:0.78rem;color:#9ca3af;margin-top:4px;">Dicetak: <?= $tgl_cetak ?>, <?= $jam_cetak ?> · <?= $total_data ?> data</div>
</div>

<!-- Report Table -->
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
        <span style="font-size:0.82rem;color:#6b7280;"><?= $total_data ?> data ditemukan</span>
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
            if ($r['status_transaksi'] === 'Pengembalian') {
                $bc = 'badge-kembali'; $bl = '<i class="fas fa-check-circle"></i> Dikembalikan';
            } elseif ($late) {
                $bc = 'badge-terlambat'; $bl = '<i class="fas fa-exclamation-circle"></i> Terlambat';
            } else {
                $bc = 'badge-dipinjam'; $bl = '<i class="fas fa-book-reader"></i> Dipinjam';
            }
        ?>
        <tr>
            <td style="color:#9ca3af;font-size:0.8rem"><?= $no++ ?></td>
            <td style="font-weight:600;color:#111827"><?= htmlspecialchars($r['nama_anggota']) ?></td>
            <td><?= htmlspecialchars($r['judul_buku']) ?></td>
            <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
            <td><?= $r['tgl_kembali_aktual'] ? date('d/m/Y', strtotime($r['tgl_kembali_aktual'])) : '<span style="color:#9ca3af">—</span>' ?></td>
            <td><span class="badge-status <?= $bc ?>"><?= $bl ?></span></td>
        </tr>
        <?php endforeach; else: ?>
        <tr class="empty-row">
            <td colspan="6">
                <div class="empty-ico">📋</div>
                <div style="font-weight:600;color:#374151">Tidak ada data</div>
                <div style="font-size:0.82rem;margin-top:4px">Coba ubah filter tanggal</div>
            </td>
        </tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Print Footer (only visible when printing) -->
<div class="print-footer">
    <div style="font-size:0.78rem;color:#6b7280;">
        <div>No. Dokumen: <strong><?= $no_laporan ?></strong></div>
        <div>Dicetak: <?= $tgl_cetak ?>, <?= $jam_cetak ?></div>
    </div>
    <div style="text-align:center">
        <div style="height:60px;border-bottom:1px solid #374151;width:160px;margin-bottom:6px"></div>
        <div style="font-size:0.82rem;font-weight:700">Petugas Perpustakaan</div>
        <div style="font-size:0.75rem;color:#6b7280">Penanggung Jawab</div>
    </div>
</div>

</main>
</div>
</div>
<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
<script src="../assets/js/script.js"></script>
</body>
</html>
