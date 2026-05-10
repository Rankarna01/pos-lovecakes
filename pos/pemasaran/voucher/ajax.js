document.addEventListener('alpine:init', () => {
    Alpine.data('voucherApp', () => ({
        vouchers: [],
        searchQuery: '',
        isLoading: false,
        
        // State Modal
        showModal: false,
        isEdit: false,
        form: {
            id: '', voucher_code: '', voucher_name: '', discount_type: 'IDR',
            discount_amount: 0, min_purchase: 0, valid_from: '', valid_until: '', max_usage: 0, is_active: true
        },

        async init() {
            // 🛡️ 1. SMART GUARD (ANTI-MEMBAL)
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                // HANYA tendang ke auth/index.php JIKA internet offline DAN sesi lokal hilang.
                // Jika online, biarkan PHP (config/auth.php) yang memutuskan.
                if (!user && !navigator.onLine) {
                    window.location.href = '../../../auth/index.php';
                    return;
                }
            }
            await this.fetchData();
        },

        async fetchData() {
            // 🛡️ 2. CEGAT JIKA OFFLINE SAAT TARIK DATA
            if (!navigator.onLine) {
                this.isLoading = false;
                window.alert('Anda sedang offline! Halaman Voucher membutuhkan koneksi internet.');
                return;
            }

            this.isLoading = true;
            try {
                // Tembak backend dengan timestamp agar tidak ter-cache
                const response = await fetch(`logic.php?action=read&nocache=${new Date().getTime()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.vouchers = result.data || [];
                }
            } catch (error) {
                console.error("Error loading vouchers", error);
            } finally {
                this.isLoading = false;
            }
        },

        get filteredData() {
            if (this.searchQuery.trim() === '') return this.vouchers;
            const q = this.searchQuery.toLowerCase();
            return this.vouchers.filter(v => 
                v.voucher_code.toLowerCase().includes(q) || 
                v.voucher_name.toLowerCase().includes(q)
            );
        },

        openModal(item = null) {
            if (item) {
                this.isEdit = true;
                this.form = { ...item, is_active: item.is_active == 1 };
            } else {
                this.isEdit = false;
                this.form = {
                    id: '', voucher_code: '', voucher_name: '', discount_type: 'IDR',
                    discount_amount: '', min_purchase: '', valid_from: '', valid_until: '', max_usage: '', is_active: true
                };
            }
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
        },

        async simpanData() {
            // 🛡️ 3. CEGAT JIKA OFFLINE SAAT SIMPAN
            if (!navigator.onLine) {
                window.alert('Koneksi terputus! Tidak dapat menyimpan data voucher.');
                return;
            }

            if (!this.form.voucher_code || !this.form.voucher_name || !this.form.discount_amount) {
                window.alert('Mohon lengkapi data wajib bertanda bintang merah (*).');
                return;
            }

            this.isLoading = true;
            try {
                const fd = new FormData();
                for (const key in this.form) {
                    fd.append(key, this.form[key]);
                }

                const response = await fetch('logic.php?action=save', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    window.alert(result.message);
                    this.closeModal();
                    await this.fetchData(); // Refresh tabel setelah simpan
                } else {
                    window.alert(result.message);
                }
            } catch (error) {
                window.alert('Gagal menghubungi server.');
            } finally {
                this.isLoading = false;
            }
        },

        hapusData(id) {
            // 🛡️ 4. CEGAT JIKA OFFLINE SAAT HAPUS
            if (!navigator.onLine) {
                window.alert('Koneksi terputus! Tidak dapat menghapus data voucher.');
                return;
            }

            // Menggunakan fungsi customConfirm dari header global!
            window.customConfirm('Yakin ingin menghapus voucher ini secara permanen?', async () => {
                this.isLoading = true;
                try {
                    const fd = new FormData();
                    fd.append('id', id);
                    const response = await fetch('logic.php?action=delete', { method: 'POST', body: fd });
                    const result = await response.json();

                    if (result.status === 'success') {
                        window.alert(result.message);
                        await this.fetchData(); // Refresh tabel
                    } else {
                        window.alert(result.message);
                    }
                } catch (error) {
                    window.alert('Gagal menghapus data.');
                } finally {
                    this.isLoading = false;
                }
            });
        },

        async toggleStatus(id, newStatus) {
            // 🛡️ 5. CEGAT JIKA OFFLINE SAAT UBAH STATUS (ON/OFF)
            if (!navigator.onLine) {
                window.alert('Koneksi terputus! Tidak dapat mengubah status voucher.');
                return;
            }

            try {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('status', newStatus);
                await fetch('logic.php?action=toggle_status', { method: 'POST', body: fd });
                await this.fetchData(); // Refresh UI diam-diam
            } catch (error) {
                console.error("Gagal mengubah status", error);
            }
        },

        formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(angka || 0);
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            return new Date(dateStr).toLocaleDateString('id-ID', options);
        }
    }));
});