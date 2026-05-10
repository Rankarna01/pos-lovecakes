<?php
require_once '../../../config/auth.php';
$page_title = "Akuntansi Internal - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="accountingApp()" x-cloak>
    <?php include '../../../components/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- Loader Background -->
        <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/70 backdrop-blur-sm flex flex-col items-center justify-center">
            <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-3"></i>
            <span class="font-bold text-slate-500 uppercase tracking-widest text-sm">Menghitung Buku Kas...</span>
        </div>

        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-scale-balanced mr-2"></i>Akuntansi Internal</h2>
            </div>
            <div class="bg-white/20 px-3 py-1.5 rounded-lg text-xs font-bold border border-white/10 flex items-center gap-2">
                <i class="fa-regular fa-calendar-check"></i> <span x-text="filterLabel"></span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <!-- KOTAK FILTER -->
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                    <div class="flex gap-2 bg-slate-100 p-1 rounded-xl border border-slate-200">
                        <button @click="setQuickFilter('today')" :class="activeFilter === 'today' ? 'bg-white shadow text-primary' : 'text-slate-500 hover:bg-slate-200'" class="px-4 py-2 rounded-lg text-xs font-black transition-all">Hari Ini</button>
                        <button @click="setQuickFilter('week')" :class="activeFilter === 'week' ? 'bg-white shadow text-primary' : 'text-slate-500 hover:bg-slate-200'" class="px-4 py-2 rounded-lg text-xs font-black transition-all">Mingguan</button>
                        <button @click="setQuickFilter('month')" :class="activeFilter === 'month' ? 'bg-white shadow text-primary' : 'text-slate-500 hover:bg-slate-200'" class="px-4 py-2 rounded-lg text-xs font-black transition-all">Bulanan</button>
                    </div>

                    <div class="h-8 w-px bg-slate-200 mx-1 hidden sm:block"></div>

                    <input type="date" x-model="startDate" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <span class="text-slate-400 font-bold text-xs">s/d</span>
                    <input type="date" x-model="endDate" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    
                    <select x-model="selectedShift" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20 text-slate-600">
                        <option value="">Semua Shift</option>
                        <template x-for="s in masterShifts" :key="s.id">
                            <option :value="s.id" x-text="s.shift_name"></option>
                        </template>
                    </select>

                    <button @click="loadData()" class="bg-primary hover:bg-slate-800 text-white px-5 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-2 ml-auto">
                        <i class="fa-solid fa-filter"></i> Terapkan
                    </button>
                </div>

                <!-- SUMMARY CARD -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-200 relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 opacity-5 text-7xl text-blue-500 group-hover:scale-110 transition-transform"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Pendapatan (Sales)</p>
                        <h3 class="text-2xl font-black text-blue-600" x-text="'Rp ' + formatRupiah(summary.income)"></h3>
                    </div>
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-200 relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 opacity-5 text-7xl text-rose-500 group-hover:scale-110 transition-transform"><i class="fa-solid fa-arrow-trend-down"></i></div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pengeluaran & HPP (Beban)</p>
                        <h3 class="text-2xl font-black text-rose-500" x-text="'- Rp ' + formatRupiah(summary.expense)"></h3>
                    </div>
                    <div class="bg-emerald-500 p-5 rounded-[1.5rem] shadow-md border border-emerald-600 text-white relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 opacity-20 text-7xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-sack-dollar"></i></div>
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 opacity-80">Laba Bersih (Net Profit)</p>
                        <h3 class="text-3xl font-black relative z-10" x-text="'Rp ' + formatRupiah(summary.net_profit)"></h3>
                    </div>
                </div>

                <!-- BUKU KAS (JURNAL) -->
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-black text-slate-700 uppercase text-xs tracking-widest"><i class="fa-solid fa-book-journal-whills mr-2 text-primary"></i> Jurnal Umum / Buku Kas</h3>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar max-h-[500px]">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="p-4 font-black">Tanggal</th>
                                    <th class="p-4 font-black">Keterangan Akun</th>
                                    <th class="p-4 font-black text-center">Tipe</th>
                                    <th class="p-4 font-black text-right">Debit (Masuk)</th>
                                    <th class="p-4 font-black text-right">Kredit (Keluar)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, index) in journal" :key="index">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 font-bold text-slate-600" x-text="item.tanggal"></td>
                                        <td class="p-4">
                                            <div class="font-black text-slate-800" x-text="item.keterangan"></div>
                                            <div class="text-[10px] font-bold text-slate-400" x-text="'Akun: ' + item.akun"></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span x-show="item.tipe === 'Pendapatan'" class="bg-blue-50 text-blue-600 border border-blue-100 px-2 py-0.5 rounded text-[10px] font-black uppercase">Pendapatan</span>
                                            <span x-show="item.tipe === 'Beban'" class="bg-rose-50 text-rose-600 border border-rose-100 px-2 py-0.5 rounded text-[10px] font-black uppercase">Beban</span>
                                        </td>
                                        <td class="p-4 text-right font-black text-emerald-600" x-text="item.debit > 0 ? 'Rp ' + formatRupiah(item.debit) : '-'"></td>
                                        <td class="p-4 text-right font-black text-rose-500" x-text="item.kredit > 0 ? 'Rp ' + formatRupiah(item.kredit) : '-'"></td>
                                    </tr>
                                </template>
                                
                                <tr x-show="journal.length === 0">
                                    <td colspan="5" class="p-10 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-folder-open text-4xl mb-3 opacity-30"></i><br>Tidak ada transaksi di periode ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>