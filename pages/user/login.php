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
$emailValue = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailValue = $email;

    $stmt = mysqli_prepare($conn, 'SELECT id, nama_lengkap, nama_pengguna, email, kata_sandi, peran FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = null;

    $passwordMatches = false;
    $needsPasswordUpgrade = false;
    while ($candidate = mysqli_fetch_assoc($result)) {
        $storedPassword = $candidate['kata_sandi'];
        if (password_verify($password, $storedPassword)) {
            $user = $candidate;
            $passwordMatches = true;
            break;
        } elseif (hash_equals($storedPassword, $password)) {
            $user = $candidate;
            $passwordMatches = true;
            $needsPasswordUpgrade = true;
            break;
        }
    }

    if ($user && $passwordMatches) {
        if ($needsPasswordUpgrade) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = mysqli_prepare($conn, 'UPDATE users SET kata_sandi = ? WHERE id = ?');
            mysqli_stmt_bind_param($updateStmt, 'si', $passwordHash, $user['id']);
            mysqli_stmt_execute($updateStmt);
        }

        $_SESSION['userid'] = $user['id'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['id'] = $user['id'];
        $_SESSION['role'] = $user['peran'];
        $_SESSION['peran'] = $user['peran'];
        $_SESSION['nama'] = $user['nama_lengkap'] ?: $user['nama_pengguna'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'] ?: $user['nama_pengguna'];
        redirectByRole($user['peran']);
    } else {
        $error = 'Email atau password tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMBA</title>
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
        .brand { text-align: center; margin-bottom: 22px; }
        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
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
        h1 { margin: 0 0 8px; font-size: 28px; line-height: 1.15; }
        .subtitle { margin: 0; color: #7b8aa4; font-size: 14px; }
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
            color: #b7791f;
            font-size: 12px;
            text-decoration: none;
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
            border: 1px solid #111827;
            border-radius: 2px;
            background: #111827;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .divider { margin: 27px 0 12px; border-top: 1px solid #d9e1ec; text-align: center; }
        .divider span {
            position: relative;
            top: -9px;
            background: #fff;
            padding: 0 14px;
            color: #7b8aa4;
            font-size: 13px;
        }
        .secondary-btn {
            width: 100%;
            height: 34px;
            border: 1px solid #111827;
            border-radius: 2px;
            color: #111827;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }
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
            <h1>Welcome to SIMBA</h1>
            <p class="subtitle">SIMBA Asset Management System</p>
        </section>

        <section class="auth-card">
            <?php if ($error !== '') { ?>
                <div class="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <form method="POST" action="">
                <div class="field">
                    <div class="field-top"><label for="email">Email</label></div>
                    <div class="input-wrap">
                        <svg class="lead" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="email" id="email" name="email" placeholder="example@student.unsika.ac.id" value="<?php echo htmlspecialchars($emailValue); ?>" required>
                    </div>
                </div>

                <div class="field">
                    <div class="field-top">
                        <label for="password">Password</label>
                        <button class="mini-link" type="button" onclick="document.getElementById('password').focus();">Forgot password?</button>
                    </div>
                    <div class="input-wrap">
                        <svg class="lead" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button class="toggle" type="button" aria-label="Show password" data-target="password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button class="primary-btn" type="submit" name="login">
                    Sign In
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                </button>
            </form>

            <div class="divider"><span>Don't have an account?</span></div>
            <a class="secondary-btn" href="signup.php">SignUp</a>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.target);
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });
    </script>
</body>
</html>
