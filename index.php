<?php
// index.php — Dashboard
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

// ── Stats ──
$total_pelanggan = $conn->query("SELECT COUNT(*) FROM pelanggan")->fetch_row()[0];
$total_transaksi = $conn->query("SELECT COUNT(*) FROM transaksi")->fetch_row()[0];

$r = $conn->query("SELECT SUM(total_harga) FROM transaksi WHERE CAST(created_at AS DATE) = CURRENT_DATE");
$pemasukan_hari  = $r->fetch_row()[0] ?? 0;

$r2 = $conn->query("SELECT SUM(total_harga) FROM transaksi WHERE EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM CURRENT_DATE)");
$pemasukan_bulan = $r2->fetch_row()[0] ?? 0;

// ── Transaksi terbaru ──
$transaksi_terbaru = $conn->query("
    SELECT t.*, p.nama AS nama_pelanggan, p.no_hp
    FROM transaksi t
    JOIN pelanggan p ON t.pelanggan_id = p.id
    ORDER BY t.created_at DESC
    LIMIT 7
");

// ── Grafik 7 hari terakhir ──
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-$i days"));
    $label = date('d/m', strtotime("-$i days"));
    $r3 = $conn->query("SELECT SUM(total_harga) FROM transaksi WHERE CAST(created_at AS DATE) = '$date'");
    $chart_data[] = ['label' => $label, 'value' => $r3->fetch_row()[0] ?? 0];
}

// ── Status summary ──
$status_counts = [];
$status_rows = $conn->query("SELECT status, COUNT(*) as total FROM transaksi GROUP BY status");
while ($row = $status_rows->fetch_assoc()) {
    $status_counts[$row['status']] = $row['total'];
}

// ── Kuota hampir habis ──
$kuota_warning = $conn->query("
    SELECT m.*, p.nama, p.no_hp,
           (m.kuota_awal - m.kuota_terpakai) AS sisa
    FROM membership m
    JOIN pelanggan p ON m.pelanggan_id = p.id
    WHERE (m.kuota_awal - m.kuota_terpakai) <= " . KUOTA_WARNING . "
");

require_once __DIR__ . '/pages/layout/header.php';
?>

<div class="page-header">
    <div>
        <h4><i class="fas fa-th-large mr-2 text-primary"></i>Dashboard</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Beranda</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/pages/transaksi.php?action=tambah" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> Transaksi Baru
    </a>
</div>

<!-- STAT CARDS -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#DBEAFE">
                <i class="fas fa-users" style="color:#2563EB"></i>
            </div>
            <div>
                <div class="stat-label">Total Pelanggan</div>
                <div class="stat-value"><?= $total_pelanggan ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#D1FAE5">
                <i class="fas fa-shopping-basket" style="color:#10B981"></i>
            </div>
            <div>
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value"><?= $total_transaksi ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#FEF3C7">
                <i class="fas fa-coins" style="color:#F59E0B"></i>
            </div>
            <div>
                <div class="stat-label">Pemasukan Hari Ini</div>
                <div class="stat-value" style="font-size:16px"><?= rupiah($pemasukan_hari) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#EDE9FE">
                <i class="fas fa-chart-line" style="color:#7C3AED"></i>
            </div>
            <div>
                <div class="stat-label">Pemasukan Bulan Ini</div>
                <div class="stat-value" style="font-size:16px"><?= rupiah($pemasukan_bulan) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($kuota_warning && $kuota_warning->num_rows > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border-left:4px solid #F59E0B">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    <strong>Peringatan Kuota!</strong>&nbsp;
    <?php while ($k = $kuota_warning->fetch_assoc()): ?>
        <span class="badge badge-warning"><?= clean($k['nama']) ?> — sisa <?= $k['sisa'] ?> kg</span>&nbsp;
    <?php endwhile; ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- Chart -->
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-chart-area mr-2 text-primary"></i>Pemasukan 7 Hari Terakhir</span>
            </div>
            <div class="card-body">
                <canvas id="chartPemasukan" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Status Summary -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-tasks mr-2 text-primary"></i>Ringkasan Status</div>
            <div class="card-body">
                <?php
                $statuses = [
                    'menunggu_dijemput' => ['label' => 'Menunggu Dijemput', 'color' => '#F59E0B'],
                    'sudah_dijemput'    => ['label' => 'Sudah Dijemput',    'color' => '#06B6D4'],
                    'diproses'          => ['label' => 'Diproses',          'color' => '#2563EB'],
                    'selesai'           => ['label' => 'Selesai',           'color' => '#10B981'],
                    'sedang_diantar'    => ['label' => 'Sedang Diantar',    'color' => '#8B5CF6'],
                    'selesai_diantar'   => ['label' => 'Selesai Diantar',   'color' => '#1E293B'],
                ];
                foreach ($statuses as $key => $info):
                    $count = $status_counts[$key] ?? 0;
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:13px;color:#475569">
                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $info['color'] ?>;margin-right:6px"></span>
                        <?= $info['label'] ?>
                    </span>
                    <span style="font-weight:700;font-size:14px;color:#1E293B"><?= $count ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Transaksi Terbaru -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-history mr-2 text-primary"></i>Transaksi Terbaru</span>
        <a href="<?= BASE_URL ?>/pages/transaksi.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Berat</th>
                        <th>Layanan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($trx = $transaksi_terbaru->fetch_assoc()): ?>
                    <tr>
                        <td><code style="font-size:12px;color:#2563EB"><?= $trx['kode_transaksi'] ?></code></td>
                        <td>
                            <div style="font-weight:600"><?= clean($trx['nama_pelanggan']) ?></div>
                            <small style="color:#94a3b8"><?= clean($trx['no_hp']) ?></small>
                        </td>
                        <td><?= $trx['berat'] ?> kg</td>
                        <td><?= layananLabel($trx['jenis_layanan']) ?></td>
                        <td style="font-weight:700"><?= rupiah($trx['total_harga']) ?></td>
                        <td><?= statusBadge($trx['status']) ?></td>
                        <td style="color:#94a3b8;font-size:12px"><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total_transaksi == 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$chartLabels = json_encode(array_column($chart_data, 'label'));
$chartValues = json_encode(array_column($chart_data, 'value'));
$extraJs = "
const ctx = document.getElementById('chartPemasukan').getContext('2d');
new Chart(ctx, {
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
                ticks: {
                    callback: v => 'Rp ' + v.toLocaleString('id-ID')
                },
                grid: { color: '#F1F5F9' }
            },
            x: { grid: { display: false } }
        }
    }
});
";

require_once __DIR__ . '/pages/layout/footer.php';
?>
