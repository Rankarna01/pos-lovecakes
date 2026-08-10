<?php
require_once '../../../config/auth.php';
$page_title = "Kelola Item & Harga Dinamis - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="customItemsApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER APPS -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-amber-300"></i> Kelola Item & Harga Dinamis
                    </h2>
                    <p class="text-[11px] text-blue-200 font-bold mt-0.5">Pengaturan hak ubah harga oleh Kasir saat transaksi POS (Katalog Produk & Item Custom)</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="openAddModal()" class="bg-white text-primary hover:bg-blue-50 font-black px-5 py-2.5 rounded-xl shadow-md transition-all flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-plus-circle text-base"></i> Tambah Item Baru
                </button>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50 space-y-6">
            <div class="w-full max-w-full space-y-6">

                <!-- SUMMARY STATS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Total Item & Template</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-0.5" x-text="summary.total"></h3>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-toggle-on"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-emerald-600 uppercase tracking-widest flex items-center gap-1">
                                <span>Harga Dinamis (Kasir)</span> ✅
                            </p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-0.5" x-text="summary.dynamic"></h3>
                            <p class="text-[10px] font-bold text-slate-400">Harga bisa diedit Kasir saat transaksi</p>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-rose-200 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-rose-600 uppercase tracking-widest flex items-center gap-1">
                                <span>Harga Fixed / Tetap</span> ❌
                            </p>
                            <h3 class="text-2xl font-black text-rose-600 mt-0.5" x-text="summary.fixed"></h3>
                            <p class="text-[10px] font-bold text-slate-400">Harga dikunci, tidak bisa diedit Kasir</p>
                        </div>
                    </div>
                </div>

                <!-- FILTER BAR -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row gap-3 justify-between items-center">
                    
                    <div class="relative w-full md:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama item, SKU, atau jenis..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-xs">
                    </div>

                    <div class="flex items-center gap-3 w-full md:w-auto overflow-x-auto custom-scrollbar pb-1 md:pb-0">
                        <!-- Type Filter -->
                        <select x-model="typeFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                            <option value="all">Semua Jenis Item</option>
                            <option value="product">🛍️ Katalog Produk</option>
                            <option value="custom_reguler">📦 Item Custom Reguler</option>
                            <option value="custom_po">🎂 Item Custom PO</option>
                        </select>

                        <!-- Status Filter -->
                        <select x-model="statusFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                            <option value="all">Semua Status Harga</option>
                            <option value="1">Harga Dinamis ✅ (Bisa Edit)</option>
                            <option value="0">Harga Tetap ❌ (Dikunci)</option>
                        </select>
                    </div>
                </div>

                <!-- TABEL UTAMA PRODUK & HARGA DINAMIS -->
                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden relative">
                    
                    <!-- Loading overlay -->
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-xs">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-wider">
                                    <th class="p-4 font-black">No</th>
                                    <th class="p-4 font-black">SKU / Kode</th>
                                    <th class="p-4 font-black">Jenis Item</th>
                                    <th class="p-4 font-black">Nama Item / Produk</th>
                                    <th class="p-4 font-black text-right">Harga Default (Rp)</th>
                                    <th class="p-4 font-black text-center">Status Setting Kasir</th>
                                    <th class="p-4 font-black text-center">Aksi Toggle Dinamis</th>
                                    <th class="p-4 font-black text-center">Aksi Item</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100 font-medium">
                                
                                <template x-for="(item, index) in filteredItems" :key="item.item_type + '_' + item.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors" :class="item.is_custom_price == 1 ? 'bg-emerald-50/20' : ''">
                                        <td class="p-4 font-bold text-slate-400 text-xs" x-text="index + 1"></td>
                                        <td class="p-4 font-mono font-bold text-slate-600 text-xs" x-text="item.code || '-'"></td>
                                        
                                        <!-- Item Type Badge -->
                                        <td class="p-4">
                                            <template x-if="item.item_type === 'product'">
                                                <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-lg text-xs font-black">
                                                    🛍️ Katalog Produk
                                                </span>
                                            </template>
                                            <template x-if="item.item_type === 'custom_reguler'">
                                                <span class="bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-lg text-xs font-black">
                                                    📦 Custom Reguler
                                                </span>
                                            </template>
                                            <template x-if="item.item_type === 'custom_po'">
                                                <span class="bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-1 rounded-lg text-xs font-black">
                                                    🎂 Custom PO
                                                </span>
                                            </template>
                                        </td>

                                        <td class="p-4">
                                            <p class="font-black text-slate-800 text-sm" x-text="item.name"></p>
                                        </td>

                                        <td class="p-4 text-right font-black text-slate-800" x-text="'Rp ' + formatRupiah(item.price)"></td>
                                        
                                        <!-- Badges Status -->
                                        <td class="p-4 text-center">
                                            <template x-if="item.is_custom_price == 1">
                                                <span class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-xl text-xs font-black inline-flex items-center gap-1.5 shadow-xs">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    HARGA DINAMIS ✅
                                                </span>
                                            </template>
                                            <template x-if="item.is_custom_price != 1">
                                                <span class="bg-slate-100 text-slate-600 border border-slate-200 px-3 py-1 rounded-xl text-xs font-bold inline-flex items-center gap-1.5">
                                                    <i class="fa-solid fa-lock text-[10px] text-slate-400"></i>
                                                    HARGA TETAP ❌
                                                </span>
                                            </template>
                                        </td>

                                        <!-- Interactive Toggle Switch -->
                                        <td class="p-4 text-center">
                                            <button @click="toggleDynamicStatus(item)" 
                                                :class="item.is_custom_price == 1 ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-slate-200 hover:bg-slate-300 text-slate-700'"
                                                class="px-4 py-1.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 mx-auto">
                                                <i class="fa-solid" :class="item.is_custom_price == 1 ? 'fa-toggle-on text-base' : 'fa-toggle-off text-base'"></i>
                                                <span x-text="item.is_custom_price == 1 ? 'Aktif (Dinamis)' : 'Non-Aktif (Fixed)'"></span>
                                            </button>
                                        </td>

                                        <!-- Action Buttons -->
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button @click="openEditModal(item)" class="p-2 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all text-xs font-bold" title="Edit Item">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button @click="deleteItem(item)" class="p-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all text-xs font-bold" title="Hapus Item">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="filteredItems.length === 0 && !isLoading">
                                    <td colspan="8" class="p-10 text-center text-slate-400 font-bold">
                                        Tidak ada item / produk ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH / EDIT ITEM -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showModal = false"></div>
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col overflow-hidden border border-slate-200">
            
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-primary text-white">
                <h3 class="font-black text-base" x-text="modalTitle"></h3>
                <button @click="showModal = false" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <div class="p-6 space-y-4 text-xs font-bold">
                <div>
                    <label class="block text-slate-500 uppercase mb-1.5">Jenis Item</label>
                    <select x-model="itemForm.item_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold outline-none">
                        <option value="custom_reguler">📦 Item Custom Reguler</option>
                        <option value="custom_po">🎂 Item Custom PO</option>
                        <option value="product">🛍️ Katalog Produk</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-500 uppercase mb-1.5">Nama Item / Produk <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="itemForm.name" placeholder="Misal: Roti Coklat, Kue Lapis legit..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-bold text-slate-800 outline-none focus:border-primary">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-500 uppercase mb-1.5">Kode / SKU</label>
                        <input type="text" x-model="itemForm.code" placeholder="SKU-001" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-mono font-bold text-slate-800 outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-slate-500 uppercase mb-1.5">Kategori</label>
                        <input type="text" x-model="itemForm.category" placeholder="Roti Manis, Bolu..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-bold text-slate-800 outline-none focus:border-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-500 uppercase mb-1.5">Harga Default (Rp)</label>
                    <input type="number" x-model.number="itemForm.price" placeholder="6000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-black text-emerald-600 outline-none focus:border-primary">
                </div>

                <!-- SETTING HARGA DINAMIS CHECKBOX / SWITCH -->
                <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl space-y-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-black text-amber-900 text-sm">Setting Harga Dinamis</p>
                            <p class="text-[11px] text-amber-700 font-medium">Izinkan Kasir mengubah harga barang ini saat transaksi POS</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="itemForm.is_custom_price" :checked="itemForm.is_custom_price == 1" @change="itemForm.is_custom_price = $event.target.checked ? 1 : 0" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>
                    <div class="text-[11px] font-bold" :class="itemForm.is_custom_price == 1 ? 'text-emerald-700' : 'text-slate-500'">
                        Status saat ini: 
                        <span class="font-black uppercase" x-text="itemForm.is_custom_price == 1 ? '✅ AKTIF (Kasir Bisa Edit Harga)' : '❌ NON-AKTIF (Harga Dikunci/Fixed)'"></span>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-2">
                <button @click="saveItem()" :disabled="isSaving" class="flex-1 bg-primary hover:bg-blue-600 text-white font-black py-3 rounded-xl transition-all disabled:opacity-50 text-xs shadow-md">
                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Item'"></span>
                </button>
                <button @click="showModal = false" class="px-5 bg-white border border-slate-200 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-100 transition-all text-xs">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
