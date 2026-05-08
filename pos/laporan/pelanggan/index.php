<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Laporan Pelanggan - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="pelangganApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-users mr-2"></i>Laporan Pelanggan</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="fetchData(true)" :disabled="isSyncing" class="bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 disabled:opacity-50 border border-white/10 shadow-sm">
                    <i class="fa-solid fa-rotate" :class="isSyncing ? 'fa-spin' : ''"></i> 
                    <span class="hidden sm:inline" x-text="isSyncing ? 'Menyinkronkan...' : 'Sync Laporan'"></span>
                </button>

                <div class="border-l border-blue-400 pl-3 ml-1">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm shadow-rose-500/30" title="Keluar">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row justify-between items-center sticky top-0 z-10 gap-3">
                    <div class="relative w-full sm:w-1/2 md:w-1/3">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama pelanggan..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold text-sm transition-all text-slate-700">
                    </div>
                </div>

                <div x-show="isLoading" class="text-center py-20 flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-500 font-bold tracking-widest uppercase text-sm">Menarik Data Pelanggan...</p>
                </div>

                <div x-show="!isLoading" class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap min-w-[800px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black text-center w-16">Rank</th>
                                    <th class="p-4 font-black">Nama Pelanggan</th>
                                    <th class="p-4 font-black text-center">Poin Aktif</th>
                                    <th class="p-4 font-black text-center">Total Kunjungan</th>
                                    <th class="p-4 font-black text-right">Total Nominal Belanja</th>
                                    <th class="p-4 font-black text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="filteredCustomers.length === 0">
                                    <td colspan="6" class="p-10 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-folder-open text-4xl mb-3 opacity-50 block"></i> Tidak ada data pelanggan.
                                    </td>
                                </tr>
                                <template x-for="(cust, index) in filteredCustomers" :key="cust.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 text-center font-black text-slate-400">#<span x-text="index + 1"></span></td>
                                        <td class="p-4">
                                            <div class="font-black text-slate-800" x-text="cust.name"></div>
                                            <div class="text-[10px] font-bold text-slate-400 mt-1" x-text="cust.phone || '-'"></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="bg-amber-50 text-amber-600 px-3 py-1 rounded-lg text-xs font-black border border-amber-100"><i class="fa-solid fa-star mr-1"></i> <span x-text="cust.points"></span></span>
                                        </td>
                                        <td class="p-4 text-center font-black text-slate-600" x-text="cust.total_trx + 'x'"></td>
                                        <td class="p-4 text-right font-black text-primary" x-text="'Rp ' + formatRupiah(cust.total_spent)"></td>
                                        <td class="p-4 text-center">
                                            <button @click="openDetail(cust)" class="bg-slate-100 hover:bg-primary hover:text-white text-slate-600 px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center justify-center gap-2 mx-auto">
                                                <i class="fa-solid fa-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="h-10"></div>
            </div>
        </main>

        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] m-4 transform transition-all overflow-hidden border border-slate-200">
                
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-primary/10 text-primary rounded-full flex items-center justify-center text-xl font-black"><i class="fa-solid fa-user"></i></div>
                        <div>
                            <h3 class="font-black text-lg text-slate-800" x-text="activeCustomer?.name"></h3>
                            <p class="text-xs font-bold text-slate-500" x-text="activeCustomer?.phone || 'No. HP Belum Diatur'"></p>
                        </div>
                    </div>
                    <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar p-5 bg-white">
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-amber-50 border border-amber-100 p-4 rounded-2xl flex flex-col items-center justify-center">
                            <i class="fa-solid fa-star text-amber-400 text-3xl mb-2"></i>
                            <span class="text-[10px] font-black uppercase text-amber-600 tracking-widest">Sisa Poin Aktif</span>
                            <span class="text-2xl font-black text-amber-600" x-text="activeCustomer?.points"></span>
                        </div>
                        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex flex-col items-center justify-center">
                            <i class="fa-solid fa-sack-dollar text-emerald-400 text-3xl mb-2"></i>
                            <span class="text-[10px] font-black uppercase text-emerald-600 tracking-widest">Total Belanja</span>
                            <span class="text-2xl font-black text-emerald-600" x-text="'Rp ' + formatRupiah(activeCustomer?.total_spent)"></span>
                        </div>
                    </div>

                    <h4 class="font-black text-slate-700 uppercase text-xs tracking-widest mb-3 flex items-center gap-2"><i class="fa-solid fa-clock-rotate-left"></i> Histori Transaksi</h4>
                    
                    <div x-show="isDetailLoading" class="text-center py-10 text-slate-400">
                        <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2"></i>
                        <p class="text-xs font-bold">Memuat histori...</p>
                    </div>

                    <div x-show="!isDetailLoading && activeHistory.length === 0" class="text-center py-10 text-slate-400 bg-slate-50 rounded-2xl border border-slate-100">
                        <p class="text-xs font-bold">Pelanggan ini belum memiliki riwayat transaksi.</p>
                    </div>

                    <div x-show="!isDetailLoading && activeHistory.length > 0" class="space-y-4">
                        <template x-for="(trx, index) in activeHistory" :key="index">
                            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                                <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-3">
                                    <div>
                                        <div class="font-black text-slate-800" x-text="trx.invoice_no"></div>
                                        <div class="text-[10px] font-bold text-slate-400 mt-1" x-text="formatDate(trx.created_at)"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-black text-primary text-base" x-text="'Rp ' + formatRupiah(trx.total_amount)"></div>
                                        <div class="text-[10px] font-bold uppercase mt-1">
                                            <span class="text-slate-500 border px-2 py-0.5 rounded" x-text="trx.payment_method"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <template x-for="item in trx.items" :key="item.product_name">
                                        <div class="flex justify-between text-xs font-bold text-slate-600">
                                            <div><span x-text="item.qty + 'x '"></span> <span x-text="item.product_name"></span></div>
                                            <div x-text="'Rp ' + formatRupiah(item.subtotal)"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>