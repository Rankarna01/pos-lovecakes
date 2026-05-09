<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
ob_clean();

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'get_master_shifts') {
        $shifts = $pdo->query("SELECT id, shift_name FROM master_shifts_pos WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $shifts]);
        exit;
    }

    if ($action === 'get_accounting') {
        $start = $_GET['start'] ?? date('Y-m-d');
        $end = $_GET['end'] ?? date('Y-m-d');
        $shift_id = $_GET['shift_id'] ?? '';

        // Base Query untuk filter Shift atau Non-Shift
        $salesJoin = ""; $salesWhere = "DATE(s.created_at) BETWEEN :start AND :end";
        $pettyJoin = ""; $pettyWhere = "DATE(p.created_at) BETWEEN :start AND :end";

        // Logic Presisi: Jika difilter per-shift, kita join ke tabel History Shift
        if (!empty($shift_id)) {
            $salesJoin = "JOIN shifts_history_pos sh ON s.created_at >= sh.start_time AND s.created_at <= COALESCE(sh.end_time, NOW())";
            $salesWhere = "sh.shift_id = :shift_id AND DATE(sh.start_time) BETWEEN :start AND :end";

            $pettyJoin = "JOIN shifts_history_pos sh ON p.shift_history_id = sh.id";
            $pettyWhere = "sh.shift_id = :shift_id AND DATE(sh.start_time) BETWEEN :start AND :end";
        }

        // 1. AMBIL JURNAL PENDAPATAN (SALES)
        $stmt_sales = $pdo->prepare("
            SELECT 'Pendapatan' as tipe, s.created_at as raw_date, DATE_FORMAT(s.created_at, '%d %b %Y %H:%i') as tanggal, 
                   CONCAT('Penjualan POS #', s.invoice_no) as keterangan, 'Kas Toko / Bank' as akun, 
                   s.total_amount as debit, 0 as kredit 
            FROM sales_pos s $salesJoin 
            WHERE $salesWhere
        ");
        $stmt_sales->bindValue(':start', $start); $stmt_sales->bindValue(':end', $end);
        if(!empty($shift_id)) $stmt_sales->bindValue(':shift_id', $shift_id);
        $stmt_sales->execute();
        $sales = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);

        // 2. AMBIL JURNAL BEBAN (PETTY CASH KELUAR)
        $stmt_petty = $pdo->prepare("
            SELECT 'Beban' as tipe, p.created_at as raw_date, DATE_FORMAT(p.created_at, '%d %b %Y %H:%i') as tanggal, 
                   p.keterangan as keterangan, 'Beban Operasional' as akun, 
                   0 as debit, p.nominal as kredit 
            FROM petty_cash_pos p $pettyJoin 
            WHERE p.jenis = 'keluar' AND $pettyWhere
        ");
        $stmt_petty->bindValue(':start', $start); $stmt_petty->bindValue(':end', $end);
        if(!empty($shift_id)) $stmt_petty->bindValue(':shift_id', $shift_id);
        $stmt_petty->execute();
        $expenses = $stmt_petty->fetchAll(PDO::FETCH_ASSOC);

        // 3. GABUNGKAN JURNAL & URUTKAN BERDASARKAN WAKTU
        $journal = array_merge($sales, $expenses);
        usort($journal, function($a, $b) {
            return strtotime($b['raw_date']) - strtotime($a['raw_date']); // Descending (Terbaru di atas)
        });

        // 4. HITUNG SUMMARY
        $total_income = 0; $total_expense = 0;
        foreach ($journal as $j) {
            $total_income += $j['debit'];
            $total_expense += $j['kredit'];
        }
        $net_profit = $total_income - $total_expense;

        echo json_encode([
            'status' => 'success', 
            'summary' => ['income' => $total_income, 'expense' => $total_expense, 'net_profit' => $net_profit],
            'journal' => $journal
        ]);
        exit;
    }

} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'CRASH PHP: ' . $e->getMessage()]);
    exit;
}
?>