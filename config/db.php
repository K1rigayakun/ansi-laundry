<?php
// config/db.php — Koneksi Database

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'db_laundry');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fee;border:1px solid #f00;border-radius:8px;margin:20px;">
        <strong>❌ Koneksi Database Gagal!</strong><br>
        Error: ' . $conn->connect_error . '<br><br>
        <em>Pastikan XAMPP MySQL sudah berjalan dan database <b>db_laundry</b> sudah dibuat.</em>
    </div>');
}

$conn->set_charset('utf8mb4');
