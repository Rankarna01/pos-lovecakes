<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'scan_barcode') {
    $code = trim($_GET['code'] ?? '');
    try {
        $stmt = $pdo->prepare("SELECT id, code, name, category, stock FROM products WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            echo json_encode(['status' => 'success', 'data' => $product]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Barcode/SKU tidak ditemukan di database.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_opname') {
    $product_id = $_POST['product_id'] ?? 0;
    $system_stock = (int)($_POST['system_stock'] ?? 0);
    $actual_stock = (int)($_POST['actual_stock'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'] ?? 1; // Default jika user_id session kosong saat testing

    $difference = $actual_stock - $system_stock;

    if ($difference == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada selisih stok yang perlu disesuaikan.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Update Stok di Master Product
        $stmt_update = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt_update->execute([$actual_stock, $product_id]);

        // 2. Catat Log Mutasi
        $type = $difference > 0 ? 'Masuk' : 'Keluar';
        $qty_mutasi = abs($difference);
        $ref_no = 'OPN-' . date('YmdHis');

        $stmt_history = $pdo->prepare("INSERT INTO inventory_history_pos (product_id, type, qty, reference_no, source) VALUES (?, ?, ?, ?, ?)");
        $stmt_history->execute([$product_id, $type, $qty_mutasi, $ref_no, 'Opname: ' . $notes]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Penyesuaian stok berhasil disimpan!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan opname: ' . $e->getMessage()]);
    }
    exit;
}
?>