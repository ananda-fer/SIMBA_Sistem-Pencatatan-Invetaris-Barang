<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['admin', 'staff_tu']);

if (!isset($_POST['submit_edit'])) {
    redirect(app_url('pages/pengembalian/index.php'));
}

$id_pengembalian = (int)$_POST['id_pengembalian'];
$tanggal_kembali_aktual = $_POST['tanggal_kembali'] ?? '';
$kondisi_kembali = $_POST['kondisi_kembali'] ?? '';
$catatan_kerusakan = trim($_POST['catatan_kerusakan'] ?? '');

$allowed = ['baik', 'rusak_ringan', 'rusak_berat'];
if (!in_array($kondisi_kembali, $allowed) || !$tanggal_kembali_aktual) {
    flash('error', 'Data tidak valid. Periksa kembali isian form.');
    redirect(app_url('pages/pengembalian/index.php?tab=riwayat'));
}

$stmt_cek = $conn->prepare("SELECT p.tanggal_kembali, kr.id_peminjaman FROM pengembalian kr JOIN pengajuan_peminjaman p ON kr.id_peminjaman = p.id WHERE kr.id = ?");
$stmt_cek->bind_param('i', $id_pengembalian);
$stmt_cek->execute();
$data = $stmt_cek->get_result()->fetch_assoc();

if (!$data) {
    flash('error', 'Data pengembalian tidak ditemukan.');
    redirect(app_url('pages/pengembalian/index.php?tab=riwayat'));
}

$hari_terlambat = 0;
$date_batas  = new DateTime($data['tanggal_kembali']);
$date_aktual = new DateTime($tanggal_kembali_aktual);
if ($date_aktual > $date_batas) {
    $hari_terlambat = (int)$date_aktual->diff($date_batas)->days;
}

$id_peminjaman = $data['id_peminjaman'];
$conn->begin_transaction();
$success = true;

$stmt_update = $conn->prepare("UPDATE pengembalian SET tanggal_kembali = ?, hari_terlambat = ?, kondisi_kembali = ?, catatan_kerusakan = ? WHERE id = ?");
$stmt_update->bind_param('sissi', $tanggal_kembali_aktual, $hari_terlambat, $kondisi_kembali, $catatan_kerusakan, $id_pengembalian);
if (!$stmt_update->execute()) $success = false;

if ($success) {
    $stmt_detail = $conn->prepare("SELECT id_barang FROM detail_peminjaman WHERE id_peminjaman = ?");
    $stmt_detail->bind_param('i', $id_peminjaman);
    $stmt_detail->execute();
    $detail_list = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($detail_list as $d) {
        $stmt_kondisi = $conn->prepare("UPDATE barang SET kondisi = ? WHERE id = ?");
        $stmt_kondisi->bind_param('si', $kondisi_kembali, $d['id_barang']);
        if (!$stmt_kondisi->execute()) { $success = false; break; }
    }
}

if ($success) {
    $conn->commit();
    flash('success', 'Data pengembalian berhasil diperbarui.');
} else {
    $conn->rollback();
    flash('error', 'Gagal memperbarui data pengembalian. Silakan coba lagi.');
}

redirect(app_url('pages/pengembalian/index.php?tab=riwayat'));
