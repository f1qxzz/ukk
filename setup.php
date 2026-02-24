<!DOCTYPE html>
<html lang="id"><head>
<meta charset="UTF-8"><title>Setup – Perpustakaan</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;display:flex;justify-content:center;align-items:center;min-height:100vh;}
.box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,.1);max-width:600px;width:100%;}
h1{color:#2563eb;margin-bottom:20px;} pre{background:#f9fafb;padding:15px;border-radius:6px;overflow-x:auto;font-size:.85rem;}
.btn{display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;text-decoration:none;font-size:1rem;}
.ok{color:#10b981;font-weight:600;} .err{color:#ef4444;font-weight:600;}
.warn{color:#f59e0b;font-weight:600;}
</style></head><body>
<div class="box">
<h1>📚 Setup Perpustakaan Digital</h1>
<?php
require_once 'config/database.php';

// Test koneksi
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
if($conn->connect_error){
    echo '<p class="err">❌ Koneksi database gagal: '.$conn->connect_error.'</p>';
    echo '<p>Pastikan MySQL berjalan dan kredensial di <code>config/database.php</code> sudah benar.</p>';
    exit;
}
echo '<p class="ok">✅ Koneksi database berhasil!</p>';

// Buat database jika belum ada
$conn->query("CREATE DATABASE IF NOT EXISTS `perpus_db` DEFAULT CHARACTER SET utf8mb4");
$conn->select_db('perpus_db');

// Cek tabel
$tables = ['pengguna','anggota','kategori','buku','transaksi','denda','ulasan_buku'];
$missing = [];
foreach($tables as $t){
    $r = $conn->query("SHOW TABLES LIKE '$t'");
    if($r->num_rows==0) $missing[]=$t;
}

if(empty($missing)){
    echo '<p class="ok">✅ Semua tabel sudah ada. Database siap digunakan!</p>';
    echo '<p><a href="index.php" class="btn">Ke Halaman Login →</a></p>';
} else {
    echo '<p class="warn">⚠️ Tabel belum lengkap. Klik tombol di bawah untuk inisialisasi database.</p>';
    echo '<p>Tabel yang belum ada: <code>'.implode(', ',$missing).'</code></p>';
    if(isset($_POST['install'])){
        $sql = file_get_contents('perpus_db.sql');
        // Remove CREATE DATABASE & USE statements (already selected)
        $sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
        $sql = preg_replace('/USE `.*?`;/is', '', $sql);
        // Execute each statement
        $conn->multi_query($sql);
        do { $conn->store_result(); } while($conn->more_results() && $conn->next_result());
        echo '<p class="ok">✅ Database berhasil diinisialisasi!</p>';
        echo '<p><strong>Akun default:</strong></p>';
        echo '<pre>Admin   → username: admin    | password: admin123
Petugas → username: petugas  | password: petugas123
Anggota → username: budi     | password: budi123</pre>';
        echo '<p><a href="index.php" class="btn">Ke Halaman Login →</a></p>';
    } else {
        echo '<form method="POST"><button type="submit" name="install" class="btn">⚙️ Inisialisasi Database</button></form>';
    }
}
$conn->close();
?>
</div></body></html>
