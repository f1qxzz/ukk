<?php
/**
 * Konfigurasi Database
 * Aplikasi Peminjaman Buku
 * 
 * Support untuk environment variables & .env.local file
 */

// Load .env.local jika ada (untuk development/staging)
if (file_exists(__DIR__ . '/../.env.local')) {
    $env = parse_ini_file(__DIR__ . '/../.env.local');
    foreach ($env as $k => $v) {
        $_ENV[$k] = $v;
    }
}

// Database Configuration (dengan fallback ke default)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'perpus_30');
define('DENDA_PER_HARI', (int)($_ENV['DENDA_PER_HARI'] ?? 1000)); // Rp 1.000 per hari

// Application Settings
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);

function getConnection() {
    // Enable error reporting in development mode
    if (APP_DEBUG) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            if (APP_DEBUG) {
                die("Koneksi database gagal: " . $conn->connect_error);
            } else {
                // Log error to file instead of showing to user
                error_log("Database connection failed: " . $conn->connect_error);
                die("Database connection error. Please contact administrator.");
            }
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        if (APP_DEBUG) {
            die("Error: " . $e->getMessage());
        } else {
            error_log("Database error: " . $e->getMessage());
            die("An error occurred. Please try again later.");
        }
    }
}

function closeConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
