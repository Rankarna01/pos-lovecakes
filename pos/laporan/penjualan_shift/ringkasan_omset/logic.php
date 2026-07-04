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

        // 1. QUERY KOMPOSISI PEMBAYARAN GLOBAL (Untuk Omset Sistem)
        $stmt_pay = $pdo->prepare("
            SELECT sp.payment_method, pm.type, SUM(sp.amount) as total 
            FROM sale_payments_pos sp
            JOIN sales_pos s ON sp.sale_id = s.id
            LEFT JOIN payment_methods pm ON sp.payment_method = pm.name
            WHERE DATE(sp.created_at) BETWEEN ? AND ? $wh_filter
            GROUP BY sp.payment_method, pm.type
        ");
        $stmt_pay->execute($params);
        $pay_results = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
        
        $paymentData = ['cash' => 0, 'qris' => 0, 'total' => 0];
        foreach ($pay_results as $row) {
            if ($row['type'] === 'Cash' || strtolower($row['payment_method']) === 'cash') { 
                $paymentData['cash'] += (float)$row['total']; 
            } else { 
                $paymentData['qris'] += (float)$row['total']; 
            }
            $paymentData['total'] += (float)$row['total'];
        }

        // 2. Rincian Metode Pembayaran
        $stmt_pay_breakdown = $pdo->prepare("
            SELECT sp.payment_method, SUM(sp.amount) as total_amount
            FROM sale_payments_pos sp
            JOIN sales_pos s ON sp.sale_id = s.id
            WHERE DATE(sp.created_at) BETWEEN ? AND ? $wh_filter
            GROUP BY sp.payment_method
            ORDER BY total_amount DESC
        ");
        $stmt_pay_breakdown->execute($params);
        $payment_breakdown = $stmt_pay_breakdown->fetchAll(PDO::FETCH_ASSOC);

        // 3. Pembayaran DP dan Pelunasan
        $stmt_dp = $pdo->prepare("
            SELECT sp.payment_type, SUM(sp.amount) as total_amount, COUNT(sp.id) as total_transactions
            FROM sale_payments_pos sp
            JOIN sales_pos s ON sp.sale_id = s.id
            WHERE sp.payment_type IN ('dp', 'pelunasan') AND DATE(sp.created_at) BETWEEN ? AND ? $wh_filter
            GROUP BY sp.payment_type
        ");
        $stmt_dp->execute($params);
        $dp_pelunasan = $stmt_dp->fetchAll(PDO::FETCH_ASSOC);

        // --- RESPONSE JSON UNTUK AJAX ---
        if($action === 'get_report') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success', 
                'payments' => $paymentData, 
                'payment_breakdown' => $payment_breakdown,
                'dp_pelunasan' => $dp_pelunasan
            ]);
            exit;
        }

        // --- RESPONSE EXCEL UNTUK DOWNLOAD ---
        if($action === 'export_excel') {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Laporan_Omset_".$start_date."_to_".$end_date.".xls");
            
            echo "<table border='1'>";
            echo "<tr><th colspan='2' style='font-size:16px; font-weight:bold; background-color:#e2e8f0;'>Komposisi Omset Sistem ($start_date s/d $end_date)</th></tr>";
            echo "<tr><th>Jenis</th><th>Total</th></tr>";
            echo "<tr><td>Cash / Tunai</td><td>{$paymentData['cash']}</td></tr>";
            echo "<tr><td>QRIS & Transfer</td><td>{$paymentData['qris']}</td></tr>";
            echo "<tr><td><b>Total Omset</b></td><td><b>{$paymentData['total']}</b></td></tr>";
            
            echo "<tr><td colspan='2'></td></tr>";
            
            echo "<tr><th colspan='2' style='font-size:16px; font-weight:bold; background-color:#e2e8f0;'>Rincian Metode Pembayaran</th></tr>";
            echo "<tr><th>Metode</th><th>Total Amount</th></tr>";
            foreach($payment_breakdown as $pb) {
                echo "<tr><td>{$pb['payment_method']}</td><td>{$pb['total_amount']}</td></tr>";
            }

            echo "<tr><td colspan='2'></td></tr>";

            echo "<tr><th colspan='3' style='font-size:16px; font-weight:bold; background-color:#e2e8f0;'>DP vs Pelunasan</th></tr>";
            echo "<tr><th>Tipe</th><th>Total Transaksi</th><th>Total Amount</th></tr>";
            foreach($dp_pelunasan as $dp) {
                echo "<tr><td>{$dp['payment_type']}</td><td>{$dp['total_transactions']}</td><td>{$dp['total_amount']}</td></tr>";
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
