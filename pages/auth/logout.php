<?php
session_start();
session_destroy();
session_start();
$_SESSION['flash_sukses'] = 'Anda berhasil logout. Sampai jumpa!';
header('Location: login.php');
exit;
