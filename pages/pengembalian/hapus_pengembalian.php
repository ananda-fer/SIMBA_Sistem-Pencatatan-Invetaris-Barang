<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['admin', 'staff_tu']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    flash('error', 'ID pengembalian tidak valid.');
    redirect(app_url('pages/pengembalian/index.php?tab=riwayat'));
}

$id_pengembalian = (int)$_GET['id'];

$stmt_cek = $conn->prepare("SELECT id_peminjaman FROM pengembalian WHERE id = ?");
$stmt_cek->bind_param('i', $id_pengembalian);
$stmt_cek->execute();
$data = $stmt_cek->get_result()->fetch_assoc();

if (!$data) {
    flash('error', 'Data pengembalian tidak ditemukan.');
    redirect(app_url('pages/pengembalian/index.php?tab=riwayat'));
}

$id_peminjaman = $data['id_peminjaman'];

$conn->begin_transaction();
$success = true;

$stmt_detail = $conn->prepare("SELECT id_barang, jumlah FROM detail_peminjaman WHERE id_peminjaman = ?");
$stmt_detail->bind_param('i', $id_peminjaman);
$stmt_detail->execute();
$detail_list = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($detail_list as $d) {
    $stmt_stok = $conn->prepare("UPDATE barang SET stok_tersedia = stok_tersedia - ? WHERE id = ?");
    $stmt_stok->bind_param('ii', $d['jumlah'], $d['id_barang']);
    if (!$stmt_stok->execute()) { $success = false; break; }
}

if ($success) {
    $stmt_status = $conn->prepare("UPDATE pengajuan_peminjaman SET status = 'aktif' WHERE id = ?");
    $stmt_status->bind_param('i', $id_peminjaman);
    if (!$stmt_status->execute()) $success = false;
}

if ($success) {
    $stmt_hapus = $conn->prepare("DELETE FROM pengembalian WHERE id = ?");
    $stmt_hapus->bind_param('i', $id_pengembalian);
    if (!$stmt_hapus->execute()) $success = false;
}

if ($success) {
    $conn->commit();
    flash('success', 'Data pengembalian berhasil dihapus. Status peminjaman kembali menjadi aktif.');
} else {
    $conn->rollback();
    flash('error', 'Gagal menghapus data pengembalian. Silakan coba lagi.');
}

redirect(app_url('pages/pengembalian/index.php?tab=riwayat'));
