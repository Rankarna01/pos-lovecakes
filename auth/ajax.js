document.addEventListener('alpine:init', () => {
    Alpine.data('loginApp', () => ({
        username: '',
        password: '',
        isLoading: false,

        init() {
            // Kosongkan. Urusan sesi sepenuhnya dipegang oleh PHP.
        },

        async doLogin() {
            if (!navigator.onLine) {
                Swal.fire('Offline!', 'Anda sedang offline! Koneksi internet wajib menyala untuk proses login awal.', 'warning');
                return;
            }

            this.isLoading = true;

            try {
                const formData = new FormData();
                formData.append('action', 'login_pos');
                formData.append('username', this.username);
                formData.append('password', this.password);

                const response = await fetch('logic.php', { method: 'POST', body: formData });
                const rawText = await response.text();

                try {
                    const result = JSON.parse(rawText);

                    if (result.status === 'success') {
                        // TAMPILKAN SWAL, LALU REDIRECT (TANPA INDEXEDDB)
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Berhasil!',
                            text: 'Mengarahkan ke Dasbor...',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                        }).then(() => {
                            // Mengikuti URL yang dikirim oleh PHP
                            window.location.href = result.redirect; 
                        });
                    } else {
                        Swal.fire('Ups!', result.message, 'error');
                    }
                } catch (parseError) {
                    Swal.fire('Error System', 'Gagal membaca respon server.', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error System', 'Gagal memproses login! Cek tab console/network.', 'error');
            } finally {
                this.isLoading = false;
            }
        }
    }));
});