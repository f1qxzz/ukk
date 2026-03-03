<?php
require_once 'includes/session.php';
require_once 'config/database.php';
initSession();
$isAdmin=$isPetugas=$isAnggota=$loggedIn=false; $username='';
if(isset($_SESSION['pengguna_logged_in'])){$loggedIn=true;$username=$_SESSION['pengguna_username']??'';
  if($_SESSION['pengguna_level']==='admin')$isAdmin=true;
  elseif($_SESSION['pengguna_level']==='petugas')$isPetugas=true;}
if(isset($_SESSION['anggota_logged_in'])){$loggedIn=true;$username=$_SESSION['anggota_nama']??'';$isAnggota=true;}
$conn=getConnection();
$total_buku=$conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c']??0;
$total_anggota=$conn->query("SELECT COUNT(*) c FROM anggota")->fetch_assoc()['c']??0;
$total_pinjam=$conn->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['c']??0;
$books=$conn->query("SELECT judul_buku,pengarang,cover FROM buku ORDER BY id_buku DESC LIMIT 8");
$buku_arr=[];
if($books&&$books->num_rows>0){while($b=$books->fetch_assoc())$buku_arr[]=$b;}
$ulasan_res=$conn->query("SELECT u.*,a.nama_anggota,b.judul_buku FROM ulasan_buku u JOIN anggota a ON u.id_anggota=a.id_anggota JOIN buku b ON u.id_buku=b.id_buku ORDER BY u.id_ulasan DESC LIMIT 6");
$ulasan_arr=[];
if($ulasan_res&&$ulasan_res->num_rows>0){while($u=$ulasan_res->fetch_assoc())$ulasan_arr[]=$u;}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>LibraSpace — Perpustakaan Digital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ═══ TOKENS — LIGHT & VIBRANT ═══ */
:root{
  --white:#ffffff;
  --off:#f5f8ff;
  --soft:#eef2ff;
  --paper:#f9fbff;
  --ink:#0d1433;--ink2:#1e2d5e;--ink3:#2d3f80;
  --muted:#5c6b9a;--subtle:#8a96bc;
  --blue:#3b5cf6;--blue2:#2742e8;--blue3:#1a30cc;
  --blue-l:#eef1fd;--blue-ll:#f4f6fe;
  --sky:#0ea5e9;--teal:#06b6d4;
  --violet:#7c3aed;--violet-l:#f3f0ff;
  --green:#10b981;--green-l:#ecfdf5;
  --amber:#f59e0b;--amber-l:#fffbeb;
  --rose:#f43f5e;--rose-l:#fff1f2;
  --border:rgba(59,92,246,.12);--border2:rgba(59,92,246,.06);
  --sh0:0 1px 4px rgba(13,20,64,.06),0 2px 12px rgba(13,20,64,.06);
  --sh1:0 4px 20px rgba(13,20,64,.09),0 8px 32px rgba(13,20,64,.08);
  --sh2:0 8px 40px rgba(13,20,64,.13),0 24px 64px rgba(13,20,64,.1);
  --r:14px;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;font-size:16px;}
body{font-family:'DM Sans',sans-serif;background:var(--white);color:var(--ink);line-height:1.6;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}
img{display:block;max-width:100%;}
::-webkit-scrollbar{width:4px;}
::-webkit-scrollbar-track{background:var(--off);}
::-webkit-scrollbar-thumb{background:#c5cde8;border-radius:4px;}

/* ═══ CANVAS PARTICLES ═══ */
#particles{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.5;}

/* ═══ NAV ═══ */
.nav{
  position:fixed;top:0;left:0;right:0;z-index:900;
  height:72px;padding:0 5%;
  display:flex;align-items:center;justify-content:space-between;
  transition:all .4s;
  background:rgba(255,255,255,.8);
  backdrop-filter:blur(20px);
  border-bottom:1px solid transparent;
}
.nav.scrolled{
  background:rgba(255,255,255,.96);
  border-bottom:1px solid var(--border);
  box-shadow:0 2px 24px rgba(13,20,64,.07);
}
.nav-logo{display:flex;align-items:center;gap:12px;}
.nav-badge{
  width:40px;height:40px;border-radius:11px;
  background:linear-gradient(135deg,var(--blue),var(--blue2));
  display:flex;align-items:center;justify-content:center;
  font-size:1.1rem;box-shadow:0 4px 14px rgba(59,92,246,.3);
  transition:transform .3s;
}
.nav-logo:hover .nav-badge{transform:rotate(-8deg) scale(1.05);}
.nav-name{
  font-family:'Cormorant Garamond',serif;
  font-size:1.3rem;font-weight:700;color:var(--ink);
}
.nav-name span{color:var(--blue);}
.nav-links{display:flex;gap:2px;}
.nav-links a{
  padding:8px 16px;border-radius:8px;
  font-size:.84rem;font-weight:500;
  color:var(--muted);transition:all .2s;
}
.nav-links a:hover{color:var(--blue);background:var(--blue-ll);}
.nav-right{display:flex;align-items:center;gap:10px;}
.btn-ghost-nav{
  padding:9px 22px;border-radius:9px;
  font-size:.83rem;font-weight:500;
  border:1.5px solid var(--border);color:var(--muted);
  transition:all .2s;
}
.btn-ghost-nav:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-ll);}
.btn-blue-nav{
  padding:9px 22px;border-radius:9px;
  font-size:.83rem;font-weight:700;
  background:linear-gradient(135deg,var(--blue),var(--blue2));
  color:#fff;
  box-shadow:0 4px 14px rgba(59,92,246,.28);
  transition:all .2s;
}
.btn-blue-nav:hover{transform:translateY(-1px);box-shadow:0 6px 22px rgba(59,92,246,.38);}
.hamburger{display:none;background:none;border:none;color:var(--ink);font-size:1.4rem;cursor:pointer;padding:4px;}

/* ═══ MOBILE DRAWER ═══ */
.drawer{
  display:none;position:fixed;inset:0;z-index:950;
  background:rgba(255,255,255,.97);backdrop-filter:blur(20px);
  flex-direction:column;align-items:center;justify-content:center;gap:20px;
}
.drawer.open{display:flex;}
.drawer a{font-size:1.2rem;font-weight:500;color:var(--muted);padding:10px 40px;border-radius:10px;transition:color .2s;}
.drawer a:hover{color:var(--blue);}
.drawer-close{position:absolute;top:24px;right:5%;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--muted);}

/* ═══ HERO ═══ */
.hero{
  min-height:100vh;position:relative;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  text-align:center;padding:120px 6% 80px;
  overflow:hidden;
  background:linear-gradient(170deg,#f0f4ff 0%,#fafbff 40%,#f4f0ff 70%,#fff7f0 100%);
}
.hero-mesh{
  position:absolute;inset:0;z-index:0;pointer-events:none;
  background:
    radial-gradient(ellipse 60% 50% at 80% 20%,rgba(59,92,246,.08) 0%,transparent 60%),
    radial-gradient(ellipse 50% 40% at 20% 80%,rgba(124,58,237,.07) 0%,transparent 55%),
    radial-gradient(ellipse 40% 35% at 60% 70%,rgba(14,165,233,.06) 0%,transparent 50%);
}
.hero-dots{
  position:absolute;inset:0;z-index:0;pointer-events:none;
  background-image:radial-gradient(circle,rgba(59,92,246,.12) 1px,transparent 1px);
  background-size:36px 36px;
  mask-image:radial-gradient(ellipse 70% 70% at 50% 50%,black 0%,transparent 100%);
}

.hero-eyebrow{
  display:inline-flex;align-items:center;gap:9px;
  padding:7px 20px;border-radius:40px;
  border:1.5px solid rgba(59,92,246,.2);
  background:rgba(59,92,246,.06);
  font-size:.68rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;
  color:var(--blue);margin-bottom:28px;
  position:relative;z-index:2;
  opacity:0;animation:revealUp .7s .1s forwards;
}
.hero-dot{width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse 2s infinite;}

.hero-h1{
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(3.4rem,6.5vw,7rem);
  font-weight:600;line-height:.96;
  letter-spacing:-.02em;
  margin-bottom:24px;
  position:relative;z-index:2;
  color:var(--ink);
  opacity:0;animation:revealUp .8s .2s forwards;
}
.hero-h1 .line2{display:block;font-style:italic;color:var(--blue);}
.hero-h1 .line3{
  display:block;
  background:linear-gradient(135deg,var(--violet),var(--sky));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  font-style:normal;
}

.hero-desc{
  font-size:1.05rem;color:var(--muted);
  max-width:520px;line-height:1.85;font-weight:400;
  margin:0 auto 40px;
  position:relative;z-index:2;
  opacity:0;animation:revealUp .7s .35s forwards;
}

.hero-ctas{
  display:flex;align-items:center;gap:13px;flex-wrap:wrap;justify-content:center;
  position:relative;z-index:2;margin-bottom:64px;
  opacity:0;animation:revealUp .7s .45s forwards;
}
.btn-hero-primary{
  padding:15px 36px;border-radius:12px;
  font-size:.96rem;font-weight:700;
  background:linear-gradient(135deg,var(--blue),var(--blue2));
  color:#fff;
  box-shadow:0 6px 28px rgba(59,92,246,.32);
  transition:all .25s;display:inline-flex;align-items:center;gap:9px;
}
.btn-hero-primary:hover{transform:translateY(-3px);box-shadow:0 12px 40px rgba(59,92,246,.44);}
.btn-hero-ghost{
  padding:15px 30px;border-radius:12px;
  font-size:.93rem;font-weight:500;
  border:1.5px solid var(--border);color:var(--muted);
  background:rgba(255,255,255,.8);
  transition:all .25s;
}
.btn-hero-ghost:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-ll);}

/* Stat strip */
.hero-stats{
  display:flex;gap:0;
  border:1.5px solid var(--border);border-radius:18px;
  background:rgba(255,255,255,.85);backdrop-filter:blur(12px);
  padding:22px 36px;position:relative;z-index:2;
  box-shadow:var(--sh1);
  opacity:0;animation:revealUp .7s .55s forwards;
}
.hstat{text-align:center;padding:0 36px;position:relative;}
.hstat+.hstat::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:1px;background:var(--border);}
.hstat-n{font-family:'Cormorant Garamond',serif;font-size:2.4rem;font-weight:700;color:var(--blue);line-height:1;}
.hstat-l{font-size:.64rem;text-transform:uppercase;letter-spacing:.14em;color:var(--subtle);margin-top:5px;font-weight:600;}

/* Floating decorations */
.hero-float-book,.hero-float-book2{
  position:absolute;z-index:2;
  opacity:0;
}
.hero-float-book{
  right:6%;bottom:18%;
  animation:bookReveal 1s .7s forwards,floatBook 6s 1.7s ease-in-out infinite;
}
.hero-float-book2{
  left:5%;top:28%;
  animation:bookReveal2 1s .9s forwards,floatBook2 7s 1.9s ease-in-out infinite;
}

/* ═══ MARQUEE STRIP ═══ */
.marquee-wrap{
  overflow:hidden;
  border-top:1px solid var(--border);border-bottom:1px solid var(--border);
  padding:13px 0;
  background:linear-gradient(90deg,var(--blue-ll),var(--soft),var(--blue-ll));
  position:relative;z-index:10;
}
.marquee-track{
  display:flex;gap:0;width:max-content;
  animation:marquee 30s linear infinite;
}
.marquee-item{
  padding:0 32px;font-size:.7rem;font-weight:700;letter-spacing:.18em;
  text-transform:uppercase;color:var(--blue);
  border-right:1px solid rgba(59,92,246,.12);white-space:nowrap;
  display:flex;align-items:center;gap:10px;
}
.marquee-item::before{content:'✦';color:var(--blue);opacity:.5;}

/* ═══ SECTIONS ═══ */
.sec{padding:96px 7%;position:relative;z-index:10;background:var(--white);}
.sec.alt{background:var(--off);}
.sec-label{
  font-size:.65rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;
  color:var(--blue);display:flex;align-items:center;gap:10px;margin-bottom:14px;
}
.sec-label::before{content:'';width:22px;height:2px;background:var(--blue);border-radius:2px;}
.sec-h{
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(2.4rem,4vw,3.6rem);
  font-weight:600;line-height:1.05;color:var(--ink);
  margin-bottom:12px;
}
.sec-h em{font-style:italic;color:var(--blue);}
.sec-sub{font-size:.95rem;color:var(--muted);line-height:1.82;max-width:500px;font-weight:400;}

/* ═══ BOOK CAROUSEL ═══ */
.books-marquee-outer{overflow:hidden;margin:0 -7%;margin-top:52px;}
.books-marquee-track{
  display:flex;gap:16px;width:max-content;
  animation:booksScroll 40s linear infinite;
  padding:16px 56px;
}
.books-marquee-track:hover{animation-play-state:paused;}
.bk{
  width:155px;flex-shrink:0;
  background:var(--white);
  border:1.5px solid var(--border);
  border-radius:12px;overflow:hidden;
  box-shadow:var(--sh0);
  transition:all .3s;cursor:pointer;
}
.bk:hover{transform:translateY(-8px) scale(1.02);border-color:rgba(59,92,246,.3);box-shadow:var(--sh2);}
.bk-cover{
  width:100%;aspect-ratio:2/3;
  background:linear-gradient(160deg,#e0e8ff,#c8d6ff);
  display:flex;align-items:center;justify-content:center;
  font-size:2.5rem;position:relative;overflow:hidden;
}
.bk-cover img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}
.bk-info{padding:10px 12px;border-top:1px solid var(--border2);}
.bk-title{font-size:.75rem;font-weight:600;color:var(--ink);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:3px;}
.bk-author{font-size:.65rem;color:var(--subtle);}

/* ═══ FEATURES ═══ */
.feat-grid{
  display:grid;grid-template-columns:repeat(3,1fr);
  gap:18px;margin-top:52px;
}
.feat{
  background:var(--white);
  border:1.5px solid var(--border);
  border-radius:16px;padding:32px 26px;
  position:relative;overflow:hidden;
  transition:all .28s;
}
.feat::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,var(--blue),var(--sky));
  transform:scaleX(0);transform-origin:left;transition:transform .35s;
}
.feat:hover{border-color:rgba(59,92,246,.25);transform:translateY(-5px);box-shadow:var(--sh2);}
.feat:hover::before{transform:scaleX(1);}
.feat-num{
  font-family:'DM Mono',monospace;
  font-size:.64rem;color:rgba(59,92,246,.4);letter-spacing:.14em;margin-bottom:18px;
}
.feat-ico{
  width:52px;height:52px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.4rem;margin-bottom:16px;
  background:var(--blue-ll);
}
.feat-h{
  font-family:'Cormorant Garamond',serif;
  font-size:1.25rem;font-weight:700;color:var(--ink);margin-bottom:9px;
}
.feat-p{font-size:.84rem;color:var(--muted);line-height:1.78;}

/* ═══ STEPS ═══ */
.steps-grid{
  display:grid;grid-template-columns:repeat(4,1fr);
  gap:0;margin-top:52px;
  border:1.5px solid var(--border);border-radius:16px;overflow:hidden;
  background:var(--white);box-shadow:var(--sh0);
}
.step{
  padding:38px 26px;
  border-right:1px solid var(--border);
  position:relative;transition:background .3s;
  background:var(--white);
}
.step:last-child{border-right:none;}
.step:hover{background:var(--blue-ll);}
.step-n{
  font-family:'Cormorant Garamond',serif;
  font-size:3rem;font-weight:300;
  color:rgba(59,92,246,.18);
  line-height:1;margin-bottom:18px;
}
.step-h{font-size:.9rem;font-weight:700;color:var(--ink);margin-bottom:8px;}
.step-p{font-size:.79rem;color:var(--muted);line-height:1.7;}

/* ═══ ROLES ═══ */
.roles-grid{
  display:grid;grid-template-columns:repeat(3,1fr);
  gap:18px;margin-top:52px;
}
.role{
  border-radius:18px;padding:38px 30px;
  position:relative;overflow:hidden;transition:all .28s;
  background:var(--white);border:1.5px solid var(--border);
  box-shadow:var(--sh0);
}
.role:hover{transform:translateY(-5px);box-shadow:var(--sh2);}
.role::before{
  content:'';position:absolute;top:0;left:0;right:0;height:4px;
  background:var(--rc,var(--blue));
}
.role-admin{--rc:linear-gradient(90deg,#3b5cf6,#7c3aed);}
.role-petugas{--rc:linear-gradient(90deg,#0ea5e9,#06b6d4);}
.role-anggota{--rc:linear-gradient(90deg,#10b981,#059669);}
.role-badge{
  display:inline-block;padding:4px 14px;border-radius:20px;
  font-size:.66rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  margin-bottom:18px;
}
.role-admin .role-badge{background:rgba(59,92,246,.1);color:var(--blue);border:1px solid rgba(59,92,246,.2);}
.role-petugas .role-badge{background:rgba(14,165,233,.1);color:var(--sky);border:1px solid rgba(14,165,233,.2);}
.role-anggota .role-badge{background:rgba(16,185,129,.1);color:var(--green);border:1px solid rgba(16,185,129,.2);}
.role-ico{font-size:2.2rem;margin-bottom:12px;display:block;}
.role-h{
  font-family:'Cormorant Garamond',serif;
  font-size:1.35rem;font-weight:700;color:var(--ink);margin-bottom:9px;
}
.role-p{font-size:.84rem;color:var(--muted);line-height:1.75;margin-bottom:20px;}
.role-list{list-style:none;display:flex;flex-direction:column;gap:7px;}
.role-list li{
  font-size:.8rem;color:var(--ink2);
  display:flex;align-items:flex-start;gap:8px;line-height:1.5;
}
.role-list li::before{content:'✓';color:var(--green);font-weight:700;flex-shrink:0;margin-top:1px;}

/* ═══ TESTIMONIALS ═══ */
.testi-grid{
  display:grid;grid-template-columns:repeat(3,1fr);
  gap:18px;margin-top:52px;
}
.testi{
  background:var(--white);
  border:1.5px solid var(--border);
  border-radius:14px;padding:28px 22px;
  transition:all .28s;box-shadow:var(--sh0);
}
.testi:hover{border-color:rgba(59,92,246,.25);box-shadow:var(--sh1);transform:translateY(-3px);}
.testi-stars{display:flex;gap:3px;margin-bottom:14px;}
.testi-stars span{color:var(--amber);font-size:.88rem;}
.testi-text{
  font-family:'Cormorant Garamond',serif;
  font-size:1.05rem;font-style:italic;
  color:var(--ink2);line-height:1.72;margin-bottom:18px;
}
.testi-text::before{content:'"';color:var(--blue);font-size:1.6rem;line-height:0;vertical-align:-.3em;}
.testi-text::after{content:'"';color:var(--blue);font-size:1.6rem;line-height:0;vertical-align:-.3em;}
.testi-author{display:flex;align-items:center;gap:10px;}
.testi-avatar{
  width:36px;height:36px;border-radius:50%;
  background:linear-gradient(135deg,var(--blue),var(--violet));
  display:flex;align-items:center;justify-content:center;
  font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0;
}
.testi-name{font-size:.83rem;font-weight:600;color:var(--ink);}
.testi-book{font-size:.7rem;color:var(--subtle);}

/* ═══ CTA SECTION ═══ */
.cta-block{
  margin:0 7% 96px;
  background:linear-gradient(135deg,#1a2e8c,#2742e8 40%,#3b5cf6 70%,#5a7ef8);
  border-radius:24px;padding:76px 60px;
  position:relative;overflow:hidden;z-index:10;
  display:grid;grid-template-columns:1fr auto;
  gap:48px;align-items:center;
  box-shadow:0 20px 60px rgba(59,92,246,.28);
}
.cta-block::before{
  content:'📚';position:absolute;
  right:260px;top:50%;transform:translateY(-50%);
  font-size:10rem;opacity:.06;pointer-events:none;line-height:1;
}
.cta-block::after{
  content:'';position:absolute;
  right:-80px;top:-80px;
  width:320px;height:320px;border-radius:50%;
  background:rgba(255,255,255,.07);
}
.cta-h{
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(2rem,3.5vw,3rem);
  font-weight:600;color:#fff;
  margin-bottom:12px;line-height:1.1;
}
.cta-h em{font-style:italic;color:rgba(255,255,255,.75);}
.cta-sub{font-size:.95rem;color:rgba(255,255,255,.62);line-height:1.8;font-weight:300;}
.cta-btns{display:flex;flex-direction:column;gap:10px;flex-shrink:0;position:relative;z-index:1;}
.btn-cta-main{
  padding:14px 40px;border-radius:11px;
  font-size:.93rem;font-weight:700;
  background:#fff;color:#2742e8;text-align:center;
  box-shadow:0 6px 24px rgba(0,0,0,.15);
  transition:all .22s;
}
.btn-cta-main:hover{transform:translateY(-2px);box-shadow:0 10px 32px rgba(0,0,0,.22);}
.btn-cta-ghost{
  padding:14px 40px;border-radius:11px;
  font-size:.9rem;font-weight:500;
  border:1.5px solid rgba(255,255,255,.32);
  color:rgba(255,255,255,.8);text-align:center;transition:all .22s;
}
.btn-cta-ghost:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.6);color:#fff;}

/* ═══ FOOTER ═══ */
footer{
  border-top:1px solid var(--border);
  padding:36px 7% 28px;
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:14px;
  background:var(--off);
  position:relative;z-index:10;
}
.foot-brand{display:flex;align-items:center;gap:10px;}
.foot-badge{
  width:32px;height:32px;border-radius:8px;
  background:linear-gradient(135deg,var(--blue),var(--blue2));
  display:flex;align-items:center;justify-content:center;
  font-size:.85rem;
}
.foot-name{font-family:'Cormorant Garamond',serif;font-size:.95rem;color:var(--ink);font-weight:700;}
.foot-copy{font-size:.7rem;color:var(--subtle);}
.foot-links{display:flex;gap:20px;}
.foot-links a{font-size:.73rem;color:var(--subtle);transition:color .2s;}
.foot-links a:hover{color:var(--blue);}

/* ═══ SCROLL INDICATOR ═══ */
.scroll-hint{
  position:absolute;bottom:30px;left:50%;transform:translateX(-50%);
  z-index:3;display:flex;flex-direction:column;align-items:center;gap:7px;
  opacity:0;animation:revealUp .7s .9s forwards;
}
.scroll-mouse{
  width:22px;height:34px;border:1.5px solid rgba(59,92,246,.35);
  border-radius:11px;display:flex;justify-content:center;padding-top:5px;
}
.scroll-dot{width:3px;height:6px;background:var(--blue);border-radius:2px;animation:scrollDot 2s ease-in-out infinite;}
.scroll-label{font-size:.58rem;letter-spacing:.2em;text-transform:uppercase;color:var(--subtle);}

/* ═══ REVEAL ═══ */
.reveal{opacity:0;transform:translateY(26px);transition:opacity .6s ease,transform .6s ease;}
.reveal.show{opacity:1;transform:none;}
.reveal-l{opacity:0;transform:translateX(-26px);transition:opacity .6s ease,transform .6s ease;}
.reveal-l.show{opacity:1;transform:none;}
.reveal-r{opacity:0;transform:translateX(26px);transition:opacity .6s ease,transform .6s ease;}
.reveal-r.show{opacity:1;transform:none;}

/* ═══ KEYFRAMES ═══ */
@keyframes revealUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
@keyframes marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@keyframes booksScroll{from{transform:translateX(0)}to{transform:translateX(calc(-50% - 8px))}}
@keyframes scrollDot{0%{opacity:1;transform:translateY(0)}100%{opacity:0;transform:translateY(8px)}}
@keyframes floatBook{0%,100%{transform:rotate(8deg) translateY(0)}50%{transform:rotate(8deg) translateY(-12px)}}
@keyframes floatBook2{0%,100%{transform:rotate(-10deg) translateY(0)}50%{transform:rotate(-10deg) translateY(-10px)}}
@keyframes bookReveal{from{opacity:0;transform:rotate(8deg) translateY(20px)}to{opacity:.9;transform:rotate(8deg) translateY(0)}}
@keyframes bookReveal2{from{opacity:0;transform:rotate(-10deg) translateY(20px)}to{opacity:.8;transform:rotate(-10deg) translateY(0)}}

/* ═══ RESPONSIVE ═══ */
@media(max-width:1100px){
  .feat-grid{grid-template-columns:repeat(2,1fr);}
  .roles-grid{grid-template-columns:1fr;}
  .cta-block{grid-template-columns:1fr;padding:52px 36px;}
  .cta-block::before{display:none;}
  .hero-float-book,.hero-float-book2{display:none;}
}
@media(max-width:900px){
  .testi-grid{grid-template-columns:repeat(2,1fr);}
  .steps-grid{grid-template-columns:repeat(2,1fr);}
  .steps-grid .step:nth-child(2n){border-right:none;}
}
@media(max-width:768px){
  .nav-links,.nav-right{display:none;}
  .hamburger{display:block;}
  .feat-grid,.testi-grid,.steps-grid{grid-template-columns:1fr;}
  .hero-stats{flex-wrap:wrap;gap:18px;justify-content:center;}
  .hstat::before{display:none;}
  .cta-block{padding:40px 24px;}
  .sec{padding:72px 5%;}
  .cta-block{margin:0 5% 72px;}
}
@media(max-width:560px){
  .hero-h1{font-size:2.9rem;}
  .hero-ctas{flex-direction:column;align-items:stretch;}
}
</style>
</head>
<body>

<!-- Particles canvas -->
<canvas id="particles"></canvas>

<!-- Grain overlay -->
<div class="hero-grain"></div>

<!-- NAV -->
<nav class="nav" id="nav">
  <a href="index.php" class="nav-logo">
    <div class="nav-badge">📖</div>
    <div class="nav-name">Libra<span>Space</span></div>
  </a>
  <div class="nav-links">
    <a href="#koleksi">Koleksi</a>
    <a href="#fitur">Fitur</a>
    <a href="#cara">Cara Pakai</a>
    <a href="#pengguna">Pengguna</a>
    <a href="#ulasan">Ulasan</a>
  </div>
  <div class="nav-right">
    <?php if($loggedIn):?>
      <span style="font-size:.8rem;color:var(--subtle)">👋 <?=htmlspecialchars($username)?></span>
      <?php if($isAdmin):?><a href="admin/dashboard.php" class="btn-blue-nav">Dashboard Admin</a>
      <?php elseif($isPetugas):?><a href="petugas/dashboard.php" class="btn-blue-nav">Dashboard</a>
      <?php else:?><a href="anggota/dashboard.php" class="btn-blue-nav">Dashboard Saya</a><?php endif;?>
    <?php else:?>
      <a href="login.php" class="btn-ghost-nav">Masuk</a>
      <a href="register.php" class="btn-blue-nav">Daftar Gratis</a>
    <?php endif;?>
  </div>
  <button class="hamburger" onclick="document.getElementById('mob-drawer').classList.add('open')">☰</button>
</nav>

<!-- MOBILE DRAWER -->
<div class="drawer" id="mob-drawer">
  <button class="drawer-close" onclick="document.getElementById('mob-drawer').classList.remove('open')">✕</button>
  <a href="#koleksi">Koleksi</a>
  <a href="#fitur">Fitur</a>
  <a href="#cara">Cara Pakai</a>
  <a href="#pengguna">Pengguna</a>
  <?php if($loggedIn):?>
    <?php if($isAdmin):?><a href="admin/dashboard.php" style="color:var(--gold)">Dashboard Admin</a>
    <?php elseif($isPetugas):?><a href="petugas/dashboard.php" style="color:var(--gold)">Dashboard</a>
    <?php else:?><a href="anggota/dashboard.php" style="color:var(--gold)">Dashboard Saya</a><?php endif;?>
    <a href="logout.php">Keluar</a>
  <?php else:?>
    <a href="login.php">Masuk</a>
    <a href="register.php" style="color:var(--gold)">Daftar Gratis</a>
  <?php endif;?>
</div>

<!-- ══ HERO ══ -->
<section class="hero">
  <div class="hero-orb1"></div>
  <div class="hero-orb2"></div>
  <div class="hero-lines"></div>

  <!-- Floating books -->
  <div class="hero-float-book">
    <div class="fbook" style="width:90px;height:130px;background:linear-gradient(135deg,#dde8ff,#b8ccff);border-radius:4px 12px 12px 4px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;box-shadow:-4px 4px 20px rgba(59,92,246,.15)">📘</div>
  </div>
  <div class="hero-float-book2">
    <div class="fbook" style="width:75px;height:110px;background:linear-gradient(135deg,#d4f0e8,#a8e0cc);border-radius:4px 10px 10px 4px;display:flex;align-items:center;justify-content:center;font-size:2rem;box-shadow:-3px 3px 16px rgba(16,185,129,.15)">📗</div>
  </div>

  <div class="hero-eyebrow">
    <span class="hero-dot"></span>
    Sistem Perpustakaan Digital
  </div>

  <h1 class="hero-h1">
    Baca Lebih
    <em class="line2">Banyak,</em>
    <span class="line3">Lebih Jauh</span>
  </h1>

  <p class="hero-desc">
    Platform perpustakaan sekolah yang elegan dan modern. Temukan, pinjam, dan nikmati koleksi buku pilihan — dari mana saja, kapan saja.
  </p>

  <div class="hero-ctas">
    <?php if($isAdmin):?><a href="admin/dashboard.php" class="btn-hero-primary">⚡ Buka Dashboard</a>
    <?php elseif($isPetugas):?><a href="petugas/dashboard.php" class="btn-hero-primary">⚡ Buka Dashboard</a>
    <?php elseif($isAnggota):?><a href="anggota/katalog.php" class="btn-hero-primary">📚 Lihat Katalog</a><a href="anggota/dashboard.php" class="btn-hero-ghost">Dashboard Saya</a>
    <?php else:?><a href="register.php" class="btn-hero-primary">✨ Mulai Gratis</a><a href="login.php" class="btn-hero-ghost">Sudah punya akun →</a><?php endif;?>
  </div>

  <div class="hero-stats">
    <div class="hstat">
      <div class="hstat-n" data-count="<?=$total_buku?>"><?=$total_buku?>+</div>
      <div class="hstat-l">Koleksi Buku</div>
    </div>
    <div class="hstat">
      <div class="hstat-n" data-count="<?=$total_anggota?>"><?=$total_anggota?>+</div>
      <div class="hstat-l">Anggota Aktif</div>
    </div>
    <div class="hstat">
      <div class="hstat-n"><?=$total_pinjam?></div>
      <div class="hstat-l">Sedang Dipinjam</div>
    </div>
    <div class="hstat">
      <div class="hstat-n">24/7</div>
      <div class="hstat-l">Akses Online</div>
    </div>
  </div>

  <div class="scroll-hint">
    <div class="scroll-mouse"><div class="scroll-dot"></div></div>
    <span class="scroll-label">Scroll</span>
  </div>
</section>

<!-- ══ MARQUEE STRIP ══ -->
<div class="marquee-wrap">
  <div class="marquee-track">
    <?php $items=['Katalog Digital','Peminjaman Online','Hitung Denda Otomatis','Laporan Lengkap','Multi-Role Access','Ulasan & Rating','Manajemen Anggota','Koleksi Buku','Pengembalian Mudah','Sistem Modern']; $allItems=array_merge($items,$items,$items,$items); foreach($allItems as $it):?>
    <div class="marquee-item"><?=htmlspecialchars($it)?></div>
    <?php endforeach;?>
  </div>
</div>

<!-- ══ KOLEKSI BUKU ══ -->
<section class="sec" id="koleksi">
  <div class="reveal-l">
    <div class="sec-label">Koleksi Terbaru</div>
    <h2 class="sec-h">Temukan Buku<br><em>Favoritmu</em></h2>
    <p class="sec-sub">Koleksi buku terlengkap untuk semua minat. Selalu diperbarui setiap minggu oleh petugas perpustakaan.</p>
  </div>
  <div class="books-marquee-outer">
    <div class="books-marquee-track">
      <?php
      $sp=[['📘','135deg,#1a2a3d,#2c4a6e'],['📗','135deg,#0d2314,#1d4530'],['📕','135deg,#2d100a,#5a1e15'],['📙','135deg,#2a1d08,#4d3515'],['📓','135deg,#1a1a2e,#2d2d5e'],['📔','135deg,#1a2820,#2e4a3c']];
      $allBooks=$buku_arr;
      if(count($allBooks)<8){for($i=count($allBooks);$i<8;$i++)$allBooks[]=['judul_buku'=>'Judul Buku '.($i+1),'pengarang'=>'Pengarang','cover'=>''];}
      $doubledBooks=array_merge($allBooks,$allBooks);
      foreach($doubledBooks as $i=>$b):
        $si=$i%6;
      ?>
      <div class="bk">
        <div class="bk-cover" style="background:linear-gradient(<?=$sp[$si][1]?>)">
          <?php if(!empty($b['cover'])&&file_exists($b['cover'])):?>
          <img src="<?=htmlspecialchars($b['cover'])?>" alt="" loading="lazy">
          <?php else:?><?=$sp[$si][0]?><?php endif;?>
        </div>
        <div class="bk-info">
          <div class="bk-title"><?=htmlspecialchars($b['judul_buku'])?></div>
          <div class="bk-author"><?=htmlspecialchars($b['pengarang'])?></div>
        </div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div style="text-align:center;margin-top:40px">
    <?php if($isAnggota):?>
    <a href="anggota/katalog.php" class="btn-blue-nav" style="padding:13px 36px;border-radius:11px;font-size:.9rem">Lihat Semua Katalog →</a>
    <?php else:?>
    <a href="register.php" class="btn-blue-nav" style="padding:13px 36px;border-radius:11px;font-size:.9rem">Daftar untuk Akses Penuh →</a>
    <?php endif;?>
  </div>
</section>

<!-- ══ FITUR ══ -->
<section class="sec alt" id="fitur">
  <div class="reveal">
    <div class="sec-label">Fitur Unggulan</div>
    <h2 class="sec-h">Semua yang Dibutuhkan<br><em>Perpustakaan Modern</em></h2>
    <p class="sec-sub">Satu platform terintegrasi untuk seluruh operasional perpustakaan sekolah.</p>
  </div>
  <div class="feat-grid">
    <div class="feat reveal">
      <div class="feat-num">01</div>
      <div class="feat-ico">📚</div>
      <h3 class="feat-h">Katalog Digital</h3>
      <p class="feat-p">Cari buku tersedia secara real-time. Filter berdasarkan kategori, pengarang, atau status ketersediaan dengan antarmuka yang intuitif.</p>
    </div>
    <div class="feat reveal">
      <div class="feat-num">02</div>
      <div class="feat-ico">📋</div>
      <h3 class="feat-h">Peminjaman Online</h3>
      <p class="feat-p">Anggota ajukan peminjaman dari sistem. Petugas proses secara digital tanpa kertas — lebih cepat, lebih efisien, lebih hijau.</p>
    </div>
    <div class="feat reveal">
      <div class="feat-num">03</div>
      <div class="feat-ico">⏰</div>
      <h3 class="feat-h">Denda Otomatis</h3>
      <p class="feat-p">Sistem hitung jatuh tempo dan denda keterlambatan otomatis. Transparansi penuh untuk semua pihak tanpa perselisihan.</p>
    </div>
    <div class="feat reveal">
      <div class="feat-num">04</div>
      <div class="feat-ico">📊</div>
      <h3 class="feat-h">Laporan Lengkap</h3>
      <p class="feat-p">Cetak laporan anggota, buku, peminjaman, dan denda. Format rapi siap print — PDF, Excel, dan tampilan langsung di browser.</p>
    </div>
    <div class="feat reveal">
      <div class="feat-num">05</div>
      <div class="feat-ico">👥</div>
      <h3 class="feat-h">Multi-Role Access</h3>
      <p class="feat-p">Admin, Petugas, dan Anggota — dashboard dan hak akses berbeda yang dirancang tepat untuk setiap peran dan tanggung jawab.</p>
    </div>
    <div class="feat reveal">
      <div class="feat-num">06</div>
      <div class="feat-ico">⭐</div>
      <h3 class="feat-h">Ulasan & Rating</h3>
      <p class="feat-p">Anggota beri ulasan dan rating bintang 1–5 untuk buku yang pernah dipinjam. Bantu sesama pembaca temukan buku terbaik.</p>
    </div>
  </div>
</section>

<!-- ══ CARA PAKAI ══ -->
<section class="sec" id="cara">
  <div class="reveal">
    <div class="sec-label">Cara Kerja</div>
    <h2 class="sec-h">Mulai dalam <em>4 Langkah</em></h2>
    <p class="sec-sub">Proses yang sederhana dan intuitif untuk semua pengguna — dari siswa pertama kali hingga admin berpengalaman.</p>
  </div>
  <div class="steps-grid">
    <div class="step reveal">
      <div class="step-line"></div>
      <div class="step-n">01</div>
      <div class="step-h">Daftar Akun</div>
      <p class="step-p">Buat akun dengan NIS, nama lengkap, dan kelas kamu. Proses hanya 1 menit.</p>
    </div>
    <div class="step reveal">
      <div class="step-line"></div>
      <div class="step-n">02</div>
      <div class="step-h">Cari Buku</div>
      <p class="step-p">Jelajahi katalog lengkap dan temukan buku yang ingin kamu baca hari ini.</p>
    </div>
    <div class="step reveal">
      <div class="step-line"></div>
      <div class="step-n">03</div>
      <div class="step-h">Pinjam Buku</div>
      <p class="step-p">Ajukan permohonan peminjaman dan ambil buku di perpustakaan setelah dikonfirmasi petugas.</p>
    </div>
    <div class="step reveal">
      <div class="step-n">04</div>
      <div class="step-h">Kembalikan</div>
      <p class="step-p">Kembalikan tepat waktu, lalu tulis ulasan untuk membantu sesama pembaca.</p>
    </div>
  </div>
</section>

<!-- ══ PENGGUNA ══ -->
<section class="sec alt" id="pengguna">
  <div class="reveal">
    <div class="sec-label">Jenis Pengguna</div>
    <h2 class="sec-h">Dirancang untuk<br><em>Semua Peran</em></h2>
    <p class="sec-sub">Setiap pengguna memiliki dashboard dan hak akses yang dirancang khusus sesuai tanggung jawabnya.</p>
  </div>
  <div class="roles-grid">
    <div class="role role-admin reveal">
      <span class="role-ico">🛡️</span>
      <span class="role-badge">Admin</span>
      <h3 class="role-h">Administrator</h3>
      <p class="role-p">Kendali penuh atas seluruh sistem perpustakaan, termasuk manajemen pengguna dan pelaporan.</p>
      <ul class="role-list">
        <li>Kelola Admin &amp; Petugas</li>
        <li>Kelola semua data anggota</li>
        <li>Akses laporan lengkap</li>
        <li>Kelola buku &amp; kategori</li>
        <li>Monitor transaksi &amp; denda</li>
      </ul>
    </div>
    <div class="role role-petugas reveal">
      <span class="role-ico">👨‍💼</span>
      <span class="role-badge">Petugas</span>
      <h3 class="role-h">Petugas Perpustakaan</h3>
      <p class="role-p">Mengelola operasional harian: peminjaman, pengembalian, dan administrasi koleksi buku.</p>
      <ul class="role-list">
        <li>Proses peminjaman buku</li>
        <li>Catat pengembalian</li>
        <li>Kelola koleksi buku</li>
        <li>Proses pembayaran denda</li>
        <li>Cetak laporan operasional</li>
      </ul>
    </div>
    <div class="role role-anggota reveal">
      <span class="role-ico">🎓</span>
      <span class="role-badge">Anggota</span>
      <h3 class="role-h">Anggota / Siswa</h3>
      <p class="role-p">Akses katalog, pinjam buku, cek riwayat, dan berikan ulasan untuk membantu sesama pembaca.</p>
      <ul class="role-list">
        <li>Cari buku di katalog</li>
        <li>Ajukan peminjaman</li>
        <li>Lihat riwayat pinjaman</li>
        <li>Cek status &amp; denda</li>
        <li>Tulis ulasan &amp; rating</li>
      </ul>
    </div>
  </div>
</section>

<!-- ══ ULASAN BUKU ══ -->
<?php if(!empty($ulasan_arr)):?>
<section class="sec" id="ulasan">
  <div class="reveal">
    <div class="sec-label">Kata Mereka</div>
    <h2 class="sec-h">Ulasan dari<br><em>Pembaca Kami</em></h2>
    <p class="sec-sub">Pendapat jujur dari anggota perpustakaan tentang buku-buku yang mereka baca.</p>
  </div>
  <div class="testi-grid">
    <?php foreach($ulasan_arr as $idx=>$u):
      $stars=$u['rating']??5;
      $initials=strtoupper(mb_substr($u['nama_anggota'],0,1).''.mb_substr(explode(' ',$u['nama_anggota'])[1]??'',0,1));
    ?>
    <div class="testi reveal" style="transition-delay:<?=$idx*.08?>s">
      <div class="testi-stars"><?php for($s=1;$s<=5;$s++)echo '<span>'.($s<=$stars?'★':'☆').'</span>';?></div>
      <div class="testi-text"><?=htmlspecialchars(mb_strimwidth($u['ulasan']??'Buku yang sangat bagus dan bermanfaat!',0,120,'…'))?></div>
      <div class="testi-author">
        <div class="testi-avatar"><?=htmlspecialchars($initials)?></div>
        <div>
          <div class="testi-name"><?=htmlspecialchars($u['nama_anggota'])?></div>
          <div class="testi-book">📖 <?=htmlspecialchars(mb_strimwidth($u['judul_buku'],0,36,'…'))?></div>
        </div>
      </div>
    </div>
    <?php endforeach;?>
  </div>
</section>
<?php else:?>
<!-- Fallback testimonials if no data -->
<section class="sec" id="ulasan">
  <div class="reveal">
    <div class="sec-label">Kata Mereka</div>
    <h2 class="sec-h">Ulasan dari<br><em>Pembaca Kami</em></h2>
    <p class="sec-sub">Daftar dan mulai baca — jadilah yang pertama memberikan ulasan!</p>
  </div>
  <div class="testi-grid">
    <?php
    $fallback=[
      ['nama'=>'Budi Santoso','buku'=>'Laskar Pelangi','rating'=>5,'teks'=>'Buku luar biasa! Sistem peminjaman yang sangat mudah digunakan. Saya bisa cari buku favorit tanpa harus ke perpustakaan dulu.'],
      ['nama'=>'Siti Rahayu','buku'=>'Bumi Manusia','rating'=>5,'teks'=>'Sangat membantu! Pengingat jatuh tempo membuat saya tidak pernah terlambat lagi. Terima kasih LibraSpace!'],
      ['nama'=>'Ahmad Fauzi','buku'=>'Sang Pemimpi','rating'=>4,'teks'=>'Interface yang elegan dan mudah dipahami. Fitur ulasan buku membuat saya bisa temukan buku berkualitas lebih cepat.'],
    ];
    foreach($fallback as $idx=>$fb):
      $ini=strtoupper(substr($fb['nama'],0,1).substr(explode(' ',$fb['nama'])[1]??'',0,1));
    ?>
    <div class="testi reveal" style="transition-delay:<?=$idx*.08?>s">
      <div class="testi-stars"><?php for($s=1;$s<=5;$s++)echo '<span>'.($s<=$fb['rating']?'★':'☆').'</span>';?></div>
      <div class="testi-text"><?=htmlspecialchars($fb['teks'])?></div>
      <div class="testi-author">
        <div class="testi-avatar"><?=htmlspecialchars($ini)?></div>
        <div>
          <div class="testi-name"><?=htmlspecialchars($fb['nama'])?></div>
          <div class="testi-book">📖 <?=htmlspecialchars($fb['buku'])?></div>
        </div>
      </div>
    </div>
    <?php endforeach;?>
  </div>
</section>
<?php endif;?>

<!-- ══ CTA ══ -->
<div class="cta-block reveal">
  <div>
    <h2 class="cta-h">Siap Mulai<br><em>Perjalanan Membaca?</em></h2>
    <p class="cta-sub">Bergabung sekarang dan nikmati akses ke seluruh koleksi buku perpustakaan.<br>Gratis untuk semua siswa terdaftar.</p>
  </div>
  <div class="cta-btns">
    <a href="register.php" class="btn-cta-main">Daftar Sekarang</a>
    <a href="login.php" class="btn-cta-ghost">Masuk ke Akun</a>
  </div>
</div>

<footer>
  <div class="foot-brand">
    <div class="foot-badge">📖</div>
    <div class="foot-name">LibraSpace</div>
  </div>
  <p class="foot-copy">© <?=date('Y')?> Sistem Perpustakaan Digital · All rights reserved</p>
  <div class="foot-links">
    <a href="login.php">Login</a>
    <a href="register.php">Daftar</a>
    <a href="setup.php">Setup</a>
  </div>
</footer>

<script>
/* ── Scroll nav ── */
const nav=document.getElementById('nav');
window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',scrollY>60));

/* ── Smooth scroll ── */
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const t=document.querySelector(a.getAttribute('href'));
    if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth'});document.getElementById('mob-drawer').classList.remove('open');}
  });
});

/* ── Reveal on scroll ── */
const ro=new IntersectionObserver(es=>{
  es.forEach(el=>{
    if(el.isIntersecting){
      const siblings=Array.from(el.target.parentElement.children).filter(c=>c.classList.contains('reveal')||c.classList.contains('reveal-l')||c.classList.contains('reveal-r'));
      const idx=siblings.indexOf(el.target);
      setTimeout(()=>{
        el.target.classList.add('show');
      },Math.min(idx,6)*90);
      ro.unobserve(el.target);
    }
  });
},{threshold:.12});
document.querySelectorAll('.reveal,.reveal-l,.reveal-r').forEach(el=>ro.observe(el));

/* ── Particles ── */
(function(){
  const c=document.getElementById('particles');
  const ctx=c.getContext('2d');
  let W,H,pts=[];
  function resize(){W=c.width=innerWidth;H=c.height=innerHeight;}
  resize();window.addEventListener('resize',resize);
  const N=60;
  for(let i=0;i<N;i++)pts.push({
    x:Math.random()*W,y:Math.random()*H,
    vx:(Math.random()-.5)*.25,vy:(Math.random()-.5)*.25,
    r:Math.random()*1.2+.3,o:Math.random()*.4+.1
  });
  function draw(){
    ctx.clearRect(0,0,W,H);
    pts.forEach(p=>{
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0)p.x=W;if(p.x>W)p.x=0;
      if(p.y<0)p.y=H;if(p.y>H)p.y=0;
      ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle=`rgba(59,92,246,${p.o})`;ctx.fill();
    });
    // Connect nearby
    pts.forEach((a,i)=>{
      pts.slice(i+1).forEach(b=>{
        const d=Math.hypot(a.x-b.x,a.y-b.y);
        if(d<120){
          ctx.beginPath();ctx.moveTo(a.x,a.y);ctx.lineTo(b.x,b.y);
          ctx.strokeStyle=`rgba(59,92,246,${.06*(1-d/120)})`;
          ctx.lineWidth=.5;ctx.stroke();
        }
      });
    });
    requestAnimationFrame(draw);
  }
  draw();
})();
</script>
</body>
</html>
