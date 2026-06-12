<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// AUTO-MIGRATE KOLOM SETTLED_AT
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM sales_pos LIKE 'settled_at'");
    if ($checkCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE sales_pos ADD COLUMN settled_at DATETIME NULL DEFAULT NULL COMMENT 'Waktu pelunasan piutang'");
    }
} catch (Exception $e) {}

if ($action === 'get_shifts') {
    $search = $_GET['search'] ?? '';
    try {
        $query = "SELECT sh.*, COALESCE(u.name, 'Kasir') as cashier_name FROM shifts_history_pos sh LEFT JOIN users_pos u ON sh.user_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($search)) { $query .= " AND u.name LIKE ?"; $params[] = "%$search%"; }

        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        if(!empty($start_date) && !empty($end_date)) {
            $query .= " AND DATE(sh.start_time) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }

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

        foreach ($shifts as &$shift) {
            $startTime = $shift['start_time'];
            $endTime = $shift['end_time'] ?: date('Y-m-d H:i:s');
            
            // 1. Penjualan Tunai Baru
            $stmtSales = $pdo->prepare("SELECT SUM(CASE WHEN payment_method = 'cash' AND payment_status != 'dp' THEN amount_paid - change_amount ELSE 0 END) as cash_baru, SUM(CASE WHEN payment_method = 'cash' AND payment_status = 'dp' THEN dp_amount ELSE 0 END) as dp_baru FROM sales_pos WHERE created_at BETWEEN ? AND ?");
            $stmtSales->execute([$startTime, $endTime]);
            $sales = $stmtSales->fetch(PDO::FETCH_ASSOC);

            // 2. Pelunasan Tunai
            $stmtSettled = $pdo->prepare("SELECT SUM(CASE WHEN payment_method = 'cash' THEN (total_amount - dp_amount) ELSE 0 END) as cash_pelunasan FROM sales_pos WHERE settled_at BETWEEN ? AND ? AND payment_status = 'lunas' AND dp_amount > 0");
            $stmtSettled->execute([$startTime, $endTime]);
            $settled = $stmtSettled->fetch(PDO::FETCH_ASSOC);

            // 3. Kas Keluar
            $stmtKas = $pdo->prepare("SELECT SUM(nominal) as kas_keluar FROM petty_cash_pos WHERE shift_history_id = ? AND jenis = 'keluar'");
            $stmtKas->execute([$shift['id']]);
            $kasKeluar = $stmtKas->fetch(PDO::FETCH_ASSOC)['kas_keluar'] ?? 0;

            $cashBaru = $sales['cash_baru'] ?? 0;
            $cashPelunasan = ($settled['cash_pelunasan'] ?? 0) + ($sales['dp_baru'] ?? 0);
            
            $shift['total_cash_sales'] = $cashBaru;
            $shift['total_cash_pelunasan'] = $cashPelunasan;
            $shift['total_kas_keluar'] = $kasKeluar;
            $shift['system_balance'] = $shift['start_cash'] + $cashBaru + $cashPelunasan - $kasKeluar;
            $shift['difference'] = ($shift['status'] === 'closed') ? ($shift['end_cash'] - $shift['system_balance']) : 0;
        }

        echo json_encode(['status' => 'success', 'data' => $shifts, 'pagination' => ['current_page' => $page, 'total_pages' => $totalPages, 'total_data' => $totalData]]);
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

if ($action === 'get_detail') {
    $id = $_GET['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("SELECT sh.*, COALESCE(u.name, 'Kasir') as cashier_name FROM shifts_history_pos sh LEFT JOIN users_pos u ON sh.user_id = u.id WHERE sh.id = ?");
        $stmt->execute([$id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$shift) { echo json_encode(['status' => 'error', 'message' => 'Shift tidak ditemukan']); exit; }

        $startTime = $shift['start_time'];
        $endTime = $shift['end_time'] ?: date('Y-m-d H:i:s');

        // RE-KALKULASI UNTUK DETAIL MODAL
        $stmtSalesSum = $pdo->prepare("SELECT SUM(CASE WHEN payment_method = 'cash' AND payment_status != 'dp' THEN amount_paid - change_amount ELSE 0 END) as cash_baru, SUM(CASE WHEN payment_method = 'cash' AND payment_status = 'dp' THEN dp_amount ELSE 0 END) as dp_baru FROM sales_pos WHERE created_at BETWEEN ? AND ?");
        $stmtSalesSum->execute([$startTime, $endTime]);
        $salesSum = $stmtSalesSum->fetch(PDO::FETCH_ASSOC);

        $stmtSettledSum = $pdo->prepare("SELECT SUM(CASE WHEN payment_method = 'cash' THEN (total_amount - dp_amount) ELSE 0 END) as cash_pelunasan FROM sales_pos WHERE settled_at BETWEEN ? AND ? AND payment_status = 'lunas' AND dp_amount > 0");
        $stmtSettledSum->execute([$startTime, $endTime]);
        $settledSum = $stmtSettledSum->fetch(PDO::FETCH_ASSOC);

        $stmtKasSum = $pdo->prepare("SELECT SUM(nominal) as kas_keluar FROM petty_cash_pos WHERE shift_history_id = ? AND jenis = 'keluar'");
        $stmtKasSum->execute([$id]);
        $kasKeluarSum = $stmtKasSum->fetch(PDO::FETCH_ASSOC)['kas_keluar'] ?? 0;

        $shift['total_cash_sales'] = $salesSum['cash_baru'] ?? 0;
        $shift['total_cash_pelunasan'] = ($settledSum['cash_pelunasan'] ?? 0) + ($salesSum['dp_baru'] ?? 0);
        $shift['total_kas_keluar'] = $kasKeluarSum;
        $shift['system_balance'] = $shift['start_cash'] + $shift['total_cash_sales'] + $shift['total_cash_pelunasan'] - $shift['total_kas_keluar'];
        $shift['difference'] = ($shift['status'] === 'closed') ? ($shift['end_cash'] - $shift['system_balance']) : 0;

        // Ambil Row Transaksi Baru
        $stmtSales = $pdo->prepare("SELECT s.*, COALESCE(c.name, 'Pelanggan Umum') as customer_name FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.created_at BETWEEN ? AND ? ORDER BY s.created_at DESC");
        $stmtSales->execute([$startTime, $endTime]);
        $transactions = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

        foreach ($transactions as &$trx) {
            $trx['deskripsi'] = ($trx['payment_status'] === 'dp') ? ('Setor DP Awal — Sisa: Rp ' . number_format($trx['total_amount'] - $trx['dp_amount'], 0, ',', '.')) : 'Penjualan Lunas Langsung';
        }

        // Ambil Row Pelunasan
        $stmtSettled = $pdo->prepare("SELECT s.*, COALESCE(c.name, 'Pelanggan Umum') as customer_name FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.settled_at BETWEEN ? AND ? AND s.payment_status = 'lunas' AND s.dp_amount > 0 ORDER BY s.settled_at DESC");
        $stmtSettled->execute([$startTime, $endTime]);
        $settlements = $stmtSettled->fetchAll(PDO::FETCH_ASSOC);

        foreach ($settlements as &$s) {
            $s['deskripsi'] = 'Pelunasan sisa nota piutang senilai Rp ' . number_format($s['total_amount'] - $s['dp_amount'], 0, ',', '.');
        }

        $stmtKas = $pdo->prepare("SELECT * FROM petty_cash_pos WHERE shift_history_id = ? ORDER BY created_at DESC");
        $stmtKas->execute([$id]);
        $pettyCash = $stmtKas->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'shift' => $shift, 'transactions' => $transactions, 'settlements' => $settlements, 'petty_cash' => $pettyCash]);
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}
?>