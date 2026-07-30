<?php
require_once '../../../config/auth.php';
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Analisa Produk & Kategori - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="analisaProdukApp()" x-cloak>
    <?php include '../../../components/sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-boxes-stacked mr-2"></i>Analisa Produk & Kategori</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 relative">
            
            <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-sm">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
            </div>

            <div class="w-full max-w-full space-y-6">
                
                <!-- FILTER BAR & AKSI -->
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2 flex-1 md:flex-none">
                        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5">
                            <i class="fa-solid fa-calendar-day text-slate-400"></i>
                            <input type="date" x-model="filters.start_date" class="bg-transparent text-sm font-bold text-slate-700 outline-none">
                            <span class="text-slate-400 font-bold text-sm">s/d</span>
                            <input type="date" x-model="filters.end_date" class="bg-transparent text-sm font-bold text-slate-700 outline-none">
                        </div>
                        <select x-model="filters.limit" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 outline-none focus:border-primary">
                            <option value="5">Top 5 Produk</option>
                            <option value="10">Top 10 Produk</option>
                            <option value="20">Top 20 Produk</option>
                            <option value="50">Top 50 Produk</option>
                        </select>
                        <button @click="fetchData()" class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-sm flex items-center gap-2 text-sm">
                            <i class="fa-solid fa-filter"></i> Terapkan
                        </button>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button @click="printPDF()" class="flex-1 sm:flex-none bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white px-4 py-2.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2 text-sm border border-indigo-200 shadow-sm">
                            <i class="fa-solid fa-print"></i> Cetak PDF
                        </button>
                        <button @click="exportCSV()" class="flex-1 sm:flex-none bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white px-4 py-2.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2 text-sm border border-emerald-200 shadow-sm">
                            <i class="fa-solid fa-file-excel"></i> Unduh Excel
                        </button>
                    </div>
                </div>

                <!-- 4 KPI CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-[1.5rem] border border-slate-200 shadow-sm relative overflow-hidden flex items-center justify-between group hover:border-primary transition-all">
                        <div class="z-10">
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Total Omset Kategori</p>
                            <h3 class="text-xl font-black text-slate-800 mt-1" x-text="'Rp ' + formatRupiah(totalRevenue)">Rp 0</h3>
                            <span class="text-[11px] font-bold text-primary mt-1 inline-block"><i class="fa-solid fa-chart-line mr-1"></i>Periode Terpilih</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-primary flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-[1.5rem] border border-slate-200 shadow-sm relative overflow-hidden flex items-center justify-between group hover:border-emerald-500 transition-all">
                        <div class="z-10">
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Total Item Terjual</p>
                            <h3 class="text-xl font-black text-slate-800 mt-1" x-text="totalQty + ' Pcs'">0 Pcs</h3>
                            <span class="text-[11px] font-bold text-emerald-600 mt-1 inline-block"><i class="fa-solid fa-boxes-stacked mr-1"></i>Akumulasi QTY</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-[1.5rem] border border-slate-200 shadow-sm relative overflow-hidden flex items-center justify-between group hover:border-purple-500 transition-all">
                        <div class="z-10">
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Kategori Terlaris</p>
                            <h3 class="text-lg font-black text-slate-800 mt-1 truncate max-w-[150px]" x-text="topCategory">-</h3>
                            <span class="text-[11px] font-bold text-purple-600 mt-1 inline-block"><i class="fa-solid fa-fire mr-1"></i>Penyumbang Omset</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-[1.5rem] border border-slate-200 shadow-sm relative overflow-hidden flex items-center justify-between group hover:border-amber-500 transition-all">
                        <div class="z-10">
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Produk Juara #1</p>
                            <h3 class="text-lg font-black text-slate-800 mt-1 truncate max-w-[150px]" x-text="topProduct">-</h3>
                            <span class="text-[11px] font-bold text-amber-600 mt-1 inline-block"><i class="fa-solid fa-crown mr-1"></i>Terbanyak Dibeli</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                    </div>
                </div>

                <!-- TOP & BOTTOM SELLERS SECTION -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- PRODUK PALING LAKU -->
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-emerald-200 relative overflow-hidden flex flex-col">
                        <div class="absolute -right-4 -top-4 opacity-5 text-emerald-500 text-8xl pointer-events-none"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        <div class="flex items-center justify-between mb-5 relative z-10">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black"><i class="fa-solid fa-arrow-up-right-dots"></i></div>
                                <div>
                                    <h3 class="font-black text-emerald-700 uppercase text-xs tracking-widest">Top <span x-text="filters.limit"></span> Produk Paling Laku</h3>
                                    <p class="text-[11px] font-bold text-slate-400">Diurutkan dari penjualan terbanyak</p>
                                </div>
                            </div>
                            <span class="text-xs font-black bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-lg">Best Seller</span>
                        </div>
                        
                        <div x-show="bestSellers.length === 0" class="flex-1 flex flex-col items-center justify-center text-slate-400 py-12">
                            <i class="fa-solid fa-box-open text-4xl mb-2 opacity-40"></i>
                            <span class="text-sm font-bold">Belum ada penjualan produk di periode ini.</span>
                        </div>

                        <div class="space-y-3 relative z-10">
                            <template x-for="(item, index) in bestSellers" :key="index">
                                <div class="relative overflow-hidden bg-slate-50 rounded-xl border border-slate-200/80 p-3 flex items-center justify-between group hover:border-emerald-400 transition-all">
                                    <!-- Visual Progress Bar -->
                                    <div class="absolute top-0 left-0 bottom-0 bg-emerald-500/10 transition-all duration-700" :style="'width: ' + getBarWidth(item.total_qty, maxBestQty) + '%'"></div>
                                    
                                    <div class="flex items-center gap-3 relative z-10">
                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-xs shadow-sm"
                                              :class="index === 0 ? 'bg-amber-400 text-amber-950' : (index === 1 ? 'bg-slate-300 text-slate-800' : (index === 2 ? 'bg-amber-700 text-white' : 'bg-white text-slate-600 border border-slate-200'))"
                                              x-text="'#' + (index + 1)"></span>
                                        <div>
                                            <div class="font-black text-slate-800 text-sm group-hover:text-emerald-700 transition-colors" x-text="item.product_name"></div>
                                            <div class="text-[11px] font-bold text-slate-400" x-text="'Omset: Rp ' + formatRupiah(item.total_revenue || 0)"></div>
                                        </div>
                                    </div>
                                    <div class="relative z-10 text-right">
                                        <span class="font-black text-emerald-600 bg-white px-3 py-1 rounded-lg shadow-sm border border-emerald-100 text-sm inline-block" x-text="item.total_qty + ' Pcs'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- PRODUK KURANG LAKU -->
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-rose-200 relative overflow-hidden flex flex-col">
                        <div class="absolute -right-4 -top-4 opacity-5 text-rose-500 text-8xl pointer-events-none"><i class="fa-solid fa-arrow-trend-down"></i></div>
                        <div class="flex items-center justify-between mb-5 relative z-10">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-black"><i class="fa-solid fa-arrow-down-right-dots"></i></div>
                                <div>
                                    <h3 class="font-black text-rose-700 uppercase text-xs tracking-widest">Top <span x-text="filters.limit"></span> Produk Kurang Laku</h3>
                                    <p class="text-[11px] font-bold text-slate-400">Diurutkan dari penjualan paling sedikit</p>
                                </div>
                            </div>
                            <span class="text-xs font-black bg-rose-100 text-rose-800 px-2.5 py-1 rounded-lg">Slow Moving</span>
                        </div>
                        
                        <div x-show="worstSellers.length === 0" class="flex-1 flex flex-col items-center justify-center text-slate-400 py-12">
                            <i class="fa-solid fa-box-open text-4xl mb-2 opacity-40"></i>
                            <span class="text-sm font-bold">Belum ada penjualan produk di periode ini.</span>
                        </div>

                        <div class="space-y-3 relative z-10">
                            <template x-for="(item, index) in worstSellers" :key="index">
                                <div class="relative overflow-hidden bg-slate-50 rounded-xl border border-slate-200/80 p-3 flex items-center justify-between group hover:border-rose-400 transition-all">
                                    <!-- Visual Progress Bar -->
                                    <div class="absolute top-0 left-0 bottom-0 bg-rose-500/10 transition-all duration-700" :style="'width: ' + getBarWidth(item.total_qty, maxWorstQty) + '%'"></div>
                                    
                                    <div class="flex items-center gap-3 relative z-10">
                                        <span class="w-7 h-7 rounded-lg bg-white text-slate-500 border border-slate-200 flex items-center justify-center font-black text-xs shadow-sm" x-text="'#' + (index + 1)"></span>
                                        <div>
                                            <div class="font-black text-slate-800 text-sm group-hover:text-rose-700 transition-colors" x-text="item.product_name"></div>
                                            <div class="text-[11px] font-bold text-slate-400" x-text="'Omset: Rp ' + formatRupiah(item.total_revenue || 0)"></div>
                                        </div>
                                    </div>
                                    <div class="relative z-10 text-right">
                                        <span class="font-black text-rose-600 bg-white px-3 py-1 rounded-lg shadow-sm border border-rose-100 text-sm inline-block" x-text="item.total_qty + ' Pcs'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ANALISA BERDASARKAN KATEGORI -->
                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                    <div class="flex flex-wrap items-center justify-between border-b border-slate-100 pb-4 mb-6 gap-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center text-lg font-black"><i class="fa-solid fa-chart-pie"></i></div>
                            <div>
                                <h3 class="font-black text-slate-800 uppercase text-sm tracking-wide">Penjualan Berdasarkan Kategori (Omset)</h3>
                                <p class="text-[11px] font-bold text-slate-400">Kontribusi omset dari tiap kategori produk</p>
                            </div>
                        </div>
                        <div class="bg-blue-50 border border-blue-100 px-4 py-2 rounded-xl">
                            <span class="text-xs font-bold text-slate-500">Total Omset Kategori: </span>
                            <span class="font-black text-primary text-base ml-1" x-text="'Rp ' + formatRupiah(totalRevenue)"></span>
                        </div>
                    </div>
                    
                    <div x-show="categories.length === 0" class="text-center py-12 text-slate-400">
                        <i class="fa-solid fa-folder-open text-4xl mb-2 opacity-40"></i>
                        <p class="text-sm font-bold">Tidak ada data omset kategori di periode ini.</p>
                    </div>
                    
                    <div x-show="categories.length > 0">
                        <!-- Progress Bar Visual -->
                        <div class="w-full bg-slate-100 rounded-2xl h-6 mb-6 flex overflow-hidden shadow-inner p-1 gap-1">
                            <template x-for="(cat, index) in categories" :key="index">
                                <div class="h-full rounded-xl transition-all duration-1000 relative group cursor-pointer flex items-center justify-center" 
                                     :class="colorPalettes[index % colorPalettes.length].bg" 
                                     :style="'width: ' + getPercentage(cat.total_revenue) + '%'"
                                     :title="cat.category_name + ' (' + getPercentage(cat.total_revenue) + '%)'">
                                     <span x-show="parseFloat(getPercentage(cat.total_revenue)) > 8" class="text-[10px] font-black text-white px-1 truncate shadow-sm" x-text="getPercentage(cat.total_revenue) + '%'"></span>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Grid Kartu Kategori -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <template x-for="(cat, index) in categories" :key="index">
                                <div class="bg-slate-50/80 hover:bg-white p-4 rounded-2xl border border-slate-200/80 hover:border-slate-300 hover:shadow-md transition-all flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3.5 h-3.5 rounded-full shadow-sm" :class="colorPalettes[index % colorPalettes.length].bg"></span> 
                                                <span class="font-black text-slate-800 text-sm truncate" x-text="cat.category_name"></span>
                                            </div>
                                            <span class="text-xs font-black px-2 py-0.5 rounded-md" :class="colorPalettes[index % colorPalettes.length].light + ' ' + colorPalettes[index % colorPalettes.length].text" x-text="getPercentage(cat.total_revenue) + '%'"></span>
                                        </div>
                                        <div class="font-black text-slate-900 text-lg mb-1" x-text="'Rp ' + formatRupiah(cat.total_revenue)"></div>
                                    </div>
                                    <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs font-bold text-slate-500">
                                        <span><i class="fa-solid fa-box mr-1 opacity-60"></i>Terjual</span>
                                        <span class="font-black text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200" x-text="cat.total_qty + ' Item'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>