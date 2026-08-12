<?php
require_once '../../../config/auth.php';
$page_title = "Food Delivery - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="foodDeliveryApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER APPS -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-motorcycle text-amber-300"></i> Food Delivery
                    </h2>
                    <p class="text-[11px] text-blue-200 font-bold mt-0.5">Atur harga jual produk untuk Ojek Online di POS</p>
                </div>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-8 bg-slate-100/50">
            <div class="w-full max-w-4xl mx-auto space-y-6">

                <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200 shadow-sm">
                    <div class="mb-6">
                        <h3 class="text-2xl font-black text-slate-800">Food Delivery</h3>
                        <p class="text-xs font-bold text-slate-400 mt-1">Pilih platform Food Delivery untuk mengatur persentase & harga khusus produk</p>
                    </div>

                    <!-- Loading State -->
                    <div x-show="isLoading" class="flex justify-center py-12">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <!-- List Platforms Grid -->
                    <div x-show="!isLoading" class="space-y-3">
                        <template x-for="p in platforms" :key="p.platform_code">
                            <a :href="'detail.php?platform=' + p.platform_code" 
                               class="group flex items-center justify-between p-4 sm:p-5 rounded-2xl border border-slate-200 hover:border-primary hover:shadow-md transition-all bg-white hover:bg-blue-50/20">
                                
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-xs shrink-0" :class="p.color_class">
                                        <i :class="p.icon_class"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-800 text-base group-hover:text-primary transition-colors" x-text="p.platform_name"></h4>
                                        <p class="text-xs font-bold text-slate-400 mt-0.5" x-text="(p.product_count || 0) + ' Produk diatur'"></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="bg-slate-100 group-hover:bg-primary group-hover:text-white text-slate-600 font-black px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2">
                                        Atur <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('foodDeliveryApp', () => ({
            platforms: [],
            isLoading: true,

            async init() {
                await this.fetchPlatforms();
            },

            async fetchPlatforms() {
                this.isLoading = true;
                try {
                    const res = await fetch('logic.php?action=get_platforms');
                    const result = await res.json();
                    if (result.status === 'success') {
                        this.platforms = result.data || [];
                    }
                } catch(e) {
                    console.error("Gagal menarik daftar platform:", e);
                } finally {
                    this.isLoading = false;
                }
            }
        }));
    });
    </script>
</body>
</html>
