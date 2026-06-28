<?php
require_once '../../../../config/auth.php';
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Riwayat Transaksi - Love Cakes POS";
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
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-users-viewfinder mr-2"></i>Riwayat & Pelanggan</h2>
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
                    <select x-model="filterStatus" @change="currentPage = 1" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="all">Semua Status</option>
                        <option value="lunas_langsung">✅ Lunas Langsung</option>
                        <option value="dp_belum">⚠️ DP Belum Lunas</option>
                        <option value="dp_lunas">🎉 DP Sudah Lunas</option>
                    </select>
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

                <!-- PELANGGAN & DETAIL TRANSAKSI -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-1 bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200">
                        <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-users text-rose-400 mr-2"></i> Top Pelanggan
                        </h3>
                        <div class="overflow-y-auto max-h-[500px] custom-scrollbar pr-2 space-y-3">
                            <template x-for="cust in salesByCustomer" :key="cust.customer_name">
                                <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <div>
                                        <div class="font-bold text-sm text-slate-700" x-text="cust.customer_name"></div>
                                        <div class="text-[10px] text-slate-500 font-medium" x-text="cust.total_transactions + ' transaksi'"></div>
                                    </div>
                                    <div class="font-black text-rose-500 text-sm" x-text="'Rp ' + formatRupiah(cust.total_spent)"></div>
                                </div>
                            </template>
                            <div x-show="salesByCustomer.length === 0" class="text-center py-4 text-xs font-bold text-slate-400">Belum ada data</div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-col">
                        <h3 class="font-black text-slate-700 mb-4 uppercase text-xs tracking-widest border-b border-slate-100 pb-3">
                            <i class="fa-solid fa-list-check text-slate-400 mr-2"></i> Rincian Transaksi
                        </h3>
                        <div class="overflow-x-auto overflow-y-auto max-h-[500px] custom-scrollbar flex-1">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-widest sticky top-0">
                                    <tr>
                                        <th class="p-3 font-black rounded-tl-xl border-b">Invoice</th>
                                        <th class="p-3 font-black border-b">Pelanggan</th>
                                        <th class="p-3 font-black text-center border-b">Status</th>
                                        <th class="p-3 font-black text-center border-b">Metode</th>
                                        <th class="p-3 font-black text-right border-b rounded-tr-xl">Total Bayar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="dt in paginatedDetails" :key="dt.invoice_no">
                                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                            <td class="p-3">
                                                <div class="font-black text-slate-800 text-xs" x-text="dt.invoice_no"></div>
                                                <div class="text-[10px] font-bold text-slate-400" x-text="dt.created_at"></div>
                                            </td>
                                            <td class="p-3 font-bold text-slate-600 text-xs" x-text="dt.customer_name"></td>
                                            <td class="p-3 text-center">
                                                <span x-show="dt.payment_status === 'dp'" class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-[9px] font-black uppercase border border-amber-300">⚠️ DP Belum Lunas</span>
                                                <span x-show="dt.payment_status === 'lunas' && parseFloat(dt.dp_amount) > 0" class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-[9px] font-black uppercase border border-blue-300">🎉 DP Sudah Lunas</span>
                                                <span x-show="dt.payment_status === 'lunas' && (!dt.dp_amount || parseFloat(dt.dp_amount) == 0)" class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-[9px] font-black uppercase border border-emerald-300">✅ Lunas Langsung</span>
                                            </td>
                                            <td class="p-3 text-center">
                                                <span class="bg-slate-100 text-slate-500 px-2 py-1 rounded text-[9px] font-black uppercase" x-text="dt.payment_method"></span>
                                            </td>
                                            <td class="p-3 text-right font-black text-primary text-sm" x-text="'Rp ' + formatRupiah(dt.total_amount)"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredDetails.length === 0">
                                        <td colspan="5" class="p-8 text-center text-slate-400 font-bold">Belum ada rincian penjualan yang sesuai filter.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINATION CONTROLS -->
                        <div x-show="totalPages > 1" class="flex justify-between items-center pt-4 border-t border-slate-100 mt-4">
                            <div class="text-xs font-bold text-slate-500">
                                Halaman <span x-text="currentPage"></span> dari <span x-text="totalPages"></span> (<span x-text="filteredDetails.length"></span> data)
                            </div>
                            <div class="flex gap-1">
                                <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg font-bold text-xs disabled:opacity-50 transition-colors">Prev</button>
                                <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg font-bold text-xs disabled:opacity-50 transition-colors">Next</button>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
