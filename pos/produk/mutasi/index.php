<?php
require_once '../../../config/auth.php';
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Mutasi Stok Produk - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="mutasiApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-right-left mr-2"></i>Mutasi Stok Produk Katalog</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if (!empty($_SESSION['pos_store_name'])): ?>
                <div class="bg-black/20 text-amber-300 border border-white/20 px-3 py-1.5 rounded-lg text-xs font-black flex items-center gap-2 shadow-inner">
                    <i class="fa-solid fa-store text-amber-400"></i> Outlet: <?= htmlspecialchars($_SESSION['pos_store_name']) ?>
                </div>
                <?php endif; ?>

                <button @click="loadInitData()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 border border-white/10 shadow-sm">
                    <i class="fa-solid fa-rotate" :class="isLoading ? 'fa-spin' : ''"></i> Refresh
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc]">
            <div class="w-full max-w-full space-y-6">
                
                <!-- CARD FORM MUTASI -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-5">
                        <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                            <i class="fa-solid fa-truck-ramp-box"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-lg">Form Mutasi / Transfer Antar Store</h3>
                            <p class="text-xs text-slate-400 font-bold">Pindahkan stok produk katalog dari satu store / outlet ke store lainnya</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitMutation()" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        <div class="md:col-span-4">
                            <label class="block text-xs font-black text-slate-600 mb-1.5 uppercase tracking-wide">Pilih Produk Katalog</label>
                            <select x-model="form.product_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm text-slate-700 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                <option value="">-- Pilih Produk --</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="p.name + ' (' + p.code + ') | Stok Global: ' + p.stock"></option>
                                </template>
                            </select>
                            <!-- Info Stok di Pilihan Produk -->
                            <div x-show="form.product_id" class="mt-1.5 flex flex-wrap gap-2 text-[11px]">
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <span class="bg-amber-500/10 text-amber-800 border border-amber-500/20 px-2 py-0.5 rounded font-black">
                                        <i class="fa-solid fa-store text-amber-500"></i> <span x-text="wh.name + ': ' + getStockInWh(form.product_id, wh.id)"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-600 mb-1.5 uppercase tracking-wide">Dari Store Asal</label>
                            <select x-model="form.from_warehouse_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm text-slate-700 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                <option value="">-- Asal --</option>
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-600 mb-1.5 uppercase tracking-wide">Ke Store Tujuan</label>
                            <select x-model="form.to_warehouse_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm text-slate-700 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                <option value="">-- Tujuan --</option>
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" :disabled="wh.id == form.from_warehouse_id" x-text="wh.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs font-black text-slate-600 mb-1.5 uppercase tracking-wide">Qty</label>
                            <input type="number" x-model="form.quantity" required min="1" placeholder="0" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm text-center text-slate-800 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-black text-slate-600 mb-1.5 uppercase tracking-wide">Catatan Mutasi</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="form.notes" placeholder="Alasan mutasi..." class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm text-slate-700 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                <button type="submit" :disabled="isSubmitting" class="bg-primary hover:bg-blue-700 text-white font-black px-5 py-2.5 rounded-xl transition-all shadow-md shadow-primary/20 flex items-center gap-2 disabled:opacity-50 shrink-0">
                                    <i class="fa-solid fa-paper-plane" :class="isSubmitting ? 'fa-spin fa-spinner' : ''"></i>
                                    <span>Mutasikan</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- TABLE RIWAYAT MUTASI -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-clock-rotate-left text-primary text-lg"></i>
                            <h3 class="font-black text-slate-800 text-base">Riwayat Mutasi Produk</h3>
                        </div>
                        <span class="text-xs font-black bg-slate-200 text-slate-600 px-3 py-1 rounded-full" x-text="mutations.length + ' Data'"></span>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black text-center w-16">No</th>
                                    <th class="p-4 font-black">Tanggal</th>
                                    <th class="p-4 font-black">No. Mutasi</th>
                                    <th class="p-4 font-black">Produk SKU</th>
                                    <th class="p-4 font-black">Asal ➔ Tujuan</th>
                                    <th class="p-4 font-black text-center">Qty Mutasi</th>
                                    <th class="p-4 font-black">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="mutations.length === 0">
                                    <td colspan="7" class="p-10 text-center">
                                        <div class="text-slate-300 text-5xl mb-3"><i class="fa-solid fa-folder-open"></i></div>
                                        <p class="text-slate-500 font-bold">Belum ada riwayat mutasi antar store.</p>
                                    </td>
                                </tr>
                                <template x-for="(m, idx) in mutations" :key="m.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4 text-center text-slate-400 font-bold text-xs" x-text="idx + 1"></td>
                                        <td class="p-4 font-semibold text-slate-700" x-text="formatDate(m.created_at)"></td>
                                        <td class="p-4">
                                            <span class="bg-blue-50 text-blue-600 border border-blue-100 px-2.5 py-1 rounded-lg text-xs font-black tracking-wider" x-text="m.mutation_no"></span>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-slate-800" x-text="m.product_name"></div>
                                            <div class="text-[10px] text-slate-400 font-black uppercase" x-text="m.product_code"></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="inline-flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-xl font-black text-xs text-slate-700">
                                                <span class="text-rose-600" x-text="m.from_store_name"></span>
                                                <i class="fa-solid fa-arrow-right text-slate-400"></i>
                                                <span class="text-emerald-600" x-text="m.to_store_name"></span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="bg-amber-500 text-white font-black px-3 py-1 rounded-lg text-xs" x-text="m.quantity"></span>
                                        </td>
                                        <td class="p-4 text-slate-600 font-medium text-xs" x-text="m.notes || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
    function mutasiApp() {
        return {
            isLoading: false,
            isSubmitting: false,
            products: [],
            warehouses: [],
            mutations: [],
            form: {
                product_id: '',
                from_warehouse_id: '<?= $_SESSION["pos_warehouse_id"] ?? "" ?>',
                to_warehouse_id: '',
                quantity: 1,
                notes: ''
            },
            init() {
                this.loadInitData();
            },
            async loadInitData() {
                this.isLoading = true;
                try {
                    const res = await fetch('logic.php?action=get_init_data');
                    const json = await res.json();
                    if (json.status === 'success') {
                        this.products = json.products;
                        this.warehouses = json.warehouses;
                        this.mutations = json.mutations;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.isLoading = false;
                }
            },
            getStockInWh(prodId, whId) {
                const p = this.products.find(x => x.id == prodId);
                if (!p || !p.wh_stocks) return p ? p.stock : 0;
                return p.wh_stocks[whId] !== undefined ? p.wh_stocks[whId] : p.stock;
            },
            async submitMutation() {
                if (!this.form.product_id || !this.form.from_warehouse_id || !this.form.to_warehouse_id) {
                    Swal.fire('Opps', 'Lengkapi semua pilihan produk dan store.', 'warning');
                    return;
                }
                if (this.form.from_warehouse_id == this.form.to_warehouse_id) {
                    Swal.fire('Opps', 'Store Asal dan Tujuan tidak boleh sama.', 'warning');
                    return;
                }
                this.isSubmitting = true;
                try {
                    const formData = new FormData();
                    formData.append('action', 'submit_mutation');
                    formData.append('product_id', this.form.product_id);
                    formData.append('from_warehouse_id', this.form.from_warehouse_id);
                    formData.append('to_warehouse_id', this.form.to_warehouse_id);
                    formData.append('quantity', this.form.quantity);
                    formData.append('notes', this.form.notes);

                    const res = await fetch('logic.php', { method: 'POST', body: formData });
                    const json = await res.json();

                    if (json.status === 'success') {
                        Swal.fire('Berhasil!', json.message, 'success');
                        this.form.quantity = 1;
                        this.form.notes = '';
                        this.loadInitData();
                    } else {
                        Swal.fire('Gagal', json.message || 'Terjadi kesalahan sistem', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal memproses mutasi.', 'error');
                } finally {
                    this.isSubmitting = false;
                }
            },
            formatDate(str) {
                if (!str) return '-';
                const d = new Date(str);
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
        }
    }
    </script>
</body>
</html>
