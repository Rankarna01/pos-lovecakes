<?php
require_once '../../config/auth.php';
// Matikan pesan error HTML agar format JSON tidak rusak saat ada warning
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../../config/database.php'; 
require_once '../../config/auth.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';
// Gunakan user_id dari session, default 1 jika sedang testing
$user_id = $_SESSION['pos_user_id'] ?? 1; 

try {
    // 1. CEK STATUS SHIFT SAAT INI
    if ($action === 'check_shift') {
        // Cek apakah kasir ini punya shift yang masih 'open'
        $stmt = $pdo->prepare("SELECT * FROM shifts_history_pos WHERE user_id = ? AND status = 'open' LIMIT 1");
        $stmt->execute([$user_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ambil daftar jam kerja dari master
        $master = $pdo->query("SELECT * FROM master_shifts_pos WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success', 
            'has_open_shift' => !!$shift, 
            'shift_id_active' => $shift ? $shift['id'] : null, 
            'master_shifts' => $master
        ]);
        exit;
    }

    // 2. BUKA SHIFT BARU (MODAL AWAL)
    if ($action === 'open_shift') {
        $shift_id = $_POST['shift_id'] ?? ''; 
        $start_cash = $_POST['start_cash'] ?? 0;
        
        $stmt = $pdo->prepare("INSERT INTO shifts_history_pos (user_id, shift_id, start_time, start_cash, status) VALUES (?, ?, NOW(), ?, 'open')");
        $stmt->execute([$user_id, $shift_id, $start_cash]);
        
        echo json_encode(['status' => 'success', 'message' => 'Shift berhasil dibuka!']); 
        exit;
    }

    // 3. TUTUP SHIFT KASIR (AKHIR KERJA)
    if ($action === 'close_shift') {
        $end_cash = $_POST['end_cash'] ?? 0;
        
        // Tutup semua shift yang masih open untuk user ini (antisipasi error double shift)
        $stmt = $pdo->prepare("UPDATE shifts_history_pos SET status = 'closed', end_time = NOW(), end_cash = ? WHERE user_id = ? AND status = 'open'");
        $stmt->execute([$end_cash, $user_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Kasir Berhasil Ditutup!']); 
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
}
?>