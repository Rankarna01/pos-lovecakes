document.addEventListener('alpine:init', () => {
    Alpine.data('opnameApp', () => ({
        // Draft State
        tanggal: new Date().toISOString().split('T')[0],
        notes: '',
        draftItems: [], // array of {id, name, category, system_stock, actual_stock, time_added}
        isPosting: false,

        // Modal State
        showModal: false,
        selectedWarehouse: window.CURRENT_WAREHOUSE_ID || '1',
        modalProducts: [],
        modalPage: 1,
        modalTotalPages: 1,
        modalTotalProducts: 0,
        modalSearch: '',
        modalCategoryFilter: '',
        modalCategories: [],
        isModalLoading: false,
        
        // Items currently being checked in modal before adding to draft
        opnameItems: [], 
        
        // History State
        historyOpen: false,
        historyRows: [],
        searchHistory: '',
        isLoadingHistory: false,
        currentTime: '',

        async init() {
            this._updateClock();
            setInterval(() => this._updateClock(), 1000);
            await this.loadHistory();
        },

        _updateClock() {
            const now = new Date();
            this.currentTime = now.toTimeString().substring(0, 8);
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

        // ---- History Actions ----
        async loadHistory() {
            this.isLoadingHistory = true;
            try {
                const wh_id = window.CURRENT_WAREHOUSE_ID || '0';
                const url = `../laporan/opname/logic.php?action=get_report&warehouse_id=${wh_id}&date_from=2000-01-01&date_to=2099-12-31`;
                const res = await fetch(url);
                const data = await res.json();
                if (data.status === 'success') {
                    // Filter by search string if any
                    let rows = data.data || [];
                    if (this.searchHistory.trim() !== '') {
                        const kw = this.searchHistory.toLowerCase();
                        rows = rows.filter(r => 
                            (r.doc_no && r.doc_no.toLowerCase().includes(kw)) ||
                            (r.product_name && r.product_name.toLowerCase().includes(kw))
                        );
                    }
                    this.historyRows = rows.slice(0, 50); // limit to 50 for quick view
                }
            } catch (e) { console.error(e); }
            finally { this.isLoadingHistory = false; }
        },

        // ---- Modal Actions ----
        async openModal() {
            this.showModal = true;
            this.modalPage = 1;
            this.modalSearch = '';
            this.modalCategoryFilter = '';
            // Reset modal selection state to prepare for new selections
            this.opnameItems = [];
            
            // Auto-populate opnameItems with draft items so they appear checked/with stock if already added
            this.draftItems.forEach(d => {
                this.opnameItems.push({...d, checked: true});
            });
            
            await this.loadAllProducts();
        },

        closeModal() {
            this.showModal = false;
            this.modalProducts = [];
            this.opnameItems = [];
        },

        onModalWarehouseChange() {
            this.opnameItems = [];
            this.modalCategoryFilter = '';
            this.modalSearch = '';
            this.modalCategories = [];
            this.loadAllProducts(true);
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

        // ---- Interaction in Modal ----
        getItemById(id) {
            return this.opnameItems.find(i => i.id == id);
        },

        ensureItem(prod) {
            let existing = this.getItemById(prod.id);
            if (!existing) {
                existing = {
                    id: prod.id, code: prod.code, name: prod.name,
                    category: prod.category || '-',
                    system_stock: parseFloat(prod.stock || 0),
                    actual_stock: 0,
                    price: parseFloat(prod.price || 0),
                    checked: true,
                    time_added: this.currentTime
                };
                this.opnameItems.push(existing);
            }
            if (!existing.checked) existing.checked = true;
        },

        toggleProductCheck(prod, event) {
            const checked = event.target.checked;
            let existing = this.getItemById(prod.id);
            if (checked) {
                if (!existing) {
                    this.opnameItems.push({
                        id: prod.id, code: prod.code, name: prod.name,
                        category: prod.category || '-',
                        system_stock: parseFloat(prod.stock || 0),
                        actual_stock: 0,
                        price: parseFloat(prod.price || 0),
                        checked: true,
                        time_added: this.currentTime
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
                            system_stock: parseFloat(prod.stock || 0),
                            actual_stock: 0,
                            price: parseFloat(prod.price || 0),
                            checked: true,
                            time_added: this.currentTime
                        });
                    } else { existing.checked = true; }
                } else {
                    if (existing) existing.checked = false;
                }
            });
        },

        incrementStock(id) {
            const item = this.getItemById(id);
            if (item) item.actual_stock++;
        },

        decrementStock(id) {
            const item = this.getItemById(id);
            if (item && item.actual_stock > 0) item.actual_stock--;
        },

        setActualStock(id, val) {
            const item = this.getItemById(id);
            if (item) {
                let v = parseFloat(val);
                if (isNaN(v) || v < 0) v = 0;
                item.actual_stock = v;
            }
        },

        // ---- Draft List Actions ----
        tambahKeDraft() {
            const checkedItems = this.opnameItems.filter(i => i.checked);
            checkedItems.forEach(item => {
                const existingIndex = this.draftItems.findIndex(d => d.id === item.id);
                if (existingIndex > -1) {
                    // Update existing
                    this.draftItems[existingIndex].actual_stock = item.actual_stock;
                } else {
                    // Add new
                    this.draftItems.push({...item});
                }
            });
            this.closeModal();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `${checkedItems.length} produk ditambahkan`,
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        },

        hapusDraftItem(id) {
            this.draftItems = this.draftItems.filter(i => i.id !== id);
        },

        // ---- Posting ----
        async postingOpname() {
            if (this.draftItems.length === 0) return;
            
            if (typeof Swal === 'undefined') return;
            const conf = await Swal.fire({
                title: 'Posting Opname?',
                text: `Anda akan menyimpan opname untuk ${this.draftItems.length} bahan baku.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Ya, Posting!',
                cancelButtonText: 'Batal'
            });

            if (!conf.isConfirmed) return;

            this.isPosting = true;
            try {
                // Ensure correct warehouse
                let wId = parseInt(this.selectedWarehouse);
                if (isNaN(wId) || wId <= 0) {
                    wId = window.CURRENT_WAREHOUSE_ID || 1;
                }

                const payload = {
                    items: this.draftItems.map(d => ({
                        product_id: d.id,
                        system_stock: d.system_stock,
                        actual_stock: d.actual_stock
                    })),
                    notes: this.notes,
                    tanggal: this.tanggal
                };

                const res = await fetch(`logic.php?action=save_opname_batch&warehouse_id=${wId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json();
                if (data.status === 'success') {
                    await Swal.fire({
                        title: 'Berhasil!',
                        text: data.message || 'Opname berhasil diposting.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Reset draft after success
                    this.draftItems = [];
                    this.notes = '';
                    
                    // Reload history and open accordion
                    await this.loadHistory();
                    this.historyOpen = true;
                } else {
                    Swal.fire('Error', data.message || 'Gagal menyimpan opname.', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan data.', 'error');
            } finally {
                this.isPosting = false;
            }
        }
    }));
});
