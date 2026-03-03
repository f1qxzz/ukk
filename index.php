<?php
require_once 'includes/session.php';
require_once 'config/database.php';
initSession();

$isAdmin=$isPetugas=$isAnggota=$loggedIn=false; $username='';
if(isset($_SESSION['pengguna_logged_in'])){
  $loggedIn=true; $username=$_SESSION['pengguna_username']??'';
  if($_SESSION['pengguna_level']==='admin')$isAdmin=true;
  elseif($_SESSION['pengguna_level']==='petugas')$isPetugas=true;
}
if(isset($_SESSION['anggota_logged_in'])){
  $loggedIn=true; $username=$_SESSION['anggota_nama']??''; $isAnggota=true;
}
$conn=getConnection();

// ── STATS ──
$total_buku    = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c']??0;
$total_anggota = $conn->query("SELECT COUNT(*) c FROM anggota")->fetch_assoc()['c']??0;
$buku_tersedia = $conn->query("SELECT COUNT(*) c FROM buku WHERE status='tersedia'")->fetch_assoc()['c']??0;
$total_pinjam  = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['c']??0;
$total_kembali = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Pengembalian'")->fetch_assoc()['c']??0;

// ── BUKU TERBARU ──
$res_baru=$conn->query("SELECT b.*,k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.id_kategori=k.id_kategori ORDER BY b.id_buku DESC LIMIT 10");
$buku_baru=[]; if($res_baru) while($r=$res_baru->fetch_assoc()) $buku_baru[]=$r;

// ── BUKU POPULER ──
$res_pop=$conn->query("SELECT b.id_buku,b.judul_buku,b.pengarang,b.cover,b.status,b.tahun_terbit,k.nama_kategori,COUNT(t.id_transaksi) as jml_pinjam FROM buku b LEFT JOIN transaksi t ON b.id_buku=t.id_buku LEFT JOIN kategori k ON b.id_kategori=b.id_kategori GROUP BY b.id_buku ORDER BY jml_pinjam DESC,b.id_buku DESC LIMIT 6");
$buku_pop=[]; if($res_pop) while($r=$res_pop->fetch_assoc()) $buku_pop[]=$r;

// ── FEATURED BOOK ──  
$featured=!empty($buku_pop)?$buku_pop[0]:(!empty($buku_baru)?$buku_baru[0]:null);

// ── KATEGORI ──
$res_kat=$conn->query("SELECT k.*,COUNT(b.id_buku) as jml FROM kategori k LEFT JOIN buku b ON k.id_kategori=b.id_kategori GROUP BY k.id_kategori ORDER BY jml DESC LIMIT 8");
$kategori=[]; if($res_kat) while($r=$res_kat->fetch_assoc()) $kategori[]=$r;

// ── ULASAN TERBARU ──
$res_ulasan=$conn->query("SELECT u.*,a.nama_anggota,b.judul_buku,b.pengarang FROM ulasan_buku u JOIN anggota a ON u.id_anggota=a.id_anggota JOIN buku b ON u.id_buku=b.id_buku ORDER BY u.id_ulasan DESC LIMIT 6");
$ulasan_arr=[]; if($res_ulasan) while($u=$res_ulasan->fetch_assoc()) $ulasan_arr[]=$u;

// ── LEADERBOARD ANGGOTA TERBANYAK PINJAM ──
$res_leader=$conn->query("SELECT a.nama_anggota,a.kelas,COUNT(t.id_transaksi) as jml FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota GROUP BY t.id_anggota ORDER BY jml DESC LIMIT 5");
$leaderboard=[]; if($res_leader) while($r=$res_leader->fetch_assoc()) $leaderboard[]=$r;

// ── ANGGOTA DATA ──
$anggota_data=null;
if($isAnggota&&isset($_SESSION['anggota_id'])){
  $aid=(int)$_SESSION['anggota_id'];
  $r=$conn->query("SELECT a.*,(SELECT COUNT(*) FROM transaksi WHERE id_anggota=$aid) as total_pinjam, (SELECT COUNT(*) FROM transaksi WHERE id_anggota=$aid AND status_transaksi='Peminjaman') as aktif_pinjam, COALESCE((SELECT SUM(d.total_denda) FROM denda d JOIN transaksi t ON d.id_transaksi=t.id_transaksi WHERE t.id_anggota=$aid AND d.status_bayar='belum'),0) as denda FROM anggota a WHERE a.id_anggota=$aid");
  if($r&&$r->num_rows) $anggota_data=$r->fetch_assoc();
}

// ── STATS EXTRA ──
$avg_rating=$conn->query("SELECT COALESCE(AVG(rating),0) avg FROM ulasan_buku")->fetch_assoc()['avg']??0;
$total_ulasan=$conn->query("SELECT COUNT(*) c FROM ulasan_buku")->fetch_assoc()['c']??0;
$jatuh_tempo=$conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND DATE(tgl_kembali_rencana)<=CURDATE()")->fetch_assoc()['c']??0;
$buku_hampir_habis=$conn->query("SELECT COUNT(*) c FROM buku WHERE stok<=2 AND status='tersedia'")->fetch_assoc()['c']??0;
$pinjam_bulan_ini=$conn->query("SELECT COUNT(*) c FROM transaksi WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_assoc()['c']??0;

// ── JAM BUKA ──
date_default_timezone_set('Asia/Jakarta');
$jam=(int)date('H'); $hari=(int)date('N');
$buka=($hari<=6&&$jam>=7&&$jam<16); $jam_str=date('H:i');

// ── QUOTE OF THE DAY ──
$quotes=[
  ['Membaca adalah jendela dunia yang tidak pernah tertutup.','Pepatah Indonesia'],
  ['Buku adalah teman terbaik yang tidak pernah mengecewakan.','Pepatah'],
  ['Satu buku yang kamu baca bisa mengubah hidupmu selamanya.','Nelson Mandela'],
  ['Investasi terbaik adalah investasi pada dirimu sendiri — membaca!','Benjamin Franklin'],
  ['Perpustakaan adalah tempat di mana masa lalu dan masa depan bertemu.','A. Whitney Brown'],
  ['Orang yang membaca buku akan selalu berada di atas orang yang menonton televisi.','Jim Rohn'],
  ['Buku hari ini adalah teman di hari tua.','Pepatah'],
];
$quote=$quotes[date('z')%count($quotes)];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description"
        content="LibraSpace — Perpustakaan digital modern. Temukan, pinjam, dan nikmati ribuan koleksi buku pilihan secara online.">
    <title>LibraSpace — Perpustakaan Digital Modern</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Lora:ital,wght@0,500;0,600;0,700;1,400;1,500;1,600;1,700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
    :root {
        --w: #fff;
        --off: #f7f9ff;
        --soft: #eef2ff;
        --card: #fff;
        --ink: #0a0f2e;
        --ink2: #1a2156;
        --ink3: #2d3a7a;
        --muted: #5a6899;
        --subtle: #8d99c8;
        --light: #c2cae8;
        --blue: #2563eb;
        --blue2: #1d4ed8;
        --blue3: #1e40af;
        --blue-l: #eff6ff;
        --blue-ll: #f0f4ff;
        --indigo: #4f46e5;
        --indigo-l: #eef2ff;
        --sky: #0ea5e9;
        --teal: #0891b2;
        --violet: #7c3aed;
        --violet-l: #f5f3ff;
        --green: #059669;
        --green-l: #ecfdf5;
        --amber: #d97706;
        --amber-l: #fffbeb;
        --rose: #e11d48;
        --rose-l: #fff1f2;
        --orange: #ea580c;
        --orange-l: #fff7ed;
        --border: rgba(37, 99, 235, .1);
        --border2: rgba(37, 99, 235, .05);
        --sh0: 0 1px 3px rgba(10, 15, 46, .04), 0 2px 8px rgba(10, 15, 46, .04);
        --sh1: 0 4px 16px rgba(10, 15, 46, .07), 0 8px 28px rgba(10, 15, 46, .05);
        --sh2: 0 8px 32px rgba(10, 15, 46, .1), 0 20px 56px rgba(10, 15, 46, .08);
        --sh3: 0 20px 60px rgba(10, 15, 46, .14), 0 40px 80px rgba(10, 15, 46, .1);
        --r: 14px;
        --rb: 20px;
    }

    *,
    *::before,
    *::after {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: 'Sora', sans-serif;
        background: var(--off);
        color: var(--ink);
        line-height: 1.6;
        overflow-x: hidden;
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    img {
        display: block;
        max-width: 100%;
    }

    ::-webkit-scrollbar {
        width: 5px;
    }

    ::-webkit-scrollbar-track {
        background: var(--soft);
    }

    ::-webkit-scrollbar-thumb {
        background: #b8c4e0;
        border-radius: 5px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--blue);
    }

    /* ──────────── STATUS BAR ──────────── */
    .topbar {
        background: linear-gradient(90deg, var(--blue3), var(--blue2), var(--blue));
        padding: 9px 5%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 6px;
        font-size: .72rem;
        color: rgba(255, 255, 255, .85);
        position: relative;
        z-index: 902;
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    .topbar-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .topbar-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .dot-open {
        background: #34d399;
        animation: blink 2s infinite;
    }

    .dot-closed {
        background: #fb7185;
    }

    .topbar-right {
        display: flex;
        gap: 14px;
        align-items: center;
    }

    .topbar-right a {
        color: rgba(255, 255, 255, .65);
        font-weight: 500;
        transition: color .2s;
    }

    .topbar-right a:hover {
        color: #fff;
    }

    /* ──────────── NAV ──────────── */
    .nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 900;
        height: 66px;
        padding: 0 5%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(255, 255, 255, .82);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-bottom: 1px solid transparent;
        transition: all .35s;
    }

    .nav.at-top {
        background: rgba(255, 255, 255, .82);
    }

    .nav.scrolled {
        background: rgba(255, 255, 255, .97);
        border-bottom: 1px solid var(--border);
        box-shadow: 0 2px 20px rgba(10, 15, 46, .06);
    }

    .nav-logo {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .nav-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        background: linear-gradient(135deg, var(--blue), var(--blue2));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        box-shadow: 0 4px 14px rgba(37, 99, 235, .3);
        transition: transform .3s;
        flex-shrink: 0;
    }

    .nav-logo:hover .nav-icon {
        transform: rotate(-8deg) scale(1.06);
    }

    .nav-name {
        font-family: 'Lora', serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--ink);
    }

    .nav-name span {
        color: var(--blue);
    }

    .nav-links {
        display: flex;
        gap: 1px;
    }

    .nav-links a {
        padding: 7px 13px;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 500;
        color: var(--muted);
        transition: all .18s;
        letter-spacing: .01em;
    }

    .nav-links a:hover {
        color: var(--blue);
        background: var(--blue-ll);
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-outline {
        padding: 8px 19px;
        border-radius: 9px;
        font-size: .82rem;
        font-weight: 500;
        border: 1.5px solid var(--border);
        color: var(--muted);
        transition: all .2s;
    }

    .btn-outline:hover {
        border-color: var(--blue);
        color: var(--blue);
        background: var(--blue-ll);
    }

    .btn-primary {
        padding: 9px 21px;
        border-radius: 9px;
        font-size: .82rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--blue), var(--blue2));
        color: #fff;
        box-shadow: 0 4px 14px rgba(37, 99, 235, .28);
        transition: all .2s;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 22px rgba(37, 99, 235, .38);
    }

    .hamburger {
        display: none;
        background: none;
        border: none;
        color: var(--ink);
        font-size: 1.4rem;
        cursor: pointer;
        padding: 4px;
    }

    /* ──────────── MOBILE DRAWER ──────────── */
    .drawer {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 950;
        background: rgba(255, 255, 255, .98);
        backdrop-filter: blur(20px);
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 18px;
    }

    .drawer.open {
        display: flex;
    }

    .drawer a {
        font-size: 1.15rem;
        font-weight: 500;
        color: var(--muted);
        padding: 10px 40px;
        border-radius: 10px;
        transition: color .2s;
    }

    .drawer a:hover {
        color: var(--blue);
    }

    .drawer-x {
        position: absolute;
        top: 20px;
        right: 5%;
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--muted);
    }

    /* ──────────── HERO ──────────── */
    .hero {
        min-height: 100vh;
        padding-top: 106px;
        background: linear-gradient(170deg, #f0f4ff 0%, #fafbff 45%, #f6f0ff 80%, #fdf4ff 100%);
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: center;
    }

    .hero-bg {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
    }

    .hero-bg-dots {
        position: absolute;
        inset: 0;
        background-image: radial-gradient(circle, rgba(37, 99, 235, .09) 1px, transparent 1px);
        background-size: 30px 30px;
        mask-image: radial-gradient(ellipse 80% 90% at 50% 50%, black 0%, transparent 100%);
    }

    .hero-bg-blob1 {
        position: absolute;
        top: -120px;
        right: -80px;
        width: 500px;
        height: 500px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(37, 99, 235, .07) 0%, transparent 65%);
        animation: float1 9s ease-in-out infinite;
    }

    .hero-bg-blob2 {
        position: absolute;
        bottom: -100px;
        left: -80px;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(124, 58, 237, .06) 0%, transparent 65%);
        animation: float2 11s ease-in-out infinite;
    }

    .hero-left {
        padding: 60px 5% 60px 8%;
        position: relative;
        z-index: 2;
    }

    .hero-right {
        padding: 40px 8% 40px 4%;
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 20px;
    }

    /* Quote pill */
    .hero-quote-pill {
        display: inline-flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 16px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .8);
        border: 1.5px solid var(--border);
        box-shadow: var(--sh0);
        max-width: 380px;
        width: 100%;
        margin-bottom: 24px;
        backdrop-filter: blur(8px);
        opacity: 0;
        animation: fadeUp .6s .05s forwards;
    }

    .hero-quote-ico {
        font-size: 1.2rem;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .hero-quote-text {
        font-family: 'Lora', serif;
        font-size: .8rem;
        font-style: italic;
        color: var(--ink2);
        line-height: 1.6;
    }

    .hero-quote-by {
        font-size: .66rem;
        color: var(--subtle);
        margin-top: 4px;
        font-style: normal;
        font-family: 'Sora', sans-serif;
        font-weight: 600;
    }

    .hero-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 14px;
        border-radius: 40px;
        background: rgba(37, 99, 235, .07);
        border: 1.5px solid rgba(37, 99, 235, .14);
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: var(--blue);
        width: fit-content;
        margin-bottom: 18px;
        opacity: 0;
        animation: fadeUp .6s .12s forwards;
    }

    .hero-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--green);
        animation: blink 2s infinite;
    }

    .hero-h1 {
        font-family: 'Lora', serif;
        font-size: clamp(2.4rem, 3.8vw, 3.9rem);
        font-weight: 700;
        line-height: 1.08;
        color: var(--ink);
        margin-bottom: 16px;
        opacity: 0;
        animation: fadeUp .7s .2s forwards;
    }

    .hero-h1 em {
        font-style: italic;
        color: var(--blue);
    }

    .hero-h1 .grad {
        background: linear-gradient(135deg, var(--blue), var(--indigo), var(--violet));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-style: italic;
    }

    .hero-desc {
        font-size: .95rem;
        color: var(--muted);
        line-height: 1.85;
        max-width: 440px;
        margin-bottom: 24px;
        opacity: 0;
        animation: fadeUp .6s .28s forwards;
    }

    /* ── LIVE SEARCH ── */
    .search-wrap {
        position: relative;
        margin-bottom: 24px;
        opacity: 0;
        animation: fadeUp .6s .35s forwards;
    }

    .search-box {
        display: flex;
        border-radius: 13px;
        overflow: visible;
        border: 2px solid var(--border);
        background: #fff;
        box-shadow: var(--sh1);
        transition: border-color .2s, box-shadow .2s;
        position: relative;
    }

    .search-box:focus-within {
        border-color: rgba(37, 99, 235, .3);
        box-shadow: 0 4px 20px rgba(37, 99, 235, .12);
    }

    .search-ico {
        padding: 0 13px;
        display: flex;
        align-items: center;
        color: var(--subtle);
    }

    .search-ico svg {
        width: 17px;
        height: 17px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
    }

    .search-inp {
        flex: 1;
        padding: 13px 4px;
        border: none;
        outline: none;
        font-size: .92rem;
        font-family: 'Sora', sans-serif;
        color: var(--ink);
        background: transparent;
    }

    .search-inp::placeholder {
        color: var(--light);
    }

    .search-btn {
        padding: 11px 22px;
        background: linear-gradient(135deg, var(--blue), var(--blue2));
        color: #fff;
        font-size: .83rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        font-family: 'Sora', sans-serif;
        border-radius: 0 11px 11px 0;
        transition: background .2s;
        white-space: nowrap;
    }

    .search-btn:hover {
        background: var(--blue3);
    }

    /* search dropdown */
    .search-drop {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        z-index: 300;
        background: #fff;
        border-radius: 14px;
        box-shadow: var(--sh3);
        border: 1.5px solid var(--border);
        overflow: hidden;
        display: none;
    }

    .search-drop.show {
        display: block;
    }

    .sd-item {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 11px 16px;
        border-bottom: 1px solid var(--border2);
        cursor: pointer;
        transition: background .15s;
    }

    .sd-item:last-child {
        border-bottom: none;
    }

    .sd-item:hover {
        background: var(--blue-ll);
    }

    .sd-ph {
        width: 30px;
        height: 40px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        flex-shrink: 0;
    }

    .sd-info {
        flex: 1;
        min-width: 0;
    }

    .sd-title {
        font-size: .81rem;
        font-weight: 600;
        color: var(--ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sd-meta {
        font-size: .67rem;
        color: var(--subtle);
        margin-top: 1px;
    }

    .sd-badge {
        font-size: .63rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .sd-yes {
        background: var(--green-l);
        color: var(--green);
    }

    .sd-no {
        background: var(--rose-l);
        color: var(--rose);
    }

    .sd-empty,
    .sd-loading {
        padding: 16px;
        text-align: center;
        font-size: .82rem;
        color: var(--subtle);
    }

    .spin {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid var(--border);
        border-top-color: var(--blue);
        border-radius: 50%;
        animation: spin .6s linear infinite;
    }

    /* search tags */
    .search-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }

    .stag {
        padding: 4px 12px;
        border-radius: 20px;
        background: rgba(255, 255, 255, .8);
        border: 1.5px solid var(--border);
        font-size: .7rem;
        font-weight: 600;
        color: var(--muted);
        cursor: pointer;
        transition: all .18s;
    }

    .stag:hover {
        border-color: var(--blue);
        color: var(--blue);
        background: var(--blue-ll);
    }

    .hero-btns {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        opacity: 0;
        animation: fadeUp .6s .42s forwards;
    }

    .btn-hero {
        padding: 13px 26px;
        border-radius: 11px;
        font-size: .9rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--blue), var(--blue2));
        color: #fff;
        box-shadow: 0 5px 20px rgba(37, 99, 235, .3);
        transition: all .22s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-hero:hover {
        transform: translateY(-2px);
        box-shadow: 0 9px 28px rgba(37, 99, 235, .42);
    }

    .btn-hero2 {
        padding: 13px 22px;
        border-radius: 11px;
        font-size: .9rem;
        font-weight: 500;
        border: 1.5px solid var(--border);
        color: var(--ink2);
        background: #fff;
        transition: all .2s;
    }

    .btn-hero2:hover {
        border-color: var(--blue);
        color: var(--blue);
        background: var(--blue-ll);
    }

    .hero-nums {
        display: flex;
        gap: 0;
        margin-top: 36px;
        padding-top: 26px;
        border-top: 1px solid var(--border);
        opacity: 0;
        animation: fadeUp .6s .5s forwards;
    }

    .hnum {
        padding-right: 24px;
    }

    .hnum+.hnum {
        padding-left: 24px;
        border-left: 1px solid var(--border);
    }

    .hnum-n {
        font-family: 'Lora', serif;
        font-size: 1.85rem;
        font-weight: 700;
        color: var(--blue);
        line-height: 1;
    }

    .hnum-l {
        font-size: .63rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--subtle);
        margin-top: 3px;
        font-weight: 600;
    }

    /* ── HERO RIGHT — Featured Book ── */
    .featured-book-3d {
        perspective: 800px;
        cursor: pointer;
        opacity: 0;
        animation: fadeUp .8s .25s forwards;
    }

    .book-3d {
        width: 200px;
        height: 280px;
        position: relative;
        transform-style: preserve-3d;
        animation: bookFloat 4s ease-in-out infinite;
        transition: transform .3s;
    }

    .book-3d:hover {
        transform: rotateY(-15deg) rotateX(5deg) scale(1.04);
    }

    .book-face {
        position: absolute;
        inset: 0;
        background: linear-gradient(160deg, var(--blue2), var(--indigo));
        border-radius: 4px 12px 12px 4px;
        box-shadow: -6px 6px 28px rgba(10, 15, 46, .35), inset -3px 0 6px rgba(0, 0, 0, .2);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px 20px;
        text-align: center;
        overflow: hidden;
    }

    .book-face::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .3), transparent);
    }

    .book-face::after {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 16px;
        background: linear-gradient(90deg, rgba(0, 0, 0, .3), rgba(0, 0, 0, .1));
        border-radius: 4px 0 0 4px;
    }

    .book-spine {
        position: absolute;
        left: -16px;
        top: 0;
        bottom: 0;
        width: 16px;
        background: linear-gradient(90deg, #0f2a8a, #1a3a9f);
        border-radius: 4px 0 0 4px;
        transform: rotateY(-90deg);
        transform-origin: right;
    }

    .book-label {
        font-size: .6rem;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .6);
        margin-bottom: 10px;
    }

    .book-title {
        font-family: 'Lora', serif;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        line-height: 1.3;
        margin-bottom: 8px;
    }

    .book-author {
        font-size: .72rem;
        color: rgba(255, 255, 255, .6);
    }

    .book-deco {
        font-size: 3.5rem;
        margin-top: 16px;
        opacity: .15;
    }

    .book-badge {
        position: absolute;
        top: 14px;
        right: 14px;
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        color: #78350f;
        font-size: .6rem;
        font-weight: 800;
        padding: 3px 9px;
        border-radius: 12px;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    /* Right panel extras */
    .hero-widgets {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-width: 320px;
    }

    .hw {
        background: rgba(255, 255, 255, .88);
        backdrop-filter: blur(12px);
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: var(--sh0);
        opacity: 0;
        animation: fadeUp .6s forwards;
    }

    .hw1 {
        animation-delay: .35s;
    }

    .hw2 {
        animation-delay: .45s;
    }

    .hw3 {
        animation-delay: .55s;
    }

    .hw-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .hw-ico {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .hw-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--subtle);
        margin-bottom: 2px;
    }

    .hw-val {
        font-size: .9rem;
        font-weight: 700;
        color: var(--ink);
    }

    .hw-sub {
        font-size: .68rem;
        color: var(--muted);
    }

    /* reading challenge bar */
    .rc-wrap {
        margin-top: 10px;
    }

    .rc-label {
        display: flex;
        justify-content: space-between;
        font-size: .68rem;
        color: var(--muted);
        margin-bottom: 6px;
    }

    .rc-track {
        height: 8px;
        background: var(--border);
        border-radius: 8px;
        overflow: hidden;
    }

    .rc-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--green), #34d399);
        border-radius: 8px;
        transition: width 1.5s ease;
    }

    /* ──────────── MEMBER BANNER ──────────── */
    .member-banner {
        background: linear-gradient(135deg, #0a0f2e 0%, #1a2156 40%, #2563eb 100%);
        padding: 24px 7%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        position: relative;
        z-index: 10;
        overflow: hidden;
    }

    .member-banner::after {
        content: '';
        position: absolute;
        right: -50px;
        top: -50px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .04);
    }

    .mb-left {
        display: flex;
        align-items: center;
        gap: 16px;
        position: relative;
        z-index: 1;
    }

    .mb-av {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--blue), var(--indigo));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
        border: 2.5px solid rgba(255, 255, 255, .2);
        flex-shrink: 0;
    }

    .mb-greet {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: rgba(255, 255, 255, .45);
        font-weight: 600;
    }

    .mb-name {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        margin-top: 1px;
    }

    .mb-sub {
        font-size: .72rem;
        color: rgba(255, 255, 255, .45);
        margin-top: 1px;
    }

    .mb-stats {
        display: flex;
        position: relative;
        z-index: 1;
    }

    .mbstat {
        padding: 0 22px;
        text-align: center;
    }

    .mbstat+.mbstat {
        border-left: 1px solid rgba(255, 255, 255, .1);
    }

    .mbstat-n {
        font-family: 'Lora', serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        line-height: 1;
    }

    .mbstat-l {
        font-size: .62rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: rgba(255, 255, 255, .38);
        margin-top: 3px;
        font-weight: 600;
    }

    .mb-btns {
        display: flex;
        gap: 8px;
        position: relative;
        z-index: 1;
    }

    .mbb {
        padding: 9px 20px;
        border-radius: 9px;
        font-size: .8rem;
        font-weight: 700;
        transition: all .2s;
    }

    .mbb-w {
        background: #fff;
        color: var(--blue2);
        box-shadow: 0 3px 12px rgba(0, 0, 0, .12);
    }

    .mbb-w:hover {
        transform: translateY(-1px);
    }

    .mbb-g {
        border: 1.5px solid rgba(255, 255, 255, .22);
        color: rgba(255, 255, 255, .75);
    }

    .mbb-g:hover {
        background: rgba(255, 255, 255, .1);
    }

    /* ──────────── ALERT JATUH TEMPO ──────────── */
    .alert-jt {
        background: linear-gradient(90deg, #fff7ed, #fffbeb);
        border-left: 4px solid var(--orange);
        padding: 12px 7%;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 10;
        position: relative;
    }

    .alert-jt-link {
        margin-left: auto;
        font-size: .8rem;
        font-weight: 700;
        color: var(--orange);
        white-space: nowrap;
    }

    /* ──────────── SECTIONS COMMON ──────────── */
    .sec {
        padding: 80px 7%;
        position: relative;
        z-index: 10;
        background: var(--w);
    }

    .sec.alt {
        background: var(--off);
    }

    .sec.dark {
        background: var(--ink);
        color: #fff;
    }

    .sec-wrap {
        max-width: 1200px;
        margin: 0 auto;
    }

    .sec-hd {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 36px;
    }

    .sec-lft {}

    .sec-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: var(--blue);
        margin-bottom: 10px;
    }

    .sec-pill::before {
        content: '';
        width: 18px;
        height: 2px;
        background: var(--blue);
        border-radius: 2px;
    }

    .sec-h {
        font-family: 'Lora', serif;
        font-size: clamp(1.8rem, 3vw, 2.8rem);
        font-weight: 700;
        line-height: 1.1;
        color: var(--ink);
    }

    .sec.dark .sec-h {
        color: #fff;
    }

    .sec-h em {
        font-style: italic;
        color: var(--blue);
    }

    .sec-sub {
        font-size: .9rem;
        color: var(--muted);
        line-height: 1.8;
        margin-top: 8px;
        max-width: 440px;
    }

    .sec.dark .sec-sub {
        color: rgba(255, 255, 255, .45);
    }

    .sec-link {
        font-size: .81rem;
        font-weight: 700;
        color: var(--blue);
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        transition: gap .2s;
    }

    .sec-link:hover {
        gap: 8px;
    }

    /* ──────────── INFO STRIP ──────────── */
    .info-strip {
        background: var(--w);
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        padding: 0 7%;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        z-index: 10;
        position: relative;
    }

    .istrip {
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 13px;
        border-right: 1px solid var(--border);
        transition: background .2s;
    }

    .istrip:last-child {
        border-right: none;
    }

    .istrip:hover {
        background: var(--blue-ll);
    }

    .istrip-ico {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .istrip-n {
        font-family: 'Lora', serif;
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1;
    }

    .istrip-l {
        font-size: .67rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--subtle);
        margin-top: 2px;
        font-weight: 600;
    }

    /* ──────────── FEATURED BOOK SECTION ──────────── */
    .featured-sec {
        padding: 64px 7%;
        background: linear-gradient(160deg, #f0f4ff, #faf5ff, #f5f0ff);
        position: relative;
        z-index: 10;
        overflow: hidden;
    }

    .featured-sec::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(124, 58, 237, .06) 0%, transparent 65%);
        pointer-events: none;
    }

    .featured-grid {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 48px;
        align-items: center;
        max-width: 860px;
    }

    .featured-cover {
        width: 170px;
        height: 240px;
        border-radius: 6px 14px 14px 6px;
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
        box-shadow: -8px 8px 32px rgba(10, 15, 46, .2), 0 4px 12px rgba(10, 15, 46, .1);
    }

    .featured-cover-bg {
        width: 100%;
        height: 100%;
        background: linear-gradient(160deg, var(--blue2), var(--indigo), var(--violet));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
    }

    .featured-cover img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .featured-cover::after {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 14px;
        background: linear-gradient(90deg, rgba(0, 0, 0, .25), transparent);
    }

    .featured-star {
        position: absolute;
        top: 10px;
        right: 10px;
        background: linear-gradient(135deg, var(--amber), #fbbf24);
        color: #78350f;
        font-size: .6rem;
        font-weight: 800;
        padding: 3px 9px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .featured-info {}

    .featured-genre {
        display: inline-block;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: var(--blue);
        background: var(--blue-ll);
        padding: 3px 10px;
        border-radius: 10px;
        margin-bottom: 10px;
    }

    .featured-title {
        font-family: 'Lora', serif;
        font-size: 1.7rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.2;
        margin-bottom: 6px;
    }

    .featured-author {
        font-size: .85rem;
        color: var(--muted);
        margin-bottom: 14px;
    }

    .featured-stars {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 14px;
    }

    .featured-stars span {
        color: var(--amber);
        font-size: .95rem;
    }

    .featured-stars em {
        font-size: .75rem;
        font-style: normal;
        color: var(--subtle);
        font-weight: 600;
    }

    .featured-desc {
        font-size: .84rem;
        color: var(--muted);
        line-height: 1.78;
        margin-bottom: 20px;
    }

    .featured-meta {
        display: flex;
        gap: 16px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .fmeta {
        font-size: .72rem;
        color: var(--subtle);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .fmeta strong {
        color: var(--ink);
        font-weight: 600;
    }

    .featured-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
        border-radius: 11px;
        background: linear-gradient(135deg, var(--blue), var(--blue2));
        color: #fff;
        font-size: .87rem;
        font-weight: 700;
        box-shadow: 0 5px 18px rgba(37, 99, 235, .28);
        transition: all .22s;
    }

    .featured-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 26px rgba(37, 99, 235, .38);
    }

    /* ──────────── BOOK SHELF (visual) ──────────── */
    .shelf-sec {
        padding: 56px 0 56px 7%;
        background: linear-gradient(135deg, #0a0f2e 0%, #1a2156 50%, #0d1547 100%);
        position: relative;
        z-index: 10;
        overflow: hidden;
    }

    .shelf-sec::before {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 300px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .02));
        pointer-events: none;
    }

    .shelf-hd {
        padding-right: 7%;
        margin-bottom: 32px;
    }

    .shelf-pill {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .35);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }

    .shelf-pill::before {
        content: '';
        width: 16px;
        height: 1.5px;
        background: rgba(255, 255, 255, .25);
        border-radius: 2px;
    }

    .shelf-h {
        font-family: 'Lora', serif;
        font-size: clamp(1.7rem, 2.8vw, 2.5rem);
        font-weight: 700;
        color: #fff;
        margin-bottom: 6px;
    }

    .shelf-sub {
        font-size: .85rem;
        color: rgba(255, 255, 255, .35);
    }

    .shelf-track {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 8px 2px 24px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .shelf-track::-webkit-scrollbar {
        display: none;
    }

    /* shelf floor */
    .shelf-floor {
        margin: 0 7% 0 0;
        height: 8px;
        background: linear-gradient(90deg, #2d1b0a, #4a2e14, #2d1b0a);
        border-radius: 0 4px 4px 0;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .4);
        position: relative;
        z-index: 1;
    }

    .shbk {
        flex-shrink: 0;
        cursor: pointer;
        position: relative;
        transform-origin: bottom center;
        transition: transform .25s;
    }

    .shbk:hover {
        transform: translateY(-16px);
    }

    .shbk-spine {
        writing-mode: vertical-rl;
        text-orientation: mixed;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 14px 7px;
        border-radius: 3px 8px 8px 3px;
        font-size: .58rem;
        font-weight: 700;
        color: rgba(255, 255, 255, .75);
        letter-spacing: .05em;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        box-shadow: inset -3px 0 6px rgba(0, 0, 0, .25), 2px 0 4px rgba(0, 0, 0, .2);
        width: 40px;
    }

    .shbk-dot {
        position: absolute;
        bottom: 4px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        border: 1.5px solid rgba(255, 255, 255, .2);
    }

    /* ──────────── KATEGORI ──────────── */
    .kat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 13px;
    }

    .kat {
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 20px 16px;
        display: flex;
        align-items: center;
        gap: 13px;
        transition: all .25s;
        cursor: pointer;
        box-shadow: var(--sh0);
    }

    .kat:hover {
        border-color: var(--kc, var(--blue));
        transform: translateY(-3px);
        box-shadow: var(--sh1);
    }

    .kat:hover .kat-ico {
        transform: scale(1.1);
    }

    .kat-ico {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        transition: transform .25s;
    }

    .kat-name {
        font-size: .83rem;
        font-weight: 700;
        color: var(--ink);
    }

    .kat-count {
        font-size: .68rem;
        color: var(--subtle);
        margin-top: 1px;
    }

    /* ──────────── BUKU POPULER ──────────── */
    .pop-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    .popbk {
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        transition: all .25s;
        box-shadow: var(--sh0);
        display: flex;
    }

    .popbk:hover {
        transform: translateY(-4px);
        box-shadow: var(--sh2);
        border-color: rgba(37, 99, 235, .2);
    }

    .popbk-cov {
        width: 82px;
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }

    .popbk-cov img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .popbk-rank {
        position: absolute;
        top: 7px;
        left: 7px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: .6rem;
        font-weight: 800;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .2);
    }

    .rank-1 {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
    }

    .rank-2 {
        background: linear-gradient(135deg, #9ca3af, #d1d5db);
    }

    .rank-3 {
        background: linear-gradient(135deg, #b45309, #d97706);
    }

    .rank-n {
        background: rgba(0, 0, 0, .5);
    }

    .popbk-body {
        padding: 14px 15px;
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .popbk-title {
        font-size: .83rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.35;
        margin-bottom: 3px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .popbk-author {
        font-size: .7rem;
        color: var(--muted);
        margin-bottom: 8px;
    }

    .popbk-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        flex-wrap: wrap;
    }

    .popbk-kat {
        font-size: .63rem;
        padding: 2px 8px;
        border-radius: 9px;
        background: var(--blue-ll);
        color: var(--blue);
        font-weight: 600;
    }

    .popbk-avail {
        font-size: .64rem;
        font-weight: 700;
    }

    .avail-y {
        color: var(--green);
    }

    .avail-n {
        color: var(--rose);
    }

    .popbk-pinjam {
        font-size: .66rem;
        color: var(--subtle);
        display: flex;
        align-items: center;
        gap: 3px;
    }

    /* ──────────── BUKU TERBARU CAROUSEL ──────────── */
    .nbk-outer {
        overflow: hidden;
        margin: 0 -7%;
    }

    .nbk-track {
        display: flex;
        gap: 14px;
        width: max-content;
        animation: slideL 45s linear infinite;
        padding: 6px 56px 16px;
    }

    .nbk-track:hover {
        animation-play-state: paused;
    }

    .nbk {
        width: 146px;
        flex-shrink: 0;
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--sh0);
        transition: all .25s;
    }

    .nbk:hover {
        transform: translateY(-6px);
        box-shadow: var(--sh2);
        border-color: rgba(37, 99, 235, .22);
    }

    .nbk-cov {
        width: 100%;
        aspect-ratio: 2/3;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .nbk-cov img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .nbk-new {
        position: absolute;
        top: 6px;
        right: 6px;
        font-size: .58rem;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 9px;
        background: var(--blue);
        color: #fff;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .nbk-info {
        padding: 9px 11px;
        border-top: 1px solid var(--border2);
    }

    .nbk-title {
        font-size: .72rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 2px;
    }

    .nbk-author {
        font-size: .61rem;
        color: var(--subtle);
    }

    /* ──────────── READING CHALLENGE KOMUNITAS ──────────── */
    .challenge {
        background: linear-gradient(135deg, #f0f4ff, #f6f0ff, #fdf4ff);
        padding: 64px 7%;
        position: relative;
        z-index: 10;
        overflow: hidden;
    }

    .challenge::before {
        content: '';
        position: absolute;
        left: -80px;
        bottom: -80px;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(37, 99, 235, .06) 0%, transparent 65%);
    }

    .challenge-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 48px;
        align-items: center;
    }

    .challenge-left {}

    .challenge-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-top: 28px;
    }

    .cstat {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 20px 18px;
        box-shadow: var(--sh0);
    }

    .cstat-ico {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .cstat-n {
        font-family: 'Lora', serif;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--blue);
        line-height: 1;
    }

    .cstat-l {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--subtle);
        margin-top: 3px;
        font-weight: 600;
    }

    .challenge-right {}

    .ch-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 18px;
        padding: 28px 24px;
        box-shadow: var(--sh1);
    }

    .ch-title {
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--subtle);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ch-prog-row {
        margin-bottom: 18px;
    }

    .ch-prog-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 7px;
    }

    .ch-prog-name {
        font-size: .84rem;
        font-weight: 600;
        color: var(--ink);
    }

    .ch-prog-val {
        font-size: .75rem;
        font-weight: 700;
        color: var(--blue);
    }

    .ch-track {
        height: 9px;
        background: var(--soft);
        border-radius: 9px;
        overflow: hidden;
    }

    .ch-fill {
        height: 100%;
        border-radius: 9px;
        transition: width 1.5s ease;
    }

    .ch-btn {
        width: 100%;
        padding: 12px;
        border-radius: 11px;
        border: none;
        cursor: pointer;
        font-family: 'Sora', sans-serif;
        font-size: .85rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--blue), var(--blue2));
        color: #fff;
        box-shadow: 0 4px 16px rgba(37, 99, 235, .25);
        transition: all .22s;
        margin-top: 18px;
    }

    .ch-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 22px rgba(37, 99, 235, .35);
    }

    /* ──────────── LEADERBOARD ──────────── */
    .leaderboard {
        background: var(--off);
        padding: 80px 7%;
        position: relative;
        z-index: 10;
    }

    .lb-grid {
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        gap: 32px;
        align-items: start;
    }

    .lb-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--sh1);
    }

    .lb-header {
        background: linear-gradient(135deg, var(--blue2), var(--indigo));
        padding: 18px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .lb-htitle {
        font-size: .85rem;
        font-weight: 700;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lb-hbadge {
        font-size: .65rem;
        padding: 3px 10px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .18);
        color: rgba(255, 255, 255, .9);
        font-weight: 600;
    }

    .lb-list {
        padding: 4px 0;
    }

    .lb-row {
        display: grid;
        grid-template-columns: 36px 1fr auto;
        padding: 12px 20px;
        border-bottom: 1px solid var(--border2);
        gap: 12px;
        align-items: center;
        transition: background .15s;
    }

    .lb-row:last-child {
        border-bottom: none;
    }

    .lb-row:hover {
        background: var(--blue-ll);
    }

    .lb-rank {
        font-family: 'JetBrains Mono', monospace;
        font-size: .82rem;
        font-weight: 700;
        text-align: center;
    }

    .rank-gold {
        color: var(--amber);
    }

    .rank-silver {
        color: #6b7280;
    }

    .rank-bronze {
        color: var(--orange);
    }

    .rank-other {
        color: var(--light);
    }

    .lb-av {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }

    .lb-name {
        font-size: .83rem;
        font-weight: 600;
        color: var(--ink);
    }

    .lb-kelas {
        font-size: .68rem;
        color: var(--subtle);
        margin-top: 1px;
    }

    .lb-count {
        text-align: right;
    }

    .lb-num {
        font-family: 'Lora', serif;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--blue);
        line-height: 1;
    }

    .lb-lbl {
        font-size: .64rem;
        color: var(--subtle);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .lb-empty {
        padding: 28px;
        text-align: center;
        font-size: .82rem;
        color: var(--subtle);
    }

    /* Rating card */
    .rat-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--sh1);
    }

    .rat-header {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        padding: 18px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .rat-htitle {
        font-size: .85rem;
        font-weight: 700;
        color: #78350f;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .rat-big {
        padding: 22px 22px;
        display: flex;
        align-items: center;
        gap: 20px;
        border-bottom: 1px solid var(--border);
    }

    .rat-num {
        font-family: 'Lora', serif;
        font-size: 3.2rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1;
    }

    .rat-stars {
        display: flex;
        gap: 3px;
    }

    .rat-stars span {
        color: var(--amber);
        font-size: 1rem;
    }

    .rat-sub {
        font-size: .72rem;
        color: var(--subtle);
        margin-top: 5px;
    }

    .rat-bars {
        padding: 16px 22px;
    }

    .rbar {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 7px;
    }

    .rbar:last-child {
        margin-bottom: 0;
    }

    .rbar-lbl {
        font-size: .7rem;
        font-weight: 600;
        color: var(--muted);
        width: 12px;
        text-align: right;
        flex-shrink: 0;
    }

    .rbar-trk {
        flex: 1;
        height: 7px;
        background: var(--soft);
        border-radius: 7px;
        overflow: hidden;
    }

    .rbar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--amber), #fbbf24);
        border-radius: 7px;
        transition: width .9s ease;
    }

    .rbar-cnt {
        font-size: .66rem;
        color: var(--subtle);
        width: 22px;
        text-align: right;
        flex-shrink: 0;
    }

    .rat-ulasan {
        padding: 0 22px 18px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .rat-ul-item {
        border-left: 2px solid var(--amber-l);
        padding-left: 12px;
    }

    .rat-ul-text {
        font-size: .78rem;
        font-style: italic;
        color: var(--ink2);
        line-height: 1.6;
    }

    .rat-ul-by {
        font-size: .67rem;
        color: var(--subtle);
        margin-top: 3px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* ──────────── JAM & PERATURAN ──────────── */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 28px;
        margin-top: 36px;
    }

    .jb-card {
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--sh1);
    }

    .jb-head {
        background: linear-gradient(135deg, var(--blue2), var(--blue));
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .jb-head-t {
        font-size: .84rem;
        font-weight: 700;
        color: #fff;
    }

    .jb-status {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 3px 11px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .18);
        font-size: .68rem;
        font-weight: 700;
        color: #fff;
    }

    .jb-rows {
        padding: 3px 0;
    }

    .jb-row {
        display: grid;
        grid-template-columns: 1fr auto auto;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border2);
        align-items: center;
        gap: 10px;
        transition: background .15s;
    }

    .jb-row:last-child {
        border-bottom: none;
    }

    .jb-row.today-row {
        background: var(--blue-ll);
    }

    .jb-day {
        font-size: .82rem;
        font-weight: 500;
        color: var(--ink2);
    }

    .jb-day.today {
        font-weight: 700;
        color: var(--blue);
    }

    .jb-time {
        font-size: .78rem;
        color: var(--muted);
    }

    .jb-lbl {
        font-size: .63rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 10px;
    }

    .lbl-open {
        background: var(--green-l);
        color: var(--green);
    }

    .lbl-half {
        background: var(--amber-l);
        color: var(--amber);
    }

    .lbl-closed {
        background: var(--rose-l);
        color: var(--rose);
    }

    .rules-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 11px;
    }

    .rule {
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 13px;
        padding: 18px 16px;
        display: flex;
        gap: 13px;
        align-items: flex-start;
        box-shadow: var(--sh0);
        transition: all .22s;
    }

    .rule:hover {
        border-color: rgba(37, 99, 235, .2);
        box-shadow: var(--sh1);
    }

    .rule-ico {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .rule-h {
        font-size: .84rem;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 4px;
    }

    .rule-p {
        font-size: .77rem;
        color: var(--muted);
        line-height: 1.65;
    }

    /* ──────────── ULASAN ──────────── */
    .ulasan-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .ulasan-card {
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 20px 18px;
        transition: all .22s;
        box-shadow: var(--sh0);
    }

    .ulasan-card:hover {
        border-color: rgba(37, 99, 235, .2);
        box-shadow: var(--sh1);
        transform: translateY(-3px);
    }

    .ulasan-stars {
        display: flex;
        gap: 2px;
        margin-bottom: 10px;
    }

    .ulasan-stars span {
        color: var(--amber);
        font-size: .82rem;
    }

    .ulasan-q {
        position: relative;
        padding-left: 16px;
        margin-bottom: 14px;
    }

    .ulasan-q::before {
        content: '"';
        position: absolute;
        left: 0;
        top: -4px;
        font-family: 'Lora', serif;
        font-size: 1.8rem;
        color: var(--blue);
        opacity: .3;
        line-height: 1;
    }

    .ulasan-text {
        font-size: .82rem;
        color: var(--ink2);
        line-height: 1.72;
        font-style: italic;
    }

    .ulasan-author {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .ulasan-av {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--blue), var(--indigo));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }

    .ulasan-name {
        font-size: .79rem;
        font-weight: 600;
        color: var(--ink);
    }

    .ulasan-buku {
        font-size: .67rem;
        color: var(--subtle);
    }

    /* ──────────── FAQ ──────────── */
    .faq-wrap {
        max-width: 700px;
        margin: 36px auto 0;
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .faq-item {
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--sh0);
        transition: border-color .2s;
    }

    .faq-item.open {
        border-color: rgba(37, 99, 235, .22);
        box-shadow: var(--sh1);
    }

    .faq-q {
        padding: 16px 20px;
        font-size: .87rem;
        font-weight: 600;
        color: var(--ink);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        cursor: pointer;
        transition: background .18s;
    }

    .faq-q:hover {
        background: var(--blue-ll);
    }

    .faq-arr {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        stroke: var(--muted);
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        transition: transform .25s;
    }

    .faq-item.open .faq-arr {
        transform: rotate(180deg);
        stroke: var(--blue);
    }

    .faq-a {
        display: none;
        padding: 0 20px 16px;
        font-size: .82rem;
        color: var(--muted);
        line-height: 1.75;
    }

    .faq-item.open .faq-a {
        display: block;
    }

    /* ──────────── CTA ──────────── */
    .cta-sec {
        margin: 0 7% 88px;
        background: linear-gradient(135deg, #0d1b5e, #1d4ed8 40%, #2563eb 70%, #4f88fb);
        border-radius: 22px;
        padding: 68px 60px;
        position: relative;
        overflow: hidden;
        z-index: 10;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 48px;
        align-items: center;
        box-shadow: 0 20px 60px rgba(37, 99, 235, .26);
    }

    .cta-sec::before {
        content: '📚';
        position: absolute;
        right: 250px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 9rem;
        opacity: .05;
    }

    .cta-sec::after {
        content: '';
        position: absolute;
        right: -60px;
        top: -60px;
        width: 280px;
        height: 280px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .05);
    }

    .cta-h {
        font-family: 'Lora', serif;
        font-size: clamp(1.8rem, 2.8vw, 2.6rem);
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px;
        line-height: 1.12;
    }

    .cta-sub {
        font-size: .9rem;
        color: rgba(255, 255, 255, .55);
        line-height: 1.8;
    }

    .cta-btns {
        display: flex;
        flex-direction: column;
        gap: 9px;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }

    .cta-b1 {
        padding: 13px 36px;
        border-radius: 11px;
        font-size: .88rem;
        font-weight: 700;
        background: #fff;
        color: #1d4ed8;
        text-align: center;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .13);
        transition: all .2s;
    }

    .cta-b1:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 26px rgba(0, 0, 0, .2);
    }

    .cta-b2 {
        padding: 13px 36px;
        border-radius: 11px;
        font-size: .86rem;
        font-weight: 500;
        border: 1.5px solid rgba(255, 255, 255, .28);
        color: rgba(255, 255, 255, .78);
        text-align: center;
        transition: all .2s;
    }

    .cta-b2:hover {
        background: rgba(255, 255, 255, .1);
        border-color: rgba(255, 255, 255, .55);
    }

    /* ──────────── KONTAK ──────────── */
    .kontak-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 32px;
        align-items: start;
    }

    .kontak-items {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 28px;
    }

    .kitem {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        padding: 16px 18px;
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 13px;
        box-shadow: var(--sh0);
        transition: all .22s;
    }

    .kitem:hover {
        border-color: rgba(37, 99, 235, .2);
        box-shadow: var(--sh1);
    }

    .kitem-ico {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .kitem-h {
        font-size: .81rem;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 2px;
    }

    .kitem-v {
        font-size: .78rem;
        color: var(--muted);
        line-height: 1.6;
    }

    .map-card {
        background: var(--w);
        border: 1.5px solid var(--border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--sh1);
    }

    .map-bg {
        height: 280px;
        position: relative;
        overflow: hidden;
        background: linear-gradient(160deg, #e8eeff, #d5e0ff);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .map-grid {
        position: absolute;
        inset: 0;
        background-image: linear-gradient(rgba(37, 99, 235, .07) 1px, transparent 1px), linear-gradient(90deg, rgba(37, 99, 235, .07) 1px, transparent 1px);
        background-size: 28px 28px;
    }

    .map-roads {
        position: absolute;
        inset: 0;
        opacity: .15;
    }

    .map-roads::before {
        content: '';
        position: absolute;
        left: 30%;
        right: 0;
        top: 50%;
        height: 3px;
        background: rgba(37, 99, 235, .6);
        border-radius: 2px;
    }

    .map-roads::after {
        content: '';
        position: absolute;
        top: 25%;
        bottom: 0;
        left: 50%;
        width: 3px;
        background: rgba(37, 99, 235, .6);
        border-radius: 2px;
    }

    .map-pin-wrap {
        position: relative;
        z-index: 1;
        text-align: center;
    }

    .map-pin-ico {
        font-size: 3.5rem;
        animation: bounce 2s ease-in-out infinite;
        display: block;
        line-height: 1;
    }

    .map-pin-label {
        background: #fff;
        border-radius: 10px;
        padding: 8px 16px;
        box-shadow: var(--sh1);
        font-size: .8rem;
        font-weight: 700;
        color: var(--ink);
        margin-top: 10px;
        display: inline-block;
    }

    .map-pin-sub {
        font-size: .7rem;
        color: var(--muted);
        margin-top: 2px;
    }

    .map-footer {
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid var(--border);
    }

    .map-addr {
        font-size: .75rem;
        color: var(--muted);
    }

    .map-link {
        font-size: .78rem;
        font-weight: 700;
        color: var(--blue);
        display: flex;
        align-items: center;
        gap: 5px;
        transition: gap .2s;
    }

    .map-link:hover {
        gap: 8px;
    }

    /* ──────────── FOOTER ──────────── */
    .footer {
        background: var(--ink);
        padding: 56px 7% 0;
        position: relative;
        z-index: 10;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 40px;
        padding-bottom: 44px;
        border-bottom: 1px solid rgba(255, 255, 255, .07);
    }

    .foot-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }

    .foot-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: linear-gradient(135deg, var(--blue), var(--blue2));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
    }

    .foot-brand {
        font-family: 'Lora', serif;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
    }

    .foot-brand span {
        color: #6b9cf8;
    }

    .foot-desc {
        font-size: .78rem;
        color: rgba(255, 255, 255, .32);
        line-height: 1.75;
        margin-bottom: 18px;
    }

    .foot-contacts {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .foot-contact {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: .76rem;
        color: rgba(255, 255, 255, .32);
    }

    .foot-contact svg {
        width: 13px;
        height: 13px;
        stroke: rgba(255, 255, 255, .25);
        fill: none;
        stroke-width: 1.8;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .foot-col-title {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .13em;
        color: rgba(255, 255, 255, .28);
        margin-bottom: 13px;
    }

    .foot-links {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .foot-links a {
        font-size: .78rem;
        color: rgba(255, 255, 255, .35);
        transition: color .2s;
    }

    .foot-links a:hover {
        color: #6b9cf8;
    }

    .footer-bottom {
        padding: 16px 7%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        background: rgba(0, 0, 0, .2);
    }

    .foot-copy {
        font-size: .7rem;
        color: rgba(255, 255, 255, .18);
    }

    .foot-btmr {
        display: flex;
        gap: 16px;
    }

    .foot-btmr a {
        font-size: .7rem;
        color: rgba(255, 255, 255, .18);
        transition: color .2s;
    }

    .foot-btmr a:hover {
        color: rgba(255, 255, 255, .45);
    }

    /* ──────────── REVEAL ──────────── */
    .reveal {
        opacity: 0;
        transform: translateY(22px);
        transition: opacity .6s ease, transform .6s ease;
    }

    .reveal.show {
        opacity: 1;
        transform: none;
    }

    /* ──────────── KEYFRAMES ──────────── */
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(18px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    @keyframes float1 {

        0%,
        100% {
            transform: translate(0, 0)
        }

        50% {
            transform: translate(-18px, 22px)
        }
    }

    @keyframes float2 {

        0%,
        100% {
            transform: translate(0, 0)
        }

        50% {
            transform: translate(16px, -18px)
        }
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .25
        }
    }

    @keyframes spin {
        to {
            transform: rotate(360deg)
        }
    }

    @keyframes slideL {
        from {
            transform: translateX(0)
        }

        to {
            transform: translateX(calc(-50% - 7px))
        }
    }

    @keyframes bookFloat {

        0%,
        100% {
            transform: rotateY(-5deg) rotateX(3deg)
        }

        50% {
            transform: rotateY(5deg) rotateX(-2deg)
        }
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-8px)
        }
    }

    /* ──────────── RESPONSIVE ──────────── */
    @media(max-width:1100px) {
        .hero {
            grid-template-columns: 1fr;
        }

        .hero-right {
            display: none;
        }

        .kat-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .pop-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .footer-grid {
            grid-template-columns: 1fr 1fr;
        }

        .info-strip {
            grid-template-columns: repeat(2, 1fr);
        }

        .istrip:nth-child(2) {
            border-right: none;
        }

        .lb-grid {
            grid-template-columns: 1fr;
        }

        .challenge-grid {
            grid-template-columns: 1fr;
        }

        .kontak-grid {
            grid-template-columns: 1fr;
        }

        .cta-sec {
            grid-template-columns: 1fr;
            padding: 52px 36px;
        }

        .cta-sec::before {
            display: none;
        }
    }

    @media(max-width:768px) {

        .nav-links,
        .nav-right {
            display: none;
        }

        .hamburger {
            display: block;
        }

        .kat-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .pop-grid,
        .ulasan-grid {
            grid-template-columns: 1fr;
        }

        .info-grid,
        .rules-grid {
            grid-template-columns: 1fr;
        }

        .info-strip {
            grid-template-columns: 1fr 1fr;
        }

        .istrip {
            border-right: none !important;
            border-bottom: 1px solid var(--border);
        }

        .featured-grid {
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .featured-cover {
            width: 130px;
            height: 185px;
            margin: 0 auto;
        }

        .cta-sec {
            margin: 0 5% 64px;
            padding: 40px 24px;
        }

        .sec {
            padding: 64px 5%;
        }

        .footer-grid {
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .hero-left {
            padding: 40px 5% 48px;
        }

        .challenge-stats {
            grid-template-columns: 1fr 1fr;
        }

        .topbar-right {
            display: none;
        }
    }

    @media(max-width:560px) {
        .hero-nums {
            flex-wrap: wrap;
        }

        .kat-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .info-strip {
            grid-template-columns: 1fr;
        }

        .istrip:nth-child(odd) {
            border-bottom: 1px solid var(--border);
        }

        .mb-stats {
            display: none;
        }
    }
    </style>
</head>

<body>

    <!-- ██ TOP STATUS BAR ██ -->
    <div class="topbar" id="topbar">
        <div class="topbar-left">
            <div class="topbar-item">
                <div class="topbar-dot <?=$buka?'dot-open':'dot-closed'?>"></div>
                <span><?=$buka?'Perpustakaan Buka':'Perpustakaan Tutup'?> · <?=$jam_str?> WIB</span>
            </div>
            <div class="topbar-item">📚 <?=$buku_tersedia?> buku tersedia dari <?=$total_buku?> koleksi</div>
            <?php if($jatuh_tempo>0&&($isAdmin||$isPetugas)):?>
            <div class="topbar-item" style="color:#fca5a5">⚠️ <?=$jatuh_tempo?> buku melewati batas kembali</div>
            <?php endif;?>
        </div>
        <div class="topbar-right">
            <span>📞 (021) 1234-5678</span>
        </div>
    </div>

    <!-- ██ NAV ██ -->
    <nav class="nav" id="nav">
        <a href="index.php" class="nav-logo">
            <div class="nav-icon">📖</div>
            <div class="nav-name">Libra<span>Space</span></div>
        </a>
        <div class="nav-links">
            <a href="#featured">Unggulan</a>
            <a href="#kategori">Kategori</a>
            <a href="#populer">Populer</a>
            <a href="#koleksi">Terbaru</a>
            <a href="#leaderboard">Peringkat</a>
            <a href="#kontak">Kontak</a>
        </div>
        <div class="nav-right">
            <?php if($loggedIn):?>
            <span style="font-size:.78rem;color:var(--muted)">👋 <?=htmlspecialchars($username)?></span>
            <?php if($isAdmin):?><a href="admin/dashboard.php" class="btn-primary">Dashboard Admin</a>
            <?php elseif($isPetugas):?><a href="petugas/dashboard.php" class="btn-primary">Dashboard</a>
            <?php else:?><a href="anggota/dashboard.php" class="btn-primary">Dashboard Saya</a><?php endif;?>
            <?php else:?>
            <a href="login.php" class="btn-outline">Masuk</a>
            <a href="register.php" class="btn-primary">Daftar Gratis</a>
            <?php endif;?>
        </div>
        <button class="hamburger" onclick="document.getElementById('mob').classList.add('open')">☰</button>
    </nav>

    <!-- MOBILE DRAWER -->
    <div class="drawer" id="mob">
        <button class="drawer-x" onclick="document.getElementById('mob').classList.remove('open')">✕</button>
        <a href="#featured">Unggulan</a><a href="#kategori">Kategori</a><a href="#populer">Populer</a>
        <a href="#koleksi">Terbaru</a><a href="#leaderboard">Peringkat</a><a href="#kontak">Kontak</a>
        <?php if($loggedIn):?>
        <?php if($isAdmin):?><a href="admin/dashboard.php" style="color:var(--blue)">Dashboard Admin</a>
        <?php elseif($isPetugas):?><a href="petugas/dashboard.php" style="color:var(--blue)">Dashboard</a>
        <?php else:?><a href="anggota/dashboard.php" style="color:var(--blue)">Dashboard Saya</a><?php endif;?>
        <a href="logout.php">Keluar</a>
        <?php else:?>
        <a href="login.php">Masuk</a>
        <a href="register.php" style="color:var(--blue)">Daftar Gratis</a>
        <?php endif;?>
    </div>

    <!-- ██ HERO ██ -->
    <section class="hero">
        <div class="hero-bg">
            <div class="hero-bg-dots"></div>
            <div class="hero-bg-blob1"></div>
            <div class="hero-bg-blob2"></div>
        </div>

        <div class="hero-left">
            <!-- Quote pill -->
            <div class="hero-quote-pill">
                <div class="hero-quote-ico">💬</div>
                <div>
                    <div class="hero-quote-text"><?=htmlspecialchars($quote[0])?></div>
                    <div class="hero-quote-by">— <?=htmlspecialchars($quote[1])?></div>
                </div>
            </div>

            <div class="hero-tag"><span class="hero-dot"></span>Perpustakaan Digital Modern</div>

            <h1 class="hero-h1">
                Temukan Buku<br>
                <em>Favoritmu</em> &amp;<br>
                <span class="grad">Perluas Wawasanmu</span>
            </h1>

            <p class="hero-desc">Platform perpustakaan sekolah terlengkap. Cari, pinjam, dan kelola buku dengan mudah —
                akses 24/7 dari mana saja.</p>

            <!-- LIVE SEARCH -->
            <div class="search-wrap">
                <div class="search-box" id="searchBox">
                    <div class="search-ico"><svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg></div>
                    <input type="text" class="search-inp" id="searchInp" placeholder="Cari judul, pengarang, atau ISBN…"
                        autocomplete="off">
                    <button class="search-btn" onclick="doSearch()">Cari →</button>
                </div>
                <div class="search-drop" id="searchDrop"></div>
                <div class="search-tags">
                    <?php
        $pop_tags=!empty($kategori)?array_slice(array_column($kategori,'nama_kategori'),0,5):['Fiksi','Sains','Teknologi','Sejarah','Bahasa'];
        foreach($pop_tags as $t):?>
                    <span class="stag" onclick="setSearch('<?=htmlspecialchars($t)?>')"><?=htmlspecialchars($t)?></span>
                    <?php endforeach;?>
                    <span class="stag" onclick="setSearch('Andrea Hirata')">Andrea Hirata</span>
                </div>
            </div>

            <div class="hero-btns">
                <?php if($isAdmin):?><a href="admin/dashboard.php" class="btn-hero">⚡ Dashboard Admin</a>
                <?php elseif($isPetugas):?><a href="petugas/dashboard.php" class="btn-hero">⚡ Dashboard</a>
                <?php elseif($isAnggota):?><a href="anggota/katalog.php" class="btn-hero">📚 Lihat Katalog</a><a
                    href="anggota/pinjam.php" class="btn-hero2">Pinjam Buku</a>
                <?php else:?><a href="register.php" class="btn-hero">✨ Daftar Gratis</a><a href="login.php"
                    class="btn-hero2">Masuk ke Akun</a><?php endif;?>
            </div>

            <div class="hero-nums">
                <div class="hnum">
                    <div class="hnum-n" data-count="<?=$total_buku?>"><?=$total_buku?></div>
                    <div class="hnum-l">Koleksi Buku</div>
                </div>
                <div class="hnum">
                    <div class="hnum-n" data-count="<?=$total_anggota?>"><?=$total_anggota?></div>
                    <div class="hnum-l">Anggota</div>
                </div>
                <div class="hnum">
                    <div class="hnum-n" data-count="<?=$buku_tersedia?>"><?=$buku_tersedia?></div>
                    <div class="hnum-l">Tersedia</div>
                </div>
            </div>
        </div>

        <!-- HERO RIGHT — Featured Book + Widgets -->
        <div class="hero-right">
            <?php if($featured):?>
            <div class="featured-book-3d" onclick="location.href='<?=$isAnggota?'anggota/katalog.php':'login.php'?>'">
                <div class="book-3d">
                    <div class="book-spine"></div>
                    <div class="book-face">
                        <div class="book-badge">⭐ Terpopuler</div>
                        <div class="book-label">Rekomendasi Minggu Ini</div>
                        <div style="font-size:3.5rem;opacity:.15;margin-bottom:12px">📖</div>
                        <div class="book-title"><?=htmlspecialchars(mb_strimwidth($featured['judul_buku'],0,40,'…'))?>
                        </div>
                        <div class="book-author"><?=htmlspecialchars($featured['pengarang'])?></div>
                    </div>
                </div>
            </div>
            <?php endif;?>
            <div class="hero-widgets">
                <div class="hw hw1">
                    <div class="hw-row">
                        <div class="hw-ico" style="background:var(--green-l)">✅</div>
                        <div>
                            <div class="hw-label">Tersedia Sekarang</div>
                            <div class="hw-val"><?=$buku_tersedia?> dari <?=$total_buku?> buku</div>
                        </div>
                    </div>
                    <div class="rc-wrap">
                        <div class="rc-label"><span>Ketersediaan
                                koleksi</span><span><?=$total_buku>0?round($buku_tersedia/$total_buku*100):0?>%</span>
                        </div>
                        <div class="rc-track">
                            <div class="rc-fill"
                                style="width:<?=$total_buku>0?round($buku_tersedia/$total_buku*100):0?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="hw hw2">
                    <div class="hw-row">
                        <div class="hw-ico" style="background:var(--amber-l)">🕐</div>
                        <div>
                            <div class="hw-label">Status Perpustakaan</div>
                            <div class="hw-val" style="color:<?=$buka?'var(--green)':'var(--rose)'?>">
                                <?=$buka?'Sedang Buka 🟢':'Tutup 🔴'?></div>
                            <div class="hw-sub">Jam operasional 07.00–16.00</div>
                        </div>
                    </div>
                </div>
                <div class="hw hw3">
                    <div class="hw-row">
                        <div class="hw-ico" style="background:var(--blue-ll)">📊</div>
                        <div>
                            <div class="hw-label">Pinjaman Bulan Ini</div>
                            <div class="hw-val"><?=$pinjam_bulan_ini?> transaksi</div>
                            <div class="hw-sub">Total kembali: <?=$total_kembali?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ██ INFO STRIP ██ -->
    <div class="info-strip">
        <div class="istrip reveal">
            <div class="istrip-ico" style="background:var(--blue-ll)">📚</div>
            <div>
                <div class="istrip-n" data-count="<?=$total_buku?>"><?=$total_buku?></div>
                <div class="istrip-l">Koleksi Buku</div>
            </div>
        </div>
        <div class="istrip reveal">
            <div class="istrip-ico" style="background:var(--green-l)">✅</div>
            <div>
                <div class="istrip-n" data-count="<?=$buku_tersedia?>"><?=$buku_tersedia?></div>
                <div class="istrip-l">Buku Tersedia</div>
            </div>
        </div>
        <div class="istrip reveal">
            <div class="istrip-ico" style="background:var(--amber-l)">🔄</div>
            <div>
                <div class="istrip-n" data-count="<?=$total_pinjam?>"><?=$total_pinjam?></div>
                <div class="istrip-l">Sedang Dipinjam</div>
            </div>
        </div>
        <div class="istrip reveal">
            <div class="istrip-ico" style="background:var(--violet-l)">👥</div>
            <div>
                <div class="istrip-n" data-count="<?=$total_anggota?>"><?=$total_anggota?></div>
                <div class="istrip-l">Anggota Aktif</div>
            </div>
        </div>
    </div>

    <!-- ██ MEMBER BANNER ██ -->
    <?php if($isAnggota&&$anggota_data):
  $inits=strtoupper(mb_substr($anggota_data['nama_anggota'],0,1).mb_substr(explode(' ',$anggota_data['nama_anggota'])[1]??'',0,1));
?>
    <div class="member-banner">
        <div class="mb-left">
            <div class="mb-av"><?=htmlspecialchars($inits)?></div>
            <div>
                <div class="mb-greet">Selamat datang kembali</div>
                <div class="mb-name"><?=htmlspecialchars($anggota_data['nama_anggota'])?></div>
                <div class="mb-sub">NIS <?=htmlspecialchars($anggota_data['nis'])?> · Kelas
                    <?=htmlspecialchars($anggota_data['kelas'])?></div>
            </div>
        </div>
        <div class="mb-stats">
            <div class="mbstat">
                <div class="mbstat-n"><?=$anggota_data['aktif_pinjam']??0?></div>
                <div class="mbstat-l">Dipinjam</div>
            </div>
            <div class="mbstat">
                <div class="mbstat-n"><?=$anggota_data['total_pinjam']??0?></div>
                <div class="mbstat-l">Total Pinjam</div>
            </div>
            <div class="mbstat">
                <div class="mbstat-n" style="<?=($anggota_data['denda']??0)>0?'color:#fb7185':''?>">
                    <?=($anggota_data['denda']??0)>0?'Rp'.number_format($anggota_data['denda'],0,',','.'):'Nihil'?>
                </div>
                <div class="mbstat-l">Denda</div>
            </div>
        </div>
        <div class="mb-btns">
            <a href="anggota/katalog.php" class="mbb mbb-w">📚 Katalog</a>
            <a href="anggota/pinjam.php" class="mbb mbb-g">Pinjam Buku</a>
            <a href="anggota/dashboard.php" class="mbb mbb-g">Dashboard →</a>
        </div>
    </div>
    <?php endif;?>
    <?php if(($isAdmin||$isPetugas)&&$jatuh_tempo>0):?>
    <div class="alert-jt">
        <span>⚠️</span>
        <span style="font-size:.84rem;color:var(--ink2)">Ada <strong><?=$jatuh_tempo?> buku</strong> yang sudah melewati
            batas pengembalian.</span>
        <a href="<?=$isAdmin?'admin':'petugas'?>/transaksi.php" class="alert-jt-link">Tindak Lanjut →</a>
    </div>
    <?php endif;?>

    <!-- ██ FEATURED BOOK OF THE WEEK ██ -->
    <?php if($featured):?>
    <section class="featured-sec" id="featured">
        <div class="sec-pill">Rekomendasi Minggu Ini</div>
        <h2 class="sec-h">Buku <em>Pilihan Editor</em></h2>
        <p class="sec-sub" style="margin-bottom:32px">Dipilih berdasarkan popularitas dan ulasan terbaik dari anggota
            perpustakaan.</p>
        <div class="featured-grid reveal">
            <div class="featured-cover">
                <?php if(!empty($featured['cover'])&&file_exists($featured['cover'])):?>
                <img src="<?=htmlspecialchars($featured['cover'])?>"
                    alt="<?=htmlspecialchars($featured['judul_buku'])?>">
                <?php else:?>
                <div class="featured-cover-bg">📖</div>
                <?php endif;?>
                <div class="featured-star">⭐ Pilihan</div>
            </div>
            <div class="featured-info">
                <div class="featured-genre"><?=htmlspecialchars($featured['nama_kategori']??'Umum')?></div>
                <div class="featured-title"><?=htmlspecialchars($featured['judul_buku'])?></div>
                <div class="featured-author">oleh <?=htmlspecialchars($featured['pengarang'])?></div>
                <div class="featured-stars">
                    <?php $fr=round($avg_rating>0?$avg_rating:4.5); for($s=1;$s<=5;$s++) echo '<span>'.($s<=$fr?'★':'☆').'</span>';?>
                    <em><?=number_format($avg_rating>0?$avg_rating:4.5,1)?>/5.0 (<?=$total_ulasan?> ulasan)</em>
                </div>
                <?php if(!empty($featured['deskripsi'])):?>
                <div class="featured-desc"><?=htmlspecialchars(mb_strimwidth($featured['deskripsi'],0,220,'…'))?></div>
                <?php else:?>
                <div class="featured-desc">Salah satu koleksi terbaik perpustakaan yang paling banyak dipinjam oleh
                    anggota. Buku ini sangat direkomendasikan untuk memperkaya wawasan dan pengetahuan kamu.</div>
                <?php endif;?>
                <div class="featured-meta">
                    <?php if(!empty($featured['penerbit'])):?><div class="fmeta">🏢
                        <strong><?=htmlspecialchars($featured['penerbit'])?></strong>
                    </div><?php endif;?>
                    <?php if(!empty($featured['tahun_terbit'])):?><div class="fmeta">📅
                        <strong><?=htmlspecialchars($featured['tahun_terbit'])?></strong>
                    </div><?php endif;?>
                    <div class="fmeta">📦 Status: <strong
                            style="color:<?=$featured['status']==='tersedia'?'var(--green)':'var(--rose)'?>"><?=$featured['status']==='tersedia'?'Tersedia':'Dipinjam'?></strong>
                    </div>
                </div>
                <a href="<?=$isAnggota?'anggota/pinjam.php':'login.php'?>" class="featured-btn">
                    <?=$isAnggota?'📚 Pinjam Sekarang':'🔒 Login untuk Meminjam'?> →
                </a>
            </div>
        </div>
    </section>
    <?php endif;?>

    <!-- ██ VISUAL BOOK SHELF ██ -->
    <div class="shelf-sec">
        <div class="shelf-hd">
            <div class="shelf-pill">Koleksi Visual</div>
            <h2 class="shelf-h">Rak <em style="color:#6b9cf8">Perpustakaan</em></h2>
            <p class="shelf-sub">Hover untuk melihat buku. 🟢 Tersedia · 🔴 Sedang Dipinjam</p>
        </div>
        <div class="shelf-track" id="shelfTrack">
            <?php
    $sc=['#c0392b','#2980b9','#27ae60','#8e44ad','#e67e22','#16a085','#2c3e50','#1abc9c','#d35400','#7f8c8d','#2ecc71','#3498db','#e74c3c','#9b59b6','#f39c12','#0097a7','#6d4c41','#455a64','#558b2f','#ad1457'];
    $sh_books=$buku_baru; if(empty($sh_books)) $sh_books=[['judul_buku'=>'Laskar Pelangi','status'=>'tersedia'],['judul_buku'=>'Bumi Manusia','status'=>'tidak'],['judul_buku'=>'Pemrograman PHP','status'=>'tersedia'],['judul_buku'=>'Matematika XII','status'=>'tersedia'],['judul_buku'=>'Fisika Dasar','status'=>'tidak'],['judul_buku'=>'Sejarah Indonesia','status'=>'tersedia'],['judul_buku'=>'Sang Pemimpi','status'=>'tersedia'],['judul_buku'=>'Negeri 5 Menara','status'=>'tersedia'],['judul_buku'=>'5 CM','status'=>'tidak'],['judul_buku'=>'Perahu Kertas','status'=>'tersedia']];
    while(count($sh_books)<20) $sh_books=array_merge($sh_books,$sh_books);
    $heights=[140,160,148,170,138,155,144,168,142,158,136,162,150,145,165,140,158,148,170,152];
    foreach(array_slice($sh_books,0,20) as $i=>$b):
      $h=$heights[$i%20]; $col=$sc[$i%20]; $avail=($b['status']==='tersedia');
    ?>
            <div class="shbk" title="<?=htmlspecialchars($b['judul_buku'])?>"
                onclick="location.href='<?=$isAnggota?'anggota/katalog.php':'login.php'?>'">
                <div class="shbk-spine"
                    style="height:<?=$h?>px;background:linear-gradient(90deg,<?=$col?>cc,<?=$col?>ff)">
                    <?=htmlspecialchars(mb_substr($b['judul_buku'],0,18))?>
                </div>
                <div class="shbk-dot" style="background:<?=$avail?'#34d399':'#fb7185'?>"></div>
            </div>
            <?php endforeach;?>
        </div>
        <div class="shelf-floor" style="margin-right:7%"></div>
    </div>

    <!-- ██ KATEGORI ██ -->
    <section class="sec alt" id="kategori">
        <div class="sec-hd reveal">
            <div class="sec-lft">
                <div class="sec-pill">Jelajahi</div>
                <h2 class="sec-h">Kategori <em>Buku</em></h2>
                <p class="sec-sub">Temukan buku sesuai minat dan kebutuhanmu.</p>
            </div>
        </div>
        <div class="kat-grid">
            <?php
    $kd=['Fiksi'=>['📖','#2563eb','#eff6ff'],'Non-Fiksi'=>['📰','#0891b2','#ecfeff'],'Pelajaran'=>['🎓','#059669','#ecfdf5'],'Referensi'=>['📕','#d97706','#fffbeb'],'Teknologi'=>['💻','#7c3aed','#f5f3ff'],'Sains'=>['🔬','#0ea5e9','#f0f9ff'],'Agama'=>['🕌','#ea580c','#fff7ed'],'Biografi'=>['👤','#db2777','#fdf2f8'],'default'=>['📚','#2563eb','#eff6ff']];
    $kat_show=!empty($kategori)?$kategori:[['nama_kategori'=>'Fiksi','jml'=>12],['nama_kategori'=>'Non-Fiksi','jml'=>8],['nama_kategori'=>'Pelajaran','jml'=>15],['nama_kategori'=>'Teknologi','jml'=>9],['nama_kategori'=>'Sains','jml'=>7],['nama_kategori'=>'Referensi','jml'=>5],['nama_kategori'=>'Agama','jml'=>6],['nama_kategori'=>'Biografi','jml'=>4]];
    foreach(array_slice($kat_show,0,8) as $idx=>$k):
      $kn=$k['nama_kategori']; $d=$kd[$kn]??$kd['default'];
    ?>
            <a href="<?=$isAnggota?'anggota/katalog.php?kategori='.($k['id_kategori']??''):'login.php'?>"
                class="kat reveal" style="transition-delay:<?=$idx*.05?>s;--kc:<?=$d[1]?>">
                <div class="kat-ico" style="background:<?=$d[2]?>"><?=$d[0]?></div>
                <div>
                    <div class="kat-name"><?=htmlspecialchars($kn)?></div>
                    <div class="kat-count"><?=$k['jml']?> buku</div>
                </div>
            </a>
            <?php endforeach;?>
        </div>
    </section>

    <!-- ██ BUKU POPULER ██ -->
    <section class="sec" id="populer">
        <div class="sec-hd reveal">
            <div class="sec-lft">
                <div class="sec-pill">Pilihan Pembaca</div>
                <h2 class="sec-h">Buku <em>Terpopuler</em></h2>
                <p class="sec-sub">Paling banyak dipinjam oleh anggota perpustakaan.</p>
            </div>
            <a href="<?=$isAnggota?'anggota/katalog.php':'login.php'?>" class="sec-link">Lihat semua →</a>
        </div>
        <div class="pop-grid">
            <?php
    $pc=['135deg,#dde8ff,#b8ccff','135deg,#d4f0e8,#a8e0cc','135deg,#ffe0dc,#ffbdb6','135deg,#fff0cc,#ffd880','135deg,#ecdeff,#d4b8ff','135deg,#ccf0f8,#99ddf0'];
    $pe=['📘','📗','📕','📙','📓','📔'];
    $books_p=!empty($buku_pop)?$buku_pop:[['judul_buku'=>'Laskar Pelangi','pengarang'=>'Andrea Hirata','cover'=>'','status'=>'tersedia','nama_kategori'=>'Fiksi','jml_pinjam'=>24],['judul_buku'=>'Bumi Manusia','pengarang'=>'Pramoedya Ananta Toer','cover'=>'','status'=>'tidak','nama_kategori'=>'Fiksi','jml_pinjam'=>18],['judul_buku'=>'Pemrograman PHP','pengarang'=>'Rizky Abdulah','cover'=>'','status'=>'tersedia','nama_kategori'=>'Teknologi','jml_pinjam'=>15],['judul_buku'=>'Sejarah Indonesia','pengarang'=>'M.C. Ricklefs','cover'=>'','status'=>'tersedia','nama_kategori'=>'Sejarah','jml_pinjam'=>12],['judul_buku'=>'Matematika XII','pengarang'=>'Kemendikbud','cover'=>'','status'=>'tersedia','nama_kategori'=>'Pelajaran','jml_pinjam'=>10],['judul_buku'=>'Fisika Dasar','pengarang'=>'Halliday','cover'=>'','status'=>'tidak','nama_kategori'=>'Sains','jml_pinjam'=>8]];
    $rank_cls=['rank-1','rank-2','rank-3','rank-n','rank-n','rank-n'];
    foreach(array_slice($books_p,0,6) as $i=>$b):
    ?>
            <div class="popbk reveal" style="transition-delay:<?=$i*.08?>s">
                <div class="popbk-cov" style="background:linear-gradient(<?=$pc[$i%6]?>)">
                    <?php if(!empty($b['cover'])&&file_exists($b['cover'])):?><img
                        src="<?=htmlspecialchars($b['cover'])?>" alt=""><?php else:?><?=$pe[$i%6]?><?php endif;?>
                    <div class="popbk-rank <?=$rank_cls[$i]?>">#<?=$i+1?></div>
                </div>
                <div class="popbk-body">
                    <div>
                        <div class="popbk-title"><?=htmlspecialchars($b['judul_buku'])?></div>
                        <div class="popbk-author"><?=htmlspecialchars($b['pengarang'])?></div>
                    </div>
                    <div class="popbk-foot">
                        <span class="popbk-kat"><?=htmlspecialchars($b['nama_kategori']??'Umum')?></span>
                        <span
                            class="popbk-avail <?=$b['status']==='tersedia'?'avail-y':'avail-n'?>"><?=$b['status']==='tersedia'?'● Tersedia':'○ Dipinjam'?></span>
                    </div>
                    <?php if(!empty($b['jml_pinjam'])):?>
                    <div class="popbk-pinjam">🔄 <?=$b['jml_pinjam']?> kali dipinjam</div>
                    <?php endif;?>
                </div>
            </div>
            <?php endforeach;?>
        </div>
    </section>

    <!-- ██ BUKU TERBARU ██ -->
    <section class="sec alt" id="koleksi">
        <div class="sec-hd reveal">
            <div class="sec-lft">
                <div class="sec-pill">Koleksi Terbaru</div>
                <h2 class="sec-h">Baru <em>Ditambahkan</em></h2>
                <p class="sec-sub">Buku-buku yang baru masuk ke koleksi perpustakaan.</p>
            </div>
            <a href="<?=$isAnggota?'anggota/katalog.php':'login.php'?>" class="sec-link">Lihat semua →</a>
        </div>
        <div class="nbk-outer reveal">
            <div class="nbk-track">
                <?php
      $nc=['135deg,#dde8ff,#b8ccff','135deg,#d4f0e8,#a8e0cc','135deg,#ffe0dc,#ffbdb6','135deg,#fff0cc,#ffd880','135deg,#ecdeff,#d4b8ff','135deg,#ccf0f8,#99ddf0'];
      $ne=['📘','📗','📕','📙','📓','📔'];
      $nb=$buku_baru; if(empty($nb)) for($i=0;$i<8;$i++) $nb[]=['judul_buku'=>'Judul Buku '.($i+1),'pengarang'=>'Pengarang','cover'=>''];
      $nbd=array_merge($nb,$nb);
      foreach($nbd as $i=>$b): $ci=$i%6;?>
                <div class="nbk">
                    <div class="nbk-cov" style="background:linear-gradient(<?=$nc[$ci]?>)">
                        <?php if(!empty($b['cover'])&&file_exists($b['cover'])):?><img
                            src="<?=htmlspecialchars($b['cover'])?>" alt=""><?php else:?><?=$ne[$ci]?><?php endif;?>
                        <?php if($i<count($buku_baru)):?><span class="nbk-new">Baru</span><?php endif;?>
                    </div>
                    <div class="nbk-info">
                        <div class="nbk-title"><?=htmlspecialchars($b['judul_buku'])?></div>
                        <div class="nbk-author"><?=htmlspecialchars($b['pengarang'])?></div>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </div>
    </section>

    <!-- ██ READING CHALLENGE KOMUNITAS ██ -->
    <section class="challenge">
        <div class="challenge-grid">
            <div>
                <div class="sec-pill">Komunitas</div>
                <h2 class="sec-h">Reading <em>Challenge</em></h2>
                <p class="sec-sub">Target membaca komunitas perpustakaan tahun ini. Bergabung dan raih pencapaianmu!</p>
                <div class="challenge-stats">
                    <div class="cstat reveal">
                        <div class="cstat-ico">📚</div>
                        <div class="cstat-n" data-count="<?=$total_kembali?>"><?=$total_kembali?></div>
                        <div class="cstat-l">Buku Selesai Dibaca</div>
                    </div>
                    <div class="cstat reveal">
                        <div class="cstat-ico">👥</div>
                        <div class="cstat-n" data-count="<?=$total_anggota?>"><?=$total_anggota?></div>
                        <div class="cstat-l">Pembaca Aktif</div>
                    </div>
                    <div class="cstat reveal">
                        <div class="cstat-ico">⭐</div>
                        <div class="cstat-n"><?=number_format($avg_rating>0?$avg_rating:4.5,1)?></div>
                        <div class="cstat-l">Rata-rata Rating</div>
                    </div>
                    <div class="cstat reveal">
                        <div class="cstat-ico">🏆</div>
                        <div class="cstat-n"><?=!empty($leaderboard)?$leaderboard[0]['jml']??0:0?></div>
                        <div class="cstat-l">Rekor Pinjaman</div>
                    </div>
                </div>
            </div>
            <div class="reveal">
                <div class="ch-card">
                    <div class="ch-title">🎯 Progress Challenge 2025</div>
                    <?php
        $target=200; $done=max($total_kembali,0);
        $pct_done=min(100,round($done/$target*100));
        $targets=[
          ['Buku Terbaca Komunitas',$done,$target,'linear-gradient(90deg,#2563eb,#60a5fa)'],
          ['Anggota Aktif Bergabung',$total_anggota,50,'linear-gradient(90deg,#059669,#34d399)'],
          ['Ulasan Ditulis',$total_ulasan,100,'linear-gradient(90deg,#d97706,#fbbf24)'],
        ];
        foreach($targets as $t):
          $pct=min(100,round(($t[1]/$t[2])*100));
        ?>
                    <div class="ch-prog-row">
                        <div class="ch-prog-head">
                            <span class="ch-prog-name"><?=$t[0]?></span>
                            <span class="ch-prog-val"><?=$t[1]?> / <?=$t[2]?></span>
                        </div>
                        <div class="ch-track">
                            <div class="ch-fill" style="width:<?=$pct?>%;background:<?=$t[3]?>"></div>
                        </div>
                    </div>
                    <?php endforeach;?>
                    <button class="ch-btn"
                        onclick="location.href='<?=$isAnggota?'anggota/pinjam.php':'register.php'?>'">
                        <?=$isAnggota?'Ikut Challenge — Pinjam Buku':'Daftar &amp; Ikut Challenge'?> 🚀
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ██ LEADERBOARD + RATING ██ -->
    <section class="leaderboard sec" id="leaderboard">
        <div class="sec-hd reveal">
            <div class="sec-lft">
                <div class="sec-pill">Papan Peringkat</div>
                <h2 class="sec-h">Pembaca <em>Paling Aktif</em></h2>
                <p class="sec-sub">Anggota dengan jumlah pinjaman terbanyak bulan ini.</p>
            </div>
        </div>
        <div class="lb-grid">
            <div class="lb-card reveal">
                <div class="lb-header">
                    <div class="lb-htitle">🏆 Top Pembaca</div>
                    <div class="lb-hbadge">All Time</div>
                </div>
                <div class="lb-list">
                    <?php
        $medal_cls=['rank-gold','rank-silver','rank-bronze'];
        $av_colors=['#2563eb','#7c3aed','#059669','#d97706','#e11d48'];
        $lb_show=!empty($leaderboard)?$leaderboard:[['nama_anggota'=>'Budi Santoso','kelas'=>'XII RPL','jml'=>24],['nama_anggota'=>'Siti Rahayu','kelas'=>'XI TKJ','jml'=>18],['nama_anggota'=>'Andi Pratama','kelas'=>'X MM','jml'=>12]];
        if(empty($lb_show)):?>
                    <div class="lb-empty">Belum ada data pinjaman</div>
                    <?php else: foreach($lb_show as $ri=>$lb):
          $rc=$ri<3?$medal_cls[$ri]:'rank-other';
          $medals=$ri<3?['🥇','🥈','🥉'][$ri]:('#'.($ri+1));
          $lbinit=strtoupper(mb_substr($lb['nama_anggota'],0,1).mb_substr(explode(' ',$lb['nama_anggota'])[1]??'',0,1));
        ?>
                    <div class="lb-row">
                        <div class="lb-rank <?=$rc?>"><?=$medals?></div>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="lb-av" style="background:<?=$av_colors[$ri%5]?>"><?=htmlspecialchars($lbinit)?>
                            </div>
                            <div>
                                <div class="lb-name"><?=htmlspecialchars($lb['nama_anggota'])?></div>
                                <div class="lb-kelas"><?=htmlspecialchars($lb['kelas']??'—')?></div>
                            </div>
                        </div>
                        <div class="lb-count">
                            <div class="lb-num"><?=$lb['jml']?></div>
                            <div class="lb-lbl">Pinjaman</div>
                        </div>
                    </div>
                    <?php endforeach; endif;?>
                </div>
            </div>

            <div class="rat-card reveal">
                <div class="rat-header">
                    <div class="rat-htitle">⭐ Rating & Ulasan</div>
                </div>
                <div class="rat-big">
                    <div>
                        <div class="rat-num"><?=number_format($avg_rating>0?$avg_rating:4.5,1)?></div>
                        <div class="rat-stars">
                            <?php $ar=round($avg_rating>0?$avg_rating:4.5); for($s=1;$s<=5;$s++) echo '<span>'.($s<=$ar?'★':'☆').'</span>';?>
                        </div>
                        <div class="rat-sub">dari <?=$total_ulasan?> ulasan</div>
                    </div>
                    <div style="flex:1">
                        <?php
          $rd=[5=>0,4=>0,3=>0,2=>0,1=>0];
          $rdr=$conn->query("SELECT rating,COUNT(*) c FROM ulasan_buku GROUP BY rating");
          if($rdr) while($r=$rdr->fetch_assoc()) $rd[(int)$r['rating']]=(int)$r['c'];
          if($total_ulasan==0) $rd=[5=>12,4=>8,3=>4,2=>2,1=>1];
          $mx=max(array_values($rd));
          ?>
                        <div class="rat-bars">
                            <?php for($st=5;$st>=1;$st--):
            $cnt=$rd[$st]??0; $pct=$mx>0?round($cnt/$mx*100):0;
          ?>
                            <div class="rbar">
                                <div class="rbar-lbl"><?=$st?></div>
                                <div class="rbar-trk">
                                    <div class="rbar-fill" style="width:<?=$pct?>%"></div>
                                </div>
                                <div class="rbar-cnt"><?=$cnt?></div>
                            </div>
                            <?php endfor;?>
                        </div>
                    </div>
                </div>
                <?php if(!empty($ulasan_arr)):?>
                <div class="rat-ulasan">
                    <?php foreach(array_slice($ulasan_arr,0,3) as $u):
          $uinit=strtoupper(mb_substr($u['nama_anggota'],0,1).mb_substr(explode(' ',$u['nama_anggota'])[1]??'',0,1));
        ?>
                    <div class="rat-ul-item">
                        <div class="rat-ul-text">"<?=htmlspecialchars(mb_strimwidth($u['ulasan'],0,100,'…'))?>"</div>
                        <div class="rat-ul-by">
                            <div class="ulasan-av" style="width:22px;height:22px;font-size:.6rem">
                                <?=htmlspecialchars($uinit)?></div>
                            <?=htmlspecialchars($u['nama_anggota'])?> ·
                            <?=htmlspecialchars(mb_strimwidth($u['judul_buku'],0,28,'…'))?>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
                <?php endif;?>
            </div>
        </div>
    </section>

    <!-- ██ ULASAN ██ -->
    <?php if(!empty($ulasan_arr)||true):?>
    <section class="sec alt">
        <div class="sec-hd reveal">
            <div class="sec-lft">
                <div class="sec-pill">Kata Pembaca</div>
                <h2 class="sec-h">Ulasan <em>Terbaru</em></h2>
                <p class="sec-sub">Pendapat jujur dari anggota tentang buku yang mereka baca.</p>
            </div>
        </div>
        <div class="ulasan-grid">
            <?php
    $uls=!empty($ulasan_arr)?$ulasan_arr:[
      ['nama_anggota'=>'Budi Santoso','judul_buku'=>'Laskar Pelangi','pengarang'=>'Andrea Hirata','rating'=>5,'ulasan'=>'Sistem peminjaman sangat mudah dan cepat! Bisa akses katalog dari rumah tanpa perlu ke perpustakaan dulu.'],
      ['nama_anggota'=>'Siti Rahayu','judul_buku'=>'Bumi Manusia','pengarang'=>'Pramoedya Ananta Toer','rating'=>5,'ulasan'=>'Pengingat jatuh tempo sangat membantu. Tidak pernah terlambat lagi setelah pakai LibraSpace!'],
      ['nama_anggota'=>'Andi Pratama','judul_buku'=>'Pemrograman PHP','pengarang'=>'Rizky Abdulah','rating'=>4,'ulasan'=>'Interface yang intuitif dan modern. Fitur kategori memudahkan pencarian buku yang relevan.'],
      ['nama_anggota'=>'Dewi Lestari','judul_buku'=>'Fisika Dasar','pengarang'=>'Halliday','rating'=>5,'ulasan'=>'Tampilan web yang cantik dan informatif. Info ketersediaan buku real-time sangat berguna!'],
      ['nama_anggota'=>'Reza Pahlawan','judul_buku'=>'Sejarah Indonesia','pengarang'=>'M.C. Ricklefs','rating'=>4,'ulasan'=>'Fitur riwayat peminjaman membantu saya melacak semua buku yang pernah dibaca. Keren!'],
      ['nama_anggota'=>'Nurul Hidayah','judul_buku'=>'Matematika XII','pengarang'=>'Kemendikbud','rating'=>5,'ulasan'=>'Proses daftar hingga bisa pinjam buku sangat cepat. Perpustakaan digital terbaik!'],
    ];
    foreach(array_slice($uls,0,6) as $idx=>$u):
      $stars=$u['rating']??5;
      $nm=$u['nama_anggota'];
      $init=strtoupper(mb_substr($nm,0,1).mb_substr(explode(' ',$nm)[1]??'',0,1));
    ?>
            <div class="ulasan-card reveal" style="transition-delay:<?=$idx*.07?>s">
                <div class="ulasan-stars"><?php for($s=1;$s<=5;$s++) echo '<span>'.($s<=$stars?'★':'☆').'</span>';?>
                </div>
                <div class="ulasan-q">
                    <div class="ulasan-text"><?=htmlspecialchars(mb_strimwidth($u['ulasan'],0,120,'…'))?></div>
                </div>
                <div class="ulasan-author">
                    <div class="ulasan-av"><?=htmlspecialchars($init)?></div>
                    <div>
                        <div class="ulasan-name"><?=htmlspecialchars($nm)?></div>
                        <div class="ulasan-buku">📖 <?=htmlspecialchars(mb_strimwidth($u['judul_buku'],0,36,'…'))?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach;?>
        </div>
    </section>
    <?php endif;?>

    <!-- ██ JAM BUKA + PERATURAN ██ -->
    <section class="sec">
        <div class="sec-hd reveal">
            <div class="sec-lft">
                <div class="sec-pill">Informasi</div>
                <h2 class="sec-h">Jam Buka &amp; <em>Peraturan</em></h2>
                <p class="sec-sub">Patuhi peraturan agar layanan berjalan lancar untuk semua.</p>
            </div>
        </div>
        <div class="info-grid">
            <div class="jb-card reveal">
                <div class="jb-head">
                    <div class="jb-head-t">🕐 Jam Operasional</div>
                    <div class="jb-status">
                        <div class="topbar-dot <?=$buka?'dot-open':'dot-closed'?>"></div><?=$buka?'Buka':'Tutup'?>
                    </div>
                </div>
                <div class="jb-rows">
                    <?php
        $jadwal=[['Senin','07.00–16.00','open'],['Selasa','07.00–16.00','open'],['Rabu','07.00–16.00','open'],['Kamis','07.00–16.00','open'],['Jumat','07.00–11.30','half'],['Sabtu','08.00–13.00','half'],['Minggu','Tutup','closed']];
        $hari_id=['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
        $hr=$hari_id[$hari-1];
        foreach($jadwal as $j):
          $isT=($j[0]===$hr);
          $lc=$j[2]==='open'?'lbl-open':($j[2]==='half'?'lbl-half':'lbl-closed');
          $ll=$j[2]==='open'?'Buka':($j[2]==='half'?'Setengah':'Tutup');
        ?>
                    <div class="jb-row <?=$isT?'today-row':''?>">
                        <span
                            class="jb-day <?=$isT?'today':''?>"><?=$j[0]?><?=$isT?' <em style="font-size:.65rem;font-style:normal">(Hari Ini)</em>':''?></span>
                        <span class="jb-time"><?=$j[1]?></span>
                        <span class="jb-lbl <?=$lc?>"><?=$ll?></span>
                    </div>
                    <?php endforeach;?>
                </div>
            </div>
            <div class="rules-grid">
                <?php $rules=[['📋','var(--blue-ll)','Masa Pinjam 7 Hari','Buku dikembalikan dalam 7 hari kalender sejak tanggal peminjaman.'],['💰','var(--rose-l)','Denda Rp 1.000/Hari','Keterlambatan dikenakan denda per hari per buku yang terlambat.'],['📖','var(--green-l)','Maks. 3 Buku','Setiap anggota hanya boleh meminjam 3 buku secara bersamaan.'],['🚫','var(--amber-l)','Jaga Kondisi Buku','Buku rusak atau hilang wajib diganti oleh peminjam.']];
      foreach($rules as $r):?>
                <div class="rule reveal">
                    <div class="rule-ico" style="background:<?=$r[1]?>"><?=$r[0]?></div>
                    <div>
                        <div class="rule-h"><?=$r[2]?></div>
                        <div class="rule-p"><?=$r[3]?></div>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </div>
    </section>

    <!-- ██ FAQ ██ -->
    <section class="sec alt" style="padding-top:64px;padding-bottom:72px">
        <div style="text-align:center;margin-bottom:32px" class="reveal">
            <div class="sec-pill" style="justify-content:center">Bantuan</div>
            <h2 class="sec-h">Pertanyaan <em>Umum</em></h2>
            <p class="sec-sub" style="margin:10px auto 0">Jawaban untuk pertanyaan yang paling sering ditanyakan.</p>
        </div>
        <div class="faq-wrap">
            <?php $faqs=[
      ['Bagaimana cara mendaftar sebagai anggota perpustakaan?','Klik tombol "Daftar Gratis" di halaman utama, isi formulir dengan NIS, nama lengkap, kelas, username, dan password. Setelah mendaftar, akun langsung aktif dan siap digunakan untuk meminjam buku.'],
      ['Berapa lama masa peminjaman buku?','Masa peminjaman adalah 7 hari kalender terhitung dari tanggal pinjam. Lewat dari batas waktu tersebut, akan dikenakan denda Rp 1.000 per hari per buku.'],
      ['Berapa buku yang boleh dipinjam sekaligus?','Setiap anggota dapat meminjam maksimal 3 buku sekaligus. Peminjaman buku baru bisa dilakukan setelah salah satu buku dikembalikan.'],
      ['Bagaimana cara mengembalikan buku?','Login ke akun kamu, masuk ke menu "Kembalikan Buku", pilih buku yang ingin dikembalikan, lalu bawa buku ke perpustakaan. Petugas akan memproses pengembalian dan memperbarui status di sistem.'],
      ['Bagaimana cara membayar denda keterlambatan?','Denda dibayarkan langsung ke petugas perpustakaan saat pengembalian buku. Jumlah denda otomatis dihitung oleh sistem, dan kamu akan mendapat struk pembayaran dari petugas.'],
      ['Apakah saya bisa memberikan ulasan untuk buku yang dipinjam?','Ya! Setelah mengembalikan buku, kamu bisa memberikan rating bintang 1–5 dan menulis ulasan. Ulasanmu akan membantu anggota lain menemukan buku yang tepat.'],
    ];
    foreach($faqs as $i=>$f):?>
            <div class="faq-item reveal" onclick="toggleFaq(this)">
                <div class="faq-q"><?=htmlspecialchars($f[0])?><svg class="faq-arr" viewBox="0 0 24 24">
                        <polyline points="6 9 12 15 18 9" />
                    </svg></div>
                <div class="faq-a"><?=htmlspecialchars($f[1])?></div>
            </div>
            <?php endforeach;?>
        </div>
    </section>

    <!-- ██ KONTAK ██ -->
    <section class="sec" id="kontak">
        <div class="sec-hd reveal">
            <div class="sec-lft">
                <div class="sec-pill">Hubungi Kami</div>
                <h2 class="sec-h">Kontak &amp; <em>Lokasi</em></h2>
                <p class="sec-sub">Ada pertanyaan? Tim kami siap membantu.</p>
            </div>
        </div>
        <div class="kontak-grid">
            <div>
                <div class="kontak-items">
                    <?php $ks=[['📍','var(--blue-ll)','Alamat','Jl. Pendidikan No. 123, Gedung B Lt.2<br>Jakarta Selatan 12345'],['📞','var(--green-l)','Telepon','(021) 1234-5678<br>Senin–Jumat · 07.00–16.00 WIB'],['✉️','var(--amber-l)','Email','perpustakaan@sekolah.sch.id<br>Respon dalam 1×24 jam'],['💬','var(--violet-l)','WhatsApp','+62 812-3456-7890<br>Chat langsung dengan petugas']];
        foreach($ks as $k):?>
                    <div class="kitem reveal">
                        <div class="kitem-ico" style="background:<?=$k[1]?>"><?=$k[0]?></div>
                        <div>
                            <div class="kitem-h"><?=$k[2]?></div>
                            <div class="kitem-v"><?=$k[3]?></div>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
            </div>
            <div class="reveal">
                <div class="map-card">
                    <div class="map-bg">
                        <div class="map-grid"></div>
                        <div class="map-roads"></div>
                        <div class="map-pin-wrap">
                            <span class="map-pin-ico">📍</span>
                            <div class="map-pin-label">LibraSpace</div>
                            <div class="map-pin-sub">Jl. Pendidikan No. 123</div>
                        </div>
                    </div>
                    <div class="map-footer">
                        <div class="map-addr">Jakarta Selatan · Dekat Stasiun MRT</div>
                        <a href="https://maps.google.com/?q=Jakarta+Selatan" target="_blank" class="map-link">Buka Maps
                            →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ██ CTA ██ -->
    <div class="cta-sec reveal">
        <div>
            <h2 class="cta-h">Siap Mulai Petualangan<br>Membacamu?</h2>
            <p class="cta-sub">Bergabung sekarang dan nikmati akses ke seluruh koleksi buku perpustakaan.<br>Gratis
                untuk semua siswa terdaftar.</p>
        </div>
        <div class="cta-btns">
            <a href="register.php" class="cta-b1">Daftar Sekarang</a>
            <a href="login.php" class="cta-b2">Masuk ke Akun</a>
        </div>
    </div>

    <!-- ██ FOOTER ██ -->
    <div class="footer">
        <div class="footer-grid">
            <div>
                <div class="foot-logo">
                    <div class="foot-icon">📖</div>
                    <div class="foot-brand">Libra<span>Space</span></div>
                </div>
                <p class="foot-desc">Platform perpustakaan digital modern untuk sekolah. Memudahkan pengelolaan koleksi,
                    peminjaman, dan pengembalian buku secara efisien dan transparan.</p>
                <div class="foot-contacts">
                    <?php $fc=[['M3 8 5h14l-1.68 8.39a2 2 0 01-1.98 1.61H8.68a2 2 0 01-1.97-1.67L5 8zm0 0L3.18 4H1','Jl. Pendidikan No. 123, Jakarta Selatan'],['M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A2 2 0 013.6 1.28h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6','(021) 1234-5678'],['M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm0 0l8 9 8-9','perpustakaan@sekolah.sch.id']];
        foreach($fc as $f):?>
                    <div class="foot-contact"><svg viewBox="0 0 24 24">
                            <path d="<?=$f[0]?>" />
                        </svg><?=$f[1]?></div>
                    <?php endforeach;?>
                </div>
            </div>
            <div>
                <div class="foot-col-title">Layanan</div>
                <div class="foot-links">
                    <a href="<?=$isAnggota?'anggota/katalog.php':'login.php'?>">Katalog Buku</a>
                    <a href="<?=$isAnggota?'anggota/pinjam.php':'login.php'?>">Pinjam Buku</a>
                    <a href="<?=$isAnggota?'anggota/kembali.php':'login.php'?>">Kembalikan Buku</a>
                    <a href="<?=$isAnggota?'anggota/riwayat.php':'login.php'?>">Riwayat Pinjaman</a>
                    <a href="<?=$isAnggota?'anggota/ulasan.php':'login.php'?>">Ulasan Buku</a>
                </div>
            </div>
            <div>
                <div class="foot-col-title">Informasi</div>
                <div class="foot-links">
                    <a href="#featured">Buku Unggulan</a>
                    <a href="#kategori">Kategori</a>
                    <a href="#leaderboard">Leaderboard</a>
                    <a href="#kontak">Kontak</a>
                    <a href="setup.php">Setup DB</a>
                </div>
            </div>
            <div>
                <div class="foot-col-title">Akun</div>
                <div class="foot-links">
                    <a href="register.php">Daftar Anggota</a>
                    <a href="login.php">Masuk</a>
                    <?php if($isAdmin):?><a href="admin/dashboard.php">Admin Panel</a><?php endif;?>
                    <?php if($isPetugas):?><a href="petugas/dashboard.php">Panel Petugas</a><?php endif;?>
                    <?php if($isAnggota):?><a href="anggota/profil.php">Profil Saya</a><?php endif;?>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p class="foot-copy">© <?=date('Y')?> LibraSpace — Sistem Perpustakaan Digital · All rights reserved.</p>
        <div class="foot-btmr">
            <a href="#">Kebijakan Privasi</a>
            <a href="#">Syarat & Ketentuan</a>
        </div>
    </div>

    <script>
    /* ── NAV scroll ── */
    const nav = document.getElementById('nav');
    const topH = document.getElementById('topbar')?.offsetHeight || 36;
    nav.style.top = topH + 'px';
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > topH + 20));

    /* ── Smooth scroll ── */
    document.querySelectorAll('a[href^="#"]').forEach(a => a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) {
            e.preventDefault();
            t.scrollIntoView({
                behavior: 'smooth'
            });
            document.getElementById('mob').classList.remove('open');
        }
    }));

    /* ── Reveal on scroll ── */
    const ro = new IntersectionObserver(es => {
        es.forEach(el => {
            if (el.isIntersecting) {
                const sibs = [...el.target.parentElement.children].filter(c => c.classList.contains(
                    'reveal'));
                setTimeout(() => el.target.classList.add('show'), Math.min(sibs.indexOf(el.target), 6) *
                    80);
                ro.unobserve(el.target);
            }
        });
    }, {
        threshold: .1
    });
    document.querySelectorAll('.reveal').forEach(el => ro.observe(el));

    /* ── Animated counters ── */
    function animCount(el) {
        const raw = el.textContent.replace(/[^\d]/g, '');
        const target = parseInt(raw) || 0;
        if (!target) return;
        let c = 0;
        const step = Math.max(1, Math.ceil(target / 50));
        const iv = setInterval(() => {
            c = Math.min(c + step, target);
            el.textContent = c + (el.dataset.sfx || '');
            if (c >= target) clearInterval(iv);
        }, 28);
    }
    const cro = new IntersectionObserver(es => {
        es.forEach(el => {
            if (el.isIntersecting) {
                animCount(el);
                cro.unobserve(el);
            }
        });
    }, {
        threshold: .5
    });
    document.querySelectorAll('[data-count]').forEach(el => {
        el.dataset.sfx = el.textContent.replace(/\d/g, '').trim();
        el.textContent = el.dataset.count;
        cro.observe(el);
    });

    /* ── FAQ toggle ── */
    function toggleFaq(item) {
        const wasOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item.open').forEach(x => x.classList.remove('open'));
        if (!wasOpen) item.classList.add('open');
    }

    /* ── Live search ── */
    (function() {
        const inp = document.getElementById('searchInp');
        const drop = document.getElementById('searchDrop');
        if (!inp || !drop) return;
        let t;
        const cov = ['135deg,#dde8ff,#b8ccff', '135deg,#d4f0e8,#a8e0cc', '135deg,#ffe0dc,#ffbdb6',
            '135deg,#fff0cc,#ffd880', '135deg,#ecdeff,#d4b8ff'
        ];
        const em = ['📘', '📗', '📕', '📙', '📓'];
        const catUrl = '<?=$isAnggota?'anggota/katalog.php':'login.php'?>';

        inp.addEventListener('input', () => {
            clearTimeout(t);
            const q = inp.value.trim();
            if (q.length < 2) {
                drop.classList.remove('show');
                return;
            }
            drop.innerHTML = '<div class="sd-loading"><div class="spin"></div></div>';
            drop.classList.add('show');
            t = setTimeout(() => {
                fetch('api_search.php?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        if (!data.length) {
                            drop.innerHTML =
                                '<div class="sd-empty">Tidak ditemukan — coba kata kunci lain</div>';
                            return;
                        }
                        drop.innerHTML = data.map((b, i) => `
            <div class="sd-item" onclick="location.href='${catUrl}'">
              <div class="sd-ph" style="background:linear-gradient(${cov[i%5]})">${em[i%5]}</div>
              <div class="sd-info">
                <div class="sd-title">${b.judul_buku||''}</div>
                <div class="sd-meta">${b.pengarang||''} · ${b.nama_kategori||'Umum'}</div>
              </div>
              <span class="sd-badge ${b.status==='tersedia'?'sd-yes':'sd-no'}">${b.status==='tersedia'?'Tersedia':'Dipinjam'}</span>
            </div>`).join('');
                    }).catch(() => drop.classList.remove('show'));
            }, 300);
        });
        document.addEventListener('click', e => {
            if (!inp.contains(e.target) && !drop.contains(e.target)) drop.classList.remove('show');
        });
        inp.addEventListener('focus', () => {
            if (inp.value.trim().length >= 2) drop.classList.add('show');
        });
    })();

    function doSearch() {
        const q = document.getElementById('searchInp').value.trim();
        if (q) location.href = '<?=$isAnggota?'anggota/katalog.php':'login.php'?>?search=' + encodeURIComponent(q);
    }
    document.getElementById('searchInp')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') doSearch();
    });

    function setSearch(val) {
        const inp = document.getElementById('searchInp');
        if (inp) {
            inp.value = val;
            inp.dispatchEvent(new Event('input'));
            inp.focus();
        }
    }

    /* ── Reading challenge progress bars animate on view ── */
    const bro = new IntersectionObserver(es => {
        es.forEach(el => {
            if (el.isIntersecting) {
                const fills = el.querySelectorAll('.ch-fill,.rbar-fill');
                fills.forEach(f => {
                    const w = f.style.width;
                    f.style.width = '0';
                    setTimeout(() => f.style.width = w, 100);
                });
                bro.unobserve(el);
            }
        });
    }, {
        threshold: .2
    });
    document.querySelectorAll('.ch-card,.rat-card').forEach(el => bro.observe(el));
    </script>
</body>

</html>