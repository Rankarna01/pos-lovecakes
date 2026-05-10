<?php
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

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-emerald-200 relative overflow-hidden flex flex-col">
                        <div class="absolute -right-4 -top-4 opacity-5 text-emerald-500 text-8xl"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        <h3 class="font-black text-emerald-600 mb-4 uppercase text-xs tracking-widest relative z-10">5 Produk Paling Laku (Berdasarkan QTY)</h3>
                        
                        <div x-show="bestSellers.length === 0" class="flex-1 flex items-center justify-center text-slate-400 text-sm font-bold">Belum ada transaksi di periode ini.</div>
                        <div class="space-y-2 relative z-10">
                            <template x-for="(item, index) in bestSellers" :key="index">
                                <div class="flex justify-between p-2.5 bg-emerald-50 rounded-xl border border-emerald-100">
                                    <span class="font-bold text-slate-700"><span x-text="(index + 1) + '. '"></span> <span x-text="item.product_name"></span></span>
                                    <span class="font-black text-emerald-600 bg-white px-2 py-0.5 rounded shadow-sm" x-text="item.total_qty + ' Terjual'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-rose-200 relative overflow-hidden flex flex-col">
                        <div class="absolute -right-4 -top-4 opacity-5 text-rose-500 text-8xl"><i class="fa-solid fa-arrow-trend-down"></i></div>
                        <h3 class="font-black text-rose-600 mb-4 uppercase text-xs tracking-widest relative z-10">5 Produk Kurang Laku</h3>
                        
                        <div x-show="worstSellers.length === 0" class="flex-1 flex items-center justify-center text-slate-400 text-sm font-bold">Belum ada transaksi di periode ini.</div>
                        <div class="space-y-2 relative z-10">
                            <template x-for="(item, index) in worstSellers" :key="index">
                                <div class="flex justify-between p-2.5 bg-rose-50 rounded-xl border border-rose-100">
                                    <span class="font-bold text-slate-700"><span x-text="(index + 1) + '. '"></span> <span x-text="item.product_name"></span></span>
                                    <span class="font-black text-rose-500 bg-white px-2 py-0.5 rounded shadow-sm" x-text="item.total_qty + ' Terjual'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                    <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-2 flex justify-between items-center">
                        <span>Penjualan Berdasarkan Kategori (Omset)</span>
                        <span class="text-primary" x-text="'Total: Rp ' + formatRupiah(totalRevenue)"></span>
                    </h3>
                    
                    <div x-show="categories.length === 0" class="text-center py-6 text-slate-400 text-sm font-bold">Tidak ada data omset.</div>
                    
                    <div x-show="categories.length > 0">
                        <div class="w-full bg-slate-100 rounded-full h-5 mb-4 flex overflow-hidden shadow-inner">
                            <template x-for="(cat, index) in categories" :key="index">
                                <div class="h-5 transition-all duration-1000" 
                                     :class="colorPalettes[index % colorPalettes.length].bg" 
                                     :style="'width: ' + getPercentage(cat.total_revenue) + '%'"
                                     :title="cat.category_name + ' (' + getPercentage(cat.total_revenue) + '%)'">
                                </div>
                            </template>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm mt-5">
                            <template x-for="(cat, index) in categories" :key="index">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-3 h-3 rounded-full" :class="colorPalettes[index % colorPalettes.length].bg"></span> 
                                        <span class="font-bold text-slate-700 truncate" x-text="cat.category_name"></span>
                                    </div>
                                    <div class="pl-5">
                                        <div class="font-black text-slate-800" x-text="'Rp ' + formatRupiah(cat.total_revenue)"></div>
                                        <div class="text-[10px] font-bold text-slate-400" x-text="cat.total_qty + ' Item Laku (' + getPercentage(cat.total_revenue) + '%)'"></div>
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