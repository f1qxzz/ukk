<?php
/**
 * Admin – Kelola Anggota
 */
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();
$msg = ''; $msgType = '';

if (isset($_POST['add'])) {
    $nis=$_POST['nis']; $nama=$_POST['nama_anggota']; $uname=$_POST['username'];
    $pw=$_POST['password']; $email=$_POST['email']; $kelas=$_POST['kelas'];
    $chk=$conn->query("SELECT id_anggota FROM anggota WHERE username='$uname' OR nis='$nis'");
    if($chk->num_rows>0){ $msg='NIS atau Username sudah digunakan!'; $msgType='warning'; }
    else {
        $s=$conn->prepare("INSERT INTO anggota(nis,nama_anggota,username,password,email,kelas) VALUES(?,?,?,?,?,?)");
        $s->bind_param("ssssss",$nis,$nama,$uname,$pw,$email,$kelas);
        $msg=$s->execute()?'Anggota berhasil ditambahkan!':'Gagal: '.$conn->error;
        $msgType='success'; $s->close();
    }
}
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
    <title>Anggota — Admin Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@300;400;500;600&display=swap"
        rel="stylesheet">
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
                        <div class="page-header-title">Data Anggota</div>
                        <div class="page-header-sub">Tambah, edit, atau hapus data anggota</div>
                    </div>
                    <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
                        <svg viewBox="0 0 24 24">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Tambah Anggota
                    </button>
                </div>

                <div class="card">
                    <form method="GET" class="filter-bar">
                        <div class="search-wrap">
                            <svg viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                            <input type="text" name="search" placeholder="Cari nama, NIS, kelas…"
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <button type="submit" class="btn btn-ghost btn-sm">Cari</button>
                        <?php if ($search): ?><a href="anggota.php"
                            class="btn btn-ghost btn-sm">Reset</a><?php endif; ?>
                    </form>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($members && $members->num_rows>0): $no=1; while($r=$members->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-muted text-sm"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($r['nis']) ?></td>
                                    <td>
                                        <div class="fw-600"><?= htmlspecialchars($r['nama_anggota']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($r['kelas']) ?></td>
                                    <td><?= htmlspecialchars($r['email']??'—') ?></td>
                                    <td><span
                                            class="badge <?= $r['status']==='aktif'?'status-tersedia':'status-terlambat' ?>"><?= $r['status']==='aktif'?'● Aktif':'○ Nonaktif' ?></span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px">
                                            <a href="?edit=<?= $r['id_anggota'] ?>"
                                                class="btn btn-ghost btn-sm">Edit</a>
                                            <button class="btn btn-ghost btn-sm"
                                                onclick="showReset(<?= $r['id_anggota'] ?>,'<?= htmlspecialchars($r['nama_anggota']) ?>')">Reset
                                                PW</button>
                                            <form method="POST" onsubmit="return confirm('Hapus anggota ini?')"
                                                style="display:inline">
                                                <input type="hidden" name="id_anggota" value="<?= $r['id_anggota'] ?>">
                                                <button name="delete" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-state-ico">👥</div>
                                            <div class="empty-state-title">Belum ada anggota</div>
                                            <div class="empty-state-sub">Tambahkan anggota baru ke perpustakaan.</div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal-overlay" style="display:none"
        onclick="if(event.target===this)this.style.display='none'">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Tambah Anggota Baru</div>
                <button class="modal-close"
                    onclick="document.getElementById('addModal').style.display='none'">✕</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group"><label class="form-label">NIS *</label><input name="nis"
                                class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Kelas *</label><input name="kelas"
                                class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Nama Lengkap *</label><input
                                name="nama_anggota" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Email</label><input name="email" type="email"
                                class="form-control"></div>
                        <div class="form-group"><label class="form-label">Username *</label><input name="username"
                                class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Password *</label><input name="password"
                                type="password" class="form-control" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('addModal').style.display='none'">Batal</button>
                    <button name="add" class="btn btn-primary">Simpan Anggota</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editMember): ?>
    <div id="editModal" class="modal-overlay" onclick="if(event.target===this)location.href='anggota.php'">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Edit Anggota</div>
                <a href="anggota.php" class="modal-close">✕</a>
            </div>
            <form method="POST">
                <input type="hidden" name="id_anggota" value="<?= $editMember['id_anggota'] ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group"><label class="form-label">NIS *</label><input name="nis"
                                class="form-control" value="<?= htmlspecialchars($editMember['nis']) ?>" required></div>
                        <div class="form-group"><label class="form-label">Kelas *</label><input name="kelas"
                                class="form-control" value="<?= htmlspecialchars($editMember['kelas']) ?>" required>
                        </div>
                        <div class="form-group"><label class="form-label">Nama Lengkap *</label><input
                                name="nama_anggota" class="form-control"
                                value="<?= htmlspecialchars($editMember['nama_anggota']) ?>" required></div>
                        <div class="form-group"><label class="form-label">Email</label><input name="email" type="email"
                                class="form-control" value="<?= htmlspecialchars($editMember['email']??'') ?>"></div>
                        <div class="form-group"><label class="form-label">Password Baru <small>(kosongkan jika tidak
                                    diubah)</small></label><input name="password" type="password" class="form-control">
                        </div>
                        <div class="form-group"><label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="aktif" <?= ($editMember['status']??'')==='aktif'?'selected':'' ?>>Aktif
                                </option>
                                <option value="nonaktif" <?= ($editMember['status']??'')==='nonaktif'?'selected':'' ?>>
                                    Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="anggota.php" class="btn btn-ghost">Batal</a>
                    <button name="edit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('editModal').style.display = 'flex';
    </script>
    <?php endif; ?>

    <!-- RESET PW MODAL -->
    <div id="resetModal" class="modal-overlay" style="display:none"
        onclick="if(event.target===this)this.style.display='none'">
        <div class="modal" style="max-width:420px">
            <div class="modal-header">
                <div class="modal-title" id="resetTitle">Reset Password</div>
                <button class="modal-close"
                    onclick="document.getElementById('resetModal').style.display='none'">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_anggota" id="resetId">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Password Baru *</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('resetModal').style.display='none'">Batal</button>
                    <button name="reset_pw" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function showReset(id, nama) {
        document.getElementById('resetId').value = id;
        document.getElementById('resetTitle').textContent = 'Reset PW: ' + nama;
        document.getElementById('resetModal').style.display = 'flex';
    }
    </script>
</body>

</html>