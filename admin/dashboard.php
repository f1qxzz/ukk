<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();

function getCount($conn, $q, $f='c') { return $conn->query($q)->fetch_assoc()[$f] ?? 0; }

$total_buku    = getCount($conn,"SELECT COUNT(*) c FROM buku");
$buku_tersedia = getCount($conn,"SELECT COUNT(*) c FROM buku WHERE status='tersedia'");
$aktif_pinjam  = getCount($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'");
$total_anggota = getCount($conn,"SELECT COUNT(*) c FROM anggota");
$total_denda   = getCount($conn,"SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'", 's');
$terlambat     = getCount($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < NOW()");
$total_pengguna= getCount($conn,"SELECT COUNT(*) c FROM pengguna");
$buku_dipinjam = $total_buku - $buku_tersedia;

$recent = $conn->query("
    SELECT t.*, a.nama_anggota, b.judul_buku
    FROM transaksi t
    JOIN anggota a ON t.id_anggota=a.id_anggota
    JOIN buku b ON t.id_buku=b.id_buku
    ORDER BY t.tgl_pinjam DESC LIMIT 8
");

$page_title = 'Dashboard';
$page_sub   = 'Ringkasan Sistem · Admin Panel';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin — Perpustakaan Digital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;0,9..144,700;1,9..144,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrap">

  <?php include 'includes/nav.php'; ?>

  <div class="main-area">
    <?php include 'includes/header.php'; ?>

    <main class="content">

      <!-- Welcome Banner -->
      <div class="welcome-banner">
        <div>
          <div class="welcome-title">Selamat Datang, <?= htmlspecialchars(getPenggunaName()) ?> 🛡️</div>
          <div class="welcome-sub">Kelola seluruh sistem perpustakaan dari satu tempat.</div>
        </div>
        <div class="welcome-actions">
          <a href="buku.php" class="btn-welcome">+ Tambah Buku</a>
          <a href="laporan.php" class="btn-welcome-ghost">Lihat Laporan</a>
        </div>
      </div>

      <!-- Stats Row 1 -->
      <div class="stats-grid mb-24">
        <div class="stat-card sc-navy">
          <div class="stat-info">
            <div class="stat-label">Total Buku</div>
            <div class="stat-val"><?= $total_buku ?></div>
            <div class="stat-sub"><?= $buku_tersedia ?> tersedia</div>
          </div>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
        </div>
        <div class="stat-card sc-rust">
          <div class="stat-info">
            <div class="stat-label">Dipinjam</div>
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
            <div class="stat-sub"><?= $total_pengguna ?> pengguna sistem</div>
          </div>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
        </div>
        <div class="stat-card sc-gold">
          <div class="stat-info">
            <div class="stat-label">Denda Belum Bayar</div>
            <div class="stat-val" style="font-size:1.3rem">Rp <?= number_format($total_denda,0,',','.') ?></div>
            <div class="stat-sub">perlu diselesaikan</div>
          </div>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
        </div>
      </div>

      <!-- Recent Transactions Table -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Transaksi Terbaru
          </div>
          <a href="transaksi.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Anggota</th>
                <th>Buku</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recent && $recent->num_rows > 0): ?>
                <?php while($row = $recent->fetch_assoc()): ?>
                  <?php
                    $is_late = $row['status_transaksi']==='Peminjaman' && strtotime($row['tgl_kembali_rencana']) < time();
                    $status_class = match(true) {
                      $row['status_transaksi']==='Pengembalian' => 'badge status-kembali',
                      $is_late => 'badge status-terlambat',
                      default => 'badge status-dipinjam'
                    };
                    $status_label = match(true) {
                      $row['status_transaksi']==='Pengembalian' => '✓ Kembali',
                      $is_late => '⚠ Terlambat',
                      default => '⇄ Dipinjam'
                    };
                  ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($row['nama_anggota']) ?></strong></td>
                    <td><?= htmlspecialchars($row['judul_buku']) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tgl_pinjam'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tgl_kembali_rencana'])) ?></td>
                    <td><span class="<?= $status_class ?>"><?= $status_label ?></span></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" style="text-align:center; padding:36px; color:var(--muted)">Belum ada transaksi</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>
<script>
// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded',()=>{
  const sidebar = document.querySelector('.sidebar');
  document.querySelector('.hamburger-btn')?.addEventListener('click',()=>sidebar.classList.toggle('open'));
});
</script>
</body>
</html>
