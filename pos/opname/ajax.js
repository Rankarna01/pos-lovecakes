document.addEventListener('alpine:init', () => {
    Alpine.data('opnameApp', () => ({
        // State history (main table)
        selectedWarehouse: window.CURRENT_WAREHOUSE_ID || '1',
        historyRows: [],
        searchHistory: '',
        isLoadingHistory: false,

        // Modal state
        showModal: false,
        modalProducts: [],
        modalPage: 1,
        modalTotalPages: 1,
        modalTotalProducts: 0,
        modalSearch: '',
        modalCategoryFilter: '',
        modalCategories: [],
        isModalLoading: false,
        isSavingBatch: false,

        // Selected items with actual stock values
        opnameItems: [], // [{id, code, name, category, system_stock, actual_stock, price, checked}]

        // Clock
        currentTime: '',

        async init() {
            this._updateClock();
            setInterval(() => this._updateClock(), 1000);
            await this.loadHistory();
        },

        _updateClock() {
            const now = new Date();
            this.currentTime = now.toTimeString().substring(0, 5);
        },

        // ---- Computed ----
        get allOnPageChecked() {
            if (!this.modalProducts.length) return false;
            return this.modalProducts.every(p => {
                const item = this.opnameItems.find(i => i.id == p.id);
                return item && item.checked;
            });
        },

        get modalPageRange() {
            const total = this.modalTotalPages;
            const cur = this.modalPage;
            const pages = [];
            const max = Math.min(total, 10);
            let start = Math.max(1, cur - 4);
            let end = Math.min(total, start + max - 1);
            if (end - start < max - 1) start = Math.max(1, end - max + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },

        // ---- History ----
        onWarehouseChange() {
            this.loadHistory();
        },

        // Saat ganti store di dalam modal - reset pilihan & reload produk
        onModalWarehouseChange() {
            this.opnameItems = [];
            this.modalCategoryFilter = '';
            this.modalSearch = '';
            this.modalCategories = [];
            this.loadAllProducts(true);
        },

        async loadHistory() {
            this.isLoadingHistory = true;
            try {
                const res = await fetch(`logic.php?action=get_history&warehouse_id=${encodeURIComponent(this.selectedWarehouse)}&search=${encodeURIComponent(this.searchHistory)}`);
                const data = await res.json();
                if (data.status === 'success') this.historyRows = data.data || [];
            } catch(e) { console.error(e); }
            finally { this.isLoadingHistory = false; }
        },

        async deleteRow(row) {
            if (typeof Swal === 'undefined') return;
            const conf = await Swal.fire({
                title: 'Hapus data ini?',
                text: 'Data opname "' + (row.product_name || '') + '" akan dihapus.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#e11d48', confirmButtonText: 'Hapus!', cancelButtonText: 'Batal'
            });
            if (!conf.isConfirmed) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete_opname');
                fd.append('id', row.id);
                const res = await fetch('logic.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Data dihapus!', showConfirmButton:false, timer:1500 });
                    this.loadHistory();
                } else {
                    Swal.fire('Error', data.message || 'Gagal menghapus.', 'error');
                }
            } catch(e) { console.error(e); }
        },

        async editRow(row) {
            if (typeof Swal === 'undefined') return;
            const { value: newStock } = await Swal.fire({
                title: 'Edit Stok Aktual',
                html: `<div class="text-sm text-slate-600 mb-3"><strong>${row.product_name}</strong></div>
                       <input id="swal-edit-stock" type="number" value="${row.actual_stock}" class="swal2-input" style="width:120px;text-align:center;font-weight:900;" min="0">`,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                preConfirm: () => parseInt(document.getElementById('swal-edit-stock').value || 0)
            });
            if (newStock === undefined) return;
            try {
                const fd = new FormData();
                fd.append('action', 'edit_opname');
                fd.append('id', row.id);
                fd.append('actual_stock', newStock);
                const res = await fetch('logic.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Berhasil diperbarui!', showConfirmButton:false, timer:1500 });
                    this.loadHistory();
                } else {
                    Swal.fire('Error', data.message || 'Gagal update.', 'error');
                }
            } catch(e) { console.error(e); }
        },

        formatRupiah(val) {
            const num = parseInt(val || 0);
            if (num === 0) return '0';
            return 'Rp ' + num.toLocaleString('id-ID');
        },

        // ---- Modal ----
        async openModal() {
            this.showModal = true;
            this.modalPage = 1;
            this.modalSearch = '';
            this.modalCategoryFilter = '';
            this.opnameItems = [];
            await this.loadAllProducts();
        },

        closeModal() {
            this.showModal = false;
            this.modalProducts = [];
            this.opnameItems = [];
        },

        async loadAllProducts(resetPage = false) {
            if (resetPage) this.modalPage = 1;
            this.isModalLoading = true;
            try {
                const url = `logic.php?action=get_all_products&warehouse_id=${encodeURIComponent(this.selectedWarehouse)}&page=${this.modalPage}&keyword=${encodeURIComponent(this.modalSearch)}&category=${encodeURIComponent(this.modalCategoryFilter)}`;
                const res = await fetch(url);
                const data = await res.json();
                if (data.status === 'success') {
                    this.modalProducts = data.data || [];
                    this.modalTotalPages = parseInt(data.total_pages) || 1;
                    this.modalTotalProducts = parseInt(data.total) || 0;
                    if (data.categories && data.categories.length && !this.modalCategories.length) {
                        this.modalCategories = data.categories;
                    }
                }
            } catch(e) { console.error(e); }
            finally { this.isModalLoading = false; }
        },

        async goToModalPage(p) {
            if (p < 1 || p > this.modalTotalPages) return;
            this.modalPage = p;
            await this.loadAllProducts();
        },

        // ---- Checkbox & counter logic ----
        getItemById(id) {
            return this.opnameItems.find(i => i.id == id) || null;
        },

        // Auto-add product to opnameItems if not yet there, and mark as checked
        ensureItem(prod) {
            const existing = this.opnameItems.find(i => i.id == prod.id);
            if (!existing) {
                this.opnameItems.push({
                    id: prod.id,
                    code: prod.code,
                    name: prod.name,
                    category: prod.category || '-',
                    system_stock: parseInt(prod.stock || 0),
                    actual_stock: 0,
                    price: parseFloat(prod.price || 0),
                    checked: true
                });
            } else if (!existing.checked) {
                existing.checked = true;
            }
        },

        toggleProductCheck(prod, event) {
            const checked = event.target.checked;
            const existing = this.opnameItems.find(i => i.id == prod.id);
            if (checked) {
                if (!existing) {
                    this.opnameItems.push({
                        id: prod.id,
                        code: prod.code,
                        name: prod.name,
                        category: prod.category || '-',
                        system_stock: parseInt(prod.stock || 0),
                        actual_stock: 0,
                        price: parseFloat(prod.price || 0),
                        checked: true
                    });
                } else {
                    existing.checked = true;
                }
            } else {
                if (existing) existing.checked = false;
            }
        },

        toggleSelectAll(event) {
            const checked = event.target.checked;
            this.modalProducts.forEach(prod => {
                const existing = this.opnameItems.find(i => i.id == prod.id);
                if (checked) {
                    if (!existing) {
                        this.opnameItems.push({
                            id: prod.id, code: prod.code, name: prod.name,
                            category: prod.category || '-',
                            system_stock: parseInt(prod.stock || 0),
                            actual_stock: 0,
                            price: parseFloat(prod.price || 0),
                            checked: true
                        });
                    } else { existing.checked = true; }
                } else {
                    if (existing) existing.checked = false;
                }
            });
        },

        incrementStock(id) {
            const item = this.opnameItems.find(i => i.id == id);
            if (item) item.actual_stock = (parseInt(item.actual_stock) || 0) + 1;
        },

        decrementStock(id) {
            const item = this.opnameItems.find(i => i.id == id);
            if (item) item.actual_stock = Math.max(0, (parseInt(item.actual_stock) || 0) - 1);
        },

        setActualStock(id, val) {
            const item = this.opnameItems.find(i => i.id == id);
            if (item) item.actual_stock = Math.max(0, parseInt(val) || 0);
        },

        // ---- Save ----
        async saveBatchOpname() {
            const checked = this.opnameItems.filter(i => i.checked);
            if (!checked.length) {
                if (typeof Swal !== 'undefined') Swal.fire('Peringatan', 'Pilih minimal 1 produk!', 'warning');
                return;
            }
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Koneksi terputus! Tidak dapat menyimpan data opname.', 'warning');
                return;
            }
            this.isSavingBatch = true;
            try {
                const payload = {
                    warehouse_id: this.selectedWarehouse,
                    items: checked.map(item => ({
                        product_id: item.id,
                        system_stock: item.system_stock,
                        actual_stock: item.actual_stock
                    })),
                    notes: ''
                };
                const res = await fetch('logic.php?action=save_opname_batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                if (result.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon:'success', title:'Berhasil!', text: result.message, timer:2000, showConfirmButton:false });
                    }
                    this.closeModal();
                    await this.loadHistory();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', result.message || 'Gagal menyimpan.', 'error');
                }
            } catch(e) {
                console.error(e);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Koneksi gagal.', 'error');
            } finally {
                this.isSavingBatch = false;
            }
        }
    }));
});
