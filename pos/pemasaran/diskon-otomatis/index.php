<?php
require_once '../../../config/auth.php';
if (!defined('BASE_URL')) {
    $is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $folder = $is_localhost ? '/pos-lovecakes/' : '/';
    if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
}
$page_title = "Diskon Otomatis Min. Belanja - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="diskonOtomatisApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-percent mr-2"></i>Diskon Otomatis Min. Belanja</h2>
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
            <div class="w-full max-w-full space-y-6">
                
                <!-- TOP BAR -->
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 justify-between items-center sticky top-0 z-10">
                    <div class="relative w-full sm:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama diskon..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700">
                    </div>
                    
                    <button @click="openModal()" class="w-full sm:w-auto bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2 shadow-sm shadow-emerald-500/30">
                        <i class="fa-solid fa-plus"></i> Tambah Diskon Otomatis
                    </button>
                </div>

                <!-- TABEL DATA -->
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden relative">
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-sm">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Nama Promo Diskon</th>
                                    <th class="p-4 font-black text-center">Minimal Belanja</th>
                                    <th class="p-4 font-black text-center">Besaran Diskon</th>
                                    <th class="p-4 font-black text-center">Periode Berlaku</th>
                                    <th class="p-4 font-black text-center">Status</th>
                                    <th class="p-4 font-black text-center w-24"><i class="fa-solid fa-bars"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="filteredData.length === 0">
                                    <td colspan="6" class="p-10 text-center">
                                        <div class="text-slate-300 text-5xl mb-3"><i class="fa-solid fa-tags"></i></div>
                                        <p class="text-slate-500 font-bold">Belum ada aturan diskon otomatis.</p>
                                    </td>
                                </tr>

                                <template x-for="item in filteredData" :key="item.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4 font-black text-slate-800" x-text="item.name"></td>
                                        <td class="p-4 text-center font-bold text-slate-600">
                                            <span x-text="item.min_purchase > 0 ? 'Rp ' + formatRupiah(item.min_purchase) : 'Tanpa Syarat'"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="bg-amber-50 text-amber-700 font-black px-3 py-1 rounded-xl border border-amber-200 text-xs inline-block">
                                                <span x-text="item.discount_type === 'PERCENT' ? item.discount_value + '%' : 'Rp ' + formatRupiah(item.discount_value)"></span>
                                            </span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="text-[11px] font-bold text-slate-600" x-text="(item.start_date ? formatDate(item.start_date) : '-') + ' s.d'"></div>
                                            <div class="text-[11px] font-bold text-rose-500" x-text="item.end_date ? formatDate(item.end_date) : '-'"></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span :class="item.is_active == 1 ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-300'" class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase border">
                                                <span x-text="item.is_active == 1 ? 'Aktif' : 'Non-aktif'"></span>
                                            </span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="editItem(item)" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-colors flex items-center justify-center" title="Edit"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                                                <button @click="deleteItem(item.id)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors flex items-center justify-center" title="Hapus"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL FORM -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
            
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] overflow-hidden m-4 transform transition-all">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800" x-text="isEdit ? 'Edit Diskon Otomatis' : 'Tambah Diskon Otomatis Baru'"></h3>
                    <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5">
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Nama Promo <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="form.name" placeholder="Misal: Diskon Pembelian Minimal 100rb diskon 3%" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Minimal Belanja (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" min="0" x-model="form.min_purchase" placeholder="Misal: 100000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Tipe Diskon</label>
                            <select x-model="form.discount_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-primary font-bold text-sm cursor-pointer">
                                <option value="PERCENT">Persentase (%)</option>
                                <option value="NOMINAL">Nominal (Rupiah)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Besaran Diskon <span class="text-rose-500">*</span></label>
                        <input type="number" min="0" x-model="form.discount_value" placeholder="Misal: 3 untuk 3%" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Mulai Berlaku</label>
                            <input type="date" x-model="form.start_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 outline-none focus:border-primary font-bold text-xs text-slate-700 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Berakhir Pada</label>
                            <input type="date" x-model="form.end_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 outline-none focus:border-primary font-bold text-xs text-slate-700 cursor-pointer">
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="isActiveCheck" x-model="form.is_active" class="w-5 h-5 rounded border-slate-300 text-primary cursor-pointer focus:ring-primary">
                        <label for="isActiveCheck" class="text-sm font-bold text-slate-700 cursor-pointer">Diskon Otomatis Aktif</label>
                    </div>
                </div>

                <div class="p-5 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-6 py-2.5 rounded-xl font-bold text-slate-500 hover:bg-slate-200 transition-colors text-sm">Batal</button>
                    <button @click="saveItem()" class="px-6 py-2.5 rounded-xl font-black bg-primary hover:bg-blue-700 text-white shadow-md shadow-primary/30 transition-all flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Aturan
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
