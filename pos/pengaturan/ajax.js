document.addEventListener('alpine:init', () => {
    Alpine.data('settingsPosApp', () => ({
        isLoading: true,
        form: {
            markup_grab: '0',
            markup_gojek: '0',
            pin_supervisor: '',
            wa_gateway_api: '',
            wa_number_sender: ''
        },

        async init() {
            // Cek Autentikasi User (Header kita sudah nyediain dbAuth)
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) {
                    window.location.href = '../../auth/index.php';
                    return;
                }
            }
            await this.fetchSettings();
        },

        async fetchSettings() {
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
                    
                    // Kita juga simpan ke memori lokal kasir
                    if(window.dbAuth) {
                        await window.dbAuth.setItem('pos_settings', result.data);
                    }
                }
            } catch (error) {
                console.error('Error fetching POS settings:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async saveSettings() {
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
                    // Update memori lokal agar mesin kasir langsung terpengaruh
                    if(window.dbAuth) {
                        await window.dbAuth.setItem('pos_settings', this.form);
                    }
                    window.alert(result.message);
                } else {
                    window.alert(result.message);
                }
            } catch (error) {
                window.alert('Gagal menghubungi server.');
            } finally {
                this.isLoading = false;
            }
        }
    }));
});