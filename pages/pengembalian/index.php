<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['admin', 'staff_tu']);

$cari   = sanitasi($_GET['cari'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_aktif  = ['p.status = ?'];
$params_aktif = ['aktif'];
$types_aktif  = 's';

if ($cari !== '') {
    $where_aktif[] = '(u.nama_lengkap LIKE ? OR p.keperluan LIKE ?)';
    $like = "%$cari%";
    $params_aktif[] = $like;
    $params_aktif[] = $like;
    $types_aktif   .= 'ss';
}

$where_aktif_sql = implode(' AND ', $where_aktif);

$sql_aktif = "SELECT p.id AS id_peminjaman, u.nama_lengkap AS nama_peminjam,
              p.keperluan, p.tanggal_pinjam, p.tanggal_kembali AS batas_kembali,
              GROUP_CONCAT(CONCAT(b.nama, ' (', dp.jumlah, ')') SEPARATOR ', ') AS detail_barang,
              DATEDIFF(CURDATE(), p.tanggal_kembali) AS hari_terlambat
              FROM pengajuan_peminjaman p
              JOIN users u ON p.id_peminjam = u.id
              JOIN detail_peminjaman dp ON dp.id_peminjaman = p.id
              JOIN barang b ON dp.id_barang = b.id
              WHERE $where_aktif_sql
              GROUP BY p.id
              ORDER BY p.tanggal_kembali ASC";

$stmt_aktif = $conn->prepare($sql_aktif);
$stmt_aktif->bind_param($types_aktif, ...$params_aktif);
$stmt_aktif->execute();
$rows_aktif = $stmt_aktif->get_result()->fetch_all(MYSQLI_ASSOC);

$where_r  = ['1=1'];
$params_r = [];
$types_r  = '';

if ($cari !== '') {
    $where_r[] = '(u_peminjam.nama_lengkap LIKE ? OR p.keperluan LIKE ?)';
    $like = "%$cari%";
    $params_r[] = $like;
    $params_r[] = $like;
    $types_r   .= 'ss';
}

$where_r_sql = implode(' AND ', $where_r);

$sql_riwayat = "SELECT kr.id, u_peminjam.nama_lengkap AS nama_peminjam,
                u_petugas.nama_lengkap AS nama_petugas,
                kr.tanggal_kembali, kr.hari_terlambat, kr.kondisi_kembali, kr.catatan_kerusakan,
                p.keperluan,
                GROUP_CONCAT(CONCAT(b.nama, ' (', dp.jumlah, ')') SEPARATOR ', ') AS detail_barang
                FROM pengembalian kr
                JOIN pengajuan_peminjaman p ON kr.id_peminjaman = p.id
                JOIN users u_peminjam ON p.id_peminjam = u_peminjam.id
                JOIN users u_petugas ON kr.diterima_oleh = u_petugas.id
                JOIN detail_peminjaman dp ON dp.id_peminjaman = p.id
                JOIN barang b ON dp.id_barang = b.id
                WHERE $where_r_sql
                GROUP BY kr.id
                ORDER BY kr.tanggal_kembali DESC";

$stmt_r = $conn->prepare($sql_riwayat);
if ($types_r) $stmt_r->bind_param($types_r, ...$params_r);
$stmt_r->execute();
$rows_riwayat = $stmt_r->get_result()->fetch_all(MYSQLI_ASSOC);

$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Barang — SIMBA</title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>

<?php if ($flash_success): ?>
<script>alert("<?= addslashes($flash_success) ?>")</script>
<?php endif; ?>
<?php if ($flash_error): ?>
<script>alert("<?= addslashes($flash_error) ?>")</script>
<?php endif; ?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h2>Pengembalian Barang</h2>
                <p>Kelola proses pengembalian dan riwayat kondisi barang</p>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET">
                <div class="filter-row">
                    <input type="text" name="cari" class="form-input" style="flex:1; min-width:200px"
                           placeholder="Cari nama peminjam / keperluan..."
                           value="<?= sanitasi($cari) ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-outline">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        Cari
                    </button>
                    <a href="index.php" class="btn btn-outline">Reset</a>
                </div>
            </form>
        </div>

        <h3 style="font-size:14px; font-weight:700; color:#1a1a1a; margin:0 0 10px 0">Daftar Peminjaman Aktif</h3>
        <div class="card" style="padding:0; overflow:hidden; margin-bottom:28px">
            <div class="table-wrapper">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width:40px">NO</th>
                            <th>PEMINJAM</th>
                            <th>KEPERLUAN</th>
                            <th>BARANG DIPINJAM</th>
                            <th>TGL PINJAM</th>
                            <th>BATAS KEMBALI</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows_aktif)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                                        <rect x="9" y="3" width="6" height="4" rx="1"/>
                                    </svg>
                                    <p>Tidak ada peminjaman aktif saat ini</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows_aktif as $i => $row):
                            $terlambat = (int)$row['hari_terlambat'] > 0;
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <div class="nama-peminjam"><?= sanitasi($row['nama_peminjam']) ?></div>
                            </td>
                            <td><?= sanitasi($row['keperluan']) ?></td>
                            <td style="max-width:200px; font-size:12px; color:#555"><?= sanitasi($row['detail_barang']) ?></td>
                            <td><?= fmt_tgl($row['tanggal_pinjam']) ?></td>
                            <td>
                                <span style="<?= $terlambat ? 'color:#991b1b;font-weight:600' : '' ?>">
                                    <?= fmt_tgl($row['batas_kembali']) ?>
                                </span>
                                <?php if ($terlambat): ?>
                                <div style="font-size:11px; color:#dc2626; margin-top:2px">
                                    <?= (int)$row['hari_terlambat'] ?> hari terlambat
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="form_pengembalian.php?id=<?= $row['id_peminjaman'] ?>" class="btn btn-primary btn-sm">
                                    Proses
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h3 style="font-size:14px; font-weight:700; color:#1a1a1a; margin:0 0 10px 0">Riwayat Pengembalian Barang</h3>
        <div class="card" style="padding:0; overflow:hidden">
            <div class="table-wrapper">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width:40px">NO</th>
                            <th>PEMINJAM</th>
                            <th>BARANG DIKEMBALIKAN</th>
                            <th>TGL KEMBALI</th>
                            <th>KETERLAMBATAN</th>
                            <th>KONDISI</th>
                            <th>CATATAN</th>
                            <th>DITERIMA OLEH</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows_riwayat)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <p>Belum ada riwayat pengembalian</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows_riwayat as $i => $rw): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <div class="nama-peminjam"><?= sanitasi($rw['nama_peminjam']) ?></div>
                                <div class="role-mini"><?= sanitasi($rw['keperluan']) ?></div>
                            </td>
                            <td style="max-width:180px; font-size:12px; color:#555"><?= sanitasi($rw['detail_barang']) ?></td>
                            <td><?= fmt_tgl($rw['tanggal_kembali']) ?></td>
                            <td>
                                <?php if ((int)$rw['hari_terlambat'] > 0): ?>
                                <span class="badge badge-ditolak"><?= (int)$rw['hari_terlambat'] ?> hari</span>
                                <?php else: ?>
                                <span class="badge badge-selesai">Tepat Waktu</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $kondisi_label = ['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_berat' => 'Rusak Berat'];
                                $kondisi_class = ['baik' => 'badge-selesai', 'rusak_ringan' => 'badge-menunggu', 'rusak_berat' => 'badge-ditolak'];
                                $k = $rw['kondisi_kembali'];
                                ?>
                                <span class="badge <?= $kondisi_class[$k] ?? 'badge-aktif' ?>">
                                    <?= $kondisi_label[$k] ?? ucfirst($k) ?>
                                </span>
                            </td>
                            <td style="font-size:12px; color:#555"><?= sanitasi($rw['catatan_kerusakan'] ?: '-') ?></td>
                            <td><?= sanitasi($rw['nama_petugas']) ?></td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap">
                                    <a href="edit_pengembalian.php?id=<?= $rw['id'] ?>" class="btn-detail">Edit</a>
                                    <a href="hapus_pengembalian.php?id=<?= $rw['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus data pengembalian ini? Status peminjaman akan kembali menjadi aktif.')">
                                       Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script src="<?= app_url('assets/js/app.js') ?>"></script>
</body>
</html>
