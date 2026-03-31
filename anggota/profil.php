<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();

$conn = getConnection();
$id = getAnggotaId();
$msg = '';
$msgType = '';

// Ambil data anggota
$anggota = $conn->query("SELECT * FROM anggota WHERE id_anggota = $id")->fetch_assoc();

// Ambil data user untuk header
$userStmt = $conn->prepare("SELECT foto, nama_anggota FROM anggota WHERE id_anggota = ?");
$userStmt->bind_param("i", $id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Inisial untuk avatar header
$initialsHeader = '';
foreach (explode(' ', trim($userData['nama_anggota'] ?? getAnggotaName())) as $w) {
    $initialsHeader .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initialsHeader) >= 2) break;
}
$fotoPathHeader = (!empty($userData['foto']) && file_exists('../' . $userData['foto'])) 
            ? '../' . htmlspecialchars($userData['foto']) 
            : null;

// Inisial untuk fallback avatar
$initials = '';
foreach (explode(' ', trim($anggota['nama_anggota'] ?? '')) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}

/* ================= UPLOAD FOTO ================= */
if (isset($_POST['upload_foto'])) {
    $adaFile = isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE;

    if (!$adaFile) {
        $msg = 'Pilih file foto terlebih dahulu.';
        $msgType = 'danger';
    } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Upload gagal, coba lagi.';
        $msgType = 'danger';
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $ftype = mime_content_type($_FILES['foto']['tmp_name']);
        $fsize = $_FILES['foto']['size'];

        if (!in_array($ftype, $allowedTypes)) {
            $msg = 'Format tidak didukung. Gunakan JPG, PNG, atau WebP.';
            $msgType = 'danger';
        } elseif ($fsize > 2 * 1024 * 1024) {
            $msg = 'Ukuran file melebihi 2 MB.';
            $msgType = 'danger';
        } else {
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$ftype];
            $newName = 'foto_anggota_' . $id . '_' . time() . '.' . $ext;
            $dest = '../uploads/foto_anggota/' . $newName;

            if (!is_dir('../uploads/foto_anggota/')) {
                mkdir('../uploads/foto_anggota/', 0755, true);
            }

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                // Hapus foto lama jika ada
                if (!empty($anggota['foto']) && file_exists('../' . $anggota['foto'])) {
                    unlink('../' . $anggota['foto']);
                }

                $s = $conn->prepare("UPDATE anggota SET foto=? WHERE id_anggota=?");
                $fotoPath = 'uploads/foto_anggota/' . $newName;
                $s->bind_param("si", $fotoPath, $id);
                if ($s->execute()) {
                    $anggota['foto'] = $fotoPath;
                    $msg = 'Foto profil berhasil diperbarui!';
                    $msgType = 'success';
                } else {
                    unlink($dest);
                    $msg = 'Gagal menyimpan foto ke database.';
                    $msgType = 'danger';
                }
                $s->close();
            } else {
                $msg = 'Gagal memindahkan file foto.';
                $msgType = 'danger';
            }
        }
    }
}

/* ================= UPDATE PROFIL ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {

    $nama   = trim($_POST['nama_anggota']);
    $email  = trim($_POST['email']);
    $alamat = trim($_POST['alamat']);
    $telp   = trim($_POST['no_telepon']);

    if (!empty($_POST['password_baru'])) {

        $pw_lama = trim($_POST['password_lama']);
        $pw_baru = trim($_POST['password_baru']);

        $chk = $conn->query("SELECT password FROM anggota WHERE id_anggota=$id")->fetch_assoc();

        if ($chk['password'] !== $pw_lama) {
            $msg = 'Password lama salah!';
            $msgType = 'danger';
        } else {
            $s = $conn->prepare("UPDATE anggota SET nama_anggota=?, email=?, alamat=?, no_telepon=?, password=? WHERE id_anggota=?");
            $s->bind_param("sssssi", $nama, $email, $alamat, $telp, $pw_baru, $id);
            $ok = $s->execute();
            $s->close();

            if ($ok) {
                $msg = 'Profil & password berhasil diperbarui!';
                $msgType = 'success';
                $_SESSION['anggota_nama'] = $nama;
                // Refresh data
                $anggota = $conn->query("SELECT * FROM anggota WHERE id_anggota=$id")->fetch_assoc();
            } else {
                $msg = 'Gagal memperbarui profil!';
                $msgType = 'danger';
            }
        }

    } else {

        $s = $conn->prepare("UPDATE anggota SET nama_anggota=?, email=?, alamat=?, no_telepon=? WHERE id_anggota=?");
        $s->bind_param("ssssi", $nama, $email, $alamat, $telp, $id);
        $ok = $s->execute();
        $s->close();

        if ($ok) {
            $msg = 'Profil berhasil diperbarui!';
            $msgType = 'success';
            $_SESSION['anggota_nama'] = $nama;
            // Refresh data
            $anggota = $conn->query("SELECT * FROM anggota WHERE id_anggota=$id")->fetch_assoc();
        } else {
            $msg = 'Gagal memperbarui profil!';
            $msgType = 'danger';
        }
    }
}

// Update inisial setelah refresh
$initials = '';
foreach (explode(' ', trim($anggota['nama_anggota'] ?? '')) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}

$fotoSrc = (!empty($anggota['foto']) && file_exists('../' . $anggota['foto']))
    ? '../' . htmlspecialchars($anggota['foto'])
    : null;

$page_title = 'Profil Saya';
$page_sub   = 'Kelola informasi akun anggota';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — Anggota Cozy-Library</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/anggota/profil.css?v=<?= @filemtime('../assets/css/anggota/profil.css')?:time() ?>">
</head>

<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-wrap">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">📚</div>
                <div>
                    <div class="brand-name">Cozy-Library</div>
                    <div class="brand-role">ANGGOTA</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-section-label">UTAMA</span>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <span class="nav-section-label">KATALOG</span>
                <a href="katalog.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <span>Katalog Buku</span>
                </a>

                <span class="nav-section-label">TRANSAKSI</span>
                <a href="pinjam.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>Pinjam Buku</span>
                </a>
                <a href="kembali.php" class="nav-link">
                    <i class="fas fa-undo-alt"></i>
                    <span>Kembalikan Buku</span>
                </a>
                <a href="riwayat.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span>
                </a>

                <span class="nav-section-label">KOMUNITAS</span>
                <a href="ulasan.php" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>Ulasan Buku</span>
                </a>

                <span class="nav-section-label">AKUN</span>
                <a href="profil.php" class="nav-link active">
                    <i class="fas fa-user"></i>
                    <span>Profil Saya</span>
                </a>
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-globe"></i>
                    <span>Beranda</span>
                </a>
            </nav>

            <div class="sidebar-foot">
                <a href="logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="main-area">
            <!-- HEADER -->
            <header class="topbar">
                <div class="topbar-left" style="display: flex; align-items: center; gap: 16px;">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-info">
                        <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
                        <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date">
                        <i class="far fa-calendar-alt"></i>
                        <span><?= date('d M Y') ?></span>
                    </div>
                    <div class="topbar-user">
                        <div class="topbar-avatar">
                            <?php if ($fotoPathHeader): ?>
                            <img src="<?= $fotoPathHeader ?>" alt="Foto">
                            <?php else: ?>
                            <?= htmlspecialchars($initialsHeader) ?>
                            <?php endif; ?>
                        </div>
                        <span class="topbar-username"><?= htmlspecialchars(getAnggotaName()) ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </header>

            <!-- CONTENT -->
            <main class="content">
                <?php if ($msg): ?>
                <div class="profil-alert <?= $msgType ?>">
                    <i
                        class="fas <?= $msgType === 'success' ? 'fa-check-circle' : ($msgType === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle') ?>"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endif; ?>

                <div class="profil-wrap">
                    <!-- LEFT COLUMN - Profile Card -->
                    <div class="profil-sidebar">
                        <!-- ID Card -->
                        <div class="id-card">
                            <div class="id-card-banner"></div>
                            <div class="id-card-body">
                                <!-- Avatar -->
                                <label for="fotoInput" style="display:block;width:fit-content;margin:0 auto">
                                    <div class="avatar-ring">
                                        <?php if ($fotoSrc): ?>
                                        <img src="<?= $fotoSrc ?>" alt="Foto Profil" class="avatar-img"
                                            id="avatarPreview">
                                        <?php else: ?>
                                        <div class="avatar-initials" id="avatarInitials">
                                            <?= htmlspecialchars($initials) ?></div>
                                        <img src="" alt="" class="avatar-img" id="avatarPreview" style="display:none">
                                        <?php endif; ?>
                                        <div class="avatar-overlay">
                                            <i class="fas fa-camera"></i>
                                            <span>Ganti</span>
                                        </div>
                                        <div class="avatar-cam">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    </div>
                                </label>

                                <div class="profil-name"><?= htmlspecialchars($anggota['nama_anggota']) ?></div>
                                <div class="profil-role-badge">
                                    <i class="fas fa-user-graduate"></i> Anggota Cozy-Library
                                </div>

                                <div class="id-meta">
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <i class="fas fa-id-card"></i>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">NIS</div>
                                            <div class="id-meta-val"><?= htmlspecialchars($anggota['nis'] ?? '—') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">Username</div>
                                            <div class="id-meta-val">
                                                <?= htmlspecialchars($anggota['username'] ?? '—') ?></div>
                                        </div>
                                    </div>
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">Email</div>
                                            <div class="id-meta-val"><?= htmlspecialchars($anggota['email'] ?? '—') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">Kelas</div>
                                            <div class="id-meta-val"><?= htmlspecialchars($anggota['kelas'] ?? '—') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!empty($anggota['no_telepon'])): ?>
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">Telepon</div>
                                            <div class="id-meta-val"><?= htmlspecialchars($anggota['no_telepon']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Photo upload form -->
                        <div class="foto-upload-card">
                            <div class="foto-upload-title">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Foto Profil
                            </div>
                            <form method="POST" enctype="multipart/form-data" id="fotoForm">
                                <div class="foto-drop-zone" id="dropZone"
                                    onclick="document.getElementById('fotoInput').click()">
                                    <div class="foto-drop-icon">
                                        <i class="fas fa-images"></i>
                                    </div>
                                    <div class="foto-drop-label">
                                        Seret foto ke sini atau<br><strong>klik untuk memilih</strong>
                                    </div>
                                    <div class="foto-hint">JPG, PNG, WebP · Maks. 2 MB</div>
                                    <div class="foto-filename" id="fotoFilename"></div>
                                </div>
                                <input type="file" id="fotoInput" name="foto" accept=".jpg,.jpeg,.png,.webp"
                                    style="display:none">
                                <button type="submit" name="upload_foto" class="btn-upload">
                                    <i class="fas fa-upload"></i>
                                    Simpan Foto
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN - Forms -->
                    <div class="profil-forms">
                        <!-- Edit Info -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <div class="form-card-icon">
                                    <i class="fas fa-user-edit"></i>
                                </div>
                                <div>
                                    <div class="form-card-title">Informasi Pribadi</div>
                                    <div class="form-card-sub">Perbarui data diri Anda</div>
                                </div>
                            </div>
                            <form method="POST">
                                <div class="form-card-body">
                                    <div class="field-row">
                                        <div class="field-group">
                                            <label class="field-label">NIS</label>
                                            <input type="text" class="field-input"
                                                value="<?= htmlspecialchars($anggota['nis'] ?? '') ?>" disabled>
                                        </div>
                                        <div class="field-group">
                                            <label class="field-label">Kelas</label>
                                            <input type="text" class="field-input"
                                                value="<?= htmlspecialchars($anggota['kelas'] ?? '') ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">Nama Lengkap <span>*</span></label>
                                        <input type="text" name="nama_anggota" class="field-input"
                                            value="<?= htmlspecialchars($anggota['nama_anggota']) ?>" required
                                            placeholder="Masukkan nama lengkap">
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">Email</label>
                                        <input type="email" name="email" class="field-input"
                                            value="<?= htmlspecialchars($anggota['email'] ?? '') ?>"
                                            placeholder="contoh@email.com">
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">Alamat</label>
                                        <textarea name="alamat" class="field-input" rows="2"
                                            placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($anggota['alamat'] ?? '') ?></textarea>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">No. Telepon</label>
                                        <input type="text" name="no_telepon" class="field-input"
                                            value="<?= htmlspecialchars($anggota['no_telepon'] ?? '') ?>"
                                            placeholder="Contoh: 08123456789">
                                    </div>
                                </div>
                                <div class="form-card-footer">
                                    <button type="submit" name="update_profil" class="btn-save">
                                        <i class="fas fa-save"></i>
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Change Password -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <div class="form-card-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <div>
                                    <div class="form-card-title">Ubah Password</div>
                                    <div class="form-card-sub">Gunakan password yang kuat dan unik</div>
                                </div>
                            </div>
                            <form method="POST">
                                <div class="form-card-body">
                                    <div class="field-group">
                                        <label class="field-label">Password Saat Ini <span>*</span></label>
                                        <input type="password" name="password_lama" class="field-input"
                                            placeholder="Masukkan password lama">
                                    </div>
                                    <div class="field-row">
                                        <div class="field-group">
                                            <label class="field-label">Password Baru <span>*</span></label>
                                            <input type="password" name="password_baru" class="field-input"
                                                id="newPassInput" placeholder="Min. 8 karakter"
                                                oninput="checkStrength(this.value)">
                                            <div class="pass-strength" id="strengthBars">
                                                <div class="strength-bar" id="bar1"></div>
                                                <div class="strength-bar" id="bar2"></div>
                                                <div class="strength-bar" id="bar3"></div>
                                                <div class="strength-bar" id="bar4"></div>
                                            </div>
                                            <div class="strength-text" id="strengthText"></div>
                                        </div>
                                        <div class="field-group">
                                            <label class="field-label">Konfirmasi Password <span>*</span></label>
                                            <input type="password" name="password_konfirmasi" class="field-input"
                                                id="confirmPassInput" placeholder="Ulangi password baru"
                                                oninput="checkMatch()">
                                            <div class="field-note" id="matchNote" style="display:none"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-card-footer">
                                    <button type="submit" name="update_profil" class="btn-save">
                                        <i class="fas fa-key"></i>
                                        Ubah Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        });
    }

    // Foto upload handling
    const fotoInput = document.getElementById('fotoInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarInitials = document.getElementById('avatarInitials');
    const fotoFilename = document.getElementById('fotoFilename');
    const dropZone = document.getElementById('dropZone');

    fotoInput.addEventListener('change', function() {
        handleFile(this.files[0]);
    });

    function handleFile(file) {
        if (!file) return;

        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            alert('Format tidak didukung. Gunakan JPG, PNG, atau WebP.');
            fotoInput.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran file melebihi 2 MB.');
            fotoInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            if (avatarPreview) {
                avatarPreview.src = e.target.result;
                avatarPreview.style.display = 'block';
                if (avatarInitials) avatarInitials.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);

        if (fotoFilename) {
            fotoFilename.textContent = '📎 ' + file.name;
            fotoFilename.style.display = 'block';
        }
    }

    // Drag and drop
    if (dropZone) {
        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');

            const file = e.dataTransfer.files[0];
            if (file) {
                const dt = new DataTransfer();
                dt.items.add(file);
                fotoInput.files = dt.files;
                handleFile(file);
            }
        });
    }

    // Password strength indicator
    function checkStrength(val) {
        const bars = [
            document.getElementById('bar1'),
            document.getElementById('bar2'),
            document.getElementById('bar3'),
            document.getElementById('bar4')
        ];
        const txt = document.getElementById('strengthText');

        if (!bars[0] || !txt) return;

        bars.forEach(b => {
            if (b) b.className = 'strength-bar';
        });

        if (!val) {
            txt.textContent = '';
            return;
        }

        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = ['', 'weak', 'medium', 'medium', 'strong'];
        const labels = ['', 'Lemah', 'Cukup', 'Baik', 'Kuat'];

        for (let i = 0; i < score; i++) {
            if (bars[i]) bars[i].classList.add(levels[score]);
        }

        txt.textContent = labels[score] ? 'Kekuatan: ' + labels[score] : '';
        txt.style.color = score === 4 ? 'var(--success-600)' : score >= 2 ? 'var(--warning-600)' : 'var(--danger-600)';
    }

    // Password match check
    function checkMatch() {
        const np = document.getElementById('newPassInput');
        const cp = document.getElementById('confirmPassInput');
        const note = document.getElementById('matchNote');

        if (!np || !cp || !note) return;

        if (!cp.value) {
            note.style.display = 'none';
            return;
        }

        note.style.display = 'flex';

        if (np.value === cp.value) {
            note.innerHTML = '<i class="fas fa-check-circle"></i> Password cocok';
            note.style.color = 'var(--success-600)';
        } else {
            note.innerHTML = '<i class="fas fa-exclamation-circle"></i> Password tidak cocok';
            note.style.color = 'var(--danger-600)';
        }
    }

    // Prevent form resubmission
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>