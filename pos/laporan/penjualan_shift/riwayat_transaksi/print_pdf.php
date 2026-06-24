<?php
session_start();
require_once '../../../../config/database.php';

$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. Penjualan per Konsumen (Top Pelanggan)
$stmt_cust = $pdo->prepare("
    SELECT COALESCE(c.name, 'Pelanggan Umum') as customer_name, COUNT(s.id) as total_transactions, SUM(s.total_amount) as total_spent
    FROM sales_pos s
    LEFT JOIN customers_pos c ON s.customer_id = c.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY s.customer_id
    ORDER BY total_spent DESC
    LIMIT 50
");
$stmt_cust->execute([$start_date, $end_date]);
$sales_by_customer = $stmt_cust->fetchAll(PDO::FETCH_ASSOC);

// 2. Rincian Detail Penjualan (List Transaksi)
$stmt_details = $pdo->prepare("
    SELECT s.invoice_no, s.created_at, COALESCE(c.name, 'Pelanggan Umum') as customer_name, s.payment_status, s.payment_method, s.total_amount
    FROM sales_pos s
    LEFT JOIN customers_pos c ON s.customer_id = c.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    ORDER BY s.created_at DESC
");
$stmt_details->execute([$start_date, $end_date]);
$sales_details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

function formatRp($angka) { return number_format((float)$angka, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Riwayat Transaksi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0 0 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-weight: bold;">🖨️ Cetak</button>
        <button onclick="window.close()" style="padding: 10px 20px;">Tutup</button>
    </div>

    <div class="header">
        <h2>Laporan Riwayat Transaksi & Pelanggan</h2>
        <p>Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></p>
    </div>

    <h3>1. Top Pelanggan</h3>
    <table>
        <tr><th>No.</th><th>Nama Pelanggan</th><th class="text-center">Total Transaksi</th><th class="text-right">Total Belanja</th></tr>
        <?php $i=1; foreach($sales_by_customer as $cust): ?>
            <tr>
                <td class="text-center"><?= $i++ ?></td>
                <td><?= htmlspecialchars($cust['customer_name']) ?></td>
                <td class="text-center"><?= $cust['total_transactions'] ?></td>
                <td class="text-right">Rp <?= formatRp($cust['total_spent']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($sales_by_customer)) echo "<tr><td colspan='4' class='text-center'>Belum ada data</td></tr>"; ?>
    </table>

    <h3>2. Rincian Penjualan (Detail Transaksi)</h3>
    <table>
        <tr><th>Invoice</th><th>Waktu</th><th>Pelanggan</th><th class="text-center">Status</th><th class="text-center">Metode</th><th class="text-right">Total Bayar</th></tr>
        <?php foreach($sales_details as $dt): ?>
            <tr>
                <td><?= $dt['invoice_no'] ?></td>
                <td><?= $dt['created_at'] ?></td>
                <td><?= htmlspecialchars($dt['customer_name']) ?></td>
                <td class="text-center"><?= strtoupper($dt['payment_status']) ?></td>
                <td class="text-center"><?= strtoupper($dt['payment_method']) ?></td>
                <td class="text-right">Rp <?= formatRp($dt['total_amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($sales_details)) echo "<tr><td colspan='6' class='text-center'>Belum ada data</td></tr>"; ?>
    </table>

</body>
</html>
