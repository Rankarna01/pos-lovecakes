<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_set_cookie_params(0);
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
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name, w.name as store_name, w.code as store_code 
            FROM users_pos u 
            JOIN roles_pos r ON u.role_id = r.id 
            LEFT JOIN warehouses w ON u.warehouse_id = w.id 
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['pos_user_id'] = $user['id'];
            $_SESSION['pos_role'] = $user['role_name'];
            $_SESSION['pos_name'] = $user['name'];
            $_SESSION['pos_warehouse_id'] = $user['warehouse_id'] ?? null;
            $_SESSION['pos_store_name'] = $user['store_name'] ?? 'Semua Outlet (Global)';
            $_SESSION['pos_store_code'] = $user['store_code'] ?? 'GLOBAL';

            // Setup URL
            $is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $folder = $is_localhost ? '/pos-lovecakes/' : '/';
            $full_base_url = $protocol . $_SERVER['HTTP_HOST'] . $folder;

            // 🎯 REDIRECT BERDASARKAN ROLE
            $role_name_lower = strtolower($user['role_name']);
            if (in_array($role_name_lower, ['kasir', 'cashier'])) {
                $redirect_url = $full_base_url . 'pos/kasir/'; 
            } else {
                $redirect_url = $full_base_url . 'pos/dashboard/'; 
            }

            // Data untuk PWA (Offline)
            $userData = [
                'id' => $user['id'], 
                'username' => $user['username'], 
                'name' => $user['name'], 
                'role' => $user['role_name']
            ];

            echo json_encode([
                'status' => 'success', 
                'message' => 'Login berhasil!', 
                'redirect' => $redirect_url,
                'data' => $userData
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username atau Password salah!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid!']);
    exit;
}
?>