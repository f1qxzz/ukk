<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn=getConnection();
$id=getAnggotaId();
$msg=''; $msgType='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nama=trim($_POST['nama_anggota']); $email=trim($_POST['email']);
    $alamat=trim($_POST['alamat']); $telp=trim($_POST['no_telepon']);
    if (!empty($_POST['password_baru'])) {
        $pw_lama=trim($_POST['password_lama']); $pw_baru=trim($_POST['password_baru']);
        $chk=$conn->query("SELECT password FROM anggota WHERE id_anggota=$id")->fetch_assoc();
        if($chk['password']!==$pw_lama){ $msg='Password lama salah!'; $msgType='danger'; }
        else {
            $s=$conn->prepare("UPDATE anggota SET nama_anggota=?,email=?,alamat=?,no_telepon=?,password=? WHERE id_anggota=?");
            $s->bind_param("sssssi",$nama,$email,$alamat,$telp,$pw_baru,$id);
            $msg=$s->execute()?'Profil & password diperbarui!':'Gagal!'; $msgType='success'; $s->close();
            $_SESSION['anggota_nama']=$nama;
        }
    } else {
        $s=$conn->prepare("UPDATE anggota SET nama_anggota=?,email=?,alamat=?,no_telepon=? WHERE id_anggota=?");
        $s->bind_param("ssssi",$nama,$email,$alamat,$telp,$id);
        $msg=$s->execute()?'Profil diperbarui!':'Gagal!'; $msgType='success'; $s->close();
        $_SESSION['anggota_nama']=$nama;
    }
}

$anggota=$conn->query("SELECT * FROM anggota WHERE id_anggota=$id")->fetch_assoc();
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><title>Profil – Anggota</title>
<link rel="stylesheet" href="../assets/css/style.css"></head><body>
<?php include 'includes/header.php'; include 'includes/nav.php'; ?>
<div class="container">
<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card" style="max-width:600px;margin:0 auto;">
  <div class="card-header"><h2>✏️ Edit Profil Pribadi</h2></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-row">
        <div class="form-group"><label>NIS</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($anggota['nis']) ?>" disabled></div>
        <div class="form-group"><label>Kelas</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($anggota['kelas']) ?>" disabled></div>
      </div>
      <div class="form-group"><label>Nama Lengkap *</label>
        <input type="text" name="nama_anggota" class="form-control" value="<?= htmlspecialchars($anggota['nama_anggota']) ?>" required></div>
      <div class="form-group"><label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($anggota['email']??'') ?>"></div>
      <div class="form-group"><label>Alamat</label>
        <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($anggota['alamat']??'') ?></textarea></div>
      <div class="form-group"><label>No. Telepon</label>
        <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($anggota['no_telepon']??'') ?>"></div>
      <hr style="margin:20px 0;">
      <p class="text-muted"><em>Isi hanya jika ingin mengubah password</em></p>
      <div class="form-group"><label>Password Lama</label>
        <input type="password" name="password_lama" class="form-control"></div>
      <div class="form-group"><label>Password Baru</label>
        <input type="password" name="password_baru" class="form-control"></div>
      <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
    </form>
  </div>
</div>
</div>
<script src="../assets/js/script.js"></script>
</body></html>
