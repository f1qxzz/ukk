<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn = getConnection();
$id   = getAnggotaId();

$aktif = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id AND status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$total = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id")->fetch_assoc()['c'];
$denda = $conn->query("SELECT COALESCE(SUM(d.total_denda),0) s FROM denda d JOIN transaksi t ON d.id_transaksi=t.id_transaksi WHERE t.id_anggota=$id AND d.status_bayar='belum'")->fetch_assoc()['s'];
$ulasan_count = $conn->query("SELECT COUNT(*) c FROM ulasan_buku WHERE id_anggota=$id")->fetch_assoc()['c'];

$pinjam_aktif = $conn->query("
    SELECT t.*,b.judul_buku,b.pengarang 
    FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku 
    WHERE t.id_anggota=$id AND t.status_transaksi='Peminjaman' 
    ORDER BY t.tgl_pinjam DESC
");

$page_title = 'Dashboard'; 
$page_sub   = 'Portal Anggota · Perpustakaan Digital';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Anggota — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>
        <div class="main-area">
            <?php include 'includes/header.php'; ?>
            <main class="content">

                <!-- Welcome -->
                <div class="welcome-banner" style="background:linear-gradient(135deg,var(--sage) 0%,#2e4028 100%)">
                    <div>
                        <div class="welcome-title">Selamat Datang, <?= htmlspecialchars(getAnggotaName()) ?> 🎓</div>
                        <div class="welcome-sub">
                            NIS: <?= htmlspecialchars($_SESSION['anggota_nis']) ?> &nbsp;·&nbsp;
                            Kelas: <?= htmlspecialchars($_SESSION['anggota_kelas']) ?>
                        </div>
                    </div>
                    <div class="welcome-actions">
                        <a href="pinjam.php" class="btn-welcome">Pinjam Buku</a>
                        <a href="katalog.php" class="btn-welcome-ghost">Lihat Katalog</a>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid mb-24">
                    <div class="stat-card sc-navy">
                        <div class="stat-info">
                            <div class="stat-label">Sedang Dipinjam</div>
                            <div class="stat-val"><?= $aktif ?></div>
                            <div class="stat-sub">buku aktif</div>
                        </div>
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card sc-sage">
                        <div class="stat-info">
                            <div class="stat-label">Total Pinjaman</div>
                            <div class="stat-val"><?= $total ?></div>
                            <div class="stat-sub">sepanjang masa</div>
                        </div>
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24">
                                <polyline points="12 8 12 12 14 14" />
                                <path d="M3.05 11a9 9 0 1 0 .5-4" />
                                <polyline points="3 3 3 7 7 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card <?= $denda>0?'sc-rust':'sc-sage' ?>">
                        <div class="stat-info">
                            <div class="stat-label">Denda Belum Bayar</div>
                            <div class="stat-val" style="font-size:<?= $denda>99999?'1.1rem':'1.5rem' ?>">
                                Rp <?= number_format($denda,0,',','.') ?>
                            </div>
                            <div class="stat-sub"><?= $denda>0?'segera bayar ke petugas':'tidak ada denda 🎉' ?></div>
                        </div>
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card sc-gold">
                        <div class="stat-info">
                            <div class="stat-label">Ulasan Ditulis</div>
                            <div class="stat-val"><?= $ulasan_count ?></div>
                            <div class="stat-sub">ulasan buku</div>
                        </div>
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24">
                                <polygon
                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions + Active Loans -->
                <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:start">

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Aksi Cepat</div>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="pinjam.php" class="qa-btn">
                                    <svg viewBox="0 0 24 24">
                                        <polyline points="17 1 21 5 17 9" />
                                        <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                                    </svg>
                                    <span>Pinjam Buku</span>
                                </a>
                                <a href="kembali.php" class="qa-btn">
                                    <svg viewBox="0 0 24 24">
                                        <polyline points="7 23 3 19 7 15" />
                                        <path d="M21 13v2a4 4 0 0 1-4 4H3" />
                                    </svg>
                                    <span>Kembalikan</span>
                                </a>
                                <a href="katalog.php" class="qa-btn">
                                    <svg viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8" />
                                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                    </svg>
                                    <span>Cari Buku</span>
                                </a>
                                <a href="riwayat.php" class="qa-btn">
                                    <svg viewBox="0 0 24 24">
                                        <polyline points="12 8 12 12 14 14" />
                                        <path d="M3.05 11a9 9 0 1 0 .5-4" />
                                    </svg>
                                    <span>Riwayat</span>
                                </a>
                                <a href="ulasan.php" class="qa-btn">
                                    <svg viewBox="0 0 24 24">
                                        <polygon
                                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                    </svg>
                                    <span>Ulasan</span>
                                </a>
                                <a href="profil.php" class="qa-btn">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    <span>Profil</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Active Loans -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <svg viewBox="0 0 24 24"
                                    style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8">
                                    <polyline points="17 1 21 5 17 9" />
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                                </svg>
                                Buku Sedang Dipinjam
                            </div>
                            <a href="kembali.php" class="btn btn-sage btn-sm">Kembalikan</a>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Judul Buku</th>
                                        <th>Pengarang</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($pinjam_aktif && $pinjam_aktif->num_rows > 0): ?>
                                    <?php while($r=$pinjam_aktif->fetch_assoc()): ?>
                                    <?php $late = strtotime($r['tgl_kembali_rencana']) < time(); ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($r['judul_buku']) ?></strong></td>
                                        <td class="text-muted text-sm"><?= htmlspecialchars($r['pengarang']) ?></td>
                                        <td><?= date('d/m/Y',strtotime($r['tgl_pinjam'])) ?></td>
                                        <td><?= date('d/m/Y',strtotime($r['tgl_kembali_rencana'])) ?></td>
                                        <td>.
                                            <span class="badge <?= $late?'status-terlambat':'status-dipinjam' ?>">
                                                <?= $late?'⚠ Terlambat':'⇄ Dipinjam' ?>
                                            </span>

                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <div class="empty-state-ico">📗</div>
                                                <div class="empty-state-title">Tidak ada pinjaman aktif</div>
                                                <div class="empty-state-sub">Cari dan pinjam buku dari katalog.</div>
                                            </div>
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
</body>

</html>