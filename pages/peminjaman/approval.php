<?php
// pages/peminjaman/approval.php - Staff TU verifikasi dan approval pengajuan
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['staff_tu']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

// Ambil data
$has_file_surat_column = mysqli_num_rows(
    mysqli_query($conn, "SHOW COLUMNS FROM pengajuan_peminjaman LIKE 'file_surat'")
) > 0;
$select_file_surat = $has_file_surat_column ? ", pp.file_surat" : ", NULL AS file_surat";

$stmt = $conn->prepare("
    SELECT pp.*,
           u.nama_lengkap  AS nama_peminjam,
           u.peran         AS peran_peminjam
           $select_file_surat
    FROM pengajuan_peminjaman pp
    JOIN users u ON u.id = pp.id_peminjam
    WHERE pp.id = ? AND pp.status IN ('menunggu', 'diverifikasi')
");
$stmt->bind_param('i', $id);
$stmt->execute();
$peminjaman = $stmt->get_result()->fetch_assoc();
if (!$peminjaman) redirect('index.php');

// Barang yang dipinjam
$d_stmt = $conn->prepare("
    SELECT dp.*, b.nama AS nama_barang, b.kode_barang, b.stok_tersedia, (b.stok_total <= 3) AS wajib_surat, k.nama AS kategori
    FROM detail_peminjaman dp
    JOIN barang b ON b.id = dp.id_barang
    JOIN kategori k ON k.id = b.kategori_id
    WHERE dp.id_peminjaman = ?
");
$d_stmt->bind_param('i', $id);
$d_stmt->execute();
$detail_list = $d_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Cek ketersediaan stok
$stok_ok = true;
foreach ($detail_list as $d) {
    if ($d['jumlah'] > $d['stok_tersedia']) {
        $stok_ok = false;
        break;
    }
}

// ----------------------
// PROSES APPROVAL
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'setujui' && $stok_ok) {
        $conn->begin_transaction();
        try {
            $stmt2 = $conn->prepare("
                UPDATE pengajuan_peminjaman
                SET status = 'disetujui',
                    diverifikasi_oleh = ?,
                    disetujui_oleh = ?,
                    diperbarui_pada = NOW()
                WHERE id = ?
            ");
            $stmt2->bind_param('iii', $_SESSION['user_id'], $_SESSION['user_id'], $id);
            $stmt2->execute();

            foreach ($detail_list as $d) {
                $upd = $conn->prepare("
                    UPDATE barang
                    SET stok_tersedia = stok_tersedia - ?,
                        status = CASE WHEN stok_tersedia - ? <= 0 THEN 'dipinjam' ELSE status END
                    WHERE id = ?
                ");
                $upd->bind_param('iii', $d['jumlah'], $d['jumlah'], $d['id_barang']);
                $upd->execute();
            }

            $conn->commit();
            flash('success', "Pengajuan #$id disetujui oleh Staff TU. Stok barang telah dikurangi.");
            redirect('index.php');
        } catch (Exception $e) {
            $conn->rollback();
            $error_global = 'Gagal menyetujui: ' . $e->getMessage();
        }
    } elseif ($aksi === 'tolak') {
        $catatan = sanitasi($_POST['catatan_tolak'] ?? '');
        if (empty($catatan)) {
            $error_tolak = 'Alasan penolakan wajib diisi.';
        } else {
            $stmt3 = $conn->prepare("
                UPDATE pengajuan_peminjaman
                SET status = 'ditolak',
                    diverifikasi_oleh = ?,
                    disetujui_oleh = ?,
                    catatan_tolak = ?,
                    diperbarui_pada = NOW()
                WHERE id = ?
            ");
            $stmt3->bind_param('iisi', $_SESSION['user_id'], $_SESSION['user_id'], $catatan, $id);
            if ($stmt3->execute()) {
                flash('success', "Pengajuan #$id ditolak oleh Staff TU.");
                redirect('index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Peminjaman - SIMBA</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>

<div class="app-wrapper">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="page-header">
            <div style="display:flex;align-items:center;gap:12px">
                <a href="index.php" class="btn btn-outline btn-sm">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Kembali
                </a>
                <div>
                    <h2>Approval Pengajuan #<?= $id ?></h2>
                    <p>Periksa ketersediaan barang, lalu setujui atau tolak pengajuan</p>
                </div>
            </div>
            <?= badge_status($peminjaman['status']) ?>
        </div>

        <?php if (isset($error_global)): ?>
        <div class="alert alert-error"><?= sanitasi($error_global) ?></div>
        <?php endif; ?>

        <?php if (isset($error_tolak)): ?>
        <div class="alert alert-error"><?= sanitasi($error_tolak) ?></div>
        <?php endif; ?>

        <?php if (!$stok_ok): ?>
        <div class="alert alert-warning">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <strong>Perhatian:</strong> Stok beberapa barang tidak mencukupi. Tidak dapat disetujui.
        </div>
        <?php endif; ?>

        <div class="detail-grid">

            <!-- Info Peminjaman -->
            <div class="card">
                <div class="card-title">Informasi Pengajuan</div>
                <table class="detail-table">
                    <tr>
                        <td class="detail-label">Peminjam</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['nama_peminjam']) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Role</td>
                        <td class="detail-value"><?= ucfirst(sanitasi($peminjaman['peran_peminjam'])) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Keperluan</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['keperluan']) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Tgl Pinjam</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['tanggal_pinjam']) ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">Tgl Kembali</td>
                        <td class="detail-value"><?= sanitasi($peminjaman['tanggal_kembali']) ?></td>
                    </tr>
                    <?php if (!empty($peminjaman['file_surat'])): ?>
                    <tr>
                        <td class="detail-label">Surat</td>
                        <td class="detail-value">
                            <a href="<?= app_url(sanitasi($peminjaman['file_surat'])) ?>" target="_blank" class="btn btn-outline btn-sm">Lihat PDF</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Daftar Barang -->
            <div class="card">
                <div class="card-title">Barang yang Diminta</div>
                <table class="barang-table">
                    <thead>
                        <tr>
                            <th>BARANG</th>
                            <th>DIMINTA</th>
                            <th>TERSEDIA</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detail_list as $d): ?>
                    <tr>
                        <td>
                            <div class="item-nama"><?= sanitasi($d['nama_barang']) ?></div>
                            <div class="item-kode"><?= sanitasi($d['kategori']) ?></div>
                        </td>
                        <td><strong><?= (int)$d['jumlah'] ?></strong></td>
                        <td>
                            <span class="stok-badge" style="<?= $d['stok_tersedia'] < $d['jumlah'] ? 'background:#fee2e2;color:#991b1b' : '' ?>">
                                <?= (int)$d['stok_tersedia'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($d['stok_tersedia'] >= $d['jumlah']): ?>
                            <span style="color:#065f46;font-size:12px;font-weight:600">Tersedia</span>
                            <?php else: ?>
                            <span style="color:#991b1b;font-size:12px;font-weight:600">Kurang</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- AKSI APPROVAL -->
        <div class="approval-actions">

            <!-- Setujui -->
            <div class="approval-box approve-box">
                <h4>Setujui Pengajuan</h4>
                <p style="font-size:12.5px;color:#065f46;margin-bottom:14px;line-height:1.5">
                    Stok barang tersedia dan data pengajuan valid. Persetujuan dilakukan oleh Staff TU dan stok akan dikurangi otomatis.
                </p>
                <form method="POST">
                    <input type="hidden" name="aksi" value="setujui">
                    <button type="submit" class="btn btn-approve btn-full"
                            <?= !$stok_ok ? 'disabled style="opacity:0.5;cursor:not-allowed"' : '' ?>>
                        Setujui & Kurangi Stok
                    </button>
                </form>
            </div>

            <!-- Tolak -->
            <div class="approval-box tolak-box">
                <h4>Tolak Pengajuan</h4>
                <form method="POST">
                    <input type="hidden" name="aksi" value="tolak">
                    <div class="form-group">
                        <textarea name="catatan_tolak" class="form-input" rows="3"
                                  placeholder="Tuliskan alasan penolakan..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-full">Tolak Pengajuan</button>
                </form>
            </div>

        </div>

    </main>
</div>

<div id="toast-container" class="toast-container"></div>
<script src="<?= app_url('assets/js/app.js') ?>"></script>
</body>
</html>
