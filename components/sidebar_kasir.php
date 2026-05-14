<?php
$current_uri = $_SERVER['REQUEST_URI'];

function getNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) return 'bg-blue-50 text-blue-600 font-bold shadow-sm ring-1 ring-blue-100/50';
    return 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium';
}

function getSubNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) return 'text-blue-600 font-black bg-blue-50/50';
    return 'text-slate-500 hover:text-blue-600 hover:bg-slate-50 font-medium';
}

function isDropdownActive($paths, $current_uri) {
    foreach ($paths as $path) {
        if (strpos($current_uri, $path) !== false) return true;
    }
    return false;
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
    #main-sidebar { font-family: 'Poppins', sans-serif; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<aside id="main-sidebar" class="w-[260px] bg-white border-r border-slate-200 flex-col shadow-sm fixed inset-y-0 left-0 z-[70] transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 flex">

    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100 shrink-0 bg-white">
        <h1 class="font-black text-primary text-xl flex items-center gap-2 tracking-tight">
            <i class="fa-solid fa-store text-blue-600"></i> Love Cakes
        </h1>
        <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-rose-500 p-2 rounded-lg hover:bg-rose-50 transition-colors">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar bg-white">

        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Menu Utama (Kasir)</div>

        <a href="<?= BASE_URL ?>pos/kasir/" title="Mesin Kasir (POS)" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/kasir/', $current_uri) ?>">
            <div class="relative w-5 text-center shrink-0">
                <i class="fa-solid fa-cash-register text-lg <?= strpos($current_uri, '/pos/kasir/') !== false ? 'text-blue-600' : 'text-emerald-500' ?>"></i>
            </div>
            <span class="text-sm whitespace-nowrap transition-all duration-300 font-bold tracking-wide">Mesin Kasir</span>
        </a>

        <a href="<?= BASE_URL ?>pos/kasir_online/" title="Kasir Online" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all mb-4 <?= getNavClass('/pos/kasir_online/', $current_uri) ?>">
            <div class="relative w-5 text-center shrink-0">
                <i class="fa-solid fa-motorcycle text-lg <?= strpos($current_uri, '/pos/kasir_online/') !== false ? 'text-blue-600' : 'text-amber-500' ?>"></i>
            </div>
            <span class="text-sm whitespace-nowrap transition-all duration-300 font-bold tracking-wide">Kasir Online</span>
        </a>

        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6 mb-2">Operasional</div>

        <?php 
            $paths = ['/pos/produk/deposit/', '/pos/produk/', '/pos/produk/inventory/', '/pos/produk/opname/', '/pos/produk/cetak_barcode/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-produk', 'icon-produk')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-blue-50 text-blue-600 font-bold shadow-sm ring-1 ring-blue-100/50' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-boxes-stacked w-5 text-center text-lg shrink-0"></i>
                    <span class="text-sm whitespace-nowrap">Produk & Inventory</span>
                </div>
                <i id="icon-produk" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-produk" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/produk/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Katalog Produk</a>
                <a href="<?= BASE_URL ?>pos/produk/cetak_barcode/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/cetak_barcode/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Cetak Barcode SKU</a>
                <a href="<?= BASE_URL ?>pos/produk/inventory/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/inventory/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Inventory Gudang</a>
                <a href="<?= BASE_URL ?>pos/produk/opname/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/opname/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Stok Opname</a>
            </div>
        </div>

        <a href="<?= BASE_URL ?>pos/pengaturan/shift/" title="Laporan Shift" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/shift/', $current_uri) ?>">
            <i class="fa-solid fa-clock-rotate-left w-5 text-center text-lg shrink-0"></i> 
            <span class="text-sm whitespace-nowrap transition-all duration-300">Laporan Shift</span>
        </a>

        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6 mb-2">Keuangan & Sales</div>

        <?php 
            $paths = ['/pos/transaksi/penjualan/', '/pos/transaksi/pembelian/', '/pos/transaksi/arus_kas/', '/pos/transaksi/pembayaran_digital/', '/pos/transaksi/piutang/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-transaksi', 'icon-transaksi')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-wallet w-5 text-center text-lg shrink-0"></i>
                    <span class="text-sm whitespace-nowrap">Transaksi & Ewallet</span>
                </div>
                <i id="icon-transaksi" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-transaksi" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/transaksi/penjualan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/penjualan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Penjualan (Riwayat)</a>
                <a href="<?= BASE_URL ?>pos/transaksi/piutang/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/piutang/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Pelunasan DP (Piutang)</a>
                <a href="<?= BASE_URL ?>pos/transaksi/pembelian/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/pembelian/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Pembelian (Restock)</a>
                <a href="<?= BASE_URL ?>pos/transaksi/arus_kas/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/arus_kas/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Arus Kas (Petty Cash)</a>
                <a href="<?= BASE_URL ?>pos/transaksi/pembayaran_digital/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/pembayaran_digital/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Rekap QRIS & E-Wallet</a>
            </div>
        </div>

        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6 mb-2">Sistem</div>

        <?php 
            $paths = ['/pos/pengaturan/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-6">
            <button onclick="toggleSubmenu('sub-pengaturan', 'icon-pengaturan')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-gear w-5 text-center text-lg shrink-0"></i>
                    <span class="text-sm whitespace-nowrap">Pengaturan Dasar</span>
                </div>
                <i id="icon-pengaturan" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-pengaturan" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/pengaturan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Setelan Global</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/printer/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/printer/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Printer</a>
            </div>
        </div>

    </nav>
</aside>

<div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-[60] hidden md:hidden backdrop-blur-sm transition-opacity opacity-0 duration-300"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        if (sidebar.classList.contains('-translate-x-full')) {
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
            setTimeout(() => { overlay.classList.add('hidden'); }, 300);
        } else {
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
                overlay.classList.add('opacity-100');
            }, 10);
        }
    }

    function toggleSubmenu(menuId, iconId) {
        const menu = document.getElementById(menuId);
        const icon = document.getElementById(iconId);

        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            menu.classList.add('flex');
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
        } else {
            menu.classList.add('hidden');
            menu.classList.remove('flex');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
        }
    }
</script>