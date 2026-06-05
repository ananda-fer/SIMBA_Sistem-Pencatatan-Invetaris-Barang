<?php
// config/koneksi.php
// File koneksi database — jangan akses langsung

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'simba');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border-radius:8px;margin:20px;">
        <strong>Koneksi database gagal:</strong> ' . htmlspecialchars($conn->connect_error) . '
    </div>');
}

$conn->set_charset('utf8mb4');
