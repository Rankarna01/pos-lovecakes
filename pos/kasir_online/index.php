<?php
require_once '../../config/auth.php';
require_once '../../config/database.php';

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder_pos = $is_localhost ? '/pos-lovecakes/' : '/'; 
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder_pos); }
$IMG_BASE_URL = $is_localhost ? "http://localhost/sim-produksi-kue/assets/img/" : "https://kokowms.my.id/assets/img/";

try {
    $stmt_toko = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1");
    $toko = $stmt_toko->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $toko = false; }
if(!$toko) { $toko = ['store_name' => 'LOVE CAKES', 'store_address' => '-', 'store_phone' => '-', 'receipt_footer' => 'Terima Kasih!']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
    <script> const BASE_URL = "<?= BASE_URL ?>"; </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @media print {
            body * { visibility: hidden; }
            #print-receipt, #print-receipt * { visibility: visible; }
            #print-receipt { position: absolute; left: 0; top: 0; width: 58mm; max-width: 58mm; margin: 0; padding: 0 4mm; font-family: 'Courier New', monospace; font-size: 11px; color: #000; }
            .no-print { display: none !important; }
        }
    </style>
<body class="bg-slate-100 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="kasirOnlineApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden no-print">
        
        <!-- HEADER APPS & CHANNEL SELECTOR -->
        <header class="bg-slate-900 text-white shadow-md px-4 py-3 flex flex-col md:flex-row justify-between items-center z-20 shrink-0 gap-3">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-slate-800 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-lg font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-tower-cell text-emerald-400"></i> Kasir Online & Channel Hub
                    </h2>
                    <p class="text-[10px] text-slate-400 font-bold">Pusat Transaksi & Penanda Harga GrabFood, GoFood, ShopeeFood, WA Delivery</p>
                </div>
            </div>

            <!-- STORE / MULTI-TENANT SELECTOR PILL -->
            <div class="flex items-center gap-1 bg-slate-800 px-2.5 py-1.5 rounded-xl border border-slate-700 shrink-0">
                <i class="fa-solid fa-store text-amber-400 text-xs"></i>
                <select x-model="selectedStoreId" @change="switchStore()" class="bg-transparent text-white font-black text-xs outline-none cursor-pointer pr-1">
                    <template x-for="wh in warehouses" :key="wh.id">
                        <option :value="wh.id" x-text="wh.name" class="bg-slate-900 text-white font-bold"></option>
                    </template>
                </select>
            </div>

            <!-- CHANNEL SELECTOR PILLS -->
            <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pb-1 md:pb-0 w-full md:w-auto">
                <button @click="selectChannel('grabfood')" :class="activeChannel === 'grabfood' ? 'bg-emerald-600 text-white shadow-md font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 font-bold'" class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-motorcycle text-emerald-400"></i> GrabFood
                </button>

                <button @click="selectChannel('gofood')" :class="activeChannel === 'gofood' ? 'bg-rose-600 text-white shadow-md font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 font-bold'" class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-utensils text-rose-400"></i> GoFood
                </button>

                <button @click="selectChannel('shopeefood')" :class="activeChannel === 'shopeefood' ? 'bg-orange-600 text-white shadow-md font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 font-bold'" class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-bag-shopping text-orange-400"></i> ShopeeFood
                </button>

                <button @click="selectChannel('travelokaeats')" :class="activeChannel === 'travelokaeats' ? 'bg-sky-600 text-white shadow-md font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 font-bold'" class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-plane-departure text-sky-400"></i> TravelokaEats
                </button>
            </div>
        </header>

        <!-- KONTEN UTAMA POS ONLINE -->
        <main class="flex-1 flex flex-col md:flex-row overflow-hidden">
            
            <!-- AREA KIRI: KATALOG PRODUK & SEARCH -->
            <div class="flex-1 flex flex-col h-full bg-slate-50 border-r border-slate-200 overflow-hidden">
                
                <!-- CHANNEL BANNER NOTIFICATION -->
                <div class="bg-blue-600 text-white px-4 py-2 text-xs font-black flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-tag text-amber-300"></i>
                        <span>Channel Aktif: <strong class="uppercase text-amber-300" x-text="activeChannel"></strong></span>
                        <span class="bg-white/20 px-2 py-0.5 rounded text-[10px]" x-text="getChannelMarkupBadge()"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="openCustomRegulerModal()" class="bg-amber-400 hover:bg-amber-500 text-slate-900 px-2.5 py-1 rounded-lg text-[11px] font-black transition-all">
                            + Custom Reguler
                        </button>
                        <button @click="openCustomPOModal()" class="bg-purple-400 hover:bg-purple-500 text-slate-900 px-2.5 py-1 rounded-lg text-[11px] font-black transition-all">
                            + Custom PO
                        </button>
                    </div>
                </div>

                <!-- SEARCH & CATEGORY BAR -->
                <div class="p-3 bg-white border-b border-slate-200 space-y-2 shrink-0">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama produk, SKU (Barcode)..." class="w-full bg-slate-100 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    </div>

                    <!-- Category Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pb-1">
                        <button @click="selectCategory('all')" :class="selectedCategory === 'all' ? 'bg-primary text-white font-black shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all">
                            Semua Produk
                        </button>
                        <template x-for="cat in categories" :key="cat">
                            <button @click="selectCategory(cat)" :class="selectedCategory.toLowerCase().trim() === cat.toLowerCase().trim() ? 'bg-primary text-white font-black shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all" x-text="cat"></button>
                        </template>
                    </div>
                </div>

                <!-- GRID KATALOG PRODUK -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-2.5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-2 sm:gap-2.5">
                        <template x-for="product in filteredProducts" :key="(product.item_type || 'product') + '_' + product.id">
                            <div @click="addToCart(product)" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:border-primary/50 hover:shadow-md transition-all cursor-pointer group flex flex-col h-full active:scale-95">
                                
                                <div class="relative pt-[70%] bg-slate-100 overflow-hidden border-b border-slate-100">
                                    <span class="absolute top-1 right-1 bg-slate-900/80 text-white text-[8px] font-mono px-1.5 py-0.5 rounded z-10" x-text="product.is_custom ? 'Custom' : 'Stok: ' + product.stock"></span>
                                    <img :src="product.image ? '<?= $IMG_BASE_URL ?>' + product.image : 'https://placehold.co/150x150/e2e8f0/64748b?text=LoveCakes'" :alt="product.name" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </div>
                                <div class="p-2 flex flex-col flex-1 bg-white">
                                    <h4 class="font-black text-slate-800 text-[11px] leading-tight line-clamp-2 mb-1" x-text="product.name"></h4>
                                    
                                    <div class="mt-auto pt-1 border-t border-slate-100 flex items-center justify-between">
                                        <div>
                                            <!-- Platform Price -->
                                            <p class="font-black text-emerald-600 text-xs" x-text="'Rp ' + formatRupiah(getProductPlatformPrice(product))"></p>
                                        </div>
                                        <div class="w-5 h-5 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] font-black group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                            <i class="fa-solid fa-plus"></i>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>

                    <div x-show="filteredProducts.length === 0" class="h-64 flex flex-col items-center justify-center text-center p-6 text-slate-400">
                        <i class="fa-solid fa-store-slash text-4xl mb-3 text-slate-300"></i>
                        <p class="font-black text-slate-700 text-sm">Tidak Ada Produk Aktif di Platform Ini</p>
                        <p class="text-xs text-slate-400 mt-1 max-w-xs">Produk belum ditambahkan/diaktifkan untuk channel <strong class="uppercase text-primary" x-text="activeChannel"></strong> di menu <b>Manaj. Online -> Food Delivery</b>.</p>
                    </div>
                </div>
            </div>

            <!-- AREA KANAN: KERANJANG & CHECKOUT ONLINE -->
            <div class="w-full md:w-96 bg-white flex flex-col h-full shrink-0 shadow-lg border-l border-slate-200">
                
                <!-- HEADER KERANJANG -->
                <div class="p-3.5 border-b border-slate-200 bg-slate-50 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm flex items-center gap-1.5">
                            <i class="fa-solid fa-cart-shopping text-primary"></i> Keranjang Pesanan Online
                        </h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase" x-text="'Channel: ' + activeChannel"></p>
                    </div>
                    <button @click="cart = []" x-show="cart.length > 0" class="text-[10px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 px-2 py-1 rounded-lg">
                        Kosongkan
                    </button>
                </div>

                <!-- DRIVER & CUSTOMER INFO -->
                <div class="p-3 bg-slate-50/50 border-b border-slate-200 space-y-2 text-xs font-bold shrink-0">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] text-slate-400 uppercase">Driver / Ojol</label>
                            <input type="text" x-model="driverName" placeholder="Nama Driver..." class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-400 uppercase">Order ID Ojol</label>
                            <input type="text" x-model="externalOrderId" placeholder="GF-8832..." class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold outline-none">
                        </div>
                    </div>
                </div>

                <!-- LIST ITEM KERANJANG -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-2">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="p-2.5 rounded-xl border border-slate-200 bg-white space-y-1.5">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-black text-slate-800 text-xs" x-text="item.name"></p>
                                    <p class="text-[10px] font-bold text-emerald-600" x-text="'Rp ' + formatRupiah(item.price)"></p>
                                </div>
                                <button @click="removeFromCart(index)" class="text-slate-300 hover:text-rose-600 text-xs p-1">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            <div class="flex justify-between items-center pt-1 border-t border-slate-100">
                                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-0.5">
                                    <button @click="updateQty(index, -1)" class="w-5 h-5 rounded bg-white text-slate-600 font-black text-xs flex items-center justify-center hover:bg-slate-200">-</button>
                                    <span class="w-6 text-center font-black text-xs" x-text="item.qty"></span>
                                    <button @click="updateQty(index, 1)" class="w-5 h-5 rounded bg-white text-slate-600 font-black text-xs flex items-center justify-center hover:bg-slate-200">+</button>
                                </div>
                                <span class="font-black text-slate-800 text-xs" x-text="'Rp ' + formatRupiah(item.subtotal)"></span>
                            </div>
                        </div>
                    </template>

                    <div x-show="cart.length === 0" class="h-48 flex flex-col items-center justify-center text-slate-400">
                        <i class="fa-solid fa-basket-shopping text-3xl mb-2"></i>
                        <p class="text-xs font-bold">Keranjang Masih Kosong</p>
                    </div>
                </div>

                <!-- SUMMARY HARGA & PEMBAYARAN -->
                <div class="p-3.5 bg-slate-50 border-t border-slate-200 space-y-2 shrink-0">
                    <div class="flex justify-between text-xs font-bold text-slate-500">
                        <span>Subtotal</span>
                        <span x-text="'Rp ' + formatRupiah(cartSubtotal)"></span>
                    </div>

                    <div class="flex justify-between text-sm font-black text-slate-800 pt-1 border-t border-slate-200">
                        <span>Total Tagihan</span>
                        <span class="text-emerald-600 text-base" x-text="'Rp ' + formatRupiah(cartTotal)"></span>
                    </div>

                    <button @click="openPaymentModal()" :disabled="cart.length === 0" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-black py-3 rounded-xl transition-all shadow-md disabled:opacity-50 text-xs flex items-center justify-center gap-2">
                        <i class="fa-solid fa-credit-card"></i> Process Online Checkout
                    </button>
                </div>

            </div>

        </main>
    </div>

    <!-- MODAL PEMBAYARAN ONLINE (PROSES PEMBAYARAN KASIR POS - WIDE 2-COLUMN NO-SCROLL) -->
    <div x-show="showPaymentModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 overflow-y-auto" style="display: none;" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showPaymentModal = false"></div>
        <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl relative z-10 p-6 flex flex-col overflow-hidden my-auto border border-slate-100">
            
            <!-- HEADER MODAL -->
            <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                <h3 class="font-black text-xl text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-blue-500"></i> Proses Pembayaran Online
                </h3>
                <button @click="showPaymentModal = false" class="text-slate-400 hover:text-rose-500 transition-colors w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- BODY 2 KOLOM -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                
                <!-- KOLOM KIRI: STATUS & METODE PEMBAYARAN -->
                <div class="space-y-4">
                    <!-- Status Pembayaran: Lunas vs DP -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">STATUS PEMBAYARAN</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button type="button" @click="setPaymentStatus('lunas')" :class="paymentStatus === 'lunas' ? 'border-blue-500 bg-blue-50 text-blue-700 ring-2 ring-blue-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-500'" class="p-3 rounded-xl border-2 transition-all text-center flex flex-col items-center justify-center">
                                <i class="fa-solid fa-check-circle text-2xl mb-1" :class="paymentStatus === 'lunas' ? 'text-blue-500' : 'text-slate-300'"></i>
                                <div class="font-black text-xs">Bayar Lunas</div>
                            </button>
                            <button type="button" @click="setPaymentStatus('dp')" :class="paymentStatus === 'dp' ? 'border-amber-500 bg-amber-50 text-amber-700 ring-2 ring-amber-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-500'" class="p-3 rounded-xl border-2 transition-all text-center flex flex-col items-center justify-center">
                                <i class="fa-solid fa-hand-holding-dollar text-2xl mb-1" :class="paymentStatus === 'dp' ? 'text-amber-500' : 'text-slate-300'"></i>
                                <div class="font-black text-xs">DP / Kasbon</div>
                            </button>
                        </div>
                    </div>

                    <!-- Metode Pembayaran Dinamis Berdasarkan Channel Aktif -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">METODE PEMBAYARAN (<span class="uppercase text-primary" x-text="activeChannel"></span>)</label>
                        <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-100 rounded-2xl max-h-48 overflow-y-auto custom-scrollbar">
                            <template x-for="item in activePlatformPaymentMethods" :key="item.id || item.name">
                                <button type="button" @click="paymentMethod = item.name" :class="paymentMethod === item.name ? 'bg-white shadow-xs text-slate-800 border-blue-500 ring-2 ring-blue-500/20 font-black' : 'text-slate-600 hover:text-slate-900 border-transparent font-bold bg-white/60'" class="p-2.5 rounded-xl text-xs transition-all flex items-center gap-2.5 border text-left">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0">
                                        <i class="fa-solid" :class="{
                                            'fa-wallet': item.type === 'E-Wallet',
                                            'fa-qrcode': item.type === 'QRIS',
                                            'fa-building-columns': item.type === 'Transfer',
                                            'fa-money-bill-transfer': item.type === 'Digital',
                                            'fa-money-bill': item.type === 'Cash'
                                        }"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <span x-text="item.name" class="block text-[11px] font-black truncate"></span>
                                        <span x-text="item.type" class="block text-[9px] text-slate-400 font-medium"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Summary Order ID & Driver -->
                    <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl space-y-1 text-xs text-slate-600">
                        <p class="flex justify-between"><span>Channel:</span> <strong class="uppercase text-primary" x-text="activeChannel"></strong></p>
                        <p class="flex justify-between"><span>Driver / Ojol:</span> <strong class="text-slate-800" x-text="driverName || '-'"></strong></p>
                        <p class="flex justify-between"><span>Order ID Ojol:</span> <strong class="text-slate-800" x-text="externalOrderId || '-'"></strong></p>
                    </div>
                </div>

                <!-- KOLOM KANAN: TAGIHAN, INPUT UANG & TOMBOL PROSES -->
                <div class="flex flex-col justify-between space-y-4">
                    
                    <!-- Total Tagihan Akhir -->
                    <div class="bg-gradient-to-br from-rose-50 to-orange-50 border border-rose-100 p-4 rounded-2xl flex justify-between items-center shadow-xs">
                        <div>
                            <span class="block text-[10px] font-black text-rose-400 uppercase tracking-widest">TOTAL TAGIHAN AKHIR</span>
                            <span class="font-black text-2xl text-rose-600" x-text="'Rp ' + formatRupiah(cartTotal)"></span>
                        </div>
                        <i class="fa-solid fa-file-invoice-dollar text-3xl text-rose-300"></i>
                    </div>

                    <!-- Input Uang Cash & Quick Suggestions -->
                    <div x-show="paymentMethod === 'Cash' || paymentMethod === 'cash'" class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">UANG DITERIMA (RP)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400">Rp</span>
                            <input type="number" x-model.number="inputUang" class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-2.5 text-left font-black text-xl outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        </div>
                        <div class="flex gap-1.5 overflow-x-auto custom-scrollbar pb-1">
                            <template x-for="sug in cashSuggestions" :key="sug">
                                <button type="button" @click="inputUang = sug" class="flex-shrink-0 px-2.5 py-1 bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 border border-slate-200 hover:border-blue-300 rounded-lg text-xs font-bold transition-colors" x-text="sug === cartTotal ? 'Uang Pas' : formatRupiah(sug)"></button>
                            </template>
                        </div>

                        <div class="flex justify-between items-center p-3 bg-emerald-50 border border-emerald-100 rounded-xl" x-show="inputUang >= cartTotal">
                            <span class="text-xs font-bold text-emerald-700">Kembalian:</span>
                            <span class="font-black text-xl text-emerald-600" x-text="'Rp ' + formatRupiah(inputUang - cartTotal)"></span>
                        </div>
                    </div>

                    <!-- Input Referensi Non-Cash -->
                    <div x-show="paymentMethod !== 'Cash' && paymentMethod !== 'cash'" class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">REF. PEMBAYARAN</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400"><i class="fa-solid fa-receipt"></i></span>
                            <input type="text" x-model="paymentReference" placeholder="Masukkan nomor referensi..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-2.5 text-left font-bold text-sm outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="pt-2 border-t border-slate-100 flex gap-3 mt-auto">
                        <button type="button" @click="showPaymentModal = false" class="py-3 px-5 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors text-xs">Batal</button>
                        <button type="button" @click="processCheckout()" :disabled="isProcessing" class="flex-1 py-3 rounded-xl font-black text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/30 transition-all flex justify-center items-center gap-2 text-xs">
                            <i class="fa-solid fa-check-double"></i> Proses Transaksi
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- MODAL CUSTOM ITEM REGULER -->
    <div x-show="showCustomRegulerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showCustomRegulerModal = false"></div>
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 flex flex-col overflow-hidden border border-slate-200">
            <div class="p-4 border-b border-slate-100 bg-amber-500 text-white flex justify-between items-center">
                <h3 class="font-black text-sm flex items-center gap-1.5"><i class="fa-solid fa-plus-circle"></i> Tambah Custom Reguler</h3>
                <button @click="showCustomRegulerModal = false" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="p-5 space-y-3 text-xs font-bold">
                <div>
                    <label class="block text-slate-500 mb-1">Nama Item Custom</label>
                    <input type="text" x-model="customRegulerForm.name" placeholder="Nama item..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 font-bold outline-none">
                </div>

                <div>
                    <label class="block text-slate-500 mb-1">Harga Item (Rp)</label>
                    <input type="number" x-model.number="customRegulerForm.price" placeholder="100000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 font-bold outline-none">
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-2">
                <button @click="submitCustomReguler()" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-black py-2.5 rounded-xl transition-all text-xs shadow-md">
                    Tambahkan ke Keranjang
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL CUSTOM ITEM PO -->
    <div x-show="showCustomPOModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showCustomPOModal = false"></div>
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 flex flex-col overflow-hidden border border-slate-200">
            <div class="p-4 border-b border-slate-100 bg-purple-500 text-white flex justify-between items-center">
                <h3 class="font-black text-sm flex items-center gap-1.5"><i class="fa-solid fa-plus-circle"></i> Tambah Custom PO</h3>
                <button @click="showCustomPOModal = false" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="p-5 space-y-3 text-xs font-bold">
                <div>
                    <label class="block text-slate-500 mb-1">Nama Item Custom PO</label>
                    <input type="text" x-model="customPOForm.name" placeholder="Nama item PO..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 font-bold outline-none">
                </div>

                <div>
                    <label class="block text-slate-500 mb-1">Harga Item (Rp)</label>
                    <input type="number" x-model.number="customPOForm.price" placeholder="150000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 font-bold outline-none">
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-2">
                <button @click="submitCustomPO()" class="flex-1 bg-purple-500 hover:bg-purple-600 text-white font-black py-2.5 rounded-xl transition-all text-xs shadow-md">
                    Tambahkan ke Keranjang PO
                </button>
            </div>
        </div>
    </div>

    <!-- STRUK STRUK PRINT RECEIPT (58mm Thermal) -->
    <div id="print-receipt" class="hidden">
        <div style="text-align: center; margin-bottom: 8px;">
            <h2 style="margin: 0; font-size: 14px; font-weight: bold;"><?= htmlspecialchars($toko['store_name']) ?></h2>
            <p style="margin: 2px 0; font-size: 9px;"><?= htmlspecialchars($toko['store_address']) ?></p>
            <p style="margin: 0; font-size: 9px;">ONLINE ORDER: <strong x-text="activeChannel.toUpperCase()"></strong></p>
        </div>
        <hr style="border-top: 1px dashed #000; margin: 4px 0;">
        <p style="margin: 2px 0;">No: <span x-text="lastReceipt.invoice_no"></span></p>
        <p style="margin: 2px 0;">Driver: <span x-text="lastReceipt.driver_name || '-'"></span></p>
        <p style="margin: 2px 0;">Order ID: <span x-text="lastReceipt.external_order_id || '-'"></span></p>
        <hr style="border-top: 1px dashed #000; margin: 4px 0;">
        <template x-for="item in lastReceipt.items" :key="item.name">
            <div style="margin-bottom: 4px;">
                <div style="font-weight: bold;" x-text="item.name"></div>
                <div style="display: flex; justify-between: space-between;">
                    <span x-text="item.qty + ' x ' + formatRupiah(item.price)"></span>
                    <span x-text="formatRupiah(item.subtotal)"></span>
                </div>
            </div>
        </template>
        <hr style="border-top: 1px dashed #000; margin: 4px 0;">
        <div style="display: flex; justify-between: space-between; font-weight: bold;">
            <span>TOTAL</span>
            <span x-text="'Rp ' + formatRupiah(lastReceipt.total_amount)"></span>
        </div>
        <hr style="border-top: 1px dashed #000; margin: 4px 0;">
        <div style="text-align: center; margin-top: 8px; font-size: 9px;">
            <p><?= htmlspecialchars($toko['receipt_footer']) ?></p>
        </div>
    </div>

    <script src="../js/device_lock.js?v=<?= time() ?>"></script>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>