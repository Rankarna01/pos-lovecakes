<?php
require 'config/database.php';

// 1. Backfill custom_name with product name for existing products
$stmt = $pdo->prepare("
    UPDATE sale_details_pos sd
    JOIN products p ON sd.product_id = p.id
    SET sd.custom_name = p.name
    WHERE sd.is_custom = 0 AND (sd.custom_name IS NULL OR sd.custom_name = '')
");
$stmt->execute();
$count1 = $stmt->rowCount();

// 2. Backfill custom_name with 'Produk Dihapus' for products that no longer exist
$stmt2 = $pdo->prepare("
    UPDATE sale_details_pos sd
    LEFT JOIN products p ON sd.product_id = p.id
    SET sd.custom_name = 'Produk Dihapus'
    WHERE sd.is_custom = 0 AND p.id IS NULL AND (sd.custom_name IS NULL OR sd.custom_name = '')
");
$stmt2->execute();
$count2 = $stmt2->rowCount();

echo json_encode([
    'status' => 'success',
    'updated_active_products' => $count1,
    'updated_deleted_products' => $count2
]);
