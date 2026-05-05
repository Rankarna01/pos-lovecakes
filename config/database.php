<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Sesuaikan
$dbname = 'sim_produksi_kue'; // Sesuaikan

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>