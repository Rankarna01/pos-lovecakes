document.addEventListener('alpine:init', () => {
    Alpine.data('salesHistoryApp', () => ({
        sales: [],
        isLoading: false,
        filters: {
            search: '',
            channel: '',
            payment: ''
        },

        // State Modal Detail
        showModal: false,
        activeSale: null,
        activeDetails: [],
        isDetailLoading: false,

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../../auth/index.php'; return; }
            }
            await this.fetchSales();
        },

        async fetchSales() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams(this.filters);
                params.append('action', 'get_sales');
                params.append('nocache', Date.now());

                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.sales = result.data;
                } else {
                    console.error(result.message);
                }
            } catch (error) {
                console.error("Gagal menarik data penjualan", error);
            } finally {
                this.isLoading = false;
            }
        },

        async openDetail(sale) {
            this.activeSale = sale;
            this.activeDetails = [];
            this.showModal = true;
            this.isDetailLoading = true;

            try {
                const response = await fetch(`logic.php?action=get_detail&id=${sale.id}&nocache=${Date.now()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.activeDetails = result.data;
                }
            } catch (error) {
                console.error("Gagal menarik detail", error);
            } finally {
                this.isDetailLoading = false;
            }
        },

        printReceipt(invoice) {
            if (!invoice) return;
            // Kita numpang cetak pakai file print_receipt.php milik Kasir biar formatnya seragam 58mm
            const printUrl = `../../kasir/print_receipt.php?invoice=${invoice}`;
            window.open(printUrl, '_blank', 'width=400,height=600');
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
                   d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(parseFloat(angka) || 0);
        }
    }));
});