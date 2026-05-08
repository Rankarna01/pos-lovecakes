<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Ringkasan & Jam Ramai - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
    <style>
        .heatmap-bar { transition: height 1s ease-in-out; }
        .heatmap-container:hover .heatmap-bar:not(:hover) { opacity: 0.5; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="ringkasanApp()" x-cloak>
    <?php include '../../../components/sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-clock mr-2"></i>Ringkasan & Jam Ramai</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 relative">
            <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-sm">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
            </div>

            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 flex-1 md:flex-none">
                        <input type="date" x-model="filters.start_date" class="w-full md:w-auto bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:border-primary">
                        <span class="text-slate-400 font-bold text-sm">s/d</span>
                        <input type="date" x-model="filters.end_date" class="w-full md:w-auto bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:border-primary">
                    </div>
                    <button @click="fetchData()" class="bg-primary hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold transition-all shadow-sm w-full md:w-auto">
                        <i class="fa-solid fa-filter mr-1"></i> Terapkan
                    </button>
                </div>

                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                    <h3 class="font-black text-slate-700 mb-2 uppercase text-xs tracking-widest border-b border-slate-100 pb-2">Analisa Jam Sibuk Transaksi (Grafik 24 Jam)</h3>
                    
                    <div x-show="maxTrx === 0" class="text-center py-10 text-slate-400 text-sm font-bold">Belum ada transaksi untuk dianalisa.</div>
                    
                    <div x-show="maxTrx > 0" class="mt-6 border-b border-slate-200 pb-1 relative">
                        <div class="flex items-end gap-1 sm:gap-2 h-48 heatmap-container">
                            <template x-for="hourData in hours" :key="hourData.hour">
                                <div class="flex-1 flex flex-col justify-end h-full group cursor-pointer heatmap-bar">
                                    <div class="w-full bg-blue-500 rounded-t-md relative heatmap-bar transition-all" 
                                         :style="'height: ' + (hourData.trx > 0 ? ((hourData.trx / maxTrx) * 100) : 1) + '%;'"
                                         :class="hourData.trx === maxTrx ? 'bg-rose-500' : (hourData.trx === 0 ? 'bg-slate-100' : 'bg-blue-500 hover:bg-blue-400')">
                                        
                                        <div class="hidden group-hover:block absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap z-10 shadow-lg after:content-[''] after:absolute after:top-full after:left-1/2 after:-translate-x-1/2 after:border-4 after:border-transparent after:border-t-slate-800">
                                            <span x-text="hourData.label"></span>: <span class="font-black text-amber-300" x-text="hourData.trx + ' Trx'"></span>
                                        </div>
                                        
                                        <div x-show="hourData.trx === maxTrx" class="absolute -top-6 text-[9px] font-black text-rose-500 w-full text-center truncate">PUNCAK</div>
                                    </div>
                                    <div class="text-[8px] sm:text-[10px] font-bold text-slate-400 text-center mt-2" x-text="hourData.label"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                    <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-2">Riwayat Belanja Pelanggan (Top 10 CRM)</h3>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]">
                                <tr>
                                    <th class="p-3 rounded-tl-lg">Peringkat</th>
                                    <th class="p-3">Nama Pelanggan</th>
                                    <th class="p-3 text-center">Total Kunjungan</th>
                                    <th class="p-3 rounded-tr-lg">Barang Sering Dibeli</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr x-show="customers.length === 0">
                                    <td colspan="4" class="p-8 text-center text-slate-400 font-bold">Belum ada data pelanggan yang tercatat.</td>
                                </tr>
                                <template x-for="(cust, index) in customers" :key="cust.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-3 text-center font-black text-slate-400">#<span x-text="index + 1"></span></td>
                                        <td class="p-3 font-bold text-slate-700" x-text="cust.customer_name"></td>
                                        <td class="p-3 text-center">
                                            <span class="bg-emerald-50 text-emerald-600 px-2 py-1 rounded-lg text-xs font-black" x-text="cust.total_visits + 'x Kunjungan'"></span>
                                        </td>
                                        <td class="p-3 text-slate-500 font-medium text-xs">
                                            <i class="fa-solid fa-star text-amber-400 mr-1 opacity-50"></i> 
                                            <span x-text="cust.favorite_items"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
    
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>