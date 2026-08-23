<?php
require_once '../../../config/auth.php';
$page_title = "Manajemen Perangkat Kasir - Love Cakes";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="deviceSettingsApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER ADMIN -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-mobile-screen-button"></i> Manajemen Perangkat Kasir
                    </h2>
                    <p class="text-xs text-blue-100 font-medium">Otorisasi & Pembatasan Akses Kasir Berbasis Device Token</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="border-l border-blue-400 pl-4 ml-2">
                    <button onclick="logoutSistem()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="w-full max-w-full space-y-6 relative" :class="isLoading ? 'opacity-50 pointer-events-none' : ''">
                
                <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-transparent">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                </div>

                <!-- 1. SAKLAR GLOBAL PEMBATASAN AKSES PERANGKAT -->
                <div class="bg-white rounded-3xl border shadow-sm overflow-hidden transition-all duration-300"
                     :class="enableRestriction ? 'border-emerald-300 ring-4 ring-emerald-500/10' : 'border-slate-200'">
                    <div class="p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6"
                         :class="enableRestriction ? 'bg-emerald-50/50' : 'bg-slate-50/60'">
                        
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0 transition-colors shadow-sm"
                                 :class="enableRestriction ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500'">
                                <i class="fa-solid" :class="enableRestriction ? 'fa-shield-halved' : 'fa-lock-open'"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2.5 mb-1">
                                    <h3 class="font-black text-slate-800 text-lg">Saklar Global: Pembatasan Akses Perangkat Kasir</h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                                          :class="enableRestriction ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'"
                                          x-text="enableRestriction ? 'AKTIF (Terproteksi)' : 'NONAKTIF (Publik)'"></span>
                                </div>
                                <p class="text-xs text-slate-500 font-bold max-w-2xl leading-relaxed">
                                    <template x-if="enableRestriction">
                                        <span><strong class="text-emerald-700">Mode Proteksi Aktif:</strong> Hanya HP/Tablet/PC yang telah didaftarkan dengan Sandi Aktivasi yang dapat membuka halaman Kasir Offline & Kasir Online. Perangkat luar yang belum terdaftar akan otomatis diblokir.</span>
                                    </template>
                                    <template x-if="!enableRestriction">
                                        <span><strong class="text-slate-600">Mode Bebas / Publik:</strong> Siapa saja yang memiliki akun login dapat membuka halaman kasir dari perangkat mana saja tanpa registrasi perangkat.</span>
                                    </template>
                                </p>
                            </div>
                        </div>

                        <!-- TOGGLE BUTTON -->
                        <div class="flex items-center gap-3 shrink-0 self-end md:self-center">
                            <button @click="toggleGlobalRestriction()" 
                                    class="relative inline-flex h-9 w-16 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shadow-inner"
                                    :class="enableRestriction ? 'bg-emerald-600' : 'bg-slate-300'">
                                <span class="pointer-events-none inline-block h-8 w-8 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out flex items-center justify-center text-xs"
                                      :class="enableRestriction ? 'translate-x-7 text-emerald-600 font-black' : 'translate-x-0 text-slate-400 font-black'">
                                    <i class="fa-solid" :class="enableRestriction ? 'fa-check' : 'fa-xmark'"></i>
                                </span>
                            </button>
                        </div>

                    </div>
                </div>

                <!-- 2. GRID INFO & SANDI AKTIVASI -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- KARTU SANDI AKTIVASI MASTER -->
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-lg">
                                    <i class="fa-solid fa-key"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-800 text-sm">Sandi Registrasi Perangkat</h4>
                                    <p class="text-[11px] text-slate-400 font-bold">Kunci aktivasi untuk mendaftarkan HP/PC toko</p>
                                </div>
                            </div>

                            <p class="text-xs text-slate-500 font-medium mb-4 leading-relaxed">
                                Berikan sandi ini kepada kasir/supervisor saat pertama kali membuka web POS di Tablet/PC toko baru.
                            </p>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">SANDI AKTIVASI SAAT INI</label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <input type="text" x-model="newPasscode" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 font-black text-base tracking-widest text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all text-center">
                                        </div>
                                        <button @click="savePasscode()" :disabled="isSavingPasscode" class="px-4 py-2.5 bg-primary hover:bg-blue-700 text-white rounded-xl font-black text-xs transition-all shadow-sm flex items-center gap-1.5 disabled:opacity-50">
                                            <i class="fa-solid fa-save" x-show="!isSavingPasscode"></i>
                                            <i class="fa-solid fa-spinner fa-spin" x-show="isSavingPasscode"></i>
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2 text-[11px] text-slate-400 font-bold">
                            <i class="fa-solid fa-circle-info text-blue-500"></i>
                            <span>Admin dapat mengubah sandi ini sewaktu-waktu.</span>
                        </div>
                    </div>

                    <!-- KARTU STATISTIK RINGKAS -->
                    <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between text-slate-400 mb-2">
                                <span class="text-xs font-black uppercase tracking-wider">Total Perangkat</span>
                                <i class="fa-solid fa-laptop text-lg text-blue-500"></i>
                            </div>
                            <div>
                                <span class="text-3xl font-black text-slate-800" x-text="devices.length"></span>
                                <p class="text-[11px] font-bold text-slate-400 mt-1">Perangkat terdata di sistem</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl border border-emerald-100 shadow-sm p-5 flex flex-col justify-between bg-emerald-50/30">
                            <div class="flex items-center justify-between text-emerald-600 mb-2">
                                <span class="text-xs font-black uppercase tracking-wider">Perangkat Aktif</span>
                                <i class="fa-solid fa-circle-check text-lg text-emerald-500"></i>
                            </div>
                            <div>
                                <span class="text-3xl font-black text-emerald-600" x-text="activeDevicesCount"></span>
                                <p class="text-[11px] font-bold text-emerald-600/70 mt-1">Diizinkan buka kasir</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl border border-rose-100 shadow-sm p-5 flex flex-col justify-between bg-rose-50/30">
                            <div class="flex items-center justify-between text-rose-500 mb-2">
                                <span class="text-xs font-black uppercase tracking-wider">Akses Diblokir</span>
                                <i class="fa-solid fa-ban text-lg text-rose-500"></i>
                            </div>
                            <div>
                                <span class="text-3xl font-black text-rose-600" x-text="devices.length - activeDevicesCount"></span>
                                <p class="text-[11px] font-bold text-rose-600/70 mt-1">Dinonaktifkan Admin</p>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- 3. TABEL DAFTAR PERANGKAT TERDAFTAR -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    
                    <!-- HEADER TABEL & SEARCH -->
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <h4 class="font-black text-slate-800 text-base flex items-center gap-2">
                                <i class="fa-solid fa-tablet-screen-button text-primary"></i> Daftar Perangkat Kasir Terdaftar
                            </h4>
                            <p class="text-xs text-slate-400 font-bold">Pantau dan kelola izin akses setiap HP, Tablet, dan PC kasir</p>
                        </div>

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" x-model="searchQuery" placeholder="Cari nama perangkat / IP..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-bold outline-none focus:border-primary focus:bg-white transition-colors">
                            </div>
                            <button @click="loadData()" class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors" title="Muat Ulang">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TABEL KONTEN -->
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-black uppercase text-[10px] tracking-wider">
                                    <th class="py-3.5 px-5">Nama Perangkat</th>
                                    <th class="py-3.5 px-4">Cabang / Store</th>
                                    <th class="py-3.5 px-4">IP Terdaftar</th>
                                    <th class="py-3.5 px-4">Terdaftar Pada</th>
                                    <th class="py-3.5 px-4">Terakhir Aktif</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-bold text-slate-700">
                                
                                <template x-for="item in filteredDevices" :key="item.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-4 px-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-sm shrink-0"
                                                     :class="parseInt(item.is_active) === 1 ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-400'">
                                                    <i class="fa-solid fa-mobile-screen"></i>
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-800 text-sm" x-text="item.device_name"></div>
                                                    <div class="text-[10px] font-mono text-slate-400" x-text="'Token: ' + item.device_token.substring(0, 12) + '...'"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-[10px] font-black" x-text="item.store_name"></span>
                                        </td>
                                        <td class="py-4 px-4 font-mono text-slate-600" x-text="item.registered_ip || '-'"></td>
                                        <td class="py-4 px-4 text-slate-500 font-normal" x-text="formatDateTime(item.created_at)"></td>
                                        <td class="py-4 px-4 text-slate-500 font-normal" x-text="formatDateTime(item.last_active_at)"></td>
                                        <td class="py-4 px-4 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black inline-flex items-center gap-1.5"
                                                  :class="parseInt(item.is_active) === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="parseInt(item.is_active) === 1 ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                                <span x-text="parseInt(item.is_active) === 1 ? 'Aktif' : 'Diblokir'"></span>
                                            </span>
                                        </td>
                                        <td class="py-4 px-5 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <!-- TOGGLE AKTIF/NONAKTIF -->
                                                <button @click="toggleDeviceStatus(item)" 
                                                        class="p-2 rounded-xl text-xs font-black transition-colors"
                                                        :class="parseInt(item.is_active) === 1 ? 'bg-amber-50 hover:bg-amber-100 text-amber-600' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600'"
                                                        :title="parseInt(item.is_active) === 1 ? 'Blokir Akses' : 'Buka Blokir'">
                                                    <i class="fa-solid" :class="parseInt(item.is_active) === 1 ? 'fa-ban' : 'fa-check'"></i>
                                                </button>

                                                <!-- EDIT NAMA -->
                                                <button @click="openEditModal(item)" class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl text-xs font-black transition-colors" title="Ubah Nama/Cabang">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>

                                                <!-- HAPUS -->
                                                <button @click="deleteDevice(item)" class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-black transition-colors" title="Hapus Permanen">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="filteredDevices.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <i class="fa-solid fa-laptop-slash text-4xl mb-3 text-slate-300"></i>
                                        <p class="text-xs font-bold">Belum ada perangkat kasir yang terdaftar.</p>
                                        <p class="text-[11px] text-slate-400 font-normal mt-1">Buka halaman Kasir pada HP/Tablet toko untuk mendaftarkan perangkat pertama.</p>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- MODAL EDIT PERANGKAT -->
    <div x-show="editModal.show" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="editModal.show = false"></div>
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 p-6 flex flex-col overflow-hidden border border-slate-200">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-primary"></i> Edit Informasi Perangkat
                </h3>
                <button @click="editModal.show = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wide mb-1.5">Nama Perangkat</label>
                    <input type="text" x-model="editModal.device_name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 font-bold text-slate-800 outline-none focus:border-primary focus:bg-white text-xs">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wide mb-1.5">Cabang / Store</label>
                    <select x-model.number="editModal.warehouse_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 font-bold text-slate-800 outline-none focus:border-primary focus:bg-white text-xs">
                        <template x-for="w in warehouses" :key="w.id">
                            <option :value="w.id" x-text="w.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 flex gap-3">
                <button type="button" @click="editModal.show = false" class="py-2.5 px-4 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 text-xs transition-colors">Batal</button>
                <button type="button" @click="saveDeviceEdit()" class="flex-1 py-2.5 rounded-xl font-black text-white bg-primary hover:bg-blue-700 text-xs shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
