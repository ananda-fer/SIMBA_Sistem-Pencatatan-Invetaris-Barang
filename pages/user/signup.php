<?php
require_once __DIR__ . '/../../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirectByRole($role) {
    if (in_array($role, ['admin', 'staff_tu'], true)) {
        header('Location: ../peminjaman/index.php');
    } else {
        header('Location: ../peminjaman/index.php');
    }
    exit();
}

if (isset($_SESSION['user_id']) && isset($_SESSION['peran'])) {
    redirectByRole($_SESSION['peran']);
}

$error = '';
$nameValue = '';
$emailValue = '';

if (isset($_POST['signup'])) {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $kata_sandi = $_POST['kata_sandi'] ?? '';
    $konfirmasi_sandi = $_POST['konfirmasi_sandi'] ?? '';
    $nameValue = $nama_lengkap;
    $emailValue = $email;

    if ($nama_lengkap === '' || $email === '' || $kata_sandi === '' || $konfirmasi_sandi === '') {
        $error = 'Semua field wajib diisi.';
    } elseif ($kata_sandi !== $konfirmasi_sandi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $checkStmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($checkStmt, 's', $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($checkResult)) {
            $error = 'Email sudah terdaftar.';
        } else {
        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', strstr($email, '@', true) ?: $email);
        $baseUsername = $baseUsername !== '' ? $baseUsername : 'user';
        $nama_pengguna = $baseUsername;
        $counter = 1;

        while (true) {
            $usernameStmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE nama_pengguna = ? LIMIT 1');
            mysqli_stmt_bind_param($usernameStmt, 's', $nama_pengguna);
            mysqli_stmt_execute($usernameStmt);
            $usernameResult = mysqli_stmt_get_result($usernameStmt);

            if (!mysqli_fetch_assoc($usernameResult)) {
                break;
            }

            $nama_pengguna = $baseUsername . $counter;
            $counter++;
        }

        $passwordHash = password_hash($kata_sandi, PASSWORD_DEFAULT);
        $peran = 'peminjam';

        $stmt = mysqli_prepare($conn, 'INSERT INTO users (nama_lengkap, nama_pengguna, email, kata_sandi, peran) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssss', $nama_lengkap, $nama_pengguna, $email, $passwordHash, $peran);

        if (mysqli_stmt_execute($stmt)) {
            $userId = mysqli_insert_id($conn);
            $_SESSION['userid'] = $userId;
            $_SESSION['user_id'] = $userId;
            $_SESSION['id'] = $userId;
            $_SESSION['role'] = $peran;
            $_SESSION['peran'] = $peran;
            $_SESSION['nama'] = $nama_lengkap;
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            redirectByRole($peran);
        } else {
            $error = 'Pendaftaran gagal. Silakan coba lagi.';
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SignUp - SIMBA</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #f4f7fb;
            display: grid;
            place-items: center;
            padding: 32px 16px;
        }
        .auth-shell { width: min(100%, 390px); }
        .brand { text-align: center; margin-bottom: 20px; }
        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .brand-mark {
            width: 34px;
            height: 34px;
            border: 3px solid #facc15;
            transform: rotate(30deg);
            position: relative;
        }
        .brand-mark::before,
        .brand-mark::after {
            content: '';
            position: absolute;
            inset: 5px -7px;
            border: 2px solid #38bdf8;
        }
        .brand-mark::after { inset: -7px 5px; border-color: #facc15; }
        .brand-name {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: .5px;
        }
        h1 { margin: 0; font-size: 28px; line-height: 1.15; }
        .auth-card {
            background: #fff;
            border: 1px solid #dfe6ef;
            border-radius: 4px;
            box-shadow: 0 6px 18px rgba(17, 24, 39, .04);
            padding: 28px 28px 26px;
        }
        .alert {
            margin-bottom: 16px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            padding: 11px 12px;
            border-radius: 4px;
            font-size: 13px;
        }
        .field { margin-bottom: 18px; }
        .field-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 7px;
        }
        label { font-size: 13px; font-weight: 700; color: #374151; }
        .mini-link {
            border: 0;
            background: transparent;
            padding: 0;
            color: #eab308;
            font-size: 12px;
            cursor: pointer;
        }
        .input-wrap { position: relative; }
        .input-wrap svg {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
        }
        .input-wrap .lead { left: 14px; }
        .input-wrap .toggle {
            right: 13px;
            pointer-events: auto;
            cursor: pointer;
            border: 0;
            background: transparent;
            padding: 0;
            width: 20px;
            height: 20px;
            display: grid;
            place-items: center;
            color: #6b7280;
        }
        input {
            width: 100%;
            height: 43px;
            border: 1px solid #d9e1ec;
            border-radius: 2px;
            padding: 0 44px 0 39px;
            font-size: 14px;
            color: #111827;
            outline: none;
            background: #fff;
        }
        input:focus { border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(148, 163, 184, .18); }
        .primary-btn {
            width: 100%;
            height: 39px;
            margin-top: 8px;
            border: 1px solid #7f8187;
            border-radius: 2px;
            background: #7f8187;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .signin-note {
            margin: 27px 0 0;
            color: #7b8aa4;
            font-size: 13px;
            text-align: center;
        }
        .signin-note a { color: #0ea5e9; font-weight: 800; text-decoration: none; }
        @media (max-width: 420px) {
            .auth-card { padding: 24px 18px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="brand" aria-label="SIMBA">
            <div class="brand-logo">
                <span class="brand-mark" aria-hidden="true"></span>
                <span class="brand-name">SIMBA</span>
            </div>
            <h1>SignUp</h1>
        </section>

        <section class="auth-card">
            <?php if ($error !== '') { ?>
                <div class="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <form method="POST" action="">
                <div class="field">
                    <div class="field-top"><label for="nama_lengkap">Name</label></div>
                    <div class="input-wrap">
                        <svg class="lead" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Enter your name" value="<?php echo htmlspecialchars($nameValue); ?>" required>
                    </div>
                </div>

                <div class="field">
                    <div class="field-top"><label for="email">Email</label></div>
                    <div class="input-wrap">
                        <svg class="lead" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input type="email" id="email" name="email" placeholder="example@student.unsika.ac.id" value="<?php echo htmlspecialchars($emailValue); ?>" required>
                    </div>
                </div>

                <div class="field">
                    <div class="field-top">
                        <label for="kata_sandi">Password</label>
                        <button class="mini-link" type="button" data-show-all>Show Password</button>
                    </div>
                    <div class="input-wrap">
                        <svg class="lead" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="kata_sandi" name="kata_sandi" placeholder="Enter Your Password" required>
                        <button class="toggle" type="button" aria-label="Show password" data-target="kata_sandi">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <div class="field-top"><label for="konfirmasi_sandi">Confirm Password</label></div>
                    <div class="input-wrap">
                        <svg class="lead" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="konfirmasi_sandi" name="konfirmasi_sandi" placeholder="Enter Your Password" required>
                    </div>
                </div>

                <button class="primary-btn" type="submit" name="signup">SignUp</button>
            </form>

            <p class="signin-note">Already have an account? <a href="login.php">Login</a></p>
        </section>
    </main>

    <script>
        function toggleInput(input) { input.type = input.type === 'password' ? 'text' : 'password'; }
        document.querySelectorAll('[data-target]').forEach(function (button) {
            button.addEventListener('click', function () { toggleInput(document.getElementById(button.dataset.target)); });
        });
        document.querySelector('[data-show-all]').addEventListener('click', function () {
            toggleInput(document.getElementById('kata_sandi'));
            toggleInput(document.getElementById('konfirmasi_sandi'));
        });
    </script>
</body>
</html>
