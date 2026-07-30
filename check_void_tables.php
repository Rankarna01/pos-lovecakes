<?php
require_once 'config/database.php';
try {
    $result = [];
    $tables = ['sales_pos', 'sale_details_pos', 'sale_cancellations_pos', 'sale_cancellation_items_pos'];
    foreach ($tables as $t) {
        try {
            $stmt = $pdo->query("DESCRIBE $t");
            $result[$t] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        } catch (Exception $e) {
            $result[$t] = 'TABLE NOT FOUND: ' . $e->getMessage();
        }
    }
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
