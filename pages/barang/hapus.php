<?php
    
    require_once __DIR__ . '/../../koneksi.php';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        echo "<script>alert('ID tidak valid!'); window.location='index.php';</script>";
        exit;
    }

    try {
        $stmtSelect = $pdo->prepare("SELECT foto FROM barang WHERE id = ?");
        $stmtSelect->execute([$id]);
        $data = $stmtSelect->fetch();

        if ($data) {
            $folder_upload = "../../assets/uploads/barang/";

            if (!empty($data['foto']) && file_exists($folder_upload . $data['foto'])) {
                unlink($folder_upload . $data['foto']);
            }

            $stmtDelete = $pdo->prepare("DELETE FROM barang WHERE id = ?");
            
            if ($stmtDelete->execute([$id])) {
                echo "<script>alert('Barang berhasil dihapus!'); window.location='index.php';</script>";
            } else {
                echo "<script>alert('Gagal menghapus barang!'); window.location='index.php';</script>";
            }
        } else {
            echo "<script>alert('Data tidak ditemukan!'); window.location='index.php';</script>";
        }
    } catch (\PDOException $e) {
        echo "<script>alert('Terjadi kesalahan database: " . addslashes($e->getMessage()) . "'); window.location='index.php';</script>";
    }
?>
