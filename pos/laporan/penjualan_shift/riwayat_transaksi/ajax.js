document.addEventListener('alpine:init', () => {
    Alpine.data('reportApp', () => ({
        isLoading: false,
        startDate: new Date().toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
        
        salesByCustomer: [],
        salesDetails: [],
        filterStatus: 'all',
        currentPage: 1,
        pageSize: 15,

        get filteredDetails() {
            return this.salesDetails.filter(dt => {
                if (this.filterStatus === 'all') return true;
                const isDp = parseFloat(dt.dp_amount) > 0;
                if (this.filterStatus === 'dp_belum') return dt.payment_status === 'dp';
                if (this.filterStatus === 'dp_lunas') return dt.payment_status === 'lunas' && isDp;
                if (this.filterStatus === 'lunas_langsung') return dt.payment_status === 'lunas' && !isDp;
                return true;
            });
        },
        get totalPages() {
            return Math.ceil(this.filteredDetails.length / this.pageSize) || 1;
        },
        get paginatedDetails() {
            const start = (this.currentPage - 1) * this.pageSize;
            return this.filteredDetails.slice(start, start + this.pageSize);
        },

        init() { this.fetchReport(); },

        async fetchReport() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({ action: 'get_report', start_date: this.startDate, end_date: this.endDate });
                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.salesByCustomer = result.sales_by_customer;
                    this.salesDetails = result.sales_details;
                    this.currentPage = 1;
                }
            } catch (error) { 
                console.error('Fetch error:', error); 
            } finally { 
                this.isLoading = false; 
            }
        },

        exportExcel() {
            if(!this.startDate || !this.endDate) return;
            if (typeof Swal !== 'undefined') {
                Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Mendownload Excel...', showConfirmButton: false, timer: 1500 });
            }
            window.location.href = `logic.php?action=export_excel&start_date=${this.startDate}&end_date=${this.endDate}`;
        },

        printPdf() {
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Cetak PDF membutuhkan koneksi internet!', 'warning');
                return;
            }
            if(!this.startDate || !this.endDate) return;
            window.open(`print_pdf.php?start_date=${this.startDate}&end_date=${this.endDate}`, '_blank');
        },

        formatRupiah(angka) { 
            return new Intl.NumberFormat('id-ID').format(parseFloat(angka) || 0); 
        }
    }));
});
