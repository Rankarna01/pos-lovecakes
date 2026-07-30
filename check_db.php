<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Sesuaikan
$dbname = 'sim-kue'; // Sesuaikan

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        if (in_array($table, ['transactions', 'transaction_items', 'shifts', 'users', 'pos_transactions', 'pos_transaction_items', 'pos_shifts', 'pos_users'])) {
            echo "TABLE: $table\n";
            $stmt2 = $pdo->query("DESCRIBE $table");
            $cols = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                echo "  - {$col['Field']} ({$col['Type']})\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
