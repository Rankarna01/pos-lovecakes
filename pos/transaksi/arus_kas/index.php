<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Arus Kas (Petty Cash) - Love Cakes POS";
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
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-money-bill-transfer mr-2"></i>Arus Kas (Petty Cash)</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="max-w-7xl mx-auto space-y-4">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-emerald-200 flex justify-between items-center relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-emerald-500 text-7xl"><i class="fa-solid fa-arrow-turn-down"></i></div>
                        <div>
                            <p class="text-xs font-black text-emerald-600 uppercase tracking-widest">Pemasukan Kas Harian</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">Rp 0</h3>
                        </div>
                        <button class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl font-bold transition-all text-sm z-10 shadow-sm">+ Catat Masuk</button>
                    </div>
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-rose-200 flex justify-between items-center relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-rose-500 text-7xl"><i class="fa-solid fa-arrow-turn-up"></i></div>
                        <div>
                            <p class="text-xs font-black text-rose-600 uppercase tracking-widest">Pengeluaran Kas Harian</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">Rp 65.000</h3>
                        </div>
                        <button class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded-xl font-bold transition-all text-sm z-10 shadow-sm">- Catat Keluar</button>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden mt-4">
                    <div class="p-4 border-b border-slate-100 bg-slate-50"><h3 class="font-black text-slate-700">Riwayat Mutasi Kasir</h3></div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Waktu</th>
                                    <th class="p-4 font-black text-center">Tipe</th>
                                    <th class="p-4 font-black">Keterangan</th>
                                    <th class="p-4 font-black text-right">Nominal</th>
                                    <th class="p-4 font-black">Oleh</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 font-bold text-slate-600">08 Mei 2026 <br><span class="text-[10px] text-slate-400">10:45 WIB</span></td>
                                    <td class="p-4 text-center"><span class="px-2 py-1 rounded bg-rose-100 text-rose-600 text-[10px] font-black uppercase"><i class="fa-solid fa-arrow-up"></i> Keluar</span></td>
                                    <td class="p-4 font-black text-slate-800">Beli lakban packing & spidol</td>
                                    <td class="p-4 text-right font-black text-rose-500">Rp 15.000</td>
                                    <td class="p-4 text-slate-500 font-bold text-xs"><i class="fa-solid fa-user-circle"></i> Kasir Shift Pagi</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 font-bold text-slate-600">08 Mei 2026 <br><span class="text-[10px] text-slate-400">08:10 WIB</span></td>
                                    <td class="p-4 text-center"><span class="px-2 py-1 rounded bg-rose-100 text-rose-600 text-[10px] font-black uppercase"><i class="fa-solid fa-arrow-up"></i> Keluar</span></td>
                                    <td class="p-4 font-black text-slate-800">Beli bahan dadakan (Telur 2kg) ke warung</td>
                                    <td class="p-4 text-right font-black text-rose-500">Rp 50.000</td>
                                    <td class="p-4 text-slate-500 font-bold text-xs"><i class="fa-solid fa-user-circle"></i> Kasir Shift Pagi</td>
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