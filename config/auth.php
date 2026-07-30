<?php
require_once __DIR__ . '/env.php';

// Pastikan session berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 🔄 GLOBAL STORE SWITCHER (ADMIN FILTER)
// ==========================================
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'switch_store') {
    try {
        require_once __DIR__ . '/database.php';
        $wh_id = intval($_REQUEST['warehouse_id'] ?? 0);
        if ($wh_id > 0) {
            $stmt_w = $pdo->prepare("SELECT name as store_name, code as store_code FROM warehouses WHERE id = ?");
            $stmt_w->execute([$wh_id]);
            $w = $stmt_w->fetch(PDO::FETCH_ASSOC);
            if ($w) {
                $_SESSION['pos_warehouse_id'] = $wh_id;
                $_SESSION['pos_store_name'] = $w['store_name'];
                $_SESSION['pos_store_code'] = $w['store_code'];
            }
        } else {
            $_SESSION['pos_warehouse_id'] = 0;
            $_SESSION['pos_store_name'] = 'Semua Outlet (Global)';
            $_SESSION['pos_store_code'] = 'GLOBAL';
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'warehouse_id' => $_SESSION['pos_warehouse_id'], 'store_name' => $_SESSION['pos_store_name']]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Cek apakah user sudah login
if (!isset($_SESSION['pos_user_id']) || empty($_SESSION['pos_user_id'])) {
    header("Location: " . BASE_URL . "auth/");
    exit();
}

// Segarkan info outlet dan pastikan struktur tabel mendukung multi-outlet
try {
    require_once __DIR__ . '/database.php';
    try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN warehouse_id INT NULL AFTER id"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE shifts_history_pos ADD COLUMN warehouse_id INT NULL AFTER id"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE petty_cash_pos ADD COLUMN warehouse_id INT NULL AFTER id"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE opname_history_pos ADD COLUMN doc_no VARCHAR(50) NULL AFTER id"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE opname_history_pos ADD COLUMN warehouse_id INT NULL AFTER doc_no"); } catch (Exception $e) {}
    try { $pdo->exec("UPDATE sales_pos SET warehouse_id = 1 WHERE warehouse_id IS NULL"); } catch (Exception $e) {}

    if (!isset($_SESSION['pos_warehouse_id']) || !isset($_SESSION['pos_store_name'])) {
        $stmt_w = $pdo->prepare("
            SELECT u.warehouse_id, w.name as store_name, w.code as store_code 
            FROM users_pos u 
            LEFT JOIN warehouses w ON u.warehouse_id = w.id 
            WHERE u.id = ?
        ");
        $stmt_w->execute([$_SESSION['pos_user_id']]);
        $u_w = $stmt_w->fetch(PDO::FETCH_ASSOC);
        if ($u_w) {
            $_SESSION['pos_warehouse_id'] = $u_w['warehouse_id'];
            $_SESSION['pos_store_name'] = $u_w['store_name'] ?? 'Semua Outlet (Global)';
            $_SESSION['pos_store_code'] = $u_w['store_code'] ?? 'GLOBAL';
        }
    }
} catch (Exception $e) {}

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
        '/pos/pengaturan/global/',
        '/pos/pengaturan/pajak/',
        '/pos/pengaturan/notifikasi/',
        '/pos/pengaturan/pembayaran/',
        '/pos/opname/'
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