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

        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Menu Utama (Admin)</div>

        <a href="<?= BASE_URL ?>pos/dashboard/" title="Dashboard" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/dashboard/', $current_uri) ?>">
            <i class="fa-solid fa-chart-pie w-5 text-center text-lg shrink-0"></i> 
            <span class="text-sm whitespace-nowrap transition-all duration-300">Dashboard</span>
        </a>

        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6 mb-2">Operasional</div>

        <!-- MENU PRODUK & INVENTORY DI-HIDE SESUAI PERMINTAAN CLIENT -->

        <?php 
            $paths = ['/pos/mitra/supplier/', '/pos/mitra/pelanggan/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-mitra', 'icon-mitra')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-handshake w-5 text-center text-lg shrink-0"></i> 
                    <span class="text-sm whitespace-nowrap">Mitra & Kontak</span>
                </div>
                <i id="icon-mitra" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-mitra" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/mitra/supplier/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/mitra/supplier/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Data Supplier</a>
                <a href="<?= BASE_URL ?>pos/mitra/pelanggan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/mitra/pelanggan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Data Pelanggan</a>
            </div>
        </div>


        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6 mb-2">Keuangan & Sales</div>

        <!-- MENU TRANSAKSI & EWALLET DI-HIDE SESUAI PERMINTAAN CLIENT -->

        <?php 
            $paths = ['/pos/laporan/ringkasan/', '/pos/laporan/penjualan_shift/', '/pos/laporan/produk_kategori/', '/pos/laporan/pelanggan/', '/pos/laporan/pencairan/', '/pos/laporan/akuntansi/', '/pos/laporan/pihak_ketiga/', '/pos/laporan/penjualan/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-laporan', 'icon-laporan')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-blue-50 text-blue-600 font-bold shadow-sm ring-1 ring-blue-100/50' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-lg shrink-0"></i>
                    <span class="text-sm whitespace-nowrap">Laporan Toko</span>
                </div>
                <i id="icon-laporan" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-laporan" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/laporan/ringkasan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/ringkasan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Ringkasan & Jam Ramai</a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Penjualan & Shift</a>
                <a href="<?= BASE_URL ?>pos/laporan/produk_kategori/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/produk_kategori/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Analisa Produk Laku</a>
                <a href="<?= BASE_URL ?>pos/laporan/pelanggan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pelanggan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Riwayat Pelanggan</a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Penjualan</a>
                <div class="my-1 border-t border-slate-100"></div> <a href="<?= BASE_URL ?>pos/laporan/pencairan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pencairan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Pencairan Dana</a>
                <a href="<?= BASE_URL ?>pos/laporan/akuntansi/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/akuntansi/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Akuntansi Internal</a>
                <a href="<?= BASE_URL ?>pos/laporan/pihak_ketiga/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pihak_ketiga/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Laporan Pihak Ketiga</a>
            </div>
        </div>

        <?php 
            $paths = ['/pos/pemasaran/crm/', '/pos/pemasaran/voucher/', '/pos/pemasaran/poin/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-pemasaran', 'icon-pemasaran')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-bullhorn w-5 text-center text-lg shrink-0"></i>
                    <span class="text-sm whitespace-nowrap">Pemasaran & Diskon</span>
                </div>
                <i id="icon-pemasaran" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-pemasaran" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/pemasaran/crm/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/crm/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> CRM Broadcast</a>
                <a href="<?= BASE_URL ?>pos/pemasaran/voucher/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/voucher/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Kelola Voucher</a>
                <a href="<?= BASE_URL ?>pos/pemasaran/poin-loyalitas/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/poin-loyalitas/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Poin Loyalitas</a>
            </div>
        </div>

        <?php 
            $paths = ['/pos/online/pemesanan/', '/pos/online/toko/', '/pos/online/marketplace/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-online', 'icon-online')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-globe w-5 text-center text-lg shrink-0"></i>
                    <span class="text-sm whitespace-nowrap">Manaj. Online</span>
                </div>
                <i id="icon-online" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-online" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/online/pemesanan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/online/pemesanan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Order Online</a>
                <a href="<?= BASE_URL ?>pos/online/toko/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/online/toko/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Toko / Web</a>
                <a href="<?= BASE_URL ?>pos/online/marketplace/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/online/marketplace/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Integrasi Grab/Gojek</a>
            </div>
        </div>

        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6 mb-2">Sistem</div>

        <a href="<?= BASE_URL ?>pos/kemitraan/" title="Kemitraan & Permodalan" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/kemitraan/', $current_uri) ?>">
            <i class="fa-solid fa-hand-holding-dollar w-5 text-center text-lg shrink-0"></i> 
            <span class="text-sm whitespace-nowrap transition-all duration-300">Kemitraan/Modal</span>
        </a>

        <?php 
            $paths = ['/pos/pengaturan/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-6">
            <button onclick="toggleSubmenu('sub-pengaturan', 'icon-pengaturan')" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-gear w-5 text-center text-lg shrink-0"></i>
                    <span class="text-sm whitespace-nowrap">Pengaturan Toko</span>
                </div>
                <i id="icon-pengaturan" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-pengaturan" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-11 pr-2">
                <a href="<?= BASE_URL ?>pos/pengaturan/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Setelan Global</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/toko/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/toko/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Identitas Toko</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/shift/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/shift/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Master Shift Kerja</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/pos/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/pos/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Sistem POS</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/printer/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/printer/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Printer</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/pajak/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/pajak/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Pajak & Pembulatan</a>
                <a href="<?= BASE_URL ?>pos/pengaturan/notifikasi/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/notifikasi/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Notifikasi Sistem</a>
            </div>
        </div>

        <!-- ===== TOMBOL LOGOUT ADMIN ===== -->
        <div class="mt-4 px-2 pb-4">
            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center gap-3 px-3 py-2.5 mb-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-user text-primary text-xs"></i>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-black text-slate-700 truncate"><?= htmlspecialchars($_SESSION['pos_nama'] ?? $_SESSION['pos_username'] ?? 'Admin') ?></p>
                        <p class="text-[10px] text-slate-400 font-medium capitalize"><?= htmlspecialchars($_SESSION['pos_role'] ?? 'admin') ?></p>
                    </div>
                </div>
                <button onclick="doLogoutAdmin()" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold transition-all group border border-rose-100 hover:border-rose-200">
                    <i class="fa-solid fa-power-off w-5 text-center text-base shrink-0 group-hover:rotate-12 transition-transform"></i>
                    <span class="text-sm whitespace-nowrap">Keluar</span>
                </button>
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

    // ===== FUNGSI LOGOUT ADMIN - SELF CONTAINED =====
    function doLogoutAdmin() {
        var jalankanLogout = function() {
            try {
                var dbAuth = localforage.createInstance({ name: 'pos_db', storeName: 'auth_store' });
                dbAuth.removeItem('user_session').finally(function() {
                    window.location.href = '<?= BASE_URL ?>logout_action.php';
                });
            } catch(e) {
                window.location.href = '<?= BASE_URL ?>logout_action.php';
            }
        };
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Yakin mau Keluar?', text: 'Sesi Anda akan dihapus.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Keluar!', cancelButtonText: 'Batal'
            }).then(function(result) { if (result.isConfirmed) { jalankanLogout(); } });
        } else {
            if (confirm('Yakin mau Keluar?')) { jalankanLogout(); }
        }
    }
</script>