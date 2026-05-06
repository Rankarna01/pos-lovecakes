<?php
$current_uri = $_SERVER['REQUEST_URI'];

// Fungsi styling jika menu aktif (Biru)
function getNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) return 'bg-primary/10 text-primary font-bold';
    return 'text-secondary hover:bg-slate-50 hover:text-primary font-medium';
}

// Cek apakah ada submenu yang aktif agar Dropdown mekar otomatis
function isDropdownActive($paths, $current_uri) {
    foreach ($paths as $path) {
        if (strpos($current_uri, $path) !== false) return true;
    }
    return false;
}

// Simulasi Cek Hak Akses (Nanti dihubungkan ke session/DB)
function hasAccess($menu_key) {
    // Contoh: Jika user adalah Kasir, mungkin dia tidak punya akses ke 'menu_pengaturan'
    // Untuk saat ini kita return true agar semua menu muncul saat kamu kembangkan
    return true; 
}
?>

<aside id="main-sidebar" class="w-[260px] bg-surface border-r border-slate-200 flex-col shadow-sm fixed inset-y-0 left-0 z-[70] transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 flex">

    <!-- Header Logo -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 shrink-0 bg-white">
        <h1 class="font-black text-primary text-xl flex items-center gap-2">
            <i class="fa-solid fa-store"></i> Love Cakes
        </h1>
        <button onclick="toggleSidebar()" class="md:hidden text-secondary hover:text-danger p-2 rounded-lg bg-slate-50 hover:bg-red-50 transition-colors">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
    </div>

    <!-- Area Menu Scrollable -->
    <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto custom-scrollbar bg-slate-50/30">

        <!-- 1. DASHBOARD -->
        <?php if(hasAccess('menu_dashboard')): ?>
        <a href="<?= BASE_URL ?>pos/dashboard/" title="Dashboard" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all <?= getNavClass('/pos/dashboard/', $current_uri) ?>">
            <i class="fa-solid fa-chart-pie w-6 text-center text-lg shrink-0"></i> 
            <span class="text-sm sidebar-text whitespace-nowrap transition-all duration-300 opacity-100">Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- 2. PRODUK & INVENTORY (Dropdown) -->
        <?php if(hasAccess('menu_produk')): 
            $paths = ['/pos/produk/deposit/', '/pos/produk/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div>
            <button onclick="toggleSubmenu('sub-produk', 'icon-produk')" class="w-full flex items-center justify-between px-3 py-3 rounded-xl transition-all <?= $isActive ? 'text-primary font-bold' : 'text-secondary hover:bg-slate-50 hover:text-primary font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-boxes-stacked w-6 text-center text-lg shrink-0"></i>
                    <span class="text-sm sidebar-text whitespace-nowrap">Produk & Inventory</span>
                </div>
                <i id="icon-produk" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-produk" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/produk" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/produk/', $current_uri) ?>">Produk</a>
                <a href="<?= BASE_URL ?>pos/produk/inventory/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/produk/inventory/', $current_uri) ?>">Inventory</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. PEMASARAN (Dropdown) -->
        <?php if(hasAccess('menu_pemasaran')): 
            $paths = ['/pos/pemasaran/crm/', '/pos/pemasaran/voucher/', '/pos/pemasaran/poin/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div>
            <button onclick="toggleSubmenu('sub-pemasaran', 'icon-pemasaran')" class="w-full flex items-center justify-between px-3 py-3 rounded-xl transition-all <?= $isActive ? 'text-primary font-bold' : 'text-secondary hover:bg-slate-50 hover:text-primary font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-bullhorn w-6 text-center text-lg shrink-0"></i>
                    <span class="text-sm sidebar-text whitespace-nowrap">Pemasaran</span>
                </div>
                <i id="icon-pemasaran" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-pemasaran" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/pemasaran/crm/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pemasaran/crm/', $current_uri) ?>">CRM</a>
                <a href="<?= BASE_URL ?>pos/pemasaran/voucher/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pemasaran/voucher/', $current_uri) ?>">Voucher & Diskon</a>
                <a href="<?= BASE_URL ?>pos/pemasaran/loyalitas/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pemasaran/loyalitas/', $current_uri) ?>">Poin Loyalitas</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- 4. PELANGGAN & SUPPLIER -->
        <?php if(hasAccess('menu_pelanggan')): ?>
        <a href="<?= BASE_URL ?>pos/kontak/" title="Pelanggan & Supplier" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all <?= getNavClass('/pos/kontak/', $current_uri) ?>">
            <i class="fa-solid fa-users w-6 text-center text-lg shrink-0"></i> 
            <span class="text-sm sidebar-text whitespace-nowrap transition-all duration-300 opacity-100">Pelanggan & Supplier</span>
        </a>
        <?php endif; ?>

        <!-- 5. TRANSAKSI & EWALLET (Dropdown) -->
        <?php if(hasAccess('menu_transaksi')): 
            $paths = ['/pos/transaksi/penjualan/', '/pos/transaksi/pembelian/', '/pos/transaksi/arus_kas/', '/pos/transaksi/pembayaran_digital/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div>
            <button onclick="toggleSubmenu('sub-transaksi', 'icon-transaksi')" class="w-full flex items-center justify-between px-3 py-3 rounded-xl transition-all <?= $isActive ? 'text-primary font-bold' : 'text-secondary hover:bg-slate-50 hover:text-primary font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-wallet w-6 text-center text-lg shrink-0"></i>
                    <span class="text-sm sidebar-text whitespace-nowrap">Transaksi & Ewallet</span>
                </div>
                <i id="icon-transaksi" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-transaksi" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/transaksi/penjualan/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/transaksi/penjualan/', $current_uri) ?>">Penjualan</a>
                <a href="<?= BASE_URL ?>pos/transaksi/pembelian/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/transaksi/pembelian/', $current_uri) ?>">Pembelian</a>
                <a href="<?= BASE_URL ?>pos/transaksi/arus_kas/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/transaksi/arus_kas/', $current_uri) ?>">Pendapatan & Pengeluaran</a>
                <a href="<?= BASE_URL ?>pos/transaksi/pembayaran_digital/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/transaksi/pembayaran_digital/', $current_uri) ?>">Pembayaran Digital</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- 6. LAPORAN & PEMBUKUAN (Dropdown) -->
        <?php if(hasAccess('menu_laporan')): 
            $paths = ['/pos/laporan/pencairan/', '/pos/laporan/umum/', '/pos/laporan/akuntansi/', '/pos/laporan/pihak_ketiga/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div>
            <button onclick="toggleSubmenu('sub-laporan', 'icon-laporan')" class="w-full flex items-center justify-between px-3 py-3 rounded-xl transition-all <?= $isActive ? 'text-primary font-bold' : 'text-secondary hover:bg-slate-50 hover:text-primary font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-file-invoice-dollar w-6 text-center text-lg shrink-0"></i>
                    <span class="text-sm sidebar-text whitespace-nowrap">Laporan & Pembukuan</span>
                </div>
                <i id="icon-laporan" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-laporan" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/laporan/pencairan/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/laporan/pencairan/', $current_uri) ?>">Pencairan Dana</a>
                <a href="<?= BASE_URL ?>pos/laporan/umum/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/laporan/umum/', $current_uri) ?>">Laporan</a>
                <a href="<?= BASE_URL ?>pos/laporan/akuntansi/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/laporan/akuntansi/', $current_uri) ?>">Akuntansi</a>
                <a href="<?= BASE_URL ?>pos/laporan/pihak_ketiga/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/laporan/pihak_ketiga/', $current_uri) ?>">Akuntansi Pihak Ketiga</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- 7. JUAL ONLINE (Dropdown) -->
        <?php if(hasAccess('menu_jual_online')): 
            $paths = ['/pos/online/pemesanan/', '/pos/online/toko/', '/pos/online/marketplace/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div>
            <button onclick="toggleSubmenu('sub-online', 'icon-online')" class="w-full flex items-center justify-between px-3 py-3 rounded-xl transition-all <?= $isActive ? 'text-primary font-bold' : 'text-secondary hover:bg-slate-50 hover:text-primary font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-globe w-6 text-center text-lg shrink-0"></i>
                    <span class="text-sm sidebar-text whitespace-nowrap">Jual Online</span>
                </div>
                <i id="icon-online" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-online" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/online/pemesanan/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/online/pemesanan/', $current_uri) ?>">Pemesanan Online</a>
                <a href="<?= BASE_URL ?>pos/online/toko/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/online/toko/', $current_uri) ?>">Toko Online</a>
                <a href="<?= BASE_URL ?>pos/online/marketplace/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/online/marketplace/', $current_uri) ?>">Marketplace</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- 8. MANAJEMEN KARYAWAN (Dropdown) -->
        <?php if(hasAccess('menu_karyawan')): 
            $paths = ['/pos/karyawan/data/', '/pos/karyawan/presensi/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div>
            <button onclick="toggleSubmenu('sub-karyawan', 'icon-karyawan')" class="w-full flex items-center justify-between px-3 py-3 rounded-xl transition-all <?= $isActive ? 'text-primary font-bold' : 'text-secondary hover:bg-slate-50 hover:text-primary font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-id-badge w-6 text-center text-lg shrink-0"></i>
                    <span class="text-sm sidebar-text whitespace-nowrap">Manajemen Karyawan</span>
                </div>
                <i id="icon-karyawan" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-karyawan" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/karyawan/data/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/karyawan/data/', $current_uri) ?>">Karyawan</a>
                <a href="<?= BASE_URL ?>pos/karyawan/presensi/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/karyawan/presensi/', $current_uri) ?>">Presensi</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- 9. KEMITRAAN & PERMODALAN -->
        <?php if(hasAccess('menu_kemitraan')): ?>
        <a href="<?= BASE_URL ?>pos/kemitraan/" title="Kemitraan & Permodalan" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all <?= getNavClass('/pos/kemitraan/', $current_uri) ?>">
            <i class="fa-solid fa-handshake w-6 text-center text-lg shrink-0"></i> 
            <span class="text-sm sidebar-text whitespace-nowrap transition-all duration-300 opacity-100">Kemitraan & Permodalan</span>
        </a>
        <?php endif; ?>

        <div class="my-4 border-t border-slate-200 w-full"></div>

        <!-- 10. PENGATURAN (Dropdown Besar) -->
        <?php if(hasAccess('menu_pengaturan')): 
            $paths = ['/pos/pengaturan/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div>
            <button onclick="toggleSubmenu('sub-pengaturan', 'icon-pengaturan')" class="w-full flex items-center justify-between px-3 py-3 rounded-xl transition-all <?= $isActive ? 'text-primary font-bold' : 'text-secondary hover:bg-slate-50 hover:text-primary font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-gear w-6 text-center text-lg shrink-0"></i>
                    <span class="text-sm sidebar-text whitespace-nowrap">Pengaturan</span>
                </div>
                <i id="icon-pengaturan" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-pengaturan" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/pengaturan/toko/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/toko/', $current_uri) ?>">Toko</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/support/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/support/', $current_uri) ?>">Akses Support</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/pos/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/pos/', $current_uri) ?>">Point Of Sale</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/pembulatan/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/pembulatan/', $current_uri) ?>">Pembulatan</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/pajak/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/pajak/', $current_uri) ?>">Pajak & Biaya</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/pengiriman/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/pengiriman/', $current_uri) ?>">Pengiriman</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/mata_uang/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/mata_uang/', $current_uri) ?>">Mata Uang</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/invoice/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/invoice/', $current_uri) ?>">Invoice Penjualan</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/notifikasi/" class="block px-3 py-2 text-xs rounded-lg transition-all <?= getNavClass('/pos/pengaturan/notifikasi/', $current_uri) ?>">Notifikasi</a>
            </div>
        </div>
        <?php endif; ?>

    </nav>
</aside>

<!-- Overlay untuk Mobile -->
<div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-[60] hidden md:hidden backdrop-blur-sm transition-opacity opacity-0 duration-300"></div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<script>
    // Fungsi buka/tutup Sidebar Mobile
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

    // Fungsi buka/tutup Submenu (Dropdown)
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