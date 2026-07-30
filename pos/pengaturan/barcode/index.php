<?php
require_once '../../../config/auth.php';
$page_title = "Pengaturan Cetak Barcode - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="barcodeSettingsApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-barcode mr-2"></i>Pengaturan Cetak Barcode</h2>
                    <p class="text-[11px] text-blue-200 font-bold mt-0.5">Konfigurasi tampilan, ukuran, dan format label stiker barcode produk</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="saveSettings()" :disabled="isSaving" class="bg-white text-primary hover:bg-blue-50 font-black px-5 py-2 rounded-xl shadow-sm transition-all flex items-center gap-2 disabled:opacity-50">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Pengaturan'"></span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            
            <div x-show="isLoading" class="flex items-center justify-center py-20">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
            </div>

            <div x-show="!isLoading" class="w-full max-w-full grid grid-cols-1 xl:grid-cols-5 gap-6">

                <!-- ===================== KOLOM KIRI: SEMUA PENGATURAN ===================== -->
                <div class="xl:col-span-3 space-y-5">

                    <!-- CARD 1: Format & Dimensi Barcode -->
                    <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-blue-50/50 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                                <i class="fa-solid fa-ruler-combined text-primary text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800 text-base">Dimensi Barcode</h4>
                                <p class="text-xs font-bold text-slate-400 mt-0.5">Tinggi, lebar garis, dan format encoding barcode</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-5">
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Tinggi Barcode (px)</label>
                                <input type="number" x-model.number="settings.barcode_height" min="15" max="100" @input="renderPreview()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-primary font-black text-sm">
                                <p class="text-[10px] text-slate-400 mt-1">Rekomendasi: 25-50</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Lebar Garis (1-3)</label>
                                <input type="number" x-model.number="settings.barcode_width" min="1" max="3" step="0.5" @input="renderPreview()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-primary font-black text-sm">
                                <p class="text-[10px] text-slate-400 mt-1">Default: 1</p>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Format / Tipe Barcode</label>
                                <select x-model="settings.barcode_format" @change="renderPreview()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-primary font-black text-sm">
                                    <option value="CODE128">CODE128 (Universal, Paling Umum)</option>
                                    <option value="CODE39">CODE39 (Huruf Kapital & Angka)</option>
                                    <option value="EAN13">EAN-13 (Standar Ritel, 12 digit)</option>
                                    <option value="EAN8">EAN-8 (Ritel Compact, 7 digit)</option>
                                    <option value="UPC">UPC-A (Pasar Amerika, 11 digit)</option>
                                    <option value="ITF14">ITF-14 (Barcode Logistik/Karton)</option>
                                    <option value="MSI">MSI (Manajemen Inventaris)</option>
                                    <option value="pharmacode">Pharmacode (Farmasi)</option>
                                </select>
                                <p class="text-[10px] text-amber-500 font-bold mt-1">
                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                    EAN13/EAN8/UPC memerlukan SKU produk dengan jumlah digit yang tepat
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: Ukuran Kertas Stiker -->
                    <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-emerald-50/50 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                                <i class="fa-solid fa-file-invoice text-emerald-600 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800 text-base">Ukuran Kertas / Stiker</h4>
                                <p class="text-xs font-bold text-slate-400 mt-0.5">Sesuaikan dengan kertas stiker Thermal yang kamu gunakan</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <template x-for="size in paperSizes" :key="size.value">
                                    <button @click="settings.barcode_paper_size = size.value; renderPreview();" 
                                        :class="settings.barcode_paper_size === size.value ? 'ring-2 ring-primary bg-blue-50 border-primary text-primary' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                                        class="border-2 rounded-xl p-3 text-center transition-all">
                                        <div class="font-black text-sm" x-text="size.label"></div>
                                        <div class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="size.desc"></div>
                                    </button>
                                </template>
                            </div>

                            <!-- Custom Size -->
                            <div x-show="settings.barcode_paper_size === 'custom'" class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                                <div>
                                    <label class="block text-xs font-black text-slate-500 mb-2 uppercase">Lebar Stiker (mm)</label>
                                    <input type="number" x-model.number="settings.barcode_paper_custom_w" min="20" max="120" @input="renderPreview()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-primary font-black text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-500 mb-2 uppercase">Tinggi Stiker (mm)</label>
                                    <input type="number" x-model.number="settings.barcode_paper_custom_h" min="15" max="80" @input="renderPreview()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-primary font-black text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-wide">Jumlah Kolom Per Baris (Cetak A4)</label>
                                <div class="flex items-center gap-2">
                                    <template x-for="n in [1, 2, 3, 4, 5, 6]" :key="n">
                                        <button @click="settings.barcode_per_row = n; renderPreview();"
                                            :class="settings.barcode_per_row == n ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                            class="w-10 h-10 rounded-xl font-black text-sm transition-all" x-text="n">
                                        </button>
                                    </template>
                                    <span class="text-xs font-bold text-slate-400">kolom per baris</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: Tampilan Teks pada Label -->
                    <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-amber-50/50 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                                <i class="fa-solid fa-font text-amber-600 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800 text-base">Tampilan Teks pada Label</h4>
                                <p class="text-xs font-bold text-slate-400 mt-0.5">Pilih informasi apa saja yang muncul di stiker</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-2 gap-5">
                            <!-- Toggle: Nama Produk -->
                            <div class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                    <input type="checkbox" x-model="settings.barcode_show_name" true-value="1" false-value="0" @change="renderPreview()" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></div>
                                </label>
                                <div>
                                    <p class="font-black text-slate-700 text-sm">Tampilkan Nama Produk</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Nama produk akan muncul di stiker</p>
                                </div>
                            </div>

                            <!-- Posisi Nama -->
                            <div x-show="settings.barcode_show_name == 1" class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <div class="w-full">
                                    <p class="font-black text-slate-700 text-sm mb-2">Posisi Nama Produk</p>
                                    <div class="flex gap-2">
                                        <button @click="settings.barcode_name_position = 'top'; renderPreview();" 
                                            :class="settings.barcode_name_position === 'top' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600'"
                                            class="flex-1 py-1.5 rounded-lg font-black text-xs transition-all">
                                            <i class="fa-solid fa-arrow-up mr-1"></i> Atas
                                        </button>
                                        <button @click="settings.barcode_name_position = 'bottom'; renderPreview();"
                                            :class="settings.barcode_name_position === 'bottom' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600'"
                                            class="flex-1 py-1.5 rounded-lg font-black text-xs transition-all">
                                            <i class="fa-solid fa-arrow-down mr-1"></i> Bawah
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle: SKU Text -->
                            <div class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                    <input type="checkbox" x-model="settings.barcode_show_sku" true-value="1" false-value="0" @change="renderPreview()" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></div>
                                </label>
                                <div>
                                    <p class="font-black text-slate-700 text-sm">Tampilkan SKU / Kode Text</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Teks angka SKU di bawah barcode garis</p>
                                </div>
                            </div>

                            <!-- Toggle: Harga -->
                            <div class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                    <input type="checkbox" x-model="settings.barcode_show_price" true-value="1" false-value="0" @change="renderPreview()" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></div>
                                </label>
                                <div>
                                    <p class="font-black text-slate-700 text-sm">Tampilkan Harga</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Harga produk di bawah barcode</p>
                                </div>
                            </div>

                            <!-- Toggle: Expired Date -->
                            <div class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                    <input type="checkbox" x-model="settings.barcode_show_expired" true-value="1" false-value="0" @change="renderPreview()" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></div>
                                </label>
                                <div>
                                    <p class="font-black text-slate-700 text-sm">Tampilkan Tgl Kadaluarsa</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Untuk produk makanan/minuman</p>
                                </div>
                            </div>

                            <!-- Toggle: Kategori -->
                            <div class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                    <input type="checkbox" x-model="settings.barcode_show_category" true-value="1" false-value="0" @change="renderPreview()" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></div>
                                </label>
                                <div>
                                    <p class="font-black text-slate-700 text-sm">Tampilkan Kategori Produk</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Kategori produk di bagian atas stiker</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ===================== KOLOM KANAN: PREVIEW STIKER ===================== -->
                <div class="xl:col-span-2">
                    <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden sticky top-0">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <div>
                                <h4 class="font-black text-slate-800 text-base flex items-center gap-2">
                                    <i class="fa-solid fa-eye text-primary"></i> Preview Stiker
                                </h4>
                                <p class="text-xs font-bold text-slate-400 mt-0.5">Tampilan real-time sesuai pengaturan</p>
                            </div>
                            <span class="text-[10px] bg-emerald-100 text-emerald-700 font-black px-2.5 py-1 rounded-lg">LIVE PREVIEW</span>
                        </div>

                        <!-- Preview Area -->
                        <div class="p-6 flex items-center justify-center min-h-[280px] bg-slate-100/50">
                            <div id="barcode-preview-container" class="bg-white shadow-md rounded-xl flex flex-col items-center justify-center p-3 border border-slate-200 transition-all" style="min-width: 130px;">
                                
                                <!-- Nama di Atas -->
                                <p id="preview-name-top" class="text-center font-black text-slate-800 mb-1 leading-tight" style="font-size:10px; display:none;">CONTOH PRODUK A</p>
                                
                                <!-- Barcode SVG -->
                                <svg id="preview-barcode"></svg>

                                <!-- Harga -->
                                <p id="preview-price" class="text-center font-black text-slate-800 mt-1" style="font-size:10px;">Rp 45.000</p>
                                
                                <!-- Nama di Bawah -->
                                <p id="preview-name-bottom" class="text-center font-black text-slate-700 mt-1 leading-tight" style="font-size:10px;">CONTOH PRODUK A</p>

                                <!-- Kategori -->
                                <p id="preview-category" class="text-center text-slate-400 font-bold" style="font-size:9px; display:none;">KUE BASAH</p>

                                <!-- Expired -->
                                <p id="preview-expired" class="text-center font-bold text-rose-500" style="font-size:9px; display:none;">EXP: 01/08/2025</p>
                            </div>
                        </div>

                        <!-- Keterangan Ukuran Stiker -->
                        <div class="px-6 pb-4 space-y-2">
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs font-bold text-blue-700">
                                <i class="fa-solid fa-info-circle mr-1.5"></i>
                                Ukuran stiker aktif: 
                                <span class="font-black" x-text="getActivePaperLabel()"></span>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-500">
                                <i class="fa-solid fa-barcode mr-1.5"></i>
                                Format: <span class="font-black text-slate-700" x-text="settings.barcode_format"></span>
                                &nbsp;|&nbsp; Kolom: <span class="font-black text-slate-700" x-text="settings.barcode_per_row + ' per baris'"></span>
                            </div>
                        </div>

                        <!-- Tombol Simpan -->
                        <div class="p-4 border-t border-slate-100">
                            <button @click="saveSettings()" :disabled="isSaving" class="w-full bg-primary hover:bg-blue-600 disabled:bg-slate-300 text-white font-black py-3.5 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Pengaturan'"></span>
                            </button>
                            <a href="<?= BASE_URL ?>pos/produk/cetak_barcode/" class="mt-2 w-full flex items-center justify-center gap-2 text-xs font-black text-primary hover:text-blue-700 transition-all py-2">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i> Pergi ke Halaman Cetak Barcode
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
