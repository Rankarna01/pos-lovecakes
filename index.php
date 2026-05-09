<?php
session_start();

// PASTIKAN INI SESUAI DENGAN NAMA FOLDER PROJECT-MU
$base_url = 'http://localhost/pos-lovecakes/'; 

// Jika sudah ada sesi login aktif, arahkan ke Kasir
if (isset($_SESSION['pos_user_id'])) {
    header("Location: " . $base_url . "pos/kasir/");
    exit;
} else {
    // Jika belum login, arahkan ke halaman Login
    header("Location: " . $base_url . "auth/");
    exit;
}
?>