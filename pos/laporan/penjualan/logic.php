<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_sales') {
    $start_date = $_GET['start_date'] ?? date('Y-m-d');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $role = $_SESSION['role'] ?? 'kasir';
    
    try {
        // Cek setting: Apakah history harus di-hidden untuk kasir?
        $stmt_set = $pdo->prepare("SELECT setting_value FROM pos_settings WHERE setting_key = 'hide_old_history_cashier' LIMIT 1");
        $stmt_set->execute();
        $is_hidden = $stmt_set->fetchColumn() == '1';

        // Jika dia kasir dan setting aktif, paksa tanggal hanya hari ini
        if ($role === 'kasir' && $is_hidden) {
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
        }

        $stmt = $pdo->prepare("
            SELECT s.*, c.name as customer_name 
            FROM sales_pos s 
            LEFT JOIN customers_pos c ON s.customer_id = c.id 
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $sales, 'restricted' => ($role === 'kasir' && $is_hidden)]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_detail') {
    $sale_id = $_GET['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("
            SELECT sd.*, COALESCE(p.name, sd.custom_name) as product_name, p.image
            FROM sale_details_pos sd 
            LEFT JOIN products p ON sd.product_id = p.id 
            WHERE sd.sale_id = ?
        ");
        $stmt->execute([$sale_id]);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>