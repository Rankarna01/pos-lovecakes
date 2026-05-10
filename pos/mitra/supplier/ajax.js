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
            // ❌ CEK SESI dbAuth DIHAPUS TOTAL! 
            // Keamanan sudah dijaga oleh config/auth.php di server.
            
            // Watcher untuk mereset pagination kalau user mengetik pencarian
            this.$watch('searchQuery', () => this.currentPage = 1);
            
            // Langsung tarik data segar dari database MySQL
            await this.fetchData();
        },

        // FUNGSI TARIK DATA LANGSUNG DARI SERVER
        async fetchData() {
            // CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                window.alert('Anda sedang offline! Halaman Supplier membutuhkan koneksi internet.');
                this.isLoading = false;
                return;
            }

            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=read&nocache=${new Date().getTime()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Masukkan data mentah dari server langsung ke variabel Alpine
                    this.suppliers = result.data || [];
                } else {
                    window.alert('Gagal memuat data: ' + result.message);
                }
            } catch (error) {
                console.error("Error loading suppliers", error);
                window.alert('Error: Gagal memuat data supplier dari server pusat.');
            } finally {
                // WAJIB: Matikan spinner apapun yang terjadi!
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
            // CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                window.alert('Koneksi terputus! Tidak dapat menyimpan data ke server.');
                return;
            }

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
                    window.alert(result.message); // Custom alert sukses kamu otomatis kepanggil
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
                window.alert('Koneksi terputus! Tidak dapat menghapus data.');
                return;
            }

            window.customConfirm('Yakin ingin menghapus supplier ini?', async () => {
                this.isLoading = true;
                try {
                    const fd = new FormData();
                    fd.append('id', id);
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