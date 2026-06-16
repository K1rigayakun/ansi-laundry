<?php
// pages/antar_jemput.php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle  = 'Layanan Antar–Jemput';
$activePage = 'antar_jemput';

$list = $conn->query("
    SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, p.alamat AS alamat_pelanggan
    FROM transaksi t
    JOIN pelanggan p ON t.pelanggan_id = p.id
    WHERE t.jenis_layanan IN ('antar', 'jemput_antar')
    ORDER BY t.created_at DESC
");

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header">
    <div>
        <h4><i class="fas fa-truck mr-2 text-primary"></i>Layanan Antar–Jemput</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Antar–Jemput</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-list mr-2"></i>Daftar Transaksi Antar/Jemput</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>No. WA</th>
                        <th>Alamat</th>
                        <th>Layanan</th>
                        <th>Status</th>
                        <th>Aksi WA</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($list->num_rows === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        <i class="fas fa-truck fa-2x mb-2 d-block"></i>
                        Belum ada transaksi antar/jemput.
                    </td></tr>
                <?php endif; ?>
                <?php while ($trx = $list->fetch_assoc()):
                    $no = preg_replace('/[^0-9]/', '', $trx['no_hp']);
                    if ($no && $no[0] === '0') $no = '62' . substr($no, 1);
                    $alamat_display = $trx['alamat_pengiriman'] ?: $trx['alamat_pelanggan'];

                    $msg_jemput  = urlencode("Halo kak *{$trx['nama_pelanggan']}*, kami dari {$_SESSION['admin_nama']} laundry. Apakah cucian ingin dijemput hari ini? 😊\nAlamat kami catat: $alamat_display");
                    $msg_selesai = urlencode("Halo kak *{$trx['nama_pelanggan']}*, cucian Kakak sudah selesai dan siap diantar ke alamat Kakak 😊\nKode: {$trx['kode_transaksi']}");
                ?>
                <tr>
                    <td><code style="font-size:11px;color:#2563EB"><?= $trx['kode_transaksi'] ?></code></td>
                    <td style="font-weight:600"><?= clean($trx['nama_pelanggan']) ?></td>
                    <td><?= clean($trx['no_hp']) ?></td>
                    <td style="font-size:12px;max-width:180px"><?= clean($alamat_display) ?></td>
                    <td><?= layananLabel($trx['jenis_layanan']) ?></td>
                    <td><?= statusBadge($trx['status']) ?></td>
                    <td>
                        <div class="d-flex" style="gap:6px;flex-wrap:wrap">
                            <?php if ($trx['jenis_layanan'] === 'jemput_antar'): ?>
                            <a href="https://wa.me/<?= $no ?>?text=<?= $msg_jemput ?>"
                               target="_blank" class="btn btn-wa btn-sm">
                                <i class="fab fa-whatsapp mr-1"></i>Jemput
                            </a>
                            <?php endif; ?>
                            <a href="https://wa.me/<?= $no ?>?text=<?= $msg_selesai ?>"
                               target="_blank" class="btn btn-wa btn-sm" style="background:#128C7E">
                                <i class="fab fa-whatsapp mr-1"></i>Antar
                            </a>
                            <a href="<?= BASE_URL ?>/pages/status.php?id=<?= $trx['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
