<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();
$msg = ''; $msgType = '';

// Add transaction
if (isset($_POST['add'])) {
    $id_ang = (int)$_POST['id_anggota'];
    $id_buku = (int)$_POST['id_buku'];
    $tgl_pinjam = $_POST['tgl_pinjam'];
    $tgl_rencana = $_POST['tgl_kembali_rencana'];
    // Check stock
    $stok = $conn->query("SELECT stok FROM buku WHERE id_buku=$id_buku")->fetch_assoc()['stok'] ?? 0;
    if ($stok < 1) {
        $msg = 'Stok buku habis!'; $msgType='warning';
    } else {
        $s = $conn->prepare("INSERT INTO transaksi(id_anggota,id_buku,tgl_pinjam,tgl_kembali_rencana,status_transaksi) VALUES(?,?,?,?,'Peminjaman')");
        $s->bind_param("iiss",$id_ang,$id_buku,$tgl_pinjam,$tgl_rencana);
        if ($s->execute()) {
            $conn->query("UPDATE buku SET stok=stok-1, status=IF(stok-1>0,'tersedia','tidak tersedia') WHERE id_buku=$id_buku");
            $msg='Transaksi berhasil dicatat!'; $msgType='success';
        } else { $msg='Gagal: '.$conn->error; $msgType='danger'; }
        $s->close();
    }
}
// Return book
if (isset($_POST['return'])) {
    $id_t = (int)$_POST['id_transaksi'];
    $tgl_kembali = $_POST['tgl_kembali'];
    $t = $conn->query("SELECT * FROM transaksi WHERE id_transaksi=$id_t")->fetch_assoc();
    $days = max(0, (strtotime($tgl_kembali)-strtotime($t['tgl_kembali_rencana']))/(60*60*24));
    $denda_total = ceil($days) * 1000;
    $s = $conn->prepare("UPDATE transaksi SET status_transaksi='Pengembalian',tgl_kembali_aktual=? WHERE id_transaksi=?");
    $s->bind_param("si",$tgl_kembali,$id_t); $s->execute(); $s->close();
    $conn->query("UPDATE buku SET stok=stok+1,status='tersedia' WHERE id_buku={$t['id_buku']}");
    if ($denda_total > 0) {
        $conn->query("INSERT INTO denda(id_transaksi,jumlah_hari,total_denda,status_bayar) VALUES($id_t,".ceil($days).",$denda_total,'belum')");
        $msg="Buku dikembalikan. Denda: Rp ".number_format($denda_total,0,',','.'); $msgType='warning';
    } else {
        $msg='Buku berhasil dikembalikan. Tidak ada denda.'; $msgType='success';
    }
}

$anggota_list = $conn->query("SELECT * FROM anggota ORDER BY nama_anggota");
$buku_list = $conn->query("SELECT * FROM buku WHERE stok>0 ORDER BY judul_buku");

$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$q = "SELECT t.*,a.nama_anggota,a.nis,b.judul_buku FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku WHERE 1=1";
if ($filter_status) $q .= " AND t.status_transaksi='$filter_status'";
if ($search) $q .= " AND (a.nama_anggota LIKE '%$search%' OR b.judul_buku LIKE '%$search%')";
$q .= " ORDER BY t.tgl_pinjam DESC";
$transaksi = $conn->query($q);

$returnItem = null;
if (isset($_GET['return'])) {
    $id = (int)$_GET['return'];
    $returnItem = $conn->query("SELECT t.*,a.nama_anggota,b.judul_buku FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_transaksi=$id")->fetch_assoc();
}

$page_title = 'Transaksi';
$page_sub   = 'Manajemen Peminjaman & Pengembalian';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Transaksi — Admin Perpustakaan</title>
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

      <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= $msg ?></div>
      <?php endif; ?>

      <div class="page-header">
        <div>
          <div class="page-header-title">Transaksi Peminjaman</div>
          <div class="page-header-sub">Catat peminjaman dan proses pengembalian buku</div>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Catat Pinjam
        </button>
      </div>

      <div class="card">
        <form method="GET" class="filter-bar">
          <div class="search-wrap">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Cari anggota atau buku…" value="<?= htmlspecialchars($search) ?>">
          </div>
          <select name="status" class="form-control" style="width:auto">
            <option value="">Semua Status</option>
            <option value="Peminjaman" <?= $filter_status==='Peminjaman'?'selected':'' ?>>Sedang Dipinjam</option>
            <option value="Pengembalian" <?= $filter_status==='Pengembalian'?'selected':'' ?>>Sudah Kembali</option>
          </select>
          <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
          <?php if ($search||$filter_status): ?>
            <a href="transaksi.php" class="btn btn-ghost btn-sm">Reset</a>
          <?php endif; ?>
        </form>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Anggota</th>
                <th>Buku</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($transaksi && $transaksi->num_rows > 0): $no=1; ?>
                <?php while($r=$transaksi->fetch_assoc()): ?>
                  <?php
                  $late = $r['status_transaksi']==='Peminjaman' && strtotime($r['tgl_kembali_rencana']) < time();
                  $sc = $r['status_transaksi']==='Pengembalian' ? 'status-kembali' : ($late?'status-terlambat':'status-dipinjam');
                  $sl = $r['status_transaksi']==='Pengembalian' ? '✓ Kembali' : ($late?'⚠ Terlambat':'⇄ Dipinjam');
                  ?>
                  <tr>
                    <td class="text-muted text-sm"><?= $no++ ?></td>
                    <td>
                      <div class="fw-600"><?= htmlspecialchars($r['nama_anggota']) ?></div>
                      <div class="text-xs text-muted"><?= htmlspecialchars($r['nis']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($r['judul_buku']) ?></td>
                    <td><?= date('d/m/Y',strtotime($r['tgl_pinjam'])) ?></td>
                    <td><?= date('d/m/Y',strtotime($r['tgl_kembali_rencana'])) ?></td>
                    <td><?= $r['tgl_kembali_aktual'] ? date('d/m/Y',strtotime($r['tgl_kembali_aktual'])) : '—' ?></td>
                    <td><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
                    <td>
                      <?php if ($r['status_transaksi']==='Peminjaman'): ?>
                        <a href="?return=<?= $r['id_transaksi'] ?>" class="btn btn-sage btn-sm">Kembalikan</a>
                      <?php else: ?>
                        <span class="text-muted text-xs">Selesai</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="8"><div class="empty-state"><div class="empty-state-ico">📋</div><div class="empty-state-title">Belum ada transaksi</div></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="modal-overlay" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Catat Peminjaman Baru</div>
      <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label class="form-label">Anggota *</label>
            <select name="id_anggota" class="form-control" required>
              <option value="">-- Pilih Anggota --</option>
              <?php while($a=$anggota_list->fetch_assoc()): ?>
              <option value="<?= $a['id_anggota'] ?>"><?= htmlspecialchars($a['nama_anggota']) ?> (<?= $a['nis'] ?>)</option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Buku *</label>
            <select name="id_buku" class="form-control" required>
              <option value="">-- Pilih Buku --</option>
              <?php while($b=$buku_list->fetch_assoc()): ?>
              <option value="<?= $b['id_buku'] ?>"><?= htmlspecialchars($b['judul_buku']) ?> (stok: <?= $b['stok'] ?>)</option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tanggal Pinjam *</label>
            <input name="tgl_pinjam" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Rencana Kembali *</label>
            <input name="tgl_kembali_rencana" type="date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').style.display='none'">Batal</button>
        <button name="add" class="btn btn-primary">Catat Pinjam</button>
      </div>
    </form>
  </div>
</div>

<?php if ($returnItem): ?>
<!-- RETURN MODAL -->
<div id="returnModal" class="modal-overlay" onclick="if(event.target===this)location.href='transaksi.php'">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Proses Pengembalian</div>
      <a href="transaksi.php" class="modal-close">✕</a>
    </div>
    <form method="POST">
      <input type="hidden" name="id_transaksi" value="<?= $returnItem['id_transaksi'] ?>">
      <div class="modal-body">
        <div style="background:var(--bg);border-radius:10px;padding:14px;margin-bottom:18px">
          <div class="text-sm fw-600"><?= htmlspecialchars($returnItem['nama_anggota']) ?></div>
          <div class="text-sm text-muted mt-8"><?= htmlspecialchars($returnItem['judul_buku']) ?></div>
          <div class="text-xs text-muted mt-8">Jatuh tempo: <?= date('d M Y',strtotime($returnItem['tgl_kembali_rencana'])) ?></div>
        </div>
        <div class="form-group">
          <label class="form-label">Tanggal Kembali *</label>
          <input name="tgl_kembali" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <p class="text-xs text-muted mt-8">Denda Rp 1.000/hari jika terlambat dari jatuh tempo.</p>
      </div>
      <div class="modal-footer">
        <a href="transaksi.php" class="btn btn-ghost">Batal</a>
        <button name="return" class="btn btn-sage">Proses Pengembalian</button>
      </div>
    </form>
  </div>
</div>
<script>document.getElementById('returnModal').style.display='flex';</script>
<?php endif; ?>
</body>
</html>
