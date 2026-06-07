<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

hanya_role(['admin', 'staff_tu']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    flash('error', 'ID pengembalian tidak ditemukan.');
    redirect(app_url('pages/pengembalian/index.php'));
}

$id_pengembalian = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT kr.*, p.keperluan, p.tanggal_pinjam, p.tanggal_kembali AS batas_kembali, u.nama_lengkap
                        FROM pengembalian kr
                        JOIN pengajuan_peminjaman p ON kr.id_peminjaman = p.id
                        JOIN users u ON p.id_peminjam = u.id
                        WHERE kr.id = ?");
$stmt->bind_param('i', $id_pengembalian);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    flash('error', 'Data pengembalian tidak ditemukan.');
    redirect(app_url('pages/pengembalian/index.php'));
}

$id_peminjaman = $data['id_peminjaman'];

$stmt_barang = $conn->prepare("SELECT dp.*, b.nama AS nama_barang, b.kode_barang FROM detail_peminjaman dp JOIN barang b ON dp.id_barang = b.id WHERE dp.id_peminjaman = ?");
$stmt_barang->bind_param('i', $id_peminjaman);
$stmt_barang->execute();
$barang_list = $stmt_barang->get_result()->fetch_all(MYSQLI_ASSOC);

$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengembalian — SIMBA</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
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
                <h2>Edit Data Pengembalian</h2>
                <p>Perbarui data pengembalian barang</p>
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
                <table style="width:100%; border-collapse:collapse; font-size:13px">
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
                        <td><?= fmt_tgl($data['batas_kembali']) ?></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <div style="font-weight:700; font-size:13px; color:#1a1a1a; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border)">
                    Barang yang Dipinjam
                </div>
                <?php foreach ($barang_list as $b): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:1px solid var(--border-light); font-size:13px">
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
                Edit Data Pengembalian
            </div>
            <form action="proses_edit_pengembalian.php" method="POST">
                <input type="hidden" name="id_pengembalian" value="<?= $id_pengembalian ?>">

                <div class="form-group">
                    <label class="form-label">Tanggal Dikembalikan</label>
                    <input type="date" name="tanggal_kembali" class="form-input"
                           value="<?= sanitasi($data['tanggal_kembali']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi Barang</label>
                    <select name="kondisi_kembali" class="form-input" required>
                        <?php foreach (['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_berat' => 'Rusak Berat'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $data['kondisi_kembali'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Kondisi / Kerusakan</label>
                    <textarea name="catatan_kerusakan" class="form-input" rows="3"
                              placeholder="Isi jika ada catatan kondisi atau kerusakan..."><?= sanitasi($data['catatan_kerusakan'] ?? '') ?></textarea>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px">
                    <button type="submit" name="submit_edit" class="btn btn-primary btn-kirim">
                        Simpan Perubahan
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
