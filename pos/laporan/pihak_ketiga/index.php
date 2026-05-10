<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Laporan Pihak Ketiga - Love Cakes POS";
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
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-motorcycle mr-2"></i>Rekonsiliasi Pihak Ketiga</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-200">
                    <p class="text-sm font-bold text-slate-500 mb-4">Pantau penjualan dari GrabFood, GoFood, dan ShopeeFood beserta persentase potongan komisi aplikasi.</p>
                    <div class="flex flex-wrap gap-2">
                        <button class="bg-primary text-white px-5 py-2 rounded-xl text-xs font-black shadow-md shadow-primary/20">Semua Platform</button>
                        <button class="bg-emerald-50 text-emerald-600 border border-emerald-200 px-5 py-2 rounded-xl text-xs font-black hover:bg-emerald-100 transition-colors">GrabFood</button>
                        <button class="bg-rose-50 text-rose-600 border border-rose-200 px-5 py-2 rounded-xl text-xs font-black hover:bg-rose-100 transition-colors">GoFood</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-200 text-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Omset Aplikasi (Gross)</p>
                        <h3 class="text-2xl font-black text-slate-800">Rp 10.000.000</h3>
                    </div>
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-200 text-center">
                        <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-1">Potongan Komisi (Fee 20%)</p>
                        <h3 class="text-2xl font-black text-rose-500">- Rp 2.000.000</h3>
                    </div>
                    <div class="bg-blue-50 p-5 rounded-[1.5rem] shadow-sm border border-blue-200 text-center">
                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Total Bersih ke Toko (Net)</p>
                        <h3 class="text-2xl font-black text-blue-600">Rp 8.000.000</h3>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] border-b border-slate-200">
                                <tr>
                                    <th class="p-4 font-black">No. Order / Waktu</th>
                                    <th class="p-4 font-black">Platform</th>
                                    <th class="p-4 font-black text-right">Harga Aplikasi (Gross)</th>
                                    <th class="p-4 font-black text-right">Potongan Komisi</th>
                                    <th class="p-4 font-black text-right">Bersih (Net)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4"><div class="font-black text-slate-800">GF-99214-A</div><div class="text-[10px] font-bold text-slate-400">08 Mei 2026 14:20</div></td>
                                    <td class="p-4"><span class="bg-emerald-100 text-emerald-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase border border-emerald-200">GrabFood</span></td>
                                    <td class="p-4 text-right font-bold text-slate-600">Rp 100.000</td>
                                    <td class="p-4 text-right font-bold text-rose-500">- Rp 20.000</td>
                                    <td class="p-4 text-right font-black text-primary text-base">Rp 80.000</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4"><div class="font-black text-slate-800">GOJ-8812-B</div><div class="text-[10px] font-bold text-slate-400">08 Mei 2026 15:10</div></td>
                                    <td class="p-4"><span class="bg-rose-100 text-rose-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase border border-rose-200">GoFood</span></td>
                                    <td class="p-4 text-right font-bold text-slate-600">Rp 50.000</td>
                                    <td class="p-4 text-right font-bold text-rose-500">- Rp 10.000</td>
                                    <td class="p-4 text-right font-black text-primary text-base">Rp 40.000</td>
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