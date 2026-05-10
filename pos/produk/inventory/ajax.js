document.addEventListener('alpine:init', () => {
    Alpine.data('inventoryApp', () => ({
        activeTab: 'masuk', // 'masuk' atau 'keluar'
        dataMasuk: [],
        dataKeluar: [],
        searchQuery: '',
        dateFilter: 'all', // all, today, week, month
        
        // Pagination State
        currentPage: 1,
        itemsPerPage: 10,
        
        isLoading: true, // Untuk spinner loading layar
        isSyncing: false, // Untuk spinner di tombol refresh

        async init() {
            // ❌ CEK SESI dbAuth DIHAPUS TOTAL! 
            // Keamanan sudah diamankan oleh PHP (config/auth.php)
            
            this.$watch('searchQuery', () => this.currentPage = 1);
            this.$watch('dateFilter', () => this.currentPage = 1);
            this.$watch('activeTab', () => this.currentPage = 1);

            // Langsung panggil fungsi tarik data dari Server MySQL!
            await this.fetchDataFromServer();
        },

        // ✅ FUNGSI BARU: TARIK DATA LANGSUNG DARI SERVER
        async fetchDataFromServer() {
            // Cegat kalau internet mati
            if (!navigator.onLine) {
                this.isLoading = false;
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline', 'Halaman Histori membutuhkan koneksi internet!', 'warning');
                }
                return;
            }

            this.isLoading = true;
            try {
                const timestamp = new Date().getTime();
                // Menembak file logic.php persis seperti kodemu sebelumnya
                const response = await fetch(`logic.php?action=get_inventory&nocache=${timestamp}`);
                const result = await response.json();

                if (result.status === 'success') {
                    // LANGSUNG masukkan data mentah ke variabel Alpine (Tanpa simpan ke dbAuth)
                    this.dataMasuk = result.data_masuk || [];
                    this.dataKeluar = result.data_keluar || [];
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Muat Data', result.message, 'error');
                }
            } catch (error) {
                console.error('Error Fetch Server:', error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal membaca riwayat database server.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        // ✅ FUNGSI SYNC DATA DIUBAH JADI FUNGSI REFRESH (Penyegaran Data)
        // (Nama fungsi tetap syncData agar tombol HTML kamu tidak error)
        async syncData() {
            if (!navigator.onLine) {
                Swal.fire('Offline', 'Koneksi terputus, tidak bisa menyegarkan data.', 'warning');
                return;
            }

            this.isSyncing = true;
            await this.fetchDataFromServer(); // Panggil ulang fungsi fetch
            this.isSyncing = false;

            // Beri notifikasi kalau sukses di-refresh
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: `Data riwayat berhasil diperbarui!`,
                    showConfirmButton: false, timer: 1500,
                    customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                });
            }
        },

        // ==========================================
        // FUNGSI FILTER & FORMAT BAWAAN (TETAP SAMA)
        // ==========================================
        get filteredData() {
            let temp = this.activeTab === 'masuk' ? this.dataMasuk : this.dataKeluar;

            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                temp = temp.filter(item => 
                    (item.produk && item.produk.toLowerCase().includes(q)) || 
                    (item.referensi && item.referensi.toLowerCase().includes(q)) ||
                    (item.kode_produk && item.kode_produk.toLowerCase().includes(q))
                );
            }

            if (this.dateFilter !== 'all') {
                const today = new Date();
                today.setHours(0,0,0,0);

                temp = temp.filter(item => {
                    const itemDate = new Date(item.tanggal);
                    if (this.dateFilter === 'today') return itemDate >= today;
                    else if (this.dateFilter === 'week') {
                        const lastWeek = new Date(today);
                        lastWeek.setDate(today.getDate() - 7);
                        return itemDate >= lastWeek;
                    } 
                    else if (this.dateFilter === 'month') {
                        return itemDate.getMonth() === today.getMonth() && itemDate.getFullYear() === today.getFullYear();
                    }
                    return true;
                });
            }
            return temp;
        },

        get paginatedData() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredData.slice(start, start + this.itemsPerPage);
        },

        get totalPages() {
            return Math.ceil(this.filteredData.length / this.itemsPerPage) || 1;
        },

        formatDate(dateString) {
            if(!dateString) return '-';
            const options = { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
            return new Date(dateString).toLocaleDateString('id-ID', options).replace(/\./g, ':');
        }
    }));
});