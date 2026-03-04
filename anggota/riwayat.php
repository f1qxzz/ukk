<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn=getConnection();
$id=getAnggotaId();

$trans=$conn->query("SELECT t.*,b.judul_buku,b.pengarang,b.penerbit,d.total_denda,d.status_bayar FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku LEFT JOIN denda d ON t.id_transaksi=d.id_transaksi WHERE t.id_anggota=$id ORDER BY t.tgl_pinjam DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Riwayat – Anggota</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrap">
  <?php include 'includes/nav.php'; ?>
  <div class="main-area">
    <?php include 'includes/header.php'; ?>
    <main class="content">
<div class="card">
  <div class="card-header"><h2>📋 Riwayat Peminjaman</h2></div>
  <div class="table-responsive">
  <table><thead><tr><th>No</th><th>Buku</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Status</th><th>Denda</th></tr></thead>
  <tbody>
  <?php if($trans->num_rows>0): $no=1; while($r=$trans->fetch_assoc()): ?>
  <tr>
    <td><?= $no++ ?></td>
    <td><strong><?= htmlspecialchars($r['judul_buku']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['pengarang']) ?></small></td>
    <td><?= date('d/m/Y',strtotime($r['tgl_pinjam'])) ?></td>
    <td><?= date('d/m/Y',strtotime($r['tgl_kembali_rencana'])) ?></td>
    <td>
      <?php if($r['status_transaksi']==='Pengembalian'): ?>
      <span class="badge badge-success">Dikembalikan</span>
      <?php elseif(strtotime($r['tgl_kembali_rencana'])<time()): ?>
      <span class="badge badge-danger">Terlambat</span>
      <?php else: ?><span class="badge badge-warning">Dipinjam</span><?php endif; ?>
    </td>
    <td>
      <?php if($r['total_denda']>0): ?>
      <span class="badge <?= $r['status_bayar']==='sudah'?'badge-success':'badge-danger' ?>">
        Rp <?= number_format($r['total_denda'],0,',','.') ?> – <?= $r['status_bayar']==='sudah'?'Lunas':'Belum' ?>
      </span>
      <?php else: ?><span class="text-muted">–</span><?php endif; ?>
    </td>
  </tr>
  <?php endwhile; else: ?>
  <tr><td colspan="6" class="text-center text-muted">Belum ada riwayat peminjaman. <a href="pinjam.php">Pinjam Buku</a></td></tr>
  <?php endif; ?>
  </tbody></table></div>
</div>
</div>
<script src="../assets/js/script.js"></script>
    </main>
  </div>
</div>
</body>
</html>
