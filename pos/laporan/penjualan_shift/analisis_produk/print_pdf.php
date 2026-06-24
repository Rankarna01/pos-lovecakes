<?php
session_start();
require_once '../../../../config/database.php';

$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. Penjualan per Kategori
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

// 2. Penjualan per Barang (Terlaris)
$stmt_item = $pdo->prepare("
    SELECT COALESCE(p.name, sd.custom_name) as item_name, SUM(sd.qty) as total_qty, SUM(sd.subtotal) as total_amount
    FROM sale_details_pos sd
    JOIN sales_pos s ON sd.sale_id = s.id
    LEFT JOIN products p ON sd.product_id = p.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY COALESCE(p.name, sd.custom_name)
    ORDER BY total_amount DESC
    LIMIT 50
");
$stmt_item->execute([$start_date, $end_date]);
$sales_by_item = $stmt_item->fetchAll(PDO::FETCH_ASSOC);

function formatRp($angka) { return number_format((float)$angka, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Analisis Produk</title>
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
        <h2>Laporan Analisis Produk</h2>
        <p>Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></p>
    </div>

    <h3>1. Penjualan per Kategori</h3>
    <table>
        <tr><th>Kategori</th><th class="text-center">Total Qty</th><th class="text-right">Total Amount</th></tr>
        <?php foreach($sales_by_category as $cat): ?>
            <tr>
                <td><?= htmlspecialchars($cat['category_name']) ?></td>
                <td class="text-center"><?= $cat['total_qty'] ?></td>
                <td class="text-right">Rp <?= formatRp($cat['total_amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($sales_by_category)) echo "<tr><td colspan='3' class='text-center'>Belum ada data</td></tr>"; ?>
    </table>

    <h3>2. Barang Terlaris</h3>
    <table>
        <tr><th>No.</th><th>Nama Barang</th><th class="text-center">Total Qty</th><th class="text-right">Total Amount</th></tr>
        <?php $i=1; foreach($sales_by_item as $item): ?>
            <tr>
                <td class="text-center"><?= $i++ ?></td>
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td class="text-center"><?= $item['total_qty'] ?></td>
                <td class="text-right">Rp <?= formatRp($item['total_amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($sales_by_item)) echo "<tr><td colspan='4' class='text-center'>Belum ada data</td></tr>"; ?>
    </table>

</body>
</html>
