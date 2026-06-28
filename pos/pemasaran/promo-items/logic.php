<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'read') {
    try {
        $stmt = $pdo->query("
            SELECT p.*, b.name as buy_product_name, g.name as get_product_name 
            FROM promo_buy_x_get_y p
            LEFT JOIN products b ON p.buy_product_id = b.id
            LEFT JOIN products g ON p.get_product_id = g.id
            ORDER BY p.created_at DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_products') {
    try {
        $stmt = $pdo->query("SELECT id, name, price FROM products ORDER BY name ASC");
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
    $buy_product_id = (int)$_POST['buy_product_id'];
    $buy_qty = (int)$_POST['buy_qty'];
    $get_product_id = (int)$_POST['get_product_id'];
    $get_qty = (int)$_POST['get_qty'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+1 year'));
    $is_active = $_POST['is_active'] === 'true' || $_POST['is_active'] == '1' ? 1 : 0;

    if (empty($name) || $buy_product_id <= 0 || $get_product_id <= 0 || $buy_qty <= 0 || $get_qty <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Lengkapi data promo dengan benar!']);
        exit;
    }

    try {
        if (empty($id)) {
            $stmt = $pdo->prepare("
                INSERT INTO promo_buy_x_get_y (name, buy_product_id, buy_qty, get_product_id, get_qty, start_date, end_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $buy_product_id, $buy_qty, $get_product_id, $get_qty, $start_date, $end_date, $is_active]);
            echo json_encode(['status' => 'success', 'message' => 'Promo Beli X Gratis Y berhasil ditambahkan!']);
        } else {
            $stmt = $pdo->prepare("
                UPDATE promo_buy_x_get_y 
                SET name = ?, buy_product_id = ?, buy_qty = ?, get_product_id = ?, get_qty = ?, start_date = ?, end_date = ?, is_active = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $buy_product_id, $buy_qty, $get_product_id, $get_qty, $start_date, $end_date, $is_active, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Promo berhasil diperbarui!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM promo_buy_x_get_y WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Promo berhasil dihapus!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
