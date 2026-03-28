<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();
$msg = ''; $msgType = '';

// Ambil data user untuk header
$userId = getPenggunaId();
$userStmt = $conn->prepare("SELECT foto, nama_pengguna FROM pengguna WHERE id_pengguna = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Inisial untuk avatar
$initials = '';
foreach (explode(' ', trim($userData['nama_pengguna'] ?? getPenggunaName())) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}
$fotoPath = (!empty($userData['foto']) && file_exists('../' . $userData['foto'])) 
            ? '../' . htmlspecialchars($userData['foto']) 
            : null;

// Hitung statistik
$totalKategori = $conn->query("SELECT COUNT(*) as total FROM kategori")->fetch_assoc()['total'];
$totalBuku = $conn->query("SELECT COUNT(*) as total FROM buku")->fetch_assoc()['total'];

if (isset($_POST['add'])) {
    $nama = trim($_POST['nama_kategori']); $desk = trim($_POST['deskripsi']);
    $s = $conn->prepare("INSERT INTO kategori(nama_kategori,deskripsi) VALUES(?,?)");
    $s->bind_param("ss",$nama,$desk);
    $msg = $s->execute()?'Kategori berhasil ditambahkan!':'Gagal!'; 
    $msgType = $s->execute()?'success':'danger'; 
    $s->close();
}
if (isset($_POST['edit'])) {
    $id=(int)$_POST['id_kategori']; $nama=trim($_POST['nama_kategori']); $desk=trim($_POST['deskripsi']);
    $s=$conn->prepare("UPDATE kategori SET nama_kategori=?,deskripsi=? WHERE id_kategori=?");
    $s->bind_param("ssi",$nama,$desk,$id);
    $msg=$s->execute()?'Kategori berhasil diperbarui!':'Gagal!'; 
    $msgType='success'; $s->close();
}
if (isset($_POST['delete'])) {
    $id=(int)$_POST['id_kategori'];
    $chk=$conn->query("SELECT COUNT(*) c FROM buku WHERE id_kategori=$id")->fetch_assoc()['c'];
    if($chk>0){ $msg='Kategori masih digunakan oleh buku!'; $msgType='warning'; }
    else {
        $s=$conn->prepare("DELETE FROM kategori WHERE id_kategori=?");
        $s->bind_param("i",$id);
        $msg=$s->execute()?'Kategori berhasil dihapus!':'Gagal!'; 
        $msgType='success'; $s->close();
    }
}

$categories = $conn->query("SELECT k.*,(SELECT COUNT(*) FROM buku WHERE id_kategori=k.id_kategori) jml FROM kategori k ORDER BY nama_kategori");
$editCat = null;
if(isset($_GET['edit'])){
    $id=(int)$_GET['edit'];
    $s=$conn->prepare("SELECT * FROM kategori WHERE id_kategori=?");
    $s->bind_param("i",$id); $s->execute();
    $editCat=$s->get_result()->fetch_assoc();
}

$page_title = 'Manajemen Kategori';
$page_sub   = 'Kelola kategori buku perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori — Admin Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_kategori.css?v=<?= @filemtime('../assets/css/admin_kategori.css')?:time() ?>">
</head>

<body>
    <div class="app-wrap">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">📚</div>
                <div>
                    <div class="brand-name">Perpustakaan Digital</div>
                    <div class="brand-role">ADMINISTRATOR</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-section-label">UTAMA</span>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <span class="nav-section-label">MANAJEMEN</span>
                <a href="pengguna.php" class="nav-link">
                    <i class="fas fa-users-cog"></i>
                    <span>Pengguna</span>
                </a>
                <a href="anggota.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i>
                    <span>Anggota</span>
                </a>

                <span class="nav-section-label">KOLEKSI</span>
                <a href="kategori.php" class="nav-link active">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
                <a href="buku.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>Buku</span>
                </a>

                <span class="nav-section-label">TRANSAKSI</span>
                <a href="transaksi.php" class="nav-link">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transaksi</span>
                </a>
                <a href="denda.php" class="nav-link">
                    <i class="fas fa-coins"></i>
                    <span>Denda</span>
                </a>
                <a href="laporan.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>

                <span class="nav-section-label">AKUN</span>
                <a href="profil.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profil Saya</span>
                </a>
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-globe"></i>
                    <span>Beranda</span>
                </a>
            </nav>

            <div class="sidebar-foot">
                <a href="logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="main-area">
            <!-- HEADER -->
            <header class="topbar">
                <div class="page-info">
                    <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
                    <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date">
                        <i class="far fa-calendar-alt"></i> <?= date('d M Y') ?>
                    </div>
                    <div class="topbar-user">
                        <div class="topbar-avatar">
                            <?php if ($fotoPath): ?>
                            <img src="<?= $fotoPath ?>" alt="Foto">
                            <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                            <?php endif; ?>
                        </div>
                        <span class="topbar-username"><?= htmlspecialchars(getPenggunaName()) ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </header>

            <!-- CONTENT -->
            <main class="content">
                <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType ?>">
                    <i
                        class="fas <?= $msgType === 'success' ? 'fa-check-circle' : ($msgType === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle') ?>"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="page-header-title">Manajemen Kategori</h1>
                        <p class="page-header-sub">Kelola kategori buku perpustakaan</p>
                    </div>
                    <button class="btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
                        <i class="fas fa-tag"></i>
                        Tambah Kategori Baru
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-tags"></i></div>
                        <div class="stat-info">
                            <h3>Total Kategori</h3>
                            <div class="stat-number"><?= $totalKategori ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-book"></i></div>
                        <div class="stat-info">
                            <h3>Total Buku</h3>
                            <div class="stat-number"><?= $totalBuku ?></div>
                        </div>
                    </div>
                </div>

                <!-- Categories Table -->
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
                                <?php if ($categories && $categories->num_rows > 0): $no = 1; while($r = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-muted text-sm"><?= $no++ ?></td>
                                    <td><span class="fw-600"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
                                    <td><?= htmlspecialchars($r['deskripsi'] ?? '—') ?></td>
                                    <td><span class="badge badge-muted"><?= $r['jml'] ?> buku</span></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?edit=<?= $r['id_kategori'] ?>" class="btn-action btn-edit"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus kategori ini?')"
                                                style="display:inline">
                                                <input type="hidden" name="id_kategori"
                                                    value="<?= $r['id_kategori'] ?>">
                                                <button type="submit" name="delete" class="btn-action btn-delete"
                                                    title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="empty-state-ico">🗂️</div>
                                            <div class="empty-state-title">Belum ada kategori</div>
                                            <p class="empty-state-sub">Klik tombol "Tambah Kategori" untuk menambahkan
                                                kategori pertama</p>
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
    <div id="addModal" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-tag"
                        style="color: var(--primary-500); margin-right: 8px;"></i>Tambah Kategori Baru</h3>
                <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'"><i
                        class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Nama Kategori <span
                                    style="color: var(--danger-500);">*</span></label>
                            <input type="text" name="nama_kategori" class="form-control" required
                                placeholder="Contoh: Fiksi, Sains, Sejarah...">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3"
                                placeholder="Deskripsi singkat tentang kategori ini..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit"
                        onclick="document.getElementById('addModal').style.display='none'" style="padding: 10px 20px;">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" name="add" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editCat): ?>
    <!-- EDIT MODAL -->
    <div id="editModal" class="modal-overlay" onclick="if(event.target===this)window.location.href='kategori.php'">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-edit"
                        style="color: var(--info-500); margin-right: 8px;"></i>Edit Kategori</h3>
                <a href="kategori.php" class="modal-close"><i class="fas fa-times"></i></a>
            </div>
            <form method="POST">
                <input type="hidden" name="id_kategori" value="<?= $editCat['id_kategori'] ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Nama Kategori <span
                                    style="color: var(--danger-500);">*</span></label>
                            <input type="text" name="nama_kategori" class="form-control"
                                value="<?= htmlspecialchars($editCat['nama_kategori']) ?>" required>
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control"
                                rows="3"><?= htmlspecialchars($editCat['deskripsi'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="kategori.php" class="btn-edit" style="padding: 10px 20px;"><i class="fas fa-times"></i>
                        Batal</a>
                    <button type="submit" name="edit" class="btn-primary"><i class="fas fa-save"></i> Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('editModal').style.display = 'flex';
    </script>
    <?php endif; ?>

    <script>
    // Tutup modal dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('addModal').style.display = 'none';
        }
    });

    // Prevent form resubmission
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>