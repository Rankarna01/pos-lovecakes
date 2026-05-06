<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
// Set Judul Halaman
$page_title = "Histori Inventori - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- PANGGIL PUSAT KENDALI (HEADER) -->
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="inventoryApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER POS -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-clock-rotate-left mr-2"></i>Histori Inventori</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="syncData()" :disabled="isSyncing" class="bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 disabled:opacity-50 border border-white/10 shadow-sm">
                    <i class="fa-solid fa-rotate" :class="isSyncing ? 'fa-spin' : ''"></i> 
                    <span class="hidden sm:inline" x-text="isSyncing ? 'Sinkronisasi...' : 'Sync Database'"></span>
                </button>

                <div class="border-l border-blue-400 pl-3 ml-1">
                    <!-- PERBAIKAN: Menggunakan fungsi global doLogout() dari header.php -->
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm shadow-rose-500/30" title="Keluar">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <!-- TOP BAR: Tab Navigation & Filter -->
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col lg:flex-row gap-4 justify-between items-center sticky top-0 z-10">
                    
                    <!-- Tab Toggle (Masuk / Keluar) -->
                    <div class="flex p-1 bg-slate-100 rounded-xl w-full lg:w-auto">
                        <button @click="activeTab = 'masuk'; currentPage = 1" :class="activeTab === 'masuk' ? 'bg-white text-emerald-600 shadow-sm font-black' : 'text-slate-500 hover:text-slate-700 font-semibold'" class="flex-1 lg:px-8 py-2.5 rounded-lg text-sm transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Barang Masuk
                        </button>
                        <button @click="activeTab = 'keluar'; currentPage = 1" :class="activeTab === 'keluar' ? 'bg-white text-rose-600 shadow-sm font-black' : 'text-slate-500 hover:text-slate-700 font-semibold'" class="flex-1 lg:px-8 py-2.5 rounded-lg text-sm transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Barang Keluar
                        </button>
                    </div>

                    <!-- Search & Date Filter -->
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <div class="relative w-full sm:w-64">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" x-model="searchQuery" placeholder="Cari referensi / produk..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700">
                        </div>
                        
                        <select x-model="dateFilter" class="w-full sm:w-auto px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700 cursor-pointer">
                            <option value="all">Semua Waktu</option>
                            <option value="today">Hari Ini</option>
                            <option value="week">7 Hari Terakhir</option>
                            <option value="month">Bulan Ini</option>
                        </select>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div x-show="isLoading" class="text-center py-20">
                    <div class="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-slate-500 font-bold tracking-widest uppercase text-sm">Memuat Histori...</p>
                </div>

                <!-- TABLE SECTION -->
                <div x-show="!isLoading" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black text-center w-16">No</th>
                                    <th class="p-4 font-black">Tanggal</th>
                                    <th class="p-4 font-black">No. Referensi</th>
                                    <th class="p-4 font-black">Nama Produk</th>
                                    <th class="p-4 font-black text-center">Qty</th>
                                    <th class="p-4 font-black">Keterangan / Sumber</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                
                                <tr x-show="paginatedData.length === 0">
                                    <td colspan="6" class="p-10 text-center">
                                        <div class="text-slate-300 text-5xl mb-3"><i class="fa-solid fa-folder-open"></i></div>
                                        <p class="text-slate-500 font-bold">Tidak ada riwayat data ditemukan.</p>
                                    </td>
                                </tr>

                                <template x-for="(item, index) in paginatedData" :key="index">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4 text-center text-slate-400 font-bold text-xs" x-text="(currentPage - 1) * itemsPerPage + index + 1"></td>
                                        <td class="p-4 font-semibold text-slate-700" x-text="formatDate(item.tanggal)"></td>
                                        <td class="p-4">
                                            <span class="bg-slate-100 px-2.5 py-1 rounded-lg text-xs font-black tracking-wider text-slate-600" x-text="item.referensi"></span>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-slate-800" x-text="item.produk"></div>
                                            <div class="text-[10px] text-slate-400 font-black uppercase" x-text="item.kode_produk"></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="px-3 py-1.5 rounded-lg font-black text-xs border"
                                                  :class="item.tipe === 'Masuk' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100'">
                                                <span x-text="item.tipe === 'Masuk' ? '+' : '-'"></span><span x-text="item.qty"></span>
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <div class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                                <i class="fa-solid fa-tags text-slate-300"></i>
                                                <span x-text="item.sumber"></span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- PAGINATION CONTROLS -->
                    <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50" x-show="totalPages > 1">
                        <div class="text-xs font-bold text-slate-500">
                            Menampilkan <span class="text-primary font-black" x-text="paginatedData.length"></span> dari <span class="text-primary font-black" x-text="filteredData.length"></span> baris data
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed font-bold text-xs transition-all shadow-sm">
                                <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                            </button>
                            
                            <span class="px-4 py-1.5 rounded-lg bg-primary text-white font-black text-xs shadow-sm shadow-primary/30" x-text="'Hal ' + currentPage + ' / ' + totalPages"></span>
                            
                            <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed font-bold text-xs transition-all shadow-sm">
                                Next <i class="fa-solid fa-chevron-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- Panggil File AJAX -->
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>