<?php
// pages/transaksi.php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle  = 'Transaksi Laundry';
$activePage = 'transaksi';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── PROSES TAMBAH TRANSAKSI ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $pelanggan_id    = (int)($_POST['pelanggan_id'] ?? 0);
    $berat           = (float)($_POST['berat'] ?? 0);
    $jenis_layanan   = clean($_POST['jenis_layanan'] ?? '');
    $harga_per_kg    = (float)($_POST['harga_per_kg'] ?? HARGA_PER_KG);
    $catatan         = clean($_POST['catatan'] ?? '');
    $alamat_pengiriman = clean($_POST['alamat_pengiriman'] ?? '');
    $gunakan_member  = isset($_POST['gunakan_membership']) ? 1 : 0;

    if ($pelanggan_id <= 0 || $berat <= 0 || empty($jenis_layanan)) {
        setFlash('error', 'Data transaksi tidak valid, periksa kembali!');
    } else {
        $total_harga = $berat * $harga_per_kg;

        // Set status awal berdasarkan layanan
        $status_awal = 'diproses';
        if ($jenis_layanan === 'jemput_antar') {
            $status_awal = 'menunggu_dijemput';
        }

        $kode = generateKodeTrx($conn);

        $stmt = $conn->prepare("
            INSERT INTO transaksi (kode_transaksi, pelanggan_id, berat, jenis_layanan, harga_per_kg, total_harga, status, alamat_pengiriman, catatan, gunakan_membership)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sidsdssssi',
            $kode, $pelanggan_id, $berat, $jenis_layanan,
            $harga_per_kg, $total_harga, $status_awal,
            $alamat_pengiriman, $catatan, $gunakan_member
        );

        if ($stmt->execute()) {
            // Kurangi kuota membership jika digunakan
            if ($gunakan_member) {
                $conn->query("UPDATE membership SET kuota_terpakai = kuota_terpakai + $berat WHERE pelanggan_id = $pelanggan_id");
            }
            setFlash('success', "Transaksi <b>$kode</b> berhasil disimpan! Total: " . rupiah($total_harga));
        } else {
            setFlash('error', 'Gagal menyimpan transaksi: ' . $conn->error);
        }
    }
    header('Location: ' . BASE_URL . '/pages/transaksi.php');
    exit;
}

// ── HAPUS TRANSAKSI ──
if ($action === 'hapus' && $id > 0) {
    $trx = $conn->query("SELECT * FROM transaksi WHERE id = $id")->fetch_assoc();
    if ($trx) {
        // Kembalikan kuota jika pakai membership
        if ($trx['gunakan_membership']) {
            $conn->query("UPDATE membership SET kuota_terpakai = kuota_terpakai - {$trx['berat']} WHERE pelanggan_id = {$trx['pelanggan_id']}");
        }
        $conn->query("DELETE FROM transaksi WHERE id = $id");
        setFlash('success', 'Transaksi berhasil dihapus.');
    }
    header('Location: ' . BASE_URL . '/pages/transaksi.php');
    exit;
}

// ── DATA PELANGGAN (untuk dropdown) ──
$pelanggan_all = $conn->query("SELECT id, nama, no_hp, alamat FROM pelanggan ORDER BY nama");

// ── FILTER & LIST ──
$filter_status = clean($_GET['status'] ?? '');
$filter_tanggal = clean($_GET['tanggal'] ?? '');
$where_parts = [];
if ($filter_status) $where_parts[] = "t.status = '$filter_status'";
if ($filter_tanggal) $where_parts[] = "CAST(t.created_at AS DATE) = '$filter_tanggal'";
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$transaksi_list = $conn->query("
    SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, p.alamat AS alamat_pelanggan
    FROM transaksi t
    JOIN pelanggan p ON t.pelanggan_id = p.id
    $where_sql
    ORDER BY t.created_at DESC
    LIMIT 100
");

// Pre-fill pelanggan dari query string (dari halaman pelanggan)
$preselect_pel = (int)($_GET['pelanggan_id'] ?? 0);
$preselect_data = null;
if ($preselect_pel > 0) {
    $preselect_data = $conn->query("SELECT *, COALESCE((SELECT kuota_awal - kuota_terpakai FROM membership WHERE pelanggan_id = $preselect_pel), 0) as sisa_kuota FROM pelanggan WHERE id = $preselect_pel")->fetch_assoc();
    $action = 'tambah'; // Auto buka form
}

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header">
    <div>
        <h4><i class="fas fa-shopping-basket mr-2 text-primary"></i>Transaksi Laundry</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Transaksi</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambahTrx">
        <i class="fas fa-plus mr-1"></i> Transaksi Baru
    </button>
</div>

<!-- FILTER BAR -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap">
            <div>
                <label class="mb-1" style="font-size:11px">Filter Status</label>
                <select name="status" class="form-control form-control-sm" style="min-width:160px">
                    <option value="">— Semua Status —</option>
                    <option value="menunggu_dijemput" <?= $filter_status === 'menunggu_dijemput' ? 'selected' : '' ?>>Menunggu Dijemput</option>
                    <option value="sudah_dijemput"    <?= $filter_status === 'sudah_dijemput' ? 'selected' : '' ?>>Sudah Dijemput</option>
                    <option value="diproses"          <?= $filter_status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                    <option value="selesai"           <?= $filter_status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="sedang_diantar"    <?= $filter_status === 'sedang_diantar' ? 'selected' : '' ?>>Sedang Diantar</option>
                    <option value="selesai_diantar"   <?= $filter_status === 'selesai_diantar' ? 'selected' : '' ?>>Selesai Diantar</option>
                </select>
            </div>
            <div>
                <label class="mb-1" style="font-size:11px">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm"
                       value="<?= $filter_tanggal ?>">
            </div>
            <div style="margin-top:18px">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= BASE_URL ?>/pages/transaksi.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list mr-2"></i>Daftar Transaksi
            <span class="badge badge-primary ml-1"><?= $transaksi_list->num_rows ?></span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Kode Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Berat</th>
                        <th>Layanan</th>
                        <th>Harga/kg</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($transaksi_list->num_rows === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Tidak ada transaksi ditemukan.
                    </td></tr>
                <?php endif; ?>

                <?php while ($trx = $transaksi_list->fetch_assoc()): ?>
                <tr>
                    <td><code style="font-size:12px;color:#2563EB"><?= $trx['kode_transaksi'] ?></code></td>
                    <td>
                        <div style="font-weight:600"><?= clean($trx['nama_pelanggan']) ?></div>
                        <small style="color:#94a3b8"><?= clean($trx['no_hp']) ?></small>
                    </td>
                    <td><?= $trx['berat'] ?> kg</td>
                    <td><?= layananLabel($trx['jenis_layanan']) ?></td>
                    <td><?= rupiah($trx['harga_per_kg']) ?></td>
                    <td style="font-weight:700;color:#1E293B"><?= rupiah($trx['total_harga']) ?></td>
                    <td>
                        <?= statusBadge($trx['status']) ?>
                        <?php if ($trx['gunakan_membership']): ?>
                            <span class="badge badge-info ml-1"><i class="fas fa-id-card"></i> Member</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#94a3b8"><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
                    <td>
                        <div class="d-flex" style="gap:4px;flex-wrap:wrap">
                            <!-- Update Status -->
                            <a href="<?= BASE_URL ?>/pages/status.php?id=<?= $trx['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Update Status">
                                <i class="fas fa-sync-alt"></i>
                            </a>

                            <?php
                            // Tombol WA berdasarkan jenis layanan
                            $no = preg_replace('/[^0-9]/', '', $trx['no_hp']);
                            if ($no && $no[0] === '0') $no = '62' . substr($no, 1);

                            if (in_array($trx['jenis_layanan'], ['jemput_antar'])):
                                $msg_jemput = urlencode("Halo kak *{$trx['nama_pelanggan']}*, apakah cucian ingin dijemput hari ini? 😊\nAlamat: {$trx['alamat_pengiriman']}");
                            ?>
                            <a href="https://wa.me/<?= $no ?>?text=<?= $msg_jemput ?>"
                               target="_blank" class="btn btn-wa btn-sm" title="WA Jemput">
                                <i class="fab fa-whatsapp"></i> Jemput
                            </a>
                            <?php endif; ?>

                            <?php if ($trx['status'] === 'selesai' || $trx['status'] === 'sedang_diantar'):
                                $msg_selesai = urlencode("Halo kak *{$trx['nama_pelanggan']}*, cucian Kakak sudah selesai dan siap " . ($trx['jenis_layanan'] !== 'ambil_sendiri' ? 'diantar' : 'diambil') . " 😊\nKode: {$trx['kode_transaksi']}");
                            ?>
                            <a href="https://wa.me/<?= $no ?>?text=<?= $msg_selesai ?>"
                               target="_blank" class="btn btn-wa btn-sm" title="WA Selesai">
                                <i class="fab fa-whatsapp"></i> Notif
                            </a>
                            <?php endif; ?>

                            <a href="?action=hapus&id=<?= $trx['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Yakin hapus transaksi ini?')">
                                <i class="fas fa-trash"></i>
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

<!-- Modal Tambah Transaksi -->
<div class="modal fade" id="modalTambahTrx" tabindex="-1" <?= ($action === 'tambah') ? 'style="display:block"' : '' ?>>
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle mr-2 text-primary"></i>Transaksi Baru</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" action="" id="formTrx">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Pelanggan <span class="text-danger">*</span></label>
                                <select name="pelanggan_id" class="form-control" id="selectPelanggan" required onchange="loadPelangganInfo(this)">
                                    <option value="">— Pilih Pelanggan —</option>
                                    <?php
                                    $pelanggan_all->data_seek(0);
                                    while ($p = $pelanggan_all->fetch_assoc()):
                                    ?>
                                    <option value="<?= $p['id'] ?>"
                                            data-hp="<?= clean($p['no_hp']) ?>"
                                            data-alamat="<?= clean($p['alamat']) ?>"
                                            <?= ($preselect_pel == $p['id']) ? 'selected' : '' ?>>
                                        <?= clean($p['nama']) ?> — <?= clean($p['no_hp']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jenis Layanan <span class="text-danger">*</span></label>
                                <select name="jenis_layanan" class="form-control" required onchange="toggleAlamat(this)">
                                    <option value="">— Pilih Layanan —</option>
                                    <option value="ambil_sendiri">🏠 Ambil Sendiri</option>
                                    <option value="antar">🚚 Antar</option>
                                    <option value="jemput_antar">🔄 Jemput + Antar</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Berat Cucian (kg) <span class="text-danger">*</span></label>
                                <input type="number" name="berat" id="inputBerat" class="form-control"
                                       step="0.1" min="0.1" placeholder="Contoh: 3.5" required
                                       oninput="hitungTotal()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Harga per Kg (Rp)</label>
                                <input type="number" name="harga_per_kg" id="inputHarga" class="form-control"
                                       value="<?= HARGA_PER_KG ?>" required oninput="hitungTotal()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Total Harga</label>
                                <div class="form-control" id="displayTotal"
                                     style="background:#F8FAFC;font-weight:700;color:#2563EB">
                                    Rp 0
                                </div>
                            </div>
                        </div>

                        <div class="col-12" id="wrapAlamat" style="display:none">
                            <div class="form-group">
                                <label>Alamat Antar/Jemput</label>
                                <input type="text" name="alamat_pengiriman" id="inputAlamat" class="form-control"
                                       placeholder="Akan diisi otomatis dari data pelanggan">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label>Catatan (opsional)</label>
                                <input type="text" name="catatan" class="form-control"
                                       placeholder="Misal: pisahkan baju putih, dll.">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="gunakan_membership" id="chkMember"
                                       class="form-check-input">
                                <label class="form-check-label" for="chkMember" style="font-weight:500">
                                    Gunakan Membership (kurangi kuota)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = "
function hitungTotal() {
    var berat  = parseFloat(document.getElementById('inputBerat').value) || 0;
    var harga  = parseFloat(document.getElementById('inputHarga').value) || 0;
    var total  = berat * harga;
    document.getElementById('displayTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function toggleAlamat(sel) {
    var wrap = document.getElementById('wrapAlamat');
    if (sel.value === 'antar' || sel.value === 'jemput_antar') {
        wrap.style.display = 'block';
    } else {
        wrap.style.display = 'none';
    }
}

function loadPelangganInfo(sel) {
    var opt = sel.options[sel.selectedIndex];
    var alamat = opt.getAttribute('data-alamat');
    document.getElementById('inputAlamat').value = alamat || '';
}

" . ($action === 'tambah' ? "\$('#modalTambahTrx').modal('show');" : "") . "
";

require_once __DIR__ . '/layout/footer.php'; ?>
