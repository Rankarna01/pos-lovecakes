<?php
session_start();

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$base_url = $is_localhost ? '/pos-lovecakes/' : '/';

// ==========================================
// Cek sesi aktif seperti di Sistem Produksi
// ==========================================
if (isset($_SESSION['pos_user_id'])) {
    // Kalau sudah login, arahkan ke Dashboard (Pastikan pakai garis miring di akhir!)
    header("Location: " . $base_url . "pos/dashboard/");
    exit;
} else {
    // Kalau belum login, arahkan ke halaman form login
    header("Location: " . $base_url . "auth/"); 
    exit;
}
?>