<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'read') {
    try {
        $stmt = $pdo->query("SELECT * FROM master_shifts_pos WHERE is_active = 1 ORDER BY start_time ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $shift_name = trim($_POST['shift_name'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if (empty($shift_name) || empty($start_time) || empty($end_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi!']);
        exit;
    }

    try {
        if (!empty($id)) {
            // Update
            $stmt = $pdo->prepare("UPDATE master_shifts_pos SET shift_name = ?, start_time = ?, end_time = ? WHERE id = ?");
            $stmt->execute([$shift_name, $start_time, $end_time, $id]);
            $msg = "Shift berhasil diperbarui!";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO master_shifts_pos (shift_name, start_time, end_time) VALUES (?, ?, ?)");
            $stmt->execute([$shift_name, $start_time, $end_time]);
            $msg = "Shift baru berhasil ditambahkan!";
        }
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        // Soft delete agar tidak merusak relasi laporan historis nantinya
        $stmt = $pdo->prepare("UPDATE master_shifts_pos SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Shift berhasil dihapus.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
    }
    exit;
}
?>