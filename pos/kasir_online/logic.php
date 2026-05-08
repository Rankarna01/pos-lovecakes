<?php
session_start();
require_once '../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'checkout') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();

        $invoice_no = 'ONL-' . date('YmdHis') . '-' . rand(100,999);
        $customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
        
        // Simpan Transaksi Master (Termasuk Channel, Ongkir, dan Catatan)
        $stmt = $pdo->prepare("INSERT INTO sales_pos (invoice_no, customer_id, order_type, channel, subtotal, shipping_cost, notes, total_amount, payment_method, payment_status, amount_paid, change_amount) VALUES (?, ?, 'online', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $invoice_no, $customer_id, $data['channel'], $data['subtotal'], $data['shipping_cost'], $data['notes'],
            $data['total_amount'], $data['payment_method'], 'lunas', $data['amount_paid'], $data['change_amount']
        ]);
        $sale_id = $pdo->lastInsertId();

        // Simpan Detail Item
        $stmt_detail = $pdo->prepare("INSERT INTO sale_details_pos (sale_id, product_id, is_custom, custom_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($data['items'] as $item) {
            $prod_id = $item['is_custom'] ? 0 : $item['id'];
            $is_custom = $item['is_custom'] ? 1 : 0;
            $custom_name = $item['is_custom'] ? $item['name'] : null;
            $stmt_detail->execute([$sale_id, $prod_id, $is_custom, $custom_name, $item['price'], $item['qty'], $item['subtotal']]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'invoice' => $invoice_no, 'message' => 'Pesanan Online Berhasil Dibuat!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal memproses: ' . $e->getMessage()]);
    }
    exit;
}
?>