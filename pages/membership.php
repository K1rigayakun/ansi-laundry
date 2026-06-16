<?php
// pages/membership.php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle  = 'Membership / Kuota';
$activePage = 'membership';

// ── PROSES ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'tambah') {
        $pelanggan_id = (int)($_POST['pelanggan_id'] ?? 0);
        $kuota_awal   = (float)($_POST['kuota_awal'] ?? 0);
        if ($pelanggan_id > 0 && $kuota_awal > 0) {
            // Cek sudah ada?
            $cek = $conn->query("SELECT id FROM membership WHERE pelanggan_id = $pelanggan_id")->num_rows;
            if ($cek > 0) {
                $conn->query("UPDATE membership SET kuota_awal = kuota_awal + $kuota_awal WHERE pelanggan_id = $pelanggan_id");
                setFlash('success', 'Kuota membership berhasil ditambah!');
            } else {
                $stmt = $conn->prepare("INSERT INTO membership (pelanggan_id, kuota_awal) VALUES (?, ?)");
                $stmt->bind_param('id', $pelanggan_id, $kuota_awal);
                $stmt->execute();
                setFlash('success', 'Membership berhasil dibuat!');
            }
        }
    } elseif ($act === 'reset') {
        $mid = (int)($_POST['membership_id'] ?? 0);
        $conn->query("UPDATE membership SET kuota_terpakai = 0 WHERE id = $mid");
        setFlash('success', 'Kuota berhasil direset!');
    } elseif ($act === 'hapus') {
        $mid = (int)($_POST['membership_id'] ?? 0);
        $conn->query("DELETE FROM membership WHERE id = $mid");
        setFlash('success', 'Membership berhasil dihapus.');
    }

    header('Location: ' . BASE_URL . '/pages/membership.php');
    exit;
}

// ── LIST ──
$membership_list = $conn->query("
    SELECT m.*, p.nama, p.no_hp, p.alamat,
           (m.kuota_awal - m.kuota_terpakai) AS sisa_kuota
    FROM membership m
    JOIN pelanggan p ON m.pelanggan_id = p.id
    ORDER BY sisa_kuota ASC
");

// Pelanggan yang belum punya membership
$pelanggan_nonmember = $conn->query("
    SELECT id, nama, no_hp FROM pelanggan
    WHERE id NOT IN (SELECT pelanggan_id FROM membership)
    ORDER BY nama
");

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header">
    <div>
        <h4><i class="fas fa-id-card mr-2 text-primary"></i>Membership / Kuota</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Membership</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambahMember">
        <i class="fas fa-plus mr-1"></i> Tambah Membership
    </button>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-list mr-2"></i>Daftar Membership</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Kuota Awal</th>
                        <th>Terpakai</th>
                        <th>Sisa Kuota</th>
                        <th>Progress</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($membership_list->num_rows === 0): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">
                        <i class="fas fa-id-card fa-2x mb-2 d-block"></i>
                        Belum ada data membership.
                    </td></tr>
                <?php endif; ?>
                <?php while ($m = $membership_list->fetch_assoc()):
                    $sisa   = max(0, $m['sisa_kuota']);
                    $pct    = $m['kuota_awal'] > 0 ? ($sisa / $m['kuota_awal']) * 100 : 0;
                    $color  = $pct <= 20 ? '#EF4444' : ($pct <= 50 ? '#F59E0B' : '#10B981');
                    $warning = $sisa <= KUOTA_WARNING;
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600"><?= clean($m['nama']) ?></div>
                        <small style="color:#94a3b8"><?= clean($m['no_hp']) ?></small>
                    </td>
                    <td><?= $m['kuota_awal'] ?> kg</td>
                    <td><?= $m['kuota_terpakai'] ?> kg</td>
                    <td>
                        <span style="font-weight:700;color:<?= $color ?>"><?= $sisa ?> kg</span>
                        <?php if ($warning): ?>
                            <span class="badge badge-warning ml-1">⚠ Hampir habis</span>
                        <?php endif; ?>
                    </td>
                    <td style="min-width:120px">
                        <div class="kuota-bar" style="margin-bottom:4px">
                            <div class="kuota-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                        </div>
                        <small style="color:#94a3b8;font-size:10px"><?= round($pct) ?>%</small>
                    </td>
                    <td>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Reset kuota ke 0?')">
                            <input type="hidden" name="action" value="reset">
                            <input type="hidden" name="membership_id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus membership ini?')">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="membership_id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambahMember" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-id-card mr-2 text-primary"></i>Tambah / Isi Membership</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih Pelanggan</label>
                        <select name="pelanggan_id" class="form-control" required>
                            <option value="">— Pilih —</option>
                            <!-- Semua pelanggan bisa ditambah (top up jika sudah ada) -->
                            <?php
                            $all_p = $conn->query("SELECT id, nama FROM pelanggan ORDER BY nama");
                            while ($p = $all_p->fetch_assoc()):
                            ?>
                            <option value="<?= $p['id'] ?>"><?= clean($p['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted">Jika sudah ada membership, kuota akan ditambahkan (top up).</small>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Kuota (kg)</label>
                        <input type="number" name="kuota_awal" class="form-control"
                               step="0.5" min="1" placeholder="Contoh: 30" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
