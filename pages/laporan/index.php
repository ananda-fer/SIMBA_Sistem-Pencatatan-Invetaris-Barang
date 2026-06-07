<?php
// =========================================================================
// KONEKSI DATABASE
// =========================================================================
require_once __DIR__ . '/../../koneksi.php';

// =========================================================================
// QUERY: AMBIL DATA LAPORAN (dengan filter dari GET)
// =========================================================================
$where  = [];
$params = [];

if (!empty($_GET['tanggal_mulai'])) {
    $where[]  = "l.periode_awal >= :tanggal_mulai";
    $params[':tanggal_mulai'] = $_GET['tanggal_mulai'];
}
if (!empty($_GET['tanggal_akhir'])) {
    $where[]  = "l.periode_akhir <= :tanggal_akhir";
    $params[':tanggal_akhir'] = $_GET['tanggal_akhir'];
}
if (!empty($_GET['jenis'])) {
    $where[]  = "l.jenis_laporan = :jenis";
    $params[':jenis'] = $_GET['jenis'];
}
if (!empty($_GET['judul'])) {
    $where[]  = "l.judul LIKE :judul";
    $params[':judul'] = '%' . $_GET['judul'] . '%';
}

$whereSql    = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt        = $pdo->prepare("
    SELECT l.id, l.judul, l.jenis_laporan, l.periode_awal, l.periode_akhir,
           l.dibuat_pada, l.catatan, u.nama_lengkap AS dibuat_oleh_nama
    FROM laporan l
    JOIN users u ON u.id = l.dibuat_oleh
    $whereSql
    ORDER BY l.dibuat_pada DESC
");
$stmt->execute($params);
$laporanList = $stmt->fetchAll();

// =========================================================================
// PAGE CONFIGURATION
// =========================================================================
$pageTitle   = 'Pusat Laporan & Arsip';
$currentPage = 'laporan';
$basePath    = '../../';
$pageCss     = ['assets/css/laporan.css'];
$pageJs      = ['assets/js/laporan.js'];

require_once $basePath . 'includes/header.php';
require_once $basePath . 'includes/navbar.php';
?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-<?php echo htmlspecialchars($_GET['type'] ?? 'info'); ?> alert-dismissible fade show mb-3" role="alert">
    <?php echo htmlspecialchars($_GET['msg']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter Card -->
<div class="filter-card">
    <form method="GET" action="">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label>Rentang Tanggal</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="date" class="form-control" name="tanggal_mulai"
                           value="<?php echo htmlspecialchars($_GET['tanggal_mulai'] ?? ''); ?>">
                    <span class="text-white fw-bold">s/d</span>
                    <input type="date" class="form-control" name="tanggal_akhir"
                           value="<?php echo htmlspecialchars($_GET['tanggal_akhir'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <label>Jenis Laporan</label>
                <select class="form-select" name="jenis">
                    <option value="">Semua Jenis</option>
                    <?php foreach (['peminjaman' => 'Peminjaman', 'pengembalian' => 'Pengembalian', 'inventaris' => 'Inventaris'] as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo (($_GET['jenis'] ?? '') === $val) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label>Judul Laporan</label>
                <input type="text" class="form-control" name="judul"
                       placeholder="Cari judul laporan..."
                       value="<?php echo htmlspecialchars($_GET['judul'] ?? ''); ?>">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn-simpan-laporan">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Header Tabel -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <small class="text-muted">Menampilkan <strong><?php echo count($laporanList); ?></strong> data laporan</small>
    <button class="btn-tambah-laporan" data-bs-toggle="modal" data-bs-target="#modalTambahLaporan">
        <i class="fa-solid fa-plus"></i> Tambah Laporan
    </button>
</div>

<!-- Tabel Data -->
<div class="table-card">
    <table class="table table-borderless">
        <thead>
            <tr>
                <th width="5%"  class="text-center">NO</th>
                <th width="24%">JUDUL LAPORAN</th>
                <th width="20%" class="text-center">PERIODE</th>
                <th width="14%" class="text-center">KATEGORI</th>
                <th width="15%" class="text-center">TANGGAL DIBUAT</th>
                <th width="22%" class="text-center">AKSI</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporanList)): ?>
                <tr class="empty-row"><td colspan="6"><i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>Belum ada data laporan.</td></tr>
            <?php else: ?>
                <?php foreach ($laporanList as $no => $lap): ?>
                    <?php $periode = date('d M Y', strtotime($lap['periode_awal'])) . ' - ' . date('d M Y', strtotime($lap['periode_akhir'])); ?>
                    <tr>
                        <td class="text-center"><?php echo $no + 1; ?></td>
                        <td><?php echo htmlspecialchars($lap['judul']); ?></td>
                        <td class="text-center"><?php echo $periode; ?></td>
                        <td class="text-center">
                            <span class="badge-kategori"><?php echo strtoupper($lap['jenis_laporan']); ?></span>
                        </td>
                        <td class="text-center"><?php echo date('d M Y', strtotime($lap['dibuat_pada'])); ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center align-items-center gap-1">
                                <button class="btn-act btn-detail" data-bs-toggle="modal" data-bs-target="#modalDetailLaporan"
                                        data-id="<?php echo $lap['id']; ?>"
                                        data-judul="<?php echo htmlspecialchars($lap['judul']); ?>"
                                        data-periode="<?php echo $periode; ?>"
                                        data-kategori="<?php echo strtoupper($lap['jenis_laporan']); ?>"
                                        data-catatan="<?php echo htmlspecialchars($lap['catatan'] ?? '-'); ?>"
                                        data-dibuat="<?php echo date('d M Y', strtotime($lap['dibuat_pada'])); ?>"
                                        data-oleh="<?php echo htmlspecialchars($lap['dibuat_oleh_nama']); ?>">
                                    Detail
                                </button>
                                <button class="btn-act btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditLaporan"
                                        data-id="<?php echo $lap['id']; ?>"
                                        data-judul="<?php echo htmlspecialchars($lap['judul']); ?>"
                                        data-mulai="<?php echo $lap['periode_awal']; ?>"
                                        data-akhir="<?php echo $lap['periode_akhir']; ?>"
                                        data-jenis="<?php echo $lap['jenis_laporan']; ?>"
                                        data-catatan="<?php echo htmlspecialchars($lap['catatan'] ?? ''); ?>">
                                    Edit
                                </button>
                                <a href="cetak.php?id=<?php echo $lap['id']; ?>" class="btn-act btn-cetak" target="_blank">Cetak</a>
                                <button class="btn-act btn-hapus" data-bs-toggle="modal" data-bs-target="#modalHapusLaporan"
                                        data-id="<?php echo $lap['id']; ?>"
                                        data-judul="<?php echo htmlspecialchars($lap['judul']); ?>">
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambahLaporan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-plus me-2"></i>Tambah Laporan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_laporan.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Laporan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="judul" placeholder="Masukkan judul laporan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rentang Tanggal <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="date" class="form-control" name="periode_awal" required>
                            <span class="fw-bold text-muted">s/d</span>
                            <input type="date" class="form-control" name="periode_akhir" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Laporan <span class="text-danger">*</span></label>
                        <select class="form-select" name="jenis_laporan" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="peminjaman">Peminjaman</option>
                            <option value="pengembalian">Pengembalian</option>
                            <option value="inventaris">Inventaris</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea class="form-control" name="catatan" rows="3" placeholder="Opsional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark btn-sm px-4"><i class="fa-solid fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetailLaporan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-file-lines me-2"></i>Detail Laporan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-borderless table-sm">
                    <tr><td class="fw-semibold text-muted" width="40%">Judul</td>        <td id="detail-judul">-</td></tr>
                    <tr><td class="fw-semibold text-muted">Periode</td>                  <td id="detail-periode">-</td></tr>
                    <tr><td class="fw-semibold text-muted">Jenis</td>                    <td id="detail-kategori">-</td></tr>
                    <tr><td class="fw-semibold text-muted">Tanggal Dibuat</td>           <td id="detail-dibuat">-</td></tr>
                    <tr><td class="fw-semibold text-muted">Dibuat Oleh</td>              <td id="detail-oleh">-</td></tr>
                    <tr><td class="fw-semibold text-muted">Catatan</td>                  <td id="detail-catatan">-</td></tr>
                </table>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEditLaporan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Laporan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_laporan.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Laporan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="judul" id="edit-judul" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rentang Tanggal <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="date" class="form-control" name="periode_awal" id="edit-mulai" required>
                            <span class="fw-bold text-muted">s/d</span>
                            <input type="date" class="form-control" name="periode_akhir" id="edit-akhir" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Laporan <span class="text-danger">*</span></label>
                        <select class="form-select" name="jenis_laporan" id="edit-jenis" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="peminjaman">Peminjaman</option>
                            <option value="pengembalian">Pengembalian</option>
                            <option value="inventaris">Inventaris</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea class="form-control" name="catatan" id="edit-catatan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4 text-dark fw-bold"><i class="fa-solid fa-save me-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="modalHapusLaporan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_laporan.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hapus-id">
                <div class="modal-body text-center px-4 py-3">
                    <p class="mb-1">Yakin ingin menghapus laporan:</p>
                    <p class="fw-bold" id="hapus-judul">-</p>
                    <small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4"><i class="fa-solid fa-trash me-1"></i> Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
