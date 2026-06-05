<?php
// =============================================
// File: pages/auth/login.php
// Halaman login SIMBA
// =============================================

session_start();

// Kalau sudah login, langsung ke index peminjaman
if (isset($_SESSION['id'])) {
    header('Location: ../peminjaman/index.php');
    exit;
}

$error = '';

if (isset($_POST['login'])) {

    require_once '../../config/koneksi.php';

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username == '' || $password == '') {
        $error = 'Username dan password wajib diisi.';
    } else {

        // Cari user berdasarkan username
        $stmt = mysqli_prepare($conn,
            "SELECT id, nama_lengkap, kata_sandi, peran, aktif
             FROM users WHERE nama_pengguna = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if (!$user) {
            $error = 'Username tidak ditemukan.';
        } elseif ($user['aktif'] == 0) {
            $error = 'Akun Anda telah dinonaktifkan. Hubungi Admin.';
        } elseif (!password_verify($password, $user['kata_sandi'])) {
            $error = 'Password salah.';
        } else {
            // Login berhasil — simpan ke session
            $_SESSION['id']           = $user['id'];
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['nama']         = $user['nama_lengkap'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role']         = $user['peran'];
            $_SESSION['peran']        = $user['peran'];

            header('Location: ../peminjaman/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIMBA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <div class="login-logo-icon">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#1a1a1a" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h1 class="login-title">SIMBA</h1>
            <p class="login-subtitle">Sistem Inventaris &amp; Manajemen Barang Fakultas</p>
        </div>

        <!-- Error message -->
        <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 16px;">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Flash sukses setelah logout -->
        <?php if (isset($_SESSION['flash_sukses'])): ?>
        <div class="alert alert-success" style="margin-bottom: 16px;">
            <?= $_SESSION['flash_sukses'] ?>
        </div>
        <?php unset($_SESSION['flash_sukses']); ?>
        <?php endif; ?>

        <!-- Form Login -->
        <form method="POST" action="login.php">

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text"
                       name="username"
                       class="form-input"
                       placeholder="Masukkan username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password"
                       name="password"
                       class="form-input"
                       placeholder="Masukkan password">
            </div>

            <button type="submit" name="login" class="btn btn-primary btn-full" style="margin-top: 8px;">
                Masuk
            </button>

        </form>

        <!-- Info akun demo -->
        <div class="login-demo-info">
            <p><strong>Akun Demo:</strong></p>
            <table class="demo-table">
                <tr><td>Admin</td><td>:</td><td>admin</td><td>/</td><td>simba123</td></tr>
                <tr><td>Staff TU</td><td>:</td><td>staff_tu</td><td>/</td><td>simba123</td></tr>
                <tr><td>Peminjam</td><td>:</td><td>fahri</td><td>/</td><td>simba123</td></tr>
            </table>
        </div>

    </div>
</div>

</body>
</html>
