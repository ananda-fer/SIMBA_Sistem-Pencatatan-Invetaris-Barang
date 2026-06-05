<?php
    
    require_once __DIR__ . '/../../koneksi.php';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        header("Location: index.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT b.*, k.nama as nama_kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id = k.id WHERE b.id = ?");
        $stmt->execute([$id]);
        $barang = $stmt->fetch();
        
        if (!$barang) {
            header("Location: index.php");
            exit;
        }
    } catch (\PDOException $e) {
        die("Terjadi kesalahan database: " . $e->getMessage());
    }

    $pageTitle   = 'Detail Barang';
    $currentPage = 'inventaris';
    $basePath    = '../../';

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

    $extraStyles = '
    <style>
        .detail-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #edf2f4;
        }
        .detail-image {
            max-width: 100%;
            height: auto;
            max-height: 320px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e9ecef;
        }
        .info-table th {
            width: 35%;
            font-weight: 600;
            color: #495057;
            padding: 0.8rem 0;
            border-bottom: 1px solid #edf2f4;
        }
        .info-table td {
            color: #212529;
            padding: 0.8rem 0;
            border-bottom: 1px solid #edf2f4;
        }
    </style>
    ';

    
    require_once $basePath . 'includes/header.php';
    require_once $basePath . 'includes/navbar.php';
?>


<div class="detail-card">
    <div class="row g-4 align-items-center">
        <div class="col-md-5 text-center">
            <?php if (!empty($barang['foto']) && file_exists("../../assets/uploads/barang/" . $barang['foto'])): ?>
                <img src="../../assets/uploads/barang/<?php echo $barang['foto']; ?>" class="detail-image shadow-sm" alt="<?php echo htmlspecialchars($barang['nama']); ?>">
            <?php else: ?>
                <div class="d-flex flex-column align-items-center justify-content-center bg-light text-muted border rounded" style="height: 280px; width: 100%;">
                    <i class="fa-solid fa-box fa-3x mb-3"></i>
                    <span>Foto tidak tersedia</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-7">
            <table class="table table-borderless info-table m-0">
                <tbody>
                    <tr>
                        <th>Kode Barang</th>
                        <td>: <?php echo htmlspecialchars($barang['kode_barang']); ?></td>
                    </tr>
                    <tr>
                        <th>Nama Barang</th>
                        <td>: <?php echo htmlspecialchars($barang['nama']); ?></td>
                    </tr>
                    <tr>
                        <th>Kategori</th>
                        <td>: <?php echo htmlspecialchars($barang['nama_kategori'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Stok Total</th>
                        <td>: <?php echo htmlspecialchars($barang['stok_total']); ?></td>
                    </tr>
                    <tr>
                        <th>Stok Tersedia</th>
                        <td>: <?php echo htmlspecialchars($barang['stok_tersedia']); ?></td>
                    </tr>
                    <tr>
                        <th>Kondisi</th>
                        <td>: 
                            <span class="badge <?php echo $kondisi_badge[$barang['kondisi']] ?? 'bg-secondary'; ?>">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $barang['kondisi'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>: 
                            <span class="badge <?php echo $status_badge[$barang['status']] ?? 'bg-secondary'; ?>">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $barang['status'])); ?>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <a href="edit.php?id=<?php echo $barang['id']; ?>" class="btn btn-warning fw-bold px-4">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
        </a>
        <a href="index.php" class="btn btn-dark fw-bold px-4">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<?php
    require_once $basePath . 'includes/footer.php';
?>
