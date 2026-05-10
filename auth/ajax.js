document.addEventListener('alpine:init', () => {
    Alpine.data('loginApp', () => ({
        username: '',
        password: '',
        isLoading: false,

        init() {
            // Pengecekan sesi online/offline pintar
            if (window.dbAuth) {
                window.dbAuth.getItem('user_session').then(user => {
                    // Kalau KTP Lokal ada, DAN kita sedang offline, langsung masuk!
                    if (user && !navigator.onLine) {
                        // Secara default arahkan ke Kasir untuk mode Offline
                        window.location.href = '../pos/kasir/'; 
                    }
                    // Catatan: Kalau Online, biarkan saja. 
                    // Nanti PHP (auth/index.php) yang otomatis melempar ke Dashboard.
                });
            }
        },

        async doLogin() {
            if (!navigator.onLine) {
                Swal.fire('Offline!', 'Koneksi internet wajib menyala untuk proses login.', 'warning');
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
                        
                        // ==========================================
                        // CETAK KTP LOKAL SEBELUM PINDAH HALAMAN
                        // ==========================================
                        if(window.dbAuth) {
                            await window.dbAuth.setItem('user_session', result.data);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Login Berhasil!',
                            text: 'Mengarahkan ke Sistem...',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                        }).then(() => {
                            window.location.href = result.redirect; 
                        });
                    } else {
                        Swal.fire('Ups!', result.message, 'error');
                    }
                } catch (parseError) {
                    console.error("❌ ERROR DARI SERVER (Format bukan JSON):", rawText);
                    Swal.fire('Error Server', 'Terjadi kesalahan sistem. Cek Console untuk melihat detail error PHP-nya.', 'error');
                }
            } catch (error) {
                console.error("Koneksi Error:", error);
                Swal.fire('Error Jaringan', 'Gagal terhubung ke server! Periksa koneksi internet Anda.', 'error');
            } finally {
                this.isLoading = false;
            }
        }
    }));
});