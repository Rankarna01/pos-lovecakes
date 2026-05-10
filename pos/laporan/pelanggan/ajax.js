document.addEventListener('alpine:init', () => {
    Alpine.data('pelangganApp', () => ({
        isLoading: true,
        isSyncing: false,
        
        searchQuery: '',
        customers: [],

        // State untuk Modal
        showModal: false,
        isDetailLoading: false,
        activeCustomer: null,
        activeHistory: [],

        async init() {
            // ❌ CEK SESI dbAuth DIHAPUS TOTAL!
            // Keamanan sudah diamankan 100% oleh config/auth.php di server.
            
            // Langsung tarik data saat pertama kali buka
            await this.fetchData(false);
        },

        async fetchData(isManualSync = true) {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                this.isLoading = false;
                this.isSyncing = false;
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline', 'Halaman Laporan Pelanggan membutuhkan koneksi internet!', 'warning');
                }
                return;
            }

            if (isManualSync) {
                this.isSyncing = true;
            } else {
                this.isLoading = true;
            }

            try {
                const timestamp = new Date().getTime(); // Anti-cache
                const response = await fetch(`logic.php?action=get_customers&nocache=${timestamp}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.customers = result.data || [];

                    if (isManualSync) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true, position: 'top-end', icon: 'success',
                                title: `Data Pelanggan Sinkron!`,
                                showConfirmButton: false, timer: 1500,
                                customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                            });
                        }
                    }
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Muat Laporan', result.message, 'error');
                }
            } catch (error) {
                console.error('Error Tarik Laporan:', error);
                if (typeof Swal !== 'undefined') Swal.fire('Error Database', 'Gagal menyambung ke server.', 'error');
            } finally {
                this.isLoading = false;
                this.isSyncing = false;
            }
        },

        async openDetail(cust) {
            // 🛡️ CEGAT JIKA OFFLINE SAAT MAU LIHAT DETAIL
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Koneksi terputus! Tidak dapat melihat histori belanja saat offline.', 'warning');
                }
                return;
            }

            this.activeCustomer = cust;
            this.activeHistory = [];
            this.showModal = true;
            this.isDetailLoading = true;

            try {
                const timestamp = new Date().getTime();
                const response = await fetch(`logic.php?action=get_history&id=${cust.id}&nocache=${timestamp}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.activeHistory = result.data || [];
                }
            } catch (error) {
                console.error("Gagal menarik histori", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menarik data histori belanja.', 'error');
            } finally {
                this.isDetailLoading = false;
            }
        },

        get filteredCustomers() {
            if (this.searchQuery.trim() === '') return this.customers;
            const q = this.searchQuery.toLowerCase();
            return this.customers.filter(c => 
                (c.name && c.name.toLowerCase().includes(q)) || 
                (c.phone && c.phone.includes(q))
            );
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
                   d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        formatRupiah(angka) {
            const val = parseFloat(angka);
            if (isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        }
    }));
});