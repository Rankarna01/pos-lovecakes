document.addEventListener('alpine:init', () => {
    Alpine.data('produkApp', () => ({
        products: [],
        filteredProducts: [],
        searchQuery: '',
        activeCategory: 'Semua',
        isLoading: true, // Awalnya muter
        isSyncing: false,

        async init() {
            // ==========================================
            // 1. SMART GUARD (ANTI MEMBAL)
            // ==========================================
            if(window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                
                // JS HANYA NENDANG KALAU: Internet Mati DAN KTP Lokal Kosong
                if (!user && !navigator.onLine) { 
                    window.location.href = '../../auth/index.php'; 
                    return; 
                }
                // Kalau Online tapi KTP kosong? JS DIAM SAJA! Karena PHP sudah mengizinkan masuk.
            }
            
            // 2. WAJIB DIPANGGIL: Tarik data agar loading berhenti!
            await this.loadLocalData();
        },

        async loadLocalData() {
            this.isLoading = true; // Pastikan spinner nyala
            
            try {
                if (window.dbAuth) {
                    const localCatalog = await window.dbAuth.getItem('katalog_produk');
                    
                    if (localCatalog && localCatalog.length > 0) {
                        // Data lokal ketemu! Langsung tampilkan
                        this.products = localCatalog;
                        this.filteredProducts = localCatalog;
                    } else {
                        // Data lokal kosong
                        if (!navigator.onLine) {
                            // Kalau offline, kasih tau user
                            if (typeof Swal !== 'undefined') Swal.fire('Offline Mode', 'Belum ada data produk tersimpan di perangkat ini. Harap online untuk sinkronisasi.', 'info');
                        } else {
                            // Kalau online, tarik dari MySQL diam-diam
                            await this.syncDataFromPusat(false); 
                        }
                    }
                }
            } catch (error) {
                console.error('Error load local:', error);
            } finally {
                // ==========================================
                // KUNCI ANTI MUTER TERUS: Wajib diset false!
                // ==========================================
                this.isLoading = false; 
            }
        },

        async syncDataFromPusat(showAlert = true) {
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Koneksi internet terputus!', 'warning');
                return;
            }

            this.isSyncing = true;
            try {
                if(window.dbAuth) { await window.dbAuth.removeItem('katalog_produk'); }

                const timestamp = new Date().getTime();
                const response = await fetch(`logic.php?action=read_produk&nocache=${timestamp}`);
                const result = await response.json();

                if (result.status === 'success') {
                    // Bypass Proxy DataCloneError
                    const dataProduk = JSON.parse(JSON.stringify(result.data || []));
                    
                    if(window.dbAuth) { await window.dbAuth.setItem('katalog_produk', dataProduk); }
                    
                    this.products = dataProduk;
                    this.filterProducts(); 

                    if (showAlert && typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: `Sukses sinkronisasi ${result.total || dataProduk.length} Produk!`,
                            showConfirmButton: false, timer: 1500,
                            customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                }
            } catch (error) {
                console.error('Error DB:', error);
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
                temp = temp.filter(p => 
                    (p.name && p.name.toLowerCase().includes(q)) || 
                    (p.code && p.code.toLowerCase().includes(q))
                );
            }
            this.filteredProducts = temp;
        },

        printBarcode(product) {
            if (!product.code) {
                if (typeof Swal !== 'undefined') Swal.fire('Gagal', 'Produk ini belum memiliki Kode SKU.', 'error');
                return;
            }

            const printWindow = window.open('', '_blank', 'width=350,height=300');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cetak Barcode - ${product.name}</title>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
                    <style>
                        @page { margin: 0; size: 50mm 30mm; } 
                        body { 
                            margin: 0; padding: 5px; text-align: center; font-family: sans-serif; 
                            background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh;
                        }
                        .product-name { font-size: 11px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }
                        .price { font-size: 10px; font-weight: bold; }
                        svg { max-width: 100%; height: auto; }
                    </style>
                </head>
                <body>
                    <div class="product-name">${product.name}</div>
                    <svg id="barcode"></svg>
                    <div class="price">Rp ${this.formatRupiah(product.offline_price || product.price || 0)}</div>
                    <script>
                        JsBarcode("#barcode", "${product.code}", { format: "CODE128", width: 1.5, height: 40, displayValue: true, fontSize: 12 });
                        setTimeout(() => { window.print(); window.close(); }, 500);
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