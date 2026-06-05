<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - SIMBA' : 'SIMBA'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Global App CSS (sidebar, navbar, footer, dll) -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/app.css">

    <?php
    /**
     * CSS per-halaman:
     * Di setiap index.php, daftarkan CSS tambahan via array $pageCss sebelum require header.
     * Contoh: $pageCss = ['assets/css/dashboard.css'];
     */
    if (!empty($pageCss) && is_array($pageCss)):
        foreach ($pageCss as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo $basePath . $cssFile; ?>">
        <?php endforeach;
    endif; ?>
</head>
<body>
