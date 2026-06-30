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
                <?php if (!empty($_SESSION['pos_store_name'])): ?>
                <div class="bg-black/20 text-amber-300 border border-white/20 px-3 py-1.5 rounded-lg text-xs font-black flex items-center gap-2 shadow-inner">
                    <i class="fa-solid fa-store text-amber-400"></i> Outlet: <?= htmlspecialchars($_SESSION['pos_store_name']) ?>
                </div>
                <?php endif; ?>
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
            <div class="w-full space-y-6">
                
                <h3 class="font-black text-slate-800 text-lg md:text-xl">Sekilas "Love Cakes"</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="../pemasaran/diskon-otomatis/" class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow-md hover:border-primary/40 transition-all group">
                        <div class="w-12 h-12 rounded-full bg-blue-50 text-primary flex items-center justify-center text-xl group-hover:bg-primary group-hover:text-white transition-colors"><i class="fa-solid fa-percent"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Diskon Persen</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Atur potongan %</p>
                        </div>
                    </a>
                    <a href="../pemasaran/promo-items/" class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow-md hover:border-emerald-500/40 transition-all group">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl group-hover:bg-emerald-500 group-hover:text-white transition-colors"><i class="fa-solid fa-gift"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Beli 1 Gratis 1</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Set promo bundling</p>
                        </div>
                    </a>
                    <a href="../pemasaran/voucher/" class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow-md hover:border-amber-500/40 transition-all group">
                        <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-xl group-hover:bg-amber-500 group-hover:text-white transition-colors"><i class="fa-solid fa-tags"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Diskon Nominal</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Potongan harga tetap</p>
                        </div>
                    </a>
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

                <!-- CHARTS GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- CHART TREN PENJUALAN -->
                    <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden p-5 lg:col-span-2 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-6">
                                <h4 class="font-black text-slate-800 text-lg">Laporan Penjualan (7 Hari Terakhir)</h4>
                                <span class="text-xs font-bold text-primary bg-blue-50 px-3 py-1 rounded-lg">Pendapatan</span>
                            </div>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-y-6 gap-x-4 mb-6">
                                <div>
                                    <p class="text-xs text-slate-400 font-bold mb-1">Total Penjualan</p>
                                    <p class="text-base font-black text-slate-800 mb-1" x-text="'IDR ' + formatRupiah(summary.total_penjualan)">IDR 0</p>
                                    <span :class="summary.pct_penjualan >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'" class="border px-2 py-0.5 rounded text-[10px] font-black">
                                        <i class="fa-solid" :class="summary.pct_penjualan >= 0 ? 'fa-chevron-up' : 'fa-chevron-down'"></i> <span x-text="Math.abs(summary.pct_penjualan) + '%'"></span>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-bold mb-1">Penjualan Kotor</p>
                                    <p class="text-base font-black text-slate-800 mb-1" x-text="'IDR ' + formatRupiah(summary.penjualan_kotor)">IDR 0</p>
                                    <span :class="summary.pct_kotor >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'" class="border px-2 py-0.5 rounded text-[10px] font-black">
                                        <i class="fa-solid" :class="summary.pct_kotor >= 0 ? 'fa-chevron-up' : 'fa-chevron-down'"></i> <span x-text="Math.abs(summary.pct_kotor) + '%'"></span>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-bold mb-1">Laba Kotor</p>
                                    <p class="text-base font-black text-slate-800 mb-1" x-text="'IDR ' + formatRupiah(summary.laba_kotor)">IDR 0</p>
                                    <span :class="summary.pct_laba >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'" class="border px-2 py-0.5 rounded text-[10px] font-black">
                                        <i class="fa-solid" :class="summary.pct_laba >= 0 ? 'fa-chevron-up' : 'fa-chevron-down'"></i> <span x-text="Math.abs(summary.pct_laba) + '%'"></span>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-bold mb-1">Transaksi</p>
                                    <p class="text-base font-black text-slate-800 mb-1" x-text="summary.total_transaksi">0</p>
                                    <span :class="summary.pct_transaksi >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'" class="border px-2 py-0.5 rounded text-[10px] font-black">
                                        <i class="fa-solid" :class="summary.pct_transaksi >= 0 ? 'fa-chevron-up' : 'fa-chevron-down'"></i> <span x-text="Math.abs(summary.pct_transaksi) + '%'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="relative w-full h-64">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>

                    <!-- CHART METODE PEMBAYARAN -->
                    <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-black text-slate-800 text-base">Metode Pembayaran</h4>
                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded">Bulan Ini</span>
                        </div>
                        <div class="relative w-full h-64 flex items-center justify-center">
                            <canvas id="payChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- TABLE RIWAYAT PENJUALAN TERAKHIR -->
                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h4 class="font-black text-slate-800 text-lg">Riwayat Penjualan Terakhir</h4>
                            <p class="text-xs text-slate-400 font-medium">Daftar transaksi terbaru beserta aksi cetak struk dan invoice</p>
                        </div>
                        <a href="../transaksi/penjualan/" class="text-xs font-black text-primary bg-blue-50 hover:bg-blue-100 px-4 py-2 rounded-xl transition-colors">
                            Lihat Semua Transaksi <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Invoice & Waktu</th>
                                    <th class="p-4 font-black">Pelanggan</th>
                                    <th class="p-4 font-black text-center">Tipe Order</th>
                                    <th class="p-4 font-black text-right">Total Bayar</th>
                                    <th class="p-4 font-black text-center">Metode</th>
                                    <th class="p-4 font-black text-center">Aksi (Struk / Invoice)</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="recentSales.length === 0">
                                    <td colspan="6" class="p-8 text-center text-slate-400 font-bold">Belum ada transaksi penjualan terbaru.</td>
                                </tr>
                                <template x-for="sale in recentSales" :key="sale.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4">
                                            <div class="font-black text-slate-800" x-text="sale.invoice_no"></div>
                                            <div class="text-[11px] text-slate-400 font-medium mt-0.5" x-text="sale.created_at"></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-slate-700" x-text="sale.customer_name || 'Pelanggan Umum'"></div>
                                            <div class="mt-1">
                                                <span x-show="sale.payment_status === 'dp'" class="bg-amber-100 text-amber-700 font-black px-2 py-0.5 rounded text-[10px]"><i class="fa-solid fa-clock mr-1"></i>DP BELUM LUNAS</span>
                                                <span x-show="sale.payment_status === 'lunas' && sale.dp_amount > 0" class="bg-blue-100 text-blue-700 font-black px-2 py-0.5 rounded text-[10px]"><i class="fa-solid fa-check-double mr-1"></i>DP SUDAH LUNAS</span>
                                                <span x-show="sale.payment_status === 'lunas' && (!sale.dp_amount || sale.dp_amount == 0)" class="bg-emerald-100 text-emerald-700 font-black px-2 py-0.5 rounded text-[10px]"><i class="fa-solid fa-check mr-1"></i>LUNAS</span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-black uppercase border border-slate-200" x-text="sale.channel || 'TOKO'"></span>
                                        </td>
                                        <td class="p-4 text-right font-black text-primary" x-text="'Rp ' + formatRupiah(sale.total_amount)"></td>
                                        <td class="p-4 text-center">
                                            <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase border" 
                                                  :class="sale.payment_method === 'cash' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-blue-50 text-blue-600 border-blue-200'" 
                                                  x-text="sale.payment_method || 'CASH'"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="printReceipt(sale.invoice_no)" class="bg-slate-800 hover:bg-slate-900 text-white px-3 py-1.5 rounded-xl text-xs font-black flex items-center gap-1.5 shadow-sm transition-all" title="Print Struk Kasir">
                                                    <i class="fa-solid fa-print"></i> Struk
                                                </button>
                                                <button @click="printInvoice(sale.invoice_no)" class="bg-primary hover:bg-blue-700 text-white px-3 py-1.5 rounded-xl text-xs font-black flex items-center gap-1.5 shadow-sm transition-all" title="Buat & Unduh Invoice">
                                                    <i class="fa-solid fa-file-invoice"></i> Invoice
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

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>