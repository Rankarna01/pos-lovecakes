document.addEventListener('alpine:init', () => {
    Alpine.data('produkApp', () => ({
        products: [],
        filteredProducts: [],
        searchQuery: '',
        activeCategory: 'Semua',
        isLoading: true,
        isSyncing: false,

        async init() {
            if(window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../auth/index.php'; return; }
            }
            await this.loadLocalData();
        },

        async loadLocalData() {
            this.isLoading = true;
            if (window.dbAuth) {
                const localCatalog = await window.dbAuth.getItem('katalog_produk');
                if (localCatalog && localCatalog.length > 0) {
                    this.products = localCatalog;
                    this.filteredProducts = localCatalog;
                } else {
                    await this.syncDataFromPusat();
                }
            }
            this.isLoading = false;
        },

        async syncDataFromPusat() {
            this.isSyncing = true;
            try {
                if(window.dbAuth) { await window.dbAuth.removeItem('katalog_produk'); }

                const timestamp = new Date().getTime();
                const response = await fetch(`logic.php?action=read_produk&nocache=${timestamp}`);
                const result = await response.json();

                if (result.status === 'success') {
                    // Pakai JSON.parse(JSON.stringify) agar tidak kena error Proxy DataCloneError
                    const dataProduk = JSON.parse(JSON.stringify(result.data));
                    
                    if(window.dbAuth) { await window.dbAuth.setItem('katalog_produk', dataProduk); }
                    
                    this.products = dataProduk;
                    this.filterProducts(); 

                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: `Sukses muat ${result.total} Produk Baru!`,
                        showConfirmButton: false, timer: 1500,
                        customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                    });
                } else {
                    Swal.fire('Gagal Muat Data', result.message, 'error');
                }
            } catch (error) {
                console.error('Error Muat Database:', error);
                Swal.fire('Error Database', 'Gagal membaca database. Cek Console.', 'error');
            } finally {
                this.isSyncing = false;
            }
        },

        setCategory(cat) {
            this.activeCategory = cat;
            this.filterProducts();
        },

        filterProducts() {
            let temp = this.products;
            if (this.activeCategory !== 'Semua') {
                temp = temp.filter(p => p.category === this.activeCategory);
            }
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                temp = temp.filter(p => p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q));
            }
            this.filteredProducts = temp;
        },

        // --- FITUR BARU: GENERATE & CETAK BARCODE (STIKER THERMAL) ---
        printBarcode(product) {
            if (!product.code) {
                Swal.fire('Gagal', 'Produk ini belum memiliki Kode SKU.', 'error');
                return;
            }

            // Buka window popup kecil seukuran stiker printer thermal
            const printWindow = window.open('', '_blank', 'width=350,height=300');
            
            // Masukkan HTML, CSS, dan Library JsBarcode ke dalam window tersebut
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cetak Barcode - ${product.name}</title>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
                    <style>
                        @page { margin: 0; size: 50mm 30mm; } /* Ukuran standar stiker barcode */
                        body { 
                            margin: 0; padding: 5px; 
                            text-align: center; 
                            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
                            background: #fff; color: #000;
                            display: flex; flex-direction: column; align-items: center; justify-content: center;
                            height: 100vh; box-sizing: border-box;
                        }
                        .product-name { font-size: 11px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
                        .price { font-size: 10px; font-weight: bold; margin-top: 0px; }
                        svg { max-width: 100%; height: auto; }
                    </style>
                </head>
                <body>
                    <div class="product-name">${product.name}</div>
                    <svg id="barcode"></svg>
                    <div class="price">Rp ${this.formatRupiah(product.offline_price || product.price)}</div>

                    <script>
                        // Generate Barcode
                        JsBarcode("#barcode", "${product.code}", {
                            format: "CODE128",
                            width: 1.5,
                            height: 40,
                            displayValue: true,
                            fontSize: 12,
                            margin: 2
                        });
                        
                        // Otomatis muncul dialog print setelah 0.5 detik, lalu tutup window jika sudah selesai
                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 500);
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
        },

        formatRupiah(angka) {
            const val = parseFloat(angka);
            if (isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        }
    }));
});