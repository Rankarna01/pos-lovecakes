<?php
require_once '../../../config/auth.php';
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
$page_title = "Data Supplier - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="supplierApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-truck-fast mr-2"></i>Data Supplier</h2>
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
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <!-- TOP BAR (Pencarian & Tombol Tambah) -->
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 justify-between items-center sticky top-0 z-10">
                    <div class="relative w-full sm:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama, CP, atau alamat..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700">
                    </div>
                    
                    <button @click="openModal()" class="w-full sm:w-auto bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2 shadow-sm shadow-primary/30">
                        <i class="fa-solid fa-plus"></i> Tambah Supplier
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
                                    <th class="p-4 font-black">Nama Perusahaan</th>
                                    <th class="p-4 font-black">Kontak & Telepon</th>
                                    <th class="p-4 font-black">Alamat</th>
                                    <th class="p-4 font-black text-center">Item Supply</th>
                                    <th class="p-4 font-black text-center w-24"><i class="fa-solid fa-bars"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="paginatedData.length === 0">
                                    <td colspan="5" class="p-10 text-center">
                                        <div class="text-slate-300 text-5xl mb-3"><i class="fa-solid fa-truck-ramp-box"></i></div>
                                        <p class="text-slate-500 font-bold">Tidak ada data supplier.</p>
                                    </td>
                                </tr>

                                <template x-for="item in paginatedData" :key="item.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4">
                                            <div class="font-black text-slate-800 text-base" x-text="item.name"></div>
                                            <div class="text-[11px] font-semibold text-slate-400 mt-0.5" x-text="item.email || 'Tidak ada email'"></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-slate-700 flex items-center gap-2"><i class="fa-solid fa-user text-slate-400 w-3"></i> <span x-text="item.contact_person || '-'"></span></div>
                                            <div class="text-xs font-bold text-blue-600 mt-1 flex items-center gap-2"><i class="fa-solid fa-phone text-blue-400 w-3"></i> <span x-text="item.phone"></span></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="text-xs font-semibold text-slate-600 max-w-xs truncate whitespace-normal line-clamp-2" x-text="item.address || 'Alamat tidak diisi'"></div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="bg-blue-50 border border-blue-100 text-blue-600 px-3 py-1.5 rounded-lg text-xs font-black" x-text="(item.items_supplied || 0) + ' Jenis'"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openModal(item)" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors flex items-center justify-center"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                                                <button @click="hapusData(item.id)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors flex items-center justify-center"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION CONTROLS -->
                    <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50" x-show="totalPages > 1">
                        <div class="text-xs font-bold text-slate-500">
                            Menampilkan <span class="text-primary font-black" x-text="paginatedData.length"></span> dari <span class="text-primary font-black" x-text="filteredData.length"></span> baris data
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 font-bold text-xs transition-all shadow-sm">
                                <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                            </button>
                            <span class="px-4 py-1.5 rounded-lg bg-primary text-white font-black text-xs shadow-sm shadow-primary/30" x-text="'Hal ' + currentPage + ' / ' + totalPages"></span>
                            <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 font-bold text-xs transition-all shadow-sm">
                                Next <i class="fa-solid fa-chevron-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="h-10"></div>
            </div>
        </main>

        <!-- MODAL FORM SUPPLIER -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal()"></div>
            
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] overflow-hidden m-4 transform transition-all">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800" x-text="isEdit ? 'Edit Supplier' : 'Tambah Supplier Baru'"></h3>
                    <button @click="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5">
                    
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Nama Perusahaan / Toko <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="form.name" placeholder="Misal: PT. Bahan Kue Maju" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Contact Person (PIC)</label>
                            <input type="text" x-model="form.contact_person" placeholder="Nama penanggung jawab" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Nomor HP / WhatsApp <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="form.phone" placeholder="08xxx..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Email Address</label>
                        <input type="email" x-model="form.email" placeholder="email@perusahaan.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Alamat Lengkap</label>
                        <textarea x-model="form.address" rows="3" placeholder="Alamat gudang / kantor..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors custom-scrollbar"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Keterangan Tambahan / Catatan</label>
                        <input type="text" x-model="form.description" placeholder="Catatan internal..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors">
                    </div>

                </div>

                <div class="p-5 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button @click="closeModal()" class="px-6 py-2.5 rounded-xl font-bold text-slate-500 hover:bg-slate-200 transition-colors">Batal</button>
                    <button @click="simpanData()" class="px-6 py-2.5 rounded-xl font-black bg-primary hover:bg-blue-700 text-white shadow-md shadow-primary/30 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Data
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- SCRIPT AJAX -->
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>