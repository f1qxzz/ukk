<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();

// ─── Inisialisasi tabel permintaan jika belum ada ────────────────────────────
$conn->query("
CREATE TABLE IF NOT EXISTS permintaan_pinjam (
    id_permintaan   INT AUTO_INCREMENT PRIMARY KEY,
    no_request      VARCHAR(20) NOT NULL UNIQUE,
    id_anggota      INT NOT NULL,
    id_buku         INT NOT NULL,
    tgl_request     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tgl_mulai       DATE NOT NULL,
    tgl_selesai     DATE NOT NULL,
    status          ENUM('Pending','Disetujui','Ditolak') NOT NULL DEFAULT 'Pending',
    tgl_aksi        DATETIME NULL,
    id_admin        INT NULL,
    catatan         TEXT NULL,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota) ON DELETE CASCADE,
    FOREIGN KEY (id_buku)    REFERENCES buku(id_buku) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ─── Data user untuk header ───────────────────────────────────────────────────
$userId = getPenggunaId();
$uStmt  = $conn->prepare("SELECT foto, nama_pengguna FROM pengguna WHERE id_pengguna = ?");
$uStmt->bind_param("i", $userId);
$uStmt->execute();
$userData = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

$initials = '';
foreach (explode(' ', trim($userData['nama_pengguna'] ?? getPenggunaName())) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}
$fotoPath = (!empty($userData['foto']) && file_exists('../' . $userData['foto']))
            ? '../' . htmlspecialchars($userData['foto']) : null;

// ─── AJAX / API handler ───────────────────────────────────────────────────────
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['api'])) {
    header('Content-Type: application/json');

    // -- Approve / Reject --
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($data['id'] ?? 0);
        $action = $data['action'] ?? '';

        if (!$id || !in_array($action, ['approve','reject'])) {
            echo json_encode(['ok' => false, 'msg' => 'Parameter tidak valid']);
            exit;
        }

        $permRow = $conn->query("SELECT p.*, b.stok, b.id_buku FROM permintaan_pinjam p JOIN buku b ON p.id_buku=b.id_buku WHERE p.id_permintaan=$id")->fetch_assoc();
        if (!$permRow) {
            echo json_encode(['ok' => false, 'msg' => 'Data tidak ditemukan']);
            exit;
        }
        if ($permRow['status'] !== 'Pending') {
            echo json_encode(['ok' => false, 'msg' => 'Permintaan sudah diproses sebelumnya']);
            exit;
        }

        if ($action === 'approve') {
            if ($permRow['stok'] < 1) {
                echo json_encode(['ok' => false, 'msg' => 'Stok buku habis, tidak bisa disetujui']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE permintaan_pinjam SET status='Disetujui', tgl_aksi=NOW(), id_admin=? WHERE id_permintaan=?");
            $stmt->bind_param("ii", $userId, $id);
            $stmt->execute(); $stmt->close();

            // Buat transaksi otomatis
            $stmt2 = $conn->prepare("INSERT INTO transaksi(id_anggota,id_buku,tgl_pinjam,tgl_kembali_rencana,status_transaksi) VALUES(?,?,?,?,'Peminjaman')");
            $stmt2->bind_param("iiss", $permRow['id_anggota'], $permRow['id_buku'], $permRow['tgl_mulai'], $permRow['tgl_selesai']);
            $stmt2->execute(); $stmt2->close();
            $conn->query("UPDATE buku SET stok=stok-1, status=IF(stok-1>0,'tersedia','tidak') WHERE id_buku={$permRow['id_buku']}");

            echo json_encode(['ok' => true, 'msg' => 'Permintaan disetujui & transaksi dibuat']);
        } else {
            $stmt = $conn->prepare("UPDATE permintaan_pinjam SET status='Ditolak', tgl_aksi=NOW(), id_admin=? WHERE id_permintaan=?");
            $stmt->bind_param("ii", $userId, $id);
            $stmt->execute(); $stmt->close();
            echo json_encode(['ok' => true, 'msg' => 'Permintaan ditolak']);
        }
        exit;
    }

    // -- Fetch realtime stats + list --
    $filter = $_GET['filter'] ?? 'Semua';
    $search = trim($_GET['search'] ?? '');

    $menunggu  = $conn->query("SELECT COUNT(*) c FROM permintaan_pinjam WHERE status='Pending'")->fetch_assoc()['c'];
    $disetujui = $conn->query("SELECT COUNT(*) c FROM permintaan_pinjam WHERE status='Disetujui' AND DATE(tgl_aksi)=CURDATE()")->fetch_assoc()['c'];
    $ditolak   = $conn->query("SELECT COUNT(*) c FROM permintaan_pinjam WHERE status='Ditolak'   AND DATE(tgl_aksi)=CURDATE()")->fetch_assoc()['c'];
    $totalBuku = $conn->query("SELECT SUM(stok) s FROM buku")->fetch_assoc()['s'] ?? 0;

    $where = "WHERE 1=1";
    if ($filter !== 'Semua') $where .= " AND p.status='" . $conn->real_escape_string($filter) . "'";
    if ($search !== '') {
        $s = $conn->real_escape_string($search);
        $where .= " AND (a.nama_anggota LIKE '%$s%' OR b.judul_buku LIKE '%$s%')";
    }

    $rows = $conn->query("
        SELECT p.id_permintaan, p.no_request, p.tgl_request, p.tgl_mulai, p.tgl_selesai, p.status,
               a.nama_anggota, a.id_anggota,
               b.judul_buku, b.stok
        FROM permintaan_pinjam p
        JOIN anggota a ON p.id_anggota=a.id_anggota
        JOIN buku    b ON p.id_buku=b.id_buku
        $where
        ORDER BY FIELD(p.status,'Pending','Disetujui','Ditolak'), p.tgl_request DESC
        LIMIT 100
    ");

    $list = [];
    while ($r = $rows->fetch_assoc()) $list[] = $r;

    echo json_encode([
        'ok'        => true,
        'stats'     => compact('menunggu','disetujui','ditolak','totalBuku'),
        'rows'      => $list,
        'filter'    => $filter,
        'search'    => $search,
    ]);
    exit;
}

// ─── Seed data demo jika kosong ───────────────────────────────────────────────
$existingCount = $conn->query("SELECT COUNT(*) c FROM permintaan_pinjam")->fetch_assoc()['c'];
if ($existingCount == 0) {
    // Ambil beberapa anggota & buku yang ada
    $anggotaRows = $conn->query("SELECT id_anggota, nama_anggota FROM anggota LIMIT 5");
    $bukuRows    = $conn->query("SELECT id_buku, judul_buku, stok FROM buku LIMIT 5");

    $anggotaList = []; while ($r = $anggotaRows->fetch_assoc()) $anggotaList[] = $r;
    $bukuList    = []; while ($r = $bukuRows->fetch_assoc()) $bukuList[] = $r;

    if (count($anggotaList) > 0 && count($bukuList) > 0) {
        $seeds = [
            ['R-101', 0, 0, '2026-03-30 10:05', '2026-04-01', '2026-04-07', 'Pending'],
            ['R-102', 1 % count($anggotaList), 1 % count($bukuList), '2026-03-30 09:45', '2026-04-01', '2026-04-05', 'Pending'],
            ['R-103', 2 % count($anggotaList), 2 % count($bukuList), '2026-03-29 16:20', '2026-03-30', '2026-04-14', 'Pending'],
            ['R-100', 3 % count($anggotaList), 3 % count($bukuList), '2026-03-29 14:10', '2026-03-29', '2026-04-05', 'Disetujui'],
            ['R-099', 4 % count($anggotaList), 4 % count($bukuList), '2026-03-29 08:30', '2026-03-29', '2026-04-12', 'Ditolak'],
        ];
        $sInsert = $conn->prepare("INSERT IGNORE INTO permintaan_pinjam(no_request,id_anggota,id_buku,tgl_request,tgl_mulai,tgl_selesai,status) VALUES(?,?,?,?,?,?,?)");
        foreach ($seeds as $s) {
            $aIdx = $s[1]; $bIdx = $s[2];
            $aId  = $anggotaList[$aIdx]['id_anggota'];
            $bId  = $bukuList[$bIdx]['id_buku'];
            $sInsert->bind_param("siissss", $s[0], $aId, $bId, $s[3], $s[4], $s[5], $s[6]);
            $sInsert->execute();
        }
        $sInsert->close();
    }
}

$page_title = 'Permintaan Peminjaman';
$page_sub   = 'Sistem Manajemen Perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Permintaan Peminjaman — Admin Perpustakaan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ─── Reset & Base ──────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --primary:   #3b82f6;
  --primary-d: #2563eb;
  --success:   #22c55e;
  --success-d: #16a34a;
  --danger:    #ef4444;
  --danger-d:  #dc2626;
  --warning:   #f59e0b;
  --navy:      #1e3a5f;
  --sidebar-w: 240px;
  --teal:      #0f766e;
  --radius:    12px;
  --shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.06);
  --neutral-50:#f8fafc; --neutral-100:#f1f5f9; --neutral-200:#e2e8f0;
  --neutral-300:#cbd5e1; --neutral-400:#94a3b8; --neutral-500:#64748b;
  --neutral-600:#475569; --neutral-700:#334155; --neutral-800:#1e293b;
}
body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg,#e0f2fe 0%,#dbeafe 40%,#ede9fe 100%); min-height: 100vh; color: var(--neutral-800); }

/* ─── Layout ────────────────────────────────────────────────── */
.app-wrap { display: flex; min-height: 100vh; }
.main-area { flex: 1; margin-left: var(--sidebar-w); display: flex; flex-direction: column; min-height: 100vh; }

/* ─── Sidebar ───────────────────────────────────────────────── */
.sidebar {
  position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh;
  background: var(--navy); color: #fff; display: flex; flex-direction: column;
  z-index: 200; overflow-y: auto;
}
.sidebar-brand { display: flex; align-items: center; gap: 10px; padding: 22px 18px 18px; border-bottom: 1px solid rgba(255,255,255,.08); }
.brand-icon { font-size: 1.6rem; }
.brand-name { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: .9rem; }
.brand-role { font-size: .65rem; color: rgba(255,255,255,.5); letter-spacing: .06em; text-transform: uppercase; margin-top: 2px; }
.sidebar-nav { flex: 1; padding: 14px 10px; display: flex; flex-direction: column; gap: 2px; }
.nav-section-label { font-size: .6rem; letter-spacing: .1em; color: rgba(255,255,255,.35); text-transform: uppercase; padding: 12px 8px 4px; }
.nav-link {
  display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px;
  color: rgba(255,255,255,.7); font-size: .85rem; font-weight: 500; text-decoration: none;
  transition: background .15s, color .15s;
}
.nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
.nav-link.active { background: rgba(255,255,255,.15); color: #fff; font-weight: 600; }
.nav-link i { width: 18px; text-align: center; font-size: .9rem; }
.sidebar-foot { padding: 12px 10px; border-top: 1px solid rgba(255,255,255,.08); }
.nav-link.logout:hover { background: rgba(239,68,68,.18); color: #fca5a5; }

/* ─── Topbar ────────────────────────────────────────────────── */
.topbar {
  background: rgba(255,255,255,.9); backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--neutral-200); padding: 14px 28px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 100;
}
.page-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 1.05rem; color: var(--neutral-800); }
.page-breadcrumb { font-size: .75rem; color: var(--neutral-400); margin-top: 1px; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.topbar-date { font-size: .78rem; color: var(--neutral-500); }
.topbar-user { display: flex; align-items: center; gap: 8px; }
.topbar-avatar {
  width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--navy), var(--teal));
  color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; overflow: hidden;
}
.topbar-avatar img { width: 100%; height: 100%; object-fit: cover; }
.topbar-username { font-size: .85rem; font-weight: 600; color: var(--neutral-700); }

/* ─── Content ───────────────────────────────────────────────── */
.content { flex: 1; padding: 28px; display: flex; flex-direction: column; gap: 22px; }

/* ─── Summary Header ────────────────────────────────────────── */
.summary-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--neutral-800); margin-bottom: 14px; }

/* ─── Summary Cards ─────────────────────────────────────────── */
.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.summary-card {
  background: #fff; border-radius: var(--radius); padding: 18px 20px;
  box-shadow: var(--shadow); display: flex; align-items: center; gap: 16px;
  border: 1px solid var(--neutral-100);
}
.summary-icon {
  width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;
}
.summary-icon.orange { background: #fff7ed; }
.summary-icon.green  { background: #f0fdf4; }
.summary-icon.red    { background: #fef2f2; }
.summary-icon.blue   { background: #eff6ff; }
.summary-label { font-size: .75rem; color: var(--neutral-500); margin-bottom: 4px; }
.summary-value { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 1.45rem; color: var(--neutral-800); line-height: 1; }

/* ─── Main Card ─────────────────────────────────────────────── */
.main-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--neutral-100); overflow: hidden; }
.main-card-header { padding: 20px 24px 0; }
.main-card-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 1.05rem; color: var(--neutral-800); margin-bottom: 14px; }

/* ─── Filter tabs + Search bar ──────────────────────────────── */
.filter-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding-bottom: 0; }
.filter-tabs { display: flex; gap: 6px; }
.tab-btn {
  padding: 7px 18px; border-radius: 20px; font-size: .82rem; font-weight: 500; border: 1.5px solid var(--neutral-200);
  background: #fff; color: var(--neutral-600); cursor: pointer; transition: all .15s;
}
.tab-btn:hover { border-color: var(--primary); color: var(--primary); }
.tab-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; font-weight: 600; }
.search-wrap { position: relative; }
.search-wrap input {
  padding: 8px 14px 8px 36px; border-radius: 20px; border: 1.5px solid var(--neutral-200);
  font-size: .82rem; color: var(--neutral-700); outline: none; width: 280px; transition: border-color .15s;
}
.search-wrap input:focus { border-color: var(--primary); }
.search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--neutral-400); font-size: .8rem; }

/* ─── Table ─────────────────────────────────────────────────── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .84rem; }
thead tr { border-bottom: 2px solid var(--neutral-100); }
th { padding: 13px 14px; text-align: left; font-size: .75rem; font-weight: 600; color: var(--neutral-500); text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
tbody tr { border-bottom: 1px solid var(--neutral-100); transition: background .12s; }
tbody tr:hover { background: var(--neutral-50); }
tbody tr:last-child { border-bottom: none; }
td { padding: 13px 14px; vertical-align: middle; color: var(--neutral-700); }
.req-id { font-weight: 700; color: var(--neutral-800); font-family: monospace; font-size: .83rem; }
.member-name { font-weight: 600; color: var(--neutral-800); }
.member-id { font-size: .73rem; color: var(--neutral-400); margin-top: 2px; }
.book-title { font-style: italic; font-weight: 500; }
.book-stock { font-size: .72rem; color: var(--neutral-400); margin-top: 2px; }
.date-range { font-size: .82rem; color: var(--neutral-600); }
.date-time { font-size: .72rem; color: var(--neutral-400); margin-top: 2px; }

/* ─── Status badge ──────────────────────────────────────────── */
.badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: .75rem; font-weight: 600; }
.badge-pending   { background: #fff7ed; color: #c2410c; }
.badge-approved  { background: #f0fdf4; color: #15803d; }
.badge-rejected  { background: #fef2f2; color: #b91c1c; }
.badge-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.badge-pending  .badge-dot { background: #f97316; }
.badge-approved .badge-dot { background: #22c55e; }
.badge-rejected .badge-dot { background: #ef4444; }

/* ─── Action Buttons ────────────────────────────────────────── */
.actions { display: flex; gap: 6px; align-items: center; }
.btn-approve {
  display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 7px;
  border: 1.5px solid var(--success-d); color: var(--success-d); background: #fff; font-size: .78rem; font-weight: 600;
  cursor: pointer; transition: all .15s; white-space: nowrap;
}
.btn-approve:hover { background: var(--success); border-color: var(--success); color: #fff; }
.btn-reject {
  display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 7px;
  border: 1.5px solid var(--danger-d); color: var(--danger-d); background: #fff; font-size: .78rem; font-weight: 600;
  cursor: pointer; transition: all .15s; white-space: nowrap;
}
.btn-reject:hover { background: var(--danger); border-color: var(--danger); color: #fff; }
.btn-detail {
  display: inline-flex; align-items: center; gap: 5px; padding: 6px 16px; border-radius: 7px;
  border: 1.5px solid var(--neutral-300); color: var(--neutral-600); background: #fff; font-size: .78rem; font-weight: 600;
  cursor: pointer; transition: all .15s;
}
.btn-detail:hover { border-color: var(--primary); color: var(--primary); }
.btn-approve:disabled, .btn-reject:disabled { opacity: .5; cursor: not-allowed; }

/* ─── Empty state ───────────────────────────────────────────── */
.empty-state { text-align: center; padding: 56px 20px; color: var(--neutral-400); }
.empty-state-ico { font-size: 2.5rem; margin-bottom: 10px; }
.empty-state-title { font-size: 1rem; font-weight: 600; color: var(--neutral-600); margin-bottom: 4px; }

/* ─── Toast ─────────────────────────────────────────────────── */
.toast-container { position: fixed; top: 20px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.toast {
  min-width: 260px; padding: 13px 18px; border-radius: 10px; background: #1e293b; color: #fff;
  font-size: .84rem; font-weight: 500; box-shadow: 0 4px 20px rgba(0,0,0,.18);
  display: flex; align-items: center; gap: 10px; animation: slideIn .25s ease;
}
.toast.success { background: #15803d; }
.toast.error   { background: #b91c1c; }
@keyframes slideIn { from { opacity:0; transform: translateX(30px); } to { opacity:1; transform: translateX(0); } }

/* ─── Loading overlay ───────────────────────────────────────── */
.loading-row td { text-align: center; padding: 40px; color: var(--neutral-400); }
.spinner { display: inline-block; width: 24px; height: 24px; border: 3px solid var(--neutral-200); border-top-color: var(--primary); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── Confirm Dialog ────────────────────────────────────────── */
.confirm-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.4); backdrop-filter: blur(3px);
  z-index: 500; display: none; align-items: center; justify-content: center;
}
.confirm-overlay.show { display: flex; }
.confirm-box {
  background: #fff; border-radius: 14px; padding: 30px 28px; max-width: 380px; width: 90%;
  box-shadow: 0 20px 60px rgba(0,0,0,.2); text-align: center;
}
.confirm-icon { font-size: 2.5rem; margin-bottom: 12px; }
.confirm-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 1.05rem; color: var(--neutral-800); margin-bottom: 8px; }
.confirm-sub { font-size: .85rem; color: var(--neutral-500); margin-bottom: 22px; line-height: 1.5; }
.confirm-actions { display: flex; gap: 10px; justify-content: center; }
.confirm-cancel { padding: 9px 22px; border-radius: 8px; border: 1.5px solid var(--neutral-200); background: #fff; color: var(--neutral-600); font-size: .85rem; font-weight: 600; cursor: pointer; }
.confirm-ok { padding: 9px 22px; border-radius: 8px; border: none; font-size: .85rem; font-weight: 600; cursor: pointer; color: #fff; }
.confirm-ok.approve-ok { background: var(--success-d); }
.confirm-ok.reject-ok  { background: var(--danger-d); }

@media (max-width: 900px) {
  .summary-grid { grid-template-columns: repeat(2, 1fr); }
  .main-area { margin-left: 0; }
  .sidebar { transform: translateX(-100%); }
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
      <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
      <span class="nav-section-label">MANAJEMEN</span>
      <a href="pengguna.php" class="nav-link"><i class="fas fa-users-cog"></i><span>Pengguna</span></a>
      <a href="anggota.php"  class="nav-link"><i class="fas fa-user-graduate"></i><span>Anggota</span></a>
      <span class="nav-section-label">KOLEKSI</span>
      <a href="kategori.php" class="nav-link"><i class="fas fa-tags"></i><span>Kategori</span></a>
      <a href="buku.php"     class="nav-link"><i class="fas fa-book"></i><span>Buku</span></a>
      <span class="nav-section-label">TRANSAKSI</span>
      <a href="permintaan.php" class="nav-link active"><i class="fas fa-bell"></i><span>Permintaan</span></a>
      <a href="transaksi.php"  class="nav-link"><i class="fas fa-exchange-alt"></i><span>Transaksi</span></a>
      <a href="denda.php"      class="nav-link"><i class="fas fa-coins"></i><span>Denda</span></a>
      <a href="laporan.php"    class="nav-link"><i class="fas fa-chart-bar"></i><span>Laporan</span></a>
      <span class="nav-section-label">AKUN</span>
      <a href="profil.php"   class="nav-link"><i class="fas fa-user"></i><span>Profil Saya</span></a>
      <a href="../index.php" class="nav-link"><i class="fas fa-globe"></i><span>Beranda</span></a>
    </nav>
    <div class="sidebar-foot">
      <a href="logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-area">

    <!-- TOPBAR -->
    <header class="topbar">
      <div>
        <div class="page-title"><?= htmlspecialchars($page_title) ?></div>
        <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
      </div>
      <div class="topbar-right">
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= date('d M Y') ?></div>
        <div class="topbar-user">
          <div class="topbar-avatar">
            <?php if ($fotoPath): ?><img src="<?= $fotoPath ?>" alt="Foto"><?php else: ?><?= htmlspecialchars($initials) ?><?php endif; ?>
          </div>
          <span class="topbar-username">Halo, <?= htmlspecialchars($userData['nama_pengguna'] ?? getPenggunaName()) ?></span>
        </div>
      </div>
    </header>

    <!-- CONTENT -->
    <main class="content">

      <!-- Summary -->
      <div>
        <div class="summary-title">Today's Summary</div>
        <div class="summary-grid">
          <div class="summary-card">
            <div class="summary-icon orange">⏳</div>
            <div>
              <div class="summary-label">Menunggu Persetujuan:</div>
              <div class="summary-value" id="stat-menunggu">—</div>
              <div style="font-size:.75rem;color:var(--neutral-500);margin-top:2px;">Permintaan</div>
            </div>
          </div>
          <div class="summary-card">
            <div class="summary-icon green">✅</div>
            <div>
              <div class="summary-label">Disetujui Hari Ini:</div>
              <div class="summary-value" id="stat-disetujui">—</div>
              <div style="font-size:.75rem;color:var(--neutral-500);margin-top:2px;">Peminjaman</div>
            </div>
          </div>
          <div class="summary-card">
            <div class="summary-icon red">❌</div>
            <div>
              <div class="summary-label">Ditolak Hari Ini:</div>
              <div class="summary-value" id="stat-ditolak">—</div>
              <div style="font-size:.75rem;color:var(--neutral-500);margin-top:2px;">Peminjaman</div>
            </div>
          </div>
          <div class="summary-card">
            <div class="summary-icon blue">📚</div>
            <div>
              <div class="summary-label">Buku Tersedia:</div>
              <div class="summary-value" id="stat-buku">—</div>
              <div style="font-size:.75rem;color:var(--neutral-500);margin-top:2px;">Buku</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="main-card">
        <div class="main-card-header">
          <div class="main-card-title">Daftar Permintaan Peminjaman Buku</div>
          <div class="filter-row">
            <div class="filter-tabs">
              <button class="tab-btn active" data-filter="Semua">Semua</button>
              <button class="tab-btn" data-filter="Pending">Menunggu Persetujuan</button>
              <button class="tab-btn" data-filter="Disetujui">Disetujui</button>
              <button class="tab-btn" data-filter="Ditolak">Ditolak</button>
            </div>
            <div class="search-wrap">
              <i class="fas fa-search"></i>
              <input type="text" id="searchInput" placeholder="Cari nama anggota atau judul buku...">
            </div>
          </div>
        </div>

        <div class="table-wrap" style="padding-top:12px;">
          <table>
            <thead>
              <tr>
                <th>ID Req</th>
                <th>Waktu Request</th>
                <th>Nama Anggota</th>
                <th>Judul Buku (Stok Tersedia)</th>
                <th>Rencana Pinjam</th>
                <th>Status</th>
                <th style="text-align:right;">Aksi</th>
              </tr>
            </thead>
            <tbody id="tableBody">
              <tr class="loading-row"><td colspan="7"><div class="spinner"></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Confirm Dialog -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <div class="confirm-icon" id="confirmIcon"></div>
    <div class="confirm-title" id="confirmTitle"></div>
    <div class="confirm-sub"   id="confirmSub"></div>
    <div class="confirm-actions">
      <button class="confirm-cancel" onclick="closeConfirm()">Batal</button>
      <button class="confirm-ok" id="confirmOkBtn" onclick="doConfirm()">Ya, Lanjutkan</button>
    </div>
  </div>
</div>

<script>
/* ─── State ─────────────────────────────────────────────── */
let currentFilter = 'Semua';
let searchTimer   = null;
let pendingAction = null;  // { id, action }
let pollingTimer  = null;

/* ─── Fetch & Render ─────────────────────────────────────── */
async function fetchData(filter, search) {
  const params = new URLSearchParams({ api: '1', filter, search });
  const res  = await fetch('permintaan.php?' + params);
  const data = await res.json();
  if (!data.ok) return;

  // Stats
  const fmt = n => Number(n).toLocaleString('id-ID');
  document.getElementById('stat-menunggu').textContent  = fmt(data.stats.menunggu);
  document.getElementById('stat-disetujui').textContent = fmt(data.stats.disetujui);
  document.getElementById('stat-ditolak').textContent   = fmt(data.stats.ditolak);
  document.getElementById('stat-buku').textContent      = fmt(data.stats.totalBuku);

  // Table
  const tbody = document.getElementById('tableBody');
  if (!data.rows.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="empty-state-ico">📋</div><div class="empty-state-title">Tidak ada data</div></div></td></tr>`;
    return;
  }

  tbody.innerHTML = data.rows.map(r => {
    const tglReq  = formatDateTime(r.tgl_request);
    const dateRange = formatDate(r.tgl_mulai) + ' - ' + formatDate(r.tgl_selesai);

    let badge = '', actions = '';
    if (r.status === 'Pending') {
      badge   = `<span class="badge badge-pending"><span class="badge-dot"></span>Pending</span>`;
      actions = `
        <button class="btn-approve" onclick="confirmAction(${r.id_permintaan},'approve')"><i class="fas fa-check"></i> Setujui</button>
        <button class="btn-reject"  onclick="confirmAction(${r.id_permintaan},'reject')"><i class="fas fa-times"></i> Tolak</button>
      `;
    } else if (r.status === 'Disetujui') {
      badge   = `<span class="badge badge-approved"><span class="badge-dot"></span>Disetujui</span>`;
      actions = `<button class="btn-detail" onclick="showDetail(${r.id_permintaan})">Detail</button>`;
    } else {
      badge   = `<span class="badge badge-rejected"><span class="badge-dot"></span>Ditolak</span>`;
      actions = `<button class="btn-detail" onclick="showDetail(${r.id_permintaan})">Detail</button>`;
    }

    const memberId = r.id_anggota ? `(M-${String(r.id_anggota).padStart(3,'0')})` : '';

    return `<tr id="row-${r.id_permintaan}">
      <td class="req-id">#${r.no_request}</td>
      <td><div>${tglReq.date}</div><div class="date-time">${tglReq.time}</div></td>
      <td><div class="member-name">${esc(r.nama_anggota)} ${memberId}</div></td>
      <td>
        <div class="book-title">${esc(r.judul_buku)}</div>
        <div class="book-stock">(Tersisa: ${r.stok})</div>
      </td>
      <td class="date-range">${dateRange}</td>
      <td>${badge}</td>
      <td style="text-align:right;"><div class="actions" style="justify-content:flex-end;">${actions}</div></td>
    </tr>`;
  }).join('');
}

function formatDate(d) {
  if (!d) return '—';
  const [y,m,day] = d.split('-');
  const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
  return `${parseInt(day)} ${months[parseInt(m)-1]} ${y}`;
}
function formatDateTime(dt) {
  if (!dt) return { date:'—', time:'' };
  const [date, time] = dt.split(' ');
  const [h, mi]      = (time||'').split(':');
  return { date: formatDate(date), time: `${h}:${mi}` };
}
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ─── Confirm Dialog ─────────────────────────────────────── */
function confirmAction(id, action) {
  pendingAction = { id, action };
  const isApprove = action === 'approve';
  document.getElementById('confirmIcon').textContent  = isApprove ? '✅' : '❌';
  document.getElementById('confirmTitle').textContent = isApprove ? 'Setujui Permintaan?' : 'Tolak Permintaan?';
  document.getElementById('confirmSub').textContent   = isApprove
    ? 'Permintaan akan disetujui dan transaksi peminjaman akan dibuat secara otomatis.'
    : 'Permintaan akan ditolak dan anggota tidak dapat meminjam buku ini dengan request ini.';
  const okBtn = document.getElementById('confirmOkBtn');
  okBtn.className = 'confirm-ok ' + (isApprove ? 'approve-ok' : 'reject-ok');
  okBtn.textContent = isApprove ? 'Ya, Setujui' : 'Ya, Tolak';
  document.getElementById('confirmOverlay').classList.add('show');
}
function closeConfirm() {
  document.getElementById('confirmOverlay').classList.remove('show');
  pendingAction = null;
}
async function doConfirm() {
  if (!pendingAction) return;
  closeConfirm();
  await sendAction(pendingAction.id, pendingAction.action);
}

/* ─── Send Action ────────────────────────────────────────── */
async function sendAction(id, action) {
  // Disable buttons on that row
  const row = document.getElementById('row-' + id);
  if (row) row.querySelectorAll('button').forEach(b => b.disabled = true);

  try {
    const res  = await fetch('permintaan.php?api=1', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ id, action }),
    });
    const data = await res.json();
    if (data.ok) {
      showToast(data.msg, 'success');
      await fetchData(currentFilter, document.getElementById('searchInput').value);
    } else {
      showToast(data.msg || 'Terjadi kesalahan', 'error');
      if (row) row.querySelectorAll('button').forEach(b => b.disabled = false);
    }
  } catch (e) {
    showToast('Gagal terhubung ke server', 'error');
    if (row) row.querySelectorAll('button').forEach(b => b.disabled = false);
  }
}

function showDetail(id) {
  showToast(`Detail permintaan #${id} — buka halaman transaksi untuk info lengkap`, 'info');
}

/* ─── Toast ──────────────────────────────────────────────── */
function showToast(msg, type = 'success') {
  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  const el = document.createElement('div');
  el.className = 'toast ' + (type === 'error' ? 'error' : type === 'success' ? 'success' : '');
  el.innerHTML = `<span>${icons[type]||''}</span><span>${msg}</span>`;
  document.getElementById('toastContainer').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

/* ─── Filter tabs ────────────────────────────────────────── */
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    fetchData(currentFilter, document.getElementById('searchInput').value);
  });
});

/* ─── Search ─────────────────────────────────────────────── */
document.getElementById('searchInput').addEventListener('input', e => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    fetchData(currentFilter, e.target.value.trim());
  }, 350);
});

/* ─── Close confirm on background click ──────────────────── */
document.getElementById('confirmOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeConfirm();
});

/* ─── Realtime polling every 15s ────────────────────────── */
function startPolling() {
  pollingTimer = setInterval(() => {
    fetchData(currentFilter, document.getElementById('searchInput').value);
  }, 15000);
}

/* ─── Init ───────────────────────────────────────────────── */
fetchData(currentFilter, '');
startPolling();
</script>
</body>
</html>