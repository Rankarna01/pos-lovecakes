<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Akuntansi Internal - Love Cakes POS";
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
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-scale-balanced mr-2"></i>Akuntansi Internal</h2>
            </div>
            <div class="bg-white/20 px-3 py-1 rounded-lg text-xs font-bold border border-white/10">Bulan: Mei 2026</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Pendapatan (Sales)</p>
                        <h3 class="text-2xl font-black text-blue-600">Rp 45.000.000</h3>
                    </div>
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pengeluaran & HPP (Beban)</p>
                        <h3 class="text-2xl font-black text-rose-500">- Rp 20.000.000</h3>
                    </div>
                    <div class="bg-emerald-500 p-5 rounded-[1.5rem] shadow-md border border-emerald-600 text-white relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-20 text-7xl"><i class="fa-solid fa-sack-dollar"></i></div>
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 opacity-80">Laba Bersih (Net Profit)</p>
                        <h3 class="text-3xl font-black relative z-10">Rp 25.000.000</h3>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-black text-slate-700 uppercase text-xs tracking-widest">Jurnal Umum / Buku Kas</h3>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] border-b border-slate-200">
                                <tr>
                                    <th class="p-4 font-black">Tanggal</th>
                                    <th class="p-4 font-black">Keterangan Akun</th>
                                    <th class="p-4 font-black text-center">Tipe</th>
                                    <th class="p-4 font-black text-right">Debit (Masuk)</th>
                                    <th class="p-4 font-black text-right">Kredit (Keluar)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 font-bold text-slate-600">08 Mei 2026</td>
                                    <td class="p-4"><div class="font-black text-slate-800">Penjualan POS Shift Pagi</div><div class="text-[10px] font-bold text-slate-400">Akun: Kas Toko</div></td>
                                    <td class="p-4 text-center"><span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[10px] font-black uppercase">Pendapatan</span></td>
                                    <td class="p-4 text-right font-black text-emerald-600">Rp 3.500.000</td>
                                    <td class="p-4 text-right font-bold text-slate-400">-</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 font-bold text-slate-600">08 Mei 2026</td>
                                    <td class="p-4"><div class="font-black text-slate-800">Beli Gas Elpiji & Telur</div><div class="text-[10px] font-bold text-slate-400">Akun: Beban Operasional Dapur</div></td>
                                    <td class="p-4 text-center"><span class="bg-rose-50 text-rose-600 px-2 py-0.5 rounded text-[10px] font-black uppercase">Beban</span></td>
                                    <td class="p-4 text-right font-bold text-slate-400">-</td>
                                    <td class="p-4 text-right font-black text-rose-500">Rp 250.000</td>
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