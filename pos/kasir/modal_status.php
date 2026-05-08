<div x-show="showStatusModal" class="fixed inset-0 z-[60] flex items-center justify-center" style="display: none;" x-cloak>
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showStatusModal = false"></div>
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative z-10 flex flex-col h-[80vh] m-4 transform transition-all overflow-hidden">
        
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <div>
                <h3 class="font-black text-lg text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-fire-burner text-orange-500"></i> Pantau Pesanan Dapur
                </h3>
                <p class="text-xs font-bold text-slate-500 mt-1">Daftar pesanan custom hari ini beserta status dari dapur.</p>
            </div>
            <button @click="showStatusModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors shrink-0">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <div class="p-4 overflow-y-auto custom-scrollbar flex-1 bg-slate-100/50">
            <div x-show="isFetchingStatus" class="p-10 text-center">
                 <i class="fa-solid fa-circle-notch fa-spin text-4xl text-blue-500 mb-2"></i>
                 <p class="text-sm font-bold text-slate-400">Sinkronisasi dengan dapur...</p>
            </div>
            
            <div x-show="!isFetchingStatus && activeOrders.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                <i class="fa-solid fa-clipboard-check text-6xl text-slate-300 mb-4"></i>
                <p class="font-black text-lg text-slate-600">Belum Ada Pesanan Custom</p>
                <p class="text-sm font-bold text-slate-400">Pesanan custom yang kamu input akan muncul di sini.</p>
            </div>
            
            <div x-show="!isFetchingStatus && activeOrders.length > 0" class="space-y-3">
                <template x-for="order in activeOrders" :key="order.id">
                    <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition-all hover:border-blue-300">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-black text-slate-800 text-lg leading-none" x-text="'#' + order.invoice_no.split('-')[1]"></h4>
                                <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded text-[10px] font-black"><i class="fa-regular fa-clock"></i> <span x-text="order.time"></span></span>
                            </div>
                            <p class="text-xs font-bold text-slate-500 mb-2"><i class="fa-solid fa-user mr-1 text-slate-400"></i> <span x-text="order.customer_name || 'Pelanggan Umum'"></span></p>
                            
                            <div class="text-xs font-bold text-slate-600 bg-amber-50 border border-amber-100 px-3 py-2 rounded-xl">
                                <span class="text-amber-500 mr-1"><i class="fa-solid fa-cake-candles"></i></span> 
                                <span x-text="order.items_list"></span>
                            </div>
                        </div>
                        <div class="flex shrink-0">
                            <template x-if="order.production_status === 'pending'">
                                <span class="bg-rose-100 text-rose-600 px-4 py-2 rounded-xl text-xs font-black border border-rose-200 shadow-sm"><i class="fa-solid fa-hourglass-half mr-1"></i> Menunggu Dapur</span>
                            </template>
                            <template x-if="order.production_status === 'diproses'">
                                <span class="bg-orange-500 text-white px-4 py-2 rounded-xl text-xs font-black shadow-md shadow-orange-500/30 animate-pulse"><i class="fa-solid fa-fire mr-1"></i> Sedang Dimasak</span>
                            </template>
                            <template x-if="order.production_status === 'selesai'">
                                <span class="bg-emerald-500 text-white px-4 py-2 rounded-xl text-xs font-black shadow-md shadow-emerald-500/30"><i class="fa-solid fa-check-double mr-1"></i> Siap Diambil</span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 bg-white flex justify-end shrink-0">
            <button @click="openStatusModal()" class="text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-xl text-xs font-black transition-colors flex items-center gap-2">
                <i class="fa-solid fa-rotate-right"></i> Perbarui Data
            </button>
        </div>
    </div>
</div>