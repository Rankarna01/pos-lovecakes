document.addEventListener('alpine:init', () => {
    Alpine.data('customItemsApp', () => ({
        items: [],
        filteredItems: [],
        searchQuery: '',
        typeFilter: 'all', // 'all', 'product', 'custom_reguler', 'custom_po'
        statusFilter: 'all', // 'all', '1' (dinamis), '0' (fixed)
        isLoading: false,
        isSaving: false,

        summary: {
            total: 0,
            dynamic: 0,
            fixed: 0
        },

        // Modal Form State
        showModal: false,
        modalTitle: 'Tambah Item Custom',
        itemForm: {
            id: 0,
            name: '',
            code: '',
            category: 'Custom',
            price: 0,
            is_custom_price: 1,
            item_type: 'product'
        },

        async init() {
            this.$watch('searchQuery', () => this.applyFilters());
            this.$watch('typeFilter', () => this.applyFilters());
            this.$watch('statusFilter', () => this.applyFilters());

            await this.fetchItems();
        },

        async fetchItems() {
            this.isLoading = true;
            try {
                const response = await fetch('logic.php?action=get_items');
                const result = await response.json();

                if (result.status === 'success') {
                    this.items = result.data || [];
                    this.summary = result.summary || { total: 0, dynamic: 0, fixed: 0 };
                    this.applyFilters();
                }
            } catch (error) {
                console.error("Gagal menarik data item custom:", error);
            } finally {
                this.isLoading = false;
            }
        },

        applyFilters() {
            let list = this.items;

            // Search Filter
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(item => 
                    (item.name && item.name.toLowerCase().includes(q)) ||
                    (item.code && item.code.toLowerCase().includes(q)) ||
                    (item.category && item.category.toLowerCase().includes(q)) ||
                    (item.type_label && item.type_label.toLowerCase().includes(q))
                );
            }

            // Type Filter
            if (this.typeFilter !== 'all') {
                list = list.filter(item => item.item_type === this.typeFilter);
            }

            // Status Filter
            if (this.statusFilter !== 'all') {
                list = list.filter(item => String(item.is_custom_price) === this.statusFilter);
            }

            this.filteredItems = list;
        },

        // TOGGLE STATUS HARGA DINAMIS (AKTIF / NON-AKTIF)
        async toggleDynamicStatus(item) {
            const newStatus = item.is_custom_price == 1 ? 0 : 1;
            const statusLabel = newStatus == 1 ? 'HARGA DINAMIS (Bisa Diubah Kasir)' : 'HARGA TETAP (Tidak Bisa Diubah Kasir)';

            window.customConfirm(`Ubah status harga '${item.name}' menjadi ${statusLabel}?`, async () => {
                this.isLoading = true;
                try {
                    const response = await fetch('logic.php?action=toggle_dynamic', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: item.id, item_type: item.item_type, status: newStatus })
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        item.is_custom_price = newStatus;
                        
                        // Update local summary
                        if (newStatus == 1) {
                            this.summary.dynamic++;
                            this.summary.fixed = Math.max(0, this.summary.fixed - 1);
                        } else {
                            this.summary.fixed++;
                            this.summary.dynamic = Math.max(0, this.summary.dynamic - 1);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Status Diperbarui!',
                            text: result.message,
                            timer: 1800,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Gagal', result.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal mengubah status harga dinamis.', 'error');
                } finally {
                    this.isLoading = false;
                }
            });
        },

        // OPEN MODAL TAMBAH
        openAddModal() {
            this.modalTitle = 'Tambah Item Baru';
            this.itemForm = {
                id: 0,
                name: '',
                code: 'ITEM-' + Math.floor(1000 + Math.random() * 9000),
                category: 'Custom Reguler',
                price: 100000,
                is_custom_price: 1, // Default Dinamis Aktif
                item_type: 'custom_reguler'
            };
            this.showModal = true;
        },

        // OPEN MODAL EDIT
        openEditModal(item) {
            this.modalTitle = `Edit Item: ${item.name}`;
            this.itemForm = {
                id: item.id,
                name: item.name,
                code: item.code,
                category: item.category || 'Umum',
                price: parseFloat(item.price || 0),
                is_custom_price: parseInt(item.is_custom_price || 0),
                item_type: item.item_type || 'product'
            };
            this.showModal = true;
        },

        // SAVE ITEM
        async saveItem() {
            if (!this.itemForm.name.trim()) {
                Swal.fire('Peringatan', 'Nama Item / Produk wajib diisi.', 'warning');
                return;
            }

            this.isSaving = true;
            try {
                const response = await fetch('logic.php?action=save_item', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.itemForm)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire('Berhasil!', result.message, 'success');
                    this.showModal = false;
                    await this.fetchItems();
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal menyimpan item.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        // DELETE ITEM
        async deleteItem(item) {
            window.customConfirm(`Hapus item '${item.name}'?`, async () => {
                this.isLoading = true;
                try {
                    const response = await fetch('logic.php?action=delete_item', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: item.id, item_type: item.item_type })
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        Swal.fire('Terhapus!', result.message, 'success');
                        await this.fetchItems();
                    } else {
                        Swal.fire('Gagal', result.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal menghapus item.', 'error');
                } finally {
                    this.isLoading = false;
                }
            });
        },

        formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(angka || 0);
        }
    }));
});
