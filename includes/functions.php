<?php
// includes/functions.php

/**
 * Sanitasi input string
 */
function sanitasi(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Flash message — simpan ke session
 */
function flash(string $key, string $msg): void {
    $_SESSION['flash_' . $key] = $msg;
}

/**
 * Ambil & hapus flash message
 */
function get_flash(string $key): string {
    $msg = $_SESSION['flash_' . $key] ?? '';
    unset($_SESSION['flash_' . $key]);
    return $msg;
}

/**
 * Format tanggal ke format Indonesia
 */
function fmt_tgl(string $tgl): string {
    if (!$tgl) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
              'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    [$y, $m, $d] = explode('-', $tgl);
    return "$d {$bulan[(int)$m]} $y";
}

/**
 * Redirect
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Cek apakah peminjaman butuh surat pengantar
 * (berdasarkan barang yang dipilih — ada yg kondisi wajib surat)
 * Logika: jika ada barang ber-kategori "Elektronik Berat" atau stok_total <= 3
 * Anda bisa sesuaikan logika ini dengan kebutuhan nyata
 */
function barang_wajib_surat(mysqli $conn, int $id_barang): bool {
    // Barang dengan stok total sedikit dianggap perlu surat pengantar.
    $stmt = $conn->prepare("SELECT (stok_total <= 3) AS wajib_surat FROM barang WHERE id = ?");
    $stmt->bind_param('i', $id_barang);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (bool)($row['wajib_surat'] ?? false);
}

/**
 * Badge HTML status peminjaman
 */
function badge_status(string $status): string {
    $map = [
        'menunggu'     => ['label' => 'MENUNGGU',     'class' => 'badge-menunggu'],
        'diverifikasi' => ['label' => 'DIVERIFIKASI', 'class' => 'badge-diverifikasi'],
        'disetujui'    => ['label' => 'DISETUJUI',    'class' => 'badge-disetujui'],
        'ditolak'      => ['label' => 'DITOLAK',      'class' => 'badge-ditolak'],
        'aktif'        => ['label' => 'AKTIF',        'class' => 'badge-aktif'],
        'selesai'      => ['label' => 'SELESAI',      'class' => 'badge-selesai'],
        'dibatalkan'   => ['label' => 'DIBATALKAN',   'class' => 'badge-dibatalkan'],
    ];
    $b = $map[$status] ?? ['label' => strtoupper($status), 'class' => 'badge-aktif'];
    return '<span class="badge ' . $b['class'] . '">' . $b['label'] . '</span>';
}

/**
 * Badge surat
 */
function badge_surat(bool $wajib): string {
    if ($wajib) {
        return '<span class="badge badge-surat surat-wajib">Wajib Surat</span>';
    }
    return '<span class="badge badge-surat surat-bebas">Bebas</span>';
}
