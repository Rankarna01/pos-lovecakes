document.addEventListener('alpine:init', () => {
    Alpine.data('loyaltyApp', () => ({
        isActive: false,
        earnPointRatio: 0,     // Nominal untuk 1 poin
        pointsRequired: 0,
        discountAmount: 0,
        discountType: 'IDR',
        isLoading: true,

        async init() {
            // 🛡️ 1. SMART GUARD (ANTI-MEMBAL)
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                // HANYA tendang ke auth/index.php JIKA internet offline DAN sesi lokal hilang.
                if (!user && !navigator.onLine) {
                    window.location.href = '../../../auth/index.php';
                    return;
                }
            }
            await this.fetchSettings();
        },

        async fetchSettings() {
            // 🛡️ 2. CEGAT JIKA OFFLINE SAAT TARIK SETTING
            if (!navigator.onLine) {
                this.isLoading = false;
                window.alert('Anda sedang offline! Halaman Pengaturan Loyalty membutuhkan koneksi internet.');
                return;
            }

            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=get_settings&nocache=${new Date().getTime()}`);
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    this.isActive = result.data.is_active == 1;
                    this.earnPointRatio = result.data.earn_point_ratio;
                    this.pointsRequired = result.data.points_required;
                    this.discountAmount = result.data.discount_amount;
                    this.discountType = result.data.discount_type;
                }
            } catch (error) {
                console.error('Error fetching settings:', error);
                window.alert('Gagal menarik data pengaturan dari server.');
            } finally {
                // WAJIB: Pastikan spinner mati
                this.isLoading = false;
            }
        },

        async saveSettings() {
            // 🛡️ 3. CEGAT JIKA OFFLINE SAAT SIMPAN SETTING
            if (!navigator.onLine) {
                window.alert('Koneksi terputus! Tidak dapat menyimpan pengaturan.');
                return;
            }

            if (this.isActive && (this.pointsRequired <= 0 || this.discountAmount <= 0 || this.earnPointRatio <= 0)) {
                window.alert('Nilai nominal kelipatan, tukar poin, dan diskon tidak boleh 0 jika fitur diaktifkan!');
                return;
            }

            this.isLoading = true;
            try {
                const formData = new FormData();
                formData.append('is_active', this.isActive);
                formData.append('earn_point_ratio', this.earnPointRatio);
                formData.append('points_required', this.pointsRequired);
                formData.append('discount_amount', this.discountAmount);
                formData.append('discount_type', this.discountType);

                const response = await fetch('logic.php?action=save_settings', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    // ✅ FITUR EMAS: Simpan settingan ke IndexedDB agar Kasir (POS) bisa membacanya tanpa internet!
                    if (window.dbAuth) {
                        await window.dbAuth.setItem('loyalty_rules', {
                            is_active: this.isActive,
                            earn_point_ratio: this.earnPointRatio,
                            points_required: this.pointsRequired,
                            discount_amount: this.discountAmount,
                            discount_type: this.discountType
                        });
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
        },

        get previewEarnText() {
            if(this.earnPointRatio <= 0) return '';
            return `*Ilustrasi: Jika pelanggan transaksi Rp ${new Intl.NumberFormat('id-ID').format(this.earnPointRatio * 2.5)}, mereka akan mendapat 2 Poin.`;
        },

        get previewRedeemText() {
            if(this.pointsRequired <= 0 || this.discountAmount <= 0) return '';
            let diskonTxt = this.discountType === 'IDR' ? 'Rp ' + new Intl.NumberFormat('id-ID').format(this.discountAmount) : this.discountAmount + '%';
            return `*Saat checkout, pelanggan dengan ${this.pointsRequired} poin bisa memotong harga belanja sebesar ${diskonTxt}.`;
        }
    }));
});