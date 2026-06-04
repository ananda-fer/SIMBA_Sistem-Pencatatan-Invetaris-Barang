<?php
// Determine the current page to set the active state on the sidebar
$currentPage = isset($currentPage) ? $currentPage : '';
?>

<!-- =====================
     SIDEBAR
===================== -->
<div class="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-layer-group"></i>
        SIMBA
    </div>

    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <?php 
            // Cek role user, jika admin atau staff maka tampilkan menu dashboard
            // Pastikan session_start() sudah dipanggil di awal aplikasi dan $_SESSION['role'] sudah diset saat login
            // Contoh: $_SESSION['role'] = 'admin';
            $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin'; // Default 'admin' untuk preview, ganti dengan session asli Anda
            
            if(in_array($userRole, ['admin', 'staff'])): 
            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>"
                   href="<?php echo $basePath; ?>pages/dashboard/index.php">
                    <i class="fa-solid fa-border-all"></i>
                    Dashboard
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'inventaris') ? 'active' : ''; ?>"
                   href="<?php echo $basePath; ?>pages/barang/index.php">
                    <i class="fa-solid fa-box-archive"></i>
                    Kelola Inventaris
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'peminjaman') ? 'active' : ''; ?>"
                   href="<?php echo $basePath; ?>pages/peminjaman/index.php">
                    <i class="fa-regular fa-file-lines"></i>
                    Permintaan Saya
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'pengembalian') ? 'active' : ''; ?>"
                   href="<?php echo $basePath; ?>pages/pengembalian/index.php">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Riwayat
                </a>
            </li>
            <?php if(in_array($userRole, ['admin', 'staff'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'laporan') ? 'active' : ''; ?>"
                   href="<?php echo $basePath; ?>pages/laporan/index.php">
                    <i class="fa-solid fa-folder-open"></i>
                    laporan
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fa-regular fa-circle-question"></i>
                    Help Center
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $basePath; ?>logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- =====================
     MAIN WRAPPER START
===================== -->
<div class="main-wrapper">

    <!-- TOP NAVBAR -->
    <div class="top-navbar">
        <h4 class="page-heading"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'SIMBA'; ?></h4>

        <div class="dropdown">
            <a href="#" class="user-profile-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration:none;">
                <div class="info">
                    <span class="u-name">Administrator</span>
                    <span class="u-role">SYSTEM CONTROL</span>
                </div>
                <div class="user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="#"><i class="fa-regular fa-user me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?php echo $basePath; ?>logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- PAGE CONTENT START -->
    <div class="page-content">
