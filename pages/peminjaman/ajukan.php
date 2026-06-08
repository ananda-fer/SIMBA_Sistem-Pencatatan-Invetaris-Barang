<?php
// =============================================
// File: pages/peminjaman/ajukan.php
// Form pengajuan peminjaman baru
// Hanya untuk role: peminjam
// =============================================

require_once '../../includes/auth_check.php';
require_once '../../config/koneksi.php';

if ($_SESSION['peran'] != 'peminjam') {
    header('Location: index.php');
    exit;
}

$id_user = $_SESSION['user_id'];
$errors = [];
$has_file_surat_column = mysqli_num_rows(
    mysqli_query($conn, "SHOW COLUMNS FROM pengajuan_peminjaman LIKE 'file_surat'")
) > 0;

// Cek apakah user punya peminjaman aktif/menunggu
$cek = mysqli_prepare(
    $conn,
    "SELECT id FROM pengajuan_peminjaman
     WHERE id_peminjam = ?
     AND status IN ('menunggu','diverifikasi','disetujui','aktif')
     LIMIT 1"
);
mysqli_stmt_bind_param($cek, 'i', $id_user);
mysqli_stmt_execute($cek);
mysqli_stmt_store_result($cek);
$punya_aktif = mysqli_stmt_num_rows($cek) > 0;
mysqli_stmt_close($cek);

// Ambil daftar barang tersedia
$res_barang = mysqli_query(
    $conn,
    "SELECT b.id, b.kode_barang, b.nama, b.stok_tersedia, (b.stok_total <= 3) AS wajib_surat, k.nama AS nama_kategori
     FROM barang b
     JOIN kategori k ON b.kategori_id = k.id
     WHERE b.status <> 'dalam_perbaikan'
     ORDER BY k.nama, b.nama ASC"
);
$daftar_barang = mysqli_fetch_all($res_barang, MYSQLI_ASSOC);

// ----------------------
// PROSES SUBMIT
// ----------------------
if (isset($_POST['simpan'])) {

    $keperluan = trim($_POST['keperluan'] ?? '');
    $tanggal_pinjam = $_POST['tanggal_pinjam'] ?? '';
    $tanggal_kembali = $_POST['tanggal_kembali'] ?? '';
    $id_barang_list = $_POST['id_barang'] ?? [];
    $jumlah_list = $_POST['jumlah'] ?? [];
    $file_surat_path = null;
    $butuh_surat = false;

    // Validasi
    if ($keperluan == '') {
        $errors[] = 'Keperluan wajib diisi.';
    }
    if ($tanggal_pinjam == '' || $tanggal_kembali == '') {
        $errors[] = 'Tanggal pinjam dan tanggal kembali wajib diisi.';
    }
    if ($tanggal_pinjam != '' && $tanggal_kembali != '' && $tanggal_kembali <= $tanggal_pinjam) {
        $errors[] = 'Tanggal kembali harus lebih besar dari tanggal pinjam.';
    }
    if ($tanggal_pinjam != '' && $tanggal_pinjam < date('Y-m-d')) {
        $errors[] = 'Tanggal pinjam tidak boleh di masa lalu.';
    }
    if (empty($id_barang_list)) {
        $errors[] = 'Pilih minimal satu barang.';
    }

    // Cek stok setiap barang
    foreach ($id_barang_list as $id_brg) {

        $jml = (int) ($jumlah_list[$id_brg] ?? 0);

        if ($jml <= 0) {
            $errors[] = 'Jumlah barang tidak boleh 0.';
            break;
        }

        $cek_stok = mysqli_prepare(
            $conn,
            "SELECT nama, stok_tersedia, (stok_total <= 3) AS wajib_surat
         FROM barang
         WHERE id = ?
         LIMIT 1"
        );

        mysqli_stmt_bind_param($cek_stok, 'i', $id_brg);
        mysqli_stmt_execute($cek_stok);

        $row_stok = mysqli_fetch_assoc(
            mysqli_stmt_get_result($cek_stok)
        );

        mysqli_stmt_close($cek_stok);

        if ($row_stok && (bool) $row_stok['wajib_surat']) {
            $butuh_surat = true;
        }

        if ($row_stok && $jml > $row_stok['stok_tersedia']) {
            $errors[] =
                'Stok "' .
                $row_stok['nama'] .
                '" tidak cukup. Tersedia: ' .
                $row_stok['stok_tersedia'];
        }
    }

    if ($has_file_surat_column && $butuh_surat) {
        if (!isset($_FILES['file_surat']) || $_FILES['file_surat']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Surat permohonan PDF wajib diupload untuk barang yang memerlukan surat.';
        } elseif ($_FILES['file_surat']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload surat gagal. Coba pilih file PDF lagi.';
        } else {
            $file_surat = $_FILES['file_surat'];
            $ext = strtolower(pathinfo($file_surat['name'], PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $errors[] = 'Surat permohonan harus berupa file PDF.';
            }
            if ($file_surat['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran surat permohonan maksimal 2 MB.';
            }
        }
    }

    // Simpan jika tidak ada error
    if (empty($errors) && $has_file_surat_column && $butuh_surat) {
        $upload_dir = __DIR__ . '/../../uploads/surat';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $nama_file = 'surat_' . $id_user . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $target_file = $upload_dir . '/' . $nama_file;

        if (!move_uploaded_file($_FILES['file_surat']['tmp_name'], $target_file)) {
            $errors[] = 'Gagal menyimpan file surat. Pastikan folder upload bisa ditulis.';
        } else {
            $file_surat_path = 'uploads/surat/' . $nama_file;
        }
    }

    if (empty($errors)) {
        // INSERT pengajuan utama
        if ($has_file_surat_column) {
            $ins = mysqli_prepare(
                $conn,
                "INSERT INTO pengajuan_peminjaman
                    (id_peminjam, keperluan, tanggal_pinjam, tanggal_kembali, status, dibuat_pada, file_surat)
                 VALUES (?, ?, ?, ?, 'menunggu', NOW(), ?)"
            );
            mysqli_stmt_bind_param($ins, 'issss', $id_user, $keperluan, $tanggal_pinjam, $tanggal_kembali, $file_surat_path);
        } else {
            $ins = mysqli_prepare(
                $conn,
                "INSERT INTO pengajuan_peminjaman
                    (id_peminjam, keperluan, tanggal_pinjam, tanggal_kembali, status, dibuat_pada)
                 VALUES (?, ?, ?, ?, 'menunggu', NOW())"
            );
            mysqli_stmt_bind_param($ins, 'isss', $id_user, $keperluan, $tanggal_pinjam, $tanggal_kembali);
        }
        if (!mysqli_stmt_execute($ins)) {
            die('Gagal simpan pengajuan: ' . mysqli_error($conn));
        }

        $id_baru = mysqli_insert_id($conn);
        mysqli_stmt_close($ins);

        // INSERT detail barang
        foreach ($id_barang_list as $id_brg) {

            $jml = (int) ($jumlah_list[$id_brg] ?? 0);

            $ins_det = mysqli_prepare(
                $conn,
                "INSERT INTO detail_peminjaman (id_peminjaman, id_barang, jumlah, kondisi_saat_pinjam) VALUES (?, ?, ?, 'baik')"
            );

            mysqli_stmt_bind_param(
                $ins_det,
                'iii',
                $id_baru,
                $id_brg,
                $jml
            );

            mysqli_stmt_execute($ins_det);
            mysqli_stmt_close($ins_det);
        }

        $_SESSION['flash_success'] = 'Pengajuan berhasil dikirim! Tunggu verifikasi Staff TU.';
        header('Location: index.php');
        exit;
    }
}

$page_title = 'Ajukan Peminjaman';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="main-content">

    <div class="topbar">
        <div>
        </div>
        <div class="topbar-right">
            <a href="index.php" class="btn btn-outline">← Kembali</a>
        </div>
    </div>

    <?php if ($punya_aktif): ?>
        <div class="alert alert-warning">
            ⚠️ Anda masih memiliki peminjaman yang sedang berjalan.
            Anda tetap bisa mengajukan peminjaman baru, selama stok barang tersedia.
            <a href="index.php" class="alert-link">Lihat status →</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Terdapat kesalahan:</strong>
            <ul class="error-list">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="ajukan.php" enctype="multipart/form-data" id="form-ajukan">

        <!-- Info Peminjaman -->
        <div class="card">
            <h3 class="card-title">Informasi Peminjaman</h3>

            <div class="form-row-2col">
                <div class="form-group">
                    <label class="form-label">Tanggal Pinjam <span class="required">*</span></label>
                    <input type="date" name="tanggal_pinjam" class="form-input" min="<?= date('Y-m-d') ?>"
                        value="<?= htmlspecialchars($_POST['tanggal_pinjam'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Kembali <span class="required">*</span></label>
                    <input type="date" name="tanggal_kembali" class="form-input"
                        min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                        value="<?= htmlspecialchars($_POST['tanggal_kembali'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Keperluan <span class="required">*</span></label>
                <input type="text" name="keperluan" class="form-input"
                    placeholder="Contoh: Seminar IT Nasional, Praktikum Jaringan..."
                    value="<?= htmlspecialchars($_POST['keperluan'] ?? '') ?>" maxlength="255" required>
            </div>

        </div>

        <!-- Pilih Barang -->
        <div class="card">
            <h3 class="card-title">Pilih Barang yang Dipinjam</h3>
            <p class="form-hint" style="margin-bottom:12px;">Centang barang yang ingin dipinjam, lalu isi jumlahnya.</p>

            <?php if (empty($daftar_barang)): ?>
                <div class="alert alert-info">Tidak ada barang yang tersedia saat ini.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="barang-table ajukan-barang-table">
                        <colgroup>
                            <col class="col-pilih">
                            <col class="col-kode">
                            <col class="col-nama">
                            <col class="col-kategori">
                            <col class="col-surat">
                            <col class="col-stok">
                            <col class="col-jumlah">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Pilih</th>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Surat</th>
                                <th>Stok</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar_barang as $brg): ?>
                                <?php $stok_habis = (int) $brg['stok_tersedia'] <= 0; ?>
                                <tr class="<?= $stok_habis ? 'is-stok-habis' : '' ?>">
                                    <td class="td-check">
                                        <input type="checkbox" name="id_barang[]" value="<?= $brg['id'] ?>"
                                            class="checkbox-barang" data-max="<?= $brg['stok_tersedia'] ?>"
                                            data-index="<?= $brg['id'] ?>"
                                            data-nama="<?= htmlspecialchars($brg['nama']) ?>"
                                            data-wajib-surat="<?= (int) $brg['wajib_surat'] ?>"
                                            onchange="toggleJumlah(this)" <?= $stok_habis ? 'disabled title="Stok habis"' : '' ?>>
                                    </td>
                                    <td class="td-kode"><?= htmlspecialchars($brg['kode_barang']) ?></td>
                                    <td class="td-nama">
                                        <div class="item-nama"><?= htmlspecialchars($brg['nama']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($brg['nama_kategori']) ?></td>
                                    <td>
                                        <?php if ($brg['wajib_surat']): ?>
                                            <span class="badge badge-surat surat-wajib">Wajib</span>
                                        <?php else: ?>
                                            <span class="badge badge-surat surat-bebas">Tidak wajib</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="stok-badge <?= $stok_habis ? 'is-empty' : '' ?>">
                                            <?= $brg['stok_tersedia'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <input type="number" name="jumlah[<?= $brg['id'] ?>]" id="jumlah-<?= $brg['id'] ?>"
                                            class="form-input qty-input" value="<?= $stok_habis ? 0 : 1 ?>"
                                            min="<?= $stok_habis ? 0 : 1 ?>" max="<?= $brg['stok_tersedia'] ?>" disabled>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($has_file_surat_column): ?>
            <div class="card upload-surat-card" id="upload-surat-section">
                <div class="card-title upload-surat-title">
                    <span class="upload-title-icon">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <path d="M14 2v6h6"/>
                        </svg>
                    </span>
                    Upload Surat Pengantar
                </div>

                <div class="alert alert-warning alert-surat" id="alert-wajib-surat">
                    Untuk barang yang memerlukan surat pengantar resmi.
                </div>

                <div class="upload-card upload-surat-drop is-disabled" id="upload-area-surat">
                    <div class="upload-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                            <path d="M17 8l-5-5-5 5"/>
                            <path d="M12 3v12"/>
                        </svg>
                    </div>
                    <div class="upload-label">Klik atau seret file PDF ke sini</div>
                    <div class="upload-hint">Format PDF · Maks. 2 MB</div>
                    <input type="file" name="file_surat" id="file-surat" accept=".pdf" hidden disabled>
                </div>

                <div class="upload-file-info" id="file-surat-info" style="display:none;"></div>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <a href="index.php" class="btn btn-outline">Batal</a>
            <button type="submit" name="simpan" class="btn btn-primary">
                Kirim Pengajuan →
            </button>
        </div>

    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>