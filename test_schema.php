<?php
require 'config/database.php';
$stmt = $pdo->query("DESCRIBE sale_details_pos");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
