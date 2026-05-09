<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Laporan Penjualan & Shift - Love Cakes POS";
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
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="reportApp()" x-cloak>
    
    <?php include '../../../components/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-receipt mr-2"></i>Penjualan & Shift</h2>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto custom-scrollbar p-4 md:p-6 relative">
            
            <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/70 backdrop-blur-sm flex flex-col items-center justify-center">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-3"></i>
                <span class="font-bold text-slate-500 uppercase tracking-widest text-sm">Menarik Data Laporan...</span>
            </div>

            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- FILTER & EXPORT -->
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                    <input type="date" x-model="startDate" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <span class="py-2 text-slate-400 font-bold text-xs">s/d</span>
                    <input type="date" x-model="endDate" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <button @click="fetchReport()" :disabled="isLoading" class="bg-primary hover:bg-slate-800 text-white px-6 py-2.5 rounded-xl font-black transition-all flex items-center gap-2 shadow-sm disabled:opacity-50">
                        <i class="fa-solid fa-magnifying-glass"></i> Tampilkan
                    </button>
                    
                    <div class="flex gap-2 sm:ml-auto">
                        <button @click="printPdf()" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2.5 rounded-xl font-black transition-all shadow-sm shadow-rose-500/30 flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> Cetak PDF
                        </button>
                        <button @click="exportExcel()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-black transition-all shadow-sm shadow-emerald-500/30 flex items-center gap-2">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>

                <!-- SUMMARY OMSET -->
                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-6 items-center">
                    <div class="flex-1 w-full">
                        <h3 class="font-black text-slate-400 uppercase text-[10px] tracking-widest mb-2"><i class="fa-solid fa-chart-pie mr-1"></i> Komposisi Omset Sistem</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                                <p class="text-xs font-bold text-emerald-600 mb-1">Cash / Tunai</p>
                                <p class="text-xl font-black text-emerald-700" x-text="'Rp ' + formatRupiah(paymentData.cash)"></p>
                            </div>
                            <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                                <p class="text-xs font-bold text-blue-600 mb-1">QRIS & Transfer</p>
                                <p class="text-xl font-black text-blue-700" x-text="'Rp ' + formatRupiah(paymentData.qris)"></p>
                            </div>
                        </div>
                    </div>
                    <div class="w-full sm:w-1/3 p-5 bg-slate-800 rounded-[1.5rem] text-white shadow-lg text-center flex flex-col justify-center">
                        <span class="font-black uppercase tracking-widest text-[10px] opacity-80 mb-1">Total Omset</span>
                        <span class="font-black text-3xl text-emerald-400" x-text="'Rp ' + formatRupiah(paymentData.total)"></span>
                    </div>
                </div>
                
                <!-- TABEL EVALUASI KASIR -->
                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                    <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-users-gear text-slate-400 mr-2"></i> Laporan Closing Kasir & Selisih Laci
                    </h3>
                    
                    <div class="overflow-x-auto custom-scrollbar pb-4">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-widest">
                                <tr>
                                    <th class="p-3 font-black rounded-tl-xl border-b">Kasir</th>
                                    <th class="p-3 font-black text-center border-b">Modal Awal</th>
                                    <th class="p-3 font-black text-center border-b text-emerald-600">+ Masuk Cash</th>
                                    <th class="p-3 font-black text-center border-b text-amber-600">- Kas Keluar</th>
                                    <th class="p-3 font-black text-center border-b bg-slate-100">= Sistem Harus</th>
                                    <th class="p-3 font-black text-center border-b bg-blue-50 text-blue-600">Laci Real</th>
                                    <th class="p-3 font-black text-center border-b rounded-tr-xl">Selisih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="shift in shiftData" :key="shift.id">
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                        <td class="p-3">
                                            <div class="font-black text-slate-800 text-xs" x-text="shift.kasir_name"></div>
                                            <div class="text-[10px] font-bold text-slate-400" x-text="shift.shift_name + ' (' + shift.start_time.split(' ')[1] + ')'"></div>
                                            <div x-show="shift.status === 'open'" class="text-[9px] font-black text-blue-500 uppercase mt-1 animate-pulse">Berjalan...</div>
                                        </td>
                                        <td class="p-3 text-center font-bold text-slate-600" x-text="formatRupiah(shift.start_cash)"></td>
                                        <td class="p-3 text-center font-bold text-emerald-600" x-text="formatRupiah(shift.total_cash_in)"></td>
                                        <td class="p-3 text-center font-bold text-amber-600" x-text="formatRupiah(shift.total_kas_keluar)"></td>
                                        <td class="p-3 text-center font-black bg-slate-50 text-slate-700" x-text="formatRupiah(shift.expected_cash)"></td>
                                        <td class="p-3 text-center font-black bg-blue-50 text-blue-700">
                                            <span x-show="shift.status === 'closed'" x-text="formatRupiah(shift.end_cash)"></span>
                                            <span x-show="shift.status === 'open'" class="text-xs text-blue-400 italic">Belum Tutup</span>
                                        </td>
                                        <td class="p-3 text-center font-black text-sm">
                                            <div x-show="shift.status === 'closed'">
                                                <span x-show="shift.selisih < 0" class="text-rose-500 bg-rose-50 px-2 py-1 rounded-lg" x-text="formatRupiah(shift.selisih)"></span>
                                                <span x-show="shift.selisih === 0" class="text-emerald-500 bg-emerald-50 px-2 py-1 rounded-lg">PAS (0)</span>
                                                <span x-show="shift.selisih > 0" class="text-blue-500 bg-blue-50 px-2 py-1 rounded-lg" x-text="'+' + formatRupiah(shift.selisih)"></span>
                                            </div>
                                            <span x-show="shift.status === 'open'" class="text-slate-300">-</span>
                                        </td>
                                    </tr>
                                </template>
                                
                                <tr x-show="shiftData.length === 0">
                                    <td colspan="7" class="p-8 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-50"></i><br>Belum ada data shift di tanggal ini.
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