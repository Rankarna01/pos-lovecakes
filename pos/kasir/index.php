<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Mesin Kasir - Love Cakes POS";

require_once '../../config/database.php';
try {
    $stmt_toko = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1");
    $toko = $stmt_toko->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $toko = false; }
if(!$toko) { $toko = ['store_name' => 'LOVE CAKES', 'store_address' => '-', 'store_phone' => '-', 'receipt_footer' => 'Terima Kasih!']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/localforage@1.10.0/dist/localforage.min.js"></script>
    <!-- WAJIB: Plugin Collapse Alpine JS (Taruh di atas Alpine utama) -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <!-- WAJIB: Alpine JS agar form dan dropdown hidup -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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

    <div class="flex-1 flex flex-col h-screen overflow-hidden no-print relative">
        
        <!-- HEADER DENGAN TOMBOL KAS KELUAR -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-cash-register mr-2"></i>Mesin Kasir</h2>
            </div>
            <div class="flex items-center gap-3">
                <div x-show="!needsShiftOpen" class="hidden sm:flex bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-3 py-1.5 rounded-lg text-xs font-black items-center gap-2">
                    <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div> Kasir Aktif
                </div>
                
                <!-- TOMBOL KAS KELUAR -->
                <button @click="openKasKeluarModal()" x-show="!needsShiftOpen" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-money-bill-transfer"></i> Kas Keluar
                </button>
                
                <button @click="openCloseShiftModal()" x-show="!needsShiftOpen" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-lock"></i> Tutup Kasir
                </button>
            </div>
        </header>

        <!-- MODAL AWAL BUKA SHIFT -->
        <div x-show="needsShiftOpen" class="absolute inset-0 z-[100] bg-slate-900/90 backdrop-blur-md flex items-center justify-center">
            <div class="bg-white p-8 rounded-[2rem] shadow-2xl max-w-md w-full border border-slate-200 text-center relative overflow-hidden">
                <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-4"><i class="fa-solid fa-cash-register"></i></div>
                <h2 class="text-2xl font-black text-slate-800 mb-2">Mulai Shift Kasir</h2>
                <p class="text-sm font-bold text-slate-500 mb-6">Masukkan uang modal awal di laci kasir (Cash) untuk mulai transaksi.</p>
                <form @submit.prevent="openShift()" class="space-y-4 text-left">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Pilih Shift Kerja</label>
                        <select x-model="shiftForm.shift_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500/20 font-bold text-slate-700">
                            <option value="">-- Pilih Shift --</option>
                            <template x-for="s in masterShifts" :key="s.id">
                                <option :value="s.id" x-text="s.shift_name + ' (' + s.start_time + ' - ' + s.end_time + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Modal Awal Cash (Di Laci)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400">Rp</span>
                            <input type="number" x-model="shiftForm.start_cash" required class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-4 py-3 outline-none focus:ring-2 focus:ring-blue-500/20 font-black text-slate-800 text-lg">
                        </div>
                    </div>
                    <button type="submit" :disabled="isLoadingShift" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-lg transition-colors duration-200 flex items-center justify-center gap-2 mt-4 disabled:opacity-50">
                        <i class="fa-solid fa-lock-open" :class="isLoadingShift ? 'fa-spin' : ''"></i> BUKA KASIR SEKARANG
                    </button>
                </form>
            </div>
        </div>

        <main class="flex-1 overflow-hidden flex flex-col lg:flex-row p-3 gap-3">
            
            <!-- PANEL KATALOG -->
            <div class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
                <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-sm"><i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i></div>

                <div class="p-4 border-b border-slate-100 flex flex-wrap gap-3 items-center bg-slate-50">
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fa-solid fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-primary"></i>
                        <input type="text" x-model="barcodeInput" @keyup.enter="scanBarcode()" x-ref="barcodeScanner" placeholder="Scan Barcode SKU di sini..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-primary/30 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-black text-sm uppercase tracking-widest shadow-inner placeholder:normal-case placeholder:font-medium placeholder:tracking-normal">
                    </div>
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama produk manual..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm">
                    </div>
                    <button @click="loadLocalData(true)" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2"><i class="fa-solid fa-rotate-right"></i> Sync</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        <template x-for="item in filteredProducts" :key="item.id">
                            <div @click="addToCart(item)" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:border-primary/50 hover:shadow-md transition-all cursor-pointer group flex flex-col h-full active:scale-95">
                                <div class="relative pt-[80%] bg-slate-100 overflow-hidden border-b border-slate-100">
                                    <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded text-[9px] font-black shadow-sm text-slate-600" x-text="item.code || '-'"></div>
                                    <img :src="item.image && item.image !== 'no-image.png' ? 'http://localhost/sim-produksi-kue/assets/img/' + item.image : ''" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" @error="$el.style.display='none'">
                                </div>
                                <div class="p-3 flex flex-col flex-1 bg-white">
                                    <h3 class="font-bold text-xs sm:text-sm text-slate-800 leading-tight mb-2 line-clamp-2" x-text="item.name"></h3>
                                    <div class="mt-auto font-black text-primary text-sm sm:text-base" x-text="'Rp ' + formatRupiah(item.price || item.offline_price || 0)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- PANEL KERANJANG -->
            <div class="w-full lg:w-[420px] flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden shrink-0">
                <div class="flex p-2 bg-slate-100 border-b border-slate-200 gap-1">
                    <button @click="activeTab = 'reguler'" :class="activeTab === 'reguler' ? 'bg-white shadow-sm text-primary font-black' : 'text-slate-500 hover:bg-slate-200 font-bold'" class="flex-1 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-all"><i class="fa-solid fa-cash-register mr-1"></i> Reguler</button>
                    <button @click="activeTab = 'po'" :class="activeTab === 'po' ? 'bg-white shadow-sm text-orange-500 font-black' : 'text-slate-500 hover:bg-slate-200 font-bold'" class="flex-1 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-all"><i class="fa-solid fa-fire-burner mr-1"></i> Pesanan Dapur</button>
                </div>

                <div class="p-3 border-b border-slate-100 bg-slate-50 space-y-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 mb-1 uppercase">Pelanggan</label>
                        <select x-model="selectedCustomerId" @change="onCustomerSelect()" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-primary font-bold text-sm cursor-pointer">
                            <option value="">-- Pelanggan Umum --</option>
                            <template x-for="cust in customers" :key="cust.id">
                                <option :value="cust.id" x-text="cust.name + ' (Poin: ' + cust.points + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="activeTab === 'po'" class="space-y-3">
                        <div class="flex gap-2">
                            <button @click="openStatusModal()" class="flex-1 bg-orange-100 hover:bg-orange-200 text-orange-600 px-3 py-2.5 rounded-xl text-xs font-black transition-all border border-orange-200 shadow-sm flex items-center justify-center gap-2"><i class="fa-solid fa-list-check"></i> Status PO Dapur</button>
                            <button @click="addCustomItem()" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white px-3 py-2.5 rounded-xl text-xs font-black transition-all shadow-sm flex items-center justify-center gap-2"><i class="fa-solid fa-pen-to-square"></i> Item Custom</button>
                        </div>
                        
                        <div class="bg-orange-50 border border-orange-200 p-3 rounded-xl space-y-3">
                            <div>
                                <label class="block text-[10px] font-black text-orange-700 mb-1 uppercase">Channel Penjualan</label>
                                <select x-model="poForm.channel" class="w-full bg-white border border-orange-200 rounded-lg px-3 py-2 outline-none font-bold text-xs text-slate-700">
                                    <option value="toko">Toko / Takeaway</option>
                                    <option value="online">Pesanan dari Online</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-black text-orange-700 mb-1 uppercase">Tgl Diambil</label>
                                    <input type="date" x-model="poForm.pickup_date" class="w-full bg-white border border-orange-200 rounded-lg px-2 py-2 outline-none font-bold text-xs text-slate-700">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-orange-700 mb-1 uppercase">Jam Diambil</label>
                                    <input type="time" x-model="poForm.pickup_time" class="w-full bg-white border border-orange-200 rounded-lg px-2 py-2 outline-none font-bold text-xs text-slate-700">
                                </div>
                            </div>
                            <div x-show="['delivery', 'grab', 'gojek', 'online'].includes(poForm.channel)">
                                <label class="block text-[10px] font-black text-orange-700 mb-1 uppercase">Biaya Ongkir / Markup (Rp)</label>
                                <input type="number" x-model.number="poForm.ongkir" class="w-full bg-white border border-orange-200 rounded-lg px-3 py-2 outline-none font-bold text-xs text-slate-700">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                    <div x-show="cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 space-y-3">
                        <i class="fa-solid fa-basket-shopping text-5xl opacity-30"></i>
                        <p class="font-bold text-sm">Keranjang masih kosong</p>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 p-2 rounded-xl" :class="item.is_custom ? 'border-orange-200 bg-orange-50/30' : ''">
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

                <div class="p-4 bg-slate-50 border-t border-slate-200 shadow-[0_-10px_30px_rgba(0,0,0,0.05)]">
                    <div class="flex gap-2 mb-3">
                        <input type="text" x-model="voucherCode" placeholder="Kode Promo..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-primary font-bold text-sm uppercase">
                        <button @click="applyVoucher()" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-black transition-all">Promo</button>
                        <button @click="applyManualDiscount()" class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 rounded-xl text-xs font-black transition-all shadow-sm" title="Diskon SPV"><i class="fa-solid fa-percent"></i></button>
                    </div>

                    <div x-show="selectedCustomer && loyaltyRules.is_active" class="flex items-center justify-between bg-amber-50 border border-amber-200 p-2 rounded-xl mb-3">
                        <div><p class="text-[10px] font-black text-amber-600 uppercase tracking-tight">Poin: <span x-text="selectedCustomer?.points || 0"></span></p></div>
                        <button @click="togglePoints()" :disabled="(selectedCustomer?.points < loyaltyRules.points_required) && !usePoints" class="px-3 py-1 rounded-lg text-[10px] font-black transition-all" :class="usePoints ? 'bg-amber-500 text-white' : 'bg-white text-amber-500 border border-amber-200 disabled:opacity-50'">
                            <span x-text="usePoints ? 'POIN DIPAKAI' : 'PAKAI POIN'"></span>
                        </button>
                    </div>

                    <div class="space-y-1.5 mb-3 border-t border-slate-200 border-dashed pt-2">
                        <div class="flex justify-between text-xs font-bold text-slate-500"><span>Subtotal Barang</span> <span x-text="'Rp ' + formatRupiah(subtotal)"></span></div>
                        <div x-show="activeTab === 'po' && poForm.ongkir > 0" class="flex justify-between text-xs font-bold text-orange-500"><span>Ongkir / Markup</span> <span x-text="'+ Rp ' + formatRupiah(poForm.ongkir)"></span></div>
                        <div x-show="discountVoucher > 0" class="flex justify-between text-xs font-bold text-emerald-500"><span>Diskon Voucher</span> <span x-text="'- Rp ' + formatRupiah(discountVoucher)"></span></div>
                        <div x-show="discountPoints > 0" class="flex justify-between text-xs font-bold text-amber-500"><span>Diskon Poin</span> <span x-text="'- Rp ' + formatRupiah(discountPoints)"></span></div>
                        <div x-show="discountManual > 0" class="flex justify-between text-xs font-bold text-rose-500"><span>Diskon Manual <i @click="discountManual = 0" class="fa-solid fa-xmark cursor-pointer ml-1"></i></span> <span x-text="'- Rp ' + formatRupiah(discountManual)"></span></div>
                    </div>
                    
                    <div class="flex justify-between items-end mb-3 border-t border-slate-200 pt-3">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase">Total Tagihan</p>
                            <div class="text-3xl font-black text-primary leading-none" x-text="'Rp ' + formatRupiah(totalAmount)"></div>
                        </div>
                        <div x-show="pointsEarned > 0" class="text-[10px] font-bold text-amber-500 bg-amber-50 px-2 py-1 rounded-md border border-amber-100">+<span x-text="pointsEarned"></span> Poin</div>
                    </div>

                    <button @click="processCheckout()" :disabled="cart.length === 0" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-4 rounded-xl shadow-lg transition-all flex justify-center items-center gap-2 text-lg disabled:opacity-50">
                        <span x-text="activeTab === 'po' ? 'SIMPAN PESANAN PO' : 'BAYAR SEKARANG'"></span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL CHECKOUT PEMBAYARAN CUSTOM -->
    <!-- ======================================================== -->
    <div x-show="showCheckoutModal" class="fixed inset-0 z-[110] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCheckoutModal = false"></div>
        <div class="bg-white w-full max-w-md rounded-[2rem] shadow-2xl relative z-10 p-6 m-4 flex flex-col overflow-hidden">
            <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                <h3 class="font-black text-xl text-slate-800"><i class="fa-solid fa-wallet text-blue-500 mr-2"></i> Proses Pembayaran</h3>
                <button @click="showCheckoutModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="paymentStatus = 'lunas'; paymentMethod = 'cash'; inputUang = totalAmount" :class="paymentStatus === 'lunas' ? 'border-blue-500 bg-blue-50 text-blue-700 ring-4 ring-blue-500/10' : 'border-slate-200 hover:bg-slate-50 text-slate-500'" class="p-4 rounded-2xl border-2 transition-all text-center">
                        <i class="fa-solid fa-check-circle text-3xl mb-2" :class="paymentStatus === 'lunas' ? 'text-blue-500' : 'text-slate-300'"></i>
                        <div class="font-black text-sm">Bayar Lunas</div>
                    </button>
                    <button type="button" @click="paymentStatus = 'dp'; paymentMethod = 'cash'; inputUang = ''" :class="paymentStatus === 'dp' ? 'border-amber-500 bg-amber-50 text-amber-700 ring-4 ring-amber-500/10' : 'border-slate-200 hover:bg-slate-50 text-slate-500'" class="p-4 rounded-2xl border-2 transition-all text-center">
                        <i class="fa-solid fa-hand-holding-dollar text-3xl mb-2" :class="paymentStatus === 'dp' ? 'text-amber-500' : 'text-slate-300'"></i>
                        <div class="font-black text-sm">DP / Kasbon</div>
                    </button>
                </div>
                <div x-show="paymentStatus === 'lunas'" class="space-y-3 mt-4" x-collapse>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Metode Pembayaran</label>
                    <div class="flex gap-2 p-1.5 bg-slate-100 rounded-xl">
                        <button type="button" @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-2.5 rounded-lg font-black text-sm transition-all"><i class="fa-solid fa-money-bill-wave text-emerald-500 mr-1"></i> Cash</button>
                        <button type="button" @click="paymentMethod = 'qris'" :class="paymentMethod === 'qris' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-2.5 rounded-lg font-black text-sm transition-all"><i class="fa-solid fa-qrcode text-blue-500 mr-1"></i> QRIS / TF</button>
                    </div>
                    <div x-show="paymentMethod === 'cash'" x-collapse>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 mt-3">Uang Diterima (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400">Rp</span>
                            <input type="number" x-model.number="inputUang" class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-3 text-left font-black text-xl outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        </div>
                        <div class="flex justify-between items-center mt-3 px-2">
                            <span class="text-xs font-bold text-slate-500">Total Tagihan:</span>
                            <span class="font-black text-lg text-rose-500" x-text="'Rp ' + formatRupiah(totalAmount)"></span>
                        </div>
                        <div class="flex justify-between items-center px-2 mt-1 pt-2 border-t border-slate-100 border-dashed" x-show="inputUang >= totalAmount">
                            <span class="text-xs font-bold text-slate-500">Kembalian:</span>
                            <span class="font-black text-xl text-emerald-500" x-text="'Rp ' + formatRupiah(inputUang - totalAmount)"></span>
                        </div>
                    </div>
                </div>
                <div x-show="paymentStatus === 'dp'" class="mt-4" x-collapse>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nominal DP (Tunai)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-amber-500">Rp</span>
                        <input type="number" x-model.number="inputUang" placeholder="Ketik jumlah DP..." class="w-full bg-amber-50/50 border border-amber-300 rounded-xl pl-11 pr-4 py-3 text-left font-black text-xl outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-amber-700">
                    </div>
                    <div class="flex justify-between items-center mt-3 px-2">
                        <span class="text-xs font-bold text-slate-500">Total Tagihan:</span>
                        <span class="font-black text-lg text-rose-500" x-text="'Rp ' + formatRupiah(totalAmount)"></span>
                    </div>
                    <div class="flex justify-between items-center px-2 mt-1 pt-2 border-t border-slate-100 border-dashed" x-show="inputUang > 0 && inputUang <= totalAmount">
                        <span class="text-xs font-bold text-rose-500">Sisa Hutang:</span>
                        <span class="font-black text-xl text-rose-600" x-text="'Rp ' + formatRupiah(totalAmount - inputUang)"></span>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex gap-3">
                <button type="button" @click="showCheckoutModal = false" class="py-3.5 px-6 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                <button type="button" @click="submitCheckout()" class="flex-1 py-3.5 rounded-xl font-black text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/30 transition-all flex justify-center items-center gap-2">
                    <i class="fa-solid fa-check-double"></i> Proses Transaksi
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL KAS KELUAR (PETTY CASH) -->
    <!-- ======================================================== -->
    <div x-show="showKasKeluarModal" class="fixed inset-0 z-[110] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showKasKeluarModal = false"></div>
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl relative z-10 p-6 m-4 flex flex-col overflow-hidden">
            <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                <h3 class="font-black text-xl text-slate-800"><i class="fa-solid fa-money-bill-transfer text-amber-500 mr-2"></i> Input Kas Keluar</h3>
                <button @click="showKasKeluarModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <p class="text-xs font-bold text-slate-500 mb-4">Catat pengeluaran operasional toko dari laci kasir (misal: beli plastik, air minum, parkir).</p>
            <form @submit.prevent="submitKasKeluar()" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nominal Pengeluaran (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400">Rp</span>
                        <input type="number" x-model="kasKeluarForm.amount" required class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-4 py-3 outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-black text-slate-800 text-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Keterangan / Keperluan</label>
                    <input type="text" x-model="kasKeluarForm.description" placeholder="Contoh: Beli es batu..." required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-bold text-slate-700 text-sm">
                </div>
                <div class="pt-2">
                    <button type="submit" :disabled="isSavingKas" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-black py-3.5 rounded-xl shadow-md shadow-amber-500/30 transition-all flex justify-center items-center gap-2 disabled:opacity-50">
                        <i class="fa-solid fa-save" :class="isSavingKas ? 'fa-spin' : ''"></i> SIMPAN PENGELUARAN
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL TUTUP SHIFT -->
    <div x-show="showCloseShiftModal" class="fixed inset-0 z-[100] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCloseShiftModal = false"></div>
        <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 p-6 m-4 text-center border border-slate-200">
            <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center text-2xl mx-auto mb-3"><i class="fa-solid fa-lock"></i></div>
            <h3 class="font-black text-xl text-slate-800 mb-2">Akhiri Shift Kasir?</h3>
            <p class="text-xs font-bold text-slate-500 mb-4">Pastikan Anda telah menghitung uang fisik di laci dengan benar sebelum menutup shift.</p>
            <form @submit.prevent="closeShift()">
                <label class="block text-[10px] text-left font-black text-slate-400 uppercase tracking-widest mb-1.5">Total Uang Cash Real di Laci (Rp)</label>
                <div class="relative mb-6">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400">Rp</span>
                    <input type="number" x-model="closeShiftCash" required class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-3 font-black text-xl outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="showCloseShiftModal = false" class="py-3.5 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" :disabled="isLoadingShift" class="py-3.5 rounded-xl font-black text-white bg-rose-500 hover:bg-rose-600 shadow-md shadow-rose-500/30 transition-all flex justify-center items-center gap-2">
                        <i class="fa-solid fa-check" :class="isLoadingShift ? 'fa-spin' : ''"></i> Tutup
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL TAMBAH ITEM CUSTOM (PESANAN KHUSUS) -->
    <!-- ======================================================== -->
    <div x-show="showCustomItemModal" class="fixed inset-0 z-[120] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCustomItemModal = false"></div>
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl relative z-10 p-6 m-4 flex flex-col overflow-hidden">
            
            <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                <h3 class="font-black text-xl text-slate-800"><i class="fa-solid fa-pen-to-square text-orange-500 mr-2"></i> Item Custom</h3>
                <button @click="showCustomItemModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <p class="text-xs font-bold text-slate-500 mb-4">Tambahkan pesanan khusus yang tidak ada di katalog untuk diteruskan ke dapur.</p>

            <form @submit.prevent="submitCustomItem()" class="space-y-4">
                
                <!-- Opsi Template Tersimpan -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Pilih Dari Template (Opsional)</label>
                    <select x-model="customItemForm.template" @change="applyCustomTemplate()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-3 outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 font-bold text-sm text-slate-700 cursor-pointer">
                        <option value="">-- Ketik Manual Baru --</option>
                        <template x-for="c in savedCustoms" :key="c.id">
                            <option :value="c.id" x-text="c.name + ' - Rp ' + formatRupiah(c.price)"></option>
                        </template>
                    </select>
                </div>

                <div class="border-t border-slate-100 pt-3">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nama Pesanan Khusus</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-cake-candles"></i></span>
                        <input type="text" x-model="customItemForm.name" placeholder="Contoh: Kue Ulang Tahun Spiderman" required class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 font-bold text-sm text-slate-800 placeholder:text-slate-400">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Harga Satuan (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-orange-500">Rp</span>
                        <input type="number" x-model="customItemForm.price" required placeholder="0" class="w-full bg-orange-50/50 border border-orange-200 rounded-xl pl-11 pr-4 py-3 outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 font-black text-orange-700 text-lg placeholder:text-orange-300">
                    </div>
                </div>

                <div class="pt-2 mt-4 flex gap-3">
                    <button type="button" @click="showCustomItemModal = false" class="py-3 px-5 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-black py-3 rounded-xl shadow-md shadow-orange-500/30 transition-all flex justify-center items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Tambahkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL SUKSES -->
    <div x-show="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 flex flex-col p-6 m-4 transform transition-all text-center">
            <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-4"><i class="fa-solid fa-check"></i></div>
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
                <button @click="printReceipt()" class="py-3 rounded-xl font-black text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all flex justify-center items-center gap-2"><i class="fa-solid fa-print"></i> Cetak Struk</button>
            </div>
        </div>
    </div>

    <?php include 'modal_status.php'; ?>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>