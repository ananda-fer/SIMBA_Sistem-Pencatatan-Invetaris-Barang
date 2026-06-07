<?php
// config/koneksi.php
// File koneksi database — jangan akses langsung

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'simba');
define('DB_CHARSET', 'utf8mb4');

// Koneksi mysqli ($conn) — digunakan oleh pages/user dan pages/peminjaman
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border-radius:8px;margin:20px;">
        <strong>Koneksi database gagal:</strong> ' . htmlspecialchars($conn->connect_error) . '
    </div>');
}

$conn->set_charset(DB_CHARSET);

// Koneksi PDO ($pdo) — digunakan oleh pages/dashboard dan pages/laporan
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    die("<div style='font-family:sans-serif;padding:30px;color:red;'>
            <h3>Koneksi Database Gagal</h3>
            <p><strong>Pesan:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Pastikan Laragon sudah berjalan dan nama database <strong>" . DB_NAME . "</strong> sudah dibuat.</p>
         </div>");
}
