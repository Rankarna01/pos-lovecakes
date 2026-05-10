// views/pengaturan_printer/ajax.js
document.addEventListener('alpine:init', () => {
    Alpine.data('printerApp', () => ({
        printerName: 'Belum ada printer terhubung',
        isConnected: false,

        init() {
            // Cek apakah sebelumnya sudah ada printer yang disimpan
            const savedPrinter = localStorage.getItem('pos_printer_name');
            if (savedPrinter) {
                this.printerName = savedPrinter;
                this.isConnected = true;
            }
        },

        async hubungkanPrinter() {
            if (!navigator.bluetooth) {
                Swal.fire('Tidak Mendukung', 'Browser ini tidak mendukung Web Bluetooth. Gunakan Google Chrome versi terbaru di PC atau Android.', 'error');
                return;
            }

            try {
                // Minta izin ke kasir untuk memilih printer dari pop-up Chrome
                const device = await navigator.bluetooth.requestDevice({
                    // Filter untuk UUID Standar Printer Thermal ESC/POS
                    filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }], 
                    optionalServices: ['e7810a71-73ae-499d-8c15-faa9aef0c3f2']
                });

                // Simpan nama printer ke memori browser
                this.printerName = device.name;
                this.isConnected = true;
                localStorage.setItem('pos_printer_name', device.name);

                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: `Printer ${device.name} Disimpan!`,
                    text: 'Printer siap digunakan untuk mencetak struk.',
                    showConfirmButton: false, timer: 3000
                });

            } catch (error) {
                console.error("Batal/Gagal:", error);
                Swal.fire('Dibatalkan', 'Pencarian printer dibatalkan atau tidak ada perangkat terdeteksi.', 'info');
            }
        },

        hapusPrinter() {
            localStorage.removeItem('pos_printer_name');
            this.printerName = 'Belum ada printer terhubung';
            this.isConnected = false;
            
            Swal.fire({ 
                toast: true, position: 'top-end', icon: 'warning', 
                title: 'Printer dihapus dari memori', 
                showConfirmButton: false, timer: 1500 
            });
        }
    }));
});