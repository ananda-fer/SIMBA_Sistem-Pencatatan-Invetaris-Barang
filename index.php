<?php
session_start();

if (isset($_SESSION['id']) || isset($_SESSION['user_id'])) {
    header('Location: pages/peminjaman/index.php');
    exit;
}

header('Location: pages/auth/login.php');
exit;
