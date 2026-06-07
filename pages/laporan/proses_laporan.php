<?php
// =========================================================================
// PROSES CRUD LAPORAN
// File ini menangani: tambah, edit, hapus laporan dari form di index.php
// =========================================================================
require_once __DIR__ . '/../../koneksi.php';

// Pastikan request via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ambil aksi yang diminta
$aksi = $_POST['aksi'] ?? '';

// =========================================================================
// TAMBAH LAPORAN
// =========================================================================
if ($aksi === 'tambah') {
    $judul        = trim($_POST['judul'] ?? '');
    $periodeAwal  = $_POST['periode_awal']  ?? '';
    $periodeAkhir = $_POST['periode_akhir'] ?? '';
    $jenis        = $_POST['jenis_laporan'] ?? '';
    $catatan      = trim($_POST['catatan'] ?? '');

    // Validasi field wajib
    if (empty($judul) || empty($periodeAwal) || empty($periodeAkhir) || empty($jenis)) {
        header('Location: index.php?msg=Semua+field+wajib+diisi!&type=danger');
        exit;
    }

    // TODO: Ganti dengan ID user yang sedang login (dari session)
    // Contoh: $dibuatOleh = $_SESSION['id'];
    $dibuatOleh = 1; // Default sementara (admin)

    $stmt = $pdo->prepare("
        INSERT INTO laporan (judul, jenis_laporan, periode_awal, periode_akhir, dibuat_oleh, catatan)
        VALUES (:judul, :jenis, :awal, :akhir, :oleh, :catatan)
    ");
    $stmt->execute([
        ':judul'   => $judul,
        ':jenis'   => $jenis,
        ':awal'    => $periodeAwal,
        ':akhir'   => $periodeAkhir,
        ':oleh'    => $dibuatOleh,
        ':catatan' => $catatan,
    ]);

    header('Location: index.php?msg=Laporan+berhasil+ditambahkan!&type=success');
    exit;
}

// =========================================================================
// EDIT LAPORAN
// =========================================================================
if ($aksi === 'edit') {
    $id           = (int) ($_POST['id'] ?? 0);
    $judul        = trim($_POST['judul'] ?? '');
    $periodeAwal  = $_POST['periode_awal']  ?? '';
    $periodeAkhir = $_POST['periode_akhir'] ?? '';
    $jenis        = $_POST['jenis_laporan'] ?? '';
    $catatan      = trim($_POST['catatan'] ?? '');

    if ($id <= 0 || empty($judul) || empty($periodeAwal) || empty($periodeAkhir) || empty($jenis)) {
        header('Location: index.php?msg=Data+tidak+valid!&type=danger');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE laporan
        SET judul = :judul,
            jenis_laporan = :jenis,
            periode_awal  = :awal,
            periode_akhir = :akhir,
            catatan       = :catatan
        WHERE id = :id
    ");
    $stmt->execute([
        ':judul'   => $judul,
        ':jenis'   => $jenis,
        ':awal'    => $periodeAwal,
        ':akhir'   => $periodeAkhir,
        ':catatan' => $catatan,
        ':id'      => $id,
    ]);

    header('Location: index.php?msg=Laporan+berhasil+diperbarui!&type=success');
    exit;
}

// =========================================================================
// HAPUS LAPORAN
// =========================================================================
if ($aksi === 'hapus') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        header('Location: index.php?msg=ID+laporan+tidak+valid!&type=danger');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM laporan WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: index.php?msg=Laporan+berhasil+dihapus!&type=success');
    exit;
}

// Jika aksi tidak dikenali, redirect kembali
header('Location: index.php');
exit;
