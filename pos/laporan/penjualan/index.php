<?php
require_once '../../../config/auth.php';
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Riwayat Penjualan - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="riwayatApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-receipt mr-2"></i>Riwayat Penjualan</h2>
            </div>
            <div class="flex items-center gap-3">
                <button @click="fetchData(true)" :disabled="isSyncing" class="bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-white/10 shadow-sm disabled:opacity-50">
                    <i class="fa-solid fa-rotate" :class="isSyncing ? 'fa-spin' : ''"></i> Sync Data
                </button>
                <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm"><i class="fa-solid fa-power-off"></i></button>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row gap-3 items-center sticky top-0 z-10 flex-wrap">
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <input type="date" x-model="filters.start_date" :disabled="isRestricted" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20 transition-all text-slate-700 disabled:opacity-50">
                        <span class="text-slate-400 font-bold text-sm">s/d</span>
                        <input type="date" x-model="filters.end_date" :disabled="isRestricted" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20 transition-all text-slate-700 disabled:opacity-50">
                    </div>
                    <div class="relative w-full md:flex-1 min-w-[200px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari No. Invoice / Pelanggan..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm">
                    </div>
                    <select x-model="filters.payment_method" @change="fetchSales()" class="w-full md:w-auto bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-600 cursor-pointer">
                        <option value="">💳 Semua Metode Bayar</option>
                        <option value="cash">💵 Cash / Tunai</option>
                        <option value="qris">📱 QRIS / Digital</option>
                        <option value="transfer">🏦 Transfer Bank</option>
                        <option value="split">🔄 Split Payment</option>
                        <?php
                        try {
                            if (!isset($pdo)) require_once '../../../config/database.php';
                            $stmt_pm = $pdo->query("SELECT name FROM payment_methods WHERE is_active = 1 ORDER BY name ASC");
                            while ($pm_row = $stmt_pm->fetch(PDO::FETCH_ASSOC)) {
                                $pm_name = htmlspecialchars($pm_row['name']);
                                if (!in_array(strtolower($pm_name), ['cash', 'qris', 'transfer bank', 'split'])) {
                                    echo '<option value="' . strtolower($pm_name) . '">💳 ' . $pm_name . '</option>';
                                }
                            }
                        } catch (Exception $e) {}
                        ?>
                    </select>
                    <select x-model="filters.payment_status" @change="fetchSales()" class="w-full md:w-auto bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-600 cursor-pointer">
                        <option value="">📌 Semua Status Bayar</option>
                        <option value="lunas">✅ Lunas</option>
                        <option value="dp">⏳ DP (Belum Lunas)</option>
                    </select>
                    <button @click="fetchSales()" class="w-full md:w-auto bg-primary text-white px-6 py-2.5 rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </div>

                <div x-show="isRestricted" class="bg-amber-50 border border-amber-200 p-4 rounded-2xl flex items-center gap-3 text-amber-700">
                    <i class="fa-solid fa-shield-halved text-xl"></i>
                    <p class="text-sm font-bold">Mode Terbatas: Kasir hanya dapat melihat riwayat transaksi hari ini.</p>
                </div>

                <div x-show="isLoading" class="text-center py-20 flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-500 font-bold tracking-widest uppercase text-sm">Memuat Data Penjualan...</p>
                </div>

                <div x-show="!isLoading" class="space-y-6">
                    <!-- TAB NAVIGATION -->
                    <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-2">
                        <button @click="activeTab = 'ringkasan'" :class="activeTab === 'ringkasan' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'" class="px-5 py-3 rounded-xl font-black text-xs flex items-center gap-2 transition-all whitespace-nowrap">
                            <i class="fa-solid fa-chart-pie"></i> Ringkasan & Pembayaran
                        </button>
                        <button @click="activeTab = 'produk'" :class="activeTab === 'produk' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'" class="px-5 py-3 rounded-xl font-black text-xs flex items-center gap-2 transition-all whitespace-nowrap">
                            <i class="fa-solid fa-cake-candles"></i> Kategori & Barang Terlaris
                        </button>
                        <button @click="activeTab = 'pelanggan'" :class="activeTab === 'pelanggan' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'" class="px-5 py-3 rounded-xl font-black text-xs flex items-center gap-2 transition-all whitespace-nowrap">
                            <i class="fa-solid fa-users"></i> Penjualan per Konsumen
                        </button>
                        <button @click="activeTab = 'rincian'" :class="activeTab === 'rincian' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'" class="px-5 py-3 rounded-xl font-black text-xs flex items-center gap-2 transition-all whitespace-nowrap">
                            <i class="fa-solid fa-receipt"></i> Rincian Detail Transaksi
                        </button>
                    </div>

                    <!-- TAB 1: RINGKASAN & PEMBAYARAN -->
                    <div x-show="activeTab === 'ringkasan'" class="space-y-6">
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

                        <!-- METODE PEMBAYARAN & STATUS DP/LUNAS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                                    <h3 class="font-black text-slate-700 uppercase text-xs tracking-widest">
                                        <i class="fa-solid fa-credit-card text-blue-400 mr-2"></i> Pembayaran TF / Cash
                                    </h3>
                                    <div class="flex items-center gap-1.5">
                                        <button @click="printPDF('metode_pembayaran', 'Laporan Pembayaran TF / Cash')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-lg transition-colors text-xs font-black flex items-center gap-1" title="Cetak PDF">
                                            <i class="fa-solid fa-print text-rose-500"></i> PDF
                                        </button>
                                        <button @click="exportCSV('metode_pembayaran')" class="bg-blue-50 hover:bg-blue-100 text-blue-600 px-2.5 py-1 rounded-lg transition-colors text-xs font-black flex items-center gap-1" title="Unduh Excel/CSV">
                                            <i class="fa-solid fa-file-excel text-emerald-600"></i> Excel
                                        </button>
                                    </div>
                                </div>
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
                                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                                    <h3 class="font-black text-slate-700 uppercase text-xs tracking-widest">
                                        <i class="fa-solid fa-file-invoice-dollar text-emerald-400 mr-2"></i> Pembayaran DP vs Pelunasan
                                    </h3>
                                    <div class="flex items-center gap-1.5">
                                        <button @click="printPDF('dp_pelunasan', 'Laporan DP vs Pelunasan')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-lg transition-colors text-xs font-black flex items-center gap-1" title="Cetak PDF">
                                            <i class="fa-solid fa-print text-rose-500"></i> PDF
                                        </button>
                                        <button @click="exportCSV('dp_pelunasan')" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 px-2.5 py-1 rounded-lg transition-colors text-xs font-black flex items-center gap-1" title="Unduh Excel/CSV">
                                            <i class="fa-solid fa-file-excel text-emerald-600"></i> Excel
                                        </button>
                                    </div>
                                </div>
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

                    <!-- TAB 2: PRODUK & KATEGORI -->
                    <div x-show="activeTab === 'produk'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                            <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                                <h3 class="font-black text-slate-700 uppercase text-xs tracking-widest">
                                    <i class="fa-solid fa-layer-group text-purple-400 mr-2"></i> Penjualan per Kategori
                                </h3>
                                <div class="flex items-center gap-1.5">
                                    <button @click="printPDF('kategori', 'Laporan Penjualan per Kategori')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-lg transition-colors text-xs font-black flex items-center gap-1" title="Cetak PDF">
                                        <i class="fa-solid fa-print text-rose-500"></i> PDF
                                    </button>
                                    <button @click="exportCSV('kategori')" class="bg-purple-50 hover:bg-purple-100 text-purple-600 px-2.5 py-1 rounded-lg transition-colors text-xs font-black flex items-center gap-1" title="Unduh Excel/CSV">
                                        <i class="fa-solid fa-file-excel text-emerald-600"></i> Excel
                                    </button>
                                </div>
                            </div>
                            <div class="overflow-y-auto max-h-[450px] custom-scrollbar pr-2 space-y-3">
                                <template x-for="cat in paginatedCategories" :key="cat.category_name">
                                    <div @click="openItemDetail('category', cat)" class="flex justify-between items-center border-b border-slate-100 pb-3 last:border-0 last:pb-0 hover:bg-slate-50 p-2 -mx-2 rounded-xl cursor-pointer transition-colors group">
                                        <div>
                                            <div class="font-bold text-sm text-slate-700 group-hover:text-primary transition-colors flex items-center gap-2">
                                                <span x-text="cat.category_name"></span>
                                                <span class="text-[9px] bg-primary/10 text-primary px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Lihat Detail</span>
                                            </div>
                                            <div class="text-xs text-slate-500 font-medium mt-0.5" x-text="cat.total_qty + ' items terjual'"></div>
                                        </div>
                                        <div class="font-black text-primary text-base" x-text="'Rp ' + formatRupiah(cat.total_amount)"></div>
                                    </div>
                                </template>
                                <div x-show="salesByCategory.length === 0" class="text-center py-8 text-sm font-bold text-slate-400">Belum ada data</div>
                            </div>
                            <div x-show="totalCatPages > 1" class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100 text-xs font-bold text-slate-500">
                                <span>Halaman <span x-text="catPage"></span> dari <span x-text="totalCatPages"></span></span>
                                <div class="flex gap-1.5">
                                    <button @click="if(catPage > 1) catPage--" :disabled="catPage === 1" class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-40 transition-all"><i class="fa-solid fa-chevron-left"></i> Prev</button>
                                    <button @click="if(catPage < totalCatPages) catPage++" :disabled="catPage === totalCatPages" class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-40 transition-all">Next <i class="fa-solid fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                                    <h3 class="font-black text-slate-700 uppercase text-xs tracking-widest">
                                        <i class="fa-solid fa-ranking-star text-amber-400 mr-2"></i> Penjualan per Barang
                                    </h3>
                                    <div class="flex items-center gap-1.5">
                                        <button @click="printPDF('barang', 'Laporan Penjualan per Barang Terlaris')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-lg transition-colors text-xs font-black flex items-center gap-1" title="Cetak PDF">
                                            <i class="fa-solid fa-print text-rose-500"></i> PDF
                                        </button>
                                        <button @click="exportCSV('barang')" class="bg-amber-50 hover:bg-amber-100 text-amber-600 px-2.5 py-1 rounded-lg transition-colors text-xs font-black flex items-center gap-1" title="Unduh Excel/CSV">
                                            <i class="fa-solid fa-file-excel text-emerald-600"></i> Excel
                                        </button>
                                    </div>
                                </div>
                                <div class="overflow-y-auto max-h-[450px] custom-scrollbar pr-2 space-y-3">
                                    <template x-for="(item, index) in paginatedItems" :key="item.item_name">
                                        <div @click="openItemDetail('product', {product_name: item.item_name, total_revenue: item.total_amount})" class="flex justify-between items-center border-b border-slate-100 pb-3 last:border-0 last:pb-0 hover:bg-slate-50 p-2 -mx-2 rounded-xl cursor-pointer transition-colors group">
                                            <div class="flex items-center gap-3">
                                                <span class="w-7 h-7 rounded-full bg-slate-100 group-hover:bg-primary group-hover:text-white text-slate-600 flex items-center justify-center text-xs font-black shrink-0 transition-colors" x-text="(itemPage - 1) * perPage + index + 1"></span>
                                                <div>
                                                    <div class="font-bold text-sm text-slate-700 group-hover:text-primary transition-colors flex items-center gap-2">
                                                        <span x-text="item.item_name"></span>
                                                        <span class="text-[9px] bg-primary/10 text-primary px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">Lihat Detail</span>
                                                    </div>
                                                    <div class="text-xs text-slate-500 font-medium mt-0.5" x-text="item.total_qty + ' pcs terjual'"></div>
                                                </div>
                                            </div>
                                            <div class="font-black text-primary text-base" x-text="'Rp ' + formatRupiah(item.total_amount)"></div>
                                        </div>                   </div>
                                    </template>
                                    <div x-show="salesByItem.length === 0" class="text-center py-8 text-sm font-bold text-slate-400">Belum ada data</div>
                                </div>
                            </div>
                            <div x-show="totalItemPages > 1" class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100 text-xs font-bold text-slate-500">
                                <span>Halaman <span x-text="itemPage"></span> dari <span x-text="totalItemPages"></span></span>
                                <div class="flex gap-1.5">
                                    <button @click="if(itemPage > 1) itemPage--" :disabled="itemPage === 1" class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-40 transition-all"><i class="fa-solid fa-chevron-left"></i> Prev</button>
                                    <button @click="if(itemPage < totalItemPages) itemPage++" :disabled="itemPage === totalItemPages" class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-40 transition-all">Next <i class="fa-solid fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: PELANGGAN -->
                    <div x-show="activeTab === 'pelanggan'" class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="font-black text-slate-800 text-base">Penjualan per Konsumen</h3>
                                <p class="text-xs text-slate-400 font-medium mt-0.5">Daftar pelanggan loyal berdasarkan total transaksi belanja</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="printPDF('pelanggan', 'Laporan Penjualan per Konsumen')" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl transition-colors text-xs font-black flex items-center gap-1.5 shadow-sm" title="Cetak PDF">
                                    <i class="fa-solid fa-print text-rose-400"></i> Cetak PDF
                                </button>
                                <button @click="exportCSV('pelanggan')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl transition-colors text-xs font-black flex items-center gap-1.5 shadow-sm" title="Unduh Excel/CSV">
                                    <i class="fa-solid fa-file-excel"></i> Unduh Excel
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="cust in paginatedCustomers" :key="cust.customer_name">
                                <div @click="openCustomerDetail(cust)" class="bg-slate-50 p-4 rounded-2xl border border-slate-200/60 flex items-center justify-between hover:shadow-lg hover:border-primary/50 cursor-pointer transition-all active:scale-[0.99] group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-500 group-hover:bg-primary group-hover:text-white transition-colors flex items-center justify-center font-black text-sm shrink-0">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="font-black text-sm text-slate-800 group-hover:text-primary transition-colors" x-text="cust.customer_name"></div>
                                            <div class="text-xs text-slate-500 font-bold mt-0.5 flex items-center gap-1.5">
                                                <span x-text="cust.total_transactions + ' transaksi'"></span>
                                                <span class="text-[10px] text-primary/80 font-black flex items-center gap-0.5"><i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> Lihat Detail</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right font-black text-rose-600 text-base group-hover:text-primary transition-colors" x-text="'Rp ' + formatRupiah(cust.total_spent)"></div>
                                </div>
                            </template>
                        </div>
                        <div x-show="salesByCustomer.length === 0" class="text-center py-12 text-sm font-bold text-slate-400">Belum ada data pelanggan pada periode ini.</div>
                        
                        <div x-show="totalCustPages > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-slate-100 text-xs font-bold text-slate-500">
                            <span>Menampilkan halaman <span x-text="custPage"></span> dari <span x-text="totalCustPages"></span> (<span x-text="salesByCustomer.length"></span> pelanggan)</span>
                            <div class="flex gap-1.5">
                                <button @click="if(custPage > 1) custPage--" :disabled="custPage === 1" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-40 transition-all font-black"><i class="fa-solid fa-chevron-left mr-1"></i> Prev</button>
                                <button @click="if(custPage < totalCustPages) custPage++" :disabled="custPage === totalCustPages" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-40 transition-all font-black">Next <i class="fa-solid fa-chevron-right ml-1"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: RINCIAN TRANSAKSI -->
                    <div x-show="activeTab === 'rincian'" class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="font-black text-slate-800 text-base">Rincian Detail Penjualan</h3>
                                <p class="text-xs text-slate-400 font-medium mt-0.5">Daftar seluruh nota penjualan sesuai rentang tanggal filter</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                <select x-model="filters.payment_method" @change="fetchSales()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-700 outline-none focus:border-primary shadow-xs cursor-pointer">
                                    <option value="">💳 Semua Metode Bayar</option>
                                    <option value="cash">💵 Cash / Tunai</option>
                                    <option value="qris">📱 QRIS / Digital</option>
                                    <option value="transfer">🏦 Transfer Bank</option>
                                    <option value="split">🔄 Split Payment</option>
                                    <?php
                                    try {
                                        if (!isset($pdo)) require_once '../../../config/database.php';
                                        $stmt_pm = $pdo->query("SELECT name FROM payment_methods WHERE is_active = 1 ORDER BY name ASC");
                                        while ($pm_row = $stmt_pm->fetch(PDO::FETCH_ASSOC)) {
                                            $pm_name = htmlspecialchars($pm_row['name']);
                                            if (!in_array(strtolower($pm_name), ['cash', 'qris', 'transfer bank', 'split'])) {
                                                echo '<option value="' . strtolower($pm_name) . '">💳 ' . $pm_name . '</option>';
                                            }
                                        }
                                    } catch (Exception $e) {}
                                    ?>
                                </select>
                                <select x-model="filters.payment_status" @change="salePage = 1" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-700 outline-none focus:border-primary shadow-xs cursor-pointer">
                                    <option value="">📌 Semua Status Bayar</option>
                                    <option value="lunas">✅ Lunas</option>
                                    <option value="dp">⏳ DP (Belum Lunas)</option>
                                </select>
                                <select x-model="filters.discount_filter" @change="salePage = 1" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-700 outline-none focus:border-primary shadow-xs cursor-pointer">
                                    <option value="">🏷️ Semua (+ Diskon)</option>
                                    <option value="has_discount">✂️ Ada Diskon</option>
                                    <option value="no_discount">💯 Tanpa Diskon</option>
                                    <option value="voucher">🎟️ Diskon Voucher</option>
                                    <option value="manual">🔐 Diskon Manual SPV</option>
                                    <option value="auto">⚡ Diskon Otomatis</option>
                                    <option value="points">⭐ Diskon Poin</option>
                                </select>
                                <button @click="printPDF('rincian', 'Laporan Rincian Transaksi Penjualan')" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl transition-colors text-xs font-black flex items-center gap-1.5 shadow-sm" title="Cetak PDF">
                                    <i class="fa-solid fa-print text-rose-400"></i> Cetak PDF
                                </button>
                                <button @click="exportCSV('rincian')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl transition-colors text-xs font-black flex items-center gap-1.5 shadow-sm" title="Unduh Excel/CSV">
                                    <i class="fa-solid fa-file-excel"></i> Unduh Excel
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse whitespace-nowrap min-w-[900px]">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                        <th class="p-4 font-black">Invoice & Waktu</th>
                                        <th class="p-4 font-black">Pelanggan</th>
                                        <th class="p-4 font-black text-center">Status</th>
                                        <th class="p-4 font-black text-right">Subtotal</th>
                                        <th class="p-4 font-black text-right text-rose-500">Diskon</th>
                                        <th class="p-4 font-black text-right">Total Bayar</th>
                                        <th class="p-4 font-black text-center">Metode</th>
                                        <th class="p-4 font-black text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-slate-100">
                                    <tr x-show="filteredSales.length === 0">
                                        <td colspan="8" class="p-8 text-center text-slate-400 font-bold">Belum ada transaksi penjualan pada periode ini.</td>
                                    </tr>
                                    <template x-for="sale in paginatedSales" :key="sale.id">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="p-4">
                                                <div class="font-black text-slate-800 flex items-center gap-2">
                                                    <span x-text="sale.invoice_no"></span>
                                                    <!-- Badge diskon jika ada -->
                                                    <template x-if="(parseFloat(sale.discount_voucher)||0)+(parseFloat(sale.discount_manual)||0)+(parseFloat(sale.discount_auto)||0)+(parseFloat(sale.discount_points)||0) > 0">
                                                        <span class="text-[9px] bg-rose-100 text-rose-600 px-1.5 py-0.5 rounded font-black tracking-wide">✂️ DISC</span>
                                                    </template>
                                                </div>
                                                <div class="text-[10px] font-bold text-slate-400 mt-1" x-text="formatDate(sale.created_at)"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="font-black text-slate-700" x-text="sale.customer_name || 'Pelanggan Umum'"></div>
                                                <div class="text-[10px] font-bold text-slate-400" x-text="sale.channel || 'toko'"></div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <span :class="sale.payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" x-text="sale.payment_status"></span>
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="font-bold text-slate-400 text-xs line-through" x-show="(parseFloat(sale.discount_voucher)||0)+(parseFloat(sale.discount_manual)||0)+(parseFloat(sale.discount_auto)||0)+(parseFloat(sale.discount_points)||0) > 0" x-text="'Rp ' + formatRupiah(parseFloat(sale.subtotal) + parseFloat(sale.shipping_cost||0))"></div>
                                            </td>
                                            <td class="p-4 text-right">
                                                <template x-if="(parseFloat(sale.discount_voucher)||0)+(parseFloat(sale.discount_manual)||0)+(parseFloat(sale.discount_auto)||0)+(parseFloat(sale.discount_points)||0) > 0">
                                                    <div class="space-y-0.5">
                                                        <template x-if="parseFloat(sale.discount_voucher) > 0">
                                                            <div class="text-[10px] font-bold text-rose-500">🎟️ -Rp <span x-text="formatRupiah(sale.discount_voucher)"></span></div>
                                                        </template>
                                                        <template x-if="parseFloat(sale.discount_manual) > 0">
                                                            <div class="text-[10px] font-bold text-rose-500">🔐 -Rp <span x-text="formatRupiah(sale.discount_manual)"></span></div>
                                                        </template>
                                                        <template x-if="parseFloat(sale.discount_auto) > 0">
                                                            <div class="text-[10px] font-bold text-rose-500">⚡ -Rp <span x-text="formatRupiah(sale.discount_auto)"></span></div>
                                                        </template>
                                                        <template x-if="parseFloat(sale.discount_points) > 0">
                                                            <div class="text-[10px] font-bold text-rose-500">⭐ -Rp <span x-text="formatRupiah(sale.discount_points)"></span></div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="(parseFloat(sale.discount_voucher)||0)+(parseFloat(sale.discount_manual)||0)+(parseFloat(sale.discount_auto)||0)+(parseFloat(sale.discount_points)||0) === 0">
                                                    <span class="text-[10px] font-bold text-slate-300">—</span>
                                                </template>
                                            </td>
                                            <td class="p-4 text-right font-black text-primary" x-text="'Rp ' + formatRupiah(sale.total_amount)"></td>
                                            <td class="p-4 text-center"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-black uppercase border border-slate-200" x-text="sale.payment_method"></span></td>
                                            <td class="p-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button @click="openDetail(sale)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-primary hover:text-white transition-all" title="Lihat Detail Barang"><i class="fa-solid fa-eye text-xs"></i></button>
                                                    <button @click="printReceipt(sale.invoice_no)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all" title="Cetak Struk"><i class="fa-solid fa-print text-xs"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        
                        <div x-show="totalSalePages > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-slate-100 text-xs font-bold text-slate-500">
                            <span>Menampilkan halaman <span x-text="salePage"></span> dari <span x-text="totalSalePages"></span> (<span x-text="filteredSales.length"></span> transaksi)</span>
                            <div class="flex gap-1.5">
                                <button @click="if(salePage > 1) salePage--" :disabled="salePage === 1" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-40 transition-all font-black"><i class="fa-solid fa-chevron-left mr-1"></i> Prev</button>
                                <button @click="if(salePage < totalSalePages) salePage++" :disabled="salePage === totalSalePages" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-40 transition-all font-black">Next <i class="fa-solid fa-chevron-right ml-1"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

        <div x-show="showModal" class="fixed inset-0 z-[60] flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[85vh] m-4 overflow-hidden border border-slate-200">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-black text-slate-800" x-text="'Detail ' + activeSale?.invoice_no"></h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <template x-for="item in activeDetails" :key="item.id">
                        <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center overflow-hidden border border-slate-200">
                                <i class="fa-solid fa-cake-candles text-slate-300"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-sm text-slate-800" x-text="item.product_name"></div>
                                <div class="text-[10px] font-black text-primary" x-text="item.qty + ' x Rp ' + formatRupiah(item.price)"></div>
                            </div>
                            <div class="font-black text-slate-800 text-sm" x-text="'Rp ' + formatRupiah(item.subtotal)"></div>
                        </div>
                    </template>
                    <div class="border-t border-dashed border-slate-200 pt-4 space-y-2">
                        <div class="flex justify-between text-xs font-bold text-slate-500"><span>Subtotal</span> <span x-text="'Rp ' + formatRupiah(activeSale?.subtotal)"></span></div>
                        <div class="flex justify-between text-xs font-bold text-rose-500"><span>Diskon</span> <span x-text="'- Rp ' + formatRupiah(parseFloat(activeSale?.discount_voucher) + parseFloat(activeSale?.discount_points) + parseFloat(activeSale?.discount_manual))"></span></div>
                        <div class="flex justify-between text-lg font-black text-primary border-t border-slate-100 pt-2"><span>Total</span> <span x-text="'Rp ' + formatRupiah(activeSale?.total_amount)"></span></div>
                    </div>

                    <!-- KHUSUS RIWAYAT PEMBAYARAN DP & PELUNASAN -->
                    <template x-if="activeSale && (activeSale.payment_status === 'dp' || parseFloat(activeSale.dp_amount || 0) > 0 || parseFloat(activeSaleInfo?.dp_amount || 0) > 0 || (activePayments && activePayments.some(p => p.payment_type === 'dp' || p.payment_type === 'pelunasan')))">
                        <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 mt-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-amber-200/60 pb-2">
                                <span class="text-xs font-black text-amber-800 flex items-center gap-1.5 uppercase tracking-wide">
                                    <i class="fa-solid fa-clock-rotate-left text-amber-600"></i> Riwayat Pembayaran (DP & Pelunasan)
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                      :class="activeSale.payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-200 text-amber-900 border border-amber-400'">
                                    <span x-text="activeSale.payment_status === 'lunas' ? '✅ SUDAH LUNAS' : '⏳ STATUS DP'"></span>
                                </span>
                            </div>

                            <!-- Cek jika ada data riwayat spesifik dari tabel sale_payments_pos -->
                            <template x-if="activePayments && activePayments.filter(p => p.payment_type === 'dp' || p.payment_type === 'pelunasan').length > 0">
                                <div class="space-y-2.5">
                                    <template x-for="(pay, idx) in activePayments.filter(p => p.payment_type === 'dp' || p.payment_type === 'pelunasan')" :key="idx">
                                        <div class="flex items-center justify-between bg-white/80 p-2.5 rounded-xl shadow-2xs border"
                                             :class="pay.payment_type === 'pelunasan' ? 'border-emerald-200 bg-emerald-50/30' : 'border-amber-200'">
                                            <div>
                                                <div class="text-xs font-black" :class="pay.payment_type === 'pelunasan' ? 'text-emerald-700' : 'text-amber-700'">
                                                    <i :class="pay.payment_type === 'pelunasan' ? 'fa-solid fa-check-circle mr-1' : 'fa-solid fa-hourglass-half mr-1'"></i>
                                                    <span x-text="pay.payment_type === 'pelunasan' ? 'PELUNASAN' : 'BAYAR DP (AWAL)'"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 font-medium mt-0.5 flex items-center gap-1">
                                                    <i class="fa-regular fa-calendar-days text-slate-400"></i>
                                                    <span x-text="formatDate(pay.created_at)"></span>
                                                    <span class="text-slate-300">•</span>
                                                    <span class="uppercase font-bold text-slate-600" x-text="pay.payment_method || 'Cash'"></span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xs font-black text-slate-800" x-text="'Rp ' + formatRupiah(pay.amount)"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Fallback jika data riwayat pembayaran di sale_payments_pos kosong (untuk data lama yang hanya ada di sales_pos) -->
                            <template x-if="!activePayments || activePayments.filter(p => p.payment_type === 'dp' || p.payment_type === 'pelunasan').length === 0">
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between bg-white/80 p-2.5 rounded-xl border border-amber-200 shadow-2xs">
                                        <div>
                                            <div class="text-xs font-black text-amber-700">
                                                <i class="fa-solid fa-hourglass-half mr-1"></i> BAYAR DP (AWAL)
                                            </div>
                                            <div class="text-[10px] text-slate-500 font-medium mt-0.5 flex items-center gap-1">
                                                <i class="fa-regular fa-calendar-days text-slate-400"></i>
                                                <span x-text="formatDate(activeSale.created_at)"></span>
                                                <span class="text-slate-300">•</span>
                                                <span class="uppercase font-bold text-slate-600" x-text="activeSale.payment_method || 'Cash'"></span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs font-black text-slate-800" x-text="'Rp ' + formatRupiah(activeSale.dp_amount || activeSaleInfo?.dp_amount || 0)"></div>
                                        </div>
                                    </div>

                                    <template x-if="activeSale.payment_status === 'lunas'">
                                        <div class="flex items-center justify-between bg-white/80 p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/30 shadow-2xs">
                                            <div>
                                                <div class="text-xs font-black text-emerald-700">
                                                    <i class="fa-solid fa-check-circle mr-1"></i> PELUNASAN
                                                </div>
                                                <div class="text-[10px] text-slate-500 font-medium mt-0.5 flex items-center gap-1">
                                                    <i class="fa-regular fa-calendar-days text-slate-400"></i>
                                                    <span x-text="formatDate(activeSaleInfo?.settled_at || activeSale.settled_at || activeSale.created_at)"></span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xs font-black text-slate-800" x-text="'Rp ' + formatRupiah((activeSale.total_amount || 0) - (activeSale.dp_amount || activeSaleInfo?.dp_amount || 0))"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Ringkasan Sisa Tagihan -->
                            <div class="border-t border-amber-200/60 pt-2 flex justify-between items-center text-xs">
                                <span class="font-bold text-slate-600">Sisa Tagihan / Status:</span>
                                <span class="font-black" :class="activeSale.payment_status === 'lunas' ? 'text-emerald-600' : 'text-rose-600'"
                                      x-text="activeSale.payment_status === 'lunas' ? 'Rp 0 (LUNAS)' : ('Rp ' + formatRupiah((activeSale.total_amount || 0) - (activeSale.dp_amount || activeSaleInfo?.dp_amount || activeSale.amount_paid || 0)))">
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- MODAL RIWAYAT TRANSAKSI PER KATEGORI/BARANG -->
        <div x-show="showItemModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showItemModal = false"></div>
            <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[85vh] m-4 overflow-hidden border border-slate-200">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center font-black text-base">
                            <i :class="activeItemType === 'category' ? 'fa-solid fa-layer-group' : 'fa-solid fa-box'"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                <span x-text="activeItemType === 'category' ? 'Penjualan Kategori' : 'Penjualan Produk'"></span>
                                <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                                <span class="text-primary" x-text="activeItemName"></span>
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5" x-text="'Total ada ' + activeItemSales.length + ' transaksi dengan omset Rp ' + formatRupiah(activeItemTotal)"></p>
                        </div>
                    </div>
                    <button @click="showItemModal = false" class="text-slate-400 hover:text-rose-500 w-8 h-8 rounded-full flex items-center justify-center hover:bg-rose-50 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 space-y-3 relative">
                    <div x-show="isItemLoading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
                        <i class="fa-solid fa-circle-notch fa-spin text-3xl text-primary mb-3"></i>
                        <span class="font-bold text-slate-600 text-sm">Menarik data transaksi...</span>
                    </div>

                    <template x-if="!isItemLoading && activeItemSales.length === 0">
                        <div class="text-center py-12 text-sm font-bold text-slate-400">Tidak ada rincian transaksi yang ditemukan.</div>
                    </template>
                    <template x-for="sale in activeItemSales" :key="sale.id">
                        <div class="bg-slate-50 hover:bg-white p-4 rounded-2xl border border-slate-200/80 hover:border-primary/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs hover:shadow-md transition-all">
                            <div class="flex items-center gap-3.5">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-base shrink-0"
                                     :class="sale.payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                                    <i :class="sale.payment_status === 'lunas' ? 'fa-solid fa-file-invoice-dollar' : 'fa-solid fa-file-invoice'"></i>
                                </div>
                                <div>
                                    <div class="font-black text-sm text-slate-800 flex items-center gap-2">
                                        <span x-text="sale.invoice_no"></span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                              :class="sale.payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'"
                                              x-text="sale.payment_status === 'lunas' ? '✅ Lunas' : '⏳ DP / Belum Lunas'"></span>
                                    </div>
                                    <div class="text-xs text-slate-400 font-medium mt-1 flex items-center gap-2">
                                        <span><i class="fa-regular fa-calendar mr-1"></i><span x-text="formatDate(sale.created_at)"></span></span>
                                        <span>•</span>
                                        <span class="uppercase font-bold text-slate-600"><i class="fa-solid fa-credit-card mr-1"></i><span x-text="sale.payment_method || 'Cash'"></span></span>
                                    </div>
                                    <div class="mt-1.5" x-show="sale.customer_name && sale.customer_name !== 'Pelanggan Umum'">
                                        <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full font-bold flex items-center gap-1 w-max">
                                            <i class="fa-solid fa-user"></i> <span x-text="sale.customer_name"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-4 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200/60">
                                <div class="text-left sm:text-right">
                                    <div class="text-[10px] text-slate-400 font-bold uppercase">Total Tagihan</div>
                                    <div class="text-sm font-black text-primary" x-text="'Rp ' + formatRupiah(sale.total_amount)"></div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button @click="openDetail(sale)" class="w-9 h-9 bg-white border border-slate-200 hover:border-primary text-slate-600 hover:text-primary rounded-xl transition-all flex items-center justify-center shadow-2xs hover:shadow-sm" title="Lihat Detail Rincian Barang">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- MODAL RIWAYAT TRANSAKSI PER KONSUMEN -->
        <div x-show="showCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCustomerModal = false"></div>
            <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[85vh] m-4 overflow-hidden border border-slate-200">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-black text-base">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-base" x-text="'Riwayat Transaksi • ' + (activeCustomer?.customer_name || 'Pelanggan')"></h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5" x-text="'Total ' + (activeCustomer?.total_transactions || 0) + ' transaksi dengan total belanja Rp ' + formatRupiah(activeCustomer?.total_spent || 0)"></p>
                        </div>
                    </div>
                    <button @click="showCustomerModal = false" class="text-slate-400 hover:text-rose-500 w-8 h-8 rounded-full flex items-center justify-center hover:bg-rose-50 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 space-y-3">
                    <template x-if="activeCustomerSales.length === 0">
                        <div class="text-center py-12 text-sm font-bold text-slate-400">Tidak ada rincian transaksi yang ditemukan pada filter periode ini.</div>
                    </template>
                    <template x-for="sale in activeCustomerSales" :key="sale.id">
                        <div class="bg-slate-50 hover:bg-white p-4 rounded-2xl border border-slate-200/80 hover:border-primary/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs hover:shadow-md transition-all">
                            <div class="flex items-center gap-3.5">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-base shrink-0"
                                     :class="sale.payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                                    <i :class="sale.payment_status === 'lunas' ? 'fa-solid fa-file-invoice-dollar' : 'fa-solid fa-file-invoice'"></i>
                                </div>
                                <div>
                                    <div class="font-black text-sm text-slate-800 flex items-center gap-2">
                                        <span x-text="sale.invoice_no"></span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                              :class="sale.payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'"
                                              x-text="sale.payment_status === 'lunas' ? '✅ Lunas' : '⏳ DP / Belum Lunas'"></span>
                                    </div>
                                    <div class="text-xs text-slate-400 font-medium mt-1 flex items-center gap-2">
                                        <span><i class="fa-regular fa-calendar mr-1"></i><span x-text="formatDate(sale.created_at)"></span></span>
                                        <span>•</span>
                                        <span class="uppercase font-bold text-slate-600"><i class="fa-solid fa-credit-card mr-1"></i><span x-text="sale.payment_method || 'Cash'"></span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-4 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200/60">
                                <div class="text-left sm:text-right">
                                    <div class="text-[10px] text-slate-400 font-bold uppercase">Total Tagihan</div>
                                    <div class="text-sm font-black text-primary" x-text="'Rp ' + formatRupiah(sale.total_amount)"></div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button @click="openDetail(sale)" class="w-9 h-9 bg-white border border-slate-200 hover:border-primary text-slate-600 hover:text-primary rounded-xl transition-all flex items-center justify-center shadow-2xs hover:shadow-sm" title="Lihat Detail Rincian Barang">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </button>
                                    <button @click="printReceipt(sale.invoice_no)" class="w-9 h-9 bg-white border border-slate-200 hover:border-rose-500 text-slate-600 hover:text-rose-500 rounded-xl transition-all flex items-center justify-center shadow-2xs hover:shadow-sm" title="Cetak Struk">
                                        <i class="fa-solid fa-print text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>