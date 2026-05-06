<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'read') {
    try {
        $stmt = $pdo->query("SELECT * FROM vouchers_pos ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $voucher_code = strtoupper(trim($_POST['voucher_code']));
    $voucher_name = trim($_POST['voucher_name']);
    $discount_type = $_POST['discount_type'];
    $discount_amount = (float)$_POST['discount_amount'];
    $min_purchase = (float)$_POST['min_purchase'];
    $valid_from = !empty($_POST['valid_from']) ? $_POST['valid_from'] : null;
    $valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
    $max_usage = (int)$_POST['max_usage'];
    $is_active = $_POST['is_active'] === 'true' ? 1 : 0;

    try {
        if (empty($id)) {
            // Cek duplikasi kode
            $cek = $pdo->prepare("SELECT id FROM vouchers_pos WHERE voucher_code = ?");
            $cek->execute([$voucher_code]);
            if ($cek->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Kode Voucher sudah digunakan!']);
                exit;
            }

            // INSERT DATA BARU
            $stmt = $pdo->prepare("
                INSERT INTO vouchers_pos (voucher_code, voucher_name, discount_type, discount_amount, min_purchase, valid_from, valid_until, max_usage, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$voucher_code, $voucher_name, $discount_type, $discount_amount, $min_purchase, $valid_from, $valid_until, $max_usage, $is_active]);
            echo json_encode(['status' => 'success', 'message' => 'Voucher berhasil ditambahkan!']);
        } else {
            // UPDATE DATA
            $stmt = $pdo->prepare("
                UPDATE vouchers_pos 
                SET voucher_code=?, voucher_name=?, discount_type=?, discount_amount=?, min_purchase=?, valid_from=?, valid_until=?, max_usage=?, is_active=? 
                WHERE id=?
            ");
            $stmt->execute([$voucher_code, $voucher_name, $discount_type, $discount_amount, $min_purchase, $valid_from, $valid_until, $max_usage, $is_active, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Voucher berhasil diperbarui!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM vouchers_pos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Voucher berhasil dihapus!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'toggle_status') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? 0;
    try {
        $stmt = $pdo->prepare("UPDATE vouchers_pos SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Status voucher diperbarui!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update status: ' . $e->getMessage()]);
    }
    exit;
}
?>