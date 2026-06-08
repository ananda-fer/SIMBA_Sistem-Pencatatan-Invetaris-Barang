<?php
    // CEK LOGIN DULU
    require_once '../../includes/auth_check.php';
    
    // HANYA UNTUK ADMIN
    hanya_role(['admin']);
    
    // KONEKSI DATABASE
    require_once __DIR__ . '/../../config/koneksi.php';

    try {
        $kategori_list = $conn->query("SELECT * FROM kategori")->fetch_all(MYSQLI_ASSOC);
    } catch (\Exception $e) {
        $kategori_list = [];
    }

    $pageTitle   = 'Tambah Barang';
    $currentPage = 'inventaris';
    $basePath    = '../../';

    
    require_once $basePath . 'includes/header.php';
    require_once $basePath . 'includes/sidebar.php';
?>
<main class="main-content">

<h3 class="fw-bold mb-4">Tambah Barang Baru</h3>


<div class="card p-4 shadow-sm">
    <form action="proses_tambah.php" method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label class="form-label fw-bold">Kategori</label>
            <select class="form-select" name="kategori_id" id="kategori_id" required>
                <option value=""> Pilih Kategori </option>
                <?php foreach ($kategori_list as $kat): ?>
                    <option value="<?php echo $kat['id']; ?>"><?php echo $kat['nama']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Kode Barang</label>
            <div class="form-text text-muted">Kode barang akan dibuat otomatis oleh sistem setelah Anda menyimpan.</div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Nama Barang</label>
            <input type="text" class="form-control" name="nama" placeholder="Masukkan nama barang" required>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Stok Total</label>
                <input type="number" class="form-control" name="stok_total" value="1" min="0" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Stok Tersedia</label>
                <input type="number" class="form-control" name="stok_tersedia" value="1" min="0" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Kondisi</label>
            <select class="form-select" name="kondisi" required>
                <option value="baik">Baik</option>
                <option value="rusak_ringan">Rusak Ringan</option>
                <option value="rusak_berat">Rusak Berat</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Foto Barang</label>
            <input type="file" class="form-control" name="foto" accept="image/*" required>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-floppy-fill"></i>  Simpan</button>
            <a href="index.php" class="btn btn-dark"><i class="bi bi-house-door-fill"></i>  Kembali</a>
        </div>

    </form>
</div>

<?php
    require_once $basePath . 'includes/footer.php';
?>
