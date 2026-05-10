<?php
require_once '../../config/auth.php';
session_start(); require_once '../../config/database.php'; header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_active_orders') {
    $stmt = $pdo->query("SELECT s.id, s.invoice_no, s.created_at, s.production_status, s.channel, s.pickup_date, s.pickup_time, c.name as customer_name FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.is_po = 1 AND s.production_status != 'selesai_diambil' ORDER BY s.pickup_date ASC, s.pickup_time ASC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = []; $today = date('Y-m-d'); $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    foreach ($orders as $order) {
        $stmtDetail = $pdo->prepare("SELECT custom_name, qty FROM sale_details_pos WHERE sale_id = ? AND is_custom = 1");
        $stmtDetail->execute([$order['id']]);
        $items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
        
        $itemNames = []; foreach($items as $it) { $itemNames[] = $it['qty'] . 'x ' . $it['custom_name']; }
        $order['items_list'] = implode(', ', $itemNames); 
        
        // LOGIC NOTIFIKASI H-1 (FIFO)
        $order['alert_type'] = '';
        if ($order['pickup_date'] === $today) { $order['alert_type'] = 'today'; }
        else if ($order['pickup_date'] === $tomorrow) { $order['alert_type'] = 'tomorrow'; }
        
        $data[] = $order;
    }
    echo json_encode(['status' => 'success', 'data' => $data]); exit;
}
?>