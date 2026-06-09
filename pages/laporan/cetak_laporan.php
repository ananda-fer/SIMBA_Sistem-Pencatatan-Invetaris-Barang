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
// QUERY BERDASARKAN JENIS (identik dengan detail_laporan.php)
// =========================================================================

// --- PEMINJAMAN ---
if ($jenis === 'peminjaman' || $jenis === 'semua') {
    $stmtPinjam = $conn->prepare("
        SELECT
            pp.id, pp.keperluan, pp.tanggal_pinjam, pp.tanggal_kembali AS rencana_kembali,
            pp.status, pp.catatan_tolak, pp.diperbarui_pada,
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
    $stat = [
        'Total Pengajuan'  => count($dataPinjam),
        'Disetujui'        => count(array_filter($dataPinjam, fn($r) => $r['status'] === 'disetujui')),
        'Ditolak'          => count(array_filter($dataPinjam, fn($r) => $r['status'] === 'ditolak')),
        'Aktif / Berjalan' => count(array_filter($dataPinjam, fn($r) => in_array($r['status'], ['aktif','menunggu','diverifikasi']))),
        'Selesai'          => count(array_filter($dataPinjam, fn($r) => $r['status'] === 'selesai')),
    ];
} elseif ($jenis === 'pengembalian') {
    $stat = [
        'Total Pengembalian' => count($dataKembali),
        'Kondisi Baik'       => count(array_filter($dataKembali, fn($r) => $r['kondisi_kembali'] === 'baik')),
        'Rusak Ringan'       => count(array_filter($dataKembali, fn($r) => $r['kondisi_kembali'] === 'rusak_ringan')),
        'Rusak Berat'        => count(array_filter($dataKembali, fn($r) => $r['kondisi_kembali'] === 'rusak_berat')),
        'Terlambat'          => count(array_filter($dataKembali, fn($r) => (int)$r['hari_terlambat'] > 0)),
    ];
} elseif ($jenis === 'inventaris') {
    $stat = [
        'Total Barang'    => count($dataInventaris),
        'Kondisi Baik'    => count(array_filter($dataInventaris, fn($r) => $r['kondisi'] === 'baik')),
        'Rusak Ringan'    => count(array_filter($dataInventaris, fn($r) => $r['kondisi'] === 'rusak_ringan')),
        'Rusak Berat'     => count(array_filter($dataInventaris, fn($r) => $r['kondisi'] === 'rusak_berat')),
        'Pernah Dipinjam' => count(array_filter($dataInventaris, fn($r) => (int)$r['dipinjam_periode'] > 0)),
    ];
} else { // semua
    $stat = [
        'Total Pengajuan'    => count($dataPinjam),
        'Sudah Dikembalikan' => count($mapKembali),
        'Belum Dikembalikan' => count($dataPinjam) - count($mapKembali),
        'Terlambat'          => count(array_filter($mapKembali, fn($r) => (int)$r['hari_terlambat'] > 0)),
    ];
}

$jenisBadge = ['peminjaman' => 'PEMINJAMAN', 'pengembalian' => 'PENGEMBALIAN', 'semua' => 'SEMUA (PEMINJAMAN + PENGEMBALIAN)', 'inventaris' => 'INVENTARIS'];
$kondisiLabelMap = ['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_berat' => 'Rusak Berat'];
$statusLabelMap  = ['tersedia' => 'Tersedia', 'dipinjam' => 'Dipinjam', 'dalam_perbaikan' => 'Dalam Perbaikan'];

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
$tgl = fn($d) => $d ? date('d M Y', strtotime($d)) : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Laporan - <?= $h($laporan['judul']) ?></title>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: "Segoe UI", Arial, sans-serif;
        color: #1e1e2d;
        font-size: 12px;
        margin: 0;
        padding: 24px 28px;
        background: #fff;
    }
    .toolbar {
        position: sticky;
        top: 0;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px dashed #d1d5db;
    }
    .toolbar button, .toolbar a {
        font-size: 13px;
        font-weight: 600;
        padding: 8px 18px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-print { background: #1e1e2d; color: #fca311; }
    .btn-back  { background: #e5e7eb; color: #374151; }

    .kop {
        text-align: center;
        border-bottom: 3px double #1e1e2d;
        padding-bottom: 12px;
        margin-bottom: 18px;
    }
    .kop h1 { font-size: 18px; margin: 0; letter-spacing: .5px; }
    .kop p  { font-size: 11px; margin: 2px 0 0; color: #4b5563; }

    .judul-laporan { text-align: center; margin-bottom: 16px; }
    .judul-laporan h2 { font-size: 15px; margin: 0; text-transform: uppercase; text-decoration: underline; }
    .judul-laporan .meta { font-size: 11px; color: #4b5563; margin-top: 4px; }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 4px 24px;
        font-size: 11.5px;
        margin-bottom: 16px;
    }
    .info-grid .row { display: flex; }
    .info-grid .label { width: 110px; color: #6b7280; }
    .info-grid .val { font-weight: 600; }

    .stat-box {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }
    .stat-box .item {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 11px;
    }
    .stat-box .item strong { font-size: 14px; display: block; }

    table.data {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
        margin-bottom: 18px;
    }
    table.data th, table.data td {
        border: 1px solid #cbd5e1;
        padding: 5px 7px;
        text-align: left;
        vertical-align: top;
    }
    table.data thead th {
        background: #1e1e2d;
        color: #fff;
        font-size: 10px;
        text-transform: uppercase;
    }
    table.data tbody tr:nth-child(even) { background: #f8fafc; }
    .text-center { text-align: center; }
    .empty { text-align: center; color: #9ca3af; font-style: italic; padding: 18px 0; }

    .badge {
        display: inline-block;
        padding: 1px 7px;
        border-radius: 10px;
        font-size: 9.5px;
        font-weight: 700;
        color: #fff;
    }
    .section-title { font-size: 13px; font-weight: 700; margin: 0 0 8px; }

    .ttd {
        margin-top: 36px;
        display: flex;
        justify-content: flex-end;
    }
    .ttd .box { text-align: center; font-size: 11.5px; width: 240px; }
    .ttd .sp { height: 56px; }

    @media print {
        body { padding: 0; }
        .toolbar { display: none; }
        table.data thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table.data { page-break-inside: auto; }
        tr { page-break-inside: avoid; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <a href="detail_laporan.php?id=<?= (int)$id ?>" class="btn-back">&larr; Kembali</a>
    <button class="btn-print" onclick="window.print()">&#128424; Cetak / Simpan PDF</button>
</div>

<!-- KOP -->
<div class="kop">
    <h1>SISTEM PENCATATAN INVENTARIS BARANG (SIMBA)</h1>
    <p>Laporan Resmi Inventaris &amp; Peminjaman Barang</p>
</div>

<!-- JUDUL -->
<div class="judul-laporan">
    <h2><?= $h($laporan['judul']) ?></h2>
    <div class="meta">Jenis: <?= $jenisBadge[$jenis] ?? strtoupper($jenis) ?></div>
</div>

<!-- INFO -->
<div class="info-grid">
    <div class="row"><span class="label">Periode</span><span class="val"><?= $tgl($awal) ?> s/d <?= $tgl($akhir) ?></span></div>
    <div class="row"><span class="label">Dibuat Oleh</span><span class="val"><?= $h($laporan['nama_pembuat']) ?></span></div>
    <div class="row"><span class="label">Tanggal Dibuat</span><span class="val"><?= date('d M Y, H:i', strtotime($laporan['dibuat_pada'])) ?></span></div>
    <div class="row"><span class="label">Tanggal Cetak</span><span class="val"><?= date('d M Y, H:i') ?></span></div>
    <?php if (!empty($laporan['catatan'])): ?>
    <div class="row" style="grid-column:1/-1"><span class="label">Catatan</span><span class="val" style="font-weight:400"><?= nl2br($h($laporan['catatan'])) ?></span></div>
    <?php endif; ?>
</div>

<!-- STATISTIK -->
<div class="stat-box">
    <?php foreach ($stat as $label => $val): ?>
    <div class="item"><strong><?= $val ?></strong><?= $h($label) ?></div>
    <?php endforeach; ?>
</div>

<?php // ================== PEMINJAMAN ================== ?>
<?php if ($jenis === 'peminjaman'): ?>
<h3 class="section-title">Data Pengajuan Peminjaman (<?= count($dataPinjam) ?>)</h3>
<table class="data">
    <thead><tr>
        <th class="text-center" style="width:4%">NO</th>
        <th style="width:16%">PEMINJAM</th>
        <th style="width:15%">KEPERLUAN</th>
        <th style="width:27%">BARANG &amp; KONDISI</th>
        <th style="width:14%">PERIODE</th>
        <th style="width:13%">DISETUJUI OLEH</th>
        <th class="text-center" style="width:11%">STATUS</th>
    </tr></thead>
    <tbody>
    <?php if (empty($dataPinjam)): ?>
        <tr><td colspan="7" class="empty">Tidak ada data peminjaman dalam periode ini.</td></tr>
    <?php else: foreach ($dataPinjam as $i => $row):
        $barang = $row['barang_detail'] ? implode('<br>', array_map($h, explode('||', $row['barang_detail']))) : '-';
    ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td><strong><?= $h($row['nama_peminjam']) ?></strong><br><small><?= $h($row['email_peminjam']) ?></small></td>
            <td><?= $h($row['keperluan']) ?></td>
            <td><?= $barang ?></td>
            <td><?= $tgl($row['tanggal_pinjam']) ?><br>s/d <?= $tgl($row['rencana_kembali']) ?></td>
            <td><?= $row['nama_staff'] ? $h($row['nama_staff']) : '-' ?></td>
            <td class="text-center"><?= strtoupper($h($row['status'])) ?>
                <?php if ($row['status'] === 'ditolak' && $row['catatan_tolak']): ?>
                <br><small><?= $h($row['catatan_tolak']) ?></small>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<?php // ================== PENGEMBALIAN ================== ?>
<?php elseif ($jenis === 'pengembalian'): ?>
<h3 class="section-title">Data Pengembalian Barang (<?= count($dataKembali) ?>)</h3>
<table class="data">
    <thead><tr>
        <th class="text-center" style="width:4%">NO</th>
        <th style="width:15%">PEMINJAM</th>
        <th style="width:14%">KEPERLUAN</th>
        <th style="width:24%">BARANG (KONDISI PINJAM)</th>
        <th style="width:12%">TGL KEMBALI</th>
        <th class="text-center" style="width:9%">TERLAMBAT</th>
        <th style="width:11%">KONDISI</th>
        <th style="width:11%">CATATAN</th>
    </tr></thead>
    <tbody>
    <?php if (empty($dataKembali)): ?>
        <tr><td colspan="8" class="empty">Tidak ada data pengembalian dalam periode ini.</td></tr>
    <?php else: foreach ($dataKembali as $i => $row):
        $barang = $row['barang_detail'] ? implode('<br>', array_map($h, explode('||', $row['barang_detail']))) : '-';
        $hari = (int)$row['hari_terlambat'];
    ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td><strong><?= $h($row['nama_peminjam']) ?></strong><br><small><?= $h($row['email_peminjam']) ?></small></td>
            <td><?= $h($row['keperluan']) ?></td>
            <td><?= $barang ?></td>
            <td><strong><?= $tgl($row['tgl_kembali_aktual']) ?></strong><br>Rencana: <?= $tgl($row['rencana_kembali']) ?></td>
            <td class="text-center"><?= $hari > 0 ? $hari . ' hari' : 'Tepat' ?></td>
            <td><?= $h($kondisiLabelMap[$row['kondisi_kembali']] ?? $row['kondisi_kembali']) ?></td>
            <td><?= $row['catatan_kerusakan'] ? $h($row['catatan_kerusakan']) : '-' ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<?php // ================== SEMUA ================== ?>
<?php elseif ($jenis === 'semua'): ?>
<h3 class="section-title">Rekap Lengkap Peminjaman &amp; Pengembalian (<?= count($dataPinjam) ?>)</h3>
<table class="data">
    <thead><tr>
        <th class="text-center" style="width:4%">NO</th>
        <th class="text-center" style="width:7%">TIPE</th>
        <th style="width:15%">PEMINJAM</th>
        <th style="width:14%">KEPERLUAN</th>
        <th style="width:25%">BARANG &amp; KONDISI</th>
        <th style="width:13%">TANGGAL</th>
        <th class="text-center" style="width:9%">TERLAMBAT</th>
        <th class="text-center" style="width:13%">STATUS / KONDISI</th>
    </tr></thead>
    <tbody>
    <?php if (empty($dataPinjam)): ?>
        <tr><td colspan="8" class="empty">Tidak ada data dalam periode ini.</td></tr>
    <?php else: foreach ($dataPinjam as $no => $pp):
        $pen = $mapKembali[$pp['id']] ?? null;
        $barangPinjam = $pp['barang_detail'] ? implode('<br>', array_map($h, explode('||', $pp['barang_detail']))) : '-';
    ?>
        <tr>
            <td class="text-center" rowspan="2"><?= $no + 1 ?></td>
            <td class="text-center"><strong>PINJAM</strong></td>
            <td><strong><?= $h($pp['nama_peminjam']) ?></strong><br><small><?= $h($pp['email_peminjam']) ?></small></td>
            <td><?= $h($pp['keperluan']) ?></td>
            <td><?= $barangPinjam ?></td>
            <td><?= $tgl($pp['tanggal_pinjam']) ?><br>Rencana: <?= $tgl($pp['rencana_kembali']) ?></td>
            <td class="text-center">-</td>
            <td class="text-center"><?= strtoupper($h($pp['status'])) ?></td>
        </tr>
        <?php if ($pen):
            $barangKembali = $pen['barang_detail'] ? implode('<br>', array_map($h, explode('||', $pen['barang_detail']))) : '-';
            $hari = (int)$pen['hari_terlambat'];
        ?>
        <tr>
            <td class="text-center"><strong>KEMBALI</strong></td>
            <td><strong><?= $h($pp['nama_peminjam']) ?></strong></td>
            <td><?= $h($pp['keperluan']) ?></td>
            <td><?= $barangKembali ?></td>
            <td><strong><?= $tgl($pen['tgl_kembali_aktual']) ?></strong><br>Diterima: <?= $h($pen['nama_penerima']) ?></td>
            <td class="text-center"><?= $hari > 0 ? $hari . ' hari' : 'Tepat' ?></td>
            <td class="text-center"><?= $h($kondisiLabelMap[$pen['kondisi_kembali']] ?? $pen['kondisi_kembali']) ?></td>
        </tr>
        <?php else: ?>
        <tr>
            <td class="text-center"><strong>KEMBALI</strong></td>
            <td colspan="6" class="empty" style="padding:8px 0">Belum dikembalikan</td>
        </tr>
        <?php endif; ?>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<?php // ================== INVENTARIS ================== ?>
<?php elseif ($jenis === 'inventaris'): ?>
<h3 class="section-title">Rekap Seluruh Barang (<?= count($dataInventaris) ?>)</h3>
<table class="data">
    <thead><tr>
        <th class="text-center" style="width:4%">NO</th>
        <th style="width:12%">KODE</th>
        <th style="width:24%">NAMA BARANG</th>
        <th style="width:15%">KATEGORI</th>
        <th class="text-center" style="width:13%">STOK</th>
        <th style="width:13%">KONDISI</th>
        <th style="width:12%">STATUS</th>
        <th class="text-center" style="width:7%">DIPINJAM</th>
    </tr></thead>
    <tbody>
    <?php if (empty($dataInventaris)): ?>
        <tr><td colspan="8" class="empty">Tidak ada data barang.</td></tr>
    <?php else: foreach ($dataInventaris as $i => $row): ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td><?= $h($row['kode_barang']) ?></td>
            <td><strong><?= $h($row['nama']) ?></strong></td>
            <td><?= $h($row['nama_kategori'] ?? '-') ?></td>
            <td class="text-center"><?= $row['stok_tersedia'] ?> / <?= $row['stok_total'] ?></td>
            <td><?= $h($kondisiLabelMap[$row['kondisi']] ?? $row['kondisi']) ?></td>
            <td><?= $h($statusLabelMap[$row['status']] ?? $row['status']) ?></td>
            <td class="text-center"><?= (int)$row['dipinjam_periode'] ?>x</td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- TTD -->
<div class="ttd">
    <div class="box">
        <div><?= date('d M Y') ?></div>
        <div>Dibuat oleh,</div>
        <div class="sp"></div>
        <div style="font-weight:700;text-decoration:underline"><?= $h($laporan['nama_pembuat']) ?></div>
    </div>
</div>

<script>
// Buka dialog cetak otomatis saat halaman dimuat (pengguna pilih "Simpan sebagai PDF").
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 400);
});
</script>
</body>
</html>
