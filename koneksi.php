<?php
// =========================================================================
// FILE KONEKSI DATABASE - SIMBA
// =========================================================================
// Gunakan file ini di setiap halaman PHP yang butuh akses database dengan:
// require_once __DIR__ . '/../../koneksi.php'; (dari folder pages/xxx/)
// require_once __DIR__ . '/../koneksi.php';    (dari folder includes/)
// require_once 'koneksi.php';                  (dari root folder)
// =========================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'simba');
define('DB_USER', 'root');       // Ganti jika username Laragon Anda berbeda
define('DB_PASS', '');           // Ganti jika Anda punya password database
define('DB_CHARSET', 'utf8mb4');

$dsn     = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Lempar exception jika ada error SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Hasil fetch berupa array asosiatif
    PDO::ATTR_EMULATE_PREPARES   => false,                     // Gunakan prepared statement asli
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // Di production, jangan tampilkan pesan error ke user
    // Ganti dengan halaman error yang ramah
    die("<div style='font-family:sans-serif;padding:30px;color:red;'>
            <h3>❌ Koneksi Database Gagal</h3>
            <p><strong>Pesan:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Pastikan Laragon sudah berjalan dan nama database <strong>" . DB_NAME . "</strong> sudah dibuat.</p>
         </div>");
}
