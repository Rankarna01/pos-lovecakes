<?php
// WAJIB ADA: Mencegah Error 500 di sidebar.php
define('BASE_URL', 'http://localhost/pos-lovecakes/');
$base_path = '../../';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Love Cakes POS</title>
    
    <!-- PANGGIL FONT DAN ICON (Path Pasti Benar) -->
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/fontawesome.css">
    <style>
        @font-face {
            font-family: 'Poppins';
            src: url('<?= $base_path ?>assets/fonts/poppins.woff2') format('woff2');
            font-weight: normal; font-style: normal;
        }
        body { font-family: 'Poppins', sans-serif; }
    </style>

    <!-- LIBRARY JS OFFLINE -->
    <script src="<?= $base_path ?>assets/js/sweetalert2.all.js"></script>
    <script src="<?= $base_path ?>assets/js/localforage.min.js"></script>
    <script src="<?= $base_path ?>assets/js/pos_db.js"></script>
    <script src="<?= $base_path ?>assets/js/chart.min.js"></script>

    <!-- TAILWIND CSS -->
    <script src="<?= $base_path ?>assets/js/tailwind.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#2563EB', danger: '#ef4444', surface: '#ffffff' } } }
        }
    </script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased">

    <!-- PANGGIL SIDEBAR -->
    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER DASHBOARD SIMPEL -->
        <header class="bg-primary text-white shadow-sm px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-bold tracking-wide">Dasbor</h2>
            </div>
            <div class="flex items-center gap-5 text-lg">
                <button class="hover:text-blue-200"><i class="fa-solid fa-magnifying-glass"></i></button>
                <button class="hover:text-blue-200"><i class="fa-solid fa-gift"></i></button>
                <button class="hover:text-blue-200 relative"><i class="fa-regular fa-bell"></i></button>

                <!-- TOMBOL LOGOUT MERAH (Langsung eksekusi fungsi js di bawah) -->
                <div class="border-l border-blue-400 pl-4 ml-2">
                    <button onclick="logoutSistem()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="max-w-5xl mx-auto space-y-6">
                
                <h3 class="font-black text-slate-800 text-lg md:text-xl">Sekilas "lovecakes bengkulu"</h3>

                <!-- QUICK ACTIONS -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-blue-50 text-primary flex items-center justify-center text-xl"><i class="fa-solid fa-percent"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Diskon Persen</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Atur potongan %</p>
                        </div>
                    </button>
                    <button class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl"><i class="fa-solid fa-gift"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Beli 1 Gratis 1</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Set promo bundling</p>
                        </div>
                    </button>
                    <button class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-xl"><i class="fa-solid fa-tags"></i></div>
                        <div class="text-left">
                            <h4 class="font-black text-sm text-slate-800">Diskon Nominal</h4>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">Potongan harga tetap</p>
                        </div>
                    </button>
                </div>

                <!-- SEKILAS TOKO ANDA -->
                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100">
                        <h4 class="font-black text-slate-800 text-lg">Sekilas toko Anda</h4>
                    </div>
                    <div class="divide-y divide-slate-100 px-5 text-sm font-medium text-slate-600">
                        <div class="flex items-center justify-between py-4"><div class="flex items-center gap-3"><i class="fa-solid fa-user-plus w-5 text-slate-400"></i> Pelanggan baru</div><div class="font-black">0</div></div>
                        <div class="flex items-center justify-between py-4"><div class="flex items-center gap-3"><i class="fa-solid fa-cart-shopping w-5 text-slate-400"></i> Pesanan</div><div class="font-black">87</div></div>
                        <div class="flex items-center justify-between py-4"><div class="flex items-center gap-3"><i class="fa-solid fa-arrow-trend-up w-5 text-slate-400"></i> Pendapatan</div><div class="font-black">IDR ---</div></div>
                    </div>
                </div>

                <!-- PENJUALAN & GRAFIK -->
                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden p-5">
                    <h4 class="font-black text-slate-800 text-lg mb-6">Penjualan</h4>
                    
                    <div class="grid grid-cols-2 gap-y-6 gap-x-4 mb-8">
                        <div><p class="text-sm text-slate-600 mb-1">Total penjualan</p><p class="text-sm font-black mb-2">IDR ---</p><span class="bg-danger text-white px-2 py-1 rounded text-[10px] font-black"><i class="fa-solid fa-chevron-down"></i> -15.14%</span></div>
                        <div><p class="text-sm text-slate-600 mb-1">Penjualan kotor</p><p class="text-sm font-black mb-2">---</p><span class="bg-danger text-white px-2 py-1 rounded text-[10px] font-black"><i class="fa-solid fa-chevron-down"></i> -15.14%</span></div>
                        <div><p class="text-sm text-slate-600 mb-1">Laba kotor</p><p class="text-sm font-black mb-2">IDR ---</p><span class="bg-danger text-white px-2 py-1 rounded text-[10px] font-black"><i class="fa-solid fa-chevron-down"></i> -4.31%</span></div>
                        <div><p class="text-sm text-slate-600 mb-1">Transaksi</p><p class="text-sm font-black mb-2">87</p><span class="bg-danger text-white px-2 py-1 rounded text-[10px] font-black"><i class="fa-solid fa-chevron-down"></i> -20.18%</span></div>
                    </div>

                    <!-- Canvas Chart.js -->
                    <div class="relative w-full h-64"><canvas id="salesChart"></canvas></div>
                </div>
                
                <div class="h-10"></div>
            </div>
        </main>
    </div>

    <!-- SCRIPT LOGIKA JAVASCRIPT MURNI (SANGAT SIMPEL) -->
    <script>
        // 1. FUNGSI CEK SESI (Otomatis jalan saat halaman dibuka)
        window.onload = function() {
            if (window.dbAuth) {
                window.dbAuth.getItem('user_session').then(user => {
                    if (!user) {
                        // Kalau belum login, lempar ke halaman auth
                        window.location.href = '../../auth/index.php';
                    }
                });
            }
            loadChart(); // Panggil fungsi grafik
        };

        // 2. FUNGSI LOGOUT
        function logoutSistem() {
            Swal.fire({
                title: 'Yakin mau Logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Keluar!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (window.dbAuth) {
                        window.dbAuth.removeItem('user_session').then(() => {
                            window.location.href = '../../logout_action.php';
                        });
                    } else {
                        window.location.href = '../../logout_action.php';
                    }
                }
            });
        }

        // 3. FUNGSI RENDER GRAFIK
        function loadChart() {
            const canvas = document.getElementById('salesChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Ming'],
                    datasets: [{
                        label: 'Pendapatan',
                        data: [1500000, 2300000, 1800000, 3200000, 2900000, 4500000, 7191800],
                        borderColor: '#2563EB', backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true, tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        }
    </script>
</body>
</html>