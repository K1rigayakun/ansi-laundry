<?php
// pages/pelanggan.php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle  = 'Data Pelanggan';
$activePage = 'pelanggan';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── PROSES FORM ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = clean($_POST['nama'] ?? '');
    $no_hp  = clean($_POST['no_hp'] ?? '');
    $alamat = clean($_POST['alamat'] ?? '');

    if (empty($nama) || empty($no_hp) || empty($alamat)) {
        setFlash('error', 'Semua field wajib diisi!');
    } else {
        $act_post = $_POST['action'] ?? '';

        if ($act_post === 'tambah') {
            $stmt = $conn->prepare("INSERT INTO pelanggan (nama, no_hp, alamat) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $nama, $no_hp, $alamat);
            if ($stmt->execute()) {
                setFlash('success', "Pelanggan <b>$nama</b> berhasil ditambahkan!");
            } else {
                setFlash('error', 'Gagal menambah pelanggan: ' . $conn->error);
            }
        } elseif ($act_post === 'edit') {
            $edit_id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE pelanggan SET nama=?, no_hp=?, alamat=? WHERE id=?");
            $stmt->bind_param('sssi', $nama, $no_hp, $alamat, $edit_id);
            if ($stmt->execute()) {
                setFlash('success', "Data pelanggan berhasil diperbarui!");
            } else {
                setFlash('error', 'Gagal memperbarui data.');
            }
        }
    }
    header('Location: ' . BASE_URL . '/pages/pelanggan.php');
    exit;
}

// ── HAPUS ──
if ($action === 'hapus' && $id > 0) {
    // Cek apakah ada transaksi
    $cek = $conn->query("SELECT COUNT(*) FROM transaksi WHERE pelanggan_id = $id")->fetch_row()[0];
    if ($cek > 0) {
        setFlash('error', 'Tidak bisa menghapus pelanggan yang memiliki transaksi!');
    } else {
        $conn->query("DELETE FROM membership WHERE pelanggan_id = $id");
        $conn->query("DELETE FROM pelanggan WHERE id = $id");
        setFlash('success', 'Pelanggan berhasil dihapus.');
    }
    header('Location: ' . BASE_URL . '/pages/pelanggan.php');
    exit;
}

// ── DATA EDIT ──
$edit_data = null;
if ($action === 'edit' && $id > 0) {
    $edit_data = $conn->query("SELECT * FROM pelanggan WHERE id = $id")->fetch_assoc();
    if (!$edit_data) { header('Location: ' . BASE_URL . '/pages/pelanggan.php'); exit; }
}

// ── SEARCH & LIST ──
$search = clean($_GET['search'] ?? '');
$where  = $search ? "WHERE nama LIKE '%$search%' OR no_hp LIKE '%$search%'" : '';
$pelanggan_list = $conn->query("
    SELECT p.*, 
           COALESCE(m.kuota_awal - m.kuota_terpakai, 0) AS sisa_kuota,
           m.kuota_awal,
           (SELECT COUNT(*) FROM transaksi WHERE pelanggan_id = p.id) AS total_trx
    FROM pelanggan p
    LEFT JOIN membership m ON m.pelanggan_id = p.id
    $where
    ORDER BY p.created_at DESC
");

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header">
    <div>
        <h4><i class="fas fa-users mr-2 text-primary"></i>Data Pelanggan</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Pelanggan</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
        <i class="fas fa-plus mr-1"></i> Tambah Pelanggan
    </button>
</div>

<div class="row">
    <!-- Form Edit (jika ada) -->
    <?php if ($edit_data): ?>
    <div class="col-12 mb-4">
        <div class="card border-primary">
            <div class="card-header text-primary"><i class="fas fa-edit mr-2"></i>Edit Pelanggan</div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" value="<?= clean($edit_data['nama']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>No. WhatsApp</label>
                                <input type="text" name="no_hp" class="form-control" value="<?= clean($edit_data['no_hp']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Alamat</label>
                                <input type="text" name="alamat" class="form-control" value="<?= clean($edit_data['alamat']) ?>" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-save mr-1"></i>Simpan</button>
                    <a href="<?= BASE_URL ?>/pages/pelanggan.php" class="btn btn-outline-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- List -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list mr-2"></i>Daftar Pelanggan
                    <span class="badge badge-primary ml-1"><?= $pelanggan_list->num_rows ?></span>
                </span>
                <form method="GET" class="d-flex" style="gap:8px">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Cari nama / no HP..." value="<?= $search ?>"
                           style="width:220px">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if ($search): ?>
                    <a href="<?= BASE_URL ?>/pages/pelanggan.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Nama</th>
                                <th>No. HP / WA</th>
                                <th>Alamat</th>
                                <th>Membership</th>
                                <th>Total Trx</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($pelanggan_list->num_rows === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                                <?= $search ? 'Pelanggan tidak ditemukan.' : 'Belum ada pelanggan.' ?>
                            </td></tr>
                        <?php endif; ?>

                        <?php $no = 1; while ($p = $pelanggan_list->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td>
                                <div style="font-weight:600"><?= clean($p['nama']) ?></div>
                                <small style="color:#94a3b8"><?= date('d/m/Y', strtotime($p['created_at'])) ?></small>
                            </td>
                            <td>
                                <?= clean($p['no_hp']) ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['no_hp']) ?>"
                                   target="_blank" class="btn btn-wa btn-sm ml-1">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= clean($p['alamat']) ?>
                            </td>
                            <td>
                                <?php if ($p['kuota_awal'] > 0): ?>
                                    <?php
                                    $sisa  = max(0, $p['sisa_kuota']);
                                    $pct   = $p['kuota_awal'] > 0 ? ($sisa / $p['kuota_awal']) * 100 : 0;
                                    $color = $pct <= 20 ? '#EF4444' : ($pct <= 50 ? '#F59E0B' : '#10B981');
                                    ?>
                                    <div style="font-size:12px;color:#475569;margin-bottom:4px">
                                        Sisa: <strong style="color:<?= $color ?>"><?= $sisa ?> kg</strong> / <?= $p['kuota_awal'] ?> kg
                                        <?php if ($sisa <= KUOTA_WARNING): ?>
                                            <span class="badge badge-warning ml-1">⚠ Hampir habis</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="kuota-bar">
                                        <div class="kuota-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= $p['total_trx'] ?> trx</span>
                            </td>
                            <td>
                                <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=hapus&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin hapus pelanggan <?= clean($p['nama']) ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/pages/transaksi.php?action=tambah&pelanggan_id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-success" title="Tambah Transaksi">
                                    <i class="fas fa-plus"></i>
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
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header" style="border-bottom:1px solid #E2E8F0">
                <h5 class="modal-title"><i class="fas fa-user-plus mr-2 text-primary"></i>Tambah Pelanggan</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>
                    <div class="form-group">
                        <label>No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 08123456789" required>
                        <small class="text-muted">Digunakan untuk kirim pesan WA</small>
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control" rows="3"
                                  placeholder="Contoh: Jl. Merdeka No. 10, Medan" required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #E2E8F0">
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
