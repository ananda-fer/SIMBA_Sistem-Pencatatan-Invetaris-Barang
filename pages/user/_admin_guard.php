<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';

if (($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ' . app_url('pages/peminjaman/index.php'));
    exit;
}

function role_label(string $role): string {
    return ucfirst(str_replace('_', ' ', $role));
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bind_params(mysqli_stmt $stmt, string $types, array &$params): void {
    $refs = [];
    foreach ($params as $key => &$value) {
        $refs[$key] = &$value;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$refs);
}
