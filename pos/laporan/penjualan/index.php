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
                
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row gap-3 items-center sticky top-0 z-10">
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <input type="date" x-model="filters.start_date" :disabled="isRestricted" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20 transition-all text-slate-700 disabled:opacity-50">
                        <span class="text-slate-400 font-bold text-sm">s/d</span>
                        <input type="date" x-model="filters.end_date" :disabled="isRestricted" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20 transition-all text-slate-700 disabled:opacity-50">
                    </div>
                    <div class="relative w-full md:flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari No. Invoice / Pelanggan..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm">
                    </div>
                    <button @click="fetchData()" class="w-full md:w-auto bg-primary text-white px-6 py-2.5 rounded-xl text-sm font-black transition-all">Filter</button>
                </div>

                <div x-show="isRestricted" class="bg-amber-50 border border-amber-200 p-4 rounded-2xl flex items-center gap-3 text-amber-700">
                    <i class="fa-solid fa-shield-halved text-xl"></i>
                    <p class="text-sm font-bold">Mode Terbatas: Kasir hanya dapat melihat riwayat transaksi hari ini.</p>
                </div>

                <div x-show="isLoading" class="text-center py-20 flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-500 font-bold tracking-widest uppercase text-sm">Memuat Data Penjualan...</p>
                </div>

                <div x-show="!isLoading" class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap min-w-[1000px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Invoice & Waktu</th>
                                    <th class="p-4 font-black">Pelanggan</th>
                                    <th class="p-4 font-black text-center">Status</th>
                                    <th class="p-4 font-black text-right">Total Bayar</th>
                                    <th class="p-4 font-black text-center">Metode</th>
                                    <th class="p-4 font-black text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <template x-for="sale in filteredSales" :key="sale.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4">
                                            <div class="font-black text-slate-800" x-text="sale.invoice_no"></div>
                                            <div class="text-[10px] font-bold text-slate-400 mt-1" x-text="formatDate(sale.created_at)"></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-black text-slate-700" x-text="sale.customer_name || 'Pelanggan Umum'"></div>
                                            <div class="text-[10px] font-bold text-slate-400" x-text="sale.channel || 'toko'"></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span :class="sale.payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" x-text="sale.payment_status"></span>
                                        </td>
                                        <td class="p-4 text-right font-black text-primary" x-text="'Rp ' + formatRupiah(sale.total_amount)"></td>
                                        <td class="p-4 text-center"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-black uppercase" x-text="sale.payment_method"></span></td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openDetail(sale)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-primary hover:text-white transition-all"><i class="fa-solid fa-eye text-xs"></i></button>
                                                <button @click="printReceipt(sale.invoice_no)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all"><i class="fa-solid fa-print text-xs"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
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
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>