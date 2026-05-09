<?php
session_start();
// Pastikan koneksi database mengarah ke database "sim-kue"
require_once '../../config/database.php'; 

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$warehouse_id = $_GET['warehouse_id'] ?? ''; // Tangkap request filter toko

if ($action === 'read_produk') {
    try {
        // Query Super Cepat: Tarik Harga Online + Baca Stok Langsung dari Tabel
        $query = "
            SELECT 
                id, 
                code, 
                name, 
                category, 
                image, 
                modal_price, 
                price AS offline_price, 
                online_price, 
                stock,
                warehouse_id
            FROM products 
            WHERE 1=1
        ";
        
        $params = [];

        // Logic Filtering Toko / Warehouse
        if (!empty($warehouse_id)) {
            // Menampilkan produk sesuai ID Toko, 
            // ATAU tampilkan juga yang NULL agar produk lama tidak gaib sebelum kamu update datanya
            $query .= " AND (warehouse_id = ? OR warehouse_id IS NULL)";
            $params[] = $warehouse_id;
        }

        $query .= " ORDER BY name ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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