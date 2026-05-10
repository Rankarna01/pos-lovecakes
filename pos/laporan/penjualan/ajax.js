document.addEventListener('alpine:init', () => {
    Alpine.data('riwayatApp', () => ({
        isLoading: true,
        isSyncing: false,
        searchQuery: '',
        isRestricted: false,
        filters: {
            start_date: new Date().toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0]
        },
        sales: [],
        showModal: false,
        activeSale: null,
        activeDetails: [],

        async init() {
            // 🛡️ 1. SMART GUARD ANTI-MEMBAL
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                // HANYA tendang ke auth/index.php JIKA internet offline DAN sesi lokal hilang.
                if (!user && !navigator.onLine) { 
                    window.location.href = '../../../auth/index.php'; 
                    return; 
                }
            }

            // 🎯 2. WATCHER TANGGAL (Agar filter tanggal berfungsi!)
            this.$watch('filters.start_date', () => { this.fetchData(false); });
            this.$watch('filters.end_date', () => { this.fetchData(false); });

            await this.fetchData(false);
        },

        async fetchData(isManual = true) {
            // 🛡️ 3. CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                this.isLoading = false;
                this.isSyncing = false;
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Halaman ini membutuhkan koneksi internet!', 'warning');
                return;
            }

            if (isManual) this.isSyncing = true;
            else this.isLoading = true;

            try {
                const params = new URLSearchParams(this.filters);
                params.append('action', 'get_sales');
                params.append('nocache', Date.now());

                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.sales = result.data || [];
                    this.isRestricted = result.restricted || false;
                    if (isManual) {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Data riwayat sinkron!', showConfirmButton: false, timer: 1500 });
                    }
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal memuat data.', 'error');
            } finally {
                this.isLoading = false;
                this.isSyncing = false;
            }
        },

        get filteredSales() {
            if (this.searchQuery.trim() === '') return this.sales;
            const q = this.searchQuery.toLowerCase();
            return this.sales.filter(s => s.invoice_no.toLowerCase().includes(q) || (s.customer_name && s.customer_name.toLowerCase().includes(q)));
        },

        async openDetail(sale) {
            // 🛡️ 4. CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Tidak bisa melihat detail saat offline.', 'warning');
                return;
            }

            this.activeSale = sale;
            this.activeDetails = [];
            this.showModal = true;
            try {
                const response = await fetch(`logic.php?action=get_detail&id=${sale.id}`);
                const result = await response.json();
                if (result.status === 'success') this.activeDetails = result.data;
            } catch (e) { 
                console.error(e); 
            }
        },

        printReceipt(invoice) {
            window.open(`../../kasir/print_receipt.php?invoice=${invoice}`, '_blank', 'width=400,height=600');
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(parseFloat(angka) || 0);
        }
    }));
});