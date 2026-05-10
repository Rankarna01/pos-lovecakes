<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? '/pos-lovecakes/' : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Rekap QRIS & E-Wallet - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="{}" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 class="text-xl font-black tracking-wide"><i class="fa-solid fa-qrcode mr-2"></i>Rekap Pembayaran Digital</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/50">
            <div class="max-w-7xl mx-auto space-y-4">
                
                <div class="bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-200 mb-4">
                    <h3 class="text-lg font-black text-slate-800 mb-2">Settlement / Pencairan Dana</h3>
                    <p class="text-sm font-bold text-slate-500 mb-4">Pantau mutasi transaksi Non-Tunai. Centang transaksi yang dananya sudah masuk ke Rekening Bank Toko.</p>
                    
                    <div class="flex flex-wrap gap-2">
                        <button class="bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold">Semua Provider</button>
                        <button class="bg-slate-100 text-slate-500 hover:bg-slate-200 px-4 py-2 rounded-xl text-xs font-bold border border-slate-200">QRIS Bank</button>
                        <button class="bg-slate-100 text-slate-500 hover:bg-slate-200 px-4 py-2 rounded-xl text-xs font-bold border border-slate-200">GrabFood</button>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 text-center"><input type="checkbox" class="w-4 h-4 rounded border-slate-300"></th>
                                    <th class="p-4 font-black">Invoice</th>
                                    <th class="p-4 font-black text-center">Provider</th>
                                    <th class="p-4 font-black text-right">Nominal Tagihan</th>
                                    <th class="p-4 font-black text-center">Status Cair</th>
                                    <th class="p-4 font-black text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50 bg-rose-50/20">
                                    <td class="p-4 text-center"><input type="checkbox" class="w-4 h-4 rounded border-slate-300"></td>
                                    <td class="p-4"><div class="font-black text-slate-800">INV-260508-012</div><div class="text-[10px] font-bold text-slate-500">08 Mei 2026</div></td>
                                    <td class="p-4 text-center"><span class="bg-blue-50 text-blue-600 px-3 py-1 rounded text-xs font-black border border-blue-200">QRIS BCA</span></td>
                                    <td class="p-4 text-right font-black text-primary">Rp 45.000</td>
                                    <td class="p-4 text-center"><span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase bg-slate-100 text-slate-500 border border-slate-200"><i class="fa-solid fa-clock rotate-180"></i> Belum Cair</span></td>
                                    <td class="p-4 text-center"><button class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-[10px] font-black shadow-sm">Tandai Cair</button></td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 text-center"><i class="fa-solid fa-check text-emerald-500"></i></td>
                                    <td class="p-4"><div class="font-black text-slate-800">ONL-260507-991</div><div class="text-[10px] font-bold text-slate-500">07 Mei 2026</div></td>
                                    <td class="p-4 text-center"><span class="bg-emerald-50 text-emerald-600 px-3 py-1 rounded text-xs font-black border border-emerald-200">GrabFood</span></td>
                                    <td class="p-4 text-right font-black text-primary">Rp 120.000</td>
                                    <td class="p-4 text-center"><span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase bg-emerald-100 text-emerald-600 border border-emerald-200"><i class="fa-solid fa-check"></i> Sudah Masuk Rekening</span></td>
                                    <td class="p-4 text-center"><button class="bg-slate-100 text-slate-400 px-3 py-1.5 rounded-lg text-[10px] font-black cursor-not-allowed" disabled>Selesai</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>