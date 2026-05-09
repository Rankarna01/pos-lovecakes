document.addEventListener('alpine:init', () => {
    Alpine.data('opnameApp', () => ({
        barcodeInput: '',
        scannedProduct: null,
        actualStock: 0,
        opnameNotes: '',
        
        isSaving: false,
        isCameraOpen: false,
        html5QrcodeScanner: null,

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../../auth/index.php'; return; }
            }
        },

        // MENCARI PRODUK BERDASARKAN BARCODE
        async searchBarcode(scannedCode = null) {
            const codeToSearch = scannedCode || this.barcodeInput;
            if (!codeToSearch) return;

            try {
                const response = await fetch(`logic.php?action=scan_barcode&code=${codeToSearch}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.scannedProduct = result.data;
                    this.actualStock = parseInt(this.scannedProduct.stock);
                    this.opnameNotes = '';
                    
                    // Bunyikan Beep Sukses (Opsional)
                    this.playBeep();
                    
                    // Kalau pakai kamera, tutup kameranya biar fokus input
                    if (this.isCameraOpen) this.toggleCamera();
                    
                } else {
                    Swal.fire('Tidak Ditemukan', result.message, 'error');
                }
            } catch (error) {
                console.error("Error Scan:", error);
                Swal.fire('Error', 'Gagal memproses barcode.', 'error');
            } finally {
                this.barcodeInput = ''; // Kosongkan lagi form inputnya
            }
        },

        // MENGHITUNG SELISIH REAL-TIME
        get selisih() {
            if (!this.scannedProduct) return 0;
            return parseInt(this.actualStock || 0) - parseInt(this.scannedProduct.stock);
        },

        // MENYIMPAN DATA OPNAME
        async saveOpname() {
            if (this.selisih === 0) return;
            this.isSaving = true;

            try {
                const fd = new FormData();
                fd.append('product_id', this.scannedProduct.id);
                fd.append('system_stock', this.scannedProduct.stock);
                fd.append('actual_stock', this.actualStock);
                fd.append('notes', this.opnameNotes);

                const response = await fetch('logic.php?action=save_opname', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: 'Stok Berhasil Disesuaikan!',
                        showConfirmButton: false, timer: 1500,
                        customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                    });
                    this.resetScan();
                } else {
                    Swal.fire('Gagal Menyimpan', result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Gagal menyambung ke database.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        resetScan() {
            this.scannedProduct = null;
            this.actualStock = 0;
            this.opnameNotes = '';
        },

        // ===== LOGIKA KAMERA HP (HTML5 QRCODE) =====
        toggleCamera() {
            if (this.isCameraOpen) {
                this.stopCamera();
            } else {
                this.startCamera();
            }
        },

        startCamera() {
            this.isCameraOpen = true;
            this.resetScan();
            
            // Konfigurasi Scanner
            this.html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 }, false);
            
            this.html5QrcodeScanner.render((decodedText, decodedResult) => {
                // Ketika Barcode berhasil terbaca oleh kamera
                this.searchBarcode(decodedText);
            }, (error) => {
                // Ignore error pembacaan frame per detik
            });
        },

        stopCamera() {
            if (this.html5QrcodeScanner) {
                this.html5QrcodeScanner.clear().then(() => {
                    this.isCameraOpen = false;
                }).catch(error => {
                    console.error("Gagal menutup kamera", error);
                });
            } else {
                this.isCameraOpen = false;
            }
        },

        // Fake Beep Sound
        playBeep() {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = context.createOscillator();
            oscillator.type = 'sine';
            oscillator.frequency.value = 800; // Nada tinggi (beep)
            oscillator.connect(context.destination);
            oscillator.start();
            setTimeout(() => { oscillator.stop(); }, 150);
        }
    }));
});