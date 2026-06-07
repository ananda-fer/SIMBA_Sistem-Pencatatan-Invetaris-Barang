<?php
require_once __DIR__ . '/_admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listuser.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash_user_error'] = 'ID user tidak valid.';
    header('Location: listuser.php');
    exit;
}

if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    $_SESSION['flash_user_error'] = 'Akun yang sedang dipakai tidak bisa dihapus.';
    header('Location: listuser.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['flash_user_success'] = 'User berhasil dihapus.';
} else {
    $_SESSION['flash_user_error'] = 'Gagal menghapus user.';
}

header('Location: listuser.php');
exit;
