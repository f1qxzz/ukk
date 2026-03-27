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
    <script>
    (function() {
        // Cek localStorage segera
        try {
            if (localStorage.getItem('libraspace_dark') === '1') {
                document.documentElement.classList.add('dark-mode-active');
            }
        } catch (e) {}
    })();
    </script>

    <!-- CSS files -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<<<<<<< HEAD

<body class="index-page">
=======
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
>>>>>>> 232df348deadaaed9bb9be90eee8a73eef4ce42e

    <!-- Scroll Progress -->
    <div id="scroll-progress"></div>
    <!-- Dark Mode Toggle -->
    <button id="dark-toggle" onclick="toggleDark()" title="Toggle Dark Mode" type="button">🌙</button>
    <!-- Back to Top FAB -->
    <button id="fab-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Kembali ke atas">↑</button>

    <!-- ██ TOP STATUS BAR ██ -->
    <div class="topbar" id="topbar">
        <div class="topbar-left">
            <div class="topbar-item">
                <div class="topbar-dot <?=$buka?'dot-open':'dot-closed'?>"></div>
                <span><?=$buka?'Perpustakaan Buka':'Perpustakaan Tutup'?> · <?=$jam_str?> WIB</span>
            </div>
            <div class="topbar-item">📚 <?=$buku_tersedia?> buku tersedia dari <?=$total_buku?> koleksi</div>
            <?php if($jatuh_tempo>0&&($isAdmin||$isPetugas)):?>
            <div class="topbar-item" class="topbar-warn">⚠️ <?=$jatuh_tempo?> buku melewati batas kembali</div>
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
            <span class="hero-greet-text">👋 <?=htmlspecialchars($username)?></span>
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
        <?php if($isAdmin):?><a href="admin/dashboard.php" class="link-blue">Dashboard Admin</a>
        <?php elseif($isPetugas):?><a href="petugas/dashboard.php" class="link-blue">Dashboard</a>
        <?php else:?><a href="anggota/dashboard.php" class="link-blue">Dashboard Saya</a><?php endif;?>
        <a href="logout.php">Keluar</a>
        <?php else:?>
        <a href="login.php">Masuk</a>
        <a href="register.php" class="link-blue">Daftar Gratis</a>
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
            <div class="hero-tag"><span class="hero-dot"></span>Perpustakaan Digital Modern</div>
            <br>
            <!-- Quote pill -->
            <div class="hero-quote-pill">
                <div class="hero-quote-ico">💬</div>
                <div>
                    <div class="hero-quote-text"><?=htmlspecialchars($quote[0])?></div>
                    <div class="hero-quote-by">— <?=htmlspecialchars($quote[1])?></div>
                </div>
            </div>


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
                        <div class="book-deco-icon">📖</div>
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
                        <div class="hw-ico" class="hw-ico hw-ico-green">✅</div>
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
                        <div class="hw-ico" class="hw-ico hw-ico-amber">🕐</div>
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
                        <div class="hw-ico" class="hw-ico hw-ico-blue">📊</div>
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

    <!-- ██ READING TICKER ██ -->
    <div class="reading-ticker">
        <div class="ticker-inner">
            <?php
            $ticker_items = [
                '📚 ' . $total_buku . ' Koleksi Buku Tersedia',
                '✅ ' . $buku_tersedia . ' Buku Siap Dipinjam',
                '👥 ' . $total_anggota . ' Anggota Terdaftar',
                '🔄 ' . $total_pinjam . ' Sedang Dipinjam',
                '⭐ Rating ' . number_format($avg_rating > 0 ? $avg_rating : 4.5, 1) . '/5.0 dari ' . $total_ulasan . ' Ulasan',
                '📅 Pinjaman Bulan Ini: ' . $pinjam_bulan_ini . ' Transaksi',
                '🕐 Jam Buka: Senin–Jumat 07.00–16.00 WIB',
            ];
            $ticker_str = implode(' &nbsp;·&nbsp; ', array_map(fn($t) => "<span class='ticker-item'>{$t}</span>", $ticker_items));
            echo $ticker_str . ' &nbsp;&nbsp;&nbsp;&nbsp; ' . $ticker_str;
            ?>
        </div>
    </div>

    <!-- ██ INFO STRIP ██ -->
    <div class="info-strip">
        <div class="istrip reveal">
            <div class="istrip-ico" class="hw-ico hw-ico-blue">📚</div>
            <div>
                <div class="istrip-n" data-count="<?=$total_buku?>"><?=$total_buku?></div>
                <div class="istrip-l">Koleksi Buku</div>
            </div>
        </div>
        <div class="istrip reveal">
            <div class="istrip-ico" class="hw-ico hw-ico-green">✅</div>
            <div>
                <div class="istrip-n" data-count="<?=$buku_tersedia?>"><?=$buku_tersedia?></div>
                <div class="istrip-l">Buku Tersedia</div>
            </div>
        </div>
        <div class="istrip reveal">
            <div class="istrip-ico" class="hw-ico hw-ico-amber">🔄</div>
            <div>
                <div class="istrip-n" data-count="<?=$total_pinjam?>"><?=$total_pinjam?></div>
                <div class="istrip-l">Sedang Dipinjam</div>
            </div>
        </div>
        <div class="istrip reveal">
            <div class="istrip-ico" class="istrip-ico istrip-ico-violet">👥</div>
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
            <h2 class="shelf-h">Rak <em class="shelf-h-em">Perpustakaan</em></h2>
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
        <div class="shelf-floor" class="shelf-floor-r"></div>
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
                        <div class="lb-flex-row">
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
                    <div class="flex-1">
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
                            class="jb-day <?=$isT?'today':''?>"><?=$j[0]?><?=$isT?' <em class="jb-today-label">(Hari Ini)</em>':''?></span>
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
    <section class="sec alt" class="sec-faq-pad">
        <div class="sec-center-mb" class="reveal">
            <div class="sec-pill" class="sec-pill-center">Bantuan</div>
            <h2 class="sec-h">Pertanyaan <em>Umum</em></h2>
            <p class="sec-sub" class="sec-sub-center">Jawaban untuk pertanyaan yang paling sering ditanyakan.</p>
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
                <div class="faq-a">
                    <div class="faq-a-inner"><?=htmlspecialchars($f[1])?></div>
                </div>
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
                <div class="foot-nl">
                    <input type="email" placeholder="Email kamu..." />
                    <button
                        onclick="alert('Terima kasih! Notifikasi akan dikirimkan ke email Anda.')">Langganan</button>
                </div>
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
    /* ── Dark Mode Fix ── */
    const DM_KEY = 'libraspace_dark';

    function toggleDark() {
        const html = document.documentElement;
        const body = document.body;
        const btn = document.getElementById('dark-toggle');

        // Cek status saat ini
        const isCurrentlyDark = html.classList.contains('dark-mode-active') || body.classList.contains('dark');

        if (!isCurrentlyDark) {
            // Aktifkan dark mode
            html.classList.add('dark-mode-active');
            body.classList.add('dark');
            localStorage.setItem(DM_KEY, '1');
            if (btn) btn.textContent = '☀️';
            console.log('Dark mode: ON');
        } else {
            // Matikan dark mode
            html.classList.remove('dark-mode-active');
            body.classList.remove('dark');
            localStorage.setItem(DM_KEY, '0');
            if (btn) btn.textContent = '🌙';
            console.log('Dark mode: OFF');
        }

        // Force repaint
        void html.offsetHeight;
    }

    // Initialize
    (function initDarkMode() {
        const saved = localStorage.getItem(DM_KEY);
        const html = document.documentElement;
        const body = document.body;
        const btn = document.getElementById('dark-toggle');

        if (saved === '1') {
            html.classList.add('dark-mode-active');
            body.classList.add('dark');
            if (btn) btn.textContent = '☀️';
        } else {
            html.classList.remove('dark-mode-active');
            body.classList.remove('dark');
            if (btn) btn.textContent = '🌙';
        }
    })();

    /* ── NAV scroll ── */
    const nav = document.getElementById('nav');
    const topH = document.getElementById('topbar')?.offsetHeight || 36;
    nav.style.top = topH + 'px';
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > topH + 20));

    /* ── Scroll progress bar ── */
    const prog = document.getElementById('scroll-progress');
    const fab = document.getElementById('fab-top');
    window.addEventListener('scroll', () => {
        const pct = (scrollY / (document.documentElement.scrollHeight - innerHeight)) * 100;
        prog.style.width = pct + '%';
        fab.classList.toggle('show', scrollY > 400);
    });

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
    <script src="assets/js/script.js"></script>
</body>

</html>