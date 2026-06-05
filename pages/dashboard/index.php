<?php
// Pastikan session sudah dimulai jika menggunakan session untuk mengecek role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================
// KONFIGURASI DATABASE (CONTOH)
// =====================
/*
Untuk menghubungkan fitur-fitur di bawah ke database, Anda perlu melakukan koneksi terlebih dahulu.
Gunakan file koneksi Anda, misalnya:
require_once '../../config.php'; // Jika Anda memiliki file config.php untuk koneksi PDO/MySQLi

Contoh koneksi PDO:
$host = 'localhost';
$db   = 'simba_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
*/

// =====================
// DATA DUMMY & QUERY (CONTOH)
// =====================
/*
Berikut adalah contoh bagaimana Anda mengambil data tersebut dari database:

1. Total Barang:
   $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang");
   $totalBarang = $stmt->fetch()['total'];

2. Dipinjam Aktif:
   $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'AKTIF'");
   $dipinjamAktif = $stmt->fetch()['total'];

3. Menunggu Verifikasi:
   $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'MENUNGGU'");
   $menungguVerifikasi = $stmt->fetch()['total'];

4. Dalam Perbaikan:
   $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE kondisi = 'DALAM PERBAIKAN'");
   $dalamPerbaikan = $stmt->fetch()['total'];

5. Kondisi Baik:
   $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE kondisi = 'BAIK'");
   $kondisiBaik = $stmt->fetch()['total'];

6. Rusak Ringan:
   $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE kondisi = 'RUSAK RINGAN'");
   $rusakRingan = $stmt->fetch()['total'];

7. Rusak Berat:
   $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE kondisi = 'RUSAK BERAT'");
   $rusakBerat = $stmt->fetch()['total'];

8. Total Kategori:
   $stmt = $pdo->query("SELECT COUNT(*) as total FROM kategori");
   $totalKategori = $stmt->fetch()['total'];

9. Pengajuan Peminjaman Terbaru:
   $stmt = $pdo->query("SELECT * FROM peminjaman ORDER BY created_at DESC LIMIT 5");
   $peminjamanTerbaru = $stmt->fetchAll();

10. Distribusi Kategori:
    $stmt = $pdo->query("SELECT k.nama, COUNT(b.id) as jumlah FROM kategori k LEFT JOIN barang b ON k.id = b.kategori_id GROUP BY k.id");
    $distribusiKategori = $stmt->fetchAll();

11. Aktivitas Terkini:
    $stmt = $pdo->query("SELECT * FROM aktivitas ORDER BY created_at DESC LIMIT 5");
    $aktivitasTerkini = $stmt->fetchAll();
*/

// Data Dummy untuk presentasi visual
$stats = [
    'total_barang' => 50,
    'dipinjam_aktif' => 18,
    'menunggu_verifikasi' => 7,
    'dalam_perbaikan' => 1,
    'kondisi_baik' => 45,
    'rusak_ringan' => 2,
    'rusak_berat' => 1,
    'total_kategori' => 2
];


// =====================
// Page Configuration
// =====================
$pageTitle   = 'Dashboard Admin';
$currentPage = 'dashboard';
$basePath    = '../../'; 

$extraStyles = '
<style>
    .card-stat {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        display: flex;
        align-items: center;
        border-left: 4px solid transparent;
        height: 100%;
    }
    .card-stat.blue { border-left-color: #4361ee; }
    .card-stat.orange { border-left-color: #f77f00; }
    .card-stat.yellow { border-left-color: #ffd166; }
    .card-stat.red { border-left-color: #ef476f; }
    
    .card-stat .icon-wrapper {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }
    .card-stat.blue .icon-wrapper { background: rgba(67, 97, 238, 0.1); color: #4361ee; }
    .card-stat.orange .icon-wrapper { background: rgba(247, 127, 0, 0.1); color: #f77f00; }
    .card-stat.yellow .icon-wrapper { background: rgba(255, 209, 102, 0.1); color: #ffd166; }
    .card-stat.red .icon-wrapper { background: rgba(239, 71, 111, 0.1); color: #ef476f; }

    .card-stat .stat-content h3 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
        color: #2b2d42;
    }
    .card-stat.blue .stat-content h3 { color: #4361ee; }
    .card-stat.orange .stat-content h3 { color: #f77f00; }
    .card-stat.yellow .stat-content h3 { color: #ffd166; }
    .card-stat.red .stat-content h3 { color: #ef476f; }
    
    .card-stat .stat-content p {
        margin: 0;
        font-size: 0.85rem;
        font-weight: 600;
        color: #2b2d42;
    }
    .card-stat .stat-content small {
        color: #8d99ae;
        font-size: 0.75rem;
    }

    .card-mini {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        display: flex;
        align-items: center;
        border: 1px solid #edf2f4;
    }
    .card-mini .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 10px;
    }
    .dot.green { background-color: #06d6a0; }
    .dot.orange { background-color: #f77f00; }
    .dot.red { background-color: #ef476f; }
    .dot.blue { background-color: #4361ee; }

    .card-mini h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        margin-right: 10px;
    }
    .card-mini.green h4 { color: #06d6a0; }
    .card-mini.orange h4 { color: #f77f00; }
    .card-mini.red h4 { color: #ef476f; }
    .card-mini.blue h4 { color: #4361ee; }
    
    .card-mini p {
        margin: 0;
        font-size: 0.75rem;
        color: #8d99ae;
        font-weight: 500;
    }

    .section-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        margin-bottom: 20px;
    }
    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #2b2d42;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .section-title a {
        font-size: 0.8rem;
        color: #4361ee;
        text-decoration: none;
        font-weight: 600;
    }

    /* Table styles for Pengajuan */
    .table-pengajuan th {
        font-size: 0.75rem;
        color: #8d99ae;
        text-transform: uppercase;
        border-bottom: 2px solid #edf2f4;
        padding: 12px 15px;
    }
    .table-pengajuan td {
        font-size: 0.85rem;
        font-weight: 600;
        color: #2b2d42;
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #edf2f4;
    }
    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .badge-menunggu { background: rgba(255, 209, 102, 0.2); color: #d4a017; }
    .badge-disetujui { background: rgba(6, 214, 160, 0.2); color: #06d6a0; }
    .badge-aktif { background: rgba(67, 97, 238, 0.2); color: #4361ee; }
    .badge-selesai { background: rgba(141, 153, 174, 0.2); color: #8d99ae; }
    .badge-ditolak { background: rgba(239, 71, 111, 0.2); color: #ef476f; }

    /* Progress bar for Kategori */
    .kategori-item { margin-bottom: 15px; }
    .kategori-info {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        font-weight: 600;
        color: #2b2d42;
        margin-bottom: 5px;
    }
    .progress-custom {
        height: 6px;
        background-color: #edf2f4;
        border-radius: 10px;
    }
    .progress-custom .progress-bar {
        border-radius: 10px;
    }
    .bg-purple { background-color: #7209b7; }
    .bg-green { background-color: #06d6a0; }

    /* Timeline / Aktivitas */
    .timeline {
        position: relative;
        padding-left: 20px;
        list-style: none;
    }
    .timeline::before {
        content: "";
        position: absolute;
        left: 5px;
        top: 5px;
        bottom: 0;
        width: 2px;
        background: #edf2f4;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 15px;
    }
    .timeline-item::before {
        content: "";
        position: absolute;
        left: -19px;
        top: 3px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    .timeline-item.blue::before { background-color: #4361ee; }
    .timeline-item.green::before { background-color: #06d6a0; }
    .timeline-item.orange::before { background-color: #f77f00; }
    
    .timeline-content {
        font-size: 0.8rem;
        color: #2b2d42;
        font-weight: 500;
    }
    .timeline-time {
        display: block;
        font-size: 0.7rem;
        color: #8d99ae;
        margin-top: 2px;
    }
</style>
';

require_once $basePath . 'includes/header.php';
require_once $basePath . 'includes/navbar.php';
?>

<!-- Top Main Stats -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card-stat blue">
            <div class="icon-wrapper"><i class="fa-solid fa-box fa-lg"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['total_barang']; ?></h3>
                <p>Total Barang</p>
                <small>Item terdaftar</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-stat orange">
            <div class="icon-wrapper"><i class="fa-solid fa-hand-holding-hand fa-lg"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['dipinjam_aktif']; ?></h3>
                <p>Dipinjam Aktif</p>
                <small>Peminjaman berjalan</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-stat yellow">
            <div class="icon-wrapper"><i class="fa-solid fa-clock-rotate-left fa-lg"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['menunggu_verifikasi']; ?></h3>
                <p>Menunggu Verifikasi</p>
                <small>Pengajuan baru</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-stat red">
            <div class="icon-wrapper"><i class="fa-solid fa-screwdriver-wrench fa-lg"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['dalam_perbaikan']; ?></h3>
                <p>Dalam Perbaikan</p>
                <small>Butuh perhatian</small>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-mini green">
            <div class="dot green"></div>
            <h4><?php echo $stats['kondisi_baik']; ?></h4>
            <p>Kondisi Baik</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-mini orange">
            <div class="dot orange"></div>
            <h4><?php echo $stats['rusak_ringan']; ?></h4>
            <p>Rusak Ringan</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-mini red">
            <div class="dot red"></div>
            <h4><?php echo $stats['rusak_berat']; ?></h4>
            <p>Rusak Berat</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-mini blue">
            <div class="dot blue"></div>
            <h4><?php echo $stats['total_kategori']; ?></h4>
            <p>Total Kategori</p>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="row g-4">
    
    <!-- Left Column: Table -->
    <div class="col-lg-8">
        <div class="section-card h-100">
            <div class="section-title">
                Pengajuan Peminjaman Terbaru
                <a href="<?php echo $basePath; ?>pages/peminjaman/index.php">Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-pengajuan">
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
                        <!-- CONTOH LOOPING DATA DARI DATABASE:
                        <?php // foreach($peminjamanTerbaru as $row): ?>
                        -->
                        <tr>
                            <td>Sidiq</td>
                            <td>Acara BEM Univ</td>
                            <td>2026-05-25</td>
                            <td>2026-05-26</td>
                            <td><span class="badge-status badge-menunggu">MENUNGGU</span></td>
                            <td><a href="#" class="text-primary text-decoration-none" style="font-size:0.8rem;">Detail</a></td>
                        </tr>
                        <tr>
                            <td>fahri</td>
                            <td>Praktikum Jaringan Komputer</td>
                            <td>2026-05-24</td>
                            <td>2026-05-27</td>
                            <td><span class="badge-status badge-disetujui">DISETUJUI</span></td>
                            <td><a href="#" class="text-primary text-decoration-none" style="font-size:0.8rem;">Detail</a></td>
                        </tr>
                        <tr>
                            <td>Ake</td>
                            <td>Workshop Sistem Operasi</td>
                            <td>2026-05-23</td>
                            <td>2026-05-24</td>
                            <td><span class="badge-status badge-aktif">AKTIF</span></td>
                            <td><a href="#" class="text-primary text-decoration-none" style="font-size:0.8rem;">Detail</a></td>
                        </tr>
                        <tr>
                            <td>Ajgab</td>
                            <td>Rapat Koordinasi BLM</td>
                            <td>2026-05-22</td>
                            <td>2026-05-22</td>
                            <td><span class="badge-status badge-selesai">SELESAI</span></td>
                            <td><a href="#" class="text-primary text-decoration-none" style="font-size:0.8rem;">Detail</a></td>
                        </tr>
                        <tr>
                            <td>Andreas</td>
                            <td>Lomba PKM</td>
                            <td>2026-05-26</td>
                            <td>2026-05-28</td>
                            <td><span class="badge-status badge-ditolak">DITOLAK</span></td>
                            <td><a href="#" class="text-primary text-decoration-none" style="font-size:0.8rem;">Detail</a></td>
                        </tr>
                        <!-- <?php // endforeach; ?> -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Charts & Timeline -->
    <div class="col-lg-4">
        
        <!-- Distribusi Kategori -->
        <div class="section-card mb-4">
            <div class="section-title">Distribusi Kategori Barang</div>
            
            <!-- CONTOH LOOPING DATA DISTRIBUSI KATEGORI:
            <?php // foreach($distribusiKategori as $kat): 
                  // $persen = ($kat['jumlah'] / $totalBarang) * 100;
            ?>
            -->
            <div class="kategori-item">
                <div class="kategori-info">
                    <span>Elektronik & IT</span>
                    <span>40</span>
                </div>
                <div class="progress progress-custom">
                    <div class="progress-bar bg-purple" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            
            <div class="kategori-item">
                <div class="kategori-info">
                    <span>Furnitur & Mebel</span>
                    <span>10</span>
                </div>
                <div class="progress progress-custom">
                    <div class="progress-bar bg-green" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <!-- <?php // endforeach; ?> -->
        </div>

        <!-- Aktivitas Terkini -->
        <div class="section-card h-100">
            <div class="section-title">Aktivitas Terkini</div>
            
            <ul class="timeline m-0 p-0 ms-2">
                <!-- CONTOH LOOPING DATA AKTIVITAS:
                <?php // foreach($aktivitasTerkini as $akt): ?>
                -->
                <li class="timeline-item blue">
                    <div class="timeline-content">
                        fahri mengajukan peminjaman Kabel HDMI
                        <span class="timeline-time">5 menit lalu</span>
                    </div>
                </li>
                <li class="timeline-item green">
                    <div class="timeline-content">
                        Proyektor dikembalikan dalam kondisi baik
                        <span class="timeline-time">32 menit lalu</span>
                    </div>
                </li>
                <li class="timeline-item orange">
                    <div class="timeline-content">
                        Laptop Zyrex ditandai rusak ringan
                        <span class="timeline-time">1 jam lalu</span>
                    </div>
                </li>
                <!-- <?php // endforeach; ?> -->
            </ul>
        </div>

    </div>

</div>

<?php
require_once $basePath . 'includes/footer.php';
?>
