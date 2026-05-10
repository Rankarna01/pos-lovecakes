<?php
ini_set('display_errors', 0);
error_reporting(0);
session_set_cookie_params(0); // Mati saat browser tutup
session_start();

require_once '../config/database.php'; 

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

if ($action === 'login_pos') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username dan Password wajib diisi!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users_pos u JOIN roles_pos r ON u.role_id = r.id WHERE u.username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // 1. Simpan ke Session PHP
            $_SESSION['pos_user_id'] = $user['id'];
            $_SESSION['pos_role'] = $user['role_name'];
            $_SESSION['pos_name'] = $user['name'];

            // 2. Tentukan Rute Pintar ke Dashboard
            $is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $folder = $is_localhost ? '/pos-lovecakes/' : '/';
            $full_base_url = $protocol . $_SERVER['HTTP_HOST'] . $folder;
            
            $redirect_url = $full_base_url . 'pos/dashboard/'; // Mengarah ke Dashboard

            // Kembalikan JSON redirect
            echo json_encode(['status' => 'success', 'message' => 'Login berhasil!', 'redirect' => $redirect_url]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username atau Password salah!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>