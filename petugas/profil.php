<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();
$conn = getConnection();

$msg = '';
$msgType = '';

$id = getPenggunaId();

$stmt = $conn->prepare("SELECT * FROM pengguna WHERE id_pengguna=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ================= UPDATE PROFIL ================= */
if (isset($_POST['update'])) {

    $nama  = trim($_POST['nama_pengguna']);
    $email = trim($_POST['email']);

    $s = $conn->prepare("UPDATE pengguna SET nama_pengguna=?, email=? WHERE id_pengguna=?");
    $s->bind_param("ssi", $nama, $email, $id);

    $ok = $s->execute();
    $s->close();

    $msg = $ok ? 'Profil berhasil diperbarui!' : 'Gagal memperbarui profil!';
    $msgType = $ok ? 'success' : 'danger';

    if ($ok) {
        $stmt = $conn->prepare("SELECT * FROM pengguna WHERE id_pengguna=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

/* ================= UBAH PASSWORD ================= */
if (isset($_POST['change_pass'])) {

    $old     = $_POST['old_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $msg = 'Konfirmasi password tidak cocok!';
        $msgType = 'danger';
    } 
    elseif (!password_verify($old, $user['password'])) {
        $msg = 'Password lama salah!';
        $msgType = 'danger';
    } 
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);

        $s = $conn->prepare("UPDATE pengguna SET password=? WHERE id_pengguna=?");
        $s->bind_param("si", $hash, $id);

        $ok = $s->execute();
        $s->close();

        $msg = $ok ? 'Password berhasil diubah!' : 'Gagal mengubah password!';
        $msgType = $ok ? 'success' : 'danger';
    }
}

$page_title = 'Profil Saya';
$page_sub   = 'Kelola informasi akun';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profil — Petugas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>
        <div class="main-area">
            <?php include 'includes/header.php'; ?>

            <main class="content">

                <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType ?>">
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endif; ?>

                <div class="profile-layout">

                    <!-- PROFILE CARD -->
                    <div class="profile-header">

                        <div class="profile-card">
                            <div class="profile-banner"></div>

                            <div class="profile-avatar">
                                <?= strtoupper(substr($user['nama_pengguna'],0,1)) ?>
                            </div>

                            <div class="profile-name">
                                <?= htmlspecialchars($user['nama_pengguna']) ?>
                            </div>

                            <div class="profile-role">Petugas</div>
                        </div>

                        <div class="profile-meta">
                            <div class="meta-box">
                                <label>Email</label>
                                <span><?= htmlspecialchars($user['email']) ?></span>
                            </div>

                            <div class="meta-box">
                                <label>Username</label>
                                <span><?= htmlspecialchars($user['username']) ?></span>
                            </div>

                            <div class="meta-box">
                                <label>Level</label>
                                <span class="badge">Petugas</span>
                            </div>
                        </div>

                    </div>

                    <!-- FORM SECTION -->
                    <div class="profile-forms">

                        <!-- UPDATE INFO -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Edit Informasi</div>
                            </div>

                            <form method="POST">
                                <div class="card-body">
                                    <div class="form-grid">
                                        <div class="form-group form-full">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input name="nama_pengguna" class="form-control"
                                                value="<?= htmlspecialchars($user['nama_pengguna']) ?>" required>
                                        </div>

                                        <div class="form-group form-full">
                                            <label class="form-label">Email</label>
                                            <input name="email" type="email" class="form-control"
                                                value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button name="update" class="btn btn-primary">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- CHANGE PASSWORD -->
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
                                            <input name="confirm_password" type="password" class="form-control"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button name="change_pass" class="btn btn-primary">
                                        Ubah Password
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>

                </div>

            </main>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>