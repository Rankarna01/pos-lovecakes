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
$role = strtolower(trim($_SESSION['pos_role'] ?? ''));

// 🎯 LOGIKA DIBALIK: Jika BUKAN Admin, maka dia adalah Kasir (Batasi ruang geraknya!)
if (!in_array($role, ['admin', 'owner', 'superadmin', 'backoffice'])) {
    
    // Daftar folder yang HARAM dimasuki oleh Kasir
    $blocked_folders = [
        '/pos/dashboard/',
        '/pos/mitra/',
        '/pos/karyawan/',
        '/pos/laporan/',
        '/pos/pemasaran/',
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
            <style>
                body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .error-card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; max-width: 400px; width: 90%; border: 1px solid #ffe4e6; }
                .icon { font-size: 70px; margin-bottom: 20px; display: inline-block; background: #fff1f2; padding: 20px; border-radius: 50%; color: #e11d48; }
                h1 { color: #0f172a; margin-bottom: 10px; font-size: 24px; font-weight: 800; }
                p { color: #64748b; margin-bottom: 30px; font-size: 14px; line-height: 1.6; }
                .btn { display: inline-block; background: #2563eb; color: white; text-decoration: none; padding: 14px 24px; border-radius: 12px; font-weight: bold; font-size: 14px; width: 100%; box-sizing: border-box; transition: 0.3s; }
                .btn:hover { background: #1d4ed8; }
            </style>
        </head>
        <body>
            <div class="error-card">
                <div class="icon">🚫</div>
                <h1>Akses Ditolak!</h1>
                <p>Mohon maaf, peran akun Anda tidak memiliki izin untuk membuka halaman Backoffice ini.</p>
                <a href="' . BASE_URL . 'pos/kasir/" class="btn">Kembali ke Mesin Kasir</a>
            </div>
        </body>
        </html>';
        exit(); // MATIKAN PROSES PHP DISINI!
    }
}
?>