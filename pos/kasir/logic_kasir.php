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

    // Gunakan shift_id 0 karena sudah tidak relasi ke master shift
    $stmt = $pdo->prepare("INSERT INTO shifts_history_pos (user_id, shift_id, start_time, start_cash, status) VALUES (?, 0, NOW(), ?, 'open')");
    $stmt->execute([$user_id, $start_cash]);
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
    $products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $customers = $pdo->query("SELECT id, name, points, phone FROM customers_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $saved_customs = $pdo->query("SELECT * FROM saved_custom_items_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $saved_customs_reguler = $pdo->query("SELECT * FROM saved_custom_reguler_pos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $payment_methods = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY type ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $loyalty = $pdo->query("SELECT * FROM loyalty_rules_pos LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $stmt_set = $pdo->prepare("SELECT setting_value FROM pos_settings WHERE setting_key = 'default_start_cash'");
    $stmt_set->execute();
    $setting = $stmt_set->fetch(PDO::FETCH_ASSOC);
    $default_start_cash = $setting ? (float)$setting['setting_value'] : 0;
    
    echo json_encode([
        'status' => 'success',
        'products' => $products,
        'customers' => $customers,
        'saved_customs' => $saved_customs,
        'saved_customs_reguler' => $saved_customs_reguler,
        'payment_methods' => $payment_methods,
        'loyalty' => $loyalty,
        'default_start_cash' => $default_start_cash
    ]);
    exit;
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

    $stmt = $pdo->prepare("INSERT INTO sales_pos (invoice_no, customer_id, order_type, subtotal, discount_voucher, voucher_code, discount_points, discount_manual, points_used, points_earned, total_amount, payment_method, payment_fee_name, payment_fee_amount, payment_status, dp_amount, amount_paid, change_amount, is_po, channel, pickup_date, pickup_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $invoice_no, $customer_id, 'offline', $data['subtotal'], $data['discount_voucher'], $data['voucher_code'], 
        $data['discount_points'], $data['discount_manual'], $data['points_used'], $data['points_earned'], 
        $data['total_amount'], $data['payment_method'], $payment_fee_name, $payment_fee_amount, $data['payment_status'], $data['dp_amount'], $data['amount_paid'], $data['change_amount'], $is_po, $channel, $pickup_date, $pickup_time, $notes
    ]);
    $sale_id = $pdo->lastInsertId();

    // 1.5 SIMPAN KE SALE_PAYMENTS_POS
    $payment_type = $data['payment_status'] === 'dp' ? 'dp' : 'full';
    $amount_to_record = $data['payment_status'] === 'dp' ? $data['dp_amount'] : ($data['amount_paid'] - $data['change_amount']);
    $stmtPay = $pdo->prepare("INSERT INTO sale_payments_pos (sale_id, amount, payment_method, payment_type) VALUES (?, ?, ?, ?)");
    $stmtPay->execute([$sale_id, $amount_to_record, $data['payment_method'], $payment_type]);

    $stmt_detail = $pdo->prepare("INSERT INTO sale_details_pos (sale_id, product_id, is_custom, custom_name, price, qty, subtotal, created_by_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_potong_stok = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    
    foreach ($data['items'] as $item) {
        $prod_id   = $item['is_custom'] ? 0 : $item['id'];
        $is_custom = $item['is_custom'] ? 1 : 0;
        $custom_name = $item['is_custom'] ? $item['name'] : null;
        // Simpan created_by_user hanya untuk item custom (produk reguler NULL)
        $item_created_by = $is_custom ? $user_id : null;
        $stmt_detail->execute([$sale_id, $prod_id, $is_custom, $custom_name, $item['price'], $item['qty'], $item['subtotal'], $item_created_by]);

        // Potong stok produk reguler (bukan PO, bukan custom)
        if (!$is_po && !$is_custom) { $stmt_potong_stok->execute([$item['qty'], $prod_id]); }

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
    $stmt = $pdo->query("SELECT s.id, s.invoice_no, s.created_at, s.production_status, c.name as customer_name FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.is_po = 1 AND DATE(s.created_at) = CURDATE() ORDER BY s.created_at DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
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
        $data[] = $order;
    }
    echo json_encode(['status' => 'success', 'data' => $data]); exit;
}


// --- LAPORAN ITEM CUSTOM (RIWAYAT PEMBUATAN / KIRIM LANGSUNG) ---
if ($action === 'get_custom_report') {
    $date_from = $_GET['date_from'] ?? date('Y-m-01'); // Default: awal bulan ini
    $date_to   = $_GET['date_to']   ?? date('Y-m-d');  // Default: hari ini

    try {
        $stmt = $pdo->prepare("
            SELECT 
                'Dapur (PO)' AS tipe_pesanan,
                1 AS is_po,
                c.name AS nama_item,
                c.price,
                c.created_at AS waktu_transaksi,
                u.name AS nama_kasir
            FROM saved_custom_items_pos c
            LEFT JOIN users_pos u ON c.created_by = u.id
            WHERE DATE(c.created_at) BETWEEN ? AND ?

            UNION ALL

            SELECT 
                'Reguler' AS tipe_pesanan,
                0 AS is_po,
                r.name AS nama_item,
                r.price,
                r.created_at AS waktu_transaksi,
                u.name AS nama_kasir
            FROM saved_custom_reguler_pos r
            LEFT JOIN users_pos u ON r.created_by = u.id
            WHERE DATE(r.created_at) BETWEEN ? AND ?

            ORDER BY waktu_transaksi DESC
        ");
        
        if (!$stmt) {
            throw new Exception("Gagal menyiapkan query SQL. Cek struktur tabel.");
        }

        $stmt->execute([$date_from, $date_to, $date_from, $date_to]);
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