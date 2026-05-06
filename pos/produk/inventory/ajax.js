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
        
        isLoading: true,
        isSyncing: false,

        async init() {
            // Cek Sesi Login
            if(window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) {
                    window.location.href = '../../../auth/index.php';
                    return;
                }
            }
            
            this.$watch('searchQuery', () => this.currentPage = 1);
            this.$watch('dateFilter', () => this.currentPage = 1);
            this.$watch('activeTab', () => this.currentPage = 1);

            await this.loadLocalData();
        },

        async loadLocalData() {
            this.isLoading = true;
            if (window.dbAuth) {
                const localIn = await window.dbAuth.getItem('inventory_in');
                const localOut = await window.dbAuth.getItem('inventory_out');
                
                if (localIn || localOut) {
                    this.dataMasuk = localIn || [];
                    this.dataKeluar = localOut || [];
                } else {
                    await this.syncData();
                }
            }
            this.isLoading = false;
        },

        async syncData() {
            this.isSyncing = true;
            try {
                // Hapus cache lama
                if(window.dbAuth) {
                    await window.dbAuth.removeItem('inventory_in');
                    await window.dbAuth.removeItem('inventory_out');
                }

                const timestamp = new Date().getTime();
                const response = await fetch(`logic.php?action=get_inventory&nocache=${timestamp}`);
                const result = await response.json();

                if (result.status === 'success') {
                    // PERBAIKAN DataCloneError: 
                    // Simpan data "result" API mentah (bukan milik Alpine) langsung ke IndexedDB
                    if(window.dbAuth) {
                        await window.dbAuth.setItem('inventory_in', result.data_masuk || []);
                        await window.dbAuth.setItem('inventory_out', result.data_keluar || []);
                    }
                    
                    // Setelah aman tersimpan, baru dimasukkan ke variabel Alpine
                    this.dataMasuk = result.data_masuk || [];
                    this.dataKeluar = result.data_keluar || [];
                    
                    // PERBAIKAN Swal is not defined: Cek apakah Swal berhasil diload
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: `Riwayat berhasil disinkronkan!`,
                            showConfirmButton: false, timer: 1500,
                            customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Muat Data', result.message, 'error');
                    else alert('Gagal: ' + result.message);
                }
            } catch (error) {
                console.error('Error Sync:', error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal membaca riwayat database.', 'error');
                else alert('Error: Gagal membaca database.');
            } finally {
                this.isSyncing = false;
            }
        },

        get filteredData() {
            let temp = this.activeTab === 'masuk' ? this.dataMasuk : this.dataKeluar;

            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                temp = temp.filter(item => 
                    item.produk.toLowerCase().includes(q) || 
                    item.referensi.toLowerCase().includes(q) ||
                    item.kode_produk.toLowerCase().includes(q)
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