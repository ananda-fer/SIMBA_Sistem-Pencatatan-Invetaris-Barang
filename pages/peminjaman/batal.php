<?php
// pages/peminjaman/batal.php — Batalkan Pengajuan (oleh peminjam)
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['peminjam']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

// Validasi: hanya bisa batalkan miliknya sendiri & status menunggu/diverifikasi
$stmt = $conn->prepare("
    SELECT id FROM pengajuan_peminjaman
    WHERE id = ? AND id_peminjam = ? AND status IN ('menunggu', 'diverifikasi')
");
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    flash('error', 'Pengajuan tidak dapat dibatalkan.');
    redirect('index.php');
}

$upd = $conn->prepare("
    UPDATE pengajuan_peminjaman
    SET status = 'dibatalkan', diperbarui_pada = NOW()
    WHERE id = ?
");
$upd->bind_param('i', $id);

if ($upd->execute()) {
    flash('success', "Pengajuan #$id berhasil dibatalkan.");
} else {
    flash('error', 'Gagal membatalkan pengajuan.');
}

redirect('index.php');