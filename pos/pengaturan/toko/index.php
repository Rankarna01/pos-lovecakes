<?php
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost/pos-lovecakes/'); }
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
                            <label class="block text-[10px] font-black text-slate-400 mb-1.5 uppercase">Otorisasi Kasir</label>
                            <h4 class="font-bold text-slate-700 mb-2">PIN Supervisor (Diskon Manual)</h4>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="password" x-model="system.pin_supervisor" class="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 outline-none focus:border-rose-400 font-black text-rose-600 tracking-[0.3em]">
                            </div>
                        </div>

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

                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>