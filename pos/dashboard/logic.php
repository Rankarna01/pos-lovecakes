<?php
// Tampilkan error saat development (matikan saat rilis)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config/database.php'; // Pastikan path ini benar!

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

if ($action === 'get_dashboard_data') {
    
    try {
        // =========================================================================
        // 🚨 PENGATURAN NAMA TABEL & KOLOM DATABASE
        // Sesuaikan dengan yang ada di phpMyAdmin kamu!
        // =========================================================================
        $tabel_transaksi = 'penjualan';      // Nama tabel penjualan/transaksi
        $kolom_tanggal   = 'created_at';     // Kolom tanggal transaksi
        $kolom_total     = 'total_bayar';    // Kolom total uang yang dibayar pelanggan
        $kolom_laba      = 'laba';           // Kolom laba (opsional, set sama dgn total jika belum ada)
        
        $tabel_pelanggan = 'customers_pos';  // (Sesuai screenshotmu)
        // =========================================================================

        $summary = [];

        // 1. DATA PELANGGAN BARU BULAN INI
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$tabel_pelanggan} WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $summary['pelanggan_baru'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 2. DATA PENJUALAN BULAN INI (CURRENT MONTH)
        $stmt_this = $pdo->query("SELECT 
            COUNT(id) as total_transaksi, 
            SUM({$kolom_total}) as total_penjualan, 
            SUM({$kolom_laba}) as laba_kotor 
            FROM {$tabel_transaksi} 
            WHERE MONTH({$kolom_tanggal}) = MONTH(CURRENT_DATE()) AND YEAR({$kolom_tanggal}) = YEAR(CURRENT_DATE())");
        $data_this = $stmt_this->fetch(PDO::FETCH_ASSOC);

        // 3. DATA PENJUALAN BULAN LALU (LAST MONTH) UNTUK PERSENTASE NAIK/TURUN
        $stmt_last = $pdo->query("SELECT 
            COUNT(id) as total_transaksi, 
            SUM({$kolom_total}) as total_penjualan, 
            SUM({$kolom_laba}) as laba_kotor 
            FROM {$tabel_transaksi} 
            WHERE MONTH({$kolom_tanggal}) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR({$kolom_tanggal}) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
        $data_last = $stmt_last->fetch(PDO::FETCH_ASSOC);

        // --- RUMUS MENGHITUNG ANGKA & PERSENTASE ---
        
        // Fungsi pembantu untuk hitung persen
        function hitungPersen($sekarang, $lalu) {
            if ($lalu == 0) return ($sekarang > 0) ? 100 : 0;
            return round((($sekarang - $lalu) / $lalu) * 100, 2);
        }

        $summary['total_penjualan'] = $data_this['total_penjualan'] ?? 0;
        $summary['pct_penjualan']   = hitungPersen($data_this['total_penjualan'], $data_last['total_penjualan']);

        $summary['penjualan_kotor'] = $data_this['total_penjualan'] ?? 0; // Asumsi sama dengan total jika tidak ada PPN terpisah
        $summary['pct_kotor']       = $summary['pct_penjualan'];

        $summary['laba_kotor']      = $data_this['laba_kotor'] ?? 0;
        $summary['pct_laba']        = hitungPersen($data_this['laba_kotor'], $data_last['laba_kotor']);

        $summary['total_transaksi'] = $data_this['total_transaksi'] ?? 0;
        $summary['pct_transaksi']   = hitungPersen($data_this['total_transaksi'], $data_last['total_transaksi']);

        // Asumsi Laba Bersih = Laba Kotor (Bisa kamu kurangi tabel beban pengeluaran nanti)
        $summary['laba_bersih']     = $summary['laba_kotor'];


        // =========================================================================
        // 4. DATA GRAFIK 7 HARI TERAKHIR (DINAMIS)
        // =========================================================================
        
        // Ambil data dari database ditarik berdasarkan tanggal
        $stmt_chart = $pdo->query("SELECT DATE({$kolom_tanggal}) as tgl, SUM({$kolom_total}) as harian 
                                   FROM {$tabel_transaksi} 
                                   WHERE {$kolom_tanggal} >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY) 
                                   GROUP BY DATE({$kolom_tanggal})");
        
        $data_harian = [];
        while($row = $stmt_chart->fetch(PDO::FETCH_ASSOC)) {
            $data_harian[$row['tgl']] = $row['harian'];
        }

        $chart_labels = [];
        $chart_values = [];

        // Looping mundur dari 6 hari yang lalu sampai hari ini (Mencegah chart putus kalau hari itu jualan kosong)
        for ($i = 6; $i >= 0; $i--) {
            $date_string = date('Y-m-d', strtotime("-$i days")); // Format YYYY-MM-DD
            $label_indo = date('d M', strtotime("-$i days"));    // Format 14 May
            
            $chart_labels[] = $label_indo;
            // Jika di tanggal itu ada penjualan, masukkan angkanya. Jika nol jualan, set 0.
            $chart_values[] = $data_harian[$date_string] ?? 0; 
        }

        $chart_data = [
            'labels' => $chart_labels,
            'values' => $chart_values
        ];

        // Output JSON untuk ditangkap oleh Alpine/Ajax
        echo json_encode([
            'status' => 'success',
            'summary' => $summary,
            'chart' => $chart_data
        ]);

    } catch (PDOException $e) {
        // Jika nama tabel/kolom salah, error ini akan muncul di inspect element (Console)
        echo json_encode(['status' => 'error', 'message' => 'Query Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak ditemukan']);
exit;
?>