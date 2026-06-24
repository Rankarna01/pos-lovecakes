<?php
session_start();
require_once '../../../../config/database.php';

$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// QUERY LAPORAN SHIFT (Logic Hitung Uang Fisik Laci)
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

function formatRp($angka) { return number_format((float)$angka, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Evaluasi Kasir</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0 0 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-weight: bold;">🖨️ Cetak</button>
        <button onclick="window.close()" style="padding: 10px 20px;">Tutup</button>
    </div>

    <div class="header">
        <h2>Laporan Evaluasi Shift Kasir</h2>
        <p>Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></p>
    </div>

    <table>
        <tr>
            <th class="text-left">Kasir</th>
            <th>Shift</th>
            <th>Waktu Mulai</th>
            <th class="text-right">Modal Awal</th>
            <th class="text-right">+ Omset Cash Masuk</th>
            <th class="text-right">- Petty Cash Keluar</th>
            <th class="text-right">= Sistem Seharusnya</th>
            <th class="text-right">Uang Fisik (Laci)</th>
            <th class="text-right">Selisih</th>
        </tr>
        <?php foreach($shifts as $s): 
            $expected_cash = $s['start_cash'] + $s['total_cash_in'] - $s['total_kas_keluar'];
            $selisih = $s['status'] === 'closed' ? ($s['end_cash'] - $expected_cash) : 0;
            $start_time = date('d/m/Y H:i', strtotime($s['start_time']));
        ?>
            <tr>
                <td class="text-left"><?= htmlspecialchars($s['kasir_name']) ?></td>
                <td><?= htmlspecialchars($s['shift_name']) ?></td>
                <td><?= $start_time ?></td>
                <td class="text-right">Rp <?= formatRp($s['start_cash']) ?></td>
                <td class="text-right">Rp <?= formatRp($s['total_cash_in']) ?></td>
                <td class="text-right">Rp <?= formatRp($s['total_kas_keluar']) ?></td>
                <td class="text-right">Rp <?= formatRp($expected_cash) ?></td>
                <td class="text-right">
                    <?php if($s['status'] === 'closed'): ?>
                        Rp <?= formatRp($s['end_cash']) ?>
                    <?php else: ?>
                        <i>Belum Tutup</i>
                    <?php endif; ?>
                </td>
                <td class="text-right">
                    <?php if($s['status'] === 'closed'): ?>
                        Rp <?= formatRp($selisih) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($shifts)) echo "<tr><td colspan='9' class='text-center'>Belum ada data shift di tanggal ini.</td></tr>"; ?>
    </table>

</body>
</html>
