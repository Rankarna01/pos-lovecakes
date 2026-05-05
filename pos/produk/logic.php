<?php
session_start();
// Pastikan koneksi database mengarah ke database "sim-kue"
require_once '../../config/database.php'; 

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'read_produk') {
    try {
        // Query Super Lengkap (Tarik Harga Online + Hitung Stok Dinamis)
        $stmt = $pdo->query("
            SELECT 
                p.id, 
                p.code, 
                p.name, 
                p.category, 
                p.image, 
                p.modal_price, 
                p.price AS offline_price, 
                p.online_price, 
                COALESCE((
                    SELECT SUM(pd.quantity)
                    FROM production_details pd
                    JOIN productions pr ON pr.id = pd.production_id
                    WHERE pd.product_id = p.id AND pr.status = 'masuk_gudang'
                ), 0) AS stock 
            FROM products p 
            ORDER BY p.name ASC
        ");
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