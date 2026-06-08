<?php
    // CEK LOGIN DULU
    require_once '../../includes/auth_check.php';
    
    // HANYA UNTUK ADMIN
    hanya_role(['admin']);
    
    // KONEKSI DATABASE
    require_once __DIR__ . '/../../config/koneksi.php';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        echo "<script>alert('ID tidak valid!'); window.location='index.php';</script>";
        exit;
    }

    try {
        $stmtSelect = $conn->prepare("SELECT foto FROM barang WHERE id = ?");
        $stmtSelect->bind_param("i", $id);
        $stmtSelect->execute();
        $data = $stmtSelect->get_result()->fetch_assoc();

        if ($data) {
            $folder_upload = "../../assets/uploads/barang/";

            if (!empty($data['foto']) && file_exists($folder_upload . $data['foto'])) {
                unlink($folder_upload . $data['foto']);
            }

            $stmtDelete = $conn->prepare("DELETE FROM barang WHERE id = ?");
            $stmtDelete->bind_param("i", $id);

            if ($stmtDelete->execute()) {
                echo "<script>alert('Barang berhasil dihapus!'); window.location='index.php';</script>";
            } else {
                echo "<script>alert('Gagal menghapus barang!'); window.location='index.php';</script>";
            }
        } else {
            echo "<script>alert('Data tidak ditemukan!'); window.location='index.php';</script>";
        }
    } catch (\Exception $e) {
        echo "<script>alert('Terjadi kesalahan database: " . addslashes($e->getMessage()) . "'); window.location='index.php';</script>";
    }
?>
