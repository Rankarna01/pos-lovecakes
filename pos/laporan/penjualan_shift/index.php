<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Laporan Penjualan & Shift - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans">
    <?php include '../../../components/sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20"><h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-receipt mr-2"></i>Penjualan & Shift</h2></header>

        <main class="flex-1 overflow-y-auto custom-scrollbar p-4 md:p-6">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap gap-3">
                    <input type="date" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-bold">
                    <span class="py-2 text-slate-400">s/d</span>
                    <input type="date" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-bold">
                    <button class="bg-primary text-white px-6 py-2 rounded-xl font-bold">Tampilkan</button>
                    <button class="bg-emerald-500 text-white px-4 py-2 rounded-xl font-bold ml-auto"><i class="fa-solid fa-file-excel"></i> Export</button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b pb-2">Komposisi Pembayaran</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl"><span class="font-bold text-slate-600"><i class="fa-solid fa-money-bill text-emerald-500 mr-2"></i>Cash / Tunai</span><span class="font-black text-lg text-emerald-600">Rp 4.500.000</span></div>
                            <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl"><span class="font-bold text-slate-600"><i class="fa-solid fa-qrcode text-blue-500 mr-2"></i>QRIS Bank</span><span class="font-black text-lg text-blue-600">Rp 2.150.000</span></div>
                            <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl"><span class="font-bold text-slate-600"><i class="fa-solid fa-building-columns text-amber-500 mr-2"></i>Transfer Bank</span><span class="font-black text-lg text-amber-600">Rp 1.000.000</span></div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b pb-2">Laporan Closing Shift Kasir</h3>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]"><tr><th class="p-2">Nama Kasir</th><th class="p-2 text-center">Shift</th><th class="p-2 text-right">Total Transaksi</th></tr></thead>
                            <tbody>
                                <tr class="border-b border-slate-100"><td class="p-2 font-bold">Rina Mustika</td><td class="p-2 text-center"><span class="bg-blue-100 text-blue-600 px-2 py-0.5 rounded text-xs font-black">Pagi</span></td><td class="p-2 text-right font-black">Rp 3.500.000</td></tr>
                                <tr class="border-b border-slate-100"><td class="p-2 font-bold">Budi Santoso</td><td class="p-2 text-center"><span class="bg-orange-100 text-orange-600 px-2 py-0.5 rounded text-xs font-black">Malam</span></td><td class="p-2 text-right font-black">Rp 4.150.000</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>