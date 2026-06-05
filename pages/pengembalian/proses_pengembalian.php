<?php
session_start();
require_once '../../config.php';

if(!isset($_POST['submit_kembali'])){
    header("Location: index.php");
    exit();
}

$id_peminjaman = intval($_POST['id_peminjaman']);
$tanggal_kembali_aktual = $_POST['tanggal_kembali'];
$kondisi_kembali = mysqli_real_escape_string($koneksi, $_POST['kondisi_kembali']);
$allowed = ['baik', 'rusak_ringan', 'rusak_berat'];
if (!in_array($kondisi_kembali, $allowed)) {
    $_SESSION['error'] = "Kondisi tidak valid";
    header("Location: index.php");
    exit();
}
$catatan_kerusakan = mysqli_real_escape_string($koneksi, $_POST['catatan_kerusakan']);

$query_peminjaman = "SELECT tanggal_kembali FROM pengajuan_peminjaman WHERE id = $id_peminjaman";
$result_peminjaman = mysqli_query($koneksi, $query_peminjaman);

if (mysqli_num_rows($result_peminjaman) === 0){
    $_SESSION['error'] = "Data peminjamanan tidak ditemukan!";
    header("Location: index.php");
    exit();
}

$data_peminjaman = mysqli_fetch_assoc($result_peminjaman);
$batas_kembali = $data_peminjaman['tanggal_kembali'];

$hari_terlambat = 0;
$date_batas = new DateTime($batas_kembali);
$date_aktual = new DateTime($tanggal_kembali_aktual);

if ($date_aktual > $date_batas){
    $selisih = $date_aktual->diff($date_batas);
    $hari_terlambat = $selisih->days;
}

mysqli_begin_transaction($koneksi);

$diterima_oleh = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 2;
$success = true;

$query_insert_pengembalian = "INSERT INTO pengembalian (id_peminjaman, diterima_oleh, tanggal_kembali, hari_terlambat, kondisi_kembali, catatan_kerusakan)
                            VALUES ($id_peminjaman, $diterima_oleh, '$tanggal_kembali_aktual', $hari_terlambat, '$kondisi_kembali', '$catatan_kerusakan')";

if (!mysqli_query($koneksi, $query_insert_pengembalian)){
    $success = false;
}

if ($success){
    $query_update_status = "UPDATE pengajuan_peminjaman SET status = 'selesai' WHERE id = $id_peminjaman";
    if (!mysqli_query($koneksi, $query_update_status)){
        $success = false;
    }
}

if ($success){
    $query_detail = "SELECT id_barang, jumlah FROM detail_peminjaman WHERE id_peminjaman = $id_peminjaman";
    $result_detail = mysqli_query($koneksi, $query_detail);

    if ($result_detail){
        while ($detail = mysqli_fetch_assoc($result_detail)){
            $id_barang = $detail['id_barang'];
            $jumlah_kembali = $detail['jumlah'];
            $query_update_stok = "UPDATE barang SET stok_tersedia = stok_tersedia + $jumlah_kembali, kondisi = '$kondisi_kembali' WHERE id = $id_barang";
                if (!mysqli_query($koneksi, $query_update_stok)){
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
    $_SESSION['success'] = "Proses pengembalian barang berhasil dicatat dan stok telah dikembalikan";
} else {
    mysqli_rollback($koneksi);
    $_SESSION['error'] = "Gagal memproses pengembalian barang. Coba lagi";
}
header("Location: index.php");
exit();
?>