<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

$wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

if ($action === 'get_summary') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    
    $start_date_full = $start_date . ' 00:00:00';
    $end_date_full = $end_date . ' 23:59:59';

    $wh_filter = "";
    $wh_params = [];
    if ($wh_id > 0) {
        $wh_filter = " AND (s.warehouse_id = ? OR (s.warehouse_id IS NULL AND ? = 1)) ";
        $wh_params = [$wh_id, $wh_id];
    }

    try {
        // 1. DATA HEATMAP JAM SIBUK (Ekstrak jam dari created_at)
        $sql1 = "
            SELECT HOUR(s.created_at) as hour_of_day, COUNT(s.id) as total_trx 
            FROM sales_pos s
            WHERE s.created_at >= ? AND s.created_at <= ? $wh_filter
            GROUP BY HOUR(s.created_at)
            ORDER BY hour_of_day ASC
        ";
        $stmt_hours = $pdo->prepare($sql1);
        $stmt_hours->execute(array_merge([$start_date_full, $end_date_full], $wh_params));
        $heatmap_raw = $stmt_hours->fetchAll(PDO::FETCH_ASSOC);

        // 2. DATA PELANGGAN TERSETIA (CRM)
        $sql2 = "
            SELECT 
                c.id, 
                c.name as customer_name, 
                COUNT(DISTINCT s.id) as total_visits
            FROM sales_pos s
            JOIN customers_pos c ON s.customer_id = c.id
            WHERE s.created_at >= ? AND s.created_at <= ? $wh_filter
            GROUP BY c.id, c.name
            ORDER BY total_visits DESC
            LIMIT 10
        ";
        $stmt_cust = $pdo->prepare($sql2);
        $stmt_cust->execute(array_merge([$start_date_full, $end_date_full], $wh_params));
        $top_customers = $stmt_cust->fetchAll(PDO::FETCH_ASSOC);

        // Cari tahu barang apa yang paling sering dibeli oleh pelanggan tersebut
        $customer_data = [];
        $sql3 = "
            SELECT COALESCE(p.name, sd.custom_name) as product_name, SUM(sd.qty) as total_qty
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE s.customer_id = ? AND s.created_at >= ? AND s.created_at <= ? $wh_filter
            GROUP BY product_name
            ORDER BY total_qty DESC
            LIMIT 3
        ";
        $stmt_fav_items = $pdo->prepare($sql3);

        foreach ($top_customers as $cust) {
            $stmt_fav_items->execute(array_merge([$cust['id'], $start_date_full, $end_date_full], $wh_params));
            $fav_items = $stmt_fav_items->fetchAll(PDO::FETCH_ASSOC);
            
            $item_names = [];
            foreach ($fav_items as $item) {
                $item_names[] = $item['product_name'];
            }
            
            $cust['favorite_items'] = !empty($item_names) ? implode(', ', $item_names) : '-';
            $customer_data[] = $cust;
        }

        echo json_encode([
            'status' => 'success', 
            'data' => [
                'heatmap' => $heatmap_raw,
                'customers' => $customer_data
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memuat data: ' . $e->getMessage()]);
    }
    exit;
}
?>