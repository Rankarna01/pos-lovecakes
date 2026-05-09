document.addEventListener('alpine:init', () => {
    Alpine.data('piutangApp', () => ({
        transactions: [],
        searchQuery: '',
        isLoading: false,
        isSubmitting: false,

        // State Modal
        showModal: false,
        activeTrx: null,
        payMethod: 'cash',
        payAmount: 0,

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../../auth/index.php'; return; }
            }
            await this.fetchData();
        },

        async fetchData() {
            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=get_piutang&nocache=${Date.now()}`);
                const result = await response.json();
                if (result.status === 'success') {
                    this.transactions = result.data || [];
                }
            } catch (error) {
                Swal.fire('Error', 'Gagal memuat data piutang.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        get filteredData() {
            if (this.searchQuery.trim() === '') return this.transactions;
            const q = this.searchQuery.toLowerCase();
            return this.transactions.filter(t => 
                t.invoice_no.toLowerCase().includes(q) || 
                (t.customer_name && t.customer_name.toLowerCase().includes(q))
            );
        },

        get sisaTagihan() {
            if (!this.activeTrx) return 0;
            return parseFloat(this.activeTrx.total_amount) - parseFloat(this.activeTrx.dp_amount);
        },

        get kembalian() {
            if (this.payMethod === 'qris') return 0;
            return parseFloat(this.payAmount || 0) - this.sisaTagihan;
        },

        openModal(trx) {
            this.activeTrx = trx;
            this.payMethod = 'cash';
            this.payAmount = this.sisaTagihan; // Default input terisi sejumlah tagihan
            this.showModal = true;
        },

        async processSettlement() {
            if (this.payMethod === 'cash' && this.payAmount < this.sisaTagihan) {
                Swal.fire('Perhatian', 'Jumlah uang diterima kurang dari sisa tagihan!', 'warning');
                return;
            }

            this.isSubmitting = true;
            try {
                const fd = new FormData();
                fd.append('sale_id', this.activeTrx.id);
                fd.append('payment_method', this.payMethod);
                // Kalau QRIS, otomatis uang diterima = sisa tagihan
                const finalPay = this.payMethod === 'qris' ? this.sisaTagihan : this.payAmount;
                fd.append('pay_amount', finalPay);

                const response = await fetch('logic.php?action=settle_payment', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    this.showModal = false;
                    Swal.fire({
                        title: 'Pelunasan Berhasil!',
                        text: `Sisa tagihan untuk Invoice ${this.activeTrx.invoice_no} sudah dilunasi.`,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa-solid fa-print"></i> Cetak Struk',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#2563EB'
                    }).then((swalResult) => {
                        if (swalResult.isConfirmed) {
                            window.open(`../../kasir/print_receipt.php?invoice=${this.activeTrx.invoice_no}`, '_blank', 'width=400,height=600');
                        }
                    });
                    
                    this.fetchData(); // Refresh tabel
                } else {
                    Swal.fire('Gagal Melunasi', result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Gagal memproses ke database.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
                   d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        formatRupiah(angka) {
            const val = parseFloat(angka);
            if (isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        }
    }));
});