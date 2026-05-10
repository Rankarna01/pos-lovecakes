document.addEventListener('alpine:init', () => {
    Alpine.data('reportApp', () => ({
        startDate: new Date().toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
        isLoading: false,
        paymentData: { cash: 0, qris: 0, total: 0 },
        shiftData: [],

        init() {
            // ❌ CEK SESI dbAuth DIHAPUS TOTAL!
            // Urusan login sudah dijamin oleh PHP (config/auth.php).
            
            // Langsung muat laporan saat pertama kali buka
            this.fetchReport(); 
        },

        async fetchReport() {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Gagal memuat laporan. Pastikan Anda terhubung ke internet!', 'warning');
                }
                this.isLoading = false;
                return;
            }

            if(!this.startDate || !this.endDate) { 
                if (typeof Swal !== 'undefined') Swal.fire('Perhatian', 'Tanggal wajib diisi!', 'warning'); 
                return; 
            }

            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_report&start_date=${this.startDate}&end_date=${this.endDate}&nocache=${Date.now()}`);
                const result = await res.json();
                
                if (result.status === 'success') {
                    this.paymentData = result.payments;
                    this.shiftData = result.shifts;
                } else { 
                    if (typeof Swal !== 'undefined') Swal.fire('Error', result.message, 'error'); 
                }
            } catch (error) { 
                console.error("Report Fetch Error:", error);
                if (typeof Swal !== 'undefined') Swal.fire('Koneksi Gagal', 'Gagal menarik data dari server pusat', 'error'); 
            } 
            finally { 
                // WAJIB: Pastikan spinner loading mati
                this.isLoading = false; 
            }
        },

        exportExcel() {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Export Excel membutuhkan koneksi internet!', 'warning');
                return;
            }

            if(!this.startDate || !this.endDate) return;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({ 
                    toast: true, position: 'top-end', icon: 'info', 
                    title: 'Mendownload Excel...', showConfirmButton: false, timer: 1500 
                });
            }
            window.location.href = `logic.php?action=export_excel&start_date=${this.startDate}&end_date=${this.endDate}`;
        },

        printPdf() {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Cetak PDF membutuhkan koneksi internet!', 'warning');
                return;
            }

            if(!this.startDate || !this.endDate) return;
            // Buka halaman khusus print di tab baru
            window.open(`print_pdf.php?start_date=${this.startDate}&end_date=${this.endDate}`, '_blank');
        },

        formatRupiah(angka) { 
            return new Intl.NumberFormat('id-ID').format(parseFloat(angka) || 0); 
        }
    }));
});