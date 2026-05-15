<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Laporan Shift - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="shiftReportApp()" x-cloak>

    <?php include '../../../components/sidebar_kasir.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-clock-rotate-left mr-2"></i>Laporan Shift Kasir</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="max-w-7xl mx-auto space-y-4">
                
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-col md:flex-row gap-3">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="filters.search" @input.debounce.500ms="applyFilter()" placeholder="Cari Nama Kasir..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:bg-white focus:border-primary font-bold text-sm">
                    </div>
                    <button @click="applyFilter()" class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden relative">
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-sm">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Waktu Buka</th>
                                    <th class="p-4 font-black">Waktu Tutup</th>
                                    <th class="p-4 font-black">Identitas</th>
                                    <th class="p-4 font-black text-right">Saldo Sistem</th>
                                    <th class="p-4 font-black text-right">Fisik (End Cash)</th>
                                    <th class="p-4 font-black text-center">Status</th>
                                    <th class="p-4 font-black text-center"><i class="fa-solid fa-bars"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="shifts.length === 0">
                                    <td colspan="7" class="p-10 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-folder-open text-4xl mb-3 opacity-50 block"></i> Tidak ada riwayat shift ditemukan.
                                    </td>
                                </tr>
                                <template x-for="shift in shifts" :key="shift.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 font-bold text-slate-600" x-text="formatDate(shift.start_time)"></td>
                                        <td class="p-4 font-bold text-slate-600">
                                            <span x-text="shift.end_time ? formatDate(shift.end_time) : '-'" :class="!shift.end_time ? 'text-slate-300' : ''"></span>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-black text-slate-800" x-text="shift.cashier_name"></div>
                                            <div class="text-[10px] text-slate-500 font-bold">Shift ID: #<span x-text="shift.id"></span></div>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="font-black text-blue-600" x-text="'Rp ' + formatRupiah(shift.system_balance)"></div>
                                            <div class="text-[10px] text-slate-400 font-bold">Modal + Cash Masuk - Keluar</div>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="font-black" :class="shift.status === 'open' ? 'text-slate-300' : (shift.difference < 0 ? 'text-rose-600' : (shift.difference > 0 ? 'text-emerald-600' : 'text-slate-800'))" x-text="shift.status === 'open' ? '-' : 'Rp ' + formatRupiah(shift.end_cash)"></div>
                                            <div x-show="shift.status === 'closed'" class="text-[10px] font-bold mt-0.5" :class="shift.difference < 0 ? 'text-rose-500' : (shift.difference > 0 ? 'text-emerald-500' : 'text-slate-400')">
                                                <span x-text="shift.difference < 0 ? 'Minus ' : (shift.difference > 0 ? 'Lebih ' : 'Pas ')"></span>
                                                <span x-text="'Rp ' + formatRupiah(Math.abs(shift.difference))"></span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase border" 
                                                  :class="shift.status === 'closed' ? 'bg-slate-100 text-slate-500 border-slate-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200'">
                                                <span x-text="shift.status === 'open' ? 'SEDANG JALAN' : 'DITUTUP'"></span>
                                            </span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <button @click="openDetail(shift)" class="bg-slate-100 hover:bg-slate-200 text-slate-600 w-8 h-8 rounded-lg flex items-center justify-center transition-colors" title="Lihat Detail">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
                        <span class="text-xs text-slate-500 font-bold" x-text="'Halaman ' + currentPage + ' dari ' + totalPages"></span>
                        <div class="flex gap-2">
                            <button @click="prevPage()" :disabled="currentPage <= 1" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-100 disabled:opacity-50 transition-colors shadow-sm">
                                <i class="fa-solid fa-chevron-left mr-1"></i> Sebelumnya
                            </button>
                            <button @click="nextPage()" :disabled="currentPage >= totalPages" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-100 disabled:opacity-50 transition-colors shadow-sm">
                                Selanjutnya <i class="fa-solid fa-chevron-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL DETAIL SHIFT -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] m-4 transform transition-all overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800">Detail Sesi Kasir</h3>
                    <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-white" x-show="activeShift">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- RINGKASAN WAKTU -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-200 pb-2">Informasi Shift</h4>
                            <div class="space-y-2 text-sm font-bold text-slate-600">
                                <div class="flex justify-between"><span>Kasir</span><span class="text-slate-800 font-black" x-text="activeShift?.cashier_name"></span></div>
                                <div class="flex justify-between"><span>Buka</span><span x-text="formatDate(activeShift?.start_time)"></span></div>
                                <div class="flex justify-between"><span>Tutup</span><span x-text="activeShift?.end_time ? formatDate(activeShift?.end_time) : 'Belum Ditutup'"></span></div>
                                <div class="flex justify-between"><span>Status</span><span class="uppercase" :class="activeShift?.status === 'closed' ? 'text-slate-500' : 'text-emerald-500'" x-text="activeShift?.status === 'open' ? 'Sedang Jalan' : 'Selesai'"></span></div>
                            </div>
                        </div>

                        <!-- RINGKASAN SALDO -->
                        <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100">
                            <h4 class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-3 border-b border-blue-200/50 pb-2">Rekapitulasi Saldo Laci (Tunai)</h4>
                            <div class="space-y-2 text-sm font-bold text-slate-600">
                                <div class="flex justify-between"><span>Modal Awal (Tunai)</span><span x-text="'Rp ' + formatRupiah(activeShift?.start_cash)"></span></div>
                                <div class="flex justify-between text-emerald-600"><span>Pendapatan Tunai</span><span x-text="'+ Rp ' + formatRupiah(activeShift?.total_cash_sales)"></span></div>
                                <div class="flex justify-between text-rose-600"><span>Kas Keluar (Tunai)</span><span x-text="'- Rp ' + formatRupiah(activeShift?.total_kas_keluar)"></span></div>
                                <div class="flex justify-between font-black text-blue-700 border-t border-blue-200 pt-2 mt-2">
                                    <span>Saldo Sistem (Harusnya)</span> <span x-text="'Rp ' + formatRupiah(activeShift?.system_balance)"></span>
                                </div>
                                <div x-show="activeShift?.status === 'closed'" class="flex justify-between font-black text-slate-800 bg-white p-2 rounded-lg mt-2 border border-slate-200">
                                    <span>Saldo Aktual (Dihitung)</span> <span x-text="'Rp ' + formatRupiah(activeShift?.end_cash)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TABEL TRANSAKSI -->
                    <div class="mb-6">
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="fa-solid fa-receipt text-primary"></i> Transaksi Penjualan (<span x-text="activeTransactions.length"></span>)</h4>
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left border-collapse whitespace-nowrap text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider">
                                        <th class="p-3 font-bold">Waktu</th>
                                        <th class="p-3 font-bold">No. Invoice</th>
                                        <th class="p-3 font-bold">Metode</th>
                                        <th class="p-3 font-bold text-right">Cash Diterima</th>
                                        <th class="p-3 font-bold text-right">Total Transaksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr x-show="activeTransactions.length === 0">
                                        <td colspan="5" class="p-4 text-center text-slate-400 font-medium">Belum ada transaksi di shift ini.</td>
                                    </tr>
                                    <template x-for="trx in activeTransactions" :key="trx.id">
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-3 text-slate-600" x-text="new Date(trx.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})"></td>
                                            <td class="p-3 font-bold text-slate-700" x-text="trx.invoice_no"></td>
                                            <td class="p-3"><span class="px-2 py-1 rounded bg-slate-100 text-[9px] font-black uppercase" x-text="trx.payment_method"></span></td>
                                            <td class="p-3 text-right text-emerald-600 font-bold" x-text="trx.payment_method === 'cash' ? 'Rp ' + formatRupiah(trx.amount_paid - trx.change_amount) : '-'"></td>
                                            <td class="p-3 text-right font-black text-slate-800" x-text="'Rp ' + formatRupiah(trx.total_amount)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TABEL KAS KELUAR -->
                    <div>
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="fa-solid fa-money-bill-transfer text-amber-500"></i> Riwayat Kas Keluar (<span x-text="activePettyCash.length"></span>)</h4>
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left border-collapse whitespace-nowrap text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider">
                                        <th class="p-3 font-bold">Waktu</th>
                                        <th class="p-3 font-bold">Keterangan</th>
                                        <th class="p-3 font-bold text-right">Nominal Pengeluaran</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr x-show="activePettyCash.length === 0">
                                        <td colspan="3" class="p-4 text-center text-slate-400 font-medium">Tidak ada kas keluar di shift ini.</td>
                                    </tr>
                                    <template x-for="kas in activePettyCash" :key="kas.id">
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-3 text-slate-600" x-text="new Date(kas.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})"></td>
                                            <td class="p-3 font-bold text-slate-700 whitespace-normal" x-text="kas.keterangan"></td>
                                            <td class="p-3 text-right text-rose-600 font-black" x-text="'Rp ' + formatRupiah(kas.nominal)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
