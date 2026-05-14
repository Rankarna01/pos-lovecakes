<?php
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
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="salesHistoryApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-receipt mr-2"></i>Riwayat Penjualan</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="max-w-7xl mx-auto space-y-4">
                
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-col md:flex-row gap-3">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="filters.search" @input.debounce.500ms="applyFilter()" placeholder="Cari No. Invoice / Pelanggan..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:bg-white focus:border-primary font-bold text-sm">
                    </div>
                    <select x-model="filters.channel" @change="applyFilter()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-600">
                        <option value="">Semua Channel</option>
                        <option value="toko">Toko (Offline)</option>
                        <option value="grab">GrabFood</option>
                        <option value="gojek">GoFood</option>
                        <option value="wa_delivery">WA / Delivery</option>
                    </select>
                    <select x-model="filters.payment" @change="applyFilter()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-600">
                        <option value="">Semua Pembayaran</option>
                        <option value="cash">Cash</option>
                        <option value="qris">QRIS</option>
                        <option value="app">Saldo App</option>
                    </select>
                    <button @click="applyFilter()" class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden relative">
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-sm">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Invoice & Waktu</th>
                                    <th class="p-4 font-black">Pelanggan</th>
                                    <th class="p-4 font-black">Tipe Order</th>
                                    <th class="p-4 font-black text-right">Total Bayar</th>
                                    <th class="p-4 font-black text-center">Metode</th>
                                    <th class="p-4 font-black text-center"><i class="fa-solid fa-bars"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="sales.length === 0">
                                    <td colspan="6" class="p-10 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-folder-open text-4xl mb-3 opacity-50 block"></i> Tidak ada riwayat transaksi ditemukan.
                                    </td>
                                </tr>
                                <template x-for="sale in sales" :key="sale.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4">
                                            <div class="font-black text-slate-800" x-text="sale.invoice_no"></div>
                                            <div class="text-[10px] text-slate-500 font-bold" x-text="formatDate(sale.created_at)"></div>
                                        </td>
                                        <td class="p-4 font-bold text-slate-600">
                                            <span x-text="sale.customer_name"></span>
                                            <div x-show="sale.payment_status === 'dp'" class="text-[9px] text-amber-600 uppercase mt-0.5 bg-amber-50 inline-block px-1 rounded">Piutang (DP)</div>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-black uppercase" x-text="sale.channel"></span>
                                        </td>
                                        <td class="p-4 text-right font-black text-primary" x-text="'Rp ' + formatRupiah(sale.total_amount)"></td>
                                        <td class="p-4 text-center">
                                            <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase border" 
                                                  :class="sale.payment_method === 'cash' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-blue-50 text-blue-600 border-blue-200'" 
                                                  x-text="sale.payment_method"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <button @click="openDetail(sale)" class="bg-slate-100 hover:bg-slate-200 text-slate-600 w-8 h-8 rounded-lg flex items-center justify-center transition-colors" title="Lihat Detail">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
                        <span class="text-xs text-slate-500 font-bold" x-text="'Halaman ' + currentPage + ' dari ' + totalPages"></span>
                        <div class="flex gap-2">
                            <button @click="prevPage()" :disabled="currentPage <= 1" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-100 disabled:opacity-50 transition-colors shadow-sm">
                                <i class="fa-solid fa-chevron-left mr-1"></i> Sebelumnya
                            </button>
                            <button @click="nextPage()" :disabled="currentPage >= totalPages" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-100 disabled:opacity-50 transition-colors shadow-sm">
                                Selanjutnya <i class="fa-solid fa-chevron-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] m-4 transform transition-all overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800">Detail Invoice</h3>
                    <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-white" x-show="activeSale">
                    <div class="text-center border-b border-slate-100 pb-4 mb-4">
                        <h2 class="text-xl font-black text-slate-800" x-text="activeSale?.invoice_no"></h2>
                        <p class="text-xs font-bold text-slate-500" x-text="formatDate(activeSale?.created_at)"></p>
                    </div>

                    <div class="space-y-2 mb-4 text-sm font-bold text-slate-600">
                        <div class="flex justify-between"><span>Pelanggan</span><span x-text="activeSale?.customer_name"></span></div>
                        <div class="flex justify-between"><span>Channel</span><span class="uppercase" x-text="activeSale?.channel"></span></div>
                        <div class="flex justify-between"><span>Pembayaran</span><span class="uppercase" x-text="activeSale?.payment_method + ' (' + activeSale?.payment_status + ')'"></span></div>
                    </div>

                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 mb-4">
                        <div class="text-[10px] font-black text-slate-400 uppercase mb-2 border-b border-slate-200 pb-1">Daftar Item</div>
                        <div x-show="isDetailLoading" class="text-center text-xs py-4 text-slate-400 font-bold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat item...</div>
                        <template x-for="item in activeDetails" :key="item.id">
                            <div class="flex justify-between text-sm font-bold text-slate-700 mb-1">
                                <div><span x-text="item.qty + 'x '"></span> <span x-text="item.product_name"></span></div>
                                <div x-text="'Rp ' + formatRupiah(item.subtotal)"></div>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-1.5 text-sm font-bold text-slate-600">
                        <div class="flex justify-between"><span>Subtotal</span> <span x-text="'Rp ' + formatRupiah(activeSale?.subtotal)"></span></div>
                        <div class="flex justify-between text-rose-500" x-show="parseFloat(activeSale?.discount_voucher) > 0"><span>Diskon Voucher</span> <span x-text="'- Rp ' + formatRupiah(activeSale?.discount_voucher)"></span></div>
                        <div class="flex justify-between text-amber-500" x-show="parseFloat(activeSale?.discount_points) > 0"><span>Diskon Poin</span> <span x-text="'- Rp ' + formatRupiah(activeSale?.discount_points)"></span></div>
                        <div class="flex justify-between text-rose-500" x-show="parseFloat(activeSale?.discount_manual) > 0"><span>Diskon Manual</span> <span x-text="'- Rp ' + formatRupiah(activeSale?.discount_manual)"></span></div>
                        <div class="flex justify-between text-blue-500" x-show="parseFloat(activeSale?.shipping_cost) > 0"><span>Ongkos Kirim</span> <span x-text="'+ Rp ' + formatRupiah(activeSale?.shipping_cost)"></span></div>
                        <div class="flex justify-between font-black text-lg text-primary border-t border-slate-200 pt-2 mt-2">
                            <span>TOTAL</span> <span x-text="'Rp ' + formatRupiah(activeSale?.total_amount)"></span>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button @click="printReceipt(activeSale?.invoice_no)" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black px-6 py-3 rounded-xl transition-all shadow-md flex justify-center items-center gap-2">
                        <i class="fa-solid fa-print"></i> Cetak Ulang Struk
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>