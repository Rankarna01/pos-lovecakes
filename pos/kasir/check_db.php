<?php
require '../../config/database.php';
try {
    $stmt = $pdo->query("SELECT c.created_at FROM saved_custom_items_pos c LIMIT 1");
    if($stmt) {
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        print_r($pdo->errorInfo());
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
