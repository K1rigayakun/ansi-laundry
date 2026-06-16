<?php
// config/auth.php — Session & Auth Helper

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

function getAdminName() {
    return $_SESSION['admin_nama'] ?? 'Admin';
}

// Flash message helper
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Sanitize input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format rupiah
function rupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Generate kode transaksi
function generateKodeTrx($conn) {
    $date = date('Ymd');
    $result = $conn->query("SELECT COUNT(*) as total FROM transaksi WHERE CAST(created_at AS DATE) = CURRENT_DATE");
    $row = $result->fetch_assoc();
    $urut = str_pad($row['total'] + 1, 4, '0', STR_PAD_LEFT);
    return "TRX-{$date}-{$urut}";
}

// Label & badge status
function statusBadge($status) {
    $map = [
        'menunggu_dijemput' => ['label' => 'Menunggu Dijemput', 'class' => 'badge-warning'],
        'sudah_dijemput'    => ['label' => 'Sudah Dijemput',    'class' => 'badge-info'],
        'diproses'          => ['label' => 'Diproses',          'class' => 'badge-primary'],
        'selesai'           => ['label' => 'Selesai',           'class' => 'badge-success'],
        'sedang_diantar'    => ['label' => 'Sedang Diantar',    'class' => 'badge-secondary'],
        'selesai_diantar'   => ['label' => 'Selesai Diantar',   'class' => 'badge-dark'],
    ];
    $s = $map[$status] ?? ['label' => $status, 'class' => 'badge-light'];
    return "<span class=\"badge {$s['class']}\">{$s['label']}</span>";
}

function layananLabel($layanan) {
    $map = [
        'ambil_sendiri' => '🏠 Ambil Sendiri',
        'antar'         => '🚚 Antar',
        'jemput_antar'  => '🔄 Jemput + Antar',
    ];
    return $map[$layanan] ?? $layanan;
}
