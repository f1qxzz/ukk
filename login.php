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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username  = trim($_POST['username']);
    $password  = trim($_POST['password']);
    $user_type = $_POST['user_type'];
    $conn = getConnection();

    if ($user_type === 'admin' || $user_type === 'petugas') {
        $stmt = $conn->prepare("SELECT * FROM pengguna WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && $password === $row['password']) {
            if ($user_type !== $row['level']) {
                $error = 'Akses tidak sesuai dengan level akun!';
            } else {
                $_SESSION['pengguna_logged_in'] = true;
                $_SESSION['pengguna_id']        = $row['id_pengguna'];
                $_SESSION['pengguna_nama']      = $row['nama_pengguna'];
                $_SESSION['pengguna_level']     = $row['level'];
                $_SESSION['pengguna_username']  = $row['username'];
                header('Location: ' . ($row['level'] === 'admin' ? 'admin/dashboard.php' : 'petugas/dashboard.php'));
                exit;
            }
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM anggota WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && $password === $row['password']) {
            if ($row['status'] !== 'aktif') {
                $error = 'Akun Anda tidak aktif. Hubungi petugas.';
            } else {
                $_SESSION['anggota_logged_in'] = true;
                $_SESSION['anggota_id']        = $row['id_anggota'];
                $_SESSION['anggota_nama']      = $row['nama_anggota'];
                $_SESSION['anggota_nis']        = $row['nis'];
                $_SESSION['anggota_kelas']     = $row['kelas'];
                header('Location: anggota/dashboard.php');
                exit;
            }
        } else {
            $error = 'Username atau password salah!';
        }
    }
    closeConnection($conn);
}

$reg_success = isset($_GET['registered']) ? 'Registrasi berhasil! Silakan masuk.' : '';
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
    <title>Masuk — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;0,9..144,700;1,9..144,400&family=Outfit:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <style>
    :root {
        --bg: #f4ede0;
        --bg2: #ede4d2;
        --paper: #faf6ef;
        --ink: #1c1509;
        --ink2: #3b2f1e;
        --muted: #7c6b52;
        --border: rgba(80, 55, 25, .11);
        --rust: #b84a2c;
        --rust2: #d05a38;
        --gold: #c48a20;
        --sage: #496640;
        --sh1: 0 1px 12px rgba(28, 21, 9, .07);
        --sh2: 0 6px 32px rgba(28, 21, 9, .13);
        --sh3: 0 20px 64px rgba(28, 21, 9, .2);
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
        grid-template-columns: 1fr 520px;
    }

    /* ── LEFT PANEL ── */
    .left {
        position: relative;
        overflow: hidden;
        background: linear-gradient(160deg, var(--bg2) 0%, #e2d5bc 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px 8%;
    }

    .left-deco {
        position: absolute;
        top: -100px;
        right: -100px;
        width: 450px;
        height: 450px;
        border-radius: 50%;
        background: radial-gradient(rgba(196, 138, 32, .1), transparent 70%);
        pointer-events: none;
    }

    .left-deco2 {
        position: absolute;
        bottom: -80px;
        left: -60px;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: radial-gradient(rgba(184, 74, 44, .07), transparent 70%);
        pointer-events: none;
    }

    /* bookshelf rows decorative */
    .mini-shelf {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 48px;
        width: fit-content
    }

    .mini-row {
        display: flex;
        align-items: flex-end;
        gap: 2px;
        background: linear-gradient(to bottom, #b89a72, #9a8060);
        border-radius: 2px 2px 0 0;
        padding: 6px 16px 0;
        position: relative;
    }

    .mini-row::after {
        content: '';
        position: absolute;
        bottom: -7px;
        left: -2px;
        right: -2px;
        height: 9px;
        background: #806840;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, .18);
    }

    .mb {
        border-radius: 2px 2px 0 0;
        flex-shrink: 0;
        transition: transform .3s
    }

    .mb:hover {
        transform: translateY(-6px)
    }

    .lr {
        background: linear-gradient(to right, #a63820, #c44830)
    }

    .lb {
        background: linear-gradient(to right, #284878, #3460a0)
    }

    .lg {
        background: linear-gradient(to right, #3a5830, #4e7040)
    }

    .lo {
        background: linear-gradient(to right, #a87020, #c88e30)
    }

    .lp {
        background: linear-gradient(to right, #5a3475, #7448a0)
    }

    .lt {
        background: linear-gradient(to right, #1e5852, #28706a)
    }

    .ln {
        background: linear-gradient(to right, #182840, #243858)
    }

    .left-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 40px;
        width: fit-content;
        background: rgba(255, 255, 255, .7);
        border: 1px solid var(--border);
        font-size: .68rem;
        letter-spacing: .13em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 22px;
        box-shadow: var(--sh1);
    }

    .ldot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--sage)
    }

    .left-h1 {
        font-family: 'Fraunces', serif;
        font-size: clamp(2.2rem, 3.8vw, 3.2rem);
        font-weight: 700;
        line-height: 1.1;
        color: var(--ink);
        margin-bottom: 16px;
    }

    .left-h1 em {
        font-style: italic;
        color: var(--rust)
    }

    .left-p {
        font-size: .95rem;
        line-height: 1.78;
        color: var(--muted);
        font-weight: 300;
        max-width: 400px;
        margin-bottom: 36px;
    }

    .left-nums {
        display: flex;
        gap: 32px;
        padding-top: 28px;
        border-top: 1px solid var(--border)
    }

    .lnum-n {
        font-family: 'Fraunces', serif;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--rust);
        line-height: 1
    }

    .lnum-l {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--muted);
        margin-top: 3px
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: .8rem;
        color: var(--muted);
        margin-top: 28px;
        transition: color .2s;
    }

    .back-link:hover {
        color: var(--rust)
    }

    /* ── RIGHT FORM PANEL ── */
    .right {
        background: var(--paper);
        border-left: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 52px 50px;
        position: relative;
        overflow: hidden;
        min-height: 100vh;
    }

    .right::before {
        content: '';
        position: absolute;
        top: -60px;
        left: 50%;
        transform: translateX(-50%);
        width: 260px;
        height: 140px;
        background: radial-gradient(rgba(184, 74, 44, .1), transparent 70%);
        pointer-events: none;
    }

    /* logo */
    .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 38px;
        opacity: 0;
        animation: up .6s .05s ease forwards
    }

    .logo-ico {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        flex-shrink: 0;
        background: linear-gradient(135deg, var(--rust), var(--rust2));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 3px 14px rgba(184, 74, 44, .28);
    }

    .logo-nm {
        font-family: 'Fraunces', serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.2
    }

    .logo-sb {
        font-size: .66rem;
        color: var(--muted);
        font-weight: 400
    }

    /* alerts */
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

    .alert-ok {
        background: rgba(73, 102, 64, .08);
        border: 1px solid rgba(73, 102, 64, .18);
        color: #2a5020
    }

    /* form heading */
    .form-hd {
        opacity: 0;
        animation: up .6s .12s ease forwards;
        margin-bottom: 28px;
    }

    .form-h {
        font-family: 'Fraunces', serif;
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 5px
    }

    .form-sub {
        font-size: .82rem;
        color: var(--muted);
        font-weight: 300
    }

    /* fields */
    .fields {
        opacity: 0;
        animation: up .6s .2s ease forwards
    }

    .field {
        margin-bottom: 17px
    }

    .field label {
        display: block;
        font-size: .7rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 8px;
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
        font-size: .88rem;
        opacity: .35;
        pointer-events: none;
        z-index: 1;
    }

    .fi input,
    .fi select {
        width: 100%;
        padding: 12px 14px 12px 40px;
        background: rgba(255, 255, 255, .8);
        border: 1.5px solid var(--border);
        border-radius: 10px;
        font-family: 'Outfit', sans-serif;
        font-size: .92rem;
        color: var(--ink);
        transition: all .22s;
        -webkit-appearance: none;
        appearance: none;
    }

    .fi input::placeholder {
        color: var(--muted);
        opacity: .4
    }

    .fi input:focus,
    .fi select:focus {
        outline: none;
        border-color: rgba(184, 74, 44, .5);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(184, 74, 44, .08);
    }

    .fi select option {
        background: #fff;
        color: var(--ink)
    }

    /* select caret */
    .fi-sel::after {
        content: '▾';
        position: absolute;
        right: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        opacity: .4;
        pointer-events: none;
        font-size: .78rem;
    }

    /* eye toggle */
    .eye-btn {
        position: absolute;
        right: 13px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: var(--muted);
        opacity: .35;
        font-size: .88rem;
        transition: opacity .2s;
    }

    .eye-btn:hover {
        opacity: .7
    }

    /* submit */
    .btn-submit {
        width: 100%;
        padding: 14px;
        border-radius: 10px;
        margin-top: 6px;
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

    /* divider */
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
        opacity: .5;
        white-space: nowrap;
        letter-spacing: .07em;
        text-transform: uppercase
    }

    /* register link */
    .reg-row {
        text-align: center
    }

    .reg-row span {
        font-size: .84rem;
        color: var(--muted);
        font-weight: 300
    }

    .reg-link {
        font-size: .84rem;
        font-weight: 600;
        color: var(--rust);
        text-decoration: underline;
        text-underline-offset: 3px;
        transition: color .2s;
    }

    .reg-link:hover {
        color: var(--rust2)
    }

    .foot-note {
        margin-top: 28px;
        text-align: center;
        font-size: .7rem;
        color: var(--muted);
        opacity: .38;
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

    @media(max-width:860px) {
        body {
            grid-template-columns: 1fr
        }

        .left {
            display: none
        }

        .right {
            min-height: 100vh;
            padding: 44px 28px;
            border-left: none
        }

        .right::before {
            display: none
        }
    }
    </style>
</head>

<body>

    <!-- ── LEFT ─────────────────────────── -->
    <aside class="left">
        <div class="left-deco"></div>
        <div class="left-deco2"></div>

        <!-- mini bookshelf deco -->
        <div class="mini-shelf">
            <div class="mini-row">
                <div class="mb lr" style="width:22px;height:90px"></div>
                <div class="mb ln" style="width:16px;height:68px"></div>
                <div class="mb lg" style="width:26px;height:100px"></div>
                <div class="mb lo" style="width:18px;height:78px"></div>
                <div class="mb lb" style="width:28px;height:110px"></div>
                <div class="mb lp" style="width:20px;height:84px"></div>
                <div class="mb lt" style="width:22px;height:90px"></div>
            </div>
            <div class="mini-row">
                <div class="mb lb" style="width:18px;height:78px"></div>
                <div class="mb lr" style="width:24px;height:94px"></div>
                <div class="mb lo" style="width:20px;height:82px"></div>
                <div class="mb lg" style="width:28px;height:106px"></div>
                <div class="mb ln" style="width:22px;height:90px"></div>
                <div class="mb lp" style="width:16px;height:68px"></div>
                <div class="mb lt" style="width:26px;height:100px"></div>
                <div class="mb lr" style="width:18px;height:72px"></div>
            </div>
        </div>

        <div class="left-tag">
            <span class="ldot"></span>
            Perpustakaan Digital
        </div>

        <h1 class="left-h1">
            Selamat Datang<br>
            <em>Kembali</em>
        </h1>
        <p class="left-p">
            Masuk ke akun Anda dan lanjutkan menjelajahi ribuan koleksi buku perpustakaan digital sekolah.
        </p>

        <div class="left-nums">
            <div>
                <div class="lnum-n"><?= $data['total']; ?></div>
                <div class="lnum-l">Koleksi Buku</div>
            </div>
            <div>
                <div class="lnum-n">7 Hari</div>
                <div class="lnum-l">Masa Pinjam</div>
            </div>
            <div>
                <div class="lnum-n">3</div>
                <div class="lnum-l">Level Akses</div>
            </div>
        </div>

        <a href="index.php" class="back-link">← Kembali ke Beranda</a>
    </aside>

    <!-- ── RIGHT ─────────────────────────── -->
    <main class="right">

        <!-- logo -->
        <div class="logo">
            <div class="logo-ico">📖</div>
            <div>
                <div class="logo-nm">Perpustakaan Digital</div>
                <div class="logo-sb">Sistem Peminjaman Buku</div>
            </div>
        </div>

        <!-- alerts -->
        <?php if ($error): ?>
        <div class="alert alert-err">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($reg_success): ?>
        <div class="alert alert-ok">✓ <?= htmlspecialchars($reg_success) ?></div>
        <?php endif; ?>

        <!-- heading -->
        <div class="form-hd">
            <h2 class="form-h">Masuk ke Akun</h2>
            <p class="form-sub">Gunakan username dan password yang terdaftar</p>
        </div>

        <!-- form -->
        <form method="POST" novalidate class="fields">
            <!-- role -->
            <div class="field">
                <label>Masuk Sebagai</label>
                <div class="fi fi-sel">
                    <span class="fi-ico">👥</span>
                    <select name="user_type" required>
                        <option value="anggota">Anggota / Siswa</option>
                        <option value="petugas">Petugas Perpustakaan</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <!-- username -->
            <div class="field">
                <label>Username</label>
                <div class="fi">
                    <span class="fi-ico">👤</span>
                    <input type="text" name="username" placeholder="Masukkan username Anda" required
                        autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <!-- password -->
            <div class="field">
                <label>Password</label>
                <div class="fi" style="position:relative">
                    <span class="fi-ico">🔑</span>
                    <input type="password" name="password" id="pw" placeholder="Masukkan password" required
                        autocomplete="current-password">
                    <button type="button" class="eye-btn" onclick="togglePw()">👁</button>
                </div>
            </div>

            <button type="submit" name="login" class="btn-submit">Masuk ke Perpustakaan →</button>
        </form>

        <div class="dvd"><span>belum punya akun?</span></div>

        <div class="reg-row">
            <span>Daftarkan diri Anda sebagai anggota&nbsp;</span>
            <a href="register.php" class="reg-link">Daftar Gratis</a>
        </div>

        <p class="foot-note">© <?= date('Y') ?> Perpustakaan Digital · Sistem Peminjaman Buku Sekolah</p>
    </main>

    <script>
    function togglePw() {
        const pw = document.getElementById('pw');
        const btn = pw.nextElementSibling;
        const isText = pw.type === 'text';
        pw.type = isText ? 'password' : 'text';
        btn.style.opacity = isText ? '.35' : '.7';
    }
    // lift effect on focus
    document.querySelectorAll('.fi input, .fi select').forEach(el => {
        el.addEventListener('focus', () => el.closest('.fi').style.cssText +=
            'transform:translateY(-1px);transition:transform .18s');
        el.addEventListener('blur', () => el.closest('.fi').style.transform = '');
    });
    </script>
</body>

</html>