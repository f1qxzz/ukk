<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

$conn = getConnection();
$msg = '';
$msgType = '';

// Ambil data user untuk header
$userId = getPenggunaId();
$userStmt = $conn->prepare("SELECT foto, nama_pengguna FROM pengguna WHERE id_pengguna = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Inisial untuk avatar
$initials = '';
foreach (explode(' ', trim($userData['nama_pengguna'] ?? getPenggunaName())) as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($initials) >= 2) break;
}
$fotoPath = (!empty($userData['foto']) && file_exists('../' . $userData['foto'])) 
            ? '../' . htmlspecialchars($userData['foto']) 
            : null;

// Hitung statistik
$totalAdmin = $conn->query("SELECT COUNT(*) as total FROM pengguna WHERE level='admin'")->fetch_assoc()['total'];
$totalPetugas = $conn->query("SELECT COUNT(*) as total FROM pengguna WHERE level='petugas'")->fetch_assoc()['total'];
$totalPengguna = $conn->query("SELECT COUNT(*) as total FROM pengguna")->fetch_assoc()['total'];

// CRUD Operations
if (isset($_POST['add'])) {
    $u = trim($_POST['username']);
    $n = trim($_POST['nama_pengguna']);
    $e = trim($_POST['email']);
    $lv = $_POST['level'];
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE username=?");
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $msg = 'Username sudah digunakan!';
        $msgType = 'warning';
    } else {
        $stmt = $conn->prepare("INSERT INTO pengguna(username, password, nama_pengguna, email, level) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $u, $p, $n, $e, $lv);
        if ($stmt->execute()) {
            $msg = 'Pengguna berhasil ditambahkan!';
            $msgType = 'success';
        } else {
            $msg = 'Gagal: ' . $conn->error;
            $msgType = 'danger';
        }
    }
    $stmt->close();
}

if (isset($_POST['delete'])) {
    $id = (int)$_POST['id_pengguna'];
    if ($id == getPenggunaId()) {
        $msg = 'Tidak bisa hapus akun sendiri!';
        $msgType = 'warning';
    } else {
        $stmt = $conn->prepare("DELETE FROM pengguna WHERE id_pengguna=?");
        $stmt->bind_param("i", $id);
        $msg = $stmt->execute() ? 'Pengguna dihapus!' : 'Gagal!';
        $msgType = $stmt->execute() ? 'success' : 'danger';
        $stmt->close();
    }
}

if (isset($_POST['edit'])) {
    $id = (int)$_POST['id_pengguna'];
    $n = trim($_POST['nama_pengguna']);
    $e = trim($_POST['email']);
    $lv = $_POST['level'];
    
    $stmt = $conn->prepare("UPDATE pengguna SET nama_pengguna=?, email=?, level=? WHERE id_pengguna=?");
    $stmt->bind_param("sssi", $n, $e, $lv, $id);
    $msg = $stmt->execute() ? 'Data diperbarui!' : 'Gagal!';
    $msgType = $stmt->execute() ? 'success' : 'danger';
    $stmt->close();
}

if (isset($_POST['reset_pw'])) {
    $id = (int)$_POST['id_pengguna'];
    $pw = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE pengguna SET password=? WHERE id_pengguna=?");
    $stmt->bind_param("si", $pw, $id);
    $msg = $stmt->execute() ? 'Password berhasil direset!' : 'Gagal!';
    $msgType = $stmt->execute() ? 'success' : 'danger';
    $stmt->close();
}

$users = $conn->query("SELECT * FROM pengguna ORDER BY 
    CASE level 
        WHEN 'admin' THEN 1 
        WHEN 'petugas' THEN 2 
        ELSE 3 
    END, nama_pengguna");

$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $s = $conn->prepare("SELECT * FROM pengguna WHERE id_pengguna=?");
    $s->bind_param("i", $id);
    $s->execute();
    $editUser = $s->get_result()->fetch_assoc();
    $s->close();
}

$page_title = 'Manajemen Pengguna';
$page_sub = 'Kelola akun admin dan petugas';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengguna — Admin Perpustakaan</title>
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

        --success-50: #ecfdf5;
        --success-500: #10b981;
        --success-600: #059669;

        --warning-50: #fffbeb;
        --warning-500: #f59e0b;
        --warning-600: #d97706;

        --danger-50: #fef2f2;
        --danger-500: #ef4444;
        --danger-600: #dc2626;

        --info-50: #eff6ff;
        --info-500: #3b82f6;

        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);

        --radius-sm: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
        --radius-2xl: 1.5rem;
        --radius-3xl: 2rem;
        --radius-full: 9999px;

        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .app-wrap {
        display: flex;
        min-height: 100vh;
    }

    /* ===== SIDEBAR ===== */
    .sidebar {
        width: 280px;
        background: white;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        position: relative;
        z-index: 10;
    }

    .sidebar-brand {
        padding: 24px;
        border-bottom: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .brand-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
    }

    .brand-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--neutral-900);
        line-height: 1.3;
    }

    .brand-role {
        font-size: 0.7rem;
        color: var(--neutral-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .sidebar-nav {
        flex: 1;
        padding: 20px 16px;
        overflow-y: auto;
    }

    .nav-section-label {
        display: block;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--neutral-400);
        margin: 20px 0 8px 12px;
    }

    .nav-section-label:first-of-type {
        margin-top: 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: var(--radius-lg);
        color: var(--neutral-600);
        text-decoration: none;
        transition: var(--transition);
        margin-bottom: 2px;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .nav-link i {
        width: 20px;
        font-size: 1rem;
        color: var(--neutral-400);
        transition: var(--transition);
    }

    .nav-link:hover {
        background: var(--primary-50);
        color: var(--primary-600);
    }

    .nav-link:hover i {
        color: var(--primary-500);
    }

    .nav-link.active {
        background: var(--primary-50);
        color: var(--primary-700);
        font-weight: 600;
    }

    .nav-link.active i {
        color: var(--primary-600);
    }

    .nav-link.logout {
        margin-top: 20px;
        border-top: 1px solid var(--neutral-200);
        padding-top: 20px;
        color: var(--danger-500);
    }

    .nav-link.logout i {
        color: var(--danger-400);
    }

    .nav-link.logout:hover {
        background: var(--danger-50);
    }

    .sidebar-foot {
        padding: 20px 16px;
        border-top: 1px solid var(--neutral-200);
    }

    /* ===== MAIN AREA ===== */
    .main-area {
        flex: 1;
        background: var(--neutral-50);
        display: flex;
        flex-direction: column;
    }

    /* Header */
    .topbar {
        background: white;
        padding: 16px 24px;
        border-bottom: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow-sm);
    }

    .page-info {
        display: flex;
        flex-direction: column;
    }

    .page-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--neutral-900);
        margin-bottom: 4px;
    }

    .page-breadcrumb {
        font-size: 0.8rem;
        color: var(--neutral-500);
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .topbar-date {
        font-size: 0.9rem;
        color: var(--neutral-600);
        background: var(--neutral-100);
        padding: 6px 12px;
        border-radius: var(--radius-full);
    }

    .topbar-user {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 4px 12px 4px 4px;
        background: var(--neutral-100);
        border-radius: var(--radius-full);
    }

    .topbar-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        overflow: hidden;
    }

    .topbar-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .topbar-username {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--neutral-700);
    }

    .btn-logout {
        background: var(--danger-50);
        color: var(--danger-600);
        border: none;
        border-radius: var(--radius-full);
        padding: 8px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: var(--transition);
    }

    .btn-logout:hover {
        background: var(--danger-100);
    }

    /* Content */
    .content {
        padding: 24px;
        overflow-y: auto;
    }

    /* Alert */
    .alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-radius: var(--radius-lg);
        margin-bottom: 24px;
        animation: slideDown 0.3s ease;
        border-left: 4px solid;
    }

    .alert-success {
        background: #f0fdf4;
        border-left-color: var(--success-500);
        color: #166534;
    }

    .alert-danger {
        background: #fef2f2;
        border-left-color: var(--danger-500);
        color: #991b1b;
    }

    .alert-warning {
        background: #fffbeb;
        border-left-color: var(--warning-500);
        color: #92400e;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Page Header */
    .page-header {
        background: white;
        border-radius: var(--radius-xl);
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-md);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
        border: 1px solid var(--neutral-200);
    }

    .page-header-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--neutral-900);
        margin-bottom: 4px;
    }

    .page-header-sub {
        color: var(--neutral-500);
        font-size: 0.95rem;
    }

    /* Button Primary */
    .btn-primary {
        padding: 12px 24px;
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        box-shadow: 0 4px 6px -1px rgba(67, 97, 238, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.4);
    }

    .btn-primary i {
        font-size: 1rem;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 20px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }

    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }

    .stat-icon.blue {
        background: var(--primary-50);
        color: var(--primary-600);
    }

    .stat-icon.purple {
        background: #ede9fe;
        color: #6d28d9;
    }

    .stat-icon.green {
        background: var(--success-50);
        color: var(--success-600);
    }

    .stat-info h3 {
        font-size: 0.85rem;
        color: var(--neutral-500);
        font-weight: 600;
        margin-bottom: 4px;
    }

    .stat-number {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        color: var(--neutral-900);
    }

    /* Card */
    .card {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        border: 1px solid var(--neutral-200);
        overflow: hidden;
    }

    /* Table */
    .table-wrap {
        overflow-x: auto;
        padding: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead tr {
        background: linear-gradient(135deg, var(--neutral-50), var(--neutral-100));
    }

    th {
        padding: 16px 20px;
        text-align: left;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--neutral-600);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--neutral-200);
        color: var(--neutral-700);
    }

    tr:hover td {
        background: var(--neutral-50);
    }

    .text-muted {
        color: var(--neutral-500);
    }

    .text-sm {
        font-size: 0.85rem;
    }

    .fw-600 {
        font-weight: 600;
        color: var(--neutral-900);
    }

    /* Badge */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: var(--radius-full);
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-admin {
        background: var(--primary-50);
        color: var(--primary-700);
    }

    .badge-petugas {
        background: var(--info-50);
        color: var(--info-700);
    }

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 8px 12px;
        border-radius: var(--radius-md);
        font-size: 0.8rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: var(--transition);
    }

    .btn-edit {
        background: var(--primary-50);
        color: var(--primary-700);
    }

    .btn-edit:hover {
        background: var(--primary-100);
    }

    .btn-reset {
        background: var(--warning-50);
        color: var(--warning-700);
    }

    .btn-reset:hover {
        background: var(--warning-100);
    }

    .btn-delete {
        background: var(--danger-50);
        color: var(--danger-700);
    }

    .btn-delete:hover {
        background: var(--danger-100);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 48px 20px;
    }

    .empty-state-ico {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--neutral-500);
        margin-bottom: 8px;
    }

    .empty-state-sub {
        color: var(--neutral-400);
        font-size: 0.9rem;
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
    }

    .modal {
        background: white;
        border-radius: var(--radius-2xl);
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalSlide 0.3s ease;
        box-shadow: var(--shadow-2xl);
    }

    @keyframes modalSlide {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, var(--neutral-50), white);
    }

    .modal-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--neutral-900);
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: var(--neutral-500);
        width: 32px;
        height: 32px;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }

    .modal-close:hover {
        background: var(--neutral-200);
        color: var(--neutral-900);
        transform: rotate(90deg);
    }

    .modal-body {
        padding: 24px;
    }

    .modal-footer {
        padding: 20px 24px;
        border-top: 1px solid var(--neutral-200);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: var(--neutral-50);
    }

    /* Form */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .form-full {
        grid-column: span 2;
    }

    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-full {
            grid-column: span 1;
        }
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--neutral-700);
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
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

    select.form-control {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 16px;
        appearance: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-primary {
            width: 100%;
            justify-content: center;
        }

        .action-btns {
            flex-direction: column;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
</head>

<body>
    <div class="app-wrap">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">📚</div>
                <div>
                    <div class="brand-name">Perpustakaan Digital</div>
                    <div class="brand-role">ADMINISTRATOR</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-section-label">UTAMA</span>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <span class="nav-section-label">MANAJEMEN</span>
                <a href="pengguna.php" class="nav-link active">
                    <i class="fas fa-users-cog"></i>
                    <span>Pengguna</span>
                </a>
                <a href="anggota.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i>
                    <span>Anggota</span>
                </a>

                <span class="nav-section-label">KOLEKSI</span>
                <a href="kategori.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
                <a href="buku.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>Buku</span>
                </a>

                <span class="nav-section-label">TRANSAKSI</span>
                <a href="transaksi.php" class="nav-link">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transaksi</span>
                </a>
                <a href="denda.php" class="nav-link">
                    <i class="fas fa-coins"></i>
                    <span>Denda</span>
                </a>
                <a href="laporan.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>

                <span class="nav-section-label">AKUN</span>
                <a href="profil.php" class="nav-link">
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
                <div class="page-info">
                    <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
                    <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-date">
                        <i class="far fa-calendar-alt"></i> <?= date('d M Y') ?>
                    </div>
                    <div class="topbar-user">
                        <div class="topbar-avatar">
                            <?php if ($fotoPath): ?>
                            <img src="<?= $fotoPath ?>" alt="Foto">
                            <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                            <?php endif; ?>
                        </div>
                        <span class="topbar-username"><?= htmlspecialchars(getPenggunaName()) ?></span>
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
                <div class="alert alert-<?= $msgType ?>">
                    <i
                        class="fas <?= $msgType === 'success' ? 'fa-check-circle' : ($msgType === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle') ?>"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="page-header-title">Manajemen Pengguna</h1>
                        <p class="page-header-sub">Kelola akun admin dan petugas perpustakaan</p>
                    </div>
                    <button class="btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
                        <i class="fas fa-plus"></i>
                        Tambah Pengguna Baru
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3>Total Pengguna</h3>
                            <div class="stat-number"><?= $totalPengguna ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-crown"></i></div>
                        <div class="stat-info">
                            <h3>Administrator</h3>
                            <div class="stat-number"><?= $totalAdmin ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-user-tie"></i></div>
                        <div class="stat-info">
                            <h3>Petugas</h3>
                            <div class="stat-number"><?= $totalPetugas ?></div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Level</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($users && $users->num_rows > 0): $no=1; while($r=$users->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-muted text-sm"><?= $no++ ?></td>
                                    <td><span class="fw-600"><?= htmlspecialchars($r['nama_pengguna']) ?></span></td>
                                    <td>@<?= htmlspecialchars($r['username']) ?></td>
                                    <td><?= htmlspecialchars($r['email'] ?? '—') ?></td>
                                    <td>
                                        <span
                                            class="badge <?= $r['level'] === 'admin' ? 'badge-admin' : 'badge-petugas' ?>">
                                            <i class="fas <?= $r['level'] === 'admin' ? 'fa-crown' : 'fa-user' ?>"></i>
                                            <?= ucfirst($r['level']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?edit=<?= $r['id_pengguna'] ?>" class="btn-action btn-edit"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn-action btn-reset" title="Reset Password"
                                                onclick="showReset(<?= $r['id_pengguna'] ?>,'<?= htmlspecialchars(addslashes($r['nama_pengguna'])) ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if($r['id_pengguna'] != getPenggunaId()): ?>
                                            <form method="POST" onsubmit="return confirm('Hapus pengguna ini?')"
                                                style="display:inline">
                                                <input type="hidden" name="id_pengguna"
                                                    value="<?= $r['id_pengguna'] ?>">
                                                <button type="submit" name="delete" class="btn-action btn-delete"
                                                    title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-ico">👤</div>
                                            <div class="empty-state-title">Belum ada pengguna</div>
                                            <p class="empty-state-sub">Klik tombol "Tambah Pengguna" untuk menambahkan
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-user-plus"
                        style="color: var(--primary-500); margin-right: 8px;"></i>Tambah Pengguna Baru</h3>
                <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'"><i
                        class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Nama Lengkap <span
                                    style="color: var(--danger-500);">*</span></label>
                            <input type="text" name="nama_pengguna" class="form-control" required
                                placeholder="Masukkan nama lengkap">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username <span style="color: var(--danger-500);">*</span></label>
                            <input type="text" name="username" class="form-control" required
                                placeholder="Buat username">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password <span style="color: var(--danger-500);">*</span></label>
                            <input type="password" name="password" class="form-control" required
                                placeholder="Min. 6 karakter">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Level <span style="color: var(--danger-500);">*</span></label>
                            <select name="level" class="form-control">
                                <option value="petugas">👤 Petugas</option>
                                <option value="admin">👑 Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit"
                        onclick="document.getElementById('addModal').style.display='none'" style="padding: 10px 20px;">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" name="add" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editUser): ?>
    <!-- EDIT MODAL -->
    <div id="editModal" class="modal-overlay" onclick="if(event.target===this)window.location.href='pengguna.php'">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-user-edit"
                        style="color: var(--info-500); margin-right: 8px;"></i>Edit Pengguna</h3>
                <a href="pengguna.php" class="modal-close"><i class="fas fa-times"></i></a>
            </div>
            <form method="POST">
                <input type="hidden" name="id_pengguna" value="<?= $editUser['id_pengguna'] ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Nama Lengkap <span
                                    style="color: var(--danger-500);">*</span></label>
                            <input type="text" name="nama_pengguna" class="form-control"
                                value="<?= htmlspecialchars($editUser['nama_pengguna']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control"
                                value="<?= htmlspecialchars($editUser['username']) ?>" readonly
                                style="background: var(--neutral-100);">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Level <span style="color: var(--danger-500);">*</span></label>
                            <select name="level" class="form-control">
                                <option value="petugas" <?= $editUser['level'] === 'petugas' ? 'selected' : '' ?>>👤
                                    Petugas</option>
                                <option value="admin" <?= $editUser['level'] === 'admin' ? 'selected' : '' ?>>👑 Admin
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="pengguna.php" class="btn-edit" style="padding: 10px 20px;"><i class="fas fa-times"></i>
                        Batal</a>
                    <button type="submit" name="edit" class="btn-primary"><i class="fas fa-save"></i> Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('editModal').style.display = 'flex';
    </script>
    <?php endif; ?>

    <!-- RESET PASSWORD MODAL -->
    <div id="resetModal" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-key"
                        style="color: var(--warning-500); margin-right: 8px;"></i>Reset Password</h3>
                <button class="modal-close" onclick="document.getElementById('resetModal').style.display='none'"><i
                        class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_pengguna" id="resetId">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Password Baru <span style="color: var(--danger-500);">*</span></label>
                        <input type="password" name="new_password" class="form-control" required
                            placeholder="Minimal 6 karakter">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit"
                        onclick="document.getElementById('resetModal').style.display='none'"
                        style="padding: 10px 20px;">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" name="reset_pw" class="btn-primary"
                        style="background: linear-gradient(135deg, var(--warning-500), var(--warning-600));">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showReset(id, nama) {
        document.getElementById('resetId').value = id;
        document.getElementById('resetModal').style.display = 'flex';
    }

    // Tutup modal dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('addModal').style.display = 'none';
            document.getElementById('resetModal').style.display = 'none';
        }
    });

    // Prevent form resubmission
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>