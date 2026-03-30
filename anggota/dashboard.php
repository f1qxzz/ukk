<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn = getConnection();
$id = getAnggotaId();

// Ambil data anggota untuk foto profil dan informasi
$anggotaStmt = $conn->prepare("SELECT foto, nama_anggota, nis, kelas, email FROM anggota WHERE id_anggota = ?");
$anggotaStmt->bind_param("i", $id);
$anggotaStmt->execute();
$anggotaData = $anggotaStmt->get_result()->fetch_assoc();
$anggotaStmt->close();

// Inisial untuk fallback avatar
$initials = '';
foreach (explode(' ', trim($anggotaData['nama_anggota'] ?? '')) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}

// Ambil data user untuk header
$userStmt = $conn->prepare("SELECT foto, nama_anggota FROM anggota WHERE id_anggota = ?");
$userStmt->bind_param("i", $id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$fotoPath = (!empty($userData['foto']) && file_exists('../' . $userData['foto'])) 
            ? '../' . htmlspecialchars($userData['foto']) 
            : null;

function cnt($c, $q, $f = 'c') {
    return $c->query($q)->fetch_assoc()[$f] ?? 0;
}

$ak = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id AND status_transaksi IN ('Pending','Dipinjam')");
$tt = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id");
$dn = cnt($conn, "SELECT COALESCE(SUM(d.total_denda),0) s FROM denda d JOIN transaksi t ON d.id_transaksi=t.id_transaksi WHERE t.id_anggota=$id AND d.status_bayar='belum'", 's');
$ul = cnt($conn, "SELECT COUNT(*) c FROM ulasan_buku WHERE id_anggota=$id");
$rows = $conn->query("SELECT t.*, b.judul_buku, b.pengarang, b.cover, b.id_buku 
                      FROM transaksi t 
                      JOIN buku b ON t.id_buku = b.id_buku 
                      WHERE t.id_anggota = $id AND t.status_transaksi IN ('Pending','Dipinjam') 
                      ORDER BY t.tgl_pinjam DESC");

$page_title = 'Dashboard';
$page_sub = 'Portal Anggota · Perpustakaan Digital';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Anggota — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/anggota/dashboard.css?v=<?= @filemtime('../assets/css/anggota/dashboard.css')?:time() ?>">
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
                <a href="dashboard.php" class="nav-link active">
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
                <a href="riwayat.php" class="nav-link">
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
                        <div class="wb-name">Selamat Datang, <?= htmlspecialchars(getAnggotaName()) ?> 👋</div>
                        <div class="wb-sub">
                            <i class="fas fa-id-card"></i> NIS: <?= htmlspecialchars($_SESSION['anggota_nis'] ?? '-') ?>
                            &nbsp;|&nbsp;
                            <i class="fas fa-users"></i> Kelas:
                            <?= htmlspecialchars($_SESSION['anggota_kelas'] ?? '-') ?>
                        </div>
                    </div>
                    <div class="wb-actions">
                        <a href="pinjam.php" class="wb-btn1"><i class="fas fa-plus-circle"></i> Pinjam Buku</a>
                        <a href="katalog.php" class="wb-btn2"><i class="fas fa-search"></i> Lihat Katalog</a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="srow">
                    <div class="sc">
                        <div>
                            <div class="sc-l">Aktif / Pending</div>
                            <div class="sc-v"><?= $ak ?></div>
                            <div class="sc-s">buku aktif</div>
                        </div>
                        <div class="sc-i"><i class="fas fa-book-open"></i></div>
                    </div>
                    <div class="sc">
                        <div>
                            <div class="sc-l">Total Pinjaman</div>
                            <div class="sc-v"><?= $tt ?></div>
                            <div class="sc-s">sepanjang masa</div>
                        </div>
                        <div class="sc-i"><i class="fas fa-history"></i></div>
                    </div>
                    <div class="sc">
                        <div>
                            <div class="sc-l">Denda Belum Bayar</div>
                            <div class="sc-v" style="font-size:<?= $dn > 99999 ? '1.15rem' : '1.9rem' ?>">Rp
                                <?= number_format($dn, 0, ',', '.') ?></div>
                            <div class="sc-s <?= $dn > 0 ? 'bad' : 'ok' ?>">
                                <?= $dn > 0 ? '<i class="fas fa-exclamation-circle"></i> Segera bayar' : '<i class="fas fa-check-circle"></i> Tidak ada denda' ?>
                            </div>
                        </div>
                        <div class="sc-i"><i class="fas fa-coins"></i></div>
                    </div>
                    <div class="sc">
                        <div>
                            <div class="sc-l">Ulasan Ditulis</div>
                            <div class="sc-v"><?= $ul ?></div>
                            <div class="sc-s"><i class="fas fa-star"></i> ulasan buku</div>
                        </div>
                        <div class="sc-i"><i class="fas fa-star"></i></div>
                    </div>
                </div>

                <!-- Quick Menu & Recent Transactions -->
                <div class="tcols">
                    <!-- Quick Menu -->
                    <div class="qm">
                        <div class="qm-h"><i class="fas fa-bolt"></i> Menu Cepat</div>
                        <div class="qm-grid">
                            <a href="pinjam.php" class="qm-btn">
                                <i class="fas fa-plus-circle"></i>
                                <span>Pinjam Buku</span>
                            </a>
                            <a href="kembali.php" class="qm-btn">
                                <i class="fas fa-undo-alt"></i>
                                <span>Kembalikan</span>
                            </a>
                            <a href="katalog.php" class="qm-btn">
                                <i class="fas fa-search"></i>
                                <span>Katalog</span>
                            </a>
                            <a href="riwayat.php" class="qm-btn">
                                <i class="fas fa-history"></i>
                                <span>Riwayat</span>
                            </a>
                            <a href="ulasan.php" class="qm-btn">
                                <i class="fas fa-star"></i>
                                <span>Ulasan</span>
                            </a>
                            <a href="profil.php" class="qm-btn">
                                <i class="fas fa-user"></i>
                                <span>Profil</span>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="dc">
                        <div class="dc-h">
                            <div class="dc-t">
                                <i class="fas fa-book-open"></i> Buku Dipinjam &amp; Menunggu Persetujuan
                            </div>
                            <a href="kembali.php" class="dc-a">Lihat Detail <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cover</th>
                                        <th>Judul Buku</th>
                                        <th>Pengarang</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Status</th>
                                        <th>Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($rows && $rows->num_rows > 0): while ($r = $rows->fetch_assoc()):
                                        $due = strtotime($r['tgl_kembali_rencana']);
                                        $sisa = (int)ceil(($due - time()) / 86400);
                                        $isPending = $r['status_transaksi'] === 'Pending';
                                        if ($isPending) {
                                            $sc = 'sl-w';
                                            $icon = 'fa-hourglass-half';
                                            $st = 'Menunggu persetujuan';
                                        } elseif ($sisa < 0) {
                                            $sc = 'sl-ov';
                                            $icon = 'fa-exclamation-triangle';
                                            $st = 'Terlambat ' . abs($sisa) . ' hari';
                                        } elseif ($sisa <= 2) {
                                            $sc = 'sl-w';
                                            $icon = 'fa-clock';
                                            $st = $sisa . ' hari lagi';
                                        } else {
                                            $sc = 'sl-ok';
                                            $icon = 'fa-check-circle';
                                            $st = $sisa . ' hari lagi';
                                        }
                                    ?>
                                    <tr>
                                        <td class="book-cover-cell">
                                            <?php if (!empty($r['cover']) && file_exists('../' . $r['cover'])): ?>
                                            <img class="cv" src="../<?= htmlspecialchars($r['cover']) ?>" alt="">
                                            <?php else: ?>
                                            <div class="cv-ph"><i class="fas fa-book"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span
                                                class="fw"><?= htmlspecialchars(mb_strimwidth($r['judul_buku'], 0, 34, '…')) ?></span>
                                        </td>
                                        <td class="text-sm"><?= htmlspecialchars($r['pengarang']) ?></td>
                                        <td><?= date('d M Y', strtotime($r['tgl_pinjam'])) ?></td>
                                        <td><?= $isPending ? '<span class="text-muted">—</span>' : date('d M Y', $due) ?></td>
                                        <td>
                                            <?php if ($isPending): ?>
                                            <span class="badge badge-warning" style="font-size:0.78em;">
                                                <i class="fas fa-hourglass-half"></i> Pending
                                            </span>
                                            <?php else: ?>
                                            <span class="badge" style="background:rgba(59,130,246,0.15);color:#2563eb;font-size:0.78em;">
                                                <i class="fas fa-book-open"></i> Dipinjam
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="sl <?= $sc ?>"><i class="fas <?= $icon ?>"></i>
                                                <?= $st ?></span></td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="7"
                                            style="text-align:center; padding:48px; color:var(--neutral-500);">
                                            <i class="fas fa-smile"
                                                style="font-size: 3rem; color: var(--soft-purple-light); margin-bottom: 12px;"></i>
                                            <br>📗 Belum ada pinjaman aktif &nbsp;·&nbsp;
                                            <a href="katalog.php"
                                                style="color:var(--soft-purple); font-weight:600;">Cari buku <i
                                                    class="fas fa-arrow-right"></i></a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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