<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require_once '../../../../config/database.php'; 

$action = $_REQUEST['action'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if ($action === 'get_report' || $action === 'export_excel') {
    try {
        // 1. Penjualan per Kategori
        $stmt_cat = $pdo->prepare("
            SELECT COALESCE(p.category, 'Uncategorized') as category_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY p.category
            ORDER BY total_amount DESC
        ");
        $stmt_cat->execute([$start_date, $end_date]);
        $sales_by_category = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        // 2. Penjualan per Barang (Terlaris)
        $stmt_item = $pdo->prepare("
            SELECT COALESCE(p.name, sd.custom_name) as item_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY COALESCE(p.name, sd.custom_name)
            ORDER BY total_amount DESC
            LIMIT 50
        ");
        $stmt_item->execute([$start_date, $end_date]);
        $sales_by_item = $stmt_item->fetchAll(PDO::FETCH_ASSOC);

        // --- RESPONSE JSON UNTUK AJAX ---
        if($action === 'get_report') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success', 
                'sales_by_category' => $sales_by_category,
                'sales_by_item' => $sales_by_item
            ]);
            exit;
        }

        // --- RESPONSE EXCEL UNTUK DOWNLOAD ---
        if($action === 'export_excel') {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Analisis_Produk_".$start_date."_to_".$end_date.".xls");
            
            echo "<table border='1'>";
            echo "<tr><th colspan='3' style='font-size:16px; font-weight:bold; background-color:#e2e8f0;'>Penjualan per Kategori ($start_date s/d $end_date)</th></tr>";
            echo "<tr><th>Kategori</th><th>Total Qty</th><th>Total Amount</th></tr>";
            foreach($sales_by_category as $cat) {
                echo "<tr><td>{$cat['category_name']}</td><td>{$cat['total_qty']}</td><td>{$cat['total_amount']}</td></tr>";
            }

            echo "<tr><td colspan='3'></td></tr>";

            echo "<tr><th colspan='3' style='font-size:16px; font-weight:bold; background-color:#e2e8f0;'>Barang Terlaris</th></tr>";
            echo "<tr><th>Nama Barang</th><th>Total Qty</th><th>Total Amount</th></tr>";
            foreach($sales_by_item as $item) {
                echo "<tr><td>{$item['item_name']}</td><td>{$item['total_qty']}</td><td>{$item['total_amount']}</td></tr>";
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
