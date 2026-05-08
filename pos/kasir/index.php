<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
$page_title = "Mesin Kasir - Love Cakes POS";

require_once '../../config/database.php';
try {
    $stmt_toko = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1");
    $toko = $stmt_toko->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $toko = false; }
if(!$toko) { $toko = ['store_name' => 'LOVE CAKES', 'store_address' => 'Alamat belum diatur', 'store_phone' => '-', 'receipt_footer' => 'Terima Kasih Atas Kunjungan Anda!']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/head.php'; ?>
    <style>
        @media print {
            body * { visibility: hidden; }
            #print-receipt, #print-receipt * { visibility: visible; }
            #print-receipt { position: absolute; left: 0; top: 0; width: 58mm; max-width: 58mm; margin: 0; padding: 0 4mm; font-family: 'Courier New', monospace; font-size: 11px; color: #000; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="posApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden no-print">
        
        <?php include '../../components/header.php'; ?>

        <main class="flex-1 overflow-hidden flex flex-col lg:flex-row p-3 gap-3">
            
            <div class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
                <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-sm">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                </div>

                <div class="p-4 border-b border-slate-100 flex flex-wrap gap-3 items-center bg-slate-50/50">
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari produk..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm">
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="loadLocalData(true)" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-sm" title="Sinkronisasi Data Master">
                            <i class="fa-solid fa-rotate-right" :class="isLoading ? 'fa-spin' : ''"></i> Sync
                        </button>
                        
                        <button @click="openStatusModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-sm shadow-blue-500/30">
                            <i class="fa-solid fa-list-check"></i> Status Pesanan
                        </button>
                        <button @click="addCustomItem()" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-sm shadow-amber-500/30">
                            <i class="fa-solid fa-pen-to-square"></i> Item Custom
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        <template x-for="item in filteredProducts" :key="item.id">
                            <div @click="addToCart(item)" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:border-primary/50 hover:shadow-md transition-all cursor-pointer group flex flex-col h-full active:scale-95">
                                <div class="relative pt-[80%] bg-slate-100 overflow-hidden">
                                    <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-300 bg-slate-100 z-0"><i class="fa-solid fa-cake-candles text-4xl mb-2"></i></div>
                                    <img :src="item.image && item.image !== 'no-image.png' ? 'http://localhost/sim-produksi-kue/assets/img/' + item.image : ''" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 z-10" @error="$el.style.display='none'">
                                </div>
                                <div class="p-3 flex flex-col flex-1 bg-white z-10">
                                    <h3 class="font-bold text-xs sm:text-sm text-slate-800 leading-tight mb-2 line-clamp-2" x-text="item.name"></h3>
                                    <div class="mt-auto font-black text-primary text-sm sm:text-base" x-text="'Rp ' + formatRupiah(item.price || item.offline_price || 0)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-[400px] flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden shrink-0">
                <div class="p-4 border-b border-slate-100 bg-slate-50">
                    <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Pelanggan</label>
                    <select x-model="selectedCustomerId" @change="onCustomerSelect()" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-primary font-bold text-sm cursor-pointer">
                        <option value="">-- Pelanggan Umum --</option>
                        <template x-for="cust in customers" :key="cust.id">
                            <option :value="cust.id" x-text="cust.name + ' (Poin: ' + cust.points + ')'"></option>
                        </template>
                    </select>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                    <div x-show="cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 space-y-3">
                        <i class="fa-solid fa-basket-shopping text-5xl opacity-50"></i>
                        <p class="font-bold text-sm">Keranjang masih kosong</p>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 p-2 rounded-xl" :class="item.is_custom ? 'border-amber-200 bg-amber-50/30' : ''">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-sm text-slate-800 truncate" x-text="(item.is_custom ? '🛠️ ' : '') + item.name"></h4>
                                    <div class="text-xs font-black text-primary" x-text="'Rp ' + formatRupiah(item.price)"></div>
                                </div>
                                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg p-1">
                                    <button @click="updateQty(index, -1)" class="w-6 h-6 flex items-center justify-center rounded bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                    <span class="w-6 text-center font-black text-sm" x-text="item.qty"></span>
                                    <button @click="updateQty(index, 1)" class="w-6 h-6 flex items-center justify-center rounded bg-primary text-white hover:bg-blue-600 font-bold"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                </div>
                                <button @click="removeItem(index)" class="w-8 h-8 flex items-center justify-center text-rose-400 hover:text-rose-600 bg-rose-50 rounded-lg"><i class="fa-solid fa-trash-can text-xs"></i></button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-3">
                    <div class="flex gap-2">
                        <input type="text" x-model="voucherCode" placeholder="Kode Promo..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-primary font-bold text-sm uppercase">
                        <button @click="applyVoucher()" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-black transition-all">Promo</button>
                        <button @click="applyManualDiscount()" class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 rounded-xl text-xs font-black transition-all shadow-sm" title="Diskon SPV"><i class="fa-solid fa-percent"></i></button>
                    </div>

                    <div x-show="selectedCustomer && loyaltyRules.is_active" class="flex items-center justify-between bg-amber-50 border border-amber-200 p-2.5 rounded-xl">
                        <div>
                            <p class="text-[10px] font-black text-amber-600 uppercase tracking-tight">Poin: <span x-text="selectedCustomer?.points || 0"></span></p>
                        </div>
                        <button @click="togglePoints()" :disabled="(selectedCustomer?.points < loyaltyRules.points_required) && !usePoints" 
                                :class="usePoints ? 'bg-amber-500 text-white' : 'bg-white text-amber-500 border border-amber-200 disabled:opacity-50'" 
                                class="px-3 py-1.5 rounded-lg text-[10px] font-black transition-all">
                            <span x-text="usePoints ? 'DIPAKAI' : 'PAKAI POIN'"></span>
                        </button>
                    </div>

                    <div class="space-y-1.5 pt-2 border-t border-slate-200 border-dashed">
                        <div class="flex justify-between text-xs font-bold text-slate-500"><span>Subtotal</span> <span x-text="'Rp ' + formatRupiah(subtotal)"></span></div>
                        <div x-show="discountVoucher > 0" class="flex justify-between text-xs font-bold text-emerald-500"><span>Diskon Voucher</span> <span x-text="'- Rp ' + formatRupiah(discountVoucher)"></span></div>
                        <div x-show="discountPoints > 0" class="flex justify-between text-xs font-bold text-amber-500"><span>Diskon Poin</span> <span x-text="'- Rp ' + formatRupiah(discountPoints)"></span></div>
                        <div x-show="discountManual > 0" class="flex justify-between text-xs font-bold text-rose-500">
                            <span>Diskon Manual <i class="fa-solid fa-circle-xmark cursor-pointer text-rose-300 hover:text-rose-600 ml-1" @click="discountManual = 0"></i></span> 
                            <span x-text="'- Rp ' + formatRupiah(discountManual)"></span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-end pt-2">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase">Total Bayar</p>
                            <div class="text-2xl font-black text-primary leading-none" x-text="'Rp ' + formatRupiah(totalAmount)"></div>
                        </div>
                        <div x-show="pointsEarned > 0" class="text-[10px] font-bold text-amber-500 bg-amber-50 px-2 py-1 rounded-md border border-amber-100">+<span x-text="pointsEarned"></span> Poin</div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-3">
                        <button @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white text-slate-500 border-slate-200'" class="py-2.5 rounded-xl font-black text-sm border shadow-sm transition-all flex items-center justify-center gap-2"><i class="fa-solid fa-money-bill-wave"></i> Cash</button>
                        <button @click="paymentMethod = 'qris'" :class="paymentMethod === 'qris' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-slate-500 border-slate-200'" class="py-2.5 rounded-xl font-black text-sm border shadow-sm transition-all flex items-center justify-center gap-2"><i class="fa-solid fa-qrcode"></i> QRIS</button>
                    </div>

                    <button @click="processCheckout()" :disabled="cart.length === 0" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-4 rounded-xl shadow-lg transition-all flex justify-center items-center gap-2 text-lg disabled:opacity-50 mt-2">
                        BAYAR SEKARANG <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </main>

        <div x-show="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 flex flex-col p-6 m-4 transform transition-all text-center">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-4">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h3 class="font-black text-2xl text-slate-800 mb-1">Transaksi Berhasil!</h3>
                <p class="text-sm font-bold text-slate-500 mb-6" x-text="'No. Invoice: ' + lastInvoice"></p>

                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-3 text-sm text-left">
                    <div class="flex justify-between font-bold text-slate-600"><span>Status</span> <span class="uppercase text-emerald-600" x-text="paymentStatusSaved"></span></div>
                    <div class="flex justify-between font-bold text-slate-600"><span>Total Tagihan</span> <span x-text="'Rp ' + formatRupiah(totalAmountSaved)"></span></div>
                    <div class="flex justify-between font-bold text-slate-600" x-show="paymentStatusSaved === 'dp'"><span>Telah Dibayar (DP)</span> <span class="text-amber-600" x-text="'Rp ' + formatRupiah(dpAmountSaved)"></span></div>
                    <div class="flex justify-between font-bold text-rose-600 border-t border-slate-200 border-dashed pt-3" x-show="paymentStatusSaved === 'dp'"><span>Sisa Tagihan (Utang)</span> <span x-text="'Rp ' + formatRupiah(totalAmountSaved - dpAmountSaved)"></span></div>
                    
                    <div class="flex justify-between font-bold text-slate-600" x-show="paymentStatusSaved === 'lunas' && paymentMethodSaved === 'cash'"><span>Uang Diterima</span> <span x-text="'Rp ' + formatRupiah(amountPaidSaved)"></span></div>
                    <div class="flex justify-between font-black text-emerald-600 text-base border-t border-slate-200 border-dashed pt-3" x-show="paymentStatusSaved === 'lunas' && paymentMethodSaved === 'cash'"><span>Kembalian</span> <span x-text="'Rp ' + formatRupiah(changeAmountSaved)"></span></div>
                </div>

                <div class="grid grid-cols-2 gap-3 mt-6">
                    <button @click="resetCart()" class="py-3 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Order Baru</button>
                    <button @click="printReceipt()" class="py-3 rounded-xl font-black text-white bg-primary hover:bg-blue-700 shadow-md transition-all flex justify-center items-center gap-2"><i class="fa-solid fa-print"></i> Cetak Struk</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'modal_status.php'; ?>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>