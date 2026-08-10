<?php
require_once 'config/database.php';
try {
    $r1 = $pdo->query("DESCRIBE saved_custom_items_pos")->fetchAll(PDO::FETCH_ASSOC);
    $r2 = $pdo->query("DESCRIBE saved_custom_reguler_pos")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['saved_custom_items_pos' => $r1, 'saved_custom_reguler_pos' => $r2], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
