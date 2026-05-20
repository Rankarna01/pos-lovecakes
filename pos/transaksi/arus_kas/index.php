<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Arus Kas (Petty Cash) - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="arusKasApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-money-bill-transfer mr-2"></i>Arus Kas (Petty Cash)</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="max-w-7xl mx-auto space-y-4 relative" :class="isLoading ? 'opacity-50 pointer-events-none' : ''">
                
                <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/50 backdrop-blur-sm flex flex-col items-center justify-center rounded-3xl" style="display: none;">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-3"></i>
                </div>

                <!-- FILTER TANGGAL -->
                <div class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                    <input type="date" x-model="startDate" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <span class="py-2 text-slate-400 font-bold text-xs">s/d</span>
                    <input type="date" x-model="endDate" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    <button @click="fetchData()" class="bg-primary hover:bg-slate-200 text-white hover:text-primary px-6 py-2.5 rounded-xl font-black transition-all flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-emerald-200 flex justify-between items-center relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-emerald-500 text-7xl"><i class="fa-solid fa-arrow-turn-down"></i></div>
                        <div>
                            <p class="text-xs font-black text-emerald-600 uppercase tracking-widest">Pemasukan Kas</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1" x-text="'Rp ' + formatRupiah(summary.masuk)">Rp 0</h3>
                        </div>
                        <button @click="openModal('masuk')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl font-bold transition-all text-sm z-10 shadow-sm">+ Catat Masuk</button>
                    </div>
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-rose-200 flex justify-between items-center relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10 text-rose-500 text-7xl"><i class="fa-solid fa-arrow-turn-up"></i></div>
                        <div>
                            <p class="text-xs font-black text-rose-600 uppercase tracking-widest">Pengeluaran Kas</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1" x-text="'Rp ' + formatRupiah(summary.keluar)">Rp 0</h3>
                        </div>
                        <button @click="openModal('keluar')" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded-xl font-bold transition-all text-sm z-10 shadow-sm">- Catat Keluar</button>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden mt-4">
                    <div class="p-4 border-b border-slate-100 bg-slate-50"><h3 class="font-black text-slate-700">Riwayat Mutasi Kas</h3></div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Waktu</th>
                                    <th class="p-4 font-black text-center">Tipe</th>
                                    <th class="p-4 font-black">Keterangan</th>
                                    <th class="p-4 font-black text-right">Nominal</th>
                                    <th class="p-4 font-black">Oleh</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <template x-for="item in history" :key="item.id">
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-4 font-bold text-slate-600">
                                            <span x-text="formatDateTime(item.created_at).date"></span><br>
                                            <span class="text-[10px] text-slate-400" x-text="formatDateTime(item.created_at).time"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <template x-if="item.jenis === 'keluar'">
                                                <span class="px-2 py-1 rounded bg-rose-100 text-rose-600 text-[10px] font-black uppercase"><i class="fa-solid fa-arrow-up"></i> Keluar</span>
                                            </template>
                                            <template x-if="item.jenis === 'masuk'">
                                                <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-600 text-[10px] font-black uppercase"><i class="fa-solid fa-arrow-down"></i> Masuk</span>
                                            </template>
                                        </td>
                                        <td class="p-4 font-black text-slate-800" x-text="item.keterangan"></td>
                                        <td class="p-4 text-right font-black" :class="item.jenis === 'keluar' ? 'text-rose-500' : 'text-emerald-500'" x-text="'Rp ' + formatRupiah(item.nominal)"></td>
                                        <td class="p-4 text-slate-500 font-bold text-xs"><i class="fa-solid fa-user-circle"></i> <span x-text="item.user_name"></span></td>
                                    </tr>
                                </template>
                                <tr x-show="history.length === 0">
                                    <td colspan="5" class="p-8 text-center text-slate-400 font-bold">Tidak ada data mutasi kas di rentang tanggal ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- MODAL FORM -->
            <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
                <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 flex flex-col p-6 m-4">
                    <h3 class="font-black text-xl mb-4 text-slate-800" x-text="form.jenis === 'masuk' ? 'Catat Pemasukan Kas' : 'Catat Pengeluaran Kas'"></h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Nominal (Rp)</label>
                            <input type="number" x-model="form.nominal" class="w-full border border-slate-200 rounded-xl px-4 py-3 font-black text-lg outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Keterangan / Tujuan</label>
                            <textarea x-model="form.keterangan" rows="3" class="w-full border border-slate-200 rounded-xl px-4 py-3 font-bold text-sm outline-none focus:border-primary" placeholder="Misal: Beli lakban..."></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button @click="showModal = false" class="px-5 py-2.5 rounded-xl font-bold text-slate-500 hover:bg-slate-100">Batal</button>
                        <button @click="saveData()" :disabled="isSaving" class="px-5 py-2.5 rounded-xl font-black text-white bg-primary hover:bg-blue-600 disabled:opacity-50">Simpan Kas</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Script SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>