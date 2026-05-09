<div x-show="showStatusModal" class="fixed inset-0 z-[60] flex items-center justify-center" style="display: none;" x-cloak>
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showStatusModal = false"></div>
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl relative z-10 flex flex-col h-[85vh] m-4 transform transition-all overflow-hidden">
        
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <div>
                <h3 class="font-black text-lg text-slate-800 flex items-center gap-2"><i class="fa-solid fa-fire-burner text-orange-500"></i> Pantau Pesanan Dapur & PO</h3>
                <p class="text-xs font-bold text-slate-500 mt-1">Daftar pesanan dengan notifikasi otomatis untuk pengambilan terdekat.</p>
            </div>
            <button @click="showStatusModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors shrink-0"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div class="p-4 overflow-y-auto custom-scrollbar flex-1 bg-slate-100/50">
            <div x-show="isFetchingStatus" class="p-10 text-center"><i class="fa-solid fa-circle-notch fa-spin text-4xl text-blue-500"></i></div>
            
            <div x-show="!isFetchingStatus && activeOrders.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                <i class="fa-solid fa-clipboard-check text-6xl text-slate-300 mb-4"></i>
                <p class="font-black text-lg text-slate-600">Belum Ada Pesanan Dapur/PO</p>
            </div>
            
            <div x-show="!isFetchingStatus && activeOrders.length > 0" class="space-y-3">
                <template x-for="order in activeOrders" :key="order.id">
                    <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm relative overflow-hidden" :class="order.alert_type === 'today' ? 'border-rose-300 bg-rose-50/30' : (order.alert_type === 'tomorrow' ? 'border-amber-300 bg-amber-50/30' : '')">
                        
                        <div x-show="order.alert_type === 'today'" class="absolute top-0 right-0 bg-rose-500 text-white text-[9px] font-black px-3 py-1 rounded-bl-lg shadow-sm animate-pulse">AMBIL HARI INI!</div>
                        <div x-show="order.alert_type === 'tomorrow'" class="absolute top-0 right-0 bg-amber-500 text-white text-[9px] font-black px-3 py-1 rounded-bl-lg shadow-sm">AMBIL BESOK (H-1)</div>

                        <div class="flex flex-col sm:flex-row justify-between items-start gap-3 mt-2">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="font-black text-slate-800 text-lg leading-none" x-text="order.invoice_no"></h4>
                                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-black uppercase" x-text="order.channel"></span>
                                </div>
                                <p class="text-xs font-bold text-slate-600 mb-2"><i class="fa-solid fa-user mr-1 text-slate-400"></i> <span x-text="order.customer_name || 'Pelanggan Umum'"></span></p>
                                <div class="text-xs font-bold text-slate-700 mb-2">
                                    <i class="fa-regular fa-calendar text-orange-500 mr-1"></i> Tgl Ambil: <span x-text="order.pickup_date + ' Jam ' + order.pickup_time"></span>
                                </div>
                                <div class="text-xs font-bold text-slate-600 bg-orange-50 border border-orange-100 px-3 py-2 rounded-xl">
                                    <span class="text-orange-500 mr-1"><i class="fa-solid fa-cake-candles"></i></span> <span x-text="order.items_list"></span>
                                </div>
                            </div>
                            <div class="flex shrink-0">
                                <template x-if="order.production_status === 'pending'"><span class="bg-rose-100 text-rose-600 px-4 py-2 rounded-xl text-xs font-black border border-rose-200"><i class="fa-solid fa-hourglass-half mr-1"></i> Menunggu Dapur</span></template>
                                <template x-if="order.production_status === 'diproses'"><span class="bg-orange-500 text-white px-4 py-2 rounded-xl text-xs font-black shadow-md"><i class="fa-solid fa-fire mr-1"></i> Sedang Dimasak</span></template>
                                <template x-if="order.production_status === 'selesai'"><span class="bg-emerald-500 text-white px-4 py-2 rounded-xl text-xs font-black shadow-md"><i class="fa-solid fa-check-double mr-1"></i> Siap Diambil</span></template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <div class="p-4 border-t border-slate-100 bg-white flex justify-end shrink-0">
            <button @click="openStatusModal()" class="text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-xl text-xs font-black transition-colors"><i class="fa-solid fa-rotate-right"></i> Perbarui Data</button>
        </div>
    </div>
</div>