<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();

$total_buku     = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];
$buku_tersedia  = $conn->query("SELECT COUNT(*) c FROM buku WHERE status='tersedia'")->fetch_assoc()['c'];
$total_anggota  = $conn->query("SELECT COUNT(*) c FROM anggota")->fetch_assoc()['c'];
$total_pinjam   = $conn->query("SELECT COUNT(*) c FROM transaksi")->fetch_assoc()['c'];
$aktif_pinjam   = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$total_denda    = $conn->query("SELECT COALESCE(SUM(total_denda),0) s FROM denda")->fetch_assoc()['s'];
$denda_belum    = $conn->query("SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'")->fetch_assoc()['s'];

$trans_all = $conn->query("SELECT t.*,a.nama_anggota,a.nis,a.kelas,b.judul_buku FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku ORDER BY t.tgl_pinjam DESC");
$denda_all = $conn->query("SELECT d.*,a.nama_anggota,b.judul_buku FROM denda d JOIN transaksi t ON d.id_transaksi=t.id_transaksi JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku ORDER BY d.id_denda DESC");

$page_title = 'Laporan';
$page_sub   = 'Ringkasan data sistem perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan — Admin Perpustakaan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
@media print {
  .sidebar, .topbar, .no-print { display:none!important }
  .main-area { margin-left:0!important }
  .content { padding:16px!important }
  .card { break-inside:avoid; box-shadow:none!important; border:1px solid #ddd!important; }
  body { background:#fff!important; }
}
.print-header { display:none; }
@media print { .print-header { display:block; text-align:center; margin-bottom:20px; } }
</style>
</head>
<body>
<div class="app-wrap">
  <?php include 'includes/nav.php'; ?>
  <div class="main-area">
    <?php include 'includes/header.php'; ?>
    <main class="content">

      <div class="print-bar no-print">
        <div>
          <div class="print-bar-title">Laporan Perpustakaan Digital</div>
          <div class="print-bar-sub">Dicetak: <?= date('d F Y, H:i') ?> WIB</div>
        </div>
        <button onclick="window.print()" class="btn btn-primary">
          <svg viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Cetak Laporan
        </button>
      </div>

      <!-- Print header -->
      <div class="print-header">
        <h2 style="font-family:Georgia,serif;font-size:1.4rem">📚 Perpustakaan Digital</h2>
        <p style="color:#666;font-size:.85rem">Laporan Sistem · <?= date('d F Y') ?></p>
        <hr style="margin:10px 0">
      </div>

      <!-- Summary Stats -->
      <div class="stats-grid mb-24">
        <div class="stat-card sc-navy"><div class="stat-info"><div class="stat-label">Total Buku</div><div class="stat-val"><?= $total_buku ?></div><div class="stat-sub"><?= $buku_tersedia ?> tersedia</div></div></div>
        <div class="stat-card sc-sage"><div class="stat-info"><div class="stat-label">Total Anggota</div><div class="stat-val"><?= $total_anggota ?></div></div></div>
        <div class="stat-card sc-rust"><div class="stat-info"><div class="stat-label">Total Pinjaman</div><div class="stat-val"><?= $total_pinjam ?></div><div class="stat-sub"><?= $aktif_pinjam ?> aktif</div></div></div>
        <div class="stat-card sc-gold"><div class="stat-info"><div class="stat-label">Total Denda</div><div class="stat-val" style="font-size:1.2rem">Rp <?= number_format($total_denda,0,',','.') ?></div><div class="stat-sub">belum: Rp <?= number_format($denda_belum,0,',','.') ?></div></div></div>
      </div>

      <!-- Transactions Report -->
      <div class="card mb-24">
        <div class="card-header">
          <div class="card-title">Laporan Transaksi Peminjaman</div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Anggota</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Buku</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($trans_all && $trans_all->num_rows > 0): $n=1; ?>
                <?php while($r=$trans_all->fetch_assoc()): ?>
                  <?php
                  $late = $r['status_transaksi']==='Peminjaman' && strtotime($r['tgl_kembali_rencana']) < time();
                  $sc = $r['status_transaksi']==='Pengembalian' ? 'status-kembali' : ($late?'status-terlambat':'status-dipinjam');
                  $sl = $r['status_transaksi']==='Pengembalian' ? 'Kembali' : ($late?'Terlambat':'Dipinjam');
                  ?>
                  <tr>
                    <td class="text-muted text-sm"><?= $n++ ?></td>
                    <td><?= htmlspecialchars($r['nama_anggota']) ?></td>
                    <td class="text-sm"><?= htmlspecialchars($r['nis']) ?></td>
                    <td class="text-sm"><?= htmlspecialchars($r['kelas']) ?></td>
                    <td><?= htmlspecialchars($r['judul_buku']) ?></td>
                    <td><?= date('d/m/Y',strtotime($r['tgl_pinjam'])) ?></td>
                    <td><?= date('d/m/Y',strtotime($r['tgl_kembali_rencana'])) ?></td>
                    <td><?= $r['tgl_kembali_aktual'] ? date('d/m/Y',strtotime($r['tgl_kembali_aktual'])) : '—' ?></td>
                    <td><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="9" style="text-align:center;padding:24px;color:var(--muted)">Belum ada data</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Fines Report -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Laporan Denda</div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Anggota</th>
                <th>Buku</th>
                <th>Jumlah Hari</th>
                <th>Total Denda</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($denda_all && $denda_all->num_rows > 0): $n=1; ?>
                <?php while($r=$denda_all->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted text-sm"><?= $n++ ?></td>
                  <td><?= htmlspecialchars($r['nama_anggota']) ?></td>
                  <td><?= htmlspecialchars($r['judul_buku']) ?></td>
                  <td><?= $r['jumlah_hari'] ?> hari</td>
                  <td>Rp <?= number_format($r['total_denda'],0,',','.') ?></td>
                  <td><span class="badge <?= $r['status_bayar']==='lunas'?'status-kembali':'status-terlambat' ?>">
                    <?= $r['status_bayar']==='lunas' ? '✓ Lunas' : '✕ Belum' ?>
                  </span></td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--muted)">Belum ada denda</td></tr>
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
