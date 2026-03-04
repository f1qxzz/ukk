<?php
require_once 'config/database.php';
require_once 'includes/session.php';
initSession();

if (isPenggunaLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'petugas/dashboard.php'));
    exit;
}
if (isAnggotaLoggedIn()) {
    header('Location: anggota/dashboard.php');
    exit;
}

$error   = '';
$success = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $old = [
        'nis'      => trim($_POST['nis'] ?? ''),
        'nama'     => trim($_POST['nama_anggota'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'kelas'    => trim($_POST['kelas'] ?? ''),
    ];
    $password = trim($_POST['password'] ?? '');
    $conn = getConnection();

    if (empty($old['nis']) || empty($old['nama']) || empty($old['username']) || empty($old['kelas']) || empty($password)) {
        $error = 'Semua field bertanda * wajib diisi!';
    } else {
        $chk = $conn->query("SELECT id_anggota FROM anggota WHERE username='{$old['username']}' OR nis='{$old['nis']}'");
        if ($chk->num_rows > 0) {
            $error = 'NIS atau Username sudah terdaftar!';
        } else {
            $stmt = $conn->prepare("INSERT INTO anggota (nis,nama_anggota,username,password,email,kelas) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $old['nis'], $old['nama'], $old['username'], $password, $old['email'], $old['kelas']);
            if ($stmt->execute()) {
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = 'Registrasi gagal. Silakan coba lagi.';
            }
        }
    }
    closeConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Anggota — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <style>
    :root {
        --bg: #f4ede0;
        --bg2: #ede4d2;
        --paper: #faf6ef;
        --white: #ffffff;
        --ink: #1c1509;
        --ink2: #3b2f1e;
        --muted: #7c6b52;
        --border: rgba(80, 55, 25, .11);
        --rust: #b84a2c;
        --rust2: #d05a38;
        --gold: #c48a20;
        --sage: #496640;
        --navy: #2c4f7c;
        --sh1: 0 1px 12px rgba(28, 21, 9, .07);
        --sh2: 0 6px 32px rgba(28, 21, 9, .13);
    }

    *,
    *::before,
    *::after {
        margin: 0;
        padding: 0;
        box-sizing: border-box
    }

    html {
        height: 100%
    }

    body {
        min-height: 100vh;
        font-family: 'Outfit', sans-serif;
        background: var(--bg);
        color: var(--ink);
        overflow-x: hidden;
        display: grid;
        grid-template-columns: 460px 1fr;
    }

    /* ── LEFT PANEL ── */
    .left {
        background: linear-gradient(175deg, var(--ink) 0%, #2c1f0a 100%);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 48px 44px;
        position: relative;
        overflow: hidden;
        min-height: 100vh;
    }

    /* ambient glows */
    .left::before {
        content: '';
        position: absolute;
        top: -80px;
        right: -80px;
        width: 380px;
        height: 380px;
        border-radius: 50%;
        background: radial-gradient(rgba(184, 74, 44, .15), transparent 70%);
        pointer-events: none;
    }

    .left::after {
        content: '';
        position: absolute;
        bottom: -60px;
        left: -60px;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: radial-gradient(rgba(196, 138, 32, .1), transparent 70%);
        pointer-events: none;
    }

    /* logo */
    .left-logo {
        display: flex;
        align-items: center;
        gap: 11px;
        position: relative;
        z-index: 2
    }

    .left-logo-ico {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: var(--rust);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0
    }

    .left-logo-n {
        font-family: 'Fraunces', serif;
        font-size: .95rem;
        font-weight: 700;
        color: #fff;
        line-height: 1.15
    }

    .left-logo-s {
        font-size: .62rem;
        color: rgba(255, 255, 255, .35);
        font-weight: 300
    }

    /* centre content */
    .left-body {
        position: relative;
        z-index: 2
    }

    .left-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 40px;
        width: fit-content;
        background: rgba(255, 255, 255, .07);
        border: 1px solid rgba(255, 255, 255, .12);
        font-size: .68rem;
        letter-spacing: .13em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .5);
        margin-bottom: 22px;
    }

    .ldot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--sage)
    }

    .left-h1 {
        font-family: 'Fraunces', serif;
        font-size: clamp(2rem, 3.2vw, 2.9rem);
        font-weight: 700;
        line-height: 1.1;
        color: #fff;
        margin-bottom: 14px;
    }

    .left-h1 em {
        font-style: italic;
        color: var(--rust2)
    }

    .left-p {
        font-size: .9rem;
        line-height: 1.8;
        color: rgba(255, 255, 255, .45);
        font-weight: 300;
        margin-bottom: 38px;
    }

    /* benefit list */
    .benefit-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 12px
    }

    .benefit-list li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: .85rem;
        color: rgba(255, 255, 255, .55);
        line-height: 1.5;
        font-weight: 300;
    }

    .bi {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        flex-shrink: 0;
        background: rgba(255, 255, 255, .06);
        border: 1px solid rgba(255, 255, 255, .1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
    }

    /* bottom */
    .left-foot {
        position: relative;
        z-index: 2;
        padding-top: 28px;
        border-top: 1px solid rgba(255, 255, 255, .07)
    }

    .left-foot p {
        font-size: .78rem;
        color: rgba(255, 255, 255, .28)
    }

    .left-foot a {
        color: rgba(255, 255, 255, .5);
        text-decoration: underline;
        text-underline-offset: 3px;
        transition: color .2s
    }

    .left-foot a:hover {
        color: rgba(255, 255, 255, .8)
    }

    /* ── RIGHT FORM ── */
    .right {
        background: var(--paper);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 48px 56px;
        position: relative;
        overflow: hidden;
        min-height: 100vh;
    }

    .right::before {
        content: '';
        position: absolute;
        top: -40px;
        right: -40px;
        width: 280px;
        height: 280px;
        border-radius: 50%;
        background: radial-gradient(rgba(196, 138, 32, .07), transparent 70%);
        pointer-events: none;
    }

    /* form header */
    .form-hd {
        margin-bottom: 28px;
        opacity: 0;
        animation: up .6s .08s ease forwards;
    }

    .form-h {
        font-family: 'Fraunces', serif;
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 5px
    }

    .form-sub {
        font-size: .82rem;
        color: var(--muted);
        font-weight: 300
    }

    /* alert */
    .alert {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: .84rem;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        animation: alertIn .3s ease;
    }

    @keyframes alertIn {
        from {
            opacity: 0;
            transform: translateY(-6px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    .alert-err {
        background: rgba(184, 74, 44, .09);
        border: 1px solid rgba(184, 74, 44, .2);
        color: #9a2a10
    }

    /* fields */
    .form-body {
        opacity: 0;
        animation: up .6s .16s ease forwards
    }

    .field {
        margin-bottom: 16px
    }

    .field label {
        display: block;
        font-size: .7rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 7px;
        font-weight: 500;
    }

    .fi {
        position: relative
    }

    .fi-ico {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        font-size: .86rem;
        opacity: .32;
        pointer-events: none;
        z-index: 1;
    }

    .fi input {
        width: 100%;
        padding: 12px 14px 12px 40px;
        background: rgba(255, 255, 255, .8);
        border: 1.5px solid var(--border);
        border-radius: 10px;
        font-family: 'Outfit', sans-serif;
        font-size: .92rem;
        color: var(--ink);
        transition: all .22s;
    }

    .fi input::placeholder {
        color: var(--muted);
        opacity: .35
    }

    .fi input:focus {
        outline: none;
        border-color: rgba(184, 74, 44, .45);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(184, 74, 44, .08);
    }

    /* eye */
    .eye-btn {
        position: absolute;
        right: 13px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: var(--muted);
        opacity: .32;
        font-size: .86rem;
        transition: opacity .2s;
    }

    .eye-btn:hover {
        opacity: .7
    }

    /* 2-col row */
    .row2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px
    }

    /* strength bar */
    .pw-strength {
        margin-top: 7px;
        display: flex;
        gap: 4px;
        height: 3px
    }

    .pws {
        flex: 1;
        border-radius: 3px;
        background: var(--border);
        transition: background .3s
    }

    .pw-s.weak {
        background: #e05050
    }

    .pw-s.fair {
        background: var(--gold)
    }

    .pw-s.strong {
        background: var(--sage)
    }

    /* submit */
    .btn-submit {
        width: 100%;
        padding: 14px;
        border-radius: 10px;
        margin-top: 8px;
        border: none;
        cursor: pointer;
        font-family: 'Outfit', sans-serif;
        font-size: .97rem;
        font-weight: 600;
        letter-spacing: .025em;
        background: linear-gradient(135deg, var(--rust), var(--rust2));
        color: #fff;
        box-shadow: 0 4px 20px rgba(184, 74, 44, .3);
        position: relative;
        overflow: hidden;
        transition: all .26s;
    }

    .btn-submit::after {
        content: '';
        position: absolute;
        top: 0;
        left: -110%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .2), transparent);
        transition: left .4s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(184, 74, 44, .4)
    }

    .btn-submit:hover::after {
        left: 110%
    }

    .btn-submit:active {
        transform: none
    }

    /* bottom link */
    .dvd {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 22px 0
    }

    .dvd::before,
    .dvd::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border)
    }

    .dvd span {
        font-size: .7rem;
        color: var(--muted);
        opacity: .45;
        white-space: nowrap;
        letter-spacing: .07em;
        text-transform: uppercase
    }

    .login-row {
        text-align: center;
        font-size: .84rem
    }

    .login-row span {
        color: var(--muted);
        font-weight: 300
    }

    .login-link {
        font-weight: 600;
        color: var(--rust);
        text-decoration: underline;
        text-underline-offset: 3px;
        transition: color .2s
    }

    .login-link:hover {
        color: var(--rust2)
    }

    .foot-note {
        margin-top: 22px;
        text-align: center;
        font-size: .7rem;
        color: var(--muted);
        opacity: .35;
        font-weight: 300;
    }

    @keyframes up {
        from {
            opacity: 0;
            transform: translateY(20px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    ::-webkit-scrollbar {
        width: 4px
    }

    ::-webkit-scrollbar-track {
        background: var(--bg)
    }

    ::-webkit-scrollbar-thumb {
        background: #c4b090;
        border-radius: 4px
    }

    @media(max-width:900px) {
        body {
            grid-template-columns: 1fr
        }

        .left {
            display: none
        }

        .right {
            min-height: 100vh;
            padding: 44px 26px
        }
    }

    @media(max-width:480px) {
        .row2 {
            grid-template-columns: 1fr
        }
    }
    </style>
</head>

<body>

    <!-- ── LEFT ────────────────────────── -->
    <aside class="left">
        <div class="left-logo">
            <div class="left-logo-ico">📖</div>
            <div>
                <div class="left-logo-n">Perpustakaan Digital</div>
                <div class="left-logo-s">Sistem Peminjaman Buku</div>
            </div>
        </div>

        <div class="left-body">
            <div class="left-tag">
                <span class="ldot"></span>
                Daftar Anggota Baru
            </div>

            <h1 class="left-h1">
                Mulai Perjalanan<br>
                <em>Membaca Anda</em>
            </h1>
            <p class="left-p">
                Daftar sebagai anggota perpustakaan digital dan nikmati akses ke ratusan koleksi buku sekolah secara
                mudah dan gratis.
            </p>

            <ul class="benefit-list">
                <li>
                    <span class="bi">📚</span>
                    <span>Akses koleksi buku digital kapan saja</span>
                </li>
                <li>
                    <span class="bi">📋</span>
                    <span>Ajukan peminjaman langsung dari sistem</span>
                </li>
                <li>
                    <span class="bi">📊</span>
                    <span>Pantau riwayat pinjaman dan status denda</span>
                </li>
                <li>
                    <span class="bi">⭐</span>
                    <span>Tulis ulasan dan rating untuk buku favorit</span>
                </li>
                <li>
                    <span class="bi">🔔</span>
                    <span>Notifikasi jatuh tempo pengembalian buku</span>
                </li>
            </ul>
        </div>

        <div class="left-foot">
            <p>Sudah punya akun? <a href="login.php">Masuk di sini</a> &nbsp;·&nbsp; <a href="index.php">← Kembali ke
                    beranda</a></p>
        </div>
    </aside>

    <!-- ── RIGHT ────────────────────────── -->
    <main class="right">

        <!-- heading -->
        <div class="form-hd">
            <h2 class="form-h">Buat Akun Baru</h2>
            <p class="form-sub">Isi data diri Anda untuk mendaftar sebagai anggota perpustakaan</p>
        </div>

        <!-- alert -->
        <?php if ($error): ?>
        <div class="alert alert-err">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- form -->
        <form method="POST" novalidate class="form-body">

            <!-- row 1 -->
            <div class="row2">
                <div class="field">
                    <label>NIS *</label>
                    <div class="fi">
                        <span class="fi-ico">🪪</span>
                        <input type="text" name="nis" placeholder="Nomor Induk Siswa" required
                            value="<?= htmlspecialchars($old['nis'] ?? '') ?>">
                    </div>
                </div>
                <div class="field">
                    <label>Kelas *</label>
                    <div class="fi">
                        <span class="fi-ico">🏫</span>
                        <input type="text" name="kelas" placeholder="cth: XII RPL" required
                            value="<?= htmlspecialchars($old['kelas'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- nama -->
            <div class="field">
                <label>Nama Lengkap *</label>
                <div class="fi">
                    <span class="fi-ico">👤</span>
                    <input type="text" name="nama_anggota" placeholder="Nama sesuai data sekolah" required
                        value="<?= htmlspecialchars($old['nama'] ?? '') ?>">
                </div>
            </div>

            <!-- row 2 -->
            <div class="row2">
                <div class="field">
                    <label>Username *</label>
                    <div class="fi">
                        <span class="fi-ico">🔖</span>
                        <input type="text" name="username" placeholder="Buat username unik" required
                            value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                    </div>
                </div>
                <div class="field">
                    <label>Email</label>
                    <div class="fi">
                        <span class="fi-ico">✉️</span>
                        <input type="email" name="email" placeholder="email@sekolah.com"
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- password -->
            <div class="field">
                <label>Password * <span
                        style="font-weight:300;text-transform:none;letter-spacing:0;font-size:.68rem;color:var(--muted)">(min.
                        6 karakter)</span></label>
                <div class="fi" style="position:relative">
                    <span class="fi-ico">🔑</span>
                    <input type="password" name="password" id="pw" placeholder="Buat password yang kuat" required
                        minlength="6" oninput="checkStrength(this.value)">
                    <button type="button" class="eye-btn" onclick="togglePw()">👁</button>
                </div>
                <div class="pw-strength">
                    <div class="pw-s" id="ps1"></div>
                    <div class="pw-s" id="ps2"></div>
                    <div class="pw-s" id="ps3"></div>
                    <div class="pw-s" id="ps4"></div>
                </div>
            </div>

            <button type="submit" name="register" class="btn-submit">Daftar Sekarang →</button>
        </form>

        <div class="dvd"><span>sudah punya akun?</span></div>
        <div class="login-row">
            <span>Masuk dengan akun yang ada&nbsp;</span>
            <a href="login.php" class="login-link">Masuk Sekarang</a>
        </div>
        <p class="foot-note">© <?= date('Y') ?> Perpustakaan Digital · Daftar gratis untuk semua siswa terdaftar</p>
    </main>

    <script>
    function togglePw() {
        const pw = document.getElementById('pw');
        const btn = pw.nextElementSibling;
        const t = pw.type === 'text';
        pw.type = t ? 'password' : 'text';
        btn.style.opacity = t ? '.32' : '.7';
    }

    function checkStrength(v) {
        const bars = [document.getElementById('ps1'), document.getElementById('ps2'), document.getElementById('ps3'),
            document.getElementById('ps4')
        ];
        bars.forEach(b => {
            b.className = 'pw-s';
        });
        if (!v) return;
        let score = 0;
        if (v.length >= 6) score++;
        if (v.length >= 10) score++;
        if (/[A-Z]/.test(v) && /[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        const cls = score <= 1 ? 'weak' : score === 2 ? 'fair' : 'strong';
        for (let i = 0; i < score; i++) bars[i].classList.add(cls);
    }
    document.querySelectorAll('.fi input').forEach(el => {
        el.addEventListener('focus', () => el.closest('.fi').style.cssText +=
            'transform:translateY(-1px);transition:transform .18s');
        el.addEventListener('blur', () => el.closest('.fi').style.transform = '');
    });
    </script>
<script src="assets/js/script.js"></script>
</body>

</html>