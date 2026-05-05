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
                if (!user) {
                    window.location.href = '../../auth/index.php';
                    return;
                }
            }
            
            // Muat data lokal saat baru buka halaman
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

        // FUNGSI SYNC YANG SUDAH DIBIKIN ANTI-CACHE (MAMPU MEMBACA UPDATE TERBARU DB)
        async syncDataFromPusat() {
            this.isSyncing = true;
            try {
                // 1. HAPUS PAKSA CACHE LAMA DI BROWSER
                if(window.dbAuth) {
                    await window.dbAuth.removeItem('katalog_produk');
                }

                // 2. TEMBAK API DENGAN PARAMETER WAKTU AGAR BROWSER TIDAK MENGGUNAKAN CACHE
                const timestamp = new Date().getTime();
                const response = await fetch(`logic.php?action=read_produk&nocache=${timestamp}`);
                const result = await response.json();

                if (result.status === 'success') {
                    const dataProduk = result.data;
                    
                    // 3. SIMPAN DATA BARU (YANG SUDAH ADA HARGA ONLINENYA)
                    if(window.dbAuth) {
                        await window.dbAuth.setItem('katalog_produk', dataProduk);
                    }
                    
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
                temp = temp.filter(p => 
                    p.name.toLowerCase().includes(q) || 
                    p.code.toLowerCase().includes(q)
                );
            }

            this.filteredProducts = temp;
        },

        formatRupiah(angka) {
            // Memastikan angka yang diformat adalah number yang valid
            const val = parseFloat(angka);
            if (isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        }
    }));
});