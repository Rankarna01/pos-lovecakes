document.addEventListener('alpine:init', () => {
    Alpine.data('analisaProdukApp', () => ({
        isLoading: false,
        
        // Filter Tanggal Default: Awal bulan s/d Hari ini
        filters: {
            start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0],
            limit: 10
        },

        // State Data
        bestSellers: [],
        worstSellers: [],
        categories: [],
        totalRevenue: 0,
        totalQty: 0,
        topCategory: '-',
        topProduct: '-',
        maxBestQty: 0,
        maxWorstQty: 0,

        // Palet warna Tailwind untuk Chart/Bar
        colorPalettes: [
            { bg: 'bg-blue-500', text: 'text-blue-600', border: 'border-blue-200', light: 'bg-blue-50' },
            { bg: 'bg-emerald-500', text: 'text-emerald-600', border: 'border-emerald-200', light: 'bg-emerald-50' },
            { bg: 'bg-amber-500', text: 'text-amber-600', border: 'border-amber-200', light: 'bg-amber-50' },
            { bg: 'bg-purple-500', text: 'text-purple-600', border: 'border-purple-200', light: 'bg-purple-50' },
            { bg: 'bg-rose-500', text: 'text-rose-600', border: 'border-rose-200', light: 'bg-rose-50' },
            { bg: 'bg-cyan-500', text: 'text-cyan-600', border: 'border-cyan-200', light: 'bg-cyan-50' },
            { bg: 'bg-indigo-500', text: 'text-indigo-600', border: 'border-indigo-200', light: 'bg-indigo-50' },
            { bg: 'bg-fuchsia-500', text: 'text-fuchsia-600', border: 'border-fuchsia-200', light: 'bg-fuchsia-50' }
        ],

        async init() {
            this.$watch('filters.limit', () => { this.fetchData(); });
            await this.fetchData();
        },

        async fetchData() {
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
                    this.totalQty = parseInt(result.data.total_qty) || 0;
                    this.topCategory = result.data.top_category || '-';
                    this.topProduct = result.data.top_product || '-';
                    this.maxBestQty = parseInt(result.data.max_best_qty) || 0;
                    this.maxWorstQty = parseInt(result.data.max_worst_qty) || 0;
                } else {
                    console.error("Server Error:", result.message);
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', 'Gagal menarik data analisa.', 'error');
                    else alert("Gagal menarik data analisa.");
                }
            } catch (error) {
                console.error("Gagal Request API", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error Database', 'Gagal terhubung ke server pusat.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        getPercentage(revenue) {
            if (this.totalRevenue <= 0) return 0;
            let val = (parseFloat(revenue) / this.totalRevenue) * 100;
            return val.toFixed(1);
        },

        getBarWidth(qty, maxQty) {
            if (!maxQty || maxQty <= 0) return 10;
            let val = (parseInt(qty) / maxQty) * 100;
            return Math.max(val, 8); // min 8% agar teks/bar tetap bagus
        },

        formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(parseFloat(angka) || 0);
        },

        printPDF() {
            let html = `
            <html>
            <head>
                <title>Laporan Analisa Produk & Kategori - Love Cakes POS</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 25px; color: #333; }
                    h2 { color: #2563eb; margin-bottom: 5px; font-size: 20px; }
                    p { color: #666; font-size: 13px; margin-top: 0; margin-bottom: 20px; }
                    .kpi-box { display: flex; gap: 15px; margin-bottom: 25px; }
                    .kpi { flex: 1; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
                    .kpi-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold; }
                    .kpi-val { font-size: 18px; color: #0f172a; font-weight: bold; margin-top: 5px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 30px; }
                    th, td { border: 1px solid #e2e8f0; padding: 10px 14px; text-align: left; font-size: 13px; }
                    th { background-color: #f8fafc; color: #334155; font-weight: bold; text-transform: uppercase; font-size: 11px; }
                    tr:nth-child(even) { background-color: #f8fafc; }
                    .footer { margin-top: 30px; font-size: 11px; color: #94a3b8; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 10px; }
                </style>
            </head>
            <body>
                <h2>Laporan Analisa Produk & Kategori</h2>
                <p>Periode: ${this.filters.start_date} s/d ${this.filters.end_date} | Top ${this.filters.limit} Produk</p>
                
                <div class="kpi-box">
                    <div class="kpi"><div class="kpi-label">Total Omset</div><div class="kpi-val">Rp ${this.formatRupiah(this.totalRevenue)}</div></div>
                    <div class="kpi"><div class="kpi-label">Total Item Terjual</div><div class="kpi-val">${this.totalQty} Pcs</div></div>
                    <div class="kpi"><div class="kpi-label">Kategori Terlaris</div><div class="kpi-val">${this.topCategory}</div></div>
                    <div class="kpi"><div class="kpi-label">Produk Juara #1</div><div class="kpi-val">${this.topProduct}</div></div>
                </div>

                <h3>Top ${this.filters.limit} Produk Paling Laku</h3>
                <table>
                    <thead><tr><th>Peringkat</th><th>Nama Produk</th><th>Terjual (QTY)</th><th>Total Omset</th></tr></thead>
                    <tbody>
                        ${this.bestSellers.map((item, idx) => `<tr><td>#${idx + 1}</td><td>${item.product_name}</td><td>${item.total_qty} Pcs</td><td>Rp ${this.formatRupiah(item.total_revenue || 0)}</td></tr>`).join('')}
                    </tbody>
                </table>

                <h3>Top ${this.filters.limit} Produk Kurang Laku</h3>
                <table>
                    <thead><tr><th>Peringkat</th><th>Nama Produk</th><th>Terjual (QTY)</th><th>Total Omset</th></tr></thead>
                    <tbody>
                        ${this.worstSellers.map((item, idx) => `<tr><td>#${idx + 1}</td><td>${item.product_name}</td><td>${item.total_qty} Pcs</td><td>Rp ${this.formatRupiah(item.total_revenue || 0)}</td></tr>`).join('')}
                    </tbody>
                </table>

                <h3>Analisa Kategori Produk</h3>
                <table>
                    <thead><tr><th>Kategori</th><th>Item Terjual</th><th>Total Omset</th><th>Kontribusi (%)</th></tr></thead>
                    <tbody>
                        ${this.categories.map(cat => `<tr><td>${cat.category_name}</td><td>${cat.total_qty} Pcs</td><td>Rp ${this.formatRupiah(cat.total_revenue)}</td><td>${this.getPercentage(cat.total_revenue)}%</td></tr>`).join('')}
                    </tbody>
                </table>

                <div class="footer">Dicetak otomatis dari Love Cakes POS pada ${new Date().toLocaleString('id-ID')}</div>
                <script>window.onload = function() { window.print(); }</script>
            </body>
            </html>`;
            
            let win = window.open('', '_blank', 'width=900,height=700');
            win.document.write(html);
            win.document.close();
        },

        exportCSV() {
            let csv = 'Laporan Analisa Produk & Kategori\n';
            csv += `Periode,${this.filters.start_date} s/d ${this.filters.end_date}\n\n`;
            
            csv += 'RINGKASAN KPI\n';
            csv += `Total Omset,Rp ${this.formatRupiah(this.totalRevenue)}\n`;
            csv += `Total Item Terjual,${this.totalQty} Pcs\n`;
            csv += `Kategori Terlaris,${this.topCategory}\n`;
            csv += `Produk Juara #1,${this.topProduct}\n\n`;

            csv += `TOP ${this.filters.limit} PRODUK PALING LAKU\n`;
            csv += 'Peringkat,Nama Produk,Terjual (QTY),Total Omset\n';
            this.bestSellers.forEach((item, idx) => {
                csv += `"${idx + 1}","${item.product_name}",${item.total_qty},"Rp ${this.formatRupiah(item.total_revenue || 0)}"\n`;
            });
            csv += '\n';

            csv += `TOP ${this.filters.limit} PRODUK KURANG LAKU\n`;
            csv += 'Peringkat,Nama Produk,Terjual (QTY),Total Omset\n';
            this.worstSellers.forEach((item, idx) => {
                csv += `"${idx + 1}","${item.product_name}",${item.total_qty},"Rp ${this.formatRupiah(item.total_revenue || 0)}"\n`;
            });
            csv += '\n';

            csv += 'ANALISA KATEGORI PRODUK\n';
            csv += 'Kategori,Item Terjual,Total Omset,Kontribusi (%)\n';
            this.categories.forEach(cat => {
                csv += `"${cat.category_name}",${cat.total_qty},"Rp ${this.formatRupiah(cat.total_revenue)}","${this.getPercentage(cat.total_revenue)}%"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `Analisa_Produk_${this.filters.start_date}_${this.filters.end_date}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }));
});