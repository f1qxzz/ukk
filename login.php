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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Masuk — Perpustakaan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    .login-left-extra {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 28px;
    }

    .login-stat {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: rgba(255, 255, 255, .08);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, .1);
    }

    .login-stat-val {
        font-family: "Playfair Display", serif;
        font-size: 1.4rem;
        font-weight: 700;
        color: #fff;
    }

    .login-stat-lbl {
        font-size: .73rem;
        color: rgba(255, 255, 255, .55);
        margin-top: 1px;
    }

    .login-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 20px 0;
    }

    .login-divider::before,
    .login-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--gray-200);
    }

    .login-divider span {
        font-size: .72rem;
        color: var(--gray-400);
        text-transform: uppercase;
        letter-spacing: .07em;
        white-space: nowrap;
    }

    .login-tabs-wrap {
        margin-bottom: 24px;
    }

    .btn-login {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        background: var(--accent);
        color: #fff;
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        font-family: inherit;
        transition: all .2s;
        box-shadow: 0 4px 16px rgba(59, 130, 246, .3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-login:hover {
        background: var(--accent-2);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, .4);
    }

    .input-icon-wrap {
        position: relative;
    }

    .input-icon-wrap svg {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        stroke: var(--gray-400);
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        pointer-events: none;
    }

    .input-icon-wrap .form-control {
        padding-left: 38px;
    }

    .eye-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--gray-400);
        background: none;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .eye-btn svg {
        width: 17px;
        height: 17px;
    }

    .reg-row {
        text-align: center;
        margin-top: 16px;
        font-size: .85rem;
        color: var(--gray-500);
    }

    .reg-link {
        color: var(--accent);
        font-weight: 600;
    }

    .reg-link:hover {
        color: var(--accent-2);
    }

    .foot {
        text-align: center;
        margin-top: 28px;
        font-size: .7rem;
        color: var(--gray-300);
    }
    </style>
</head>

<body>
    <div class="login-page">
        <!-- LEFT -->
        <aside class="login-left">
            <div class="login-hero-icon">📖</div>
            <h1 class="login-hero-title">Perpustakaan Digital</h1>
            <p class="login-hero-sub">Platform manajemen perpustakaan modern untuk mengelola koleksi, anggota, dan
                transaksi peminjaman buku.</p>

            <div class="login-left-extra">
                <div class="login-stat">
                    <svg style="width:24px;height:24px;stroke:var(--accent);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"
                        viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                    </svg>
                    <div>
                        <div class="login-stat-val"><?= $data['total'] ?></div>
                        <div class="login-stat-lbl">Koleksi Buku Tersedia</div>
                    </div>
                </div>
                <div class="login-stat">
                    <svg style="width:24px;height:24px;stroke:var(--success);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"
                        viewBox="0 0 24 24">
                        <polyline points="12 8 12 12 14 14" />
                        <path d="M3.05 11a9 9 0 1 0 .5-4" />
                        <polyline points="3 3 3 7 7 7" />
                    </svg>
                    <div>
                        <div class="login-stat-val">7 Hari</div>
                        <div class="login-stat-lbl">Masa Peminjaman</div>
                    </div>
                </div>
                <div class="login-stat">
                    <svg style="width:24px;height:24px;stroke:#f59e0b;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"
                        viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <div>
                        <div class="login-stat-val">3 Level</div>
                        <div class="login-stat-lbl">Admin · Petugas · Anggota</div>
                    </div>
                </div>
            </div>

            <a href="index.php"
                style="margin-top:32px;font-size:.8rem;color:rgba(255,255,255,.5);display:inline-flex;align-items:center;gap:6px;">
                <svg viewBox="0 0 24 24"
                    style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
                Kembali ke Beranda
            </a>
        </aside>

        <!-- RIGHT -->
        <main class="login-right">
            <div class="login-box">
                <div class="login-logo">
                    <div class="login-logo-icon">📖</div>
                    <div>
                        <div class="login-title">Masuk ke Akun</div>
                        <div class="login-subtitle">Gunakan username dan password terdaftar</div>
                    </div>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($reg_success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($reg_success) ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="form-group">
                        <label class="form-label">Masuk Sebagai</label>
                        <select name="user_type" class="form-control">
                            <option value="anggota">👨‍🎓 Anggota / Siswa</option>
                            <option value="petugas">👮 Petugas Perpustakaan</option>
                            <option value="admin">🛡️ Administrator</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-icon-wrap">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username"
                                required autocomplete="username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-icon-wrap">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <input type="password" name="password" id="pw" class="form-control"
                                placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="eye-btn" onclick="togglePw()">
                                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn-login">
                        <svg viewBox="0 0 24 24"
                            style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" y1="12" x2="3" y2="12" />
                        </svg>
                        Masuk ke Sistem
                    </button>
                </form>

                <div class="login-divider"><span>belum punya akun?</span></div>
                <div class="reg-row">
                    <span>Daftar sebagai anggota </span>
                    <a href="register.php" class="reg-link">Daftar Gratis →</a>
                </div>

                <p class="foot">© <?= date('Y') ?> Perpustakaan Digital · Sistem Peminjaman Buku</p>
            </div>
        </main>
    </div>

    <script>
    function togglePw() {
        const pw = document.getElementById('pw');
        pw.type = pw.type === 'password' ? 'text' : 'password';
    }
    </script>
    <script src="assets/js/script.js"></script>
</body>

</html>