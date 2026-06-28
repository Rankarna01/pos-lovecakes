document.addEventListener('alpine:init', () => {
    Alpine.data('promoItemsApp', () => ({
        promos: [],
        products: [],
        searchQuery: '',
        isLoading: false,
        
        showModal: false,
        isEdit: false,
        form: {
            id: '', name: '', buy_product_id: '', buy_qty: 1,
            get_product_id: '', get_qty: 1, start_date: '', end_date: '', is_active: true
        },

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user && !navigator.onLine) {
                    window.location.href = '../../../auth/index.php';
                    return;
                }
            }
            await this.fetchProducts();
            await this.fetchData();
        },

        async fetchProducts() {
            try {
                const res = await fetch(`logic.php?action=get_products&nocache=${new Date().getTime()}`);
                const result = await res.json();
                if (result.status === 'success') {
                    this.products = result.data;
                }
            } catch(e) { console.error("Gagal load produk:", e); }
        },

        async fetchData() {
            if (!navigator.onLine) {
                this.isLoading = false;
                return;
            }
            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=read&nocache=${new Date().getTime()}`);
                const result = await response.json();
                if (result.status === 'success') {
                    this.promos = result.data;
                } else {
                    window.alert('Gagal mengambil data promo: ' + result.message);
                }
            } catch (error) {
                console.error('Error fetching data:', error);
            } finally {
                this.isLoading = false;
            }
        },

        get filteredData() {
            if (!this.searchQuery) return this.promos;
            const query = this.searchQuery.toLowerCase();
            return this.promos.filter(item => 
                item.name.toLowerCase().includes(query) || 
                (item.buy_product_name && item.buy_product_name.toLowerCase().includes(query)) ||
                (item.get_product_name && item.get_product_name.toLowerCase().includes(query))
            );
        },

        formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(parseFloat(angka) || 0);
        },

        openModal() {
            this.isEdit = false;
            this.form = {
                id: '', name: '', buy_product_id: '', buy_qty: 1,
                get_product_id: '', get_qty: 1, 
                start_date: new Date().toISOString().split('T')[0], 
                end_date: new Date(Date.now() + 31536000000).toISOString().split('T')[0], 
                is_active: true
            };
            this.showModal = true;
        },

        editItem(item) {
            this.isEdit = true;
            this.form = {
                id: item.id, name: item.name, buy_product_id: item.buy_product_id, buy_qty: item.buy_qty,
                get_product_id: item.get_product_id, get_qty: item.get_qty, start_date: item.start_date, end_date: item.end_date,
                is_active: item.is_active == 1
            };
            this.showModal = true;
        },

        async saveItem() {
            if (!this.form.name || !this.form.buy_product_id || !this.form.get_product_id) {
                window.alert('Lengkapi nama promo dan pilih produk syarat maupun produk gratis!');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save');
            for (const key in this.form) {
                formData.append(key, this.form[key]);
            }

            try {
                const response = await fetch('logic.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    this.showModal = false;
                    await this.fetchData();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: result.message, showConfirmButton: false, timer: 3000 });
                    } else { window.alert(result.message); }
                } else { window.alert(result.message); }
            } catch (error) { window.alert('Terjadi kesalahan koneksi.'); }
        },

        async deleteItem(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus promo ini?')) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            try {
                const response = await fetch('logic.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    await this.fetchData();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: result.message, showConfirmButton: false, timer: 3000 });
                    }
                } else { window.alert(result.message); }
            } catch (error) { window.alert('Gagal menghapus data.'); }
        }
    }));
});
