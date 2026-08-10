<?php
session_start();
// Pastikan koneksi database mengarah ke database "sim-kue"
require_once '../../config/database.php'; 

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$warehouse_id = $_GET['warehouse_id'] ?? ''; // Tangkap request filter toko

if ($action === 'read_produk') {
    try {
        $wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : (!empty($warehouse_id) ? intval($warehouse_id) : 0);

        // Query: Tarik Harga + Baca Stok Langsung dari Tabel multi-tenant
        $query = "
            SELECT 
                p.id, 
                p.code, 
                p.name, 
                p.category, 
                p.image, 
                p.modal_price, 
                p.price AS offline_price, 
                p.online_price, 
                COALESCE(p.is_custom_price, 0) AS is_custom_price,
                " . ($wh_id > 0 ? "COALESCE(pws.stock, p.stock)" : "p.stock") . " AS stock,
                COALESCE(w.name, CASE WHEN p.warehouse_id = 2 THEN 'Store 02' ELSE 'Store 01' END) AS store_name,
                p.warehouse_id
            FROM products p
            " . ($wh_id > 0 ? "LEFT JOIN product_warehouse_stocks pws ON p.id = pws.product_id AND pws.warehouse_id = $wh_id" : "") . "
            LEFT JOIN warehouses w ON " . ($wh_id > 0 ? "$wh_id = w.id" : "p.warehouse_id = w.id") . "
            WHERE 1=1
        ";
        
        $params = [];

        // Logic Filtering Toko / Warehouse
        if ($wh_id > 0) {
            $query .= " AND (p.warehouse_id = ? OR p.warehouse_id IS NULL OR ? = 1)";
            $params[] = $wh_id;
            $params[] = $wh_id;
        }

        $query .= " ORDER BY p.name ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as &$prod) {
            $prod['store_name'] = str_ireplace('gudang', 'Store', $prod['store_name']);
        }
        unset($prod);

        echo json_encode([
            'status' => 'success',
            'data' => $products,
            'total' => count($products)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}
?>