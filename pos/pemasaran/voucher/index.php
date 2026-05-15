<?php
require_once '../../../config/auth.php';
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
$page_title = "Manajemen Voucher - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="voucherApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-ticket mr-2"></i>Voucher & Diskon</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="fetchData()" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm">
                    <i class="fa-solid fa-rotate" :class="isLoading ? 'fa-spin' : ''"></i> 
                </button>
                <div class="border-l border-blue-400 pl-4 ml-2">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- TOP BAR (Pencarian & Tombol Tambah) -->
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 justify-between items-center sticky top-0 z-10">
                    <div class="relative w-full sm:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari kode atau nama voucher..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700">
                    </div>
                    
                    <button @click="openModal()" class="w-full sm:w-auto bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2 shadow-sm shadow-emerald-500/30">
                        <i class="fa-solid fa-plus"></i> Tambah Voucher Baru
                    </button>
                </div>

                <!-- TABEL DATA -->
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden relative">
                    <!-- Loading Overlay -->
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-sm">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Kode Promo</th>
                                    <th class="p-4 font-black">Detail Voucher</th>
                                    <th class="p-4 font-black text-center">Diskon</th>
                                    <th class="p-4 font-black text-center">Min. Belanja</th>
                                    <th class="p-4 font-black text-center">Masa Berlaku</th>
                                    <th class="p-4 font-black text-center">Status</th>
                                    <th class="p-4 font-black text-center w-24"><i class="fa-solid fa-bars"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="filteredData.length === 0">
                                    <td colspan="7" class="p-10 text-center">
                                        <div class="text-slate-300 text-5xl mb-3"><i class="fa-solid fa-ticket-simple"></i></div>
                                        <p class="text-slate-500 font-bold">Tidak ada data voucher.</p>
                                    </td>
                                </tr>

                                <template x-for="item in filteredData" :key="item.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4">
                                            <span class="bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg text-sm font-black tracking-widest text-primary border-dashed" x-text="item.voucher_code"></span>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-slate-800" x-text="item.voucher_name"></div>
                                            <div class="text-[10px] font-bold text-slate-400 mt-1">Kuota: <span class="text-slate-600" x-text="item.used_count + ' / ' + (item.max_usage == 0 ? 'Unlimited' : item.max_usage)"></span></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="font-black text-emerald-600" x-text="item.discount_type === 'PERCENT' ? item.discount_amount + '%' : 'Rp ' + formatRupiah(item.discount_amount)"></span>
                                        </td>
                                        <td class="p-4 text-center font-bold text-slate-500">
                                            <span x-text="item.min_purchase > 0 ? 'Rp ' + formatRupiah(item.min_purchase) : 'Tanpa Min.'"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="text-[11px] font-bold text-slate-600" x-text="(item.valid_from ? formatDate(item.valid_from) : '-') + ' s.d'"></div>
                                            <div class="text-[11px] font-bold text-rose-500" x-text="item.valid_until ? formatDate(item.valid_until) : '-'"></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <!-- Toggle Status Aktif/Nonaktif -->
                                            <button @click="toggleStatus(item.id, item.is_active == 1 ? 0 : 1)" 
                                                    :class="item.is_active == 1 ? 'bg-emerald-500' : 'bg-slate-300'" 
                                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shadow-inner">
                                                <span :class="item.is_active == 1 ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                            </button>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openModal(item)" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-colors flex items-center justify-center"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                                                <button @click="hapusData(item.id)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors flex items-center justify-center"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                            </div>
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

        <!-- MODAL FORM VOUCHER -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal()"></div>
            
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] overflow-hidden m-4 transform transition-all">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800" x-text="isEdit ? 'Edit Voucher' : 'Tambah Voucher Baru'"></h3>
                    <button @click="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <!-- Form Scrollable -->
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Kode Promo <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="form.voucher_code" placeholder="Misal: MERDEKA26" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-black text-sm uppercase transition-colors" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Nama Voucher <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="form.voucher_name" placeholder="Nama promo..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Tipe Diskon</label>
                            <select x-model="form.discount_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm cursor-pointer">
                                <option value="IDR">Nominal (Rupiah)</option>
                                <option value="PERCENT">Persentase (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Besaran Diskon <span class="text-rose-500">*</span></label>
                            <input type="number" x-model="form.discount_amount" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Min. Belanja (Rp)</label>
                            <input type="number" x-model="form.min_purchase" placeholder="0 = tanpa syarat" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Batas Kuota Pengguna</label>
                            <input type="number" x-model="form.max_usage" placeholder="0 = Unlimited" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-5 mt-2">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Mulai Berlaku</label>
                            <input type="date" x-model="form.valid_from" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-700 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Berakhir Pada</label>
                            <input type="date" x-model="form.valid_until" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm text-slate-700 cursor-pointer">
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 mt-4">
                        <input type="checkbox" id="isActiveCheck" x-model="form.is_active" class="w-5 h-5 rounded border-slate-300 text-primary cursor-pointer focus:ring-primary">
                        <label for="isActiveCheck" class="text-sm font-bold text-slate-700 cursor-pointer">Voucher ini dalam keadaan Aktif (Bisa diklaim)</label>
                    </div>

                </div>

                <div class="p-5 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button @click="closeModal()" class="px-6 py-2.5 rounded-xl font-bold text-slate-500 hover:bg-slate-200 transition-colors">Batal</button>
                    <button @click="simpanData()" class="px-6 py-2.5 rounded-xl font-black bg-primary hover:bg-blue-700 text-white shadow-md shadow-primary/30 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Voucher
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- SCRIPT AJAX -->
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>