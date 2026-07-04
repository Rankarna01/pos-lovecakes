<?php
require_once '../../config/auth.php';
$page_title = "Stok Opname Bahan & Produk - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="opnameApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER TOP -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-check text-amber-300"></i> Stok Opname Bahan & Produk
                    </h2>
                    <p class="text-xs text-blue-100 hidden sm:block">Sesuaikan stok fisik toko/dapur dengan catatan sistem komputer.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Tombol Catat Opname Baru (Top Right) -->
                <button @click="openModal()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-black text-xs sm:text-sm shadow-lg shadow-emerald-500/30 flex items-center gap-2 transition-all transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>Catat Opname Baru</span>
                </button>

                <div class="border-l border-blue-400 pl-3 ml-1">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm shadow-rose-500/30" title="Keluar">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- BAGIAN 1: TABEL RIWAYAT PENYESUAIAN STOK -->
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50">
                        <div>
                            <h3 class="font-black text-slate-800 text-lg flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-primary"></i> Riwayat Penyesuaian Stok
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Daftar histori audit dan sinkronisasi stok antar-store & warehouse</p>
                        </div>
                        
                        <div class="w-full sm:w-auto flex items-center gap-2">
                            <div class="relative flex-1 sm:w-64">
                                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" x-model="searchHistory" @keyup.enter="loadHistory()" placeholder="Cari produk / no. dokumen..." class="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <button @click="loadHistory()" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-black transition-colors shrink-0">
                                <i class="fa-solid fa-rotate mr-1"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black text-center w-12">No</th>
                                    <th class="p-4 font-black">Waktu Opname</th>
                                    <th class="p-4 font-black">No. Dokumen</th>
                                    <th class="p-4 font-black">Bahan / Produk</th>
                                    <th class="p-4 font-black text-center">Stok Sistem</th>
                                    <th class="p-4 font-black text-center text-primary">Stok Fisik</th>
                                    <th class="p-4 font-black text-center">Selisih</th>
                                    <th class="p-4 font-black">Catatan</th>
                                    <th class="p-4 font-black text-center">Petugas</th>
                                    <th class="p-4 font-black text-center">Store / Lokasi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <!-- Loading State -->
                                <tr x-show="isLoadingHistory">
                                    <td colspan="10" class="p-8 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2 text-primary"></i><br>
                                        Memuat riwayat opname...
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr x-show="!isLoadingHistory && historyRows.length === 0">
                                    <td colspan="10" class="p-12 text-center text-slate-400">
                                        <div class="text-5xl mb-3 text-slate-300"><i class="fa-solid fa-box-archive"></i></div>
                                        <p class="font-bold text-base text-slate-600">Belum ada riwayat penyesuaian stok.</p>
                                        <p class="text-xs mt-1">Klik tombol <span class="font-bold text-emerald-600">"Catat Opname Baru"</span> di kanan atas untuk mulai audit stok.</p>
                                    </td>
                                </tr>

                                <!-- Data Rows -->
                                <template x-for="(row, idx) in historyRows" :key="row.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4 text-center font-bold text-slate-400" x-text="idx + 1"></td>
                                        <td class="p-4">
                                            <div class="font-bold text-slate-700" x-text="row.created_at.split(' ')[0]"></div>
                                            <div class="text-[11px] text-slate-400 font-medium mt-0.5" x-text="row.created_at.split(' ')[1] + ' WIB'"></div>
                                        </td>
                                        <td class="p-4">
                                            <span class="bg-blue-50 text-blue-700 border border-blue-200 font-black px-2.5 py-1 rounded-lg text-xs" x-text="row.doc_no || 'SO-MANUAL'"></span>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-black text-slate-800" x-text="row.product_name"></div>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded text-[10px] font-bold border" x-text="row.product_code"></span>
                                                <span class="text-xs text-slate-400 font-medium" x-text="row.category || '-'"></span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center font-bold text-slate-600" x-text="row.system_stock"></td>
                                        <td class="p-4 text-center font-black text-primary text-base bg-blue-50/40" x-text="row.actual_stock"></td>
                                        <td class="p-4 text-center">
                                            <span class="px-2.5 py-1 rounded-lg font-black text-xs inline-flex items-center gap-1 border"
                                                  :class="row.difference > 0 ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : (row.difference < 0 ? 'bg-rose-50 text-rose-600 border-rose-200' : 'bg-slate-100 text-slate-500 border-slate-200')">
                                                <i class="fa-solid" :class="row.difference > 0 ? 'fa-arrow-up' : (row.difference < 0 ? 'fa-arrow-down' : 'fa-minus')"></i>
                                                <span x-text="(row.difference > 0 ? '+' : '') + row.difference"></span>
                                            </span>
                                        </td>
                                        <td class="p-4 text-xs font-medium text-slate-600 max-w-xs truncate" x-text="row.notes || '--'" :title="row.notes"></td>
                                        <td class="p-4 text-center">
                                            <span class="bg-slate-100 text-slate-700 font-bold px-2.5 py-1 rounded-lg text-xs" x-text="row.admin_name || 'Admin'"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="bg-purple-50 text-purple-700 border border-purple-200 font-black px-2 py-0.5 rounded text-[10px]" x-text="row.store_name || 'Store 01'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- BAGIAN 2: MODE SCANNER CEPAT (COLLAPSIBLE / ALTERNATIF) -->
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden relative">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <div>
                            <h3 class="font-black text-slate-700 uppercase tracking-widest text-xs flex items-center gap-1.5">
                                <i class="fa-solid fa-barcode text-primary"></i> Mode Scanner Tunggal (Hardware / Kamera HP)
                            </h3>
                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Gunakan mode ini untuk pengecekan cepat satu per satu item langsung di rak toko.</p>
                        </div>
                    </div>
                    
                    <div class="p-6 flex flex-col items-center">
                        <!-- Pilih Outlet untuk Scan -->
                        <div class="w-full max-w-xl mb-4 bg-blue-50/70 border border-blue-200 p-3 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-2">
                            <span class="text-xs font-black text-blue-800 flex items-center gap-1.5 shrink-0">
                                <i class="fa-solid fa-store text-blue-600"></i> Pilih Outlet / Store:
                            </span>
                            <select x-model="selectedWarehouse" @change="onWarehouseChange()" class="w-full sm:w-auto flex-1 bg-white border border-blue-300 focus:border-blue-600 rounded-lg px-3 py-1.5 text-xs font-black text-blue-900 outline-none cursor-pointer">
                                <?php
                                try {
                                    if (!isset($pdo)) require_once '../../config/database.php';
                                    $stmt_wh = $pdo->query("SELECT id, name, code FROM warehouses ORDER BY id ASC");
                                    while ($wh_row = $stmt_wh->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $wh_row['id'] . '">🏬 ' . htmlspecialchars($wh_row['name']) . ' (' . htmlspecialchars($wh_row['code']) . ')</option>';
                                    }
                                } catch (Exception $e) {}
                                ?>
                            </select>
                        </div>

                        <div id="reader" class="w-full max-w-sm mb-4 rounded-xl overflow-hidden border-2 border-primary/20" x-show="isCameraOpen"></div>
                        
                        <div class="flex flex-col sm:flex-row gap-3 w-full max-w-xl">
                            <button @click="toggleCamera()" :class="isCameraOpen ? 'bg-rose-500 hover:bg-rose-600 text-white' : 'bg-slate-800 hover:bg-slate-900 text-white'" class="px-5 py-3 rounded-xl font-black transition-all shadow-sm flex items-center justify-center gap-2 shrink-0">
                                <i class="fa-solid" :class="isCameraOpen ? 'fa-video-slash' : 'fa-camera'"></i> 
                                <span x-text="isCameraOpen ? 'Tutup Kamera' : 'Buka Kamera HP'"></span>
                            </button>
                            
                            <div class="relative flex-1">
                                <i class="fa-solid fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                                <input type="text" x-model="barcodeInput" @keyup.enter="searchBarcode()" placeholder="Scan barcode / ketik SKU di sini lalu Enter..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-black text-slate-700 uppercase">
                            </div>
                        </div>
                    </div>

                    <!-- Hasil Scan Tunggal -->
                    <div x-show="scannedProduct" class="border-t border-slate-200 bg-blue-50/30 p-6" x-transition x-cloak>
                        <div class="max-w-2xl mx-auto bg-white rounded-2xl border border-primary/30 p-6 shadow-sm relative">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <span class="bg-blue-100 text-blue-600 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border border-blue-200" x-text="scannedProduct?.code"></span>
                                    <h4 class="text-xl font-black text-slate-800 mt-2" x-text="scannedProduct?.name"></h4>
                                    <p class="text-xs font-bold text-slate-500" x-text="scannedProduct?.category"></p>
                                </div>
                                <button @click="resetScan()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-rose-100 hover:text-rose-600 text-slate-400 transition-colors"><i class="fa-solid fa-xmark"></i></button>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex flex-col items-center">
                                    <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Stok Di Sistem</span>
                                    <span class="text-3xl font-black text-slate-800 mt-1" x-text="scannedProduct?.stock"></span>
                                </div>
                                <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl flex flex-col items-center">
                                    <span class="text-[10px] font-black uppercase text-blue-600 tracking-widest">Stok Fisik Nyata</span>
                                    <input type="number" x-model="actualStock" class="w-28 bg-white border border-blue-300 rounded-xl px-2 py-1.5 text-center font-black text-2xl text-primary outline-none focus:ring-2 focus:ring-primary/50 mt-1" autofocus>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-3 rounded-xl border" :class="selisih === 0 ? 'bg-slate-50 border-slate-200' : (selisih > 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200')">
                                    <span class="font-black text-xs uppercase tracking-widest" :class="selisih === 0 ? 'text-slate-500' : (selisih > 0 ? 'text-emerald-600' : 'text-rose-600')">Selisih Opname</span>
                                    <span class="text-lg font-black" :class="selisih === 0 ? 'text-slate-700' : (selisih > 0 ? 'text-emerald-600' : 'text-rose-600')" x-text="(selisih > 0 ? '+' : '') + selisih"></span>
                                </div>
                                <div>
                                    <input type="text" x-model="opnameNotes" placeholder="Keterangan / alasan selisih (opsional)..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                                </div>
                                <button @click="saveOpname()" :disabled="isSaving || selisih === 0" class="w-full bg-primary hover:bg-blue-700 text-white font-black py-3.5 rounded-xl shadow-lg shadow-primary/30 transition-all flex justify-center items-center gap-2 text-xs uppercase disabled:opacity-50">
                                    <i class="fa-solid fa-floppy-disk" :class="isSaving ? 'fa-fade' : ''"></i> Simpan Penyesuaian
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- MODAL DOKUMEN STOK OPNAME (BATCH AUDIT) -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-transition x-cloak>
        <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden" @click.outside="closeModal()">
            
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg font-black shadow-sm">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800">Dokumen Stok Opname</h3>
                        <p class="text-xs text-slate-400 font-medium">Audit dan sesuaikan stok beberapa produk sekaligus dalam satu dokumen</p>
                    </div>
                </div>
                <button @click="closeModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-rose-100 hover:text-rose-600 text-slate-400 flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
                
                <!-- PILIH OUTLET / STORE YANG DIOPNAME -->
                <div class="space-y-2 bg-emerald-50/70 border-2 border-emerald-300 p-4 rounded-2xl">
                    <label class="block text-xs font-black uppercase tracking-widest text-emerald-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-store text-emerald-600"></i> PILIH OUTLET / STORE YANG DIOPNAME <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="selectedWarehouse" @change="onWarehouseChange()" class="w-full bg-white border-2 border-emerald-400 focus:border-emerald-600 rounded-xl px-4 py-3 text-sm font-black text-emerald-900 outline-none transition-all shadow-xs cursor-pointer">
                        <?php
                        try {
                            if (!isset($pdo)) require_once '../../config/database.php';
                            $stmt_wh = $pdo->query("SELECT id, name, code FROM warehouses ORDER BY id ASC");
                            while ($wh_row = $stmt_wh->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $wh_row['id'] . '">🏬 ' . htmlspecialchars($wh_row['name']) . ' (' . htmlspecialchars($wh_row['code']) . ')</option>';
                            }
                        } catch (Exception $e) {}
                        ?>
                    </select>
                    <p class="text-[11px] font-bold text-emerald-700/90 flex items-center gap-1 mt-1">
                        <i class="fa-solid fa-circle-info"></i> Daftar produk dan stok sistem yang dicari di bawah hanya untuk outlet terpilih ini.
                    </p>
                </div>

                <!-- Cari & Tambah Bahan / Produk -->
                <div class="space-y-2 relative">
                    <label class="block text-xs font-black uppercase tracking-widest text-emerald-600">
                        CARI & TAMBAH BAHAN / PRODUK <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchKeyword" @input.debounce.300ms="searchProductsModal()" 
                               placeholder="Ketik nama atau kode bahan baku / produk..." 
                               class="w-full bg-slate-50 border-2 border-slate-200 focus:border-emerald-500 rounded-xl pl-11 pr-4 py-3 text-sm font-bold text-slate-700 outline-none transition-all">
                        <button x-show="searchKeyword" @click="searchKeyword = ''; searchResults = []" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </button>
                    </div>

                    <!-- Live Dropdown Search Results -->
                    <div x-show="searchResults.length > 0" class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 max-h-60 overflow-y-auto z-30 divide-y divide-slate-100">
                        <template x-for="prod in searchResults" :key="prod.id">
                            <div @click="addItemToOpname(prod)" class="p-3.5 hover:bg-emerald-50/60 cursor-pointer transition-colors flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="bg-slate-100 text-slate-600 font-black px-2 py-1 rounded text-xs border" x-text="prod.code"></span>
                                    <div>
                                        <div class="font-bold text-sm text-slate-800" x-text="prod.name"></div>
                                        <div class="text-[11px] text-slate-400 font-medium" x-text="prod.category || 'Umum'"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black uppercase text-slate-400 block tracking-widest">Stok Saat Ini</span>
                                    <span class="font-black text-sm text-slate-700" x-text="prod.stock"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Daftar Bahan Diopname -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <h4 class="text-xs font-black text-slate-700 uppercase tracking-widest">Daftar Bahan / Produk Diopname</h4>
                        <span class="bg-emerald-100 text-emerald-800 font-black text-xs px-2.5 py-0.5 rounded-full" x-text="opnameItems.length + ' Item'"></span>
                    </div>

                    <!-- Empty List Box ( Sesuai Contoh Screenshot ) -->
                    <div x-show="opnameItems.length === 0" class="border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center bg-slate-50/50 flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mb-3 shadow-inner">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <p class="font-bold text-sm text-slate-600">Belum ada bahan baku / produk yang dipilih.</p>
                        <p class="text-xs text-slate-400 mt-1 font-medium">Silakan cari dan pilih pada kolom pencarian di atas.</p>
                    </div>

                    <!-- Table Selected Items -->
                    <div x-show="opnameItems.length > 0" class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[10px] text-slate-500 uppercase tracking-widest font-black">
                                    <th class="p-3.5">Bahan / Produk</th>
                                    <th class="p-3.5 text-center w-28">Stok Sistem</th>
                                    <th class="p-3.5 text-center w-36 text-primary">Stok Fisik</th>
                                    <th class="p-3.5 text-center w-28">Selisih</th>
                                    <th class="p-3.5 text-center w-16">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <template x-for="(item, idx) in opnameItems" :key="item.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-3.5">
                                            <div class="font-black text-slate-800" x-text="item.name"></div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="bg-slate-100 text-slate-600 px-1.5 py-0.2 rounded text-[10px] font-bold" x-text="item.code"></span>
                                                <span class="text-xs text-slate-400" x-text="item.category"></span>
                                            </div>
                                        </td>
                                        <td class="p-3.5 text-center font-bold text-slate-600 bg-slate-50/50" x-text="item.system_stock"></td>
                                        <td class="p-3.5 text-center">
                                            <input type="number" x-model.number="item.actual_stock" 
                                                   class="w-24 bg-white border-2 border-emerald-400 focus:border-emerald-600 rounded-xl px-2 py-1 text-center font-black text-base text-slate-800 outline-none shadow-sm">
                                        </td>
                                        <td class="p-3.5 text-center">
                                            <span class="px-2 py-1 rounded font-black text-xs inline-block min-w-[3rem]"
                                                  :class="(item.actual_stock - item.system_stock) > 0 ? 'bg-emerald-100 text-emerald-700' : ((item.actual_stock - item.system_stock) < 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500')"
                                                  x-text="((item.actual_stock - item.system_stock) > 0 ? '+' : '') + (item.actual_stock - item.system_stock)">
                                            </span>
                                        </td>
                                        <td class="p-3.5 text-center">
                                            <button @click="removeItem(idx)" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-colors" title="Hapus dari daftar">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Catatan Penyesuaian -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500">
                        CATATAN PENYESUAIAN (OPSIONAL)
                    </label>
                    <input type="text" x-model="batchNotes" placeholder="Contoh: Audit stok bulanan / Pembuangan bahan rusak" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end items-center gap-3">
                <button @click="closeModal()" class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm text-slate-600 bg-slate-200 hover:bg-slate-300 transition-colors">
                    Batal
                </button>
                <button @click="saveBatchOpname()" :disabled="isSavingBatch || opnameItems.length === 0" 
                        class="px-6 py-2.5 rounded-xl font-black text-xs sm:text-sm text-white bg-emerald-500 hover:bg-emerald-600 shadow-lg shadow-emerald-500/30 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-check-to-slot" :class="isSavingBatch ? 'fa-fade' : ''"></i>
                    <span>Post Opname</span>
                </button>
            </div>

        </div>
    </div>

    <script>
        window.CURRENT_WAREHOUSE_ID = <?= !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 1 ?>;
    </script>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>