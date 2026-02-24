<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

$conn = getConnection();
$msg = '';
$msgType = '';

if (isset($_POST['add'])) {
  $u = trim($_POST['username']); $n = trim($_POST['nama_pengguna']);
  $e = trim($_POST['email']); $lv = $_POST['level'];
  $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $stmt = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE username=?");
  $stmt->bind_param("s", $u); $stmt->execute(); $stmt->store_result();
  if ($stmt->num_rows > 0) { $msg = 'Username sudah digunakan!'; $msgType = 'warning'; }
  else {
    $stmt = $conn->prepare("INSERT INTO pengguna(username,password,nama_pengguna,email,level) VALUES(?,?,?,?,?)");
    $stmt->bind_param("sssss", $u, $p, $n, $e, $lv);
    $msg = $stmt->execute() ? 'Pengguna berhasil ditambahkan!' : 'Gagal: '.$conn->error;
    $msgType = $stmt->execute() ? 'success' : 'danger';
  }
}
if (isset($_POST['delete'])) {
  $id = (int)$_POST['id_pengguna'];
  if ($id == getPenggunaId()) { $msg = 'Tidak bisa hapus akun sendiri!'; $msgType = 'warning'; }
  else {
    $stmt = $conn->prepare("DELETE FROM pengguna WHERE id_pengguna=?");
    $stmt->bind_param("i", $id);
    $msg = $stmt->execute() ? 'Pengguna dihapus!' : 'Gagal!';
    $msgType = 'success';
  }
}
if (isset($_POST['edit'])) {
  $id = (int)$_POST['id_pengguna']; $n = trim($_POST['nama_pengguna']);
  $e = trim($_POST['email']); $lv = $_POST['level'];
  $stmt = $conn->prepare("UPDATE pengguna SET nama_pengguna=?,email=?,level=? WHERE id_pengguna=?");
  $stmt->bind_param("sssi", $n, $e, $lv, $id); $stmt->execute();
  $msg = 'Data diperbarui!'; $msgType = 'success';
}
if (isset($_POST['reset_pw'])) {
  $id = (int)$_POST['id_pengguna'];
  $pw = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE pengguna SET password=? WHERE id_pengguna=?");
  $stmt->bind_param("si", $pw, $id); $stmt->execute();
  $msg = 'Password berhasil direset!'; $msgType = 'success';
}

$users = $conn->query("SELECT * FROM pengguna ORDER BY level,nama_pengguna");
$editUser = null;
if(isset($_GET['edit'])){
  $id=(int)$_GET['edit'];
  $s=$conn->prepare("SELECT * FROM pengguna WHERE id_pengguna=?");
  $s->bind_param("i",$id); $s->execute();
  $editUser=$s->get_result()->fetch_assoc();
}

$page_title = 'Manajemen Pengguna';
$page_sub   = 'Kelola akun admin dan petugas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pengguna — Admin Perpustakaan</title>
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
          <div class="page-header-title">Data Pengguna</div>
          <div class="page-header-sub">Kelola akun admin dan petugas perpustakaan</div>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Pengguna
        </button>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th><th>Nama</th><th>Username</th><th>Email</th><th>Level</th><th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if($users && $users->num_rows>0): $no=1; while($r=$users->fetch_assoc()): ?>
              <tr>
                <td class="text-muted text-sm"><?= $no++ ?></td>
                <td><div class="fw-600"><?= htmlspecialchars($r['nama_pengguna']) ?></div></td>
                <td><?= htmlspecialchars($r['username']) ?></td>
                <td><?= htmlspecialchars($r['email']??'—') ?></td>
                <td><span class="badge <?= $r['level']==='admin'?'badge-rust':'badge-muted' ?>"><?= ucfirst($r['level']) ?></span></td>
                <td>
                  <div style="display:flex;gap:6px">
                    <a href="?edit=<?= $r['id_pengguna'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                    <button class="btn btn-ghost btn-sm" onclick="showReset(<?= $r['id_pengguna'] ?>,'<?= htmlspecialchars($r['nama_pengguna']) ?>')">Reset PW</button>
                    <?php if($r['id_pengguna']!=getPenggunaId()): ?>
                    <form method="POST" onsubmit="return confirm('Hapus pengguna ini?')" style="display:inline">
                      <input type="hidden" name="id_pengguna" value="<?= $r['id_pengguna'] ?>">
                      <button name="delete" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="6">
                <div class="empty-state">
                  <div class="empty-state-ico">👤</div>
                  <div class="empty-state-title">Belum ada pengguna</div>
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
      <div class="modal-title">Tambah Pengguna Baru</div>
      <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Nama Lengkap *</label><input name="nama_pengguna" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Level</label>
            <select name="level" class="form-control">
              <option value="petugas">Petugas</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Username *</label><input name="username" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Password *</label><input name="password" type="password" class="form-control" required></div>
          <div class="form-group form-full"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').style.display='none'">Batal</button>
        <button name="add" class="btn btn-primary">Simpan Pengguna</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editUser): ?>
<div id="editModal" class="modal-overlay" onclick="if(event.target===this)location.href='pengguna.php'">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Pengguna</div>
      <a href="pengguna.php" class="modal-close">✕</a>
    </div>
    <form method="POST">
      <input type="hidden" name="id_pengguna" value="<?= $editUser['id_pengguna'] ?>">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Nama Lengkap *</label><input name="nama_pengguna" class="form-control" value="<?= htmlspecialchars($editUser['nama_pengguna']) ?>" required></div>
          <div class="form-group"><label class="form-label">Level</label>
            <select name="level" class="form-control">
              <option value="petugas" <?= $editUser['level']==='petugas'?'selected':'' ?>>Petugas</option>
              <option value="admin" <?= $editUser['level']==='admin'?'selected':'' ?>>Admin</option>
            </select>
          </div>
          <div class="form-group form-full"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?= htmlspecialchars($editUser['email']??'') ?>"></div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="pengguna.php" class="btn btn-ghost">Batal</a>
        <button name="edit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
<script>document.getElementById('editModal').style.display='flex';</script>
<?php endif; ?>

<!-- RESET PW MODAL -->
<div id="resetModal" class="modal-overlay" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <div class="modal-title" id="resetTitle">Reset Password</div>
      <button class="modal-close" onclick="document.getElementById('resetModal').style.display='none'">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="id_pengguna" id="resetId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Password Baru *</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('resetModal').style.display='none'">Batal</button>
        <button name="reset_pw" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function showReset(id,nama){
  document.getElementById('resetId').value=id;
  document.getElementById('resetTitle').textContent='Reset PW: '+nama;
  document.getElementById('resetModal').style.display='flex';
}
</script>
</body>
</html>
