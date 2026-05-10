<?php
// MATIKAN ERROR HTML UNTUK PRODUCTION (Agar JSON tidak rusak)
ini_set('display_errors', 0); 
error_reporting(0); 

// KUNCI UTAMA: Atur agar session PHP langsung mati ketika browser disilang (0)
session_set_cookie_params(0);
session_start();

require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username dan Password wajib diisi!']);
        exit;
    }

    try {
        // Ambil data user dari database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $login_success = false;

            // 1. Cek password dengan Bcrypt (Standar Keamanan Tinggi)
            if (password_verify($password, $user['password'])) {
                $login_success = true;
            } 
            // 2. Transisi dari Plain Text ke Bcrypt (Jika ada akun lama yg belum di-hash)
            else if ($password === $user['password']) {
                $login_success = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$newHash, $user['id']]);
            }

            if ($login_success) {
                // Daftarkan Sesi PHP Khusus POS Kasir
                $_SESSION['pos_user_id'] = $user['id'];
                $_SESSION['pos_name'] = $user['name'];
                $_SESSION['pos_role'] = $user['role']; 

                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Login Berhasil',
                    'data' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Password yang Anda masukkan salah!']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username tidak ditemukan!']);
        }
    } catch (PDOException $e) {
        // Jangan cetak $e->getMessage() di production agar nama tabel aman
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server database.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
}
?>