<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'read') {
    try {
        $stmt = $pdo->query("SELECT * FROM promo_auto_discounts ORDER BY min_purchase ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name']);
    $min_purchase = (float)$_POST['min_purchase'];
    $discount_type = $_POST['discount_type'] ?? 'PERCENT';
    $discount_value = (float)$_POST['discount_value'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+1 year'));
    $is_active = $_POST['is_active'] === 'true' || $_POST['is_active'] == '1' ? 1 : 0;

    if (empty($name) || $discount_value <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Lengkapi nama promo dan besaran diskon!']);
        exit;
    }

    try {
        if (empty($id)) {
            $stmt = $pdo->prepare("
                INSERT INTO promo_auto_discounts (name, min_purchase, discount_type, discount_value, start_date, end_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $min_purchase, $discount_type, $discount_value, $start_date, $end_date, $is_active]);
            echo json_encode(['status' => 'success', 'message' => 'Diskon otomatis berhasil ditambahkan!']);
        } else {
            $stmt = $pdo->prepare("
                UPDATE promo_auto_discounts 
                SET name = ?, min_purchase = ?, discount_type = ?, discount_value = ?, start_date = ?, end_date = ?, is_active = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $min_purchase, $discount_type, $discount_value, $start_date, $end_date, $is_active, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Diskon otomatis berhasil diperbarui!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM promo_auto_discounts WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Diskon otomatis berhasil dihapus!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
