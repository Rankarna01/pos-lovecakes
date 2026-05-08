<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_summary') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    
    $start_date_full = $start_date . ' 00:00:00';
    $end_date_full = $end_date . ' 23:59:59';

    try {
        // 1. DATA HEATMAP JAM SIBUK (Ekstrak jam dari created_at)
        // Menghitung berapa banyak transaksi yang terjadi pada setiap jam (00 - 23)
        $stmt_hours = $pdo->prepare("
            SELECT HOUR(created_at) as hour_of_day, COUNT(id) as total_trx 
            FROM sales_pos 
            WHERE created_at >= ? AND created_at <= ?
            GROUP BY HOUR(created_at)
            ORDER BY hour_of_day ASC
        ");
        $stmt_hours->execute([$start_date_full, $end_date_full]);
        $heatmap_raw = $stmt_hours->fetchAll(PDO::FETCH_ASSOC);

        // 2. DATA PELANGGAN TERSETIA (CRM)
        // Hitung total kunjungan unik per pelanggan
        $stmt_cust = $pdo->prepare("
            SELECT 
                c.id, 
                c.name as customer_name, 
                COUNT(DISTINCT s.id) as total_visits
            FROM sales_pos s
            JOIN customers_pos c ON s.customer_id = c.id
            WHERE s.created_at >= ? AND s.created_at <= ?
            GROUP BY c.id, c.name
            ORDER BY total_visits DESC
            LIMIT 10
        ");
        $stmt_cust->execute([$start_date_full, $end_date_full]);
        $top_customers = $stmt_cust->fetchAll(PDO::FETCH_ASSOC);

        // Cari tahu barang apa yang paling sering dibeli oleh pelanggan tersebut
        $customer_data = [];
        $stmt_fav_items = $pdo->prepare("
            SELECT COALESCE(p.name, sd.custom_name) as product_name, SUM(sd.qty) as total_qty
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE s.customer_id = ? AND s.created_at >= ? AND s.created_at <= ?
            GROUP BY product_name
            ORDER BY total_qty DESC
            LIMIT 3
        ");

        foreach ($top_customers as $cust) {
            $stmt_fav_items->execute([$cust['id'], $start_date_full, $end_date_full]);
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