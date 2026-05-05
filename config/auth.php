<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['warehouse_id'])) {
        header("Location: ../auth/index.php");
        exit;
    }
}
?>