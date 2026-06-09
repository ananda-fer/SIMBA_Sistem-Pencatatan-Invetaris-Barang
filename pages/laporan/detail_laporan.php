<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/koneksi.php';

hanya_role(['admin', 'staff_tu']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php?tab=arsip'); exit; }

// =========================================================================
// DATA LAPORAN
// =========================================================================
$stmtL = $conn->prepare("
    SELECT l.*, u.nama_lengkap AS nama_pembuat
    FROM laporan l JOIN users u ON u.id = l.dibuat_oleh
    WHERE l.id = ?
");
$stmtL->bind_param("i", $id);
$stmtL->execute();
$laporan = $stmtL->get_result()->fetch_assoc();
if (!$laporan) { header('Location: index.php?tab=arsip'); exit; }

$jenis  = $laporan['jenis_laporan'];
$awal   = $laporan['periode_awal'];
$akhir  = $laporan['periode_akhir'];

// =========================================================================
// QUERY BERDASARKAN JENIS
// =========================================================================

// --- PEMINJAMAN ---
if ($jenis === 'peminjaman' || $jenis === 'semua') {
    $stmtPinjam = $conn->prepare("
        SELECT
            pp.id, pp.keperluan, pp.tanggal_pinjam, pp.tanggal_kembali AS rencana_kembali,
            pp.status, pp.file_surat, pp.catatan_tolak, pp.dibuat_pada, pp.diperbarui_pada,
            u_p.nama_lengkap  AS nama_peminjam,
            u_p.email         AS email_peminjam,
            u_s.nama_lengkap  AS nama_staff,
            GROUP_CONCAT(
                b.nama, ' x', dp.jumlah, ' [', dp.kondisi_saat_pinjam, ']'
                ORDER BY b.nama SEPARATOR '||'
            ) AS barang_detail
        FROM pengajuan_peminjaman pp
        JOIN  users u_p ON u_p.id = pp.id_peminjam
        LEFT JOIN users u_s ON u_s.id = pp.disetujui_oleh
        LEFT JOIN detail_peminjaman dp ON dp.id_peminjaman = pp.id
        LEFT JOIN barang b ON b.id = dp.id_barang
        WHERE pp.tanggal_pinjam BETWEEN ? AND ?
        GROUP BY pp.id
        ORDER BY pp.tanggal_pinjam ASC, pp.id ASC
    ");
    $stmtPinjam->bind_param("ss", $awal, $akhir);
    $stmtPinjam->execute();
    $dataPinjam = $stmtPinjam->get_result()->fetch_all(MYSQLI_ASSOC);
}

// --- PENGEMBALIAN ---
if ($jenis === 'pengembalian' || $jenis === 'semua') {
    $stmtKembali = $conn->prepare("
        SELECT
            pen.id AS pen_id,
            pen.tanggal_kembali AS tgl_kembali_aktual,
            pen.hari_terlambat,
            pen.kondisi_kembali,
            pen.catatan_kerusakan,
            pen.dibuat_pada AS pen_dibuat,
            pp.id AS pp_id,
            pp.keperluan,
            pp.tanggal_pinjam,
            pp.tanggal_kembali AS rencana_kembali,
            u_p.nama_lengkap   AS nama_peminjam,
            u_p.email          AS email_peminjam,
            u_t.nama_lengkap   AS nama_penerima,
            GROUP_CONCAT(
                b.nama, ' x', dp.jumlah, ' [pinjam:', dp.kondisi_saat_pinjam, ']'
                ORDER BY b.nama SEPARATOR '||'
            ) AS barang_detail
        FROM pengembalian pen
        JOIN pengajuan_peminjaman pp ON pp.id = pen.id_peminjaman
        JOIN users u_p ON u_p.id = pp.id_peminjam
        JOIN users u_t ON u_t.id = pen.diterima_oleh
        LEFT JOIN detail_peminjaman dp ON dp.id_peminjaman = pp.id
        LEFT JOIN barang b ON b.id = dp.id_barang
        WHERE pen.tanggal_kembali BETWEEN ? AND ?
        GROUP BY pen.id
        ORDER BY pen.tanggal_kembali ASC, pen.id ASC
    ");
    $stmtKembali->bind_param("ss", $awal, $akhir);
    $stmtKembali->execute();
    $dataKembali = $stmtKembali->get_result()->fetch_all(MYSQLI_ASSOC);
    // Buat map pp_id → pengembalian untuk mode "semua"
    $mapKembali = [];
    foreach ($dataKembali as $k) $mapKembali[$k['pp_id']] = $k;
}

// --- INVENTARIS ---
if ($jenis === 'inventaris') {
    $stmtInv = $conn->prepare("
        SELECT
            b.id, b.kode_barang, b.nama, b.stok_total, b.stok_tersedia,
            b.kondisi, b.status,
            k.nama AS nama_kategori,
            COUNT(DISTINCT dp.id_peminjaman) AS dipinjam_periode
        FROM barang b
        LEFT JOIN kategori k ON k.id = b.kategori_id
        LEFT JOIN detail_peminjaman dp ON dp.id_barang = b.id
        LEFT JOIN pengajuan_peminjaman pp ON pp.id = dp.id_peminjaman
            AND pp.tanggal_pinjam BETWEEN ? AND ?
        GROUP BY b.id
        ORDER BY dipinjam_periode DESC, b.nama ASC
    ");
    $stmtInv->bind_param("ss", $awal, $akhir);
    $stmtInv->execute();
    $dataInventaris = $stmtInv->get_result()->fetch_all(MYSQLI_ASSOC);
}

// =========================================================================
// STATISTIK
// =========================================================================
$stat = [];
if ($jenis === 'peminjaman') {
    $tot = count($dataPinjam);
    $stat = [
        'Total Pengajuan'  => $tot,
        'Disetujui'        => count(array_filter($dataPinjam, fn($r) => $r['status'] === 'disetujui')),
        'Ditolak'          => count(array_filter($dataPinjam, fn($r) => $r['status'] === 'ditolak')),
        'Aktif / Berjalan' => count(array_filter($dataPinjam, fn($r) => in_array($r['status'], ['aktif','menunggu','diverifikasi']))),
        'Selesai'          => count(array_filter($dataPinjam, fn($r) => $r['status'] === 'selesai')),
    ];
} elseif ($jenis === 'pengembalian') {
    $tot = count($dataKembali);
    $stat = [
        'Total Pengembalian' => $tot,
        'Kondisi Baik'       => count(array_filter($dataKembali, fn($r) => $r['kondisi_kembali'] === 'baik')),
        'Rusak Ringan'       => count(array_filter($dataKembali, fn($r) => $r['kondisi_kembali'] === 'rusak_ringan')),
        'Rusak Berat'        => count(array_filter($dataKembali, fn($r) => $r['kondisi_kembali'] === 'rusak_berat')),
        'Terlambat'          => count(array_filter($dataKembali, fn($r) => (int)$r['hari_terlambat'] > 0)),
    ];
} elseif ($jenis === 'inventaris') {
    $stat = [
        'Total Barang'      => count($dataInventaris),
        'Kondisi Baik'      => count(array_filter($dataInventaris, fn($r) => $r['kondisi'] === 'baik')),
        'Rusak Ringan'      => count(array_filter($dataInventaris, fn($r) => $r['kondisi'] === 'rusak_ringan')),
        'Rusak Berat'       => count(array_filter($dataInventaris, fn($r) => $r['kondisi'] === 'rusak_berat')),
        'Pernah Dipinjam'   => count(array_filter($dataInventaris, fn($r) => (int)$r['dipinjam_periode'] > 0)),
    ];
} else { // semua
    $stat = [
        'Total Pengajuan'    => count($dataPinjam),
        'Sudah Dikembalikan' => count($mapKembali),
        'Belum Dikembalikan' => count($dataPinjam) - count($mapKembali),
        'Terlambat'          => count(array_filter($mapKembali, fn($r) => (int)$r['hari_terlambat'] > 0)),
    ];
}

// =========================================================================
// PAGE CONFIG
// =========================================================================
$jenisBadge = ['peminjaman' => 'PEMINJAMAN', 'pengembalian' => 'PENGEMBALIAN', 'semua' => 'SEMUA', 'inventaris' => 'INVENTARIS'];
$pageTitle  = 'Detail Laporan';
$basePath   = '../../';
$pageCss    = ['assets/css/laporan.css'];

require_once $basePath . 'includes/header.php';
require_once $basePath . 'includes/sidebar.php';
?>
<main class="main-content">

<!-- Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="index.php?tab=arsip"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid #d1d5db;border-radius:6px;font-size:.83rem;font-weight:600;color:#374151;text-decoration:none;background:#fff">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
        <div>
            <h2 style="font-size:1.25rem;font-weight:700;color:#1e1e2d;margin:0"><?= htmlspecialchars($laporan['judul']) ?></h2>
            <p style="color:#6b7280;font-size:.8rem;margin:3px 0 0">
                Dibuat oleh <strong><?= htmlspecialchars($laporan['nama_pembuat']) ?></strong>
                &middot; <?= date('d M Y', strtotime($laporan['dibuat_pada'])) ?>
            </p>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="cetak_laporan.php?id=<?= (int)$laporan['id'] ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:8px;background:#fca311;color:#1e1e2d;padding:8px 18px;border-radius:8px;font-weight:700;font-size:.85rem;text-decoration:none">
            <i class="fa-solid fa-file-arrow-down"></i> Export PDF
        </a>
        <?php if ($laporan['file_pdf']): ?>
        <a href="<?= app_url($laporan['file_pdf']) ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:8px;background:#1e1e2d;color:#fca311;padding:8px 18px;border-radius:8px;font-weight:700;font-size:.85rem;text-decoration:none">
            <i class="fa-solid fa-file-pdf"></i> Lihat PDF Resmi
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Info Laporan + Statistik -->
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:18px">
            <h6 style="font-weight:700;color:#1e1e2d;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #f0f0f0">
                <i class="fa-solid fa-circle-info me-1 text-secondary"></i> Informasi Laporan
            </h6>
            <div style="display:grid;grid-template-columns:120px 1fr;gap:6px;font-size:.85rem">
                <span style="color:#6b7280">Jenis</span>
                <span><span class="badge-kategori"><?= $jenisBadge[$jenis] ?? strtoupper($jenis) ?></span></span>
                <span style="color:#6b7280">Periode</span>
                <span><strong><?= date('d M Y', strtotime($awal)) ?></strong> s/d <strong><?= date('d M Y', strtotime($akhir)) ?></strong></span>
                <?php if ($laporan['catatan']): ?>
                <span style="color:#6b7280">Catatan</span>
                <span><?= nl2br(htmlspecialchars($laporan['catatan'])) ?></span>
                <?php endif; ?>
                <span style="color:#6b7280">File PDF</span>
                <span>
                    <?php if ($laporan['file_pdf']): ?>
                    <a href="<?= app_url($laporan['file_pdf']) ?>" target="_blank"
                       style="color:#1a7a4a;font-weight:600;font-size:.83rem;text-decoration:none">
                        <i class="fa-solid fa-file-pdf me-1"></i><?= htmlspecialchars(basename($laporan['file_pdf'])) ?>
                    </a>
                    <?php else: ?><span style="color:#aaa">Tidak ada</span><?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:18px;height:100%">
            <h6 style="font-weight:700;color:#1e1e2d;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #f0f0f0">
                <i class="fa-solid fa-chart-bar me-1 text-secondary"></i> Ringkasan
            </h6>
            <?php foreach ($stat as $label => $val): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f5f5f5;font-size:.85rem">
                <span style="color:#6b7280"><?= $label ?></span>
                <strong style="font-size:1rem;color:#1e1e2d"><?= $val ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php // ================================================================
      // TAMPILAN JENIS: PEMINJAMAN
      // ================================================================
if ($jenis === 'peminjaman'): ?>

<div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
    <h6 style="font-weight:700;color:#1e1e2d;margin:0">
        <i class="fa-solid fa-list-check me-1 text-secondary"></i> Data Pengajuan Peminjaman
    </h6>
    <small style="color:#6b7280"><?= count($dataPinjam) ?> pengajuan</small>
</div>

<div class="table-card">
    <table class="table table-borderless">
        <thead><tr>
            <th width="4%"  class="text-center">NO</th>
            <th width="14%">PEMINJAM</th>
            <th width="14%">KEPERLUAN</th>
            <th width="26%">BARANG &amp; KONDISI PINJAM</th>
            <th width="13%">PERIODE</th>
            <th width="7%"  class="text-center">SURAT</th>
            <th width="12%">DISETUJUI OLEH</th>
            <th width="10%" class="text-center">STATUS</th>
        </tr></thead>
        <tbody>
        <?php if (empty($dataPinjam)): ?>
            <tr class="empty-row"><td colspan="8"><i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>Tidak ada data peminjaman dalam periode ini.</td></tr>
        <?php else: foreach ($dataPinjam as $i => $row):
            $sc = ['menunggu'=>'#9c7e2f','diverifikasi'=>'#1a6ba8','disetujui'=>'#1a7a4a','ditolak'=>'#a33c44','aktif'=>'#1e1e2d','selesai'=>'#495057','dibatalkan'=>'#6c757d'][$row['status']] ?? '#6c757d';
            $barangHtml = '';
            if ($row['barang_detail']) {
                foreach (explode('||', $row['barang_detail']) as $b) {
                    // format: "Nama x2 [baik]"
                    $barangHtml .= '<div style="font-size:.77rem;line-height:1.7">' . htmlspecialchars($b) . '</div>';
                }
            } else $barangHtml = '<span style="color:#aaa">—</span>';
        ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td>
                <div style="font-weight:700;color:#1e1e2d"><?= htmlspecialchars($row['nama_peminjam']) ?></div>
                <div style="font-size:.72rem;color:#6b7280"><?= htmlspecialchars($row['email_peminjam']) ?></div>
            </td>
            <td style="font-size:.83rem"><?= htmlspecialchars($row['keperluan']) ?></td>
            <td><?= $barangHtml ?></td>
            <td style="font-size:.78rem;white-space:nowrap">
                <i class="fa-regular fa-calendar me-1" style="color:#9c7e2f"></i><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?><br>
                <span style="color:#6b7280;padding-left:16px">s/d <?= date('d M Y', strtotime($row['rencana_kembali'])) ?></span>
            </td>
            <td class="text-center">
                <?php if ($row['file_surat']): ?>
                <a href="<?= app_url($row['file_surat']) ?>" target="_blank"
                   style="background:#1e1e2d;color:#fca311;padding:4px 10px;border-radius:4px;font-size:.72rem;font-weight:700;text-decoration:none">
                    <i class="fa-solid fa-file-pdf me-1"></i>PDF
                </a>
                <?php else: ?><span style="color:#aaa">—</span><?php endif; ?>
            </td>
            <td style="font-size:.78rem">
                <?= $row['nama_staff'] ? htmlspecialchars($row['nama_staff']) : '<span style="color:#aaa">—</span>' ?>
            </td>
            <td class="text-center">
                <span style="background:<?= $sc ?>;color:#fff;padding:3px 8px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap">
                    <?= strtoupper($row['status']) ?>
                </span>
                <?php if ($row['status'] === 'ditolak' && $row['catatan_tolak']): ?>
                <div style="font-size:.7rem;color:#a33c44;margin-top:3px"><?= htmlspecialchars($row['catatan_tolak']) ?></div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php // ================================================================
      // TAMPILAN JENIS: PENGEMBALIAN
      // ================================================================
elseif ($jenis === 'pengembalian'): ?>

<div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
    <h6 style="font-weight:700;color:#1e1e2d;margin:0">
        <i class="fa-solid fa-rotate-left me-1 text-secondary"></i> Data Pengembalian Barang
    </h6>
    <small style="color:#6b7280"><?= count($dataKembali) ?> pengembalian</small>
</div>

<div class="table-card">
    <table class="table table-borderless">
        <thead><tr>
            <th width="4%"  class="text-center">NO</th>
            <th width="13%">PEMINJAM</th>
            <th width="13%">KEPERLUAN</th>
            <th width="22%">BARANG (KONDISI PINJAM → KEMBALI)</th>
            <th width="10%">TGL KEMBALI</th>
            <th width="8%"  class="text-center">TERLAMBAT</th>
            <th width="12%">KONDISI KEMBALI</th>
            <th width="18%">CATATAN KERUSAKAN</th>
        </tr></thead>
        <tbody>
        <?php if (empty($dataKembali)): ?>
            <tr class="empty-row"><td colspan="8"><i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>Tidak ada data pengembalian dalam periode ini.</td></tr>
        <?php else: foreach ($dataKembali as $i => $row):
            $kondisiColor = ['baik' => '#1a7a4a', 'rusak_ringan' => '#9c7e2f', 'rusak_berat' => '#a33c44'][$row['kondisi_kembali']] ?? '#6c757d';
            $kondisiLabel = ['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_berat' => 'Rusak Berat'][$row['kondisi_kembali']] ?? $row['kondisi_kembali'];
            $barangHtml = '';
            if ($row['barang_detail']) {
                foreach (explode('||', $row['barang_detail']) as $b)
                    $barangHtml .= '<div style="font-size:.77rem;line-height:1.7">'.htmlspecialchars($b).'</div>';
            } else $barangHtml = '<span style="color:#aaa">—</span>';
        ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td>
                <div style="font-weight:700;color:#1e1e2d"><?= htmlspecialchars($row['nama_peminjam']) ?></div>
                <div style="font-size:.72rem;color:#6b7280"><?= htmlspecialchars($row['email_peminjam']) ?></div>
            </td>
            <td style="font-size:.83rem"><?= htmlspecialchars($row['keperluan']) ?></td>
            <td><?= $barangHtml ?></td>
            <td style="font-size:.8rem;white-space:nowrap">
                <strong><?= date('d M Y', strtotime($row['tgl_kembali_aktual'])) ?></strong><br>
                <span style="color:#6b7280">Rencana: <?= date('d M Y', strtotime($row['rencana_kembali'])) ?></span>
            </td>
            <td class="text-center">
                <?php $h = (int)$row['hari_terlambat']; ?>
                <?php if ($h > 0): ?>
                <span style="background:#fee2e2;color:#a33c44;padding:3px 8px;border-radius:20px;font-size:.72rem;font-weight:700"><?= $h ?> hari</span>
                <?php else: ?>
                <span style="background:#f0f9f4;color:#1a7a4a;padding:3px 8px;border-radius:20px;font-size:.72rem;font-weight:700">Tepat waktu</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <span style="background:<?= $kondisiColor ?>;color:#fff;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap">
                    <?= $kondisiLabel ?>
                </span>
            </td>
            <td style="font-size:.8rem;color:#6b7280">
                <?= $row['catatan_kerusakan'] ? htmlspecialchars($row['catatan_kerusakan']) : '<span style="color:#aaa">—</span>' ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php // ================================================================
      // TAMPILAN JENIS: SEMUA (Peminjaman + Pengembalian, dikelompokkan)
      // ================================================================
elseif ($jenis === 'semua'): ?>

<div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
    <h6 style="font-weight:700;color:#1e1e2d;margin:0">
        <i class="fa-solid fa-table-list me-1 text-secondary"></i> Rekap Lengkap (Peminjaman &amp; Pengembalian)
    </h6>
    <small style="color:#6b7280"><?= count($dataPinjam) ?> pengajuan</small>
</div>

<!-- Legenda -->
<div style="display:flex;gap:12px;margin-bottom:12px;font-size:.78rem;flex-wrap:wrap">
    <span style="display:flex;align-items:center;gap:6px">
        <span style="width:12px;height:12px;border-radius:2px;background:#eef2ff;border:1px solid #c7d2fe;display:inline-block"></span> Baris Peminjaman
    </span>
    <span style="display:flex;align-items:center;gap:6px">
        <span style="width:12px;height:12px;border-radius:2px;background:#f0fdf4;border:1px solid #bbf7d0;display:inline-block"></span> Baris Pengembalian
    </span>
    <span style="display:flex;align-items:center;gap:6px">
        <span style="width:12px;height:12px;border-radius:2px;background:#fafafa;border:1px solid #e5e7eb;display:inline-block"></span> Belum Dikembalikan
    </span>
</div>

<div class="table-card">
    <table class="table table-borderless" style="font-size:.8rem">
        <thead><tr>
            <th width="4%"  class="text-center">NO</th>
            <th width="5%"  class="text-center">TIPE</th>
            <th width="13%">PEMINJAM</th>
            <th width="14%">KEPERLUAN</th>
            <th width="23%">BARANG &amp; KONDISI</th>
            <th width="11%">TANGGAL</th>
            <th width="9%"  class="text-center">TERLAMBAT</th>
            <th width="11%">DIPROSES</th>
            <th width="10%" class="text-center">STATUS / KONDISI</th>
        </tr></thead>
        <tbody>
        <?php if (empty($dataPinjam)): ?>
            <tr class="empty-row"><td colspan="9"><i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>Tidak ada data dalam periode ini.</td></tr>
        <?php else: foreach ($dataPinjam as $no => $pp):
            $pen = $mapKembali[$pp['id']] ?? null;

            // Barang pinjam
            $barangPinjamHtml = '';
            if ($pp['barang_detail']) {
                foreach (explode('||', $pp['barang_detail']) as $b)
                    $barangPinjamHtml .= '<div style="line-height:1.7">'.htmlspecialchars($b).'</div>';
            } else $barangPinjamHtml = '<span style="color:#aaa">—</span>';

            // Barang kembali (sama tapi kondisi kembali juga ditampilkan)
            $barangKembaliHtml = '';
            if ($pen && $pen['barang_detail']) {
                // Tambah info kondisi kembali
                $kondisiColor = ['baik'=>'#1a7a4a','rusak_ringan'=>'#9c7e2f','rusak_berat'=>'#a33c44'][$pen['kondisi_kembali']] ?? '#6c757d';
                $kondisiLabel = ['baik'=>'Baik','rusak_ringan'=>'Rusak Ringan','rusak_berat'=>'Rusak Berat'][$pen['kondisi_kembali']] ?? '';
                foreach (explode('||', $pen['barang_detail']) as $b)
                    $barangKembaliHtml .= '<div style="line-height:1.7">'.htmlspecialchars($b).'</div>';
                $barangKembaliHtml .= '<span style="background:'.$kondisiColor.';color:#fff;padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:700;margin-top:2px;display:inline-block">'.htmlspecialchars($kondisiLabel).'</span>';
            }

            $scPinjam = ['menunggu'=>'#9c7e2f','diverifikasi'=>'#1a6ba8','disetujui'=>'#1a7a4a','ditolak'=>'#a33c44','aktif'=>'#1e1e2d','selesai'=>'#495057','dibatalkan'=>'#6c757d'][$pp['status']] ?? '#6c757d';
        ?>

        <!-- Baris Peminjaman -->
        <tr style="background:#eef2ff;border-top:2px solid #c7d2fe">
            <td class="text-center" rowspan="2" style="font-weight:700;vertical-align:middle;font-size:.9rem"><?= $no+1 ?></td>
            <td class="text-center">
                <span style="background:#4361ee;color:#fff;padding:2px 7px;border-radius:4px;font-size:.68rem;font-weight:700;white-space:nowrap">PINJAM</span>
            </td>
            <td>
                <div style="font-weight:700;color:#1e1e2d"><?= htmlspecialchars($pp['nama_peminjam']) ?></div>
                <div style="font-size:.7rem;color:#6b7280"><?= htmlspecialchars($pp['email_peminjam']) ?></div>
            </td>
            <td><?= htmlspecialchars($pp['keperluan']) ?></td>
            <td><?= $barangPinjamHtml ?></td>
            <td style="white-space:nowrap">
                <div><i class="fa-solid fa-arrow-right-from-bracket me-1" style="color:#4361ee;font-size:.7rem"></i><?= date('d M Y', strtotime($pp['tanggal_pinjam'])) ?></div>
                <div style="color:#6b7280">Rencana: <?= date('d M Y', strtotime($pp['rencana_kembali'])) ?></div>
            </td>
            <td class="text-center">—</td>
            <td style="font-size:.78rem">
                <?= $pp['nama_staff'] ? htmlspecialchars($pp['nama_staff']) : '<span style="color:#aaa">—</span>' ?>
                <?php if ($pp['diperbarui_pada'] && $pp['nama_staff']): ?>
                <div style="color:#6b7280"><?= date('d M Y', strtotime($pp['diperbarui_pada'])) ?></div>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <span style="background:<?= $scPinjam ?>;color:#fff;padding:3px 7px;border-radius:20px;font-size:.68rem;font-weight:700;white-space:nowrap">
                    <?= strtoupper($pp['status']) ?>
                </span>
                <?php if ($pp['file_surat']): ?>
                <div style="margin-top:3px"><a href="<?= app_url($pp['file_surat']) ?>" target="_blank" style="font-size:.68rem;color:#4361ee;font-weight:600"><i class="fa-solid fa-file-pdf me-1"></i>Surat</a></div>
                <?php endif; ?>
            </td>
        </tr>

        <!-- Baris Pengembalian -->
        <?php if ($pen): ?>
        <tr style="background:#f0fdf4;border-bottom:2px solid #bbf7d0">
            <td class="text-center">
                <span style="background:#1a7a4a;color:#fff;padding:2px 7px;border-radius:4px;font-size:.68rem;font-weight:700;white-space:nowrap">KEMBALI</span>
            </td>
            <td>
                <div style="font-weight:700;color:#1e1e2d"><?= htmlspecialchars($pp['nama_peminjam']) ?></div>
                <div style="font-size:.7rem;color:#6b7280"><?= htmlspecialchars($pp['email_peminjam']) ?></div>
            </td>
            <td style="font-size:.83rem"><?= htmlspecialchars($pp['keperluan']) ?></td>
            <td><?= $barangKembaliHtml ?></td>
            <td style="white-space:nowrap">
                <div><i class="fa-solid fa-arrow-right-to-bracket me-1" style="color:#1a7a4a;font-size:.7rem"></i><strong><?= date('d M Y', strtotime($pen['tgl_kembali_aktual'])) ?></strong></div>
            </td>
            <td class="text-center">
                <?php $h = (int)$pen['hari_terlambat']; ?>
                <?php if ($h > 0): ?>
                <span style="background:#fee2e2;color:#a33c44;padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:700"><?= $h ?> hari</span>
                <?php else: ?>
                <span style="background:#f0f9f4;color:#1a7a4a;padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:700">Tepat</span>
                <?php endif; ?>
            </td>
            <td style="font-size:.78rem"><?= htmlspecialchars($pen['nama_penerima']) ?></td>
            <td style="font-size:.78rem;color:#6b7280">
                <?= $pen['catatan_kerusakan'] ? '<span style="color:#a33c44">'.htmlspecialchars($pen['catatan_kerusakan']).'</span>' : '<span style="color:#aaa">Tidak ada catatan</span>' ?>
            </td>
        </tr>
        <?php else: ?>
        <tr style="background:#fafafa;border-bottom:2px solid #e5e7eb">
            <td class="text-center">
                <span style="background:#9ca3af;color:#fff;padding:2px 7px;border-radius:4px;font-size:.68rem;font-weight:700;white-space:nowrap">KEMBALI</span>
            </td>
            <td colspan="7" style="color:#aaa;font-style:italic;font-size:.8rem;padding-top:10px;padding-bottom:10px">
                <i class="fa-regular fa-clock me-1"></i> Belum dikembalikan
            </td>
        </tr>
        <?php endif; ?>

        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php // ================================================================
      // TAMPILAN JENIS: INVENTARIS
      // ================================================================
if ($jenis === 'inventaris'):
    $bermasalah = array_filter($dataInventaris, fn($r) =>
        $r['kondisi'] !== 'baik' || $r['status'] === 'dalam_perbaikan'
    );
?>

<?php if (!empty($bermasalah)): ?>
<!-- Kartu Perlu Perhatian -->
<div style="background:#fff5f5;border:1.5px solid #fecaca;border-radius:10px;padding:18px;margin-bottom:22px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
        <i class="fa-solid fa-triangle-exclamation" style="color:#a33c44;font-size:1rem"></i>
        <h6 style="font-weight:700;color:#a33c44;margin:0">Barang Perlu Perhatian
            <span style="background:#a33c44;color:#fff;font-size:.7rem;padding:2px 8px;border-radius:20px;margin-left:6px"><?= count($bermasalah) ?> item</span>
        </h6>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($bermasalah as $bm):
        $bmKondisiLabel = ['baik'=>'Baik','rusak_ringan'=>'Rusak Ringan','rusak_berat'=>'Rusak Berat'][$bm['kondisi']] ?? $bm['kondisi'];
        $bmKondisiColor = ['baik'=>'#1a7a4a','rusak_ringan'=>'#9c7e2f','rusak_berat'=>'#a33c44'][$bm['kondisi']] ?? '#6c757d';
        $bmStatusLabel  = ['tersedia'=>'Tersedia','dipinjam'=>'Dipinjam','dalam_perbaikan'=>'Dalam Perbaikan'][$bm['status']] ?? $bm['status'];
        $bmStatusColor  = ['tersedia'=>'#1a7a4a','dipinjam'=>'#4361ee','dalam_perbaikan'=>'#a33c44'][$bm['status']] ?? '#6c757d';
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;flex-wrap:wrap;gap:8px">
        <div style="display:flex;align-items:center;gap:10px">
            <i class="fa-solid fa-box" style="color:#aaa;font-size:.85rem"></i>
            <div>
                <div style="font-weight:700;color:#1e1e2d;font-size:.88rem"><?= htmlspecialchars($bm['nama']) ?></div>
                <div style="font-size:.72rem;color:#6b7280"><?= htmlspecialchars($bm['kode_barang']) ?> &middot; <?= htmlspecialchars($bm['nama_kategori'] ?? '—') ?></div>
            </div>
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
            <span style="background:#fff8ed;color:<?= $bmKondisiColor ?>;border:1px solid <?= $bmKondisiColor ?>55;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700">
                Kondisi: <?= $bmKondisiLabel ?>
            </span>
            <?php if ($bm['status'] === 'dalam_perbaikan'): ?>
            <span style="background:#fff5f5;color:#a33c44;border:1px solid #fecaca;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700">
                <?= $bmStatusLabel ?>
            </span>
            <?php endif; ?>
            <span style="color:#6b7280;font-size:.78rem">Stok: <strong><?= $bm['stok_tersedia'] ?>/<?= $bm['stok_total'] ?></strong></span>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tabel Lengkap -->
<div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
    <h6 style="font-weight:700;color:#1e1e2d;margin:0">
        <i class="fa-solid fa-boxes-stacked me-1 text-secondary"></i> Rekap Seluruh Barang
    </h6>
    <small style="color:#6b7280"><?= count($dataInventaris) ?> barang &mdash; periode <?= date('d M Y', strtotime($awal)) ?> s/d <?= date('d M Y', strtotime($akhir)) ?></small>
</div>

<div class="table-card">
    <table class="table table-borderless" style="font-size:.82rem">
        <thead><tr>
            <th width="4%"  class="text-center">NO</th>
            <th width="10%">KODE</th>
            <th width="20%">NAMA BARANG</th>
            <th width="12%">KATEGORI</th>
            <th width="13%">STOK</th>
            <th width="12%">KONDISI</th>
            <th width="12%">STATUS</th>
            <th width="17%" class="text-center">DIPINJAM (PERIODE)</th>
        </tr></thead>
        <tbody>
        <?php if (empty($dataInventaris)): ?>
            <tr class="empty-row"><td colspan="8"><i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>Tidak ada data barang.</td></tr>
        <?php else: foreach ($dataInventaris as $i => $row):
            $adaMasalah   = $row['kondisi'] !== 'baik' || $row['status'] === 'dalam_perbaikan';
            $kondisiColor = ['baik'=>'#1a7a4a','rusak_ringan'=>'#9c7e2f','rusak_berat'=>'#a33c44'][$row['kondisi']] ?? '#6c757d';
            $kondisiBg    = ['baik'=>'#f0f9f4','rusak_ringan'=>'#fff8ed','rusak_berat'=>'#fff5f5'][$row['kondisi']] ?? '#f3f4f6';
            $kondisiLabel = ['baik'=>'Baik','rusak_ringan'=>'Rusak Ringan','rusak_berat'=>'Rusak Berat'][$row['kondisi']] ?? $row['kondisi'];
            $statusColor  = ['tersedia'=>'#1a7a4a','dipinjam'=>'#4361ee','dalam_perbaikan'=>'#a33c44'][$row['status']] ?? '#6c757d';
            $statusBg     = ['tersedia'=>'#f0f9f4','dipinjam'=>'#eef2ff','dalam_perbaikan'=>'#fff5f5'][$row['status']] ?? '#f3f4f6';
            $statusLabel  = ['tersedia'=>'Tersedia','dipinjam'=>'Dipinjam','dalam_perbaikan'=>'Dalam Perbaikan'][$row['status']] ?? $row['status'];
            $stokPersen   = $row['stok_total'] > 0 ? round(($row['stok_tersedia'] / $row['stok_total']) * 100) : 0;
            $stokColor    = $stokPersen >= 70 ? '#1a7a4a' : ($stokPersen >= 30 ? '#9c7e2f' : '#a33c44');
            $freq         = (int)$row['dipinjam_periode'];
            $rowBg        = $adaMasalah ? 'background:#fffbf0;border-left:3px solid #f59e0b' : '';
        ?>
        <tr style="<?= $rowBg ?>">
            <td class="text-center"><?= $i+1 ?></td>
            <td style="font-family:monospace;font-weight:700;color:#4361ee;font-size:.78rem"><?= htmlspecialchars($row['kode_barang']) ?></td>
            <td>
                <div style="font-weight:700;color:#1e1e2d"><?= htmlspecialchars($row['nama']) ?></div>
                <?php if ($adaMasalah): ?>
                <div style="font-size:.7rem;color:#a33c44;margin-top:2px"><i class="fa-solid fa-circle-exclamation me-1"></i>Perlu perhatian</div>
                <?php endif; ?>
            </td>
            <td style="color:#6b7280"><?= htmlspecialchars($row['nama_kategori'] ?? '—') ?></td>
            <td>
                <div style="margin-bottom:4px">
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
                <?php if ($freq > 0): ?>
                <span style="font-size:1.1rem;font-weight:800;color:#4361ee"><?= $freq ?>x</span>
                <?php else: ?>
                <span style="color:#aaa;font-size:.8rem">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once $basePath . 'includes/footer.php'; ?>
