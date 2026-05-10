<?php
// Sama persis seperti di sistem produksi RotiKu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';

if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder);
}

// JIKA BELUM LOGIN -> TENDANG KE FOLDER AUTH
if (!isset($_SESSION['pos_user_id']) || empty($_SESSION['pos_user_id'])) {
    header("Location: " . BASE_URL . "auth/");
    exit();
}

// (Kamu bisa tambahkan fungsi checkRole atau hasPermission di bawah ini nanti)
?>