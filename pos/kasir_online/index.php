<?php
require_once '../../config/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
    <style>
        .channel-grab { background-color: #00b140; color: white; }
        .channel-gojek { background-color: #ee2737; color: white; }
        .channel-wa { background-color: #0070ba; color: white; }
    </style>
</head>
<body class="bg-slate-100 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="posOnlineApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden no-print">
        
        <!-- HEADER APPS -->
        <header class="bg-slate-900 text-white shadow-md px-4 py-3.5 flex flex-col md:flex-row justify-between items-center z-20 shrink-0 gap-3">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-slate-800 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <div>
                    <h2 class="text-lg font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-tower-cell text-emerald-400"></i> Kasir Online & Channel Hub
                    </h2>
                    <p class="text-[10px] text-slate-400 font-bold">Pusat Kelola Pesanan GrabFood, GoFood, & WA Delivery</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto justify-end">
                <!-- Status API Grab -->
                <div class="bg-slate-800 border border-slate-700 text-emerald-400 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>GrabFood API: <strong class="text-white" x-text="grabConfig.env ? grabConfig.env.toUpperCase() : 'SANDBOX'"></strong></span>
                </div>

                <!-- Toggle Suara -->
                <button @click="soundEnabled = !soundEnabled" :class="soundEnabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-slate-800 text-slate-400 border-slate-700'" class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5">
                    <i class="fa-solid" :class="soundEnabled ? 'fa-bell text-emerald-400 animate-bounce' : 'fa-bell-slash'"></i>
                    <span x-text="soundEnabled ? 'Suara ON' : 'Suara OFF'"></span>
                </button>

                <!-- Tombol Simulasi Order (Untuk Testing Live) -->
                <div class="flex items-center gap-1 bg-slate-800 p-1 rounded-xl border border-slate-700">
                    <button @click="simulateOrder('grab')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-2.5 py-1 rounded-lg text-xs font-black transition-all flex items-center gap-1">
                        <i class="fa-solid fa-plus-circle"></i> +Test Grab
                    </button>
                    <button @click="simulateOrder('gojek')" class="bg-rose-600 hover:bg-rose-500 text-white px-2.5 py-1 rounded-lg text-xs font-black transition-all flex items-center gap-1">
                        <i class="fa-solid fa-plus-circle"></i> +Test Gojek
                    </button>
                </div>

                <!-- Tombol Input Manual -->
                <button @click="showManualModal = true" class="bg-primary hover:bg-blue-600 text-white px-4 py-1.5 rounded-xl text-xs font-black transition-all shadow-md flex items-center gap-1.5">
                    <i class="fa-solid fa-cart-plus"></i> Input Manual
                </button>
            </div>
        </header>

        <!-- KONTEN UTAMA HUB PESANAN -->
        <main class="flex-1 overflow-hidden flex flex-col p-4 gap-4 bg-[#f8fafc]">
            
            <!-- BAR FILTER CHANNEL & PENCARIAN -->
            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col lg:flex-row gap-3 justify-between items-center shrink-0">
                
                <!-- Channel Selector Tabs -->
                <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar w-full lg:w-auto pb-1 lg:pb-0">
                    <button @click="setChannelFilter('all')" :class="channelFilter === 'all' ? 'bg-slate-900 text-white shadow-sm font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-layer-group"></i> Semua Channel
                    </button>
                    <button @click="setChannelFilter('grab')" :class="channelFilter === 'grab' ? 'bg-emerald-600 text-white shadow-sm font-black' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-bold'" class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-bag-shopping"></i> GrabFood
                    </button>
                    <button @click="setChannelFilter('gojek')" :class="channelFilter === 'gojek' ? 'bg-rose-600 text-white shadow-sm font-black' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 font-bold'" class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-motorcycle"></i> GoFood
                    </button>
                    <button @click="setChannelFilter('wa_delivery')" :class="channelFilter === 'wa_delivery' ? 'bg-blue-600 text-white shadow-sm font-black' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 font-bold'" class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-brands fa-whatsapp"></i> WA / Web Delivery
                    </button>
                </div>

                <!-- Input Pencarian -->
                <div class="relative w-full lg:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari Order ID / Pelanggan / Driver..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-slate-900/20 font-bold text-xs">
                </div>
            </div>

            <!-- STATUS PIPELINE TABS -->
            <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar shrink-0 pb-1">
                <button @click="setStatusFilter('new')" :class="statusFilter === 'new' ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20 font-black' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 font-bold'" class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap relative">
                    <i class="fa-solid fa-fire text-amber-300"></i> Pesanan Masuk (Baru)
                    <span class="ml-1 bg-white text-rose-600 px-2 py-0.5 rounded-full text-[10px] font-black" x-text="getOrderCount('new')"></span>
                </button>

                <button @click="setStatusFilter('cooking')" :class="statusFilter === 'cooking' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20 font-black' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 font-bold'" class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap">
                    <i class="fa-solid fa-kitchen-set"></i> Diproses / Dapur
                    <span class="ml-1 bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-[10px] font-black" x-text="getOrderCount('cooking')"></span>
                </button>

                <button @click="setStatusFilter('ready')" :class="statusFilter === 'ready' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20 font-black' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 font-bold'" class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap">
                    <i class="fa-solid fa-motorcycle"></i> Siap Pick-Up
                    <span class="ml-1 bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-[10px] font-black" x-text="getOrderCount('ready')"></span>
                </button>

                <button @click="setStatusFilter('completed')" :class="statusFilter === 'completed' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20 font-black' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 font-bold'" class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap">
                    <i class="fa-solid fa-circle-check"></i> Selesai
                    <span class="ml-1 bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full text-[10px] font-black" x-text="getOrderCount('completed')"></span>
                </button>

                <button @click="setStatusFilter('cancelled')" :class="statusFilter === 'cancelled' ? 'bg-slate-700 text-white shadow-sm font-black' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 font-bold'" class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap">
                    <i class="fa-solid fa-ban"></i> Dibatalkan
                </button>
            </div>

            <!-- GRID CARDS PESANAN ONLINE -->
            <div class="flex-1 overflow-y-auto custom-scrollbar relative">
                
                <!-- Loading State -->
                <div x-show="isLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 backdrop-blur-xs rounded-2xl">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-slate-800 mb-2"></i>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Memuat Feed Pesanan Online...</p>
                </div>

                <!-- Empty State -->
                <div x-show="!isLoading && filteredOrders.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 p-8 text-center bg-white rounded-2xl border border-slate-200">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-3 text-slate-300">
                        <i class="fa-solid fa-motorcycle text-3xl"></i>
                    </div>
                    <h3 class="font-black text-slate-700 text-base">Tidak Ada Pesanan Online</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm">Belum ada pesanan masuk pada filter ini. Cobalah mengklik tombol <strong>+Test Grab</strong> di pojok kanan atas untuk mensimulasikan pesanan baru!</p>
                </div>

                <!-- Grid Order Cards -->
                <div x-show="!isLoading && filteredOrders.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 pb-4">
                    
                    <template x-for="order in filteredOrders" :key="order.id">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden relative" :class="order.order_status === 'new' ? 'border-rose-300 ring-2 ring-rose-500/20' : ''">
                            
                            <!-- Card Header -->
                            <div class="p-3.5 border-b border-slate-100 flex justify-between items-center" :class="order.channel === 'grab' ? 'bg-emerald-50/50' : (order.channel === 'gojek' ? 'bg-rose-50/50' : 'bg-blue-50/50')">
                                <div class="flex items-center gap-2">
                                    <!-- Channel Badge -->
                                    <template x-if="order.channel === 'grab'">
                                        <span class="bg-emerald-600 text-white text-[10px] font-black px-2 py-0.5 rounded-md flex items-center gap-1 uppercase">
                                            <i class="fa-solid fa-bag-shopping"></i> GrabFood
                                        </span>
                                    </template>
                                    <template x-if="order.channel === 'gojek'">
                                        <span class="bg-rose-600 text-white text-[10px] font-black px-2 py-0.5 rounded-md flex items-center gap-1 uppercase">
                                            <i class="fa-solid fa-motorcycle"></i> GoFood
                                        </span>
                                    </template>
                                    <template x-if="order.channel === 'wa_delivery'">
                                        <span class="bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded-md flex items-center gap-1 uppercase">
                                            <i class="fa-brands fa-whatsapp"></i> WA / Web
                                        </span>
                                    </template>

                                    <!-- Order ID -->
                                    <span class="font-black text-slate-800 text-xs" x-text="order.external_order_id || order.invoice_no"></span>
                                </div>

                                <div class="text-[10px] font-bold text-slate-400" x-text="formatTime(order.created_at)"></div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 flex-1 space-y-3">
                                
                                <!-- Info Pelanggan & Driver -->
                                <div class="flex justify-between items-start gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Pemesan</p>
                                        <p class="font-black text-slate-800 text-xs mt-0.5" x-text="order.customer_name || 'Pelanggan Online'"></p>
                                    </div>
                                    <div class="text-right" x-show="order.driver_name">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Driver</p>
                                        <p class="font-bold text-slate-700 text-xs mt-0.5" x-text="order.driver_name"></p>
                                    </div>
                                </div>

                                <!-- Items List -->
                                <div class="space-y-1.5">
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Item Pesanan (<span x-text="order.items ? order.items.length : 0"></span>)</p>
                                    <div class="max-h-28 overflow-y-auto custom-scrollbar space-y-1 pr-1 text-xs">
                                        <template x-for="item in order.items" :key="item.id">
                                            <div class="flex justify-between items-center text-slate-700">
                                                <span class="font-bold truncate flex-1" x-text="item.qty + 'x ' + item.item_name"></span>
                                                <span class="font-black text-slate-800 ml-2" x-text="'Rp ' + formatRupiah(item.subtotal)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Notes if exists -->
                                <template x-if="order.notes">
                                    <div class="bg-amber-50 border border-amber-200 text-amber-900 p-2 rounded-xl text-[11px] font-bold">
                                        <i class="fa-solid fa-sticky-note text-amber-500 mr-1"></i>
                                        <span x-text="order.notes"></span>
                                    </div>
                                </template>

                                <!-- Total Payment -->
                                <div class="pt-2 border-t border-slate-100 flex justify-between items-end">
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase">Metode Pembayaran</p>
                                        <span class="text-[10px] font-black bg-slate-100 text-slate-600 px-2 py-0.5 rounded border border-slate-200 uppercase" x-text="order.payment_method === 'app' ? 'Saldo Merchant (App)' : order.payment_method"></span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-black text-slate-400 uppercase">Total Tagihan</p>
                                        <p class="text-base font-black text-emerald-600" x-text="'Rp ' + formatRupiah(order.total_amount)"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Action Footer -->
                            <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                                
                                <button @click="openDetail(order)" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition-all text-xs font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-eye text-xs"></i> Detail
                                </button>

                                <div class="flex items-center gap-1.5">
                                    <!-- Dynamic Action Buttons depending on order status -->
                                    <template x-if="order.order_status === 'new'">
                                        <div class="flex items-center gap-1.5">
                                            <button @click="updateOrderStatus(order.id, 'cancelled', 'Dibatalkan')" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-3 py-1.5 rounded-xl text-xs font-black transition-all">
                                                Tolak
                                            </button>
                                            <button @click="updateOrderStatus(order.id, 'cooking', 'Proses Dapur')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl text-xs font-black shadow-sm transition-all flex items-center gap-1">
                                                <i class="fa-solid fa-check"></i> Terima & Dapur
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="order.order_status === 'cooking'">
                                        <button @click="updateOrderStatus(order.id, 'ready', 'Siap Pick-Up')" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-xl text-xs font-black shadow-sm transition-all flex items-center gap-1">
                                            <i class="fa-solid fa-motorcycle"></i> Siap Pick-Up
                                        </button>
                                    </template>

                                    <template x-if="order.order_status === 'ready'">
                                        <button @click="updateOrderStatus(order.id, 'completed', 'Selesai')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-xl text-xs font-black shadow-sm transition-all flex items-center gap-1">
                                            <i class="fa-solid fa-flag-checkered"></i> Driver Picked Up
                                        </button>
                                    </template>

                                    <!-- Print Receipt -->
                                    <button @click="printReceipt(order.invoice_no)" class="p-2 rounded-xl bg-slate-800 text-white hover:bg-slate-900 transition-all text-xs font-bold" title="Cetak Struk">
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL DETAIL PESANAN ONLINE -->
    <div x-show="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showDetailModal = false"></div>
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] overflow-hidden border border-slate-200">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-900 text-white">
                <div>
                    <h3 class="font-black text-base" x-text="'Rincian Pesanan #' + (selectedOrder ? (selectedOrder.external_order_id || selectedOrder.invoice_no) : '')"></h3>
                    <p class="text-xs text-slate-400 font-bold" x-text="selectedOrder ? selectedOrder.channel.toUpperCase() : ''"></p>
                </div>
                <button @click="showDetailModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <div class="p-5 flex-1 overflow-y-auto custom-scrollbar space-y-4 text-sm" x-if="selectedOrder">
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 space-y-1 text-xs">
                    <p><strong>Waktu Pesan:</strong> <span x-text="selectedOrder ? selectedOrder.created_at : ''"></span></p>
                    <p><strong>Pemesan:</strong> <span x-text="selectedOrder ? selectedOrder.customer_name : ''"></span></p>
                    <p><strong>Driver:</strong> <span x-text="selectedOrder ? (selectedOrder.driver_name || '-') : '-'"></span> (<span x-text="selectedOrder ? (selectedOrder.driver_phone || '-') : '-'"></span>)</p>
                    <p><strong>Status:</strong> <span class="font-black uppercase text-emerald-600" x-text="selectedOrder ? selectedOrder.order_status : ''"></span></p>
                </div>

                <div>
                    <h4 class="font-black text-slate-800 text-xs uppercase tracking-wider mb-2">Item Pesanan:</h4>
                    <div class="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden">
                        <template x-for="item in (selectedOrder ? selectedOrder.items : [])" :key="item.id">
                            <div class="p-3 flex justify-between items-center text-xs">
                                <div>
                                    <p class="font-black text-slate-800" x-text="item.item_name"></p>
                                    <p class="text-[10px] text-slate-400" x-text="item.qty + ' x Rp ' + formatRupiah(item.price)"></p>
                                </div>
                                <span class="font-black text-slate-800" x-text="'Rp ' + formatRupiah(item.subtotal)"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 space-y-1 text-xs">
                    <div class="flex justify-between"><span>Subtotal:</span> <span class="font-bold" x-text="'Rp ' + formatRupiah(selectedOrder ? selectedOrder.subtotal : 0)"></span></div>
                    <div class="flex justify-between"><span>Ongkos Kirim:</span> <span class="font-bold" x-text="'Rp ' + formatRupiah(selectedOrder ? selectedOrder.shipping_cost : 0)"></span></div>
                    <div class="flex justify-between text-base font-black text-emerald-600 pt-2 border-t border-slate-200"><span>Total:</span> <span x-text="'Rp ' + formatRupiah(selectedOrder ? selectedOrder.total_amount : 0)"></span></div>
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-2">
                <button @click="printReceipt(selectedOrder.invoice_no)" class="flex-1 bg-slate-900 text-white font-black py-3 rounded-xl hover:bg-slate-800 transition-all text-xs">
                    <i class="fa-solid fa-print mr-1"></i> Cetak Struk
                </button>
                <button @click="showDetailModal = false" class="px-5 bg-white border border-slate-200 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-100 transition-all text-xs">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL INPUT PESANAN MANUAL -->
    <div x-show="showManualModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showManualModal = false"></div>
        <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl relative z-10 flex flex-col h-[85vh] overflow-hidden border border-slate-200">
            
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-900 text-white">
                <h3 class="font-black text-base"><i class="fa-solid fa-cart-plus mr-2 text-emerald-400"></i>Input Pesanan Online Manual</h3>
                <button @click="showManualModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <div class="flex-1 flex flex-col md:flex-row overflow-hidden p-4 gap-4 bg-slate-50">
                <!-- Katalog Produk Manual -->
                <div class="flex-1 flex flex-col bg-white rounded-2xl border border-slate-200 p-3 overflow-hidden">
                    <div class="mb-3">
                        <input type="text" x-model="manualSearch" placeholder="Cari produk..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold">
                    </div>
                    <div class="flex-1 overflow-y-auto custom-scrollbar grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <template x-for="p in filteredProducts" :key="p.id">
                            <div @click="addToCart(p)" class="p-2 bg-slate-50 border border-slate-200 rounded-xl hover:border-emerald-500 cursor-pointer transition-all">
                                <p class="font-bold text-xs text-slate-800 line-clamp-1" x-text="p.name"></p>
                                <p class="font-black text-emerald-600 text-xs mt-1" x-text="'Rp ' + formatRupiah(calculateMarkupPrice(p))"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Keranjang Checkout -->
                <div class="w-full md:w-80 flex flex-col bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shrink-0">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Channel Pesanan</label>
                        <select x-model="manualChannel" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-bold">
                            <option value="wa_delivery">WA / Web Delivery</option>
                            <option value="grab">GrabFood</option>
                            <option value="gojek">GoFood</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nama Pemesan</label>
                        <input type="text" x-model="customerName" placeholder="Nama Pelanggan..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-bold">
                    </div>

                    <!-- Cart items -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar space-y-1.5 max-h-36 border-t border-b border-slate-100 py-2">
                        <template x-for="(item, idx) in cart" :key="idx">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-bold truncate flex-1" x-text="item.qty + 'x ' + item.name"></span>
                                <span class="font-black ml-2" x-text="'Rp ' + formatRupiah(item.subtotal)"></span>
                                <button @click="removeItem(idx)" class="ml-2 text-rose-500"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </template>
                    </div>

                    <div class="pt-2 border-t border-slate-100 text-xs space-y-1">
                        <div class="flex justify-between font-bold text-slate-600"><span>Total:</span> <span class="text-base font-black text-emerald-600" x-text="'Rp ' + formatRupiah(totalAmount)"></span></div>
                    </div>

                    <button @click="processManualCheckout()" :disabled="cart.length === 0" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 rounded-xl transition-all disabled:opacity-50 text-xs">
                        PROSES PESANAN ONLINE
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>