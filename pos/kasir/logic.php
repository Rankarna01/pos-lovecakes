<?php
require_once '../../config/auth.php';
// Matikan error HTML agar JSON tidak rusak
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../../config/database.php'; 
require_once '../../config/auth.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['pos_user_id'] ?? 1;

try {
    // ==========================================
    // 1. FUNGSI SHIFT KASIR
    // ==========================================
    if ($action === 'check_shift') {
        $stmt = $pdo->prepare("SELECT * FROM shifts_history_pos WHERE user_id = ? AND status = 'open' LIMIT 1");
        $stmt->execute([$user_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        $master = $pdo->query("SELECT * FROM master_shifts_pos WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'has_open_shift' => !!$shift, 'master_shifts' => $master]);
        exit;
    }

    if ($action === 'open_shift') {
        $shift_id = $_POST['shift_id'] ?? '';
        $start_cash = $_POST['start_cash'] ?? 0;
        $pdo->prepare("INSERT INTO shifts_history_pos (user_id, shift_id, start_time, start_cash, status) VALUES (?, ?, NOW(), ?, 'open')")->execute([$user_id, $shift_id, $start_cash]);
        echo json_encode(['status' => 'success', 'message' => 'Shift berhasil dibuka!']);
        exit;
    }

    if ($action === 'close_shift') {
        $end_cash = $_POST['end_cash'] ?? 0;
        $pdo->prepare("UPDATE shifts_history_pos SET status = 'closed', end_time = NOW(), end_cash = ? WHERE user_id = ? AND status = 'open'")->execute([$end_cash, $user_id]);
        echo json_encode(['status' => 'success', 'message' => 'Kasir Berhasil Ditutup!']);
        exit;
    }

    // ==========================================
    // 2. FUNGSI MASTER DATA & VOUCHER
    // ==========================================
    // --- FUNGSI MASTER DATA ---
if ($action === 'get_master_data') {
    $products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $customers = $pdo->query("SELECT id, name, points FROM customers_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    // Tarik juga template menu custom
    $saved_customs = $pdo->query("SELECT * FROM saved_custom_items_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success', 
        'products' => $products, 
        'customers' => $customers,
        'saved_customs' => $saved_customs
    ]); 
    exit;
}

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

    // ==========================================
    // 3. FUNGSI CHECKOUT (REGULER & PO)
    // ==========================================
    if ($action === 'checkout') {
        $data = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();

        $invoice_no = 'INV-' . date('YmdHis') . '-' . rand(100,999);
        $customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
        
        $is_po = $data['is_po'] ? 1 : 0;
        $channel = $data['channel'] ?? 'toko';
        $pickup_date = !empty($data['pickup_date']) ? $data['pickup_date'] : null;
        $pickup_time = !empty($data['pickup_time']) ? $data['pickup_time'] : null;

        $stmt = $pdo->prepare("INSERT INTO sales_pos (invoice_no, customer_id, order_type, subtotal, discount_voucher, voucher_code, discount_points, discount_manual, points_used, points_earned, total_amount, payment_method, payment_status, dp_amount, amount_paid, change_amount, is_po, channel, pickup_date, pickup_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $invoice_no, $customer_id, $data['order_type'], $data['subtotal'], 
            $data['discount_voucher'], $data['voucher_code'], $data['discount_points'], $data['discount_manual'], 
            $data['points_used'], $data['points_earned'], $data['total_amount'], 
            $data['payment_method'], $data['payment_status'], $data['dp_amount'], $data['amount_paid'], $data['change_amount'], 
            $is_po, $channel, $pickup_date, $pickup_time
        ]);
        $sale_id = $pdo->lastInsertId();

        $stmt_detail = $pdo->prepare("INSERT INTO sale_details_pos (sale_id, product_id, is_custom, custom_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_potong_stok = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_history = $pdo->prepare("INSERT INTO inventory_history_pos (product_id, type, qty, reference_no, source) VALUES (?, 'Keluar', ?, ?, 'Penjualan POS')");
        
        foreach ($data['items'] as $item) {
            $prod_id = $item['is_custom'] ? 0 : $item['id'];
            $is_custom = $item['is_custom'] ? 1 : 0;
            $custom_name = $item['is_custom'] ? $item['name'] : null;

            $stmt_detail->execute([$sale_id, $prod_id, $is_custom, $custom_name, $item['price'], $item['qty'], $item['subtotal']]);

            // Potong stok otomatis JIKA BUKAN ITEM CUSTOM dan BUKAN PO
            if (!$is_custom && !$is_po) {
                $stmt_potong_stok->execute([$item['qty'], $prod_id]);
                $stmt_history->execute([$prod_id, $item['qty'], $invoice_no]);
            }
        }

        if (!empty($data['voucher_code'])) {
            $pdo->prepare("UPDATE vouchers_pos SET used_count = used_count + 1 WHERE voucher_code = ?")->execute([$data['voucher_code']]);
        }
        if ($customer_id) {
            $pdo->prepare("UPDATE customers_pos SET points = points - ? + ? WHERE id = ?")->execute([$data['points_used'], $data['points_earned'], $customer_id]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'invoice' => $invoice_no, 'message' => 'Transaksi Berhasil!']);
        exit;
    }

    // ==========================================
    // 4. FUNGSI STATUS PESANAN DAPUR (FIFO)
    // ==========================================
    if ($action === 'get_active_orders') {
        $stmt = $pdo->query("
            SELECT s.id, s.invoice_no, s.created_at, s.production_status, s.channel, s.pickup_date, s.pickup_time, c.name as customer_name
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE s.is_po = 1 AND s.production_status != 'selesai_diambil'
            ORDER BY s.pickup_date ASC, s.pickup_time ASC
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

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
            
            // LOGIC ALERT FIFO (Merah untuk hari ini, Kuning untuk besok)
            $order['alert_type'] = '';
            if ($order['pickup_date'] === $today) {
                $order['alert_type'] = 'today';
            } else if ($order['pickup_date'] === $tomorrow) {
                $order['alert_type'] = 'tomorrow';
            }

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