<?php
require_once __DIR__ . '/env.php';

// Cek apakah lingkungan lokal atau server produksi
$is_local_host = (
    isset($_SERVER['HTTP_HOST']) && 
    (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)
);

if ($is_local_host) {
    // 💻 Konfigurasi Localhost (XAMPP Komputer)
    $host   = env('DB_HOST', 'localhost');
    $user   = env('DB_USER', 'root');
    $pass   = env('DB_PASS', '');
    $dbname = env('DB_NAME', 'sim-kue');
} else {
    // 🌐 Konfigurasi Server Produksi (Hostinger / cPanel)
    $host   = env('DB_HOST', 'localhost');
    $user   = env('DB_USER', 'u672726995_lovecakes21');
    $pass   = env('DB_PASS', 'Randy2005_');
    $dbname = env('DB_NAME', 'u672726995_lovecakes21');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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
    // Tangani error format JSON jika dipanggil oleh endpoint AJAX
    if ((isset($_POST['action']) || isset($_GET['action'])) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        die(json_encode([
            'status' => 'error', 
            'message' => 'Koneksi database gagal di server: ' . $e->getMessage()
        ]));
    } else {
        die("Koneksi gagal: " . $e->getMessage());
    }
}
?>