<?php
require_once '../../../config/auth.php';
if (!defined('BASE_URL')) {
    $is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $folder = $is_localhost ? '/pos-lovecakes/' : '/';
    if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
}
$page_title = "Data Pelanggan & WhatsApp CRM - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
    <style>
        .wa-bubble {
            background-color: #dcf8c6;
            border-radius: 16px 16px 4px 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            position: relative;
        }
        .wa-bubble::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: -6px;
            width: 0;
            height: 0;
            border-left: 8px solid #dcf8c6;
            border-top: 8px solid transparent;
        }
        .tag-pill {
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }
        .tag-pill:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="customerApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-users mr-2"></i>Data Pelanggan & CRM</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="fetchData()" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Refresh Data">
                    <i class="fa-solid fa-rotate" :class="isLoading ? 'fa-spin' : ''"></i> 
                </button>
                <div class="border-l border-blue-400 pl-4 ml-2">
                    <button onclick="logoutSistem()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div class="w-full max-w-full space-y-6">
                
                <!-- Page Header (Title & Actions) -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
                            <span>Data Pelanggan & CRM</span>
                        </h2>
                        <p class="text-sm text-slate-500 font-medium mt-1">Kelola data pelanggan, tanggal ulang tahun, point loyalitas & kirim pesan WhatsApp dinamis.</p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                        <input type="file" x-ref="importFile" @change="handleImport($event)" accept=".csv" class="hidden">
                        
                        <!-- Tombol Kelola Template WA -->
                        <button @click="openTemplateManager()" class="w-full sm:w-auto bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                            <i class="fa-solid fa-wand-magic-sparkles text-indigo-600"></i>
                            <span>Template WA</span>
                        </button>

                        <div class="relative w-full sm:w-auto" x-data="{ openOptions: false }">
                            <button @click="openOptions = !openOptions" @click.outside="openOptions = false" class="w-full sm:w-auto bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                                <i class="fa-solid fa-file-excel text-emerald-600"></i>
                                <span>Excel CSV</span>
                                <i class="fa-solid fa-chevron-down text-[10px] ml-1 opacity-60" :class="openOptions ? 'rotate-180' : ''" style="transition: transform 0.2s;"></i>
                            </button>
                            
                            <div x-show="openOptions" x-transition.opacity.duration.200ms class="absolute right-0 sm:right-0 left-0 sm:left-auto mt-2 w-full sm:w-56 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-20" style="display: none;">
                                <div class="p-2 space-y-1">
                                    <a href="logic.php?action=export_csv" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-emerald-50 hover:text-emerald-700 transition-colors text-sm font-bold text-slate-700 group">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                                            <i class="fa-solid fa-file-export"></i>
                                        </div>
                                        Export Data
                                    </a>
                                    <button @click="$refs.importFile.click(); openOptions = false" class="w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-blue-50 hover:text-blue-700 transition-colors text-sm font-bold text-slate-700 group">
                                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                            <i class="fa-solid fa-file-import"></i>
                                        </div>
                                        Import Data
                                    </button>
                                </div>
                                <div class="border-t border-slate-100 bg-slate-50 p-2">
                                    <a href="logic.php?action=download_template" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-slate-200 transition-colors text-[11px] font-bold text-slate-500 justify-center">
                                        <i class="fa-solid fa-download"></i> Unduh Template CSV
                                    </a>
                                </div>
                            </div>
                        </div>

                        <button @click="openModal()" class="w-full sm:w-auto bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2 shadow-sm shadow-primary/30">
                            <i class="fa-solid fa-plus"></i> Tambah Pelanggan
                        </button>
                    </div>
                </div>

                <!-- STATS & QUICK FILTER BAR -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <button @click="filterTab = 'all'" :class="filterTab === 'all' ? 'bg-primary text-white border-primary shadow-md shadow-primary/20' : 'bg-white text-slate-700 border-slate-200 hover:border-blue-400'" class="p-3.5 rounded-2xl border transition-all text-left flex items-center justify-between">
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-wider opacity-75">Semua Member</span>
                            <span class="text-xl font-black" x-text="stats.total"></span>
                        </div>
                        <i class="fa-solid fa-users text-2xl opacity-40"></i>
                    </button>

                    <button @click="filterTab = 'birthday_today'" :class="filterTab === 'birthday_today' ? 'bg-rose-500 text-white border-rose-500 shadow-md shadow-rose-500/20' : 'bg-white text-slate-700 border-slate-200 hover:border-rose-400'" class="p-3.5 rounded-2xl border transition-all text-left flex items-center justify-between relative overflow-hidden">
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-wider opacity-75 flex items-center gap-1">
                                <span>🎂 Ulang Tahun Hari Ini</span>
                            </span>
                            <span class="text-xl font-black" x-text="stats.birthday_today"></span>
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-cake-candles text-2xl opacity-40" :class="stats.birthday_today > 0 ? 'text-rose-400' : ''"></i>
                            <span x-show="stats.birthday_today > 0" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-500 rounded-full animate-ping"></span>
                        </div>
                    </button>

                    <button @click="filterTab = 'birthday_month'" :class="filterTab === 'birthday_month' ? 'bg-amber-500 text-white border-amber-500 shadow-md shadow-amber-500/20' : 'bg-white text-slate-700 border-slate-200 hover:border-amber-400'" class="p-3.5 rounded-2xl border transition-all text-left flex items-center justify-between">
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-wider opacity-75">🎂 Ulang Tahun Bulan Ini</span>
                            <span class="text-xl font-black" x-text="stats.birthday_month"></span>
                        </div>
                        <i class="fa-solid fa-gift text-2xl opacity-40"></i>
                    </button>

                    <button @click="filterTab = 'top_points'" :class="filterTab === 'top_points' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20' : 'bg-white text-slate-700 border-slate-200 hover:border-indigo-400'" class="p-3.5 rounded-2xl border transition-all text-left flex items-center justify-between">
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-wider opacity-75">⭐ Loyalitas Tertinggi</span>
                            <span class="text-xs font-bold mt-1 block opacity-90">Urutkan Poin</span>
                        </div>
                        <i class="fa-solid fa-medal text-2xl opacity-40"></i>
                    </button>
                </div>

                <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-200 overflow-hidden relative">
                    <!-- Search Bar Toolbar -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="relative w-full">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" x-model="searchQuery" placeholder="Cari nama, WhatsApp, alamat, atau catatan pelanggan..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 hover:bg-slate-100 focus:bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-bold text-sm text-slate-700 transition-colors">
                        </div>
                        <div class="shrink-0 text-xs font-bold text-slate-400 flex items-center gap-2">
                            <span x-text="filteredData.length + ' data ditemukan'"></span>
                            <button x-show="filterTab !== 'all' || searchQuery" @click="filterTab = 'all'; searchQuery = ''" class="text-rose-500 hover:underline">Reset Filter</button>
                        </div>
                    </div>

                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-sm">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary"></i>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 font-black">Pelanggan</th>
                                    <th class="p-4 font-black">🎂 Tanggal Lahir / Ulang Tahun</th>
                                    <th class="p-4 font-black">HP / WhatsApp</th>
                                    <th class="p-4 font-black">Alamat & Catatan</th>
                                    <th class="p-4 font-black text-center">Point Loyalitas</th>
                                    <th class="p-4 font-black text-center w-28"><i class="fa-solid fa-gear"></i> Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr x-show="paginatedData.length === 0">
                                    <td colspan="6" class="p-10 text-center">
                                        <div class="text-slate-300 text-5xl mb-3"><i class="fa-solid fa-id-card-clip"></i></div>
                                        <p class="text-slate-500 font-bold">Belum ada data pelanggan yang sesuai dengan filter.</p>
                                    </td>
                                </tr>

                                <template x-for="item in paginatedData" :key="item.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors group">
                                        <!-- Kolom Pelanggan -->
                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <div class="font-black text-slate-800 text-base" x-text="item.name"></div>
                                                <span x-show="item.is_birthday_today" class="inline-flex items-center gap-1 bg-rose-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full animate-bounce">
                                                    <span>🎉 HARI INI!</span>
                                                </span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter" x-text="'Member ID: #' + String(item.id).padStart(4, '0')"></div>
                                        </td>

                                        <!-- Kolom Ulang Tahun -->
                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                                    <i class="fa-solid fa-cake-candles text-xs"></i>
                                                </div>
                                                <div>
                                                    <span class="font-bold text-slate-700 block text-xs" x-text="item.formatted_birth_date"></span>
                                                    <span x-show="item.age" class="text-[10px] text-slate-400 font-medium" x-text="'Usia: ' + item.age + ' tahun'"></span>
                                                    <span x-show="!item.birth_date" class="text-[10px] text-slate-400 italic">Belum dicatat</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kolom HP / WhatsApp -->
                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-slate-700 text-xs" x-text="item.phone || '-'"></span>
                                                <button x-show="item.phone" @click="openWAModal(item)" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white border border-emerald-200 hover:border-emerald-600 text-[11px] font-black transition-all flex items-center gap-1 shadow-2xs" title="Kirim Pesan WhatsApp">
                                                    <i class="fa-brands fa-whatsapp text-sm"></i>
                                                    <span>Chat WA</span>
                                                </button>
                                            </div>
                                        </td>

                                        <!-- Kolom Alamat & Catatan -->
                                        <td class="p-4 max-w-xs">
                                            <div class="text-xs font-semibold text-slate-600 truncate" x-text="item.address || 'Alamat tidak diisi'"></div>
                                            <div x-show="item.custom_notes" class="text-[10px] text-amber-700 bg-amber-50/80 px-2 py-0.5 rounded mt-0.5 truncate border border-amber-200/50" x-text="'📝 ' + item.custom_notes"></div>
                                        </td>

                                        <!-- Kolom Point Loyalitas -->
                                        <td class="p-4 text-center">
                                            <div class="inline-flex flex-col items-center">
                                                <span class="bg-amber-50 border border-amber-200 text-amber-600 px-3 py-1.5 rounded-xl text-xs font-black shadow-sm flex items-center gap-1.5">
                                                    <i class="fa-solid fa-medal text-sm"></i>
                                                    <span x-text="item.points"></span>
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Kolom Aksi -->
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Tombol Kirim Ucapan Cepat Jika Ultah -->
                                                <button x-show="item.phone && item.is_birthday_today" @click="openWAModal(item)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors flex items-center justify-center animate-pulse" title="Kirim Ucapan Ultah"><i class="fa-solid fa-gift text-xs"></i></button>

                                                <button @click="openModal(item)" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors flex items-center justify-center" title="Edit Data"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                                                <button @click="hapusData(item.id)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors flex items-center justify-center" title="Hapus"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50" x-show="totalPages > 1">
                        <div class="text-xs font-bold text-slate-500">
                            Menampilkan <span class="text-primary font-black" x-text="paginatedData.length"></span> dari <span class="text-slate-800 font-black" x-text="filteredData.length"></span> pelanggan
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 font-bold text-xs shadow-sm">Prev</button>
                            <span class="px-4 py-1.5 rounded-lg bg-primary text-white font-black text-xs shadow-sm" x-text="currentPage + ' / ' + totalPages"></span>
                            <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 font-bold text-xs shadow-sm">Next</button>
                        </div>
                    </div>
                </div>

                <div class="h-10"></div>
            </div>
        </main>

        <!-- ============================================================= -->
        <!-- 📱 MODAL 1: KIRIM PESAN WHATSAPP (SCRIPTING DINAMIS CRM)       -->
        <!-- ============================================================= -->
        <div x-show="showWAModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="closeWAModal()"></div>
            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[92vh] overflow-hidden border border-slate-200">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center text-xl">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-lg leading-tight">Kirim WhatsApp (Scripting Dinamis)</h3>
                            <p class="text-xs text-white/80 font-medium">Redirect pesan personal dari nomor admin ke WhatsApp pelanggan.</p>
                        </div>
                    </div>
                    <button @click="closeWAModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5 bg-slate-50/50">
                    
                    <!-- Customer Summary Banner -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-sm">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800 text-sm" x-text="activeCustomer?.name"></h4>
                                <p class="text-xs text-slate-500 font-bold flex items-center gap-2">
                                    <span x-text="activeCustomer?.phone"></span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-amber-600 flex items-center gap-1"><i class="fa-solid fa-medal"></i> <span x-text="activeCustomer?.points + ' Poin'"></span></span>
                                </p>
                            </div>
                        </div>

                        <div x-show="activeCustomer?.is_birthday_today" class="bg-rose-50 border border-rose-200 text-rose-700 px-3 py-1.5 rounded-xl text-xs font-black flex items-center gap-1.5 animate-pulse">
                            <i class="fa-solid fa-cake-candles text-rose-500"></i>
                            <span>Ulang Tahun Hari Ini! 🎉</span>
                        </div>
                    </div>

                    <!-- Template Selector -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider">Pilih Template Pesan:</label>
                            <button type="button" @click="openTemplateManager()" class="text-[11px] font-bold text-indigo-600 hover:underline flex items-center gap-1">
                                <i class="fa-solid fa-gear text-[10px]"></i> Kelola Template
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <template x-for="tpl in templates" :key="tpl.id">
                                <button type="button" @click="applyTemplate(tpl.id)" :class="selectedTemplateId == tpl.id ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-500/20 font-black' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300 font-bold'" class="p-3 rounded-xl border text-left text-xs transition-all flex items-center justify-between">
                                    <span x-text="tpl.title" class="truncate"></span>
                                    <i x-show="selectedTemplateId == tpl.id" class="fa-solid fa-circle-check text-emerald-600 ml-2 shrink-0"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Variable Tags Chips -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Klik Tag untuk Menambahkan Variabel Dinamis:</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" @click="insertTag('{nama}')" class="tag-pill px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-bold">+ {nama}</button>
                            <button type="button" @click="insertTag('{poin}')" class="tag-pill px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold">+ {poin}</button>
                            <button type="button" @click="insertTag('{toko}')" class="tag-pill px-2.5 py-1 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-bold">+ {toko}</button>
                            <button type="button" @click="insertTag('{tgl_lahir}')" class="tag-pill px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold">+ {tgl_lahir}</button>
                            <button type="button" @click="insertTag('{member_id}')" class="tag-pill px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 text-xs font-bold">+ {member_id}</button>
                            <button type="button" @click="insertTag('{alamat}')" class="tag-pill px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 text-xs font-bold">+ {alamat}</button>
                        </div>
                    </div>

                    <!-- Script Editor Textarea -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Editor Pesan WhatsApp (Script):</label>
                        <textarea id="waTextarea" x-model="waMessageText" rows="5" placeholder="Tulis pesan..." class="w-full bg-white border border-slate-200 rounded-2xl p-3.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium text-sm text-slate-800 custom-scrollbar"></textarea>
                    </div>

                    <!-- Live Rendered Preview -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-eye text-emerald-500"></i>
                            <span>Live Preview (Tampilan Pesan WhatsApp di Layar Pelanggan):</span>
                        </label>
                        <div class="bg-[#efeae2] p-4 rounded-2xl border border-slate-200/80">
                            <div class="wa-bubble p-3.5 text-xs text-slate-800 whitespace-pre-wrap font-sans leading-relaxed" x-text="renderedWAMessage"></div>
                            <div class="text-[9px] text-slate-400 text-right mt-1.5 font-bold flex items-center justify-end gap-1">
                                <span x-text="new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                <i class="fa-solid fa-check-double text-blue-500"></i>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="p-5 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row justify-between items-center gap-3 shrink-0">
                    <p class="text-[11px] text-slate-400 font-bold">
                        <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i>
                        Akan membuka tab WhatsApp Web / WhatsApp Desktop otomatis.
                    </p>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button type="button" @click="closeWAModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-bold text-slate-500 hover:bg-slate-200 transition-colors text-xs">
                            Batal
                        </button>
                        <button type="button" @click="sendWhatsAppRedirect()" class="w-full sm:w-auto px-6 py-2.5 rounded-xl font-black bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 transition-all flex items-center justify-center gap-2 text-xs">
                            <i class="fa-brands fa-whatsapp text-base"></i>
                            <span>Buka WhatsApp (Kirim)</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- ============================================================= -->
        <!-- ⚙️ MODAL 2: KELOLA TEMPLATE PESAN WHATSAPP                     -->
        <!-- ============================================================= -->
        <div x-show="showTemplateManagerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="closeTemplateManager()"></div>
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] overflow-hidden border border-slate-200">
                
                <div class="p-5 border-b border-slate-100 bg-indigo-600 text-white flex justify-between items-center shrink-0">
                    <h3 class="font-black text-lg flex items-center gap-2"><i class="fa-solid fa-wand-magic-sparkles"></i> Kelola Template WhatsApp</h3>
                    <button @click="closeTemplateManager()" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5">
                    
                    <!-- Form Tambah / Edit Template -->
                    <div class="bg-indigo-50/60 border border-indigo-200/80 p-4 rounded-2xl space-y-3">
                        <h4 class="font-black text-xs text-indigo-900 uppercase tracking-wider" x-text="isEditingTemplate ? '✏️ Edit Template' : '➕ Tambah Template Baru'"></h4>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Judul Template</label>
                            <input type="text" x-model="templateForm.title" placeholder="Misal: 🎂 Ucapan Ulang Tahun..." class="w-full bg-white border border-indigo-200 rounded-xl px-3 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Isi Pesan (Gunakan tag {nama}, {poin}, {toko}, {tgl_lahir})</label>
                            <textarea x-model="templateForm.template_text" rows="4" placeholder="Tulis template..." class="w-full bg-white border border-indigo-200 rounded-xl p-3 text-xs font-medium outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-1">
                            <button x-show="isEditingTemplate" type="button" @click="cancelEditTemplate()" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 bg-white hover:bg-slate-100">Batal</button>
                            <button type="button" @click="saveTemplate()" :disabled="isSavingTemplate" class="px-4 py-1.5 rounded-lg text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span x-text="isEditingTemplate ? 'Perbarui Template' : 'Simpan Template'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Daftar Template Tersimpan -->
                    <div>
                        <h4 class="font-black text-xs text-slate-500 uppercase tracking-wider mb-2">Daftar Template Tersedia:</h4>
                        <div class="space-y-2">
                            <template x-for="t in templates" :key="t.id">
                                <div class="bg-white border border-slate-200 p-3.5 rounded-xl shadow-2xs flex justify-between items-start gap-3">
                                    <div class="overflow-hidden">
                                        <div class="font-black text-slate-800 text-xs flex items-center gap-1.5">
                                            <span x-text="t.title"></span>
                                            <span x-show="t.is_default == 1" class="text-[9px] bg-emerald-100 text-emerald-700 px-1.5 py-0.2 rounded font-bold">Default</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 line-clamp-2 mt-1 whitespace-pre-wrap" x-text="t.template_text"></p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button @click="editTemplate(t)" class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors flex items-center justify-center text-xs"><i class="fa-solid fa-pen"></i></button>
                                        <button @click="deleteTemplate(t.id)" class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors flex items-center justify-center text-xs"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                    <button @click="closeTemplateManager()" class="px-5 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-200 hover:bg-slate-300">Tutup</button>
                </div>

            </div>
        </div>

        <!-- ============================================================= -->
        <!-- 📝 MODAL 3: TAMBAH / UBAH DATA PELANGGAN                       -->
        <!-- ============================================================= -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal()"></div>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 flex flex-col max-h-[90vh] overflow-hidden m-4 transform transition-all">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-lg text-slate-800" x-text="isEdit ? 'Ubah Data Pelanggan' : 'Daftar Pelanggan Baru'"></h3>
                    <button @click="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="form.name" placeholder="Nama pelanggan..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Nomor WhatsApp / HP</label>
                            <input type="text" x-model="form.phone" placeholder="08xxx..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">🎂 Tanggal Lahir / Ultah</label>
                            <input type="date" x-model="form.birth_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm text-slate-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Alamat Lengkap</label>
                        <textarea x-model="form.address" rows="2" placeholder="Alamat rumah / domisili..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm custom-scrollbar"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase">Catatan Khusus Pelanggan</label>
                        <input type="text" x-model="form.custom_notes" placeholder="Misal: Suka rasa cokelat, langganan hampers kantor..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:bg-white focus:border-primary font-bold text-sm">
                    </div>

                    <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200">
                        <label class="block text-[11px] font-black text-amber-600 mb-1.5 uppercase">Total Point Loyalitas</label>
                        <div class="flex items-center gap-3">
                            <input type="number" x-model="form.points" class="w-full bg-white border border-amber-300 rounded-xl px-4 py-2 outline-none focus:border-amber-500 font-black text-lg text-amber-600">
                            <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center text-xl shadow-sm"><i class="fa-solid fa-medal"></i></div>
                        </div>
                        <p class="text-[10px] font-bold text-amber-500 mt-2 italic">* Poin bertambah otomatis setiap transaksi selesai dan dapat ditukar diskon di kasir.</p>
                    </div>
                </div>

                <div class="p-5 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button @click="closeModal()" class="px-6 py-2.5 rounded-xl font-bold text-slate-500 hover:bg-slate-200 transition-colors">Batal</button>
                    <button @click="simpanData()" class="px-6 py-2.5 rounded-xl font-black bg-primary hover:bg-blue-700 text-white shadow-md shadow-primary/30 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Data
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>