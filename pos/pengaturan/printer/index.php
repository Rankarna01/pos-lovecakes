<?php
// views/pengaturan_printer/index.php

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Pengaturan Printer - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="printerApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-print mr-2"></i>Perangkat & Printer</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-5xl mx-auto">
                
                <div class="mb-6">
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Integrasi Hardware POS</p>
                    <p class="text-xs text-slate-400 mt-1">Hubungkan mesin kasir dengan Printer Thermal, EDC, atau layar tambahan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 p-6">
                        <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                            <h3 class="text-lg font-black text-slate-800 tracking-wide">
                                <i class="fa-solid fa-print text-blue-500 mr-2"></i> Printer Thermal
                            </h3>
                            <span class="text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full" 
                                  :class="isConnected ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                  x-text="isConnected ? 'Aktif' : 'Terputus'">
                            </span>
                        </div>
                        
                        <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">Perangkat Terhubung:</p>
                            <p class="text-base font-bold text-slate-700" x-text="printerName"></p>
                        </div>

                        <div class="flex gap-2 mt-4">
                            <button @click="hubungkanPrinter()" class="flex-1 bg-primary hover:bg-slate-800 text-white font-black py-3 px-4 rounded-xl shadow-lg shadow-primary/30 transition-all text-sm flex items-center justify-center gap-2">
                                <i class="fa-brands fa-bluetooth"></i> Cari & Hubungkan
                            </button>
                            
                            <template x-if="isConnected">
                                <button @click="hapusPrinter()" class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white font-black py-3 px-4 rounded-xl text-sm border border-rose-200 transition-colors flex items-center justify-center shadow-sm shadow-rose-100">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </template>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-4 italic font-medium"><i class="fa-solid fa-circle-info text-blue-400 mr-1"></i> Gunakan Google Chrome versi terbaru agar fitur Bluetooth berfungsi maksimal.</p>
                    </div>

                    <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 p-6 opacity-60 grayscale-[30%]">
                        <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                            <h3 class="text-lg font-black text-slate-800 tracking-wide">
                                <i class="fa-solid fa-credit-card text-emerald-500 mr-2"></i> Mesin EDC
                            </h3>
                            <span class="bg-slate-200 text-slate-600 text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full">Coming Soon</span>
                        </div>
                        <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <p class="text-sm font-bold text-slate-500">Integrasi EDC Bank belum dikonfigurasi.</p>
                        </div>
                        <button disabled class="w-full bg-slate-200 text-slate-400 font-black py-3 px-4 rounded-xl text-sm cursor-not-allowed">
                            JALANKAN SETTLEMENT EDC
                        </button>
                    </div>

                    <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 p-6 opacity-60 grayscale-[30%]">
                        <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                            <h3 class="text-lg font-black text-slate-800 tracking-wide">
                                <i class="fa-solid fa-network-wired text-purple-500 mr-2"></i> Mode Resto
                            </h3>
                        </div>
                        <div class="mb-2">
                            <p class="text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">Alamat IP Server Lokal:</p>
                            <p class="text-base font-bold text-slate-700">-</p>
                        </div>
                        <div class="flex items-center gap-2 mt-4 bg-slate-50 p-2 rounded-lg border border-slate-100 inline-flex">
                            <div class="w-2.5 h-2.5 rounded-full bg-slate-300"></div>
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Dinonaktifkan</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 p-6 opacity-60 grayscale-[30%]">
                        <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                            <h3 class="text-lg font-black text-slate-800 tracking-wide">
                                <i class="fa-solid fa-desktop text-orange-500 mr-2"></i> Customer Display
                            </h3>
                        </div>
                        <div class="mb-2">
                            <p class="text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">Alamat IP / Port:</p>
                            <p class="text-base font-bold text-slate-700">-</p>
                        </div>
                        <div class="flex items-center gap-2 mt-4 bg-slate-50 p-2 rounded-lg border border-slate-100 inline-flex">
                            <div class="w-2.5 h-2.5 rounded-full bg-slate-300"></div>
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Dinonaktifkan</span>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>