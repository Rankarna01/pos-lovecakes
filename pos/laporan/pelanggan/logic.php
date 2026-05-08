<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// 1. TARIK SEMUA PELANGGAN & REKAPANNYA
if ($action === 'get_customers') {
    try {
        // Gabungkan tabel customers dengan sales untuk mencari total belanja seumur hidup
        $stmt = $pdo->query("
            SELECT 
                c.id, c.name, c.phone, c.points, c.address,
                COUNT(s.id) as total_trx,
                COALESCE(SUM(s.total_amount), 0) as total_spent
            FROM customers_pos c
            LEFT JOIN sales_pos s ON c.id = s.customer_id
            GROUP BY c.id, c.name, c.phone, c.points, c.address
            ORDER BY total_spent DESC
        ");
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $customers]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. TARIK DETAIL HISTORI BELANJA 1 PELANGGAN
if ($action === 'get_history') {
    $customer_id = $_GET['id'] ?? 0;
    try {
        // Ambil riwayat struk
        $stmt_sales = $pdo->prepare("
            SELECT id, invoice_no, total_amount, payment_status, payment_method, created_at 
            FROM sales_pos 
            WHERE customer_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt_sales->execute([$customer_id]);
        $sales = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);

        // Ambil barang apa saja yang dibeli di tiap struk
        foreach ($sales as &$sale) {
            $stmt_items = $pdo->prepare("
                SELECT COALESCE(p.name, sd.custom_name) as product_name, sd.qty, sd.price, sd.subtotal
                FROM sale_details_pos sd
                LEFT JOIN products p ON sd.product_id = p.id
                WHERE sd.sale_id = ?
            ");
            $stmt_items->execute([$sale['id']]);
            $sale['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['status' => 'success', 'data' => $sales]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>