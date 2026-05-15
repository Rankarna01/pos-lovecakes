<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';

if (!defined('BASE_URL')) { 
    define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); 
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?= $page_title ?? 'POS Sistem Kasir Offline' ?></title>

<link rel="manifest" href="<?= BASE_URL ?>manifest.json">

<meta name="theme-color" content="#1e293b">
<link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/img/icon-192.png">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    // 🛠️ PERBAIKAN 2: Config Tailwind diletakkan di sini untuk membungkam peringatan kuning
    tailwind.config = {
        corePlugins: { preflight: true },
        theme: {
            extend: {
                fontFamily: { sans: ['Poppins', 'sans-serif'] },
                colors: {
                    surface: '#FFFFFF', background: '#F8FAFC', primary: '#2563EB',
                    secondary: '#94A3B8', accent: '#F59E0B', danger: '#EF4444', success: '#10B981'
                }
            }
        }
    }
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fontawesome.css">

<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>sweetalert2.all.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/localforage.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/pos_db.js"></script>

<style>
    @font-face {
        font-family: 'Poppins';
        src: url('<?= BASE_URL ?>assets/fonts/poppins.woff2') format('woff2');
        font-weight: normal; font-style: normal;
    }
    body { font-family: 'Poppins', sans-serif !important; background-color: #F8FAFC; }
    .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    /* INI KUNCI ANTI KEDAP-KEDIP */
    [x-cloak] { display: none !important; }
    
    #global-loader { display: none; backdrop-filter: blur(4px); }
    div:where(.swal2-container) { font-family: 'Poppins', sans-serif !important; }
</style>

<script>
    // 🛠️ PERBAIKAN 3: Registrasi Service Worker dihilangkan kata "pos/"-nya
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= BASE_URL ?>sw.js')
                .then(registration => {
                    console.log('ServiceWorker sukses didaftarkan dengan scope: ', registration.scope);
                })
                .catch(err => {
                    console.log('ServiceWorker gagal didaftarkan: ', err);
                });
        });
    }

    // 🛠️ PERBAIKAN: Paksa bersihkan Cache lama dari memori Safari/Chrome
    if ('caches' in window) {
        caches.keys().then(function(names) {
            for (let name of names) {
                if (name !== 'lovecakes-pos-v5') {
                    caches.delete(name);
                }
            }
        });
    }

    // --- LOGIKA ALERT CUSTOM BAWAANMU (TIDAK ADA YANG DIHAPUS) ---
    window.alert = function(message) {
        let type = 'info';
        let msgStr = String(message).toLowerCase();
        
        if(msgStr.includes('berhasil') || msgStr.includes('success') || msgStr.includes('dicatat') || msgStr.includes('disimpan')) type = 'success';
        if(msgStr.includes('gagal') || msgStr.includes('error') || msgStr.includes('maaf')) type = 'error';
        if(msgStr.includes('pilih') || msgStr.includes('wajib') || msgStr.includes('harap')) type = 'warning';

        if (type === 'success') {
            if(typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true,
                    customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                });
                Toast.fire({ icon: 'success', title: message });
            }
        } else {
            if(typeof Swal !== 'undefined') {
                Swal.fire({
                    title: type === 'error' ? 'Oops! Ada Masalah' : (type === 'warning' ? 'Perhatian' : 'Informasi'),
                    html: `<p style="color: #475569; font-weight: 500; font-size: 14px;">${message}</p>`,
                    icon: type,
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: type === 'error' ? '#EF4444' : (type === 'warning' ? '#F59E0B' : '#2563EB'),
                    customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                });
            }
        }
    };

    window.customConfirm = function(message, callback) {
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                html: `<p style="color: #475569; font-weight: 500; font-size: 14px;">${message}</p>`,
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#EF4444', cancelButtonColor: '#94A3B8',  
                confirmButtonText: '<i class="fa-solid fa-check mr-1"></i> Ya, Lanjutkan!', cancelButtonText: 'Batal',
                reverseButtons: true, customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
            }).then((result) => { if (result.isConfirmed) { callback(); } });
        }
    };

    function eksekusiLogout() {
        if (window.dbAuth) {
            window.dbAuth.removeItem('user_session').then(() => { window.location.href = '<?= BASE_URL ?>logout_action.php'; })
            .catch(() => { window.location.href = '<?= BASE_URL ?>logout_action.php'; });
        } else {
            window.location.href = '<?= BASE_URL ?>logout_action.php';
        }
    }

    window.logoutSistem = function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Yakin mau Logout?', icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8', confirmButtonText: 'Ya, Keluar!', cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) { eksekusiLogout(); } });
        } else {
            if (confirm('Yakin mau Logout?')) { eksekusiLogout(); }
        }
    };
    window.doLogout = window.logoutSistem;
</script>