document.addEventListener('alpine:init', () => {
    Alpine.data('salesHistoryApp', () => ({
        sales: [],
        isLoading: false,
        filters: {
            search: '',
            channel: '',
            payment: '',
            time_range: '', // FITUR BARU
            status: ''      // FITUR BARU
        },
        currentPage: 1,
        totalPages: 1,

        // State Modal Detail
        activeSale: null,
        activeDetails: [],
        activePayments: [],
        activeSaleInfo: null,
        showModal: false,
        isDetailLoading: false,

        // State Modal Void
        showVoidModal: false,
        voidItems: [],
        voidReason: '',
        voidPin: '',
        voidTotalAmount: 0,
        isSubmittingVoid: false,

        async init() {
            await this.fetchSales();
        },

        async fetchSales() {
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
                // Semua filter dimasukkan secara otomatis
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
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Koneksi terputus! Tidak dapat melihat detail transaksi saat offline.', 'warning');
                }
                return;
            }

            this.activeSale = sale;
            this.activeDetails = [];
            this.activePayments = [];
            this.activeSaleInfo = null;
            this.showModal = true;
            this.isDetailLoading = true;

            try {
                const response = await fetch(`logic.php?action=get_detail&id=${sale.id}&nocache=${Date.now()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.activeDetails = result.data || [];
                    this.activePayments = result.payments || [];
                    this.activeSaleInfo = result.sale_info || null;
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                }
            } catch (error) {
                console.error("Gagal menarik detail", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menarik rincian pesanan dari database.', 'error');
            } finally {
                this.isDetailLoading = false;
            }
        },

        printReceipt(invoice) {
            if (!invoice) return;
            const printUrl = `../../kasir/print_receipt.php?invoice=${invoice}&auto_print_usb=1`;
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
        },

        // VOID LOGIC
        openVoidModal() {
            if (!this.activeSale) return;
            this.voidReason = '';
            this.voidPin = '';
            this.voidTotalAmount = 0;
            
            // Map items for void checklist
            this.voidItems = this.activeDetails.map(item => ({
                id: item.id,
                product_id: item.product_id,
                product_name: item.product_name,
                price: parseFloat(item.price),
                max_qty: parseInt(item.qty) - parseInt(item.cancelled_qty || 0),
                void_qty: parseInt(item.qty) - parseInt(item.cancelled_qty || 0),
                selected: false
            })).filter(item => item.max_qty > 0);

            this.showVoidModal = true;
        },

        closeVoidModal() {
            this.showVoidModal = false;
        },

        toggleSelectAllVoid() {
            const allSelected = this.voidItems.every(i => i.selected);
            this.voidItems.forEach(i => i.selected = !allSelected);
            this.calculateVoidAmount();
        },

        calculateVoidAmount() {
            let total = 0;
            this.voidItems.forEach(item => {
                if (item.selected) {
                    let vq = parseInt(item.void_qty);
                    if (isNaN(vq) || vq < 1) vq = 1;
                    if (vq > item.max_qty) vq = item.max_qty;
                    item.void_qty = vq;
                    total += (vq * item.price);
                }
            });
            this.voidTotalAmount = total;
        },

        async submitVoid() {
            const selectedItems = this.voidItems.filter(i => i.selected);
            if (selectedItems.length === 0) {
                if (typeof Swal !== 'undefined') Swal.fire('Peringatan', 'Pilih minimal 1 item untuk dibatalkan', 'warning');
                return;
            }
            if (!this.voidReason || !this.voidPin) {
                if (typeof Swal !== 'undefined') Swal.fire('Peringatan', 'Alasan dan PIN Admin wajib diisi!', 'warning');
                return;
            }

            const isFullVoid = this.voidItems.every(i => i.selected && i.void_qty === i.max_qty);

            this.isSubmittingVoid = true;
            try {
                const formData = new FormData();
                formData.append('action', 'cancel_sale');
                formData.append('sale_id', this.activeSale.id);
                formData.append('cancellation_type', isFullVoid ? 'full' : 'partial');
                formData.append('reason', this.voidReason);
                formData.append('pin', this.voidPin);
                formData.append('total_amount', this.voidTotalAmount);
                formData.append('items', JSON.stringify(selectedItems.map(i => ({
                    sale_detail_id: i.id,
                    product_id: i.product_id,
                    qty: i.void_qty,
                    price: i.price,
                    amount: i.void_qty * i.price
                }))));

                const response = await fetch('logic.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    if (typeof Swal !== 'undefined') Swal.fire('Berhasil!', result.message, 'success');
                    this.closeVoidModal();
                    this.showModal = false;
                    this.fetchSales();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                }
            } catch (error) {
                console.error("Gagal membatalkan transaksi", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan sistem saat membatalkan.', 'error');
            } finally {
                this.isSubmittingVoid = false;
            }
        }
    }));
});