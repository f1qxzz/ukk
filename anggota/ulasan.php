<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn=getConnection();
$id=getAnggotaId();
$msg=''; $msgType='';

// TAMBAH ULASAN
if (isset($_POST['tambah'])) {
    $id_buku=(int)$_POST['id_buku']; $rating=(int)$_POST['rating']; $ulasan=trim($_POST['ulasan']);
    // Cek apakah pernah meminjam buku ini
    $chk=$conn->query("SELECT id_transaksi FROM transaksi WHERE id_anggota=$id AND id_buku=$id_buku")->num_rows;
    if($chk==0){ $msg='Anda hanya bisa mengulas buku yang pernah dipinjam!'; $msgType='warning'; }
    else {
        $dupl=$conn->query("SELECT id_ulasan FROM ulasan_buku WHERE id_anggota=$id AND id_buku=$id_buku")->num_rows;
        if($dupl>0){ $msg='Anda sudah memberikan ulasan untuk buku ini!'; $msgType='warning'; }
        else {
            $s=$conn->prepare("INSERT INTO ulasan_buku(id_anggota,id_buku,rating,ulasan) VALUES(?,?,?,?)");
            $s->bind_param("iiis",$id,$id_buku,$rating,$ulasan);
            $msg=$s->execute()?'Ulasan berhasil ditambahkan!':'Gagal!'; $msgType='success'; $s->close();
        }
    }
}

// HAPUS ULASAN
if (isset($_POST['hapus'])) {
    $id_ulasan=(int)$_POST['id_ulasan'];
    $s=$conn->prepare("DELETE FROM ulasan_buku WHERE id_ulasan=? AND id_anggota=?");
    $s->bind_param("ii",$id_ulasan,$id);
    $msg=$s->execute()?'Ulasan dihapus!':'Gagal!'; $msgType='success'; $s->close();
}

// Buku yang pernah dipinjam anggota ini
$buku_pinjam=$conn->query("SELECT DISTINCT b.id_buku,b.judul_buku FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_anggota=$id ORDER BY b.judul_buku");

// Ulasan saya
$ulasan_saya=$conn->query("SELECT u.*,b.judul_buku FROM ulasan_buku u JOIN buku b ON u.id_buku=b.id_buku WHERE u.id_anggota=$id ORDER BY u.created_at DESC");

// Semua ulasan (buku yang pernah dipinjam)
$semua_ulasan=$conn->query("SELECT u.*,b.judul_buku,a.nama_anggota FROM ulasan_buku u JOIN buku b ON u.id_buku=b.id_buku JOIN anggota a ON u.id_anggota=a.id_anggota ORDER BY u.created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ulasan Buku – Anggota</title>
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

<!-- FORM TAMBAH ULASAN -->
<div class="card">
  <div class="card-header"><h2>⭐ Tambah Ulasan Buku</h2></div>
  <div class="card-body">
    <?php if($buku_pinjam->num_rows>0): ?>
    <form method="POST">
      <div class="form-row">
        <div class="form-group"><label>Pilih Buku *</label>
          <select name="id_buku" class="form-control" required>
            <option value="">-- Pilih Buku yang Pernah Dipinjam --</option>
            <?php while($b=$buku_pinjam->fetch_assoc()): ?>
            <option value="<?= $b['id_buku'] ?>"><?= htmlspecialchars($b['judul_buku']) ?></option>
            <?php endwhile; ?>
          </select></div>
        <div class="form-group"><label>Rating *</label>
          <div style="display:flex;align-items:center;gap:5px;margin-top:8px;">
            <?php for($i=1;$i<=5;$i++): ?>
            <span class="star-input" data-value="<?= $i ?>" style="font-size:2rem;cursor:pointer;color:#ccc;">★</span>
            <?php endfor; ?>
            <input type="hidden" name="rating" id="rating_value" value="5">
          </div></div>
      </div>
      <div class="form-group"><label>Ulasan *</label>
        <textarea name="ulasan" class="form-control" rows="3" required placeholder="Tulis ulasan Anda..."></textarea></div>
      <button type="submit" name="tambah" class="btn btn-primary">Kirim Ulasan</button>
    </form>
    <?php else: ?>
    <p class="text-muted">Anda belum memiliki riwayat peminjaman. <a href="pinjam.php">Pinjam buku dulu</a></p>
    <?php endif; ?>
  </div>
</div>

<!-- ULASAN SAYA -->
<div class="card">
  <div class="card-header"><h2>Ulasan Saya</h2></div>
  <div class="table-responsive">
  <table><thead><tr><th>Buku</th><th>Rating</th><th>Ulasan</th><th>Tanggal</th><th>Aksi</th></tr></thead>
  <tbody>
  <?php if($ulasan_saya->num_rows>0): while($r=$ulasan_saya->fetch_assoc()): ?>
  <tr>
    <td><?= htmlspecialchars($r['judul_buku']) ?></td>
    <td><span class="stars"><?= str_repeat('★',$r['rating']) ?><span style="color:#ccc"><?= str_repeat('★',5-$r['rating']) ?></span></span></td>
    <td><?= htmlspecialchars($r['ulasan']) ?></td>
    <td><?= date('d/m/Y',strtotime($r['created_at'])) ?></td>
    <td>
      <form method="POST" onsubmit="return confirm('Hapus ulasan ini?')">
        <input type="hidden" name="id_ulasan" value="<?= $r['id_ulasan'] ?>">
        <button type="submit" name="hapus" class="btn btn-danger btn-sm">Hapus</button>
      </form>
    </td>
  </tr>
  <?php endwhile; else: ?>
  <tr><td colspan="5" class="text-center text-muted">Belum ada ulasan.</td></tr>
  <?php endif; ?>
  </tbody></table></div>
</div>

<!-- ULASAN KOMUNITAS -->
<div class="card">
  <div class="card-header"><h2>Ulasan Terbaru</h2></div>
  <div class="table-responsive">
  <table><thead><tr><th>Buku</th><th>Anggota</th><th>Rating</th><th>Ulasan</th><th>Tanggal</th></tr></thead>
  <tbody>
  <?php while($r=$semua_ulasan->fetch_assoc()): ?>
  <tr>
    <td><?= htmlspecialchars($r['judul_buku']) ?></td>
    <td><?= htmlspecialchars($r['nama_anggota']) ?></td>
    <td><span class="stars"><?= str_repeat('★',$r['rating']) ?></span></td>
    <td><?= htmlspecialchars($r['ulasan']) ?></td>
    <td><?= date('d/m/Y',strtotime($r['created_at'])) ?></td>
  </tr>
  <?php endwhile; ?>
  </tbody></table></div>
</div>
</div>
<script src="../assets/js/script.js"></script>
    </main>
  </div>
</div>
</body>
</html>
