<?php
/**
 * Admin – Kelola Anggota
 * Fitur: Tambah, Edit, Hapus, Reset Password
 */
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();
$conn = getConnection();
$msg = ''; $msgType = '';

// TAMBAH
if (isset($_POST['add'])) {
    $nis=$_POST['nis']; $nama=$_POST['nama_anggota']; $uname=$_POST['username'];
    $pw=$_POST['password']; $email=$_POST['email']; $kelas=$_POST['kelas'];
    $chk=$conn->query("SELECT id_anggota FROM anggota WHERE username='$uname' OR nis='$nis'");
    if($chk->num_rows>0){ $msg='NIS atau Username sudah digunakan!'; $msgType='warning'; }
    else {
        $s=$conn->prepare("INSERT INTO anggota(nis,nama_anggota,username,password,email,kelas) VALUES(?,?,?,?,?,?)");
        $s->bind_param("ssssss",$nis,$nama,$uname,$pw,$email,$kelas);
        $msg=$s->execute()?'Anggota ditambahkan!':'Gagal: '.$conn->error;
        $msgType=$s->execute()?'success':'danger'; $s->close();
        if($conn->affected_rows>=0){ $msg='Anggota berhasil ditambahkan!'; $msgType='success'; }
    }
}

// EDIT
if (isset($_POST['edit'])) {
    $id=(int)$_POST['id_anggota'];
    $nis=$_POST['nis']; $nama=$_POST['nama_anggota']; $email=$_POST['email'];
    $kelas=$_POST['kelas']; $status=$_POST['status'];
    if (!empty($_POST['password'])) {
        $pw=$_POST['password'];
        $s=$conn->prepare("UPDATE anggota SET nis=?,nama_anggota=?,email=?,kelas=?,status=?,password=? WHERE id_anggota=?");
        $s->bind_param("ssssssi",$nis,$nama,$email,$kelas,$status,$pw,$id);
    } else {
        $s=$conn->prepare("UPDATE anggota SET nis=?,nama_anggota=?,email=?,kelas=?,status=? WHERE id_anggota=?");
        $s->bind_param("sssssi",$nis,$nama,$email,$kelas,$status,$id);
    }
    $msg=$s->execute()?'Data diperbarui!':'Gagal!'; $msgType='success'; $s->close();
}

// HAPUS
if (isset($_POST['delete'])) {
    $id=(int)$_POST['id_anggota'];
    $chk=$conn->query("SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id AND status_transaksi='Peminjaman'")->fetch_assoc()['c'];
    if($chk>0){ $msg='Anggota masih memiliki peminjaman aktif!'; $msgType='warning'; }
    else {
        $s=$conn->prepare("DELETE FROM anggota WHERE id_anggota=?");
        $s->bind_param("i",$id);
        $msg=$s->execute()?'Anggota dihapus!':'Gagal!'; $msgType='success'; $s->close();
    }
}

// RESET PASSWORD
if (isset($_POST['reset_pw'])) {
    $id=(int)$_POST['id_anggota']; $pw=trim($_POST['new_password']);
    $s=$conn->prepare("UPDATE anggota SET password=? WHERE id_anggota=?");
    $s->bind_param("si",$pw,$id);
    $msg=$s->execute()?'Password direset!':'Gagal!'; $msgType='success'; $s->close();
}

$search=isset($_GET['search'])?$_GET['search']:'';
$q="SELECT * FROM anggota";
if($search) $q.=" WHERE nama_anggota LIKE '%$search%' OR nis LIKE '%$search%' OR kelas LIKE '%$search%'";
$q.=" ORDER BY id_anggota DESC";
$members=$conn->query($q);

$editMember=null;
if(isset($_GET['edit'])){
    $id=(int)$_GET['edit'];
    $s=$conn->prepare("SELECT * FROM anggota WHERE id_anggota=?");
    $s->bind_param("i",$id); $s->execute();
    $editMember=$s->get_result()->fetch_assoc();
}
$page_title = 'Manajemen Anggota';
$page_sub   = 'Kelola data anggota perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Anggota — Petugas Perpustakaan</title>
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
  <div class="card-header"><h2><?= $editMember?'Edit Anggota':'Tambah Anggota Baru' ?></h2></div>
  <div class="card-body">
    <form method="POST">
      <?php if($editMember): ?><input type="hidden" name="id_anggota" value="<?= $editMember['id_anggota'] ?>"><?php endif; ?>
      <div class="form-row">
        <div class="form-group"><label>NIS *</label>
          <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($editMember['nis']??'') ?>" required></div>
        <div class="form-group"><label>Kelas *</label>
          <input type="text" name="kelas" class="form-control" value="<?= htmlspecialchars($editMember['kelas']??'') ?>" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Nama Lengkap *</label>
          <input type="text" name="nama_anggota" class="form-control" value="<?= htmlspecialchars($editMember['nama_anggota']??'') ?>" required></div>
        <div class="form-group"><label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editMember['email']??'') ?>"></div>
      </div>
      <?php if(!$editMember): ?>
      <div class="form-row">
        <div class="form-group"><label>Username *</label>
          <input type="text" name="username" class="form-control" required></div>
        <div class="form-group"><label>Password *</label>
          <input type="password" name="password" class="form-control" required></div>
      </div>
      <?php else: ?>
      <div class="form-row">
        <div class="form-group"><label>Password Baru <small>(kosongkan jika tidak diubah)</small></label>
          <input type="password" name="password" class="form-control"></div>
        <div class="form-group"><label>Status</label>
          <select name="status" class="form-control">
            <option value="aktif"    <?= ($editMember['status']??'')==='aktif'   ?'selected':'' ?>>Aktif</option>
            <option value="nonaktif" <?= ($editMember['status']??'')==='nonaktif'?'selected':'' ?>>Nonaktif</option>
          </select></div>
      </div>
      <?php endif; ?>
      <div style="display:flex;gap:10px;">
        <button type="submit" name="<?= $editMember?'edit':'add' ?>" class="btn btn-primary"><?= $editMember?'Update':'Tambah' ?></button>
        <?php if($editMember): ?><a href="anggota.php" class="btn btn-secondary">Batal</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2>Daftar Anggota</h2></div>
  <div class="card-body">
    <div class="search-box">
      <form method="GET" style="display:flex;gap:10px;width:100%;">
        <input type="text" name="search" class="form-control" placeholder="Cari nama, NIS, kelas..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Cari</button>
        <?php if($search): ?><a href="anggota.php" class="btn btn-secondary">Reset</a><?php endif; ?>
      </form>
    </div>
    <div class="table-responsive">
    <table><thead><tr><th>ID</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php if($members->num_rows>0): while($r=$members->fetch_assoc()): ?>
    <tr>
      <td><?= $r['id_anggota'] ?></td>
      <td><?= htmlspecialchars($r['nis']) ?></td>
      <td><?= htmlspecialchars($r['nama_anggota']) ?></td>
      <td><?= htmlspecialchars($r['kelas']) ?></td>
      <td><?= htmlspecialchars($r['email']??'-') ?></td>
      <td><span class="badge <?= $r['status']==='aktif'?'badge-success':'badge-danger' ?>"><?= ucfirst($r['status']) ?></span></td>
      <td style="white-space:nowrap">
        <a href="?edit=<?= $r['id_anggota'] ?>" class="btn btn-warning btn-sm">Edit</a>
        <button class="btn btn-info btn-sm" onclick="showReset(<?= $r['id_anggota'] ?>,'<?= htmlspecialchars($r['nama_anggota']) ?>')">Reset PW</button>
        <form method="POST" style="display:inline" onsubmit="return confirmDelete()">
          <input type="hidden" name="id_anggota" value="<?= $r['id_anggota'] ?>">
          <button type="submit" name="delete" class="btn btn-danger btn-sm">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
    <?php endif; ?>
    </tbody></table></div>
  </div>
</div>

<!-- RESET PW MODAL -->
<div id="resetModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:999;justify-content:center;align-items:center;">
  <div style="background:#fff;padding:30px;border-radius:10px;width:380px;">
    <h3 id="resetTitle" style="margin-bottom:16px;"></h3>
    <form method="POST">
      <input type="hidden" name="id_anggota" id="resetId">
      <div class="form-group"><label>Password Baru</label><input type="password" name="new_password" class="form-control" required></div>
      <div style="display:flex;gap:10px;">
        <button type="submit" name="reset_pw" class="btn btn-primary">Simpan</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('resetModal').style.display='none'">Batal</button>
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
function confirmDelete(){ return confirm('Hapus anggota ini?'); }
</script>
<script src="../assets/js/script.js"></script>
  </main>
  </div>
</div>
</body></html>
