<?php
    // CEK LOGIN DULU
    require_once '../../includes/auth_check.php';
    
    // HANYA UNTUK ADMIN
    hanya_role(['admin']);
    
    // KONEKSI DATABASE
    require_once __DIR__ . '/../../config/koneksi.php';

    $kategori_id   = $_POST['kategori_id'];
    $nama          = $_POST['nama'];
    $stok_total    = (int)$_POST['stok_total'];
    $stok_tersedia = (int)$_POST['stok_tersedia'];
    $kondisi       = $_POST['kondisi'];
    $status        = 'tersedia';

    // VALIDASI INPUT STOK BARANG
    if ($stok_tersedia > $stok_total) {
        echo "<script>alert('Stok tersedia tidak boleh melebihi stok total!'); window.location='tambah.php';</script>";
        exit;
    }

    // KODE BARANG OTOMATIS
    try {
        $stmt_kategori = $conn->prepare("SELECT kode FROM kategori WHERE id = ?");
        $stmt_kategori->bind_param("i", $kategori_id);
        $stmt_kategori->execute();
        $kategori = $stmt_kategori->get_result()->fetch_assoc();

        if ($kategori) {
            $kode_kategori = $kategori['kode'];
            $prefix = "FKT-" . $kode_kategori . "-";
            $prefix_like = $prefix . '%';

            $stmt_barang = $conn->prepare("SELECT kode_barang FROM barang WHERE kode_barang LIKE ? ORDER BY kode_barang DESC LIMIT 1");
            $stmt_barang->bind_param("s", $prefix_like);
            $stmt_barang->execute();
            $barang = $stmt_barang->get_result()->fetch_assoc();

            if ($barang) {
                $last_code = $barang['kode_barang'];
                $parts = explode('-', $last_code);
                $last_num = (int)end($parts);
                $next_num = $last_num + 1;
            } else {
                $next_num = 1;
            }

            $next_seq = str_pad($next_num, 3, '0', STR_PAD_LEFT);
            $kode_barang = $prefix . $next_seq;
        } else {
            $kode_barang = 'FKT-UNKNOWN-001';
        }
    } catch (\Exception $e) {
        echo "<script>alert('Gagal membuat kode barang!'); window.location='tambah.php';</script>";
        exit;
    }

    // PROSES UPLOAD FOTO BARANG
    $nama_foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $nama_foto = time() . '_' . $_FILES['foto']['name'];
        $folder_upload = "../../assets/uploads/barang/";
        
        if (!is_dir($folder_upload)) {
            mkdir($folder_upload, 0777, true);
        }
        
        $tujuan = $folder_upload . $nama_foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], $tujuan);
    }

    // SIMPAN DATA KE DATABASE
    try {
        $stmt = $conn->prepare("INSERT INTO barang (kategori_id, kode_barang, nama, stok_total, stok_tersedia, kondisi, status, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiisss", $kategori_id, $kode_barang, $nama, $stok_total, $stok_tersedia, $kondisi, $status, $nama_foto);
        $stmt->execute();

        echo "<script>alert('Barang berhasil ditambahkan!'); window.location='index.php';</script>";
    } catch (\Exception $e) {
        echo "<script>alert('Gagal menambahkan barang!'); window.location='tambah.php';</script>";
    }
?>
