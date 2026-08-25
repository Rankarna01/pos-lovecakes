<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php';

header('Content-Type: application/json');

// 1. Auto-create table food_delivery_payment_methods_pos
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS food_delivery_payment_methods_pos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform_code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(50) NULL,
            type VARCHAR(50) DEFAULT 'Digital',
            fee_percent DECIMAL(5,2) DEFAULT 0.00,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Auto-seed default non-cash methods if empty
    $count = $pdo->query("SELECT COUNT(*) FROM food_delivery_payment_methods_pos")->fetchColumn();
    if ($count == 0) {
        $seeds = [
            // GrabFood
            ['grabfood', 'Saldo GrabMerchant', 'GRAB_MERCHANT', 'E-Wallet', 0],
            ['grabfood', 'OVO / GrabPay', 'OVO_GRAB', 'Digital', 0],
            ['grabfood', 'QRIS BCA', 'QRIS_BCA', 'QRIS', 0],
            ['grabfood', 'Transfer Bank', 'TRANSFER', 'Transfer', 0],

            // GoFood
            ['gofood', 'Saldo GoBiz / GoPay', 'GOBIZ_MERCHANT', 'E-Wallet', 0],
            ['gofood', 'GoPay Customer', 'GOPAY_CUST', 'Digital', 0],
            ['gofood', 'QRIS BCA', 'QRIS_BCA', 'QRIS', 0],
            ['gofood', 'Transfer Bank', 'TRANSFER', 'Transfer', 0],

            // ShopeeFood
            ['shopeefood', 'Saldo Shopee Merchant', 'SHOPEE_MERCHANT', 'E-Wallet', 0],
            ['shopeefood', 'ShopeePay', 'SHOPEEPAY', 'Digital', 0],
            ['shopeefood', 'QRIS BCA', 'QRIS_BCA', 'QRIS', 0],
            ['shopeefood', 'Transfer Bank', 'TRANSFER', 'Transfer', 0],

            // TravelokaEats
            ['travelokaeats', 'Saldo Traveloka Merchant', 'TRAVELOKA_MERCHANT', 'E-Wallet', 0],
            ['travelokaeats', 'TravelokaPay', 'TRAVELOKAPAY', 'Digital', 0],
            ['travelokaeats', 'QRIS BCA', 'QRIS_BCA', 'QRIS', 0],
            ['travelokaeats', 'Transfer Bank', 'TRANSFER', 'Transfer', 0],
        ];

        $stmtSeed = $pdo->prepare("INSERT INTO food_delivery_payment_methods_pos (platform_code, name, code, type, fee_percent, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        foreach ($seeds as $s) {
            $stmtSeed->execute($s);
        }
    }
} catch (Exception $e) {}

$action = $_REQUEST['action'] ?? '';

// ----------------------------------------------------
// 1. GET METHODS (Per Platform atau Semua)
// ----------------------------------------------------
if ($action === 'get_methods') {
    try {
        $platform = trim($_REQUEST['platform'] ?? '');
        if (!empty($platform)) {
            $stmt = $pdo->prepare("SELECT * FROM food_delivery_payment_methods_pos WHERE platform_code = ? ORDER BY is_active DESC, sort_order ASC, id ASC");
            $stmt->execute([$platform]);
            $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->query("SELECT * FROM food_delivery_payment_methods_pos ORDER BY platform_code ASC, is_active DESC, sort_order ASC, id ASC");
            $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $platforms = [];
        try {
            $platforms = $pdo->query("SELECT * FROM food_delivery_platforms_pos WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        if (empty($platforms)) {
            $platforms = [
                ['platform_code' => 'grabfood', 'platform_name' => 'GrabFood'],
                ['platform_code' => 'gofood', 'platform_name' => 'GoFood'],
                ['platform_code' => 'shopeefood', 'platform_name' => 'ShopeeFood'],
                ['platform_code' => 'travelokaeats', 'platform_name' => 'TravelokaEats']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'methods' => $methods,
            'platforms' => $platforms
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// 2. SAVE METHOD (Tambah / Update)
// ----------------------------------------------------
if ($action === 'save_method') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $id = intval($data['id'] ?? 0);
        $platform_code = trim($data['platform_code'] ?? 'grabfood');
        $name = trim($data['name'] ?? '');
        $type = trim($data['type'] ?? 'Digital');
        $fee_percent = floatval($data['fee_percent'] ?? 0);
        $code = trim($data['code'] ?? strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '_', $name)));

        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama metode pembayaran wajib diisi!']);
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE food_delivery_payment_methods_pos SET platform_code = ?, name = ?, code = ?, type = ?, fee_percent = ? WHERE id = ?");
            $stmt->execute([$platform_code, $name, $code, $type, $fee_percent, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Metode pembayaran platform berhasil diperbarui!']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO food_delivery_payment_methods_pos (platform_code, name, code, type, fee_percent, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$platform_code, $name, $code, $type, $fee_percent]);
            echo json_encode(['status' => 'success', 'message' => 'Metode pembayaran platform berhasil ditambahkan!']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// 3. TOGGLE STATUS (Aktifkan / Nonaktifkan)
// ----------------------------------------------------
if ($action === 'toggle_status') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = intval($data['id'] ?? 0);
        $is_active = !empty($data['is_active']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE food_delivery_payment_methods_pos SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $id]);

        echo json_encode([
            'status' => 'success',
            'message' => $is_active ? 'Metode pembayaran diaktifkan!' : 'Metode pembayaran dinonaktifkan!'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// 4. DELETE METHOD
// ----------------------------------------------------
if ($action === 'delete_method') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = intval($data['id'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM food_delivery_payment_methods_pos WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Metode pembayaran berhasil dihapus!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
