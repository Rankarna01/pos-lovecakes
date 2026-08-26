<?php
require_once __DIR__ . '/env.php';

$host   = env('DB_HOST', 'localhost');
$user   = env('DB_USER', 'u672726995_lovecakes21');
$pass   = env('DB_PASS', 'Randy2005_');
$dbname = env('DB_NAME', 'u672726995_lovecakes21');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_mutations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mutation_no VARCHAR(50) NOT NULL,
            product_id INT NOT NULL,
            from_warehouse_id INT NOT NULL,
            to_warehouse_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            notes TEXT NULL,
            created_by INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $e) {}
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>