<?php
// includes/header.php
$page_title = $pageTitle ?? $page_title ?? 'SIMBA';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SIMBA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <?php if (!empty($pageCss)): foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= app_url($css) ?>">
    <?php endforeach; endif; ?>
</head>
<body>
<div class="app-wrapper">
