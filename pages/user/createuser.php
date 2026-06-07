<?php
require_once __DIR__ . '/_admin_guard.php';

$allowedRoles = ['admin', 'staff_tu', 'peminjam'];
$errors = [];
$values = [
    'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
    'nama_pengguna' => trim($_POST['nama_pengguna'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'peran' => $_POST['peran'] ?? 'peminjam',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['kata_sandi'] ?? '';

    if ($values['nama_lengkap'] === '') $errors[] = 'Nama lengkap wajib diisi.';
    if ($values['nama_pengguna'] === '') $errors[] = 'Username wajib diisi.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!in_array($values['peran'], $allowedRoles, true)) $errors[] = 'Role tidak valid.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (!$errors) {
        $check = mysqli_prepare($conn, 'SELECT id FROM users WHERE nama_pengguna = ? OR email = ? LIMIT 1');
        mysqli_stmt_bind_param($check, 'ss', $values['nama_pengguna'], $values['email']);
        mysqli_stmt_execute($check);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
            $errors[] = 'Username atau email sudah digunakan.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, 'INSERT INTO users (nama_lengkap, nama_pengguna, email, kata_sandi, peran) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssss', $values['nama_lengkap'], $values['nama_pengguna'], $values['email'], $hash, $values['peran']);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['flash_user_success'] = 'User baru berhasil ditambahkan.';
            header('Location: listuser.php');
            exit;
        }

        $errors[] = 'Gagal menyimpan user. Silakan coba lagi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - SIMBA</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <style>
        .page-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px}.page-head h2{margin:0;font-size:24px}.page-head p{margin:5px 0 0;color:#6b7280}
        .form-card{background:#fff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 8px 18px rgba(15,23,42,.04);padding:22px;max-width:720px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{display:flex;flex-direction:column;gap:7px}.field.full{grid-column:1/-1}
        label{font-size:13px;font-weight:800;color:#374151}.input{height:40px;border:1px solid #d1d5db;border-radius:4px;padding:0 11px;font-size:14px;background:#fff}
        .actions{display:flex;gap:10px;margin-top:20px}.btn{height:38px;border-radius:4px;border:1px solid #111827;padding:0 14px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700;font-size:13px;cursor:pointer}
        .btn-primary{background:#111827;color:#fff}.btn-outline{background:#fff;color:#111827}.alert{margin-bottom:14px;border-radius:4px;padding:11px 13px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
        @media(max-width:720px){.grid{grid-template-columns:1fr}.actions{flex-direction:column}.btn{width:100%}}
    </style>
</head>
<body>
<div class="app-wrapper">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-head">
            <div><h2>Tambah User</h2><p>Buat akun baru untuk akses SIMBA.</p></div>
            <a class="btn btn-outline" href="listuser.php">Kembali</a>
        </div>

        <?php if ($errors): ?><div class="alert"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

        <form class="form-card" method="POST" action="">
            <div class="grid">
                <div class="field">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input class="input" id="nama_lengkap" name="nama_lengkap" value="<?= e($values['nama_lengkap']) ?>" required>
                </div>
                <div class="field">
                    <label for="nama_pengguna">Username</label>
                    <input class="input" id="nama_pengguna" name="nama_pengguna" value="<?= e($values['nama_pengguna']) ?>" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($values['email']) ?>" required>
                </div>
                <div class="field">
                    <label for="peran">Role</label>
                    <select class="input" id="peran" name="peran" required>
                        <?php foreach ($allowedRoles as $role): ?>
                            <option value="<?= e($role) ?>" <?= $values['peran'] === $role ? 'selected' : '' ?>><?= e(role_label($role)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field full">
                    <label for="kata_sandi">Password</label>
                    <input class="input" type="password" id="kata_sandi" name="kata_sandi" minlength="6" required>
                </div>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Simpan User</button>
                <a class="btn btn-outline" href="listuser.php">Batal</a>
            </div>
        </form>
    </main>
</div>
</body>
</html>
