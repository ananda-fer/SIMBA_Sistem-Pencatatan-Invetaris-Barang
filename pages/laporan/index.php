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
$typesP = "";
$params = [];
if ($f_mulai)  { $where[] = 'pp.tanggal_pinjam >= ?'; $typesP .= "s"; $params[] = $f_mulai; }
if ($f_akhir)  { $where[] = 'pp.tanggal_pinjam <= ?'; $typesP .= "s"; $params[] = $f_akhir; }
if ($f_status) { $where[] = 'pp.status = ?';          $typesP .= "s"; $params[] = $f_status; }
if ($f_cari)   { $where[] = 'u_peminjam.nama_lengkap LIKE ?'; $typesP .= "s"; $params[] = '%' . $f_cari . '%'; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtP = $conn->prepare("
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
if ($typesP) { $stmtP->bind_param($typesP, ...$params); }
$stmtP->execute();
$dataPengajuan = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($dataPengajuan as &$row) {
    $row['surat_url'] = $row['file_surat'] ? app_url($row['file_surat']) : '';
}
unset($row);

// =========================================================================
// DATA TAB 2: PENGEMBALIAN
// =========================================================================
$fp_mulai   = $_GET['pen_mulai']   ?? '';
$fp_akhir   = $_GET['pen_akhir']   ?? '';
$fp_kondisi = $_GET['pen_kondisi'] ?? '';
$fp_cari    = trim($_GET['pen_cari'] ?? '');

$whereP  = [];
$typesPen = "";
$paramsP = [];
if ($fp_mulai)   { $whereP[] = 'pen.tanggal_kembali >= ?'; $typesPen .= "s"; $paramsP[] = $fp_mulai; }
if ($fp_akhir)   { $whereP[] = 'pen.tanggal_kembali <= ?'; $typesPen .= "s"; $paramsP[] = $fp_akhir; }
if ($fp_kondisi) { $whereP[] = 'pen.kondisi_kembali = ?';  $typesPen .= "s"; $paramsP[] = $fp_kondisi; }
if ($fp_cari)    { $whereP[] = 'u_p.nama_lengkap LIKE ?';  $typesPen .= "s"; $paramsP[] = '%' . $fp_cari . '%'; }

$whereSqlP = $whereP ? 'WHERE ' . implode(' AND ', $whereP) : '';

$stmtPen = $conn->prepare("
    SELECT pen.id, pen.tanggal_kembali, pen.hari_terlambat,
           pen.kondisi_kembali, pen.catatan_kerusakan, pen.dibuat_pada,
           pp.keperluan, pp.tanggal_pinjam, pp.tanggal_kembali AS rencana_kembali,
           u_p.nama_lengkap AS nama_peminjam,
           u_p.email        AS email_peminjam,
           u_t.nama_lengkap AS nama_penerima,
           GROUP_CONCAT(b.nama    ORDER BY b.nama SEPARATOR '||') AS nama_barang_list,
           GROUP_CONCAT(dp.jumlah ORDER BY b.nama SEPARATOR '||') AS jumlah_list
    FROM pengembalian pen
    JOIN pengajuan_peminjaman pp ON pp.id = pen.id_peminjaman
    JOIN users u_p ON u_p.id = pp.id_peminjam
    JOIN users u_t ON u_t.id = pen.diterima_oleh
    LEFT JOIN detail_peminjaman dp ON dp.id_peminjaman = pp.id
    LEFT JOIN barang b ON b.id = dp.id_barang
    $whereSqlP
    GROUP BY pen.id
    ORDER BY pen.tanggal_kembali DESC
");
if ($typesPen) { $stmtPen->bind_param($typesPen, ...$paramsP); }
$stmtPen->execute();
$dataPengembalian = $stmtPen->get_result()->fetch_all(MYSQLI_ASSOC);

// =========================================================================
// DATA TAB 3: BARANG
// =========================================================================
$fb_kondisi = $_GET['brg_kondisi'] ?? '';
$fb_status  = $_GET['brg_status']  ?? '';
$fb_cari    = trim($_GET['brg_cari'] ?? '');

$whereB  = ['1=1'];
$typesB  = "";
$paramsB = [];
if ($fb_kondisi) { $whereB[] = 'b.kondisi = ?'; $typesB .= "s"; $paramsB[] = $fb_kondisi; }
if ($fb_status)  { $whereB[] = 'b.status  = ?'; $typesB .= "s"; $paramsB[] = $fb_status; }
if ($fb_cari)    { $whereB[] = '(b.nama LIKE ? OR b.kode_barang LIKE ?)';
                   $typesB .= "ss";
                   $brg_cari_like = '%'.$fb_cari.'%';
                   $paramsB[] = $brg_cari_like;
                   $paramsB[] = $brg_cari_like; }

$whereSqlB = implode(' AND ', $whereB);

$stmtB = $conn->prepare("
    SELECT b.id, b.kode_barang, b.nama, b.stok_total, b.stok_tersedia,
           b.kondisi, b.status, b.dibuat_pada,
           k.nama AS nama_kategori,
           COUNT(DISTINCT dp.id_peminjaman) AS total_dipinjam
    FROM barang b
    LEFT JOIN kategori k ON k.id = b.kategori_id
    LEFT JOIN detail_peminjaman dp ON dp.id_barang = b.id
    WHERE $whereSqlB
    GROUP BY b.id
    ORDER BY b.nama ASC
");
if ($typesB) { $stmtB->bind_param($typesB, ...$paramsB); }
$stmtB->execute();
$dataBarang = $stmtB->get_result()->fetch_all(MYSQLI_ASSOC);

$kategoriList = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);

// =========================================================================
// DATA TAB 4: ARSIP LAPORAN
// =========================================================================
$dataLaporan = $conn->query("
    SELECT l.*, u.nama_lengkap AS nama_pembuat
    FROM laporan l
    JOIN users u ON u.id = l.dibuat_oleh
    ORDER BY l.dibuat_pada DESC
")->fetch_all(MYSQLI_ASSOC);

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
<div class="status-tabs">
    <a href="?tab=pengajuan" class="status-tab <?= $tab === 'pengajuan' ? 'active' : '' ?>">
        Data Pengajuan
        <?php if (count($dataPengajuan)): ?>
        <span class="status-count"><?= count($dataPengajuan) ?></span>
        <?php endif; ?>
    </a>
    <a href="?tab=pengembalian" class="status-tab <?= $tab === 'pengembalian' ? 'active' : '' ?>">
        Data Pengembalian
        <?php if (count($dataPengembalian)): ?>
        <span class="status-count"><?= count($dataPengembalian) ?></span>
        <?php endif; ?>
    </a>
    <a href="?tab=barang" class="status-tab <?= $tab === 'barang' ? 'active' : '' ?>">
        Data Barang
        <?php if (count($dataBarang)): ?>
        <span class="status-count"><?= count($dataBarang) ?></span>
        <?php endif; ?>
    </a>
    <a href="?tab=arsip" class="status-tab <?= $tab === 'arsip' ? 'active' : '' ?>">
        Arsip Laporan
        <?php if (count($dataLaporan)): ?>
        <span class="status-count"><?= count($dataLaporan) ?></span>
        <?php endif; ?>
    </a>
</div>

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
            <div class="filter-row">
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px">
                    <label>Dari Tanggal</label>
                    <input type="date" class="form-input" name="mulai" value="<?= htmlspecialchars($f_mulai) ?>">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px">
                    <label>Sampai Tanggal</label>
                    <input type="date" class="form-input" name="akhir" value="<?= htmlspecialchars($f_akhir) ?>">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px">
                    <label>Status</label>
                    <select class="form-input" name="status">
                        <option value="">Semua Status</option>
                        <?php foreach (['menunggu','diverifikasi','disetujui','ditolak','aktif','selesai','dibatalkan'] as $s): ?>
                        <option value="<?= $s ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:2;min-width:180px">
                    <label>Cari Peminjam</label>
                    <input type="text" class="form-input" name="cari" value="<?= htmlspecialchars($f_cari) ?>" placeholder="Nama peminjam...">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if ($f_mulai || $f_akhir || $f_status || $f_cari): ?>
                    <a href="?tab=pengajuan" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div style="margin-bottom:10px">
        <small style="color:#6b7280">Menampilkan <strong><?= count($dataPengajuan) ?></strong> pengajuan</small>
    </div>

    <div class="table-card">
        <div class="table-wrapper">
        <table class="table-modern">
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
                <?php else:
                $statusCfgP = [
                    'menunggu'     => ['bg'=>'#fef9c3','fg'=>'#854d0e'],
                    'diverifikasi' => ['bg'=>'#dbeafe','fg'=>'#1d4ed8'],
                    'disetujui'    => ['bg'=>'#dcfce7','fg'=>'#166534'],
                    'ditolak'      => ['bg'=>'#fee2e2','fg'=>'#991b1b'],
                    'aktif'        => ['bg'=>'#e0f2fe','fg'=>'#0369a1'],
                    'selesai'      => ['bg'=>'#d1fae5','fg'=>'#065f46'],
                    'dibatalkan'   => ['bg'=>'#f3f4f6','fg'=>'#6b7280'],
                ];
                foreach ($dataPengajuan as $i => $row):
                    $namaArr   = $row['nama_barang_list'] ? explode('||', $row['nama_barang_list']) : [];
                    $jumlahArr = $row['jumlah_list']      ? explode('||', $row['jumlah_list'])      : [];
                    $bagian = [];
                    foreach ($namaArr as $k => $nb) $bagian[] = htmlspecialchars($nb).' <span style="color:#555">x'.((int)($jumlahArr[$k]??1)).'</span>';
                    $barangHtml = $bagian ? implode(', ', $bagian) : '<span style="color:#aaa">—</span>';
                    $sc = $statusCfgP[$row['status']] ?? ['bg'=>'#f3f4f6','fg'=>'#6b7280'];
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
                        <span style="background:<?= $sc['bg'] ?>;color:<?= $sc['fg'] ?>;padding:4px 8px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap">
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
</div>

<!-- ===================================================================
     TAB 2: DATA PENGEMBALIAN (Read-only, auto dari DB)
=================================================================== -->
<div id="tabPengembalian" <?= $tab !== 'pengembalian' ? 'style="display:none"' : '' ?>>

    <!-- Summary Badges -->
    <?php
    $pen_baik    = count(array_filter($dataPengembalian, fn($r) => $r['kondisi_kembali'] === 'baik'));
    $pen_ringan  = count(array_filter($dataPengembalian, fn($r) => $r['kondisi_kembali'] === 'rusak_ringan'));
    $pen_berat   = count(array_filter($dataPengembalian, fn($r) => $r['kondisi_kembali'] === 'rusak_berat'));
    $pen_lambat  = count(array_filter($dataPengembalian, fn($r) => (int)$r['hari_terlambat'] > 0));
    ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <span style="background:#f0f9f4;color:#1a7a4a;border:1px solid #bbf7d0;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $pen_baik ?> Kondisi Baik</span>
        <span style="background:#fff8ed;color:#9c7e2f;border:1px solid #fde68a;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $pen_ringan ?> Rusak Ringan</span>
        <span style="background:#fff5f5;color:#a33c44;border:1px solid #fecaca;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $pen_berat ?> Rusak Berat</span>
        <span style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $pen_lambat ?> Terlambat</span>
    </div>

    <!-- Filter -->
    <div class="filter-card">
        <form method="GET">
            <input type="hidden" name="tab" value="pengembalian">
            <div class="filter-row">
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px">
                    <label>Dari Tanggal</label>
                    <input type="date" class="form-input" name="pen_mulai" value="<?= htmlspecialchars($fp_mulai) ?>">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px">
                    <label>Sampai Tanggal</label>
                    <input type="date" class="form-input" name="pen_akhir" value="<?= htmlspecialchars($fp_akhir) ?>">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px">
                    <label>Kondisi</label>
                    <select class="form-input" name="pen_kondisi">
                        <option value="">Semua Kondisi</option>
                        <option value="baik"        <?= $fp_kondisi==='baik'         ? 'selected':'' ?>>Baik</option>
                        <option value="rusak_ringan" <?= $fp_kondisi==='rusak_ringan' ? 'selected':'' ?>>Rusak Ringan</option>
                        <option value="rusak_berat"  <?= $fp_kondisi==='rusak_berat'  ? 'selected':'' ?>>Rusak Berat</option>
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:2;min-width:180px">
                    <label>Cari Peminjam</label>
                    <input type="text" class="form-input" name="pen_cari" value="<?= htmlspecialchars($fp_cari) ?>" placeholder="Nama peminjam...">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if ($fp_mulai || $fp_akhir || $fp_kondisi || $fp_cari): ?>
                    <a href="?tab=pengembalian" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div style="margin-bottom:10px">
        <small style="color:#6b7280">Menampilkan <strong><?= count($dataPengembalian) ?></strong> data pengembalian</small>
    </div>

    <div class="table-card">
        <div class="table-wrapper">
        <table class="table-modern">
            <thead>
                <tr>
                    <th width="4%"  class="text-center">NO</th>
                    <th width="14%">PEMINJAM</th>
                    <th width="15%">KEPERLUAN</th>
                    <th width="20%">BARANG DIKEMBALIKAN</th>
                    <th width="12%">TGL KEMBALI</th>
                    <th width="9%"  class="text-center">TERLAMBAT</th>
                    <th width="11%">KONDISI</th>
                    <th width="10%">DITERIMA OLEH</th>
                    <th width="5%"  class="text-center">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataPengembalian)): ?>
                    <tr class="empty-row"><td colspan="9">
                        <i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>
                        Belum ada data pengembalian<?= ($fp_mulai||$fp_akhir||$fp_kondisi||$fp_cari) ? ' sesuai filter.' : '.' ?>
                    </td></tr>
                <?php else: foreach ($dataPengembalian as $i => $row):
                    $namaArr   = $row['nama_barang_list'] ? explode('||', $row['nama_barang_list']) : [];
                    $jumlahArr = $row['jumlah_list']      ? explode('||', $row['jumlah_list'])      : [];
                    $bagian = [];
                    foreach ($namaArr as $k => $nb) $bagian[] = htmlspecialchars($nb).' <span style="color:#555">x'.((int)($jumlahArr[$k]??1)).'</span>';
                    $barangHtml = $bagian ? implode(', ', $bagian) : '<span style="color:#aaa">—</span>';
                    $kondisiColor = ['baik'=>'#1a7a4a','rusak_ringan'=>'#9c7e2f','rusak_berat'=>'#a33c44'][$row['kondisi_kembali']] ?? '#6c757d';
                    $kondisiBg    = ['baik'=>'#f0f9f4','rusak_ringan'=>'#fff8ed','rusak_berat'=>'#fff5f5'][$row['kondisi_kembali']] ?? '#f3f4f6';
                    $kondisiLabel = ['baik'=>'Baik','rusak_ringan'=>'Rusak Ringan','rusak_berat'=>'Rusak Berat'][$row['kondisi_kembali']] ?? $row['kondisi_kembali'];
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
                        <strong><?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></strong><br>
                        <span style="color:#6b7280">Rencana: <?= date('d M Y', strtotime($row['rencana_kembali'])) ?></span>
                    </td>
                    <td class="text-center">
                        <?php $h = (int)$row['hari_terlambat']; ?>
                        <?php if ($h > 0): ?>
                        <span style="background:#fee2e2;color:#a33c44;padding:3px 8px;border-radius:20px;font-size:.7rem;font-weight:700"><?= $h ?> hari</span>
                        <?php else: ?>
                        <span style="background:#f0f9f4;color:#1a7a4a;padding:3px 8px;border-radius:20px;font-size:.7rem;font-weight:700">Tepat</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="background:<?= $kondisiBg ?>;color:<?= $kondisiColor ?>;border:1px solid <?= $kondisiColor ?>40;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap">
                            <?= $kondisiLabel ?>
                        </span>
                    </td>
                    <td style="font-size:.78rem"><?= htmlspecialchars($row['nama_penerima']) ?></td>
                    <td class="text-center">
                        <button class="btn-act btn-detail"
                            onclick='bukaDetailPengembalian(<?= htmlspecialchars(json_encode($row, JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)'>
                            Detail
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ===================================================================
     TAB 3: DATA BARANG (Read-only, rekap inventaris)
=================================================================== -->
<div id="tabBarang" <?= $tab !== 'barang' ? 'style="display:none"' : '' ?>>

    <!-- Summary Badges -->
    <?php
    $brg_tersedia   = count(array_filter($dataBarang, fn($r) => $r['status'] === 'tersedia'));
    $brg_dipinjam   = count(array_filter($dataBarang, fn($r) => $r['status'] === 'dipinjam'));
    $brg_perbaikan  = count(array_filter($dataBarang, fn($r) => $r['status'] === 'dalam_perbaikan'));
    $brg_baik       = count(array_filter($dataBarang, fn($r) => $r['kondisi'] === 'baik'));
    $brg_rusak      = count(array_filter($dataBarang, fn($r) => in_array($r['kondisi'], ['rusak_ringan','rusak_berat'])));
    ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <span style="background:#f0f9f4;color:#1a7a4a;border:1px solid #bbf7d0;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $brg_tersedia ?> Tersedia</span>
        <span style="background:#eef2ff;color:#4361ee;border:1px solid #c7d2fe;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $brg_dipinjam ?> Sedang Dipinjam</span>
        <span style="background:#fff5f5;color:#a33c44;border:1px solid #fecaca;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $brg_perbaikan ?> Dalam Perbaikan</span>
        <span style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:6px;padding:5px 12px;font-size:.78rem;font-weight:700"><?= $brg_baik ?> Kondisi Baik &nbsp;|&nbsp; <?= $brg_rusak ?> Rusak</span>
    </div>

    <!-- Filter -->
    <div class="filter-card">
        <form method="GET">
            <input type="hidden" name="tab" value="barang">
            <div class="filter-row">
                <div style="display:flex;flex-direction:column;gap:5px;flex:2;min-width:180px">
                    <label>Cari Barang</label>
                    <input type="text" class="form-input" name="brg_cari" value="<?= htmlspecialchars($fb_cari) ?>" placeholder="Nama atau kode barang...">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px">
                    <label>Kondisi</label>
                    <select class="form-input" name="brg_kondisi">
                        <option value="">Semua Kondisi</option>
                        <option value="baik"         <?= $fb_kondisi==='baik'         ? 'selected':'' ?>>Baik</option>
                        <option value="rusak_ringan"  <?= $fb_kondisi==='rusak_ringan'  ? 'selected':'' ?>>Rusak Ringan</option>
                        <option value="rusak_berat"   <?= $fb_kondisi==='rusak_berat'   ? 'selected':'' ?>>Rusak Berat</option>
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px">
                    <label>Status</label>
                    <select class="form-input" name="brg_status">
                        <option value="">Semua Status</option>
                        <option value="tersedia"       <?= $fb_status==='tersedia'       ? 'selected':'' ?>>Tersedia</option>
                        <option value="dipinjam"       <?= $fb_status==='dipinjam'       ? 'selected':'' ?>>Dipinjam</option>
                        <option value="dalam_perbaikan"<?= $fb_status==='dalam_perbaikan'? 'selected':'' ?>>Dalam Perbaikan</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if ($fb_kondisi || $fb_status || $fb_cari): ?>
                    <a href="?tab=barang" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div style="margin-bottom:10px">
        <small style="color:#6b7280">Menampilkan <strong><?= count($dataBarang) ?></strong> barang</small>
    </div>

    <div class="table-card">
        <div class="table-wrapper">
        <table class="table-modern">
            <thead>
                <tr>
                    <th width="4%"  class="text-center">NO</th>
                    <th width="10%">KODE</th>
                    <th width="22%">NAMA BARANG</th>
                    <th width="13%">KATEGORI</th>
                    <th width="13%">STOK</th>
                    <th width="13%">KONDISI</th>
                    <th width="13%">STATUS</th>
                    <th width="12%" class="text-center">TOTAL DIPINJAM</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataBarang)): ?>
                    <tr class="empty-row"><td colspan="8">
                        <i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>
                        Belum ada data barang<?= ($fb_kondisi||$fb_status||$fb_cari) ? ' sesuai filter.' : '.' ?>
                    </td></tr>
                <?php else: foreach ($dataBarang as $i => $row):
                    $kondisiColor = ['baik'=>'#1a7a4a','rusak_ringan'=>'#9c7e2f','rusak_berat'=>'#a33c44'][$row['kondisi']] ?? '#6c757d';
                    $kondisiBg    = ['baik'=>'#f0f9f4','rusak_ringan'=>'#fff8ed','rusak_berat'=>'#fff5f5'][$row['kondisi']] ?? '#f3f4f6';
                    $kondisiLabel = ['baik'=>'Baik','rusak_ringan'=>'Rusak Ringan','rusak_berat'=>'Rusak Berat'][$row['kondisi']] ?? $row['kondisi'];
                    $statusColor  = ['tersedia'=>'#1a7a4a','dipinjam'=>'#4361ee','dalam_perbaikan'=>'#a33c44'][$row['status']] ?? '#6c757d';
                    $statusBg     = ['tersedia'=>'#f0f9f4','dipinjam'=>'#eef2ff','dalam_perbaikan'=>'#fff5f5'][$row['status']] ?? '#f3f4f6';
                    $statusLabel  = ['tersedia'=>'Tersedia','dipinjam'=>'Dipinjam','dalam_perbaikan'=>'Dalam Perbaikan'][$row['status']] ?? $row['status'];
                    $stokPersen   = $row['stok_total'] > 0 ? round(($row['stok_tersedia'] / $row['stok_total']) * 100) : 0;
                    $stokColor    = $stokPersen >= 70 ? '#1a7a4a' : ($stokPersen >= 30 ? '#9c7e2f' : '#a33c44');
                ?>
                <tr>
                    <td class="text-center"><?= $i+1 ?></td>
                    <td style="font-size:.78rem;font-family:monospace;font-weight:700;color:#4361ee"><?= htmlspecialchars($row['kode_barang']) ?></td>
                    <td>
                        <div style="font-weight:700;color:#1e1e2d"><?= htmlspecialchars($row['nama']) ?></div>
                        <div style="font-size:.72rem;color:#6b7280">Ditambahkan <?= date('d M Y', strtotime($row['dibuat_pada'])) ?></div>
                    </td>
                    <td style="font-size:.83rem"><?= htmlspecialchars($row['nama_kategori'] ?? '—') ?></td>
                    <td>
                        <div style="font-size:.82rem;margin-bottom:4px">
                            <strong style="color:<?= $stokColor ?>"><?= $row['stok_tersedia'] ?></strong>
                            <span style="color:#6b7280"> / <?= $row['stok_total'] ?> unit</span>
                        </div>
                        <div style="background:#e5e7eb;border-radius:4px;height:5px;overflow:hidden">
                            <div style="background:<?= $stokColor ?>;height:100%;width:<?= $stokPersen ?>%"></div>
                        </div>
                    </td>
                    <td>
                        <span style="background:<?= $kondisiBg ?>;color:<?= $kondisiColor ?>;border:1px solid <?= $kondisiColor ?>40;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap">
                            <?= $kondisiLabel ?>
                        </span>
                    </td>
                    <td>
                        <span style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;border:1px solid <?= $statusColor ?>40;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap">
                            <?= $statusLabel ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span style="font-size:1rem;font-weight:700;color:<?= $row['total_dipinjam'] > 0 ? '#4361ee' : '#aaa' ?>">
                            <?= (int)$row['total_dipinjam'] ?>x
                        </span>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ===================================================================
     TAB 4: ARSIP LAPORAN (CRUD)
=================================================================== -->
<div id="tabArsip" <?= $tab !== 'arsip' ? 'style="display:none"' : '' ?>>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
        <small style="color:#6b7280">Arsip laporan resmi yang dibuat oleh admin atau staff TU</small>
        <button class="btn-tambah-laporan" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fa-solid fa-plus me-1"></i> Buat Laporan Baru
        </button>
    </div>

    <div class="table-card">
        <div class="table-wrapper">
        <table class="table-modern">
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
     MODAL DETAIL PENGEMBALIAN (Tab 2)
================================================================ -->
<div class="modal fade" id="modalDetailPengembalian" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title"><i class="fa-solid fa-rotate-left me-2"></i>Detail Pengembalian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="bodyDetailPengembalian"></div>
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
                            <option value="inventaris">Inventaris — kondisi &amp; frekuensi pemakaian barang</option>
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
                            <option value="inventaris">Inventaris</option>
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

    var scMap = {
        menunggu:    {bg:'#fef9c3',fg:'#854d0e'},
        diverifikasi:{bg:'#dbeafe',fg:'#1d4ed8'},
        disetujui:   {bg:'#dcfce7',fg:'#166534'},
        ditolak:     {bg:'#fee2e2',fg:'#991b1b'},
        aktif:       {bg:'#e0f2fe',fg:'#0369a1'},
        selesai:     {bg:'#d1fae5',fg:'#065f46'},
        dibatalkan:  {bg:'#f3f4f6',fg:'#6b7280'}
    };
    var sc = scMap[row.status] || {bg:'#f3f4f6',fg:'#6b7280'};
    var badge  = '<span style="background:'+sc.bg+';color:'+sc.fg+';padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700">'+row.status.toUpperCase()+'</span>';
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

// ---- Detail Pengembalian (Tab 2) ----
function bukaDetailPengembalian(row) {
    var namaArr   = row.nama_barang_list ? row.nama_barang_list.split('||') : [];
    var jumlahArr = row.jumlah_list      ? row.jumlah_list.split('||')      : [];
    var barangRows = '';
    for (var k = 0; k < namaArr.length; k++) {
        barangRows += '<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0">'
            + namaArr[k] + '</td><td style="padding:6px 0;text-align:center;font-weight:700">'
            + (jumlahArr[k]||'1') + '</td></tr>';
    }
    if (!barangRows) barangRows = '<tr><td colspan="2" style="text-align:center;color:#aaa;padding:10px 0">Tidak ada data</td></tr>';

    var kondisiMap = {baik:'Baik', rusak_ringan:'Rusak Ringan', rusak_berat:'Rusak Berat'};
    var kondisiColorMap = {baik:'#1a7a4a', rusak_ringan:'#9c7e2f', rusak_berat:'#a33c44'};
    var kondisiLabel = kondisiMap[row.kondisi_kembali] || row.kondisi_kembali;
    var kondisiColor = kondisiColorMap[row.kondisi_kembali] || '#6c757d';
    var kondisiBadge = '<span style="background:'+kondisiColor+';color:#fff;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700">'+kondisiLabel+'</span>';
    var terlambat = parseInt(row.hari_terlambat) > 0
        ? '<span style="background:#fee2e2;color:#a33c44;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700">'+row.hari_terlambat+' hari</span>'
        : '<span style="background:#f0f9f4;color:#1a7a4a;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:700">Tepat Waktu</span>';
    var catatan = row.catatan_kerusakan
        ? '<div style="background:#fff5f5;border:1px solid #fecaca;border-radius:6px;padding:10px 14px;margin-top:10px;font-size:.83rem"><strong style="color:#a33c44">Catatan Kerusakan:</strong> '+row.catatan_kerusakan+'</div>'
        : '';

    document.getElementById('bodyDetailPengembalian').innerHTML =
        '<div class="row g-3">'
        +'<div class="col-md-6"><div style="background:#f8f9fa;border-radius:8px;padding:16px;height:100%">'
        +'<h6 style="font-weight:700;color:#1e1e2d;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #e9ecef"><i class="fa-solid fa-circle-info me-1 text-secondary"></i> Informasi Pengembalian</h6>'
        +'<table style="width:100%;font-size:.85rem;border-collapse:collapse">'
        +'<tr><td style="color:#6b7280;padding:5px 0;width:42%">Peminjam</td><td><strong>'+row.nama_peminjam+'</strong><br><small style="color:#6b7280">'+row.email_peminjam+'</small></td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Keperluan</td><td>'+row.keperluan+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Tgl Pinjam</td><td>'+row.tanggal_pinjam+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Rencana Kembali</td><td>'+row.rencana_kembali+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Tgl Kembali Aktual</td><td><strong>'+row.tanggal_kembali+'</strong></td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Keterlambatan</td><td>'+terlambat+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Kondisi Kembali</td><td>'+kondisiBadge+'</td></tr>'
        +'<tr><td style="color:#6b7280;padding:5px 0">Diterima Oleh</td><td>'+row.nama_penerima+'</td></tr>'
        +'</table>'+catatan+'</div></div>'
        +'<div class="col-md-6"><div style="background:#f8f9fa;border-radius:8px;padding:16px;height:100%">'
        +'<h6 style="font-weight:700;color:#1e1e2d;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #e9ecef"><i class="fa-solid fa-boxes-stacked me-1 text-secondary"></i> Barang Dikembalikan</h6>'
        +'<table style="width:100%;font-size:.85rem;border-collapse:collapse">'
        +'<thead><tr style="border-bottom:2px solid #dee2e6"><th style="padding:6px 0;color:#6b7280;font-size:.75rem;text-transform:uppercase">Nama Barang</th><th style="padding:6px 0;color:#6b7280;font-size:.75rem;text-transform:uppercase;text-align:center">Jml</th></tr></thead>'
        +'<tbody>'+barangRows+'</tbody></table>'
        +'</div></div></div>';

    new bootstrap.Modal(document.getElementById('modalDetailPengembalian')).show();
}

// ---- Edit Laporan (Tab 3) ----
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
