<?php
session_start();
require_once '../../config.php';

$query = "SELECT p.id AS id_peminjaman, u.nama_lengkap AS nama_peminjam, p.keperluan, p.tanggal_pinjam, p.tanggal_kembali AS batas_kembali, 
        GROUP_CONCAT(CONCAT(b.nama, ' (', dp.jumlah, ')') SEPARATOR ', ') AS detail_barang 
        FROM pengajuan_peminjaman p
        JOIN users u ON p.id_peminjam = u.id
        JOIN detail_peminjaman dp ON dp.id_peminjaman = p.id
        JOIN barang b ON dp.id_barang = b.id
        WHERE p.status = 'aktif'
        GROUP BY p.id";

$result = mysqli_query($koneksi, $query);
if (!$result){
    die("Query Error: " . mysqli_error($koneksi));
}

/*if (!isset($_SESSION['user_id'])){
    header("Location:../../login.php");
    exit();
}

if ($_SESSION['peran'] !== 'admin' && $_SESSION['peran'] !== 'staff_tu'){
    $_SESSION['error'] = "Anda tidak memiliki hak akses untuk halaman ini";
    header("Location: ../dashboard/index.php");
    exit();
}*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Barang dan Kondisi</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <script>
            alert("<?php echo htmlspecialchars($_SESSION['success']); ?>");
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            alert("<?php echo htmlspecialchars($_SESSION['error']); ?>");
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>


    <h2>Daftar Peminjaman Aktif</h2>
    
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Peminjam</th>
                <th>Keperluan</th>
                <th>Barang yang Dipinjam (Jumlah)</th>
                <th>Tanggal Pinjam</th>
                <th>Batas Kembali</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            //jika data ditemukan
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) { 
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_peminjam']); ?></td>
                    <td><?php echo htmlspecialchars($row['keperluan']); ?></td>
                    <td><?php echo htmlspecialchars($row['detail_barang']); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['tanggal_pinjam'])); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['batas_kembali'])); ?></td>
                    <td>
                        <!-- Tombol Aksi untuk mengarahkan ke form pengembalian -->
                        <a href="form_pengembalian.php?id=<?php echo $row['id_peminjaman']; ?>">
                            Proses Pengembalian
                        </a>
                    </td>
                </tr>
            <?php 
                }
            } else { 
            ?>
                <tr>
                    <td colspan="7" align="center">Tidak ada peminjaman aktif saat ini.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

</body>
</html>