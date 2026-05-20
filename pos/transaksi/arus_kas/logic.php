<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require_once '../../../config/database.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'get_arus_kas') {
    $start_date = $_GET['start_date'] ?? date('Y-m-d');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    try {
        // Ambil summary Pemasukan
        $stmt_in = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) as total FROM petty_cash_pos WHERE jenis = 'masuk' AND DATE(created_at) BETWEEN ? AND ?");
        $stmt_in->execute([$start_date, $end_date]);
        $total_in = $stmt_in->fetchColumn();

        // Ambil summary Pengeluaran
        $stmt_out = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) as total FROM petty_cash_pos WHERE jenis = 'keluar' AND DATE(created_at) BETWEEN ? AND ?");
        $stmt_out->execute([$start_date, $end_date]);
        $total_out = $stmt_out->fetchColumn();

        // Ambil mutasi detail
        $stmt_list = $pdo->prepare("
            SELECT p.*, COALESCE(u.name, 'Sistem') as user_name, s.shift_name
            FROM petty_cash_pos p
            LEFT JOIN users_pos u ON p.user_id = u.id
            LEFT JOIN shifts_history_pos sh ON p.shift_history_id = sh.id
            LEFT JOIN master_shifts_pos s ON sh.shift_id = s.id
            WHERE DATE(p.created_at) BETWEEN ? AND ?
            ORDER BY p.created_at DESC
        ");
        $stmt_list->execute([$start_date, $end_date]);
        $history = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'summary' => [
                'masuk' => (float)$total_in,
                'keluar' => (float)$total_out
            ],
            'data' => $history
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'save_arus_kas') {
    $jenis = $_POST['jenis'] ?? ''; // 'masuk' / 'keluar'
    $nominal = $_POST['nominal'] ?? 0;
    $keterangan = $_POST['keterangan'] ?? '';
    
    $user_id = $_SESSION['pos_user_id'] ?? 0;
    
    // Cek shift aktif
    $stmt_shift = $pdo->prepare("SELECT id FROM shifts_history_pos WHERE user_id = ? AND status = 'open' ORDER BY start_time DESC LIMIT 1");
    $stmt_shift->execute([$user_id]);
    $shift = $stmt_shift->fetch(PDO::FETCH_ASSOC);
    $shift_history_id = $shift ? $shift['id'] : 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO petty_cash_pos (user_id, shift_history_id, jenis, nominal, keterangan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $shift_history_id, $jenis, $nominal, $keterangan]);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Catatan arus kas berhasil disimpan!']);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
?>
