<?php
// pages/laporan.php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle  = 'Laporan Keuangan';
$activePage = 'laporan';

$periode = clean($_GET['periode'] ?? 'bulanan');
$bulan   = clean($_GET['bulan'] ?? date('Y-m'));
$tahun   = clean($_GET['tahun'] ?? date('Y'));

// ── PEMASUKAN HARIAN (bulan ini atau pilihan) ──
[$y, $m] = explode('-', $bulan . '-01');
$harian = $conn->query("
    SELECT CAST(created_at AS DATE) AS tgl,
           COUNT(*) AS jumlah_trx,
           SUM(total_harga) AS total
    FROM transaksi
    WHERE EXTRACT(YEAR FROM created_at) = '$y' AND EXTRACT(MONTH FROM created_at) = '$m'
    GROUP BY CAST(created_at AS DATE)
    ORDER BY tgl ASC
");

// ── PEMASUKAN MINGGUAN (tahun ini) ──
$mingguan = $conn->query("
    SELECT WEEK(created_at, 1) AS minggu,
           MIN(CAST(created_at AS DATE)) AS mulai,
           COUNT(*) AS jumlah_trx,
           SUM(total_harga) AS total
    FROM transaksi
    WHERE EXTRACT(YEAR FROM created_at) = '$tahun'
    GROUP BY WEEK(created_at, 1)
    ORDER BY minggu ASC
    LIMIT 52
");

// ── PEMASUKAN BULANAN (tahun ini) ──
$bulanan = $conn->query("
    SELECT EXTRACT(MONTH FROM created_at) AS bln,
           MONTHNAME(created_at) AS nama_bulan,
           COUNT(*) AS jumlah_trx,
           SUM(total_harga) AS total
    FROM transaksi
    WHERE EXTRACT(YEAR FROM created_at) = '$tahun'
    GROUP BY EXTRACT(MONTH FROM created_at)
    ORDER BY bln ASC
");

// ── SUMMARY ──
$total_bulan_ini = $conn->query("
    SELECT SUM(total_harga) FROM transaksi
    WHERE EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM CURRENT_DATE)
")->fetch_row()[0] ?? 0;

$total_hari_ini = $conn->query("
    SELECT SUM(total_harga) FROM transaksi WHERE CAST(created_at AS DATE) = CURRENT_DATE
")->fetch_row()[0] ?? 0;

$total_semua = $conn->query("SELECT SUM(total_harga) FROM transaksi")->fetch_row()[0] ?? 0;

// Untuk chart bulanan
$chart_bulan = []; $chart_total = [];
$bulanan->data_seek(0);
$bulan_names = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
for ($i = 1; $i <= 12; $i++) {
    $chart_bulan[] = $bulan_names[$i];
    $chart_total[] = 0;
}
$bulanan->data_seek(0);
while ($row = $bulanan->fetch_assoc()) {
    $chart_total[(int)$row['bln'] - 1] = (float)$row['total'];
}

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header">
    <div>
        <h4><i class="fas fa-chart-bar mr-2 text-primary"></i>Laporan Keuangan</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Laporan</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#FEF3C7"><i class="fas fa-sun" style="color:#F59E0B"></i></div>
            <div>
                <div class="stat-label">Pemasukan Hari Ini</div>
                <div class="stat-value" style="font-size:18px"><?= rupiah($total_hari_ini) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#DBEAFE"><i class="fas fa-calendar" style="color:#2563EB"></i></div>
            <div>
                <div class="stat-label">Pemasukan Bulan Ini</div>
                <div class="stat-value" style="font-size:18px"><?= rupiah($total_bulan_ini) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#D1FAE5"><i class="fas fa-piggy-bank" style="color:#10B981"></i></div>
            <div>
                <div class="stat-label">Total Seluruh Waktu</div>
                <div class="stat-value" style="font-size:18px"><?= rupiah($total_semua) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Bulanan -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-chart-bar mr-2 text-primary"></i>Pemasukan Bulanan <?= $tahun ?></span>
        <form method="GET" class="d-flex" style="gap:8px">
            <input type="hidden" name="periode" value="<?= $periode ?>">
            <input type="hidden" name="bulan" value="<?= $bulan ?>">
            <input type="number" name="tahun" class="form-control form-control-sm"
                   value="<?= $tahun ?>" min="2020" max="2030" style="width:90px">
            <button type="submit" class="btn btn-sm btn-outline-primary">Tampil</button>
        </form>
    </div>
    <div class="card-body">
        <canvas id="chartBulanan" height="80"></canvas>
    </div>
</div>

<!-- Tab Laporan -->
<ul class="nav nav-tabs mb-3" style="border-bottom:2px solid #E2E8F0">
    <li class="nav-item">
        <a class="nav-link <?= $periode === 'harian' ? 'active' : '' ?>"
           href="?periode=harian&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>">
            <i class="fas fa-calendar-day mr-1"></i>Harian
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $periode === 'mingguan' ? 'active' : '' ?>"
           href="?periode=mingguan&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>">
            <i class="fas fa-calendar-week mr-1"></i>Mingguan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $periode === 'bulanan' ? 'active' : '' ?>"
           href="?periode=bulanan&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>">
            <i class="fas fa-calendar-alt mr-1"></i>Bulanan
        </a>
    </li>
</ul>

<div class="card">
    <?php if ($periode === 'harian'): ?>
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-calendar-day mr-2"></i>Laporan Harian</span>
        <form method="GET" class="d-flex" style="gap:8px">
            <input type="hidden" name="periode" value="harian">
            <input type="hidden" name="tahun" value="<?= $tahun ?>">
            <input type="month" name="bulan" class="form-control form-control-sm" value="<?= $bulan ?>">
            <button type="submit" class="btn btn-sm btn-outline-primary">Tampil</button>
        </form>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Tanggal</th><th>Jumlah Transaksi</th><th>Total Pemasukan</th></tr>
            </thead>
            <tbody>
            <?php
            $grand = 0;
            $harian->data_seek(0);
            while ($row = $harian->fetch_assoc()):
                $grand += $row['total'];
            ?>
            <tr>
                <td><?= date('d F Y', strtotime($row['tgl'])) ?></td>
                <td><?= $row['jumlah_trx'] ?> transaksi</td>
                <td style="font-weight:700;color:#10B981"><?= rupiah($row['total']) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($harian->num_rows === 0): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data untuk periode ini.</td></tr>
            <?php else: ?>
            <tr style="background:#F8FAFC">
                <td colspan="2" style="font-weight:700;text-align:right">Total Bulan <?= date('F Y', strtotime($bulan)) ?></td>
                <td style="font-weight:800;color:#2563EB;font-size:15px"><?= rupiah($grand) ?></td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($periode === 'mingguan'): ?>
    <div class="card-header"><i class="fas fa-calendar-week mr-2"></i>Laporan Mingguan <?= $tahun ?></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Minggu ke-</th><th>Mulai</th><th>Jumlah Transaksi</th><th>Total Pemasukan</th></tr>
            </thead>
            <tbody>
            <?php
            $grand = 0; $no = 1;
            while ($row = $mingguan->fetch_assoc()):
                $grand += $row['total'];
            ?>
            <tr>
                <td>Minggu <?= $no++ ?></td>
                <td><?= date('d F Y', strtotime($row['mulai'])) ?></td>
                <td><?= $row['jumlah_trx'] ?> transaksi</td>
                <td style="font-weight:700;color:#10B981"><?= rupiah($row['total']) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($mingguan->num_rows === 0): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php else: ?>
            <tr style="background:#F8FAFC">
                <td colspan="3" style="font-weight:700;text-align:right">Total <?= $tahun ?></td>
                <td style="font-weight:800;color:#2563EB;font-size:15px"><?= rupiah($grand) ?></td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
    <div class="card-header"><i class="fas fa-calendar-alt mr-2"></i>Laporan Bulanan <?= $tahun ?></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Bulan</th><th>Jumlah Transaksi</th><th>Total Pemasukan</th></tr>
            </thead>
            <tbody>
            <?php
            $grand = 0;
            $bulanan->data_seek(0);
            while ($row = $bulanan->fetch_assoc()):
                $grand += $row['total'];
            ?>
            <tr>
                <td><?= $row['nama_bulan'] ?> <?= $tahun ?></td>
                <td><?= $row['jumlah_trx'] ?> transaksi</td>
                <td style="font-weight:700;color:#10B981"><?= rupiah($row['total']) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($bulanan->num_rows === 0): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php else: ?>
            <tr style="background:#F8FAFC">
                <td colspan="2" style="font-weight:700;text-align:right">Total Tahun <?= $tahun ?></td>
                <td style="font-weight:800;color:#2563EB;font-size:15px"><?= rupiah($grand) ?></td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
$chartLabels = json_encode($chart_bulan);
$chartValues = json_encode($chart_total);
$extraJs = "
new Chart(document.getElementById('chartBulanan').getContext('2d'), {
    type: 'bar',
    data: {
        labels: $chartLabels,
        datasets: [{
            label: 'Pemasukan (Rp)',
            data: $chartValues,
            backgroundColor: 'rgba(37,99,235,.15)',
            borderColor: '#2563EB',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') },
                grid: { color: '#F1F5F9' }
            },
            x: { grid: { display: false } }
        }
    }
});
";

require_once __DIR__ . '/layout/footer.php'; ?>
