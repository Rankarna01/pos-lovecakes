<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Pembelian & Restock - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="{}" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-boxes-packing mr-2"></i>Pembelian & Restock</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="max-w-7xl mx-auto space-y-4">
                
                <div class="flex justify-between items-end bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                    <div>
                        <h3 class="text-lg font-black text-slate-800">Daftar Purchase Order (PO)</h3>
                        <p class="text-sm font-bold text-slate-500">Ajukan permintaan barang ke gudang pusat atau catat belanja ke supplier luar.</p>
                    </div>
                    <button class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Buat PO Baru
                    </button>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Tanggal & No. PO</th>
                                    <th class="p-4 font-black">Supplier / Tujuan</th>
                                    <th class="p-4 font-black text-center">Item</th>
                                    <th class="p-4 font-black text-right">Total Est.</th>
                                    <th class="p-4 font-black text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4"><div class="font-black text-slate-800">PO-260508-001</div><div class="text-[10px] font-bold text-slate-500">08 Mei 2026 09:30</div></td>
                                    <td class="p-4 font-bold text-slate-600"><span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[10px] uppercase">Gudang Utama</span><br>Sistem Produksi RotiKu</td>
                                    <td class="p-4 text-center font-black text-slate-700">5 Item</td>
                                    <td class="p-4 text-right font-black text-slate-800">-</td>
                                    <td class="p-4 text-center"><span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase bg-rose-100 text-rose-600 border border-rose-200">Menunggu ACC</span></td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4"><div class="font-black text-slate-800">PO-260507-042</div><div class="text-[10px] font-bold text-slate-500">07 Mei 2026 14:15</div></td>
                                    <td class="p-4 font-bold text-slate-600"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] uppercase">Supplier Luar</span><br>Toko Plastik Maju Jaya</td>
                                    <td class="p-4 text-center font-black text-slate-700">2 Item</td>
                                    <td class="p-4 text-right font-black text-slate-800">Rp 150.000</td>
                                    <td class="p-4 text-center"><span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase bg-emerald-100 text-emerald-600 border border-emerald-200">Selesai Diterima</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>