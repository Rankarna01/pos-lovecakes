document.addEventListener('alpine:init', () => {
    Alpine.data('opnameApp', () => ({
        // Scanner tunggal
        barcodeInput: '',
        scannedProduct: null,
        actualStock: 0,
        opnameNotes: '',
        isSaving: false,
        isCameraOpen: false,
        html5QrcodeScanner: null,

        // Pilihan Outlet / Store untuk Opname
        selectedWarehouse: window.CURRENT_WAREHOUSE_ID || '1',

        // Riwayat & Tabel
        historyRows: [],
        searchHistory: '',
        isLoadingHistory: false,

        // Modal Dokumen Stok Opname
        showModal: false,
        searchKeyword: '',
        searchResults: [],
        opnameItems: [],
        batchNotes: '',
        isSavingBatch: false,

        async init() {
            await this.loadHistory();
        },

        onWarehouseChange() {
            if (this.opnameItems.length > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Ganti Outlet / Store?',
                        text: 'Daftar produk yang belum diposting akan dikosongkan agar sesuai dengan stok outlet baru.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Ganti & Kosongkan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.opnameItems = [];
                            this.scannedProduct = null;
                            this.searchResults = [];
                            this.loadHistory();
                        }
                    });
                } else {
                    this.opnameItems = [];
                    this.scannedProduct = null;
                    this.searchResults = [];
                    this.loadHistory();
                }
            } else {
                this.scannedProduct = null;
                this.searchResults = [];
                this.loadHistory();
            }
        },

        // 1. LOAD RIWAYAT PENYESUAIAN STOK
        async loadHistory() {
            this.isLoadingHistory = true;
            try {
                const response = await fetch(`logic.php?action=get_history&warehouse_id=${encodeURIComponent(this.selectedWarehouse)}&search=${encodeURIComponent(this.searchHistory)}`);
                const result = await response.json();
                if (result.status === 'success') {
                    this.historyRows = result.data || [];
                } else {
                    console.error("Gagal memuat riwayat:", result.message);
                }
            } catch (error) {
                console.error("Error loadHistory:", error);
            } finally {
                this.isLoadingHistory = false;
            }
        },

        // 2. MODAL DOKUMEN STOK OPNAME (BATCH)
        openModal() {
            this.showModal = true;
            this.opnameItems = [];
            this.batchNotes = '';
            this.searchKeyword = '';
            this.searchResults = [];
        },

        closeModal() {
            this.showModal = false;
            this.searchKeyword = '';
            this.searchResults = [];
        },

        async searchProductsModal() {
            if (!this.searchKeyword || this.searchKeyword.length < 2) {
                this.searchResults = [];
                return;
            }
            try {
                const response = await fetch(`logic.php?action=search_products&warehouse_id=${encodeURIComponent(this.selectedWarehouse)}&keyword=${encodeURIComponent(this.searchKeyword)}`);
                const result = await response.json();
                if (result.status === 'success') {
                    this.searchResults = result.data || [];
                }
            } catch (error) {
                console.error("Error searchProducts:", error);
            }
        },

        addItemToOpname(prod) {
            const exists = this.opnameItems.find(i => i.id === prod.id);
            if (exists) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'info',
                        title: 'Produk sudah ada di daftar!', showConfirmButton: false, timer: 1500
                    });
                }
            } else {
                this.opnameItems.push({
                    id: prod.id,
                    code: prod.code,
                    name: prod.name,
                    category: prod.category || '-',
                    system_stock: parseInt(prod.stock || 0),
                    actual_stock: parseInt(prod.stock || 0)
                });
            }
            this.searchKeyword = '';
            this.searchResults = [];
        },

        removeItem(index) {
            this.opnameItems.splice(index, 1);
        },

        async saveBatchOpname() {
            if (this.opnameItems.length === 0) {
                if (typeof Swal !== 'undefined') Swal.fire('Peringatan', 'Daftar bahan yang diopname masih kosong!', 'warning');
                return;
            }

            // CEGAT JIKA INTERNET MATI
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Koneksi terputus! Tidak dapat menyimpan data opname.', 'warning');
                return;
            }

            this.isSavingBatch = true;
            try {
                const payload = {
                    warehouse_id: this.selectedWarehouse,
                    items: this.opnameItems.map(item => ({
                        product_id: item.id,
                        system_stock: item.system_stock,
                        actual_stock: item.actual_stock
                    })),
                    notes: this.batchNotes
                };

                const response = await fetch('logic.php?action=save_opname_batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    this.closeModal();
                    await this.loadHistory();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Menyimpan', result.message, 'error');
                }
            } catch (error) {
                console.error("Error saveBatchOpname:", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyambung ke server.', 'error');
            } finally {
                this.isSavingBatch = false;
            }
        },

        // 3. MENCARI PRODUK BERDASARKAN BARCODE (SCANNER TUNGGAL)
        async searchBarcode(scannedCode = null) {
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Pencarian barcode ke server membutuhkan koneksi internet!', 'warning');
                return;
            }

            const codeToSearch = scannedCode || this.barcodeInput;
            if (!codeToSearch) return;

            try {
                const response = await fetch(`logic.php?action=scan_barcode&warehouse_id=${encodeURIComponent(this.selectedWarehouse)}&code=${encodeURIComponent(codeToSearch)}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.scannedProduct = result.data;
                    this.actualStock = parseInt(this.scannedProduct.stock || 0);
                    this.opnameNotes = '';
                    
                    this.playBeep();
                    if (this.isCameraOpen) this.toggleCamera();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Tidak Ditemukan', result.message, 'error');
                }
            } catch (error) {
                console.error("Error Scan:", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal memproses barcode. Pastikan koneksi server stabil.', 'error');
            } finally {
                this.barcodeInput = '';
            }
        },

        get selisih() {
            if (!this.scannedProduct) return 0;
            return parseInt(this.actualStock || 0) - parseInt(this.scannedProduct.stock || 0);
        },

        async saveOpname() {
            if (this.selisih === 0) return;
            
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Koneksi terputus! Tidak dapat menyimpan data opname.', 'warning');
                return;
            }

            this.isSaving = true;

            try {
                const fd = new FormData();
                fd.append('warehouse_id', this.selectedWarehouse);
                fd.append('product_id', this.scannedProduct.id);
                fd.append('system_stock', this.scannedProduct.stock);
                fd.append('actual_stock', this.actualStock);
                fd.append('notes', this.opnameNotes);

                const response = await fetch('logic.php?action=save_opname', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: 'Stok Berhasil Disesuaikan!',
                            showConfirmButton: false, timer: 1500,
                            customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                        });
                    }
                    this.resetScan();
                    await this.loadHistory();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Menyimpan', result.message, 'error');
                }
            } catch (error) {
                console.error("Error Save:", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyambung ke database.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        resetScan() {
            this.scannedProduct = null;
            this.actualStock = 0;
            this.opnameNotes = '';
        },

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
            
            this.html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 }, false);
            
            this.html5QrcodeScanner.render((decodedText, decodedResult) => {
                this.searchBarcode(decodedText);
            }, (error) => {});
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

        playBeep() {
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.type = "sine";
                oscillator.frequency.value = 800;
                gain.gain.value = 0.5;
                oscillator.start();
                setTimeout(() => { oscillator.stop(); }, 150);
            } catch (e) {}
        }
    }));
});