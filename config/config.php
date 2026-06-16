<?php
// config/config.php — Konstanta Aplikasi

define('APP_NAME', 'LaundryKu');
define('APP_TAGLINE', 'Sistem Informasi Laundry');
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
define('BASE_URL', getenv('BASE_URL') ?: "$protocol://$host");

define('HARGA_PER_KG', 7000);        // Harga default per kg
define('KUOTA_WARNING', 5);          // Notif jika sisa kuota <= 5 kg

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
