<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['admin', 'staff_tu']);

if (!isset($_POST['submit_kembali'])) {
    redirect(app_url('pages/pengembalian/index.php'));
}

$id_peminjaman = (int)$_POST['id_peminjaman'];
$tanggal_kembali_aktual = $_POST['tanggal_kembali'] ?? '';
$kondisi_kembali = $_POST['kondisi_kembali'] ?? '';
$catatan_kerusakan = trim($_POST['catatan_kerusakan'] ?? '');

$allowed = ['baik', 'rusak_ringan', 'rusak_berat'];
if (!in_array($kondisi_kembali, $allowed) || !$tanggal_kembali_aktual) {
    flash('error', 'Data tidak valid. Periksa kembali isian form.');
    redirect(app_url('pages/pengembalian/form_pengembalian.php?id=' . $id_peminjaman));
}

$stmt_cek = $conn->prepare("SELECT tanggal_kembali FROM pengajuan_peminjaman WHERE id = ? AND status = 'aktif'");
$stmt_cek->bind_param('i', $id_peminjaman);
$stmt_cek->execute();
$peminjaman = $stmt_cek->get_result()->fetch_assoc();

if (!$peminjaman) {
    flash('error', 'Data peminjaman tidak ditemukan atau sudah selesai.');
    redirect(app_url('pages/pengembalian/index.php'));
}

$batas_kembali = $peminjaman['tanggal_kembali'];
$hari_terlambat = 0;
$date_batas  = new DateTime($batas_kembali);
$date_aktual = new DateTime($tanggal_kembali_aktual);
if ($date_aktual > $date_batas) {
    $hari_terlambat = (int)$date_aktual->diff($date_batas)->days;
}

$diterima_oleh = (int)$_SESSION['user_id'];

$conn->begin_transaction();
$success = true;

$stmt_insert = $conn->prepare("INSERT INTO pengembalian (id_peminjaman, diterima_oleh, tanggal_kembali, hari_terlambat, kondisi_kembali, catatan_kerusakan) VALUES (?, ?, ?, ?, ?, ?)");
$stmt_insert->bind_param('iisiss', $id_peminjaman, $diterima_oleh, $tanggal_kembali_aktual, $hari_terlambat, $kondisi_kembali, $catatan_kerusakan);
if (!$stmt_insert->execute()) $success = false;

if ($success) {
    $stmt_status = $conn->prepare("UPDATE pengajuan_peminjaman SET status = 'selesai' WHERE id = ?");
    $stmt_status->bind_param('i', $id_peminjaman);
    if (!$stmt_status->execute()) $success = false;
}

if ($success) {
    $stmt_detail = $conn->prepare("SELECT id_barang, jumlah FROM detail_peminjaman WHERE id_peminjaman = ?");
    $stmt_detail->bind_param('i', $id_peminjaman);
    $stmt_detail->execute();
    $detail_list = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($detail_list as $d) {
        $stmt_stok = $conn->prepare("UPDATE barang SET stok_tersedia = stok_tersedia + ?, kondisi = ? WHERE id = ?");
        $stmt_stok->bind_param('isi', $d['jumlah'], $kondisi_kembali, $d['id_barang']);
        if (!$stmt_stok->execute()) { $success = false; break; }
    }
}

if ($success) {
    $conn->commit();
    flash('success', 'Pengembalian barang berhasil dicatat dan stok telah diperbarui.');
} else {
    $conn->rollback();
    flash('error', 'Gagal memproses pengembalian barang. Silakan coba lagi.');
}

redirect(app_url('pages/pengembalian/index.php?tab=riwayat'));
