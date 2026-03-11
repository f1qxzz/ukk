<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();
$conn=getConnection();

// Ambil data pengguna untuk foto profil
$userId = getPenggunaId();
$userStmt = $conn->prepare("SELECT foto, nama_pengguna FROM pengguna WHERE id_pengguna = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Inisial untuk fallback avatar
$initials = '';
foreach (explode(' ', trim($userData['nama_pengguna'] ?? '')) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}

function cnt($c,$q,$f='c'){return $c->query($q)->fetch_assoc()[$f]??0;}
$tb=cnt($conn,"SELECT COUNT(*) c FROM buku");
$ts=cnt($conn,"SELECT COUNT(*) c FROM buku WHERE status='tersedia'");
$ap=cnt($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'");
$ta=cnt($conn,"SELECT COUNT(*) c FROM anggota");
$td=cnt($conn,"SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'",'s');
$tl=cnt($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana<NOW()");
$tp=cnt($conn,"SELECT COUNT(*) c FROM pengguna");
$kh=cnt($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Pengembalian' AND DATE(tgl_kembali_aktual)=CURDATE()");
$rows=$conn->query("SELECT t.*,a.nama_anggota,b.judul_buku,b.cover FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku ORDER BY t.tgl_pinjam DESC LIMIT 8");
$page_title='Dashboard'; $page_sub='Admin Panel · Perpustakaan Digital';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Admin — Perpustakaan Digital</title>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/table.css">
    <link rel="stylesheet" href="../assets/css/form.css">
    <style>
    /* Style untuk avatar di welcome box */
    .wb-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--navy, #1a2744) 0%, var(--teal, #7c3aed) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.2rem;
        box-shadow: 0 4px 10px rgba(124, 58, 237, 0.3);
        overflow: hidden;
        flex-shrink: 0;
    }

    .wb-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .wb-avatar.initials {
        font-family: 'Playfair Display', serif;
    }
    </style>
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>
        <div class="main-area">
            <?php include 'includes/header.php'; ?>
            <main class="content">

                <div class="wb wb-admin">
                    <!-- Ganti wb-emoji dengan avatar -->
                    <div class="wb-avatar <?= empty($userData['foto']) ? 'initials' : '' ?>">
                        <?php 
        $fotoPath = !empty($userData['foto']) && file_exists('../' . $userData['foto']) 
                    ? '../' . htmlspecialchars($userData['foto']) 
                    : null;
        if ($fotoPath): ?>
                        <img src="<?= $fotoPath ?>" alt="Foto Profil">
                        <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="wb-name">Selamat Datang, <?=htmlspecialchars(getPenggunaName())?></div>
                        <div class="wb-desc">Kelola seluruh sistem perpustakaan dari satu tempat · Panel Admin</div>
                    </div>
                    <div class="wb-actions">
                        <a href="buku.php" class="wb-btn1">+ Tambah Buku</a>
                        <a href="laporan.php" class="wb-btn2">Lihat Laporan</a>
                    </div>
                </div>

                <div class="srow">
                    <div class="sc" style="--a:#ef4444;--ab:rgba(239,68,68,.08)">
                        <div>
                            <div class="sc-l">Total Buku</div>
                            <div class="sc-v"><?=$tb?></div>
                            <div class="sc-s ok">↑ <?=$ts?> tersedia</div>
                        </div>
                        <div class="sc-i"><svg viewBox="0 0 24 24">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                            </svg></div>
                    </div>
                    <div class="sc" style="--a:var(--accent);--ab:rgba(44,79,124,.08)">
                        <div>
                            <div class="sc-l">Aktif Dipinjam</div>
                            <div class="sc-v"><?=$ap?></div>
                            <div class="sc-s <?=$tl>0?'bad':''?>"><?=$tl?> terlambat</div>
                        </div>
                        <div class="sc-i"><svg viewBox="0 0 24 24">
                                <polyline points="17 1 21 5 17 9" />
                                <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                                <polyline points="7 23 3 19 7 15" />
                                <path d="M21 13v2a4 4 0 0 1-4 4H3" />
                            </svg></div>
                    </div>
                    <div class="sc" style="--a:var(--success);--ab:rgba(16,185,129,.08)">
                        <div>
                            <div class="sc-l">Total Anggota</div>
                            <div class="sc-v"><?=$ta?></div>
                            <div class="sc-s"><?=$tp?> pengguna sistem</div>
                        </div>
                        <div class="sc-i"><svg viewBox="0 0 24 24">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg></div>
                    </div>
                    <div class="sc" style="--a:#f59e0b;--ab:rgba(245,158,11,.08)">
                        <div>
                            <div class="sc-l">Denda Belum Bayar</div>
                            <div class="sc-v" style="font-size:1.35rem">Rp <?=number_format($td,0,',','.')?></div>
                            <div class="sc-s bad">perlu diselesaikan</div>
                        </div>
                        <div class="sc-i"><svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg></div>
                    </div>
                </div>

                <div class="dc">
                    <div class="dc-h">
                        <div class="dc-t"><svg viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                            </svg>Transaksi Terbaru</div>
                        <a href="transaksi.php" class="dc-a">Lihat Semua →</a>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="t">
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
                                <?php if($rows&&$rows->num_rows>0):while($r=$rows->fetch_assoc()):
          $late=$r['status_transaksi']==='Peminjaman'&&strtotime($r['tgl_kembali_rencana'])<time();
          if($r['status_transaksi']==='Pengembalian'){$bc='bd-g';$bt='✓ Kembali';}
          elseif($late){$bc='bd-r';$bt='⚠ Terlambat';}
          else{$bc='bd-b';$bt='⇄ Dipinjam';}
        ?>
                                <tr>
                                    <td class="book-cover-cell">
                                        <?php if(!empty($r['cover'])&&file_exists('../'.$r['cover'])):?><img class="cv"
                                            src="../<?=htmlspecialchars($r['cover'])?>" alt=""><?php else:?><div
                                            class="cv-ph">📖</div><?php endif;?></td>
                                    <td><span class="fw"><?=htmlspecialchars($r['nama_anggota'])?></span></td>
                                    <td><span
                                            class="fw"><?=htmlspecialchars(mb_strimwidth($r['judul_buku'],0,34,'…'))?></span>
                                    </td>
                                    <td><?=date('d M Y',strtotime($r['tgl_pinjam']))?></td>
                                    <td><?=date('d M Y',strtotime($r['tgl_kembali_rencana']))?></td>
                                    <td><span class="bd <?=$bc?>"><?=$bt?></span></td>
                                </tr>
                                <?php endwhile;else:?>
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:40px;color:var(--muted)">Belum ada
                                        transaksi</td>
                                </tr>
                                <?php endif;?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="ms">
                    <div class="ms-c">
                        <div class="ms-ico" style="background:rgba(184,74,44,.08)">📚</div>
                        <div>
                            <div class="ms-v"><?=$tb-$ts?></div>
                            <div class="ms-l">Buku Sedang Dipinjam</div>
                        </div>
                    </div>
                    <div class="ms-c">
                        <div class="ms-ico" style="background:rgba(73,102,64,.08)">↩️</div>
                        <div>
                            <div class="ms-v"><?=$kh?></div>
                            <div class="ms-l">Pengembalian Hari Ini</div>
                        </div>
                    </div>
                    <div class="ms-c">
                        <div class="ms-ico" style="background:rgba(196,138,32,.08)">⚠️</div>
                        <div>
                            <div class="ms-v"><?=$tl?></div>
                            <div class="ms-l">Keterlambatan Aktif</div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
    <script src="../assets/js/script.js"></script>
</body>

</html>