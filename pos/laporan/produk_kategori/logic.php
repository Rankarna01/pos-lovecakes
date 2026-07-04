<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

$wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

if ($action === 'get_analysis') {
    // Default: Bulan Ini
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    
    // Tambahkan jam agar mencakup transaksi sampai tengah malam di hari terakhir
    $end_date_full = $end_date . ' 23:59:59';
    $start_date_full = $start_date . ' 00:00:00';

    $wh_filter = "";
    $wh_params = [];
    if ($wh_id > 0) {
        $wh_filter = " AND (s.warehouse_id = ? OR (s.warehouse_id IS NULL AND ? = 1)) ";
        $wh_params = [$wh_id, $wh_id];
    }

    try {
        // 1. TOP 5 PRODUK PALING LAKU
        $sql1 = "
            SELECT COALESCE(p.name, sd.custom_name) as product_name, SUM(sd.qty) as total_qty 
            FROM sale_details_pos sd 
            JOIN sales_pos s ON sd.sale_id = s.id 
            LEFT JOIN products p ON sd.product_id = p.id 
            WHERE s.created_at >= ? AND s.created_at <= ? $wh_filter
            GROUP BY product_name 
            ORDER BY total_qty DESC LIMIT 5
        ";
        $stmt_best = $pdo->prepare($sql1);
        $stmt_best->execute(array_merge([$start_date_full, $end_date_full], $wh_params));
        $best_sellers = $stmt_best->fetchAll(PDO::FETCH_ASSOC);

        // 2. TOP 5 PRODUK PALING TIDAK LAKU (Lebih dari 0 tapi paling kecil)
        $sql2 = "
            SELECT COALESCE(p.name, sd.custom_name) as product_name, SUM(sd.qty) as total_qty 
            FROM sale_details_pos sd 
            JOIN sales_pos s ON sd.sale_id = s.id 
            LEFT JOIN products p ON sd.product_id = p.id 
            WHERE s.created_at >= ? AND s.created_at <= ? $wh_filter
            GROUP BY product_name 
            ORDER BY total_qty ASC LIMIT 5
        ";
        $stmt_worst = $pdo->prepare($sql2);
        $stmt_worst->execute(array_merge([$start_date_full, $end_date_full], $wh_params));
        $worst_sellers = $stmt_worst->fetchAll(PDO::FETCH_ASSOC);

        // 3. ANALISA BERDASARKAN KATEGORI
        $sql3 = "
            SELECT COALESCE(p.category, 'Item Custom') as category_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_revenue
            FROM sale_details_pos sd 
            JOIN sales_pos s ON sd.sale_id = s.id 
            LEFT JOIN products p ON sd.product_id = p.id 
            WHERE s.created_at >= ? AND s.created_at <= ? $wh_filter
            GROUP BY category_name 
            ORDER BY total_revenue DESC
        ";
        $stmt_cat = $pdo->prepare($sql3);
        $stmt_cat->execute(array_merge([$start_date_full, $end_date_full], $wh_params));
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        // Hitung total seluruh revenue untuk mencari persentase kategori
        $total_all_revenue = 0;
        foreach($categories as $cat) {
            $total_all_revenue += (float)$cat['total_revenue'];
        }

        echo json_encode([
            'status' => 'success', 
            'data' => [
                'best_sellers' => $best_sellers,
                'worst_sellers' => $worst_sellers,
                'categories' => $categories,
                'total_revenue' => $total_all_revenue
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memuat data: ' . $e->getMessage()]);
    }
    exit;
}
?>