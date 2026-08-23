<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php';

header('Content-Type: application/json');

// Ensure database table and defaults exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pos_registered_devices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            device_token VARCHAR(64) NOT NULL UNIQUE,
            device_name VARCHAR(100) NOT NULL,
            warehouse_id INT DEFAULT 1,
            registered_ip VARCHAR(45) NULL,
            user_agent TEXT NULL,
            is_active TINYINT(1) DEFAULT 1,
            last_active_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("INSERT IGNORE INTO pos_settings (setting_key, setting_value) VALUES ('enable_device_restriction', '0'), ('device_reg_passcode', '889900')");
} catch (Exception $e) {}

$action = $_REQUEST['action'] ?? '';

// Helper to get / set pos_settings
function getPosSetting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM pos_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function setPosSetting($pdo, $key, $val) {
    $stmt = $pdo->prepare("INSERT INTO pos_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $val, $val]);
}

// ----------------------------------------------------
// 1. GET DATA (Daftar Perangkat & Pengaturan)
// ----------------------------------------------------
if ($action === 'get_data') {
    try {
        $devices = $pdo->query("
            SELECT d.*, 
                   COALESCE(w.name, CASE WHEN d.warehouse_id = 2 THEN 'Store 02' ELSE 'Store 01' END) AS store_name
            FROM pos_registered_devices d
            LEFT JOIN warehouses w ON d.warehouse_id = w.id
            ORDER BY d.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $warehouses = [];
        try { $warehouses = $pdo->query("SELECT id, name FROM warehouses ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {}
        if (empty($warehouses)) {
            $warehouses = [
                ['id' => 1, 'name' => 'Store 01'],
                ['id' => 2, 'name' => 'Store 02']
            ];
        }

        $enable_restriction = getPosSetting($pdo, 'enable_device_restriction', '0');
        $passcode = getPosSetting($pdo, 'device_reg_passcode', '889900');

        echo json_encode([
            'status' => 'success',
            'devices' => $devices,
            'warehouses' => $warehouses,
            'enable_restriction' => $enable_restriction === '1',
            'passcode' => $passcode
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// 2. TOGGLE RESTRICTION (Saklar Global ON / OFF)
// ----------------------------------------------------
if ($action === 'toggle_restriction') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $enable = !empty($data['enable']) ? '1' : '0';
        setPosSetting($pdo, 'enable_device_restriction', $enable);

        echo json_encode([
            'status' => 'success',
            'enable_restriction' => $enable === '1',
            'message' => $enable === '1' 
                ? 'Pembatasan Perangkat KASIR BERHASIL DIAKTIFKAN! Hanya perangkat terdaftar yang dapat membuka kasir.' 
                : 'Pembatasan Perangkat Kasir Dinonaktifkan (Mode Publik Aktif).'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// 3. UPDATE PASSCODE (Ubah Sandi Aktivasi)
// ----------------------------------------------------
if ($action === 'update_passcode') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $new_passcode = trim($data['passcode'] ?? '');

        if (empty($new_passcode)) {
            echo json_encode(['status' => 'error', 'message' => 'Sandi aktivasi tidak boleh kosong!']);
            exit;
        }

        setPosSetting($pdo, 'device_reg_passcode', $new_passcode);

        echo json_encode([
            'status' => 'success',
            'passcode' => $new_passcode,
            'message' => 'Sandi Aktivasi Perangkat berhasil diperbarui!'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// 4. TOGGLE DEVICE STATUS (Aktifkan / Nonaktifkan Perangkat)
// ----------------------------------------------------
if ($action === 'toggle_device_status') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $device_id = intval($data['id'] ?? 0);
        $is_active = !empty($data['is_active']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE pos_registered_devices SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $device_id]);

        echo json_encode([
            'status' => 'success',
            'message' => $is_active ? 'Perangkat berhasil diaktifkan kembali!' : 'Akses perangkat berhasil dinonaktifkan/diblokir!'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// 5. UPDATE DEVICE NAME / STORE
// ----------------------------------------------------
if ($action === 'update_device') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $device_id = intval($data['id'] ?? 0);
        $device_name = trim($data['device_name'] ?? '');
        $warehouse_id = intval($data['warehouse_id'] ?? 1);

        if (empty($device_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama Perangkat tidak boleh kosong!']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE pos_registered_devices SET device_name = ?, warehouse_id = ? WHERE id = ?");
        $stmt->execute([$device_name, $warehouse_id, $device_id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Informasi perangkat berhasil diperbarui!'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// 6. DELETE DEVICE
// ----------------------------------------------------
if ($action === 'delete_device') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $device_id = intval($data['id'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM pos_registered_devices WHERE id = ?");
        $stmt->execute([$device_id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Perangkat berhasil dihapus dari sistem!'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
