document.addEventListener('alpine:init', () => {
    Alpine.data('diskonOtomatisApp', () => ({
        discounts: [],
        searchQuery: '',
        isLoading: false,
        
        showModal: false,
        isEdit: false,
        form: {
            id: '', name: '', min_purchase: 100000, discount_type: 'PERCENT',
            discount_value: 3, start_date: '', end_date: '', is_active: true
        },

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user && !navigator.onLine) {
                    window.location.href = '../../../auth/index.php';
                    return;
                }
            }
            await this.fetchData();
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
                    this.discounts = result.data;
                } else {
                    window.alert('Gagal mengambil data diskon: ' + result.message);
                }
            } catch (error) {
                console.error('Error fetching data:', error);
            } finally {
                this.isLoading = false;
            }
        },

        get filteredData() {
            if (!this.searchQuery) return this.discounts;
            const query = this.searchQuery.toLowerCase();
            return this.discounts.filter(item => item.name.toLowerCase().includes(query));
        },

        openModal() {
            this.isEdit = false;
            this.form = {
                id: '', name: '', min_purchase: 100000, discount_type: 'PERCENT',
                discount_value: 3, 
                start_date: new Date().toISOString().split('T')[0], 
                end_date: new Date(Date.now() + 31536000000).toISOString().split('T')[0], 
                is_active: true
            };
            this.showModal = true;
        },

        editItem(item) {
            this.isEdit = true;
            this.form = {
                id: item.id, name: item.name, min_purchase: item.min_purchase, discount_type: item.discount_type,
                discount_value: item.discount_value, start_date: item.start_date, end_date: item.end_date,
                is_active: item.is_active == 1
            };
            this.showModal = true;
        },

        async saveItem() {
            if (!this.form.name || !this.form.discount_value) {
                window.alert('Lengkapi nama promo dan besaran diskon!');
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
            if (!confirm('Apakah Anda yakin ingin menghapus diskon otomatis ini?')) return;
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
