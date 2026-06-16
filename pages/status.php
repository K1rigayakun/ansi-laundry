<?php
// pages/status.php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle  = 'Status Cucian';
$activePage = 'status';

$id = (int)($_GET['id'] ?? 0);

// ── UPDATE STATUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $trx_id    = (int)($_POST['trx_id'] ?? 0);
    $new_status = clean($_POST['status'] ?? '');
    $valid = ['menunggu_dijemput','sudah_dijemput','diproses','selesai','sedang_diantar','selesai_diantar'];

    if ($trx_id > 0 && in_array($new_status, $valid)) {
        $stmt = $conn->prepare("UPDATE transaksi SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $trx_id);
        if ($stmt->execute()) {
            setFlash('success', 'Status cucian berhasil diperbarui!');
        }
    }
    header('Location: ' . BASE_URL . '/pages/status.php');
    exit;
}

// ── QUICK UPDATE STATUS via GET ──
if (isset($_GET['set_status']) && $id > 0) {
    $new_st = clean($_GET['set_status']);
    $valid = ['menunggu_dijemput','sudah_dijemput','diproses','selesai','sedang_diantar','selesai_diantar'];
    if (in_array($new_st, $valid)) {
        $conn->query("UPDATE transaksi SET status = '$new_st' WHERE id = $id");
        setFlash('success', 'Status berhasil diubah!');
    }
    header('Location: ' . BASE_URL . '/pages/status.php');
    exit;
}

// ── DETAIL TRANSAKSI (untuk update) ──
$detail = null;
if ($id > 0) {
    $detail = $conn->query("
        SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, p.alamat
        FROM transaksi t JOIN pelanggan p ON t.pelanggan_id = p.id
        WHERE t.id = $id
    ")->fetch_assoc();
}

// ── LIST SEMUA TRANSAKSI AKTIF ──
$transaksi_aktif = $conn->query("
    SELECT t.*, p.nama AS nama_pelanggan, p.no_hp
    FROM transaksi t
    JOIN pelanggan p ON t.pelanggan_id = p.id
    WHERE t.status NOT IN ('selesai_diantar', 'selesai')
    ORDER BY t.created_at DESC
");

$transaksi_selesai = $conn->query("
    SELECT t.*, p.nama AS nama_pelanggan, p.no_hp
    FROM transaksi t
    JOIN pelanggan p ON t.pelanggan_id = p.id
    WHERE t.status IN ('selesai_diantar', 'selesai')
    ORDER BY t.updated_at DESC
    LIMIT 20
");

// Flow status
$status_flow = [
    'menunggu_dijemput' => 'sudah_dijemput',
    'sudah_dijemput'    => 'diproses',
    'diproses'          => 'selesai',
    'selesai'           => 'sedang_diantar',
    'sedang_diantar'    => 'selesai_diantar',
];

$status_next_label = [
    'menunggu_dijemput' => ['icon' => 'fa-motorcycle', 'text' => 'Tandai Sudah Dijemput',  'color' => 'btn-info'],
    'sudah_dijemput'    => ['icon' => 'fa-soap',       'text' => 'Mulai Proses',            'color' => 'btn-primary'],
    'diproses'          => ['icon' => 'fa-check',      'text' => 'Tandai Selesai',          'color' => 'btn-success'],
    'selesai'           => ['icon' => 'fa-truck',      'text' => 'Mulai Antar',             'color' => 'btn-secondary'],
    'sedang_diantar'    => ['icon' => 'fa-flag-checkered','text' => 'Sudah Diantar',        'color' => 'btn-dark'],
];

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header">
    <div>
        <h4><i class="fas fa-tasks mr-2 text-primary"></i>Status Cucian</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Status Cucian</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($detail): ?>
<!-- Detail Update Status -->
<div class="card mb-4" style="border-left: 4px solid #2563EB">
    <div class="card-header">
        <i class="fas fa-sync-alt mr-2 text-primary"></i>
        Update Status — <code><?= $detail['kode_transaksi'] ?></code>
        <a href="<?= BASE_URL ?>/pages/status.php" class="btn btn-sm btn-outline-secondary float-right">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Pelanggan</small>
                <strong><?= clean($detail['nama_pelanggan']) ?></strong>
            </div>
            <div class="col-md-2">
                <small class="text-muted d-block">Berat</small>
                <strong><?= $detail['berat'] ?> kg</strong>
            </div>
            <div class="col-md-2">
                <small class="text-muted d-block">Layanan</small>
                <strong><?= layananLabel($detail['jenis_layanan']) ?></strong>
            </div>
            <div class="col-md-2">
                <small class="text-muted d-block">Total</small>
                <strong><?= rupiah($detail['total_harga']) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Status Sekarang</small>
                <?= statusBadge($detail['status']) ?>
            </div>
        </div>

        <!-- Status progress bar -->
        <?php
        $statuses_order = ['menunggu_dijemput','sudah_dijemput','diproses','selesai','sedang_diantar','selesai_diantar'];
        $current_idx = array_search($detail['status'], $statuses_order);
        $status_labels = [
            'menunggu_dijemput' => 'Tunggu Jemput',
            'sudah_dijemput'    => 'Dijemput',
            'diproses'          => 'Diproses',
            'selesai'           => 'Selesai',
            'sedang_diantar'    => 'Diantar',
            'selesai_diantar'   => 'Terkirim',
        ];
        ?>
        <div class="d-flex align-items-center mb-4" style="gap:4px;overflow-x:auto;padding-bottom:4px">
        <?php foreach ($statuses_order as $idx => $st): ?>
            <?php
            $done   = $idx <= $current_idx;
            $active = $idx === $current_idx;
            ?>
            <div style="text-align:center;min-width:80px">
                <div style="
                    width:32px;height:32px;border-radius:50%;
                    background:<?= $done ? '#2563EB' : '#E2E8F0' ?>;
                    color:<?= $done ? '#fff' : '#94A3B8' ?>;
                    display:inline-flex;align-items:center;justify-content:center;
                    font-size:12px;font-weight:700;
                    <?= $active ? 'box-shadow:0 0 0 4px rgba(37,99,235,.2);' : '' ?>
                ">
                    <?= $done ? '<i class="fas fa-check" style="font-size:11px"></i>' : ($idx + 1) ?>
                </div>
                <div style="font-size:10px;color:<?= $active ? '#2563EB' : '#94A3B8' ?>;margin-top:4px;font-weight:<?= $active ? '700' : '500' ?>">
                    <?= $status_labels[$st] ?>
                </div>
            </div>
            <?php if ($idx < count($statuses_order) - 1): ?>
            <div style="flex:1;height:2px;background:<?= $idx < $current_idx ? '#2563EB' : '#E2E8F0' ?>;min-width:20px"></div>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap" style="gap:8px">
            <?php if (isset($status_flow[$detail['status']])): ?>
                <?php $next = $status_flow[$detail['status']]; $btn = $status_next_label[$detail['status']]; ?>
                <a href="?id=<?= $detail['id'] ?>&set_status=<?= $next ?>"
                   class="btn <?= $btn['color'] ?>">
                    <i class="fas <?= $btn['icon'] ?> mr-2"></i><?= $btn['text'] ?>
                </a>
            <?php endif; ?>

            <!-- Manual select -->
            <form method="POST" class="d-inline">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="trx_id" value="<?= $detail['id'] ?>">
                <div class="d-flex" style="gap:6px">
                    <select name="status" class="form-control form-control-sm" style="min-width:160px">
                        <?php foreach ($statuses_order as $st): ?>
                        <option value="<?= $st ?>" <?= $detail['status'] === $st ? 'selected' : '' ?>><?= $status_labels[$st] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Set Manual</button>
                </div>
            </form>

            <!-- WA Buttons -->
            <?php
            $no = preg_replace('/[^0-9]/', '', $detail['no_hp']);
            if ($no && $no[0] === '0') $no = '62' . substr($no, 1);
            $msg_selesai = urlencode("Halo kak *{$detail['nama_pelanggan']}*, cucian Kakak sudah selesai dan siap diambil/diantar 😊\nKode: {$detail['kode_transaksi']}\nTotal: " . rupiah($detail['total_harga']));
            ?>
            <a href="https://wa.me/<?= $no ?>?text=<?= $msg_selesai ?>" target="_blank" class="btn btn-wa">
                <i class="fab fa-whatsapp mr-1"></i> Notif Selesai
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Transaksi Aktif -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-spinner fa-spin mr-2 text-warning"></i>Cucian Aktif</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Kode</th><th>Pelanggan</th><th>Layanan</th><th>Status</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($transaksi_aktif->num_rows === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-check-double fa-2x mb-2 d-block text-success"></i>
                                Semua cucian sudah selesai! 🎉
                            </td></tr>
                        <?php endif; ?>
                        <?php while ($trx = $transaksi_aktif->fetch_assoc()): ?>
                        <tr>
                            <td><code style="font-size:11px;color:#2563EB"><?= $trx['kode_transaksi'] ?></code></td>
                            <td>
                                <div style="font-weight:600;font-size:13px"><?= clean($trx['nama_pelanggan']) ?></div>
                                <small style="color:#94a3b8"><?= clean($trx['no_hp']) ?></small>
                            </td>
                            <td style="font-size:12px"><?= layananLabel($trx['jenis_layanan']) ?></td>
                            <td><?= statusBadge($trx['status']) ?></td>
                            <td>
                                <a href="?id=<?= $trx['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit mr-1"></i>Update
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaksi Selesai -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-check-double mr-2 text-success"></i>Selesai Terbaru</div>
            <div class="card-body p-0">
                <?php while ($trx = $transaksi_selesai->fetch_assoc()): ?>
                <div style="padding:12px 16px;border-bottom:1px solid #F1F5F9">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <code style="font-size:11px;color:#10B981"><?= $trx['kode_transaksi'] ?></code>
                            <div style="font-size:12px;font-weight:600"><?= clean($trx['nama_pelanggan']) ?></div>
                        </div>
                        <?= statusBadge($trx['status']) ?>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if ($transaksi_selesai->num_rows === 0): ?>
                    <div class="text-center text-muted py-3" style="font-size:13px">Belum ada</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
