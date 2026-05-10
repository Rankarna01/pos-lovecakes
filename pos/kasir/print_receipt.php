<?php
// NYALAKAN X-RAY ERROR
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';

$invoice = $_GET['invoice'] ?? '';

if (empty($invoice)) {
    die("<h3 style='font-family:sans-serif; text-align:center; color:#ef4444;'>Nomor Invoice tidak ditemukan.</h3>");
}

try {
    // 1. Tarik Data Master Transaksi
    $stmtHead = $pdo->prepare("SELECT s.*, c.name as customer_name FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.invoice_no = ?");
    $stmtHead->execute([$invoice]);
    $sale = $stmtHead->fetch(PDO::FETCH_ASSOC);

    if (!$sale) die("<h3 style='font-family:sans-serif; text-align:center;'>Transaksi tidak valid.</h3>");

    // 2. Tarik Data Detail Item
    $stmtDetail = $pdo->prepare("SELECT sd.*, COALESCE(p.name, sd.custom_name, 'Produk') as product_name FROM sale_details_pos sd LEFT JOIN products p ON sd.product_id = p.id WHERE sd.sale_id = ?");
    $stmtDetail->execute([$sale['id']]);
    $items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

    // 3. Tarik Pengaturan Toko
    $toko = false;
    try {
        $stmt_toko = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1");
        $toko = $stmt_toko->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) { }

    if(!$toko) {
        $toko = ['store_name' => 'LOVE CAKES', 'store_address' => 'Alamat belum diatur', 'store_phone' => '-', 'receipt_footer' => 'Terima Kasih!'];
    }

    $calculated_ongkir = $sale['total_amount'] - ($sale['subtotal'] - $sale['discount_voucher'] - $sale['discount_points'] - $sale['discount_manual']);
    
    $is_po = !empty($sale['is_po']) ? true : false;
    $channel = !empty($sale['channel']) ? $sale['channel'] : 'Toko';
    $pickup_date = !empty($sale['pickup_date']) ? date('d/m/Y', strtotime($sale['pickup_date'])) : '-';
    $pickup_time = !empty($sale['pickup_time']) ? date('H:i', strtotime($sale['pickup_time'])) : '-';

} catch (Exception $e) {
    die("<h3>⚠️ SYSTEM ERROR</h3><p>" . $e->getMessage() . "</p>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk <?= htmlspecialchars($invoice) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @page { margin: 0; }
        body { font-family: 'Courier New', Courier, monospace; width: 58mm; max-width: 58mm; margin: 0 auto; padding: 3mm 4mm; color: #000; background: #fff; font-size: 11px; line-height: 1.4; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .store-name { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .store-info { font-size: 10px; margin-bottom: 0px; }
        .info-table, .item-table, .summary-table { width: 100%; font-size: 10px; border-collapse: collapse; }
        .info-table td, .summary-table td { padding: 2px 0; vertical-align: top; }
        .item-name { font-weight: bold; padding-bottom: 2px; }
        .item-row td { padding-bottom: 5px; vertical-align: top; }
        .barcode-container { margin-top: 10px; text-align: center; }
        .barcode-container svg { max-width: 100%; height: auto; display: block; margin: 0 auto; }
        @media print { .no-print { display: none !important; } }
        .btn { padding: 10px 15px; cursor: pointer; border-radius: 5px; font-weight:bold; width:100%; margin-bottom:5px; border:none; display: block; box-sizing: border-box; }
        .btn-bt { background: #3b82f6; color: #ffffff; }
        .btn-usb { background: #e2e8f0; color: #0f172a; border: 1px solid #cbd5e1; }
        .btn-close { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
    </style>
</head>
<body>
    
    <div class="text-center">
        <div class="store-name"><?= htmlspecialchars($toko['store_name']) ?></div>
        <div class="store-info"><?= htmlspecialchars($toko['store_address']) ?></div>
        <div class="store-info">Telp/WA: <?= htmlspecialchars($toko['store_phone']) ?></div>
    </div>
    
    <div class="divider"></div>
    
    <table class="info-table">
        <tr><td style="width: 35px;">Tgl</td><td>: <?= date('d/m/y H:i', strtotime($sale['created_at'])) ?></td></tr>
        <tr><td>Inv</td><td>: <?= htmlspecialchars($invoice) ?></td></tr>
        <?php if($is_po): ?>
            <tr><td>Tipe</td><td>: <span class="text-bold">PO (<?= strtoupper($channel) ?>)</span></td></tr>
            <tr><td>Ambil</td><td>: <span class="text-bold"><?= $pickup_date ?> <?= $pickup_time ?></span></td></tr>
        <?php else: ?>
            <tr><td>Tipe</td><td>: REGULER</td></tr>
        <?php endif; ?>
        <?php if(!empty($sale['customer_name'])): ?>
        <tr><td>Cust</td><td>: <?= htmlspecialchars($sale['customer_name']) ?></td></tr>
        <?php endif; ?>
    </table>
    
    <div class="divider"></div>
    
    <table class="item-table">
        <?php foreach($items as $i): ?>
        <tr><td colspan="2" class="item-name"><?= ($i['is_custom'] ? '🛠️ ' : '') . htmlspecialchars($i['product_name']) ?></td></tr>
        <tr class="item-row">
            <td style="width: 60%;"><?= $i['qty'] ?> x <?= number_format($i['price'], 0, ',', '.') ?></td>
            <td class="text-right"><?= number_format($i['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <div class="divider"></div>
    
    <table class="summary-table">
        <tr><td>Subtotal</td><td class="text-right"><?= number_format($sale['subtotal'], 0, ',', '.') ?></td></tr>
        <?php if($calculated_ongkir > 0): ?><tr><td>Ongkir/Markup</td><td class="text-right">+<?= number_format($calculated_ongkir, 0, ',', '.') ?></td></tr><?php endif; ?>
        <?php if($sale['discount_voucher'] > 0): ?><tr><td>Disc. Vcr</td><td class="text-right">-<?= number_format($sale['discount_voucher'], 0, ',', '.') ?></td></tr><?php endif; ?>
        <?php if($sale['discount_points'] > 0): ?><tr><td>Disc. Poin</td><td class="text-right">-<?= number_format($sale['discount_points'], 0, ',', '.') ?></td></tr><?php endif; ?>
        <?php if($sale['discount_manual'] > 0): ?><tr><td>Disc. Manual</td><td class="text-right">-<?= number_format($sale['discount_manual'], 0, ',', '.') ?></td></tr><?php endif; ?>
        <tr><td class="text-bold" style="font-size: 13px; padding-top: 5px;">TOTAL</td><td class="text-bold text-right" style="font-size: 13px; padding-top: 5px;"><?= number_format($sale['total_amount'], 0, ',', '.') ?></td></tr>
        <tr><td style="padding-top: 5px;">Dibayar (<?= strtoupper($sale['payment_method']) ?>)</td><td class="text-right" style="padding-top: 5px;"><?= number_format($sale['amount_paid'], 0, ',', '.') ?></td></tr>
        <?php if($sale['payment_status'] === 'dp'): ?>
        <tr><td class="text-bold">SISA HUTANG</td><td class="text-bold text-right"><?= number_format($sale['total_amount'] - $sale['dp_amount'], 0, ',', '.') ?></td></tr>
        <?php elseif($sale['payment_method'] === 'cash'): ?>
        <tr><td>Kembali</td><td class="text-right"><?= number_format($sale['change_amount'], 0, ',', '.') ?></td></tr>
        <?php endif; ?>
    </table>
    
    <div class="divider" style="margin-top: 10px;"></div>
    
    <div class="text-center store-info" style="margin-top: 5px;">
        <p style="margin: 0; font-style: italic;"><?= htmlspecialchars($toko['receipt_footer']) ?></p>
    </div>

    <div class="barcode-container"><svg id="barcode"></svg></div>

    <div class="text-center no-print" style="margin-top: 20px;">
        <button onclick="printBluetooth()" class="btn btn-bt" id="btn-bt">📶 Print Bluetooth</button>
        <button onclick="window.print()" class="btn btn-usb">🖨️ Print USB/Biasa</button>
        <button onclick="window.close()" class="btn btn-close">❌ Tutup</button>
    </div>

    <script>
        const receiptData = {
            storeName: <?= json_encode($toko['store_name']) ?>,
            invoice: <?= json_encode($invoice) ?>,
            date: <?= json_encode(date('d/m/y H:i', strtotime($sale['created_at']))) ?>,
            items: <?= json_encode($items) ?>,
            total: "<?= number_format($sale['total_amount'], 0, ',', '.') ?>",
            paid: "<?= number_format($sale['amount_paid'], 0, ',', '.') ?>",
            change: "<?= number_format($sale['change_amount'], 0, ',', '.') ?>",
            footer: <?= json_encode($toko['receipt_footer']) ?>
        };

        document.addEventListener("DOMContentLoaded", function() {
            JsBarcode("#barcode", "<?= htmlspecialchars($invoice) ?>", { format: "CODE128", displayValue: true, fontSize: 12, height: 40, width: 1.2 });
            
            // Cek apakah ada printer default yang tersimpan. Jika ada, otomatis jalankan Bluetooth Print!
            const savedPrinter = localStorage.getItem('pos_printer_name');
            if(savedPrinter && navigator.bluetooth) {
                // Beri jeda sedikit agar DOM selesai load
                setTimeout(() => { printBluetooth(true); }, 500);
            }
        });

        async function printBluetooth(isAutoPrint = false) {
            const btn = document.getElementById('btn-bt');
            const savedPrinter = localStorage.getItem('pos_printer_name');
            let device;

            btn.innerHTML = 'Menghubungkan Printer...';
            btn.disabled = true;

            try {
                // JIKA BROWSER MENDUKUNG GET DEVICES (Auto-connect ke printer yang sudah diizinkan)
                if (savedPrinter && navigator.bluetooth.getDevices) {
                    const devices = await navigator.bluetooth.getDevices();
                    device = devices.find(d => d.name === savedPrinter);
                }

                // JIKA BELUM ADA IZIN ATAU KASIR KLIK MANUAL
                if (!device) {
                    if (isAutoPrint) {
                        btn.innerHTML = '📶 Print Bluetooth';
                        btn.disabled = false;
                        return; // Jangan paksa muncul popup kalau mode auto
                    }
                    device = await navigator.bluetooth.requestDevice({
                        filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }],
                        optionalServices: ['e7810a71-73ae-499d-8c15-faa9aef0c3f2'] 
                    });
                    localStorage.setItem('pos_printer_name', device.name);
                }

                // 2. Konek ke server Bluetooth GATT
                const server = await device.gatt.connect();
                const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

                // 3. Merakit Struk ESC/POS
                const encoder = new TextEncoder();
                let printText = "\x1B\x61\x01\x1B\x45\x01" + receiptData.storeName + "\n\x1B\x45\x00\x1B\x61\x00";
                printText += "--------------------------------\n";
                printText += "Tgl : " + receiptData.date + "\nInv : " + receiptData.invoice + "\n";
                printText += "--------------------------------\n";
                
                receiptData.items.forEach(i => {
                    let pFormat = parseInt(i.price).toLocaleString('id-ID');
                    let sFormat = parseInt(i.subtotal).toLocaleString('id-ID');
                    printText += i.product_name + "\n";
                    let row = i.qty + " x " + pFormat;
                    let space = 32 - row.length - sFormat.length;
                    printText += row + " ".repeat(space > 0 ? space : 1) + sFormat + "\n";
                });
                
                printText += "--------------------------------\n";
                printText += " ".repeat(Math.max(0, 32 - ("TOTAL: Rp "+receiptData.total).length)) + "TOTAL: Rp " + receiptData.total + "\n";
                printText += " ".repeat(Math.max(0, 32 - ("BAYAR: Rp "+receiptData.paid).length)) + "BAYAR: Rp " + receiptData.paid + "\n";
                printText += "--------------------------------\n";
                printText += "\x1B\x61\x01" + receiptData.footer + "\n\n\n\n"; 

                // 4. Tembak ke Printer!
                await characteristic.writeValue(encoder.encode(printText));
                
                btn.innerHTML = 'Berhasil Dicetak! ✅';
                setTimeout(() => { btn.innerHTML = '📶 Print Bluetooth'; btn.disabled = false; }, 2000);

            } catch (error) {
                console.error("Gagal Print:", error);
                if (!isAutoPrint) alert('Gagal menghubungkan ke Printer Bluetooth. Pastikan printer menyala.');
                btn.innerHTML = '📶 Print Bluetooth';
                btn.disabled = false;
            }
        }
    </script>
</body> 
</html>