<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pos-lovecakes/');
}
$page_title = "Pengaturan POS - Love Cakes";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="settingsPosApp()" x-cloak>

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-cash-register mr-2"></i>Pengaturan POS</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="border-l border-blue-400 pl-4 ml-2">
                    <button onclick="logoutSistem()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="max-w-4xl mx-auto space-y-6 relative" :class="isLoading ? 'opacity-50 pointer-events-none' : ''">
                
                <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-transparent">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-emerald-50/50">
                        <h4 class="font-black text-slate-800 text-lg flex items-center gap-2">
                            <i class="fa-solid fa-motorcycle text-emerald-500"></i> Markup Harga Channel Online
                        </h4>
                        <p class="text-xs font-bold text-slate-500 mt-1">Sistem otomatis menaikkan harga produk sekian persen saat Kasir memilih channel bersangkutan.</p>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Markup Grab (%)</label>
                            <div class="flex">
                                <span class="bg-slate-100 border border-slate-200 border-r-0 rounded-l-xl px-4 py-2.5 flex items-center text-slate-500"><i class="fa-solid fa-bag-shopping text-emerald-500"></i></span>
                                <input type="number" x-model="form.markup_grab" class="w-full bg-white border border-slate-200 rounded-r-xl px-4 py-2.5 outline-none focus:border-emerald-500 font-black text-slate-700 transition-colors" placeholder="Misal: 30">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Markup Gojek (%)</label>
                            <div class="flex">
                                <span class="bg-slate-100 border border-slate-200 border-r-0 rounded-l-xl px-4 py-2.5 flex items-center text-slate-500"><i class="fa-solid fa-motorcycle text-emerald-500"></i></span>
                                <input type="number" x-model="form.markup_gojek" class="w-full bg-white border border-slate-200 rounded-r-xl px-4 py-2.5 outline-none focus:border-emerald-500 font-black text-slate-700 transition-colors" placeholder="Misal: 25">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-rose-50/50">
                        <h4 class="font-black text-slate-800 text-lg flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-rose-500"></i> Keamanan Otorisasi (Supervisor)
                        </h4>
                        <p class="text-xs font-bold text-slate-500 mt-1">PIN untuk menyetujui Diskon Manual Tambahan di mesin kasir.</p>
                    </div>
                    <div class="p-6">
                        <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">PIN Otorisasi Diskon (Angka)</label>
                        <input type="password" x-model="form.pin_supervisor" class="w-full max-w-xs bg-white border border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-rose-500 font-black text-xl text-rose-600 transition-colors tracking-widest text-center" placeholder="******">
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-blue-50/50">
                        <h4 class="font-black text-slate-800 text-lg flex items-center gap-2">
                            <i class="fa-brands fa-whatsapp text-blue-500"></i> Integrasi Kirim Struk WhatsApp
                        </h4>
                        <p class="text-xs font-bold text-slate-500 mt-1">Kredensial API untuk mengirim struk pembelian otomatis ke WA pelanggan.</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Endpoint WA API Gateway</label>
                            <input type="text" x-model="form.wa_gateway_api" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors text-blue-600" placeholder="https://api.fonnte.com/send...">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Nomor Sender WA / API Token</label>
                            <input type="text" x-model="form.wa_number_sender" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm transition-colors" placeholder="Isikan Token API...">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-amber-50/50">
                        <h4 class="font-black text-slate-800 text-lg flex items-center gap-2">
                            <i class="fa-solid fa-money-bill-wave text-amber-500"></i> Modal Awal Kasir (Deposit Default)
                        </h4>
                        <p class="text-xs font-bold text-slate-500 mt-1">Nilai default untuk modal uang fisik di laci saat kasir membuka shift baru.</p>
                    </div>
                    <div class="p-6">
                        <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Nominal Default (Rp)</label>
                        <div class="flex max-w-xs">
                            <span class="bg-slate-100 border border-slate-200 border-r-0 rounded-l-xl px-4 py-3 flex items-center font-black text-amber-500">Rp</span>
                            <input type="number" x-model="form.default_start_cash" class="w-full bg-white border border-slate-200 rounded-r-xl px-4 py-3 outline-none focus:border-amber-500 font-black text-xl text-slate-700 transition-colors" placeholder="Misal: 100000">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 pb-10">
                    <button @click="saveSettings()" class="w-full sm:w-auto bg-primary hover:bg-blue-700 text-white font-black px-10 py-4 rounded-xl transition-all shadow-md shadow-primary/30 flex justify-center items-center gap-2 text-base">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan POS
                    </button>
                </div>

            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>