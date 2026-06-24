document.addEventListener('alpine:init', () => {
    Alpine.data('reportApp', () => ({
        isLoading: false,
        startDate: new Date().toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
        
        salesByCategory: [],
        salesByItem: [],

        init() { this.fetchReport(); },

        async fetchReport() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({ action: 'get_report', start_date: this.startDate, end_date: this.endDate });
                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.salesByCategory = result.sales_by_category;
                    this.salesByItem = result.sales_by_item;
                    this.catPage = 1;
                    this.itemPage = 1;
                }
            } catch (error) { 
                console.error('Fetch error:', error); 
            } finally { 
                this.isLoading = false; 
            }
        },

        // Pagination State
        catPage: 1,
        itemPage: 1,
        itemsPerPage: 10,

        // Getter for Paginated Data
        get paginatedCategory() {
            const start = (this.catPage - 1) * this.itemsPerPage;
            return this.salesByCategory.slice(start, start + this.itemsPerPage);
        },
        get totalCatPages() {
            return Math.ceil(this.salesByCategory.length / this.itemsPerPage) || 1;
        },
        nextCat() { if (this.catPage < this.totalCatPages) this.catPage++; },
        prevCat() { if (this.catPage > 1) this.catPage--; },

        get paginatedItem() {
            const start = (this.itemPage - 1) * this.itemsPerPage;
            return this.salesByItem.slice(start, start + this.itemsPerPage);
        },
        get totalItemPages() {
            return Math.ceil(this.salesByItem.length / this.itemsPerPage) || 1;
        },
        nextItem() { if (this.itemPage < this.totalItemPages) this.itemPage++; },
        prevItem() { if (this.itemPage > 1) this.itemPage--; },

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
