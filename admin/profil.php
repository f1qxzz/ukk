<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();
$msg = ''; $msgType = '';

$id = getAnggotaId();
$user = $conn->query("SELECT * FROM pengguna WHERE id_pengguna=".getPenggunaId())->fetch_assoc();

if (isset($_POST['update'])) {
    $nama = $_POST['nama_pengguna'];
    $email = $_POST['email'];
    $s = $conn->prepare("UPDATE pengguna SET nama_pengguna=?, email=? WHERE id_pengguna=?");
    $s->bind_param("ssi", $nama, $email, $user['id_pengguna']);
    $msg = $s->execute() ? 'Profil berhasil diperbarui!' : 'Gagal memperbarui!';
    $msgType = $s->execute() ? 'success' : 'danger';
    $s->close();
    $user = $conn->query("SELECT * FROM pengguna WHERE id_pengguna=".$user['id_pengguna'])->fetch_assoc();
}

if (isset($_POST['change_pass'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    if (password_verify($old, $user['password'])) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $s = $conn->prepare("UPDATE pengguna SET password=? WHERE id_pengguna=?");
        $s->bind_param("si", $hash, $user['id_pengguna']);
        $msg = $s->execute() ? 'Password berhasil diubah!' : 'Gagal!';
        $msgType = 'success'; $s->close();
    } else { $msg = 'Password lama salah!'; $msgType = 'danger'; }
}

$page_title = 'Profil Saya';
$page_sub   = 'Kelola informasi akun';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profil — Admin Perpustakaan</title>
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

      <div style="display:grid;grid-template-columns:1fr 1.8fr;gap:20px;align-items:start">

        <!-- Profile Card -->
        <div class="profile-card">
          <div class="profile-banner"></div>
          <div class="profile-avatar-wrap">
            <div class="profile-avatar" style="background:linear-gradient(135deg,var(--rust),var(--rust2))">
              <?= strtoupper(substr($user['nama_pengguna'],0,1)) ?>
            </div>
            <div class="profile-info">
              <div class="profile-name"><?= htmlspecialchars($user['nama_pengguna']) ?></div>
              <div class="profile-role">Administrator</div>
            </div>
          </div>
          <div class="profile-body">
            <div class="profile-fields">
              <div class="profile-field">
                <label>Email</label>
                <span><?= htmlspecialchars($user['email'] ?? '—') ?></span>
              </div>
              <div class="profile-field">
                <label>Username</label>
                <span><?= htmlspecialchars($user['username']) ?></span>
              </div>
              <div class="profile-field">
                <label>Level</label>
                <span><span class="badge badge-rust">Admin</span></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Forms -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Update Info -->
          <div class="card">
            <div class="card-header">
              <div class="card-title">Edit Informasi</div>
            </div>
            <form method="POST">
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group form-full">
                    <label class="form-label">Nama Lengkap</label>
                    <input name="nama_pengguna" class="form-control" value="<?= htmlspecialchars($user['nama_pengguna']) ?>">
                  </div>
                  <div class="form-group form-full">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                  </div>
                </div>
              </div>
              <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
                <button name="update" class="btn btn-primary">Simpan Perubahan</button>
              </div>
            </form>
          </div>

          <!-- Change Password -->
          <div class="card">
            <div class="card-header">
              <div class="card-title">Ubah Password</div>
            </div>
            <form method="POST">
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group form-full">
                    <label class="form-label">Password Lama</label>
                    <input name="old_password" type="password" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input name="new_password" type="password" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <input name="confirm_password" type="password" class="form-control" required>
                  </div>
                </div>
              </div>
              <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
                <button name="change_pass" class="btn btn-primary">Ubah Password</button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
</body>
</html>
