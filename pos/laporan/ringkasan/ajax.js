document.addEventListener('alpine:init', () => {
    Alpine.data('ringkasanApp', () => ({
        isLoading: false,
        
        // Filter Tanggal Default: Awal bulan s/d Hari ini
        filters: {
            start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0]
        },

        // State Data
        hours: [],
        maxTrx: 0, // Untuk menentukan tinggi maksimal bar grafik
        customers: [],

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../../auth/index.php'; return; }
            }
            this.generateEmptyHours();
            await this.fetchData();
        },

        // Bikin array 24 jam kosongan (00:00 - 23:00)
        generateEmptyHours() {
            let temp = [];
            for (let i = 0; i < 24; i++) {
                temp.push({
                    hour: i,
                    label: i.toString().padStart(2, '0') + ':00',
                    trx: 0
                });
            }
            this.hours = temp;
        },

        async fetchData() {
            this.isLoading = true;
            this.generateEmptyHours(); // Reset grafik
            this.maxTrx = 0;

            try {
                const params = new URLSearchParams(this.filters);
                params.append('action', 'get_summary');
                params.append('nocache', Date.now());

                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();

                if (result.status === 'success') {
                    // Masukkan data heatmap
                    const heatmapRaw = result.data.heatmap || [];
                    heatmapRaw.forEach(item => {
                        let hourInt = parseInt(item.hour_of_day);
                        let totalTrx = parseInt(item.total_trx);
                        
                        // Timpa data kosong dengan data asli
                        if(this.hours[hourInt]) {
                            this.hours[hourInt].trx = totalTrx;
                            if (totalTrx > this.maxTrx) {
                                this.maxTrx = totalTrx; // Update puncak tertinggi
                            }
                        }
                    });

                    // Masukkan data pelanggan
                    this.customers = result.data.customers || [];
                } else {
                    alert("Gagal menarik data ringkasan.");
                }
            } catch (error) {
                console.error("Gagal Request API", error);
            } finally {
                this.isLoading = false;
            }
        }
    }));
});