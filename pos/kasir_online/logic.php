<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../config/database.php';

header('Content-Type: application/json');

// Ensure auxiliary columns exist in sales_pos
try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN IF NOT EXISTS order_status VARCHAR(20) DEFAULT 'completed' AFTER payment_status"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN IF NOT EXISTS driver_name VARCHAR(100) NULL AFTER notes"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN IF NOT EXISTS driver_phone VARCHAR(50) NULL AFTER driver_name"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN IF NOT EXISTS external_order_id VARCHAR(100) NULL AFTER invoice_no"); } catch (Exception $e) {}

$action = $_REQUEST['action'] ?? '';
$selected_store_id = !empty($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : (!empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 1);
$_SESSION['pos_warehouse_id'] = $selected_store_id;

$user_id = $_SESSION['user_id'] ?? 1;

// ===================================================
// 1. GET MASTER DATA (PRODUK, STOK STORE, CHANNEL, PRICES)
// ===================================================
if ($action === 'get_master_data') {
    try {
        $warehouses = [];
        try {
            $warehouses = $pdo->query("SELECT id, name FROM warehouses ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        if (empty($warehouses)) {
            $warehouses = [
                ['id' => 1, 'name' => 'Store 01'],
                ['id' => 2, 'name' => 'Store 02']
            ];
        }
        foreach ($warehouses as &$w) {
            $w['name'] = str_ireplace('gudang', 'Store', $w['name']);
        }
        unset($w);

        $prod_sql = "
            SELECT p.*, 
                   COALESCE(pws.stock, p.stock) AS stock,
                   COALESCE(w.name, CASE WHEN p.warehouse_id = 2 THEN 'Store 02' ELSE 'Store 01' END) AS store_name
            FROM products p
            LEFT JOIN product_warehouse_stocks pws ON p.id = pws.product_id AND pws.warehouse_id = $selected_store_id
            LEFT JOIN warehouses w ON w.id = $selected_store_id
            WHERE 1=1 AND (p.warehouse_id = $selected_store_id OR p.warehouse_id IS NULL OR $selected_store_id = 1)
            ORDER BY p.name ASC
        ";
        $products = $pdo->query($prod_sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as &$prod) {
            $prod['store_name'] = str_ireplace('gudang', 'Store', $prod['store_name']);
            $prod['item_type'] = 'product';
            $prod['is_custom'] = 0;
        }
        unset($prod);

        $saved_customs = [];
        try { $saved_customs = $pdo->query("SELECT *, COALESCE(is_custom_price, 0) as is_custom_price FROM saved_custom_items_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {}

        $saved_customs_reguler = [];
        try { $saved_customs_reguler = $pdo->query("SELECT *, COALESCE(is_custom_price, 0) as is_custom_price FROM saved_custom_reguler_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {}

        // Merge catalog products, custom reguler, and custom PO for complete catalog view
        $all_items = $products;

        foreach ($saved_customs_reguler as $cr) {
            $all_items[] = [
                'id' => $cr['id'],
                'code' => 'CR-' . $cr['id'],
                'name' => $cr['name'],
                'category' => 'Custom Reguler',
                'price' => $cr['price'],
                'stock' => 999,
                'item_type' => 'custom_reguler',
                'is_custom' => 1,
                'image' => null,
                'store_name' => 'Store 01'
            ];
        }

        foreach ($saved_customs as $cp) {
            $all_items[] = [
                'id' => $cp['id'],
                'code' => 'CPO-' . $cp['id'],
                'name' => $cp['name'],
                'category' => 'Custom PO',
                'price' => $cp['price'],
                'stock' => 999,
                'item_type' => 'custom_po',
                'is_custom' => 1,
                'is_po' => 1,
                'image' => null,
                'store_name' => 'Store 01'
            ];
        }

        $payment_methods = [];
        try { $payment_methods = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY type ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {}

        $food_delivery_payment_methods = [];
        try { 
            $food_delivery_payment_methods = $pdo->query("SELECT * FROM food_delivery_payment_methods_pos WHERE is_active = 1 ORDER BY platform_code ASC, sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC); 
        } catch (Exception $e) {}

        $customers = [];
        try { $customers = $pdo->query("SELECT id, name, phone, points FROM customers_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {}

        $platforms = [];
        try { $platforms = $pdo->query("SELECT * FROM food_delivery_platforms_pos WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {}

        $food_delivery_prices = [];
        try { 
            $food_delivery_prices = $pdo->query("
                SELECT * FROM food_delivery_prices_pos 
                WHERE is_active = 1 
                  AND (warehouse_id = $selected_store_id OR warehouse_id IS NULL OR warehouse_id = 0)
            ")->fetchAll(PDO::FETCH_ASSOC); 
        } catch (Exception $e) {}

        $settings = [];
        try {
            $settings_rows = $pdo->query("SELECT setting_key, setting_value FROM pos_settings")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($settings_rows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }
        } catch (Exception $e) {}

        $categories = [];
        try {
            $categories = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {}

        echo json_encode([
            'status' => 'success',
            'store_id' => $selected_store_id,
            'warehouses' => $warehouses,
            'products' => $all_items,
            'categories' => $categories,
            'customers' => $customers,
            'saved_customs' => $saved_customs,
            'saved_customs_reguler' => $saved_customs_reguler,
            'payment_methods' => $payment_methods,
            'food_delivery_payment_methods' => $food_delivery_payment_methods,
            'platforms' => $platforms,
            'food_delivery_prices' => $food_delivery_prices,
            'settings' => $settings
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ===================================================
// 2. CHECKOUT TRANSAKSI KASIR ONLINE
// ===================================================
if ($action === 'checkout') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['items'])) {
        echo json_encode(['status' => 'error', 'message' => 'Keranjang kosong!']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $channel = !empty($data['channel']) ? strtolower($data['channel']) : 'grabfood';
        $invoice_prefix = 'ONL-' . strtoupper(substr($channel, 0, 3));
        $invoice_no = $invoice_prefix . '-' . date('YmdHis') . '-' . rand(100, 999);
        
        $customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : null;
        $driver_name = !empty($data['driver_name']) ? trim($data['driver_name']) : null;
        $driver_phone = !empty($data['driver_phone']) ? trim($data['driver_phone']) : null;
        $external_order_id = !empty($data['external_order_id']) ? trim($data['external_order_id']) : null;
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;
        $payment_method = !empty($data['payment_method']) ? $data['payment_method'] : 'Cash';
        $payment_status = (!empty($data['payment_status']) && $data['payment_status'] === 'dp') ? 'dp' : 'lunas';
        
        $subtotal = floatval($data['subtotal'] ?? 0);
        $total_amount = floatval($data['total_amount'] ?? $subtotal);
        $amount_paid = floatval($data['amount_paid'] ?? $total_amount);
        $change_amount = floatval($data['change_amount'] ?? 0);
        $warehouse_id = $selected_store_id > 0 ? $selected_store_id : 1;

        // Insert into sales_pos
        $stmt = $pdo->prepare("
            INSERT INTO sales_pos (
                invoice_no, external_order_id, customer_id, order_type, channel, subtotal, 
                discount_voucher, discount_manual, total_amount, payment_method, payment_status, order_status, 
                amount_paid, change_amount, is_po, notes, driver_name, driver_phone, warehouse_id
            ) VALUES (?, ?, ?, 'online', ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invoice_no, $external_order_id, $customer_id, $channel, $subtotal,
            floatval($data['discount_voucher'] ?? 0), floatval($data['discount_manual'] ?? 0),
            $total_amount, $payment_method, $payment_status, $amount_paid, $change_amount,
            !empty($data['is_po']) ? 1 : 0, $notes, $driver_name, $driver_phone, $warehouse_id
        ]);
        $sale_id = $pdo->lastInsertId();

        // Insert into sale_payments_pos
        $stmtPay = $pdo->prepare("INSERT INTO sale_payments_pos (sale_id, amount, payment_method, payment_type) VALUES (?, ?, ?, 'full')");
        $stmtPay->execute([$sale_id, $total_amount, $payment_method]);

        // Insert line items into sale_details_pos & deduct stock
        $stmt_detail = $pdo->prepare("
            INSERT INTO sale_details_pos (
                sale_id, product_id, is_custom, custom_name, price, qty, subtotal, discount_type, discount_value, created_by_user
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_potong_stok = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_potong_wh = $pdo->prepare("INSERT INTO product_warehouse_stocks (product_id, warehouse_id, stock) VALUES (?, ?, -?) ON DUPLICATE KEY UPDATE stock = stock - ?");

        foreach ($data['items'] as $item) {
            $is_custom = !empty($item['is_custom']) ? 1 : 0;
            $prod_id = $is_custom ? 0 : intval($item['id']);
            $custom_name = $item['name'];
            $price = floatval($item['price']);
            $qty = intval($item['qty']);
            $item_subtotal = floatval($item['subtotal'] ?? ($price * $qty));

            $stmt_detail->execute([
                $sale_id, $prod_id, $is_custom, $custom_name, $price, $qty, $item_subtotal,
                $item['discount_type'] ?? 'none', floatval($item['discount_value'] ?? 0), $is_custom ? $user_id : null
            ]);

            if (!$is_custom && $prod_id > 0) {
                $stmt_potong_stok->execute([$qty, $prod_id]);
                $stmt_potong_wh->execute([$prod_id, $warehouse_id, $qty, $qty]);
            }
        }

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Pesanan Online berhasil diproses!',
            'sale_id' => $sale_id,
            'invoice_no' => $invoice_no
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);