<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>POS Sistem Kasir Offline</title>
<link rel="manifest" href="../manifest.json">
<meta name="theme-color" content="#2563EB">

<!-- PANGGIL FONT DAN ICON DARI LOKAL -->
<link rel="stylesheet" href="../assets/css/fontawesome.css">
<style>
    /* Contoh panggil font lokal jika didownload, atau hapus jika pakai font bawaan OS saat offline */
    @font-face {
        font-family: 'Poppins';
        src: url('../assets/fonts/poppins.woff2') format('woff2');
        font-weight: normal;
        font-style: normal;
    }
</style>

<!-- PANGGIL LIBRARY JS DARI LOKAL (WAJIB DOWNLOAD FILENYA) -->
<script src="../assets/js/sweetalert2.all.js"></script>
<script src="../assets/js/alpine.min.js" defer></script>
<script src="../assets/js/localforage.min.js"></script>
<script src="../assets/js/pos_db.js"></script>

<!-- TAILWIND VIA SCRIPT LOKAL (Download https://cdn.tailwindcss.com jadi tailwind.js) -->
<script src="../assets/js/tailwind.js"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Poppins', 'sans-serif'] },
                colors: {
                    surface: '#FFFFFF',
                    background: '#F8FAFC',
                    primary: '#2563EB',
                    secondary: '#94A3B8',
                    accent: '#F59E0B',
                    danger: '#EF4444',
                    success: '#10B981'
                }
            }
        }
    }
</script>

<style>
    body { font-family: 'Poppins', sans-serif; background-color: theme('colors.background'); }
    #global-loader { display: none; backdrop-filter: blur(4px); }
    div:where(.swal2-container) { font-family: 'Poppins', sans-serif; }
</style> 

<script>
    // 1. REGISTRASI SERVICE WORKER UNTUK OFFLINE MODE
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('../sw.js')
                .then(reg => console.log('Service Worker POS aktif!'))
                .catch(err => console.error('Service Worker gagal!', err));
        });
    }

    // 2. FUNGSI OVERRIDE ALERT() SEPERTI DI PRODUKSI
    window.alert = function(message) {
        let type = 'info';
        let msgStr = String(message).toLowerCase();
        
        if(msgStr.includes('berhasil') || msgStr.includes('success') || msgStr.includes('dicatat') || msgStr.includes('disimpan')) type = 'success';
        if(msgStr.includes('gagal') || msgStr.includes('error') || msgStr.includes('maaf')) type = 'error';
        if(msgStr.includes('pilih') || msgStr.includes('wajib') || msgStr.includes('harap')) type = 'warning';

        if (type === 'success') {
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true,
                customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            Toast.fire({ icon: 'success', title: message });
        } else {
            Swal.fire({
                title: type === 'error' ? 'Oops! Ada Masalah' : (type === 'warning' ? 'Perhatian' : 'Informasi'),
                html: `<p style="color: #475569; font-weight: 500; font-size: 14px;">${message}</p>`,
                icon: type,
                confirmButtonText: 'Mengerti',
                confirmButtonColor: type === 'error' ? '#EF4444' : (type === 'warning' ? '#F59E0B' : '#2563EB'),
                customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
            });
        }
    };

    // 3. FUNGSI CUSTOM CONFIRM ()
    window.customConfirm = function(message, callback) {
        Swal.fire({
            title: 'Apakah Anda Yakin?',
            html: `<p style="color: #475569; font-weight: 500; font-size: 14px;">${message}</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444', 
            cancelButtonColor: '#94A3B8',  
            confirmButtonText: '<i class="fa-solid fa-check mr-1"></i> Ya, Lanjutkan!',
            cancelButtonText: 'Batal',
            reverseButtons: true, 
            customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
        }).then((result) => {
            if (result.isConfirmed) { callback(); }
        });
    };
</script>