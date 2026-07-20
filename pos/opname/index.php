<?php
require_once '../../config/auth.php';
$page_title = "Detail Stok Opname - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
    <style>
        .accordion-content { transition: max-height 0.3s ease-in-out; overflow: hidden; max-height: 0; }
        .accordion-content.open { max-height: 2000px; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="opnameApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0 shadow-sm">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-primary hover:bg-blue-50 p-2 rounded-xl transition-colors">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black text-slate-800 tracking-wide flex items-center gap-2">
                        Detail Stok Opname
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Buat dokumen penyesuaian stok fisik dapur dengan sistem secara real-time.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="postingOpname()" :disabled="isPosting || draftItems.length === 0" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-black text-xs sm:text-sm shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-cloud-arrow-up" :class="isPosting ? 'fa-bounce' : ''"></i> <span>POSTING OPNAME</span>
                </button>
                <div class="border-l border-slate-200 pl-3 ml-1">
                    <button onclick="doLogout()" class="bg-rose-50 hover:bg-rose-100 text-rose-500 w-10 h-10 rounded-xl flex items-center justify-center transition-all" title="Keluar">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- HEADER DOKUMEN -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex justify-between items-center group">
                        <div class="flex-1">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tanggal Opname</label>
                            <input type="date" x-model="tanggal" class="w-full text-base font-black text-slate-800 border-none p-0 focus:ring-0 bg-transparent outline-none cursor-pointer">
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex justify-between items-center group">
                        <div class="flex-1">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Catatan / Keterangan</label>
                            <input type="text" x-model="notes" placeholder="Contoh: Audit Stok Harian" class="w-full text-base font-bold text-slate-800 border-none p-0 focus:ring-0 bg-transparent outline-none placeholder:text-slate-300">
                        </div>
                    </div>
                </div>

                <!-- DAFTAR ITEM DRAFT -->
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-boxes-stacked text-blue-600"></i> Bahan Baku Stok Opname (<span x-text="draftItems.length"></span>)
                            </h3>
                        </div>
                        <button @click="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-black text-xs transition-colors flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-plus"></i> TAMBAH BAHAN
                        </button>
                    </div>

                    <!-- EMPTY DRAFT -->
                    <div x-show="draftItems.length === 0" class="p-16 text-center flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-3xl mb-4 text-slate-200 shadow-inner">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <p class="font-bold text-sm text-slate-400 italic">Belum ada bahan baku diopname.</p>
                        <p class="text-xs text-slate-400 mt-1 italic">Klik tombol <span class="font-bold text-blue-600">+ Tambah Bahan</span> untuk memasukkan data.</p>
                    </div>

                    <!-- DRAFT ITEMS -->
                    <div x-show="draftItems.length > 0" class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-[10px] text-slate-400 uppercase tracking-widest font-black">
                                    <th class="p-4 w-12 text-center">NO</th>
                                    <th class="p-4">NAMA BAHAN BAKU</th>
                                    <th class="p-4 text-center">WAKTU INPUT</th>
                                    <th class="p-4 text-center">QTY AKTUAL</th>
                                    <th class="p-4 text-center">QTY SYSTEM</th>
                                    <th class="p-4 text-center">QTY SELISIH</th>
                                    <th class="p-4 text-center w-16">AKSI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <template x-for="(item, idx) in draftItems" :key="item.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-4 text-center font-bold text-slate-400" x-text="idx + 1"></td>
                                        <td class="p-4">
                                            <div class="font-black text-slate-800 text-sm" x-text="item.name"></div>
                                            <div class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="item.code"></div>
                                        </td>
                                        <td class="p-4 text-center text-xs font-bold text-slate-500" x-text="item.time_added"></td>
                                        <td class="p-4 text-center">
                                            <input type="number" x-model.number="item.actual_stock" 
                                                   class="w-20 bg-white border border-slate-200 rounded-lg px-2 py-1.5 text-center font-black text-blue-600 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm mx-auto flex items-center justify-center">
                                        </td>
                                        <td class="p-4 text-center font-black text-slate-500" x-text="(item.system_stock || 0) + ' ' + (item.unit || '')"></td>
                                        <td class="p-4 text-center">
                                            <span class="px-2 py-1 rounded font-black text-xs inline-block min-w-[3rem]"
                                                  :class="(item.actual_stock - item.system_stock) > 0 ? 'bg-emerald-100 text-emerald-700' : ((item.actual_stock - item.system_stock) < 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500')"
                                                  x-text="((item.actual_stock - item.system_stock) > 0 ? '+' : '') + (item.actual_stock - item.system_stock)">
                                            </span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <button @click="hapusDraftItem(item.id)" class="w-8 h-8 rounded bg-rose-50 hover:bg-rose-100 text-rose-500 flex items-center justify-center transition-colors mx-auto" title="Hapus">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ACCORDION HISTORY -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <button @click="historyOpen = !historyOpen" class="w-full p-5 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors">
                        <h3 class="font-black text-slate-700 flex items-center gap-2 text-sm">
                            <i class="fa-solid fa-clock-rotate-left text-slate-400"></i> Riwayat Penyesuaian Stok Terakhir
                        </h3>
                        <i class="fa-solid transition-transform" :class="historyOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    
                    <div class="accordion-content" :class="historyOpen ? 'open' : ''">
                        <div class="p-5 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white">
                            <div class="relative flex-1 sm:max-w-xs">
                                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" x-model="searchHistory" @keyup.enter="loadHistory()" placeholder="Cari dokumen..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <button @click="loadHistory()" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-black transition-colors shrink-0">
                                <i class="fa-solid fa-rotate mr-1"></i> Refresh
                            </button>
                        </div>
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead class="bg-slate-50 border-y border-slate-100 text-[10px] text-slate-500 uppercase tracking-widest font-black">
                                    <tr>
                                        <th class="p-3 text-center">NO</th>
                                        <th class="p-3">WAKTU</th>
                                        <th class="p-3">DOKUMEN</th>
                                        <th class="p-3">BAHAN / PRODUK</th>
                                        <th class="p-3 text-center">STOK SISTEM</th>
                                        <th class="p-3 text-center text-primary">STOK FISIK</th>
                                        <th class="p-3 text-center">SELISIH</th>
                                        <th class="p-3 text-center">PETUGAS</th>
                                    </tr>
                                </thead>
                                <tbody class="text-xs divide-y divide-slate-100">
                                    <tr x-show="isLoadingHistory">
                                        <td colspan="8" class="p-6 text-center text-slate-400 font-bold">Memuat riwayat...</td>
                                    </tr>
                                    <tr x-show="!isLoadingHistory && historyRows.length === 0">
                                        <td colspan="8" class="p-6 text-center text-slate-400 font-bold">Belum ada riwayat.</td>
                                    </tr>
                                    <template x-for="(row, idx) in historyRows" :key="row.id">
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-3 text-center font-bold text-slate-400" x-text="idx + 1"></td>
                                            <td class="p-3">
                                                <div class="font-bold text-slate-700" x-text="row.created_at.split(' ')[0]"></div>
                                                <div class="text-[10px] text-slate-400" x-text="row.created_at.split(' ')[1]"></div>
                                            </td>
                                            <td class="p-3"><span class="bg-blue-50 text-blue-700 font-black px-2 py-0.5 rounded text-[10px]" x-text="row.doc_no"></span></td>
                                            <td class="p-3">
                                                <div class="font-black text-slate-800" x-text="row.product_name"></div>
                                                <div class="text-[9px] text-slate-400 font-bold" x-text="row.sku"></div>
                                            </td>
                                            <td class="p-3 text-center font-bold text-slate-600" x-text="row.system_stock"></td>
                                            <td class="p-3 text-center font-black text-blue-600 bg-blue-50/30" x-text="row.actual_stock"></td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-0.5 rounded font-black text-[10px]"
                                                      :class="row.difference > 0 ? 'bg-emerald-50 text-emerald-600' : (row.difference < 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-500')"
                                                      x-text="(row.difference > 0 ? '+' : '') + row.difference">
                                                </span>
                                            </td>
                                            <td class="p-3 text-center font-bold text-slate-600" x-text="row.admin_name"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH BAHAN BAKU -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-transition x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden" @click.outside="closeModal()">
            
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white">
                <h3 class="text-base font-black text-slate-800 flex items-center gap-2 uppercase tracking-widest">
                    <i class="fa-solid fa-basket-shopping text-blue-600"></i> TAMBAH BAHAN BAKU
                </h3>
                <div class="flex items-center gap-3">
                    <button @click="closeModal()" class="px-5 py-2 rounded-full border border-slate-200 text-slate-600 font-black text-xs hover:bg-slate-50 transition-colors">Batal</button>
                    <button @click="tambahKeDraft()" class="px-5 py-2 rounded-full bg-emerald-600 text-white font-black text-xs hover:bg-emerald-700 transition-colors uppercase flex items-center gap-1.5 shadow-md shadow-emerald-500/20">
                        TAMBAH ( <span x-text="opnameItems.filter(i => i.checked).length"></span> )
                    </button>
                </div>
            </div>

            <div class="p-4 border-b border-slate-100 bg-white flex gap-3 flex-wrap items-center">
                <div class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-black text-slate-500 flex items-center gap-2 bg-slate-50">
                    <i class="fa-regular fa-clock text-slate-400"></i> <span x-text="currentTime"></span>
                </div>
                
                <select x-model="selectedWarehouse" @change="onModalWarehouseChange()" class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-xs font-black text-slate-700 outline-none focus:border-blue-500 cursor-pointer min-w-[150px]">
                    <?php
                    try {
                        if (!isset($pdo)) require_once '../../config/database.php';
                        $stmt_wh = $pdo->query("SELECT id, name FROM warehouses ORDER BY id ASC");
                        $wh_idx2 = 1;
                        while ($wh_row = $stmt_wh->fetch(PDO::FETCH_ASSOC)) {
                            $display = preg_replace('/gudang\s*0?(\d+)/i', 'Gudang $1', $wh_row['name']);
                            if ($display === $wh_row['name']) $display = 'Gudang ' . $wh_idx2;
                            echo '<option value="' . $wh_row['id'] . '">' . htmlspecialchars($display) . '</option>';
                            $wh_idx2++;
                        }
                    } catch (Exception $e) {}
                    ?>
                </select>

                <div class="relative flex-1 min-w-[200px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" x-model="modalSearch" @input.debounce.300ms="loadAllProducts(true)" placeholder="Cari Bahan Baku / SKU / Kode..." class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs font-bold text-slate-700 outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="px-6 py-2.5 bg-blue-50 border-b border-blue-100 text-[9px] font-black text-blue-700 flex items-center gap-2 uppercase tracking-widest">
                <i class="fa-solid fa-circle-info text-blue-500"></i> MAKSIMAL 500 BAHAN BAKU TERPILIH TIAP PENAMBAHAN
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-white">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="bg-white border-b border-slate-200">
                            <tr class="text-[10px] text-slate-400 uppercase tracking-widest font-black">
                                <th class="p-4 w-10 text-center"><input type="checkbox" @change="toggleSelectAll($event)" :checked="allOnPageChecked" class="rounded cursor-pointer w-4 h-4 accent-blue-600 border-slate-300"></th>
                                <th class="p-4">BAHAN BAKU</th>
                                <th class="p-4">KODE / SKU</th>
                                <th class="p-4">GUDANG</th>
                                <th class="p-4 text-center">STOK SISTEM</th>
                                <th class="p-4 text-center">STOK AKTUAL</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100">
                            <tr x-show="isModalLoading">
                                <td colspan="6" class="p-10 text-center text-slate-400 font-bold">Memuat produk...</td>
                            </tr>
                            <tr x-show="!isModalLoading && modalProducts.length === 0">
                                <td colspan="6" class="p-10 text-center text-slate-400 font-bold">Bahan baku tidak ditemukan.</td>
                            </tr>
                            <template x-for="prod in modalProducts" :key="prod.id">
                                <tr class="hover:bg-slate-50 transition-colors"
                                    :class="getItemById(prod.id) && getItemById(prod.id).checked ? 'bg-slate-50' : ''">
                                    <td class="p-4 text-center">
                                        <input type="checkbox" 
                                               :checked="getItemById(prod.id) && getItemById(prod.id).checked"
                                               @change="toggleProductCheck(prod, $event)"
                                               class="rounded cursor-pointer w-4 h-4 accent-blue-600 border-slate-300">
                                    </td>
                                    <td class="p-4">
                                        <div class="font-black text-slate-700 text-sm" x-text="prod.name"></div>
                                        <div class="text-[10px] text-slate-400 font-bold mt-0.5" x-text="(prod.category || 'PRODUK').toUpperCase()"></div>
                                    </td>
                                    <td class="p-4 text-xs font-black text-slate-500" x-text="prod.code || '-'"></td>
                                    <td class="p-4 text-xs font-bold text-slate-500">
                                        <span x-text="prod.warehouse_id == 2 ? 'Gudang 02' : 'gudang 01'"></span>
                                    </td>
                                    <td class="p-4 text-center font-bold text-slate-500 text-sm" x-text="prod.stock"></td>
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button @click="ensureItem(prod); decrementStock(prod.id)" 
                                                    class="w-7 h-7 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 font-black flex items-center justify-center transition-colors leading-none select-none text-lg">&minus;</button>
                                            <input type="number" 
                                                   :value="getItemById(prod.id) ? getItemById(prod.id).actual_stock : 0"
                                                   @focus="ensureItem(prod)"
                                                   @change="ensureItem(prod); setActualStock(prod.id, $event.target.value)"
                                                   @click.stop
                                                   class="w-16 bg-white border border-slate-200 rounded text-center font-black text-sm text-slate-800 outline-none py-1 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm"
                                                   min="0">
                                            <button @click="ensureItem(prod); incrementStock(prod.id)" 
                                                    class="w-7 h-7 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 font-black flex items-center justify-center transition-colors leading-none select-none text-lg">+</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-white flex items-center justify-between">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">TOTAL: <span x-text="modalTotalProducts"></span> BAHAN</span>
                <div x-show="modalTotalPages > 1" class="flex items-center gap-1 flex-wrap">
                    <button x-show="modalPage > 1" @click="goToModalPage(modalPage - 1)" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-black hover:bg-slate-100">&lsaquo;</button>
                    <template x-for="p in modalPageRange" :key="p">
                        <button @click="goToModalPage(p)" class="w-8 h-8 rounded-lg text-xs font-black transition-all" :class="p === modalPage ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'" x-text="p"></button>
                    </template>
                    <button x-show="modalPage < modalTotalPages" @click="goToModalPage(modalPage + 1)" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-black hover:bg-slate-100">&rsaquo;</button>
                </div>
            </div>
        </div>
    </div>

    <script>window.CURRENT_WAREHOUSE_ID = <?= !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 1 ?>;</script>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>