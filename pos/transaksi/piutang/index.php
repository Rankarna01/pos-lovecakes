<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Pelunasan DP (Piutang) - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="piutangApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-hand-holding-dollar mr-2"></i>Pelunasan DP & Piutang</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="fetchData()" :disabled="isLoading" class="bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 disabled:opacity-50 border border-white/10 shadow-sm">
                    <i class="fa-solid fa-rotate" :class="isLoading ? 'fa-spin' : ''"></i> 
                    <span class="hidden sm:inline" x-text="isLoading ? 'Memuat...' : 'Refresh Data'"></span>
                </button>
                <div class="border-l border-blue-400 pl-3 ml-1">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm shadow-rose-500/30" title="Keluar">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div class="w-full max-w-full space-y-6">
                
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3 sticky top-0 z-10">
                    <div class="relative flex-1 min-w-[250px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" @input.debounce.500ms="fetchData()" placeholder="Cari No. Invoice atau Nama Pelanggan..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold text-sm transition-all text-slate-700">
                    </div>
                    
                    <select x-model="timeRange" @change="fetchData()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold text-sm text-slate-600">
                        <option value="">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="week">Minggu Ini</option>
                        <option value="month">Bulan Ini</option>
                    </select>
                </div>

                <div x-show="isLoading" class="text-center py-20 flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-500 font-bold tracking-widest uppercase text-sm">Memuat Data Tagihan...</p>
                </div>

                <div x-show="!isLoading" class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap min-w-[1000px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Invoice & Waktu</th>
                                    <th class="p-4 font-black">Pelanggan</th>
                                    <th class="p-4 font-black text-right">Total Tagihan</th>
                                    <th class="p-4 font-black text-right">Sudah Dibayar (DP)</th>
                                    <th class="p-4 font-black text-right text-rose-500">Sisa Tagihan</th>
                                    <th class="p-4 font-black text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="transactions.length === 0">
                                    <td colspan="6" class="p-16 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-check-circle text-5xl mb-4 text-emerald-300 block"></i> 
                                        Hore! Tidak ada pelanggan yang berhutang (menunggak DP).
                                    </td>
                                </tr>
                                <template x-for="trx in transactions" :key="trx.id">
                                    <tr class="hover:bg-amber-50/30 transition-colors">
                                        <td class="p-4">
                                            <div class="font-black text-slate-800" x-text="trx.invoice_no"></div>
                                            <div class="text-[10px] font-bold text-slate-400 mt-1" x-text="formatDate(trx.created_at)"></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-black text-slate-700" x-text="trx.customer_name || 'Pelanggan Umum'"></div>
                                            <div class="text-[10px] font-bold text-slate-400 mt-1" x-text="trx.phone || '-'"></div>
                                        </td>
                                        <td class="p-4 text-right font-bold text-slate-600" x-text="'Rp ' + formatRupiah(trx.total_amount)"></td>
                                        <td class="p-4 text-right font-black text-emerald-600" x-text="'Rp ' + formatRupiah(trx.dp_amount)"></td>
                                        <td class="p-4 text-right font-black text-rose-600 text-base" x-text="'Rp ' + formatRupiah(trx.total_amount - trx.dp_amount)"></td>
                                        <td class="p-4 text-center">
                                            <button @click="openModal(trx)" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center justify-center gap-2 mx-auto">
                                                <i class="fa-solid fa-cash-register"></i> Lunasi
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
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col m-4 transform transition-all overflow-hidden border border-slate-200">
                
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800 flex items-center gap-2"><i class="fa-solid fa-file-invoice text-primary"></i> Proses Pelunasan</h3>
                    <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 bg-white space-y-5">
                    
                    <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Pelanggan</span>
                            <span class="text-sm font-black text-slate-800" x-text="activeTrx?.customer_name"></span>
                        </div>
                        <div class="flex justify-between items-center mb-2 border-t border-amber-200/50 pt-2">
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Total Tagihan</span>
                            <span class="text-sm font-bold text-slate-700" x-text="'Rp ' + formatRupiah(activeTrx?.total_amount)"></span>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Sudah Dibayar (DP)</span>
                            <span class="text-sm font-bold text-emerald-600" x-text="'- Rp ' + formatRupiah(activeTrx?.dp_amount)"></span>
                        </div>
                        <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-amber-200">
                            <span class="text-xs font-black text-rose-500 uppercase tracking-widest">Sisa Harus Dibayar</span>
                            <span class="text-2xl font-black text-rose-600" x-text="'Rp ' + formatRupiah(sisaTagihan)"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 max-h-40 overflow-y-auto custom-scrollbar">
                        <template x-for="item in paymentMethods" :key="item.id">
                            <button type="button" @click="payMethod = item.name" :class="payMethod === item.name ? 'bg-blue-50 border-blue-500 text-blue-700 ring-1 ring-blue-500' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50'" class="py-2 px-1 rounded-xl font-black text-xs border shadow-sm transition-all flex flex-col items-center justify-center gap-1">
                                <i :class="item.type === 'Cash' ? 'fa-solid fa-money-bill-wave text-emerald-500' : (item.type === 'QRIS' ? 'fa-solid fa-qrcode text-blue-500' : (item.type === 'Debit' ? 'fa-solid fa-credit-card text-indigo-500' : 'fa-solid fa-wallet text-slate-500'))"></i>
                                <span x-text="item.name" class="text-[10px]"></span>
                            </button>
                        </template>
                    </div>

                    <div x-show="paymentMethods.find(m => m.name === payMethod)?.type === 'Cash'">
                        <label class="block text-[10px] font-black text-slate-500 mb-1.5 uppercase">Jumlah Uang Diterima (Rp)</label>
                        <input type="number" x-model="payAmount" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-4 text-center font-black text-2xl outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary text-slate-800" autofocus>
                    </div>

                    <div x-show="paymentMethods.find(m => m.name === payMethod)?.type === 'Cash' && kembalian >= 0" class="flex justify-between items-center p-3 rounded-xl bg-emerald-50 border border-emerald-200">
                        <span class="text-xs font-black text-emerald-700 uppercase tracking-widest">Kembalian</span>
                        <span class="text-xl font-black text-emerald-600" x-text="'Rp ' + formatRupiah(kembalian)"></span>
                    </div>

                    <button @click="processSettlement()" :disabled="isSubmitting || (paymentMethods.find(m => m.name === payMethod)?.type === 'Cash' && payAmount < sisaTagihan)" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-4 rounded-xl shadow-lg shadow-slate-800/30 transition-all flex justify-center items-center gap-2 disabled:opacity-50">
                        <i class="fa-solid fa-check-circle" :class="isSubmitting ? 'fa-spin' : ''"></i> LUNASI SEKARANG
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>