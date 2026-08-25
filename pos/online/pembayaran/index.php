<?php
require_once '../../../config/auth.php';
$page_title = "Metode Pembayaran Online - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="platformPaymentApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER ADMIN -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-credit-card text-amber-300"></i> Metode Pembayaran Platform Online
                    </h2>
                    <p class="text-xs text-blue-100 font-medium">Atur metode pembayaran yang aktif untuk masing-masing platform Food Delivery</p>
                </div>
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
            <div class="w-full max-w-5xl mx-auto space-y-6">

                <!-- 1. TAB SELECTOR PER PLATFORM -->
                <div class="bg-white rounded-2xl border border-slate-200 p-2 shadow-xs flex items-center gap-2 overflow-x-auto custom-scrollbar">
                    <button @click="activeTab = 'grabfood'" 
                            class="px-5 py-3 rounded-xl font-black text-xs transition-all flex items-center gap-2.5 shrink-0"
                            :class="activeTab === 'grabfood' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="fa-solid fa-motorcycle text-base"></i>
                        <span>GrabFood</span>
                    </button>

                    <button @click="activeTab = 'gofood'" 
                            class="px-5 py-3 rounded-xl font-black text-xs transition-all flex items-center gap-2.5 shrink-0"
                            :class="activeTab === 'gofood' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="fa-solid fa-utensils text-base"></i>
                        <span>GoFood</span>
                    </button>

                    <button @click="activeTab = 'shopeefood'" 
                            class="px-5 py-3 rounded-xl font-black text-xs transition-all flex items-center gap-2.5 shrink-0"
                            :class="activeTab === 'shopeefood' ? 'bg-orange-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="fa-solid fa-bag-shopping text-base"></i>
                        <span>ShopeeFood</span>
                    </button>

                    <button @click="activeTab = 'travelokaeats'" 
                            class="px-5 py-3 rounded-xl font-black text-xs transition-all flex items-center gap-2.5 shrink-0"
                            :class="activeTab === 'travelokaeats' ? 'bg-sky-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="fa-solid fa-plane-departure text-base"></i>
                        <span>TravelokaEats</span>
                    </button>
                </div>

                <!-- 2. KARTU UTAMA PENGATURAN METODE -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    
                    <!-- HEADER ACTIONS -->
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2.5 mb-1">
                                <h3 class="font-black text-slate-800 text-lg">Metode Pembayaran: <span class="capitalize text-primary" x-text="activePlatformInfo.platform_name || activeTab"></span></h3>
                            </div>
                            <p class="text-xs text-slate-400 font-bold">Pilihan metode di bawah ini akan otomatis muncul saat kasir memproses pesanan channel ini di Kasir Online.</p>
                        </div>

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-56">
                                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" x-model="searchQuery" placeholder="Cari metode..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-bold outline-none focus:border-primary focus:bg-white transition-colors">
                            </div>
                            
                            <button @click="openAddModal()" class="px-4 py-2.5 bg-primary hover:bg-blue-700 text-white rounded-xl font-black text-xs transition-all shadow-md shadow-blue-500/20 flex items-center gap-2 shrink-0">
                                <i class="fa-solid fa-plus"></i> Tambah Metode
                            </button>
                        </div>
                    </div>

                    <!-- TABEL METODE PEMBAYARAN -->
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-black uppercase text-[10px] tracking-wider">
                                    <th class="py-4 px-6">Nama Metode Pembayaran</th>
                                    <th class="py-4 px-4">Tipe / Kategori</th>
                                    <th class="py-4 px-4 text-center">Status di Kasir</th>
                                    <th class="py-4 px-6 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-bold text-slate-700">
                                
                                <template x-for="item in currentPlatformMethods" :key="item.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-base shrink-0">
                                                    <i class="fa-solid" :class="{
                                                        'fa-wallet': item.type === 'E-Wallet',
                                                        'fa-qrcode': item.type === 'QRIS',
                                                        'fa-building-columns': item.type === 'Transfer',
                                                        'fa-money-bill-transfer': item.type === 'Digital',
                                                        'fa-money-bill': item.type === 'Cash'
                                                    }"></i>
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-800 text-sm" x-text="item.name"></div>
                                                    <div class="text-[10px] font-mono text-slate-400" x-text="'Kode: ' + (item.code || '-')"></div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-4 px-4">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black"
                                                  :class="{
                                                    'bg-purple-50 text-purple-700 border border-purple-200': item.type === 'E-Wallet',
                                                    'bg-blue-50 text-blue-700 border border-blue-200': item.type === 'QRIS',
                                                    'bg-emerald-50 text-emerald-700 border border-emerald-200': item.type === 'Transfer',
                                                    'bg-slate-100 text-slate-700 border border-slate-200': item.type === 'Digital'
                                                  }"
                                                  x-text="item.type"></span>
                                        </td>

                                        <td class="py-4 px-4 text-center">
                                            <button @click="toggleStatus(item)" 
                                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                                    :class="parseInt(item.is_active) === 1 ? 'bg-emerald-500' : 'bg-slate-300'">
                                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                      :class="parseInt(item.is_active) === 1 ? 'translate-x-5' : 'translate-x-0'"></span>
                                            </button>
                                        </td>

                                        <td class="py-4 px-6 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button @click="openEditModal(item)" class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl text-xs font-black transition-colors" title="Edit Metode">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button @click="deleteMethod(item)" class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-black transition-colors" title="Hapus">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="currentPlatformMethods.length === 0">
                                    <td colspan="4" class="py-12 text-center text-slate-400">
                                        <i class="fa-solid fa-credit-card text-4xl mb-3 text-slate-300"></i>
                                        <p class="text-xs font-bold">Belum ada metode pembayaran untuk platform ini.</p>
                                        <button @click="openAddModal()" class="mt-3 px-4 py-2 bg-primary text-white rounded-xl text-xs font-black">
                                            + Tambah Metode Pertama
                                        </button>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH / EDIT METODE -->
    <div x-show="modal.show" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="modal.show = false"></div>
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 p-6 flex flex-col overflow-hidden border border-slate-200">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid" :class="modal.isEdit ? 'fa-pen-to-square text-blue-600' : 'fa-plus-circle text-primary'"></i>
                    <span x-text="modal.isEdit ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran'"></span>
                </h3>
                <button @click="modal.show = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="saveMethod()" class="space-y-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wide mb-1.5">Platform Channel</label>
                    <select x-model="modal.platform_code" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 font-bold text-slate-800 outline-none focus:border-primary focus:bg-white text-xs">
                        <option value="grabfood">GrabFood</option>
                        <option value="gofood">GoFood</option>
                        <option value="shopeefood">ShopeeFood</option>
                        <option value="travelokaeats">TravelokaEats</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wide mb-1.5">Nama Metode Pembayaran</label>
                    <input type="text" x-model="modal.name" required placeholder="Misal: Saldo GrabMerchant / OVO" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 font-bold text-slate-800 outline-none focus:border-primary focus:bg-white text-xs">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wide mb-1.5">Tipe Pembayaran</label>
                    <select x-model="modal.type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 font-bold text-slate-800 outline-none focus:border-primary focus:bg-white text-xs">
                        <option value="Digital">Digital / Saldo Merchant</option>
                        <option value="E-Wallet">E-Wallet (OVO/GoPay/ShopeePay)</option>
                        <option value="QRIS">QRIS</option>
                        <option value="Transfer">Transfer Bank</option>
                        <option value="Giro">Giro / Cek</option>
                    </select>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex gap-3">
                    <button type="button" @click="modal.show = false" class="py-2.5 px-4 rounded-xl font-black text-slate-500 bg-slate-100 hover:bg-slate-200 text-xs transition-colors">Batal</button>
                    <button type="submit" :disabled="modal.isSaving" class="flex-1 py-2.5 rounded-xl font-black text-white bg-primary hover:bg-blue-700 text-xs shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50">
                        <i class="fa-solid fa-save" x-show="!modal.isSaving"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="modal.isSaving"></i>
                        Simpan Metode
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
