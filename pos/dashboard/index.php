<?php
require_once '../../config/auth.php';
$page_title = "Dashboard - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="dashboardApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/70 backdrop-blur-sm flex flex-col items-center justify-center transition-opacity">
            <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-3"></i>
            <span class="font-bold text-slate-500 uppercase tracking-widest text-sm">Menyiapkan Laporan...</span>
        </div>

        <header class="bg-primary text-white shadow-sm px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-bold tracking-wide">Dasbor</h2>
            </div>
            <div class="flex items-center gap-4 md:gap-5 text-lg">
                <button class="hover:text-blue-200 transition-colors hidden sm:block"><i class="fa-solid fa-magnifying-glass"></i></button>
                <button class="hover:text-blue-200 transition-colors hidden sm:block"><i class="fa-solid fa-gift"></i></button>
                <button class="hover:text-blue-200 relative transition-colors hidden sm:block"><i class="fa-regular fa-bell"></i></button>

                <button @click="installPWA()" x-show="showInstallBtn" style="display: none;" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-xs font-black shadow-sm transition-all flex items-center gap-2">
                    <i class="fa-solid fa-download"></i> <span class="hidden sm:inline">Install POS</span>
                </button>

                <div class="border-l border-blue-400 pl-4 ml-1">
                    <button onclick="logoutSistem()" class="bg-rose-500 hover:bg-rose-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-5xl mx-auto space-y-6">
                
                <h3 class="font-black text-slate-800 text-lg md:text-xl">Sekilas "Love Cakes"</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow-md hover:border-primary/20 transition-all group">
                        <div class="w-12 h-12 rounded-full bg-blue-50 text-primary flex items-center justify-center text-xl group-hover:bg-primary group-hover:text-white transition-colors"><i class="fa-solid fa-percent"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Diskon Persen</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Atur potongan %</p>
                        </div>
                    </button>
                    <button class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow-md hover:border-emerald-500/20 transition-all group">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl group-hover:bg-emerald-500 group-hover:text-white transition-colors"><i class="fa-solid fa-gift"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Beli 1 Gratis 1</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Set promo bundling</p>
                        </div>
                    </button>
                    <button class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow-md hover:border-amber-500/20 transition-all group">
                        <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-xl group-hover:bg-amber-500 group-hover:text-white transition-colors"><i class="fa-solid fa-tags"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Diskon Nominal</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Potongan harga tetap</p>
                        </div>
                    </button>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                        <h4 class="font-black text-slate-800 text-lg">Sekilas Toko Bulan Ini</h4>
                        <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-lg">Real-time</span>
                    </div>
                    <div class="divide-y divide-slate-100 px-5 text-sm font-medium text-slate-600">
                        <div class="flex items-center justify-between py-4"><div class="flex items-center gap-3"><i class="fa-solid fa-user-plus w-5 text-slate-400"></i> Pelanggan Baru</div><div class="font-black text-slate-800" x-text="summary.pelanggan_baru">0</div></div>
                        <div class="flex items-center justify-between py-4"><div class="flex items-center gap-3"><i class="fa-solid fa-cart-shopping w-5 text-slate-400"></i> Total Pesanan</div><div class="font-black text-slate-800" x-text="summary.total_transaksi">0</div></div>
                        <div class="flex items-center justify-between py-4"><div class="flex items-center gap-3"><i class="fa-solid fa-arrow-trend-up w-5 text-slate-400"></i> Pendapatan Bersih</div><div class="font-black text-emerald-600" x-text="'IDR ' + formatRupiah(summary.laba_bersih)">IDR 0</div></div>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden p-5">
                    <div class="flex justify-between items-center mb-6">
                        <h4 class="font-black text-slate-800 text-lg">Laporan Penjualan</h4>
                        <select class="text-xs font-bold bg-slate-50 border border-slate-200 text-slate-600 rounded-lg px-2 py-1 outline-none">
                            <option>Bulan Ini</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-y-6 gap-x-4 mb-8">
                        <div>
                            <p class="text-sm text-slate-500 font-bold mb-1">Total Penjualan</p>
                            <p class="text-lg font-black text-slate-800 mb-2" x-text="'IDR ' + formatRupiah(summary.total_penjualan)">IDR 0</p>
                            <span :class="summary.pct_penjualan >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'" class="border px-2.5 py-1 rounded-md text-[10px] font-black transition-colors">
                                <i class="fa-solid" :class="summary.pct_penjualan >= 0 ? 'fa-chevron-up' : 'fa-chevron-down'"></i> <span x-text="Math.abs(summary.pct_penjualan) + '%'"></span>
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-bold mb-1">Penjualan Kotor</p>
                            <p class="text-lg font-black text-slate-800 mb-2" x-text="'IDR ' + formatRupiah(summary.penjualan_kotor)">IDR 0</p>
                            <span :class="summary.pct_kotor >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'" class="border px-2.5 py-1 rounded-md text-[10px] font-black transition-colors">
                                <i class="fa-solid" :class="summary.pct_kotor >= 0 ? 'fa-chevron-up' : 'fa-chevron-down'"></i> <span x-text="Math.abs(summary.pct_kotor) + '%'"></span>
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-bold mb-1">Laba Kotor</p>
                            <p class="text-lg font-black text-slate-800 mb-2" x-text="'IDR ' + formatRupiah(summary.laba_kotor)">IDR 0</p>
                            <span :class="summary.pct_laba >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'" class="border px-2.5 py-1 rounded-md text-[10px] font-black transition-colors">
                                <i class="fa-solid" :class="summary.pct_laba >= 0 ? 'fa-chevron-up' : 'fa-chevron-down'"></i> <span x-text="Math.abs(summary.pct_laba) + '%'"></span>
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-bold mb-1">Transaksi</p>
                            <p class="text-lg font-black text-slate-800 mb-2" x-text="summary.total_transaksi">0</p>
                            <span :class="summary.pct_transaksi >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'" class="border px-2.5 py-1 rounded-md text-[10px] font-black transition-colors">
                                <i class="fa-solid" :class="summary.pct_transaksi >= 0 ? 'fa-chevron-up' : 'fa-chevron-down'"></i> <span x-text="Math.abs(summary.pct_transaksi) + '%'"></span>
                            </span>
                        </div>
                    </div>

                    <div class="relative w-full h-72">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>