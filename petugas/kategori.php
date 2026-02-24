<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();

$conn = getConnection();
$msg = '';
$msgType = '';

if (isset($_POST['add'])) {
    $nama = trim($_POST['nama_kategori']);
    $desk = trim($_POST['deskripsi']);
    $s = $conn->prepare("INSERT INTO kategori(nama_kategori,deskripsi) VALUES(?,?)");
    $s->bind_param("ss",$nama,$desk);
    $msg = $s->execute() ? 'Kategori ditambahkan!' : 'Gagal menambahkan kategori!';
    $msgType = ($msg === 'Kategori ditambahkan!') ? 'success' : 'danger';
    $s->close();
}

if (isset($_POST['edit'])) {
    $id = (int)$_POST['id_kategori'];
    $nama = trim($_POST['nama_kategori']);
    $desk = trim($_POST['deskripsi']);
    $s = $conn->prepare("UPDATE kategori SET nama_kategori=?,deskripsi=? WHERE id_kategori=?");
    $s->bind_param("ssi",$nama,$desk,$id);
    $msg = $s->execute() ? 'Kategori diperbarui!' : 'Gagal update!';
    $msgType = 'success'; $s->close();
}

if (isset($_POST['delete'])) {
    $id = (int)$_POST['id_kategori'];
    $chk = $conn->query("SELECT COUNT(*) as c FROM buku WHERE id_kategori=$id")->fetch_assoc()['c'];
    if ($chk > 0) {
        $msg = 'Kategori masih dipakai buku!'; $msgType = 'warning';
    } else {
        $s = $conn->prepare("DELETE FROM kategori WHERE id_kategori=?");
        $s->bind_param("i",$id);
        $msg = $s->execute() ? 'Kategori dihapus!' : 'Gagal hapus!';
        $msgType = 'success'; $s->close();
    }
}

$categories = $conn->query("
    SELECT k.*, (SELECT COUNT(*) FROM buku WHERE id_kategori=k.id_kategori) as jml
    FROM kategori k ORDER BY nama_kategori
");

$editCat = null;
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $s = $conn->prepare("SELECT * FROM kategori WHERE id_kategori=?");
    $s->bind_param("i",$id); $s->execute();
    $editCat = $s->get_result()->fetch_assoc();
}

$page_title = 'Manajemen Kategori';
$page_sub   = 'Kelola kategori buku perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kategori — Petugas Perpustakaan</title>
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
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <div class="page-header">
        <div>
          <div class="page-header-title">Kategori Buku</div>
          <div class="page-header-sub">Tambah, edit, atau hapus kategori koleksi</div>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Kategori
        </button>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th>Jumlah Buku</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($categories && $categories->num_rows > 0): $no = 1; ?>
                <?php while($r = $categories->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted text-sm"><?= $no++ ?></td>
                  <td><div class="fw-600"><?= htmlspecialchars($r['nama_kategori']) ?></div></td>
                  <td class="text-muted"><?= htmlspecialchars($r['deskripsi'] ?? '—') ?></td>
                  <td><span class="badge badge-muted"><?= $r['jml'] ?> buku</span></td>
                  <td>
                    <div style="display:flex;gap:6px">
                      <a href="?edit=<?= $r['id_kategori'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                      <form method="POST" onsubmit="return confirm('Hapus kategori ini?')" style="display:inline">
                        <input type="hidden" name="id_kategori" value="<?= $r['id_kategori'] ?>">
                        <button name="delete" class="btn btn-danger btn-sm">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5">
                  <div class="empty-state">
                    <div class="empty-state-ico">🗂️</div>
                    <div class="empty-state-title">Belum ada kategori</div>
                    <div class="empty-state-sub">Tambahkan kategori pertama untuk mengorganisir buku.</div>
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
      <div class="modal-title">Tambah Kategori Baru</div>
      <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label class="form-label">Nama Kategori *</label>
            <input name="nama_kategori" class="form-control" placeholder="Contoh: Fiksi, Sains, Sejarah…" required>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Deskripsi</label>
            <input name="deskripsi" class="form-control" placeholder="Deskripsi singkat kategori…">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').style.display='none'">Batal</button>
        <button name="add" class="btn btn-primary">Simpan Kategori</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editCat): ?>
<div id="editModal" class="modal-overlay" onclick="if(event.target===this)location.href='kategori.php'">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Kategori</div>
      <a href="kategori.php" class="modal-close">✕</a>
    </div>
    <form method="POST">
      <input type="hidden" name="id_kategori" value="<?= $editCat['id_kategori'] ?>">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label class="form-label">Nama Kategori *</label>
            <input name="nama_kategori" class="form-control" value="<?= htmlspecialchars($editCat['nama_kategori']) ?>" required>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Deskripsi</label>
            <input name="deskripsi" class="form-control" value="<?= htmlspecialchars($editCat['deskripsi'] ?? '') ?>">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="kategori.php" class="btn btn-ghost">Batal</a>
        <button name="edit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
<script>document.getElementById('editModal').style.display='flex';</script>
<?php endif; ?>
</body>
</html>
