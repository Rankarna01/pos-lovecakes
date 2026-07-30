<?php
require_once 'config/database.php';

try {
    // 1. Tambah kolom ke sales_pos
    $pdo->exec("ALTER TABLE sales_pos 
        ADD COLUMN IF NOT EXISTS cancellation_status ENUM('none', 'partial', 'full') DEFAULT 'none' AFTER payment_status,
        ADD COLUMN IF NOT EXISTS cancelled_amount DECIMAL(10,2) DEFAULT 0.00 AFTER cancellation_status;");

    // 2. Tambah kolom ke sale_details_pos
    $pdo->exec("ALTER TABLE sale_details_pos 
        ADD COLUMN IF NOT EXISTS cancelled_qty INT DEFAULT 0 AFTER qty;");

    // 3. Buat tabel sale_cancellations_pos
    $pdo->exec("CREATE TABLE IF NOT EXISTS sale_cancellations_pos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        cancellation_type ENUM('partial', 'full') NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        is_cash_deducted TINYINT(1) DEFAULT 0,
        reason TEXT NULL,
        authorized_by_pin VARCHAR(6) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (sale_id)
    )");

    // 4. Buat tabel sale_cancellation_items_pos
    $pdo->exec("CREATE TABLE IF NOT EXISTS sale_cancellation_items_pos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cancellation_id INT NOT NULL,
        sale_detail_id INT NOT NULL,
        qty INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        INDEX (cancellation_id),
        INDEX (sale_detail_id)
    )");

    echo "Migration Success\n";

} catch (Exception $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
