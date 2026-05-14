document.addEventListener('alpine:init', () => {
    Alpine.data('salesHistoryApp', () => ({
        sales: [],
        isLoading: false,
        filters: {
            search: '',
            channel: '',
            payment: ''
        },
        currentPage: 1,
        totalPages: 1,

        // State Modal Detail
        showModal: false,
        activeSale: null,
        activeDetails: [],
        isDetailLoading: false,

        async init() {
            // ❌ CEK SESI dbAuth DIHAPUS TOTAL!
            // Keamanan halaman Transaksi ini 100% dijamin oleh config/auth.php dari server.
            
            // Langsung tarik data histori dari MySQL
            await this.fetchSales();
        },

        async fetchSales() {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Halaman Riwayat Transaksi membutuhkan koneksi internet!', 'warning');
                } else {
                    alert('Anda sedang offline! Halaman Transaksi membutuhkan koneksi internet.');
                }
                this.isLoading = false;
                return;
            }

            this.isLoading = true;
            try {
                const params = new URLSearchParams(this.filters);
                params.append('action', 'get_sales');
                params.append('page', this.currentPage);
                params.append('nocache', Date.now());

                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.sales = result.data;
                    this.totalPages = result.pagination.total_pages;
                } else {
                    console.error(result.message);
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                }
            } catch (error) {
                console.error("Gagal menarik data penjualan", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error Database', 'Gagal menarik data dari server pusat.', 'error');
            } finally {
                // WAJIB: Pastikan spinner selalu mati
                this.isLoading = false;
            }
        },

        applyFilter() {
            this.currentPage = 1;
            this.fetchSales();
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.fetchSales();
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.fetchSales();
            }
        },

        async openDetail(sale) {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Koneksi terputus! Tidak dapat melihat detail transaksi saat offline.', 'warning');
                }
                return;
            }

            this.activeSale = sale;
            this.activeDetails = [];
            this.showModal = true;
            this.isDetailLoading = true;

            try {
                const response = await fetch(`logic.php?action=get_detail&id=${sale.id}&nocache=${Date.now()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.activeDetails = result.data;
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                }
            } catch (error) {
                console.error("Gagal menarik detail", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menarik rincian pesanan dari database.', 'error');
            } finally {
                // WAJIB: Pastikan spinner detail mati
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