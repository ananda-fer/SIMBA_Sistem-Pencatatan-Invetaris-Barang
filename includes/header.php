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

    <style>
        /* =====================
           GLOBAL STYLES
        ===================== */
        * {
            box-sizing: border-box;
        }
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* =====================
           SIDEBAR
        ===================== */
        .sidebar {
            width: 220px;
            background-color: #2a2a2a;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            color: #dcdcdc;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 18px 22px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-brand i {
            color: #fca311;
            font-size: 1.4rem;
        }
        .sidebar-nav {
            padding: 10px 0;
            flex: 1;
        }
        .sidebar-nav .nav-link {
            color: #e0e0e0;
            padding: 11px 22px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.25s ease;
            font-size: 0.88rem;
            border-left: 4px solid transparent;
            text-decoration: none;
        }
        .sidebar-nav .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
        }
        .sidebar-nav .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.08);
            border-left-color: rgba(252,163,17,0.5);
        }
        .sidebar-nav .nav-link.active {
            color: #fca311;
            background-color: rgba(252, 163, 17, 0.08);
            border-left-color: #fca311;
            font-weight: 600;
        }
        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 10px 0;
        }

        /* =====================
           MAIN CONTENT WRAPPER
        ===================== */
        .main-wrapper {
            margin-left: 220px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* =====================
           TOP NAVBAR
        ===================== */
        .top-navbar {
            background-color: #fff;
            padding: 12px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .top-navbar .page-heading {
            font-weight: 700;
            font-size: 1.25rem;
            color: #1e1e2d;
            margin: 0;
        }
        .user-profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 6px 14px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .user-profile-btn:hover {
            background: #e9ecef;
        }
        .user-profile-btn .info {
            text-align: right;
        }
        .user-profile-btn .info .u-name {
            font-weight: 700;
            font-size: 0.85rem;
            color: #212529;
            display: block;
            line-height: 1.2;
        }
        .user-profile-btn .info .u-role {
            font-size: 0.68rem;
            color: #adb5bd;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .user-avatar {
            width: 34px;
            height: 34px;
            background-color: #ffca28;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-size: 14px;
        }

        /* =====================
           PAGE CONTENT
        ===================== */
        .page-content {
            padding: 24px 28px;
            flex: 1;
        }

        /* =====================
           FOOTER
        ===================== */
        .main-footer {
            background-color: #fff;
            border-top: 1px solid #e9ecef;
            padding: 12px 28px;
            text-align: center;
            font-size: 0.78rem;
            color: #adb5bd;
        }
    </style>

    <!-- Page-specific styles placeholder -->
    <?php if (isset($extraStyles)) echo $extraStyles; ?>

    <?php if (function_exists('app_url')): ?>
        <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
        <?php if (!empty($pageCss)): foreach ($pageCss as $css): ?>
        <link rel="stylesheet" href="<?= app_url($css) ?>">
        <?php endforeach; endif; ?>
    <?php else: ?>
        <link rel="stylesheet" href="<?= ($basePath ?? '../../') . 'assets/css/style.css' ?>">
    <?php endif; ?>
</head>
<body>
<div class="app-wrapper">
