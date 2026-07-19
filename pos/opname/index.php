<?php
require_once '../../config/auth.php';
$page_title = "Stok Opname - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="opnameApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-check text-amber-300"></i> Stok Opname
                    </h2>
                    <p class="text-xs text-blue-100 hidden sm:block">Sesuaikan stok fisik toko dengan catatan sistem.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openModal()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-black text-xs sm:text-sm shadow-lg shadow-emerald-500/30 flex items-center gap-2 transition-all">
                    <i class="fa-solid fa-plus"></i> <span>Tambah Produk</span>
                </button>
                <div class="border-l border-blue-400 pl-3 ml-1">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all" title="Keluar">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <select x-model="selectedWarehouse" @change="onWarehouseChange()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-700 outline-none focus:border-primary cursor-pointer">
                            <?php
                            try {
                                if (!isset($pdo)) require_once '../../config/database.php';
                                $stmt_wh = $pdo->query("SELECT id, name FROM warehouses ORDER BY id ASC");
                                $wh_index = 1;
                                while ($wh_row = $stmt_wh->fetch(PDO::FETCH_ASSOC)) {
                                    // Rename: gudang 01/02 -> Store 1/Store 2
                                    $display = preg_replace('/gudang\s*0?(\d+)/i', 'Store $1', $wh_row['name']);
                                    if ($display === $wh_row['name']) $display = 'Store ' . $wh_index; // fallback
                                    echo '<option value="' . $wh_row['id'] . '">🏬 ' . htmlspecialchars($display) . '</option>';
                                    $wh_index++;
                                }
                            } catch (Exception $e) {}
                            ?>
                        </select>
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="searchHistory" @keyup.enter="loadHistory()" placeholder="Cari produk / no. dokumen..." class="bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-primary w-56">
                        </div>
                        <button @click="loadHistory()" class="bg-slate-800 hover:bg-slate-900 text-white px-3 py-2 rounded-xl text-xs font-black transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-rotate"></i> Refresh
                        </button>
                    </div>
                    <div class="text-xs text-slate-400 font-bold" x-text="historyRows.length + ' data ditemukan'"></div>
                </div>

                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-box-archive text-primary"></i>
                                Produk Stok Opname
                                <span class="bg-primary text-white text-xs px-2.5 py-0.5 rounded-full font-black" x-text="historyRows.length"></span>
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Riwayat audit stok fisik vs sistem</p>
                        </div>
                        <button @click="openModal()" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-black flex items-center gap-1.5 transition-all shadow-sm">
                            <i class="fa-solid fa-plus"></i> + Tambah Produk
                        </button>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap min-w-[900px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black text-center w-12">No</th>
                                    <th class="p-4 font-black">Nama</th>
                                    <th class="p-4 font-black text-center">Waktu</th>
                                    <th class="p-4 font-black text-center text-primary">Qty Aktual</th>
                                    <th class="p-4 font-black text-center">Qty System</th>
                                    <th class="p-4 font-black text-center">Qty Selisih</th>
                                    <th class="p-4 font-black text-right">Selisih Harga</th>
                                    <th class="p-4 font-black text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="isLoadingHistory">
                                    <td colspan="8" class="p-10 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-circle-notch fa-spin text-2xl text-primary mb-2 block"></i>
                                        Memuat data opname...
                                    </td>
                                </tr>
                                <tr x-show="!isLoadingHistory && historyRows.length === 0">
                                    <td colspan="8" class="p-14 text-center text-slate-400">
                                        <div class="text-5xl mb-3 text-slate-200"><i class="fa-solid fa-box-archive"></i></div>
                                        <p class="font-bold text-slate-500 text-sm">Belum ada riwayat penyesuaian stok.</p>
                                        <p class="text-xs mt-1 text-slate-400">Klik <span class="font-bold text-primary">+ Tambah Produk</span> di atas untuk mulai audit stok.</p>
                                    </td>
                                </tr>
                                <template x-for="(row, idx) in historyRows" :key="row.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4 text-center font-bold text-slate-400 text-sm" x-text="idx + 1"></td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 shrink-0">
                                                    <i class="fa-solid fa-cake-candles text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-800 text-sm" x-text="(row.product_name || '-') + (row.product_code ? ' - ' + row.product_code : '')"></div>
                                                    <span class="text-[10px] text-slate-400 font-medium" x-text="row.category || ''"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="font-bold text-slate-600 text-sm" x-text="row.created_at ? row.created_at.substring(11,16) : '-'"></div>
                                            <div class="text-[10px] text-slate-400" x-text="row.created_at ? row.created_at.substring(0,10) : ''"></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="font-black text-primary text-base" x-text="row.actual_stock"></span>
                                        </td>
                                        <td class="p-4 text-center font-bold text-slate-600" x-text="row.system_stock"></td>
                                        <td class="p-4 text-center">
                                            <span class="px-2.5 py-1 rounded-lg font-black text-xs inline-flex items-center gap-1"
                                                  :class="row.difference > 0 ? 'bg-emerald-100 text-emerald-700' : (row.difference < 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500')">
                                                <i class="fa-solid text-[10px]" :class="row.difference > 0 ? 'fa-arrow-up' : (row.difference < 0 ? 'fa-arrow-down' : 'fa-minus')"></i>
                                                <span x-text="(row.difference > 0 ? '+' : '') + row.difference"></span>
                                            </span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <span class="font-black text-sm" 
                                                  :class="row.difference != 0 ? (row.difference > 0 ? 'text-emerald-600' : 'text-rose-600') : 'text-slate-400'"
                                                  x-text="formatRupiah(Math.abs((row.difference || 0) * (row.price || 0)))"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button @click="deleteRow(row)" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-400 hover:text-rose-600 flex items-center justify-center transition-all" title="Hapus">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                                <button @click="editRow(row)" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-blue-100 text-slate-400 hover:text-blue-600 flex items-center justify-center transition-all" title="Edit">
                                                    <i class="fa-solid fa-pen text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH PRODUK -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-2 bg-slate-900/60 backdrop-blur-sm" x-transition x-cloak>
        <div class="bg-white rounded-[1.5rem] shadow-2xl border border-slate-100 w-full max-w-5xl max-h-[94vh] flex flex-col overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-base font-black text-slate-800">Tambah Produk</h3>
                <div class="flex items-center gap-2">
                    <button @click="closeModal()" class="px-4 py-2 rounded-xl font-bold text-xs text-slate-600 bg-slate-200 hover:bg-slate-300 transition-colors">Batal</button>
                    <button @click="saveBatchOpname()" 
                            :disabled="isSavingBatch || opnameItems.filter(i=>i.checked).length === 0"
                            class="px-5 py-2 rounded-xl font-black text-xs text-white bg-emerald-500 hover:bg-emerald-600 shadow-sm transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-check" :class="isSavingBatch ? 'fa-fade' : ''"></i>
                        Tambah (<span x-text="opnameItems.filter(i=>i.checked).length"></span>)
                    </button>
                </div>
            </div>

            <div class="px-5 py-3 border-b border-slate-100 bg-white flex flex-wrap items-center gap-2">
                <!-- Jam realtime -->
                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600">
                    <i class="fa-regular fa-clock text-slate-400"></i>
                    <span x-text="currentTime"></span>
                </div>
                <!-- Store selector (sinkron dengan main page) -->
                <select x-model="selectedWarehouse" @change="onModalWarehouseChange()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-700 outline-none focus:border-primary cursor-pointer">
                    <?php
                    try {
                        if (!isset($pdo)) require_once '../../config/database.php';
                        $stmt_wh2 = $pdo->query("SELECT id, name FROM warehouses ORDER BY id ASC");
                        $wh_idx2 = 1;
                        while ($wh_row2 = $stmt_wh2->fetch(PDO::FETCH_ASSOC)) {
                            $disp2 = preg_replace('/gudang\s*0?(\d+)/i', 'Store $1', $wh_row2['name']);
                            if ($disp2 === $wh_row2['name']) $disp2 = 'Store ' . $wh_idx2;
                            echo '<option value="' . $wh_row2['id'] . '">' . htmlspecialchars($disp2) . '</option>';
                            $wh_idx2++;
                        }
                    } catch (Exception $e) {}
                    ?>
                </select>
                <!-- Kategori filter -->
                <select x-model="modalCategoryFilter" @change="loadAllProducts(true)" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-700 outline-none focus:border-primary cursor-pointer">
                    <option value="">Kategori</option>
                    <template x-for="cat in modalCategories" :key="cat">
                        <option :value="cat" x-text="cat"></option>
                    </template>
                </select>
                <!-- Search -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" x-model="modalSearch" @input.debounce.300ms="loadAllProducts(true)" placeholder="Cari Produk / SKU / Barcode" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-4 py-2 text-xs font-bold text-slate-700 outline-none focus:border-primary">
                </div>
            </div>

            <div x-show="opnameItems.filter(i=>i.checked).length > 0" class="px-5 py-2 bg-emerald-50 border-b border-emerald-100 text-xs font-bold text-emerald-700 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                <span x-text="opnameItems.filter(i=>i.checked).length + ' Terpilih (Maksimal 500)'"></span>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead class="sticky top-0 z-10 bg-white border-b border-slate-200 shadow-sm">
                        <tr class="text-[11px] text-slate-500 uppercase tracking-widest">
                            <th class="p-3.5 w-10"><input type="checkbox" @change="toggleSelectAll($event)" :checked="allOnPageChecked" class="rounded cursor-pointer w-4 h-4 accent-blue-600"></th>
                            <th class="p-3.5 font-black">Product</th>
                            <th class="p-3.5 font-black">SKU</th>
                            <th class="p-3.5 font-black">Kategori</th>
                            <th class="p-3.5 font-black text-center">Stok Sistem</th>
                            <th class="p-3.5 font-black text-center text-primary">Stok Aktual</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <tr x-show="isModalLoading">
                            <td colspan="6" class="p-10 text-center text-slate-400 font-bold">
                                <i class="fa-solid fa-circle-notch fa-spin text-2xl text-primary mb-2 block"></i>Memuat produk...
                            </td>
                        </tr>
                        <tr x-show="!isModalLoading && modalProducts.length === 0">
                            <td colspan="6" class="p-10 text-center text-slate-400">
                                <i class="fa-solid fa-search text-3xl text-slate-200 mb-2 block"></i>Produk tidak ditemukan.
                            </td>
                        </tr>
                        <template x-for="prod in modalProducts" :key="prod.id">
                            <tr class="hover:bg-blue-50/30 transition-colors border-b border-slate-100"
                                :class="getItemById(prod.id) && getItemById(prod.id).checked ? 'bg-blue-50/50' : ''">
                                <td class="p-3.5">
                                    <input type="checkbox" 
                                           :checked="getItemById(prod.id) && getItemById(prod.id).checked"
                                           @change="toggleProductCheck(prod, $event)"
                                           class="rounded cursor-pointer w-4 h-4 accent-blue-600">
                                </td>
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-800 text-sm" x-text="prod.name"></div>
                                    <div class="text-[11px] text-slate-400 font-medium" x-text="prod.category || 'Produk'"></div>
                                </td>
                                <td class="p-3.5 text-xs font-bold text-blue-600" x-text="prod.code || '-'"></td>
                                <td class="p-3.5 text-xs font-bold text-slate-500" x-text="prod.category || '-'"></td>
                                <td class="p-3.5 text-center font-bold text-slate-600 text-sm" x-text="prod.stock"></td>
                                <td class="p-3.5 text-center">
                                    <!-- Counter selalu tampil (seperti referensi gambar) -->
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="ensureItem(prod); decrementStock(prod.id)" 
                                                class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-lg flex items-center justify-center transition-all leading-none select-none">−</button>
                                        <input type="number" 
                                               :value="getItemById(prod.id) ? getItemById(prod.id).actual_stock : 0"
                                               @focus="ensureItem(prod)"
                                               @change="ensureItem(prod); setActualStock(prod.id, $event.target.value)"
                                               @click.stop
                                               class="w-14 bg-white border border-slate-300 rounded-lg text-center font-black text-sm text-slate-800 outline-none py-1 focus:border-primary focus:ring-1 focus:ring-primary/20"
                                               min="0">
                                        <button @click="ensureItem(prod); incrementStock(prod.id)" 
                                                class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-lg flex items-center justify-center transition-all leading-none select-none">+</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3">
                <span class="text-xs font-bold text-slate-400">50/page</span>
                <div x-show="modalTotalPages > 1" class="flex items-center gap-1 flex-wrap">
                    <button x-show="modalPage > 1" @click="goToModalPage(modalPage - 1)" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-black hover:bg-slate-100">&lsaquo;</button>
                    <template x-for="p in modalPageRange" :key="p">
                        <button @click="goToModalPage(p)" class="w-8 h-8 rounded-lg text-xs font-black transition-all" :class="p === modalPage ? 'bg-primary text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'" x-text="p"></button>
                    </template>
                    <button x-show="modalPage < modalTotalPages" @click="goToModalPage(modalPage + 1)" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-black hover:bg-slate-100">&rsaquo;</button>
                </div>
                <span class="text-xs font-bold text-slate-400" x-text="modalTotalProducts + ' produk'"></span>
            </div>
        </div>
    </div>

    <script>window.CURRENT_WAREHOUSE_ID = <?= !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 1 ?>;</script>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>