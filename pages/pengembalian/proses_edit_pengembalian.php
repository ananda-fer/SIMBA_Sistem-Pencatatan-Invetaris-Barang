<?php
session_start();
require_once '../../config.php';

if (!isset($_POST['submit_edit'])){
    header("Location: index.php");
    exit();
}

$id_pengembalian = intval($_POST['id_pengembalian']);
$tanggal_kembali_aktual = $_POST['tanggal_kembali'];
$kondisi_kembali = mysqli_real_escape_string($koneksi, $_POST['kondisi_kembali']);
$catatan_kerusakan = mysqli_real_escape_string($koneksi, $_POST['catatan_kerusakan']);

$allowed = ['baik', 'rusak_ringan', 'rusak_berat'];
if (!in_array($kondisi_kembali, $allowed)){
    $_SESSION['error'] = "Pilihan kondisi tidak valid!";
    header("Location: index.php");
    exit();
}

$query_peminjaman = "SELECT p.tanggal_kembali, kr.id_peminjaman 
                     FROM pengembalian kr
                     JOIN pengajuan_peminjaman p ON kr.id_peminjaman = p.id
                     WHERE kr.id = $id_pengembalian";

$result_peminjaman = mysqli_query($koneksi, $query_peminjaman);

if (mysqli_num_rows($result_peminjaman) === 0){
    $_SESSION['error'] = "Data pengembalian tidak ditemukan!";
    header("Location: index.php");
    exit();
}

$data_peminjaman = mysqli_fetch_assoc($result_peminjaman);
$batas_kembali = $data_peminjaman['tanggal_kembali'];
$id_peminjaman = $data_peminjaman['id_peminjaman'];

$hari_terlambat = 0;
$date_batas = new DateTime($batas_kembali);
$date_aktual = new DateTime($tanggal_kembali_aktual);

if ($date_aktual > $date_batas) {
    $selisih = $date_aktual->diff($date_batas);
    $hari_terlambat = $selisih->days;
}

mysqli_begin_transaction($koneksi);
$success = true;

$query_update_kembali = "UPDATE pengembalian 
                         SET tanggal_kembali = '$tanggal_kembali_aktual', 
                             hari_terlambat = $hari_terlambat, 
                             kondisi_kembali = '$kondisi_kembali', 
                             catatan_kerusakan = '$catatan_kerusakan' 
                         WHERE id = $id_pengembalian";

if (!mysqli_query($koneksi, $query_update_kembali)){
    $success = false;
}

if ($success){
    $query_detail = "SELECT id_barang FROM detail_peminjaman WHERE id_peminjaman = $id_peminjaman";
    $result_detail = mysqli_query($koneksi, $query_detail);
    
    if ($result_detail) {
        while ($detail = mysqli_fetch_assoc($result_detail)) {
            $id_barang = $detail['id_barang'];

            $query_update_kondisi = "UPDATE barang SET kondisi = '$kondisi_kembali' WHERE id = $id_barang";
            if (!mysqli_query($koneksi, $query_update_kondisi)) {
                $success = false;
                break;
            }
        }
    } else {
        $success = false;
    }
}

if ($success){
    mysqli_commit($koneksi);
    $_SESSION['success'] = "Data pengembalian berhasil diperbarui dan disinkronkan";
} else {
    mysqli_rollback($koneksi);
    $_SESSION['error'] = "Gagal memperbarui data pengembalian. Silakan coba lagi";
}

header("Location: index.php");
exit();
?>
