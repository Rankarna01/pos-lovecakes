<?php
session_start();

// Deteksi Routing Dinamis (Apakah ini di localhost atau di server asli?)
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);

// Jika di XAMPP/lokal, gunakan nama foldernya. Jika di server/cPanel, cukup gunakan '/'
$base_url = $is_localhost ? '/pos-lovecakes/' : '/';

// ==========================================
// Pengecekan Sesi Login (Otomatis & Aman)
// ==========================================

// Jika Sesi PHP untuk kasir sudah aktif
if (isset($_SESSION['pos_user_id'])) {
    // Arahkan ke Layar Kasir
    header("Location: " . $base_url . "pos/kasir/dashboard");
    exit;
} else {
    // Jika belum login atau sesi sudah hangus (browser ditutup), arahkan ke halaman Login
    // Sesuaikan path-nya dengan letak folder auth kamu
    header("Location: " . $base_url . "pos/dashboard"); 
    exit;
}
// Jangan tambahkan spasi atau karakter apapun di bawah baris ini!