<?php
require_once '../../../../config/auth.php';
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Ringkasan Omset - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../../components/header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="reportApp()" x-cloak>
    
    <?php include '../../../../components/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-receipt mr-2"></i>Ringkasan Omset</h2>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto custom-scrollbar p-4 md:p-6 relative">
            
            <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/70 backdrop-blur-sm flex flex-col items-center justify-center">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-3"></i>
                <span class="font-bold text-slate-500 uppercase tracking-widest text-sm">Menarik Data Laporan...</span>
            </div>

            <div class="w-full max-w-full space-y-6">
                
                <!-- FILTER & EXPORT -->
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                    <input type="date" x-model="startDate" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <span class="py-2 text-slate-400 font-bold text-xs">s/d</span>
                    <input type="date" x-model="endDate" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <button @click="fetchReport()" :disabled="isLoading" class="bg-primary hover:bg-slate-200 text-primary px-6 py-2.5 rounded-xl font-black transition-all flex items-center gap-2 shadow-sm disabled:opacity-50">
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
                        <h3 class="font-black text-slate-400 uppercase text-[10px] tracking-widest mb-2"><i class="fa-solid fa-chart-pie mr-1"></i> Komposisi Omset Sistem (Sederhana)</h3>
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

                <!-- METODE PEMBAYARAN & STATUS DP/LUNAS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-credit-card text-blue-400 mr-2"></i> Rincian Pembayaran (Cash/TF/Qris)
                        </h3>
                        <div class="space-y-3">
                            <template x-for="pay in paymentBreakdown" :key="pay.payment_method">
                                <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="font-bold text-slate-600 text-sm uppercase tracking-wide" x-text="pay.payment_method"></span>
                                    <span class="font-black text-slate-800" x-text="'Rp ' + formatRupiah(pay.total_amount)"></span>
                                </div>
                            </template>
                            <div x-show="paymentBreakdown.length === 0" class="text-center py-4 text-xs font-bold text-slate-400">Belum ada data</div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-file-invoice-dollar text-emerald-400 mr-2"></i> Pembayaran DP vs Pelunasan
                        </h3>
                        <div class="space-y-3">
                            <template x-for="dp in dpPelunasan" :key="dp.payment_type">
                                <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="font-bold text-slate-600 text-sm uppercase tracking-wide" x-text="dp.payment_type"></span>
                                        <span class="text-[10px] bg-slate-200 text-slate-500 px-2 py-0.5 rounded ml-2" x-text="dp.total_transactions + ' trx'"></span>
                                    </div>
                                    <span class="font-black text-emerald-600" x-text="'Rp ' + formatRupiah(dp.total_amount)"></span>
                                </div>
                            </template>
                            <div x-show="dpPelunasan.length === 0" class="text-center py-4 text-xs font-bold text-slate-400">Belum ada data DP/Pelunasan</div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
