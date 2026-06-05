<?php
session_start();
require_once '../../config.php';

if (!isset($_GET['id']) || empty($_GET['id'])){
    die("Error: ID Peminjaman Tidak Ditemukan!");
}

$id_peminjaman = intval($_GET['id']);

$query_peminjaman = "SELECT p.*, u.nama_lengkap
                    FROM pengajuan_peminjaman p
                    JOIN users u ON p.id_peminjam = u.id
                    WHERE p.id = $id_peminjaman AND p.status = 'aktif'";

$result_peminjaman = mysqli_query($koneksi, $query_peminjaman);

if (mysqli_num_rows($result_peminjaman) === 0){
    die("Error: Data peminjam tidak ditemukan atau transaksi sudah selesai");
}

$data_peminjaman = mysqli_fetch_assoc($result_peminjaman);

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
    <title>Form Pengembalian Barang</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body>
    <h2 class="text-2x1 font-bold text-gray-800 mb-2">Form Proses Pengembalian Barang</h2>
    <a href="index.php" class="text-sm text-blue-500 rounded-x1 hover:underline transition">Kembali ke Daftar</a>
    <hr>

    <h3>Informasi Peminjaman</h3>
    <table cellpadding="5">
        <tr>
            <td><strong>Nama Peminjam: </strong></td>
            <td><?php echo htmlspecialchars($data_peminjaman['nama_lengkap']); ?></td>
        </tr>
        <tr>
            <td><strong>Keperluan: </strong></td>
            <td><?php echo htmlspecialchars($data_peminjaman['keperluan']); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Pinjam: </strong></td>
            <td><?php echo date('d-m-Y', strtotime($data_peminjaman['tanggal_pinjam'])); ?></td>
        </tr>
        <tr>
            <td><strong>Batas dikembalikan: </strong></td>
            <td><?php echo date('d-m-Y', strtotime($data_peminjaman['tanggal_kembali'])); ?></td>
        </tr>
    </table>

    <h3>Daftar Barang yang dipinjam</h3>
    <ul>
        <?php while ($barang = mysqli_fetch_assoc($result_barang)){?>
            <li>
                <strong><?php echo htmlspecialchars($barang['nama_barang']); ?></strong>
                (Kode: <?php echo htmlspecialchars($barang['kode_barang']); ?>) -
                Jumlah: <?php echo $barang['jumlah']; ?> unit
            </li>
            <?php } ?>
    </ul>
    <hr>
    
    <h3>Input Data Pengembalian: </h3>
    <form action="proses_pengembalian.php" method="POST">
        <input type="hidden" name="id_peminjaman" value="<?php echo $id_peminjaman; ?>">
        <table cellpadding="8">
            <tr>
                <td><label for="tanggal_kembali">Tanggal dikembalikan</label></td>
                <td><input type="date" id="tanggal_kembali" name="tanggal_kembali" value="<?php echo date('Y-m-d'); ?>"required></td>
            </tr>
            <tr>
                <td><label for="kondisi_kembali">Kondisi Barang:</label></td>
                <td>
                    <select id="kondisi_kembali" name="kondisi_kembali" required>
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="catatan_kerusakan">Catatan Kondisi / Kerusakan:</label></td>
                <td><textarea id="catatan_kerusakan" name="catatan_kerusakan" rows="4" cols="40" placeholder="Isi jika barang rusak..."></textarea></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="submit" name="submit_kembali">Simpan Pengembalian</button>
                </td>
            </tr>
        </table>
    </form>
</body>
</html>