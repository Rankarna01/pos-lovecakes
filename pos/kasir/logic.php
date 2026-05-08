<?php
// Matikan error HTML agar JSON tidak rusak
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

try {
    // 1. TARIK DATA MASTER (PRODUK & PELANGGAN) REAL-TIME
    if ($action === 'get_master_data') {
        $products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $customers = $pdo->query("SELECT id, name, points FROM customers_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success', 
            'products' => $products, 
            'customers' => $customers
        ]);
        exit;
    }

    // 2. FUNGSI CEK VOUCHER
    if ($action === 'check_voucher') {
        $code = trim($_POST['code'] ?? '');
        $subtotal = (float)($_POST['subtotal'] ?? 0);

        $stmt = $pdo->prepare("SELECT * FROM vouchers_pos WHERE voucher_code = ? AND is_active = 1");
        $stmt->execute([$code]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) { echo json_encode(['status' => 'error', 'message' => 'Voucher tidak ditemukan.']); exit; }
        if ($voucher['valid_until'] && strtotime($voucher['valid_until']) < strtotime(date('Y-m-d'))) { echo json_encode(['status' => 'error', 'message' => 'Voucher sudah kadaluarsa.']); exit; }
        if ($voucher['max_usage'] > 0 && $voucher['used_count'] >= $voucher['max_usage']) { echo json_encode(['status' => 'error', 'message' => 'Kuota voucher sudah habis.']); exit; }
        if ($subtotal < $voucher['min_purchase']) { echo json_encode(['status' => 'error', 'message' => 'Minimal belanja Rp ' . number_format($voucher['min_purchase'], 0, ',', '.')]); exit; }

        echo json_encode(['status' => 'success', 'data' => $voucher]);
        exit;
    }

    // 3. FUNGSI PROSES CHECKOUT & POTONG STOK
    if ($action === 'checkout') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $pdo->beginTransaction();

        $invoice_no = 'INV-' . date('YmdHis') . '-' . rand(100,999);
        $customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
        
        // Simpan Transaksi Master
        $stmt = $pdo->prepare("INSERT INTO sales_pos (invoice_no, customer_id, order_type, subtotal, discount_voucher, voucher_code, discount_points, discount_manual, points_used, points_earned, total_amount, payment_method, payment_status, dp_amount, amount_paid, change_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $invoice_no, $customer_id, $data['order_type'], $data['subtotal'], 
            $data['discount_voucher'], $data['voucher_code'], $data['discount_points'], $data['discount_manual'], 
            $data['points_used'], $data['points_earned'], $data['total_amount'], 
            $data['payment_method'], $data['payment_status'], $data['dp_amount'], $data['amount_paid'], $data['change_amount']
        ]);
        $sale_id = $pdo->lastInsertId();

        $stmt_detail = $pdo->prepare("INSERT INTO sale_details_pos (sale_id, product_id, is_custom, custom_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_potong_stok = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_history = $pdo->prepare("INSERT INTO inventory_history_pos (product_id, type, qty, reference_no, source) VALUES (?, 'Keluar', ?, ?, 'Penjualan POS')");
        
        // Looping Setiap Item
        foreach ($data['items'] as $item) {
            $prod_id = $item['is_custom'] ? 0 : $item['id'];
            $is_custom = $item['is_custom'] ? 1 : 0;
            $custom_name = $item['is_custom'] ? $item['name'] : null;

            $stmt_detail->execute([$sale_id, $prod_id, $is_custom, $custom_name, $item['price'], $item['qty'], $item['subtotal']]);

            if (!$is_custom) {
                $stmt_potong_stok->execute([$item['qty'], $prod_id]);
                $stmt_history->execute([$prod_id, $item['qty'], $invoice_no]);
            }
        }

        // Update Kuota Voucher & Poin
        if (!empty($data['voucher_code'])) {
            $pdo->prepare("UPDATE vouchers_pos SET used_count = used_count + 1 WHERE voucher_code = ?")->execute([$data['voucher_code']]);
        }
        if ($customer_id) {
            $pdo->prepare("UPDATE customers_pos SET points = points - ? + ? WHERE id = ?")->execute([$data['points_used'], $data['points_earned'], $customer_id]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'invoice' => $invoice_no, 'message' => 'Transaksi Berhasil! Stok telah dikurangi.']);
        exit;
    }

    // 4. FUNGSI MENGAMBIL STATUS PESANAN CUSTOM
    if ($action === 'get_active_orders') {
        $stmt = $pdo->query("
            SELECT s.id, s.invoice_no, s.created_at, s.production_status, c.name as customer_name
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE s.id IN (SELECT sale_id FROM sale_details_pos WHERE is_custom = 1)
            AND DATE(s.created_at) = CURDATE()
            ORDER BY s.created_at DESC
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($orders as $order) {
            $stmtDetail = $pdo->prepare("SELECT custom_name, qty FROM sale_details_pos WHERE sale_id = ? AND is_custom = 1");
            $stmtDetail->execute([$order['id']]);
            $items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
            
            $itemNames = [];
            foreach($items as $it) {
                $itemNames[] = $it['qty'] . 'x ' . $it['custom_name'];
            }
            
            $order['items_list'] = implode(', ', $itemNames); 
            $order['time'] = date('H:i', strtotime($order['created_at']));
            $data[] = $order;
        }

        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>