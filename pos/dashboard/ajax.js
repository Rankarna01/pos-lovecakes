function dashboardApp() {
    return {
        isLoading: true,
        showInstallBtn: false,
        deferredPrompt: null,
        chartInstance: null,

        // Wadah Data Default
        summary: {
            pelanggan_baru: 0,
            total_penjualan: 0, pct_penjualan: 0,
            penjualan_kotor: 0, pct_kotor: 0,
            laba_kotor: 0, pct_laba: 0,
            laba_bersih: 0,
            total_transaksi: 0, pct_transaksi: 0
        },

        init() {
            // Deteksi PWA Install
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.showInstallBtn = true;
                this.$el.querySelector('button[x-show="showInstallBtn"]').style.display = 'flex';
            });

            // Panggil Data dari Database
            this.fetchDashboardData();
        },

        async fetchDashboardData() {
            this.isLoading = true;
            try {
                let formData = new FormData();
                formData.append('action', 'get_dashboard_data');

                let response = await fetch('logic.php', {
                    method: 'POST',
                    body: formData
                });
                
                let result = await response.json();

                if (result.status === 'success') {
                    this.summary = result.summary;
                    this.renderChart(result.chart);
                } else {
                    console.error("Gagal memuat data:", result.message);
                }
            } catch (error) {
                console.error("Kesalahan jaringan:", error);
            } finally {
                this.isLoading = false;
            }
        },

        renderChart(chartData) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Hancurkan chart lama jika ada (mencegah bug tumpuk)
            if (this.chartInstance) {
                this.chartInstance.destroy();
            }

            // Buat Gradasi Warna Biru Modern
            let gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(37, 99, 235, 0.4)'); // Biru transparan
            gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)'); // Memudar ke bawah

            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels, // Tanggal (Sumbu X)
                    datasets: [{
                        label: 'Pendapatan Harian (IDR)',
                        data: chartData.values, // Nilai (Sumbu Y)
                        borderColor: '#2563EB', // Garis Utama
                        backgroundColor: gradient, // Isian Bawah Garis
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4, // Efek Melengkung (Smooth Curved)
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2563EB',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { family: 'Poppins' },
                            bodyFont: { family: 'Poppins', weight: 'bold' },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'IDR ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        x: { 
                            grid: { display: false },
                            ticks: { font: { family: 'Poppins', size: 11 }, color: '#94a3b8' }
                        },
                        y: { 
                            grid: { color: '#f1f5f9', borderDash: [5, 5] },
                            ticks: { font: { family: 'Poppins', size: 11 }, color: '#94a3b8' },
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        installPWA() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                this.deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        this.showInstallBtn = false;
                    }
                    this.deferredPrompt = null;
                });
            }
        },

        formatRupiah(angka) {
            if (!angka) return '0';
            return parseInt(angka).toLocaleString('id-ID');
        }
    }
}