<?php
// pages/peminjaman/index.php — Daftar Peminjaman
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

$peran = $_SESSION['peran'];

// ----------------------
// FILTER & PAGINATION
// ----------------------
$cari        = sanitasi($_GET['cari'] ?? '');
$filter_stat = sanitasi($_GET['status'] ?? '');
$filter_tgl  = sanitasi($_GET['tanggal'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;
$offset      = ($page - 1) * $per_page;

// Tabs — status yang ditampilkan
$tab_aktif = sanitasi($_GET['tab'] ?? 'semua');

// Bila tab dipilih, override filter status
if ($tab_aktif !== 'semua') {
    $filter_stat = $tab_aktif;
}

// ----------------------
// QUERY
// ----------------------
// Untuk peminjam: hanya tampilkan miliknya sendiri
$where_parts = ['1=1'];
$params      = [];
$types       = '';

if ($peran === 'peminjam') {
    $where_parts[] = 'pp.id_peminjam = ?';
    $params[]      = $_SESSION['user_id'];
    $types        .= 'i';
}

if ($cari !== '') {
    $where_parts[] = '(u.nama_lengkap LIKE ? OR pp.keperluan LIKE ?)';
    $like           = "%$cari%";
    $params[]       = $like;
    $params[]       = $like;
    $types         .= 'ss';
}

if ($filter_stat && $filter_stat !== 'semua') {
    $where_parts[] = 'pp.status = ?';
    $params[]      = $filter_stat;
    $types        .= 's';
}

if ($filter_tgl) {
    $where_parts[] = 'pp.tanggal_pinjam = ?';
    $params[]      = $filter_tgl;
    $types        .= 's';
}

$where_sql = implode(' AND ', $where_parts);

// Hitung total
$count_sql  = "SELECT COUNT(*) FROM pengajuan_peminjaman pp 
               JOIN users u ON u.id = pp.id_peminjam
               WHERE $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_row()[0];
$total_pages = max(1, ceil($total_rows / $per_page));

// Data utama
$sql = "SELECT pp.*, 
               u.nama_lengkap  AS nama_peminjam,
               u.peran         AS peran_peminjam,
               vf.nama_lengkap AS nama_verifikator,
               ap.nama_lengkap AS nama_approver,
               EXISTS(
                   SELECT 1 FROM detail_peminjaman dp
                   JOIN barang b ON b.id = dp.id_barang
                   WHERE dp.id_peminjaman = pp.id AND b.stok_total <= 3
               ) AS ada_wajib_surat
        FROM pengajuan_peminjaman pp
        JOIN users u    ON u.id  = pp.id_peminjam
        LEFT JOIN users vf ON vf.id = pp.diverifikasi_oleh
        LEFT JOIN users ap ON ap.id = pp.disetujui_oleh
        WHERE $where_sql
        ORDER BY pp.dibuat_pada DESC
        LIMIT ? OFFSET ?";

$params[]  = $per_page;
$params[]  = $offset;
$types    .= 'ii';

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung per-tab
$tab_counts_sql = "SELECT status, COUNT(*) as total 
                   FROM pengajuan_peminjaman pp
                   " . ($peran === 'peminjam' ? "WHERE id_peminjam = " . (int)$_SESSION['user_id'] : "") . "
                   GROUP BY status";
$tab_counts_res = $conn->query($tab_counts_sql);
$tab_counts = [];
while ($r = $tab_counts_res->fetch_assoc()) {
    $tab_counts[$r['status']] = $r['total'];
}
$total_menunggu = $tab_counts['menunggu'] ?? 0;

$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Peminjaman — SIMBA</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>

<div class="app-wrapper">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <!-- Flash message sebagai hidden element -->
        <?php if ($flash_success): ?>
        <span id="flash-success-data" style="display:none"><?= sanitasi($flash_success) ?></span>
        <?php endif; ?>
        <?php if ($flash_error): ?>
        <span id="flash-error-data" style="display:none"><?= sanitasi($flash_error) ?></span>
        <?php endif; ?>

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <h2>Daftar Peminjaman</h2>
                <p>Kelola semua pengajuan peminjaman barang</p>
            </div>
            <?php if ($peran === 'peminjam'): ?>
            <a href="ajukan.php" class="btn-ajukan">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Ajukan Peminjaman
            </a>
            <?php endif; ?>
        </div>

        <!-- STATUS TABS -->
        <div class="status-tabs">
            <?php
            $tabs = [
                'semua'        => 'Semua',
                'menunggu'     => 'Menunggu',
                'diverifikasi' => 'Diverifikasi',
                'disetujui'    => 'Disetujui',
                'aktif'        => 'Aktif',
                'selesai'      => 'Selesai',
            ];
            if ($peran !== 'peminjam') $tabs['ditolak'] = 'Ditolak';
            foreach ($tabs as $key => $label):
                $is_active = $tab_aktif === $key;
                $count     = $tab_counts[$key] ?? null;
                $qs = http_build_query(array_merge($_GET, ['tab' => $key, 'page' => 1]));
            ?>
            <a href="?<?= $qs ?>" class="status-tab <?= $is_active ? 'active' : '' ?>">
                <?= $label ?>
                <?php if ($key === 'menunggu' && ($count ?? 0) > 0): ?>
                <span class="status-count"><?= $count ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- FILTER -->
        <div class="filter-card">
            <form method="GET">
                <input type="hidden" name="tab" value="<?= sanitasi($tab_aktif) ?>">
                <div class="filter-row">
                    <input type="text" name="cari" class="form-input" style="flex:1;min-width:200px"
                           placeholder="Cari peminjam / keperluan..."
                           value="<?= sanitasi($cari) ?>">
                    <select name="status" class="form-input filter-select">
                        <option value="">Semua Status</option>
                        <?php foreach (['menunggu','diverifikasi','disetujui','ditolak','aktif','selesai','dibatalkan'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filter_stat === $s ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_',' ',$s)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="tanggal" class="form-input" style="min-width:160px"
                           value="<?= sanitasi($filter_tgl) ?>">
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

        <!-- TABEL -->
        <div class="card" style="padding:0;overflow:hidden">
            <div class="table-wrapper">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width:40px">NO</th>
                            <th>PEMINJAM</th>
                            <th>KEPERLUAN</th>
                            <th>TGL PINJAM</th>
                            <th>TGL KEMBALI</th>
                            <th>SURAT</th>
                            <th>STATUS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <p>Tidak ada data peminjaman ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $i => $row): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td>
                                <div class="nama-peminjam"><?= sanitasi($row['nama_peminjam']) ?></div>
                                <div class="role-mini"><?= ucfirst($row['peran_peminjam']) ?></div>
                            </td>
                            <td><?= sanitasi($row['keperluan']) ?></td>
                            <td><?= sanitasi($row['tanggal_pinjam']) ?></td>
                            <td><?= sanitasi($row['tanggal_kembali']) ?></td>
                            <td><?= badge_surat((bool)$row['ada_wajib_surat']) ?></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    <a href="detail.php?id=<?= $row['id'] ?>" class="btn-detail">Detail</a>

                                    <?php if ($peran === 'staff_tu' && in_array($row['status'], ['menunggu','diverifikasi'])): ?>
                                    <a href="approval.php?id=<?= $row['id'] ?>" class="btn-verifikasi">Approval</a>

                                    <?php elseif ($peran === 'staff_tu' && $row['status'] === 'disetujui'): ?>
                                    <a href="serahkan.php?id=<?= $row['id'] ?>" class="btn btn-approve btn-sm"
                                       onclick="return confirm('Serahkan barang dan ubah status peminjaman menjadi aktif?')">Serahkan</a>

                                    <?php elseif ($peran === 'peminjam' && in_array($row['status'], ['menunggu','diverifikasi'])): ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="konfirmasiBatal(<?= $row['id'] ?>)">Batal</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div style="padding:0 20px 16px">
                <div class="pagination">
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>"
                       class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/>
                        </svg>
                    </a>
                    <?php for ($p = max(1, $page-2); $p <= min($total_pages, $page+2); $p++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
                       class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"
                       class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/>
                        </svg>
                    </a>
                </div>
                <div class="table-info">
                    Menampilkan <?= $offset + 1 ?>–<?= min($offset + $per_page, $total_rows) ?> dari <?= $total_rows ?> data
                </div>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Modal Konfirmasi Batal -->
<div class="modal-overlay" id="confirm-modal">
    <div class="modal-box">
        <div class="modal-icon modal-icon-danger">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div class="modal-title" id="modal-title">Batalkan Pengajuan?</div>
        <div class="modal-desc" id="modal-desc">Pengajuan yang dibatalkan tidak dapat dikembalikan.</div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Tidak</button>
            <button type="button" class="btn btn-danger" id="modal-confirm-btn">Ya, Batalkan</button>
        </div>
    </div>
</div>

<div id="toast-container" class="toast-container"></div>
<script src="<?= app_url('assets/js/app.js') ?>"></script>
</body>
</html>
