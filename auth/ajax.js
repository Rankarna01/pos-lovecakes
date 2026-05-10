document.addEventListener('alpine:init', () => {
    Alpine.data('loginApp', () => ({
        username: '',
        password: '',
        isLoading: false,

        init() {
            // Cek SessionStorage: Jika masih ada tiket aktif, langsung lempar ke Layar Kasir
            // (Ingat: sessionStorage otomatis hilang kalau browser ditutup)
            const user = sessionStorage.getItem('pos_user_session');
            if (user) {
                window.location.href = '../kasir/index.php'; 
            }
        },

        async doLogin() {
            // Cek konektivitas internet secara dasar
            if (!navigator.onLine) {
                Swal.fire('Offline!', 'Koneksi internet Anda terputus! Harap hubungkan perangkat ke internet.', 'warning');
                return;
            }

            this.isLoading = true;

            try {
                const formData = new FormData();
                formData.append('username', this.username);
                formData.append('password', this.password);

                // Tembak ke API login logic.php
                const response = await fetch('logic.php', {
                    method: 'POST',
                    body: formData
                });

                // X-Ray Error Handler untuk antisipasi server nge-blank
                const rawText = await response.text();
                
                try {
                    const result = JSON.parse(rawText);

                    if (result.status === 'success') {
                        // SIMPAN KE SESSION STORAGE (Bukan dbAuth / IndexedDB lagi)
                        sessionStorage.setItem('pos_user_session', JSON.stringify(result.data));
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Berhasil!',
                            text: 'Mengarahkan ke Layar Kasir...',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                        }).then(() => {
                            // Arahkan ke Layar POS Utama
                            window.location.href = '../kasir/index.php'; 
                        });
                    } else {
                        Swal.fire('Ups!', result.message, 'error');
                    }
                } catch (parseError) {
                    console.error("❌ ERROR PHP:", rawText);
                    Swal.fire('Error System', 'Server merespons dengan format tidak valid.', 'error');
                }
            } catch (error) {
                console.error("Jaringan Gagal:", error);
                Swal.fire('Error Jaringan', 'Koneksi ke server pusat terputus.', 'error');
            } finally {
                this.isLoading = false;
            }
        }
    }));
});