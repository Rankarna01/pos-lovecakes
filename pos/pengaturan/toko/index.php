<?php
require_once '../../../config/auth.php';
$page_title = "Pengaturan Toko - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="pengaturanApp()" x-cloak>
    
    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-store mr-2"></i>Pengaturan Toko & POS</h2>
            </div>
            <div class="flex items-center gap-3">
                <button @click="saveData()" :disabled="isSaving" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-2.5 rounded-xl text-sm font-black transition-all shadow-md shadow-emerald-500/30 disabled:opacity-50 flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk" :class="isSaving ? 'fa-fade' : ''"></i> 
                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div x-show="isLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-slate-50/80 backdrop-blur-sm">
                <div class="w-16 h-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                <p class="text-sm font-bold tracking-widest text-slate-500 uppercase">Memuat Konfigurasi...</p>
            </div>

            <div class="max-w-5xl mx-auto space-y-6" x-show="!isLoading">
                
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm flex items-center gap-2"><i class="fa-solid fa-receipt text-primary"></i> Identitas Toko & Struk Kasir</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-8">
                        
                        <div class="flex flex-col items-center gap-3">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest w-full text-center">Logo Toko (Struk & Laporan)</label>
                            <div class="relative w-40 h-40 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden group cursor-pointer" @click="$refs.logoInput.click()">
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" class="w-full h-full object-contain p-2">
                                </template>
                                <template x-if="!logoPreview">
                                    <div class="text-center text-slate-400 group-hover:text-primary transition-colors">
                                        <i class="fa-solid fa-image text-3xl mb-2"></i>
                                        <p class="text-[10px] font-bold">Pilih Gambar</p>
                                    </div>
                                </template>
                                <div class="absolute inset-0 bg-black/50 hidden group-hover:flex items-center justify-center text-white transition-all">
                                    <i class="fa-solid fa-camera text-2xl"></i>
                                </div>
                            </div>
                            <input type="file" x-ref="logoInput" @change="handleLogoSelect" accept="image/*" class="hidden">
                            <p class="text-[10px] font-bold text-slate-400 text-center">Rasio 1:1, Max 1MB (PNG/JPG)</p>
                        </div>

                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-1.5 uppercase">Nama Toko</label>
                                <input type="text" x-model="store.store_name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold text-slate-700">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-black text-slate-500 mb-1.5 uppercase">No. WhatsApp / Telepon</label>
                                    <input type="text" x-model="store.store_phone" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-1.5 uppercase">Alamat Lengkap Toko</label>
                                <textarea x-model="store.store_address" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-sm text-slate-700 custom-scrollbar"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-1.5 uppercase">Pesan Penutup di Struk (Footer)</label>
                                <input type="text" x-model="store.receipt_footer" placeholder="Misal: Terima Kasih, Silakan Datang Kembali!" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold text-slate-700 text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm flex items-center gap-2"><i class="fa-solid fa-cogs text-rose-500"></i> Konfigurasi Sistem POS</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        


                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <label class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase">Penyesuaian Harga Online</label>
                            <h4 class="font-bold text-slate-700 mb-2">Markup Harga GrabFood (%)</h4>
                            <div class="relative">
                                <input type="number" x-model="system.markup_grab" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-emerald-500 font-black text-emerald-600 text-right pr-10">
                                <i class="fa-solid fa-percent absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <label class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase">Penyesuaian Harga Online</label>
                            <h4 class="font-bold text-slate-700 mb-2">Markup Harga GoFood (%)</h4>
                            <div class="relative">
                                <input type="number" x-model="system.markup_gojek" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-rose-500 font-black text-rose-600 text-right pr-10">
                                <i class="fa-solid fa-percent absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ==========================================
                     KARTU MANAJEMEN PIN SUPERVISOR OTP
                =========================================== -->
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden" x-data="pinOTPApp()" x-init="loadPins()">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-violet-500"></i> Manajemen PIN Otorisasi Supervisor
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-1">PIN sekali pakai untuk otorisasi diskon manual di kasir. Setiap PIN hanya bisa digunakan satu kali.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <select x-model="genQty" class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold text-slate-600 outline-none focus:border-violet-400">
                                <option value="1">1 PIN</option>
                                <option value="3">3 PIN</option>
                                <option value="5" selected>5 PIN</option>
                                <option value="10">10 PIN</option>
                            </select>
                            <button @click="generatePins()" :disabled="isGenerating" class="bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white px-4 py-2 rounded-xl text-sm font-black flex items-center gap-2 transition-all shadow-md shadow-violet-500/20">
                                <i class="fa-solid fa-wand-magic-sparkles" :class="isGenerating ? 'fa-spin' : ''"></i>
                                <span x-text="isGenerating ? 'Generating...' : 'Generate PIN'"></span>
                            </button>
                            <button @click="deleteUsedPins()" class="bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 px-3 py-2 rounded-xl text-sm font-bold flex items-center gap-1.5 transition-all border border-slate-200 hover:border-rose-200" title="Hapus semua PIN yang sudah dipakai">
                                <i class="fa-solid fa-broom"></i> Bersihkan
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        <!-- Loading state -->
                        <div x-show="isLoading" class="flex flex-col items-center justify-center py-10 text-slate-400">
                            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-violet-400 mb-3"></i>
                            <span class="text-sm font-bold">Memuat daftar PIN...</span>
                        </div>

                        <!-- Empty state -->
                        <template x-if="!isLoading && pins.length === 0">
                            <div class="text-center py-10">
                                <i class="fa-solid fa-key text-4xl text-slate-200 mb-3"></i>
                                <p class="font-bold text-slate-400 text-sm">Belum ada PIN yang dibuat.</p>
                                <p class="text-xs text-slate-300 mt-1">Klik tombol "Generate PIN" untuk membuat PIN baru.</p>
                            </div>
                        </template>

                        <!-- PIN List -->
                        <div x-show="!isLoading && pins.length > 0" class="space-y-2">
                            <!-- Stats bar -->
                            <div class="flex items-center gap-3 mb-4 text-xs font-bold">
                                <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-lg flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle text-[8px]"></i> 
                                    <span x-text="pins.filter(p => p.is_used == 0).length + ' Aktif'"></span>
                                </span>
                                <span class="bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle text-[8px]"></i> 
                                    <span x-text="pins.filter(p => p.is_used == 1).length + ' Sudah Dipakai'"></span>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                <template x-for="p in pins" :key="p.id">
                                    <div class="rounded-2xl border p-3.5 flex items-center justify-between gap-3 transition-all"
                                         :class="p.is_used == 1 ? 'bg-slate-50 border-slate-100 opacity-60' : 'bg-gradient-to-br from-violet-50 to-indigo-50 border-violet-100 shadow-sm'">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-sm"
                                                 :class="p.is_used == 1 ? 'bg-slate-200 text-slate-400' : 'bg-violet-100 text-violet-600'">
                                                <i :class="p.is_used == 1 ? 'fa-solid fa-ban' : 'fa-solid fa-key'"></i>
                                            </div>
                                            <div>
                                                <div class="font-black text-lg tracking-widest leading-none" 
                                                     :class="p.is_used == 1 ? 'text-slate-400 line-through' : 'text-violet-700'"
                                                     x-text="p.pin"></div>
                                                <div class="text-[10px] font-bold mt-0.5"
                                                     :class="p.is_used == 1 ? 'text-slate-400' : 'text-violet-400'"
                                                     x-text="p.is_used == 1 ? '✓ Dipakai ' + formatDateShort(p.used_at) : 'Siap digunakan'"></div>
                                            </div>
                                        </div>
                                        <button @click="deletePin(p.id)" class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-all"
                                                title="Hapus PIN ini">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>