<?php
// includes/header.php
$page_title = $pageTitle ?? $page_title ?? 'SIMBA';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - SIMBA' : 'SIMBA'; ?></title>

    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Layout & komponen di-handle sepenuhnya oleh assets/css/style.css -->

    <!-- Page-specific styles placeholder -->
    <?php if (isset($extraStyles)) echo $extraStyles; ?>

    <?php if (function_exists('asset_url')): ?>
        <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
        <?php if (!empty($pageCss)): foreach ($pageCss as $css): ?>
        <link rel="stylesheet" href="<?= asset_url($css) ?>">
        <?php endforeach; endif; ?>
    <?php else: ?>
        <link rel="stylesheet" href="<?= ($basePath ?? '../../') . 'assets/css/style.css' ?>">
    <?php endif; ?>
</head>
<body>
<div class="app-wrapper">
