<?php
    // CEK LOGIN DULU
    require_once '../../includes/auth_check.php';
    
    // HANYA ADMIN DAN STAFF
    hanya_role(['admin', 'staff_tu']);
    
    // KONEKSI DATABASE
    require_once __DIR__ . '/../../config/koneksi.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $foto_lama     = isset($_POST['foto_lama']) ? $_POST['foto_lama'] : '';
        $nama          = isset($_POST['nama']) ? $_POST['nama'] : '';
        $stok_total    = isset($_POST['stok_total']) ? (int)$_POST['stok_total'] : 0;
        $stok_tersedia = isset($_POST['stok_tersedia']) ? (int)$_POST['stok_tersedia'] : 0;
        $kondisi       = isset($_POST['kondisi']) ? $_POST['kondisi'] : '';
        $status        = isset($_POST['status']) ? $_POST['status'] : '';

        if ($id <= 0) {
            header("Location: index.php");
            exit;
        }

        // VALIDASI INPUT STOK BARANG
        if ($stok_tersedia > $stok_total) {
            echo "<script>alert('Stok tersedia tidak boleh melebihi stok total!'); window.location='edit.php?id=" . $id . "';</script>";
            exit;
        }

        $nama_foto = $foto_lama; 

        // PROSES UPLOAD FOTO BARANG
        if (!empty($_FILES['foto']['name'])) {
            $file_name = $_FILES['foto']['name'];
            $file_size = $_FILES['foto']['size'];
            $file_tmp  = $_FILES['foto']['tmp_name'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                echo "<script>alert('Format file tidak didukung! Harap unggah foto dengan format JPG, JPEG, atau PNG.'); window.location='edit.php?id=" . $id . "';</script>";
                exit;
            }
            
            if ($file_size > 2 * 1024 * 1024) {
                echo "<script>alert('Ukuran file terlalu besar! Maksimal ukuran file adalah 2MB.'); window.location='edit.php?id=" . $id . "';</script>";
                exit;
            }
            
            $nama_foto = time() . '_' . $file_name;
            $folder_upload = "../../assets/uploads/barang/";

            if (!is_dir($folder_upload)) {
                mkdir($folder_upload, 0777, true);
            }

            $tujuan = $folder_upload . $nama_foto;
            
            if (move_uploaded_file($file_tmp, $tujuan)) {
                if (!empty($foto_lama) && file_exists($folder_upload . $foto_lama)) {
                    unlink($folder_upload . $foto_lama);
                }
            } else {
                echo "<script>alert('Gagal mengunggah foto!'); window.location='edit.php?id=" . $id . "';</script>";
                exit;
            }
        }

        // UPDATE DATA KE DATABASE
        try {
            $stmt = $conn->prepare("UPDATE barang SET nama = ?, stok_total = ?, stok_tersedia = ?, kondisi = ?, status = ?, foto = ? WHERE id = ?");
            $stmt->bind_param("siisssi", $nama, $stok_total, $stok_tersedia, $kondisi, $status, $nama_foto, $id);
            $result = $stmt->execute();

            if ($result) {
                echo "<script>alert('Barang berhasil diperbarui!'); window.location='index.php';</script>";
            } else {
                echo "<script>alert('Gagal memperbarui barang!'); window.location='edit.php?id=" . $id . "';</script>";
            }
        } catch (\Exception $e) {
            echo "<script>alert('Terjadi kesalahan database: " . addslashes($e->getMessage()) . "'); window.location='edit.php?id=" . $id . "';</script>";
        }
    } else {
        header("Location: index.php");
        exit;
    }
?>
