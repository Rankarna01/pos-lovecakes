<?php
require_once 'config/database.php';
try {
    $result = [];
    $tables = ['shifts_history_pos', 'supervisor_pins_pos'];
    foreach ($tables as $t) {
        $stmt = $pdo->query("DESCRIBE $t");
        $result[$t] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
