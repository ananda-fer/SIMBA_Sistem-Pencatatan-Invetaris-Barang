<?php
// includes/auth_check.php
// Include di awal setiap halaman yang butuh login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('app_base_path')) {
    function app_base_path(): string {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $pos = strpos($script, '/pages/');

        if ($pos !== false) {
            return rtrim(substr($script, 0, $pos), '/');
        }

        return rtrim(dirname($script), '/\\');
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        $base = app_base_path();
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_url')) {
    // Sama seperti app_url tapi menambahkan ?v=<filemtime> untuk cache-busting,
    // sehingga perubahan CSS/JS langsung dimuat browser tanpa hard refresh.
    function asset_url(string $path): string {
        $full = dirname(__DIR__) . '/' . ltrim($path, '/');
        $ver  = is_file($full) ? filemtime($full) : time();
        return app_url($path) . '?v=' . $ver;
    }
}

if (isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['id'];
}
if (isset($_SESSION['role']) && !isset($_SESSION['peran'])) {
    $_SESSION['peran'] = $_SESSION['role'];
}
if (isset($_SESSION['nama']) && !isset($_SESSION['nama_lengkap'])) {
    $_SESSION['nama_lengkap'] = $_SESSION['nama'];
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}

// Fungsi cek role
function hanya_role(array $roles) {
    if (!in_array($_SESSION['peran'], $roles)) {
        header('Location: ' . app_url('pages/auth/login.php'));
        exit;
    }
}
