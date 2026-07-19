<?php
require 'config/database.php';
$stmt = $pdo->query('SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 20');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
