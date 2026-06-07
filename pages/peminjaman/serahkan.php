<?php
// pages/peminjaman/serahkan.php - Konfirmasi penyerahan barang oleh Staff TU
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['staff_tu']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

$stmt = $conn->prepare("
    SELECT id
    FROM pengajuan_peminjaman
    WHERE id = ? AND status = 'disetujui'
");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    flash('error', 'Barang tidak dapat diserahkan. Pastikan status peminjaman sudah disetujui.');
    redirect('index.php');
}

$upd = $conn->prepare("
    UPDATE pengajuan_peminjaman
    SET status = 'aktif',
        diperbarui_pada = NOW()
    WHERE id = ? AND status = 'disetujui'
");
$upd->bind_param('i', $id);

if ($upd->execute() && $upd->affected_rows > 0) {
    flash('success', "Barang untuk peminjaman #$id berhasil diserahkan. Status menjadi aktif.");
} else {
    flash('error', 'Gagal menyerahkan barang.');
}

redirect('index.php');
