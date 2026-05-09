<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// 1. AMBIL DAFTAR TRANSAKSI YANG MASIH DP
if ($action === 'get_piutang') {
    try {
        $stmt = $pdo->query("
            SELECT s.*, c.name as customer_name, c.phone 
            FROM sales_pos s 
            LEFT JOIN customers_pos c ON s.customer_id = c.id 
            WHERE s.payment_status = 'dp' 
            ORDER BY s.created_at DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. PROSES PELUNASAN
if ($action === 'settle_payment') {
    $sale_id = $_POST['sale_id'] ?? 0;
    $pay_amount = (float)($_POST['pay_amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';

    try {
        $pdo->beginTransaction();

        // Cek data transaksi
        $stmt_check = $pdo->prepare("SELECT total_amount, dp_amount, amount_paid, change_amount FROM sales_pos WHERE id = ? AND payment_status = 'dp'");
        $stmt_check->execute([$sale_id]);
        $sale = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$sale) {
            throw new Exception("Data transaksi tidak ditemukan atau sudah lunas.");
        }

        $sisa_tagihan = $sale['total_amount'] - $sale['dp_amount'];

        if ($pay_amount < $sisa_tagihan) {
            throw new Exception("Nominal pembayaran kurang dari sisa tagihan!");
        }

        $kembalian_baru = $pay_amount - $sisa_tagihan;
        $total_uang_diterima = $sale['dp_amount'] + $pay_amount;

        // Update status jadi LUNAS
        $stmt_update = $pdo->prepare("
            UPDATE sales_pos 
            SET payment_status = 'lunas', 
                amount_paid = ?, 
                change_amount = ?,
                payment_method = ? -- Update metode bayar pelunasan (misal DP cash, lunas QRIS)
            WHERE id = ?
        ");
        $stmt_update->execute([$total_uang_diterima, $kembalian_baru, $payment_method, $sale_id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pelunasan berhasil!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>