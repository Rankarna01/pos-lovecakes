<?php
require_once '../../config/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
</head>
<body class="bg-slate-100 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="posOnlineApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden no-print">
        
        <header class="bg-amber-500 text-white shadow-md px-4 py-3 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-amber-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-motorcycle mr-2"></i>Kasir Online (Delivery)</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/20 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 border border-white/30 shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span> Mode Online
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-hidden flex flex-col lg:flex-row p-3 gap-3">
            
            <div class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
                <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-sm">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-amber-500"></i>
                </div>

                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3 bg-amber-50/30">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari pesanan online..." class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-amber-500/20 font-bold text-sm">
                    </div>
                    
                    <div class="flex gap-2 bg-slate-100 p-1.5 rounded-xl overflow-x-auto custom-scrollbar">
                        <button @click="setChannel('wa_delivery')" :class="channel === 'wa_delivery' ? 'bg-white shadow-sm text-blue-500 font-black border border-slate-200' : 'text-slate-500 font-bold'" class="px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap"><i class="fa-brands fa-whatsapp"></i> WA / Web</button>
                        <button @click="setChannel('grab')" :class="channel === 'grab' ? 'bg-white shadow-sm text-emerald-600 font-black border border-emerald-200' : 'text-slate-500 font-bold'" class="px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap"><i class="fa-solid fa-bag-shopping"></i> GrabFood</button>
                        <button @click="setChannel('gojek')" :class="channel === 'gojek' ? 'bg-white shadow-sm text-rose-500 font-black border border-rose-200' : 'text-slate-500 font-bold'" class="px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap"><i class="fa-solid fa-motorcycle"></i> GoFood</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        <template x-for="item in filteredProducts" :key="item.id">
                            <div @click="addToCart(item)" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:border-amber-500/50 hover:shadow-md transition-all cursor-pointer group flex flex-col h-full active:scale-95">
                                <div class="relative pt-[80%] bg-slate-100 overflow-hidden">
                                    <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-300 bg-slate-100 z-0"><i class="fa-solid fa-cake-candles text-4xl mb-2"></i></div>
                                    <img :src="item.image && item.image !== 'no-image.png' ? 'http://localhost/sim-produksi-kue/assets/img/' + item.image : ''" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 z-10" @error="$el.style.display='none'">
                                </div>
                                <div class="p-3 flex flex-col flex-1 bg-white z-10">
                                    <h3 class="font-bold text-xs sm:text-sm text-slate-800 leading-tight mb-2 line-clamp-2" x-text="item.name"></h3>
                                    <div class="mt-auto flex justify-between items-end">
                                        <div class="font-black text-amber-600 text-sm sm:text-base" x-text="'Rp ' + formatRupiah(calculateMarkupPrice(item))"></div>
                                        <div x-show="channel !== 'wa_delivery'" class="text-[9px] font-bold text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">Markup</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-[400px] flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden shrink-0">
                
                <div class="p-4 border-b border-slate-100 bg-slate-50 space-y-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 mb-1.5 uppercase">Pemesan (Opsional)</label>
                        <input type="text" x-model="customerName" placeholder="Nama Pelanggan / Driver..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-amber-500 font-bold text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 mb-1.5 uppercase">Catatan Dapur / Alamat</label>
                        <input type="text" x-model="notes" placeholder="Misal: Pedas, Alamat Lengkap..." class="w-full bg-amber-50/50 border border-amber-200 rounded-xl px-3 py-2 outline-none focus:border-amber-500 font-bold text-sm text-amber-900">
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                    <div x-show="cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 space-y-3">
                        <i class="fa-solid fa-motorcycle text-5xl opacity-50"></i>
                        <p class="font-bold text-sm">Keranjang Online Kosong</p>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 p-2 rounded-xl">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-sm text-slate-800 truncate" x-text="item.name"></h4>
                                    <div class="text-xs font-black text-amber-600" x-text="'Rp ' + formatRupiah(item.price)"></div>
                                </div>
                                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg p-1">
                                    <button @click="updateQty(index, -1)" class="w-6 h-6 flex items-center justify-center rounded bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold">-</button>
                                    <span class="w-6 text-center font-black text-sm" x-text="item.qty"></span>
                                    <button @click="updateQty(index, 1)" class="w-6 h-6 flex items-center justify-center rounded bg-amber-500 text-white hover:bg-amber-600 font-bold">+</button>
                                </div>
                                <button @click="removeItem(index)" class="w-8 h-8 flex items-center justify-center text-rose-400 hover:text-rose-600 bg-rose-50 rounded-lg"><i class="fa-solid fa-trash-can text-xs"></i></button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 mb-1.5 uppercase">Biaya Ongkir / Lainnya</label>
                        <div class="flex items-center">
                            <span class="bg-slate-200 border border-slate-300 border-r-0 rounded-l-xl px-3 py-2 text-xs font-bold text-slate-600">Rp</span>
                            <input type="number" x-model="shippingCost" class="w-full bg-white border border-slate-300 rounded-r-xl px-3 py-2 outline-none focus:border-amber-500 font-black text-sm">
                        </div>
                    </div>

                    <div class="space-y-1.5 pt-2 border-t border-slate-200 border-dashed">
                        <div class="flex justify-between text-xs font-bold text-slate-500"><span>Subtotal Produk</span> <span x-text="'Rp ' + formatRupiah(subtotal)"></span></div>
                        <div x-show="shippingCost > 0" class="flex justify-between text-xs font-bold text-blue-500"><span>Ongkos Kirim</span> <span x-text="'+ Rp ' + formatRupiah(shippingCost)"></span></div>
                    </div>
                    
                    <div class="pt-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Total Tagihan Online</p>
                        <div class="text-3xl font-black text-amber-500 leading-none" x-text="'Rp ' + formatRupiah(totalAmount)"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-3">
                        <button @click="paymentMethod = 'app'" :class="paymentMethod === 'app' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-slate-500 border-slate-200'" class="py-2.5 rounded-xl font-black text-sm border shadow-sm transition-all flex items-center justify-center gap-2"><i class="fa-solid fa-mobile-screen"></i> Saldo App</button>
                        <button @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white text-slate-500 border-slate-200'" class="py-2.5 rounded-xl font-black text-sm border shadow-sm transition-all flex items-center justify-center gap-2"><i class="fa-solid fa-money-bill-wave"></i> Cash/COD</button>
                    </div>

                    <button @click="processCheckout()" :disabled="cart.length === 0" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-4 rounded-xl shadow-lg transition-all flex justify-center items-center gap-2 text-lg disabled:opacity-50 mt-2">
                        PROSES PESANAN <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>