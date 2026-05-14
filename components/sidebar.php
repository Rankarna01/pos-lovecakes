<?php
// SAKLAR PINTAR: Deteksi siapa yang login, lalu panggil file UI fisiknya!
$user_role_sidebar = strtolower($_SESSION['pos_role'] ?? '');

if (in_array($user_role_sidebar, ['kasir', 'cashier'])) {
    // Jika Kasir, muat file sidebar_kasir.php
    require_once __DIR__ . '/sidebar_kasir.php';
} else {
    // Jika Admin/Owner, muat file sidebar_admin.php
    require_once __DIR__ . '/sidebar_admin.php';
}
?>