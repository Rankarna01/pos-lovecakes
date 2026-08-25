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

    /* Sidebar selalu tersembunyi default, muncul sebagai floating overlay */
    #main-sidebar {
        position: fixed;
        inset-y: 0;
        top: 0;
        bottom: 0;
        left: 0;
        height: 100vh;
        height: 100dvh;
        max-height: 100vh;
        max-height: 100dvh;
        z-index: 9999;
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 280px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #main-sidebar.sidebar-open {
        transform: translateX(0);
    }

    /* Overlay gelap di belakang sidebar */
    #sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        z-index: 9998;
        backdrop-filter: blur(3px);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    #sidebar-overlay.overlay-visible {
        display: block;
        opacity: 1;
    }

    /* Tombol Toggle Floating */
    #sidebar-toggle-btn {
        position: fixed;
        top: 14px;
        left: 14px;
        z-index: 9997;
        width: 40px;
        height: 40px;
        background: #1e293b;
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: none;
        box-shadow: 0 4px 14px rgba(30,41,59,0.3);
        transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        font-size: 16px;
    }
    #sidebar-toggle-btn:hover {
        background: #2563eb;
        transform: scale(1.08);
        box-shadow: 0 6px 18px rgba(37,99,235,0.4);
    }
    #sidebar-toggle-btn:active {
        transform: scale(0.95);
    }
</style>

<!-- ===== TOMBOL TOGGLE FLOATING ===== -->
<button id="sidebar-toggle-btn" onclick="toggleSidebar()" title="Menu Navigasi" aria-label="Toggle Sidebar">
    <i class="fa-solid fa-ellipsis-vertical"></i>
</button>

<!-- ===== SIDEBAR KASIR ===== -->
<aside id="main-sidebar" class="bg-white border-r border-slate-200 flex-col shadow-2xl flex h-screen max-h-screen">

    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-100 shrink-0 bg-white">
        <h1 class="font-black text-primary text-lg flex items-center gap-2 tracking-tight">
            <i class="fa-solid fa-store text-blue-600"></i> Love Cakes
        </h1>
        <button onclick="toggleSidebar()" class="text-slate-400 hover:text-rose-500 p-2 rounded-lg hover:bg-rose-50 transition-colors" title="Tutup Menu">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <?php if (!empty($_SESSION['pos_store_name'])): ?>
    <div class="px-4 pt-4 pb-1 shrink-0 bg-white">
        <div class="bg-gradient-to-r from-amber-500/15 to-orange-500/15 border border-amber-500/30 rounded-xl p-2.5 flex items-center gap-3 text-amber-800 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0 font-bold text-sm shadow">
                <i class="fa-solid fa-store"></i>
            </div>
            <div class="overflow-hidden">
                <div class="text-[9px] font-black uppercase tracking-wider text-amber-600 leading-tight">Lokasi / Outlet</div>
                <div class="text-xs font-black truncate text-amber-900"><?= htmlspecialchars($_SESSION['pos_store_name']) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <nav class="flex-1 min-h-0 px-4 py-5 space-y-1 overflow-y-auto custom-scrollbar bg-white" style="-webkit-overflow-scrolling: touch;">

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
            $paths = ['/pos/produk/deposit/', '/pos/produk/', '/pos/produk/inventory/', '/pos/produk/mutasi/', '/pos/produk/cetak_barcode/']; 
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
                <a href="<?= BASE_URL ?>pos/produk/mutasi/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/mutasi/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Mutasi Antar Store</a>
                <a href="<?= BASE_URL ?>pos/produk/cetak_barcode/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/cetak_barcode/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Cetak Barcode SKU</a>
                <a href="<?= BASE_URL ?>pos/produk/inventory/" class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/inventory/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Inventory Gudang</a>
            </div>
        </div>

        <a href="<?= BASE_URL ?>pos/kasir/laporan_shift/" title="Laporan Shift" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/kasir/laporan_shift/', $current_uri) ?>">
            <i class="fa-solid fa-clock-rotate-left w-5 text-center text-lg shrink-0"></i> 
            <span class="text-sm whitespace-nowrap transition-all duration-300">Laporan Shift</span>
        </a>

        <a href="<?= BASE_URL ?>pos/kasir/laporan_custom/" title="Laporan Item Custom" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/kasir/laporan_custom/', $current_uri) ?>">
            <i class="fa-solid fa-pen-to-square w-5 text-center text-lg shrink-0 <?= strpos($current_uri, '/pos/kasir/laporan_custom/') !== false ? 'text-violet-600' : 'text-violet-400' ?>"></i>
            <span class="text-sm whitespace-nowrap transition-all duration-300">Laporan Item Custom</span>
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

        <div class="mb-6">
            <a href="<?= BASE_URL ?>pos/pengaturan/printer/" title="Pengaturan Printer" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all <?= getNavClass('/pos/pengaturan/printer/', $current_uri) ?>">
                <i class="fa-solid fa-print w-5 text-center text-lg shrink-0"></i>
                <span class="text-sm whitespace-nowrap transition-all duration-300">Printer</span>
            </a>
        </div>

        <!-- ===== TOMBOL LOGOUT KASIR ===== -->
        <div class="mt-4 px-2 pb-8">
            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center gap-3 px-3 py-2.5 mb-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-user text-primary text-xs"></i>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-black text-slate-700 truncate"><?= htmlspecialchars($_SESSION['pos_nama'] ?? $_SESSION['pos_username'] ?? 'Kasir') ?></p>
                        <p class="text-[10px] text-slate-400 font-medium capitalize"><?= htmlspecialchars($_SESSION['pos_role'] ?? 'kasir') ?></p>
                    </div>
                </div>
                <button onclick="doLogoutKasir()" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold transition-all group border border-rose-100 hover:border-rose-200">
                    <i class="fa-solid fa-power-off w-5 text-center text-base shrink-0 group-hover:rotate-12 transition-transform"></i>
                    <span class="text-sm whitespace-nowrap">Keluar</span>
                </button>
            </div>
        </div>

    </nav>
</aside>

<!-- Overlay gelap -->
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const isOpen  = sidebar.classList.contains('sidebar-open');

        if (isOpen) {
            // Tutup
            sidebar.classList.remove('sidebar-open');
            overlay.classList.remove('overlay-visible');
        } else {
            // Buka
            sidebar.classList.add('sidebar-open');
            overlay.classList.add('overlay-visible');
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

    // Tutup sidebar jika tekan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const sidebar = document.getElementById('main-sidebar');
            if (sidebar.classList.contains('sidebar-open')) toggleSidebar();
        }
    });

    // ===== FUNGSI LOGOUT KASIR - SELF CONTAINED =====
    function doLogoutKasir() {
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