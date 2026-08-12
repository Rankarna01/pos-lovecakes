document.addEventListener('alpine:init', () => {
    Alpine.data('foodDeliveryDetailApp', (platformCode) => ({
        platformCode: platformCode,
        items: [],
        filteredItems: [],
        availableItems: [],
        filteredAvailableItems: [],
        selectedIds: [],
        selectedAddItems: [],
        stores: [],
        
        searchQuery: '',
        typeFilter: 'all',
        statusFilter: 'all',
        storeFilter: 'all',
        modalSearchQuery: '',
        modalStoreFilter: 'all',
        
        currentMarkup: 30.00,
        bulkMarkupInput: 30.00,
        
        isLoading: true,
        isProcessing: false,
        showBulkModal: false,
        showAddModal: false,

        async init() {
            this.$watch('searchQuery', () => this.applyFilters());
            this.$watch('typeFilter', () => this.applyFilters());
            this.$watch('statusFilter', () => this.applyFilters());
            this.$watch('storeFilter', () => this.fetchItems());
            this.$watch('modalSearchQuery', () => this.applyModalFilters());
            this.$watch('modalStoreFilter', () => this.fetchAvailableItems());

            await this.fetchStores();
            await this.fetchItems();
        },

        async fetchStores() {
            try {
                const res = await fetch('logic.php?action=get_stores');
                const result = await res.json();
                if (result.status === 'success') {
                    this.stores = result.data || [];
                }
            } catch(e) {
                console.error("Gagal memuat data Store:", e);
            }
        },

        async fetchItems() {
            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_platform_items&platform=${this.platformCode}&warehouse_id=${this.storeFilter === 'all' ? 0 : this.storeFilter}&nocache=${Date.now()}`);
                const result = await res.json();

                if (result.status === 'success') {
                    this.items = result.data || [];
                    this.applyFilters();
                }
            } catch (e) {
                console.error("Gagal memuat produk platform:", e);
            } finally {
                this.isLoading = false;
            }
        },

        applyFilters() {
            let list = this.items;

            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(item => 
                    (item.item_name && item.item_name.toLowerCase().includes(q)) ||
                    (item.item_code && item.item_code.toLowerCase().includes(q)) ||
                    (item.item_category && item.item_category.toLowerCase().includes(q))
                );
            }

            if (this.typeFilter !== 'all') {
                list = list.filter(item => item.item_type === this.typeFilter);
            }

            if (this.statusFilter !== 'all') {
                list = list.filter(item => String(item.is_active) === this.statusFilter);
            }

            this.filteredItems = list;
        },

        selectAll(e) {
            if (e.target.checked) {
                this.selectedIds = this.filteredItems.map(i => i.price_setting_id);
            } else {
                this.selectedIds = [];
            }
        },

        // --- SELECT ALL DI MODAL TAMBAH PRODUK ---
        get isAllModalSelected() {
            const unadded = this.filteredAvailableItems.filter(i => !i.is_added);
            if (unadded.length === 0) return false;
            return unadded.every(i => this.selectedAddItems.includes(i.item_type + '_' + i.id));
        },

        toggleSelectAllModal(e) {
            if (e.target.checked) {
                const unaddedKeys = this.filteredAvailableItems
                    .filter(i => !i.is_added)
                    .map(i => i.item_type + '_' + i.id);
                this.selectedAddItems = Array.from(new Set([...this.selectedAddItems, ...unaddedKeys]));
            } else {
                const unaddedKeys = new Set(this.filteredAvailableItems.map(i => i.item_type + '_' + i.id));
                this.selectedAddItems = this.selectedAddItems.filter(k => !unaddedKeys.has(k));
            }
        },

        // BULK MARKUP PERSENTASE (+30%)
        openBulkMarkupModal() {
            this.bulkMarkupInput = this.currentMarkup;
            this.showBulkModal = true;
        },

        async applyBulkMarkup() {
            const markup = parseFloat(this.bulkMarkupInput);
            if (isNaN(markup)) return;

            this.isProcessing = true;
            try {
                const res = await fetch('logic.php?action=apply_bulk_markup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        platform: this.platformCode,
                        markup_percent: markup,
                        price_setting_ids: this.selectedIds
                    })
                });
                const result = await res.json();

                if (result.status === 'success') {
                    this.currentMarkup = markup;
                    this.showBulkModal = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Markup Diterapkan!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    await this.fetchItems();
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal menerapkan markup massal.', 'error');
            } finally {
                this.isProcessing = false;
            }
        },

        // MODAL TAMBAH PRODUK
        async openAddProductModal() {
            this.selectedAddItems = [];
            this.showAddModal = true;
            await this.fetchAvailableItems();
        },

        async fetchAvailableItems() {
            try {
                const res = await fetch(`logic.php?action=get_available_items&platform=${this.platformCode}&warehouse_id=${this.modalStoreFilter === 'all' ? 0 : this.modalStoreFilter}&nocache=${Date.now()}`);
                const result = await res.json();
                if (result.status === 'success') {
                    this.availableItems = result.data || [];
                    this.applyModalFilters();
                }
            } catch(e) {
                console.error("Gagal memuat produk tersedia:", e);
            }
        },

        applyModalFilters() {
            let list = this.availableItems;
            if (this.modalSearchQuery.trim() !== '') {
                const q = this.modalSearchQuery.toLowerCase();
                list = list.filter(item => 
                    (item.name && item.name.toLowerCase().includes(q)) ||
                    (item.code && item.code.toLowerCase().includes(q)) ||
                    (item.category && item.category.toLowerCase().includes(q))
                );
            }
            this.filteredAvailableItems = list;
        },

        async submitAddProducts() {
            if (this.selectedAddItems.length === 0) return;

            const selectedObjects = this.availableItems.filter(item => 
                this.selectedAddItems.includes(item.item_type + '_' + item.id)
            );

            this.isProcessing = true;
            try {
                const res = await fetch('logic.php?action=add_items_to_platform', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        platform: this.platformCode,
                        items: selectedObjects,
                        markup_percent: this.currentMarkup
                    })
                });
                const result = await res.json();

                if (result.status === 'success') {
                    Swal.fire('Berhasil!', result.message, 'success');
                    this.showAddModal = false;
                    await this.fetchItems();
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Gagal menambahkan produk.', 'error');
            } finally {
                this.isProcessing = false;
            }
        },

        // TOGGLE ACTIVE STATUS
        async toggleActive(item) {
            const newStatus = item.is_active == 1 ? 0 : 1;
            try {
                const res = await fetch('logic.php?action=toggle_active', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: item.price_setting_id, is_active: newStatus })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    item.is_active = newStatus;
                }
            } catch(e) {}
        },

        // EDIT MANUAL HARGA PRODUK (OVERRIDE)
        async editPriceModal(item) {
            const { value: newPrice } = await Swal.fire({
                title: `Edit Harga ${this.platformCode.toUpperCase()}`,
                text: `Produk: ${item.item_name} (Harga Normal: Rp ${this.formatRupiah(item.base_price)})`,
                input: 'number',
                inputValue: item.final_price,
                showCancelButton: true,
                confirmButtonText: 'Simpan Harga',
                cancelButtonText: 'Batal',
                inputValidator: (val) => {
                    if (!val || parseFloat(val) < 0) return 'Harga tidak valid!';
                }
            });

            if (newPrice) {
                this.isProcessing = true;
                try {
                    const res = await fetch('logic.php?action=update_item_price', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: item.price_setting_id, final_price: newPrice })
                    });
                    const result = await res.json();
                    if (result.status === 'success') {
                        item.final_price = parseFloat(newPrice);
                        Swal.fire({ icon: 'success', title: 'Harga Diperbarui!', timer: 1500, showConfirmButton: false });
                    }
                } catch(e) {
                    Swal.fire('Error', 'Gagal memperbarui harga.', 'error');
                } finally {
                    this.isProcessing = false;
                }
            }
        },

        // REMOVE ITEM
        async removeItem(item) {
            window.customConfirm(`Hapus '${item.item_name}' dari platform ${this.platformCode}?`, async () => {
                try {
                    const res = await fetch('logic.php?action=remove_item', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: item.price_setting_id })
                    });
                    const result = await res.json();
                    if (result.status === 'success') {
                        await this.fetchItems();
                    }
                } catch(e) {}
            });
        },

        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val || 0);
        }
    }));
});
