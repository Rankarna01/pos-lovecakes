<?php
// Pastikan session berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set BASE_URL dinamis
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';

if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder);
}

// Cek apakah user sudah login
if (!isset($_SESSION['pos_user_id']) || empty($_SESSION['pos_user_id'])) {
    header("Location: " . BASE_URL . "auth/");
    exit();
}

// ==========================================
// KUNCI PINTU URL (ROUTE BLOCKER 403)
// ==========================================
$current_uri = $_SERVER['REQUEST_URI'];
$role = strtolower($_SESSION['pos_role'] ?? '');

// JIKA YANG LOGIN ADALAH KASIR, KITA BATASI RUANG GERAKNYA!
if (in_array($role, ['kasir', 'cashier'])) {
    
    // Daftar folder yang HARAM dimasuki oleh Kasir
    $blocked_folders = [
        '/pos/dashboard/',
        '/pos/mitra/',
        '/pos/karyawan/',
        '/pos/laporan/',
        '/pos/pemasaran/',
        '/pos/online/', 
        '/pos/kemitraan/',
        '/pos/pengaturan/toko/',
        '/pos/pengaturan/pajak/',
        '/pos/pengaturan/notifikasi/'
    ];

    $is_blocked = false;
    foreach ($blocked_folders as $folder_haram) {
        if (strpos($current_uri, $folder_haram) !== false) {
            $is_blocked = true;
            break;
        }
    }

    // JIKA TERCIDUK DI FOLDER HARAM -> TAMPILKAN ERROR 403!
    if ($is_blocked) {
        http_response_code(403);
        echo '<!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>403 - Akses Ditolak</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;900&display=swap" rel="stylesheet">
        </head>
        <body class="bg-slate-100 flex items-center justify-center h-screen p-4" style="font-family: \'Poppins\', sans-serif;">
            <div class="bg-white p-8 md:p-10 rounded-[2rem] shadow-xl max-w-md w-full text-center border border-rose-100">
                <div class="w-24 h-24 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-5xl">🚫</span>
                </div>
                <h1 class="text-3xl font-black text-slate-800 mb-2">Akses Ditolak!</h1>
                <p class="text-slate-500 mb-8 text-sm font-medium leading-relaxed">
                    Mohon maaf, peran akun Anda (<b>KASIR</b>) tidak memiliki izin untuk membuka halaman ini.
                </p>
                <a href="' . BASE_URL . 'pos/kasir/" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-6 rounded-xl transition-all w-full block shadow-lg shadow-blue-600/30">
                    Kembali ke Mesin Kasir
                </a>
            </div>
        </body>
        </html>';
        exit(); // MATIKAN PROSES PHP AGAR HALAMAN ADMIN TIDAK TER-LOAD!
    }
}
?>