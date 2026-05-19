<?php
require_once '../../../config/auth.php';

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Master Pembayaran - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="pembayaranApp()" x-cloak>

    <?php include '../../../components/sidebar_admin.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center shrink-0 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-500 hover:bg-slate-100 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-lg font-black text-slate-800 tracking-tight"><i class="fa-solid fa-credit-card text-blue-600 mr-2"></i> Master Pembayaran</h2>
                    <p class="text-xs font-bold text-slate-500">Kelola metode pembayaran dan biaya layanan tambahan (MDR)</p>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto custom-scrollbar p-6 bg-slate-50/50 relative">
            <div x-show="isLoading" class="absolute inset-0 z-50 bg-white/50 backdrop-blur-sm flex items-center justify-center">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-blue-600"></i>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-6xl mx-auto">
                
                <!-- KIRI: FORM -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden h-fit">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="font-black text-slate-700" x-text="form.id ? 'Edit Cara Pembayaran' : 'Tambahkan Cara Pembayaran'"></h3>
                        <button @click="saveMethod()" class="bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg text-xs font-black transition-all flex items-center gap-2">
                            <i class="fa-solid fa-check"></i> Simpan
                        </button>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="text-xs font-black text-slate-500 text-left md:text-right">
                                <span class="text-rose-500">*</span> Cara Pembayaran
                            </label>
                            <div class="md:col-span-2">
                                <select x-model="form.type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-bold text-sm text-slate-700">
                                    <option value="Cash">Tunai / Cash</option>
                                    <option value="Debit">Kartu Debit / Kredit</option>
                                    <option value="QRIS">QRIS / E-Wallet</option>
                                    <option value="Transfer">Transfer Bank</option>
                                    <option value="Hutang">Hutang / Piutang</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="text-xs font-black text-slate-500 text-left md:text-right">
                                <span class="text-rose-500">*</span> Nama Metode
                            </label>
                            <div class="md:col-span-2">
                                <input type="text" x-model="form.name" placeholder="Masukkan Nama (CASH, QRIS BCA, EDC)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-bold text-sm text-slate-800 uppercase">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="text-xs font-black text-slate-500 text-left md:text-right">
                                Nama Biaya Tambahan
                            </label>
                            <div class="md:col-span-2">
                                <input type="text" x-model="form.fee_name" placeholder="Cth: Biaya MDR, Admin" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-bold text-sm text-slate-800">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="text-xs font-black text-slate-500 text-left md:text-right">
                                Tagihan Tambahan
                            </label>
                            <div class="md:col-span-2 relative">
                                <input type="number" step="0.01" x-model="form.fee_percent" placeholder="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-black text-sm text-slate-800">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 font-black text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4" x-show="form.id">
                            <button @click="resetForm()" class="text-xs font-bold text-slate-500 hover:text-slate-700 underline">Batal Edit</button>
                        </div>
                    </div>
                </div>

                <!-- KANAN: LIST -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-black text-slate-700">Daftar Cara Pembayaran</h3>
                    </div>
                    <div class="p-4">
                        <div class="space-y-2 max-h-[500px] overflow-y-auto custom-scrollbar pr-2">
                            <template x-for="item in paymentMethods" :key="item.id">
                                <div class="flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-white hover:border-blue-200 hover:shadow-sm transition-all group" :class="item.is_active == 1 ? '' : 'opacity-50 grayscale'">
                                    <div class="flex items-center gap-4">
                                        <button @click="toggleStatus(item.id, item.is_active)" class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors" :class="item.is_active == 1 ? 'text-emerald-500 bg-emerald-50 hover:bg-emerald-100' : 'text-slate-400 bg-slate-100 hover:bg-slate-200'" title="Aktif/Nonaktif">
                                            <i class="fa-solid fa-power-off text-xs"></i>
                                        </button>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest" x-text="item.type"></p>
                                            <p class="font-black text-sm text-slate-800 uppercase flex items-center gap-2">
                                                <span x-text="item.name"></span>
                                                <span class="text-[10px] px-2 py-0.5 rounded bg-blue-50 text-blue-600 border border-blue-100" x-text="'+' + item.fee_percent + '%'"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="editMethod(item)" class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-500 bg-blue-50 hover:bg-blue-100 transition-colors opacity-0 group-hover:opacity-100">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        <button @click="deleteMethod(item.id)" class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 bg-rose-50 hover:bg-rose-100 transition-colors opacity-0 group-hover:opacity-100">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="paymentMethods.length === 0" class="py-10 text-center text-slate-400 font-bold text-sm">
                                Belum ada metode pembayaran.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
