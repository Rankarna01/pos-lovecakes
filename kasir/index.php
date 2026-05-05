<?php
require_once '../config/auth.php';
// checkLogin(); // Uncomment ini nanti kalau sistem login sudah dibuat
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../components/header.php'; ?>
</head>
<body class="flex flex-col h-screen overflow-hidden" x-data="posKasir()">
    
    <!-- Navbar Atas -->
    <header class="bg-surface shadow-sm border-b border-slate-200 px-6 py-4 flex justify-between items-center z-10 shrink-0">
        <h1 class="text-2xl font-black text-primary">KASIR POS</h1>
        
        <div class="flex items-center gap-4">
            <!-- Indikator Jaringan -->
            <template x-if="isOnline">
                <div class="bg-success/10 text-success px-4 py-1.5 rounded-full text-xs font-bold border border-success/20 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span> ONLINE
                </div>
            </template>
            <template x-if="!isOnline">
                <div class="bg-danger/10 text-danger px-4 py-1.5 rounded-full text-xs font-bold border border-danger/20 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-danger animate-pulse"></span> OFFLINE MODE
                </div>
            </template>
            
            <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-black">
                A
            </div>
        </div>
    </header>

    <!-- Area Konten (Katalog Kiri, Keranjang Kanan) -->
    <main class="flex-1 flex overflow-hidden">
        <!-- KATALOG KIRI -->
        <div class="flex-1 p-6 overflow-y-auto bg-background">
            <h2 class="font-bold text-slate-800 mb-4">Pilih Produk</h2>
            <button @click="testNotif" class="bg-primary text-surface px-4 py-2 rounded-xl font-bold shadow-sm">Test Tombol SweetAlert</button>
        </div>

        <!-- KERANJANG KANAN -->
        <div class="w-96 bg-surface border-l border-slate-200 flex flex-col shadow-lg z-10">
            <div class="p-4 border-b border-slate-100 bg-slate-50">
                <h3 class="font-black text-slate-800 text-lg"><i class="fa-solid fa-cart-shopping text-primary mr-2"></i> Keranjang</h3>
            </div>
            <div class="flex-1 p-4 overflow-y-auto">
                <p class="text-center text-secondary text-sm mt-10">Keranjang masih kosong.</p>
            </div>
            <div class="p-4 border-t border-slate-200 bg-surface">
                <button class="w-full bg-primary hover:opacity-90 text-surface py-3 rounded-xl font-black text-lg transition-all shadow-md">
                    BAYAR SEKARANG
                </button>
            </div>
        </div>
    </main>

    <script src="ajax.js"></script>
</body>
</html>