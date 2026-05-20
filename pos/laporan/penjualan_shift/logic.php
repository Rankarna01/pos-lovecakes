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
        $stmt_pay = $pdo->prepare("
            SELECT sp.payment_method, pm.type, SUM(sp.amount) as total 
            FROM sale_payments_pos sp
            LEFT JOIN payment_methods pm ON sp.payment_method = pm.name
            WHERE DATE(sp.created_at) BETWEEN ? AND ? 
            GROUP BY sp.payment_method, pm.type
        ");
        $stmt_pay->execute([$start_date, $end_date]);
        $pay_results = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
        
        $paymentData = ['cash' => 0, 'qris' => 0, 'total' => 0];
        foreach ($pay_results as $row) {
            if ($row['type'] === 'Cash' || strtolower($row['payment_method']) === 'cash') { 
                $paymentData['cash'] += (float)$row['total']; 
            } else { 
                $paymentData['qris'] += (float)$row['total']; 
            }
            $paymentData['total'] += (float)$row['total'];
        }

        // 2. QUERY LAPORAN SHIFT (Logic Hitung Uang Fisik Laci)
        $stmt_shift = $pdo->prepare("
            SELECT 
                sh.id, COALESCE(u.name, 'Admin') as kasir_name, ms.shift_name, 
                sh.start_time, sh.end_time, sh.start_cash, sh.end_cash, sh.status,
                
                (SELECT COALESCE(SUM(sp.amount), 0) FROM sale_payments_pos sp
                 LEFT JOIN payment_methods pm ON sp.payment_method = pm.name
                 WHERE (pm.type = 'Cash' OR sp.payment_method = 'cash' OR sp.payment_method = 'Cash') 
                 AND sp.created_at >= sh.start_time AND sp.created_at <= COALESCE(sh.end_time, NOW())) as total_cash_in,
                 
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

        // ==========================================
        // 3. TAMBAHAN: DATA PENJUALAN LENGKAP
        // ==========================================

        // a. Penjualan berdasarkan TF / Cash (Detail Payment Breakdown)
        $stmt_pay_breakdown = $pdo->prepare("
            SELECT payment_method, SUM(amount) as total_amount
            FROM sale_payments_pos 
            WHERE DATE(created_at) BETWEEN ? AND ? 
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ");
        $stmt_pay_breakdown->execute([$start_date, $end_date]);
        $payment_breakdown = $stmt_pay_breakdown->fetchAll(PDO::FETCH_ASSOC);

        // b. Penjualan per Kategori
        $stmt_cat = $pdo->prepare("
            SELECT COALESCE(p.category, 'Uncategorized') as category_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY p.category
            ORDER BY total_amount DESC
        ");
        $stmt_cat->execute([$start_date, $end_date]);
        $sales_by_category = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        // c. Penjualan per Barang (Terlaris)
        $stmt_item = $pdo->prepare("
            SELECT COALESCE(p.name, sd.custom_name) as item_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
            FROM sale_details_pos sd
            JOIN sales_pos s ON sd.sale_id = s.id
            LEFT JOIN products p ON sd.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY COALESCE(p.name, sd.custom_name)
            ORDER BY total_amount DESC
            LIMIT 20
        ");
        $stmt_item->execute([$start_date, $end_date]);
        $sales_by_item = $stmt_item->fetchAll(PDO::FETCH_ASSOC);

        // d. Penjualan per Konsumen
        $stmt_cust = $pdo->prepare("
            SELECT COALESCE(c.name, 'Pelanggan Umum') as customer_name, COUNT(s.id) as total_transactions, SUM(s.total_amount) as total_spent
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY s.customer_id
            ORDER BY total_spent DESC
            LIMIT 20
        ");
        $stmt_cust->execute([$start_date, $end_date]);
        $sales_by_customer = $stmt_cust->fetchAll(PDO::FETCH_ASSOC);

        // e. Pembayaran DP dan Pelunasan
        $stmt_dp = $pdo->prepare("
            SELECT payment_type, SUM(amount) as total_amount, COUNT(id) as total_transactions
            FROM sale_payments_pos
            WHERE payment_type IN ('dp', 'pelunasan') AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY payment_type
        ");
        $stmt_dp->execute([$start_date, $end_date]);
        $dp_pelunasan = $stmt_dp->fetchAll(PDO::FETCH_ASSOC);

        // f. Rincian Detail Penjualan (List Transaksi)
        $stmt_details = $pdo->prepare("
            SELECT s.invoice_no, s.created_at, COALESCE(c.name, 'Pelanggan Umum') as customer_name, s.payment_status, s.payment_method, s.total_amount
            FROM sales_pos s
            LEFT JOIN customers_pos c ON s.customer_id = c.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            ORDER BY s.created_at DESC
        ");
        $stmt_details->execute([$start_date, $end_date]);
        $sales_details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

        // --- RESPONSE JSON UNTUK AJAX ---
        if($action === 'get_report') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success', 
                'payments' => $paymentData, 
                'shifts' => $shifts,
                
                // Tambahan Data Lengkap
                'payment_breakdown' => $payment_breakdown,
                'sales_by_category' => $sales_by_category,
                'sales_by_item' => $sales_by_item,
                'sales_by_customer' => $sales_by_customer,
                'dp_pelunasan' => $dp_pelunasan,
                'sales_details' => $sales_details
            ]);
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