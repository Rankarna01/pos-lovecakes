<?php
// pos/cetak_barcode/logic.php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_products') {
    try {
        // Ambil produk yang punya kode SKU (tidak kosong)
        $stmt = $pdo->query("
            SELECT id, code, name, price, category 
            FROM products 
            WHERE code IS NOT NULL AND code != ''
            ORDER BY name ASC
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $products
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
?>