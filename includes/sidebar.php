<?php
// includes/sidebar.php
// Sidebar navigasi berbeda per role

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

function sb_active(string $dir, string $file = ''): string {
    global $current_dir, $current_page;
    if ($file) return ($current_dir === $dir && $current_page === $file) ? 'active' : '';
    return ($current_dir === $dir) ? 'active' : '';
}

$peran = $_SESSION['peran'] ?? 'peminjam';
$nama  = $_SESSION['nama_lengkap'] ?? 'User';
$inisial = strtoupper(substr($nama, 0, 1)) . (strpos($nama, ' ') !== false ? strtoupper(substr(strrchr($nama, ' '), 1, 1)) : '');
?>
<aside class="sidebar">

    <!-- Logo -->
    <div class="sb-logo">
        <div class="sb-logo-icon">
            <svg width="16" height="16" fill="none" stroke="#1a1a1a" stroke-width="2.2" viewBox="0 0 24 24">
                <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                <path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/>
            </svg>
        </div>
        <span class="sb-logo-text">SIMBA</span>
    </div>

    <!-- Info User -->
    <div class="sb-user-info">
        <div class="sb-avatar"><?= htmlspecialchars($inisial) ?></div>
        <div style="min-width:0">
            <div class="sb-uname"><?= htmlspecialchars($nama) ?></div>
            <div class="sb-urole"><?= ucfirst(str_replace('_', ' ', $peran)) ?></div>
        </div>
    </div>

    <!-- Navigasi -->
    <nav class="sb-nav">
        <?php if (in_array($peran, ['admin', 'staff_tu'])): ?>
        <!-- Dashboard -->
        <a href="<?= app_url('pages/dashboard/index.php') ?>"
           class="sb-item <?= sb_active('dashboard') ?>">
            <span class="sb-icon">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
            </span>
            Dashboard
        </a>
        <?php endif; ?>

        <?php if (in_array($peran, ['admin', 'staff_tu'])): ?>
        <!-- Barang -->
        <a href="<?= app_url('pages/barang/index.php') ?>" class="sb-item <?= sb_active('barang') ?>">
            <span class="sb-icon">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                    <path d="M16 3H8v4h8V3z"/>
                </svg>
            </span>
            Data Barang
        </a>
        <?php endif; ?>

        <?php if ($peran === 'admin'): ?>
        <!-- Kelola User -->
        <a href="<?= app_url('pages/admin/user/index.php') ?>" class="sb-item <?= sb_active('user') ?>">
            <span class="sb-icon">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </span>
            Kelola User
        </a>
        <?php endif; ?>

        <!-- Peminjaman: semua role -->
        <a href="<?= app_url('pages/peminjaman/index.php') ?>" class="sb-item <?= sb_active('peminjaman') ?>">
            <span class="sb-icon">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
                </svg>
            </span>
            Peminjaman
        </a>

        <?php if (in_array($peran, ['admin', 'staff_tu'])): ?>
        <!-- Pengembalian -->
        <a href="<?= app_url('pages/pengembalian/index.php') ?>" class="sb-item <?= sb_active('pengembalian') ?>">
            <span class="sb-icon">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="1 4 1 10 7 10"/>
                    <path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
                </svg>
            </span>
            Pengembalian
        </a>
        <?php endif; ?>

        <?php if (in_array($peran, ['admin', 'staff_tu'])): ?>
        <!-- Laporan -->
        <a href="<?= app_url('pages/laporan/index.php') ?>" class="sb-item <?= sb_active('laporan') ?>">
            <span class="sb-icon">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 20V10M12 20V4M6 20v-6"/>
                </svg>
            </span>
            Laporan
        </a>
        <?php endif; ?>
    </nav>

    <!-- Logout -->
    <div class="sb-footer">
        <a href="<?= app_url('pages/auth/logout.php') ?>" class="sb-logout">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
            </svg>
            Keluar
        </a>
    </div>
</aside>
