document.addEventListener('alpine:init', () => {
    Alpine.data('loginApp', () => ({
        username: '',
        password: '',
        isLoading: false,

        init() {
            // Cek apakah user sudah login sebelumnya di IndexedDB
            // Jika ada sesi, langsung lempar ke Dasbor
            if(window.dbAuth) {
                window.dbAuth.getItem('user_session').then(user => {
                    if (user) {
                        window.location.href = '../pos/dashboard/';
                    }
                });
            }
        },

        async doLogin() {
            // Wajib online untuk login verifikasi ke server pusat (database MySQL)
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

                // Tembak ke file PHP di folder yang sama
                const API_URL = 'logic.php'; 

                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Simpan data user ke database lokal (IndexedDB)
                    if(window.dbAuth) {
                        await window.dbAuth.setItem('user_session', result.data);
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Berhasil!',
                        text: 'Mengarahkan ke Dasbor...',
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                    }).then(() => {
                        window.location.href = '../pos/dashboard/';
                    });
                } else {
                    Swal.fire('Ups!', result.message, 'error');
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