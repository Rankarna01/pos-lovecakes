document.addEventListener('alpine:init', () => {
    Alpine.data('riwayatApp', () => ({
        isLoading: true,
        isSyncing: false,
        searchQuery: '',
        isRestricted: false,
        filters: {
            start_date: new Date().toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0],
            payment_method: '',
            payment_status: '',
            discount_filter: ''
        },
        activeTab: 'ringkasan',
        sales: [],
        showModal: false,
        activeSale: null,
        activeDetails: [],
        activePayments: [],
        activeSaleInfo: null,
        showCustomerModal: false,
        activeCustomer: null,
        
        showItemModal: false,
        activeItemType: '', // 'category' or 'product'
        activeItemName: '',
        activeItemTotal: 0,
        activeItemSales: [],
        isItemLoading: false,
        
        // Detailed Stats
        paymentData: { cash: 0, qris: 0, total: 0 },
        paymentBreakdown: [],
        dpPelunasan: [],
        salesByCategory: [],
        salesByItem: [],
        salesByCustomer: [],

        // Pagination variables
        catPage: 1,
        itemPage: 1,
        custPage: 1,
        salePage: 1,
        perPage: 10,

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

            this.checkRestriction();
            this.$watch('filters.start_date', () => { this.fetchSales(); });
            this.$watch('filters.end_date', () => { this.fetchSales(); });
            this.$watch('filters.payment_method', () => { this.fetchSales(); });
            this.$watch('filters.payment_status', () => { this.fetchSales(); });
            this.$watch('searchQuery', () => { this.salePage = 1; });
            this.fetchSales();
        },

        checkRestriction() {
            const role = localStorage.getItem('pos_role');
            if (role === 'kasir') {
                this.isRestricted = true;
                const today = new Date().toISOString().split('T')[0];
                this.filters.start_date = today;
                this.filters.end_date = today;
            }
        },

        async syncNow() {
            if (this.isSyncing) return;
            this.isSyncing = true;
            try {
                if (window.syncSalesToServer) {
                    await window.syncSalesToServer();
                }
                await this.fetchSales();
            } catch (error) {
                console.error("Gagal sinkronisasi:", error);
            } finally {
                this.isSyncing = false;
            }
        },

        async fetchSales() {
            this.isLoading = true;
            try {
                const formData = new FormData();
                formData.append('action', 'get_sales');
                formData.append('start_date', this.filters.start_date);
                formData.append('end_date', this.filters.end_date);
                formData.append('payment_method', this.filters.payment_method || '');
                formData.append('payment_status', this.filters.payment_status || '');

                const response = await fetch('logic.php', {
                    method: 'POST',
                    body: formData
                });
                
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

                    this.catPage = 1;
                    this.itemPage = 1;
                    this.custPage = 1;
                    this.salePage = 1;
                }
            } catch (error) {
                console.error('Error fetching sales report:', error);
            } finally {
                this.isLoading = false;
            }
        },

        get filteredSales() {
            if (!this.searchQuery && !this.filters.payment_method && !this.filters.payment_status && !this.filters.discount_filter) return this.sales;
            const query = (this.searchQuery || '').toLowerCase();
            const pay = (this.filters.payment_method || '').toLowerCase();
            const status = (this.filters.payment_status || '').toLowerCase();
            const discFilter = this.filters.discount_filter || '';
            
            return this.sales.filter(s => {
                const matchSearch = !query || 
                    (s.invoice_no && s.invoice_no.toLowerCase().includes(query)) ||
                    (s.customer_name && s.customer_name.toLowerCase().includes(query));
                
                let matchPay = true;
                if (pay) {
                    const sPay = (s.payment_method || '').toLowerCase();
                    if (pay === 'cash') {
                        matchPay = sPay === 'cash' || sPay === 'tunai';
                    } else if (pay === 'qris') {
                        matchPay = sPay.includes('qris');
                    } else if (pay === 'transfer') {
                        matchPay = sPay.includes('transfer') || sPay.includes('bank');
                    } else if (pay === 'split') {
                        matchPay = sPay.includes('split');
                    } else {
                        matchPay = sPay === pay;
                    }
                }

                let matchStatus = true;
                if (status) {
                    const sStatus = (s.payment_status || '').toLowerCase();
                    matchStatus = sStatus === status;
                }

                // Filter Diskon
                let matchDiscount = true;
                if (discFilter) {
                    const totalDiscount = (parseFloat(s.discount_voucher) || 0) + (parseFloat(s.discount_manual) || 0) + (parseFloat(s.discount_auto) || 0) + (parseFloat(s.discount_points) || 0);
                    const hasDiscount = totalDiscount > 0;
                    if (discFilter === 'has_discount') matchDiscount = hasDiscount;
                    else if (discFilter === 'no_discount') matchDiscount = !hasDiscount;
                    else if (discFilter === 'voucher') matchDiscount = (parseFloat(s.discount_voucher) || 0) > 0;
                    else if (discFilter === 'manual') matchDiscount = (parseFloat(s.discount_manual) || 0) > 0;
                    else if (discFilter === 'auto') matchDiscount = (parseFloat(s.discount_auto) || 0) > 0;
                    else if (discFilter === 'points') matchDiscount = (parseFloat(s.discount_points) || 0) > 0;
                }
                
                return matchSearch && matchPay && matchStatus && matchDiscount;
            });
        },

        // Pagination computed properties
        get paginatedCategories() {
            const start = (this.catPage - 1) * this.perPage;
            return this.salesByCategory.slice(start, start + this.perPage);
        },
        get totalCatPages() {
            return Math.ceil(this.salesByCategory.length / this.perPage) || 1;
        },

        get paginatedItems() {
            const start = (this.itemPage - 1) * this.perPage;
            return this.salesByItem.slice(start, start + this.perPage);
        },
        get totalItemPages() {
            return Math.ceil(this.salesByItem.length / this.perPage) || 1;
        },

        get paginatedCustomers() {
            const start = (this.custPage - 1) * this.perPage;
            return this.salesByCustomer.slice(start, start + this.perPage);
        },
        get totalCustPages() {
            return Math.ceil(this.salesByCustomer.length / this.perPage) || 1;
        },

        get paginatedSales() {
            const start = (this.salePage - 1) * this.perPage;
            return this.filteredSales.slice(start, start + this.perPage);
        },
        get totalSalePages() {
            return Math.ceil(this.filteredSales.length / this.perPage) || 1;
        },

        openCustomerDetail(cust) {
            this.activeCustomer = cust;
            this.showCustomerModal = true;
        },

        get activeCustomerSales() {
            if (!this.activeCustomer) return [];
            const cId = parseInt(this.activeCustomer.customer_id || 0);
            const cName = (this.activeCustomer.customer_name || 'Pelanggan Umum').toLowerCase();
            
            return this.sales.filter(s => {
                const sId = parseInt(s.customer_id || 0);
                const sName = (s.customer_name || 'Pelanggan Umum').toLowerCase();
                
                if (cId > 0 && sId > 0) {
                    return cId === sId;
                }
                return cName === sName;
            });
        },

        async openItemDetail(type, itemObj) {
            this.activeItemType = type;
            this.activeItemName = type === 'category' ? itemObj.category_name : itemObj.product_name;
            this.activeItemTotal = type === 'category' ? itemObj.total_revenue : itemObj.total_revenue;
            this.activeItemSales = [];
            this.showItemModal = true;
            this.isItemLoading = true;
            
            try {
                let url = `logic.php?action=get_sales_by_item&start_date=${this.filters.start_date}&end_date=${this.filters.end_date}&filter_type=${type}&filter_value=${encodeURIComponent(this.activeItemName)}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.activeItemSales = result.data || [];
                }
            } catch (error) {
                console.error("Gagal menarik data transaksi item:", error);
            } finally {
                this.isItemLoading = false;
            }
        },

        async openDetail(sale) {
            this.activeSale = sale;
            this.activeDetails = [];
            this.activePayments = [];
            this.activeSaleInfo = null;
            this.showModal = true;

            try {
                const formData = new FormData();
                formData.append('action', 'get_detail');
                formData.append('id', sale.id);

                const response = await fetch('logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.status === 'success') {
                    this.activeDetails = result.data || [];
                    this.activePayments = result.payments || [];
                    this.activeSaleInfo = result.sale_info || null;
                }
            } catch (error) {
                console.error('Error fetching sale details:', error);
            }
        },

        printReceipt(invoiceNo) {
            window.open(`../../kasir/print_receipt.php?invoice=${invoiceNo}&auto_print_usb=1`, '_blank', 'width=400,height=600');
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(parseFloat(angka) || 0);
        },

        printPDF(type, title) {
            let headers = [];
            let rows = [];
            
            if (type === 'metode_pembayaran') {
                headers = ['Metode Pembayaran', 'Total Pendapatan'];
                rows = this.paymentBreakdown.map(p => [p.payment_method, 'Rp ' + this.formatRupiah(p.total_amount)]);
            } else if (type === 'dp_pelunasan') {
                headers = ['Jenis Pembayaran', 'Jumlah Transaksi', 'Total Pendapatan'];
                rows = this.dpPelunasan.map(p => [p.payment_type, p.total_transactions + ' trx', 'Rp ' + this.formatRupiah(p.total_amount)]);
            } else if (type === 'kategori') {
                headers = ['Kategori', 'Item Terjual', 'Total Pendapatan'];
                rows = this.salesByCategory.map(c => [c.category_name, c.total_qty + ' items', 'Rp ' + this.formatRupiah(c.total_amount)]);
            } else if (type === 'barang') {
                headers = ['Peringkat', 'Nama Barang', 'Terjual (Pcs)', 'Total Pendapatan'];
                rows = this.salesByItem.map((i, idx) => [idx + 1, i.item_name, i.total_qty + ' pcs', 'Rp ' + this.formatRupiah(i.total_amount)]);
            } else if (type === 'pelanggan') {
                headers = ['Nama Pelanggan', 'Jumlah Transaksi', 'Total Belanja'];
                rows = this.salesByCustomer.map(c => [c.customer_name, c.total_transactions + ' trx', 'Rp ' + this.formatRupiah(c.total_spent)]);
            } else if (type === 'rincian') {
                headers = ['Invoice', 'Waktu', 'Pelanggan', 'Status', 'Metode', 'Total Bayar'];
                rows = this.filteredSales.map(s => [s.invoice_no, this.formatDate(s.created_at), s.customer_name || 'Pelanggan Umum', s.payment_status, s.payment_method, 'Rp ' + this.formatRupiah(s.total_amount)]);
            }

            let html = `
            <html>
            <head>
                <title>${title} - Love Cakes POS</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 25px; color: #333; }
                    h2 { color: #2563eb; margin-bottom: 5px; font-size: 20px; }
                    p { color: #666; font-size: 13px; margin-top: 0; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th, td { border: 1px solid #e2e8f0; padding: 10px 14px; text-align: left; font-size: 13px; }
                    th { background-color: #f8fafc; color: #334155; font-weight: bold; text-transform: uppercase; font-size: 11px; }
                    tr:nth-child(even) { background-color: #f8fafc; }
                    .footer { margin-top: 30px; font-size: 11px; color: #94a3b8; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 10px; }
                </style>
            </head>
            <body>
                <h2>${title}</h2>
                <p>Periode Filter: ${this.filters.start_date} s/d ${this.filters.end_date}</p>
                <table>
                    <thead>
                        <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
                    </thead>
                    <tbody>
                        ${rows.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('')}
                    </tbody>
                </table>
                <div class="footer">Dicetak otomatis dari Love Cakes POS pada ${new Date().toLocaleString('id-ID')}</div>
                <script>window.onload = function() { window.print(); }</script>
            </body>
            </html>`;
            
            let win = window.open('', '_blank', 'width=900,height=700');
            win.document.write(html);
            win.document.close();
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