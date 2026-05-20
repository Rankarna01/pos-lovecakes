document.addEventListener('alpine:init', () => {
    Alpine.data('piutangApp', () => ({
        transactions: [],
        searchQuery: '',
        timeRange: '', // Menyimpan nilai filter dropdown
        isLoading: false,
        isSubmitting: false,

        // State Modal
        showModal: false,
        activeTrx: null,
        payMethod: 'Cash',
        payAmount: 0,
        paymentMethods: [],

        async init() {
            await this.fetchPaymentMethods();
            await this.fetchData();
        },

        async fetchPaymentMethods() {
            try {
                const response = await fetch('logic.php?action=get_payment_methods');
                const result = await response.json();
                if (result.status === 'success') {
                    this.paymentMethods = result.data;
                    if (this.paymentMethods.length > 0) {
                        this.payMethod = this.paymentMethods[0].name;
                    }
                }
            } catch (error) {
                console.error("Gagal memuat metode pembayaran:", error);
            }
        },

        async fetchData() {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Halaman Data Piutang membutuhkan koneksi internet!', 'warning');
                } else {
                    alert('Anda sedang offline! Halaman Piutang membutuhkan koneksi internet.');
                }
                this.isLoading = false;
                return;
            }

            this.isLoading = true;
            try {
                // Susun URL Parameter untuk filter search & time
                const params = new URLSearchParams({
                    action: 'get_piutang',
                    search: this.searchQuery,
                    time_range: this.timeRange,
                    nocache: Date.now()
                });

                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.transactions = result.data || [];
                } else {
                    console.error("Gagal menarik data piutang:", result.message);
                }
            } catch (error) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal memuat data piutang dari server.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        get sisaTagihan() {
            if (!this.activeTrx) return 0;
            return parseFloat(this.activeTrx.total_amount) - parseFloat(this.activeTrx.dp_amount);
        },

        get kembalian() {
            const selectedMethod = this.paymentMethods.find(m => m.name === this.payMethod);
            if (selectedMethod && selectedMethod.type !== 'Cash') return 0;
            return parseFloat(this.payAmount || 0) - this.sisaTagihan;
        },

        openModal(trx) {
            this.activeTrx = trx;
            if (this.paymentMethods.length > 0) {
                this.payMethod = this.paymentMethods[0].name;
            }
            this.payAmount = this.sisaTagihan; // Default input terisi sejumlah sisa tagihan
            this.showModal = true;
        },

        async processSettlement() {
            // 🛡️ CEGAT JIKA OFFLINE SAAT MAU BAYAR
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline Mode', 'Koneksi terputus! Tidak dapat memproses pelunasan piutang saat offline.', 'warning');
                return;
            }

            const selectedMethod = this.paymentMethods.find(m => m.name === this.payMethod);
            const isCash = selectedMethod && selectedMethod.type === 'Cash';

            if (isCash && this.payAmount < this.sisaTagihan) {
                if (typeof Swal !== 'undefined') Swal.fire('Perhatian', 'Jumlah uang diterima kurang dari sisa tagihan!', 'warning');
                return;
            }

            this.isSubmitting = true;
            try {
                const fd = new FormData();
                fd.append('sale_id', this.activeTrx.id);
                fd.append('payment_method', this.payMethod);
                
                // Kalau bukan Cash, otomatis uang diterima = sisa tagihan
                const finalPay = isCash ? this.payAmount : this.sisaTagihan;
                fd.append('pay_amount', finalPay);

                const response = await fetch('logic.php?action=settle_payment', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    this.showModal = false;
                    
                    if (typeof Swal !== 'undefined') {
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
                    }
                    
                    this.fetchData(); // Refresh tabel setelah pelunasan
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Melunasi', result.message, 'error');
                }
            } catch (error) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal memproses ke database pusat.', 'error');
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