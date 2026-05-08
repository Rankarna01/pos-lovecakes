document.addEventListener('alpine:init', () => {
    Alpine.data('customerApp', () => ({
        customers: [],
        searchQuery: '',
        isLoading: false,
        
        currentPage: 1,
        itemsPerPage: 10,
        
        showModal: false,
        isEdit: false,
        form: { id: '', name: '', phone: '', address: '', points: 0 },

        async init() {
            // Watch pencarian untuk reset ke hal 1
            this.$watch('searchQuery', () => this.currentPage = 1);
            
            // Cek Sesi (Dari Header)
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../../auth/index.php'; return; }
            }

            await this.loadLocalData();
        },

        async loadLocalData() {
            this.isLoading = true;
            if (window.dbAuth) {
                const local = await window.dbAuth.getItem('customers_data');
                if (local && local.length > 0) {
                    this.customers = local;
                } else {
                    await this.fetchData();
                }
            }
            this.isLoading = false;
        },

        async fetchData() {
            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=read&nocache=${new Date().getTime()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.customers = result.data || [];
                    if (window.dbAuth) await window.dbAuth.setItem('customers_data', this.customers);
                }
            } catch (error) {
                console.error("Error loading customers", error);
            } finally {
                this.isLoading = false;
            }
        },

        get filteredData() {
            if (this.searchQuery.trim() === '') return this.customers;
            const q = this.searchQuery.toLowerCase();
            return this.customers.filter(c => 
                (c.name && c.name.toLowerCase().includes(q)) || 
                (c.phone && c.phone.toLowerCase().includes(q))
            );
        },

        get paginatedData() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredData.slice(start, start + this.itemsPerPage);
        },

        get totalPages() {
            return Math.ceil(this.filteredData.length / this.itemsPerPage) || 1;
        },

        openModal(item = null) {
            if (item) {
                this.isEdit = true;
                this.form = { ...item };
            } else {
                this.isEdit = false;
                this.form = { id: '', name: '', phone: '', address: '', points: 0 };
            }
            this.showModal = true;
        },

        closeModal() { this.showModal = false; },

        async simpanData() {
            if (!this.form.name) { window.alert('Nama Pelanggan wajib diisi!'); return; }

            this.isLoading = true;
            try {
                const fd = new FormData();
                for (const key in this.form) fd.append(key, this.form[key]);

                const response = await fetch('logic.php?action=save', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    window.alert(result.message);
                    this.closeModal();
                    await this.fetchData(); 
                } else { window.alert(result.message); }
            } catch (error) { window.alert('Gagal menghubungi server.'); } finally { this.isLoading = false; }
        },

        hapusData(id) {
            window.customConfirm('Hapus pelanggan ini secara permanen?', async () => {
                this.isLoading = true;
                try {
                    const fd = new FormData(); fd.append('id', id);
                    const response = await fetch('logic.php?action=delete', { method: 'POST', body: fd });
                    const result = await response.json();

                    if (result.status === 'success') {
                        window.alert(result.message);
                        await this.fetchData();
                    } else { window.alert(result.message); }
                } catch (error) { window.alert('Gagal menghapus data.'); } finally { this.isLoading = false; }
            });
        }
    }));
});