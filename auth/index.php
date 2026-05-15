<?php
session_start();

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$base_url = $is_localhost ? '/pos-lovecakes/' : '/';

// Cegah user yang sudah login melihat form ini lagi
if (isset($_SESSION['pos_user_id'])) {
    if (strtolower($_SESSION['pos_role']) === 'kasir' || strtolower($_SESSION['pos_role']) === 'cashier') {
        header("Location: " . $base_url . "pos/kasir/");
    } else {
        header("Location: " . $base_url . "pos/dashboard/");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../components/header.php'; ?>
    <title>Login — Love Cakes POS</title>
    <meta name="description" content="Masuk ke sistem kasir Love Cakes untuk memulai sesi Anda.">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lottie Player -->
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }

        /* ---- Animated gradient background for left panel ---- */
        .hero-panel {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 40%, #7c3aed 100%);
            background-size: 300% 300%;
            animation: gradientShift 8s ease infinite;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            filter: blur(60px);
            animation: floatOrb 6s ease-in-out infinite;
        }
        .orb-1 { width: 300px; height: 300px; background: #60a5fa; top: -80px; left: -80px; animation-delay: 0s; }
        .orb-2 { width: 200px; height: 200px; background: #a78bfa; bottom: 60px; right: -60px; animation-delay: 2s; }
        .orb-3 { width: 150px; height: 150px; background: #34d399; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 4s; }

        @keyframes floatOrb {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-20px) scale(1.05); }
        }

        /* ---- Input focus ring ---- */
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            border-color: #2563eb;
        }

        /* ---- Login button pulse ---- */
        .btn-login {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(37, 99, 235, 0.5);
        }
        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        /* ---- Fade-in animation ---- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up { animation: fadeInUp 0.6s ease forwards; }
        .delay-1    { animation-delay: 0.1s; opacity: 0; }
        .delay-2    { animation-delay: 0.2s; opacity: 0; }
        .delay-3    { animation-delay: 0.3s; opacity: 0; }
        .delay-4    { animation-delay: 0.4s; opacity: 0; }

        /* ---- Glassmorphism card on hero ---- */
        .glass-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        /* ---- Lottie sizing ---- */
        lottie-player {
            width: 100%;
            max-width: 420px;
        }
    </style>
</head>
<body class="min-h-screen flex" x-data="{ ...loginApp(), showPass: false }">

    <!-- ========== LEFT HERO PANEL ========== -->
    <div class="hidden lg:flex hero-panel w-1/2 xl:w-3/5 flex-col items-center justify-center p-12 relative">

        <!-- Floating orbs -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <!-- Content -->
        <div class="relative z-10 text-center max-w-lg">

            <!-- Logo badge -->
            <div class="inline-flex items-center gap-3 glass-card rounded-2xl px-5 py-3 mb-8">
                <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-cake-candles text-white text-base"></i>
                </div>
                <span class="text-white font-black text-lg tracking-wide">Love Cakes</span>
            </div>

            <!-- Lottie Animation -->
            <div class="flex justify-center mb-8">
                <lottie-player
                    src="../assets/img/Business Analysis.json"
                    background="transparent"
                    speed="1"
                    loop
                    autoplay>
                </lottie-player>
            </div>

            <!-- Tagline -->
            <h1 class="text-4xl xl:text-5xl font-black text-white leading-tight mb-4">
                Sistem POS<br>
                <span class="text-blue-200">Terpadu & Modern</span>
            </h1>
            <p class="text-blue-100 text-base font-medium leading-relaxed">
                Kelola transaksi, laporan, dan inventori dengan mudah.<br>
                Semua dalam satu platform yang andal.
            </p>

            <!-- Feature badges -->
            <div class="flex flex-wrap justify-center gap-3 mt-8">
                <div class="glass-card rounded-xl px-4 py-2 flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-yellow-300 text-sm"></i>
                    <span class="text-white text-sm font-semibold">Transaksi Cepat</span>
                </div>
                <div class="glass-card rounded-xl px-4 py-2 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-green-300 text-sm"></i>
                    <span class="text-white text-sm font-semibold">Laporan Real-time</span>
                </div>
                <div class="glass-card rounded-xl px-4 py-2 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-purple-300 text-sm"></i>
                    <span class="text-white text-sm font-semibold">Multi Role</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== RIGHT LOGIN PANEL ========== -->
    <div class="flex-1 flex items-center justify-center p-6 sm:p-10 bg-slate-50 min-h-screen">
        <div class="w-full max-w-md">

            <!-- Mobile logo (hidden on desktop) -->
            <div class="flex lg:hidden items-center justify-center gap-3 mb-8">
                <div class="w-11 h-11 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-600/30">
                    <i class="fa-solid fa-cake-candles text-white text-xl"></i>
                </div>
                <span class="text-2xl font-black text-slate-800">Love Cakes</span>
            </div>

            <!-- Header -->
            <div class="mb-8 fade-in-up">
                <p class="text-blue-600 text-sm font-bold uppercase tracking-widest mb-1">Selamat Datang Kembali 👋</p>
                <h2 class="text-3xl font-black text-slate-800 leading-tight">Masuk ke Akun Anda</h2>
                <p class="text-slate-500 text-sm mt-2 font-medium">Masukkan kredensial untuk melanjutkan ke sistem</p>
            </div>

            <!-- ===== FORM LOGIN ===== -->
            <form @submit.prevent="doLogin" class="space-y-5">

                <!-- Username -->
                <div class="fade-in-up delay-1">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">
                        <i class="fa-solid fa-user mr-1 text-blue-500"></i> Username
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            x-model="username"
                            required
                            autocomplete="username"
                            placeholder="Masukkan username Anda..."
                            class="input-field w-full pl-4 pr-4 py-4 border-2 border-slate-200 rounded-2xl outline-none bg-white font-semibold text-slate-700 placeholder-slate-300 text-sm"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="fade-in-up delay-2">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">
                        <i class="fa-solid fa-lock mr-1 text-blue-500"></i> Password
                    </label>
                    <div class="relative">
                        <input
                            :type="showPass ? 'text' : 'password'"
                            x-model="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan password Anda..."
                            class="input-field w-full pl-4 pr-12 py-4 border-2 border-slate-200 rounded-2xl outline-none bg-white font-semibold text-slate-700 placeholder-slate-300 text-sm"
                        >
                        <button type="button" @click="showPass = !showPass"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 transition-colors">
                            <i :class="showPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="fade-in-up delay-3 pt-2">
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="btn-login w-full text-white font-black py-4 rounded-2xl flex items-center justify-center gap-3 text-base disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none"
                    >
                        <template x-if="!isLoading">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                Masuk Sekarang
                            </span>
                        </template>
                        <template x-if="isLoading">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                                Memverifikasi...
                            </span>
                        </template>
                    </button>
                </div>

            </form>

            <!-- Footer info -->
            <div class="fade-in-up delay-4 mt-8 pt-6 border-t border-slate-200">
                <div class="flex items-center justify-center gap-6 text-xs text-slate-400 font-medium">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-green-500"></i>
                        Koneksi Aman
                    </span>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-clock text-blue-500"></i>
                        Sesi Otomatis
                    </span>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-wifi text-purple-500"></i>
                        PWA Ready
                    </span>
                </div>
                <p class="text-center text-xs text-slate-300 mt-4">
                    &copy; <?= date('Y') ?> Love Cakes POS System. All rights reserved.
                </p>
            </div>

        </div>
    </div>

    <!-- Inject showPass property BEFORE ajax.js defines loginApp -->
    <script>
        // We intercept Alpine.data to extend loginApp with showPass
        document.addEventListener('alpine:init', () => {
            Alpine.data('loginApp_base', () => ({ showPass: false }));
        });
    </script>

    <script src="ajax.js?v=<?= time() ?>"></script>

    <!-- Patch showPass into loginApp after ajax.js has registered it -->
    <script>
        document.addEventListener('alpine:init', () => {
            const origLoginApp = Alpine._data?.loginApp || null;
            // showPass is referenced in x-data="loginApp()" on body
            // We extend by re-registering with showPass mixed in
            const _prev = Alpine.data;
            // The body's x-data will init with loginApp()
            // We need showPass available — safest way: override after ajax.js
            const _intercept = () => {
                const body = document.querySelector('body');
                if (body && body._x_dataStack) {
                    body._x_dataStack.forEach(d => {
                        if (!('showPass' in d)) d.showPass = false;
                    });
                }
            };
            // Try to inject after DOM ready
            setTimeout(_intercept, 0);
        }, { capture: true });
    </script>

</body>
</html>