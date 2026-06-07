<?php
// includes/header.php
// Header umum untuk halaman yang memakai sidebar.

$page_title = $page_title ?? 'SIMBA';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SIMBA</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>
<div class="app-wrapper">
