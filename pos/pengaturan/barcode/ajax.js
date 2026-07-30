document.addEventListener('alpine:init', () => {
    Alpine.data('barcodeSettingsApp', () => ({
        isLoading: true,
        isSaving: false,

        settings: {
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

        paperSizes: [
            { value: '40x30',  label: '40 × 30mm', desc: 'Thermal Mini' },
            { value: '50x30',  label: '50 × 30mm', desc: 'Thermal Standar' },
            { value: '58x40',  label: '58 × 40mm', desc: 'Thermal Lebar' },
            { value: '80x50',  label: '80 × 50mm', desc: 'Label Besar' },
            { value: 'a4',     label: 'A4 Grid',   desc: 'Multi per lembar' },
            { value: 'custom', label: 'Custom',    desc: 'Ukuran bebas' },
        ],

        async init() {
            await this.loadSettings();
        },

        async loadSettings() {
            this.isLoading = true;
            try {
                const res = await fetch('logic.php?action=get');
                const result = await res.json();
                if (result.status === 'success') {
                    this.settings = { ...this.settings, ...result.data };
                    this.settings.barcode_height = parseInt(this.settings.barcode_height) || 30;
                    this.settings.barcode_width  = parseFloat(this.settings.barcode_width) || 1;
                    this.settings.barcode_per_row = parseInt(this.settings.barcode_per_row) || 3;
                    this.settings.barcode_paper_custom_w = parseInt(this.settings.barcode_paper_custom_w) || 40;
                    this.settings.barcode_paper_custom_h = parseInt(this.settings.barcode_paper_custom_h) || 30;
                }
            } catch(e) {
                console.error('Gagal memuat pengaturan barcode:', e);
            } finally {
                this.isLoading = false;
                this.$nextTick(() => { this.renderPreview(); });
            }
        },

        renderPreview() {
            try {
                const svgEl = document.getElementById('preview-barcode');
                if (!svgEl || typeof JsBarcode === 'undefined') return;

                const sampleCode = 'SKU-001234';
                const format = this.settings.barcode_format || 'CODE128';

                JsBarcode(svgEl, sampleCode, {
                    format:       format,
                    width:        parseFloat(this.settings.barcode_width) || 1,
                    height:       parseInt(this.settings.barcode_height) || 30,
                    displayValue: this.settings.barcode_show_sku == '1',
                    fontSize:     10,
                    margin:       2,
                    background:   '#ffffff',
                    lineColor:    '#000000',
                });

                // Toggle Nama Atas
                const nameTop = document.getElementById('preview-name-top');
                const nameBot = document.getElementById('preview-name-bottom');
                if (this.settings.barcode_show_name == '1') {
                    if (this.settings.barcode_name_position === 'top') {
                        nameTop.style.display = 'block';
                        nameBot.style.display = 'none';
                    } else {
                        nameTop.style.display = 'none';
                        nameBot.style.display = 'block';
                    }
                } else {
                    nameTop.style.display = 'none';
                    nameBot.style.display = 'none';
                }

                // Toggle Harga
                const price = document.getElementById('preview-price');
                price.style.display = this.settings.barcode_show_price == '1' ? 'block' : 'none';

                // Toggle Expired
                const expired = document.getElementById('preview-expired');
                expired.style.display = this.settings.barcode_show_expired == '1' ? 'block' : 'none';

                // Toggle Kategori
                const cat = document.getElementById('preview-category');
                cat.style.display = this.settings.barcode_show_category == '1' ? 'block' : 'none';

            } catch(err) {
                console.warn('Preview barcode gagal (format tidak support?):', err.message);
                const svgEl = document.getElementById('preview-barcode');
                if (svgEl) svgEl.innerHTML = '<text x="0" y="15" fill="red" font-size="10">Format tidak valid untuk SKU ini</text>';
            }
        },

        getActivePaperLabel() {
            const found = this.paperSizes.find(s => s.value === this.settings.barcode_paper_size);
            if (found) {
                if (found.value === 'custom') {
                    return `Custom ${this.settings.barcode_paper_custom_w}×${this.settings.barcode_paper_custom_h}mm`;
                }
                return `${found.label} (${found.desc})`;
            }
            return this.settings.barcode_paper_size;
        },

        async saveSettings() {
            this.isSaving = true;
            try {
                const res = await fetch('logic.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.settings)
                });
                const result = await res.json();

                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Gagal menyimpan pengaturan.', 'error');
            } finally {
                this.isSaving = false;
            }
        }
    }));
});
