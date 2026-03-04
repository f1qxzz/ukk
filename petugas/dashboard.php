<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requirePetugas();
$conn=getConnection();
function cnt($c,$q,$f='c'){return $c->query($q)->fetch_assoc()[$f]??0;}
$tb=cnt($conn,"SELECT COUNT(*) c FROM buku");
$ts=cnt($conn,"SELECT COUNT(*) c FROM buku WHERE status='tersedia'");
$ta=cnt($conn,"SELECT COUNT(*) c FROM anggota");
$ap=cnt($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'");
$tl=cnt($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali_rencana<NOW()");
$td=cnt($conn,"SELECT COALESCE(SUM(total_denda),0) s FROM denda WHERE status_bayar='belum'",'s');
$kh=cnt($conn,"SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Pengembalian' AND DATE(tgl_kembali_aktual)=CURDATE()");
$rows=$conn->query("SELECT t.*,a.nama_anggota,a.nis,b.judul_buku,b.cover FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku WHERE t.status_transaksi='Peminjaman' ORDER BY t.tgl_pinjam DESC LIMIT 8");
$page_title='Dashboard'; $page_sub='Panel Petugas · Perpustakaan Digital';
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Petugas — Perpustakaan Digital</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrap">
<?php include 'includes/nav.php'; ?>
<div class="main-area">
<?php include 'includes/header.php'; ?>
<main class="content">

<div class="wb wb-petugas">
  <div class="wb-emoji">👨‍💼</div>
  <div>
    <div class="wb-name">Halo, <?=htmlspecialchars(getPenggunaName())?> 👨‍💼</div>
    <div class="wb-desc">Kelola peminjaman dan pengembalian buku harian · Panel Petugas</div>
  </div>
  <div class="wb-actions">
    <a href="transaksi.php" class="wb-btn1">+ Catat Pinjam</a>
    <a href="laporan.php" class="wb-btn2">Cetak Laporan</a>
  </div>
</div>

<div class="srow">
  <div class="sc" style="--a:var(--accent);--ab:rgba(44,79,124,.08)">
    <div><div class="sc-l">Total Buku</div><div class="sc-v"><?=$tb?></div><div class="sc-s ok"><?=$ts?> tersedia</div></div>
    <div class="sc-i"><svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
  </div>
  <div class="sc" style="--a:#ef4444;--ab:rgba(239,68,68,.08)">
    <div><div class="sc-l">Aktif Pinjam</div><div class="sc-v"><?=$ap?></div><div class="sc-s <?=$tl>0?'bad':''?>"><?=$tl?> terlambat</div></div>
    <div class="sc-i"><svg viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg></div>
  </div>
  <div class="sc" style="--a:var(--success);--ab:rgba(16,185,129,.08)">
    <div><div class="sc-l">Total Anggota</div><div class="sc-v"><?=$ta?></div><div class="sc-s">terdaftar</div></div>
    <div class="sc-i"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
  </div>
  <div class="sc" style="--a:#f59e0b;--ab:rgba(245,158,11,.08)">
    <div><div class="sc-l">Denda Belum Lunas</div><div class="sc-v" style="font-size:1.3rem">Rp <?=number_format($td,0,',','.')?></div><div class="sc-s bad">perlu diproses</div></div>
    <div class="sc-i"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
  </div>
</div>

<div class="dc">
  <div class="dc-h">
    <div class="dc-t"><svg viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>Peminjaman Aktif</div>
    <a href="transaksi.php" class="dc-a">Lihat Semua →</a>
  </div>
  <div style="overflow-x:auto">
    <table class="t">
      <thead><tr><th>Cover</th><th>Anggota</th><th>NIS</th><th>Buku</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
      <tbody>
        <?php if($rows&&$rows->num_rows>0):while($r=$rows->fetch_assoc()):$late=strtotime($r['tgl_kembali_rencana'])<time();?>
        <tr>
          <td class="book-cover-cell"><?php if(!empty($r['cover'])&&file_exists('../'.$r['cover'])):?><img class="cv" src="../<?=htmlspecialchars($r['cover'])?>" alt=""><?php else:?><div class="cv-ph">📖</div><?php endif;?></td>
          <td><span class="fw"><?=htmlspecialchars($r['nama_anggota'])?></span></td>
          <td class="text-sm text-muted"><?=htmlspecialchars($r['nis'])?></td>
          <td><span class="fw"><?=htmlspecialchars(mb_strimwidth($r['judul_buku'],0,30,'…'))?></span></td>
          <td><?=date('d M Y',strtotime($r['tgl_pinjam']))?></td>
          <td><?=date('d M Y',strtotime($r['tgl_kembali_rencana']))?></td>
          <td><span class="bd <?=$late?'bd-r':'bd-b'?>"><?=$late?'⚠ Terlambat':'⇄ Dipinjam'?></span></td>
        </tr>
        <?php endwhile;else:?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">✅ Tidak ada pinjaman aktif</td></tr>
        <?php endif;?>
      </tbody>
    </table>
  </div>
</div>

<div class="ms">
  <div class="ms-c"><div class="ms-ico" style="background:rgba(44,79,124,.08)">📚</div><div><div class="ms-v"><?=$tb-$ts?></div><div class="ms-l">Buku Di Tangan Anggota</div></div></div>
  <div class="ms-c"><div class="ms-ico" style="background:rgba(73,102,64,.08)">↩️</div><div><div class="ms-v"><?=$kh?></div><div class="ms-l">Dikembalikan Hari Ini</div></div></div>
  <div class="ms-c"><div class="ms-ico" style="background:rgba(196,138,32,.08)">⚠️</div><div><div class="ms-v"><?=$tl?></div><div class="ms-l">Keterlambatan Aktif</div></div></div>
</div>

</main></div></div>
<script src="../assets/js/script.js"></script>
</body></html>
