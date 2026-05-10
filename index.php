<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
$base = $protocol . $_SERVER['HTTP_HOST'] . $folder;

// CEK SESI KHUSUS POS! Bukan warehouse_id lagi
if (!isset($_SESSION['pos_user_id'])) {
    header("Location: " . $base . "auth/");
    exit;
}
?>