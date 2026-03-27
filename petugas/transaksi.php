<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();

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
$totalPinjam = $conn->query("SELECT COUNT(*) as total FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['total'];
$totalKembali = $conn->query("SELECT COUNT(*) as total FROM transaksi WHERE status_transaksi='Pengembalian'")->fetch_assoc()['total'];
$totalTerlambat = $conn->query("SELECT COUNT(*) as total FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < NOW()")->fetch_assoc()['total'];

// Add transaction
if (isset($_POST['add'])) {
    $id_ang = (int)$_POST['id_anggota'];
    $id_buku = (int)$_POST['id_buku'];
    $tgl_pinjam = $_POST['tgl_pinjam'];
    $tgl_rencana = $_POST['tgl_kembali_rencana'];
    // Check stock
    $stok = $conn->query("SELECT stok FROM buku WHERE id_buku=$id_buku")->fetch_assoc()['stok'] ?? 0;
    if ($stok < 1) {
        $msg = 'Stok buku habis!'; $msgType='warning';
    } else {
        $s = $conn->prepare("INSERT INTO transaksi(id_anggota,id_buku,tgl_pinjam,tgl_kembali_rencana,status_transaksi) VALUES(?,?,?,?,'Peminjaman')");
        $s->bind_param("iiss",$id_ang,$id_buku,$tgl_pinjam,$tgl_rencana);
        if ($s->execute()) {
            $conn->query("UPDATE buku SET stok=stok-1, status=IF(stok-1>0,'tersedia','tidak') WHERE id_buku=$id_buku");
            $msg='Transaksi berhasil dicatat!'; $msgType='success';
        } else { 
            $msg='Gagal: '.$conn->error; $msgType='danger'; 
        }
        $s->close();
    }
}

// Return book
if (isset($_POST['return'])) {
    $id_t = (int)$_POST['id_transaksi'];
    $tgl_kembali = $_POST['tgl_kembali'];
    $t = $conn->query("SELECT * FROM transaksi WHERE id_transaksi=$id_t")->fetch_assoc();
    $days = max(0, (strtotime($tgl_kembali)-strtotime($t['tgl_kembali_rencana']))/(60*60*24));
    $denda_total = ceil($days) * 1000;
    $s = $conn->prepare("UPDATE transaksi SET status_transaksi='Pengembalian',tgl_kembali_aktual=? WHERE id_transaksi=?");
    $s->bind_param("si",$tgl_kembali,$id_t); 
    $s->execute(); 
    $s->close();
    $conn->query("UPDATE buku SET stok=stok+1, status='tersedia' WHERE id_buku={$t['id_buku']}");
    if ($denda_total > 0) {
        $conn->query("INSERT INTO denda(id_transaksi,jumlah_hari,total_denda,status_bayar) VALUES($id_t,".ceil($days).",$denda_total,'belum')");
        $msg="Buku dikembalikan. Denda: Rp ".number_format($denda_total,0,',','.'); 
        $msgType='warning';
    } else {
        $msg='Buku berhasil dikembalikan. Tidak ada denda.'; 
        $msgType='success';
    }
}

$anggota_list = $conn->query("SELECT * FROM anggota WHERE status='aktif' ORDER BY nama_anggota");
$buku_list = $conn->query("SELECT * FROM buku WHERE stok>0 ORDER BY judul_buku");

$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$q = "SELECT t.*,a.nama_anggota,a.nis,a.kelas,b.judul_buku,b.cover 
      FROM transaksi t 
      JOIN anggota a ON t.id_anggota=a.id_anggota 
      JOIN buku b ON t.id_buku=b.id_buku 
      WHERE 1=1";
if ($filter_status) $q .= " AND t.status_transaksi='$filter_status'";
if ($search) {
    $search = $conn->real_escape_string($search);
    $q .= " AND (a.nama_anggota LIKE '%$search%' OR b.judul_buku LIKE '%$search%')";
}
$q .= " ORDER BY t.tgl_pinjam DESC";
$transaksi = $conn->query($q);

$returnItem = null;
if (isset($_GET['return'])) {
    $id = (int)$_GET['return'];
    $returnItem = $conn->query("SELECT t.*,a.nama_anggota,b.judul_buku,b.cover FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_transaksi=$id")->fetch_assoc();
}

$page_title = 'Manajemen Transaksi';
$page_sub   = 'Pencatatan Peminjaman & Pengembalian Buku';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi — Petugas Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/petugas_transaksi.css">
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>

        <div class="main-area">
            <?php include 'includes/header.php'; ?>

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
                        <h1 class="page-header-title">Manajemen Transaksi</h1>
                        <p class="page-header-sub">Pencatatan peminjaman dan pengembalian buku</p>
                    </div>
                    <button class="btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
                        <i class="fas fa-plus-circle"></i>
                        Catat Peminjaman
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-book-reader"></i></div>
                        <div class="stat-info">
                            <h3>Sedang Dipinjam</h3>
                            <div class="stat-number"><?= $totalPinjam ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3>Sudah Kembali</h3>
                            <div class="stat-number"><?= $totalKembali ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <h3>Terlambat</h3>
                            <div class="stat-number"><?= $totalTerlambat ?></div>
                        </div>
                    </div>
                </div>

                <!-- Filter & Table -->
                <div class="card">
                    <form method="GET" class="filter-bar">
                        <div class="search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari anggota atau judul buku..."
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <select name="status" class="form-control" style="width:200px">
                            <option value="">Semua Status</option>
                            <option value="Peminjaman" <?= $filter_status==='Peminjaman'?'selected':'' ?>>Sedang
                                Dipinjam</option>
                            <option value="Pengembalian" <?= $filter_status==='Pengembalian'?'selected':'' ?>>Sudah
                                Kembali</option>
                        </select>
                        <button type="submit" class="btn-ghost btn-sm"><i class="fas fa-filter"></i> Filter</button>
                        <?php if ($search || $filter_status): ?>
                        <a href="transaksi.php" class="btn-ghost btn-sm"><i class="fas fa-times"></i> Reset</a>
                        <?php endif; ?>
                    </form>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Tgl Kembali</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($transaksi && $transaksi->num_rows > 0): $no=1; while($r=$transaksi->fetch_assoc()):
                                    $late = $r['status_transaksi']==='Peminjaman' && strtotime($r['tgl_kembali_rencana']) < time();
                                    if ($r['status_transaksi']==='Pengembalian') {
                                        $statusClass = 'status-kembali';
                                        $statusText = '✓ Kembali';
                                    } elseif ($late) {
                                        $statusClass = 'status-terlambat';
                                        $statusText = '⚠ Terlambat';
                                    } else {
                                        $statusClass = 'status-dipinjam';
                                        $statusText = '⇄ Dipinjam';
                                    }
                                ?>
                                <tr>
                                    <td class="text-muted text-sm"><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-600"><?= htmlspecialchars($r['nama_anggota']) ?></div>
                                        <div class="text-xs text-muted">NIS: <?= htmlspecialchars($r['nis']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($r['judul_buku']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_kembali_rencana'])) ?></td>
                                    <td><?= $r['tgl_kembali_aktual'] ? date('d/m/Y', strtotime($r['tgl_kembali_aktual'])) : '—' ?>
                                    </td>
                                    <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                    <td>
                                        <?php if ($r['status_transaksi']==='Peminjaman'): ?>
                                        <a href="?return=<?= $r['id_transaksi'] ?>" class="btn-sage btn-sm">
                                            <i class="fas fa-undo-alt"></i> Kembalikan
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted text-xs"><i class="fas fa-check-circle"></i>
                                            Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <div class="empty-state-ico">📋</div>
                                            <div class="empty-state-title">Belum ada transaksi</div>
                                            <p class="empty-state-sub">Klik tombol "Catat Peminjaman" untuk memulai
                                                transaksi</p>
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
                <h3 class="modal-title"><i class="fas fa-plus-circle"></i> Catat Peminjaman Baru</h3>
                <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'"><i
                        class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Anggota <span>*</span></label>
                            <select name="id_anggota" class="form-control" required>
                                <option value="">-- Pilih Anggota --</option>
                                <?php 
                                $anggota_list->data_seek(0);
                                while($a=$anggota_list->fetch_assoc()): 
                                ?>
                                <option value="<?= $a['id_anggota'] ?>">
                                    <?= htmlspecialchars($a['nama_anggota']) ?> (NIS: <?= $a['nis'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Buku <span>*</span></label>
                            <select name="id_buku" class="form-control" required>
                                <option value="">-- Pilih Buku --</option>
                                <?php 
                                $buku_list->data_seek(0);
                                while($b=$buku_list->fetch_assoc()): 
                                ?>
                                <option value="<?= $b['id_buku'] ?>">
                                    <?= htmlspecialchars($b['judul_buku']) ?> (Stok: <?= $b['stok'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Pinjam <span>*</span></label>
                            <input type="date" name="tgl_pinjam" class="form-control" value="<?= date('Y-m-d') ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rencana Kembali <span>*</span></label>
                            <input type="date" name="tgl_kembali_rencana" class="form-control"
                                value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ghost"
                        onclick="document.getElementById('addModal').style.display='none'">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" name="add" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($returnItem): ?>
    <!-- RETURN MODAL -->
    <div id="returnModal" class="modal-overlay" onclick="if(event.target===this)window.location.href='transaksi.php'">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-undo-alt"></i> Proses Pengembalian</h3>
                <a href="transaksi.php" class="modal-close"><i class="fas fa-times"></i></a>
            </div>
            <form method="POST">
                <input type="hidden" name="id_transaksi" value="<?= $returnItem['id_transaksi'] ?>">
                <div class="modal-body">
                    <div class="info-box">
                        <div class="info-box-title"><?= htmlspecialchars($returnItem['nama_anggota']) ?></div>
                        <div class="info-box-sub"><?= htmlspecialchars($returnItem['judul_buku']) ?></div>
                        <div class="info-box-meta">
                            <i class="far fa-calendar-alt"></i> Jatuh tempo:
                            <?= date('d M Y', strtotime($returnItem['tgl_kembali_rencana'])) ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Kembali <span>*</span></label>
                        <input type="date" name="tgl_kembali" class="form-control" value="<?= date('Y-m-d') ?>"
                            required>
                    </div>
                    <p class="text-muted text-xs">
                        <i class="fas fa-info-circle"></i> Denda Rp 1.000/hari jika terlambat dari jatuh tempo.
                    </p>
                </div>
                <div class="modal-footer">
                    <a href="transaksi.php" class="btn-ghost"><i class="fas fa-times"></i> Batal</a>
                    <button type="submit" name="return" class="btn-sage">
                        <i class="fas fa-check-circle"></i> Proses Pengembalian
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('returnModal').style.display = 'flex';
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