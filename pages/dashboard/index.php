<?php
// =========================================================================
// KONEKSI DATABASE
// =========================================================================
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/koneksi.php';

// =========================================================================
// QUERY: STATISTIK UTAMA
// =========================================================================
$totalBarang        = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$dipinjamAktif      = $pdo->query("SELECT COUNT(*) FROM pengajuan_peminjaman WHERE status = 'aktif'")->fetchColumn();
$menungguVerifikasi = $pdo->query("SELECT COUNT(*) FROM pengajuan_peminjaman WHERE status = 'menunggu'")->fetchColumn();
$dalamPerbaikan     = $pdo->query("SELECT COUNT(*) FROM barang WHERE status = 'dalam_perbaikan'")->fetchColumn();

// =========================================================================
// QUERY: KONDISI BARANG
// =========================================================================
$kondisiBaik  = $pdo->query("SELECT COUNT(*) FROM barang WHERE kondisi = 'baik'")->fetchColumn();
$rusakRingan  = $pdo->query("SELECT COUNT(*) FROM barang WHERE kondisi = 'rusak_ringan'")->fetchColumn();
$rusakBerat   = $pdo->query("SELECT COUNT(*) FROM barang WHERE kondisi = 'rusak_berat'")->fetchColumn();
$totalKategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();

// =========================================================================
// QUERY: PENGAJUAN PEMINJAMAN TERBARU
// =========================================================================
$peminjamanTerbaru = $pdo->query("
    SELECT pp.id, u.nama_lengkap AS nama_peminjam, pp.keperluan,
           pp.tanggal_pinjam, pp.tanggal_kembali, pp.status
    FROM pengajuan_peminjaman pp
    JOIN users u ON u.id = pp.id_peminjam
    ORDER BY pp.dibuat_pada DESC
    LIMIT 5
")->fetchAll();

// =========================================================================
// QUERY: DISTRIBUSI KATEGORI
// =========================================================================
$distribusiKategori = $pdo->query("
    SELECT k.nama, COUNT(b.id) AS jumlah
    FROM kategori k
    LEFT JOIN barang b ON b.kategori_id = k.id
    GROUP BY k.id, k.nama
    ORDER BY jumlah DESC
")->fetchAll();

$progressColors = ['bg-purple', 'bg-green', 'bg-primary', 'bg-warning', 'bg-danger'];

// =========================================================================
// QUERY: AKTIVITAS TERKINI
// =========================================================================
$aktivitasTerkini = $pdo->query("
    (SELECT CONCAT(u.nama_lengkap, ' mengajukan peminjaman: ', pp.keperluan) AS deskripsi,
            pp.dibuat_pada AS waktu, 'blue' AS warna
     FROM pengajuan_peminjaman pp JOIN users u ON u.id = pp.id_peminjam)
    UNION ALL
    (SELECT CONCAT(u.nama_lengkap, ' mengembalikan barang peminjaman') AS deskripsi,
            p.dibuat_pada AS waktu, 'green' AS warna
     FROM pengembalian p
     JOIN pengajuan_peminjaman pp ON pp.id = p.id_peminjaman
     JOIN users u ON u.id = pp.id_peminjam)
    ORDER BY waktu DESC
    LIMIT 5
")->fetchAll();

// =========================================================================
// PAGE CONFIGURATION
// =========================================================================
$pageTitle   = 'Dashboard Admin';
$currentPage = 'dashboard';
$basePath    = '../../';
$pageCss     = ['assets/css/dashboard.css'];
// $pageJs   = []; // Tidak ada JS khusus di dashboard saat ini

require_once $basePath . 'includes/header.php';
require_once $basePath . 'includes/sidebar.php';
?>
<main class="main-content">

<!-- Kartu Statistik Atas -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card-stat blue">
            <div class="icon-wrapper"><i class="fa-solid fa-box fa-lg"></i></div>
            <div class="stat-content">
                <h3><?php echo $totalBarang; ?></h3>
                <p>Total Barang</p>
                <small>Item terdaftar</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-stat orange">
            <div class="icon-wrapper"><i class="fa-solid fa-hand-holding-hand fa-lg"></i></div>
            <div class="stat-content">
                <h3><?php echo $dipinjamAktif; ?></h3>
                <p>Dipinjam Aktif</p>
                <small>Peminjaman berjalan</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-stat yellow">
            <div class="icon-wrapper"><i class="fa-solid fa-clock-rotate-left fa-lg"></i></div>
            <div class="stat-content">
                <h3><?php echo $menungguVerifikasi; ?></h3>
                <p>Menunggu Verifikasi</p>
                <small>Pengajuan baru</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-stat red">
            <div class="icon-wrapper"><i class="fa-solid fa-screwdriver-wrench fa-lg"></i></div>
            <div class="stat-content">
                <h3><?php echo $dalamPerbaikan; ?></h3>
                <p>Dalam Perbaikan</p>
                <small>Butuh perhatian</small>
            </div>
        </div>
    </div>
</div>

<!-- Kartu Kondisi Barang -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card-mini green"><div class="dot green"></div><h4><?php echo $kondisiBaik; ?></h4><p>Kondisi Baik</p></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-mini orange"><div class="dot orange"></div><h4><?php echo $rusakRingan; ?></h4><p>Rusak Ringan</p></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-mini red"><div class="dot red"></div><h4><?php echo $rusakBerat; ?></h4><p>Rusak Berat</p></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-mini blue"><div class="dot blue"></div><h4><?php echo $totalKategori; ?></h4><p>Total Kategori</p></div>
    </div>
</div>

<!-- Konten Utama -->
<div class="row g-4">

    <!-- Tabel Pengajuan Terbaru -->
    <div class="col-lg-8">
        <div class="section-card h-100">
            <div class="section-title">
                Pengajuan Peminjaman Terbaru
                <a href="<?php echo $basePath; ?>pages/peminjaman/index.php">Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-pengajuan mb-0">
                    <thead>
                        <tr>
                            <th>PEMINJAM</th>
                            <th>KEPERLUAN</th>
                            <th>TGL PINJAM</th>
                            <th>TGL KEMBALI</th>
                            <th>STATUS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($peminjamanTerbaru)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                    Belum ada pengajuan peminjaman.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($peminjamanTerbaru as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nama_peminjam']); ?></td>
                                    <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?php echo htmlspecialchars($row['keperluan']); ?>
                                    </td>
                                    <td><?php echo $row['tanggal_pinjam']; ?></td>
                                    <td><?php echo $row['tanggal_kembali']; ?></td>
                                    <td>
                                        <span class="badge-status badge-<?php echo strtolower($row['status']); ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo $basePath; ?>pages/peminjaman/index.php?id=<?php echo $row['id']; ?>"
                                           style="font-size:.8rem;color:#4361ee;text-decoration:none;font-weight:600;">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan -->
    <div class="col-lg-4">

        <!-- Distribusi Kategori -->
        <div class="section-card mb-4">
            <div class="section-title">Distribusi Kategori Barang</div>
            <?php if (empty($distribusiKategori)): ?>
                <p class="empty-state">Belum ada data kategori.</p>
            <?php else: ?>
                <?php foreach ($distribusiKategori as $idx => $kat): ?>
                    <?php $persen = ($totalBarang > 0) ? round(($kat['jumlah'] / $totalBarang) * 100) : 0; ?>
                    <div class="kategori-item">
                        <div class="kategori-info">
                            <span><?php echo htmlspecialchars($kat['nama']); ?></span>
                            <span><?php echo $kat['jumlah']; ?></span>
                        </div>
                        <div class="progress progress-custom">
                            <div class="progress-bar <?php echo $progressColors[$idx % count($progressColors)]; ?>"
                                 role="progressbar" style="width:<?php echo $persen; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Aktivitas Terkini -->
        <div class="section-card">
            <div class="section-title">Aktivitas Terkini</div>
            <?php if (empty($aktivitasTerkini)): ?>
                <p class="empty-state">Belum ada aktivitas.</p>
            <?php else: ?>
                <ul class="timeline">
                    <?php foreach ($aktivitasTerkini as $akt): ?>
                        <?php
                            $detik = time() - strtotime($akt['waktu']);
                            if ($detik < 60)        $rel = $detik . ' detik lalu';
                            elseif ($detik < 3600)  $rel = floor($detik/60) . ' menit lalu';
                            elseif ($detik < 86400) $rel = floor($detik/3600) . ' jam lalu';
                            else                    $rel = floor($detik/86400) . ' hari lalu';
                        ?>
                        <li class="timeline-item <?php echo htmlspecialchars($akt['warna']); ?>">
                            <div class="timeline-content">
                                <?php echo htmlspecialchars($akt['deskripsi']); ?>
                                <span class="timeline-time"><?php echo $rel; ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
