<?php
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// AMBIL DAFTAR TRANSAKSI
if ($action === 'get_sales') {
    $search = $_GET['search'] ?? '';
    $channel = $_GET['channel'] ?? '';
    $payment = $_GET['payment'] ?? '';
    $time_range = $_GET['time_range'] ?? ''; // FILTER BARU
    $status = $_GET['status'] ?? '';         // FILTER BARU

    try {
        $query = "
            SELECT s.*, COALESCE(c.name, 'Pelanggan Umum') as customer_name 
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE 1=1
        ";
        $params = [];

        $wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;
        if ($wh_id > 0) {
            $query .= " AND (s.warehouse_id = ? OR (s.warehouse_id IS NULL AND ? = 1))";
            $params[] = $wh_id;
            $params[] = $wh_id;
        }

        if (!empty($search)) {
            $query .= " AND (s.invoice_no LIKE ? OR c.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($channel)) {
            $query .= " AND s.channel = ?";
            $params[] = $channel;
        }
        if (!empty($payment)) {
            if (strtolower($payment) === 'cash') {
                $query .= " AND (LOWER(s.payment_method) = 'cash' OR LOWER(s.payment_method) = 'tunai')";
            } elseif (strtolower($payment) === 'qris') {
                $query .= " AND LOWER(s.payment_method) LIKE '%qris%'";
            } elseif (strtolower($payment) === 'transfer') {
                $query .= " AND (LOWER(s.payment_method) LIKE '%transfer%' OR LOWER(s.payment_method) LIKE '%bank%')";
            } elseif (strtolower($payment) === 'split') {
                $query .= " AND LOWER(s.payment_method) LIKE '%split%'";
            } else {
                $query .= " AND LOWER(s.payment_method) = ?";
                $params[] = strtolower($payment);
            }
        }
        if (!empty($status)) {
            if ($status === 'lunas_langsung') {
                $query .= " AND s.payment_status = 'lunas' AND (s.dp_amount IS NULL OR s.dp_amount = 0)";
            } elseif ($status === 'dp_semua') {
                $query .= " AND s.dp_amount > 0";
            } elseif ($status === 'dp_belum') {
                $query .= " AND s.payment_status = 'dp'";
            } elseif ($status === 'dp_lunas') {
                $query .= " AND s.payment_status = 'lunas' AND s.dp_amount > 0";
            } else {
                $query .= " AND s.payment_status = ?";
                $params[] = $status;
            }
        }

        // FILTER RENTANG WAKTU
        if ($time_range === 'today') {
            $query .= " AND DATE(s.created_at) = CURRENT_DATE()";
        } elseif ($time_range === 'week') {
            // Data minggu ini (dimulai dari Senin)
            $query .= " AND YEARWEEK(s.created_at, 1) = YEARWEEK(CURRENT_DATE(), 1)";
        } elseif ($time_range === 'month') {
            // Data bulan dan tahun ini
            $query .= " AND MONTH(s.created_at) = MONTH(CURRENT_DATE()) AND YEAR(s.created_at) = YEAR(CURRENT_DATE())";
        }

        // Hitung total data untuk pagination
        $countQuery = str_replace("s.*, COALESCE(c.name, 'Pelanggan Umum') as customer_name", "COUNT(*) as total", $query);
        $stmtCount = $pdo->prepare($countQuery);
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $totalPages = ceil($totalData / $limit);

        $query .= " ORDER BY s.created_at DESC LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'data' => $data, 
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_data' => $totalData,
                'limit' => $limit
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// AMBIL DETAIL ITEM PER TRANSAKSI
if ($action === 'get_detail') {
    $id = $_GET['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("
            SELECT sd.*, COALESCE(p.name, sd.custom_name) as product_name 
            FROM sale_details_pos sd
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE sd.sale_id = ?
        ");
        $stmt->execute([$id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil riwayat pembayaran (DP & Pelunasan) dari sale_payments_pos
        $payments = [];
        try {
            $stmt_pay = $pdo->prepare("
                SELECT payment_type, amount, payment_method, created_at
                FROM sale_payments_pos
                WHERE sale_id = ?
                ORDER BY created_at ASC
            ");
            $stmt_pay->execute([$id]);
            $payments = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Ambil info pokok sale
        $sale_info = null;
        try {
            $stmt_sale = $pdo->prepare("SELECT payment_status, dp_amount, amount_paid, total_amount, created_at, settled_at FROM sales_pos WHERE id = ?");
            $stmt_sale->execute([$id]);
            $sale_info = $stmt_sale->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        echo json_encode([
            'status' => 'success', 
            'data' => $details,
            'payments' => $payments,
            'sale_info' => $sale_info
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// PEMBATALAN TRANSAKSI (VOID)
if ($action === 'cancel_sale') {
    $sale_id = $_POST['sale_id'] ?? 0;
    $cancellation_type = $_POST['cancellation_type'] ?? 'partial'; // full / partial
    $reason = $_POST['reason'] ?? '';
    $pin = $_POST['pin'] ?? '';
    $total_amount = (float)($_POST['total_amount'] ?? 0);
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    $user_id = $_SESSION['pos_user_id'] ?? 0;
    $wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 1;

    try {
        $pdo->beginTransaction();

        // 1. Validasi PIN Supervisor
        $stmt_pin = $pdo->prepare("SELECT * FROM supervisor_pins_pos WHERE pin = ? AND (is_used = 0 OR is_used IS NULL)");
        $stmt_pin->execute([$pin]);
        $valid_pin = $stmt_pin->fetch(PDO::FETCH_ASSOC);
        if (!$valid_pin) {
            throw new Exception("PIN Admin tidak valid atau sudah tidak bisa digunakan.");
        }

        // 2. Cek Shift Kasir (Hanya bisa void jika ada shift aktif)
        $stmt_shift = $pdo->prepare("SELECT id, start_time FROM shifts_history_pos WHERE user_id = ? AND status = 'open' LIMIT 1");
        $stmt_shift->execute([$user_id]);
        $active_shift = $stmt_shift->fetch(PDO::FETCH_ASSOC);
        if (!$active_shift) {
            throw new Exception("Anda tidak memiliki shift aktif. Silakan buka shift terlebih dahulu di menu Kasir.");
        }

        // 3. Tarik data penjualan
        $stmt_sale = $pdo->prepare("SELECT * FROM sales_pos WHERE id = ? FOR UPDATE");
        $stmt_sale->execute([$sale_id]);
        $sale = $stmt_sale->fetch(PDO::FETCH_ASSOC);
        
        if (!$sale) {
            throw new Exception("Transaksi tidak ditemukan.");
        }
        if ($sale['cancellation_status'] === 'full') {
            throw new Exception("Transaksi ini sudah dibatalkan sepenuhnya.");
        }

        // Cek apakah transaksi dibuat sebelum shift aktif ini dimulai
        if (strtotime($sale['created_at']) < strtotime($active_shift['start_time'])) {
            throw new Exception("Pembatalan ditolak! Transaksi ini berasal dari shift lama yang sudah ditutup.");
        }

        // 4. Logika Uang Kas
        $is_cash = 0;
        $pm = strtolower($sale['payment_method']);
        if ($pm === 'cash' || $pm === 'tunai') {
            $is_cash = 1;
        }

        // 5. Catat Pembatalan
        $stmt_cancel = $pdo->prepare("INSERT INTO sale_cancellations_pos (sale_id, cancellation_type, amount, is_cash_deducted, reason, authorized_by_pin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_cancel->execute([$sale_id, $cancellation_type, $total_amount, $is_cash, $reason, $pin]);
        $cancellation_id = $pdo->lastInsertId();

        // 6. Proses Item & Stok
        $stmt_item_cancel = $pdo->prepare("INSERT INTO sale_cancellation_items_pos (cancellation_id, sale_detail_id, qty, amount) VALUES (?, ?, ?, ?)");
        $stmt_update_sd = $pdo->prepare("UPDATE sale_details_pos SET cancelled_qty = cancelled_qty + ? WHERE id = ?");
        $stmt_restore_stock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt_restore_wh = $pdo->prepare("UPDATE product_warehouse_stocks SET stock = stock + ? WHERE product_id = ? AND warehouse_id = ?");
        $stmt_history = $pdo->prepare("INSERT INTO inventory_history_pos (product_id, type, qty, reference_no, source) VALUES (?, 'Masuk', ?, ?, 'Batal Transaksi')");

        foreach ($items as $item) {
            $qty = intval($item['qty']);
            $prod_id = intval($item['product_id']);
            $sd_id = intval($item['sale_detail_id']);
            
            // Catat detail item yg dibatal
            $stmt_item_cancel->execute([$cancellation_id, $sd_id, $qty, $item['amount']]);
            // Update cancelled qty
            $stmt_update_sd->execute([$qty, $sd_id]);

            // Kembalikan Stok jika bukan item custom
            if ($prod_id > 0) {
                $stmt_restore_stock->execute([$qty, $prod_id]);
                $stmt_restore_wh->execute([$qty, $prod_id, $wh_id]);
                $stmt_history->execute([$prod_id, $qty, 'VOID-'.$sale['invoice_no']]);
            }
        }

        // 7. Pengembalian Poin & Voucher (Hanya jika Full Void)
        if ($cancellation_type === 'full') {
            if (!empty($sale['voucher_code'])) {
                $pdo->prepare("UPDATE vouchers_pos SET used_count = used_count - 1 WHERE voucher_code = ? AND used_count > 0")->execute([$sale['voucher_code']]);
            }
            if (!empty($sale['customer_id'])) {
                // Poin yg dipake dibalikin, poin yg didapat ditarik
                $pdo->prepare("UPDATE customers_pos SET points = points + ? - ? WHERE id = ?")->execute([$sale['points_used'], $sale['points_earned'], $sale['customer_id']]);
            }
        }

        // 8. Update Sales Status
        $new_cancelled_amount = $sale['cancelled_amount'] + $total_amount;
        $pdo->prepare("UPDATE sales_pos SET cancellation_status = ?, cancelled_amount = ? WHERE id = ?")->execute([$cancellation_type, $new_cancelled_amount, $sale_id]);

        // 9. Tandai PIN sebagai sudah digunakan
        $pdo->prepare("UPDATE supervisor_pins_pos SET is_used = 1, used_at = NOW() WHERE id = ?")->execute([$valid_pin['id']]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil dibatalkan.']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>