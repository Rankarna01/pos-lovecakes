<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// 1. AMBIL DAFTAR TRANSAKSI YANG MASIH DP DENGAN FILTER
if ($action === 'get_piutang') {
    $search = $_GET['search'] ?? '';
    $time_range = $_GET['time_range'] ?? '';
    
    try {
        $query = "
            SELECT s.*, c.name as customer_name, c.phone 
            FROM sales_pos s 
            LEFT JOIN customers_pos c ON s.customer_id = c.id 
            WHERE s.payment_status = 'dp' 
        ";
        $params = [];

        // Filter Pencarian (Invoice / Nama Pelanggan)
        if (!empty($search)) {
            $query .= " AND (s.invoice_no LIKE ? OR c.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        // Filter Waktu Transaksi
        if ($time_range === 'today') {
            $query .= " AND DATE(s.created_at) = CURRENT_DATE()";
        } elseif ($time_range === 'week') {
            $query .= " AND YEARWEEK(s.created_at, 1) = YEARWEEK(CURRENT_DATE(), 1)";
        } elseif ($time_range === 'month') {
            $query .= " AND MONTH(s.created_at) = MONTH(CURRENT_DATE()) AND YEAR(s.created_at) = YEAR(CURRENT_DATE())";
        }

        $query .= " ORDER BY s.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. PROSES PELUNASAN (Logika ini tetap 100% sama dengan aslimu)
if ($action === 'settle_payment') {
    $sale_id = $_POST['sale_id'] ?? 0;
    $pay_amount = (float)($_POST['pay_amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';

    try {
        $pdo->beginTransaction();

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

        $stmt_update = $pdo->prepare("
            UPDATE sales_pos 
            SET payment_status = 'lunas', 
                amount_paid = ?, 
                change_amount = ?,
                payment_method = ?,
                settled_at = NOW()
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