<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Stok Opname - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="opnameApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-barcode mr-2"></i>Stok Opname</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="border-l border-blue-400 pl-3 ml-1">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm shadow-rose-500/30" title="Keluar">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="max-w-3xl mx-auto space-y-6">
                
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden relative">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-black text-slate-700 uppercase tracking-widest text-xs"><i class="fa-solid fa-camera text-primary mr-1"></i> Mode Scanner</h3>
                    </div>
                    
                    <div class="p-6 flex flex-col items-center">
                        
                        <div id="reader" class="w-full max-w-sm mb-4 rounded-xl overflow-hidden border-2 border-primary/20" x-show="isCameraOpen"></div>
                        
                        <div class="flex gap-2 w-full max-w-sm">
                            <button @click="toggleCamera()" :class="isCameraOpen ? 'bg-rose-500 hover:bg-rose-600 text-white' : 'bg-slate-800 hover:bg-slate-900 text-white'" class="px-4 py-3 rounded-xl font-black transition-all shadow-sm flex items-center justify-center gap-2 whitespace-nowrap">
                                <i class="fa-solid" :class="isCameraOpen ? 'fa-video-slash' : 'fa-camera'"></i> 
                                <span class="hidden sm:inline" x-text="isCameraOpen ? 'Tutup Kamera' : 'Buka Kamera HP'"></span>
                            </button>
                            
                            <div class="relative flex-1">
                                <i class="fa-solid fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" x-model="barcodeInput" @keyup.enter="searchBarcode()" placeholder="Atau ketik/scan SKU di sini..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-4 py-3 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-black text-slate-700 uppercase">
                            </div>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 mt-3 text-center">Tekan "Enter" jika menggunakan Scanner Tembak (Hardware).</p>
                    </div>
                </div>

                <div x-show="scannedProduct" class="bg-white rounded-[1.5rem] shadow-sm border border-primary/30 overflow-hidden relative" x-transition>
                    <div class="absolute -right-4 -bottom-4 opacity-5 text-primary text-9xl pointer-events-none"><i class="fa-solid fa-box-open"></i></div>
                    
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="bg-blue-100 text-blue-600 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border border-blue-200" x-text="scannedProduct?.code"></span>
                                <h3 class="text-2xl font-black text-slate-800 mt-2" x-text="scannedProduct?.name"></h3>
                                <p class="text-sm font-bold text-slate-500" x-text="scannedProduct?.category"></p>
                            </div>
                            <button @click="resetScan()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-rose-100 hover:text-rose-600 text-slate-400 transition-colors"><i class="fa-solid fa-xmark"></i></button>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex flex-col items-center">
                                <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Stok Di Sistem</span>
                                <span class="text-4xl font-black text-slate-800" x-text="scannedProduct?.stock"></span>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl flex flex-col items-center relative">
                                <span class="text-[10px] font-black uppercase text-blue-600 tracking-widest">Stok Fisik Nyata</span>
                                <input type="number" x-model="actualStock" class="w-24 bg-white border border-blue-300 rounded-xl px-2 py-1 text-center font-black text-3xl text-primary outline-none focus:ring-2 focus:ring-primary/50 mt-1" autofocus>
                            </div>
                        </div>

                        <div class="space-y-4 relative z-10">
                            <div class="flex justify-between items-center p-3 rounded-xl border" :class="selisih === 0 ? 'bg-slate-50 border-slate-200' : (selisih > 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200')">
                                <span class="font-black text-xs uppercase tracking-widest" :class="selisih === 0 ? 'text-slate-500' : (selisih > 0 ? 'text-emerald-600' : 'text-rose-600')">Selisih Opname</span>
                                <span class="text-xl font-black" :class="selisih === 0 ? 'text-slate-700' : (selisih > 0 ? 'text-emerald-600' : 'text-rose-600')" x-text="(selisih > 0 ? '+' : '') + selisih"></span>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 mb-1.5 uppercase">Keterangan / Alasan (Opsional)</label>
                                <input type="text" x-model="opnameNotes" placeholder="Misal: Hilang, Basi, atau Salah Hitung..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700">
                            </div>
                            
                            <button @click="saveOpname()" :disabled="isSaving || selisih === 0" class="w-full bg-primary hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-lg shadow-primary/30 transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fa-solid fa-floppy-disk" :class="isSaving ? 'fa-fade' : ''"></i> SIMPAN PENYESUAIAN STOK
                            </button>
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