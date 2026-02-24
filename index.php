<?php
require_once 'includes/session.php';
require_once 'config/database.php';

initSession();

$isAdmin   = false;
$isPetugas = false;
$isAnggota = false;
$username  = '';
$loggedIn  = false; // ← ini biar gak undefined

if (isset($_SESSION['pengguna_logged_in'])) {
    $loggedIn = true;
    $username = $_SESSION['pengguna_username'] ?? '';
    
    if ($_SESSION['pengguna_level'] === 'admin') {
        $isAdmin = true;
    } elseif ($_SESSION['pengguna_level'] === 'petugas') {
        $isPetugas = true;
    }
}

if (isset($_SESSION['anggota_logged_in'])) {
    $loggedIn = true;
    $username = $_SESSION['anggota_nama'] ?? '';
    $isAnggota = true;
}

$conn = getConnection();

$query = $conn->query("SELECT COUNT(*) AS total FROM buku");

if (!$query) {
    die("Query error: " . $conn->error);
}

$data = $query->fetch_assoc();

?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Digital — Selamat Datang</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;0,9..144,700;1,9..144,400;1,9..144,600&family=Outfit:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <style>
    /* ─── TOKENS ─────────────────────────────── */
    :root {
        --bg: #f4ede0;
        --bg2: #ede4d2;
        --paper: #faf6ef;
        --white: #ffffff;
        --ink: #1c1509;
        --ink2: #3b2f1e;
        --muted: #7c6b52;
        --border: rgba(80, 55, 25, .1);
        --rust: #b84a2c;
        --rust2: #d05a38;
        --gold: #c48a20;
        --sage: #496640;
        --navy: #2c4f7c;
        --sh1: 0 1px 12px rgba(28, 21, 9, .07);
        --sh2: 0 6px 32px rgba(28, 21, 9, .13);
        --sh3: 0 16px 60px rgba(28, 21, 9, .18);
    }

    *,
    *::before,
    *::after {
        margin: 0;
        padding: 0;
        box-sizing: border-box
    }

    html {
        scroll-behavior: smooth;
        font-size: 16px
    }

    body {
        font-family: 'Outfit', sans-serif;
        background: var(--bg);
        color: var(--ink);
        overflow-x: hidden
    }

    img {
        display: block;
        max-width: 100%
    }

    a {
        text-decoration: none;
        color: inherit
    }

    /* ─── NAVBAR ────────────────────────────── */
    .nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 200;
        height: 66px;
        padding: 0 5%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(244, 237, 224, .9);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--border);
        transition: box-shadow .3s;
    }

    .nav.stuck {
        box-shadow: var(--sh1)
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 11px
    }

    .logo-icon {
        width: 40px;
        height: 40px;
        border-radius: 9px;
        background: linear-gradient(135deg, var(--rust), var(--rust2));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        box-shadow: 0 3px 14px rgba(184, 74, 44, .28);
    }

    .logo-name {
        font-family: 'Fraunces', serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.15
    }

    .logo-sub {
        font-size: .65rem;
        color: var(--muted);
        font-weight: 400;
        letter-spacing: .03em
    }

    .nav-center {
        display: flex;
        gap: 4px
    }

    .nav-link {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: .87rem;
        font-weight: 500;
        color: var(--ink2);
        transition: background .2s, color .2s;
    }

    .nav-link:hover {
        background: var(--bg2);
        color: var(--ink)
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 8px
    }

    .btn-ghost-sm {
        padding: 9px 20px;
        border-radius: 8px;
        font-size: .87rem;
        font-weight: 500;
        border: 1.5px solid var(--border);
        color: var(--ink2);
        transition: all .22s;
    }

    .btn-ghost-sm:hover {
        border-color: var(--rust);
        color: var(--rust)
    }

    .btn-solid-sm {
        padding: 9px 22px;
        border-radius: 8px;
        font-size: .87rem;
        font-weight: 600;
        background: var(--rust);
        color: #fff;
        box-shadow: 0 3px 14px rgba(184, 74, 44, .26);
        transition: all .22s;
    }

    .btn-solid-sm:hover {
        background: var(--rust2);
        transform: translateY(-1px);
        box-shadow: 0 5px 20px rgba(184, 74, 44, .36)
    }

    .hamburger {
        display: none;
        background: none;
        border: none;
        font-size: 1.4rem;
        cursor: pointer;
        color: var(--ink);
        padding: 4px
    }

    /* ─── HERO ──────────────────────────────── */
    .hero {
        min-height: 100vh;
        padding-top: 66px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        position: relative;
        overflow: hidden;
    }

    .hero-left {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 72px 5% 72px 8%;
        position: relative;
        z-index: 2;
    }

    .eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 7px 15px;
        border-radius: 40px;
        background: var(--white);
        border: 1px solid var(--border);
        font-size: .7rem;
        letter-spacing: .13em;
        text-transform: uppercase;
        color: var(--muted);
        width: fit-content;
        margin-bottom: 26px;
        box-shadow: var(--sh1);
        opacity: 0;
        animation: up .7s .08s ease forwards;
    }

    .eyebrow-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--sage)
    }

    .hero-h1 {
        font-family: 'Fraunces', serif;
        font-size: clamp(2.8rem, 4.8vw, 4.4rem);
        font-weight: 700;
        line-height: 1.07;
        margin-bottom: 22px;
        opacity: 0;
        animation: up .7s .18s ease forwards;
    }

    .hero-h1 em {
        font-style: italic;
        color: var(--rust)
    }

    .hero-p {
        font-size: 1.05rem;
        line-height: 1.82;
        color: var(--muted);
        font-weight: 300;
        max-width: 450px;
        margin-bottom: 38px;
        opacity: 0;
        animation: up .7s .28s ease forwards;
    }

    .hero-btns {
        display: flex;
        align-items: center;
        gap: 13px;
        flex-wrap: wrap;
        opacity: 0;
        animation: up .7s .38s ease forwards;
    }

    .btn-lg {
        padding: 15px 32px;
        border-radius: 11px;
        font-size: .97rem;
        font-weight: 600;
        letter-spacing: .02em;
        transition: all .25s;
    }

    .btn-primary-lg {
        background: var(--rust);
        color: #fff;
        box-shadow: 0 4px 22px rgba(184, 74, 44, .32);
    }

    .btn-primary-lg:hover {
        background: var(--rust2);
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(184, 74, 44, .42)
    }

    .btn-outline-lg {
        border: 1.5px solid var(--border);
        color: var(--ink2);
        background: var(--white);
    }

    .btn-outline-lg:hover {
        border-color: var(--rust);
        color: var(--rust);
        background: rgba(184, 74, 44, .03)
    }

    .hero-nums {
        display: flex;
        gap: 0;
        margin-top: 52px;
        opacity: 0;
        animation: up .7s .48s ease forwards;
        border-top: 1px solid var(--border);
        padding-top: 36px;
    }

    .hnum {
        padding: 0 30px 0 0;
        position: relative
    }

    .hnum+.hnum::before {
        content: '';
        position: absolute;
        left: 0;
        top: 20%;
        bottom: 20%;
        width: 1px;
        background: var(--border);
    }

    .hnum+.hnum {
        padding-left: 30px
    }

    .hnum-n {
        font-family: 'Fraunces', serif;
        font-size: 2.1rem;
        font-weight: 700;
        color: var(--rust);
        line-height: 1
    }

    .hnum-l {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--muted);
        margin-top: 4px
    }

    /* right side bookshelf illustration */
    .hero-right {
        position: relative;
        overflow: hidden;
        background: linear-gradient(160deg, var(--bg2) 0%, #e6d9c0 60%, #d8cbb0 100%);
    }

    .shelf-scene {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 10px;
        padding: 80px 40px;
    }

    /* single shelf row */
    .shelf-row {
        display: flex;
        align-items: flex-end;
        gap: 3px;
        background: linear-gradient(to bottom, #c2a87a, #a8906a);
        border-radius: 2px 2px 0 0;
        padding: 8px 24px 0;
        position: relative;
        width: 100%;
        max-width: 440px;
    }

    .shelf-row::after {
        content: '';
        position: absolute;
        bottom: -9px;
        left: -3px;
        right: -3px;
        height: 11px;
        background: linear-gradient(to bottom, #9a7a54, #866844);
        border-radius: 0 0 5px 5px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
    }

    /* books */
    .bk {
        border-radius: 2px 2px 0 0;
        position: relative;
        cursor: default;
        transition: transform .28s ease;
        flex-shrink: 0
    }

    .bk:hover {
        transform: translateY(-9px)
    }

    .bk::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 3px;
        bottom: 0;
        background: rgba(0, 0, 0, .1);
        border-radius: 0 2px 0 0
    }

    /* color variants */
    .r {
        background: linear-gradient(to right, #a63820, #c44830)
    }

    .b {
        background: linear-gradient(to right, #284878, #3460a0)
    }

    .g {
        background: linear-gradient(to right, #3a5830, #4e7040)
    }

    .o {
        background: linear-gradient(to right, #a87020, #c88e30)
    }

    .p {
        background: linear-gradient(to right, #5a3475, #7448a0)
    }

    .t {
        background: linear-gradient(to right, #1e5852, #28706a)
    }

    .n {
        background: linear-gradient(to right, #182840, #243858)
    }

    .m {
        background: linear-gradient(to right, #6a2020, #882828)
    }

    /* size utility */
    .w16 {
        width: 16px
    }

    .w20 {
        width: 20px
    }

    .w24 {
        width: 24px
    }

    .w28 {
        width: 28px
    }

    .w32 {
        width: 32px
    }

    .w36 {
        width: 36px
    }

    .h70 {
        height: 70px
    }

    .h84 {
        height: 84px
    }

    .h96 {
        height: 96px
    }

    .h108 {
        height: 108px
    }

    .h120 {
        height: 120px
    }

    .h136 {
        height: 136px
    }

    .h150 {
        height: 150px
    }

    /* floating info cards */
    .fcard {
        position: absolute;
        background: var(--white);
        border-radius: 14px;
        padding: 16px 20px;
        box-shadow: var(--sh3);
        border: 1px solid var(--border);
        z-index: 10;
    }

    .fcard-ico {
        font-size: 1.3rem;
        margin-bottom: 5px
    }

    .fcard-label {
        font-size: .7rem;
        color: var(--muted);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: .07em
    }

    .fcard-val {
        font-family: 'Fraunces', serif;
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.1
    }

    .fcard-sub {
        font-size: .68rem;
        color: var(--muted)
    }

    .fcard1 {
        bottom: 90px;
        right: 16px;
        animation: float1 4s ease-in-out infinite;
        width: 150px
    }

    .fcard2 {
        top: 110px;
        left: 10px;
        animation: float1 5s 1.2s ease-in-out infinite;
        width: 155px
    }

    .fcard3 {
        bottom: 190px;
        left: 30px;
        padding: 12px 16px;
        animation: float1 6s .5s ease-in-out infinite;
        width: 140px;
    }

    .hero-bg-deco {
        position: absolute;
        top: -80px;
        right: -80px;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: radial-gradient(rgba(196, 138, 32, .1), transparent 70%);
        pointer-events: none;
    }

    @keyframes float1 {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-10px)
        }
    }

    @keyframes up {
        from {
            opacity: 0;
            transform: translateY(24px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    /* ─── STATS BAR ─────────────────────────── */
    .stats-bar {
        background: var(--ink);
        padding: 36px 8%;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1px;
        position: relative;
    }

    .stats-bar::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(184, 74, 44, .08), transparent, rgba(196, 138, 32, .06))
    }

    .sbar-item {
        padding: 24px 32px;
        text-align: center;
        border-right: 1px solid rgba(255, 255, 255, .06);
    }

    .sbar-item:last-child {
        border-right: none
    }

    .sbar-n {
        font-family: 'Fraunces', serif;
        font-size: 2.3rem;
        font-weight: 700;
        color: var(--rust2);
        line-height: 1
    }

    .sbar-l {
        font-size: .75rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .4);
        margin-top: 6px
    }

    /* ─── FEATURES ──────────────────────────── */
    .sec {
        padding: 96px 8%
    }

    .sec-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: .7rem;
        letter-spacing: .15em;
        text-transform: uppercase;
        color: var(--rust);
        font-weight: 600;
        margin-bottom: 14px;
    }

    .sec-h2 {
        font-family: 'Fraunces', serif;
        font-size: clamp(1.9rem, 3.2vw, 2.9rem);
        font-weight: 700;
        line-height: 1.14;
        margin-bottom: 12px;
    }

    .sec-p {
        font-size: 1rem;
        color: var(--muted);
        font-weight: 300;
        line-height: 1.76;
        max-width: 520px
    }

    .feat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
        margin-top: 56px
    }

    .feat {
        background: var(--paper);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 32px 28px;
        position: relative;
        overflow: hidden;
        transition: all .28s ease;
    }

    .feat::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--fc, var(--rust));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .3s ease;
    }

    .feat:hover {
        transform: translateY(-5px);
        box-shadow: var(--sh2);
        background: var(--white)
    }

    .feat:hover::after {
        transform: scaleX(1)
    }

    .feat-ico {
        width: 54px;
        height: 54px;
        border-radius: 13px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: var(--fi, rgba(184, 74, 44, .08));
    }

    .feat-h {
        font-family: 'Fraunces', serif;
        font-size: 1.12rem;
        font-weight: 600;
        margin-bottom: 10px
    }

    .feat-p {
        font-size: .875rem;
        color: var(--muted);
        line-height: 1.72;
        font-weight: 300
    }

    /* ─── HOW IT WORKS ──────────────────────── */
    .how {
        background: var(--ink);
        padding: 96px 8%;
        position: relative;
        overflow: hidden
    }

    .how::before {
        content: '';
        position: absolute;
        top: -150px;
        right: -150px;
        width: 500px;
        height: 500px;
        border-radius: 50%;
        background: radial-gradient(rgba(184, 74, 44, .1), transparent 70%);
        pointer-events: none;
    }

    .how .sec-h2 {
        color: #fff
    }

    .how .sec-p {
        color: rgba(255, 255, 255, .45)
    }

    .how .sec-eyebrow {
        color: var(--rust2)
    }

    .steps {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 28px;
        margin-top: 56px;
        position: relative
    }

    .steps::before {
        content: '';
        position: absolute;
        top: 29px;
        left: calc(12.5% + 14px);
        right: calc(12.5% + 14px);
        height: 1px;
        background: rgba(255, 255, 255, .08);
        z-index: 0;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        z-index: 1
    }

    .step-n {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: var(--rust);
        color: #fff;
        font-family: 'Fraunces', serif;
        font-size: 1.35rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 18px;
        box-shadow: 0 4px 20px rgba(184, 74, 44, .4);
    }

    .step-h {
        font-size: .95rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 7px
    }

    .step-p {
        font-size: .8rem;
        color: rgba(255, 255, 255, .4);
        line-height: 1.65;
        font-weight: 300
    }

    /* ─── CATEGORIES ────────────────────────── */
    .cats-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 14px;
        margin-top: 48px;
    }

    .cat {
        background: var(--paper);
        border: 1px solid var(--border);
        border-radius: 15px;
        padding: 26px 16px;
        text-align: center;
        transition: all .24s;
        cursor: default;
    }

    .cat:hover {
        background: var(--white);
        transform: translateY(-3px);
        box-shadow: var(--sh1)
    }

    .cat-ico {
        font-size: 1.9rem;
        margin-bottom: 10px
    }

    .cat-n {
        font-size: .8rem;
        font-weight: 600;
        color: var(--ink2)
    }

    .cat-c {
        font-size: .68rem;
        color: var(--muted);
        margin-top: 3px
    }

    /* ─── ROLES ─────────────────────────────── */
    .roles-sec {
        padding: 96px 8%;
        background: var(--bg2)
    }

    .roles-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
        margin-top: 54px
    }

    .role {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 36px 30px;
        transition: all .28s;
    }

    .role:hover {
        transform: translateY(-5px);
        box-shadow: var(--sh2)
    }

    .role-badge {
        display: inline-block;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 20px;
    }

    .role-admin .role-badge {
        background: rgba(184, 74, 44, .1);
        color: var(--rust)
    }

    .role-petugas .role-badge {
        background: rgba(44, 79, 124, .1);
        color: var(--navy)
    }

    .role-anggota .role-badge {
        background: rgba(73, 102, 64, .1);
        color: var(--sage)
    }

    .role-ico {
        font-size: 2.2rem;
        margin-bottom: 14px
    }

    .role-h {
        font-family: 'Fraunces', serif;
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 10px
    }

    .role-p {
        font-size: .86rem;
        color: var(--muted);
        line-height: 1.72;
        margin-bottom: 18px;
        font-weight: 300
    }

    .role-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 7px
    }

    .role-list li {
        font-size: .82rem;
        color: var(--ink2);
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .role-list li::before {
        content: '✓';
        color: var(--sage);
        font-weight: 700;
        flex-shrink: 0;
        margin-top: .05em
    }

    /* ─── CTA ───────────────────────────────── */
    .cta-wrap {
        padding: 0 8% 96px
    }

    .cta {
        background: linear-gradient(135deg, var(--rust) 0%, #7e2510 100%);
        border-radius: 24px;
        padding: 72px 64px;
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 40px;
        position: relative;
        overflow: hidden;
    }

    .cta::before {
        content: '📚';
        position: absolute;
        right: 240px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 11rem;
        opacity: .06;
        user-select: none;
        line-height: 1;
    }

    .cta-h {
        font-family: 'Fraunces', serif;
        font-size: 2.3rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px
    }

    .cta-p {
        font-size: 1rem;
        color: rgba(255, 255, 255, .6);
        line-height: 1.65;
        font-weight: 300
    }

    .cta-btns {
        display: flex;
        flex-direction: column;
        gap: 11px;
        flex-shrink: 0
    }

    .btn-wh {
        padding: 14px 34px;
        border-radius: 10px;
        font-size: .95rem;
        font-weight: 700;
        white-space: nowrap;
        text-align: center;
        background: #fff;
        color: var(--rust);
        box-shadow: 0 4px 18px rgba(0, 0, 0, .18);
        transition: all .24s;
    }

    .btn-wh:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 28px rgba(0, 0, 0, .25)
    }

    .btn-whl {
        padding: 14px 34px;
        border-radius: 10px;
        font-size: .95rem;
        font-weight: 500;
        white-space: nowrap;
        text-align: center;
        border: 1.5px solid rgba(255, 255, 255, .38);
        color: #fff;
        transition: all .24s;
    }

    .btn-whl:hover {
        background: rgba(255, 255, 255, .1);
        border-color: rgba(255, 255, 255, .7)
    }

    /* ─── FOOTER ─────────────────────────────── */
    footer {
        background: var(--ink);
        padding: 44px 8% 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 18px;
    }

    .foot-logo {
        display: flex;
        align-items: center;
        gap: 10px
    }

    .foot-logo-ico {
        width: 34px;
        height: 34px;
        border-radius: 7px;
        background: var(--rust);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem
    }

    .foot-name {
        font-family: 'Fraunces', serif;
        font-size: .95rem;
        font-weight: 700;
        color: #fff
    }

    .foot-copy {
        font-size: .76rem;
        color: rgba(255, 255, 255, .28)
    }

    .foot-links {
        display: flex;
        gap: 22px
    }

    .foot-links a {
        font-size: .78rem;
        color: rgba(255, 255, 255, .32);
        transition: color .2s
    }

    .foot-links a:hover {
        color: rgba(255, 255, 255, .72)
    }

    /* scrollbar */
    ::-webkit-scrollbar {
        width: 5px
    }

    ::-webkit-scrollbar-track {
        background: var(--bg)
    }

    ::-webkit-scrollbar-thumb {
        background: #c4b090;
        border-radius: 5px
    }

    /* ─── SCROLL REVEAL ─────────────────────── */
    .reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity .55s ease, transform .55s ease
    }

    .reveal.show {
        opacity: 1;
        transform: none
    }

    /* ─── RESPONSIVE ─────────────────────────── */
    @media(max-width:1100px) {
        .feat-grid {
            grid-template-columns: repeat(2, 1fr)
        }

        .cats-grid {
            grid-template-columns: repeat(3, 1fr)
        }
    }

    @media(max-width:860px) {
        .hero {
            grid-template-columns: 1fr
        }

        .hero-right {
            display: none
        }

        .hero-left {
            padding: 56px 6%
        }

        .stats-bar {
            grid-template-columns: repeat(2, 1fr)
        }

        .steps {
            grid-template-columns: repeat(2, 1fr)
        }

        .steps::before {
            display: none
        }

        .roles-grid {
            grid-template-columns: 1fr
        }

        .cta {
            grid-template-columns: 1fr;
            padding: 50px 36px
        }

        .cta::before {
            display: none
        }

        .nav-center,
        .nav-right {
            display: none
        }

        .hamburger {
            display: block
        }
    }

    @media(max-width:560px) {
        .feat-grid {
            grid-template-columns: 1fr
        }

        .cats-grid {
            grid-template-columns: repeat(2, 1fr)
        }

        .steps {
            grid-template-columns: 1fr
        }

        .cta-btns {
            flex-direction: row;
            flex-wrap: wrap
        }

        .stats-bar {
            grid-template-columns: 1fr 1fr
        }
    }
    </style>
</head>

<body>

    <!-- ╔══════════════════════════╗
     ║         NAVBAR           ║
     ╚══════════════════════════╝ -->
    <nav class="nav" id="mainNav">
        <a href="index.php" class="logo">
            <div class="logo-icon">📖</div>
            <div>
                <div class="logo-name">Perpustakaan Digital</div>
                <div class="logo-sub">Sistem Peminjaman Buku</div>
            </div>
        </a>

        <div class="nav-center">
            <a href="#fitur" class="nav-link">Fitur</a>
            <a href="#cara" class="nav-link">Cara Pakai</a>
            <a href="#koleksi" class="nav-link">Koleksi</a>
            <a href="#pengguna" class="nav-link">Pengguna</a>
        </div>

        <div class="nav-right">
            <?php if ($loggedIn): ?>
            <span>Halo, <?= htmlspecialchars($username) ?></span>
            <a href="logout.php" class="btn-ghost-sm">Logout</a>
            <?php else: ?>
            <a href="login.php" class="btn-ghost-sm">Masuk</a>
            <a href="register.php" class="btn-solid-sm">Daftar Gratis</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ╔══════════════════════════╗
     ║         HERO             ║
     ╚══════════════════════════╝ -->
    <section class="hero">
        <!-- LEFT -->
        <div class="hero-left">
            <div class="eyebrow">
                <span class="eyebrow-dot"></span>
                Sistem Perpustakaan Modern
            </div>

            <h1 class="hero-h1">
                Akses Ribuan Buku<br>
                <em>Kapan Saja,</em><br>
                Di Mana Saja
            </h1>

            <p class="hero-p">
                Platform perpustakaan digital sekolah yang memudahkan peminjaman,
                pengembalian, dan pengelolaan koleksi buku secara efisien dan modern.
            </p>

            <div class="hero-btns">
                <?php if ($isAdmin): ?>
                <a href="admin/dashboard.php">Dashboard</a>
                <?php elseif ($isPetugas): ?>
                <a href="petugas/dashboard.php">Dashboard</a>
                <?php elseif ($isAnggota): ?>
                <a href="anggota/dashboard.php">Dashboard</a>
                <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Daftar</a>
                <?php endif; ?>

            </div>


            <div class="hero-nums">
                <div class="hnum">
                    <div class="hnum-n"><?= $data['total']; ?></div>
                    <div class="hnum-l">Koleksi Buku</div>
                </div>
                <div class="hnum">
                    <div class="hnum-n">3</div>
                    <div class="hnum-l">Level Akses</div>
                </div>
                <div class="hnum">
                    <div class="hnum-n">24/7</div>
                    <div class="hnum-l">Akses Online</div>
                </div>
            </div>
        </div>

        <!-- RIGHT — bookshelf illustration -->
        <div class="hero-right">
            <div class="hero-bg-deco"></div>

            <!-- floating card top-left -->
            <div class="fcard fcard2">
                <div class="fcard-ico">📚</div>
                <div class="fcard-label">Koleksi</div>
                <div class="fcard-val"><?= $data['total']; ?></div>
                <div class="fcard-sub">judul buku</div>
            </div>

            <!-- bookshelf -->
            <div class="shelf-scene">
                <!-- shelf 1 -->
                <div class="shelf-row">
                    <div class="bk r w32 h136"></div>
                    <div class="bk n w20 h96"></div>
                    <div class="bk g w28 h120"></div>
                    <div class="bk m w16 h84"></div>
                    <div class="bk b w36 h150"></div>
                    <div class="bk o w24 h108"></div>
                    <div class="bk p w20 h96"></div>
                    <div class="bk t w28 h120"></div>
                    <div class="bk r w18 h84"></div>
                    <div class="bk g w32 h136"></div>
                </div>
                <!-- shelf 2 -->
                <div class="shelf-row">
                    <div class="bk b w24 h108"></div>
                    <div class="bk m w32 h120"></div>
                    <div class="bk o w20 h84"></div>
                    <div class="bk n w36 h150"></div>
                    <div class="bk r w24 h96"></div>
                    <div class="bk t w18 h70"></div>
                    <div class="bk p w30 h108"></div>
                    <div class="bk g w22 h96"></div>
                    <div class="bk b w28 h120"></div>
                    <div class="bk o w16 h84"></div>
                    <div class="bk m w24 h96"></div>
                </div>
                <!-- shelf 3 -->
                <div class="shelf-row">
                    <div class="bk g w20 h96"></div>
                    <div class="bk r w28 h108"></div>
                    <div class="bk n w36 h136"></div>
                    <div class="bk t w22 h84"></div>
                    <div class="bk o w28 h120"></div>
                    <div class="bk p w18 h70"></div>
                    <div class="bk b w32 h120"></div>
                    <div class="bk m w24 h96"></div>
                    <div class="bk g w20 h84"></div>
                </div>
            </div>

            <!-- floating cards bottom-right -->
            <div class="fcard fcard1">
                <div class="fcard-ico">🔖</div>
                <div class="fcard-label">Dipinjam</div>
                <div class="fcard-val">24</div>
                <div class="fcard-sub">hari ini</div>
            </div>
            <div class="fcard fcard3">
                <div class="fcard-ico">⭐</div>
                <div class="fcard-label">Rating Avg</div>
                <div class="fcard-val">4.8</div>
                <div class="fcard-sub">ulasan anggota</div>
            </div>
        </div>
    </section>

    <!-- ╔══════════════════════════╗
     ║       STATS BAR          ║
     ╚══════════════════════════╝ -->
    <div class="stats-bar">
        <div class="sbar-item">
            <div class="sbar-n"><?= $data['total']; ?></div>
            <div class="sbar-l">Judul Buku</div>
        </div>
        <div class="sbar-item">
            <div class="sbar-n">3</div>
            <div class="sbar-l">Level Pengguna</div>
        </div>
        <div class="sbar-item">
            <div class="sbar-n">Rp 1K</div>
            <div class="sbar-l">Denda / Hari</div>
        </div>
        <div class="sbar-item">
            <div class="sbar-n">7 Hari</div>
            <div class="sbar-l">Masa Pinjam</div>
        </div>
    </div>

    <!-- ╔══════════════════════════╗
     ║        FEATURES          ║
     ╚══════════════════════════╝ -->
    <section class="sec" id="fitur">
        <div class="sec-eyebrow">✦ Fitur Unggulan</div>
        <h2 class="sec-h2">Semua yang Dibutuhkan<br>Perpustakaan Modern</h2>
        <p class="sec-p">Kelola perpustakaan sekolah dengan fitur lengkap — dari peminjaman hingga laporan — semua dalam
            satu platform.</p>

        <div class="feat-grid">
            <div class="feat reveal" style="--fc:var(--rust);--fi:rgba(184,74,44,.08)">
                <div class="feat-ico">📚</div>
                <h3 class="feat-h">Katalog Buku Digital</h3>
                <p class="feat-p">Cari dan temukan buku yang tersedia secara real-time. Filter berdasarkan kategori,
                    pengarang,
                    atau status ketersediaan.</p>
            </div>
            <div class="feat reveal" style="--fc:var(--navy);--fi:rgba(44,79,124,.08)">
                <div class="feat-ico">📋</div>
                <h3 class="feat-h">Peminjaman Online</h3>
                <p class="feat-p">Anggota mengajukan peminjaman dari sistem. Petugas memproses dan mencatat secara
                    digital tanpa
                    kertas.</p>
            </div>
            <div class="feat reveal" style="--fc:var(--sage);--fi:rgba(73,102,64,.08)">
                <div class="feat-ico">↩️</div>
                <h3 class="feat-h">Pengembalian Otomatis</h3>
                <p class="feat-p">Sistem hitung tanggal jatuh tempo dan denda keterlambatan secara otomatis. Tidak perlu
                    hitung
                    manual lagi.</p>
            </div>
            <div class="feat reveal" style="--fc:var(--gold);--fi:rgba(196,138,32,.08)">
                <div class="feat-ico">📊</div>
                <h3 class="feat-h">Laporan Lengkap</h3>
                <p class="feat-p">Cetak laporan anggota, buku, peminjaman, dan denda siap print. Data akurat,
                    terstruktur, dan
                    komprehensif.</p>
            </div>
            <div class="feat reveal" style="--fc:#6b3a8a;--fi:rgba(107,58,138,.08)">
                <div class="feat-ico">👥</div>
                <h3 class="feat-h">Multi-Role Access</h3>
                <p class="feat-p">Tiga level akses: Admin, Petugas, dan Anggota — masing-masing dengan dashboard dan hak
                    akses
                    berbeda.</p>
            </div>
            <div class="feat reveal" style="--fc:#1e6055;--fi:rgba(30,96,85,.08)">
                <div class="feat-ico">⭐</div>
                <h3 class="feat-h">Ulasan & Rating Buku</h3>
                <p class="feat-p">Anggota memberi ulasan dan rating 1–5 bintang untuk buku yang pernah dipinjam,
                    membangun
                    komunitas pembaca.</p>
            </div>
        </div>
    </section>

    <!-- ╔══════════════════════════╗
     ║      HOW IT WORKS        ║
     ╚══════════════════════════╝ -->
    <section class="how" id="cara">
        <div class="sec-eyebrow">✦ Cara Kerja</div>
        <h2 class="sec-h2">Mulai dalam 4 Langkah</h2>
        <p class="sec-p">Proses yang sederhana dan intuitif untuk semua pengguna.</p>

        <div class="steps">
            <div class="step reveal">
                <div class="step-n">1</div>
                <div class="step-h">Daftar Akun</div>
                <p class="step-p">Buat akun anggota dengan NIS, nama, dan kelas Anda</p>
            </div>
            <div class="step reveal">
                <div class="step-n">2</div>
                <div class="step-h">Cari Buku</div>
                <p class="step-p">Jelajahi katalog dan temukan buku yang ingin dibaca</p>
            </div>
            <div class="step reveal">

                <div class="step-n">3</div>
                <div class="step-h">Pinjam</div>
                <p class="step-p">Ajukan peminjaman dan ambil buku di perpustakaan</p>
            </div>
            <div class="step reveal">
                <div class="step-n">4</div>
                <div class="step-h">Kembalikan</div>
                <p class="step-p">Kembalikan tepat waktu dan beri ulasan buku tersebut</p>
            </div>
        </div>
    </section>

    <!-- ╔══════════════════════════╗
     ║       CATEGORIES         ║
     ╚══════════════════════════╝ -->
    <section class="sec" id="koleksi">
        <div class="sec-eyebrow">✦ Koleksi Kami</div>
        <h2 class="sec-h2">Berbagai Kategori Buku</h2>
        <p class="sec-p">Dari fiksi hingga buku pelajaran, koleksi kami mencakup semua kebutuhan literasi siswa.</p>

        <div class="cats-grid">
            <div class="cat reveal">
                <div class="cat-ico">📖</div>
                <div class="cat-n">Fiksi</div>
                <div class="cat-c">Novel & Cerpen</div>
            </div>
            <div class="cat reveal">
                <div class="cat-ico">🧪</div>
                <div class="cat-n">Sains</div>
                <div class="cat-c">IPA & Biologi</div>
            </div>
            <div class="cat reveal">
                <div class="cat-ico">💻</div>
                <div class="cat-n">Teknologi</div>
                <div class="cat-c">IT & Pemrograman</div>
            </div>
            <div class="cat reveal">
                <div class="cat-ico">🏛️</div>
                <div class="cat-n">Sejarah</div>
                <div class="cat-c">Indonesia & Dunia</div>
            </div>
            <div class="cat reveal">
                <div class="cat-ico">📐</div>
                <div class="cat-n">Pelajaran</div>
                <div class="cat-c">Buku Teks Sekolah</div>
            </div>
            <div class="cat reveal">
                <div class="cat-ico">🌍</div>
                <div class="cat-n">Referensi</div>
                <div class="cat-c">Kamus & Ensiklopedia</div>
            </div>
        </div>
    </section>

    <!-- ╔══════════════════════════╗
     ║         ROLES            ║
     ╚══════════════════════════╝ -->
    <section class="roles-sec" id="pengguna">
        <div class="sec-eyebrow">✦ Jenis Pengguna</div>
        <h2 class="sec-h2">Dirancang untuk<br>Semua Pemangku Kepentingan</h2>
        <p class="sec-p">Setiap pengguna memiliki dashboard dan hak akses tersendiri yang sesuai peran mereka.</p>

        <div class="roles-grid">
            <div class="role role-admin reveal">
                <div class="role-ico">🛡️</div>
                <span class="role-badge">Admin</span>
                <h3 class="role-h">Administrator</h3>
                <p class="role-p">Kendali penuh atas seluruh sistem perpustakaan, termasuk manajemen pengguna dan
                    konfigurasi.
                </p>
                <ul class="role-list">
                    <li>Kelola Admin & Petugas</li>
                    <li>Kelola semua data anggota</li>
                    <li>Akses laporan lengkap</li>
                    <li>Kelola buku & kategori</li>
                    <li>Monitor transaksi & denda</li>
                </ul>
            </div>
            <div class="role role-petugas reveal">
                <div class="role-ico">👨‍💼</div>
                <span class="role-badge">Petugas</span>
                <h3 class="role-h">Petugas Perpustakaan</h3>
                <p class="role-p">Mengelola operasional harian perpustakaan: peminjaman, pengembalian, dan administrasi
                    koleksi.
                </p>
                <ul class="role-list">
                    <li>Proses peminjaman buku</li>
                    <li>Catat pengembalian</li>
                    <li>Kelola koleksi buku</li>
                    <li>Proses pembayaran denda</li>
                    <li>Cetak laporan operasional</li>
                </ul>
            </div>
            <div class="role role-anggota reveal">
                <div class="role-ico">🎓</div>
                <span class="role-badge">Anggota</span>
                <h3 class="role-h">Anggota / Siswa</h3>
                <p class="role-p">Akses katalog, pinjam buku, cek riwayat, dan berikan ulasan untuk membantu sesama
                    pembaca.</p>
                <ul class="role-list">
                    <li>Cari buku di katalog</li>
                    <li>Ajukan peminjaman</li>
                    <li>Lihat riwayat pinjaman</li>
                    <li>Cek status & denda</li>
                    <li>Tulis ulasan & rating</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ╔══════════════════════════╗
     ║          CTA             ║
     ╚══════════════════════════╝ -->
    <div class="cta-wrap">
        <div class="cta">
            <div>
                <h2 class="cta-h">Siap Mulai Membaca?</h2>
                <p class="cta-p">Bergabung sekarang dan nikmati akses ke ribuan koleksi buku.<br>Gratis untuk semua
                    siswa
                    terdaftar.</p>
            </div>
            <div class="cta-btns">
                <a href="register.php" class="btn-wh">Daftar Gratis</a>
                <a href="login.php" class="btn-whl">Masuk ke Akun</a>
            </div>
        </div>
    </div>

    <!-- ╔══════════════════════════╗
     ║         FOOTER           ║
     ╚══════════════════════════╝ -->
    <footer>
        <div class="foot-logo">
            <div class="foot-logo-ico">📖</div>
            <div class="foot-name">Perpustakaan Digital</div>
        </div>
        <p class="foot-copy">© <?= date('Y') ?> Sistem Peminjaman Buku Sekolah · All rights reserved</p>
        <div class="foot-links">
            <a href="login.php">Login</a>
            <a href="register.php">Daftar</a>
            <a href="setup.php">Setup</a>
        </div>
    </footer>

    <script>
    // navbar shadow on scroll
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => nav.classList.toggle('stuck', scrollY > 10));

    // smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const t = document.querySelector(a.getAttribute('href'));
            if (t) t.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });

    // scroll reveal
    const ro = new IntersectionObserver(entries => {
        entries.forEach((e, i) => {
            if (e.isIntersecting) {
                // stagger siblings
                const siblings = Array.from(e.target.parentElement.children);
                const idx = siblings.indexOf(e.target);
                setTimeout(() => e.target.classList.add('show'), idx * 80);
                ro.unobserve(e.target);
            }
        });
    }, {
        threshold: 0.12
    });
    document.querySelectorAll('.reveal').forEach(el => ro.observe(el));
    </script>
</body>

</html>