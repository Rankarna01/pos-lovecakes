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
            // ❌ CEK SESI dbAuth DIHAPUS TOTAL! 
            // Keamanan 100% diurus oleh config/auth.php di server
            
            // Watch pencarian untuk reset ke halaman 1
            this.$watch('searchQuery', () => this.currentPage = 1);
            
            // Langsung tarik data dari MySQL Server
            await this.fetchData();
        },

        // FUNGSI TARIK DATA LANGSUNG DARI SERVER
        async fetchData() {
            // CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                window.alert('Anda sedang offline! Halaman Pelanggan membutuhkan koneksi internet.');
                this.isLoading = false;
                return;
            }

            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=read&nocache=${new Date().getTime()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Masukkan data mentah dari server ke variabel Alpine
                    this.customers = result.data || [];
                } else {
                    window.alert('Gagal memuat data: ' + result.message);
                }
            } catch (error) {
                console.error("Error loading customers", error);
                window.alert('Error: Gagal memuat data pelanggan dari server pusat.');
            } finally {
                // WAJIB: Matikan spinner apapun yang terjadi!
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
            // CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                window.alert('Koneksi terputus! Tidak dapat menyimpan data pelanggan ke server.');
                return;
            }

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
                    await this.fetchData(); // Ambil ulang data segar dari MySQL
                } else { 
                    window.alert(result.message); 
                }
            } catch (error) { 
                console.error("Error saving data:", error);
                window.alert('Gagal menghubungi server.'); 
            } finally { 
                this.isLoading = false; 
            }
        },

        hapusData(id) {
            // CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                window.alert('Koneksi terputus! Tidak dapat menghapus pelanggan.');
                return;
            }

            window.customConfirm('Hapus pelanggan ini secara permanen?', async () => {
                this.isLoading = true;
                try {
                    const fd = new FormData(); fd.append('id', id);
                    const response = await fetch('logic.php?action=delete', { method: 'POST', body: fd });
                    const result = await response.json();

                    if (result.status === 'success') {
                        window.alert(result.message);
                        await this.fetchData(); // Segarkan tabel
                    } else { 
                        window.alert(result.message); 
                    }
                } catch (error) { 
                    console.error("Error deleting data:", error);
                    window.alert('Gagal menghapus data.'); 
                } finally { 
                    this.isLoading = false; 
                }
            });
        }
    }));
});