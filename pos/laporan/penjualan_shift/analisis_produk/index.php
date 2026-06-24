<?php
require_once '../../../../config/auth.php';
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Analisis Produk - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../../components/header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="reportApp()" x-cloak>
    
    <?php include '../../../../components/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-chart-pie mr-2"></i>Analisis Produk Laku</h2>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto custom-scrollbar p-4 md:p-6 relative">
            
            <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/70 backdrop-blur-sm flex flex-col items-center justify-center">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-3"></i>
                <span class="font-bold text-slate-500 uppercase tracking-widest text-sm">Menarik Data Laporan...</span>
            </div>

            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- FILTER & EXPORT -->
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                    <input type="date" x-model="startDate" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <span class="py-2 text-slate-400 font-bold text-xs">s/d</span>
                    <input type="date" x-model="endDate" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <button @click="fetchReport()" :disabled="isLoading" class="bg-primary hover:bg-slate-200 text-primary px-6 py-2.5 rounded-xl font-black transition-all flex items-center gap-2 shadow-sm disabled:opacity-50">
                        <i class="fa-solid fa-magnifying-glass"></i> Tampilkan
                    </button>
                    
                    <div class="flex gap-2 sm:ml-auto">
                        <button @click="printPdf()" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2.5 rounded-xl font-black transition-all shadow-sm shadow-rose-500/30 flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> Cetak PDF
                        </button>
                        <button @click="exportExcel()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-black transition-all shadow-sm shadow-emerald-500/30 flex items-center gap-2">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>

                <!-- KATEGORI & ITEM TERLARIS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-layer-group text-purple-400 mr-2"></i> Penjualan per Kategori
                        </h3>
                        <div class="overflow-y-auto max-h-[500px] custom-scrollbar pr-2 space-y-3">
                            <template x-for="cat in paginatedCategory" :key="cat.category_name">
                                <div class="flex justify-between items-center border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                                    <div>
                                        <div class="font-bold text-sm text-slate-700" x-text="cat.category_name"></div>
                                        <div class="text-[10px] text-slate-500 font-medium" x-text="cat.total_qty + ' items terjual'"></div>
                                    </div>
                                    <div class="font-black text-primary text-sm" x-text="'Rp ' + formatRupiah(cat.total_amount)"></div>
                                </div>
                            </template>
                            <div x-show="salesByCategory.length === 0" class="text-center py-4 text-xs font-bold text-slate-400">Belum ada data</div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center" x-show="salesByCategory.length > 0">
                            <span class="text-[10px] text-slate-500 font-bold" x-text="'Halaman ' + catPage + ' dari ' + totalCatPages"></span>
                            <div class="flex gap-2">
                                <button @click="prevCat()" :disabled="catPage <= 1" class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-bold disabled:opacity-50 hover:bg-slate-100 transition-colors"><i class="fa-solid fa-chevron-left"></i></button>
                                <button @click="nextCat()" :disabled="catPage >= totalCatPages" class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-bold disabled:opacity-50 hover:bg-slate-100 transition-colors"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-ranking-star text-amber-400 mr-2"></i> Barang Terlaris
                        </h3>
                        <div class="overflow-y-auto max-h-[500px] custom-scrollbar pr-2 space-y-3">
                            <template x-for="(item, index) in paginatedItem" :key="item.item_name">
                                <div class="flex justify-between items-center border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-black" x-text="((itemPage - 1) * itemsPerPage) + index + 1"></span>
                                        <div>
                                            <div class="font-bold text-sm text-slate-700" x-text="item.item_name"></div>
                                            <div class="text-[10px] text-slate-500 font-medium" x-text="item.total_qty + ' pcs terjual'"></div>
                                        </div>
                                    </div>
                                    <div class="font-black text-primary text-sm" x-text="'Rp ' + formatRupiah(item.total_amount)"></div>
                                </div>
                            </template>
                            <div x-show="salesByItem.length === 0" class="text-center py-4 text-xs font-bold text-slate-400">Belum ada data</div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center" x-show="salesByItem.length > 0">
                            <span class="text-[10px] text-slate-500 font-bold" x-text="'Halaman ' + itemPage + ' dari ' + totalItemPages"></span>
                            <div class="flex gap-2">
                                <button @click="prevItem()" :disabled="itemPage <= 1" class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-bold disabled:opacity-50 hover:bg-slate-100 transition-colors"><i class="fa-solid fa-chevron-left"></i></button>
                                <button @click="nextItem()" :disabled="itemPage >= totalItemPages" class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-bold disabled:opacity-50 hover:bg-slate-100 transition-colors"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
