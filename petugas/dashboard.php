<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();
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
$ta = cnt($conn, "SELECT COUNT(*) c FROM anggota");
$ap = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'");
$tl = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < NOW()");
$td = cnt($conn, "SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'", 's');
$kh = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Pengembalian' AND DATE(tgl_kembali_aktual) = CURDATE()");

$rows = $conn->query("SELECT t.*, a.nama_anggota, a.nis, b.judul_buku, b.cover 
                      FROM transaksi t 
                      JOIN anggota a ON t.id_anggota = a.id_anggota 
                      JOIN buku b ON t.id_buku = b.id_buku 
                      WHERE t.status_transaksi = 'Peminjaman' 
                      ORDER BY t.tgl_pinjam DESC LIMIT 8");

$page_title = 'Dashboard';
$page_sub = 'Panel Petugas · Perpustakaan Digital';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/petugas_dashboard.css">
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>

        <div class="main-area">
            <?php include 'includes/header.php'; ?>

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
                        <div class="wb-name">Halo, <?= htmlspecialchars(getPenggunaName()) ?> 👋</div>
                        <div class="wb-desc">Kelola peminjaman dan pengembalian buku harian · Panel Petugas</div>
                    </div>
                    <div class="wb-actions">
                        <a href="transaksi.php" class="wb-btn1"><i class="fas fa-plus"></i> Catat Pinjam</a>
                        <a href="laporan.php" class="wb-btn2"><i class="fas fa-print"></i> Cetak Laporan</a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="srow">
                    <div class="sc">
                        <div>
                            <div class="sc-l">Total Buku</div>
                            <div class="sc-v"><?= $tb ?></div>
                            <div class="sc-s ok"><i class="fas fa-check-circle"></i> <?= $ts ?> tersedia</div>
                        </div>
                        <div class="sc-i"><i class="fas fa-book"></i></div>
                    </div>
                    <div class="sc">
                        <div>
                            <div class="sc-l">Aktif Pinjam</div>
                            <div class="sc-v"><?= $ap ?></div>
                            <div class="sc-s <?= $tl > 0 ? 'bad' : '' ?>">
                                <i class="fas <?= $tl > 0 ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                                <?= $tl ?> terlambat
                            </div>
                        </div>
                        <div class="sc-i"><i class="fas fa-exchange-alt"></i></div>
                    </div>
                    <div class="sc">
                        <div>
                            <div class="sc-l">Total Anggota</div>
                            <div class="sc-v"><?= $ta ?></div>
                            <div class="sc-s"><i class="fas fa-user-graduate"></i> terdaftar</div>
                        </div>
                        <div class="sc-i"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="sc">
                        <div>
                            <div class="sc-l">Denda Belum Lunas</div>
                            <div class="sc-v" style="font-size:1.3rem">Rp <?= number_format($td, 0, ',', '.') ?></div>
                            <div class="sc-s bad"><i class="fas fa-clock"></i> perlu diproses</div>
                        </div>
                        <div class="sc-i"><i class="fas fa-coins"></i></div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="dc">
                    <div class="dc-h">
                        <div class="dc-t">
                            <i class="fas fa-clock"></i> Peminjaman Aktif
                        </div>
                        <a href="transaksi.php" class="dc-a">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Anggota</th>
                                    <th>NIS</th>
                                    <th>Buku</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($rows && $rows->num_rows > 0): while($r = $rows->fetch_assoc()): 
                                    $late = strtotime($r['tgl_kembali_rencana']) < time();
                                ?>
                                <tr>
                                    <td class="book-cover-cell">
                                        <?php if (!empty($r['cover']) && file_exists('../'.$r['cover'])): ?>
                                        <img class="cv" src="../<?= htmlspecialchars($r['cover']) ?>" alt="">
                                        <?php else: ?>
                                        <div class="cv-ph"><i class="fas fa-book"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="fw"><?= htmlspecialchars($r['nama_anggota']) ?></span></td>
                                    <td class="text-sm"><?= htmlspecialchars($r['nis']) ?></td>
                                    <td><?= htmlspecialchars(mb_strimwidth($r['judul_buku'], 0, 30, '…')) ?></td>
                                    <td><?= date('d M Y', strtotime($r['tgl_pinjam'])) ?></td>
                                    <td><?= date('d M Y', strtotime($r['tgl_kembali_rencana'])) ?></td>
                                    <td>
                                        <span class="bd <?= $late ? 'bd-r' : 'bd-b' ?>">
                                            <?php if ($late): ?>
                                            <i class="fas fa-exclamation-triangle"></i> Terlambat
                                            <?php else: ?>
                                            <i class="fas fa-book-open"></i> Dipinjam
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:48px; color:var(--neutral-500);">
                                        <i class="fas fa-check-circle"
                                            style="font-size: 3rem; color: #56b386; margin-bottom: 12px;"></i>
                                        <br>✅ Tidak ada pinjaman aktif
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mini Stats -->
                <div class="ms">
                    <div class="ms-c">
                        <div class="ms-ico" style="background:rgba(155,140,156,0.15); color: var(--soft-purple);">📚
                        </div>
                        <div>
                            <div class="ms-v"><?= $tb - $ts ?></div>
                            <div class="ms-l">Buku Di Tangan Anggota</div>
                        </div>
                    </div>
                    <div class="ms-c">
                        <div class="ms-ico" style="background:rgba(86,179,134,0.15); color: #3e8b63;">↩️</div>
                        <div>
                            <div class="ms-v"><?= $kh ?></div>
                            <div class="ms-l">Dikembalikan Hari Ini</div>
                        </div>
                    </div>
                    <div class="ms-c">
                        <div class="ms-ico" style="background:rgba(229,122,143,0.15); color: var(--danger-600);">⚠️
                        </div>
                        <div>
                            <div class="ms-v"><?= $tl ?></div>
                            <div class="ms-l">Keterlambatan Aktif</div>
                        </div>
                    </div>
                </div>
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