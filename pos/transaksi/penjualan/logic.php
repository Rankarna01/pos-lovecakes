<?php
session_start();
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

        if (!empty($_SESSION['pos_warehouse_id'])) {
            $query .= " AND s.warehouse_id = ?";
            $params[] = intval($_SESSION['pos_warehouse_id']);
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
            $query .= " AND s.payment_method = ?";
            $params[] = $payment;
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

        echo json_encode(['status' => 'success', 'data' => $details]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>