// pos/dashboard/ajax.js
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardApp', () => ({
        showInstallBtn: false,
        deferredPrompt: null,

        init() {
            // 1. MENGUPING SINYAL INSTALL PWA DARI CHROME
            window.addEventListener('beforeinstallprompt', (e) => {
                // Cegah pop-up otomatis bawaan Chrome
                e.preventDefault();
                // Simpan event-nya ke memori
                this.deferredPrompt = e;
                // Munculkan tombol hijau "Install POS"
                this.showInstallBtn = true;
            });

            // 2. MENGUPING JIKA APLIKASI SUDAH SUKSES DI-INSTALL
            window.addEventListener('appinstalled', () => {
                console.log('Mantap! PWA berhasil di-install.');
                this.showInstallBtn = false;
                this.deferredPrompt = null;
            });

            // 3. RENDER GRAFIK CHART.JS SAAT HALAMAN DIBUKA
            setTimeout(() => {
                this.loadChart();
            }, 100);
        },

        // FUNGSI KETIKA TOMBOL INSTALL DIKLIK KASIR
        async installPWA() {
            if (this.deferredPrompt) {
                // Tampilkan pop-up dialog instalasi
                this.deferredPrompt.prompt();
                // Tunggu reaksi user (klik Install atau Cancel)
                const { outcome } = await this.deferredPrompt.userChoice;
                console.log(`Kasir memilih: ${outcome}`);
                
                // Hapus event dan sembunyikan tombol
                this.deferredPrompt = null;
                this.showInstallBtn = false;
            }
        },

        // FUNGSI RENDER GRAFIK PENJUALAN
        loadChart() {
            const canvas = document.getElementById('salesChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Ming'],
                    datasets: [{
                        label: 'Pendapatan',
                        data: [1500000, 2300000, 1800000, 3200000, 2900000, 4500000, 7191800],
                        borderColor: '#2563EB', backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true, tension: 0.4
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    plugins: { legend: { display: false } } 
                }
            });
        }
    }));
});