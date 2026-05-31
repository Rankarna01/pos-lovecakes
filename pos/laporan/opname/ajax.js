function laporanOpnameApp() {
    return {
        isLoading: false,
        rows: [],
        summary: {
            total: 0,
            plus: 0,
            minus: 0
        },
        
        // Filter Tanggal (Default: Awal Bulan sd Hari Ini)
        dateFrom: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        dateTo: new Date().toISOString().split('T')[0],

        init() {
            this.loadData();
        },

        async loadData() {
            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_report&date_from=${this.dateFrom}&date_to=${this.dateTo}`);
                const data = await res.json();
                
                if (data.status === 'success') {
                    this.rows = data.data;
                    this.summary = data.summary;
                } else {
                    Swal.fire('Error', data.message || 'Gagal memuat laporan', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        formatWaktu(datetime) {
            if (!datetime) return '-';
            return new Date(datetime).toLocaleString('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },

        printReport() {
            window.print();
        }
    };
}
