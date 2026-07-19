<?php
require 'config/database.php';
$stmt = $pdo->query('DESCRIBE inventory_history_pos');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
