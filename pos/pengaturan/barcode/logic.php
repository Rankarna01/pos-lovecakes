<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// Pastikan tabel tersedia
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pos_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

// =====================================================
// GET - Ambil semua setting barcode
// =====================================================
if ($action === 'get') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM pos_settings WHERE setting_key LIKE 'barcode_%'");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $defaults = [
            'barcode_format'        => 'CODE128',
            'barcode_height'        => '30',
            'barcode_width'         => '1',
            'barcode_paper_size'    => '40x30',
            'barcode_paper_custom_w'=> '40',
            'barcode_paper_custom_h'=> '30',
            'barcode_per_row'       => '3',
            'barcode_show_name'     => '1',
            'barcode_name_position' => 'bottom',
            'barcode_show_sku'      => '1',
            'barcode_show_price'    => '1',
            'barcode_show_expired'  => '0',
            'barcode_show_category' => '0',
        ];

        $result = array_merge($defaults, $rows);
        echo json_encode(['status' => 'success', 'data' => $result]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// POST - Simpan setting barcode
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception("Data tidak valid.");
        }

        $allowed_keys = [
            'barcode_format', 'barcode_height', 'barcode_width', 'barcode_paper_size',
            'barcode_paper_custom_w', 'barcode_paper_custom_h', 'barcode_per_row',
            'barcode_show_name', 'barcode_name_position', 'barcode_show_sku',
            'barcode_show_price', 'barcode_show_expired', 'barcode_show_category'
        ];

        $stmt = $pdo->prepare("INSERT INTO pos_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        $pdo->beginTransaction();
        foreach ($allowed_keys as $key) {
            if (array_key_exists($key, $data)) {
                $stmt->execute([$key, strval($data[$key])]);
            }
        }
        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => 'Pengaturan barcode berhasil disimpan!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// GET PUBLIC - Untuk dipakai halaman cetak_barcode (tanpa auth cek)
// =====================================================
if ($action === 'get_public') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM pos_settings WHERE setting_key LIKE 'barcode_%'");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $defaults = [
            'barcode_format'        => 'CODE128',
            'barcode_height'        => '30',
            'barcode_width'         => '1',
            'barcode_paper_size'    => '40x30',
            'barcode_paper_custom_w'=> '40',
            'barcode_paper_custom_h'=> '30',
            'barcode_per_row'       => '3',
            'barcode_show_name'     => '1',
            'barcode_name_position' => 'bottom',
            'barcode_show_sku'      => '1',
            'barcode_show_price'    => '1',
            'barcode_show_expired'  => '0',
            'barcode_show_category' => '0',
        ];
        echo json_encode(['status' => 'success', 'data' => array_merge($defaults, $rows)]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal.']);
