<?php
/**
 * Konfigurasi Database
 * Aplikasi Peminjaman Buku
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'perpus_30');
define('DENDA_PER_HARI', 1000); // Rp 1.000 per hari

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function closeConnection($conn) {
    if ($conn) $conn->close();
}
