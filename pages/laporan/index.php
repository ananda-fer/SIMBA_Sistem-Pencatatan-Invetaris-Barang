<?php
// =====================
// Page Configuration
// =====================
$pageTitle   = 'Pusat Laporan & Arsip';
$currentPage = 'laporan';
$basePath    = '../../'; // Relative path back to project root

// Extra inline styles specifically for this page
$extraStyles = '
<style>
    /* =====================
       FILTER CARD
    ===================== */
    .filter-card {
        background-color: #8c8c8c;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
    }
    .filter-card label {
        font-weight: 500;
        margin-bottom: 6px;
        color: #f1f1f1;
        font-size: 0.82rem;
        display: block;
    }
    .filter-card .form-control,
    .filter-card .form-select {
        background-color: #2b2b2b;
        border: 1px solid #3a3a3a;
        color: #fff;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 0.83rem;
    }
    .filter-card .form-control::-webkit-calendar-picker-indicator {
        filter: invert(0.7);
    }
    .filter-card .form-control::placeholder { color: #888; }
    .filter-card .form-control:focus,
    .filter-card .form-select:focus {
        background-color: #333;
        color: #fff;
        box-shadow: none;
        border-color: #fca311;
    }
    .filter-card .form-select option { background-color: #2b2b2b; }
    .filter-divider {
        color: #ccc;
        font-size: 0.8rem;
        padding-top: 34px;
        text-align: center;
    }
    .btn-simpan-laporan {
        background-color: #fff;
        color: #212529;
        font-weight: 700;
        border: none;
        padding: 9px 18px;
        border-radius: 6px;
        font-size: 0.85rem;
        white-space: nowrap;
        transition: background 0.2s;
        margin-top: 22px;
        width: 100%;
    }
    .btn-simpan-laporan:hover { background-color: #e9ecef; }

    /* =====================
       DATA TABLE
    ===================== */
    .table-card {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .table { margin-bottom: 0; }
    .table thead th {
        background-color: #1e1e2d;
        color: #fff;
        font-weight: 600;
        border: none;
        padding: 14px 18px;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.6px;
        white-space: nowrap;
    }
    .table tbody tr {
        background-color: #b0b4b8;
        border-bottom: 2px solid #d0d3d6;
        transition: background 0.2s;
    }
    .table tbody tr:nth-child(even) { background-color: #a8acb0; }
    .table tbody tr:hover { background-color: #9ea3a8; }
    .table tbody tr:last-child { border-bottom: none; }
    .table tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        color: #1e1e2d;
        font-weight: 600;
        border: none;
        font-size: 0.83rem;
    }

    /* =====================
       ACTION BUTTONS
    ===================== */
    .btn-act {
        border-radius: 20px;
        padding: 4px 13px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #fff;
        border: none;
        text-decoration: none;
        display: inline-block;
        transition: opacity 0.2s, transform 0.15s;
        cursor: pointer;
    }
    .btn-act:hover { opacity: 0.85; color: #fff; transform: translateY(-1px); }
    .btn-detail { background-color: #6c757d; }
    .btn-edit   { background-color: #9c7e2f; }
    .btn-cetak  { background-color: #1a7a4a; }
    .btn-hapus  { background-color: #a33c44; }

    /* =====================
       MODALS
    ===================== */
    .modal-header-dark {
        background-color: #1e1e2d;
        color: #fff;
        border-bottom: none;
    }
    .modal-header-dark .btn-close { filter: invert(1); }
    .modal-content { border: none; border-radius: 12px; overflow: hidden; }
    .badge-kategori {
        background-color: #1e1e2d;
        color: #fca311;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
    }

    /* Add Report button */
    .btn-tambah-laporan {
        background-color: #1e1e2d;
        color: #fca311;
        font-weight: 700;
        border: none;
        padding: 9px 20px;
        border-radius: 8px;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s, color 0.2s;
    }
    .btn-tambah-laporan:hover { background-color: #2b2d42; color: #fca311; }
</style>
';

// Include header (HTML head + opening body)
require_once $basePath . 'includes/header.php';
// Include navbar (sidebar + top bar + opening .page-content div)
require_once $basePath . 'includes/navbar.php';
?>

<!-- =====================
     FILTER CARD
===================== -->
<div class="filter-card">
    <form method="GET" action="">
        <div class="row g-3 align-items-end">

            <!-- Rentang Tanggal -->
            <div class="col-12 col-md-4">
                <label>Rentang Tanggal</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="date" class="form-control" name="tanggal_mulai"
                           value="<?php echo isset($_GET['tanggal_mulai']) ? htmlspecialchars($_GET['tanggal_mulai']) : ''; ?>">
                    <span class="text-white fw-bold">s/d</span>
                    <input type="date" class="form-control" name="tanggal_akhir"
                           value="<?php echo isset($_GET['tanggal_akhir']) ? htmlspecialchars($_GET['tanggal_akhir']) : ''; ?>">
                </div>
            </div>

            <!-- Kategori -->
            <div class="col-12 col-md-3">
                <label>Kategori</label>
                <select class="form-select" name="kategori">
                    <option value="">Semua Kategori</option>
                    <option value="SEMUA PEMINJAM" <?php echo (isset($_GET['kategori']) && $_GET['kategori'] === 'SEMUA PEMINJAM') ? 'selected' : ''; ?>>Semua Peminjam</option>
                    <option value="PEMINJAMAN"    <?php echo (isset($_GET['kategori']) && $_GET['kategori'] === 'PEMINJAMAN') ? 'selected' : ''; ?>>Peminjaman</option>
                    <option value="PENGEMBALIAN"  <?php echo (isset($_GET['kategori']) && $_GET['kategori'] === 'PENGEMBALIAN') ? 'selected' : ''; ?>>Pengembalian</option>
                    <option value="KERUSAKAN"     <?php echo (isset($_GET['kategori']) && $_GET['kategori'] === 'KERUSAKAN') ? 'selected' : ''; ?>>Kerusakan</option>
                </select>
            </div>

            <!-- Judul Laporan -->
            <div class="col-12 col-md-3">
                <label>Judul Laporan</label>
                <input type="text" class="form-control" name="judul"
                       placeholder="Cari judul laporan..."
                       value="<?php echo isset($_GET['judul']) ? htmlspecialchars($_GET['judul']) : ''; ?>">
            </div>

            <!-- Submit -->
            <div class="col-12 col-md-2">
                <button type="submit" class="btn-simpan-laporan">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Simpan Laporan
                </button>
            </div>

        </div>
    </form>
</div>

<!-- =====================
     TABLE HEADER ACTION
===================== -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <small class="text-muted">Menampilkan semua data laporan</small>
    <a href="#" class="btn-tambah-laporan" data-bs-toggle="modal" data-bs-target="#modalTambahLaporan">
        <i class="fa-solid fa-plus"></i> Tambah Laporan
    </a>
</div>

<!-- =====================
     DATA TABLE
===================== -->
<div class="table-card">
    <table class="table table-borderless">
        <thead>
            <tr>
                <th width="5%"  class="text-center">NO</th>
                <th width="25%">JUDUL LAPORAN</th>
                <th width="20%" class="text-center">PERIODE</th>
                <th width="15%" class="text-center">KATEGORI</th>
                <th width="15%" class="text-center">TANGGAL DIBUAT</th>
                <th width="20%" class="text-center">AKSI</th>
            </tr>
        </thead>
        <tbody>
            <?php
            /**
             * =====================================================
             * TODO: Ganti bagian ini dengan query database nyata.
             * Contoh menggunakan PDO atau MySQLi dari config.php
             * =====================================================
             *
             * Contoh data dummy untuk tampilan sementara:
             */
            $laporanList = [
                [
                    'id'             => 1,
                    'judul'          => 'Proyektor Meledak',
                    'tanggal_mulai'  => '2026-06-02',
                    'tanggal_akhir'  => '2026-06-30',
                    'kategori'       => 'SEMUA PEMINJAM',
                    'tanggal_dibuat' => '2026-06-02',
                ],
                [
                    'id'             => 2,
                    'judul'          => 'Laporan Peminjaman Laptop',
                    'tanggal_mulai'  => '2026-05-01',
                    'tanggal_akhir'  => '2026-05-31',
                    'kategori'       => 'PEMINJAMAN',
                    'tanggal_dibuat' => '2026-06-01',
                ],
            ];

            if (empty($laporanList)) : ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>
                        Belum ada data laporan.
                    </td>
                </tr>
            <?php else :
                foreach ($laporanList as $no => $laporan) :
                    $periode = date('d M Y', strtotime($laporan['tanggal_mulai']))
                             . ' - '
                             . date('d M Y', strtotime($laporan['tanggal_akhir']));
            ?>
                <tr>
                    <td class="text-center"><?php echo $no + 1; ?></td>
                    <td><?php echo htmlspecialchars($laporan['judul']); ?></td>
                    <td class="text-center"><?php echo $periode; ?></td>
                    <td class="text-center">
                        <span class="badge-kategori"><?php echo htmlspecialchars($laporan['kategori']); ?></span>
                    </td>
                    <td class="text-center"><?php echo date('d M Y', strtotime($laporan['tanggal_dibuat'])); ?></td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center align-items-center gap-1">
                        <!-- Detail -->
                        <button class="btn-act btn-detail"
                                data-bs-toggle="modal"
                                data-bs-target="#modalDetailLaporan"
                                data-id="<?php echo $laporan['id']; ?>"
                                data-judul="<?php echo htmlspecialchars($laporan['judul']); ?>"
                                data-periode="<?php echo $periode; ?>"
                                data-kategori="<?php echo htmlspecialchars($laporan['kategori']); ?>"
                                data-dibuat="<?php echo date('d M Y', strtotime($laporan['tanggal_dibuat'])); ?>">
                            Detail
                        </button>
                        <!-- Edit -->
                        <button class="btn-act btn-edit"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEditLaporan"
                                data-id="<?php echo $laporan['id']; ?>"
                                data-judul="<?php echo htmlspecialchars($laporan['judul']); ?>"
                                data-mulai="<?php echo $laporan['tanggal_mulai']; ?>"
                                data-akhir="<?php echo $laporan['tanggal_akhir']; ?>"
                                data-kategori="<?php echo htmlspecialchars($laporan['kategori']); ?>">
                            Edit
                        </button>
                        <!-- Cetak -->
                        <a href="cetak.php?id=<?php echo $laporan['id']; ?>" class="btn-act btn-cetak" target="_blank">
                            Cetak
                        </a>
                        <!-- Hapus -->
                        <button class="btn-act btn-hapus"
                                data-bs-toggle="modal"
                                data-bs-target="#modalHapusLaporan"
                                data-id="<?php echo $laporan['id']; ?>"
                                data-judul="<?php echo htmlspecialchars($laporan['judul']); ?>">
                            Hapus
                        </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>


<!-- =====================================================================
     MODAL: TAMBAH LAPORAN
===================================================================== -->
<div class="modal fade" id="modalTambahLaporan" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="modalTambahLabel">
                    <i class="fa-solid fa-plus me-2"></i>Tambah Laporan Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <input type="date" class="form-control" name="tanggal_mulai" required>
                            <span class="fw-bold text-muted">s/d</span>
                            <input type="date" class="form-control" name="tanggal_akhir" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" name="kategori" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="SEMUA PEMINJAM">Semua Peminjam</option>
                            <option value="PEMINJAMAN">Peminjaman</option>
                            <option value="PENGEMBALIAN">Pengembalian</option>
                            <option value="KERUSAKAN">Kerusakan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark btn-sm px-4">
                        <i class="fa-solid fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: DETAIL LAPORAN
===================================================================== -->
<div class="modal fade" id="modalDetailLaporan" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="modalDetailLabel">
                    <i class="fa-solid fa-file-lines me-2"></i>Detail Laporan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="fw-semibold text-muted" width="40%">Judul Laporan</td>
                        <td id="detail-judul">-</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold text-muted">Periode</td>
                        <td id="detail-periode">-</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold text-muted">Kategori</td>
                        <td id="detail-kategori">-</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold text-muted">Tanggal Dibuat</td>
                        <td id="detail-dibuat">-</td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: EDIT LAPORAN
===================================================================== -->
<div class="modal fade" id="modalEditLaporan" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="modalEditLabel">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Laporan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <input type="date" class="form-control" name="tanggal_mulai" id="edit-mulai" required>
                            <span class="fw-bold text-muted">s/d</span>
                            <input type="date" class="form-control" name="tanggal_akhir" id="edit-akhir" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" name="kategori" id="edit-kategori" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="SEMUA PEMINJAM">Semua Peminjam</option>
                            <option value="PEMINJAMAN">Peminjaman</option>
                            <option value="PENGEMBALIAN">Pengembalian</option>
                            <option value="KERUSAKAN">Kerusakan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4 text-dark fw-bold">
                        <i class="fa-solid fa-save me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: HAPUS LAPORAN
===================================================================== -->
<div class="modal fade" id="modalHapusLaporan" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="modalHapusLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <button type="submit" class="btn btn-danger btn-sm px-4">
                        <i class="fa-solid fa-trash me-1"></i> Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
// Page-specific JavaScript
$extraScripts = '
<script>
// =====================================================================
// DETAIL MODAL — isi data dari data-attribute tombol
// =====================================================================
document.getElementById("modalDetailLaporan").addEventListener("show.bs.modal", function (event) {
    var btn = event.relatedTarget;
    document.getElementById("detail-judul").textContent   = btn.getAttribute("data-judul");
    document.getElementById("detail-periode").textContent = btn.getAttribute("data-periode");
    document.getElementById("detail-kategori").textContent= btn.getAttribute("data-kategori");
    document.getElementById("detail-dibuat").textContent  = btn.getAttribute("data-dibuat");
});

// =====================================================================
// EDIT MODAL — isi form dari data-attribute tombol
// =====================================================================
document.getElementById("modalEditLaporan").addEventListener("show.bs.modal", function (event) {
    var btn = event.relatedTarget;
    document.getElementById("edit-id").value      = btn.getAttribute("data-id");
    document.getElementById("edit-judul").value   = btn.getAttribute("data-judul");
    document.getElementById("edit-mulai").value   = btn.getAttribute("data-mulai");
    document.getElementById("edit-akhir").value   = btn.getAttribute("data-akhir");

    var kategoriVal = btn.getAttribute("data-kategori");
    var sel = document.getElementById("edit-kategori");
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === kategoriVal) { sel.selectedIndex = i; break; }
    }
});

// =====================================================================
// HAPUS MODAL — konfirmasi hapus
// =====================================================================
document.getElementById("modalHapusLaporan").addEventListener("show.bs.modal", function (event) {
    var btn = event.relatedTarget;
    document.getElementById("hapus-id").value       = btn.getAttribute("data-id");
    document.getElementById("hapus-judul").textContent = btn.getAttribute("data-judul");
});
</script>
';

// Include footer (closes .page-content, .main-wrapper, loads Bootstrap JS)
require_once $basePath . 'includes/footer.php';
?>
