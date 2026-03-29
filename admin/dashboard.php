<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn = getConnection();

// Ambil data pengguna untuk foto profil
$userId = getPenggunaId();
$userStmt = $conn->prepare("SELECT foto, nama_pengguna FROM pengguna WHERE id_pengguna = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Inisial untuk fallback avatar
$initials = '';
foreach (explode(' ', trim($userData['nama_pengguna'] ?? getPenggunaName())) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}
$fotoPath = (!empty($userData['foto']) && file_exists('../' . $userData['foto'])) 
            ? '../' . htmlspecialchars($userData['foto']) 
            : null;

function cnt($c, $q, $f = 'c') {
    return $c->query($q)->fetch_assoc()[$f] ?? 0;
}

$tb = cnt($conn, "SELECT COUNT(*) c FROM buku");
$ts = cnt($conn, "SELECT COUNT(*) c FROM buku WHERE status='tersedia'");
$ap = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'");
$ta = cnt($conn, "SELECT COUNT(*) c FROM anggota");
$td = cnt($conn, "SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'", 's');
$tl = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < NOW()");
$tp = cnt($conn, "SELECT COUNT(*) c FROM pengguna");
$kh = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Pengembalian' AND DATE(tgl_kembali_aktual) = CURDATE()");

$rows = $conn->query("SELECT t.*, a.nama_anggota, b.judul_buku, b.cover 
                      FROM transaksi t 
                      JOIN anggota a ON t.id_anggota = a.id_anggota 
                      JOIN buku b ON t.id_buku = b.id_buku 
                      ORDER BY t.tgl_pinjam DESC LIMIT 8");

$page_title = 'Dashboard';
$page_sub = 'Admin Panel · Perpustakaan Digital';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin/dashboard.css?v=<?= @filemtime('../assets/css/admin/dashboard.css')?:time() ?>">
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
                <a href="dashboard.php" class="nav-link active">
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
                <a href="kategori.php" class="nav-link">
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
                <!-- Welcome Box -->
                <div class="wb">
                    <div class="wb-avatar">
                        <?php if ($fotoPath): ?>
                        <img src="<?= $fotoPath ?>" alt="Foto Profil">
                        <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="wb-name">Selamat Datang, <?= htmlspecialchars(getPenggunaName()) ?></div>
                        <div class="wb-desc">Kelola seluruh sistem perpustakaan dari satu tempat · Panel Admin</div>
                    </div>
                    <div class="wb-actions">
                        <a href="buku.php" class="wb-btn1"><i class="fas fa-plus"></i> Tambah Buku</a>
                        <a href="laporan.php" class="wb-btn2"><i class="fas fa-chart-bar"></i> Lihat Laporan</a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Total Buku</h3>
                            <div class="stat-number"><?= $tb ?></div>
                            <div class="stat-desc success"><i class="fas fa-arrow-up"></i> <?= $ts ?> tersedia</div>
                        </div>
                        <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Aktif Dipinjam</h3>
                            <div class="stat-number"><?= $ap ?></div>
                            <div class="stat-desc <?= $tl > 0 ? 'warning' : '' ?>"><?= $tl ?> terlambat</div>
                        </div>
                        <div class="stat-icon red"><i class="fas fa-exchange-alt"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Total Anggota</h3>
                            <div class="stat-number"><?= $ta ?></div>
                            <div class="stat-desc"><?= $tp ?> pengguna sistem</div>
                        </div>
                        <div class="stat-icon green"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Denda Belum Bayar</h3>
                            <div class="stat-number">Rp <?= number_format($td, 0, ',', '.') ?></div>
                            <div class="stat-desc warning">perlu diselesaikan</div>
                        </div>
                        <div class="stat-icon amber"><i class="fas fa-coins"></i></div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="recent-card">
                    <div class="recent-header">
                        <div class="recent-title">
                            <i class="fas fa-history"></i>
                            <h2>Transaksi Terbaru</h2>
                        </div>
                        <a href="transaksi.php" class="recent-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($rows && $rows->num_rows > 0): while($r = $rows->fetch_assoc()):
                                    $late = $r['status_transaksi'] === 'Peminjaman' && strtotime($r['tgl_kembali_rencana']) < time();
                                    if ($r['status_transaksi'] === 'Pengembalian') {
                                        $statusClass = 'success';
                                        $statusText = '✓ Kembali';
                                    } elseif ($late) {
                                        $statusClass = 'danger';
                                        $statusText = '⚠ Terlambat';
                                    } else {
                                        $statusClass = 'warning';
                                        $statusText = '⇄ Dipinjam';
                                    }
                                ?>
                                <tr>
                                    <td class="book-cover-cell">
                                        <?php if (!empty($r['cover']) && file_exists('../'.$r['cover'])): ?>
                                        <img class="cover-thumb" src="../<?= htmlspecialchars($r['cover']) ?>" alt="">
                                        <?php else: ?>
                                        <div class="cover-thumb"
                                            style="background: var(--neutral-100); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-book" style="color: var(--neutral-400);"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="fw-600"><?= htmlspecialchars($r['nama_anggota']) ?></span></td>
                                    <td><?= htmlspecialchars(mb_strimwidth($r['judul_buku'], 0, 34, '…')) ?></td>
                                    <td><?= date('d M Y', strtotime($r['tgl_pinjam'])) ?></td>
                                    <td><?= date('d M Y', strtotime($r['tgl_kembali_rencana'])) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6"
                                        style="text-align: center; padding: 40px; color: var(--neutral-500);">Belum ada
                                        transaksi</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mini Stats -->
                <div class="mini-stats">
                    <div class="mini-card">
                        <div class="mini-icon rust"><i class="fas fa-book"></i></div>
                        <div class="mini-info">
                            <h4><?= $tb - $ts ?></h4>
                            <p>Buku Sedang Dipinjam</p>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="mini-icon green"><i class="fas fa-undo-alt"></i></div>
                        <div class="mini-info">
                            <h4><?= $kh ?></h4>
                            <p>Pengembalian Hari Ini</p>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="mini-icon amber"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="mini-info">
                            <h4><?= $tl ?></h4>
                            <p>Keterlambatan Aktif</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="../assets/js/script.js"></script>
</body>

</html>