document.addEventListener('alpine:init', () => {
    Alpine.data('pengaturanApp', () => ({
        isLoading: true,
        isSaving: false,
        
        // Data Toko
        store: { store_name: '', store_address: '', store_phone: '', receipt_footer: '' },
        logoFile: null,
        logoPreview: '',

        // Data Sistem (Key-Value)
        system: { pin_supervisor: '', markup_grab: 0, markup_gojek: 0 },

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../../auth/index.php'; return; }
            }
            await this.fetchData();
        },

        async fetchData() {
            this.isLoading = true;
            try {
                const timestamp = new Date().getTime();
                const response = await fetch(`logic.php?action=get_settings&nocache=${timestamp}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.store = result.data.store || this.store;
                    
                    // Kalau ada logo lama, tampilkan previewnya
                    if (this.store.logo) {
                        this.logoPreview = `../../../assets/img/${this.store.logo}`;
                    }

                    // Gabungkan setting sistem dari DB ke state UI
                    this.system = { ...this.system, ...result.data.system };
                } else {
                    Swal.fire('Gagal Muat Data', result.message, 'error');
                }
            } catch (error) {
                console.error("Gagal Tarik Pengaturan:", error);
                Swal.fire('Error', 'Gagal menyambung ke database.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        handleLogoSelect(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 1024 * 1024) { // Max 1MB
                    Swal.fire('Ukuran Terlalu Besar', 'Maksimal ukuran logo adalah 1 MB.', 'warning');
                    return;
                }
                this.logoFile = file;
                this.logoPreview = URL.createObjectURL(file);
            }
        },

        async saveData() {
            this.isSaving = true;
            try {
                const formData = new FormData();
                
                // Append Identitas Toko
                formData.append('store_name', this.store.store_name);
                formData.append('store_address', this.store.store_address);
                formData.append('store_phone', this.store.store_phone);
                formData.append('receipt_footer', this.store.receipt_footer);
                if (this.logoFile) {
                    formData.append('logo', this.logoFile);
                }

                // Append Konfigurasi Sistem (jadikan format JSON agar mudah ditangkap PHP)
                formData.append('system_settings', JSON.stringify(this.system));

                const response = await fetch('logic.php?action=save_settings', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Update cache Memori Lokal agar Mesin Kasir otomatis tahu PIN dan Setting terbaru
                    if (window.dbAuth) {
                        await window.dbAuth.setItem('pos_settings', this.system);
                    }

                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: 'Pengaturan Disimpan!',
                        showConfirmButton: false, timer: 1500,
                        customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                    });
                } else {
                    Swal.fire('Gagal Menyimpan', result.message, 'error');
                }
            } catch (error) {
                console.error("Gagal Simpan Pengaturan:", error);
                Swal.fire('Error', 'Gagal menyimpan pengaturan ke database.', 'error');
            } finally {
                this.isSaving = false;
            }
        }
    }));
});