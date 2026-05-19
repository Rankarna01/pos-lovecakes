<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/auth.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_methods') {
    $stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY is_active DESC, id DESC");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $methods]);
    exit;
}

if ($action === 'save_method') {
    $id = $_POST['id'] ?? '';
    $type = trim($_POST['type'] ?? 'Cash');
    $name = trim($_POST['name'] ?? '');
    $fee_name = trim($_POST['fee_name'] ?? '');
    $fee_percent = (float)($_POST['fee_percent'] ?? 0);

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama metode wajib diisi!']);
        exit;
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE payment_methods SET type=?, name=?, fee_name=?, fee_percent=? WHERE id=?");
            $stmt->execute([$type, $name, $fee_name, $fee_percent, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Metode berhasil diperbarui!']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO payment_methods (type, name, fee_name, fee_percent) VALUES (?, ?, ?, ?)");
            $stmt->execute([$type, $name, $fee_name, $fee_percent]);
            echo json_encode(['status' => 'success', 'message' => 'Metode berhasil ditambahkan!']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'toggle_status') {
    $id = $_POST['id'] ?? '';
    $is_active = $_POST['is_active'] ?? 1;
    
    $stmt = $pdo->prepare("UPDATE payment_methods SET is_active = ? WHERE id = ?");
    if ($stmt->execute([$is_active, $id])) {
        echo json_encode(['status' => 'success', 'message' => 'Status berhasil diubah!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status.']);
    }
    exit;
}

if ($action === 'delete_method') {
    $id = $_POST['id'] ?? '';
    
    $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Metode berhasil dihapus!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus metode.']);
    }
    exit;
}
?>
