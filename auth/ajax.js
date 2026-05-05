document.addEventListener('alpine:init', () => {
    Alpine.data('loginApp', () => ({
        username: '',
        password: '',
        isLoading: false,

        init() {
            // Cek apakah kasir sebelumnya sudah pernah login
            // Jika sudah ada sesi di IndexedDB, langsung lempar ke halaman Kasir
            dbAuth.getItem('user_session').then(user => {
                if (user) {
                    window.location.href = '../kasir/index.php';
                }
            });
        },

        async doLogin() {
            // Wajib online untuk login verifikasi ke server pusat
            if (!navigator.onLine) {
                alert('Anda sedang offline! Koneksi internet wajib menyala untuk proses login.');
                return;
            }

            this.isLoading = true;

            try {
                const formData = new FormData();
                formData.append('action', 'login_pos');
                formData.append('username', this.username);
                formData.append('password', this.password);

                // ========================================================
                // SESUAIKAN DENGAN URL API SISTEM PRODUKSIMU YA!
                // ========================================================
                const API_URL = 'http://localhost/sim_produksi_kue/owner/master_produk/api.php'; 

                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Simpan data user ke database lokal (IndexedDB)
                    await dbAuth.setItem('user_session', result.data);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Berhasil!',
                        text: 'Mengarahkan ke layar kasir...',
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                    }).then(() => {
                        window.location.href = '../kasir/index.php';
                    });
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error(error);
                alert('Gagal terhubung ke server pusat! Pastikan alamat API benar dan server menyala.');
            } finally {
                this.isLoading = false;
            }
        }
    }));
});