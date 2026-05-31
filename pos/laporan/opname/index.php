<?php
require_once '../../../config/auth.php';
$page_title = "Laporan Stok Opname - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
    <!-- Memastikan Alpine ada -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            main { padding: 0 !important; background: white !important; }
            .print-shadow-none { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="laporanOpnameApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="bg-primary text-white shadow-sm px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0 no-print">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-bold tracking-wide">Laporan Stok Opname</h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL ?>pos/dashboard/" class="bg-blue-600/50 hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-6xl mx-auto space-y-6">
                
                <!-- Filter Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 no-print">
                    <div class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="w-full sm:w-1/3">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Dari Tanggal</label>
                            <input type="date" x-model="dateFrom" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700">
                        </div>
                        <div class="w-full sm:w-1/3">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Sampai Tanggal</label>
                            <input type="date" x-model="dateTo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700">
                        </div>
                        <div class="w-full sm:w-1/3 flex gap-2">
                            <button @click="loadData()" class="flex-1 bg-primary hover:bg-blue-700 text-white font-black py-2.5 rounded-xl shadow-sm transition-all">
                                <i class="fa-solid fa-filter mr-1"></i> Tampilkan
                            </button>
                            <button @click="printReport()" class="bg-slate-800 hover:bg-slate-900 text-white font-black px-4 py-2.5 rounded-xl shadow-sm transition-all">
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm print-shadow-none">
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Total Opname</span>
                        <div class="text-3xl font-black text-slate-800 mt-1" x-text="summary.total"></div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl shadow-sm print-shadow-none">
                        <span class="text-[10px] font-black uppercase text-emerald-600 tracking-widest">Stok Bertambah (Plus)</span>
                        <div class="text-3xl font-black text-emerald-700 mt-1" x-text="summary.plus"></div>
                    </div>
                    <div class="bg-rose-50 border border-rose-200 p-4 rounded-2xl shadow-sm print-shadow-none">
                        <span class="text-[10px] font-black uppercase text-rose-600 tracking-widest">Stok Berkurang (Minus)</span>
                        <div class="text-3xl font-black text-rose-700 mt-1" x-text="summary.minus"></div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden print-shadow-none">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-black text-slate-800 text-sm flex items-center gap-2">
                            <i class="fa-solid fa-table-list text-primary"></i> Detail Penyesuaian Stok
                        </h2>
                    </div>
                    
                    <div x-show="isLoading" class="p-10 text-center text-slate-400 font-bold">
                        <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2"></i><br>Memuat...
                    </div>

                    <div x-show="!isLoading && rows.length === 0" class="p-10 text-center">
                        <div class="text-6xl text-slate-200 mb-3"><i class="fa-solid fa-box-open"></i></div>
                        <p class="text-sm font-bold text-slate-400">Belum ada data stok opname di periode ini.</p>
                    </div>

                    <div x-show="!isLoading && rows.length > 0" class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-sm text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
                                    <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">SKU & Produk</th>
                                    <th class="text-center px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Sistem</th>
                                    <th class="text-center px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fisik</th>
                                    <th class="text-center px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Selisih</th>
                                    <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Keterangan</th>
                                    <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Petugas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(row, idx) in rows" :key="idx">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="text-xs text-slate-500 font-medium whitespace-nowrap" x-text="formatWaktu(row.created_at)"></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-black text-xs text-blue-600 mb-0.5" x-text="row.sku"></div>
                                            <div class="font-bold text-slate-800 text-xs" x-text="row.product_name"></div>
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold text-slate-500" x-text="row.system_stock"></td>
                                        <td class="px-4 py-3 text-center font-black text-slate-800" x-text="row.actual_stock"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-black border"
                                                :class="row.difference > 0 ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200'">
                                                <span x-text="(row.difference > 0 ? '+' : '') + row.difference"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-600 italic" x-text="row.notes || '-'"></td>
                                        <td class="px-4 py-3 text-xs font-bold text-slate-700" x-text="row.admin_name || 'Sistem'"></td>
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

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
