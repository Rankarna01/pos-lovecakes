document.addEventListener('alpine:init', () => {
    Alpine.data('supplierApp', () => ({
        suppliers: [],
        searchQuery: '',
        isLoading: false,
        
        // Pagination state
        currentPage: 1,
        itemsPerPage: 10,
        
        // State Modal
        showModal: false,
        isEdit: false,
        form: {
            id: '', name: '', contact_person: '', phone: '', email: '', address: '', description: ''
        },

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) {
                    window.location.href = '../../../auth/index.php';
                    return;
                }
            }
            
            // Watcher untuk mereset pagination kalau user mengetik pencarian
            this.$watch('searchQuery', () => this.currentPage = 1);
            
            await this.loadLocalData();
        },

        async loadLocalData() {
            this.isLoading = true;
            if (window.dbAuth) {
                const localData = await window.dbAuth.getItem('suppliers_data');
                if (localData && localData.length > 0) {
                    this.suppliers = localData;
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
                    this.suppliers = result.data || [];
                    if (window.dbAuth) {
                        await window.dbAuth.setItem('suppliers_data', this.suppliers);
                    }
                }
            } catch (error) {
                console.error("Error loading suppliers", error);
            } finally {
                this.isLoading = false;
            }
        },

        // Filter berdasarkan pencarian
        get filteredData() {
            if (this.searchQuery.trim() === '') return this.suppliers;
            const q = this.searchQuery.toLowerCase();
            return this.suppliers.filter(s => 
                (s.name && s.name.toLowerCase().includes(q)) || 
                (s.contact_person && s.contact_person.toLowerCase().includes(q)) ||
                (s.address && s.address.toLowerCase().includes(q))
            );
        },

        // Potong data untuk Halaman (Pagination)
        get paginatedData() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredData.slice(start, end);
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
                this.form = { id: '', name: '', contact_person: '', phone: '', email: '', address: '', description: '' };
            }
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
        },

        async simpanData() {
            if (!this.form.name || !this.form.phone) {
                window.alert('Mohon isi Nama Perusahaan dan Nomor Telepon.');
                return;
            }

            this.isLoading = true;
            try {
                const fd = new FormData();
                for (const key in this.form) {
                    fd.append(key, this.form[key]);
                }

                const response = await fetch('logic.php?action=save', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    window.alert(result.message);
                    this.closeModal();
                    await this.fetchData(); // Ambil ulang data segar
                } else {
                    window.alert(result.message);
                }
            } catch (error) {
                window.alert('Gagal menghubungi server.');
            } finally {
                this.isLoading = false;
            }
        },

        hapusData(id) {
            window.customConfirm('Yakin ingin menghapus supplier ini?', async () => {
                this.isLoading = true;
                try {
                    const fd = new FormData();
                    fd.append('id', id);
                    const response = await fetch('logic.php?action=delete', { method: 'POST', body: fd });
                    const result = await response.json();

                    if (result.status === 'success') {
                        window.alert(result.message);
                        await this.fetchData();
                    } else {
                        window.alert(result.message);
                    }
                } catch (error) {
                    window.alert('Gagal menghapus data.');
                } finally {
                    this.isLoading = false;
                }
            });
        }
    }));
});