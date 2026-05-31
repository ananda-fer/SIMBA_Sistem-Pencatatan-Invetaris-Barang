<?php
// Mencegah akses langsung ke file ini jika dipanggil secara independen via URL
if (basename($_SERVER['SCRIPT_FILENAME']) === 'config.php') {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak.");
}

// Konfigurasi Kredensial Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Username default Laragon/XAMPP
define('DB_PASS', '');          // Password default Laragon/XAMPP (kosong)
define('DB_NAME', 'cobapbw');   // Nama database sesuai pbw.sql

// Membuat koneksi ke database MySQL menggunakan MySQLi
$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Validasi apakah koneksi berhasil atau gagal
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set timezone ke Waktu Indonesia Barat (WIB) agar akurat menghitung hari terlambat
date_default_timezone_set('Asia/Jakarta');
?>
