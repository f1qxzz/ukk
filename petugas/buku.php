<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();
$conn = getConnection();
$msg = ''; $msgType = '';

// CRUD logic (unchanged from original)
if (isset($_POST['add'])) {
    $judul=$_POST['judul_buku']; $id_kat=(int)$_POST['id_kategori'];
    $peng=$_POST['pengarang']; $nerbit=$_POST['penerbit'];
    $tahun=(int)$_POST['tahun_terbit']; $isbn=trim($_POST['isbn']);
    $desk=trim($_POST['deskripsi']); $stok=(int)$_POST['stok'];
    $status=$stok>0?'tersedia':'tidak tersedia';
    $s=$conn->prepare("INSERT INTO buku(judul_buku,id_kategori,pengarang,penerbit,tahun_terbit,isbn,deskripsi,stok,status) VALUES(?,?,?,?,?,?,?,?,?)");
    $s->bind_param("sississis",$judul,$id_kat,$peng,$nerbit,$tahun,$isbn,$desk,$stok,$status);
    $msg=$s->execute()?'Buku berhasil ditambahkan!':'Gagal: '.$conn->error; $msgType='success'; $s->close();
}
if (isset($_POST['edit'])) {
    $id=(int)$_POST['id_buku']; $judul=$_POST['judul_buku']; $id_kat=(int)$_POST['id_kategori'];
    $peng=$_POST['pengarang']; $nerbit=$_POST['penerbit'];
    $tahun=(int)$_POST['tahun_terbit']; $isbn=trim($_POST['isbn']);
    $desk=trim($_POST['deskripsi']); $stok=(int)$_POST['stok']; $status=$_POST['status'];
    $s=$conn->prepare("UPDATE buku SET judul_buku=?,id_kategori=?,pengarang=?,penerbit=?,tahun_terbit=?,isbn=?,deskripsi=?,stok=?,status=? WHERE id_buku=?");
    $s->bind_param("sississisi",$judul,$id_kat,$peng,$nerbit,$tahun,$isbn,$desk,$stok,$status,$id);
    $msg=$s->execute()?'Buku berhasil diperbarui!':'Gagal!'; $msgType='success'; $s->close();
}
if (isset($_POST['delete'])) {
    $id=(int)$_POST['id_buku'];
    $chk=$conn->query("SELECT COUNT(*) c FROM transaksi WHERE id_buku=$id AND status_transaksi='Peminjaman'")->fetch_assoc()['c'];
    if($chk>0){ $msg='Buku sedang dipinjam, tidak bisa dihapus!'; $msgType='warning'; }
    else {
        $s=$conn->prepare("DELETE FROM buku WHERE id_buku=?");
        $s->bind_param("i",$id);
        $msg=$s->execute()?'Buku berhasil dihapus!':'Gagal!'; $msgType='success'; $s->close();
    }
}

$cats = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
$search=isset($_GET['search'])?$_GET['search']:'';
$filter_kat=isset($_GET['kat'])?(int)$_GET['kat']:0;
$q="SELECT b.*,k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.id_kategori=k.id_kategori WHERE 1=1";
if($search) $q.=" AND (b.judul_buku LIKE '%$search%' OR b.pengarang LIKE '%$search%')";
if($filter_kat) $q.=" AND b.id_kategori=$filter_kat";
$q.=" ORDER BY b.id_buku DESC";
$books=$conn->query($q);

$editBook=null;
if(isset($_GET['edit'])){
    $id=(int)$_GET['edit'];
    $s=$conn->prepare("SELECT * FROM buku WHERE id_buku=?");
    $s->bind_param("i",$id); $s->execute();
    $editBook=$s->get_result()->fetch_assoc();
}

$page_title = 'Manajemen Buku';
$page_sub   = 'Kelola koleksi perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Buku — Admin Perpustakaan</title>
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
          <div class="page-header-title">Koleksi Buku</div>
          <div class="page-header-sub">Tambah, edit, atau hapus data buku perpustakaan</div>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Buku
        </button>
      </div>

      <!-- Table Card -->
      <div class="card">
        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
          <div class="search-wrap">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Cari judul atau pengarang…" value="<?= htmlspecialchars($search) ?>">
          </div>
          <select name="kat" class="form-control" style="width:auto">
            <option value="">Semua Kategori</option>
            <?php
            $cats->data_seek(0);
            while($c=$cats->fetch_assoc()):
            ?>
              <option value="<?= $c['id_kategori'] ?>" <?= $filter_kat==$c['id_kategori']?'selected':'' ?>><?= htmlspecialchars($c['nama_kategori']) ?></option>
            <?php endwhile; ?>
          </select>
          <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
          <?php if ($search||$filter_kat): ?>
            <a href="buku.php" class="btn btn-ghost btn-sm">Reset</a>
          <?php endif; ?>
        </form>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Judul Buku</th>
                <th>Pengarang</th>
                <th>Kategori</th>
                <th>Tahun</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($books && $books->num_rows > 0): $no=1; ?>
                <?php while($b=$books->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted text-sm"><?= $no++ ?></td>
                  <td>
                    <div class="fw-600"><?= htmlspecialchars($b['judul_buku']) ?></div>
                    <div class="text-xs text-muted"><?= htmlspecialchars($b['isbn'] ?: '—') ?></div>
                  </td>
                  <td><?= htmlspecialchars($b['pengarang']) ?></td>
                  <td><span class="badge badge-muted"><?= htmlspecialchars($b['nama_kategori'] ?: '—') ?></span></td>
                  <td><?= $b['tahun_terbit'] ?></td>
                  <td><?= $b['stok'] ?></td>
                  <td>
                    <span class="badge <?= $b['status']==='tersedia'?'status-tersedia':'status-terlambat' ?>">
                      <?= $b['status']==='tersedia' ? '● Tersedia' : '○ Habis' ?>
                    </span>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px">
                      <a href="?edit=<?= $b['id_buku'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                      <form method="POST" onsubmit="return confirm('Hapus buku ini?')" style="display:inline">
                        <input type="hidden" name="id_buku" value="<?= $b['id_buku'] ?>">
                        <button name="delete" class="btn btn-danger btn-sm">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="8">
                  <div class="empty-state">
                    <div class="empty-state-ico">📚</div>
                    <div class="empty-state-title">Belum ada buku</div>
                    <div class="empty-state-sub">Tambahkan buku pertama ke koleksi perpustakaan.</div>
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

<!-- ADD MODAL -->
<div id="addModal" class="modal-overlay" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Tambah Buku Baru</div>
      <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label class="form-label">Judul Buku *</label>
            <input name="judul_buku" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Pengarang *</label>
            <input name="pengarang" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Penerbit</label>
            <input name="penerbit" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Kategori</label>
            <select name="id_kategori" class="form-control">
              <?php $cats->data_seek(0); while($c=$cats->fetch_assoc()): ?>
              <option value="<?= $c['id_kategori'] ?>"><?= htmlspecialchars($c['nama_kategori']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tahun Terbit</label>
            <input name="tahun_terbit" type="number" class="form-control" value="<?= date('Y') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">ISBN</label>
            <input name="isbn" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Stok *</label>
            <input name="stok" type="number" class="form-control" min="0" value="1" required>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').style.display='none'">Batal</button>
        <button name="add" class="btn btn-primary">Simpan Buku</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editBook): ?>
<!-- EDIT MODAL -->
<div id="editModal" class="modal-overlay" onclick="if(event.target===this)location.href='buku.php'">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Buku</div>
      <a href="buku.php" class="modal-close">✕</a>
    </div>
    <form method="POST">
      <input type="hidden" name="id_buku" value="<?= $editBook['id_buku'] ?>">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label class="form-label">Judul Buku *</label>
            <input name="judul_buku" class="form-control" value="<?= htmlspecialchars($editBook['judul_buku']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Pengarang *</label>
            <input name="pengarang" class="form-control" value="<?= htmlspecialchars($editBook['pengarang']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Penerbit</label>
            <input name="penerbit" class="form-control" value="<?= htmlspecialchars($editBook['penerbit']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Kategori</label>
            <select name="id_kategori" class="form-control">
              <?php $cats->data_seek(0); while($c=$cats->fetch_assoc()): ?>
              <option value="<?= $c['id_kategori'] ?>" <?= $c['id_kategori']==$editBook['id_kategori']?'selected':'' ?>><?= htmlspecialchars($c['nama_kategori']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tahun Terbit</label>
            <input name="tahun_terbit" type="number" class="form-control" value="<?= $editBook['tahun_terbit'] ?>">
          </div>
          <div class="form-group">
            <label class="form-label">ISBN</label>
            <input name="isbn" class="form-control" value="<?= htmlspecialchars($editBook['isbn']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Stok *</label>
            <input name="stok" type="number" class="form-control" min="0" value="<?= $editBook['stok'] ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="tersedia" <?= $editBook['status']==='tersedia'?'selected':'' ?>>Tersedia</option>
              <option value="tidak tersedia" <?= $editBook['status']==='tidak tersedia'?'selected':'' ?>>Tidak Tersedia</option>
            </select>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control"><?= htmlspecialchars($editBook['deskripsi']) ?></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="buku.php" class="btn btn-ghost">Batal</a>
        <button name="edit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
<script>document.getElementById('editModal').style.display='flex';</script>
<?php endif; ?>
</body>
</html>
