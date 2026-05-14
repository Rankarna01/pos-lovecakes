<?php
// Pastikan session menyala agar bisa baca role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil role, pangkas spasi kosong, ubah ke huruf kecil
$user_role_sidebar = strtolower(trim($_SESSION['pos_role'] ?? ''));

// 🎯 LOGIKA DIBALIK (LEBIH AMAN): 
// Hanya yang bernama 'admin', 'owner', atau 'superadmin' yang dapat sidebar Admin!
if (in_array($user_role_sidebar, ['admin', 'owner', 'superadmin', 'backoffice'])) {
    require_once __DIR__ . '/sidebar_admin.php';
} else {
    // Sisanya (Kasir, Staff, atau kalau session ngadat), WAJIB MASUK KE KASIR!
    require_once __DIR__ . '/sidebar_kasir.php';
}
?>