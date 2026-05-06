<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
$page_title = "Poin Loyalitas - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="loyaltyApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-medal mr-2"></i>Poin Loyalitas</h2>
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
            <div class="max-w-3xl mx-auto space-y-6">
                
                <!-- Alert Banner -->
                <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl p-5 text-white shadow-md shadow-orange-500/20">
                    <h3 class="font-black text-lg mb-2 flex items-center gap-2">
                        Loyalty Point Baru ✨
                    </h3>
                    <ul class="text-sm font-medium space-y-1.5 opacity-90">
                        <li><i class="fa-solid fa-check mr-2"></i> Tukar Point dengan Diskon Transaksi.</li>
                        <li><i class="fa-solid fa-check mr-2"></i> Atur rasio penukaran sesuka Anda.</li>
                    </ul>
                </div>

                <!-- Card Pengaturan -->
                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden relative" :class="isLoading ? 'opacity-50 pointer-events-none' : ''">
                    
                    <!-- Loading Overlay -->
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 backdrop-blur-sm">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <!-- Toggle Aktif/Nonaktif -->
                    <div class="p-5 flex items-center justify-between border-b border-slate-100 bg-slate-50/50">
                        <div>
                            <h4 class="font-black text-slate-800 text-lg">Status Loyalty Point</h4>
                            <p class="text-xs font-bold text-slate-500 mt-1" x-text="isActive ? 'Fitur poin pelanggan sedang AKTIF' : 'Nyalakan pelatuk untuk mengaktifkan Loyalty Point'"></p>
                        </div>
                        
                        <!-- Switch Toggle Apple Style -->
                        <button @click="isActive = !isActive" 
                                :class="isActive ? 'bg-emerald-500' : 'bg-slate-300'" 
                                class="relative inline-flex h-8 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shadow-inner">
                            <span :class="isActive ? 'translate-x-6' : 'translate-x-0'" 
                                  class="pointer-events-none inline-block h-7 w-7 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <div class="p-5" x-show="!isActive">
                        <div class="bg-slate-50 rounded-xl p-4 flex gap-3 text-slate-500 border border-slate-200">
                            <i class="fa-solid fa-circle-info mt-0.5 text-slate-400"></i>
                            <p class="text-sm font-semibold">Setelah diaktifkan, pelanggan akan otomatis mendapatkan poin dari setiap transaksi (Bisa diatur di kasir). Masa berlaku poin bergantung pada kebijakan toko.</p>
                        </div>
                    </div>

                    <!-- Konversi Pengaturan (Hanya Tampil Jika Aktif) -->
                    <div class="p-5 space-y-6" x-show="isActive" x-transition>
                        <div>
                            <h4 class="font-black text-slate-800 text-base mb-4"><i class="fa-solid fa-money-bill-transfer text-primary mr-2"></i> Konversi Penggunaan</h4>
                            
                            <!-- Input Besar Poin -->
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Tukar Point Sebesar</label>
                                <div class="flex">
                                    <input type="number" x-model="pointsRequired" class="w-full bg-slate-50 border border-slate-200 border-r-0 rounded-l-xl px-4 py-3 outline-none focus:bg-white focus:border-primary font-bold text-slate-700 transition-colors" placeholder="0">
                                    <span class="bg-slate-200 text-slate-600 font-black px-5 py-3 rounded-r-xl border border-slate-200 flex items-center justify-center">
                                        Poin
                                    </span>
                                </div>
                            </div>

                            <!-- Input Dapat Diskon -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Mendapatkan Diskon</label>
                                <div class="flex">
                                    <input type="number" x-model="discountAmount" class="w-full bg-slate-50 border border-slate-200 border-r-0 rounded-l-xl px-4 py-3 outline-none focus:bg-white focus:border-primary font-bold text-slate-700 transition-colors" placeholder="0">
                                    <div class="flex bg-slate-100 border border-slate-200 rounded-r-xl overflow-hidden p-1">
                                        <button @click="discountType = 'IDR'" :class="discountType === 'IDR' ? 'bg-white shadow-sm text-slate-800 border border-slate-200' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-xs font-black rounded-lg transition-all">IDR</button>
                                        <button @click="discountType = 'PERCENT'" :class="discountType === 'PERCENT' ? 'bg-white shadow-sm text-slate-800 border border-slate-200' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-xs font-black rounded-lg transition-all">%</button>
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-400 font-semibold mt-2" x-text="previewText"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Simpan -->
                    <div class="p-5 border-t border-slate-100 bg-slate-50/50">
                        <button @click="saveSettings()" class="w-full bg-primary hover:bg-blue-700 text-white font-black py-3.5 rounded-xl transition-all shadow-md shadow-primary/20 flex justify-center items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan
                        </button>
                    </div>

                </div>

                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- SCRIPT AJAX -->
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>