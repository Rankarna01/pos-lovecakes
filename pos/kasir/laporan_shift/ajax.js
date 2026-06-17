document.addEventListener('alpine:init', () => {
    Alpine.data('shiftReportApp', () => ({
        shifts: [],
        isLoading: false,
        currentPage: 1,
        totalPages: 1,
        filters: { 
            search: '',
            startDate: new Date().toISOString().split('T')[0],
            endDate: new Date().toISOString().split('T')[0]
        },

        showModal: false,
        isDetailLoading: false,
        activeShift: null,
        activeTransactions: [],
        activeSettlements: [],
        activePettyCash: [],

        init() { this.fetchShifts(); },

        formatRupiah(number) {
            if (!number) return '0';
            return new Intl.NumberFormat('id-ID').format(Math.floor(number));
        },

        formatDate(datetime) {
            if (!datetime) return '-';
            const d = new Date(datetime);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        async fetchShifts() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({ 
                    action: 'get_shifts', 
                    page: this.currentPage, 
                    search: this.filters.search,
                    start_date: this.filters.startDate,
                    end_date: this.filters.endDate
                });
                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.shifts = result.data;
                    this.currentPage = result.pagination.current_page;
                    this.totalPages = result.pagination.total_pages;
                }
            } catch (error) { console.error('Fetch error:', error); } 
            finally { this.isLoading = false; }
        },

        applyFilter() { this.currentPage = 1; this.fetchShifts(); },
        nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.fetchShifts(); } },
        prevPage() { if (this.currentPage > 1) { this.currentPage--; this.fetchShifts(); } },

        async openDetail(shift) {
            this.activeShift = shift;
            this.activeTransactions = [];
            this.activeSettlements = [];
            this.activePettyCash = [];
            this.showModal = true;
            this.isDetailLoading = true;

            try {
                const response = await fetch(`logic.php?action=get_detail&id=${shift.id}`);
                const result = await response.json();

                if (result.status === 'success') {
                    // Ambil object shift matang yang sudah dihitung server
                    this.activeShift = result.shift;
                    this.activeTransactions = result.transactions;
                    this.activeSettlements  = result.settlements || [];
                    this.activePettyCash    = result.petty_cash;
                } else {
                    alert(result.message);
                    this.showModal = false;
                }
            } catch (error) {
                console.error('Detail fetch error:', error);
                this.showModal = false;
            } finally {
                this.isDetailLoading = false;
            }
        },

        printShift(shiftId) {
            window.open(`../print_shift.php?id=${shiftId}`, '_blank', 'width=400,height=600');
        }
    }));
});