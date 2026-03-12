<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();

// Definisikan konstanta DENDA_PER_HARI jika belum ada
if (!defined('DENDA_PER_HARI')) {
    define('DENDA_PER_HARI', 1000);
}

$conn = getConnection();
$id = getAnggotaId();
$msg = ''; $msgType = '';

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

if (isset($_POST['kembalikan'])) {
    $id_trans = (int)$_POST['id_transaksi'];
    // Validasi milik anggota ini
    $chk = $conn->query("SELECT * FROM transaksi WHERE id_transaksi=$id_trans AND id_anggota=$id AND status_transaksi='Peminjaman'")->fetch_assoc();
    if (!$chk) { 
        $msg = 'Transaksi tidak valid!'; 
        $msgType = 'danger'; 
    } else {
        $now = date('Y-m-d H:i:s');
        $conn->query("UPDATE transaksi SET status_transaksi='Pengembalian', tgl_kembali_aktual='$now' WHERE id_transaksi=$id_trans");
        $conn->query("UPDATE buku SET stok = stok + 1, status = 'tersedia' WHERE id_buku={$chk['id_buku']}");
        // Hitung denda
        if (strtotime($now) > strtotime($chk['tgl_kembali_rencana'])) {
            $hari = max(1, floor((time() - strtotime($chk['tgl_kembali_rencana'])) / 86400));
            $total = $hari * DENDA_PER_HARI;
            $conn->query("INSERT IGNORE INTO denda(id_transaksi, jumlah_hari, tarif_per_hari, total_denda) VALUES($id_trans, $hari, " . DENDA_PER_HARI . ", $total)");
            $msg = "Pengembalian dicatat. Anda terlambat $hari hari. Denda: Rp " . number_format($total, 0, ',', '.') . ". Harap bayar ke petugas.";
            $msgType = 'warning';
        } else { 
            $msg = 'Pengembalian berhasil! Terima kasih telah membaca.'; 
            $msgType = 'success'; 
        }
    }
}

$aktif = $conn->query("SELECT t.*, b.judul_buku, b.pengarang, b.cover 
                       FROM transaksi t 
                       JOIN buku b ON t.id_buku = b.id_buku 
                       WHERE t.id_anggota = $id AND t.status_transaksi = 'Peminjaman' 
                       ORDER BY t.tgl_kembali_rencana");

$page_title = 'Kembalikan Buku';
$page_sub   = 'Kembalikan buku yang sudah selesai dibaca';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kembalikan Buku — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/anggota_kembali.css">
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
                <a href="kembali.php" class="nav-link active">
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
                <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType ?>">
                    <i
                        class="fas <?= $msgType === 'success' ? 'fa-check-circle' : ($msgType === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle') ?>"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endif; ?>

                <div class="page-header">
                    <div>
                        <h1 class="page-header-title">Kembalikan Buku</h1>
                        <p class="page-header-sub">Kembalikan buku yang sudah selesai Anda baca</p>
                    </div>
                    <a href="pinjam.php" class="btn-success">
                        <i class="fas fa-plus-circle"></i> Pinjam Buku
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-undo-alt"></i> Daftar Buku yang Sedang Dipinjam</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Buku</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Sisa Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($aktif && $aktif->num_rows > 0): while($r = $aktif->fetch_assoc()):
                                    $sisa = floor((strtotime($r['tgl_kembali_rencana']) - time()) / 86400);
                                    $late = $sisa < 0;
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
                                        <?php if ($late):
                                            $d = abs($sisa);
                                            $denda_est = $d * DENDA_PER_HARI;
                                        ?>
                                        <span class="badge badge-danger">
                                            <i class="fas fa-exclamation-triangle"></i> Terlambat <?= $d ?> hari
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-coins"></i> Denda: Rp
                                            <?= number_format($denda_est, 0, ',', '.') ?>
                                        </small>
                                        <?php elseif ($sisa <= 2): ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Segera (<?= $sisa ?> hari)
                                        </span>
                                        <?php else: ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> <?= $sisa ?> hari
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST"
                                            onsubmit="return confirm('Yakin ingin mengembalikan buku &quot;<?= htmlspecialchars(addslashes($r['judul_buku'])) ?>&quot;?')">
                                            <input type="hidden" name="id_transaksi" value="<?= $r['id_transaksi'] ?>">
                                            <button type="submit" name="kembalikan" class="btn-success btn-sm">
                                                <i class="fas fa-undo-alt"></i> Kembalikan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-ico">📚</div>
                                            <div class="empty-state-title">Tidak ada buku yang perlu dikembalikan</div>
                                            <div class="empty-state-sub">
                                                Anda belum meminjam buku saat ini.
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

                <!-- Informasi Denda -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2><i class="fas fa-info-circle"></i> Informasi Denda</h2>
                    </div>
                    <div style="padding: 20px;">
                        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                            <div style="flex: 1;">
                                <p style="color: var(--neutral-600); margin-bottom: 8px;">
                                    <i class="fas fa-clock" style="color: var(--soft-purple); margin-right: 8px;"></i>
                                    Durasi peminjaman: <strong>7 hari</strong>
                                </p>
                                <p style="color: var(--neutral-600);">
                                    <i class="fas fa-coins" style="color: var(--soft-purple); margin-right: 8px;"></i>
                                    Denda keterlambatan: <strong>Rp <?= number_format(DENDA_PER_HARI, 0, ',', '.') ?> /
                                        hari</strong>
                                </p>
                            </div>
                            <div
                                style="background: rgba(155, 140, 156, 0.1); padding: 12px 20px; border-radius: var(--radius-lg);">
                                <p style="color: var(--neutral-600); margin: 0;">
                                    <i class="fas fa-lightbulb"
                                        style="color: var(--soft-purple); margin-right: 8px;"></i>
                                    Kembalikan buku tepat waktu untuk menghindari denda.
                                </p>
                            </div>
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