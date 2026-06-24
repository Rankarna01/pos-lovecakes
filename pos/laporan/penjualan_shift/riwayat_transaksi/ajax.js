document.addEventListener('alpine:init', () => {
    Alpine.data('reportApp', () => ({
        isLoading: false,
        startDate: new Date().toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
        
        salesByCustomer: [],
        salesDetails: [],

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
