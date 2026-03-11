<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn = getConnection();
$id = getAnggotaId();

// Ambil data anggota untuk foto profil
$anggotaStmt = $conn->prepare("SELECT foto, nama_anggota, nis, kelas FROM anggota WHERE id_anggota = ?");
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

function cnt($c, $q, $f = 'c') {
    return $c->query($q)->fetch_assoc()[$f] ?? 0;
}

$ak = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id AND status_transaksi='Peminjaman'");
$tt = cnt($conn, "SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id");
$dn = cnt($conn, "SELECT COALESCE(SUM(d.total_denda),0) s FROM denda d JOIN transaksi t ON d.id_transaksi=t.id_transaksi WHERE t.id_anggota=$id AND d.status_bayar='belum'", 's');
$ul = cnt($conn, "SELECT COUNT(*) c FROM ulasan_buku WHERE id_anggota=$id");
$rows = $conn->query("SELECT t.*,b.judul_buku,b.pengarang,b.cover FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_anggota=$id AND t.status_transaksi='Peminjaman' ORDER BY t.tgl_pinjam DESC");

$page_title = 'Dashboard';
$page_sub = 'Portal Anggota · Perpustakaan Digital';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Anggota — Perpustakaan Digital</title>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    /* Style untuk avatar di welcome box */
    .wb-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2c4f7c 0%, #3a6ea5 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.4rem;
        box-shadow: 0 4px 12px rgba(44, 79, 124, 0.3);
        overflow: hidden;
        flex-shrink: 0;
        margin-right: 8px;
    }

    .wb-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .wb-avatar.initials {
        font-family: 'Playfair Display', serif;
    }

    /* Menyesuaikan layout wb-anggota */
    .wb-anggota {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    </style>
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>
        <div class="main-area">
            <?php include 'includes/header.php'; ?>
            <main class="content">

                <div class="wb wb-anggota">
                    <!-- Ganti wb-emoji dengan avatar -->
                    <?php 
                    $fotoPath = (!empty($anggotaData['foto']) && file_exists('../' . $anggotaData['foto'])) 
                                ? '../' . htmlspecialchars($anggotaData['foto']) 
                                : null;
                    ?>
                    <div class="wb-avatar <?= empty($fotoPath) ? 'initials' : '' ?>">
                        <?php if ($fotoPath): ?>
                        <img src="<?= $fotoPath ?>" alt="Foto Profil">
                        <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="wb-name">Selamat Datang, <?= htmlspecialchars(getAnggotaName()) ?> 🎓</div>
                        <div class="wb-sub">NIS: <?= htmlspecialchars($_SESSION['anggota_nis'] ?? '-') ?> &nbsp;·&nbsp;
                            Kelas: <?= htmlspecialchars($_SESSION['anggota_kelas'] ?? '-') ?></div>
                    </div>
                    <div class="wb-actions">
                        <a href="pinjam.php" class="wb-btn1">📚 Pinjam Buku</a>
                        <a href="katalog.php" class="wb-btn2">Lihat Katalog</a>
                    </div>
                </div>

                <div class="srow">
                    <div class="sc" style="--a:#ef4444;--ab:rgba(239,68,68,.08)">
                        <div>
                            <div class="sc-l">Sedang Dipinjam</div>
                            <div class="sc-v"><?= $ak ?></div>
                            <div class="sc-s">buku aktif</div>
                        </div>
                        <div class="sc-i">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="sc" style="--a:var(--accent);--ab:rgba(44,79,124,.08)">
                        <div>
                            <div class="sc-l">Total Pinjaman</div>
                            <div class="sc-v"><?= $tt ?></div>
                            <div class="sc-s">sepanjang masa</div>
                        </div>
                        <div class="sc-i">
                            <svg viewBox="0 0 24 24">
                                <polyline points="12 8 12 12 14 14" />
                                <path d="M3.05 11a9 9 0 1 0 .5-4" />
                                <polyline points="3 3 3 7 7 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="sc"
                        style="--a:<?= $dn > 0 ? 'var(--rust)' : 'var(--sage)' ?>;--ab:<?= $dn > 0 ? 'rgba(184,74,44,.08)' : 'rgba(73,102,64,.08)' ?>">
                        <div>
                            <div class="sc-l">Denda Belum Bayar</div>
                            <div class="sc-v" style="font-size:<?= $dn > 99999 ? '1.15rem' : '1.9rem' ?>">Rp
                                <?= number_format($dn, 0, ',', '.') ?></div>
                            <div class="sc-s <?= $dn > 0 ? 'bad' : 'ok' ?>">
                                <?= $dn > 0 ? 'Segera bayar ke petugas' : 'Tidak ada denda 🎉' ?></div>
                        </div>
                        <div class="sc-i">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                    </div>
                    <div class="sc" style="--a:#f59e0b;--ab:rgba(245,158,11,.08)">
                        <div>
                            <div class="sc-l">Ulasan Ditulis</div>
                            <div class="sc-v"><?= $ul ?></div>
                            <div class="sc-s">ulasan buku</div>
                        </div>
                        <div class="sc-i">
                            <svg viewBox="0 0 24 24">
                                <polygon
                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="tcols">
                    <div class="qm">
                        <div class="qm-h">Menu Cepat</div>
                        <div class="qm-grid">
                            <a href="pinjam.php" class="qm-btn">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="17 1 21 5 17 9" />
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                                </svg><span>Pinjam Buku</span>
                            </a>
                            <a href="kembali.php" class="qm-btn">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="7 23 3 19 7 15" />
                                    <path d="M21 13v2a4 4 0 0 1-4 4H3" />
                                </svg><span>Kembalikan</span>
                            </a>
                            <a href="katalog.php" class="qm-btn">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="11" cy="11" r="8" />
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                </svg><span>Katalog</span>
                            </a>
                            <a href="riwayat.php" class="qm-btn">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="12 8 12 12 14 14" />
                                    <path d="M3.05 11a9 9 0 1 0 .5-4" />
                                </svg><span>Riwayat</span>
                            </a>
                            <a href="ulasan.php" class="qm-btn">
                                <svg viewBox="0 0 24 24">
                                    <polygon
                                        points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                </svg><span>Ulasan</span>
                            </a>
                            <a href="profil.php" class="qm-btn">
                                <svg viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg><span>Profil</span>
                            </a>
                        </div>
                    </div>

                    <div class="dc">
                        <div class="dc-h">
                            <div class="dc-t">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="17 1 21 5 17 9" />
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                                </svg>Buku Sedang Dipinjam
                            </div>
                            <a href="kembali.php" class="dc-a">Kembalikan →</a>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="t">
                                <thead>
                                    <tr>
                                        <th>Cover</th>
                                        <th>Judul Buku</th>
                                        <th>Pengarang</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($rows && $rows->num_rows > 0): while ($r = $rows->fetch_assoc()):
                                        $due = strtotime($r['tgl_kembali_rencana']);
                                        $sisa = (int)ceil(($due - time()) / 86400);
                                        if ($sisa < 0) {
                                            $sc = 'sl-ov';
                                            $st = 'Terlambat ' . abs($sisa) . 'h';
                                        } elseif ($sisa <= 2) {
                                            $sc = 'sl-w';
                                            $st = $sisa . ' hari lagi';
                                        } else {
                                            $sc = 'sl-ok';
                                            $st = $sisa . ' hari lagi';
                                        }
                                    ?>
                                    <tr>
                                        <td class="book-cover-cell">
                                            <?php if (!empty($r['cover']) && file_exists('../' . $r['cover'])): ?>
                                            <img class="cv" src="../<?= htmlspecialchars($r['cover']) ?>" alt="">
                                            <?php else: ?>
                                            <div class="cv-ph">📖</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span
                                                class="fw"><?= htmlspecialchars(mb_strimwidth($r['judul_buku'], 0, 34, '…')) ?></span>
                                        </td>
                                        <td class="text-sm"><?= htmlspecialchars($r['pengarang']) ?></td>
                                        <td><?= date('d M Y', strtotime($r['tgl_pinjam'])) ?></td>
                                        <td><?= date('d M Y', $due) ?></td>
                                        <td><span class="sl <?= $sc ?>"><?= $st ?></span></td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;padding:48px;color:var(--muted)">
                                            📗 Belum ada pinjaman aktif &nbsp;·&nbsp;
                                            <a href="katalog.php" style="color:var(--rust);font-weight:600">Cari buku
                                                →</a>
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
    <script src="../assets/js/script.js"></script>
</body>

</html>