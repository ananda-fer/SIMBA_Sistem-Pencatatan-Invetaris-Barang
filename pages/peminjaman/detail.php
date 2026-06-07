<?php
// pages/peminjaman/detail.php — Detail Peminjaman
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

$peran = $_SESSION['peran'];

// Ambil data peminjaman
$stmt = $conn->prepare("
    SELECT pp.*,
           u.nama_lengkap  AS nama_peminjam,
           u.email         AS email_peminjam,
           u.peran         AS peran_peminjam,
           vf.nama_lengkap AS nama_verifikator,
           ap.nama_lengkap AS nama_approver
    FROM pengajuan_peminjaman pp
    JOIN users u    ON u.id = pp.id_peminjam
    LEFT JOIN users vf ON vf.id = pp.diverifikasi_oleh
    LEFT JOIN users ap ON ap.id = pp.disetujui_oleh
    WHERE pp.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$peminjaman = $stmt->get_result()->fetch_assoc();

if (!$peminjaman) redirect('index.php');

// Jika peminjam, hanya bisa lihat miliknya
if ($peran === 'peminjam' && $peminjaman['id_peminjam'] != $_SESSION['user_id']) {
    redirect('index.php');
}

// Ambil detail barang
$detail_stmt = $conn->prepare("
    SELECT dp.*, b.nama AS nama_barang, b.kode_barang, (b.stok_total <= 3) AS wajib_surat, k.nama AS kategori
    FROM detail_peminjaman dp
    JOIN barang b ON b.id = dp.id_barang
    JOIN kategori k ON k.id = b.kategori_id
    WHERE dp.id_peminjaman = ?
");
$detail_stmt->bind_param('i', $id);
$detail_stmt->execute();
$detail_list = $detail_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Status alur steps
$status_order = ['menunggu','diverifikasi','disetujui','aktif','selesai'];
$current_idx  = array_search($peminjaman['status'], $status_order);

$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman — SIMBA</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>

<div class="app-wrapper">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php if ($flash_success): ?>
        <span id="flash-success-data" style="display:none"><?= sanitasi($flash_success) ?></span>
        <?php endif; ?>

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div style="display:flex;align-items:center;gap:12px">
                <a href="index.php" class="btn btn-outline btn-sm">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Kembali
                </a>
                <div>
                    <h2>Detail Peminjaman #<?= $id ?></h2>
                    <p>Informasi lengkap pengajuan peminjaman</p>
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <?= badge_status($peminjaman['status']) ?>

                <?php if ($peran === 'staff_tu' && in_array($peminjaman['status'], ['menunggu','diverifikasi'])): ?>
                <a href="approval.php?id=<?= $id ?>" class="btn-verifikasi">Approval</a>

                <?php elseif ($peran === 'staff_tu' && $peminjaman['status'] === 'disetujui'): ?>
                <a href="serahkan.php?id=<?= $id ?>" class="btn btn-approve btn-sm"
                   onclick="return confirm('Serahkan barang dan ubah status peminjaman menjadi aktif?')">Serahkan Barang</a>

                <?php elseif ($peran === 'peminjam' && in_array($peminjaman['status'], ['menunggu','diverifikasi'])): ?>
                <button type="button" class="btn btn-danger btn-sm"
                        onclick="konfirmasiBatal(<?= $id ?>)">Batalkan</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- ALUR STATUS -->
        <?php if ($peminjaman['status'] !== 'ditolak' && $peminjaman['status'] !== 'dibatalkan'): ?>
        <div class="card">
            <div style="font-size:12.5px;font-weight:600;color:#6b7280;margin-bottom:14px">ALUR STATUS</div>
            <div class="alur-steps">
                <?php
                $step_labels = ['Menunggu','Diverifikasi','Disetujui','Aktif','Selesai'];
                foreach ($status_order as $si => $s):
                    $done = ($current_idx !== false && $si <= $current_idx);
                ?>
                <div class="alur-step <?= $done ? 'done' : '' ?>">
                    <div class="step-dot"></div>
                    <div class="step-label"><?= $step_labels[$si] ?></div>
                </div>
                <?php if ($si < count($status_order)-1): ?>
                <div class="alur-arrow">›</div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($peminjaman['status'] === 'menunggu'): ?>
            <div style="margin-top:10px;font-size:12px;color:#9ca3af">Menunggu verifikasi dari Staff TU</div>
            <?php elseif ($peminjaman['status'] === 'diverifikasi'): ?>
            <div style="margin-top:10px;font-size:12px;color:#1e40af">Sudah diverifikasi, menunggu keputusan Staff TU</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($peminjaman['status'] === 'ditolak'): ?>
        <div class="alert alert-error">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <div>
                <strong>Pengajuan Ditolak</strong>
                <?php if ($peminjaman['catatan_tolak']): ?>
                <div style="margin-top:4px">Alasan: <?= sanitasi($peminjaman['catatan_tolak']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- GRID DETAIL -->
        <div class="detail-grid">

            <!-- Info Peminjam -->
            <div class="card">
                <div class="card-title">Informasi Peminjam</div>
                <table class="detail-table">
                    <tr>
                        <td class="detail-label">Nama</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['nama_peminjam']) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Email</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['email_peminjam']) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Role</td>
                        <td class="detail-value"><?= ucfirst(sanitasi($peminjaman['peran_peminjam'])) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Keperluan</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['keperluan']) ?></td>
                    </tr>
                </table>
            </div>

            <!-- Info Peminjaman -->
            <div class="card">
                <div class="card-title">Informasi Peminjaman</div>
                <table class="detail-table">
                    <tr>
                        <td class="detail-label">Tgl Pinjam</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['tanggal_pinjam']) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Tgl Kembali</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['tanggal_kembali']) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Diverifikasi</td>
                        <td class="detail-value"><?= $peminjaman['nama_verifikator'] ? sanitasi($peminjaman['nama_verifikator']) : '<span class="text-muted">Belum</span>' ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Disetujui</td>
                        <td class="detail-value"><?= $peminjaman['nama_approver'] ? sanitasi($peminjaman['nama_approver']) : '<span class="text-muted">Belum</span>' ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Dibuat</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['dibuat_pada']) ?></td>
                    </tr>
                    <?php if (!empty($peminjaman['file_surat'])): ?>
                    <tr>
                        <td class="detail-label">Surat</td>
                        <td class="detail-value">
                            <a href="<?= app_url(sanitasi($peminjaman['file_surat'])) ?>" target="_blank" class="btn btn-outline btn-sm">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                Lihat PDF
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

        </div>

        <!-- Daftar Barang -->
        <div class="card">
            <div class="card-title">Daftar Barang Dipinjam</div>
            <div class="table-wrapper">
                <table class="barang-table">
                    <thead>
                        <tr>
                            <th>BARANG</th>
                            <th>KATEGORI</th>
                            <th>JUMLAH</th>
                            <th>SURAT</th>
                            <th>KONDISI PINJAM</th>
                            <?php if (in_array($peminjaman['status'], ['selesai'])): ?>
                            <th>KONDISI KEMBALI</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detail_list as $d): ?>
                    <tr>
                        <td>
                            <div class="item-nama"><?= sanitasi($d['nama_barang']) ?></div>
                            <div class="item-kode"><?= sanitasi($d['kode_barang']) ?></div>
                        </td>
                        <td><?= sanitasi($d['kategori']) ?></td>
                        <td><strong><?= (int)$d['jumlah'] ?></strong></td>
                        <td><?= badge_surat((bool)$d['wajib_surat']) ?></td>
                        <td><?= badge_status($d['kondisi_saat_pinjam']) ?></td>
                        <?php if (in_array($peminjaman['status'], ['selesai'])): ?>
                        <td><?= $d['kondisi_saat_kembali'] ? badge_status($d['kondisi_saat_kembali']) : '<span class="text-muted">-</span>' ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
