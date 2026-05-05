<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Memanggil semua script penting dari header global -->
    <?php include '../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4" x-data="loginApp()">
    
    <div class="bg-white p-8 sm:p-10 rounded-[2rem] shadow-2xl w-full max-w-md border border-slate-100 relative overflow-hidden">
        <!-- Dekorasi Background -->
        <div class="absolute -top-20 -right-20 w-40 h-40 bg-blue-600/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-blue-400/5 rounded-full blur-3xl"></div>

        <div class="text-center mb-8 relative z-10">
            <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center text-4xl mx-auto mb-5 shadow-inner">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">Login POS Kasir</h2>
            <p class="text-slate-500 text-sm mt-2 font-medium">Masuk untuk memulai sesi kasir Anda</p>
        </div>
        
        <form @submit.prevent="doLogin" class="space-y-5 relative z-10">
            <div>
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-user text-slate-400"></i>
                    </div>
                    <input type="text" x-model="username" required class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-600/20 focus:border-blue-600 outline-none bg-slate-50 transition-all font-bold text-slate-700" placeholder="Ketik username...">
                </div>
            </div>
            <div>
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-slate-400"></i>
                    </div>
                    <input type="password" x-model="password" required class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-600/20 focus:border-blue-600 outline-none bg-slate-50 transition-all font-bold text-slate-700" placeholder="Ketik password...">
                </div>
            </div>
            
            <button type="submit" :disabled="isLoading" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 mt-6 disabled:opacity-70 disabled:cursor-not-allowed">
                <span x-show="!isLoading"><i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang</span>
                <span x-show="isLoading"><i class="fa-solid fa-circle-notch fa-spin"></i> Memverifikasi...</span>
            </button>
        </form>
        
        <div class="mt-8 text-center text-[11px] text-slate-400 font-bold bg-slate-50 py-3 rounded-xl border border-slate-100">
            <i class="fa-solid fa-wifi text-emerald-500 mr-1"></i> Pastikan Anda terhubung ke internet untuk login pertama kali.
        </div>
    </div>

    <!-- Panggil Logika Login (Ditambah time() agar browser tidak membaca cache lama) -->
    <script src="ajax.js?v=<?= time() ?>"></script>

</body>
</html>