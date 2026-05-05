<?php
session_start();
// Pastikan path ini benar mengarah ke config database kamu
require_once '../config/database.php'; 

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'login_pos') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi kosong
    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username dan Password wajib diisi!']);
        exit;
    }

    try {
        // PERUBAHAN: Cek user dan rolenya di database MENGGUNAKAN TABEL KHUSUS POS (_pos)
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name 
            FROM users_pos u 
            JOIN roles_pos r ON u.role_id = r.id 
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifikasi kecocokan password hash
        if ($user && password_verify($password, $user['password'])) {
            
            // 1. Simpan ke Session PHP (Sesi Server)
            $_SESSION['pos_user_id'] = $user['id'];
            $_SESSION['pos_role'] = $user['role_name'];
            $_SESSION['pos_name'] = $user['name'];

            // 2. Siapkan data untuk dilempar ke IndexedDB (Sesi Browser Offline)
            $userData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role_name']
            ];

            echo json_encode(['status' => 'success', 'message' => 'Login berhasil!', 'data' => $userData]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username atau Password salah!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>