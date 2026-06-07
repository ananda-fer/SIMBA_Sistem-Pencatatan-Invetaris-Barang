<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/koneksi.php';

hanya_role(['admin', 'staff_tu']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?tab=arsip');
    exit;
}

$aksi = $_POST['aksi'] ?? '';

// =========================================================================
// HELPER: Upload PDF
// =========================================================================
function uploadPdf(string $fieldName, ?string $oldPath = null): ?string {
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldPath; // tidak ada file baru, pakai lama
    }
    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $oldPath;
    }
    $file = $_FILES[$fieldName];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf' || $file['size'] > 5 * 1024 * 1024) {
        return $oldPath; // abaikan file invalid, pakai lama
    }
    $uploadDir = __DIR__ . '/../../uploads/laporan';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $namaFile = 'laporan_' . $_SESSION['user_id'] . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $namaFile)) {
        // Hapus file lama jika ada
        if ($oldPath) {
            $oldFull = __DIR__ . '/../../' . $oldPath;
            if (file_exists($oldFull)) @unlink($oldFull);
        }
        return 'uploads/laporan/' . $namaFile;
    }
    return $oldPath;
}

// =========================================================================
// TAMBAH (Create)
// =========================================================================
if ($aksi === 'tambah') {
    $judul       = trim($_POST['judul']        ?? '');
    $jenis       = $_POST['jenis_laporan']     ?? '';
    $periodeAwal = $_POST['periode_awal']      ?? '';
    $periodeAkhr = $_POST['periode_akhir']     ?? '';
    $catatan     = trim($_POST['catatan']      ?? '');

    if (!$judul || !$jenis || !$periodeAwal || !$periodeAkhr) {
        $_SESSION['flash_laporan'] = ['type' => 'danger', 'msg' => 'Semua field wajib diisi.'];
        header('Location: index.php?tab=arsip');
        exit;
    }
    if ($periodeAkhr < $periodeAwal) {
        $_SESSION['flash_laporan'] = ['type' => 'danger', 'msg' => 'Tanggal akhir periode tidak boleh sebelum tanggal awal.'];
        header('Location: index.php?tab=arsip');
        exit;
    }

    $filePdf = uploadPdf('file_pdf');

    $stmt = $pdo->prepare("
        INSERT INTO laporan (judul, jenis_laporan, periode_awal, periode_akhir, dibuat_oleh, catatan, file_pdf)
        VALUES (:judul, :jenis, :awal, :akhir, :oleh, :catatan, :pdf)
    ");
    $stmt->execute([
        ':judul'   => $judul,
        ':jenis'   => $jenis,
        ':awal'    => $periodeAwal,
        ':akhir'   => $periodeAkhr,
        ':oleh'    => $_SESSION['user_id'],
        ':catatan' => $catatan ?: null,
        ':pdf'     => $filePdf,
    ]);

    $_SESSION['flash_laporan'] = ['type' => 'success', 'msg' => 'Laporan "' . htmlspecialchars($judul) . '" berhasil dibuat.'];
    header('Location: index.php?tab=arsip');
    exit;
}

// =========================================================================
// EDIT (Update)
// =========================================================================
if ($aksi === 'edit') {
    $id          = (int)($_POST['id']          ?? 0);
    $judul       = trim($_POST['judul']        ?? '');
    $jenis       = $_POST['jenis_laporan']     ?? '';
    $periodeAwal = $_POST['periode_awal']      ?? '';
    $periodeAkhr = $_POST['periode_akhir']     ?? '';
    $catatan     = trim($_POST['catatan']      ?? '');

    if (!$id || !$judul || !$jenis || !$periodeAwal || !$periodeAkhr) {
        $_SESSION['flash_laporan'] = ['type' => 'danger', 'msg' => 'Data tidak valid.'];
        header('Location: index.php?tab=arsip');
        exit;
    }

    // Ambil file lama
    $existing = $pdo->prepare("SELECT file_pdf FROM laporan WHERE id = :id");
    $existing->execute([':id' => $id]);
    $oldRow = $existing->fetch();
    if (!$oldRow) {
        $_SESSION['flash_laporan'] = ['type' => 'danger', 'msg' => 'Laporan tidak ditemukan.'];
        header('Location: index.php?tab=arsip');
        exit;
    }

    $filePdf = uploadPdf('file_pdf', $oldRow['file_pdf']);

    $stmt = $pdo->prepare("
        UPDATE laporan
        SET judul        = :judul,
            jenis_laporan= :jenis,
            periode_awal = :awal,
            periode_akhir= :akhir,
            catatan      = :catatan,
            file_pdf     = :pdf
        WHERE id = :id
    ");
    $stmt->execute([
        ':judul'   => $judul,
        ':jenis'   => $jenis,
        ':awal'    => $periodeAwal,
        ':akhir'   => $periodeAkhr,
        ':catatan' => $catatan ?: null,
        ':pdf'     => $filePdf,
        ':id'      => $id,
    ]);

    $_SESSION['flash_laporan'] = ['type' => 'success', 'msg' => 'Laporan berhasil diperbarui.'];
    header('Location: index.php?tab=arsip');
    exit;
}

// =========================================================================
// HAPUS (Delete)
// =========================================================================
if ($aksi === 'hapus') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        header('Location: index.php?tab=arsip');
        exit;
    }

    // Ambil & hapus file PDF
    $existing = $pdo->prepare("SELECT file_pdf, judul FROM laporan WHERE id = :id");
    $existing->execute([':id' => $id]);
    $row = $existing->fetch();

    if ($row) {
        if ($row['file_pdf']) {
            $fullPath = __DIR__ . '/../../' . $row['file_pdf'];
            if (file_exists($fullPath)) @unlink($fullPath);
        }
        $pdo->prepare("DELETE FROM laporan WHERE id = :id")->execute([':id' => $id]);
        $_SESSION['flash_laporan'] = ['type' => 'success', 'msg' => 'Laporan "' . htmlspecialchars($row['judul']) . '" berhasil dihapus.'];
    }

    header('Location: index.php?tab=arsip');
    exit;
}

header('Location: index.php?tab=arsip');
exit;
