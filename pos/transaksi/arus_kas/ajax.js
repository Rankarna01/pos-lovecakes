document.addEventListener('alpine:init', () => {
    Alpine.data('arusKasApp', () => ({
        isLoading: false,
        isSaving: false,
        startDate: new Date().toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
        
        summary: { masuk: 0, keluar: 0 },
        history: [],

        showModal: false,
        form: {
            jenis: 'keluar',
            nominal: '',
            keterangan: ''
        },

        init() {
            this.fetchData();
        },

        async fetchData() {
            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=get_arus_kas&start_date=${this.startDate}&end_date=${this.endDate}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    this.summary = data.summary;
                    this.history = data.data;
                } else {
                    Swal.fire('Error', data.message || 'Gagal memuat data', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi ke server terputus.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        openModal(jenis) {
            this.form.jenis = jenis;
            this.form.nominal = '';
            this.form.keterangan = '';
            this.showModal = true;
        },

        async saveData() {
            if (!this.form.nominal || this.form.nominal <= 0) {
                return Swal.fire('Peringatan', 'Nominal harus lebih dari 0', 'warning');
            }
            if (!this.form.keterangan) {
                return Swal.fire('Peringatan', 'Keterangan harus diisi', 'warning');
            }

            this.isSaving = true;
            try {
                const formData = new URLSearchParams();
                formData.append('action', 'save_arus_kas');
                formData.append('jenis', this.form.jenis);
                formData.append('nominal', this.form.nominal);
                formData.append('keterangan', this.form.keterangan);

                const response = await fetch('logic.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                
                const data = await response.json();
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success');
                    this.showModal = false;
                    this.fetchData(); // reload tabel
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        formatRupiah(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '';
            const dt = new Date(dateStr);
            const d = dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const t = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
            return { date: d, time: t };
        }
    }));
});
