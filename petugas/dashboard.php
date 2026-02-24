<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();
$conn = getConnection();

function getCount($conn, $q, $f='c') { return $conn->query($q)->fetch_assoc()[$f] ?? 0; }

$total_buku     = getCount($conn,"SELECT COUNT(*) c FROM buku");
$buku_tersedia  = getCount($conn,"SELECT COUNT(*) c FROM buku WHERE status='tersedia'");
$total_anggota  = getCount($conn,"SELECT COUNT(*) c FROM anggota");
$aktif_pinjam   = getCount($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'");
$terlambat      = getCount($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < NOW()");
$total_denda    = getCount($conn,"SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'",'s');

$recent = $conn->query("
    SELECT t.*, a.nama_anggota, a.nis, b.judul_buku
    FROM transaksi t
    JOIN anggota a ON t.id_anggota=a.id_anggota
    JOIN buku b ON t.id_buku=b.id_buku
    WHERE t.status_transaksi='Peminjaman'
    ORDER BY t.tgl_pinjam DESC LIMIT 8
");
closeConnection($conn);

$page_title = 'Dashboard';
$page_sub   = 'Ringkasan Operasional · Petugas Panel';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Petugas — Perpustakaan Digital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrap">
  <?php include 'includes/nav.php'; ?>
  <div class="main-area">
    <?php include 'includes/header.php'; ?>
    <main class="content">

      <!-- Welcome -->
      <div class="welcome-banner" style="background:linear-gradient(135deg,var(--navy) 0%,#1e3a5f 100%)">
        <div>
          <div class="welcome-title">Halo, <?= htmlspecialchars(getPenggunaName()) ?> 👨‍💼</div>
          <div class="welcome-sub">Kelola peminjaman dan pengembalian buku harian di sini.</div>
        </div>
        <div class="welcome-actions">
          <a href="transaksi.php" class="btn-welcome">+ Catat Pinjam</a>
          <a href="laporan.php" class="btn-welcome-ghost">Cetak Laporan</a>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid mb-24">
        <div class="stat-card sc-navy">
          <div class="stat-info">
            <div class="stat-label">Total Buku</div>
            <div class="stat-val"><?= $total_buku ?></div>
            <div class="stat-sub"><?= $buku_tersedia ?> tersedia</div>
          </div>
          <div class="stat-icon" style="--si-bg:rgba(44,79,124,.09);--si-c:var(--navy)">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
        </div>
        <div class="stat-card sc-rust">
          <div class="stat-info">
            <div class="stat-label">Aktif Pinjam</div>
            <div class="stat-val"><?= $aktif_pinjam ?></div>
            <div class="stat-sub"><?= $terlambat ?> terlambat</div>
          </div>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          </div>
        </div>
        <div class="stat-card sc-sage">
          <div class="stat-info">
            <div class="stat-label">Total Anggota</div>
            <div class="stat-val"><?= $total_anggota ?></div>
            <div class="stat-sub">terdaftar</div>
          </div>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
        </div>
        <div class="stat-card sc-gold">
          <div class="stat-info">
            <div class="stat-label">Denda Belum Lunas</div>
            <div class="stat-val" style="font-size:1.3rem">Rp <?= number_format($total_denda,0,',','.') ?></div>
            <div class="stat-sub">perlu diproses</div>
          </div>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
        </div>
      </div>

      <!-- Active Loans Table -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
            Peminjaman Aktif
          </div>
          <a href="transaksi.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Anggota</th>
                <th>NIS</th>
                <th>Buku</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recent && $recent->num_rows > 0): ?>
                <?php while($r=$recent->fetch_assoc()): ?>
                  <?php $late = strtotime($r['tgl_kembali_rencana']) < time(); ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($r['nama_anggota']) ?></strong></td>
                    <td class="text-muted text-sm"><?= htmlspecialchars($r['nis']) ?></td>
                    <td><?= htmlspecialchars($r['judul_buku']) ?></td>
                    <td><?= date('d/m/Y',strtotime($r['tgl_pinjam'])) ?></td>
                    <td><?= date('d/m/Y',strtotime($r['tgl_kembali_rencana'])) ?></td>
                    <td>
                      <span class="badge <?= $late?'status-terlambat':'status-dipinjam' ?>">
                        <?= $late ? '⚠ Terlambat' : '⇄ Dipinjam' ?>
                      </span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6">
                  <div class="empty-state">
                    <div class="empty-state-ico">✅</div>
                    <div class="empty-state-title">Tidak ada pinjaman aktif</div>
                    <div class="empty-state-sub">Semua buku sudah dikembalikan.</div>
                  </div>
                </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>
</body>
</html>
