<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
$page_title = "Data Pelanggan - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="customerApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-users mr-2"></i>Data Pelanggan</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="fetchData()" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm">
                    <i class="fa-solid fa-rotate" :class="isLoading ? 'fa-spin' : ''"></i> 
                </button>
                <div class="border-l border-blue-400 pl-4 ml-2">
                    <button onclick="logoutSistem()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 justify-between items-center sticky top-0 z-10">
                    <div class="relative w-full sm:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama atau telepon..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700">
                    </div>
                    
                    <button @click="openModal()" class="w-full sm:w-auto bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2 shadow-sm shadow-primary/30">
                        <i class="fa-solid fa-plus"></i> Tambah Pelanggan
                    </button>
                </div>

                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden relative">
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-sm">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Pelanggan</th>
                                    <th class="p-4 font-black">HP / WhatsApp</th>
                                    <th class="p-4 font-black">Alamat</th>
                                    <th class="p-4 font-black text-center">Point Loyalitas</th>
                                    <th class="p-4 font-black text-center w-24"><i class="fa-solid fa-bars"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="paginatedData.length === 0">
                                    <td colspan="5" class="p-10 text-center">
                                        <div class="text-slate-300 text-5xl mb-3"><i class="fa-solid fa-id-card-clip"></i></div>
                                        <p class="text-slate-500 font-bold">Belum ada data pelanggan.</p>
                                    </td>
                                </tr>

                                <template x-for="item in paginatedData" :key="item.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors group">
                                        <td class="p-4">
                                            <div class="font-black text-slate-800 text-base" x-text="item.name"></div>
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter" x-text="'Member ID: #' + String(item.id).padStart(4, '0')"></div>
                                        </td>
                                        <td class="p-4 font-bold text-slate-600">
                                            <span x-text="item.phone || '-'"></span>
                                        </td>
                                        <td class="p-4 text-xs font-semibold text-slate-500 max-w-xs truncate" x-text="item.address || 'Alamat tidak diisi'"></td>
                                        <td class="p-4 text-center">
                                            <div class="inline-flex flex-col items-center">
                                                <span class="bg-amber-50 border border-amber-200 text-amber-600 px-3 py-1.5 rounded-xl text-xs font-black shadow-sm flex items-center gap-1.5">
                                                    <i class="fa-solid fa-medal text-sm"></i>
                                                    <span x-text="item.points"></span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button @click="openModal(item)" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors flex items-center justify-center" title="Edit"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                                                <button @click="hapusData(item.id)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors flex items-center justify-center" title="Hapus"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50" x-show="totalPages > 1">
                        <div class="text-xs font-bold text-slate-500">
                            Menampilkan <span class="text-primary font-black" x-text="paginatedData.length"></span> pelanggan
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 font-bold text-xs shadow-sm">Prev</button>
                            <span class="px-4 py-1.5 rounded-lg bg-primary text-white font-black text-xs shadow-sm" x-text="currentPage + ' / ' + totalPages"></span>
                            <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 font-bold text-xs shadow-sm">Next</button>
                        </div>
                    </div>
                </div>

                <div class="h-10"></div>
            </div>
        </main>

        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal()"></div>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] overflow-hidden m-4 transform transition-all">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800" x-text="isEdit ? 'Ubah Data Pelanggan' : 'Daftar Pelanggan Baru'"></h3>
                    <button @click="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5">
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="form.name" placeholder="Nama pelanggan..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Nomor WhatsApp / HP</label>
                        <input type="text" x-model="form.phone" placeholder="08xxx..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Alamat Lengkap</label>
                        <textarea x-model="form.address" rows="3" placeholder="Alamat rumah..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm custom-scrollbar"></textarea>
                    </div>

                    <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200">
                        <label class="block text-[11px] font-black text-amber-600 mb-1.5 uppercase">Total Point Loyalitas</label>
                        <div class="flex items-center gap-3">
                            <input type="number" x-model="form.points" class="w-full bg-white border border-amber-300 rounded-xl px-4 py-2 outline-none focus:border-amber-500 font-black text-lg text-amber-600">
                            <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center text-xl shadow-sm"><i class="fa-solid fa-medal"></i></div>
                        </div>
                        <p class="text-[10px] font-bold text-amber-500 mt-2 italic">* Poin akan bertambah otomatis setiap transaksi selesai.</p>
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

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>