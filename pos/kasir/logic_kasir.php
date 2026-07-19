<?php
ini_set('display_errors', 0); 
session_start(); 
require_once '../../config/database.php'; 
require_once '../../config/auth.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); 
$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['pos_user_id'] ?? 1;

// --- FUNGSI SHIFT KASIR ---
if ($action === 'check_shift') {
    $stmt = $pdo->prepare("SELECT id FROM shifts_history_pos WHERE user_id = ? AND status = 'open' LIMIT 1");
    $stmt->execute([$user_id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'status' => 'success', 
        'has_open_shift' => !!$shift, 
        'shift_id_active' => $shift ? $shift['id'] : null
    ]);
    exit;
}

if ($action === 'open_shift') {
    // Ambil default_start_cash dari pos_settings
    $stmt_set = $pdo->prepare("SELECT setting_value FROM pos_settings WHERE setting_key = 'default_start_cash'");
    $stmt_set->execute();
    $setting = $stmt_set->fetch(PDO::FETCH_ASSOC);
    $start_cash = $setting ? (float)$setting['setting_value'] : 0;
    $warehouse_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 1;

    // Gunakan shift_id 0 karena sudah tidak relasi ke master shift
    $stmt = $pdo->prepare("INSERT INTO shifts_history_pos (user_id, shift_id, start_time, start_cash, status, warehouse_id) VALUES (?, 0, NOW(), ?, 'open', ?)");
    $stmt->execute([$user_id, $start_cash, $warehouse_id]);
    echo json_encode(['status' => 'success', 'message' => 'Shift berhasil dibuka!']);
    exit;
}

if ($action === 'close_shift') {
    $end_cash = $_POST['end_cash'] ?? 0;
    // Get current open shift id
    $stmtGet = $pdo->prepare("SELECT id FROM shifts_history_pos WHERE user_id = ? AND status = 'open' LIMIT 1");
    $stmtGet->execute([$user_id]);
    $open_shift = $stmtGet->fetch(PDO::FETCH_ASSOC);
    $shift_id = $open_shift ? $open_shift['id'] : 0;

    $stmt = $pdo->prepare("UPDATE shifts_history_pos SET status = 'closed', end_time = NOW(), end_cash = ? WHERE id = ?");
    $stmt->execute([$end_cash, $shift_id]);
    echo json_encode(['status' => 'success', 'message' => 'Kasir Berhasil Ditutup!', 'shift_id' => $shift_id]);
    exit;
}

// --- FUNGSI MASTER DATA ---
if ($action === 'get_master_data') {
    $wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;
    $prod_sql = "
        SELECT p.*, 
               " . ($wh_id > 0 ? "COALESCE(pws.stock, p.stock)" : "p.stock") . " AS stock,
               COALESCE(w.name, CASE WHEN p.warehouse_id = 2 THEN 'Store 02' ELSE 'Store 01' END) AS store_name
        FROM products p
        " . ($wh_id > 0 ? "LEFT JOIN product_warehouse_stocks pws ON p.id = pws.product_id AND pws.warehouse_id = $wh_id" : "") . "
        LEFT JOIN warehouses w ON " . ($wh_id > 0 ? "$wh_id = w.id" : "p.warehouse_id = w.id") . "
        WHERE 1=1 " . ($wh_id > 0 ? "AND (p.warehouse_id = $wh_id OR p.warehouse_id IS NULL OR $wh_id = 1)" : "") . "
        ORDER BY p.name ASC
    ";
    $products = $pdo->query($prod_sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as &$prod) {
        $prod['store_name'] = str_ireplace('gudang', 'Store', $prod['store_name']);
    }
    unset($prod);
    $customers = $pdo->query("SELECT id, name, points, phone FROM customers_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $saved_customs = $pdo->query("SELECT * FROM saved_custom_items_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $saved_customs_reguler = $pdo->query("SELECT * FROM saved_custom_reguler_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $payment_methods = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY type ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    // Gunakan try-catch untuk mencegah JSON rusak jika tabel tidak ada
    try {
        $loyalty = $pdo->query("SELECT * FROM loyalty_settings_pos LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $loyalty = ['is_active' => 0, 'earn_point_ratio' => 0, 'points_required' => 0, 'discount_amount' => 0, 'discount_type' => 'IDR'];
    }

    $promos_buy_get = [];
    $promos_auto_disc = [];
    try {
        $promos_buy_get = $pdo->query("SELECT * FROM promo_buy_x_get_y WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date")->fetchAll(PDO::FETCH_ASSOC);
        $promos_auto_disc = $pdo->query("SELECT * FROM promo_auto_discounts WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date ORDER BY min_purchase DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    $stmt_set = $pdo->prepare("SELECT setting_key, setting_value FROM pos_settings");
    $stmt_set->execute();
    $settings_rows = $stmt_set->fetchAll(PDO::FETCH_ASSOC);
    
    $settings = [];
    foreach ($settings_rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $default_start_cash = isset($settings['default_start_cash']) ? (float)$settings['default_start_cash'] : 0;
    
    // Pastikan pin_supervisor ada di db, jika tidak buat default
    if (!isset($settings['pin_supervisor'])) {
        try {
            $pdo->query("INSERT IGNORE INTO pos_settings (setting_key, setting_value) VALUES ('pin_supervisor', '123456')");
            $settings['pin_supervisor'] = '123456';
        } catch (Exception $e) {}
    }

    // Ambil daftar PIN OTP supervisor yang aktif (belum dipakai)
    $valid_pins = [];
    try {
        $stmt_pins = $pdo->query("SELECT pin FROM supervisor_pins_pos WHERE is_used = 0 ORDER BY created_at ASC");
        $valid_pins = $stmt_pins->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}

    echo json_encode([
        'status' => 'success',
        'products' => $products,
        'customers' => $customers,
        'saved_customs' => $saved_customs,
        'saved_customs_reguler' => $saved_customs_reguler,
        'payment_methods' => $payment_methods,
        'loyalty' => $loyalty,
        'promos_buy_get' => $promos_buy_get,
        'promos_auto_disc' => $promos_auto_disc,
        'default_start_cash' => $default_start_cash,
        'settings' => $settings,
        'valid_supervisor_pins' => $valid_pins
    ]);
    exit;
}

// --- GUNAKAN (HANGUSKAN) PIN SUPERVISOR ---
if ($action === 'use_supervisor_pin') {
    $pin = trim($_POST['pin'] ?? '');
    if (empty($pin)) {
        echo json_encode(['status' => 'error', 'message' => 'PIN tidak boleh kosong']); exit;
    }
    try {
        // Cek apakah PIN valid dan belum dipakai
        $stmt = $pdo->prepare("SELECT id FROM supervisor_pins_pos WHERE pin = ? AND is_used = 0");
        $stmt->execute([$pin]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'PIN tidak valid atau sudah dipakai!']); exit;
        }
        // Tandai PIN sebagai sudah dipakai
        $stmt_use = $pdo->prepare("UPDATE supervisor_pins_pos SET is_used = 1, used_at = NOW() WHERE id = ?");
        $stmt_use->execute([$row['id']]);
        echo json_encode(['status' => 'success', 'message' => 'PIN valid dan berhasil dipakai!']); exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]); exit;
    }
}

// --- FUNGSI PELANGGAN BARU ---
if ($action === 'add_customer') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama pelanggan wajib diisi!']); exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO customers_pos (name, phone, address) VALUES (?, ?, ?)");
        $stmt->execute([$name, $phone, $address]);
        $new_id = $pdo->lastInsertId();

        $customers = $pdo->query("SELECT id, name, points, phone FROM customers_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'message' => 'Pelanggan baru ditambahkan!', 'new_id' => $new_id, 'customers' => $customers]); exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan pelanggan.']); exit;
    }
}

// --- FUNGSI ITEM CUSTOM REGULER (KHUSUS REGULER) ---
if ($action === 'save_custom_reguler_item') {
    $name = trim($_POST['name'] ?? '');
    $price = str_replace(['Rp', '.', ' '], '', $_POST['price'] ?? 0);
    $template_id = $_POST['template_id'] ?? '';

    // Hanya validasi nama/harga jika BUKAN dari template (template_id kosong)
    if (empty($template_id)) {
        if(empty($name) || empty($price)) { echo json_encode(['status' => 'error', 'message' => 'Nama dan Harga Item Custom Reguler wajib diisi!']); exit; }
        
        $stmt = $pdo->prepare("INSERT INTO saved_custom_reguler_pos (name, price, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $user_id]);
        $custom_id = $pdo->lastInsertId();
    } else {
        $custom_id = $template_id; // Pakai ID template yang dipilih
    }
    
    echo json_encode(['status' => 'success', 'custom_id' => $custom_id]);
    exit;
}

// --- FUNGSI ITEM CUSTOM DAPUR (DAPUR SAJA) ---
if ($action === 'save_custom_item') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    if (empty($name)) { echo json_encode(['status' => 'error', 'message' => 'Nama wajib diisi']); exit; }
    
    try {
        // Simpan created_by agar bisa dilacak siapa kasir yang membuat item custom ini
        $stmt = $pdo->prepare("INSERT INTO saved_custom_items_pos (name, price, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $user_id]);
        echo json_encode(['status' => 'success', 'new_id' => $pdo->lastInsertId()]); exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error']); exit;
    }
}

// --- VALIDASI RESEP ITEM CUSTOM ---
if ($action === 'check_custom_recipe') {
    $custom_item_id = (int)($_POST['custom_item_id'] ?? 0);
    if (!$custom_item_id) {
        echo json_encode(['status' => 'error', 'message' => 'ID item custom tidak valid.']); exit;
    }
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM bom_custom WHERE custom_item_id = ?");
    $stmt->execute([$custom_item_id]);
    $count = (int)$stmt->fetchColumn();
    echo json_encode([
        'status'     => 'success',
        'has_recipe' => $count > 0,
        'bahan_count'=> $count
    ]);
    exit;
}

// --- FUNGSI VOUCHER ---
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

    echo json_encode(['status' => 'success', 'data' => $voucher]); exit;
}

// --- FUNGSI KAS KELUAR (PETTY CASH) ---
// --- FUNGSI KAS KELUAR (PETTY CASH) ---
if ($action === 'save_kas_keluar') {
    try {
        $amount = $_POST['amount'] ?? 0;
        $description = $_POST['description'] ?? '';

        // Cari shift aktif kasir ini
        $stmt = $pdo->prepare("SELECT id FROM shifts_history_pos WHERE user_id = ? AND status = 'open' LIMIT 1");
        $stmt->execute([$user_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$shift) { 
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada shift aktif! Buka shift terlebih dahulu.']); 
            exit; 
        }

        $stmt = $pdo->prepare("INSERT INTO petty_cash_pos (user_id, shift_history_id, jenis, nominal, keterangan) VALUES (?, ?, 'keluar', ?, ?)");
        $stmt->execute([$user_id, $shift['id'], $amount, $description]);

        echo json_encode(['status' => 'success', 'message' => 'Kas keluar operasional berhasil dicatat!']); 
        exit;
        
    } catch (PDOException $e) {
        // TANGKAP ERROR JIKA TABEL BELUM ADA ATAU SALAH KOLOM
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        // TANGKAP ERROR SISTEM LAINNYA
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
        exit;
    }
}

// --- FUNGSI CHECKOUT ---
if ($action === 'checkout') {
    // AUTO-MIGRATE KOLOM PAYMENT_REFERENCE & DISCOUNT_AUTO
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM sales_pos LIKE 'payment_reference'");
        if ($checkCol->rowCount() === 0) {
            $pdo->exec("ALTER TABLE sales_pos ADD COLUMN payment_reference VARCHAR(100) NULL DEFAULT NULL AFTER payment_fee_amount");
        }
        $checkCol2 = $pdo->query("SHOW COLUMNS FROM sales_pos LIKE 'discount_auto'");
        if ($checkCol2->rowCount() === 0) {
            $pdo->exec("ALTER TABLE sales_pos ADD COLUMN discount_auto DECIMAL(15,2) DEFAULT 0.00 AFTER discount_manual");
        }
    } catch (Exception $e) {}

    $data = json_decode(file_get_contents('php://input'), true);
    $pdo->beginTransaction();
    $invoice_no = 'INV-' . date('YmdHis') . '-' . rand(100,999);
    $customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
    
    $is_po = $data['is_po'] ? 1 : 0;
    $channel = $data['channel'] ?? 'toko';
    $pickup_date = !empty($data['pickup_date']) ? $data['pickup_date'] : null;
    $pickup_time = !empty($data['pickup_time']) ? $data['pickup_time'] : null;
    $notes = !empty($data['notes']) ? $data['notes'] : null;
    
    $payment_fee_name = !empty($data['payment_fee_name']) ? $data['payment_fee_name'] : null;
    $payment_fee_amount = !empty($data['payment_fee_amount']) ? $data['payment_fee_amount'] : 0.00;
    $payment_reference = !empty($data['payment_reference']) ? $data['payment_reference'] : null;
    $discount_auto = !empty($data['discount_auto']) ? $data['discount_auto'] : 0.00;
    $warehouse_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 1;

    $stmt = $pdo->prepare("INSERT INTO sales_pos (invoice_no, customer_id, order_type, subtotal, discount_voucher, voucher_code, discount_points, discount_manual, discount_auto, points_used, points_earned, total_amount, payment_method, payment_fee_name, payment_fee_amount, payment_reference, payment_status, dp_amount, amount_paid, change_amount, is_po, channel, pickup_date, pickup_time, notes, warehouse_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $invoice_no, $customer_id, 'offline', $data['subtotal'], $data['discount_voucher'], $data['voucher_code'], 
        $data['discount_points'], $data['discount_manual'], $discount_auto, $data['points_used'], $data['points_earned'], 
        $data['total_amount'], $data['payment_method'], $payment_fee_name, $payment_fee_amount, $payment_reference, $data['payment_status'], $data['dp_amount'], $data['amount_paid'], $data['change_amount'], $is_po, $channel, $pickup_date, $pickup_time, $notes, $warehouse_id
    ]);
    $sale_id = $pdo->lastInsertId();

    // 1.5 SIMPAN KE SALE_PAYMENTS_POS
    $payment_type = $data['payment_status'] === 'dp' ? 'dp' : 'full';
    $amount_to_record = $data['payment_status'] === 'dp' ? $data['dp_amount'] : ($data['amount_paid'] - $data['change_amount']);
    $stmtPay = $pdo->prepare("INSERT INTO sale_payments_pos (sale_id, amount, payment_method, payment_type) VALUES (?, ?, ?, ?)");
    $stmtPay->execute([$sale_id, $amount_to_record, $data['payment_method'], $payment_type]);

    $stmt_detail = $pdo->prepare("INSERT INTO sale_details_pos (sale_id, product_id, is_custom, custom_name, price, qty, subtotal, discount_type, discount_value, created_by_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_potong_stok = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    
    foreach ($data['items'] as $item) {
        $prod_id   = $item['is_custom'] ? 0 : $item['id'];
        $is_custom = $item['is_custom'] ? 1 : 0;
        // SELALU SIMPAN NAMA PRODUK UNTUK HISTORI JAGA-JAGA JIKA MASTER PRODUK DIHAPUS
        $custom_name = $item['name']; 
        // Simpan created_by_user hanya untuk item custom (produk reguler NULL)
        $item_created_by = $is_custom ? $user_id : null;
        $disc_type = !empty($item['discount_type']) ? $item['discount_type'] : 'none';
        $disc_val  = !empty($item['discount_value']) ? (float)$item['discount_value'] : 0;
        $stmt_detail->execute([$sale_id, $prod_id, $is_custom, $custom_name, $item['price'], $item['qty'], $item['subtotal'], $disc_type, $disc_val, $item_created_by]);

        // Potong stok produk katalog (baik Reguler maupun PO)
        if (!$is_custom) { 
            $wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 1;
            $stmt_potong_wh = $pdo->prepare("INSERT INTO product_warehouse_stocks (product_id, warehouse_id, stock) VALUES (?, ?, -?) ON DUPLICATE KEY UPDATE stock = stock - ?");
            $stmt_potong_wh->execute([$prod_id, $wh_id, $item['qty'], $item['qty']]);

            $stmt_potong_stok->execute([$item['qty'], $prod_id]); 
        }

        // ============================================================
        // PENGURANGAN BAHAN BAKU OTOMATIS UNTUK ITEM CUSTOM
        // Hanya berlaku jika dipesan via Pesanan Dapur (is_po = 1)
        // ============================================================
        if ($is_po && $is_custom && !empty($item['template_id'])) {
            $custom_item_id = (int)$item['template_id'];

            // Ambil semua bahan dari resep item custom (bom_custom)
            $bom_stmt = $pdo->prepare("
                SELECT material_id, quantity_needed, unit_used 
                FROM bom_custom 
                WHERE custom_item_id = ?
            ");
            $bom_stmt->execute([$custom_item_id]);
            $bom_list = $bom_stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($bom_list)) {
                $stmt_potong_bahan = $pdo->prepare("
                    UPDATE materials_stocks 
                    SET stock = stock - ? 
                    WHERE id = ?
                ");
                foreach ($bom_list as $bom) {
                    // Total bahan = kebutuhan per pcs × qty di keranjang
                    $qty_terjual    = (int)$item['qty'];
                    $bahan_per_pcs  = floatval($bom['quantity_needed']);
                    $total_deducted = $bahan_per_pcs * $qty_terjual;

                    $stmt_potong_bahan->execute([$total_deducted, (int)$bom['material_id']]);
                }
            }
            // Jika item custom tidak punya resep, bahan baku tidak dipotong (tidak diblokir)
        }
    }
    
    // Update Poin & Kuota Voucher
    if (!empty($data['voucher_code'])) { $pdo->prepare("UPDATE vouchers_pos SET used_count = used_count + 1 WHERE voucher_code = ?")->execute([$data['voucher_code']]); }
    if ($customer_id) { $pdo->prepare("UPDATE customers_pos SET points = points - ? + ? WHERE id = ?")->execute([$data['points_used'], $data['points_earned'], $customer_id]); }

    $pdo->commit(); echo json_encode(['status' => 'success', 'invoice' => $invoice_no]); exit;
}

// --- FUNGSI STATUS PO ---
if ($action === 'get_active_orders') {
    $mode = $_GET['mode'] ?? 'nunggak';
    $date_filter = $_GET['date'] ?? date('Y-m-d');
    
    if ($mode === 'nunggak') {
        $stmt = $pdo->prepare("SELECT s.id, s.invoice_no, s.created_at, s.production_status, s.channel, s.pickup_date, s.pickup_time, c.name as customer_name FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.is_po = 1 AND (s.production_status IS NULL OR s.production_status IN ('pending', 'diproses')) ORDER BY s.pickup_date ASC, s.pickup_time ASC");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT s.id, s.invoice_no, s.created_at, s.production_status, s.channel, s.pickup_date, s.pickup_time, c.name as customer_name FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.is_po = 1 AND s.pickup_date = ? ORDER BY s.pickup_date ASC, s.pickup_time ASC");
        $stmt->execute([$date_filter]);
    }
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    foreach ($orders as $order) {
        $stmtDetail = $pdo->prepare("SELECT p.name as product_name, sd.custom_name, sd.is_custom, sd.qty FROM sale_details_pos sd LEFT JOIN products p ON sd.product_id = p.id WHERE sd.sale_id = ?");
        $stmtDetail->execute([$order['id']]);
        $items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
        
        $itemNames = []; 
        foreach($items as $it) { 
            $name = $it['is_custom'] ? $it['custom_name'] : $it['product_name'];
            $itemNames[] = $it['qty'] . 'x ' . $name; 
        }
        $order['items_list'] = implode(', ', $itemNames); 
        $order['time'] = date('H:i', strtotime($order['created_at']));
        if (empty($order['production_status'])) $order['production_status'] = 'pending';
        
        // Tentukan alert_type
        if ($order['pickup_date'] === $today) {
            $order['alert_type'] = 'today';
        } elseif ($order['pickup_date'] === $tomorrow) {
            $order['alert_type'] = 'tomorrow';
        } else {
            $order['alert_type'] = '';
        }
        
        $data[] = $order;
    }
    echo json_encode(['status' => 'success', 'data' => $data]); exit;
}

if ($action === 'update_production_status') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $status = $input['status'] ?? 'pending';
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE sales_pos SET production_status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID pesanan tidak valid']);
    }
    exit;
}


// --- LAPORAN ITEM CUSTOM (RIWAYAT PEMBUATAN / KIRIM LANGSUNG) ---
if ($action === 'get_custom_report') {
    $date_from = $_GET['date_from'] ?? date('Y-m-01'); // Default: awal bulan ini
    $date_to   = $_GET['date_to']   ?? date('Y-m-d');  // Default: hari ini

    try {
        $stmt = $pdo->prepare("
            SELECT 
                IF(s.is_po = 1, 'Dapur (PO)', 'Reguler') AS tipe_pesanan,
                s.is_po,
                REPLACE(sd.custom_name, ' (c)', '') AS nama_item,
                sd.price,
                s.created_at AS waktu_transaksi,
                u.name AS nama_kasir
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN users_pos u ON sd.created_by_user = u.id
            WHERE sd.is_custom = 1 
              AND DATE(s.created_at) BETWEEN ? AND ?
            ORDER BY waktu_transaksi DESC
        ");
        
        if (!$stmt) {
            throw new Exception("Gagal menyiapkan query SQL. Cek struktur tabel.");
        }

        $stmt->execute([$date_from, $date_to]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Rekap per nama kasir
        $rekap_kasir = [];
        foreach ($rows as $row) {
            $kasir = $row['nama_kasir'] ?? 'Tidak Diketahui';
            if (!isset($rekap_kasir[$kasir])) {
                $rekap_kasir[$kasir] = ['nama_kasir' => $kasir, 'total_item' => 0, 'total_nilai' => 0];
            }
            $rekap_kasir[$kasir]['total_item'] += 1;
            $rekap_kasir[$kasir]['total_nilai'] += (float)$row['price'];
        }

        $total_semua = 0;
        foreach ($rows as $row) {
            $total_semua += (float)$row['price'];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'rekap_kasir' => array_values($rekap_kasir),
            'total_semua' => $total_semua
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'DB Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

?>