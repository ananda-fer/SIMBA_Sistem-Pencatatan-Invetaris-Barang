<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/koneksi.php';

hanya_role(['admin', 'staff_tu']);

// =========================================================================
// FLASH MESSAGE
// =========================================================================
$flash = '';
if (!empty($_SESSION['flash_laporan'])) {
    $flash = $_SESSION['flash_laporan'];
    unset($_SESSION['flash_laporan']);
}

// =========================================================================
// TAB AKTIF
// =========================================================================
$tab = $_GET['tab'] ?? 'pengajuan';

// =========================================================================
// DATA TAB 1: PENGAJUAN PEMINJAMAN
// =========================================================================
$f_mulai  = $_GET['mulai']  ?? '';
$f_akhir  = $_GET['akhir']  ?? '';
$f_status = $_GET['status'] ?? '';
$f_cari   = trim($_GET['cari'] ?? '');

$where  = [];
$params = [];
if ($f_mulai)  { $where[] = 'pp.tanggal_pinjam >= :mulai';               $params[':mulai']  = $f_mulai; }
if ($f_akhir)  { $where[] = 'pp.tanggal_pinjam <= :akhir';               $params[':akhir']  = $f_akhir; }
if ($f_status) { $where[] = 'pp.status = :status';                       $params[':status'] = $f_status; }
if ($f_cari)   { $where[] = 'u_peminjam.nama_lengkap LIKE :cari';        $params[':cari']   = '%' . $f_cari . '%'; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtP = $pdo->prepare("
    SELECT pp.id, pp.keperluan, pp.tanggal_pinjam, pp.tanggal_kembali,
           pp.status, pp.file_surat, pp.catatan_tolak, pp.dibuat_pada, pp.diperbarui_pada,
           u_peminjam.nama_lengkap AS nama_peminjam,
           u_peminjam.email        AS email_peminjam,
           u_staff.nama_lengkap    AS nama_staff,
           GROUP_CONCAT(b.nama    ORDER BY b.nama SEPARATOR '||') AS nama_barang_list,
           GROUP_CONCAT(dp.jumlah ORDER BY b.nama SEPARATOR '||') AS jumlah_list
    FROM pengajuan_peminjaman pp
    JOIN  users u_peminjam ON u_peminjam.id = pp.id_peminjam
    LEFT JOIN users u_staff    ON u_staff.id    = pp.disetujui_oleh
    LEFT JOIN detail_peminjaman dp ON dp.id_peminjaman = pp.id
    LEFT JOIN barang b             ON b.id = dp.id_barang
    $whereSql
    GROUP BY pp.id
    ORDER BY pp.dibuat_pada DESC
");
$stmtP->execute($params);
$dataPengajuan = $stmtP->fetchAll();

foreach ($dataPengajuan as &$row) {
    $row['surat_url'] = $row['file_surat'] ? app_url($row['file_surat']) : '';
}
unset($row);

// =========================================================================
// DATA TAB 2: ARSIP LAPORAN
// =========================================================================
$stmtL = $pdo->query("
    SELECT l.*, u.nama_lengkap AS nama_pembuat
    FROM laporan l
    JOIN users u ON u.id = l.dibuat_oleh
    ORDER BY l.dibuat_pada DESC
");
$dataLaporan = $stmtL->fetchAll();

// =========================================================================
// PAGE CONFIG
// =========================================================================
$pageTitle   = 'Laporan';
$currentPage = 'laporan';
$basePath    = '../../';
$pageCss     = ['assets/css/laporan.css'];

require_once $basePath . 'includes/header.php';
require_once $basePath . 'includes/sidebar.php';
?>
<main class="main-content">

<!-- Flash Message -->
<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show mb-3" role="alert"
     style="font-size:.88rem">
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Page Header -->
<div style="margin-bottom:20px">
    <h2 style="font-size:1.4rem;font-weight:700;color:#1e1e2d;margin:0">Laporan</h2>
    <p style="color:#6b7280;font-size:.85rem;margin:4px 0 0">Pantau pengajuan peminjaman dan kelola arsip laporan resmi</p>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" style="border-bottom:2px solid #e5e7eb">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'pengajuan' ? 'active fw-700' : '' ?>"
           href="?tab=pengajuan"
           style="font-weight:600;font-size:.88rem;color:<?= $tab==='pengajuan' ? '#1e1e2d' : '#6b7280' ?>">
            <i class="fa-solid fa-list-check me-1"></i> Data Pengajuan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'arsip' ? 'active fw-700' : '' ?>"
           href="?tab=arsip"
           style="font-weight:600;font-size:.88rem;color:<?= $tab==='arsip' ? '#1e1e2d' : '#6b7280' ?>">
            <i class="fa-solid fa-folder-open me-1"></i> Arsip Laporan
            <?php if (count($dataLaporan)): ?>
            <span class="badge rounded-pill ms-1"
                  style="background:#1e1e2d;color:#fca311;font-size:.7rem"><?= count($dataLaporan) ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<!-- ===================================================================
     TAB 1: DATA PENGAJUAN PEMINJAMAN (Read-only, auto dari DB)
=================================================================== -->
<div id="tabPengajuan" <?= $tab !== 'pengajuan' ? 'style="display:none"' : '' ?>>

    <!-- Summary Badges -->
    <?php
    $cnt_setujui = count(array_filter($dataPengajuan, fn($r) => $r['status'] === 'disetujui'));
    $cnt_tolak   = count(array_filter($dataPengajuan, fn($r) => $r['status'] === 'ditolak'));
    $cnt_aktif   = count(array_filter($dataPengajuan, fn($r) => in_array($r['status'], ['aktif','menunggu','diverifikasi'])));
    ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <span style="background:#f0f9f4;color:#1a7a4a;border:1px solid #bbf7d0;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $cnt_setujui ?> Disetujui</span>
        <span style="background:#fff5f5;color:#a33c44;border:1px solid #fecaca;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $cnt_tolak ?> Ditolak</span>
        <span style="background:#fff8ed;color:#9c7e2f;border:1px solid #fde68a;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $cnt_aktif ?> Berjalan</span>
    </div>

    <!-- Filter -->
    <div class="filter-card">
        <form method="GET">
            <input type="hidden" name="tab" value="pengajuan">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label>Dari Tanggal</label>
                    <input type="date" class="form-control" name="mulai" value="<?= htmlspecialchars($f_mulai) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label>Sampai Tanggal</label>
                    <input type="date" class="form-control" name="akhir" value="<?= htmlspecialchars($f_akhir) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label>Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <?php foreach (['menunggu','diverifikasi','disetujui','ditolak','aktif','selesai','dibatalkan'] as $s): ?>
                        <option value="<?= $s ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label>Cari Peminjam</label>
                    <input type="text" class="form-control" name="cari" value="<?= htmlspecialchars($f_cari) ?>" placeholder="Nama peminjam...">
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn-simpan-laporan">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                    </button>
                </div>
                <?php if ($f_mulai || $f_akhir || $f_status || $f_cari): ?>
                <div class="col-auto" style="display:flex;align-items:flex-end">
                    <a href="?tab=pengajuan" style="color:#ccc;font-size:.8rem;text-decoration:none;margin-top:22px">&#x2715; Reset</a>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div style="margin-bottom:10px">
        <small style="color:#6b7280">Menampilkan <strong><?= count($dataPengajuan) ?></strong> pengajuan</small>
    </div>

    <div class="table-card">
        <table class="table table-borderless">
            <thead>
                <tr>
                    <th width="4%"  class="text-center">NO</th>
                    <th width="14%">PEMINJAM</th>
                    <th width="15%">KEPERLUAN</th>
                    <th width="21%">BARANG DIPINJAM</th>
                    <th width="13%">PERIODE</th>
                    <th width="7%"  class="text-center">SURAT</th>
                    <th width="11%">DIPROSES</th>
                    <th width="9%"  class="text-center">STATUS</th>
                    <th width="6%"  class="text-center">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataPengajuan)): ?>
                    <tr class="empty-row"><td colspan="9">
                        <i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>
                        Belum ada data pengajuan<?= ($f_mulai||$f_akhir||$f_status||$f_cari) ? ' sesuai filter.' : '.' ?>
                    </td></tr>
                <?php else: foreach ($dataPengajuan as $i => $row):
                    $namaArr   = $row['nama_barang_list'] ? explode('||', $row['nama_barang_list']) : [];
                    $jumlahArr = $row['jumlah_list']      ? explode('||', $row['jumlah_list'])      : [];
                    $bagian = [];
                    foreach ($namaArr as $k => $nb) $bagian[] = htmlspecialchars($nb).' <span style="color:#555">x'.((int)($jumlahArr[$k]??1)).'</span>';
                    $barangHtml = $bagian ? implode(', ', $bagian) : '<span style="color:#aaa">—</span>';
                    $sc = ['menunggu'=>'#9c7e2f','diverifikasi'=>'#1a6ba8','disetujui'=>'#1a7a4a','ditolak'=>'#a33c44','aktif'=>'#1e1e2d','selesai'=>'#495057','dibatalkan'=>'#6c757d'][$row['status']] ?? '#6c757d';
                ?>
                <tr>
                    <td class="text-center"><?= $i+1 ?></td>
                    <td>
                        <div style="font-weight:700;color:#1e1e2d"><?= htmlspecialchars($row['nama_peminjam']) ?></div>
                        <div style="font-size:.72rem;color:#6b7280"><?= htmlspecialchars($row['email_peminjam']) ?></div>
                    </td>
                    <td style="font-size:.83rem"><?= htmlspecialchars($row['keperluan']) ?></td>
                    <td style="font-size:.78rem;line-height:1.6"><?= $barangHtml ?></td>
                    <td style="font-size:.78rem;white-space:nowrap">
                        <i class="fa-regular fa-calendar me-1" style="color:#9c7e2f"></i><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?><br>
                        <span style="color:#6b7280;padding-left:16px">s/d <?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($row['file_surat']): ?>
                        <a href="<?= htmlspecialchars($row['surat_url']) ?>" target="_blank"
                           style="background:#1e1e2d;color:#fca311;padding:4px 10px;border-radius:4px;font-size:.72rem;font-weight:700;text-decoration:none;white-space:nowrap">
                            <i class="fa-solid fa-file-pdf me-1"></i>PDF
                        </a>
                        <?php else: ?><span style="color:#aaa;font-size:.8rem">—</span><?php endif; ?>
                    </td>
                    <td style="font-size:.78rem">
                        <?php if ($row['nama_staff']): ?>
                            <div style="font-weight:600;color:#1e1e2d"><?= htmlspecialchars($row['nama_staff']) ?></div>
                            <div style="color:#6b7280"><?= date('d M Y', strtotime($row['diperbarui_pada'])) ?></div>
                        <?php else: ?><span style="color:#aaa">—</span><?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span style="background:<?= $sc ?>;color:#fff;padding:4px 8px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap">
                            <?= strtoupper($row['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn-act btn-detail"
                            onclick='bukaDetailPengajuan(<?= htmlspecialchars(json_encode($row, JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)'>
                            Detail
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===================================================================
     TAB 2: ARSIP LAPORAN (CRUD)
=================================================================== -->
<div id="tabArsip" <?= $tab !== 'arsip' ? 'style="display:none"' : '' ?>>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
        <small style="color:#6b7280">Arsip laporan resmi yang dibuat oleh admin atau staff TU</small>
        <button class="btn-tambah-laporan" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fa-solid fa-plus me-1"></i> Buat Laporan Baru
        </button>
    </div>

    <div class="table-card">
        <table class="table table-borderless">
            <thead>
                <tr>
                    <th width="5%"  class="text-center">NO</th>
                    <th width="26%">JUDUL LAPORAN</th>
                    <th width="13%">JENIS</th>
                    <th width="18%">PERIODE</th>
                    <th width="13%">DIBUAT OLEH</th>
                    <th width="10%">FILE PDF</th>
                    <th width="15%" class="text-center">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataLaporan)): ?>
                    <tr class="empty-row"><td colspan="7">
                        <i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>
                        Belum ada arsip laporan. Buat laporan pertama Anda.
                    </td></tr>
                <?php else: foreach ($dataLaporan as $i => $lap): ?>
                <tr>
                    <td class="text-center"><?= $i+1 ?></td>
                    <td>
                        <div style="font-weight:700;color:#1e1e2d"><?= htmlspecialchars($lap['judul']) ?></div>
                        <div style="font-size:.72rem;color:#6b7280"><?= date('d M Y, H:i', strtotime($lap['dibuat_pada'])) ?></div>
                    </td>
                    <td><span class="badge-kategori"><?= strtoupper($lap['jenis_laporan']) ?></span></td>
                    <td style="font-size:.8rem">
                        <?= date('d M Y', strtotime($lap['periode_awal'])) ?><br>
                        <span style="color:#6b7280">s/d <?= date('d M Y', strtotime($lap['periode_akhir'])) ?></span>
                    </td>
                    <td style="font-size:.82rem"><?= htmlspecialchars($lap['nama_pembuat']) ?></td>
                    <td>
                        <?php if ($lap['file_pdf']): ?>
                        <a href="<?= app_url($lap['file_pdf']) ?>" target="_blank"
                           style="background:#1e1e2d;color:#fca311;padding:4px 10px;border-radius:4px;font-size:.72rem;font-weight:700;text-decoration:none">
                            <i class="fa-solid fa-file-pdf me-1"></i>PDF
                        </a>
                        <?php else: ?><span style="color:#aaa;font-size:.8rem">—</span><?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <a href="detail_laporan.php?id=<?= $lap['id'] ?>" class="btn-act btn-detail">
                                <i class="fa-solid fa-eye me-1"></i>Lihat
                            </a>
                            <button class="btn-act btn-edit"
                                onclick='bukaEdit(<?= htmlspecialchars(json_encode($lap, JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)'>
                                <i class="fa-solid fa-pen me-1"></i>Edit
                            </button>
                            <button class="btn-act btn-hapus"
                                onclick='bukaHapus(<?= (int)$lap["id"] ?>, "<?= htmlspecialchars($lap['judul'], ENT_QUOTES) ?>")'>
                                <i class="fa-solid fa-trash me-1"></i>Hapus
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================================================================
     MODAL DETAIL PENGAJUAN (Tab 1)
================================================================ -->
<div class="modal fade" id="modalDetailPengajuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-file-lines me-2"></i>Detail Pengajuan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="bodyDetailPengajuan"></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================
     MODAL TAMBAH LAPORAN (Create)
================================================================ -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-plus me-2"></i>Buat Laporan Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_laporan.php" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Laporan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="judul" placeholder="Contoh: Laporan Peminjaman Mei 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Laporan <span class="text-danger">*</span></label>
                        <select class="form-select" name="jenis_laporan" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="peminjaman">Peminjaman — rekap pengajuan &amp; barang dipinjam</option>
                            <option value="pengembalian">Pengembalian — rekap barang dikembalikan &amp; kondisinya</option>
                            <option value="semua">Semua — peminjaman + pengembalian dalam satu laporan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Periode <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="date" class="form-control" name="periode_awal" required>
                            <span class="text-muted fw-bold">s/d</span>
                            <input type="date" class="form-control" name="periode_akhir" required>
                        </div>
                        <small class="text-muted">Data akan otomatis ditarik dari database sesuai periode &amp; jenis yang dipilih.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Laporan PDF <small class="text-muted">(opsional)</small></label>
                        <input type="file" class="form-control" name="file_pdf" accept=".pdf">
                        <small class="text-muted">Format PDF, maks 5 MB. Upload laporan yang sudah ditandatangani.</small>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Catatan <small class="text-muted">(opsional)</small></label>
                        <textarea class="form-control" name="catatan" rows="3" placeholder="Catatan tambahan untuk laporan ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark btn-sm px-4">
                        <i class="fa-solid fa-save me-1"></i> Simpan Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================
     MODAL EDIT LAPORAN (Update)
================================================================ -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Laporan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_laporan.php" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Laporan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="judul" id="edit-judul" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Laporan <span class="text-danger">*</span></label>
                        <select class="form-select" name="jenis_laporan" id="edit-jenis" required>
                            <option value="peminjaman">Peminjaman</option>
                            <option value="pengembalian">Pengembalian</option>
                            <option value="semua">Semua (Peminjaman + Pengembalian)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Periode <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="date" class="form-control" name="periode_awal" id="edit-mulai" required>
                            <span class="text-muted fw-bold">s/d</span>
                            <input type="date" class="form-control" name="periode_akhir" id="edit-akhir" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ganti File PDF <small class="text-muted">(kosongkan jika tidak diganti)</small></label>
                        <input type="file" class="form-control" name="file_pdf" accept=".pdf">
                        <div id="edit-pdf-info" class="mt-1" style="font-size:.82rem;color:#6b7280"></div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea class="form-control" name="catatan" id="edit-catatan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4 text-dark fw-bold">
                        <i class="fa-solid fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================
     MODAL HAPUS LAPORAN (Delete)
================================================================ -->
<div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_laporan.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hapus-id">
                <div class="modal-body text-center px-4 py-3">
                    <p class="mb-1">Hapus laporan:</p>
                    <p class="fw-bold" id="hapus-judul" style="color:#a33c44">—</p>
                    <small class="text-muted">File PDF terkait juga akan dihapus. Tindakan ini tidak dapat dibatalkan.</small>
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

</main>

<script>
// ---- Detail Pengajuan (Tab 1) ----
function bukaDetailPengajuan(row) {
    var namaArr   = row.nama_barang_list ? row.nama_barang_list.split('||') : [];
    var jumlahArr = row.jumlah_list      ? row.jumlah_list.split('||')      : [];
    var barangRows = '';
    for (var k = 0; k < namaArr.length; k++) {
        barangRows += '<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0">'
            + namaArr[k] + '</td><td style="padding:6px 0;text-align:center;font-weight:700">'
            + (jumlahArr[k]||'1') + '</td></tr>';
    }
    if (!barangRows) barangRows = '<tr><td colspan="2" style="text-align:center;color:#aaa;padding:10px 0">Tidak ada data</td></tr>';

    var sc = {menunggu:'#9c7e2f',diverifikasi:'#1a6ba8',disetujui:'#1a7a4a',ditolak:'#a33c44',aktif:'#1e1e2d',selesai:'#495057',dibatalkan:'#6c757d'}[row.status]||'#6c757d';
    var badge  = '<span style="background:'+sc+';color:#fff;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700">'+row.status.toUpperCase()+'</span>';
    var surat  = row.surat_url ? '<a href="'+row.surat_url+'" target="_blank" style="color:#1a7a4a;font-weight:700;text-decoration:none"><i class="fa-solid fa-file-pdf me-1"></i>Lihat PDF</a>' : '<span style="color:#aaa">Tidak ada</span>';
    var staff  = row.nama_staff ? '<strong>'+row.nama_staff+'</strong> <small style="color:#6b7280">('+row.diperbarui_pada.substring(0,10)+')</small>' : '<span style="color:#aaa">Belum diproses</span>';
    var catatan = (row.status==='ditolak' && row.catatan_tolak)
        ? '<div style="background:#fff5f5;border:1px solid #fecaca;border-radius:6px;padding:10px 14px;margin-top:10px;font-size:.83rem"><strong style="color:#a33c44">Alasan Penolakan:</strong> '+row.catatan_tolak+'</div>' : '';

    document.getElementById('bodyDetailPengajuan').innerHTML =
        '<div class="row g-3">'
        +'<div class="col-md-6"><div style="background:#f8f9fa;border-radius:8px;padding:16px;height:100%">'
        +'<h6 style="font-weight:700;color:#1e1e2d;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #e9ecef"><i class="fa-solid fa-circle-info me-1 text-secondary"></i> Informasi Pengajuan</h6>'
        +'<table style="width:100%;font-size:.85rem;border-collapse:collapse">'
        +'<tr><td style="color:#6b7280;padding:5px 0;width:42%">ID</td><td><strong>#'+row.id+'</strong></td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Peminjam</td><td><strong>'+row.nama_peminjam+'</strong><br><small style="color:#6b7280">'+row.email_peminjam+'</small></td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Keperluan</td><td>'+row.keperluan+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Tgl Pinjam</td><td>'+row.tanggal_pinjam+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Tgl Kembali</td><td>'+row.tanggal_kembali+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Surat</td><td>'+surat+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Status</td><td>'+badge+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Diproses</td><td>'+staff+'</td></tr>'
        +'</table>'+catatan+'</div></div>'
        +'<div class="col-md-6"><div style="background:#f8f9fa;border-radius:8px;padding:16px;height:100%">'
        +'<h6 style="font-weight:700;color:#1e1e2d;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #e9ecef"><i class="fa-solid fa-boxes-stacked me-1 text-secondary"></i> Barang Dipinjam</h6>'
        +'<table style="width:100%;font-size:.85rem;border-collapse:collapse">'
        +'<thead><tr style="border-bottom:2px solid #dee2e6"><th style="padding:6px 0;color:#6b7280;font-size:.75rem;text-transform:uppercase">Nama Barang</th><th style="padding:6px 0;color:#6b7280;font-size:.75rem;text-transform:uppercase;text-align:center">Jml</th></tr></thead>'
        +'<tbody>'+barangRows+'</tbody></table>'
        +'</div></div></div>';

    new bootstrap.Modal(document.getElementById('modalDetailPengajuan')).show();
}

// ---- Edit Laporan (Tab 2) ----
function bukaEdit(lap) {
    document.getElementById('edit-id').value      = lap.id;
    document.getElementById('edit-judul').value   = lap.judul;
    document.getElementById('edit-jenis').value   = lap.jenis_laporan;
    document.getElementById('edit-mulai').value   = lap.periode_awal;
    document.getElementById('edit-akhir').value   = lap.periode_akhir;
    document.getElementById('edit-catatan').value = lap.catatan || '';
    document.getElementById('edit-pdf-info').innerHTML = lap.file_pdf
        ? 'File saat ini: <strong>' + lap.file_pdf.split('/').pop() + '</strong>'
        : 'Belum ada file PDF.';
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// ---- Hapus Laporan (Tab 2) ----
function bukaHapus(id, judul) {
    document.getElementById('hapus-id').value        = id;
    document.getElementById('hapus-judul').textContent = judul;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
