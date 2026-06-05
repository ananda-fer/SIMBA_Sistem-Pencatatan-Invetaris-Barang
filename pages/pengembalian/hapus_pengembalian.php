<?php
session_start();
require_once '../../config.php';

if (!isset($_GET['id']) || empty($_GET['id'])){
    $_SESSION['error'] = "ID Pengembalian tidak valid";
    header("Location: index.php");
    exit();
}

$id_pengembalian = intval($_GET['id']);

$query_cek = "SELECT kr.id_peminjaman FROM pengembalian kr
            WHERE kr.id = $id_pengembalian";
$result_cek = mysqli_query($koneksi, $query_cek);

if (mysqli_num_rows($result_cek) === 0) {
    $_SESSION['error'] = "Data pengembalian tidak ditemukan";
    header("Location: index.php");
    exit();
}

$data_pengembalian = mysqli_fetch_assoc($result_cek);
$id_peminjaman = $data_pengembalian['id_peminjaman'];
mysqli_begin_transaction($koneksi);
$success = true;

$query_detail = "SELECT id_barang, jumlah FROM detail_peminjaman
                WHERE id_peminjaman = $id_peminjaman";
$result_detail = mysqli_query($koneksi, $query_detail);

if ($result_detail){
    while ($detail = mysqli_fetch_assoc($result_detail)){
        $id_barang = $detail['id_barang'];
        $jumlah = $detail['jumlah'];

        $query_update_stok = "UPDATE barang SET stok_tersedia = stok_tersedia - $jumlah WHERE id = $id_barang";
        if (!mysqli_query($koneksi, $query_update_stok)){
            $success = false;
            break;
        }   
    }
} else {
    $success = false;
}

if ($success){
    $query_update_status = "UPDATE pengajuan_peminjaman SET status = 'aktif'
                            WHERE id = $id_peminjaman";
    if (!mysqli_query($koneksi, $query_update_status)){
        $success = false;
    }
}

if ($success){
    $query_delete = "DELETE FROM pengembalian WHERE id = $id_pengembalian";
    if (!mysqli_query($koneksi, $query_delete)){
        $success = false;   
    }
}

if ($success){
    mysqli_commit($koneksi);
    $_SESSION['success'] = "Data pengembalian berhasil dihapus dan peminjaman aktif kembali";
} else {
    mysqli_rollback($koneksi);
    $_SESSION['error'] = "Gagal menghapus data pengembalian. Coba lagi";
}

header("Location: index.php");
exit();
?>
