<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/upload_helper.php';
requirePetugas();
$conn = getConnection();
$msg = ''; $msgType = '';

// ============================================================
//  TAMBAH BUKU
// ============================================================
if (isset($_POST['add'])) {
    $judul  = trim($_POST['judul_buku']);
    $id_kat = (int)$_POST['id_kategori'];
    $peng   = trim($_POST['pengarang']);
    $nerbit = trim($_POST['penerbit']);
    $tahun  = (int)$_POST['tahun_terbit'];
    $isbn   = trim($_POST['isbn']);
    $desk   = trim($_POST['deskripsi']);
    $stok   = (int)$_POST['stok'];
    // FIX 1: Nilai enum DB adalah 'tidak', bukan 'tidak tersedia'
    $status = $stok > 0 ? 'tersedia' : 'tidak';

    // FIX 2: Deteksi file baru yang benar — termasuk error batas ukuran server
    $adaFileAdd = isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE;
    $coverPath = null; $uploadGagal = false;

    if ($adaFileAdd) {
        $coverResult = processBookCover($_FILES['cover']);
        if (!$coverResult['ok']) { $msg = 'Upload cover gagal: ' . $coverResult['error']; $msgType = 'warning'; $uploadGagal = true; }
        else { $coverPath = $coverResult['path']; }
    }

    if (!$uploadGagal) {
        $s = $conn->prepare("INSERT INTO buku (judul_buku,id_kategori,pengarang,penerbit,tahun_terbit,isbn,deskripsi,stok,status,cover) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sissississ", $judul, $id_kat, $peng, $nerbit, $tahun, $isbn, $desk, $stok, $status, $coverPath);
        if ($s->execute()) { $s->close(); header('Location: buku.php?notif=tambah_ok'); exit; }
        else { if ($coverPath) deleteBookCover($coverPath); $msg = 'Gagal menyimpan buku: ' . $conn->error; $msgType = 'danger'; }
        $s->close();
    }
}

// ============================================================
//  EDIT BUKU
// ============================================================
if (isset($_POST['edit'])) {
    $id     = (int)$_POST['id_buku'];
    $judul  = trim($_POST['judul_buku']);
    $id_kat = (int)$_POST['id_kategori'];
    $peng   = trim($_POST['pengarang']);
    $nerbit = trim($_POST['penerbit']);
    $tahun  = (int)$_POST['tahun_terbit'];
    $isbn   = trim($_POST['isbn']);
    $desk   = trim($_POST['deskripsi']);
    $stok   = (int)$_POST['stok'];
    $status = $_POST['status'];

    // Ambil cover lama
    $stmtOld = $conn->prepare("SELECT cover FROM buku WHERE id_buku=?");
    $stmtOld->bind_param("i", $id); $stmtOld->execute(); $stmtOld->bind_result($oldCover); $stmtOld->fetch(); $stmtOld->close();

    // FIX 3: Deteksi file baru yang benar — termasuk error batas ukuran server
    $adaFileBaru = isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE;
    $newCover = $oldCover; $uploadGagal = false;

    if ($adaFileBaru) {
        $coverResult = processBookCover($_FILES['cover']);
        if (!$coverResult['ok']) {
            $msg = 'Upload cover gagal: ' . $coverResult['error']; $msgType = 'warning'; $uploadGagal = true;
        } else {
            // FIX 4: Simpan path baru dulu; cover lama BELUM dihapus di sini.
            // Penghapusan dilakukan SETELAH database berhasil di-update.
            $newCover = $coverResult['path'];
        }
    }

    if (!$uploadGagal) {
        $s = $conn->prepare(
            "UPDATE buku SET judul_buku=?,id_kategori=?,pengarang=?,penerbit=?,
             tahun_terbit=?,isbn=?,deskripsi=?,stok=?,status=?,cover=? WHERE id_buku=?"
        );
        // FIX 5: Tipe stok adalah integer (i), bukan string (s) → "sissississi"
        // Tipe: s       i       s     s       i      s     s     i     s       s         i
        //    judul  id_kat  peng  nerbit  tahun  isbn  desk  stok  status newCover  id
        $s->bind_param("sissississi", $judul, $id_kat, $peng, $nerbit, $tahun, $isbn, $desk, $stok, $status, $newCover, $id);

        // FIX 6: Gunakan try...catch agar error MySQL tidak tersembunyi
        try {
            $s->execute();
            $s->close();

            // UPDATE berhasil — baru sekarang hapus cover lama jika benar-benar diganti
            if ($adaFileBaru && $newCover !== $oldCover) {
                // FIX 7 & 9: Jangan hapus cover "default"
                if (!empty($oldCover) && strpos($oldCover, 'default') === false) {
                    deleteBookCover($oldCover);
                }
            }

            header('Location: buku.php?notif=edit_ok');
            exit;

        } catch (Exception $e) {
            // UPDATE gagal — tampilkan error MySQL & rollback file baru yang terlanjur ter-upload
            if ($adaFileBaru && $newCover !== $oldCover) {
                deleteBookCover($newCover); // rollback
            }
            $msg = 'Gagal memperbarui buku: ' . $e->getMessage();
            $msgType = 'danger';
            $s->close();
        }
    }
}

// ============================================================
//  HAPUS BUKU
// ============================================================
if (isset($_POST['delete'])) {
    $id = (int)$_POST['id_buku'];
    $cekAktif = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE id_buku=? AND status_transaksi='Peminjaman'");
    $cekAktif->bind_param("i", $id); $cekAktif->execute(); $cekAktif->bind_result($jumlahAktif); $cekAktif->fetch(); $cekAktif->close();
    if ($jumlahAktif > 0) { $msg = 'Buku sedang dipinjam, tidak bisa dihapus!'; $msgType = 'warning'; }
    else {
        $stmtCov = $conn->prepare("SELECT cover FROM buku WHERE id_buku=?");
        $stmtCov->bind_param("i", $id); $stmtCov->execute(); $stmtCov->bind_result($coverToDel); $stmtCov->fetch(); $stmtCov->close();
        $s = $conn->prepare("DELETE FROM buku WHERE id_buku=?");
        $s->bind_param("i", $id);
        if ($s->execute()) {
            $s->close();
            // FIX 9: Jangan hapus cover "default"
            if (!empty($coverToDel) && strpos($coverToDel, 'default') === false) {
                deleteBookCover($coverToDel);
            }
            header('Location: buku.php?notif=hapus_ok'); exit;
        } else { $msg = 'Gagal menghapus buku!'; $msgType = 'danger'; }
        $s->close();
    }
}

if (empty($msg) && isset($_GET['notif'])) {
    $notifMap = ['tambah_ok'=>['Buku berhasil ditambahkan!','success'],'edit_ok'=>['Buku berhasil diperbarui!','success'],'hapus_ok'=>['Buku berhasil dihapus!','success']];
    if (isset($notifMap[$_GET['notif']])) [$msg, $msgType] = $notifMap[$_GET['notif']];
}

$cats = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search = $conn->real_escape_string($search);
$filter_kat = isset($_GET['kat']) ? (int)$_GET['kat'] : 0;
$q = "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.id_kategori=k.id_kategori WHERE 1=1";
if ($search) $q .= " AND (b.judul_buku LIKE '%$search%' OR b.pengarang LIKE '%$search%')";
if ($filter_kat) $q .= " AND b.id_kategori=$filter_kat";
$q .= " ORDER BY b.id_buku DESC";
$books = $conn->query($q);

$editBook = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $s = $conn->prepare("SELECT * FROM buku WHERE id_buku=?");
    $s->bind_param("i", $eid); $s->execute();
    $editBook = $s->get_result()->fetch_assoc();
    $s->close();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Buku — Petugas Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/petugas.css">
    <link rel="stylesheet" href="../assets/css/table.css">
    <link rel="stylesheet" href="../assets/css/form.css">
    <style>
    /* =========================================================
   DESAIN AREA UPLOAD/EDIT COVER BUKU
   ========================================================= */

    /* Area utama upload (Kotak putus-putus) */
    .cover-upload-area {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        border: 2px dashed #cbd5e1;
        /* Warna garis putus-putus abu-abu */
        border-radius: 12px;
        background-color: #f8fafc;
        transition: all 0.3s ease-in-out;
    }

    /* Efek saat area di-hover */
    .cover-upload-area:hover {
        border-color: #3b82f6;
        /* Berubah biru saat di-hover */
        background-color: #eff6ff;
    }

    /* Pembungkus gambar preview */
    .cover-preview-wrap {
        position: relative;
        width: 110px;
        /* Proporsi rasio cover buku */
        height: 150px;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        flex-shrink: 0;
        background-color: #e2e8f0;
    }

    /* Gambar preview di dalamnya */
    .cover-preview-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    /* Efek zoom kecil saat gambar disentuh */
    .cover-preview-wrap:hover img {
        transform: scale(1.05);
    }

    /* Lapisan transparan (Overlay) 'Ganti Cover' di atas gambar */
    .overlay-hint {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.65);
        color: #ffffff;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        padding: 8px 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        opacity: 0;
        /* Disembunyikan secara default */
        transition: opacity 0.3s ease;
    }

    /* Munculkan overlay saat gambar di-hover */
    .cover-preview-wrap:hover .overlay-hint {
        opacity: 1;
    }

    /* Bagian teks dan tombol di sebelah gambar */
    .cover-upload-meta {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 8px;
    }

    /* Desain Tombol Pilih File */
    .cover-upload-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        width: fit-content;
    }

    .cover-upload-btn:hover {
        background-color: #f1f5f9;
        border-color: #94a3b8;
    }

    /* Nama file yang terpilih */
    .cover-filename {
        font-size: 13px;
        font-weight: 600;
        color: #10b981;
        /* Warna hijau sukses */
        display: none;
        /* Disembunyikan sampai ada file dipilih */
        word-break: break-all;
        max-width: 200px;
    }

    /* Teks panduan (Maks 2MB, format JPG, dll) */
    .cover-upload-hint {
        font-size: 12px;
        color: #64748b;
        line-height: 1.5;
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
                <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <div class="page-header">
                    <div>
                        <div class="page-header-title">Koleksi Buku</div>
                        <div class="page-header-sub">Tambah, edit, atau hapus data buku perpustakaan</div>
                    </div>
                    <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
                        <svg viewBox="0 0 24 24">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg> Tambah Buku
                    </button>
                </div>

                <div class="card">
                    <form method="GET" class="filter-bar">
                        <div class="search-wrap">
                            <input type="text" name="search" placeholder="Cari judul atau pengarang…"
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <select name="kat" class="form-control" style="width:auto">
                            <option value="">Semua Kategori</option>
                            <?php $cats->data_seek(0); while($c=$cats->fetch_assoc()): ?>
                            <option value="<?= $c['id_kategori'] ?>"
                                <?= $filter_kat==$c['id_kategori']?'selected':'' ?>>
                                <?= htmlspecialchars($c['nama_kategori']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
                        <?php if ($search||$filter_kat): ?><a href="buku.php"
                            class="btn btn-ghost btn-sm">Reset</a><?php endif; ?>
                    </form>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cover</th>
                                    <th>Judul Buku</th>
                                    <th>Pengarang</th>
                                    <th>Kategori</th>
                                    <th>Tahun</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($books && $books->num_rows > 0): $no=1; ?>
                                <?php while($b=$books->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-muted text-sm"><?= $no++ ?></td>
                                    <td class="book-cover-cell">
                                        <?php if (!empty($b['cover']) && file_exists('../' . $b['cover'])): ?>
                                        <img src="../<?= htmlspecialchars($b['cover']) ?>" class="cover-thumb"
                                            alt="cover">
                                        <?php else: ?>
                                        <img src="../<?= DEFAULT_COVER ?>" class="cover-thumb" alt="default">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="book-info-main"><?= htmlspecialchars($b['judul_buku']) ?></div>
                                        <div class="book-info-sub"><?= htmlspecialchars($b['isbn'] ?: '—') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($b['pengarang']) ?></td>
                                    <td><span
                                            class="badge badge-muted"><?= htmlspecialchars($b['nama_kategori'] ?: '—') ?></span>
                                    </td>
                                    <td><?= $b['tahun_terbit'] ?></td>
                                    <td><?= $b['stok'] ?></td>
                                    <td>
                                        <span
                                            class="badge <?= $b['status']==='tersedia'?'status-tersedia':'status-terlambat' ?>">
                                            <?= $b['status']==='tersedia' ? '● Tersedia' : '○ Habis' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px">
                                            <a href="?edit=<?= $b['id_buku'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                                            <form method="POST" onsubmit="return confirm('Hapus buku ini?')"
                                                style="display:inline">
                                                <input type="hidden" name="id_buku" value="<?= $b['id_buku'] ?>">
                                                <button name="delete" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <div class="empty-state-ico">📚</div>
                                            <div class="empty-state-title">Belum ada buku</div>
                                            <div class="empty-state-sub">Tambahkan buku pertama ke koleksi perpustakaan.
                                            </div>
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

    <!-- MODAL TAMBAH -->
    <div id="addModal" class="modal-overlay" style="display:none"
        onclick="if(event.target===this)this.style.display='none'">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div class="modal-title">Tambah Buku Baru</div>
                <button class="modal-close"
                    onclick="document.getElementById('addModal').style.display='none'">✕</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Judul Buku *</label>
                            <input name="judul_buku" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pengarang *</label>
                            <input name="pengarang" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Penerbit</label>
                            <input name="penerbit" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <select name="id_kategori" class="form-control">
                                <?php $cats->data_seek(0); while($c=$cats->fetch_assoc()): ?>
                                <option value="<?= $c['id_kategori'] ?>"><?= htmlspecialchars($c['nama_kategori']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tahun Terbit</label>
                            <input name="tahun_terbit" type="number" class="form-control" value="<?= date('Y') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ISBN</label>
                            <input name="isbn" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stok *</label>
                            <input name="stok" type="number" class="form-control" min="0" value="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cover Buku</label>
                            <div class="cover-upload-area">
                                <div class="cover-preview-wrap"
                                    onclick="document.getElementById('addCoverInput').click()">
                                    <img id="addCoverPreview" src="../<?= DEFAULT_COVER ?>" alt="Preview">
                                    <div class="overlay-hint">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="17 8 12 3 7 8" />
                                            <line x1="12" y1="3" x2="12" y2="15" />
                                        </svg>
                                        Pilih Cover
                                    </div>
                                </div>
                                <div class="cover-upload-meta">
                                    <button type="button" class="cover-upload-btn"
                                        onclick="document.getElementById('addCoverInput').click()">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.5">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="17 8 12 3 7 8" />
                                            <line x1="12" y1="3" x2="12" y2="15" />
                                        </svg>
                                        Pilih File
                                    </button>
                                    <div class="cover-filename" id="addCoverFilename"></div>
                                    <div class="cover-upload-hint">Format: JPG atau PNG<br>Maks. 2 MB · Opsional<br>Klik
                                        gambar untuk memilih</div>
                                </div>
                            </div>
                            <input type="file" id="addCoverInput" name="cover" accept=".jpg,.jpeg,.png"
                                style="display:none" onchange="previewCover(this,'addCoverPreview','addCoverFilename')">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('addModal').style.display='none'">Batal</button>
                    <button name="add" class="btn btn-primary">Simpan Buku</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editBook): ?>
    <!-- MODAL EDIT -->
    <div id="editModal" class="modal-overlay" onclick="if(event.target===this)location.href='buku.php'">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div class="modal-title">Edit Buku</div>
                <a href="buku.php" class="modal-close">✕</a>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_buku" value="<?= $editBook['id_buku'] ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Judul Buku *</label>
                            <input name="judul_buku" class="form-control"
                                value="<?= htmlspecialchars($editBook['judul_buku']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pengarang *</label>
                            <input name="pengarang" class="form-control"
                                value="<?= htmlspecialchars($editBook['pengarang']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Penerbit</label>
                            <input name="penerbit" class="form-control"
                                value="<?= htmlspecialchars($editBook['penerbit']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <select name="id_kategori" class="form-control">
                                <?php $cats->data_seek(0); while($c=$cats->fetch_assoc()): ?>
                                <option value="<?= $c['id_kategori'] ?>"
                                    <?= $c['id_kategori']==$editBook['id_kategori']?'selected':'' ?>>
                                    <?= htmlspecialchars($c['nama_kategori']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tahun Terbit</label>
                            <input name="tahun_terbit" type="number" class="form-control"
                                value="<?= $editBook['tahun_terbit'] ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ISBN</label>
                            <input name="isbn" class="form-control" value="<?= htmlspecialchars($editBook['isbn']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stok *</label>
                            <input name="stok" type="number" class="form-control" min="0"
                                value="<?= $editBook['stok'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <!-- FIX 8: value harus cocok dengan enum DB: 'tersedia' dan 'tidak' -->
                                <option value="tersedia" <?= $editBook['status']==='tersedia'?'selected':'' ?>>Tersedia
                                </option>
                                <option value="tidak" <?= $editBook['status']==='tidak'?'selected':'' ?>>Tidak Tersedia
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cover Buku</label>
                            <?php
                            $editCoverSrc = (!empty($editBook['cover']) && file_exists('../' . $editBook['cover']))
                                            ? '../' . htmlspecialchars($editBook['cover'])
                                            : '../' . DEFAULT_COVER;
                            ?>
                            <div class="cover-upload-area">
                                <div class="cover-preview-wrap"
                                    onclick="document.getElementById('editCoverInput').click()">
                                    <img id="editCoverPreview" src="<?= $editCoverSrc ?>" alt="Cover Saat Ini">
                                    <div class="overlay-hint">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="17 8 12 3 7 8" />
                                            <line x1="12" y1="3" x2="12" y2="15" />
                                        </svg>
                                        Ganti Cover
                                    </div>
                                </div>
                                <div class="cover-upload-meta">
                                    <button type="button" class="cover-upload-btn"
                                        onclick="document.getElementById('editCoverInput').click()">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.5">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="17 8 12 3 7 8" />
                                            <line x1="12" y1="3" x2="12" y2="15" />
                                        </svg>
                                        Ganti Cover
                                    </button>
                                    <div class="cover-filename" id="editCoverFilename"></div>
                                    <div class="cover-upload-hint">Format: JPG atau PNG<br>Maks. 2 MB<br>Kosongkan jika
                                        tidak ingin mengubah</div>
                                </div>
                            </div>
                            <input type="file" id="editCoverInput" name="cover" accept=".jpg,.jpeg,.png"
                                style="display:none"
                                onchange="previewCover(this,'editCoverPreview','editCoverFilename')">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi"
                                class="form-control"><?= htmlspecialchars($editBook['deskripsi']) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="buku.php" class="btn btn-ghost">Batal</a>
                    <button name="edit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('editModal').style.display = 'flex';
    </script>
    <?php endif; ?>

    <script>
    function previewCover(input, previewId, filenameId) {
        const preview = document.getElementById(previewId);
        const fnLabel = document.getElementById(filenameId);
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            alert('Hanya file JPG atau PNG yang diizinkan.');
            input.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran file melebihi 2 MB.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
        if (fnLabel) {
            fnLabel.textContent = file.name;
            fnLabel.style.display = 'block';
        }
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>