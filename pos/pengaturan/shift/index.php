<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
$page_title = "Master Shift - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="shiftApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-business-time mr-2"></i>Master Shift</h2>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openModal()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 shadow-sm shadow-emerald-500/30">
                    <i class="fa-solid fa-plus"></i> <span class="hidden sm:inline">Tambah Shift</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-4xl mx-auto">
                
                <div x-show="isLoading" class="text-center py-20 flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-500 font-bold tracking-widest uppercase text-sm">Memuat Data...</p>
                </div>

                <div x-show="!isLoading" class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Daftar Jam Kerja Operasional</h3>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-white border-b border-slate-200 text-xs text-slate-400 uppercase tracking-widest">
                                    <th class="p-4 font-black">Nama Shift</th>
                                    <th class="p-4 font-black text-center">Jam Mulai</th>
                                    <th class="p-4 font-black text-center">Jam Selesai</th>
                                    <th class="p-4 font-black text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <template x-for="shift in shifts" :key="shift.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 font-black text-slate-800" x-text="shift.shift_name"></td>
                                        <td class="p-4 text-center font-bold text-emerald-600" x-text="formatTime(shift.start_time)"></td>
                                        <td class="p-4 text-center font-bold text-rose-600" x-text="formatTime(shift.end_time)"></td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openModal(shift)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all"><i class="fa-solid fa-pen text-xs"></i></button>
                                                <button @click="deleteShift(shift.id)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all"><i class="fa-solid fa-trash text-xs"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="shifts.length === 0">
                                    <td colspan="4" class="p-10 text-center text-slate-400 font-bold">Belum ada data shift kerja.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 flex flex-col m-4 overflow-hidden border border-slate-200" x-transition>
                
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800" x-text="formData.id ? 'Edit Shift' : 'Tambah Shift Baru'"></h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                
                <form @submit.prevent="saveShift()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-black text-slate-500 mb-1.5 uppercase tracking-widest">Nama Shift</label>
                        <input type="text" x-model="formData.shift_name" required placeholder="Misal: Shift Pagi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 font-bold text-slate-700">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1.5 uppercase tracking-widest">Jam Mulai</label>
                            <input type="time" x-model="formData.start_time" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 font-bold text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1.5 uppercase tracking-widest">Jam Selesai</label>
                            <input type="time" x-model="formData.end_time" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 font-bold text-slate-700">
                        </div>
                    </div>
                    
                    <button type="submit" :disabled="isSaving" class="w-full bg-primary hover:bg-slate-800 text-white font-black py-3.5 rounded-xl shadow-lg shadow-primary/30 transition-all flex justify-center items-center gap-2 mt-4 disabled:opacity-50">
                        <i class="fa-solid fa-floppy-disk" :class="isSaving ? 'fa-fade' : ''"></i> <span x-text="isSaving ? 'Menyimpan...' : 'SIMPAN SHIFT'"></span>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>