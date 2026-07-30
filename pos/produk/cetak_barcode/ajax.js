// pos/cetak_barcode/ajax.js
document.addEventListener('alpine:init', () => {
    Alpine.data('barcodeApp', () => ({
        products: [],
        filteredProducts: [],
        searchQuery: '',
        printQueue: [],
        isLoading: false,
        barcodeSettings: {
            barcode_format:         'CODE128',
            barcode_height:         30,
            barcode_width:          1,
            barcode_paper_size:     '40x30',
            barcode_paper_custom_w: 40,
            barcode_paper_custom_h: 30,
            barcode_per_row:        3,
            barcode_show_name:      '1',
            barcode_name_position:  'bottom',
            barcode_show_sku:       '1',
            barcode_show_price:     '1',
            barcode_show_expired:   '0',
            barcode_show_category:  '0',
        },

        async init() {
            this.$watch('searchQuery', (val) => {
                if (val.trim() === '') {
                    this.filteredProducts = this.products;
                } else {
                    const q = val.toLowerCase();
                    this.filteredProducts = this.products.filter(p => 
                        p.name.toLowerCase().includes(q) || (p.code && p.code.toLowerCase().includes(q))
                    );
                }
            });

            await Promise.all([
                this.fetchProducts(),
                this.loadBarcodeSettings()
            ]);
        },

        async loadBarcodeSettings() {
            try {
                const res = await fetch('../../pengaturan/barcode/logic.php?action=get_public');
                const result = await res.json();
                if (result.status === 'success') {
                    this.barcodeSettings = { ...this.barcodeSettings, ...result.data };
                    this.barcodeSettings.barcode_height   = parseInt(this.barcodeSettings.barcode_height) || 30;
                    this.barcodeSettings.barcode_width    = parseFloat(this.barcodeSettings.barcode_width) || 1;
                    this.barcodeSettings.barcode_per_row  = parseInt(this.barcodeSettings.barcode_per_row) || 3;
                }
            } catch(e) {
                console.warn('Menggunakan setting barcode default:', e);
            }
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
            const existing = this.printQueue.find(item => item.id === product.id);
            if (existing) {
                existing.printQty++;
            } else {
                this.printQueue.push({ ...product, printQty: 1 });
            }
            this.playBeep();
        },

        removeFromQueue(index) {
            this.printQueue.splice(index, 1);
        },

        get totalStickers() {
            return this.printQueue.reduce((total, item) => total + (parseInt(item.printQty) || 0), 0);
        },

        getPaperDimensions() {
            const s = this.barcodeSettings;
            const sizeMap = {
                '40x30': { w: '40mm', h: '30mm' },
                '50x30': { w: '50mm', h: '30mm' },
                '58x40': { w: '58mm', h: '40mm' },
                '80x50': { w: '80mm', h: '50mm' },
                'a4':    { w: '210mm', h: '297mm' },
                'custom':{ w: `${s.barcode_paper_custom_w}mm`, h: `${s.barcode_paper_custom_h}mm` },
            };
            return sizeMap[s.barcode_paper_size] || sizeMap['40x30'];
        },

        // =====================================================
        // GENERATE CETAK DINAMIS DARI SETTINGS
        // =====================================================
        generateBulkPrint() {
            if (this.printQueue.length === 0) return;

            const cfg  = this.barcodeSettings;
            const dim  = this.getPaperDimensions();
            const isA4 = cfg.barcode_paper_size === 'a4';
            const perRow = parseInt(cfg.barcode_per_row) || 3;

            const stickerStyles = isA4
                ? `
                    @page { margin: 5mm; size: A4; }
                    .sticker-grid { display: grid; grid-template-columns: repeat(${perRow}, 1fr); gap: 3mm; }
                    .sticker-page { border: 0.5pt dashed #ccc; border-radius: 2mm; padding: 2mm; text-align: center; break-inside: avoid; }
                  `
                : `
                    @page { margin: 0; size: ${dim.w} ${dim.h}; }
                    .sticker-grid { display: block; }
                    .sticker-page {
                        width: ${dim.w}; height: ${dim.h};
                        display: flex; flex-direction: column; align-items: center; justify-content: center;
                        page-break-after: always; box-sizing: border-box; padding: 2px; text-align: center;
                    }
                  `;

            const printWindow = window.open('', '_blank', 'width=500,height=700');

            let stickerItems = '';
            this.printQueue.forEach(item => {
                const qty = parseInt(item.printQty) || 1;
                const formattedPrice = new Intl.NumberFormat('id-ID').format(item.price || 0);
                const expDate = item.expired_date ? item.expired_date : '01/08/2025';

                for (let i = 0; i < qty; i++) {
                    const showNameTop    = cfg.barcode_show_name == '1' && cfg.barcode_name_position === 'top';
                    const showNameBottom = cfg.barcode_show_name == '1' && cfg.barcode_name_position !== 'top';
                    const showPrice      = cfg.barcode_show_price == '1';
                    const showExpired    = cfg.barcode_show_expired == '1';
                    const showCategory   = cfg.barcode_show_category == '1';

                    stickerItems += `
                        <div class="sticker-page">
                            ${showCategory ? `<div class="p-cat">${item.category || 'Produk'}</div>` : ''}
                            ${showNameTop  ? `<div class="p-name">${item.name}</div>` : ''}
                            <svg class="barcode-item" data-code="${item.code || item.id}" data-show-sku="${cfg.barcode_show_sku}"></svg>
                            ${showNameBottom ? `<div class="p-name">${item.name}</div>` : ''}
                            ${showPrice     ? `<div class="p-price">Rp ${formattedPrice}</div>` : ''}
                            ${showExpired   ? `<div class="p-exp">EXP: ${expDate}</div>` : ''}
                        </div>
                    `;
                }
            });

            const htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cetak Barcode - Love Cakes</title>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                    <style>
                        ${stickerStyles}
                        body { margin: 0; padding: 0; background: #fff; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
                        .p-name { font-size: 8px; font-weight: bold; text-transform: uppercase; overflow: hidden; text-overflow: ellipsis; max-width: 100%; margin: 1px 0; white-space: nowrap; }
                        .p-price { font-size: 9px; font-weight: bold; margin-top: 1px; }
                        .p-exp { font-size: 8px; font-weight: bold; color: #cc0000; margin-top: 1px; }
                        .p-cat { font-size: 7px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
                        svg { display: block; margin: 0 auto; }
                    </style>
                </head>
                <body>
                    <div class="sticker-grid">
                        ${stickerItems}
                    </div>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const barcodes = document.querySelectorAll('.barcode-item');
                            barcodes.forEach(function(svg) {
                                const code     = svg.getAttribute('data-code');
                                const showSku  = svg.getAttribute('data-show-sku') === '1';
                                try {
                                    JsBarcode(svg, code, {
                                        format:       "${cfg.barcode_format}",
                                        width:        ${parseFloat(cfg.barcode_width) || 1},
                                        height:       ${parseInt(cfg.barcode_height) || 30},
                                        displayValue: showSku,
                                        fontSize:     9,
                                        margin:       2,
                                        background:   '#ffffff',
                                        lineColor:    '#000000'
                                    });
                                } catch(e) {
                                    svg.outerHTML = '<div style="font-size:8px;color:red;">SKU Invalid</div>';
                                }
                            });
                            setTimeout(function() { window.print(); }, 600);
                        });
                    <\/script>
                </body>
                </html>
            `;

            printWindow.document.write(htmlContent);
            printWindow.document.close();
        },

        playBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                osc.type = 'sine';
                osc.frequency.value = 1200;
                osc.connect(ctx.destination);
                osc.start();
                setTimeout(() => { osc.stop(); }, 100);
            } catch(e) {}
        }
    }));
});