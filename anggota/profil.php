<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAnggota();
$conn = getConnection();

$id = getAnggotaId();
$msg = '';
$msgType = '';

// Ambil data anggota
$anggota = $conn->query("SELECT * FROM anggota WHERE id_anggota=$id")->fetch_assoc();

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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profil – Anggota Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    :root {
        --anggota-primary: #2c4f7c;
        --anggota-primary-light: #3a6ea5;
        --anggota-primary-soft: #e8f0fe;
        --anggota-accent: #10b981;
        --anggota-warning: #f59e0b;
        --anggota-danger: #ef4444;
        --anggota-success: #10b981;
        --ink: #1e1e2e;
        --ink-mid: #4a4a6a;
        --ink-light: #7a7a9a;
        --ink-faint: #b0b0c8;
        --rule: #e2e2ec;
        --surface: #f7f7fb;
        --white: #ffffff;
        --serif: 'Lora', Georgia, serif;
        --sans: 'DM Sans', sans-serif;
        --radius: 10px;
        --shadow-sm: 0 1px 4px rgba(26, 39, 68, .07);
        --shadow-md: 0 4px 20px rgba(26, 39, 68, .10);
        --shadow-lg: 0 8px 40px rgba(26, 39, 68, .14);
    }

    body {
        font-family: var(--sans);
        background: var(--surface);
    }

    .app-wrap {
        display: flex;
        min-height: 100vh;
    }

    .main-area {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .content {
        padding: 24px;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
        box-sizing: border-box;
    }

    /* ── Page layout ── */
    .profil-wrap {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 24px;
        align-items: start;
        max-width: 1000px;
        margin: 0 auto;
    }

    /* ── LEFT COLUMN ── */
    .profil-sidebar {}

    /* Identity card */
    .id-card {
        background: var(--white);
        border: 1px solid var(--rule);
        border-radius: var(--radius);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        margin-bottom: 16px;
    }

    .id-card-banner {
        height: 80px;
        background: linear-gradient(135deg, var(--anggota-primary) 0%, #3b1d8a 50%, var(--anggota-primary-light) 100%);
        position: relative;
    }

    .id-card-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(circle at 20% 50%, rgba(255, 255, 255, .07) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(201, 146, 43, .15) 0%, transparent 40%);
    }

    .id-card-body {
        padding: 0 24px 24px;
        text-align: center;
    }

    /* ── Avatar ── */
    .avatar-ring {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        border: 3px solid var(--white);
        box-shadow: 0 2px 12px rgba(26, 39, 68, .18);
        margin: -44px auto 12px;
        position: relative;
        cursor: pointer;
        transition: transform .2s;
    }

    .avatar-ring:hover {
        transform: scale(1.04);
    }

    .avatar-ring:hover .avatar-overlay {
        opacity: 1;
    }

    .avatar-img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        display: block;
    }

    .avatar-initials {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--anggota-primary) 0%, var(--anggota-primary-light) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--serif);
        font-size: 1.5rem;
        font-weight: 600;
        color: #fff;
        letter-spacing: .02em;
    }

    .avatar-overlay {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(44, 79, 124, .72);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity .2s;
        gap: 2px;
    }

    .avatar-overlay svg {
        color: #fff;
    }

    .avatar-overlay span {
        font-size: .62rem;
        color: #fff;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    /* Camera badge */
    .avatar-cam {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 24px;
        height: 24px;
        background: var(--anggota-primary);
        border: 2px solid #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
    }

    .profil-name {
        font-family: var(--serif);
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--ink);
        margin-bottom: 4px;
    }

    .profil-role-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: var(--anggota-primary-soft);
        color: var(--anggota-primary);
        border: 1px solid rgba(44, 79, 124, .2);
        border-radius: 20px;
        padding: 3px 12px;
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        margin-bottom: 16px;
    }

    /* Meta info rows */
    .id-meta {
        text-align: left;
        border-top: 1px solid var(--rule);
        padding-top: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .id-meta-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .id-meta-icon {
        width: 30px;
        height: 30px;
        background: var(--surface);
        border: 1px solid var(--rule);
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--ink-light);
    }

    .id-meta-content {}

    .id-meta-label {
        font-size: .67rem;
        color: var(--ink-faint);
        text-transform: uppercase;
        letter-spacing: .06em;
        font-weight: 600;
        line-height: 1;
        margin-bottom: 2px;
    }

    .id-meta-val {
        font-size: .82rem;
        color: var(--ink-mid);
        font-weight: 500;
        word-break: break-all;
    }

    /* Photo upload mini form */
    .foto-upload-card {
        background: var(--white);
        border: 1px solid var(--rule);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        padding: 16px;
    }

    .foto-upload-title {
        font-size: .75rem;
        font-weight: 600;
        color: var(--ink-mid);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .foto-upload-title svg {
        color: var(--anggota-primary);
    }

    .foto-drop-zone {
        border: 2px dashed var(--rule);
        border-radius: 8px;
        padding: 20px 12px;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s, background .2s;
        background: var(--surface);
    }

    .foto-drop-zone:hover {
        border-color: var(--anggota-primary);
        background: var(--anggota-primary-soft);
    }

    .foto-drop-zone.dragover {
        border-color: var(--anggota-primary);
        background: var(--anggota-primary-soft);
    }

    .foto-drop-icon {
        width: 36px;
        height: 36px;
        background: var(--anggota-primary-soft);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        color: var(--anggota-primary);
    }

    .foto-drop-label {
        font-size: .78rem;
        color: var(--ink-light);
        line-height: 1.5;
    }

    .foto-drop-label strong {
        color: var(--anggota-primary);
        cursor: pointer;
    }

    .foto-hint {
        font-size: .68rem;
        color: var(--ink-faint);
        margin-top: 8px;
        line-height: 1.4;
    }

    .foto-filename {
        font-size: .75rem;
        color: var(--anggota-primary);
        font-weight: 500;
        margin-top: 8px;
        display: none;
        word-break: break-all;
    }

    .btn-upload {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        background: var(--anggota-primary);
        color: #fff;
        border: none;
        border-radius: 7px;
        font-family: var(--sans);
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
        width: 100%;
        justify-content: center;
        margin-top: 10px;
    }

    .btn-upload:hover {
        background: var(--anggota-primary-light);
    }

    /* ── RIGHT COLUMN ── */
    .profil-forms {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Form cards */
    .form-card {
        background: var(--white);
        border: 1px solid var(--rule);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        animation: fadeUp .4s ease both;
    }

    .form-card:nth-child(2) {
        animation-delay: .08s;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-card-header {
        padding: 18px 24px 14px;
        border-bottom: 1px solid var(--rule);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-card-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .form-card-icon.blue {
        background: var(--anggota-primary-soft);
        color: var(--anggota-primary);
    }

    .form-card-icon.gold {
        background: #fef3c7;
        color: var(--anggota-warning);
    }

    .form-card-title {
        font-family: var(--serif);
        font-size: .95rem;
        font-weight: 600;
        color: var(--ink);
    }

    .form-card-sub {
        font-size: .74rem;
        color: var(--ink-faint);
        margin-top: 1px;
    }

    .form-card-body {
        padding: 22px 24px;
    }

    .form-card-footer {
        padding: 14px 24px;
        background: var(--surface);
        border-top: 1px solid var(--rule);
        display: flex;
        justify-content: flex-end;
    }

    /* Form fields */
    .field-group {
        margin-bottom: 18px;
    }

    .field-group:last-child {
        margin-bottom: 0;
    }

    .field-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .field-label {
        display: block;
        font-size: .75rem;
        font-weight: 600;
        color: var(--ink-mid);
        letter-spacing: .03em;
        margin-bottom: 6px;
    }

    .field-label span {
        color: var(--anggota-danger);
        margin-left: 2px;
    }

    .field-input {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--rule);
        border-radius: 8px;
        font-family: var(--sans);
        font-size: .85rem;
        color: var(--ink);
        background: var(--white);
        transition: border-color .15s, box-shadow .15s;
        box-sizing: border-box;
        outline: none;
    }

    .field-input:focus {
        border-color: var(--anggota-primary);
        box-shadow: 0 0 0 3px rgba(44, 79, 124, .1);
    }

    .field-input:read-only,
    .field-input:disabled {
        background: var(--surface);
        color: var(--ink-light);
        cursor: default;
    }

    textarea.field-input {
        resize: vertical;
        min-height: 80px;
    }

    /* Password strength */
    .pass-strength {
        margin-top: 6px;
        display: flex;
        gap: 3px;
    }

    .strength-bar {
        height: 3px;
        flex: 1;
        border-radius: 2px;
        background: var(--rule);
        transition: background .3s;
    }

    .strength-bar.weak {
        background: var(--anggota-danger);
    }

    .strength-bar.medium {
        background: var(--anggota-warning);
    }

    .strength-bar.strong {
        background: var(--anggota-success);
    }

    .strength-text {
        font-size: .68rem;
        color: var(--ink-faint);
        margin-top: 4px;
    }

    /* Buttons */
    .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 22px;
        background: var(--anggota-primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-family: var(--sans);
        font-size: .84rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, transform .1s, box-shadow .15s;
        box-shadow: 0 2px 8px rgba(44, 79, 124, .18);
    }

    .btn-save:hover {
        background: var(--anggota-primary-light);
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(44, 79, 124, .22);
    }

    .btn-save:active {
        transform: translateY(0);
    }

    .divider {
        height: 1px;
        background: var(--rule);
        margin: 24px 0 16px;
    }

    /* Alert */
    .profil-alert {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: .83rem;
        font-weight: 500;
        margin-bottom: 20px;
        animation: fadeUp .3s ease;
    }

    .profil-alert.success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .profil-alert.danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .profil-alert.warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .profil-alert svg {
        flex-shrink: 0;
    }

    /* NIS/Kelas read-only note */
    .field-note {
        font-size: .7rem;
        color: var(--ink-faint);
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    @media (max-width: 720px) {
        .profil-wrap {
            grid-template-columns: 1fr;
        }

        .field-row {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>
    <div class="app-wrap">
        <?php include 'includes/nav.php'; ?>
        <div class="main-area">
            <?php include 'includes/header.php'; ?>

            <main class="content">

                <?php if ($msg): ?>
                <div class="profil-alert <?= $msgType ?>">
                    <?php if ($msgType === 'success'): ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <?php elseif ($msgType === 'warning'): ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <?php else: ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    <?php endif; ?>
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endif; ?>

                <div class="profil-wrap">

                    <!-- ═══ LEFT: Identity sidebar ═══ -->
                    <div class="profil-sidebar">

                        <!-- ID Card -->
                        <div class="id-card">
                            <div class="id-card-banner"></div>
                            <div class="id-card-body">

                                <!-- Avatar — klik untuk upload -->
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
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path
                                                    d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                                <circle cx="12" cy="13" r="4" />
                                            </svg>
                                            <span>Ganti</span>
                                        </div>
                                        <div class="avatar-cam">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff"
                                                stroke-width="2.5">
                                                <path
                                                    d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                                <circle cx="12" cy="13" r="3" />
                                            </svg>
                                        </div>
                                    </div>
                                </label>

                                <div class="profil-name"><?= htmlspecialchars($anggota['nama_anggota']) ?></div>
                                <div class="profil-role-badge">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                                    </svg>
                                    Anggota Perpustakaan
                                </div>

                                <div class="id-meta">
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                <line x1="16" y1="2" x2="16" y2="6" />
                                                <line x1="8" y1="2" x2="8" y2="6" />
                                                <line x1="3" y1="10" x2="21" y2="10" />
                                            </svg>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">NIS</div>
                                            <div class="id-meta-val"><?= htmlspecialchars($anggota['nis'] ?? '—') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                                <circle cx="12" cy="7" r="4" />
                                            </svg>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">Username</div>
                                            <div class="id-meta-val">
                                                <?= htmlspecialchars($anggota['username'] ?? '—') ?></div>
                                        </div>
                                    </div>
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path
                                                    d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                                <polyline points="22,6 12,13 2,6" />
                                            </svg>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">Email</div>
                                            <div class="id-meta-val"><?= htmlspecialchars($anggota['email'] ?? '—') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="id-meta-row">
                                        <div class="id-meta-icon">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10" />
                                                <path d="M12 6v6l4 2" />
                                            </svg>
                                        </div>
                                        <div class="id-meta-content">
                                            <div class="id-meta-label">Kelas</div>
                                            <div class="id-meta-val"><?= htmlspecialchars($anggota['kelas'] ?? '—') ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Photo upload form -->
                        <div class="foto-upload-card">
                            <div class="foto-upload-title">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                                Foto Profil
                            </div>
                            <form method="POST" enctype="multipart/form-data" id="fotoForm">
                                <div class="foto-drop-zone" id="dropZone"
                                    onclick="document.getElementById('fotoInput').click()">
                                    <div class="foto-drop-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path
                                                d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                            <circle cx="12" cy="13" r="4" />
                                        </svg>
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
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="17 8 12 3 7 8" />
                                        <line x1="12" y1="3" x2="12" y2="15" />
                                    </svg>
                                    Simpan Foto
                                </button>
                            </form>
                        </div>

                    </div>

                    <!-- ═══ RIGHT: Forms ═══ -->
                    <div class="profil-forms">

                        <!-- Edit Info -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <div class="form-card-icon blue">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
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
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Change Password -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <div class="form-card-icon gold">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
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
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.5">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg>
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
    // ── Avatar preview on file select ──
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

    // ── Drag-and-drop ──
    if (dropZone) {
        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file) {
                // Assign to input for form submit
                const dt = new DataTransfer();
                dt.items.add(file);
                fotoInput.files = dt.files;
                handleFile(file);
            }
        });
    }

    // ── Password strength indicator ──
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
        txt.style.color = score === 4 ? 'var(--anggota-success)' : score >= 2 ? 'var(--anggota-warning)' :
            'var(--anggota-danger)';
    }

    // ── Password match check ──
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
            note.innerHTML =
                '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Password cocok';
            note.style.color = 'var(--anggota-success)';
        } else {
            note.innerHTML =
                '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Password tidak cocok';
            note.style.color = 'var(--anggota-danger)';
        }
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>