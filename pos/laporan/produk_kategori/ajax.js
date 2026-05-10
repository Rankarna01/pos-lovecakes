document.addEventListener('alpine:init', () => {
    Alpine.data('analisaProdukApp', () => ({
        isLoading: false,
        
        // Filter Tanggal Default: Awal bulan s/d Hari ini
        filters: {
            start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0]
        },

        // State Data
        bestSellers: [],
        worstSellers: [],
        categories: [],
        totalRevenue: 0,

        // Palet warna Tailwind untuk Chart/Bar
        colorPalettes: [
            { bg: 'bg-blue-500' },
            { bg: 'bg-emerald-500' },
            { bg: 'bg-amber-500' },
            { bg: 'bg-purple-500' },
            { bg: 'bg-rose-500' },
            { bg: 'bg-cyan-500' },
            { bg: 'bg-indigo-500' },
            { bg: 'bg-fuchsia-500' }
        ],

        async init() {
            // ❌ CEK SESI dbAuth DIHAPUS TOTAL!
            // Keamanan sudah diamankan 100% oleh config/auth.php di server.
            
            await this.fetchData();
        },

        async fetchData() {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Halaman Analisa Produk membutuhkan koneksi internet!', 'warning');
                } else {
                    alert('Anda sedang offline! Halaman ini membutuhkan koneksi internet.');
                }
                this.isLoading = false;
                return;
            }

            this.isLoading = true;
            try {
                const params = new URLSearchParams(this.filters);
                params.append('action', 'get_analysis');
                params.append('nocache', Date.now());

                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.bestSellers = result.data.best_sellers || [];
                    this.worstSellers = result.data.worst_sellers || [];
                    this.categories = result.data.categories || [];
                    this.totalRevenue = parseFloat(result.data.total_revenue) || 0;
                } else {
                    console.error("Server Error:", result.message);
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', 'Gagal menarik data analisa.', 'error');
                    else alert("Gagal menarik data analisa.");
                }
            } catch (error) {
                console.error("Gagal Request API", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error Database', 'Gagal terhubung ke server pusat.', 'error');
            } finally {
                // WAJIB: Pastikan spinner selalu mati apapun yang terjadi
                this.isLoading = false;
            }
        },

        getPercentage(revenue) {
            if (this.totalRevenue <= 0) return 0;
            let val = (parseFloat(revenue) / this.totalRevenue) * 100;
            return val.toFixed(1); // Ambil 1 angka di belakang koma
        },

        formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(parseFloat(angka) || 0);
        }
    }));
});