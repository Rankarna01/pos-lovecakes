<?php
session_start();
require_once '../../../../config/database.php';

$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. QUERY KOMPOSISI PEMBAYARAN GLOBAL
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

// 2. Rincian Metode Pembayaran
$stmt_pay_breakdown = $pdo->prepare("
    SELECT payment_method, SUM(amount) as total_amount
    FROM sale_payments_pos 
    WHERE DATE(created_at) BETWEEN ? AND ? 
    GROUP BY payment_method
    ORDER BY total_amount DESC
");
$stmt_pay_breakdown->execute([$start_date, $end_date]);
$payment_breakdown = $stmt_pay_breakdown->fetchAll(PDO::FETCH_ASSOC);

// 3. Pembayaran DP dan Pelunasan
$stmt_dp = $pdo->prepare("
    SELECT payment_type, SUM(amount) as total_amount, COUNT(id) as total_transactions
    FROM sale_payments_pos
    WHERE payment_type IN ('dp', 'pelunasan') AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_type
");
$stmt_dp->execute([$start_date, $end_date]);
$dp_pelunasan = $stmt_dp->fetchAll(PDO::FETCH_ASSOC);

function formatRp($angka) { return number_format((float)$angka, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Ringkasan Omset</title>
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
        <h2>Laporan Ringkasan Omset Sistem</h2>
        <p>Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></p>
    </div>

    <h3>1. Komposisi Omset</h3>
    <table>
        <tr><th>Jenis Pembayaran</th><th class="text-right">Total</th></tr>
        <tr><td>Cash / Tunai</td><td class="text-right">Rp <?= formatRp($paymentData['cash']) ?></td></tr>
        <tr><td>QRIS & Transfer</td><td class="text-right">Rp <?= formatRp($paymentData['qris']) ?></td></tr>
        <tr><th>Total Keseluruhan</th><th class="text-right">Rp <?= formatRp($paymentData['total']) ?></th></tr>
    </table>

    <h3>2. Rincian Metode Pembayaran</h3>
    <table>
        <tr><th>Metode</th><th class="text-right">Total Amount</th></tr>
        <?php foreach($payment_breakdown as $pb): ?>
            <tr><td><?= strtoupper($pb['payment_method']) ?></td><td class="text-right">Rp <?= formatRp($pb['total_amount']) ?></td></tr>
        <?php endforeach; ?>
        <?php if(empty($payment_breakdown)) echo "<tr><td colspan='2' class='text-center'>Belum ada data</td></tr>"; ?>
    </table>

    <h3>3. Pembayaran DP vs Pelunasan</h3>
    <table>
        <tr><th>Tipe</th><th class="text-center">Total Transaksi</th><th class="text-right">Total Amount</th></tr>
        <?php foreach($dp_pelunasan as $dp): ?>
            <tr>
                <td><?= strtoupper($dp['payment_type']) ?></td>
                <td class="text-center"><?= $dp['total_transactions'] ?></td>
                <td class="text-right">Rp <?= formatRp($dp['total_amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($dp_pelunasan)) echo "<tr><td colspan='3' class='text-center'>Belum ada data</td></tr>"; ?>
    </table>

</body>
</html>
