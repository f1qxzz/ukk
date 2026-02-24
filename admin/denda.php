<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();
$msg = ''; $msgType = '';

// Proses pembayaran denda
if (isset($_POST['bayar'])) {
    $id = (int)$_POST['id_denda'];
    $s = $conn->prepare("UPDATE denda SET status_bayar='sudah',tgl_bayar=NOW() WHERE id_denda=?");
    $s->bind_param("i", $id);
    $msg = $s->execute()?'Denda berhasil dibayar!':'Gagal!'; $msgType='success'; $s->close();
}

// Hitung dan buat denda otomatis untuk yang terlambat
$overdue = $conn->query("SELECT t.id_transaksi,t.tgl_kembali_rencana FROM transaksi t
    LEFT JOIN denda d ON t.id_transaksi=d.id_transaksi
    WHERE t.status_transaksi='Peminjaman' AND t.tgl_kembali_rencana < NOW() AND d.id_denda IS NULL");
while ($od = $overdue->fetch_assoc()) {
    $hari = max(1, floor((time() - strtotime($od['tgl_kembali_rencana'])) / 86400));
    $total = $hari * DENDA_PER_HARI;
    $conn->query("INSERT INTO denda(id_transaksi,jumlah_hari,tarif_per_hari,total_denda) VALUES({$od['id_transaksi']},$hari,".DENDA_PER_HARI.",$total)");
}

$filter = isset($_GET['f'])?$_GET['f']:'semua';
$q = "SELECT d.*,t.tgl_pinjam,t.tgl_kembali_rencana,a.nama_anggota,a.nis,b.judul_buku
      FROM denda d
      JOIN transaksi t ON d.id_transaksi=t.id_transaksi
      JOIN anggota a ON t.id_anggota=a.id_anggota
      JOIN buku b ON t.id_buku=b.id_buku";
if ($filter==='belum') $q .= " WHERE d.status_bayar='belum'";
elseif ($filter==='sudah') $q .= " WHERE d.status_bayar='sudah'";
$q .= " ORDER BY d.created_at DESC";
$dendas = $conn->query($q);
$total_belum = $conn->query("SELECT SUM(total_denda) s FROM denda WHERE status_bayar='belum'")->fetch_assoc()['s']??0;
$page_title = 'Monitoring Denda';
$page_sub   = 'Kelola denda keterlambatan pengembalian';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Denda — Admin Perpustakaan</title>
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
<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="stats-grid">
  <div class="stat-card red"><div class="stat-value" style="color:var(--danger);font-size:1.4rem;">Rp <?= number_format($total_belum,0,',','.') ?></div><div class="stat-label">Total Denda Belum Bayar</div></div>
</div>

<div class="card">
  <div class="card-header"><h2>Monitoring Denda Buku</h2>
    <div class="action-buttons">
      <a href="?f=semua"  class="btn btn-sm <?= $filter==='semua'?'btn-primary':'btn-secondary' ?>">Semua</a>
      <a href="?f=belum"  class="btn btn-sm <?= $filter==='belum'?'btn-primary':'btn-secondary' ?>">Belum Bayar</a>
      <a href="?f=sudah"  class="btn btn-sm <?= $filter==='sudah'?'btn-primary':'btn-secondary' ?>">Sudah Bayar</a>
    </div>
  </div>
  <div class="table-responsive">
  <table><thead><tr><th>ID</th><th>Anggota</th><th>Buku</th><th>Tgl Kembali Rencana</th><th>Telat (Hari)</th><th>Total Denda</th><th>Status</th><th>Aksi</th></tr></thead>
  <tbody>
  <?php if($dendas->num_rows>0): while($r=$dendas->fetch_assoc()): ?>
  <tr>
    <td><?= $r['id_denda'] ?></td>
    <td><strong><?= htmlspecialchars($r['nama_anggota']) ?></strong><br><small><?= htmlspecialchars($r['nis']) ?></small></td>
    <td><?= htmlspecialchars($r['judul_buku']) ?></td>
    <td><?= date('d/m/Y',strtotime($r['tgl_kembali_rencana'])) ?></td>
    <td><?= $r['jumlah_hari'] ?> hari</td>
    <td><strong>Rp <?= number_format($r['total_denda'],0,',','.') ?></strong></td>
    <td><span class="badge <?= $r['status_bayar']==='sudah'?'badge-success':'badge-danger' ?>"><?= $r['status_bayar']==='sudah'?'Lunas':'Belum' ?></span></td>
    <td>
      <?php if($r['status_bayar']==='belum'): ?>
      <form method="POST" style="display:inline" onsubmit="return confirm('Konfirmasi pembayaran denda?')">
        <input type="hidden" name="id_denda" value="<?= $r['id_denda'] ?>">
        <button type="submit" name="bayar" class="btn btn-success btn-sm">Bayar</button>
      </form>
      <?php else: ?>
      <small class="text-muted"><?= date('d/m/Y',strtotime($r['tgl_bayar'])) ?></small>
      <?php endif; ?>
    </td>
  </tr>
  <?php endwhile; else: ?>
  <tr><td colspan="8" class="text-center text-muted">Tidak ada data denda.</td></tr>
  <?php endif; ?>
  </tbody></table></div>
</div>
<script src="../assets/js/script.js"></script>
  </main>
  </div>
</div>
</body></html>
