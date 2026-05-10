<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Pencairan Dana - Love Cakes POS";
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
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-building-columns mr-2"></i>Pencairan Dana (Settlement)</h2>
            </div>
            <div class="flex items-center gap-3">
                <button class="bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-sm border border-white/10"><i class="fa-solid fa-download mr-1"></i> Export Excel</button>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-amber-200 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-amber-500 text-7xl"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <p class="text-xs font-black text-amber-600 uppercase tracking-widest mb-1">Dana Mengendap (Belum Cair)</p>
                        <h3 class="text-3xl font-black text-slate-800">Rp 1.250.000</h3>
                        <p class="text-xs font-bold text-slate-400 mt-2">Dari 15 Transaksi Non-Tunai H-1 & H-2</p>
                    </div>
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-emerald-200 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-emerald-500 text-7xl"><i class="fa-solid fa-check-double"></i></div>
                        <p class="text-xs font-black text-emerald-600 uppercase tracking-widest mb-1">Berhasil Masuk Rekening (Bulan Ini)</p>
                        <h3 class="text-3xl font-black text-slate-800">Rp 18.500.000</h3>
                        <p class="text-xs font-bold text-slate-400 mt-2">Sesuai mutasi rekening BCA & Mandiri</p>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-black text-slate-700 uppercase text-xs tracking-widest">Riwayat Settlement / Pencairan</h3>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] border-b border-slate-200">
                                <tr>
                                    <th class="p-4 font-black">Tgl Transaksi</th>
                                    <th class="p-4 font-black">Sumber / Provider</th>
                                    <th class="p-4 font-black text-right">Nominal Kotor</th>
                                    <th class="p-4 font-black text-right">MDR / Biaya Layanan</th>
                                    <th class="p-4 font-black text-right">Nominal Diterima</th>
                                    <th class="p-4 font-black text-center">Status Pencairan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4"><div class="font-black text-slate-800">07 Mei 2026</div><div class="text-[10px] font-bold text-slate-400">Total 5 Trx</div></td>
                                    <td class="p-4"><span class="bg-blue-50 text-blue-600 px-3 py-1 rounded border border-blue-100 font-black text-xs">QRIS BCA</span></td>
                                    <td class="p-4 text-right font-bold text-slate-600">Rp 500.000</td>
                                    <td class="p-4 text-right font-bold text-rose-500">- Rp 3.500 <span class="text-[10px] text-slate-400">(0.7%)</span></td>
                                    <td class="p-4 text-right font-black text-emerald-600 text-base">Rp 496.500</td>
                                    <td class="p-4 text-center"><span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase bg-emerald-100 text-emerald-600">Selesai (Masuk)</span></td>
                                </tr>
                                <tr class="hover:bg-slate-50 bg-amber-50/20">
                                    <td class="p-4"><div class="font-black text-slate-800">08 Mei 2026</div><div class="text-[10px] font-bold text-slate-400">Total 12 Trx</div></td>
                                    <td class="p-4"><span class="bg-emerald-50 text-emerald-600 px-3 py-1 rounded border border-emerald-100 font-black text-xs">GrabFood</span></td>
                                    <td class="p-4 text-right font-bold text-slate-600">Rp 1.250.000</td>
                                    <td class="p-4 text-right font-bold text-rose-500">- Rp 250.000 <span class="text-[10px] text-slate-400">(20%)</span></td>
                                    <td class="p-4 text-right font-black text-emerald-600 text-base">Rp 1.000.000</td>
                                    <td class="p-4 text-center"><span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase bg-amber-100 text-amber-600">Menunggu (H+1)</span></td>
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