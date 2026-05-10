<?php
// pos/cetak_barcode/index.php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Cetak Barcode Massal - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="barcodeApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-barcode mr-2"></i>Cetak Barcode SKU</h2>
            </div>
        </header>

        <main class="flex-1 overflow-hidden p-4 md:p-6 bg-[#f8fafc] flex flex-col md:flex-row gap-6">
            
            <div class="w-full md:w-7/12 lg:w-2/3 bg-white rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50">
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama produk atau SKU..." class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 font-bold text-slate-700">
                    </div>
                </div>
                
                <div x-show="isLoading" class="p-10 flex justify-center">
                    <div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
                </div>

                <div x-show="!isLoading" class="flex-1 overflow-y-auto custom-scrollbar p-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="p in filteredProducts" :key="p.id">
                            <div class="border border-slate-200 rounded-xl p-3 flex justify-between items-center hover:border-primary/50 hover:shadow-md transition-all cursor-pointer bg-white" @click="addToQueue(p)">
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm mb-0.5" x-text="p.name"></h4>
                                    <p class="text-xs text-slate-500 font-mono" x-text="'SKU: ' + p.code"></p>
                                </div>
                                <button class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors shrink-0">
                                    <i class="fa-solid fa-plus text-sm"></i>
                                </button>
                            </div>
                        </template>
                        <div x-show="filteredProducts.length === 0" class="col-span-full p-10 text-center text-slate-400 font-bold">
                            Tidak ada produk dengan SKU ditemukan.
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-5/12 lg:w-1/3 bg-white rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-col overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-primary text-white">
                    <h3 class="font-black uppercase tracking-widest text-sm"><i class="fa-solid fa-print mr-2"></i>Antrean Cetak</h3>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-3">
                    <template x-for="(item, index) in printQueue" :key="item.id">
                        <div class="border border-slate-200 rounded-xl p-3 bg-slate-50 relative group">
                            <button @click="removeFromQueue(index)" class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 text-white rounded-full text-[10px] hidden group-hover:flex items-center justify-center shadow-md hover:bg-rose-600 transition-all z-10"><i class="fa-solid fa-xmark"></i></button>
                            
                            <h4 class="font-bold text-slate-800 text-sm line-clamp-1" x-text="item.name"></h4>
                            <p class="text-[10px] text-slate-500 font-mono mb-2" x-text="item.code"></p>
                            
                            <div class="flex items-center justify-between border-t border-slate-200 pt-2 mt-2">
                                <span class="text-xs font-bold text-slate-600">Jml Stiker:</span>
                                <div class="flex items-center gap-2">
                                    <button @click="if(item.printQty > 1) item.printQty--" class="w-6 h-6 rounded bg-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-300"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                    <input type="number" x-model.number="item.printQty" min="1" class="w-12 text-center bg-white border border-slate-300 rounded text-sm font-bold py-0.5 outline-none">
                                    <button @click="item.printQty++" class="w-6 h-6 rounded bg-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-300"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="printQueue.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 p-6 text-center">
                        <i class="fa-solid fa-box-open text-4xl mb-3 opacity-50"></i>
                        <p class="text-sm font-bold">Keranjang cetak kosong.</p>
                        <p class="text-xs">Pilih produk dari daftar di sebelah kiri.</p>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-100 bg-white">
                    <div class="flex justify-between items-center mb-3 px-1">
                        <span class="text-sm font-bold text-slate-500">Total Stiker:</span>
                        <span class="text-lg font-black text-primary" x-text="totalStickers + ' Lembar'"></span>
                    </div>
                    <button @click="generateBulkPrint()" :disabled="printQueue.length === 0" class="w-full bg-emerald-500 hover:bg-emerald-600 disabled:bg-slate-300 text-white font-black py-3.5 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-print"></i> CETAK SEMUA BARCODE
                    </button>
                </div>
            </div>

        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>