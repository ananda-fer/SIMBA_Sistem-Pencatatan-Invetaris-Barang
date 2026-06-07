<?php
    // CEK LOGIN DULU
    require_once '../../includes/auth_check.php';
    
    // AKTIFKAN SESSION SEMENTARA UNTUK TESTING
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // PERAN SEMENTARA (Ubah ke 'admin', 'staff_tu', atau 'peminjam' untuk tes)
    if (!isset($_SESSION['peran'])) {
        $_SESSION['peran'] = 'admin';
    }
    
    // KONEKSI DATABASE
    require_once __DIR__ . '/../../koneksi.php';

    $search = isset($_GET['cari']) ? $_GET['cari'] : '';
    $filter_kat = isset($_GET['kategori']) ? $_GET['kategori'] : '';
    $filter_kondisi = isset($_GET['kondisi']) ? $_GET['kondisi'] : '';

    $query = "SELECT b.*, k.nama as nama_kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id = k.id WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (b.nama LIKE :search_nama OR b.kode_barang LIKE :search_kode)";
        $params['search_nama'] = "%" . $search . "%";
        $params['search_kode'] = "%" . $search . "%";
    }
    if (!empty($filter_kat)) {
        $query .= " AND b.kategori_id = :kategori_id";
        $params['kategori_id'] = (int)$filter_kat;
    }
    if (!empty($filter_kondisi)) {
        $query .= " AND b.kondisi = :kondisi";
        $params['kondisi'] = $filter_kondisi;
    }

    $query .= " ORDER BY b.id DESC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
    } catch (\PDOException $e) {
        $result = [];
    }

    try {
        $kategori_stmt = $pdo->query("SELECT * FROM kategori");
        $kategori_list = $kategori_stmt->fetchAll();
    } catch (\PDOException $e) {
        $kategori_list = [];
    }

    $kondisi_badge = [
        'baik' => 'bg-success',
        'rusak_ringan' => 'bg-warning text-dark',
        'rusak_berat' => 'bg-danger'
    ];

    $status_badge = [
        'tersedia' => 'bg-success',
        'dipinjam' => 'bg-primary',
        'dalam_perbaikan' => 'bg-danger'
    ];

    $pageTitle   = 'Katalog Barang';
    $currentPage = 'inventaris';
    $basePath    = '../../'; 

    $extraStyles = '
    <style>
        .filter-box {
            background: #91969e; border-radius: 14px; padding: 1.5rem;
        }
        .filter-box label { color: #fff; font-weight: 500; }
        .filter-box .form-control,
        .filter-box .form-select {
            background: #1a1e24; color: #fff; border: 1px solid #2d3748;
        }
        .filter-box .form-control::placeholder { color: #999; }
    </style>
    ';

    
    require_once $basePath . 'includes/header.php';
    require_once $basePath . 'includes/navbar.php';
?>

        <?php if ($_SESSION['peran'] === 'admin'): ?>
        <?php // TOMBOL TAMBAH KHUSUS ADMIN ?>
        <a href="tambah.php" class="btn btn-warning fw-bold mb-3"><i class="bi bi-bag-plus-fill"></i> Tambah Barang</a>
        <?php endif; ?>
        <!-- FORM FILTER KATALOG BARANG -->
        <div class="filter-box mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Pencarian</label>
                <input type="text" class="form-control" name="cari" placeholder="Cari nama/kode barang......" value="<?php echo htmlspecialchars($search); ?>">            
        </div>
            <div class="col-md-3">
                <label class="form-label">Kategori</label>
                <select class="form-select" name="kategori">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>" <?php echo $filter_kat == $kat['id'] ? 'selected' : ''; ?>><?php echo $kat['nama']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Kondisi</label>
                <select class="form-select" name="kondisi">
                    <option value="">Semua Kondisi</option>
                    <option value="baik" <?php echo $filter_kondisi == 'baik' ? 'selected' : ''; ?>>Baik</option>
                    <option value="rusak_ringan" <?php echo $filter_kondisi == 'rusak_ringan' ? 'selected' : ''; ?>>Rusak Ringan</option>
                    <option value="rusak_berat" <?php echo $filter_kondisi == 'rusak_berat' ? 'selected' : ''; ?>>Rusak Berat</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-search"></i>  Cari</button>
                <a href="index.php" class="btn btn-dark"><i class="bi bi-x-circle"></i>  Reset</a>
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR KATALOG BARANG -->
    <table class="table Kolom-SIMBA">
        <thead>
            <tr>
                <th>NO</th>
                <th>FOTO</th>
                <th>KODE</th>
                <th>NAMA BARANG</th>
                <th>KATEGORI</th>
                <th>STOK</th>
                <th>KONDISI</th>
                <th>STATUS</th>
                <th>AKSI</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($result as $row): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td>
                    <?php if (!empty($row['foto']) && file_exists("../../assets/uploads/barang/" . $row['foto'])): ?>
                        <img src="../../assets/uploads/barang/<?php echo $row['foto']; ?>" width="50" height="50" style="object-fit:cover;" class="rounded">
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $row['kode_barang']; ?></td>
                <td><?php echo $row['nama']; ?></td>
                <td><?php echo $row['nama_kategori']; ?></td>
                <td><?php echo $row['stok_tersedia']; ?>/<?php echo $row['stok_total']; ?></td>
                <td><span class="badge <?php echo $kondisi_badge[$row['kondisi']] ?? 'bg-secondary'; ?>"><?php echo str_replace('_', ' ', $row['kondisi']); ?></span></td>
                <td><span class="badge <?php echo $status_badge[$row['status']] ?? 'bg-secondary'; ?>"><?php echo str_replace('_', ' ', $row['status']); ?></span></td>
                <td>
                    <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Detail</a>
                    <?php if ($_SESSION['peran'] === 'admin' || $_SESSION['peran'] === 'staff_tu'): ?>
                        <?php // TOMBOL EDIT ?>
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <?php endif; ?>
                    <?php if ($_SESSION['peran'] === 'admin'): ?>
                        <?php // TOMBOL HAPUS KHUSUS ADMIN ?>
                        <a href="hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php
    require_once $basePath . 'includes/footer.php';
?>