<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// AUTO-MIGRATE: Tambah kolom settled_at jika belum ada
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM sales_pos LIKE 'settled_at'");
    if ($checkCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE sales_pos ADD COLUMN settled_at DATETIME NULL DEFAULT NULL COMMENT 'Waktu pelunasan piutang/DP'");
    }
} catch (Exception $e) { /* Abaikan jika gagal, mungkin kolom sudah ada */ }

// AMBIL DAFTAR SHIFT
if ($action === 'get_shifts') {
    $search = $_GET['search'] ?? '';
    
    try {
        $query = "
            SELECT sh.*, COALESCE(u.name, 'Kasir') as cashier_name
            FROM shifts_history_pos sh
            LEFT JOIN users_pos u ON sh.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $query .= " AND u.name LIKE ?";
            $params[] = "%$search%";
        }

        // Pagination
        $countQuery = str_replace("sh.*, COALESCE(u.name, 'Kasir') as cashier_name", "COUNT(*) as total", $query);
        $stmtCount = $pdo->prepare($countQuery);
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $totalPages = ceil($totalData / $limit);

        $query .= " ORDER BY sh.start_time DESC LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Kalkulasi Total per Shift
        foreach ($shifts as &$shift) {
            $startTime = $shift['start_time'];
            $endTime = $shift['end_time'] ?: date('Y-m-d H:i:s');
            
            // 1. Penjualan baru di shift ini (transaksi yang dibuat saat shift berlangsung)
            $stmtSales = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN payment_method = 'cash' AND payment_status != 'dp' THEN amount_paid - change_amount ELSE 0 END) as total_cash_sales,
                    SUM(CASE WHEN payment_method = 'qris' AND payment_status != 'dp' THEN amount_paid - change_amount ELSE 0 END) as total_qris_sales
                FROM sales_pos 
                WHERE created_at BETWEEN ? AND ?
            ");
            $stmtSales->execute([$startTime, $endTime]);
            $sales = $stmtSales->fetch(PDO::FETCH_ASSOC);

            // 2. Pelunasan piutang yang terjadi di shift ini (settled_at dalam rentang shift)
            $stmtSettled = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN payment_method = 'cash' THEN (amount_paid - dp_amount) - change_amount ELSE 0 END) as cash_pelunasan,
                    SUM(CASE WHEN payment_method = 'qris' THEN (amount_paid - dp_amount) ELSE 0 END) as qris_pelunasan
                FROM sales_pos 
                WHERE settled_at BETWEEN ? AND ?
                  AND payment_status = 'lunas'
                  AND dp_amount > 0
            ");
            $stmtSettled->execute([$startTime, $endTime]);
            $settled = $stmtSettled->fetch(PDO::FETCH_ASSOC);

            $totalCashSales  = ($sales['total_cash_sales'] ?? 0) + ($settled['cash_pelunasan'] ?? 0);
            $totalQrisSales  = ($sales['total_qris_sales'] ?? 0) + ($settled['qris_pelunasan'] ?? 0);

            // Kas Keluar
            $stmtKas = $pdo->prepare("
                SELECT SUM(nominal) as total_kas_keluar 
                FROM petty_cash_pos 
                WHERE shift_history_id = ? AND jenis = 'keluar'
            ");
            $stmtKas->execute([$shift['id']]);
            $kasKeluar = $stmtKas->fetch(PDO::FETCH_ASSOC)['total_kas_keluar'] ?? 0;

            $shift['total_cash_sales']  = $totalCashSales;
            $shift['total_qris_sales']  = $totalQrisSales;
            $shift['total_kas_keluar']  = $kasKeluar;
            
            // Saldo Sistem = Modal Awal + Penjualan Cash - Kas Keluar
            $shift['system_balance'] = $shift['start_cash'] + $totalCashSales - $kasKeluar;
            
            if ($shift['status'] === 'closed') {
                $shift['difference'] = $shift['end_cash'] - $shift['system_balance'];
            } else {
                $shift['difference'] = 0;
            }
        }

        echo json_encode([
            'status' => 'success', 
            'data' => $shifts, 
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

// AMBIL DETAIL SHIFT
if ($action === 'get_detail') {
    $id = $_GET['id'] ?? 0;
    try {
        // Ambil Data Shift
        $stmt = $pdo->prepare("SELECT sh.*, COALESCE(u.name, 'Kasir') as cashier_name FROM shifts_history_pos sh LEFT JOIN users_pos u ON sh.user_id = u.id WHERE sh.id = ?");
        $stmt->execute([$id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$shift) {
            echo json_encode(['status' => 'error', 'message' => 'Shift tidak ditemukan']); exit;
        }

        $startTime = $shift['start_time'];
        $endTime = $shift['end_time'] ?: date('Y-m-d H:i:s');

        // 1. Transaksi penjualan baru di shift ini
        $stmtSales = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(c.name, 'Pelanggan Umum') as customer_name,
                   'penjualan' as entry_type,
                   s.created_at as entry_time
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE s.created_at BETWEEN ? AND ?
            ORDER BY s.created_at DESC
        ");
        $stmtSales->execute([$startTime, $endTime]);
        $transactions = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

        // Tandai transaksi DP yang belum dilunasi
        foreach ($transactions as &$trx) {
            if ($trx['payment_status'] === 'dp') {
                $trx['deskripsi'] = 'Uang Muka (DP) — Sisa: Rp ' . number_format($trx['total_amount'] - $trx['dp_amount'], 0, ',', '.');
            } else {
                $trx['deskripsi'] = ucfirst($trx['payment_method'] ?? '-');
            }
        }
        unset($trx);

        // 2. Pelunasan piutang yang terjadi DI shift ini
        $stmtSettled = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(c.name, 'Pelanggan Umum') as customer_name,
                   'pelunasan' as entry_type,
                   s.settled_at as entry_time
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE s.settled_at BETWEEN ? AND ?
              AND s.payment_status = 'lunas'
              AND s.dp_amount > 0
            ORDER BY s.settled_at DESC
        ");
        $stmtSettled->execute([$startTime, $endTime]);
        $settlements = $stmtSettled->fetchAll(PDO::FETCH_ASSOC);

        foreach ($settlements as &$s) {
            $sisa = $s['amount_paid'] - $s['dp_amount'];
            $s['deskripsi'] = 'Pelunasan Piutang — DP sebelumnya: Rp ' . number_format($s['dp_amount'], 0, ',', '.') . ', Dilunasi: Rp ' . number_format($sisa, 0, ',', '.');
        }
        unset($s);

        // Kas Keluar per Shift
        $stmtKas = $pdo->prepare("SELECT * FROM petty_cash_pos WHERE shift_history_id = ? ORDER BY created_at DESC");
        $stmtKas->execute([$id]);
        $pettyCash = $stmtKas->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'shift' => $shift,
            'transactions' => $transactions,
            'settlements' => $settlements,
            'petty_cash' => $pettyCash
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
