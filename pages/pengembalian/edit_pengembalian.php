<?php
session_start();
require_once '../../config.php';

if (!isset($_GET['id']) || empty($_GET['id'])){
    $_SESSION['error'] = "Id pengembalian tidak ditemukan";
    header("Location: index.php");
    exit();
}

$id_pengembalian = intval($_GET['id']);

$query_pengembalian = "SELECT kr.*, p.keperluan, p.tanggal_pinjam, p.tanggal_kembali AS batas_kembali, u.nama_lengkap
                        FROM pengembalian kr
                        JOIN pengajuan_peminjaman p ON kr.id_peminjaman = p.id
                        JOIN users u ON p.id_peminjam = u.id
                        WHERE kr.id = $id_pengembalian";

$result_pengembalian = mysqli_query($koneksi, $query_pengembalian);

if (mysqli_num_rows($result_pengembalian) === 0){
    $_SESSION['error'] = "Data pengembalian tidak ditemukan";
    header("Location: index.php");
    exit();
}

$data_kembali = mysqli_fetch_assoc($result_pengembalian);
$id_peminjaman = $data_kembali['id_peminjaman'];

$query_barang = "SELECT dp.*, b.nama AS nama_barang, b.kode_barang
                FROM detail_peminjaman dp
                JOIN barang b ON dp.id_barang = b.id
                WHERE dp.id_peminjaman = $id_peminjaman";

$result_barang = mysqli_query($koneksi, $query_barang);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengembalian Barang</title>
</head>
<body>
    <h2>Form Edit Data Pengembalian</h2>
    <a href="index.php">Kembali ke Daftar</a>
    <hr>
    <h3>Informasi Peminjaman</h3>
    <table cellpadding="5">
        <tr>
            <td><strong>Nama Peminjam:</strong></td>
            <td><?php echo htmlspecialchars($data_kembali['nama_lengkap']); ?></td>
        </tr>
        <tr>
            <td><strong>Keperluan:</strong></td>
            <td><?php echo htmlspecialchars($data_kembali['keperluan']); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Pinjam:</strong></td>
            <td><?php echo date('d-m-Y', strtotime($data_kembali['tanggal_pinjam'])); ?></td>
        </tr>
        <tr>
            <td><strong>Batas Harus Kembali:</strong></td>
            <td><?php echo date('d-m-Y', strtotime($data_kembali['batas_kembali'])); ?></td>
        </tr>
    </table>

    <h3>Daftar Barang yag dipinjam:</h3>
    <ul>
        <?php while ($barang = mysqli_fetch_assoc($result_barang)){ ?>
            <li>
                <strong><?php echo htmlspecialchars($barang['nama_barang']); ?></strong>
            (Kode: <?php echo htmlspecialchars($barang['kode_barang']);?>) -
            Jumlah: <?php echo $barang['jumlah']; ?> unit
            </li>
        <?php } ?>
    </ul>
    <hr>

    <h3>Edit Data Input Pengembalian:</h3>
    <form action="proses_edit_pengembalian.php" method="POST">
        <input type="hidden" name="id_pengembalian" value="<?php echo $id_pengembalian; ?>">
        <table cellpadding="8">
            <tr>
                <td><label for="tanggal_kembali">Tanggal Dikembalikan:</label></td>
                <td>
                    <input type="date" id="tanggal_kembali" name="tanggal_kembali" 
                           value="<?php echo $data_kembali['tanggal_kembali']; ?>" required>
                </td>
            </tr>
            <tr>
                <td><label for="kondisi_kembali">Kondisi Barang:</label></td>
                <td>
                    <select id="kondisi_kembali" name="kondisi_kembali" required>
                        <option value="baik" <?php echo ($data_kembali['kondisi_kembali'] == 'baik') ? 'selected' : ''; ?>>Baik</option>
                        <option value="rusak_ringan" <?php echo ($data_kembali['kondisi_kembali'] == 'rusak_ringan') ? 'selected' : ''; ?>>Rusak Ringan</option>
                        <option value="rusak_berat" <?php echo ($data_kembali['kondisi_kembali'] == 'rusak_berat') ? 'selected' : ''; ?>>Rusak Berat</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="catatan_kerusakan">Catatan Kondisi / Kerusakan:</label></td>
                <td>
                    <textarea id="catatan_kerusakan" name="catatan_kerusakan" rows="4" cols="40" 
                              placeholder="Wajib diisi jika barang rusak..."><?php echo htmlspecialchars($data_kembali['catatan_kerusakan'] ?? ''); ?></textarea>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="submit" name="submit_edit">Simpan Perubahan</button>
                </td>
            </tr>
        </table>
    </form>
</body>
</html>