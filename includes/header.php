<?php
// includes/header.php
// Header umum untuk halaman yang memakai sidebar.

$page_title = $page_title ?? $pageTitle ?? 'SIMBA';
$pageCss    = $pageCss ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SIMBA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= app_url($css) ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="app-wrapper">
