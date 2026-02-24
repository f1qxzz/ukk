<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn=getConnection();
$id=getAnggotaId();
$msg=''; $msgType='';

if (isset($_POST['pinjam'])) {
    $id_buku=(int)$_POST['id_buku'];
    // Cek buku tersedia
    $chk=$conn->query("SELECT status,judul_buku FROM buku WHERE id_buku=$id_buku")->fetch_assoc();
    if(!$chk){ $msg='Buku tidak ditemukan!'; $msgType='danger'; }
    elseif($chk['status']==='tidak'){ $msg='Buku sedang tidak tersedia!'; $msgType='warning'; }
    else {
        // Cek apakah anggota sudah pinjam buku ini
        $dupl=$conn->query("SELECT id_transaksi FROM transaksi WHERE id_anggota=$id AND id_buku=$id_buku AND status_transaksi='Peminjaman'")->num_rows;
        if($dupl>0){ $msg='Anda sudah meminjam buku ini!'; $msgType='warning'; }
        else {
            $tgl_pinjam=date('Y-m-d H:i:s');
            $tgl_kembali=date('Y-m-d H:i:s',strtotime('+7 days'));
            $s=$conn->prepare("INSERT INTO transaksi(id_anggota,id_buku,tgl_pinjam,tgl_kembali_rencana,status_transaksi) VALUES(?,?,?,?,'Peminjaman')");
            $s->bind_param("iiss",$id,$id_buku,$tgl_pinjam,$tgl_kembali);
            if($s->execute()){
                $conn->query("UPDATE buku SET status='tidak' WHERE id_buku=$id_buku");
                $msg='Permintaan peminjaman berhasil! Buku akan siap diambil.'; $msgType='success';
            } else { $msg='Gagal: '.$conn->error; $msgType='danger'; }
            $s->close();
        }
    }
}

$search=isset($_GET['search'])?$_GET['search']:'';
$filter_kat=isset($_GET['kat'])?(int)$_GET['kat']:0;
$q="SELECT b.*,k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.id_kategori=k.id_kategori WHERE 1=1";
if($search) $q.=" AND (b.judul_buku LIKE '%$search%' OR b.pengarang LIKE '%$search%')";
if($filter_kat) $q.=" AND b.id_kategori=$filter_kat";
$q.=" ORDER BY b.judul_buku";
$books=$conn->query($q);
$cats=$conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Peminjaman – Anggota</title>
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
  <div class="card-header"><h2>📖 Katalog & Peminjaman Buku</h2></div>
  <div class="card-body">
    <div class="search-box">
      <form method="GET" style="display:flex;gap:10px;width:100%;">
        <input type="text" name="search" class="form-control" placeholder="Cari judul / pengarang..." value="<?= htmlspecialchars($search) ?>">
        <select name="kat" class="form-control" style="max-width:180px">
          <option value="">Semua Kategori</option>
          <?php $cats->data_seek(0); while($c=$cats->fetch_assoc()): ?>
          <option value="<?= $c['id_kategori'] ?>" <?= $filter_kat==$c['id_kategori']?'selected':'' ?>><?= htmlspecialchars($c['nama_kategori']) ?></option>
          <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary">Cari</button>
        <a href="pinjam.php" class="btn btn-secondary">Reset</a>
      </form>
    </div>
    <div class="table-responsive">
    <table><thead><tr><th>Judul</th><th>Kategori</th><th>Pengarang</th><th>Tahun</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php if($books->num_rows>0): while($r=$books->fetch_assoc()): ?>
    <tr>
      <td><strong><?= htmlspecialchars($r['judul_buku']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['penerbit']) ?></small></td>
      <td><?= htmlspecialchars($r['nama_kategori']??'-') ?></td>
      <td><?= htmlspecialchars($r['pengarang']) ?></td>
      <td><?= $r['tahun_terbit'] ?></td>
      <td><span class="badge <?= $r['status']==='tersedia'?'badge-success':'badge-danger' ?>"><?= $r['status']==='tersedia'?'✓ Tersedia':'✗ Dipinjam' ?></span></td>
      <td>
        <?php if($r['status']==='tersedia'): ?>
        <form method="POST" onsubmit="return confirm('Pinjam buku ini?')">
          <input type="hidden" name="id_buku" value="<?= $r['id_buku'] ?>">
          <button type="submit" name="pinjam" class="btn btn-primary btn-sm">Pinjam</button>
        </form>
        <?php else: ?><span class="text-muted">Tidak tersedia</span><?php endif; ?>
      </td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="6" class="text-center text-muted">Tidak ada buku ditemukan.</td></tr>
    <?php endif; ?>
    </tbody></table></div>
  </div>
</div>
<div class="alert alert-info">ℹ️ Durasi peminjaman 7 hari. Denda keterlambatan Rp <?= number_format(DENDA_PER_HARI,0,',','.') ?>/hari.</div>
</div>
<script src="../assets/js/script.js"></script>
    </main>
  </div>
</div>
</body>
</html>
