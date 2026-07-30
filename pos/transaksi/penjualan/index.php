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
            <div class="flex items-center gap-3">
                <?php if (!empty($_SESSION['pos_store_name'])): ?>
                <div class="bg-black/20 text-amber-300 border border-white/20 px-3 py-1.5 rounded-lg text-xs font-black flex items-center gap-2 shadow-inner">
                    <i class="fa-solid fa-store text-amber-400"></i> Outlet: <?= htmlspecialchars($_SESSION['pos_store_name']) ?>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="w-full max-w-full space-y-4">
                
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="filters.search" @input.debounce.500ms="applyFilter()" placeholder="Cari No. Invoice / Pelanggan..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:bg-white focus:border-primary font-bold text-sm">
                    </div>
                    
                    <select x-model="filters.time_range" @change="applyFilter()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-600">
                        <option value="">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="week">Minggu Ini</option>
                        <option value="month">Bulan Ini</option>
                    </select>

                    <select x-model="filters.status" @change="applyFilter()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-600">
                        <option value="">Semua Jenis & Status</option>
                        <option value="lunas_langsung">✅ Lunas Langsung</option>
                        <option value="dp_semua">⏳ Semua Riwayat DP</option>
                        <option value="dp_belum">⚠️ DP Belum Lunas</option>
                        <option value="dp_lunas">🎉 DP Sudah Lunas</option>
                    </select>

                    <select x-model="filters.channel" @change="applyFilter()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-600">
                        <option value="">Semua Channel</option>
                        <option value="toko">Toko (Offline)</option>
                        <option value="grab">GrabFood</option>
                        <option value="gojek">GoFood</option>
                        <option value="wa_delivery">WA / Delivery</option>
                    </select>

                    <select x-model="filters.payment" @change="applyFilter()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-600">
                        <option value="">Semua Metode Bayar</option>
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
                                            <div x-show="sale.payment_status === 'dp'" class="text-[9px] font-black text-amber-700 uppercase mt-1 bg-amber-100 inline-block px-2 py-0.5 rounded border border-amber-300 block w-max">⚠️ DP Belum Lunas</div>
                                            <div x-show="sale.payment_status === 'lunas' && parseFloat(sale.dp_amount) > 0" class="text-[9px] font-black text-blue-700 uppercase mt-1 bg-blue-100 inline-block px-2 py-0.5 rounded border border-blue-300 block w-max">🎉 DP Sudah Lunas</div>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-black uppercase border border-slate-200" x-text="sale.channel"></span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="font-black text-primary" :class="sale.cancellation_status !== 'none' ? 'line-through text-slate-400' : ''" x-text="'Rp ' + formatRupiah(sale.total_amount)"></div>
                                            <div x-show="sale.cancellation_status === 'full'" class="text-[9px] font-black text-rose-600 uppercase mt-1 bg-rose-100 inline-block px-2 py-0.5 rounded border border-rose-300">BATAL PENUH</div>
                                            <div x-show="sale.cancellation_status === 'partial'" class="text-[9px] font-black text-amber-600 uppercase mt-1 bg-amber-100 inline-block px-2 py-0.5 rounded border border-amber-300">BATAL SEBAGIAN</div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase border" 
                                                  :class="sale.payment_method === 'cash' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-blue-50 text-blue-600 border-blue-200'" 
                                                  x-text="sale.payment_method"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openDetail(sale)" class="bg-slate-100 hover:bg-slate-200 text-slate-600 w-8 h-8 rounded-lg flex items-center justify-center transition-colors" title="Lihat Detail">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </button>
                                                
                                                <a x-show="sale.payment_status === 'dp'" href="<?= BASE_URL ?>pos/transaksi/piutang/" class="bg-amber-100 hover:bg-amber-500 hover:text-white text-amber-600 border border-amber-200 w-8 h-8 rounded-lg flex items-center justify-center transition-all" title="Lunasi Piutang/DP">
                                                    <i class="fa-solid fa-money-bill-transfer text-xs"></i>
                                                </a>
                                            </div>
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
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] m-4 transform transition-all overflow-hidden border border-slate-100">
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
                        <div class="flex justify-between"><span>Pembayaran</span><span class="uppercase font-black" :class="activeSale?.payment_status === 'dp' ? 'text-amber-600' : 'text-emerald-600'" x-text="activeSale?.payment_method + ' (' + (activeSale?.payment_status === 'lunas' && parseFloat(activeSale?.dp_amount) > 0 ? 'PELUNASAN DP' : activeSale?.payment_status) + ')'"></span></div>
                        <div class="flex justify-between text-blue-600 bg-blue-50 px-3 py-1.5 rounded-xl border border-blue-200 mt-1" x-show="parseFloat(activeSale?.dp_amount) > 0">
                            <span>Setor DP Awal</span>
                            <span class="font-black" x-text="'Rp ' + formatRupiah(activeSale?.dp_amount)"></span>
                        </div>
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

                    <!-- KHUSUS RIWAYAT PEMBAYARAN DP & PELUNASAN -->
                    <template x-if="activeSale && (activeSale.payment_status === 'dp' || parseFloat(activeSale.dp_amount || 0) > 0 || parseFloat(activeSaleInfo?.dp_amount || 0) > 0 || (activePayments && activePayments.some(p => p.payment_type === 'dp' || p.payment_type === 'pelunasan')))">
                        <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 mt-5 space-y-3">
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

                            <!-- Fallback jika data riwayat pembayaran di sale_payments_pos kosong -->
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

                <div class="p-5 border-t border-slate-100 bg-slate-50 flex justify-between gap-3 flex-wrap sm:flex-nowrap">
                    <button @click="openVoidModal()" class="w-full sm:w-auto bg-rose-50 hover:bg-rose-100 text-rose-600 font-black px-6 py-3 rounded-xl transition-all border border-rose-200 flex justify-center items-center gap-2" x-show="activeSale && activeSale.cancellation_status !== 'full'">
                        <i class="fa-solid fa-ban"></i> Batalkan Transaksi
                    </button>
                    <button @click="printReceipt(activeSale?.invoice_no)" class="w-full sm:flex-1 bg-slate-800 hover:bg-slate-900 text-white font-black px-6 py-3 rounded-xl transition-all shadow-md flex justify-center items-center gap-2">
                        <i class="fa-solid fa-print"></i> Cetak Ulang Struk
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL PEMBATALAN TRANSAKSI (VOID) -->
        <div x-show="showVoidModal" class="fixed inset-0 z-[60] flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeVoidModal()"></div>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] m-4 transform transition-all overflow-hidden border border-slate-100">
                <div class="p-5 border-b border-rose-100 flex justify-between items-center bg-rose-50">
                    <h3 class="font-black text-lg text-rose-800"><i class="fa-solid fa-ban mr-2"></i>Batalkan Transaksi</h3>
                    <button @click="closeVoidModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-rose-200 hover:bg-rose-600 hover:text-white text-rose-700 transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-white space-y-4">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <div class="text-xs font-black text-slate-500 uppercase mb-3 flex justify-between items-center">
                            <span>Pilih Item yang Dibatalkan</span>
                            <button @click="toggleSelectAllVoid()" class="text-primary hover:underline lowercase font-bold">Pilih Semua</button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(item, index) in voidItems" :key="index">
                                <label class="flex items-start gap-3 p-3 bg-white border rounded-lg cursor-pointer transition-colors" :class="item.selected ? 'border-primary bg-blue-50/50' : 'border-slate-200'">
                                    <div class="mt-1">
                                        <input type="checkbox" x-model="item.selected" @change="calculateVoidAmount()" class="w-4 h-4 text-primary bg-slate-100 border-slate-300 rounded focus:ring-primary focus:ring-2">
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-sm text-slate-800" x-text="item.product_name"></div>
                                        <div class="flex justify-between items-center mt-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-500 font-bold">Qty Batal:</span>
                                                <input type="number" x-model="item.void_qty" @input="calculateVoidAmount()" min="1" :max="item.max_qty" :disabled="!item.selected" class="w-16 px-2 py-1 text-xs border border-slate-300 rounded outline-none focus:border-primary">
                                                <span class="text-xs text-slate-500" x-text="'/ ' + item.max_qty"></span>
                                            </div>
                                            <div class="text-xs font-black text-slate-700" x-text="'Rp ' + formatRupiah(item.price * item.void_qty)"></div>
                                        </div>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-between items-center p-4 bg-rose-50 rounded-xl border border-rose-100">
                        <div class="text-sm font-bold text-rose-800">Total Nominal Pembatalan:</div>
                        <div class="text-lg font-black text-rose-600" x-text="'Rp ' + formatRupiah(voidTotalAmount)"></div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Alasan Pembatalan <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="voidReason" placeholder="Misal: Salah input pesanan" class="w-full px-4 py-2 border border-slate-300 rounded-xl outline-none focus:border-primary text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">PIN Admin / Supervisor <span class="text-rose-500">*</span></label>
                            <input type="password" x-model="voidPin" placeholder="Masukkan 6 digit PIN" maxlength="6" class="w-full px-4 py-2 border border-slate-300 rounded-xl outline-none focus:border-primary text-sm font-black tracking-[0.3em] text-center">
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-slate-100 bg-slate-50 flex gap-3">
                    <button @click="submitVoid()" :disabled="isSubmittingVoid || voidTotalAmount <= 0 || !voidReason || !voidPin" class="w-full bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black px-6 py-3 rounded-xl transition-all shadow-md flex justify-center items-center gap-2">
                        <i class="fa-solid fa-check" x-show="!isSubmittingVoid"></i>
                        <i class="fa-solid fa-circle-notch fa-spin" x-show="isSubmittingVoid"></i>
                        Konfirmasi Pembatalan
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>