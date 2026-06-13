<?php
    // CEK LOGIN DULU
    require_once '../../includes/auth_check.php';
    
    // HANYA ADMIN DAN STAFF
    hanya_role(['admin', 'staff_tu']);
    
    // KONEKSI DATABASE
    require_once __DIR__ . '/../../config/koneksi.php';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        header("Location: index.php");
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $barang = $stmt->get_result()->fetch_assoc();

        if (!$barang) {
            header("Location: index.php");
            exit;
        }
    } catch (\Exception $e) {
        die("Terjadi kesalahan database: " . $e->getMessage());
    }

    $pageTitle   = 'Edit Data Barang';
    $currentPage = 'inventaris';
    $basePath    = '../../';

    $extraStyles = '
    <style>
        .edit-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #edf2f4;
        }
        .current-image {
            max-width: 100%;
            height: auto;
            max-height: 220px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e9ecef;
            margin-bottom: 1rem;
        }
    </style>
    ';

    require_once $basePath . 'includes/header.php';
    require_once $basePath . 'includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h2>Edit Barang</h2>
            <p>Perbarui informasi dan detail barang inventaris.</p>
        </div>
    </div>


<div class="edit-card">
    <form action="proses_edit.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $barang['id']; ?>">
        <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($barang['foto']); ?>">

        <div class="row g-4">
            <div class="col-md-7">
                <div class="mb-3">
                    <label class="form-label fw-bold">Kode Barang</label>
                    <input type="text" class="form-control" name="kode_barang" value="<?php echo htmlspecialchars($barang['kode_barang']); ?>" readonly style="background-color: #e9ecef;">
                    <div class="form-text text-muted">*Kode barang tidak bisa diubah.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Barang</label>
                    <input type="text" class="form-control" name="nama" value="<?php echo htmlspecialchars($barang['nama']); ?>" required placeholder="Masukkan nama barang">
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Stok Total</label>
                        <input type="number" class="form-control" name="stok_total" value="<?php echo htmlspecialchars($barang['stok_total']); ?>" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Stok Tersedia</label>
                        <input type="number" class="form-control" name="stok_tersedia" value="<?php echo htmlspecialchars($barang['stok_tersedia']); ?>" min="0" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Kondisi</label>
                    <select class="form-select" name="kondisi" required>
                        <option value="baik" <?php echo $barang['kondisi'] === 'baik' ? 'selected' : ''; ?>>Baik</option>
                        <option value="rusak_ringan" <?php echo $barang['kondisi'] === 'rusak_ringan' ? 'selected' : ''; ?>>Rusak Ringan</option>
                        <option value="rusak_berat" <?php echo $barang['kondisi'] === 'rusak_berat' ? 'selected' : ''; ?>>Rusak Berat</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select class="form-select" name="status" required>
                        <option value="tersedia" <?php echo $barang['status'] === 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="dipinjam" <?php echo $barang['status'] === 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                        <option value="dalam_perbaikan" <?php echo $barang['status'] === 'dalam_perbaikan' ? 'selected' : ''; ?>>Dalam Perbaikan</option>
                    </select>
                </div>
            </div>

            <div class="col-md-5 text-center">
                <label class="form-label fw-bold d-block text-start mb-3">Foto Barang Saat Ini</label>
                <?php if (!empty($barang['foto']) && file_exists("../../assets/uploads/barang/" . $barang['foto'])): ?>
                    <img src="../../assets/uploads/barang/<?php echo $barang['foto']; ?>" class="current-image shadow-sm d-block mx-auto" alt="Foto Saat Ini">
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center bg-light text-muted border rounded mx-auto mb-3" style="height: 180px; max-width: 260px;">
                        <i class="fa-solid fa-box fa-2x mb-2"></i>
                        <span>Belum ada foto</span>
                    </div>
                <?php endif; ?>

                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">Update Foto</label>
                    <input type="file" class="form-control" name="foto" accept="image/*">
                    <div class="form-text text-muted">Kosongkan jika tidak ingin mengubah foto. Maks. 2MB (JPG, PNG, JPEG)</div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-dark fw-bold px-4">Batal</a>
            <button type="submit" class="btn btn-warning fw-bold px-4">Simpan</button>
        </div>
    </form>
</div>

<?php
    require_once $basePath . 'includes/footer.php';
?>
