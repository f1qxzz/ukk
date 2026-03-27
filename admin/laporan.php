<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

$conn = getConnection();

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

// Statistik
$total_buku     = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];
$buku_tersedia  = $conn->query("SELECT COUNT(*) c FROM buku WHERE status='tersedia'")->fetch_assoc()['c'];
$total_anggota  = $conn->query("SELECT COUNT(*) c FROM anggota")->fetch_assoc()['c'];
$total_pinjam   = $conn->query("SELECT COUNT(*) c FROM transaksi")->fetch_assoc()['c'];
$aktif_pinjam   = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$total_denda    = $conn->query("SELECT COALESCE(SUM(total_denda),0) s FROM denda")->fetch_assoc()['s'];
$denda_belum    = $conn->query("SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'")->fetch_assoc()['s'];

$trans_all = $conn->query("SELECT t.*,a.nama_anggota,a.nis,a.kelas,b.judul_buku,b.cover 
                           FROM transaksi t 
                           JOIN anggota a ON t.id_anggota=a.id_anggota 
                           JOIN buku b ON t.id_buku=b.id_buku 
                           ORDER BY t.tgl_pinjam DESC");
$denda_all = $conn->query("SELECT d.*,a.nama_anggota,b.judul_buku,b.cover 
                           FROM denda d 
                           JOIN transaksi t ON d.id_transaksi=t.id_transaksi 
                           JOIN anggota a ON t.id_anggota=a.id_anggota 
                           JOIN buku b ON t.id_buku=b.id_buku 
                           ORDER BY d.id_denda DESC");

$page_title = 'Laporan Sistem';
$page_sub   = 'Rekap data dan statistik perpustakaan';
$no_laporan = 'RPT-' . date('Ymd') . '-001';
$tgl_cetak  = date('d F Y');
$jam_cetak  = date('H:i') . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan — Admin Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_laporan.css">
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
                <a href="laporan.php" class="nav-link active">
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
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="page-header-title">Laporan Sistem</h1>
                        <p class="page-header-sub">Rekap data dan statistik perpustakaan</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                        </div>
                        <div class="stat-label">Total Buku</div>
                        <div class="stat-value"><?= number_format($total_buku) ?></div>
                        <div class="stat-sub"><?= $buku_tersedia ?> unit tersedia</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-label">Anggota</div>
                        <div class="stat-value"><?= number_format($total_anggota) ?></div>
                        <div class="stat-sub">Terdaftar aktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange"><i class="fas fa-exchange-alt"></i></div>
                        </div>
                        <div class="stat-label">Transaksi</div>
                        <div class="stat-value"><?= number_format($total_pinjam) ?></div>
                        <div class="stat-sub"><?= $aktif_pinjam ?> sedang dipinjam</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon red"><i class="fas fa-coins"></i></div>
                        </div>
                        <div class="stat-label">Total Denda</div>
                        <div class="stat-value">Rp <?= number_format($total_denda,0,',','.') ?></div>
                        <div class="stat-sub">Belum lunas: Rp <?= number_format($denda_belum,0,',','.') ?></div>
                    </div>
                </div>

                <!-- Print control bar (screen only) -->
                <div class="print-bar-wrap no-print">
                    <div class="print-bar-label">
                        <i class="fas fa-file-pdf" style="color: var(--danger-500);"></i>
                        Pratinjau dokumen laporan — No. <strong><?= $no_laporan ?></strong>
                    </div>
                    <button onclick="window.print()" class="btn-primary">
                        <i class="fas fa-print"></i>
                        Cetak / Export PDF
                    </button>
                </div>

                <!-- REPORT DOCUMENT -->
                <div class="report-document">
                    <!-- Letterhead -->
                    <div class="rpt-letterhead">
                        <div class="rpt-org">
                            <div class="rpt-logo-mark">P</div>
                            <div class="rpt-org-name">Perpustakaan Digital</div>
                            <div class="rpt-org-sub">Sistem Manajemen Perpustakaan</div>
                        </div>
                        <div class="rpt-meta">
                            <div class="rpt-doc-number"><?= $no_laporan ?></div>
                            <div><strong>Tanggal Cetak</strong><br><?= $tgl_cetak ?></div>
                            <div><strong>Waktu</strong><br><?= $jam_cetak ?></div>
                            <div><strong>Dicetak oleh</strong><br>Administrator</div>
                        </div>
                    </div>

                    <!-- Title band -->
                    <div class="rpt-title-band">
                        <div class="rpt-title">Laporan Sistem Perpustakaan</div>
                        <div class="rpt-title-sub">Periode: <?= $tgl_cetak ?></div>
                    </div>

                    <!-- Stats -->
                    <div class="rpt-stats">
                        <div class="rpt-stat">
                            <div class="rpt-stat-bar"></div>
                            <div class="rpt-stat-label">Total Buku</div>
                            <div class="rpt-stat-val"><?= number_format($total_buku) ?></div>
                            <div class="rpt-stat-sub"><?= $buku_tersedia ?> unit tersedia</div>
                        </div>
                        <div class="rpt-stat">
                            <div class="rpt-stat-bar"></div>
                            <div class="rpt-stat-label">Anggota</div>
                            <div class="rpt-stat-val"><?= number_format($total_anggota) ?></div>
                            <div class="rpt-stat-sub">terdaftar aktif</div>
                        </div>
                        <div class="rpt-stat">
                            <div class="rpt-stat-bar"></div>
                            <div class="rpt-stat-label">Transaksi</div>
                            <div class="rpt-stat-val"><?= number_format($total_pinjam) ?></div>
                            <div class="rpt-stat-sub"><?= $aktif_pinjam ?> sedang dipinjam</div>
                        </div>
                        <div class="rpt-stat">
                            <div class="rpt-stat-bar"></div>
                            <div class="rpt-stat-label">Total Denda</div>
                            <div class="rpt-stat-val money">Rp <?= number_format($total_denda,0,',','.') ?></div>
                            <div class="rpt-stat-sub">belum lunas: Rp <?= number_format($denda_belum,0,',','.') ?></div>
                        </div>
                    </div>

                    <!-- Section 1: Transactions -->
                    <div class="rpt-section">
                        <div class="rpt-section-head">
                            <div class="rpt-section-num">1</div>
                            <div class="rpt-section-title">Laporan Transaksi Peminjaman</div>
                            <div class="rpt-section-count"><?= $trans_all ? $trans_all->num_rows : 0 ?> data</div>
                        </div>
                        <table class="rpt-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Anggota</th>
                                    <th>NIS</th>
                                    <th>Kelas</th>
                                    <th>Judul Buku</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Tgl Kembali</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($trans_all && $trans_all->num_rows > 0): $n = 1; ?>
                                <?php while($r = $trans_all->fetch_assoc()):
                                    $late = $r['status_transaksi'] === 'Peminjaman' && strtotime($r['tgl_kembali_rencana']) < time();
                                    if ($r['status_transaksi'] === 'Pengembalian') { 
                                        $sc = 'kembali';  
                                        $sl = '<i class="fas fa-check-circle"></i> Kembali'; 
                                    } elseif ($late) { 
                                        $sc = 'terlambat'; 
                                        $sl = '<i class="fas fa-exclamation-triangle"></i> Terlambat'; 
                                    } else { 
                                        $sc = 'dipinjam';  
                                        $sl = '<i class="fas fa-book-reader"></i> Dipinjam'; 
                                    }
                                ?>
                                <tr>
                                    <td class="num"><?= $n++ ?></td>
                                    <td class="name"><?= htmlspecialchars($r['nama_anggota']) ?></td>
                                    <td class="mono"><?= htmlspecialchars($r['nis']) ?></td>
                                    <td><?= htmlspecialchars($r['kelas']) ?></td>
                                    <td class="book"><?= htmlspecialchars($r['judul_buku']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_kembali_rencana'])) ?></td>
                                    <td><?= $r['tgl_kembali_aktual'] ? date('d/m/Y', strtotime($r['tgl_kembali_aktual'])) : '—' ?>
                                    </td>
                                    <td><span class="rpt-badge <?= $sc ?>"><?= $sl ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="9" class="rpt-empty">Belum ada data transaksi</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Section 2: Fines -->
                    <div class="rpt-section">
                        <div class="rpt-section-head">
                            <div class="rpt-section-num">2</div>
                            <div class="rpt-section-title">Laporan Denda Keterlambatan</div>
                            <div class="rpt-section-count"><?= $denda_all ? $denda_all->num_rows : 0 ?> data</div>
                        </div>
                        <table class="rpt-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Anggota</th>
                                    <th>Judul Buku</th>
                                    <th>Keterlambatan</th>
                                    <th>Total Denda</th>
                                    <th>Status Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($denda_all && $denda_all->num_rows > 0): $n = 1; ?>
                                <?php while($r = $denda_all->fetch_assoc()): ?>
                                <tr>
                                    <td class="num"><?= $n++ ?></td>
                                    <td class="name"><?= htmlspecialchars($r['nama_anggota']) ?></td>
                                    <td class="book"><?= htmlspecialchars($r['judul_buku']) ?></td>
                                    <td><?= $r['jumlah_hari'] ?> hari</td>
                                    <td class="money-cell">Rp <?= number_format($r['total_denda'],0,',','.') ?></td>
                                    <td>
                                        <?php if ($r['status_bayar'] === 'sudah'): ?>
                                        <span class="rpt-badge lunas"><i class="fas fa-check"></i> Lunas</span>
                                        <?php else: ?>
                                        <span class="rpt-badge belum"><i class="fas fa-times"></i> Belum</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="rpt-empty">Belum ada data denda</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer / Signature -->
                    <div class="rpt-footer">
                        <div class="rpt-footer-left">
                            <i class="fas fa-file-alt" style="color: var(--primary-500);"></i>
                            Dokumen ini digenerate otomatis oleh sistem.<br>
                            No. Dokumen: <strong><?= $no_laporan ?></strong> &nbsp;|&nbsp; <?= $tgl_cetak ?>,
                            <?= $jam_cetak ?>
                        </div>
                        <div class="rpt-signature">
                            <div class="rpt-signature-line"></div>
                            <div>Administrator</div>
                            <div class="rpt-signature-title">Penanggung Jawab</div>
                        </div>
                    </div>
                </div><!-- /report-document -->
            </main>
        </div>
    </div>

    <script>
    // Prevent form resubmission
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>