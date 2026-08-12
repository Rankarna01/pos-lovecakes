<?php
require_once '../../../config/auth.php';
$platform_code = trim($_GET['platform'] ?? 'grabfood');
$platform_names = [
    'grabfood' => 'GrabFood',
    'gofood' => 'GoFood',
    'shopeefood' => 'ShopeeFood',
    'travelokaeats' => 'TravelokaEats'
];
$platform_name = $platform_names[$platform_code] ?? ucfirst($platform_code);
$page_title = "Atur Produk $platform_name - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="foodDeliveryDetailApp('<?= $platform_code ?>')" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER APPS -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-white/80 hover:text-white p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-motorcycle text-amber-300"></i> <?= htmlspecialchars($platform_name) ?>
                    </h2>
                    <p class="text-[11px] text-blue-200 font-bold mt-0.5">Pengaturan harga jual khusus di <?= htmlspecialchars($platform_name) ?></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Button Bulk Markup (e.g. + 30.00 %) -->
                <button @click="openBulkMarkupModal()" class="bg-emerald-500 hover:bg-emerald-600 text-white font-black px-4 py-2.5 rounded-xl shadow-md transition-all flex items-center gap-2 text-xs sm:text-sm">
                    <i class="fa-solid fa-percent"></i>
                    <span x-text="'+ ' + currentMarkup + ' %'"></span>
                </button>

                <!-- Button Tambah Produk -->
                <button @click="openAddProductModal()" class="bg-white text-primary hover:bg-blue-50 font-black px-4 py-2.5 rounded-xl shadow-md transition-all flex items-center gap-2 text-xs sm:text-sm">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>+ Tambah Produk</span>
                </button>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50 space-y-6">
            <div class="w-full max-w-full space-y-6">

                <!-- TITLE BAR -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">Atur Produk</h3>
                        <p class="text-xs font-bold text-slate-400 mt-0.5">Atur daftar produk dan harga yang dijual di platform <?= htmlspecialchars($platform_name) ?></p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500">Markup Aktif:</span>
                        <span class="bg-emerald-100 text-emerald-700 font-black px-3 py-1 rounded-xl text-xs" x-text="'+' + currentMarkup + '%'"></span>
                    </div>
                </div>

                <!-- FILTER BAR & SEARCH (WITH STORE / MULTI-TENANT FILTER) -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row gap-3 justify-between items-center">
                    
                    <div class="relative w-full md:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama produk, SKU, atau kategori..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-xs">
                    </div>

                    <div class="flex items-center gap-3 w-full md:w-auto overflow-x-auto custom-scrollbar pb-1 md:pb-0">
                        <!-- Store / Warehouse Filter (Multi-Tenant) -->
                        <select x-model="storeFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none text-slate-700">
                            <option value="all">🏢 Semua Store</option>
                            <template x-for="st in stores" :key="st.id">
                                <option :value="st.id" x-text="'🏬 ' + st.name"></option>
                            </template>
                        </select>

                        <!-- Type Filter -->
                        <select x-model="typeFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none text-slate-700">
                            <option value="all">Semua Tipe Produk</option>
                            <option value="product">🛍️ Katalog Produk</option>
                            <option value="custom_reguler">📦 Custom Reguler</option>
                            <option value="custom_po">🎂 Custom PO</option>
                        </select>

                        <!-- Status Filter -->
                        <select x-model="statusFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none text-slate-700">
                            <option value="all">Semua Status</option>
                            <option value="1">Hanya Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>
                </div>

                <!-- TABEL UTAMA PRODUK PLATFORM -->
                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden relative">
                    
                    <!-- Loading overlay -->
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-xs">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-wider">
                                    <th class="p-4 w-10 text-center">
                                        <input type="checkbox" @change="selectAll($event)" class="rounded text-primary focus:ring-primary">
                                    </th>
                                    <th class="p-4 font-black">Nama Produk</th>
                                    <th class="p-4 font-black">Kategori</th>
                                    <th class="p-4 font-black">Store / Outlet</th>
                                    <th class="p-4 font-black text-right">Harga Normal</th>
                                    <th class="p-4 font-black text-right">Harga <?= htmlspecialchars($platform_name) ?></th>
                                    <th class="p-4 font-black text-center">Status Aktif</th>
                                    <th class="p-4 font-black text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100 font-medium">
                                
                                <template x-for="(item, index) in filteredItems" :key="item.price_setting_id">
                                    <tr class="hover:bg-slate-50/80 transition-colors" :class="item.is_active == 1 ? '' : 'opacity-50 bg-slate-50/50'">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" :value="item.price_setting_id" x-model="selectedIds" class="rounded text-primary focus:ring-primary">
                                        </td>

                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <p class="font-black text-slate-800 text-sm" x-text="item.item_name"></p>
                                                <template x-if="item.item_type === 'custom_reguler'">
                                                    <span class="text-[9px] bg-amber-100 text-amber-700 font-bold px-1.5 py-0.5 rounded">REGULER</span>
                                                </template>
                                                <template x-if="item.item_type === 'custom_po'">
                                                    <span class="text-[9px] bg-purple-100 text-purple-700 font-bold px-1.5 py-0.5 rounded">PO</span>
                                                </template>
                                            </div>
                                            <p class="text-[10px] font-mono text-slate-400" x-text="item.item_code"></p>
                                        </td>

                                        <td class="p-4">
                                            <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg text-xs font-bold uppercase" x-text="item.item_category || 'Others'"></span>
                                        </td>

                                        <!-- STORE / OUTLET BADGE -->
                                        <td class="p-4">
                                            <span class="inline-flex items-center gap-1 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded-lg text-xs font-black text-amber-800">
                                                <i class="fa-solid fa-store text-amber-500 text-[10px]"></i>
                                                <span x-text="item.store_name || 'Store 01'"></span>
                                            </span>
                                        </td>

                                        <td class="p-4 text-right font-bold text-slate-500" x-text="'IDR ' + formatRupiah(item.base_price)"></td>

                                        <!-- HARGA PLATFORM + PENSIL EDIT -->
                                        <td class="p-4 text-right">
                                            <div class="inline-flex items-center gap-2 font-black text-emerald-600">
                                                <span x-text="'IDR ' + formatRupiah(item.final_price)"></span>
                                                <button @click="editPriceModal(item)" class="text-slate-400 hover:text-primary transition-colors text-xs" title="Edit Harga Manual">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                            </div>
                                        </td>

                                        <!-- TOGGLE SWITCH STATUS -->
                                        <td class="p-4 text-center">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" :checked="item.is_active == 1" @change="toggleActive(item)" class="sr-only peer">
                                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                            </label>
                                        </td>

                                        <!-- ACTION HAPUS -->
                                        <td class="p-4 text-center">
                                            <button @click="removeItem(item)" class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all text-xs" title="Hapus dari Platform">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="filteredItems.length === 0 && !isLoading">
                                    <td colspan="8" class="p-10 text-center text-slate-400 font-bold">
                                        Belum ada produk yang ditambahkan untuk <?= htmlspecialchars($platform_name) ?>. Klik <b>+ Tambah Produk</b> di pojok kanan atas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL 1: BULK MARKUP PERSENTASE (+30%) -->
    <div x-show="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showBulkModal = false"></div>
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 flex flex-col overflow-hidden border border-slate-200">
            
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-emerald-500 text-white">
                <h3 class="font-black text-base flex items-center gap-2"><i class="fa-solid fa-percent"></i> Atur Persentase Markup Massal</h3>
                <button @click="showBulkModal = false" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <div class="p-6 space-y-4 text-xs font-bold">
                <div>
                    <label class="block text-slate-500 uppercase mb-1.5">Persentase Markup (%)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-emerald-500 text-lg">+</span>
                        <input type="number" step="0.5" x-model.number="bulkMarkupInput" placeholder="30.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-12 py-3 text-lg font-black text-slate-800 outline-none focus:border-emerald-500">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 font-black text-slate-400">%</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Sistem akan otomatis menghitung: <code>Harga <?= htmlspecialchars($platform_name) ?> = Harga Normal + X%</code> dengan pembulatan ke ratusan terdekat.</p>
                </div>

                <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl text-emerald-800 text-[11px]">
                    Target: <span class="font-black" x-text="selectedIds.length > 0 ? selectedIds.length + ' produk terpilih' : 'Semua produk di platform ini (' + items.length + ' produk)'"></span>
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-2">
                <button @click="applyBulkMarkup()" :disabled="isProcessing" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-black py-3 rounded-xl transition-all disabled:opacity-50 text-xs shadow-md">
                    Terapkan Persentase
                </button>
                <button @click="showBulkModal = false" class="px-5 bg-white border border-slate-200 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-100 transition-all text-xs">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: TAMBAH PRODUK KE PLATFORM (DENGAN TOMBOL 'PILIH SEMUA' & STORE FILTER) -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showAddModal = false"></div>
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[85vh] overflow-hidden border border-slate-200">
            
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-primary text-white shrink-0">
                <h3 class="font-black text-base flex items-center gap-2"><i class="fa-solid fa-plus-circle"></i> Pilih Produk untuk <?= htmlspecialchars($platform_name) ?></h3>
                <button @click="showAddModal = false" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <!-- Search & Store filter inside modal -->
            <div class="p-4 border-b border-slate-100 bg-slate-50 flex flex-col sm:flex-row gap-3 justify-between items-center shrink-0">
                <div class="relative flex-1 w-full">
                    <input type="text" x-model="modalSearchQuery" placeholder="Cari produk yang ingin ditambahkan..." class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                </div>

                <select x-model="modalStoreFilter" class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none text-slate-700 w-full sm:w-auto">
                    <option value="all">🏢 Semua Store</option>
                    <template x-for="st in stores" :key="st.id">
                        <option :value="st.id" x-text="'🏬 ' + st.name"></option>
                    </template>
                </select>
            </div>

            <!-- TOOLBAR PILIH SEMUA (SELECT ALL) -->
            <div class="px-4 py-2.5 bg-blue-50/60 border-b border-blue-100 flex items-center justify-between shrink-0">
                <label class="flex items-center gap-2.5 cursor-pointer font-black text-xs text-primary select-none">
                    <input type="checkbox" :checked="isAllModalSelected" @change="toggleSelectAllModal($event)" class="w-4 h-4 rounded text-primary focus:ring-primary">
                    <span>Pilih Semua Produk (<span x-text="filteredAvailableItems.filter(i => !i.is_added).length"></span>)</span>
                </label>

                <span class="text-[11px] font-bold text-slate-500" x-text="selectedAddItems.length + ' Produk Dicenang'"></span>
            </div>

            <!-- List Products -->
            <div class="p-4 flex-1 overflow-y-auto custom-scrollbar space-y-2">
                <template x-for="item in filteredAvailableItems" :key="item.item_type + '_' + item.id">
                    <div class="flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 hover:border-primary/50 hover:bg-blue-50/20 transition-all" :class="item.is_added ? 'bg-slate-50/50 opacity-60' : 'bg-white'">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" :value="item.item_type + '_' + item.id" x-model="selectedAddItems" :disabled="item.is_added" class="w-4 h-4 rounded text-primary focus:ring-primary cursor-pointer">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-black text-slate-800 text-xs" x-text="item.name"></p>
                                    <span class="inline-flex items-center gap-1 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded text-[9px] font-black text-amber-800">
                                        <i class="fa-solid fa-store text-amber-500 text-[8px]"></i>
                                        <span x-text="item.store_name || 'Store 01'"></span>
                                    </span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="'Harga Normal: Rp ' + formatRupiah(item.price)"></p>
                            </div>
                        </div>

                        <span x-show="item.is_added" class="text-[10px] font-black text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-xl">Sudah Ditambahkan</span>
                    </div>
                </template>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-between items-center shrink-0">
                <span class="text-xs font-bold text-slate-500" x-text="selectedAddItems.length + ' Produk dipilih'"></span>
                <div class="flex gap-2">
                    <button @click="submitAddProducts()" :disabled="selectedAddItems.length === 0 || isProcessing" class="bg-primary hover:bg-blue-600 text-white font-black px-6 py-2.5 rounded-xl transition-all disabled:opacity-50 text-xs shadow-md">
                        Tambahkan Produk
                    </button>
                    <button @click="showAddModal = false" class="px-5 bg-white border border-slate-200 text-slate-600 font-bold py-2.5 rounded-xl hover:bg-slate-100 transition-all text-xs">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
