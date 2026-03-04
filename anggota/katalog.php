<?php
/**
 * ============================================================
 *  anggota/katalog.php  —  Katalog Buku dengan Tampilan Cover
 * ============================================================
 */
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/upload_helper.php'; // ← untuk bookCoverImg()
requireAnggota();
$conn = getConnection();

$search = $_GET['search'] ?? '';
$kat    = isset($_GET['kat']) ? (int)$_GET['kat'] : 0;
$cats   = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");

$q = "SELECT b.*, k.nama_kategori
      FROM buku b
      LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
      WHERE 1=1";
if ($search) $q .= " AND (b.judul_buku LIKE '%$search%' OR b.pengarang LIKE '%$search%')";
if ($kat)    $q .= " AND b.id_kategori = $kat";
$q .= " ORDER BY b.judul_buku";
$books = $conn->query($q);
$book_emojis = ['📗','📘','📕','📙','📓','📔','📒']; // fallback jika tidak ada cover

$page_title = 'Katalog Buku';
$page_sub   = 'Jelajahi koleksi perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Katalog — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>
        <div class="main-area">
            <?php include 'includes/header.php'; ?>
            <main class="content">

                <div class="page-header">
                    <div>
                        <div class="page-header-title">Katalog Buku</div>
                        <div class="page-header-sub">Temukan buku yang ingin kamu baca</div>
                    </div>
                    <a href="pinjam.php" class="btn btn-sage">
                        <svg viewBox="0 0 24 24">
                            <polyline points="17 1 21 5 17 9" />
                            <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                        </svg>
                        Pinjam Buku
                    </a>
                </div>

                <!-- Filter -->
                <form method="GET" class="card mb-24" style="overflow:visible">
                    <div class="filter-bar" style="border:none">
                        <div class="search-wrap" style="flex:1">
                            <svg viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                            <input type="text" name="search" placeholder="Cari judul buku atau pengarang…"
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <select name="kat" class="form-control" style="width:auto">
                            <option value="">Semua Kategori</option>
                            <?php while($c=$cats->fetch_assoc()): ?>
                            <option value="<?= $c['id_kategori'] ?>" <?= $kat==$c['id_kategori']?'selected':'' ?>>
                                <?= htmlspecialchars($c['nama_kategori']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn btn-sage">Cari</button>
                        <?php if ($search||$kat): ?>
                        <a href="katalog.php" class="btn btn-ghost">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($books && $books->num_rows > 0): ?>
                <div class="book-grid">
                    <?php $i = 0; while($b = $books->fetch_assoc()): $i++; ?>
                    <div class="book-card">

                        <!--
              ══════════════════════════════════════════════════
              TAMPILKAN COVER:
              - Jika kolom `cover` tidak NULL dan file ada → <img src="cover">
              - Jika NULL atau file hilang            → <img src="default.jpg">
              Logika ada di fungsi bookCoverImg() di upload_helper.php
              ══════════════════════════════════════════════════
            -->
                        <?php
              // Pakai warna background hanya jika cover NULL (sebagai fallback)
              $bgColors  = ['#f0e8d6','#e8e0d0','#ede4d2','#f4ede0','#e0d8c8'];
              $bgStyle   = empty($b['cover']) ? 'background:' . $bgColors[$i % 5] : 'background:#f8f5f0';
            ?>
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
                            <span class="badge badge-muted"
                                style="font-size:.62rem"><?= htmlspecialchars($b['nama_kategori']) ?></span>
                            <?php endif; ?>
                            <div class="book-footer">
                                <span
                                    class="badge <?= $b['status']==='tersedia'?'status-tersedia':'status-terlambat' ?>">
                                    <?= $b['status']==='tersedia' ? '● Tersedia' : '○ Habis' ?>
                                </span>
                                <?php if ($b['status']==='tersedia'): ?>
                                <a href="pinjam.php?buku=<?= $b['id_buku'] ?>" class="btn btn-sage btn-sm">Pinjam</a>
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
<script src="../assets/js/script.js"></script>
</body>

</html>