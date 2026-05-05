document.addEventListener('alpine:init', () => {
    Alpine.data('posKasir', () => ({
        isOnline: navigator.onLine,
        
        init() {
            // Pantau perubahan jaringan
            window.addEventListener('online', () => {
                this.isOnline = true;
                alert('Kembali Online! Sistem akan memproses data tertunda.');
                // Nanti kita jalankan fungsi sinkronisasi (POST) otomatis di sini
            });

            window.addEventListener('offline', () => {
                this.isOnline = false;
                alert('Koneksi Terputus! Beralih ke Mode Offline. Transaksi akan disimpan di HP/Browser.');
            });
        },

        testNotif() {
            customConfirm('Yakin ingin mencoba notifikasi?', () => {
                alert('Berhasil! Library lokal berjalan sempurna tanpa internet!');
            });
        }
    }));
});