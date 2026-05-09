<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require_once '../../../config/database.php'; 

$action = $_REQUEST['action'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if ($action === 'get_report' || $action === 'export_excel') {
    try {
        // 1. QUERY KOMPOSISI PEMBAYARAN GLOBAL (Untuk Omset Sistem)
        $stmt_pay = $pdo->prepare("SELECT payment_method, SUM(total_amount) as total FROM sales_pos WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY payment_method");
        $stmt_pay->execute([$start_date, $end_date]);
        $pay_results = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
        
        $paymentData = ['cash' => 0, 'qris' => 0, 'total' => 0];
        foreach ($pay_results as $row) {
            if ($row['payment_method'] === 'cash') { $paymentData['cash'] += (float)$row['total']; } 
            else { $paymentData['qris'] += (float)$row['total']; }
            $paymentData['total'] += (float)$row['total'];
        }

        // 2. QUERY LAPORAN SHIFT (Logic Hitung Uang Fisik Laci)
        $stmt_shift = $pdo->prepare("
            SELECT 
                sh.id, COALESCE(u.name, 'Admin') as kasir_name, ms.shift_name, 
                sh.start_time, sh.end_time, sh.start_cash, sh.end_cash, sh.status,
                
                (SELECT COALESCE(SUM(amount_paid - change_amount), 0) FROM sales_pos 
                 WHERE payment_method = 'cash' AND created_at >= sh.start_time AND created_at <= COALESCE(sh.end_time, NOW())) as total_cash_in,
                 
                (SELECT COALESCE(SUM(nominal), 0) FROM petty_cash_pos 
                 WHERE shift_history_id = sh.id AND jenis = 'keluar') as total_kas_keluar
                 
            FROM shifts_history_pos sh
            LEFT JOIN users_pos u ON sh.user_id = u.id
            LEFT JOIN master_shifts_pos ms ON sh.shift_id = ms.id
            WHERE DATE(sh.start_time) BETWEEN ? AND ?
            ORDER BY sh.start_time DESC
        ");
        $stmt_shift->execute([$start_date, $end_date]);
        $shifts = $stmt_shift->fetchAll(PDO::FETCH_ASSOC);

        // Kalkulasi matematika untuk tabel
        foreach($shifts as &$s) {
            $s['start_time'] = date('d/m/Y H:i', strtotime($s['start_time']));
            $s['end_time'] = $s['end_time'] ? date('d/m/Y H:i', strtotime($s['end_time'])) : null;
            
            $s['expected_cash'] = $s['start_cash'] + $s['total_cash_in'] - $s['total_kas_keluar'];
            $s['selisih'] = $s['status'] === 'closed' ? ($s['end_cash'] - $s['expected_cash']) : 0;
        }

        // --- RESPONSE JSON UNTUK AJAX ---
        if($action === 'get_report') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'payments' => $paymentData, 'shifts' => $shifts]);
            exit;
        }

        // --- RESPONSE EXCEL UNTUK DOWNLOAD ---
        if($action === 'export_excel') {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Evaluasi_Kasir_".$start_date."_to_".$end_date.".xls");
            
            echo "<table border='1'>";
            echo "<tr><th colspan='9' style='font-size:16px; font-weight:bold; background-color:#e2e8f0;'>Laporan Evaluasi Shift Kasir ($start_date s/d $end_date)</th></tr>";
            echo "<tr style='background-color:#f8fafc;'>
                    <th>Kasir</th><th>Shift</th><th>Waktu Masuk</th><th>Modal Awal</th><th>Omset Cash Masuk</th><th>Petty Cash (Keluar)</th><th>Sistem Seharusnya</th><th>Uang Fisik (Laci)</th><th>Selisih (Minus/Plus)</th>
                  </tr>";
            
            foreach($shifts as $s) {
                echo "<tr>";
                echo "<td>{$s['kasir_name']}</td><td>{$s['shift_name']}</td><td>{$s['start_time']}</td>";
                echo "<td>{$s['start_cash']}</td><td>{$s['total_cash_in']}</td><td>{$s['total_kas_keluar']}</td>";
                echo "<td>{$s['expected_cash']}</td>";
                echo "<td>" . ($s['status'] === 'closed' ? $s['end_cash'] : 'Belum Tutup') . "</td>";
                echo "<td>" . ($s['status'] === 'closed' ? $s['selisih'] : '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            exit;
        }

    } catch (Exception $e) {
        if($action === 'get_report') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } else {
            echo "System Error: " . $e->getMessage();
        }
    }
}
?>