document.addEventListener('alpine:init', () => {
    Alpine.data('loyaltyApp', () => ({
        isActive: false,
        pointsRequired: 0,
        discountAmount: 0,
        discountType: 'IDR',
        isLoading: true,

        async init() {
            // Cek Auth
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) {
                    window.location.href = '../../../auth/index.php';
                    return;
                }
            }
            await this.fetchSettings();
        },

        async fetchSettings() {
            this.isLoading = true;
            try {
                const response = await fetch('logic.php?action=get_settings');
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    this.isActive = result.data.is_active == 1;
                    this.pointsRequired = result.data.points_required;
                    this.discountAmount = result.data.discount_amount;
                    this.discountType = result.data.discount_type;
                }
            } catch (error) {
                console.error('Error fetching settings:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async saveSettings() {
            if (this.isActive && (this.pointsRequired <= 0 || this.discountAmount <= 0)) {
                window.alert('Nilai point dan diskon tidak boleh 0 jika fitur diaktifkan!');
                return;
            }

            this.isLoading = true;
            try {
                const formData = new FormData();
                formData.append('is_active', this.isActive);
                formData.append('points_required', this.pointsRequired);
                formData.append('discount_amount', this.discountAmount);
                formData.append('discount_type', this.discountType);

                const response = await fetch('logic.php?action=save_settings', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    window.alert(result.message);
                } else {
                    window.alert(result.message);
                }
            } catch (error) {
                window.alert('Gagal menghubungi server.');
            } finally {
                this.isLoading = false;
            }
        },

        // Computed Property buat nge-preview kata-katanya
        get previewText() {
            if(this.pointsRequired <= 0 || this.discountAmount <= 0) return '';
            
            let diskonTxt = '';
            if(this.discountType === 'IDR') {
                diskonTxt = 'Rp ' + new Intl.NumberFormat('id-ID').format(this.discountAmount);
            } else {
                diskonTxt = this.discountAmount + '%';
            }

            return `Pelanggan akan mendapatkan diskon ${diskonTxt} jika menukarkan ${this.pointsRequired} poin.`;
        }
    }));
});
