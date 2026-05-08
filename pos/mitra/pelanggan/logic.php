<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'read') {
    try {
        $stmt = $pdo->query("SELECT * FROM customers_pos ORDER BY points DESC, name ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $points = (int)($_POST['points'] ?? 0);

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama pelanggan wajib diisi!']); 
        exit;
    }

    try {
        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO customers_pos (name, phone, address, points) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $address, $points]);
            echo json_encode(['status' => 'success', 'message' => 'Pelanggan baru berhasil didaftarkan!']);
        } else {
            $stmt = $pdo->prepare("UPDATE customers_pos SET name=?, phone=?, address=?, points=? WHERE id=?");
            $stmt->execute([$name, $phone, $address, $points, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Data pelanggan diperbarui!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM customers_pos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Data pelanggan telah dihapus permanen!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
    exit;
}
?>