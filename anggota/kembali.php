<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn=getConnection();
$id=getAnggotaId();
$msg=''; $msgType='';

if (isset($_POST['kembalikan'])) {
    $id_trans=(int)$_POST['id_transaksi'];
    // Validasi milik anggota ini
    $chk=$conn->query("SELECT * FROM transaksi WHERE id_transaksi=$id_trans AND id_anggota=$id AND status_transaksi='Peminjaman'")->fetch_assoc();
    if(!$chk){ $msg='Transaksi tidak valid!'; $msgType='danger'; }
    else {
        $now=date('Y-m-d H:i:s');
        $conn->query("UPDATE transaksi SET status_transaksi='Pengembalian',tgl_kembali_aktual='$now' WHERE id_transaksi=$id_trans");
        $conn->query("UPDATE buku SET status='tersedia' WHERE id_buku={$chk['id_buku']}");
        // Hitung denda
        if(strtotime($now)>strtotime($chk['tgl_kembali_rencana'])){
            $hari=max(1,floor((time()-strtotime($chk['tgl_kembali_rencana']))/86400));
            $total=$hari*DENDA_PER_HARI;
            $conn->query("INSERT IGNORE INTO denda(id_transaksi,jumlah_hari,tarif_per_hari,total_denda) VALUES($id_trans,$hari,".DENDA_PER_HARI.",$total)");
            $msg="Pengembalian dicatat. Anda terlambat $hari hari. Denda: Rp ".number_format($total,0,',','.').". Harap bayar ke petugas.";
            $msgType='warning';
        } else { $msg='Pengembalian berhasil! Terima kasih.'; $msgType='success'; }
    }
}

$aktif=$conn->query("SELECT t.*,b.judul_buku,b.pengarang FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_anggota=$id AND t.status_transaksi='Peminjaman' ORDER BY t.tgl_kembali_rencana");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pengembalian – Anggota</title>
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

<div class="card">
  <div class="card-header"><h2>↩️ Pengembalian Buku</h2></div>
  <div class="table-responsive">
  <table><thead><tr><th>Buku</th><th>Tgl Pinjam</th><th>Tgl Kembali Rencana</th><th>Sisa Waktu</th><th>Aksi</th></tr></thead>
  <tbody>
  <?php if($aktif->num_rows>0): while($r=$aktif->fetch_assoc()):
    $sisa=floor((strtotime($r['tgl_kembali_rencana'])-time())/86400);
    $late=$sisa<0;
  ?>
  <tr>
    <td><strong><?= htmlspecialchars($r['judul_buku']) ?></strong><br><small><?= htmlspecialchars($r['pengarang']) ?></small></td>
    <td><?= date('d/m/Y',strtotime($r['tgl_pinjam'])) ?></td>
    <td><?= date('d/m/Y',strtotime($r['tgl_kembali_rencana'])) ?></td>
    <td>
      <?php if($late):
        $d=abs($sisa); $denda_est=$d*DENDA_PER_HARI;
      ?><span class="badge badge-danger">Terlambat <?= $d ?> hari</span>
        <br><small class="text-muted">Denda estimasi: Rp <?= number_format($denda_est,0,',','.') ?></small>
      <?php elseif($sisa<=2): ?><span class="badge badge-warning">Segera kembalikan (<?= $sisa ?> hari)</span>
      <?php else: ?><span class="badge badge-success">Masih <?= $sisa ?> hari</span>
      <?php endif; ?>
    </td>
    <td>
      <form method="POST" onsubmit="return confirm('Konfirmasi pengembalian buku ini?')">
        <input type="hidden" name="id_transaksi" value="<?= $r['id_transaksi'] ?>">
        <button type="submit" name="kembalikan" class="btn btn-success btn-sm">Kembalikan</button>
      </form>
    </td>
  </tr>
  <?php endwhile; else: ?>
  <tr><td colspan="5" class="text-center text-muted">Tidak ada buku yang perlu dikembalikan. <a href="pinjam.php">Pinjam buku</a></td></tr>
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
