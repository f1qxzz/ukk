<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();
$conn = getConnection();

$total_buku     = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];
$buku_tersedia  = $conn->query("SELECT COUNT(*) c FROM buku WHERE status='tersedia'")->fetch_assoc()['c'];
$total_anggota  = $conn->query("SELECT COUNT(*) c FROM anggota")->fetch_assoc()['c'];
$total_pinjam   = $conn->query("SELECT COUNT(*) c FROM transaksi")->fetch_assoc()['c'];
$aktif_pinjam   = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$total_terlambat = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana < CURDATE()")->fetch_assoc()['c'];

$trans_all = $conn->query("SELECT t.*,a.nama_anggota,a.nis,a.kelas,b.judul_buku FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku ORDER BY t.tgl_pinjam DESC");

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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Laporan — Petugas Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    /* ── Design tokens ── */
    :root {
        --ink: #1a1a2e;
        --ink-mid: #3d3d5c;
        --ink-light: #6b6b8a;
        --ink-faint: #9898b0;
        --rule: #d4d4e0;
        --rule-dark: #a0a0c0;
        --paper: #fafaf8;
        --paper-alt: #f3f3f0;
        --cream: #fffef9;
        --accent: #1e3a5f;
        --accent2: #c8392b;
        --gold: #b8860b;
        --green: #1a6b3c;
        --serif: 'Libre Baskerville', Georgia, serif;
        --sans: 'Source Sans 3', sans-serif;
    }

    /* ── Screen wrapper (hidden when printing) ── */
    .screen-only {
        display: block;
    }

    /* ── Report document shell ── */
    .report-document {
        background: var(--cream);
        max-width: 900px;
        margin: 28px auto;
        border: 1px solid var(--rule);
        box-shadow: 0 4px 32px rgba(30, 30, 60, .10), 0 1px 4px rgba(30, 30, 60, .06);
        font-family: var(--sans);
        color: var(--ink);
        position: relative;
    }

    /* Decorative corner marks */
    .report-document::before,
    .report-document::after {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        border-color: var(--accent);
        border-style: solid;
    }

    .report-document::before {
        top: 10px;
        left: 10px;
        border-width: 2px 0 0 2px;
    }

    .report-document::after {
        bottom: 10px;
        right: 10px;
        border-width: 0 2px 2px 0;
    }

    /* ── Letterhead ── */
    .rpt-letterhead {
        padding: 36px 48px 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
    }

    .rpt-org {
        flex: 1;
    }

    .rpt-logo-mark {
        width: 44px;
        height: 44px;
        background: var(--accent);
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-family: var(--serif);
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 10px;
        letter-spacing: -1px;
    }

    .rpt-org-name {
        font-family: var(--serif);
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--accent);
        line-height: 1.2;
    }

    .rpt-org-sub {
        font-size: .78rem;
        color: var(--ink-light);
        margin-top: 2px;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .rpt-meta {
        text-align: right;
        font-size: .78rem;
        color: var(--ink-light);
        line-height: 1.8;
    }

    .rpt-meta strong {
        color: var(--ink-mid);
        font-weight: 600;
    }

    .rpt-doc-number {
        display: inline-block;
        background: var(--paper-alt);
        border: 1px solid var(--rule);
        border-radius: 3px;
        padding: 2px 8px;
        font-family: monospace;
        font-size: .75rem;
        color: var(--accent);
        letter-spacing: .05em;
        margin-bottom: 4px;
    }

    /* ── Title band ── */
    .rpt-title-band {
        margin: 20px 48px 0;
        padding-bottom: 18px;
        border-bottom: 2.5px solid var(--accent);
        display: flex;
        align-items: baseline;
        gap: 16px;
    }

    .rpt-title-band::after {
        content: '';
        display: block;
        position: absolute;
        left: 48px;
        height: 1px;
        background: var(--rule);
        width: calc(100% - 96px);
        margin-top: 3px;
    }

    .rpt-title {
        font-family: var(--serif);
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--accent);
        letter-spacing: -.02em;
    }

    .rpt-title-sub {
        font-size: .82rem;
        color: var(--ink-faint);
        font-style: italic;
    }

    /* ── Stats summary strip ── */
    .rpt-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        margin: 24px 48px;
        border: 1px solid var(--rule);
        border-radius: 4px;
        overflow: hidden;
    }

    .rpt-stat {
        padding: 18px 20px;
        border-right: 1px solid var(--rule);
        background: var(--paper-alt);
        position: relative;
    }

    .rpt-stat:last-child {
        border-right: none;
    }

    .rpt-stat-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--ink-faint);
        font-weight: 600;
        margin-bottom: 6px;
    }

    .rpt-stat-val {
        font-family: var(--serif);
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1;
    }

    .rpt-stat-val.money {
        font-size: 1.1rem;
    }

    .rpt-stat-sub {
        font-size: .73rem;
        color: var(--ink-light);
        margin-top: 5px;
    }

    .rpt-stat-bar {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .rpt-stat:nth-child(1) .rpt-stat-bar {
        background: var(--accent);
    }

    .rpt-stat:nth-child(2) .rpt-stat-bar {
        background: var(--green);
    }

    .rpt-stat:nth-child(3) .rpt-stat-bar {
        background: #e67e22;
    }

    .rpt-stat:nth-child(4) .rpt-stat-bar {
        background: var(--accent2);
    }

    /* ── Section header ── */
    .rpt-section {
        margin: 0 48px 28px;
    }

    .rpt-section-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--rule);
    }

    .rpt-section-num {
        width: 22px;
        height: 22px;
        background: var(--accent);
        color: #fff;
        border-radius: 50%;
        font-size: .68rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-family: var(--sans);
    }

    .rpt-section-title {
        font-family: var(--serif);
        font-size: .95rem;
        font-weight: 700;
        color: var(--ink);
    }

    .rpt-section-count {
        margin-left: auto;
        font-size: .72rem;
        color: var(--ink-faint);
        background: var(--paper-alt);
        border: 1px solid var(--rule);
        border-radius: 3px;
        padding: 2px 7px;
    }

    /* ── Tables ── */
    .rpt-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .8rem;
    }

    .rpt-table thead tr {
        background: var(--accent);
        color: #fff;
    }

    .rpt-table thead th {
        padding: 9px 11px;
        text-align: left;
        font-weight: 600;
        letter-spacing: .04em;
        font-size: .72rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .rpt-table thead th:first-child {
        border-radius: 0;
    }

    .rpt-table tbody tr {
        border-bottom: 1px solid var(--rule);
        transition: background .1s;
    }

    .rpt-table tbody tr:nth-child(even) {
        background: var(--paper-alt);
    }

    .rpt-table tbody tr:hover {
        background: #eef1f8;
    }

    .rpt-table tbody tr:last-child {
        border-bottom: 2px solid var(--rule-dark);
    }

    .rpt-table td {
        padding: 8px 11px;
        color: var(--ink-mid);
        vertical-align: middle;
    }

    .rpt-table td.num {
        color: var(--ink-faint);
        font-size: .72rem;
        width: 28px;
        text-align: center;
    }

    .rpt-table td.mono {
        font-family: monospace;
        font-size: .76rem;
        color: var(--ink-light);
    }

    .rpt-table td.name {
        font-weight: 600;
        color: var(--ink);
    }

    .rpt-table td.book {
        font-style: italic;
        color: var(--ink-mid);
        max-width: 200px;
    }

    .rpt-table td.money-cell {
        font-weight: 600;
        color: var(--accent);
        white-space: nowrap;
    }

    /* ── Status badges ── */
    .rpt-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 2px;
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        border: 1px solid transparent;
    }

    .rpt-badge.kembali {
        background: #e6f5ec;
        color: var(--green);
        border-color: #b2dfc2;
    }

    .rpt-badge.dipinjam {
        background: #eef2fb;
        color: #2255a4;
        border-color: #b8c8ef;
    }

    .rpt-badge.terlambat {
        background: #fdf0ee;
        color: var(--accent2);
        border-color: #f0c0ba;
    }

    .rpt-badge.lunas {
        background: #e6f5ec;
        color: var(--green);
        border-color: #b2dfc2;
    }

    .rpt-badge.belum {
        background: #fdf0ee;
        color: var(--accent2);
        border-color: #f0c0ba;
    }

    /* ── Footer ── */
    .rpt-footer {
        margin: 20px 48px 36px;
        padding-top: 14px;
        border-top: 1px solid var(--rule);
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .rpt-footer-left {
        font-size: .72rem;
        color: var(--ink-faint);
        line-height: 1.7;
    }

    .rpt-signature {
        text-align: center;
        font-size: .75rem;
        color: var(--ink-mid);
    }

    .rpt-signature-line {
        width: 140px;
        border-bottom: 1px solid var(--ink-mid);
        margin: 36px auto 6px;
    }

    .rpt-signature-title {
        font-size: .68rem;
        color: var(--ink-faint);
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    /* ── Empty state ── */
    .rpt-empty {
        text-align: center;
        padding: 28px;
        color: var(--ink-faint);
        font-style: italic;
        font-size: .82rem;
    }

    /* ── Print bar (screen only) ── */
    .print-bar-wrap {
        max-width: 900px;
        margin: 0 auto 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 4px;
    }

    .print-bar-label {
        font-size: .8rem;
        color: var(--ink-light);
        font-family: var(--sans);
    }

    /* ── PRINT STYLES ── */
    @media print {

        .screen-only,
        .no-print,
        .app-wrap>.sidebar,
        nav,
        header {
            display: none !important;
        }

        body {
            background: #fff;
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .app-wrap,
        .main-area,
        .content {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        @page {
            size: A4 portrait;
            margin: 12mm 14mm;
        }

        .report-document {
            max-width: 100%;
            margin: 0;
            box-shadow: none;
            border: none;
        }

        .rpt-letterhead {
            padding: 0 24px;
        }

        .rpt-title-band,
        .rpt-stats,
        .rpt-section,
        .rpt-footer {
            margin-left: 24px;
            margin-right: 24px;
        }

        .rpt-table {
            font-size: .74rem;
        }

        .rpt-table thead tr {
            background: #1e3a5f !important;
            -webkit-print-color-adjust: exact;
        }

        .rpt-table tbody tr:nth-child(even) {
            background: #f5f5f3 !important;
        }

        .rpt-badge {
            border: 1px solid #999 !important;
            background: none !important;
            color: #000 !important;
        }

        .rpt-stat {
            background: #f5f5f3 !important;
        }

        .card {
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }
    }
    </style>
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>
        <div class="main-area">
            <?php include 'includes/header.php'; ?>
            <main class="content">

                <!-- Print control bar (screen only) -->
                <div class="print-bar-wrap no-print screen-only">
                    <div class="print-bar-label">
                        Pratinjau dokumen laporan &mdash; No. <strong><?= $no_laporan ?></strong>
                    </div>
                    <button onclick="window.print()" class="btn btn-primary" style="gap:8px">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9" />
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <rect x="6" y="14" width="12" height="8" />
                        </svg>
                        Cetak / Export PDF
                    </button>
                </div>

                <!-- ═══════════════════════════════════════════
                     REPORT DOCUMENT
                ═══════════════════════════════════════════ -->
                <div class="report-document">

                    <!-- Letterhead -->
                    <div class="rpt-letterhead">
                        <div class="rpt-org">
                            <div class="rpt-logo-mark">P</div>
                            <div class="rpt-org-name">Perpustakaan Digital</div>
                            <div class="rpt-org-sub">Sistem Manajemen Perpustakaan</div>
                        </div>
                        <div class="rpt-meta">
                            <div class="rpt-doc-number"><?= $no_laporan ?></div><br>
                            <strong>Tanggal Cetak</strong><br>
                            <?= $tgl_cetak ?><br>
                            <?= $jam_cetak ?><br><br>
                            <strong>Dicetak oleh</strong><br>
                            Petugas
                        </div>
                    </div>

                    <!-- Title band -->
                    <div class="rpt-title-band" style="position:relative">
                        <div class="rpt-title">Laporan Sirkulasi Perpustakaan</div>
                        <div class="rpt-title-sub">Rekap peminjaman per <?= $tgl_cetak ?></div>
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
                                    if ($r['status_transaksi'] === 'Pengembalian') { $sc = 'kembali';  $sl = '✓ Kembali'; }
                                    elseif ($late)                                 { $sc = 'terlambat'; $sl = '⚠ Terlambat'; }
                                    else                                           { $sc = 'dipinjam';  $sl = '● Dipinjam'; }
                                ?>
                                <tr>
                                    <td class="num"><?= $n++ ?></td>
                                    <td class="name"><?= htmlspecialchars($r['nama_anggota']) ?></td>
                                    <td class="mono"><?= htmlspecialchars($r['nis']) ?></td>
                                    <td class="text-sm"><?= htmlspecialchars($r['kelas']) ?></td>
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
                        $aktif_all = $conn->query("SELECT t.*,a.nama_anggota,a.nis,a.kelas,b.judul_buku FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku WHERE t.status_transaksi='Peminjaman' ORDER BY t.tgl_kembali_rencana ASC");
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
                                    $sl = $late ? '⚠ Terlambat' : '● Dipinjam';
                                ?>
                                <tr>
                                    <td class="num"><?= $n++ ?></td>
                                    <td class="name"><?= htmlspecialchars($r['nama_anggota']) ?></td>
                                    <td class="mono"><?= htmlspecialchars($r['nis']) ?></td>
                                    <td class="text-sm"><?= htmlspecialchars($r['kelas']) ?></td>
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
    <script src="../assets/js/script.js"></script>
</body>

</html>