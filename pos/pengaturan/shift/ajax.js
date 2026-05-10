document.addEventListener('alpine:init', () => {
    Alpine.data('shiftApp', () => ({
        shifts: [],
        isLoading: false,
        isSaving: false,
        
        // Modal State
        showModal: false,
        formData: { id: '', shift_name: '', start_time: '', end_time: '' },

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
            await this.fetchData();
        },

        async fetchData() {
            // 🛡️ 2. CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                this.isLoading = false;
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Halaman Pengaturan Shift membutuhkan koneksi internet!', 'warning');
                }
                return;
            }

            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=read&nocache=${Date.now()}`);
                const result = await response.json();
                if (result.status === 'success') {
                    this.shifts = result.data || [];
                }
            } catch (error) {
                console.error(error);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal memuat data shift.', 'error');
            } finally {
                // WAJIB: Pastikan spinner mati apapun yang terjadi
                this.isLoading = false;
            }
        },

        openModal(shift = null) {
            if (shift) {
                // Hapus detik dari waktu SQL (07:00:00 jadi 07:00) agar pas di input type="time"
                this.formData = { 
                    id: shift.id, 
                    shift_name: shift.shift_name, 
                    start_time: shift.start_time.substring(0, 5), 
                    end_time: shift.end_time.substring(0, 5) 
                };
            } else {
                this.formData = { id: '', shift_name: '', start_time: '', end_time: '' };
            }
            this.showModal = true;
        },

        async saveShift() {
            // 🛡️ 3. CEGAT JIKA OFFLINE SAAT SIMPAN
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Koneksi terputus! Tidak dapat menyimpan data shift.', 'warning');
                return;
            }

            this.isSaving = true;
            try {
                const fd = new FormData();
                fd.append('action', 'save');
                fd.append('id', this.formData.id);
                fd.append('shift_name', this.formData.shift_name);
                fd.append('start_time', this.formData.start_time);
                fd.append('end_time', this.formData.end_time);

                const response = await fetch('logic.php', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    this.showModal = false;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: result.message, showConfirmButton: false, timer: 1500 });
                    }
                    this.fetchData();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                }
            } catch (error) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyimpan data.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        async deleteShift(id) {
            // 🛡️ 4. CEGAT JIKA OFFLINE SAAT HAPUS
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') Swal.fire('Offline', 'Koneksi terputus! Tidak dapat menghapus data.', 'warning');
                return;
            }

            const confirm = await Swal.fire({
                title: 'Hapus Shift?',
                text: "Data shift ini akan dinonaktifkan dari sistem.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus!'
            });

            if (confirm.isConfirmed) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);

                    const response = await fetch('logic.php', { method: 'POST', body: fd });
                    const result = await response.json();

                    if (result.status === 'success') {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: result.message, showConfirmButton: false, timer: 1500 });
                        this.fetchData();
                    }
                } catch (error) {
                    Swal.fire('Error', 'Gagal menghapus data.', 'error');
                }
            }
        },

        formatTime(timeString) {
            if (!timeString) return '-';
            return timeString.substring(0, 5); // Ambil HH:MM saja
        }
    }));
});