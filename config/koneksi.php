<?php
// config/koneksi.php
// File koneksi database — jangan akses langsung

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'simba');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border-radius:8px;margin:20px;">
        <strong>Koneksi database gagal:</strong> ' . htmlspecialchars($conn->connect_error) . '
    </div>');
}

$conn->set_charset('utf8mb4');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border-radius:8px;margin:20px;">
        <strong>Koneksi database gagal:</strong> ' . htmlspecialchars($e->getMessage()) . '
    </div>');
}
