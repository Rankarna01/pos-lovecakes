document.addEventListener('alpine:init', () => {
    Alpine.data('settingsPosApp', () => ({
        isLoading: true,
        form: {
            markup_grab: '0',
            markup_gojek: '0',
            pin_supervisor: '',
            wa_gateway_api: '',
            wa_number_sender: '',
            default_start_cash: '0'
        },

        async init() {
            // 🛡️ 1. SMART GUARD (ANTI-MEMBAL)
            // Cek Autentikasi User (Header kita sudah nyediain dbAuth)
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                // HANYA tendang ke auth/index.php JIKA internet offline DAN sesi lokal hilang.
                if (!user && !navigator.onLine) {
                    window.location.href = '../../auth/index.php';
                    return;
                }
            }
            await this.fetchSettings();
        },

        async fetchSettings() {
            // 🛡️ 2. CEGAT JIKA OFFLINE SAAT TARIK PENGATURAN
            if (!navigator.onLine) {
                this.isLoading = false;
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Halaman Pengaturan POS membutuhkan koneksi internet!', 'warning');
                } else {
                    window.alert('Anda sedang offline! Halaman Pengaturan POS membutuhkan koneksi internet.');
                }
                return;
            }

            this.isLoading = true;
            try {
                // Tambahkan nocache agar selalu dapat data terbaru
                const response = await fetch(`logic.php?action=get&nocache=${new Date().getTime()}`);
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    // Gabungkan data dari server ke form 
                    // (Jika di server ada, pakai dari server, jika tidak pakai nilai default form)
                    this.form.markup_grab = result.data.markup_grab || '0';
                    this.form.markup_gojek = result.data.markup_gojek || '0';
                    this.form.pin_supervisor = result.data.pin_supervisor || '';
                    this.form.wa_gateway_api = result.data.wa_gateway_api || '';
                    this.form.wa_number_sender = result.data.wa_number_sender || '';
                    this.form.default_start_cash = result.data.default_start_cash || '0';
                    
                    // ✅ FITUR EMAS: Kita juga simpan ke memori lokal kasir
                    if(window.dbAuth) {
                        await window.dbAuth.setItem('pos_settings', result.data);
                    }
                }
            } catch (error) {
                console.error('Error fetching POS settings:', error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menarik data pengaturan dari server.', 'error');
            } finally {
                // WAJIB: Pastikan spinner mati
                this.isLoading = false;
            }
        },

        async saveSettings() {
            // 🛡️ 3. CEGAT JIKA OFFLINE SAAT SIMPAN
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline', 'Koneksi terputus! Tidak dapat menyimpan pengaturan POS.', 'warning');
                } else {
                    window.alert('Koneksi terputus! Tidak dapat menyimpan pengaturan POS.');
                }
                return;
            }

            this.isLoading = true;
            try {
                const formData = new FormData();
                for (const key in this.form) {
                    formData.append(key, this.form[key]);
                }

                const response = await fetch('logic.php?action=save', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();

                if (result.status === 'success') {
                    // ✅ UPDATE MEMORI LOKAL agar mesin kasir langsung terpengaruh tanpa perlu refresh
                    if(window.dbAuth) {
                        await window.dbAuth.setItem('pos_settings', this.form);
                    }
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: result.message, showConfirmButton: false, timer: 1500
                        });
                    } else {
                        window.alert(result.message);
                    }
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Menyimpan', result.message, 'error');
                    else window.alert(result.message);
                }
            } catch (error) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                else window.alert('Gagal menghubungi server.');
            } finally {
                this.isLoading = false;
            }
        }
    }));
});