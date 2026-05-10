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
            // 🛡️ 1. SMART GUARD (ANTI-MEMBAL)
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                // HANYA tendang ke auth/index.php JIKA internet offline DAN sesi lokal hilang.
                // Jika online, biarkan PHP (config/auth.php) yang memutuskan nasibnya.
                if (!user && !navigator.onLine) { 
                    window.location.href = '../../../auth/index.php'; 
                    return; 
                }
            }
            await this.fetchData();
        },

        async fetchData() {
            // 🛡️ 2. CEGAT JIKA OFFLINE SAAT TARIK PENGATURAN
            if (!navigator.onLine) {
                this.isLoading = false;
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Halaman Pengaturan Toko membutuhkan koneksi internet!', 'warning');
                } else {
                    alert('Anda sedang offline! Halaman ini membutuhkan koneksi internet.');
                }
                return;
            }

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
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Muat Data', result.message, 'error');
                }
            } catch (error) {
                console.error("Gagal Tarik Pengaturan:", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyambung ke database.', 'error');
            } finally {
                // WAJIB: Pastikan spinner selalu mati
                this.isLoading = false;
            }
        },

        handleLogoSelect(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 1024 * 1024) { // Max 1MB
                    if (typeof Swal !== 'undefined') Swal.fire('Ukuran Terlalu Besar', 'Maksimal ukuran logo adalah 1 MB.', 'warning');
                    else alert('Maksimal ukuran logo adalah 1 MB.');
                    return;
                }
                this.logoFile = file;
                this.logoPreview = URL.createObjectURL(file);
            }
        },

        async saveData() {
            // 🛡️ 3. CEGAT JIKA OFFLINE SAAT SIMPAN PENGATURAN
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Koneksi terputus! Tidak dapat menyimpan pengaturan toko.', 'warning');
                else alert('Koneksi terputus! Tidak dapat menyimpan pengaturan toko.');
                return;
            }

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
                    // ✅ UPDATE CACHE LOKAL AGAR MESIN KASIR BACA PIN & MARKUP TERBARU
                    if (window.dbAuth) {
                        await window.dbAuth.setItem('pos_settings', this.system);
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: 'Pengaturan Disimpan!',
                            showConfirmButton: false, timer: 1500,
                            customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                        });
                    } else {
                        alert('Pengaturan Disimpan!');
                    }
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Menyimpan', result.message, 'error');
                }
            } catch (error) {
                console.error("Gagal Simpan Pengaturan:", error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyimpan pengaturan ke database.', 'error');
            } finally {
                this.isSaving = false;
            }
        }
    }));
});