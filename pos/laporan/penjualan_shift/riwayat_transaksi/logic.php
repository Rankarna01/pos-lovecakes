<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require_once '../../../../config/database.php'; 

$action = $_REQUEST['action'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

if ($action === 'get_report' || $action === 'export_excel') {
    try {
        $wh_filter = "";
        $params = [$start_date, $end_date];
        if ($wh_id > 0) {
            $wh_filter = " AND (s.warehouse_id = ? OR (s.warehouse_id IS NULL AND ? = 1)) ";
            $params[] = $wh_id;
            $params[] = $wh_id;
        }

        // 1. Penjualan per Konsumen (Top Pelanggan)
        $stmt_cust = $pdo->prepare("
            SELECT COALESCE(c.name, 'Pelanggan Umum') as customer_name, COUNT(s.id) as total_transactions, SUM(s.total_amount) as total_spent
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? $wh_filter
            GROUP BY s.customer_id
            ORDER BY total_spent DESC
            LIMIT 50
        ");
        $stmt_cust->execute($params);
        $sales_by_customer = $stmt_cust->fetchAll(PDO::FETCH_ASSOC);

        // 2. Rincian Detail Penjualan (List Transaksi)
        $stmt_details = $pdo->prepare("
            SELECT s.invoice_no, s.created_at, COALESCE(c.name, 'Pelanggan Umum') as customer_name, s.payment_status, s.payment_method, s.total_amount, s.dp_amount
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? $wh_filter
            ORDER BY s.created_at DESC
        ");
        $stmt_details->execute($params);
        $sales_details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

        // --- RESPONSE JSON UNTUK AJAX ---
        if($action === 'get_report') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success', 
                'sales_by_customer' => $sales_by_customer,
                'sales_details' => $sales_details
            ]);
            exit;
        }

        // --- RESPONSE EXCEL UNTUK DOWNLOAD ---
        if($action === 'export_excel') {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Riwayat_Transaksi_".$start_date."_to_".$end_date.".xls");
            
            echo "<table border='1'>";
            echo "<tr><th colspan='3' style='font-size:16px; font-weight:bold; background-color:#e2e8f0;'>Top Pelanggan ($start_date s/d $end_date)</th></tr>";
            echo "<tr><th>Nama Pelanggan</th><th>Total Transaksi</th><th>Total Belanja</th></tr>";
            foreach($sales_by_customer as $cust) {
                echo "<tr><td>{$cust['customer_name']}</td><td>{$cust['total_transactions']}</td><td>{$cust['total_spent']}</td></tr>";
            }

            echo "<tr><td colspan='3'></td></tr>";

            echo "<tr><th colspan='6' style='font-size:16px; font-weight:bold; background-color:#e2e8f0;'>Rincian Detail Penjualan</th></tr>";
            echo "<tr><th>Invoice</th><th>Waktu</th><th>Pelanggan</th><th>Status</th><th>Metode</th><th>Total Bayar</th></tr>";
            foreach($sales_details as $dt) {
                echo "<tr>";
                echo "<td>{$dt['invoice_no']}</td>";
                echo "<td>{$dt['created_at']}</td>";
                echo "<td>{$dt['customer_name']}</td>";
                echo "<td>{$dt['payment_status']}</td>";
                echo "<td>{$dt['payment_method']}</td>";
                echo "<td>{$dt['total_amount']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            exit;
        }

    } catch (Exception $e) {
        if($action === 'get_report') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } else {
            echo "System Error: " . $e->getMessage();
        }
    }
}
?>
