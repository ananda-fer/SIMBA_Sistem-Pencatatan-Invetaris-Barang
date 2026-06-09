<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['admin', 'staff_tu']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    flash('error', 'ID peminjaman tidak ditemukan.');
    redirect(app_url('pages/pengembalian/index.php'));
}

$id_peminjaman = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT p.*, u.nama_lengkap FROM pengajuan_peminjaman p JOIN users u ON p.id_peminjam = u.id WHERE p.id = ? AND p.status = 'aktif'");
$stmt->bind_param('i', $id_peminjaman);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    flash('error', 'Data peminjaman tidak ditemukan atau sudah selesai.');
    redirect(app_url('pages/pengembalian/index.php'));
}

$stmt_barang = $conn->prepare("SELECT dp.*, b.nama AS nama_barang, b.kode_barang FROM detail_peminjaman dp JOIN barang b ON dp.id_barang = b.id WHERE dp.id_peminjaman = ?");
$stmt_barang->bind_param('i', $id_peminjaman);
$stmt_barang->execute();
$barang_list = $stmt_barang->get_result()->fetch_all(MYSQLI_ASSOC);

$hari_sisa   = (int) ceil((strtotime($data['tanggal_kembali']) - time()) / 86400);
$sudah_lewat = $hari_sisa < 0;

$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pengembalian — SIMBA</title>
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
                <h2>Proses Pengembalian</h2>
                <p>Input data pengembalian barang peminjaman</p>
            </div>
            <a href="index.php" class="btn btn-outline">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Kembali
            </a>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px">

            <div class="card">
                <div style="font-weight:700; font-size:13px; color:#1a1a1a; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border)">
                    Informasi Peminjaman
                </div>
                <table style="width:100%; border-collapse:collapse;font-size:13px">
                    <tr>
                        <td style="color:#6b7280; padding:5px 0; width:140px">Nama Peminjam</td>
                        <td style="font-weight:600; color:#1a1a1a"><?= sanitasi($data['nama_lengkap']) ?></td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280; padding:5px 0">Keperluan</td>
                        <td><?= sanitasi($data['keperluan']) ?></td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280; padding:5px 0">Tanggal Pinjam</td>
                        <td><?= fmt_tgl($data['tanggal_pinjam']) ?></td>
                    </tr>
                    <tr>
                        <td style="color:#6b7280; padding:5px 0">Batas Kembali</td>
                        <td>
                            <span style="<?= $sudah_lewat ? 'color:#dc2626;font-weight:600' : 'color:#059669; font-weight:600' ?>">
                                <?= fmt_tgl($data['tanggal_kembali']) ?>
                            </span>
                            <?php if ($sudah_lewat): ?>
                            <div style="font-size:11px; color:#dc2626; margin-top:2px"><?= abs($hari_sisa) ?> hari terlambat</div>
                            <?php else: ?>
                            <div style="font-size:11px; color:#6b7280; margin-top:2px">Sisa <?= $hari_sisa ?> hari</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <div style="font-weight:700; font-size:13px; color:#1a1a1a; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border)">
                    Barang yang Dipinjam
                </div>
                <?php foreach ($barang_list as $b): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:7px 0;border-bottom:1px solid var(--border-light); font-size:13px">
                    <div>
                        <div style="font-weight:600; color:#1a1a1a"><?= sanitasi($b['nama_barang']) ?></div>
                        <div style="font-size:11px; color:#6b7280"><?= sanitasi($b['kode_barang']) ?></div>
                    </div>
                    <span class="badge badge-aktif"><?= (int)$b['jumlah'] ?> unit</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="max-width:560px">
            <div style="font-weight:700; font-size:13px; color:#1a1a1a; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid var(--border)">
                Data Pengembalian
            </div>
            <form action="proses_pengembalian.php" method="POST">
                <input type="hidden" name="id_peminjaman" value="<?= $id_peminjaman ?>">

                <div class="form-group">
                    <label class="form-label">Tanggal Dikembalikan</label>
                    <input type="date" name="tanggal_kembali" class="form-input"
                           value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi Barang</label>
                    <select name="kondisi_kembali" class="form-input" required>
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Kondisi / Kerusakan</label>
                    <textarea name="catatan_kerusakan" class="form-input" rows="3"
                              placeholder="Isi jika ada catatan kondisi atau kerusakan..."></textarea>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px">
                    <button type="submit" name="submit_kembali" class="btn btn-primary btn-kirim">
                        Simpan Pengembalian
                    </button>
                    <a href="index.php" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </main>
</div>
<script src="<?= app_url('assets/js/app.js') ?>"></script>
</body>
</html>
