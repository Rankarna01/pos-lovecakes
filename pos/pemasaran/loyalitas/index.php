<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
$page_title = "Setelan Poin Loyalitas - Love Cakes POS";
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
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-medal mr-2"></i>Setelan Poin & Loyalitas</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="border-l border-blue-400 pl-4 ml-2">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
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
                        Master Pengaturan Loyalty Point ✨
                    </h3>
                    <p class="text-sm font-medium opacity-90 leading-relaxed">
                        Atur bagaimana pelanggan mendapatkan poin dari setiap transaksi, dan bagaimana mereka menukarkannya menjadi potongan harga/diskon.
                    </p>
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
                        <button @click="isActive = !isActive" 
                                :class="isActive ? 'bg-emerald-500' : 'bg-slate-300'" 
                                class="relative inline-flex h-8 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shadow-inner">
                            <span :class="isActive ? 'translate-x-6' : 'translate-x-0'" class="pointer-events-none inline-block h-7 w-7 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <!-- Blok Tampil Jika Fitur Aktif -->
                    <div x-show="isActive" x-transition>
                        
                        <!-- 1. ATURAN MENDAPATKAN POIN -->
                        <div class="p-6 border-b border-slate-100 bg-emerald-50/30">
                            <h4 class="font-black text-slate-800 text-base mb-4 flex items-center"><i class="fa-solid fa-coins text-emerald-500 mr-2"></i> Aturan Mendapatkan Poin</h4>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Setiap Kelipatan Belanja</label>
                                <div class="flex items-center">
                                    <span class="bg-slate-100 text-slate-500 font-black px-4 py-3 rounded-l-xl border border-slate-200 border-r-0">Rp</span>
                                    <input type="number" x-model="earnPointRatio" class="w-full bg-white border border-slate-200 px-4 py-3 outline-none focus:border-emerald-500 font-black text-emerald-600 transition-colors" placeholder="10000">
                                    <span class="bg-slate-100 text-slate-500 font-black px-4 py-3 rounded-r-xl border border-slate-200 border-l-0 whitespace-nowrap">= 1 Poin</span>
                                </div>
                                <p class="text-[11px] text-emerald-600/80 font-bold mt-2" x-text="previewEarnText"></p>
                            </div>
                        </div>

                        <!-- 2. ATURAN MENUKARKAN POIN -->
                        <div class="p-6">
                            <h4 class="font-black text-slate-800 text-base mb-4 flex items-center"><i class="fa-solid fa-hand-holding-dollar text-amber-500 mr-2"></i> Konversi Penukaran Poin (Redeem)</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Tukar Sebesar</label>
                                    <div class="flex">
                                        <input type="number" x-model="pointsRequired" class="w-full bg-slate-50 border border-slate-200 border-r-0 rounded-l-xl px-4 py-3 outline-none focus:bg-white focus:border-amber-500 font-black text-slate-700 transition-colors" placeholder="100">
                                        <span class="bg-slate-200 text-slate-600 font-black px-5 py-3 rounded-r-xl border border-slate-200 flex items-center justify-center">Poin</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Mendapatkan Diskon</label>
                                    <div class="flex">
                                        <input type="number" x-model="discountAmount" class="w-full bg-slate-50 border border-slate-200 border-r-0 rounded-l-xl px-4 py-3 outline-none focus:bg-white focus:border-amber-500 font-black text-slate-700 transition-colors" placeholder="10000">
                                        <div class="flex bg-slate-100 border border-slate-200 rounded-r-xl overflow-hidden p-1">
                                            <button @click="discountType = 'IDR'" :class="discountType === 'IDR' ? 'bg-white shadow-sm text-slate-800 border border-slate-200' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-xs font-black rounded-lg transition-all">IDR</button>
                                            <button @click="discountType = 'PERCENT'" :class="discountType === 'PERCENT' ? 'bg-white shadow-sm text-slate-800 border border-slate-200' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-xs font-black rounded-lg transition-all">%</button>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-amber-600/80 font-bold mt-2" x-text="previewRedeemText"></p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Tombol Simpan -->
                    <div class="p-5 border-t border-slate-100 bg-slate-50 flex justify-end">
                        <button @click="saveSettings()" class="w-full sm:w-auto bg-primary hover:bg-blue-700 text-white font-black px-8 py-3.5 rounded-xl transition-all shadow-md shadow-primary/20 flex justify-center items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Master Pengaturan
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