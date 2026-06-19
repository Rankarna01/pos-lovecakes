<?php
session_start();
require_once '../../config/database.php';

$shift_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM shifts_history_pos WHERE id = ?");
$stmt->execute([$shift_id]);
$shift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shift) {
    die("Shift tidak ditemukan.");
}

$store = $pdo->query("SELECT * FROM pos_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$store_name = $store['store_name'] ?? 'Love Cakes Bengkulu';

// Query Total Tamu (jumlah transaksi)
$stmt_guests = $pdo->prepare("SELECT COUNT(*) as total FROM sales_pos WHERE created_at BETWEEN ? AND IFNULL(?, NOW())");
$stmt_guests->execute([$shift['start_time'], $shift['end_time']]);
$total_tamu = $stmt_guests->fetchColumn();

// Hitung Penjualan dari sale_payments_pos di shift ini
$stmt_payments = $pdo->prepare("
    SELECT 
        sp.payment_method,
        sp.payment_type,
        SUM(sp.amount) as total_amount
    FROM sale_payments_pos sp
    WHERE sp.created_at BETWEEN ? AND IFNULL(?, NOW())
    GROUP BY sp.payment_method, sp.payment_type
");
$stmt_payments->execute([$shift['start_time'], $shift['end_time']]);
$payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan berdasar metode pembayaran
$grouped_payments = [];
$total_penjualan_tunai = 0;
$total_pembayaran_kredit_tunai = 0;

foreach ($payments as $p) {
    $method = strtoupper($p['payment_method']);
    if (!isset($grouped_payments[$method])) {
        $grouped_payments[$method] = [
            'penjualan' => 0,
            'pembayaran_kredit' => 0,
            'pengembalian' => 0,
            'pembatalan' => 0
        ];
    }
    
    if ($p['payment_type'] === 'full') {
        $grouped_payments[$method]['penjualan'] += $p['total_amount'];
        if ($method === 'CASH') $total_penjualan_tunai += $p['total_amount'];
    } else if (in_array($p['payment_type'], ['dp', 'pelunasan'])) {
        $grouped_payments[$method]['pembayaran_kredit'] += $p['total_amount'];
        if ($method === 'CASH') $total_pembayaran_kredit_tunai += $p['total_amount'];
    }
}

// Hitung Kas Aktual / Expected
$awal_laci = $shift['start_cash'];
$kas_keluar = $shift['total_kas_keluar'];
$kas_masuk = $shift['total_cash_in'] ?? 0; 
$expected_cash = $awal_laci + $total_penjualan_tunai + $total_pembayaran_kredit_tunai + $kas_masuk - $kas_keluar;

// Hitung Grand Total (Tunai + Non-Tunai)
$total_diharapkan = 0;
foreach ($grouped_payments as $method => $data) {
    $total_diharapkan += $data['penjualan'] + $data['pembayaran_kredit'];
}
// Total Diharapkan di sini maksudnya Omset keseluruhan atau uang masuk total?
// Di struk referensi: Total Diharapkan = Awal Laci + Penjualan (tunai+non-tunai) dll? Atau hanya omset?
// Dari struk foto, "Total Diharapkan 4.082.900". Mari kita hitung: Awal Laci (500k) + Tunai Penjualan (1.554k) + QRIS BCA (326k) + EDC BCA (149k) + Trf BCA (790k) + Grab (185.9k) + QRIS BRI (383k) + EDC BRI (195k) = 4.082.900!
// Berarti Total Diharapkan = Awal Laci + Semua Uang Masuk!

$total_uang_masuk_semua = 0;
foreach ($grouped_payments as $method => $data) {
    $total_uang_masuk_semua += $data['penjualan'] + $data['pembayaran_kredit'];
}

$grand_total_diharapkan = $awal_laci + $total_uang_masuk_semua + $kas_masuk - $kas_keluar;

// Actual dari shift (untuk tunai saja? atau selisih?)
// Di struk referensi, Total Aktual sama dengan Total Diharapkan jika tidak ada selisih di laci tunai.
$selisih = $shift['end_time'] ? ($shift['end_cash'] - $expected_cash) : 0;
$grand_total_aktual = $grand_total_diharapkan + $selisih;

function fRp($val) {
    return number_format($val, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Tutup Shift - <?= $shift['id'] ?></title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 80mm;
            color: #000;
            line-height: 1.4;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .flex { display: flex; justify-content: space-between; }
        .dashed-line { border-top: 1px dashed #000; margin: 5px 0; }
        .uppercase { text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .w-half { width: 50%; }
        @media print {
            body { width: 100%; padding: 0; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="text-center" style="margin-bottom: 10px;">
        <div class="font-bold uppercase"><?= $store_name ?></div>
        <div>Penutupan Penjualan</div>
    </div>

    <div class="flex">
        <span>Dicetak</span>
        <span><?= date('d M Y H:i') ?></span>
    </div>
    
    <div style="margin: 10px 0;">
        <div class="flex"><span>Kasir</span><span><?= $shift['cashier_name'] ?></span></div>
        <div class="flex"><span>Mulai Shift</span><span><?= date('d M Y H:i', strtotime($shift['start_time'])) ?></span></div>
        <div class="flex"><span>Akhiri Shift</span><span><?= $shift['end_time'] ? date('d M Y H:i', strtotime($shift['end_time'])) : '-' ?></span></div>
        <div class="flex"><span>Jumlah Tamu</span><span><?= $total_tamu ?> pax(s)</span></div>
        <div class="flex"><span>Resi</span><span><?= $total_tamu ?></span></div>
        <div class="flex"><span>Pengembalian</span><span>0</span></div>
    </div>

    <div class="dashed-line"></div>

    <!-- TUNAI -->
    <div class="flex font-bold" style="margin-top: 5px;">
        <span>Tunai</span>
        <span><?= fRp($expected_cash) ?></span>
    </div>
    <div class="dashed-line"></div>
    <div class="flex"><span>Awal di Laci</span><span><?= fRp($awal_laci) ?></span></div>
    <div class="flex"><span>Penjualan Tunai</span><span><?= fRp($total_penjualan_tunai) ?></span></div>
    <?php if ($total_pembayaran_kredit_tunai > 0): ?>
    <div class="flex"><span>Pembayaran Kredit</span><span><?= fRp($total_pembayaran_kredit_tunai) ?></span></div>
    <?php endif; ?>
    <div class="flex"><span>Pengembalian Tunai</span><span>0</span></div>
    <div class="flex"><span>Pembatalan Tunai</span><span>0</span></div>
    <div class="flex"><span>Kas Masuk-Keluar</span><span><?= fRp($kas_masuk - $kas_keluar) ?></span></div>
    <div class="dashed-line"></div>
    <div class="flex" style="margin-top: 5px;"><span>Kas Aktual</span><span><?= $shift['end_time'] ? fRp($shift['end_cash']) : '-' ?></span></div>
    <div class="dashed-line"></div>
    <div class="flex"><span>Kas Selisih</span><span><?= fRp($selisih) ?></span></div>

    <div class="dashed-line"></div>

    <!-- NON TUNAI -->
    <?php foreach ($grouped_payments as $method => $data): ?>
        <?php if ($method !== 'CASH'): ?>
        <?php $method_total = $data['penjualan'] + $data['pembayaran_kredit']; ?>
        <div class="flex font-bold" style="margin-top: 5px;">
            <span><?= $method ?></span>
            <span><?= fRp($method_total) ?></span>
        </div>
        <div class="flex"><span>Penjualan</span><span><?= fRp($data['penjualan']) ?></span></div>
        <?php if ($data['pembayaran_kredit'] > 0): ?>
        <div class="flex"><span>Pembayaran Kredit</span><span><?= fRp($data['pembayaran_kredit']) ?></span></div>
        <?php endif; ?>
        <div class="flex"><span>Pengembalian</span><span>0</span></div>
        <div class="flex"><span>Pembatalan</span><span>0</span></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="dashed-line" style="margin-top: 10px;"></div>
    <div class="flex"><span>Total Diharapkan</span><span><?= fRp($grand_total_diharapkan) ?></span></div>
    <div class="flex"><span>Total Aktual</span><span><?= $shift['end_time'] ? fRp($grand_total_aktual) : '-' ?></span></div>
    <div class="flex font-bold" style="margin-bottom: 20px;"><span>Total Selisih</span><span><?= fRp($selisih) ?></span></div>

    <div class="flex" style="margin-top: 30px;">
        <div class="text-center" style="width: 45%;">
            <div style="border-bottom: 1px solid #000; height: 50px;"></div>
        </div>
        <div class="text-center" style="width: 45%;">
            <div style="border-bottom: 1px solid #000; height: 50px;"></div>
        </div>
    </div>

</body>
</html>
