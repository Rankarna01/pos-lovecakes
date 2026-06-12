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
        
        // Detailed Stats
        paymentData: { cash: 0, qris: 0, total: 0 },
        paymentBreakdown: [],
        dpPelunasan: [],
        salesByCategory: [],
        salesByItem: [],
        salesByCustomer: [],

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
                    
                    this.paymentData = result.payments || { cash: 0, qris: 0, total: 0 };
                    this.paymentBreakdown = result.payment_breakdown || [];
                    this.dpPelunasan = result.dp_pelunasan || [];
                    this.salesByCategory = result.sales_by_category || [];
                    this.salesByItem = result.sales_by_item || [];
                    this.salesByCustomer = result.sales_by_customer || [];

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
        },

        exportCSV(type) {
            let data = [];
            let filename = '';
            
            switch(type) {
                case 'metode_pembayaran':
                    data = this.paymentBreakdown.map(p => ({
                        'Metode Pembayaran': p.payment_method,
                        'Total (Rp)': p.total_amount
                    }));
                    filename = 'laporan_pembayaran';
                    break;
                case 'dp_pelunasan':
                    data = this.dpPelunasan.map(p => ({
                        'Jenis Pembayaran': p.payment_type,
                        'Jumlah Transaksi': p.total_transactions,
                        'Total (Rp)': p.total_amount
                    }));
                    filename = 'laporan_dp_pelunasan';
                    break;
                case 'kategori':
                    data = this.salesByCategory.map(c => ({
                        'Kategori': c.category_name,
                        'Total Terjual (Item)': c.total_qty,
                        'Total Pendapatan (Rp)': c.total_amount
                    }));
                    filename = 'laporan_kategori';
                    break;
                case 'barang':
                    data = this.salesByItem.map(i => ({
                        'Nama Barang': i.item_name,
                        'Total Terjual (Pcs)': i.total_qty,
                        'Total Pendapatan (Rp)': i.total_amount
                    }));
                    filename = 'laporan_barang_terlaris';
                    break;
                case 'pelanggan':
                    data = this.salesByCustomer.map(c => ({
                        'Nama Pelanggan': c.customer_name,
                        'Total Transaksi': c.total_transactions,
                        'Total Belanja (Rp)': c.total_spent
                    }));
                    filename = 'laporan_pelanggan';
                    break;
                case 'rincian':
                    data = this.filteredSales.map(s => ({
                        'No Invoice': s.invoice_no,
                        'Waktu': this.formatDate(s.created_at),
                        'Pelanggan': s.customer_name || 'Pelanggan Umum',
                        'Channel': s.channel || 'toko',
                        'Status': s.payment_status,
                        'Metode': s.payment_method,
                        'Total Bayar (Rp)': s.total_amount
                    }));
                    filename = 'rincian_penjualan';
                    break;
            }

            if (data.length === 0) {
                Swal.fire('Info', 'Tidak ada data untuk diunduh', 'info');
                return;
            }

            // Convert array of objects to CSV
            const headers = Object.keys(data[0]);
            const csvRows = [];
            
            // Header row
            csvRows.push(headers.join(','));
            
            // Data rows
            for (const row of data) {
                const values = headers.map(header => {
                    const val = row[header];
                    // Escape quotes and wrap in quotes if there's a comma
                    const escaped = ('' + val).replace(/"/g, '""');
                    return `"${escaped}"`;
                });
                csvRows.push(values.join(','));
            }
            
            const csvData = csvRows.join('\n');
            const blob = new Blob([csvData], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            
            a.download = `${filename}_${this.filters.start_date}_sd_${this.filters.end_date}.csv`;
            a.href = url;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    }));
});