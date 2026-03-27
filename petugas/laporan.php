<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();

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
$total_terlambat = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < CURDATE()")->fetch_assoc()['c'];

$trans_all = $conn->query("SELECT t.*, a.nama_anggota, a.nis, a.kelas, b.judul_buku, b.cover 
                           FROM transaksi t 
                           JOIN anggota a ON t.id_anggota = a.id_anggota 
                           JOIN buku b ON t.id_buku = b.id_buku 
                           ORDER BY t.tgl_pinjam DESC");

$page_title = 'Laporan';
$page_sub   = 'Ringkasan data sistem perpustakaan';
$no_laporan = 'PTG-' . date('Ymd') . '-001';
$tgl_cetak  = date('d F Y');
$jam_cetak  = date('H:i') . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan — Petugas Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/petugas_laporan.css">
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>

        <div class="main-area">
            <?php include 'includes/header.php'; ?>

            <main class="content">
                <!-- Page Header -->
                <div class="page-header no-print">
                    <div>
                        <h1 class="page-header-title">Laporan Sistem</h1>
                        <p class="page-header-sub">Ringkasan data sirkulasi perpustakaan</p>
                    </div>
                </div>

                <!-- Stats Cards - Screen only -->
                <div class="stats-grid no-print">
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
                            <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                        <div class="stat-label">Terlambat</div>
                        <div class="stat-value"><?= number_format($total_terlambat) ?></div>
                        <div class="stat-sub">Melewati jatuh tempo</div>
                    </div>
                </div>

                <!-- Print control bar (screen only) -->
                <div class="print-bar-wrap no-print">
                    <div class="print-bar-label">
                        <i class="fas fa-file-pdf"></i>
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
                            <div><strong>Dicetak oleh</strong><br>Petugas</div>
                        </div>
                    </div>

                    <!-- Title band -->
                    <div class="rpt-title-band">
                        <div class="rpt-title">Laporan Sirkulasi Perpustakaan</div>
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
                            <div class="rpt-stat-label">Terlambat</div>
                            <div class="rpt-stat-val"><?= number_format($total_terlambat) ?></div>
                            <div class="rpt-stat-sub">melewati jatuh tempo</div>
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
                                        $sl = 'Kembali'; 
                                    } elseif ($late) { 
                                        $sc = 'terlambat'; 
                                        $sl = 'Terlambat'; 
                                    } else { 
                                        $sc = 'dipinjam';  
                                        $sl = 'Dipinjam'; 
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

                    <!-- Section 2: Peminjaman Aktif & Terlambat -->
                    <div class="rpt-section">
                        <div class="rpt-section-head">
                            <div class="rpt-section-num">2</div>
                            <div class="rpt-section-title">Peminjaman Aktif &amp; Terlambat</div>
                            <div class="rpt-section-count"><?= $aktif_pinjam ?> aktif</div>
                        </div>
                        <?php
                        $aktif_all = $conn->query("SELECT t.*, a.nama_anggota, a.nis, a.kelas, b.judul_buku, b.cover 
                                                   FROM transaksi t 
                                                   JOIN anggota a ON t.id_anggota = a.id_anggota 
                                                   JOIN buku b ON t.id_buku = b.id_buku 
                                                   WHERE t.status_transaksi = 'Peminjaman' 
                                                   ORDER BY t.tgl_kembali_rencana ASC");
                        ?>
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
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($aktif_all && $aktif_all->num_rows > 0): $n = 1; ?>
                                <?php while($r = $aktif_all->fetch_assoc()):
                                    $late = strtotime($r['tgl_kembali_rencana']) < time();
                                    $sc = $late ? 'terlambat' : 'dipinjam';
                                    $sl = $late ? 'Terlambat' : 'Dipinjam';
                                ?>
                                <tr>
                                    <td class="num"><?= $n++ ?></td>
                                    <td class="name"><?= htmlspecialchars($r['nama_anggota']) ?></td>
                                    <td class="mono"><?= htmlspecialchars($r['nis']) ?></td>
                                    <td><?= htmlspecialchars($r['kelas']) ?></td>
                                    <td class="book"><?= htmlspecialchars($r['judul_buku']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_kembali_rencana'])) ?></td>
                                    <td><span class="rpt-badge <?= $sc ?>"><?= $sl ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" class="rpt-empty">Tidak ada peminjaman aktif saat ini</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer / Signature -->
                    <div class="rpt-footer">
                        <div class="rpt-footer-left">
                            <i class="fas fa-file-alt"></i>
                            Dokumen ini digenerate otomatis oleh sistem.<br>
                            No. Dokumen: <strong><?= $no_laporan ?></strong> &nbsp;|&nbsp; <?= $tgl_cetak ?>,
                            <?= $jam_cetak ?>
                        </div>
                        <div class="rpt-signature">
                            <div class="rpt-signature-line"></div>
                            <div>Petugas Perpustakaan</div>
                            <div class="rpt-signature-title">Petugas yang Bertugas</div>
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

    // Print preview helper
    document.querySelector('.btn-primary')?.addEventListener('click', function() {
        // You can add any pre-print logic here if needed
        console.log('Printing report...');
    });
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>