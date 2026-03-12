<?php
/**
 * ============================================================
 *  anggota/katalog.php  —  Katalog Buku dengan Tampilan Cover
 * ============================================================
 */
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/upload_helper.php';
requireAnggota();
$conn = getConnection();

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

$search = $_GET['search'] ?? '';
$kat    = isset($_GET['kat']) ? (int)$_GET['kat'] : 0;
$cats   = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");

$q = "SELECT b.*, k.nama_kategori
      FROM buku b
      LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
      WHERE 1=1";
if ($search) {
    $search = $conn->real_escape_string($search);
    $q .= " AND (b.judul_buku LIKE '%$search%' OR b.pengarang LIKE '%$search%')";
}
if ($kat)    $q .= " AND b.id_kategori = $kat";
$q .= " ORDER BY b.judul_buku";
$books = $conn->query($q);
$book_emojis = ['📗','📘','📕','📙','📓','📔','📒'];

$page_title = 'Katalog Buku';
$page_sub   = 'Jelajahi koleksi perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/anggota_katalog.css">
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
                <a href="katalog.php" class="nav-link active">
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
                <div class="page-header">
                    <div>
                        <h1 class="page-header-title">Katalog Buku</h1>
                        <p class="page-header-sub">Temukan buku yang ingin kamu baca</p>
                    </div>
                    <a href="pinjam.php" class="btn-sage">
                        <i class="fas fa-plus-circle"></i>
                        Pinjam Buku
                    </a>
                </div>

                <!-- Filter -->
                <form method="GET" class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Cari judul buku atau pengarang…"
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <select name="kat" class="form-control">
                        <option value="">Semua Kategori</option>
                        <?php 
                        $cats->data_seek(0);
                        while($c=$cats->fetch_assoc()): 
                        ?>
                        <option value="<?= $c['id_kategori'] ?>" <?= $kat==$c['id_kategori']?'selected':'' ?>>
                            <?= htmlspecialchars($c['nama_kategori']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn-sage">Cari</button>
                    <?php if ($search||$kat): ?>
                    <a href="katalog.php" class="btn-ghost">Reset</a>
                    <?php endif; ?>
                </form>

                <?php if ($books && $books->num_rows > 0): ?>
                <div class="book-grid">
                    <?php $i = 0; while($b = $books->fetch_assoc()): $i++; ?>
                    <div class="book-card">
                        <div class="book-cover">
                            <?php if (!empty($b['cover'])): ?>
                            <img src="../<?= htmlspecialchars($b['cover']) ?>"
                                alt="<?= htmlspecialchars($b['judul_buku']) ?>" class="book-cover-img">
                            <?php else: ?>
                            <div class="book-cover-inner">
                                <?= $book_emojis[$i % count($book_emojis)] ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="book-info">
                            <div class="book-title"><?= htmlspecialchars($b['judul_buku']) ?></div>
                            <div class="book-author"><?= htmlspecialchars($b['pengarang']) ?></div>
                            <?php if ($b['nama_kategori']): ?>
                            <span class="badge badge-muted">
                                <i class="fas fa-tag"></i>
                                <?= htmlspecialchars($b['nama_kategori']) ?>
                            </span>
                            <?php endif; ?>
                            <div class="book-footer">
                                <span
                                    class="badge <?= $b['status']==='tersedia' ? 'status-tersedia' : 'status-terlambat' ?>">
                                    <i
                                        class="fas <?= $b['status']==='tersedia' ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                    <?= $b['status']==='tersedia' ? 'Tersedia' : 'Habis' ?>
                                </span>
                                <?php if ($b['status']==='tersedia'): ?>
                                <a href="pinjam.php?buku=<?= $b['id_buku'] ?>" class="btn-sage btn-sm">
                                    <i class="fas fa-book"></i> Pinjam
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-ico">🔍</div>
                        <div class="empty-state-title">Buku tidak ditemukan</div>
                        <div class="empty-state-sub">Coba kata kunci yang berbeda atau lihat semua kategori.</div>
                    </div>
                </div>
                <?php endif; ?>

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