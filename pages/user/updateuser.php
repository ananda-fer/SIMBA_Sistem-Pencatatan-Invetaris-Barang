<?php
require_once __DIR__ . '/_admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listuser.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$nama_pengguna = trim($_POST['nama_pengguna'] ?? '');
$email = trim($_POST['email'] ?? '');
$kata_sandi = $_POST['kata_sandi'] ?? '';
$peran = $_POST['peran'] ?? '';
$allowedRoles = ['admin', 'staff_tu', 'peminjam'];
$errors = [];

if (!$id) $errors[] = 'ID user tidak valid.';
if ($nama_lengkap === '') $errors[] = 'Nama lengkap wajib diisi.';
if ($nama_pengguna === '') $errors[] = 'Username wajib diisi.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
if (!in_array($peran, $allowedRoles, true)) $errors[] = 'Role tidak valid.';
if ($kata_sandi !== '' && strlen($kata_sandi) < 6) $errors[] = 'Password minimal 6 karakter.';

if (!$errors) {
    $check = mysqli_prepare($conn, 'SELECT id FROM users WHERE (nama_pengguna = ? OR email = ?) AND id <> ? LIMIT 1');
    mysqli_stmt_bind_param($check, 'ssi', $nama_pengguna, $email, $id);
    mysqli_stmt_execute($check);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
        $errors[] = 'Username atau email sudah digunakan user lain.';
    }
}

if ($errors) {
    $_SESSION['flash_user_error'] = implode(' ', $errors);
    header('Location: edituser.php?id=' . (int)$id);
    exit;
}

if ($kata_sandi !== '') {
    $hash = password_hash($kata_sandi, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, 'UPDATE users SET nama_pengguna = ?, nama_lengkap = ?, email = ?, kata_sandi = ?, peran = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'sssssi', $nama_pengguna, $nama_lengkap, $email, $hash, $peran, $id);
} else {
    $stmt = mysqli_prepare($conn, 'UPDATE users SET nama_pengguna = ?, nama_lengkap = ?, email = ?, peran = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ssssi', $nama_pengguna, $nama_lengkap, $email, $peran, $id);
}

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['flash_user_success'] = 'Data user berhasil diperbarui.';
} else {
    $_SESSION['flash_user_error'] = 'Gagal memperbarui user.';
}

header('Location: listuser.php');
exit;
