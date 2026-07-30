<?php
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

$wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

if ($action === 'get_sales') {
    $start_date = $_REQUEST['start_date'] ?? date('Y-m-d');
    $end_date = $_REQUEST['end_date'] ?? date('Y-m-d');
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

        $wh_filter = "";
        $wh_params = [];
        if ($wh_id > 0) {
            $wh_filter = " AND (s.warehouse_id = ? OR (s.warehouse_id IS NULL AND ? = 1)) ";
            $wh_params = [$wh_id, $wh_id];
        }

        $payment = $_REQUEST['payment_method'] ?? '';
        $pay_filter = "";
        $pay_params = [];
        if (!empty($payment)) {
            if (strtolower($payment) === 'cash') {
                $pay_filter = " AND (LOWER(s.payment_method) = 'cash' OR LOWER(s.payment_method) = 'tunai') ";
            } elseif (strtolower($payment) === 'qris') {
                $pay_filter = " AND LOWER(s.payment_method) LIKE '%qris%' ";
            } elseif (strtolower($payment) === 'transfer') {
                $pay_filter = " AND (LOWER(s.payment_method) LIKE '%transfer%' OR LOWER(s.payment_method) LIKE '%bank%') ";
            } elseif (strtolower($payment) === 'split') {
                $pay_filter = " AND LOWER(s.payment_method) LIKE '%split%' ";
            } else {
                $pay_filter = " AND LOWER(s.payment_method) = ? ";
                $pay_params = [strtolower($payment)];
            }
        }

        $payment_status = $_REQUEST['payment_status'] ?? '';
        $status_filter = "";
        $status_params = [];
        if (!empty($payment_status)) {
            $status_filter = " AND LOWER(s.payment_status) = ? ";
            $status_params = [strtolower($payment_status)];
        }

        // 1. Rincian Detail Penjualan (History List)
        $sql1 = "
            SELECT s.*, c.name as customer_name 
            FROM sales_pos s 
            LEFT JOIN customers_pos c ON s.customer_id = c.id 
            WHERE DATE(s.created_at) BETWEEN ? AND ? $wh_filter $pay_filter $status_filter
            ORDER BY s.created_at DESC
        ";
        $stmt = $pdo->prepare($sql1);
        $stmt->execute(array_merge([$start_date, $end_date], $wh_params, $pay_params, $status_params));
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Global Payment Summary (Omset Sistem)
        $sql2 = "
            SELECT sp.payment_method, pm.type, SUM(sp.amount) as total 
            FROM sale_payments_pos sp
            JOIN sales_pos s ON sp.sale_id = s.id
            LEFT JOIN payment_methods pm ON sp.payment_method = pm.name
            WHERE DATE(sp.created_at) BETWEEN ? AND ? $wh_filter $pay_filter $status_filter
            GROUP BY sp.payment_method, pm.type
        ";
        $stmt_pay = $pdo->prepare($sql2);
        $stmt_pay->execute(array_merge([$start_date, $end_date], $wh_params, $pay_params, $status_params));
        $pay_results = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
        
        $paymentData = ['cash' => 0, 'qris' => 0, 'total' => 0];
        foreach ($pay_results as $row) {
            $pm = strtolower($row['payment_method']);
            $dbType = strtolower($row['type'] ?? '');
            
            // Dinamiskan: Jika nama metode mengandung qris/transfer, atau tipenya non-cash
            if (strpos($pm, 'qris') !== false || strpos($pm, 'transfer') !== false || in_array($dbType, ['qris', 'transfer', 'non-cash'])) {
                $paymentData['qris'] += (float)$row['total'];
            } else {
                // Sisanya masuk ke Cash
                $paymentData['cash'] += (float)$row['total'];
            }
            $paymentData['total'] += (float)$row['total'];
        }

        // 3. Pembayaran Detail (Cash/TF Breakdown)
        $sql3 = "
            SELECT sp.payment_method, SUM(sp.amount) as total_amount
            FROM sale_payments_pos sp
            JOIN sales_pos s ON sp.sale_id = s.id
            WHERE DATE(sp.created_at) BETWEEN ? AND ? $wh_filter $pay_filter $status_filter
            GROUP BY sp.payment_method
            ORDER BY total_amount DESC
        ";
        $stmt_pay_breakdown = $pdo->prepare($sql3);
        $stmt_pay_breakdown->execute(array_merge([$start_date, $end_date], $wh_params, $pay_params, $status_params));
        $payment_breakdown = $stmt_pay_breakdown->fetchAll(PDO::FETCH_ASSOC);

        // 4. Pembayaran DP dan Pelunasan
        $sql4 = "
            SELECT sp.payment_type, SUM(sp.amount) as total_amount, COUNT(sp.id) as total_transactions
            FROM sale_payments_pos sp
            JOIN sales_pos s ON sp.sale_id = s.id
            WHERE sp.payment_type IN ('dp', 'pelunasan') AND DATE(sp.created_at) BETWEEN ? AND ? $wh_filter $pay_filter $status_filter
            GROUP BY sp.payment_type
        ";
        $stmt_dp = $pdo->prepare($sql4);
        $stmt_dp->execute(array_merge([$start_date, $end_date], $wh_params, $pay_params, $status_params));
        $dp_pelunasan = $stmt_dp->fetchAll(PDO::FETCH_ASSOC);

        // 5. Penjualan per Kategori
        $sql5 = "
            SELECT COALESCE(p.category, 'Produk Custom') as category_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? $wh_filter $pay_filter $status_filter
            GROUP BY p.category
            ORDER BY total_amount DESC
        ";
        $stmt_cat = $pdo->prepare($sql5);
        $stmt_cat->execute(array_merge([$start_date, $end_date], $wh_params, $pay_params, $status_params));
        $sales_by_category = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        // 6. Penjualan per Barang (Terlaris)
        $sql6 = "
            SELECT COALESCE(p.name, sd.custom_name) as item_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? $wh_filter $pay_filter $status_filter
            GROUP BY COALESCE(p.name, sd.custom_name)
            ORDER BY total_amount DESC
        ";
        $stmt_item = $pdo->prepare($sql6);
        $stmt_item->execute(array_merge([$start_date, $end_date], $wh_params, $pay_params, $status_params));
        $sales_by_item = $stmt_item->fetchAll(PDO::FETCH_ASSOC);

        // 7. Penjualan per Konsumen
        $sql7 = "
            SELECT s.customer_id, COALESCE(c.name, 'Pelanggan Umum') as customer_name, COUNT(s.id) as total_transactions, SUM(s.total_amount) as total_spent
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? $wh_filter $pay_filter $status_filter
            GROUP BY s.customer_id, COALESCE(c.name, 'Pelanggan Umum')
            ORDER BY total_spent DESC
        ";
        $stmt_cust = $pdo->prepare($sql7);
        $stmt_cust->execute(array_merge([$start_date, $end_date], $wh_params, $pay_params, $status_params));
        $sales_by_customer = $stmt_cust->fetchAll(PDO::FETCH_ASSOC);

        // 8. Riwayat Pembatalan Transaksi
        $sql8 = "
            SELECT sc.*, s.invoice_no, s.payment_method, sp.note as admin_name
            FROM sale_cancellations_pos sc
            JOIN sales_pos s ON sc.sale_id = s.id
            LEFT JOIN supervisor_pins_pos sp ON sc.authorized_by_pin = sp.pin
            WHERE DATE(sc.created_at) BETWEEN ? AND ? $wh_filter
            ORDER BY sc.created_at DESC
        ";
        $stmt_cancel = $pdo->prepare($sql8);
        $stmt_cancel->execute(array_merge([$start_date, $end_date], $wh_params));
        $cancellations = $stmt_cancel->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'data' => $sales, 
            'restricted' => ($role === 'kasir' && $is_hidden),
            'payments' => $paymentData,
            'payment_breakdown' => $payment_breakdown,
            'dp_pelunasan' => $dp_pelunasan,
            'sales_by_category' => $sales_by_category,
            'sales_by_item' => $sales_by_item,
            'sales_by_customer' => $sales_by_customer,
            'cancellations' => $cancellations
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_detail') {
    $sale_id = $_REQUEST['id'] ?? $_REQUEST['sale_id'] ?? 0;
    try {
        $stmt = $pdo->prepare("
            SELECT sd.*, COALESCE(p.name, sd.custom_name) as product_name, p.image
            FROM sale_details_pos sd 
            LEFT JOIN products p ON sd.product_id = p.id 
            WHERE sd.sale_id = ?
        ");
        $stmt->execute([$sale_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil juga riwayat pembayaran (DP & Pelunasan) dari sale_payments_pos
        $payments = [];
        try {
            $stmt_pay = $pdo->prepare("
                SELECT payment_type, amount, payment_method, created_at
                FROM sale_payments_pos
                WHERE sale_id = ?
                ORDER BY created_at ASC
            ");
            $stmt_pay->execute([$sale_id]);
            $payments = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Dan kita ambil info pokok sale jika dibutuhkan
        $sale_info = null;
        try {
            $stmt_sale = $pdo->prepare("SELECT payment_status, dp_amount, amount_paid, total_amount, created_at, settled_at FROM sales_pos WHERE id = ?");
            $stmt_sale->execute([$sale_id]);
            $sale_info = $stmt_sale->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        echo json_encode([
            'status' => 'success', 
            'data' => $details,
            'payments' => $payments,
            'sale_info' => $sale_info
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// AMBIL DAFTAR TRANSAKSI BERDASARKAN KATEGORI ATAU PRODUK
if ($action === 'get_sales_by_item') {
    $start_date = $_REQUEST['start_date'] ?? date('Y-m-d');
    $end_date = $_REQUEST['end_date'] ?? date('Y-m-d');
    $filter_type = $_REQUEST['filter_type'] ?? 'category'; // 'category' atau 'product'
    $filter_value = $_REQUEST['filter_value'] ?? '';
    
    // Base parameters
    $params = [$start_date, $end_date];
    
    // Warehouse Filter
    $wh_filter = "";
    if ($wh_id > 0) {
        $wh_filter = " AND (s.warehouse_id = ? OR (s.warehouse_id IS NULL AND ? = 1)) ";
        $params[] = $wh_id;
        $params[] = $wh_id;
    }

    try {
        $sql = "
            SELECT DISTINCT s.*, c.name as customer_name 
            FROM sales_pos s 
            LEFT JOIN customers_pos c ON s.customer_id = c.id 
            JOIN sale_details_pos sd ON s.id = sd.sale_id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? $wh_filter
        ";

        if ($filter_type === 'category') {
            if ($filter_value === 'Uncategorized' || $filter_value === 'Item Custom' || $filter_value === 'Produk Custom') {
                $sql .= " AND (p.category IS NULL OR p.category = '' OR p.category = 'Item Custom' OR p.category = 'Produk Custom' OR p.id IS NULL) ";
            } else {
                $sql .= " AND p.category = ? ";
                $params[] = $filter_value;
            }
        } else {
            // product
            $sql .= " AND COALESCE(p.name, sd.custom_name) = ? ";
            $params[] = $filter_value;
        }

        $sql .= " ORDER BY s.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $sales]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
