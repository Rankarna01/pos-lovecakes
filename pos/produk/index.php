<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
// Path turun 2 kali ke root folder
$base_path = '../../'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk & Inventori - Love Cakes POS</title>
    
    <!-- FONT & ICONS -->
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/fontawesome.css">
    <style>
        @font-face {
            font-family: 'Poppins';
            src: url('<?= $base_path ?>assets/fonts/poppins.woff2') format('woff2');
            font-weight: normal; font-style: normal;
        }
        body { font-family: 'Poppins', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        /* Mencegah UI berkedip sebelum AlpineJS dimuat */
        [x-cloak] { display: none !important; }
    </style>

    <!-- LIBRARY JS OFFLINE WAJIB -->
    <script src="<?= $base_path ?>assets/js/sweetalert2.all.min.js"></script>
    <script src="<?= $base_path ?>assets/js/localforage.min.js"></script>
    <script src="<?= $base_path ?>assets/js/pos_db.js"></script>
    
    <!-- ALPINE JS (Wajib 'defer' agar jalan setelah HTML selesai dimuat) -->
    <script src="<?= $base_path ?>assets/js/alpine.min.js" defer></script>
    
    <!-- TAILWIND CSS -->
    <script src="<?= $base_path ?>assets/js/tailwind.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#2563EB', danger: '#ef4444', surface: '#ffffff' } } }
        }
    </script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased" x-data="produkApp()" x-cloak>

    <!-- SIDEBAR -->
    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-primary text-white shadow-sm px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-bold tracking-wide">Katalog Produk</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Tombol Sync Data (Tarik dari kokowms) -->
                <button @click="syncDataFromPusat()" :disabled="isSyncing" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 disabled:opacity-50">
                    <i class="fa-solid fa-rotate" :class="isSyncing ? 'fa-spin' : ''"></i> 
                    <span class="hidden sm:inline" x-text="isSyncing ? 'Menarik Data...' : 'Sync Pusat'"></span>
                </button>

                <div class="border-l border-blue-400 pl-3 ml-1">
                    <button onclick="logoutSistem()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50 relative">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Filter & Pencarian -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 justify-between items-center">
                    <div class="relative w-full sm:w-96">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" @input="filterProducts()" placeholder="Cari nama atau kode produk..." class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-medium text-sm transition-all">
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
                        <button @click="setCategory('Semua')" :class="activeCategory === 'Semua' ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all">Semua</button>
                        <button @click="setCategory('Roti Manis')" :class="activeCategory === 'Roti Manis' ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all">Roti Manis</button>
                        <button @click="setCategory('Roti Tawar')" :class="activeCategory === 'Roti Tawar' ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all">Roti Tawar</button>
                        <button @click="setCategory('Kue Kering')" :class="activeCategory === 'Kue Kering' ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all">Kue Kering</button>
                        <button @click="setCategory('Bolu')" :class="activeCategory === 'Bolu' ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all">Bolu</button>
                    </div>
                </div>

                <!-- Loading State Internal -->
                <div x-show="isLoading" class="text-center py-20">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-3"></i>
                    <p class="text-slate-500 font-bold">Memuat Katalog...</p>
                </div>

                <!-- Grid Produk -->
                <div x-show="!isLoading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <template x-for="item in filteredProducts" :key="item.id">
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg transition-all group flex flex-col h-full cursor-pointer">
                            <!-- Image Container -->
                            <div class="relative pt-[100%] bg-slate-100 overflow-hidden">
                                <img :src="item.image && item.image !== 'no-image.png' ? 'http://localhost/SIM-PRODUKSI-KUE/assets/img/' + item.image : '../../assets/img/no-image.png'" 
                                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                     onerror="this.onerror=null; this.src='../../assets/img/no-image.png';">
                                
                                <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg text-[10px] font-black text-slate-700 shadow-sm" x-text="item.category"></div>
                            </div>
                            
                            <!-- Info Container -->
                            <div class="p-4 flex flex-col flex-1">
                                <p class="text-[10px] font-black text-slate-400 mb-1 tracking-widest uppercase" x-text="item.code"></p>
                                <h3 class="font-bold text-sm text-slate-800 leading-tight mb-3 line-clamp-2" x-text="item.name"></h3>
                                
                                <!-- BLOK HARGA & STOK DENGAN UI BARU -->
                                <div class="mt-auto flex flex-col gap-2">
                                    
                                    <div class="flex justify-between items-center bg-slate-50 p-2 rounded-lg border border-slate-100">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"><i class="fa-solid fa-store mr-1 text-slate-400"></i>Toko</span>
                                        <span class="font-black text-emerald-600 text-sm" x-text="'Rp ' + formatRupiah(item.offline_price)"></span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center bg-blue-50/50 p-2 rounded-lg border border-blue-100/50">
                                        <span class="text-[10px] font-bold text-blue-500 uppercase tracking-wider"><i class="fa-solid fa-motorcycle mr-1"></i>Online</span>
                                        <span class="font-black text-blue-600 text-sm" x-text="'Rp ' + formatRupiah(item.online_price)"></span>
                                    </div>

                                    <div class="mt-1 flex justify-end">
                                        <div class="text-[10px] font-black px-3 py-1.5 rounded-lg border transition-all" 
                                             :class="item.stock > 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-500'" 
                                             x-text="item.stock > 0 ? 'STOK: ' + item.stock : 'HABIS'">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State (Jika Kosong) -->
                <div x-show="filteredProducts.length === 0 && !isLoading" class="text-center py-20 bg-white rounded-3xl border border-slate-200 border-dashed">
                    <div class="text-slate-300 text-6xl mb-4"><i class="fa-solid fa-box-open"></i></div>
                    <h3 class="text-xl font-bold text-slate-700">Tidak ada produk ditemukan</h3>
                    <p class="text-slate-500 text-sm mt-2 mb-6">Silakan lakukan "Sync Pusat" untuk menarik data terbaru dari database utama.</p>
                    <button @click="syncDataFromPusat()" class="bg-primary text-white px-6 py-3 rounded-xl font-bold shadow-md shadow-primary/20 hover:-translate-y-1 transition-transform"><i class="fa-solid fa-cloud-arrow-down mr-2"></i> Tarik Data Sekarang</button>
                </div>

                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- Gunakan versi dinamis agar browser tidak cache javascript lama -->
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>