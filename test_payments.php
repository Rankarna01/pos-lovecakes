<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT * FROM payment_methods");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query("SELECT sp.payment_method, pm.type, SUM(sp.amount) as total 
            FROM sale_payments_pos sp
            JOIN sales_pos s ON sp.sale_id = s.id
            LEFT JOIN payment_methods pm ON sp.payment_method = pm.name
            GROUP BY sp.payment_method, pm.type");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
