<?php
session_start();
// Naik 3 folder ke root untuk panggil config
require_once '../../../config/database.php'; 

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_inventory') {
    try {
        $wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;
        $filter_in = ($wh_id > 0) ? " AND (p.warehouse_id = $wh_id OR (p.warehouse_id IS NULL AND $wh_id = 1))" : "";

        // 1. DATA BARANG MASUK (Dari Produksi)
        $stmtIn = $pdo->query("
            SELECT 
                p.created_at AS tanggal, 
                p.invoice_no AS referensi, 
                pr.code AS kode_produk, 
                pr.name AS produk, 
                pd.quantity AS qty, 
                'Masuk' AS tipe,
                'Produksi (Dapur)' AS sumber,
                COALESCE(w.name, CASE WHEN p.warehouse_id = 2 THEN 'Gudang 02' ELSE 'gudang 01' END) AS store_name
            FROM productions p
            JOIN production_details pd ON p.id = pd.production_id
            JOIN products pr ON pd.product_id = pr.id
            LEFT JOIN warehouses w ON p.warehouse_id = w.id
            WHERE p.status != 'batal' AND p.status != 'rejected' $filter_in
            ORDER BY p.created_at DESC LIMIT 300
        ");
        $incoming = $stmtIn->fetchAll(PDO::FETCH_ASSOC);

        // 2. DATA BARANG KELUAR (Dari Penjualan Kasir POS & product_outs)
        $outgoing = [];
        $filter_out_sales = ($wh_id > 0) ? " AND (s.warehouse_id = $wh_id OR (s.warehouse_id IS NULL AND $wh_id = 1))" : "";
        try {
            $stmtSalesOut = $pdo->query("
                SELECT 
                    s.created_at AS tanggal, 
                    s.invoice_no AS referensi, 
                    pr.code AS kode_produk, 
                    pr.name AS produk, 
                    sd.qty AS qty, 
                    'Keluar' AS tipe,
                    'Penjualan Kasir POS' AS sumber,
                    COALESCE(w.name, CASE WHEN s.warehouse_id = 2 THEN 'Gudang 02' ELSE 'gudang 01' END) AS store_name
                FROM sale_details_pos sd
                JOIN sales_pos s ON sd.sale_id = s.id
                JOIN products pr ON sd.product_id = pr.id
                LEFT JOIN warehouses w ON s.warehouse_id = w.id
                WHERE 1=1 $filter_out_sales
                ORDER BY s.created_at DESC LIMIT 300
            ");
            $outgoing = $stmtSalesOut->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        try {
            $filter_po = ($wh_id > 0) ? " AND (po.warehouse_id = $wh_id)" : "";
            $stmtOut = $pdo->query("
                SELECT 
                    po.created_at AS tanggal, 
                    po.invoice_no AS referensi, 
                    pr.code AS kode_produk, 
                    pr.name AS produk, 
                    po.quantity AS qty, 
                    'Keluar' AS tipe,
                    COALESCE(po.notes, 'Pengeluaran Stok') AS sumber,
                    COALESCE(w.name, 'gudang 01') AS store_name
                FROM product_outs po
                JOIN products pr ON po.product_id = pr.id
                LEFT JOIN warehouses w ON po.warehouse_id = w.id
                WHERE 1=1 $filter_po
                ORDER BY po.created_at DESC LIMIT 100
            ");
            $outgoing = array_merge($outgoing, $stmtOut->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {}

        usort($outgoing, function($a, $b) {
            return strtotime($b['tanggal']) - strtotime($a['tanggal']);
        });

        echo json_encode([
            'status' => 'success',
            'data_masuk' => $incoming,
            'data_keluar' => $outgoing
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}
?>