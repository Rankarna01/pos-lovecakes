<?php
session_start();
// Naik 3 tingkat ke folder config
require_once '../../../config/database.php'; 

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'get_settings') {
    try {
        $stmt = $pdo->query("SELECT * FROM loyalty_settings_pos WHERE id = 1 LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $settings]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_settings') {
    $is_active = $_POST['is_active'] === 'true' ? 1 : 0;
    $points_required = (int)$_POST['points_required'];
    $discount_amount = (float)$_POST['discount_amount'];
    $discount_type = $_POST['discount_type'];

    try {
        $stmt = $pdo->prepare("
            UPDATE loyalty_settings_pos 
            SET is_active = ?, points_required = ?, discount_amount = ?, discount_type = ? 
            WHERE id = 1
        ");
        $stmt->execute([$is_active, $points_required, $discount_amount, $discount_type]);
        
        echo json_encode(['status' => 'success', 'message' => 'Pengaturan Poin Loyalitas berhasil disimpan!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}
?>