<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_sales') {
    $start_date = $_GET['start_date'] ?? date('Y-m-d');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $role = $_SESSION['pos_role'] ?? 'kasir';
    
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

        // 1. Rincian Detail Penjualan (History List)
        $stmt = $pdo->prepare("
            SELECT s.*, c.name as customer_name 
            FROM sales_pos s 
            LEFT JOIN customers_pos c ON s.customer_id = c.id 
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Global Payment Summary (Omset Sistem)
        $stmt_pay = $pdo->prepare("
            SELECT sp.payment_method, pm.type, SUM(sp.amount) as total 
            FROM sale_payments_pos sp
            LEFT JOIN payment_methods pm ON sp.payment_method = pm.name
            WHERE DATE(sp.created_at) BETWEEN ? AND ? 
            GROUP BY sp.payment_method, pm.type
        ");
        $stmt_pay->execute([$start_date, $end_date]);
        $pay_results = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
        
        $paymentData = ['cash' => 0, 'qris' => 0, 'total' => 0];
        foreach ($pay_results as $row) {
            if ($row['type'] === 'Cash' || strtolower($row['payment_method']) === 'cash') { 
                $paymentData['cash'] += (float)$row['total']; 
            } else { 
                $paymentData['qris'] += (float)$row['total']; 
            }
            $paymentData['total'] += (float)$row['total'];
        }

        // 3. Pembayaran Detail (Cash/TF Breakdown)
        $stmt_pay_breakdown = $pdo->prepare("
            SELECT payment_method, SUM(amount) as total_amount
            FROM sale_payments_pos 
            WHERE DATE(created_at) BETWEEN ? AND ? 
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ");
        $stmt_pay_breakdown->execute([$start_date, $end_date]);
        $payment_breakdown = $stmt_pay_breakdown->fetchAll(PDO::FETCH_ASSOC);

        // 4. Pembayaran DP dan Pelunasan
        $stmt_dp = $pdo->prepare("
            SELECT payment_type, SUM(amount) as total_amount, COUNT(id) as total_transactions
            FROM sale_payments_pos
            WHERE payment_type IN ('dp', 'pelunasan') AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY payment_type
        ");
        $stmt_dp->execute([$start_date, $end_date]);
        $dp_pelunasan = $stmt_dp->fetchAll(PDO::FETCH_ASSOC);

        // 5. Penjualan per Kategori
        $stmt_cat = $pdo->prepare("
            SELECT COALESCE(p.category, 'Uncategorized') as category_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY p.category
            ORDER BY total_amount DESC
        ");
        $stmt_cat->execute([$start_date, $end_date]);
        $sales_by_category = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        // 6. Penjualan per Barang (Terlaris)
        $stmt_item = $pdo->prepare("
            SELECT COALESCE(p.name, sd.custom_name) as item_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY COALESCE(p.name, sd.custom_name)
            ORDER BY total_amount DESC
            LIMIT 50
        ");
        $stmt_item->execute([$start_date, $end_date]);
        $sales_by_item = $stmt_item->fetchAll(PDO::FETCH_ASSOC);

        // 7. Penjualan per Konsumen
        $stmt_cust = $pdo->prepare("
            SELECT COALESCE(c.name, 'Pelanggan Umum') as customer_name, COUNT(s.id) as total_transactions, SUM(s.total_amount) as total_spent
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY s.customer_id
            ORDER BY total_spent DESC
            LIMIT 50
        ");
        $stmt_cust->execute([$start_date, $end_date]);
        $sales_by_customer = $stmt_cust->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'data' => $sales, 
            'restricted' => ($role === 'kasir' && $is_hidden),
            'payments' => $paymentData,
            'payment_breakdown' => $payment_breakdown,
            'dp_pelunasan' => $dp_pelunasan,
            'sales_by_category' => $sales_by_category,
            'sales_by_item' => $sales_by_item,
            'sales_by_customer' => $sales_by_customer
        ]);
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
