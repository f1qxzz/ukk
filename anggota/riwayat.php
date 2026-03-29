<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();

$conn = getConnection();
$id = getAnggotaId();

// Ambil data user untuk header
$userId = getAnggotaId();
$userStmt = $conn->prepare("SELECT foto, nama_anggota FROM anggota WHERE id_anggota = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Inisial untuk avatar
$initials = '';
foreach (explode(' ', trim($userData['nama_anggota'] ?? getAnggotaName())) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}
$fotoPath = (!empty($userData['foto']) && file_exists('../' . $userData['foto'])) 
            ? '../' . htmlspecialchars($userData['foto']) 
            : null;

$trans = $conn->query("SELECT t.*, b.judul_buku, b.pengarang, b.penerbit, b.cover, d.total_denda, d.status_bayar 
                       FROM transaksi t 
                       JOIN buku b ON t.id_buku = b.id_buku 
                       LEFT JOIN denda d ON t.id_transaksi = d.id_transaksi 
                       WHERE t.id_anggota = $id 
                       ORDER BY t.tgl_pinjam DESC");

// Hitung statistik
$totalPinjam = $trans->num_rows;
$totalDenda = 0;
$trans->data_seek(0);
while($r = $trans->fetch_assoc()) {
    if ($r['total_denda'] > 0 && $r['status_bayar'] === 'belum') {
        $totalDenda += $r['total_denda'];
    }
}
$trans->data_seek(0);

$page_title = 'Riwayat Peminjaman';
$page_sub   = 'Lihat semua aktivitas peminjaman Anda';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/anggota/riwayat.css?v=<?= @filemtime('../assets/css/anggota/riwayat.css')?:time() ?>">
</head>

<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-wrap">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">📚</div>
                <div>
                    <div class="brand-name">Perpustakaan Digital</div>
                    <div class="brand-role">ANGGOTA</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-section-label">UTAMA</span>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <span class="nav-section-label">KATALOG</span>
                <a href="katalog.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <span>Katalog Buku</span>
                </a>

                <span class="nav-section-label">TRANSAKSI</span>
                <a href="pinjam.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>Pinjam Buku</span>
                </a>
                <a href="kembali.php" class="nav-link">
                    <i class="fas fa-undo-alt"></i>
                    <span>Kembalikan Buku</span>
                </a>
                <a href="riwayat.php" class="nav-link active">
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span>
                </a>

                <span class="nav-section-label">KOMUNITAS</span>
                <a href="ulasan.php" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>Ulasan Buku</span>
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
                <div class="topbar-left" style="display: flex; align-items: center; gap: 16px;">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-info">
                        <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
                        <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date">
                        <i class="far fa-calendar-alt"></i>
                        <span><?= date('d M Y') ?></span>
                    </div>
                    <div class="topbar-user">
                        <div class="topbar-avatar">
                            <?php if ($fotoPath): ?>
                            <img src="<?= $fotoPath ?>" alt="Foto">
                            <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                            <?php endif; ?>
                        </div>
                        <span class="topbar-username"><?= htmlspecialchars(getAnggotaName()) ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </header>

            <!-- CONTENT -->
            <main class="content">
                <div class="page-header">
                    <div>
                        <h1 class="page-header-title">Riwayat Peminjaman</h1>
                        <p class="page-header-sub">Lihat semua aktivitas peminjaman Anda</p>
                    </div>
                    <a href="pinjam.php" class="btn-logout"
                        style="background: linear-gradient(135deg, var(--soft-purple), var(--soft-lavender)); color: white; border: none;">
                        <i class="fas fa-plus-circle"></i> Pinjam Buku
                    </a>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-info">
                            <h3>Total Pinjaman</h3>
                            <div class="stat-number"><?= $totalPinjam ?></div>
                            <div class="stat-sub">transaksi</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="stat-info">
                            <h3>Total Denda</h3>
                            <div class="stat-number">Rp <?= number_format($totalDenda, 0, ',', '.') ?></div>
                            <div class="stat-sub">belum dibayar</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-star"></i></div>
                        <div class="stat-info">
                            <h3>Ulasan</h3>
                            <div class="stat-number">0</div>
                            <div class="stat-sub">yang ditulis</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Daftar Riwayat Peminjaman</h2>
                        <span class="badge-total">
                            <i class="fas fa-list"></i> <?= $totalPinjam ?> transaksi
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Buku</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Tgl Kembali</th>
                                    <th>Status</th>
                                    <th>Denda</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($trans && $trans->num_rows > 0): $no = 1; while($r = $trans->fetch_assoc()): 
                                    $late = strtotime($r['tgl_kembali_rencana']) < time() && $r['status_transaksi'] === 'Peminjaman';
                                ?>
                                <tr>
                                    <td class="book-cover-cell">
                                        <?php if (!empty($r['cover']) && file_exists('../' . $r['cover'])): ?>
                                        <img src="../<?= htmlspecialchars($r['cover']) ?>" alt="Cover"
                                            class="book-cover-img">
                                        <?php else: ?>
                                        <div class="book-cover-placeholder">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-600"><?= htmlspecialchars($r['judul_buku']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($r['pengarang']) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_kembali_rencana'])) ?></td>
                                    <td>
                                        <?php if ($r['tgl_kembali_aktual']): ?>
                                        <?= date('d/m/Y', strtotime($r['tgl_kembali_aktual'])) ?>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['status_transaksi'] === 'Pengembalian'): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> Dikembalikan
                                        </span>
                                        <?php elseif ($late): ?>
                                        <span class="badge badge-danger">
                                            <i class="fas fa-exclamation-triangle"></i> Terlambat
                                        </span>
                                        <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Dipinjam
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['total_denda'] > 0): ?>
                                        <span
                                            class="badge <?= $r['status_bayar'] === 'sudah' ? 'badge-success' : 'badge-danger' ?>">
                                            <i class="fas fa-coins"></i>
                                            Rp <?= number_format($r['total_denda'], 0, ',', '.') ?>
                                            <br>
                                            <small><?= $r['status_bayar'] === 'sudah' ? 'Lunas' : 'Belum' ?></small>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-state-ico">📋</div>
                                            <div class="empty-state-title">Belum ada riwayat peminjaman</div>
                                            <div class="empty-state-sub">
                                                Anda belum pernah meminjam buku.
                                                <a href="pinjam.php">Pinjam buku sekarang</a>
                                            </div>
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

    <script>
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        });
    }

    // Prevent form resubmission
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>