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

// Hitung Pembatalan (Void)
$stmt_void = $pdo->prepare("
    SELECT SUM(amount) as total_void_amount, SUM(IF(is_cash_deducted=1, amount, 0)) as total_void_cash 
    FROM sale_cancellations_pos 
    WHERE created_at BETWEEN ? AND IFNULL(?, NOW())
");
$stmt_void->execute([$shift['start_time'], $shift['end_time']]);
$void_data = $stmt_void->fetch(PDO::FETCH_ASSOC);
$total_void_amount = $void_data['total_void_amount'] ?? 0;
$total_void_cash = $void_data['total_void_cash'] ?? 0;

// Query pembatalan per metode pembayaran
$stmt_void_methods = $pdo->prepare("
    SELECT UPPER(s.payment_method) as payment_method, SUM(sc.amount) as void_amount
    FROM sale_cancellations_pos sc
    JOIN sales_pos s ON sc.sale_id = s.id
    WHERE sc.created_at BETWEEN ? AND IFNULL(?, NOW())
    GROUP BY UPPER(s.payment_method)
");
$stmt_void_methods->execute([$shift['start_time'], $shift['end_time']]);
$void_methods = $stmt_void_methods->fetchAll(PDO::FETCH_ASSOC);

foreach ($void_methods as $vm) {
    $method = $vm['payment_method'];
    if (isset($grouped_payments[$method])) {
        $grouped_payments[$method]['pembatalan'] += $vm['void_amount'];
    } else {
        $grouped_payments[$method] = [
            'penjualan' => 0,
            'pembayaran_kredit' => 0,
            'pengembalian' => 0,
            'pembatalan' => $vm['void_amount']
        ];
    }
}

// Hitung Kas Aktual / Expected
$awal_laci = $shift['start_cash'];
$kas_keluar = $shift['total_kas_keluar'];
$kas_masuk = $shift['total_cash_in'] ?? 0; 
$expected_cash = $awal_laci + $total_penjualan_tunai + $total_pembayaran_kredit_tunai + $kas_masuk - $kas_keluar - $total_void_cash;


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

$grand_total_diharapkan = $awal_laci + $total_uang_masuk_semua + $kas_masuk - $kas_keluar - $total_void_amount;


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
            margin: 0; 
            padding: 20px; 
            background: #f1f5f9; 
            color: #1e293b;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .receipt-container {
            width: 80mm; 
            max-width: 100%;
            background: #fff; 
            padding: 5mm; 
            font-size: 12px; 
            line-height: 1.4; 
            font-weight: bold;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            border-radius: 8px;
            color: #000;
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            color: white;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .btn-usb { background: #10b981; }
        .btn-bt { background: #3b82f6; }
        .btn-close { background: #64748b; }

        @media print {
            body { 
                background: none; 
                padding: 0; 
                display: block; 
            }
            .receipt-container { 
                width: 100%;
                max-width: 100%;
                padding: 0; 
                box-shadow: none; 
                border-radius: 0; 
            }
            .no-print, .action-buttons { display: none !important; }
        }
    </style>
</head>
<body>
    
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn btn-usb">🖨️ Print Thermal (USB)</button>
        <button onclick="printBluetooth()" class="btn btn-bt" id="btn-bt">📶 Print Bluetooth</button>
        <button onclick="window.close()" class="btn btn-close">Tutup</button>
    </div>

    <div class="receipt-container">


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
        <div class="flex"><span>Pembatalan Uang Cash</span><span><?= fRp($total_void_cash) ?></span></div>
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
    <div class="flex"><span>Pembatalan Tunai</span><span><?= fRp($total_void_cash) ?></span></div>
    <div class="flex"><span>Kas Masuk-Keluar</span><span><?= fRp($kas_masuk - $kas_keluar) ?></span></div>
    <div class="dashed-line"></div>
    <div class="flex" style="margin-top: 5px;"><span>Kas Aktual</span><span><?= $shift['end_time'] ? fRp($shift['end_cash']) : '-' ?></span></div>
    <div class="dashed-line"></div>
    <div class="flex"><span>Kas Selisih</span><span><?= fRp($selisih) ?></span></div>

    <div class="dashed-line"></div>

    <!-- NON TUNAI -->
    <?php foreach ($grouped_payments as $method => $data): ?>
        <?php if ($method !== 'CASH'): ?>
        <?php $method_total = $data['penjualan'] + $data['pembayaran_kredit'] - $data['pembatalan']; ?>
        <div class="flex font-bold" style="margin-top: 5px;">
            <span><?= $method ?></span>
            <span><?= fRp($method_total) ?></span>
        </div>
        <div class="flex"><span>Penjualan</span><span><?= fRp($data['penjualan']) ?></span></div>
        <?php if ($data['pembayaran_kredit'] > 0): ?>
        <div class="flex"><span>Pembayaran Kredit</span><span><?= fRp($data['pembayaran_kredit']) ?></span></div>
        <?php endif; ?>
        <div class="flex"><span>Pengembalian</span><span>0</span></div>
        <div class="flex"><span>Pembatalan</span><span><?= fRp($data['pembatalan']) ?></span></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="dashed-line" style="margin-top: 10px;"></div>
    <div class="flex"><span>Total Diharapkan</span><span><?= fRp($grand_total_diharapkan) ?></span></div>
    <div class="flex"><span>Total Aktual</span><span><?= $shift['end_time'] ? fRp($grand_total_aktual) : '-' ?></span></div>
    <div class="flex font-bold" style="margin-bottom: 20px;"><span>Total Selisih</span><span><?= fRp($selisih) ?></span></div>

    </div> <!-- End receipt-container -->

    <script>
        const shiftData = {
            storeName: <?= json_encode($store_name) ?>,
            cashier: <?= json_encode($shift['cashier_name']) ?>,
            printed: <?= json_encode(date('d M Y H:i')) ?>,
            start: <?= json_encode(date('d M Y H:i', strtotime($shift['start_time']))) ?>,
            end: <?= json_encode($shift['end_time'] ? date('d M Y H:i', strtotime($shift['end_time'])) : '-') ?>,
            tamu: <?= json_encode($total_tamu) ?>,
            
            tunai_diharapkan: "<?= fRp($expected_cash) ?>",
            awal_laci: "<?= fRp($awal_laci) ?>",
            penjualan_tunai: "<?= fRp($total_penjualan_tunai) ?>",
            kredit_tunai: "<?= fRp($total_pembayaran_kredit_tunai) ?>",
            kas_masuk_keluar: "<?= fRp($kas_masuk - $kas_keluar) ?>",
            pembatalan_tunai: "<?= fRp($total_void_cash) ?>",
            kas_aktual: "<?= $shift['end_time'] ? fRp($shift['end_cash']) : '-' ?>",
            kas_selisih: "<?= fRp($selisih) ?>",
            
            total_diharapkan: "<?= fRp($grand_total_diharapkan) ?>",
            total_aktual: "<?= $shift['end_time'] ? fRp($grand_total_aktual) : '-' ?>",
            total_selisih: "<?= fRp($selisih) ?>"
        };

        // Dihapus auto-print timer untuk memungkinkan preview
        // document.addEventListener("DOMContentLoaded", function() {
        //     setTimeout(() => { 
        //         window.print(); 
        //     }, 500);
        // });

        async function printBluetooth(isAutoPrint = false) {
            const btn = document.getElementById('btn-bt');
            const savedPrinter = localStorage.getItem('pos_printer_name');
            let device;

            btn.innerHTML = 'Menghubungkan...';
            btn.disabled = true;

            try {
                if (savedPrinter && navigator.bluetooth.getDevices) {
                    const devices = await navigator.bluetooth.getDevices();
                    device = devices.find(d => d.name === savedPrinter);
                }
                if (!device) {
                    if (isAutoPrint) { btn.innerHTML = '📶 Print Bluetooth'; btn.disabled = false; return; }
                    device = await navigator.bluetooth.requestDevice({
                        filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }],
                        optionalServices: ['e7810a71-73ae-499d-8c15-faa9aef0c3f2'] 
                    });
                    localStorage.setItem('pos_printer_name', device.name);
                }

                const server = await device.gatt.connect();
                const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

                const encoder = new TextEncoder();
                let printText = "\x1B\x61\x01\x1B\x45\x01" + shiftData.storeName + "\nPenutupan Penjualan\x1B\x45\x00\x1B\x61\x00\n";
                printText += "--------------------------------\n";
                printText += "Dicetak : " + shiftData.printed + "\n";
                printText += "Kasir   : " + shiftData.cashier + "\n";
                printText += "Buka    : " + shiftData.start + "\n";
                printText += "Tutup   : " + shiftData.end + "\n";
                printText += "Tamu    : " + shiftData.tamu + "\n";
                printText += "--------------------------------\n";
                printText += "TUNAI\n";
                printText += "Diharapkan     : Rp " + shiftData.tunai_diharapkan + "\n";
                printText += "Awal Laci      : Rp " + shiftData.awal_laci + "\n";
                printText += "Penjualan      : Rp " + shiftData.penjualan_tunai + "\n";
                printText += "P. Kredit      : Rp " + shiftData.kredit_tunai + "\n";
                printText += "Pembatalan     : Rp " + shiftData.pembatalan_tunai + "\n";
                printText += "Kas In-Out     : Rp " + shiftData.kas_masuk_keluar + "\n";
                printText += "Kas Aktual     : Rp " + shiftData.kas_aktual + "\n";
                printText += "Selisih        : Rp " + shiftData.kas_selisih + "\n";
                printText += "--------------------------------\n";
                printText += "TOTAL DIHARAPKAN : Rp " + shiftData.total_diharapkan + "\n";
                printText += "TOTAL AKTUAL     : Rp " + shiftData.total_aktual + "\n";
                printText += "TOTAL SELISIH    : Rp " + shiftData.total_selisih + "\n";
                printText += "--------------------------------\n";
                printText += "\n\n\n\n"; 

                await characteristic.writeValue(encoder.encode(printText));
                
                btn.innerHTML = 'Berhasil ✅';
                setTimeout(() => { btn.innerHTML = '📶 Print Bluetooth'; btn.disabled = false; if(isAutoPrint) window.close(); }, 2000);

            } catch (error) {
                console.error("Gagal Print:", error);
                btn.innerHTML = '📶 Print Bluetooth';
                btn.disabled = false;
                if(isAutoPrint) { setTimeout(() => window.close(), 1500); }
            }
        }
    </script>
</body>
</html>
