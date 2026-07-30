<?php require 'config/database.php'; try { $stmt = $pdo->query('DESCRIBE users_pos'); print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); } catch(Exception $e) { echo $e->getMessage(); } ?>
