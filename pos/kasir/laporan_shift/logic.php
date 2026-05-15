<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

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
            
            // Hitung Penjualan (Hanya Cash, Aktual = amount_paid - change_amount)
            $stmtSales = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN payment_method = 'cash' THEN amount_paid - change_amount ELSE 0 END) as total_cash_sales,
                    SUM(CASE WHEN payment_method = 'qris' THEN amount_paid - change_amount ELSE 0 END) as total_qris_sales
                FROM sales_pos 
                WHERE created_at BETWEEN ? AND ?
            ");
            $stmtSales->execute([$startTime, $endTime]);
            $sales = $stmtSales->fetch(PDO::FETCH_ASSOC);

            $totalCashSales = $sales['total_cash_sales'] ?? 0;
            $totalQrisSales = $sales['total_qris_sales'] ?? 0;

            // Hitung Kas Keluar
            $stmtKas = $pdo->prepare("
                SELECT SUM(nominal) as total_kas_keluar 
                FROM petty_cash_pos 
                WHERE shift_history_id = ? AND jenis = 'keluar'
            ");
            $stmtKas->execute([$shift['id']]);
            $kasKeluar = $stmtKas->fetch(PDO::FETCH_ASSOC)['total_kas_keluar'] ?? 0;

            $shift['total_cash_sales'] = $totalCashSales;
            $shift['total_qris_sales'] = $totalQrisSales;
            $shift['total_kas_keluar'] = $kasKeluar;
            
            // Saldo Sistem = Modal Awal + Penjualan Cash - Kas Keluar
            $shift['system_balance'] = $shift['start_cash'] + $totalCashSales - $kasKeluar;
            
            // Selisih = Saldo Sistem vs Uang di Laci (End Cash)
            // Jika belum tutup, end_cash = null, selisih = 0
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

        // Transaksi per Shift
        $stmtSales = $pdo->prepare("
            SELECT s.*, COALESCE(c.name, 'Pelanggan Umum') as customer_name 
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE s.created_at BETWEEN ? AND ?
            ORDER BY s.created_at DESC
        ");
        $stmtSales->execute([$startTime, $endTime]);
        $transactions = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

        // Kas Keluar per Shift
        $stmtKas = $pdo->prepare("SELECT * FROM petty_cash_pos WHERE shift_history_id = ? ORDER BY created_at DESC");
        $stmtKas->execute([$id]);
        $pettyCash = $stmtKas->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'shift' => $shift,
            'transactions' => $transactions,
            'petty_cash' => $pettyCash
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
