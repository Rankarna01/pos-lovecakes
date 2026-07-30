ALTER TABLE sales_pos 
    ADD COLUMN IF NOT EXISTS cancellation_status ENUM('none', 'partial', 'full') DEFAULT 'none',
    ADD COLUMN IF NOT EXISTS cancelled_amount DECIMAL(10,2) DEFAULT 0.00;


ALTER TABLE sale_details_pos 
    ADD COLUMN IF NOT EXISTS cancelled_qty INT DEFAULT 0;


CREATE TABLE IF NOT EXISTS sale_cancellations_pos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    cancellation_type ENUM('partial', 'full') NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_cash_deducted TINYINT(1) DEFAULT 0,
    reason TEXT NULL,
    authorized_by_pin VARCHAR(6) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (sale_id)
);


CREATE TABLE IF NOT EXISTS sale_cancellation_items_pos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cancellation_id INT NOT NULL,
    sale_detail_id INT NOT NULL,
    qty INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    INDEX (cancellation_id),
    INDEX (sale_detail_id)
);



https://domain-toko.com/pos-lovecakes/migrate_void.php
