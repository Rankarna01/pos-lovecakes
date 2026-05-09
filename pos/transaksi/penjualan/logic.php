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

    try {
        $query = "
            SELECT s.*, COALESCE(c.name, 'Pelanggan Umum') as customer_name 
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE 1=1
        ";
        $params = [];

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

        $query .= " ORDER BY s.created_at DESC LIMIT 100"; // Batasi 100 terakhir agar ringan

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $data]);
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