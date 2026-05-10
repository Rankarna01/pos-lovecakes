<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Katalog Produk - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="produkApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-store mr-2"></i>Katalog Produk</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="syncDataFromPusat()" :disabled="isSyncing" class="bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 disabled:opacity-50 border border-white/10 shadow-sm">
                    <i class="fa-solid fa-rotate" :class="isSyncing ? 'fa-spin' : ''"></i> 
                    <span class="hidden sm:inline" x-text="isSyncing ? 'Menyinkronkan...' : 'Sync Database'"></span>
                </button>

                <div class="border-l border-blue-400 pl-3 ml-1">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm shadow-rose-500/30" title="Keluar">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <div class="bg-white p-2 sm:p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col xl:flex-row gap-3 justify-between items-center sticky top-0 z-10">
                    <div class="relative w-full xl:w-1/3">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" @input="filterProducts()" placeholder="Cari Roti, Kue, atau Kode..." class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary font-bold text-sm transition-all text-slate-700">
                    </div>
                    
                    <div class="flex items-center gap-2 w-full xl:w-auto overflow-x-auto custom-scrollbar pb-2 xl:pb-0 px-1">
                        <button @click="setCategory('Semua')" :class="activeCategory === 'Semua' ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-50 text-slate-500 border border-slate-200 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl text-xs font-black whitespace-nowrap transition-all uppercase tracking-wider">Semua</button>
                        <button @click="setCategory('Roti Manis')" :class="activeCategory === 'Roti Manis' ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-50 text-slate-500 border border-slate-200 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl text-xs font-black whitespace-nowrap transition-all uppercase tracking-wider">Roti Manis</button>
                        <button @click="setCategory('Roti Tawar')" :class="activeCategory === 'Roti Tawar' ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-50 text-slate-500 border border-slate-200 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl text-xs font-black whitespace-nowrap transition-all uppercase tracking-wider">Roti Tawar</button>
                        <button @click="setCategory('Kue Kering')" :class="activeCategory === 'Kue Kering' ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-50 text-slate-500 border border-slate-200 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl text-xs font-black whitespace-nowrap transition-all uppercase tracking-wider">Kue Kering</button>
                        <button @click="setCategory('Bolu')" :class="activeCategory === 'Bolu' ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-50 text-slate-500 border border-slate-200 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl text-xs font-black whitespace-nowrap transition-all uppercase tracking-wider">Bolu</button>
                    </div>
                </div>

                <div x-show="isLoading" class="text-center py-20 flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-500 font-bold tracking-widest uppercase text-sm">Menyiapkan Etalase...</p>
                </div>

                <div x-show="!isLoading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
                    <template x-for="item in filteredProducts" :key="item.id">
                        <div class="bg-white rounded-[1.5rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl hover:shadow-primary/5 hover:border-primary/30 transition-all duration-300 group flex flex-col h-full hover:-translate-y-1">
                            
                            <div class="relative pt-[100%] bg-slate-100 overflow-hidden border-b border-slate-100">
                                <img :src="item.image && item.image !== 'no-image.png' ? 'http://localhost/sim-produksi-kue/assets/img/' + item.image : '<?= BASE_URL ?>assets/img/no-image.png'" 
                                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                     onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/img/no-image.png';">
                                
                                <div class="absolute top-3 left-3 bg-white/95 backdrop-blur-md px-2.5 py-1 rounded-lg text-[9px] font-black text-slate-700 shadow-sm uppercase tracking-widest border border-slate-200/50" x-text="item.category || 'PRODUK'"></div>
                            </div>
                            
                            <div class="p-4 sm:p-5 flex flex-col flex-1 bg-white">
                                <div class="mb-3 flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 mb-1 tracking-widest uppercase" x-text="item.code"></p>
                                        <h3 class="font-bold text-sm sm:text-base text-slate-800 leading-snug line-clamp-2 min-h-[2.5rem]" x-text="item.name"></h3>
                                    </div>
                                </div>
                                
                                <div class="mt-auto flex flex-col gap-2.5">
                                    <div class="flex justify-between items-center bg-slate-50 p-2.5 rounded-xl border border-slate-100 group-hover:bg-emerald-50/50 transition-colors">
                                        <div class="flex items-center gap-1.5 text-slate-500">
                                            <i class="fa-solid fa-store text-[11px]"></i>
                                            <span class="text-[10px] font-black uppercase tracking-wider">Toko</span>
                                        </div>
                                        <div class="font-black text-emerald-600 text-sm sm:text-base" x-text="'Rp ' + formatRupiah(item.offline_price || item.price)"></div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center gap-2">
                                        <div class="px-2.5 py-1.5 rounded-xl border flex-1 flex items-center justify-center text-[10px] font-black tracking-wide" 
                                             :class="item.stock > 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-500'">
                                             <span x-text="item.stock > 0 ? 'STOK: ' + item.stock : 'HABIS'"></span>
                                        </div>
                                        
                                        <button @click="printBarcode(item)" class="bg-slate-100 hover:bg-slate-800 hover:text-white text-slate-600 w-8 h-8 rounded-xl flex items-center justify-center transition-colors border border-slate-200 hover:border-slate-800 shadow-sm" title="Cetak Stiker Barcode">
                                            <i class="fa-solid fa-barcode"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="filteredProducts.length === 0 && !isLoading" class="text-center py-20 bg-white rounded-[2rem] border border-slate-200 border-dashed">
                    <div class="text-slate-200 text-7xl mb-4 drop-shadow-sm"><i class="fa-solid fa-box-open"></i></div>
                    <h3 class="text-xl font-black text-slate-700">Etalase Kosong</h3>
                    <p class="text-slate-500 text-sm mt-2 mb-6 font-medium">Silakan tarik data terbaru dari database utama untuk memunculkan produk.</p>
                    <button @click="syncDataFromPusat()" class="bg-primary text-white px-6 py-3.5 rounded-2xl font-black shadow-lg shadow-primary/30 transition-all hover:scale-105 active:scale-95">
                        <i class="fa-solid fa-cloud-arrow-down mr-2"></i> Tarik Database Sekarang
                    </button>
                </div>

                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>