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
    $stmtHead = $pdo->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.invoice_no = ?");
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

    $calculated_ongkir = $sale['total_amount'] - ($sale['subtotal'] - $sale['discount_voucher'] - $sale['discount_points'] - $sale['discount_manual'] - ($sale['discount_auto'] ?? 0));
    
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
        body { font-family: 'Arial', 'Helvetica', sans-serif; width: 58mm; max-width: 58mm; margin: 0 auto; padding: 3mm 4mm; color: #000; background: #fff; font-size: 11px; line-height: 1.4; font-weight: bold; }
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
        .btn { padding: 9px 12px; cursor: pointer; border-radius: 8px; font-weight: bold; width: 100%; margin-bottom: 6px; border: none; display: flex; align-items: center; justify-content: center; gap: 6px; box-sizing: border-box; font-family: inherit; font-size: 11px; transition: all 0.2s; }
        .btn:hover { opacity: 0.92; transform: translateY(-1px); }
        .btn-print { background: #2563eb; color: #ffffff; font-size: 12px; padding: 11px 14px; box-shadow: 0 2px 5px rgba(37,99,235,0.25); }
        .btn-usb { background: #10b981; color: #ffffff; }
        .btn-serial { background: #059669; color: #ffffff; }
        .btn-bt { background: #6366f1; color: #ffffff; }
        .btn-close { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; margin-top: 8px; }
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
        <tr><td style="width: 45px;">Tgl</td><td>: <?= date('d/m/y H:i', strtotime($sale['created_at'])) ?></td></tr>
        <tr><td>Inv</td><td>: <?= htmlspecialchars($invoice) ?></td></tr>
        <?php 
            $order_type = strtolower($sale['order_type'] ?? '');
            $channel_name = strtoupper($channel);
        ?>
        <?php if($order_type === 'online' || in_array(strtolower($channel), ['grabfood', 'gofood', 'shopeefood', 'travelokaeats', 'wa'])): ?>
            <tr><td>Sumber</td><td>: <strong style="font-weight: 900; font-size: 11px;">ONLINE (<?= htmlspecialchars($channel_name) ?>)</strong></td></tr>
            <?php if(!empty($sale['external_order_id'])): ?>
            <tr><td>Order ID</td><td>: <span class="text-bold"><?= htmlspecialchars($sale['external_order_id']) ?></span></td></tr>
            <?php endif; ?>
            <?php if(!empty($sale['driver_name'])): ?>
            <tr><td>Driver</td><td>: <?= htmlspecialchars($sale['driver_name']) ?> (<?= htmlspecialchars($sale['driver_phone'] ?? '-') ?>)</td></tr>
            <?php endif; ?>
        <?php else: ?>
            <tr><td>Kasir</td><td>: <?= htmlspecialchars($sale['cashier_name'] ?? 'Kasir') ?></td></tr>
            <tr><td>Plg</td><td>: <?= htmlspecialchars($sale['customer_name'] ?? 'Umum') ?></td></tr>
            <?php if($is_po): ?>
            <tr><td>Tipe</td><td>: <span style="background:#000; color:#fff; padding:1px 3px; border-radius:2px;">PESANAN PO</span></td></tr>
            <tr><td>Ambil</td><td>: <?= $pickup_date ?> <?= $pickup_time ?></td></tr>
            <?php endif; ?>
        <?php endif; ?>
    </table>
    
    <div class="divider"></div>
    
    <table class="item-table">
        <?php foreach ($items as $item): ?>
            <tr class="item-row">
                <td colspan="2" class="item-name"><?= htmlspecialchars($item['product_name']) ?></td>
            </tr>
            <tr class="item-row">
                <td style="padding-left: 5px; color: #333; font-weight: normal;"><?= $item['qty'] ?> x <?= number_format($item['price'], 0, ',', '.') ?></td>
                <td class="text-right text-bold"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <div class="divider"></div>
    
    <table class="summary-table">
        <tr><td>Subtotal</td><td class="text-right"><?= number_format($sale['subtotal'], 0, ',', '.') ?></td></tr>
        <?php if($calculated_ongkir > 0): ?><tr><td>Ongkir</td><td class="text-right"><?= number_format($calculated_ongkir, 0, ',', '.') ?></td></tr><?php endif; ?>
        <?php if($sale['discount_voucher'] > 0): ?><tr><td>Voucher</td><td class="text-right">-<?= number_format($sale['discount_voucher'], 0, ',', '.') ?></td></tr><?php endif; ?>
        <?php if($sale['discount_points'] > 0): ?><tr><td>Poin</td><td class="text-right">-<?= number_format($sale['discount_points'], 0, ',', '.') ?></td></tr><?php endif; ?>
        <?php if($sale['discount_manual'] > 0): ?><tr><td>Disc. Manual</td><td class="text-right">-<?= number_format($sale['discount_manual'], 0, ',', '.') ?></td></tr><?php endif; ?>
        <tr><td class="text-bold" style="font-size: 13px; padding-top: 5px;">TOTAL</td><td class="text-bold text-right" style="font-size: 13px; padding-top: 5px;"><?= number_format($sale['total_amount'], 0, ',', '.') ?></td></tr>
        <tr><td style="padding-top: 5px;"> (<?= strtoupper($sale['payment_method']) ?>)</td><td class="text-right" style="padding-top: 5px;"><?= number_format($sale['amount_paid'], 0, ',', '.') ?></td></tr>
        <?php if(!empty($sale['payment_reference'])): ?>
        <tr><td colspan="2" style="font-size: 9px;">Ref: <?= htmlspecialchars($sale['payment_reference']) ?></td></tr>
        <?php endif; ?>
        <?php if($sale['payment_status'] === 'dp'): ?>
        <tr><td class="text-bold">SISA HUTANG</td><td class="text-bold text-right"><?= number_format($sale['total_amount'] - $sale['dp_amount'], 0, ',', '.') ?></td></tr>
        <?php endif; ?>
        <tr><td>Kembali</td><td class="text-right"><?= number_format($sale['change_amount'], 0, ',', '.') ?></td></tr>
    </table>
    
    <div class="divider" style="margin-top: 10px;"></div>
    
    <div class="text-center store-info" style="margin-top: 5px;">
        <p style="margin: 0; font-style: italic;"><?= htmlspecialchars($toko['receipt_footer']) ?></p>
    </div>

    <div class="barcode-container"><svg id="barcode"></svg></div>

    <!-- PANDUAN & AKSI PRINT (TIDAK IKUT TERCETAK) -->
    <div class="no-print" style="margin-top: 20px; border-top: 2px dashed #cbd5e1; padding-top: 14px;" id="action-buttons">
        
        <!-- RINGKASAN KONFIGURASI + BUTTON TOGGLE DETAIL -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; border-radius: 8px; margin-bottom: 12px; font-size: 10px; line-height: 1.4; color: #475569;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                <span>💡 <b>Tips Cetak Cepat:</b> Kiosk Mode langsung keluar struk.</span>
                <button type="button" onclick="toggleConfigGuide()" id="btn-guide-toggle" style="background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; border-radius: 6px; padding: 3px 8px; font-size: 9px; font-weight: bold; cursor: pointer; white-space: nowrap;">
                    ℹ️ Panduan
                </button>
            </div>
            <div id="config-guide-details" style="display: none; margin-top: 8px; border-top: 1px dashed #cbd5e1; padding-top: 8px; font-size: 9.5px;">
                <div style="font-weight: bold; color: #1e293b; margin-bottom: 2px;">🚀 1. Auto-Print Kiosk Mode (1 Detik Tanpa Popup):</div>
                <p style="margin: 0 0 6px 0;">Tambahkan <code>--kiosk-printing</code> di shortcut Google Chrome/Edge di desktop kasir agar struk otomatis tercetak tanpa dialog konfirmasi.</p>
                <div style="font-weight: bold; color: #1e293b; margin-bottom: 2px;">⚡ 2. Print Direct (WebUSB / Bluetooth / COM):</div>
                <p style="margin: 0 0 6px 0;">Kirim data ESC/POS langsung ke printer thermal menggunakan tombol direct di bawah.</p>
                <div style="font-weight: bold; color: #1e293b; margin-bottom: 2px;">📄 3. Ukuran Kertas:</div>
                <p style="margin: 0;">Gunakan <b>58mm Roll</b> (atau 80mm) dan set Margin ke <b>None</b>.</p>
            </div>
        </div>

        <!-- TOMBOL AKSI UTAMA -->
        <button onclick="window.print()" class="btn btn-print">🖨️ Cetak / Cetak Ulang Struk</button>
        <button onclick="printWebUSB()" class="btn btn-usb" id="btn-usb">⚡ Print Langsung WebUSB (ESC/POS)</button>
        <button onclick="printSerial()" class="btn btn-serial" id="btn-serial">🔌 Print Langsung WebSerial (COM)</button>
        <button onclick="printBluetooth()" class="btn btn-bt" id="btn-bt">📶 Print Bluetooth (GATT)</button>
        <button onclick="window.close()" class="btn btn-close">❌ Tutup Halaman</button>
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

        // Toggle Panduan Konfigurasi
        function toggleConfigGuide() {
            const el = document.getElementById('config-guide-details');
            const btn = document.getElementById('btn-guide-toggle');
            if (el.style.display === 'none' || el.style.display === '') {
                el.style.display = 'block';
                btn.innerHTML = '✖️ Tutup';
            } else {
                el.style.display = 'none';
                btn.innerHTML = 'ℹ️ Panduan';
            }
        }

        // Fungsi merakit teks ESC/POS mentah
        function buildEscPosText() {
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
            printText += " ".repeat(Math.max(0, 32 - ("KEMBALI: Rp "+receiptData.change).length)) + "KEMBALI: Rp " + receiptData.change + "\n";
            printText += "--------------------------------\n";
            printText += "\x1B\x61\x01" + receiptData.footer + "\n\n\n\n";
            printText += "\x1D\x56\x42\x00"; // Potong kertas otomatis (Cut Paper command)
            return printText;
        }

        document.addEventListener("DOMContentLoaded", function() {
            JsBarcode("#barcode", "<?= htmlspecialchars($invoice) ?>", { format: "CODE128", displayValue: true, fontSize: 12, height: 40, width: 1.2 });
            
            const params = new URLSearchParams(window.location.search);
            const isAutoBt = params.get('auto_print_bt');
            const isAutoUsb = params.get('auto_print_usb');

            // Cek Bluetooth Auto Print
            if (isAutoBt) {
                const savedPrinter = localStorage.getItem('pos_printer_name');
                if(savedPrinter && navigator.bluetooth) {
                    setTimeout(() => { printBluetooth(true); }, 500);
                }
            } 
            // Default ke USB / Thermal Printer (Cek WebUSB / WebSerial Otomatis dulu!)
            else {
                setTimeout(async () => {
                    let autoPrinted = false;
                    
                    // 1. Cek apakah ada printer WebUSB yang sudah diizinkan sebelumnya
                    if (isAutoUsb && navigator.usb && navigator.usb.getDevices) {
                        try {
                            const usbDevices = await navigator.usb.getDevices();
                            if (usbDevices && usbDevices.length > 0) {
                                autoPrinted = await printWebUSB(true, usbDevices[0]);
                            }
                        } catch(e) { console.warn("Auto WebUSB bypass", e); }
                    }

                    // 2. Cek apakah ada printer WebSerial yang sudah diizinkan sebelumnya
                    if (!autoPrinted && isAutoUsb && navigator.serial && navigator.serial.getPorts) {
                        try {
                            const serialPorts = await navigator.serial.getPorts();
                            if (serialPorts && serialPorts.length > 0) {
                                autoPrinted = await printSerial(true, serialPorts[0]);
                            }
                        } catch(e) { console.warn("Auto WebSerial bypass", e); }
                    }

                    // 3. 🛡️ FALLBACK OTOMATIS: Print Browser (window.print)
                    if (!autoPrinted && isAutoUsb) {
                        window.print();
                        // Jangan tutup otomatis agar kasir bisa cetak ulang jika kertas macet/habis
                    }
                }, 500);
            }
        });

        // 1. PRINT LANGSUNG VIA WEBUSB (ESC/POS RAW)
        async function printWebUSB(isAutoPrint = false, existingDevice = null) {
            const btn = document.getElementById('btn-usb');
            if (!navigator.usb) {
                if(!isAutoPrint) alert('Browser Anda tidak mendukung WebUSB. Silakan gunakan Google Chrome atau Microsoft Edge.');
                return false;
            }
            if (btn) { btn.innerHTML = 'Mencari Printer USB...'; btn.disabled = true; }

            try {
                let device = existingDevice;
                if (!device && navigator.usb.getDevices) {
                    const devList = await navigator.usb.getDevices();
                    if (devList && devList.length > 0) device = devList[0];
                }
                if (!device) {
                    if (isAutoPrint) return false;
                    device = await navigator.usb.requestDevice({ filters: [] });
                }

                await device.open();
                if (device.configuration === null) await device.selectConfiguration(1);
                
                let interfaceNumber = 0;
                let endpointNumber = 1;
                for (let config of device.configurations) {
                    for (let iface of config.interfaces) {
                        for (let alt of iface.alternates) {
                            for (let ep of alt.endpoints) {
                                if (ep.direction === "out") {
                                    interfaceNumber = iface.interfaceNumber;
                                    endpointNumber = ep.endpointNumber;
                                    break;
                                }
                            }
                        }
                    }
                }

                await device.claimInterface(interfaceNumber);
                const encoder = new TextEncoder();
                const printText = buildEscPosText();
                await device.transferOut(endpointNumber, encoder.encode(printText));
                await device.close();

                if (btn) { btn.innerHTML = 'Berhasil Dicetak WebUSB! ✅'; }
                setTimeout(() => { 
                    if (btn) { btn.innerHTML = '⚡ Print Langsung WebUSB (ESC/POS)'; btn.disabled = false; }
                }, 2000);
                return true;
            } catch (err) {
                console.error("WebUSB Error:", err);
                if (btn) { btn.innerHTML = '⚡ Print Langsung WebUSB (ESC/POS)'; btn.disabled = false; }
                
                if (!isAutoPrint) {
                    let msg = "Gagal mencetak via WebUSB: " + err.message;
                    if (err.name === 'SecurityError' || err.message.includes('Access denied') || err.message.includes('claimed')) {
                        msg = "⚠️ AKSES WEBUSB DITOLAK (SecurityError)\n\n" +
                              "💡 PENJELASAN:\n" +
                              "Printer Anda sedang dikunci oleh Driver Windows (Spooler).\n\n" +
                              "🚀 SOLUSI MUDAH:\n" +
                              "Silakan gunakan tombol biru '🖨️ Cetak / Cetak Ulang Struk' di atas!";
                    }
                    alert(msg);
                }
                return false;
            }
        }

        // 2. PRINT LANGSUNG VIA WEBSERIAL (COM PORT / RS232 / USB SERIAL)
        async function printSerial(isAutoPrint = false, existingPort = null) {
            const btn = document.getElementById('btn-serial');
            if (!navigator.serial) {
                if(!isAutoPrint) alert('Browser Anda tidak mendukung WebSerial. Silakan gunakan Google Chrome atau Microsoft Edge.');
                return false;
            }
            if (btn) { btn.innerHTML = 'Membuka Port Serial...'; btn.disabled = true; }

            try {
                let port = existingPort;
                if (!port && navigator.serial.getPorts) {
                    const portList = await navigator.serial.getPorts();
                    if (portList && portList.length > 0) port = portList[0];
                }
                if (!port) {
                    if (isAutoPrint) return false;
                    port = await navigator.serial.requestPort();
                }

                await port.open({ baudRate: 9600 });
                const writer = port.writable.getWriter();
                const encoder = new TextEncoder();
                const printText = buildEscPosText();
                await writer.write(encoder.encode(printText));
                writer.releaseLock();
                await port.close();

                if (btn) { btn.innerHTML = 'Berhasil Dicetak WebSerial! ✅'; }
                setTimeout(() => { 
                    if (btn) { btn.innerHTML = '🔌 Print Langsung WebSerial (COM)'; btn.disabled = false; }
                }, 2000);
                return true;
            } catch (err) {
                console.error("WebSerial Error:", err);
                if (btn) { btn.innerHTML = '🔌 Print Langsung WebSerial (COM)'; btn.disabled = false; }
                if (!isAutoPrint) alert("Gagal mencetak via WebSerial: " + err.message);
                return false;
            }
        }

        // 3. PRINT VIA BLUETOOTH (GATT)
        async function printBluetooth(isAutoPrint = false) {
            const btn = document.getElementById('btn-bt');
            const savedPrinter = localStorage.getItem('pos_printer_name');
            let device;

            if (btn) {
                btn.innerHTML = 'Menghubungkan Printer...';
                btn.disabled = true;
            }

            try {
                if (savedPrinter && navigator.bluetooth.getDevices) {
                    const devices = await navigator.bluetooth.getDevices();
                    device = devices.find(d => d.name === savedPrinter);
                }

                if (!device) {
                    if (isAutoPrint) {
                        if (btn) {
                            btn.innerHTML = '📶 Print Bluetooth (GATT)';
                            btn.disabled = false;
                        }
                        return;
                    }
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
                const printText = buildEscPosText();
                await characteristic.writeValue(encoder.encode(printText));
                
                if (btn) { btn.innerHTML = 'Berhasil Dicetak! ✅'; }
                setTimeout(() => { 
                    if (btn) {
                        btn.innerHTML = '📶 Print Bluetooth (GATT)'; 
                        btn.disabled = false; 
                    }
                }, 2000);

            } catch (error) {
                console.error("Gagal Print:", error);
                if (btn) {
                    btn.innerHTML = '📶 Print Bluetooth (GATT)';
                    btn.disabled = false;
                }
                if (!isAutoPrint) alert('Gagal menghubungkan ke Printer Bluetooth. Pastikan printer menyala.');
            }
        }
    </script>
</body> 
</html>