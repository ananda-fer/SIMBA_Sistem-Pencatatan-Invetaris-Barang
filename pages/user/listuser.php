<?php
require_once __DIR__ . '/_admin_guard.php';

$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';
$allowedRoles = ['admin', 'staff_tu', 'peminjam'];
$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = '(nama_lengkap LIKE ? OR nama_pengguna LIKE ? OR email LIKE ?)';
    $keyword = '%' . $q . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= 'sss';
}

if (in_array($role, $allowedRoles, true)) {
    $where[] = 'peran = ?';
    $params[] = $role;
    $types .= 's';
}

$sql = 'SELECT id, nama_lengkap, nama_pengguna, email, peran FROM users';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    bind_params($stmt, $types, $params);
}
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);

$success = $_SESSION['flash_user_success'] ?? '';
$error = $_SESSION['flash_user_error'] ?? '';
unset($_SESSION['flash_user_success'], $_SESSION['flash_user_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - SIMBA</title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
    <style>
        .user-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px;flex-wrap:wrap}
        .user-toolbar h2{margin:0;font-size:24px;color:#111827}.user-toolbar p{margin:5px 0 0;color:#6b7280}
        .user-card{background:#fff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 8px 18px rgba(15,23,42,.04);overflow:hidden}
        .filter-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:16px;border-bottom:1px solid #e5e7eb}
        .form-control{height:38px;border:1px solid #d1d5db;border-radius:4px;padding:0 11px;font-size:14px;background:#fff;color:#111827}
        .form-control.search{min-width:260px;flex:1}.btn{height:38px;border-radius:4px;border:1px solid #111827;padding:0 14px;display:inline-flex;align-items:center;gap:8px;text-decoration:none;font-weight:700;font-size:13px;cursor:pointer}
        .btn-primary{background:#111827;color:#fff}.btn-outline{background:#fff;color:#111827}.btn-danger{background:#dc2626;border-color:#dc2626;color:#fff}
        .table-wrap{overflow:auto}.user-table{width:100%;border-collapse:collapse;min-width:760px}.user-table th,.user-table td{padding:13px 16px;border-bottom:1px solid #edf0f4;text-align:left;font-size:14px}
        .user-table th{font-size:12px;text-transform:uppercase;color:#6b7280;background:#f9fafb;letter-spacing:.04em}.user-name{font-weight:800;color:#111827}.user-meta{font-size:12px;color:#6b7280;margin-top:3px}
        .badge{display:inline-flex;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:800;background:#eef2ff;color:#3730a3}.actions{display:flex;gap:8px;align-items:center}
        .alert{margin-bottom:14px;border-radius:4px;padding:11px 13px;font-size:14px}.alert-success{background:#ecfdf5;color:#065f46;border:1px solid #bbf7d0}.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
        .empty{padding:28px;text-align:center;color:#6b7280}.inline-form{margin:0}
        @media(max-width:720px){.form-control.search{min-width:100%}.btn{width:100%;justify-content:center}.actions{flex-wrap:wrap}}
    </style>
</head>
<body>
<div class="app-wrapper">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="user-toolbar">
            <div>
                <h2>Manajemen User</h2>
                <p>Kelola akun admin, staff TU, dan peminjam.</p>
            </div>
            <a class="btn btn-primary" href="createuser.php">Tambah User</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <section class="user-card">
            <form class="filter-bar" method="GET" action="">
                <input class="form-control search" type="search" name="q" placeholder="Cari nama, username, atau email" value="<?= e($q) ?>">
                <select class="form-control" name="role">
                    <option value="">Semua Role</option>
                    <?php foreach ($allowedRoles as $item): ?>
                        <option value="<?= e($item) ?>" <?= $role === $item ? 'selected' : '' ?>><?= e(role_label($item)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline" type="submit">Filter</button>
                <a class="btn btn-outline" href="listuser.php">Reset</a>
            </form>

            <div class="table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="width:190px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($users) === 0): ?>
                            <tr><td colspan="4"><div class="empty">Belum ada user yang cocok dengan filter.</div></td></tr>
                        <?php endif; ?>
                        <?php while ($user = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td>
                                    <div class="user-name"><?= e($user['nama_lengkap']) ?></div>
                                    <div class="user-meta">@<?= e($user['nama_pengguna']) ?> · ID <?= (int)$user['id'] ?></div>
                                </td>
                                <td><?= e($user['email']) ?></td>
                                <td><span class="badge"><?= e(role_label($user['peran'])) ?></span></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-outline" href="edituser.php?id=<?= (int)$user['id'] ?>">Edit</a>
                                        <form class="inline-form" method="POST" action="deleteuser.php" onsubmit="return confirm('Hapus user ini?');">
                                            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                            <button class="btn btn-danger" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
