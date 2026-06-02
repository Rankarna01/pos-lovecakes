<?php
require_once '../../config/auth.php';

// DETEKSI OTOMATIS LOKAL VS HOSTINGER
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// URL UNTUK SISTEM POS
$folder_pos = $is_localhost ? '/pos-lovecakes/' : '/'; 
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder_pos); }
$IMG_BASE_URL = $is_localhost 
    ? "http://localhost/sim-produksi-kue/assets/img/" 
    : "https://kokowms.my.id/sim-produksi-kue/assets/img/";

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

    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { corePlugins: { preflight: true } }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
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
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-2.5 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-cash-register mr-2"></i>Mesin Kasir</h2>
            </div>
            <div class="flex items-center gap-3">
                <div x-show="!needsShiftOpen" class="hidden sm:flex bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-3 py-1.5 rounded-lg text-xs font-black items-center gap-2">
                    <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div> Kasir Aktif
                </div>

                <!-- INDIKATOR OFFLINE -->
                <div x-show="!isOnline" class="bg-rose-500/20 text-rose-100 border border-rose-500/30 px-3 py-1.5 rounded-lg text-xs font-black flex items-center gap-2" x-cloak>
                    <i class="fa-solid fa-wifi text-rose-400"></i> Mode Offline
                </div>

                <!-- TOMBOL SYNC PENDING -->
                <button @click="syncOfflineTransactions()" x-show="isOnline && pendingSyncCount > 0" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-2 relative" x-cloak>
                    <i class="fa-solid fa-rotate" :class="isSyncing ? 'fa-spin' : ''"></i> Sync (<span x-text="pendingSyncCount"></span>)
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                    </span>
                </button>
                
                <button @click="openKasKeluarModal()" x-show="!needsShiftOpen" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-money-bill-transfer"></i> Kas Keluar
                </button>
                
                <button @click="openCloseShiftModal()" x-show="!needsShiftOpen" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-lock"></i> Tutup Kasir
                </button>
            </div>
        </header>

        <div x-show="needsShiftOpen" class="absolute inset-0 z-[100] bg-slate-900/90 backdrop-blur-md flex items-center justify-center">
            <div class="bg-white p-8 rounded-[2rem] shadow-2xl max-w-md w-full border border-slate-200 text-center relative overflow-hidden">
                <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-4"><i class="fa-solid fa-cash-register"></i></div>
                <h2 class="text-2xl font-black text-slate-800 mb-2">Mulai Shift Kasir</h2>
                <p class="text-sm font-bold text-slate-500 mb-6">Masukkan uang modal awal di laci kasir (Cash) untuk mulai transaksi.</p>
                <form @submit.prevent="openShift()" class="space-y-4 text-left">

                    <p class="text-sm font-bold text-slate-500 mb-6 text-center">Klik tombol di bawah untuk membuka laci dan memulai transaksi hari ini.</p>

                    <button type="submit" :disabled="isLoadingShift" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-lg transition-colors duration-200 flex items-center justify-center gap-2 mt-4 disabled:opacity-50">
                        <i class="fa-solid fa-lock-open" :class="isLoadingShift ? 'fa-spin' : ''"></i> BUKA KASIR SEKARANG
                    </button>
                </form>
            </div>
        </div>

        <main class="flex-1 overflow-hidden flex flex-col lg:flex-row p-2 gap-2">

            <!-- PRODUK DI KIRI -->
            <div class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
                <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-sm"><i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i></div>

                <div class="p-2.5 border-b border-slate-100 flex flex-wrap gap-2 items-center bg-slate-50">
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fa-solid fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-primary"></i>
                        <input type="text" x-model="barcodeInput" @keyup.enter="scanBarcode()" x-ref="barcodeScanner" placeholder="Scan Barcode SKU di sini..." class="w-full pl-11 pr-4 py-2 bg-white border border-primary/30 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-black text-sm uppercase tracking-widest shadow-inner placeholder:normal-case placeholder:font-medium placeholder:tracking-normal">
                    </div>
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama produk manual..." class="w-full pl-11 pr-4 py-2 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm">
                    </div>
                    <button @click="loadLocalData(true)" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2"><i class="fa-solid fa-rotate-right"></i> Sync</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-2.5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
                        <template x-for="item in filteredProducts" :key="item.id">
                            <div @click="addToCart(item)" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:border-primary/50 hover:shadow-md transition-all cursor-pointer group flex flex-col h-full active:scale-95">
                                <div class="relative pt-[75%] bg-slate-100 overflow-hidden border-b border-slate-100">
                                    <div class="absolute top-1.5 right-1.5 bg-white/90 backdrop-blur-sm px-1.5 py-0.5 rounded text-[8px] font-black shadow-sm text-slate-600" x-text="item.code || '-'"></div>
                                    <img :src="item.image && item.image !== 'no-image.png' ? '<?= $IMG_BASE_URL ?>' + item.image : ''" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" @error="$el.style.display='none'">
                                </div>
                                <div class="p-2 flex flex-col flex-1 bg-white">
                                    <h3 class="font-bold text-[11px] text-slate-800 leading-tight mb-1 line-clamp-2" x-text="item.name"></h3>
                                    <div class="mt-auto font-black text-primary text-xs" x-text="'Rp ' + formatRupiah(item.price || item.offline_price || 0)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- KERANJANG DI KANAN -->
            <div class="w-full lg:w-[400px] flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden shrink-0">
                <div class="flex p-1 bg-slate-100 border-b border-slate-200 gap-1">
                    <button @click="activeTab = 'reguler'" :class="activeTab === 'reguler' ? 'bg-white shadow-sm text-primary font-black' : 'text-slate-500 hover:bg-slate-200 font-bold'" class="flex-1 py-1.5 rounded-lg text-[10px] uppercase tracking-widest transition-all"><i class="fa-solid fa-cash-register mr-1"></i> Reguler</button>
                    <button @click="activeTab = 'po'" :class="activeTab === 'po' ? 'bg-orange-100 shadow-sm text-orange-600 font-black' : 'text-slate-500 hover:bg-slate-200 font-bold'" class="flex-1 py-1.5 rounded-lg text-[10px] uppercase tracking-widest transition-all"><i class="fa-solid fa-fire-burner mr-1"></i> Pesanan Dapur</button>
                </div>

                <div class="px-2.5 py-1.5 border-b border-slate-100 bg-slate-50">
                    <div class="relative flex gap-1.5">
                        <div class="relative flex-1" @click.away="isCustomerDropdownOpen = false">
                            <button @click="isCustomerDropdownOpen = !isCustomerDropdownOpen" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 flex justify-between items-center outline-none focus:border-primary font-bold text-xs text-left">
                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-user text-[9px] text-slate-400"></i> <span x-text="selectedCustomer ? selectedCustomer.name : 'Pelanggan Umum'" class="truncate"></span></span>
                                <i class="fa-solid fa-chevron-down text-slate-400 text-[9px]"></i>
                            </button>
                            <div x-show="isCustomerDropdownOpen" class="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden" x-cloak>
                                <div class="p-2 border-b border-slate-100">
                                    <input type="text" x-model="searchCustomer" placeholder="Cari pelanggan..." class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold">
                                </div>
                                <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                    <button @click="selectCustomer('')" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 border-b border-slate-50">-- Pelanggan Umum --</button>
                                    <template x-for="cust in filteredCustomers" :key="cust.id">
                                        <button @click="selectCustomer(cust.id)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-slate-50 border-b border-slate-50">
                                            <span x-text="cust.name"></span>
                                            <span class="block text-[10px] text-slate-400" x-text="cust.phone ? cust.phone : '-'"></span>
                                        </button>
                                    </template>
                                    <div x-show="filteredCustomers.length === 0" class="p-3 text-center text-xs text-slate-400 font-bold">Pelanggan tidak ditemukan</div>
                                </div>
                            </div>
                        </div>
                        <button @click="showAddCustomerModal = true" class="bg-slate-800 hover:bg-slate-900 text-white px-2.5 py-1.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0 text-xs">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- TOMBOL ITEM CUSTOM (BEDA BENTUK BERDASARKAN TAB)             -->
                <!-- ============================================================ -->
                <div class="px-2.5 py-1">
                    <button x-show="activeTab === 'reguler'" @click="addCustomItem()"
                        class="w-full flex items-center justify-center gap-1.5 bg-sky-50 hover:bg-sky-100 border border-sky-200 text-sky-700 px-3 py-1.5 rounded-lg text-[11px] font-black transition-all">
                        <i class="fa-solid fa-pen-to-square text-[10px]"></i> + Item Custom
                    </button>
                    
                    <button x-show="activeTab === 'po'" @click="addCustomItem()"
                        class="w-full flex items-center justify-center gap-1.5 bg-orange-50 hover:bg-orange-100 border border-orange-200 text-orange-700 px-3 py-1.5 rounded-lg text-[11px] font-black transition-all">
                        <i class="fa-solid fa-cake-candles text-[10px]"></i> + Item Custom Dapur
                    </button>
                </div>

                <!-- ============================================================ -->
                <!-- FORM INFO PESANAN REGULER: Tgl Ambil, Jam Ambil, Delivery    -->
                <!-- Semua field opsional — hanya tampil jika diisi / diaktifkan  -->
                <!-- ============================================================ -->
                <div x-show="activeTab === 'reguler'" x-transition class="px-2.5 pb-1.5">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 space-y-1.5">
                        <div class="grid grid-cols-2 gap-1.5">
                            <div>
                                <label class="block text-[9px] font-black text-slate-400 mb-0.5 uppercase">Tgl Ambil</label>
                                <input type="date" x-model="regulerForm.pickup_date"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 outline-none text-[11px] font-bold text-slate-700 focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-slate-400 mb-0.5 uppercase">Jam Ambil</label>
                                <input type="time" x-model="regulerForm.pickup_time"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 outline-none text-[11px] font-bold text-slate-700 focus:border-primary">
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-slate-200 border-dashed">
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-motorcycle text-amber-500 text-[10px]"></i>
                                <span class="text-[10px] font-black text-slate-600">Delivery</span>
                            </div>
                            <button type="button"
                                @click="regulerForm.is_delivery = !regulerForm.is_delivery; if(!regulerForm.is_delivery) regulerForm.ongkir = 0"
                                class="relative inline-flex h-4 w-8 items-center rounded-full transition-colors duration-200 focus:outline-none"
                                :class="regulerForm.is_delivery ? 'bg-amber-500' : 'bg-slate-300'">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                                    :class="regulerForm.is_delivery ? 'translate-x-4' : 'translate-x-0.5'"></span>
                            </button>
                        </div>
                        <div x-show="regulerForm.is_delivery" x-transition>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 font-black text-amber-500 text-[10px]">Rp</span>
                                <input type="number" x-model.number="regulerForm.ongkir" placeholder="0"
                                    class="w-full pl-8 pr-2 py-1 bg-amber-50 border border-amber-200 rounded-lg outline-none text-[11px] font-black text-amber-700 focus:border-amber-400">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- FORM INFO PESANAN DAPUR (PO): WAJIB Tgl & Jam Ambil          -->
                <!-- ============================================================ -->
                <div x-show="activeTab === 'po'" x-transition class="px-2.5 pb-1.5">
                    <div class="bg-orange-50 border border-orange-200 rounded-xl p-2 space-y-1.5">
                        <div>
                            <label class="block text-[9px] font-black text-orange-500 mb-0.5 uppercase">Via</label>
                            <select x-model="poForm.channel" class="w-full bg-white border border-orange-200 rounded-lg px-2 py-1 outline-none font-bold text-[11px] text-slate-700 focus:border-orange-400">
                                <option value="toko">Ambil di Toko / Takeaway</option>
                                <option value="delivery">Kurir / Delivery</option>
                                <option value="grab">GrabExpress</option>
                                <option value="gojek">GoSend</option>
                                <option value="online">Platform Online Lainnya</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5">
                            <div>
                                <label class="block text-[9px] font-black text-orange-500 mb-0.5 uppercase">Tgl Ambil *</label>
                                <input type="date" x-model="poForm.pickup_date" required
                                    class="w-full bg-white border border-orange-200 rounded-lg px-2 py-1 outline-none text-[11px] font-bold text-slate-700 focus:border-orange-400">
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-orange-500 mb-0.5 uppercase">Jam Ambil *</label>
                                <input type="time" x-model="poForm.pickup_time" required
                                    class="w-full bg-white border border-orange-200 rounded-lg px-2 py-1 outline-none text-[11px] font-bold text-slate-700 focus:border-orange-400">
                            </div>
                        </div>
                        <div x-show="['delivery', 'grab', 'gojek', 'online'].includes(poForm.channel)" x-transition>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 font-black text-orange-500 text-[10px]">Rp</span>
                                <input type="number" x-model.number="poForm.ongkir" placeholder="Ongkir"
                                    class="w-full pl-8 pr-2 py-1 bg-white border border-orange-200 rounded-lg outline-none text-[11px] font-black text-orange-600 focus:border-orange-400">
                            </div>
                        </div>
                        <div>
                            <textarea x-model="poForm.notes" rows="1" class="w-full bg-white border border-orange-200 rounded-lg px-2 py-1 outline-none font-bold text-[11px] text-slate-700 resize-none focus:border-orange-400" placeholder="Catatan dapur..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar px-2 py-1">
                    <div x-show="cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 space-y-2">
                        <i class="fa-solid fa-basket-shopping text-4xl opacity-30"></i>
                        <p class="font-bold text-xs">Keranjang kosong</p>
                    </div>
                    <div class="space-y-1">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-100 px-2 py-1.5 rounded-lg" :class="item.is_custom ? 'border-orange-200 bg-orange-50/30' : ''">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-xs text-slate-800 truncate" x-text="(item.is_custom ? '🛠️ ' : '') + item.name"></h4>
                                    
                                    <template x-if="item.is_custom_price == 1">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[10px] font-black text-slate-400">Rp</span>
                                            <input type="number" x-model.number="item.price" @input="updatePrice(index)" class="w-20 bg-white border border-slate-200 rounded px-1.5 py-0.5 text-[11px] font-black text-primary outline-none focus:border-primary">
                                        </div>
                                    </template>
                                    <template x-if="item.is_custom_price != 1">
                                        <div class="text-[11px] font-black text-primary" x-text="'Rp ' + formatRupiah(item.price)"></div>
                                    </template>
                                </div>
                                <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-md px-0.5 py-0.5">
                                    <button @click="updateQty(index, -1)" class="w-5 h-5 flex items-center justify-center rounded bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold"><i class="fa-solid fa-minus text-[9px]"></i></button>
                                    <span class="w-5 text-center font-black text-xs" x-text="item.qty"></span>
                                    <button @click="updateQty(index, 1)" class="w-5 h-5 flex items-center justify-center rounded bg-primary text-white hover:bg-blue-600 font-bold"><i class="fa-solid fa-plus text-[9px]"></i></button>
                                </div>
                                <button @click="removeItem(index)" class="w-6 h-6 flex items-center justify-center text-rose-400 hover:text-rose-600 bg-rose-50 rounded-md shrink-0"><i class="fa-solid fa-trash-can text-[10px]"></i></button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- BOTTOM PANEL: flex-col agar bisa dibagi scrollable + sticky button -->
                <!-- <div class="bg-slate-50 border-t border-slate-200 shadow-[0_-10px_30px_rgba(0,0,0,0.05)] flex flex-col" style="max-height: 320px;"> -->

                    <!-- AREA SCROLLABLE: Promo, Poin, Subtotal, 4 Tombol -->
                    <div class="overflow-y-auto custom-scrollbar px-2.5 pt-2 flex-1">

                        <div class="flex gap-1.5 mb-2">
                            <input type="text" x-model="voucherCode" placeholder="Kode Promo..." class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 outline-none focus:border-primary font-bold text-xs uppercase">
                            <button @click="applyVoucher()" class="bg-slate-800 hover:bg-slate-900 text-white px-3 py-1.5 rounded-lg text-[10px] font-black transition-all">Promo</button>
                            <button @click="applyManualDiscount()" class="bg-rose-500 hover:bg-rose-600 text-white px-2.5 py-1.5 rounded-lg text-[10px] font-black transition-all shadow-sm" title="Diskon SPV"><i class="fa-solid fa-percent"></i></button>
                        </div>

                        <div x-show="selectedCustomer && loyaltyRules.is_active" class="flex items-center justify-between bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg mb-2">
                            <div><p class="text-[9px] font-black text-amber-600 uppercase tracking-tight">Poin: <span x-text="selectedCustomer?.points || 0"></span></p></div>
                            <button @click="togglePoints()" :disabled="(selectedCustomer?.points < loyaltyRules.points_required) && !usePoints" class="px-2 py-0.5 rounded text-[9px] font-black transition-all" :class="usePoints ? 'bg-amber-500 text-white' : 'bg-white text-amber-500 border border-amber-200 disabled:opacity-50'">
                                <span x-text="usePoints ? 'POIN DIPAKAI' : 'PAKAI POIN'"></span>
                            </button>
                        </div>

                        <div class="space-y-0.5 mb-1.5 border-t border-slate-200 border-dashed pt-1.5">
                            <div class="flex justify-between text-[11px] font-bold text-slate-500"><span>Subtotal</span> <span x-text="'Rp ' + formatRupiah(subtotal)"></span></div>
                            <div x-show="activeTab === 'po' && poForm.ongkir > 0" class="flex justify-between text-[11px] font-bold text-orange-500"><span>Ongkir (PO)</span> <span x-text="'+ Rp ' + formatRupiah(poForm.ongkir)"></span></div>
                        <div x-show="activeTab === 'reguler' && regulerForm.is_delivery && regulerForm.ongkir > 0" class="flex justify-between text-[11px] font-bold text-amber-500"><span>Ongkir</span> <span x-text="'+ Rp ' + formatRupiah(regulerForm.ongkir)"></span></div>
                            <div x-show="discountVoucher > 0" class="flex justify-between text-[11px] font-bold text-emerald-500"><span>Diskon Voucher</span> <span x-text="'- Rp ' + formatRupiah(discountVoucher)"></span></div>
                            <div x-show="discountPoints > 0" class="flex justify-between text-[11px] font-bold text-amber-500"><span>Diskon Poin</span> <span x-text="'- Rp ' + formatRupiah(discountPoints)"></span></div>
                            <div x-show="discountManual > 0" class="flex justify-between text-[11px] font-bold text-rose-500"><span>Diskon Manual <i @click="discountManual = 0" class="fa-solid fa-xmark cursor-pointer ml-1"></i></span> <span x-text="'- Rp ' + formatRupiah(discountManual)"></span></div>
                        </div>

                        <div class="flex justify-between items-end mb-2 border-t border-slate-200 pt-2">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase">Total</p>
                                <div class="text-2xl font-black text-primary leading-none" x-text="'Rp ' + formatRupiah(totalAmount)"></div>
                            </div>
                            <div x-show="pointsEarned > 0" class="text-[9px] font-bold text-amber-500 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100">+<span x-text="pointsEarned"></span> Poin</div>
                        </div>

                        <div class="grid grid-cols-5 gap-1 mb-2">
                            <button @click="showNotesModal = true" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-[9px] font-bold"><i class="fa-solid fa-note-sticky text-sm mb-0.5"></i> Catatan</button>
                            <button @click="applyManualDiscount()" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-[9px] font-bold"><i class="fa-solid fa-percent text-sm mb-0.5"></i> Diskon</button>
                            <button onclick="window.open('print_receipt.php?invoice=' + (posApp().lastInvoice || ''), '_blank', 'width=400,height=600')" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-[9px] font-bold"><i class="fa-solid fa-print text-sm mb-0.5"></i> Cetak</button>
                            <button @click="openStatusModal()" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-lg bg-orange-100 hover:bg-orange-200 text-orange-600 text-[9px] font-bold"><i class="fa-solid fa-list-check text-sm mb-0.5"></i> Status</button>
                            <button @click="showDapurModal = true" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-lg bg-orange-100 hover:bg-orange-200 text-orange-600 text-[9px] font-bold"><i class="fa-solid fa-fire-burner text-sm mb-0.5"></i> Dapur</button>
                        </div>

                    </div>

                    <!-- TOMBOL CHECKOUT: SELALU TERLIHAT DI PALING BAWAH -->
                    <div class="px-2.5 pb-2.5 pt-1.5 shrink-0">
                        <button @click="processCheckout()" :disabled="cart.length === 0" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-3 rounded-xl shadow-lg transition-all flex justify-center items-center gap-2 text-base disabled:opacity-50" :class="activeTab === 'po' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/30' : ''">
                            <span x-text="activeTab === 'po' ? 'KIRIM KE DAPUR' : 'BAYAR SEKARANG'"></span> <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>

                </div>

            </div>

        </main>
    </div>

    <!-- ===== MODAL STATUS PO ===== --></div>


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
                    <div class="grid grid-cols-3 gap-2 p-1.5 bg-slate-100 rounded-xl max-h-40 overflow-y-auto custom-scrollbar">
                        <template x-for="item in paymentMethods" :key="item.id">
                            <button type="button" @click="paymentMethod = item.name" :class="paymentMethod === item.name ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'" class="py-2 px-1 rounded-lg font-black text-xs transition-all flex flex-col items-center justify-center gap-1 border border-transparent" :class="paymentMethod === item.name ? 'border-blue-200' : ''">
                                <i :class="item.type === 'Cash' ? 'fa-solid fa-money-bill-wave text-emerald-500' : (item.type === 'QRIS' ? 'fa-solid fa-qrcode text-blue-500' : (item.type === 'Debit' ? 'fa-solid fa-credit-card text-indigo-500' : 'fa-solid fa-wallet text-slate-500'))"></i>
                                <span x-text="item.name" class="text-[10px]"></span>
                                <span x-show="item.fee_percent > 0" class="text-[9px] text-rose-500 font-bold" x-text="'+' + item.fee_percent + '%'"></span>
                            </button>
                        </template>
                    </div>
                    
                    <div class="flex justify-between items-center mt-3 px-2 text-xs font-bold text-slate-500" x-show="paymentFeeAmount > 0">
                        <span x-text="paymentFeeName"></span>
                        <span class="text-rose-500" x-text="'+ Rp ' + formatRupiah(paymentFeeAmount)"></span>
                    </div>
                    <div class="flex justify-between items-center mt-1 px-2">
                        <span class="text-xs font-bold text-slate-500">Total Tagihan Akhir:</span>
                        <span class="font-black text-lg text-rose-500" x-text="'Rp ' + formatRupiah(totalAmount)"></span>
                    </div>

                    <div x-show="paymentMethods.find(m => m.name === paymentMethod)?.type === 'Cash'" x-collapse>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 mt-3">Uang Diterima (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400">Rp</span>
                            <input type="number" x-model.number="inputUang" class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-3 text-left font-black text-xl outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        </div>
                        <div class="flex justify-between items-center px-2 mt-3 pt-2 border-t border-slate-100 border-dashed" x-show="inputUang >= totalAmount">
                            <span class="text-xs font-bold text-slate-500">Kembalian:</span>
                            <span class="font-black text-xl text-emerald-500" x-text="'Rp ' + formatRupiah(inputUang - totalAmount)"></span>
                        </div>
                    </div>
                </div>
                <div x-show="paymentStatus === 'dp'" class="mt-4" x-collapse>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Metode Pembayaran</label>
                    <div class="grid grid-cols-3 gap-2 p-1.5 bg-slate-100 rounded-xl mb-3 max-h-40 overflow-y-auto custom-scrollbar">
                        <template x-for="item in paymentMethods" :key="item.id">
                            <button type="button" @click="paymentMethod = item.name" :class="paymentMethod === item.name ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'" class="py-2 px-1 rounded-lg font-black text-xs transition-all flex flex-col items-center justify-center gap-1 border border-transparent" :class="paymentMethod === item.name ? 'border-amber-300' : ''">
                                <i :class="item.type === 'Cash' ? 'fa-solid fa-money-bill-wave text-emerald-500' : (item.type === 'QRIS' ? 'fa-solid fa-qrcode text-blue-500' : (item.type === 'Debit' ? 'fa-solid fa-credit-card text-indigo-500' : 'fa-solid fa-wallet text-slate-500'))"></i>
                                <span x-text="item.name" class="text-[10px]"></span>
                                <span x-show="item.fee_percent > 0" class="text-[9px] text-rose-500 font-bold" x-text="'+' + item.fee_percent + '%'"></span>
                            </button>
                        </template>
                    </div>
                    
                    <div class="flex justify-between items-center mt-2 px-2 text-xs font-bold text-slate-500" x-show="paymentFeeAmount > 0">
                        <span x-text="paymentFeeName"></span>
                        <span class="text-rose-500" x-text="'+ Rp ' + formatRupiah(paymentFeeAmount)"></span>
                    </div>
                    <div class="flex justify-between items-center mt-1 mb-3 px-2">
                        <span class="text-xs font-bold text-slate-500">Total Tagihan Akhir:</span>
                        <span class="font-black text-lg text-rose-500" x-text="'Rp ' + formatRupiah(totalAmount)"></span>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 mt-3">Nominal DP (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-amber-500">Rp</span>
                            <input type="number" x-model.number="inputUang" placeholder="Ketik jumlah DP..." class="w-full bg-amber-50/50 border border-amber-300 rounded-xl pl-11 pr-4 py-3 text-left font-black text-xl outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-amber-700">
                        </div>
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

    <div x-show="showCustomItemModal" class="fixed inset-0 z-[120] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCustomItemModal = false"></div>
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl relative z-10 p-6 m-4 flex flex-col overflow-hidden">
            
            <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                <h3 class="font-black text-xl text-slate-800"><i class="fa-solid fa-pen-to-square text-orange-500 mr-2"></i> Item Custom PO</h3>
                <button @click="showCustomItemModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <p class="text-xs font-bold text-slate-500 mb-4">Tambahkan pesanan khusus yang tidak ada di katalog untuk diteruskan ke dapur.</p>

            <form @submit.prevent="saveCustomItem()" class="space-y-4">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Pilih Dari Template Dapur (Opsional)</label>
                    <select x-model="customItemForm.template" @change="selectCustomTemplate($event, 'po')" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-3 outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 font-bold text-sm text-slate-700 cursor-pointer">
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
                    <button type="submit" :disabled="isSavingCustomItem" class="flex-1 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white font-black py-3 rounded-xl shadow-md shadow-orange-500/30 transition-all flex justify-center items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Tambahkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CUSTOM ITEM REGULER -->
    <div x-show="showCustomRegulerModal" class="fixed inset-0 z-[120] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCustomRegulerModal = false"></div>
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl relative z-10 p-6 m-4 flex flex-col overflow-hidden">
            
            <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                <h3 class="font-black text-xl text-slate-800"><i class="fa-solid fa-pen-to-square text-sky-500 mr-2"></i> Item Custom Reguler</h3>
                <button @click="showCustomRegulerModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <p class="text-xs font-bold text-slate-500 mb-4">Tambahkan item pesanan yang langsung dibawa tanpa ke dapur.</p>

            <form @submit.prevent="saveCustomItem()" class="space-y-4">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Pilih Dari Template Reguler (Opsional)</label>
                    <select x-model="customItemForm.template" @change="selectCustomTemplate($event, 'reguler')" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-3 outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-bold text-sm text-slate-700 cursor-pointer">
                        <option value="">-- Ketik Manual Baru --</option>
                        <template x-for="c in savedCustomsReguler" :key="c.id">
                            <option :value="c.id" x-text="c.name + ' - Rp ' + formatRupiah(c.price)"></option>
                        </template>
                    </select>
                </div>

                <div class="border-t border-slate-100 pt-3">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nama Item Reguler</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-shopping-bag"></i></span>
                        <input type="text" x-model="customItemForm.name" placeholder="Misal: Kue Reguler Extra" required class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-bold text-sm text-slate-800 placeholder:text-slate-400">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Harga Satuan (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-sky-500">Rp</span>
                        <input type="number" x-model="customItemForm.price" required placeholder="0" class="w-full bg-sky-50/50 border border-sky-200 rounded-xl pl-11 pr-4 py-3 outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-black text-sky-700 text-lg placeholder:text-sky-300">
                    </div>
                </div>

                <div class="pt-2 mt-4 flex gap-3">
                    <button type="button" @click="showCustomRegulerModal = false" class="py-3 px-5 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" :disabled="isSavingCustomItem" class="flex-1 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white font-black py-3 rounded-xl shadow-md shadow-sky-500/30 transition-all flex justify-center items-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i> Kirim
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showNotesModal" class="fixed inset-0 z-[120] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showNotesModal = false"></div>
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl relative z-10 p-6 m-4 flex flex-col overflow-hidden">
            <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                <h3 class="font-black text-xl text-slate-800"><i class="fa-solid fa-note-sticky text-amber-500 mr-2"></i> Catatan Pesanan</h3>
                <button @click="showNotesModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <p class="text-xs font-bold text-slate-500 mb-4">Tambahkan catatan khusus untuk seluruh pesanan ini.</p>
            <div class="space-y-4">
                <textarea x-model="orderNotes" rows="4" placeholder="Misal: Tolong bungkus terpisah, atau tambahan sambal..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-bold text-sm text-slate-800"></textarea>
                <div class="pt-2 flex gap-3">
                    <button type="button" @click="showNotesModal = false; orderNotes = ''" class="py-3 px-5 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Hapus</button>
                    <button type="button" @click="showNotesModal = false" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-black py-3 rounded-xl shadow-md shadow-amber-500/30 transition-all flex justify-center items-center gap-2">
                        Simpan Catatan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showAddCustomerModal" class="fixed inset-0 z-[120] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddCustomerModal = false"></div>
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl relative z-10 p-6 m-4 flex flex-col overflow-hidden">
            <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                <h3 class="font-black text-xl text-slate-800"><i class="fa-solid fa-user-plus text-blue-500 mr-2"></i> Pelanggan Baru</h3>
                <button @click="showAddCustomerModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form @submit.prevent="submitNewCustomer()" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nama Lengkap</label>
                    <input type="text" x-model="newCustomerForm.name" required placeholder="Nama Pelanggan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-bold text-sm text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nomor Handphone (Opsional)</label>
                    <input type="text" x-model="newCustomerForm.phone" placeholder="081234..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-bold text-sm text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Alamat (Opsional)</label>
                    <textarea x-model="newCustomerForm.address" rows="2" placeholder="Alamat Pengiriman..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-bold text-sm text-slate-800"></textarea>
                </div>
                <div class="pt-2 flex gap-3">
                    <button type="button" @click="showAddCustomerModal = false" class="py-3 px-5 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" :disabled="isSavingCustomer" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-3 rounded-xl shadow-md shadow-blue-500/30 transition-all flex justify-center items-center gap-2 disabled:opacity-50">
                        <i class="fa-solid fa-save" :class="isSavingCustomer ? 'fa-spin' : ''"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

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