<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Pastikan kolom penunjang pesanan online tersedia
try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN order_status VARCHAR(20) DEFAULT 'new' AFTER payment_status"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN driver_name VARCHAR(100) NULL AFTER notes"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN driver_phone VARCHAR(50) NULL AFTER driver_name"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sales_pos ADD COLUMN external_order_id VARCHAR(100) NULL AFTER invoice_no"); } catch (Exception $e) {}

$action = $_REQUEST['action'] ?? '';
$wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

// ===================================================
// 1. GET LIST ORDERS (FETCH REALTIME ONLINE ORDERS)
// ===================================================
if ($action === 'get_orders') {
    try {
        $channel_filter = $_REQUEST['channel'] ?? '';
        $status_filter  = $_REQUEST['status'] ?? '';
        
        $where = ["s.order_type = 'online'"];
        $params = [];

        if ($wh_id > 0) {
            $where[] = "(s.warehouse_id = ? OR s.warehouse_id IS NULL)";
            $params[] = $wh_id;
        }

        if (!empty($channel_filter) && $channel_filter !== 'all') {
            $where[] = "s.channel = ?";
            $params[] = $channel_filter;
        }

        if (!empty($status_filter) && $status_filter !== 'all') {
            $where[] = "COALESCE(s.order_status, 'new') = ?";
            $params[] = $status_filter;
        }

        $where_str = implode(" AND ", $where);

        $sql = "SELECT s.*, c.name as customer_name_db, c.phone as customer_phone
                FROM sales_pos s
                LEFT JOIN customers_pos c ON s.customer_id = c.id
                WHERE $where_str
                ORDER BY s.id DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch detail items for each order
        foreach ($orders as &$order) {
            $stmt_items = $pdo->prepare("
                SELECT sd.*, COALESCE(p.name, sd.custom_name) as item_name
                FROM sale_details_pos sd
                LEFT JOIN products p ON sd.product_id = p.id
                WHERE sd.sale_id = ?
            ");
            $stmt_items->execute([$order['id']]);
            $order['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            // Fallback display names
            if (empty($order['customer_name'])) {
                $order['customer_name'] = !empty($order['customer_name_db']) ? $order['customer_name_db'] : 'Pelanggan Online';
            }
            if (empty($order['order_status'])) {
                $order['order_status'] = 'new';
            }
        }

        // Return API env info
        $grab_env = [
            'merchant_id' => env('GRAB_MERCHANT_ID', 'GF-MERCHANT-88219'),
            'env'         => env('GRAB_ENV', 'sandbox'),
            'api_url'     => env('GRAB_API_BASE_URL', 'https://partner-api.stg-grab.com'),
            'is_active'   => !empty(env('GRAB_CLIENT_ID'))
        ];

        echo json_encode([
            'status' => 'success',
            'orders' => $orders,
            'grab_config' => $grab_env
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ===================================================
// 2. UPDATE ORDER STATUS (TERIMA, DAPUR, READY, CANCEL)
// ===================================================
if ($action === 'update_status') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $order_id   = intval($data['order_id'] ?? 0);
        $new_status = trim($data['status'] ?? '');

        if ($order_id <= 0 || empty($new_status)) {
            throw new Exception("Parameter order_id atau status tidak valid.");
        }

        $stmt = $pdo->prepare("UPDATE sales_pos SET order_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        echo json_encode([
            'status' => 'success',
            'message' => "Status pesanan berhasil diperbarui menjadi '" . strtoupper($new_status) . "'"
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ===================================================
// 3. SIMULATE INCOMING GRAB/GOFOOD ORDER (FOR TESTING)
// ===================================================
if ($action === 'simulate_incoming') {
    try {
        $channel = $_REQUEST['channel'] ?? 'grab';
        $pdo->beginTransaction();

        $prefix = strtoupper($channel) === 'GRAB' ? 'GF' : (strtoupper($channel) === 'GOJEK' ? 'GFD' : 'WA');
        $ext_id = $prefix . '-' . rand(10000, 99999);
        $invoice_no = 'ONL-' . date('YmdHis') . '-' . rand(100, 999);

        // Fetch random products
        $stmt_p = $pdo->query("SELECT id, name, offline_price, online_price FROM products ORDER BY RAND() LIMIT 2");
        $products = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
            $products = [
                ['id' => 0, 'name' => 'Bolu Tar Keju Special', 'offline_price' => 45000, 'online_price' => 50000],
                ['id' => 0, 'name' => 'Brownies Coklat Lumer', 'offline_price' => 35000, 'online_price' => 40000]
            ];
        }

        $subtotal = 0;
        $items = [];
        foreach ($products as $p) {
            $qty = rand(1, 2);
            $price = floatval(!empty($p['online_price']) ? $p['online_price'] : $p['offline_price']);
            $item_subtotal = $price * $qty;
            $subtotal += $item_subtotal;
            $items[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $item_subtotal,
                'is_custom' => ($p['id'] == 0 ? 1 : 0)
            ];
        }

        $shipping_cost = 0; // Grab handles shipping directly
        $total_amount = $subtotal;

        $customer_names = ['Siti Rahmawati', 'Budi Santoso', 'Anisa Putri', 'Rizky Pratama', 'Dewi Lestari'];
        $driver_names   = ['Pak Joko (GrabDriver)', 'Mas Hendra (Gojek)', 'Bang Asep (Express)', 'Pak Supri (Grab)'];

        $cust_name   = $customer_names[array_rand($customer_names)];
        $driver_name = $driver_names[array_rand($driver_names)];
        $driver_phone = '0812' . rand(10000000, 99999999);
        $notes = "Tanpa lilin, tolong potong 8 bagian rapi. Makasih!";

        $stmt = $pdo->prepare("
            INSERT INTO sales_pos 
            (invoice_no, external_order_id, warehouse_id, order_type, channel, notes, driver_name, driver_phone, subtotal, shipping_cost, total_amount, payment_method, payment_status, order_status, amount_paid, change_amount) 
            VALUES (?, ?, ?, 'online', ?, ?, ?, ?, ?, ?, ?, 'app', 'lunas', 'new', ?, 0)
        ");
        $stmt->execute([
            $invoice_no, $ext_id, ($wh_id > 0 ? $wh_id : 1), $channel, $notes, $driver_name, $driver_phone,
            $subtotal, $shipping_cost, $total_amount, $total_amount
        ]);
        $sale_id = $pdo->lastInsertId();

        $stmt_detail = $pdo->prepare("INSERT INTO sale_details_pos (sale_id, product_id, is_custom, custom_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt_detail->execute([$sale_id, $item['id'], $item['is_custom'], $item['name'], $item['price'], $item['qty'], $item['subtotal']]);
        }

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => "Simulasi pesanan masuk $channel ($ext_id) berhasil dimasukkan!",
            'invoice' => $invoice_no
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ===================================================
// 4. CHECKOUT (MANUAL ONLINE CHECKOUT FROM POS)
// ===================================================
if ($action === 'checkout') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();

        $invoice_no = 'ONL-' . date('YmdHis') . '-' . rand(100,999);
        $customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO sales_pos 
            (invoice_no, warehouse_id, customer_id, order_type, channel, subtotal, shipping_cost, notes, total_amount, payment_method, payment_status, order_status, amount_paid, change_amount) 
            VALUES (?, ?, ?, 'online', ?, ?, ?, ?, ?, ?, 'lunas', 'new', ?, ?)
        ");
        $stmt->execute([
            $invoice_no, ($wh_id > 0 ? $wh_id : 1), $customer_id, $data['channel'], $data['subtotal'], $data['shipping_cost'], $data['notes'],
            $data['total_amount'], $data['payment_method'], $data['amount_paid'], $data['change_amount']
        ]);
        $sale_id = $pdo->lastInsertId();

        $stmt_detail = $pdo->prepare("INSERT INTO sale_details_pos (sale_id, product_id, is_custom, custom_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($data['items'] as $item) {
            $prod_id = $item['is_custom'] ? 0 : $item['id'];
            $is_custom = $item['is_custom'] ? 1 : 0;
            $custom_name = $item['name'];
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