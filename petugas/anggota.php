<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requirePetugas();

$conn = getConnection();
$msg = '';
$msgType = '';

/* =========================
   TAMBAH ANGGOTA
========================= */
if (isset($_POST['add'])) {

    $nis   = $_POST['nis'];
    $nama  = $_POST['nama_anggota'];
    $uname = $_POST['username'];
    $pw    = $_POST['password'];
    $email = $_POST['email'];
    $kelas = $_POST['kelas'];

    $chk = $conn->prepare("SELECT id_anggota FROM anggota WHERE username=? OR nis=?");
    $chk->bind_param("ss",$uname,$nis);
    $chk->execute();
    $chk->store_result();

    if($chk->num_rows > 0){

        $msg = 'NIS atau Username sudah digunakan!';
        $msgType = 'warning';

    } else {

        $s = $conn->prepare("
            INSERT INTO anggota
            (nis,nama_anggota,username,password,email,kelas)
            VALUES (?,?,?,?,?,?)
        ");

        $s->bind_param("ssssss",$nis,$nama,$uname,$pw,$email,$kelas);

        $ok = $s->execute();

        $msg = $ok ? 'Anggota berhasil ditambahkan!' : 'Gagal menambahkan anggota';
        $msgType = $ok ? 'success' : 'danger';

        $s->close();
    }

    $chk->close();
}


/* =========================
   EDIT ANGGOTA
========================= */
if (isset($_POST['edit'])) {

    $id    = (int)$_POST['id_anggota'];
    $nis   = $_POST['nis'];
    $nama  = $_POST['nama_anggota'];
    $email = $_POST['email'];
    $kelas = $_POST['kelas'];
    $status= $_POST['status'];

    if(!empty($_POST['password'])){

        $pw = $_POST['password'];

        $s = $conn->prepare("
            UPDATE anggota
            SET nis=?,nama_anggota=?,email=?,kelas=?,status=?,password=?
            WHERE id_anggota=?
        ");

        $s->bind_param("ssssssi",$nis,$nama,$email,$kelas,$status,$pw,$id);

    } else {

        $s = $conn->prepare("
            UPDATE anggota
            SET nis=?,nama_anggota=?,email=?,kelas=?,status=?
            WHERE id_anggota=?
        ");

        $s->bind_param("sssssi",$nis,$nama,$email,$kelas,$status,$id);
    }

    $ok = $s->execute();

    $msg = $ok ? 'Data diperbarui!' : 'Gagal memperbarui data';
    $msgType = $ok ? 'success' : 'danger';

    $s->close();
}


/* =========================
   HAPUS ANGGOTA
========================= */
if (isset($_POST['delete'])) {

    $id = (int)$_POST['id_anggota'];

    $chk = $conn->query("
        SELECT COUNT(*) c
        FROM transaksi
        WHERE id_anggota=$id
        AND status_transaksi='Peminjaman'
    ")->fetch_assoc()['c'];

    if($chk > 0){

        $msg = 'Anggota masih memiliki peminjaman aktif!';
        $msgType = 'warning';

    } else {

        $s = $conn->prepare("DELETE FROM anggota WHERE id_anggota=?");
        $s->bind_param("i",$id);

        $ok = $s->execute();

        $msg = $ok ? 'Anggota dihapus!' : 'Gagal menghapus anggota';
        $msgType = $ok ? 'success' : 'danger';

        $s->close();
    }
}


/* =========================
   RESET PASSWORD
========================= */
if (isset($_POST['reset_pw'])) {

    $id = (int)$_POST['id_anggota'];
    $pw = trim($_POST['new_password']);

    $s = $conn->prepare("
        UPDATE anggota
        SET password=?
        WHERE id_anggota=?
    ");

    $s->bind_param("si",$pw,$id);

    $ok = $s->execute();

    $msg = $ok ? 'Password berhasil direset!' : 'Reset gagal';
    $msgType = $ok ? 'success' : 'danger';

    $s->close();
}


/* =========================
   SEARCH
========================= */
$search = $_GET['search'] ?? '';

$q = "SELECT * FROM anggota";

if($search){

    $es = $conn->real_escape_string($search);

    $q .= "
        WHERE nama_anggota LIKE '%$es%'
        OR nis LIKE '%$es%'
        OR kelas LIKE '%$es%'
    ";
}

$q .= " ORDER BY id_anggota DESC";

$members = $conn->query($q);


/* =========================
   EDIT MODE
========================= */
$editMember = null;

if(isset($_GET['edit'])){

    $id = (int)$_GET['edit'];

    $s = $conn->prepare("SELECT * FROM anggota WHERE id_anggota=?");
    $s->bind_param("i",$id);
    $s->execute();

    $editMember = $s->get_result()->fetch_assoc();
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
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/petugas.css">
    <link rel="stylesheet" href="../assets/css/table.css">
    <link rel="stylesheet" href="../assets/css/form.css">
    <style>
    /* Variables untuk petugas */
    :root {
        --petugas-primary: #2c4f7c;
        --petugas-primary-light: #3a6ea5;
        --petugas-primary-soft: #e8f0fe;
        --petugas-success: #10b981;
        --petugas-warning: #f59e0b;
        --petugas-danger: #ef4444;
    }

    /* Page Header */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .page-header-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .page-header-sub {
        font-size: 0.9rem;
        color: #64748b;
    }

    /* Button Primary */
    .btn-primary {
        background: var(--petugas-primary);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 40px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 10px rgba(44, 79, 124, 0.2);
    }

    .btn-primary:hover {
        background: var(--petugas-primary-light);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(44, 79, 124, 0.3);
    }

    .btn-primary svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        stroke-width: 2.5;
        fill: none;
    }

    /* Button Ghost */
    .btn-ghost {
        background: transparent;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
    }

    .btn-ghost:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .btn-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .btn-danger:hover {
        background: #fecaca;
    }

    .btn-sm {
        padding: 4px 12px;
        font-size: 0.75rem;
    }

    /* Filter Bar */
    .filter-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .search-wrap {
        flex: 1;
        min-width: 250px;
    }

    .search-wrap input {
        width: 100%;
        padding: 10px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 40px;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .search-wrap input:focus {
        outline: none;
        border-color: var(--petugas-primary);
        box-shadow: 0 0 0 3px rgba(44, 79, 124, 0.1);
    }

    /* Card */
    .card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 24px;
    }

    /* Table */
    .table-wrap {
        overflow-x: auto;
        padding: 0 20px 20px 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead tr {
        background: #f8fafc;
        border-radius: 12px;
    }

    th {
        padding: 16px 12px;
        text-align: left;
        font-weight: 600;
        font-size: 0.8rem;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    td {
        padding: 16px 12px;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
    }

    tr:hover td {
        background: #f8fafc;
    }

    .text-muted {
        color: #94a3b8;
    }

    .text-sm {
        font-size: 0.85rem;
    }

    .fw-600 {
        font-weight: 600;
    }

    /* Badge */
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 40px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-tersedia {
        background: #d1fae5;
        color: #065f46;
    }

    .status-terlambat {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state-ico {
        font-size: 4rem;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.2rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }

    .empty-state-sub {
        font-size: 0.9rem;
        color: #94a3b8;
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
    }

    .modal {
        background: white;
        border-radius: 24px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalSlide 0.3s ease;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    @keyframes modalSlide {
        from {
            transform: translateY(-30px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .modal-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: #64748b;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
    }

    .modal-close:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .modal-body {
        padding: 24px;
    }

    .modal-footer {
        padding: 20px 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: #f8fafc;
    }

    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.9rem;
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--petugas-primary);
        box-shadow: 0 0 0 3px rgba(44, 79, 124, 0.1);
    }

    select.form-control {
        background-color: white;
    }

    /* Alert */
    .alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        animation: slideDown 0.3s ease;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .alert-warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    </style>
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>
        <div class="main-area">
            <?php include 'includes/header.php'; ?>
            <main class="content">

                <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType ?>">
                    <?php if($msgType === 'success'): ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <?php elseif($msgType === 'warning'): ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <?php else: ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    <?php endif; ?>
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endif; ?>

                <div class="page-header">
                    <div>
                        <div class="page-header-title">Data Anggota</div>
                        <div class="page-header-sub">Tambah, edit, atau hapus data anggota perpustakaan</div>
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
                            <input type="text" name="search" placeholder="Cari nama, NIS, atau kelas…"
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <button type="submit" class="btn-ghost btn-sm">Cari</button>
                        <?php if ($search): ?><a href="anggota.php" class="btn-ghost btn-sm">Reset</a><?php endif; ?>
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
                                    <td>
                                        <span
                                            class="badge <?= $r['status']==='aktif'?'status-tersedia':'status-terlambat' ?>">
                                            <?= $r['status']==='aktif'?'● Aktif':'○ Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?edit=<?= $r['id_anggota'] ?>" class="btn-ghost btn-sm">Edit</a>
                                            <button class="btn-ghost btn-sm"
                                                onclick="showReset(<?= $r['id_anggota'] ?>,'<?= htmlspecialchars(addslashes($r['nama_anggota'])) ?>')">
                                                Reset PW
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Hapus anggota ini?')"
                                                style="display:inline">
                                                <input type="hidden" name="id_anggota" value="<?= $r['id_anggota'] ?>">
                                                <button name="delete" class="btn-ghost btn-sm btn-danger">Hapus</button>
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
                        <div class="form-group">
                            <label class="form-label">NIS <span style="color: var(--petugas-danger);">*</span></label>
                            <input type="text" name="nis" class="form-control" required placeholder="Contoh: 2023001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kelas <span style="color: var(--petugas-danger);">*</span></label>
                            <input type="text" name="kelas" class="form-control" required placeholder="Contoh: X IPA 1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span
                                    style="color: var(--petugas-danger);">*</span></label>
                            <input type="text" name="nama_anggota" class="form-control" required
                                placeholder="Masukkan nama lengkap">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="contoh@email.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username <span
                                    style="color: var(--petugas-danger);">*</span></label>
                            <input type="text" name="username" class="form-control" required
                                placeholder="Buat username">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password <span
                                    style="color: var(--petugas-danger);">*</span></label>
                            <input type="password" name="password" class="form-control" required
                                placeholder="Min. 6 karakter">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ghost"
                        onclick="document.getElementById('addModal').style.display='none'">Batal</button>
                    <button type="submit" name="add" class="btn-primary" style="padding: 8px 24px;">Simpan
                        Anggota</button>
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
                        <div class="form-group">
                            <label class="form-label">NIS *</label>
                            <input type="text" name="nis" class="form-control"
                                value="<?= htmlspecialchars($editMember['nis']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kelas *</label>
                            <input type="text" name="kelas" class="form-control"
                                value="<?= htmlspecialchars($editMember['kelas']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama_anggota" class="form-control"
                                value="<?= htmlspecialchars($editMember['nama_anggota']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($editMember['email']??'') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control"
                                placeholder="Kosongkan jika tidak diubah">
                            <small style="color: #64748b; font-size: 0.7rem;">Isi hanya jika ingin mengubah
                                password</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
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
                    <a href="anggota.php" class="btn-ghost">Batal</a>
                    <button type="submit" name="edit" class="btn-primary" style="padding: 8px 24px;">Simpan
                        Perubahan</button>
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
                        <label class="form-label">Password Baru <span
                                style="color: var(--petugas-danger);">*</span></label>
                        <input type="password" name="new_password" class="form-control" required
                            placeholder="Minimal 6 karakter">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ghost"
                        onclick="document.getElementById('resetModal').style.display='none'">Batal</button>
                    <button type="submit" name="reset_pw" class="btn-primary" style="padding: 8px 24px;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showReset(id, nama) {
        document.getElementById('resetId').value = id;
        document.getElementById('resetTitle').textContent = 'Reset Password: ' + nama;
        document.getElementById('resetModal').style.display = 'flex';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const addModal = document.getElementById('addModal');
        const resetModal = document.getElementById('resetModal');
        if (event.target === addModal) {
            addModal.style.display = 'none';
        }
        if (event.target === resetModal) {
            resetModal.style.display = 'none';
        }
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>