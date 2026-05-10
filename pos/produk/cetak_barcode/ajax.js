// pos/cetak_barcode/ajax.js
document.addEventListener('alpine:init', () => {
    Alpine.data('barcodeApp', () => ({
        products: [],
        filteredProducts: [],
        searchQuery: '',
        printQueue: [],
        isLoading: false,

        async init() {
            // Watcher Pencarian
            this.$watch('searchQuery', (val) => {
                if (val.trim() === '') {
                    this.filteredProducts = this.products;
                } else {
                    const q = val.toLowerCase();
                    this.filteredProducts = this.products.filter(p => 
                        p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q)
                    );
                }
            });

            await this.fetchProducts();
        },

        async fetchProducts() {
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Koneksi terputus!', 'warning');
                return;
            }

            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=get_products&nocache=${Date.now()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.products = result.data;
                    this.filteredProducts = result.data;
                }
            } catch (error) {
                console.error(error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menarik data produk.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        addToQueue(product) {
            // Cek apakah sudah ada di keranjang
            const existing = this.printQueue.find(item => item.id === product.id);
            if (existing) {
                existing.printQty++; // Tambah jumlah stikernya
            } else {
                // Masukkan produk baru, default 1 stiker
                this.printQueue.push({ ...product, printQty: 1 });
            }
            
            // Efek suara beep kecil (opsional)
            this.playBeep();
        },

        removeFromQueue(index) {
            this.printQueue.splice(index, 1);
        },

        get totalStickers() {
            return this.printQueue.reduce((total, item) => total + (parseInt(item.printQty) || 0), 0);
        },

        // --- ALGORITMA UTAMA GENERATE BARCODE MASSAL ---
        generateBulkPrint() {
            if (this.printQueue.length === 0) return;

            // Buka Window Baru
            const printWindow = window.open('', '_blank', 'width=400,height=600');
            
            // Susun HTML-nya
            let htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cetak Barcode Massal</title>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
                    <style>
                        /* Ukuran stiker Thermal standar (Misal: 40mm x 30mm) */
                        @page { margin: 0; size: 40mm 30mm; } 
                        body { 
                            margin: 0; padding: 0; 
                            background: #fff; color: #000;
                            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                        }
                        .sticker-page {
                            width: 40mm; height: 30mm;
                            display: flex; flex-direction: column; align-items: center; justify-content: center;
                            page-break-after: always; /* PENTING: Memotong setiap stiker */
                            box-sizing: border-box; padding: 2px;
                            text-align: center;
                        }
                        .p-name { font-size: 10px; font-weight: bold; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; margin-bottom: 2px; }
                        .p-price { font-size: 9px; font-weight: bold; margin-top: 0px; }
                        svg { max-width: 100%; height: 18mm; display: block; }
                    </style>
                </head>
                <body>
            `;

            // Looping produk di keranjang
            this.printQueue.forEach(item => {
                // Looping sejumlah 'printQty' yang diminta
                let qty = parseInt(item.printQty) || 1;
                for (let i = 0; i < qty; i++) {
                    let formattedPrice = new Intl.NumberFormat('id-ID').format(item.price);
                    
                    htmlContent += `
                        <div class="sticker-page">
                            <div class="p-name">${item.name}</div>
                            <svg class="barcode-item" data-code="${item.code}"></svg>
                            <div class="p-price">Rp ${formattedPrice}</div>
                        </div>
                    `;
                }
            });

            htmlContent += `
                    <script>
                        // Setelah HTML ter-load, jalankan library JsBarcode untuk semua elemen SVG
                        document.addEventListener("DOMContentLoaded", function() {
                            const barcodes = document.querySelectorAll('.barcode-item');
                            
                            barcodes.forEach(svg => {
                                const code = svg.getAttribute('data-code');
                                JsBarcode(svg, code, {
                                    format: "CODE128",
                                    width: 1.5,
                                    height: 35,
                                    displayValue: true,
                                    fontSize: 10,
                                    margin: 1
                                });
                            });

                            // Beri waktu 0.5 detik agar render selesai, lalu panggil Print
                            setTimeout(() => {
                                window.print();
                                // window.close(); // Hapus komentar ini jika ingin tab langsung tertutup
                            }, 500);
                        });
                    </script>
                </body>
                </html>
            `;

            // Tulis dan render ke tab baru
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
            // Kosongkan keranjang setelah dikirim ke printer (opsional)
            // this.printQueue = []; 
        },

        playBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                osc.type = 'sine';
                osc.frequency.value = 1200; // Nada tinggi
                osc.connect(ctx.destination);
                osc.start();
                setTimeout(() => { osc.stop(); }, 100);
            } catch(e) {}
        }
    }));
});