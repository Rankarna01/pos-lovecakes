document.addEventListener('alpine:init', () => {
    Alpine.data('reportApp', () => ({
        startDate: new Date().toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
        isLoading: false,
        paymentData: { cash: 0, qris: 0, total: 0 },
        shiftData: [],

        init() { this.fetchReport(); },

        async fetchReport() {
            if(!this.startDate || !this.endDate) { Swal.fire('Perhatian', 'Tanggal wajib diisi!', 'warning'); return; }
            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_report&start_date=${this.startDate}&end_date=${this.endDate}&nocache=${Date.now()}`);
                const result = await res.json();
                
                if (result.status === 'success') {
                    this.paymentData = result.payments;
                    this.shiftData = result.shifts;
                } else { Swal.fire('Error', result.message, 'error'); }
            } catch (error) { Swal.fire('Koneksi Gagal', 'Gagal menarik data dari server', 'error'); } 
            finally { this.isLoading = false; }
        },

        exportExcel() {
            if(!this.startDate || !this.endDate) return;
            Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Mendownload Excel...', showConfirmButton: false, timer: 1500 });
            window.location.href = `logic.php?action=export_excel&start_date=${this.startDate}&end_date=${this.endDate}`;
        },

        printPdf() {
            if(!this.startDate || !this.endDate) return;
            // Buka halaman khusus print di tab baru
            window.open(`print_pdf.php?start_date=${this.startDate}&end_date=${this.endDate}`, '_blank');
        },

        formatRupiah(angka) { 
            return new Intl.NumberFormat('id-ID').format(parseFloat(angka) || 0); 
        }
    }));
});