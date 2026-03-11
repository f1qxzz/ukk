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
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-50: #eef2ff;
        --primary-100: #e0e7ff;
        --primary-200: #c7d2fe;
        --primary-300: #a5b4fc;
        --primary-400: #818cf8;
        --primary-500: #6366f1;
        --primary-600: #4f46e5;
        --primary-700: #4338ca;
        --primary-800: #3730a3;
        --primary-900: #312e81;

        --neutral-50: #f9fafb;
        --neutral-100: #f3f4f6;
        --neutral-200: #e5e7eb;
        --neutral-300: #d1d5db;
        --neutral-400: #9ca3af;
        --neutral-500: #6b7280;
        --neutral-600: #4b5563;
        --neutral-700: #374151;
        --neutral-800: #1f2937;
        --neutral-900: #111827;

        --success-500: #10b981;
        --warning-500: #f59e0b;
        --danger-500: #ef4444;

        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);

        --radius-lg: 1rem;
        --radius-xl: 1.5rem;
        --radius-2xl: 2rem;
        --radius-full: 9999px;

        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    /* Main Container */
    .register-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        max-width: 1200px;
        width: 100%;
        background: white;
        border-radius: var(--radius-2xl);
        overflow: hidden;
        box-shadow: var(--shadow-2xl);
    }

    /* Left Panel */
    .register-left {
        background: linear-gradient(135deg, #4338ca, #312e81);
        padding: 48px;
        color: white;
        display: flex;
        flex-direction: column;
    }

    .register-left-content {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .register-icon {
        font-size: 3rem;
        margin-bottom: 24px;
        background: rgba(255, 255, 255, 0.1);
        width: fit-content;
        padding: 16px;
        border-radius: var(--radius-xl);
    }

    .register-title-large {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 2.2rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 16px;
    }

    .register-title-large span {
        color: #fbbf24;
    }

    .register-description {
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
        margin-bottom: 32px;
    }

    /* Benefits List */
    .benefits-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 32px;
    }

    .benefit-item {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.9);
        padding: 10px 14px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: var(--radius-lg);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .benefit-icon {
        width: 32px;
        height: 32px;
        border-radius: var(--radius-lg);
        background: rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    /* Back Link */
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-size: 0.9rem;
        margin-top: auto;
        padding: 8px 12px;
        border-radius: var(--radius-full);
        width: fit-content;
        transition: var(--transition);
    }

    .back-link:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }

    /* Right Panel */
    .register-right {
        padding: 48px;
        background: white;
    }

    .register-box {
        max-width: 400px;
        margin: 0 auto;
    }

    .register-header {
        text-align: center;
        margin-bottom: 32px;
    }

    .register-header-icon {
        font-size: 2.5rem;
        margin-bottom: 16px;
        color: var(--primary-600);
    }

    .register-header-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--neutral-900);
        margin-bottom: 8px;
    }

    .register-header-subtitle {
        color: var(--neutral-500);
        font-size: 0.9rem;
    }

    /* Alert */
    .alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: var(--radius-lg);
        margin-bottom: 24px;
        font-size: 0.9rem;
    }

    .alert-danger {
        background: #fef2f2;
        border-left: 4px solid var(--danger-500);
        color: #991b1b;
    }

    /* Form */
    .form-group {
        margin-bottom: 20px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--neutral-700);
        margin-bottom: 6px;
    }

    .form-label span {
        color: var(--danger-500);
        margin-left: 2px;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--neutral-400);
        font-size: 1rem;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 1px solid var(--neutral-200);
        border-radius: var(--radius-lg);
        font-size: 0.95rem;
        font-family: 'Inter', sans-serif;
        transition: var(--transition);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-500);
        box-shadow: 0 0 0 3px var(--primary-100);
    }

    .password-toggle {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--neutral-400);
        cursor: pointer;
        padding: 4px;
    }

    .password-toggle:hover {
        color: var(--primary-500);
    }

    /* Password Strength */
    .password-strength {
        margin-top: 8px;
        display: flex;
        gap: 4px;
        height: 4px;
    }

    .strength-bar {
        flex: 1;
        border-radius: var(--radius-full);
        background: var(--neutral-200);
        transition: var(--transition);
    }

    .strength-bar.weak {
        background: var(--danger-500);
    }

    .strength-bar.medium {
        background: var(--warning-500);
    }

    .strength-bar.strong {
        background: var(--success-500);
    }

    .strength-text {
        font-size: 0.7rem;
        margin-top: 4px;
        color: var(--neutral-500);
    }

    /* Register Button */
    .btn-register {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: var(--transition);
        box-shadow: 0 4px 6px -1px rgba(67, 97, 238, 0.3);
        margin-top: 10px;
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.4);
    }

    /* Divider */
    .divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 24px 0;
        color: var(--neutral-400);
        font-size: 0.8rem;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--neutral-200);
    }

    /* Login Link */
    .login-link {
        text-align: center;
        font-size: 0.9rem;
        color: var(--neutral-600);
    }

    .login-link a {
        color: var(--primary-600);
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: var(--transition);
    }

    .login-link a:hover {
        color: var(--primary-700);
        gap: 8px;
    }

    /* Footer */
    .footer-text {
        text-align: center;
        margin-top: 24px;
        font-size: 0.7rem;
        color: var(--neutral-400);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .register-container {
            grid-template-columns: 1fr;
        }

        .register-left {
            display: none;
        }

        .register-right {
            padding: 32px 24px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 10px;
        }

        .register-right {
            padding: 24px 16px;
        }

        .register-header-title {
            font-size: 1.4rem;
        }
    }
    </style>
</head>

<body>
    <div class="register-container">
        <!-- Left Panel -->
        <div class="register-left">
            <div class="register-left-content">
                <div class="register-icon">
                    <i class="fas fa-user-plus"></i>
                </div>

                <h1 class="register-title-large">
                    Bergabung dengan<br>
                    <span>Perpustakaan Digital</span>
                </h1>

                <p class="register-description">
                    Daftar sebagai anggota dan nikmati kemudahan mengakses ribuan koleksi buku dari mana saja, kapan
                    saja.
                </p>

                <!-- Benefits List -->
                <ul class="benefits-list">
                    <li class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-book"></i></div>
                        <span>Akses koleksi buku digital kapan saja</span>
                    </li>
                    <li class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-pen"></i></div>
                        <span>Ajukan peminjaman langsung dari sistem</span>
                    </li>
                    <li class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-chart-line"></i></div>
                        <span>Pantau riwayat pinjaman dan status denda</span>
                    </li>
                    <li class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-star"></i></div>
                        <span>Tulis ulasan dan rating untuk buku favorit</span>
                    </li>
                    <li class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-bell"></i></div>
                        <span>Notifikasi jatuh tempo pengembalian buku</span>
                    </li>
                </ul>

                <!-- Back to Home -->
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="register-right">
            <div class="register-box">
                <div class="register-header">
                    <div class="register-header-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h2 class="register-header-title">Buat Akun Baru</h2>
                    <p class="register-header-subtitle">Isi data diri Anda untuk mendaftar sebagai anggota</p>
                </div>

                <!-- Alert Messages -->
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="POST" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">NIS <span>*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-id-card input-icon"></i>
                                <input type="text" name="nis" class="form-control" placeholder="Nomor Induk Siswa"
                                    required value="<?= htmlspecialchars($old['nis'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kelas <span>*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-school input-icon"></i>
                                <input type="text" name="kelas" class="form-control" placeholder="Contoh: XII RPL"
                                    required value="<?= htmlspecialchars($old['kelas'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span>*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="nama_anggota" class="form-control"
                                placeholder="Nama sesuai data sekolah" required
                                value="<?= htmlspecialchars($old['nama'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Username <span>*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-at input-icon"></i>
                                <input type="text" name="username" class="form-control" placeholder="Buat username unik"
                                    required value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" name="email" class="form-control" placeholder="email@sekolah.com"
                                    value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password <span>*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" class="form-control"
                                placeholder="Minimal 6 karakter" required minlength="6"
                                oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>

                        <!-- Password Strength Indicator -->
                        <div class="password-strength">
                            <div class="strength-bar" id="bar1"></div>
                            <div class="strength-bar" id="bar2"></div>
                            <div class="strength-bar" id="bar3"></div>
                            <div class="strength-bar" id="bar4"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>

                    <button type="submit" name="register" class="btn-register">
                        <span>Daftar Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="divider">
                    <span>sudah punya akun?</span>
                </div>

                <div class="login-link">
                    <span>Masuk dengan akun yang ada </span>
                    <a href="login.php">
                        Masuk Sekarang
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <p class="footer-text">
                    © <?= date('Y') ?> Perpustakaan Digital · Daftar gratis untuk semua siswa terdaftar
                </p>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    function checkPasswordStrength(password) {
        const bars = [
            document.getElementById('bar1'),
            document.getElementById('bar2'),
            document.getElementById('bar3'),
            document.getElementById('bar4')
        ];
        const strengthText = document.getElementById('strengthText');

        // Reset bars
        bars.forEach(bar => {
            bar.className = 'strength-bar';
        });

        if (!password) {
            strengthText.textContent = '';
            return;
        }

        let score = 0;

        // Length check
        if (password.length >= 6) score++;
        if (password.length >= 10) score++;

        // Complexity checks
        if (/[A-Z]/.test(password) && /[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;

        // Update bars
        for (let i = 0; i < score; i++) {
            if (bars[i]) {
                if (score <= 2) bars[i].classList.add('weak');
                else if (score === 3) bars[i].classList.add('medium');
                else bars[i].classList.add('strong');
            }
        }

        // Update text
        const strengthLevels = ['Lemah', 'Cukup', 'Baik', 'Kuat'];
        strengthText.textContent = score > 0 ? `Kekuatan password: ${strengthLevels[score-1]}` : '';
    }

    // Prevent form resubmission
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>

</html>