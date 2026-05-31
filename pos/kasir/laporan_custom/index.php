<?php
require_once '../../../config/auth.php';
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder_pos = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder_pos); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/head.php'; ?>
    <script>const BASE_URL = "<?= BASE_URL ?>";</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <title>Laporan Item Custom — Love Cakes</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 antialiased" x-data="laporanCustom()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-h-screen">

        <!-- HEADER -->
        <header class="bg-white border-b border-slate-200 shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-20">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="text-slate-500 hover:text-slate-800 p-2 rounded-lg hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div>
                    <h1 class="text-xl font-black text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square text-violet-600"></i> Laporan Item Custom
                    </h1>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Rekap siapa yang membuat item custom & detail transaksinya</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>pos/kasir/" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-black transition-all">
                    <i class="fa-solid fa-cash-register"></i> Kembali ke Kasir
                </a>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-6">

            <!-- FILTER -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Dari Tanggal</label>
                        <input type="date" x-model="filter.date_from"
                            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none font-bold text-sm text-slate-700 focus:border-violet-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Sampai Tanggal</label>
                        <input type="date" x-model="filter.date_to"
                            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none font-bold text-sm text-slate-700 focus:border-violet-500">
                    </div>
                    <button @click="loadData()" :disabled="isLoading"
                        class="flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl text-sm font-black transition-all shadow-sm shadow-violet-500/20 disabled:opacity-60">
                        <i class="fa-solid fa-magnifying-glass" :class="isLoading ? 'fa-spin' : ''"></i>
                        <span x-text="isLoading ? 'Memuat...' : 'Tampilkan'"></span>
                    </button>
                    <button @click="printPage()"
                        class="flex items-center gap-2 bg-slate-700 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-sm font-black transition-all">
                        <i class="fa-solid fa-print"></i> Cetak
                    </button>
                </div>
            </div>

            <!-- REKAP CARDS: per kasir -->
            <div x-show="rekapKasir.length > 0">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Rekap per Kasir</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <template x-for="k in rekapKasir" :key="k.nama_kasir">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-user text-violet-600 text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-black text-sm text-slate-800 truncate" x-text="k.nama_kasir"></p>
                                    <p class="text-[10px] text-slate-400 font-medium">Kasir</p>
                                </div>
                            </div>
                            <div class="border-t border-slate-100 pt-2 space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500 font-medium">Total Item</span>
                                    <span class="font-black text-violet-600" x-text="k.total_item + ' pcs'"></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500 font-medium">Total Nilai</span>
                                    <span class="font-black text-slate-800" x-text="'Rp ' + formatRp(k.total_nilai)"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Kartu total semua -->
                    <div class="bg-gradient-to-br from-violet-600 to-indigo-600 rounded-2xl shadow-sm p-4 flex flex-col gap-2 text-white">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-calculator text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-black text-sm">Total Semua</p>
                                <p class="text-[10px] text-white/70 font-medium" x-text="'Periode: ' + filter.date_from + ' s/d ' + filter.date_to"></p>
                            </div>
                        </div>
                        <div class="border-t border-white/20 pt-2 space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-white/70">Total Transaksi</span>
                                <span class="font-black" x-text="rows.length + ' item'"></span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-white/70">Total Nilai</span>
                                <span class="font-black" x-text="'Rp ' + formatRp(totalSemua)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABEL DETAIL -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-black text-slate-800 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-table-list text-violet-500"></i> Riwayat Pengiriman Item Custom
                    </h2>
                    <span class="text-xs font-bold text-slate-400" x-text="rows.length + ' data ditemukan'"></span>
                </div>

                <!-- Loading state -->
                <div x-show="isLoading" class="py-20 flex flex-col items-center justify-center text-slate-400 space-y-3">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-violet-500"></i>
                    <p class="font-bold text-sm">Memuat data...</p>
                </div>

                <!-- Empty state -->
                <div x-show="!isLoading && rows.length === 0" class="py-20 flex flex-col items-center justify-center text-slate-300 space-y-3">
                    <i class="fa-solid fa-pen-to-square text-5xl"></i>
                    <p class="font-bold text-sm">Belum ada data item custom di periode ini</p>
                </div>

                <!-- Table -->
                <!-- Table -->
                <div x-show="!isLoading && rows.length > 0" class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-center px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap w-10">No</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Waktu Dibuat</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Nama Item Custom</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Tipe Pesanan</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Dibuat Oleh</th>
                                <th class="text-right px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap pr-5">Harga Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 text-xs font-bold text-slate-400 text-center" x-text="idx + 1"></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2 text-xs text-slate-500 font-medium whitespace-nowrap">
                                            <i class="fa-regular fa-clock text-violet-400"></i>
                                            <span x-text="formatWaktu(row.waktu_transaksi)"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-pen-to-square text-violet-600 text-xs"></i>
                                            </div>
                                            <span class="font-bold text-slate-800 text-xs" x-text="row.nama_item"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black"
                                            :class="row.is_po == 1 ? 'bg-orange-100 text-orange-700' : 'bg-sky-100 text-sky-700'"
                                            x-text="row.is_po == 1 ? '🔥 Dapur (PO)' : '🛒 Reguler'">
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-user text-slate-500 text-[9px]"></i>
                                            </div>
                                            <span class="font-bold text-xs text-slate-700" x-text="row.nama_kasir || 'Tidak Diketahui'"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right pr-5 text-xs font-black text-slate-800" x-text="'Rp ' + formatRp(row.price)"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-xs font-black text-slate-600 uppercase text-right">Total Nilai</td>
                                <td class="px-4 py-3 text-right pr-5 font-black text-sm text-violet-600" x-text="'Rp ' + formatRp(totalSemua)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
    function laporanCustom() {
        return {
            isLoading: false,
            filter: {
                date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
                date_to:   new Date().toISOString().slice(0, 10)
            },
            rows: [],
            rekapKasir: [],
            totalSemua: 0,

            async init() {
                await this.loadData();
            },

            async loadData() {
                this.isLoading = true;
                try {
                    const url = `../logic_kasir.php?action=get_custom_report&date_from=${this.filter.date_from}&date_to=${this.filter.date_to}&nocache=${Date.now()}`;
                    const res  = await fetch(url);
                    const text = await res.text();
                    const data = JSON.parse(text);

                    if (data.status === 'success') {
                        this.rows        = data.data;
                        this.rekapKasir  = data.rekap_kasir;
                        this.totalSemua  = data.total_semua;
                    } else {
                        Swal.fire('Error', 'Gagal memuat data laporan.', 'error');
                    }
                } catch(e) {
                    console.error(e);
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data.', 'error');
                } finally {
                    this.isLoading = false;
                }
            },

            printPage() {
                window.print();
            },

            formatRp(val) {
                const v = parseFloat(val);
                if (isNaN(v)) return '0';
                return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(v);
            },

            formatWaktu(dt) {
                if (!dt) return '-';
                const d = new Date(dt);
                return d.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' }) + ' ' +
                       d.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
            }
        };
    }
    </script>

</body>
</html>
