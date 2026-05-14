<?php
session_start();
// PERHATIKAN: Path naik 3 folder (pos -> pengaturan -> pos)
require_once '../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM pos_settings");
        // FETCH_KEY_PAIR akan membuat array format: ['markup_grab' => '30', 'pin_supervisor' => '123456']
        $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    // Tangkap data dari form (tambahkan default kosong jika tidak ada)
    $settings = [
        'markup_grab'      => $_POST['markup_grab'] ?? '0',
        'markup_gojek'     => $_POST['markup_gojek'] ?? '0',
        'pin_supervisor'   => $_POST['pin_supervisor'] ?? '',
        'wa_gateway_api'   => $_POST['wa_gateway_api'] ?? '',
        'wa_number_sender' => $_POST['wa_number_sender'] ?? '',
        'default_start_cash' => $_POST['default_start_cash'] ?? '0'
    ];

    try {
        $pdo->beginTransaction();
        
        // Simpan / Update menggunakan ON DUPLICATE KEY
        $stmt = $pdo->prepare("INSERT INTO pos_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pengaturan POS berhasil diperbarui!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}
?>