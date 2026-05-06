<?php
session_start();
// Naik 3 folder ke root untuk panggil config
require_once '../../../config/database.php'; 

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_inventory') {
    try {
        // 1. DATA BARANG MASUK (Dari Produksi yang sudah Masuk Gudang)
        $stmtIn = $pdo->query("
            SELECT 
                p.created_at AS tanggal, 
                p.invoice_no AS referensi, 
                pr.code AS kode_produk, 
                pr.name AS produk, 
                pd.quantity AS qty, 
                'Masuk' AS tipe,
                'Produksi (Dapur)' AS sumber
            FROM productions p
            JOIN production_details pd ON p.id = pd.production_id
            JOIN products pr ON pd.product_id = pr.id
            WHERE p.status = 'masuk_gudang'
            ORDER BY p.created_at DESC
        ");
        $incoming = $stmtIn->fetchAll(PDO::FETCH_ASSOC);

        // 2. DATA BARANG KELUAR (Dari tabel product_outs atau penjualan)
        $outgoing = [];
        try {
            // Asumsi menggunakan tabel product_outs yang ada di database kamu
            // Jika kolomnya berbeda, cukup sesuaikan aliasnya
            $stmtOut = $pdo->query("
                SELECT 
                    po.created_at AS tanggal, 
                    po.invoice_no AS referensi, 
                    pr.code AS kode_produk, 
                    pr.name AS produk, 
                    po.quantity AS qty, 
                    'Keluar' AS tipe,
                    COALESCE(po.notes, 'Penjualan POS') AS sumber
                FROM product_outs po
                JOIN products pr ON po.product_id = pr.id
                ORDER BY po.created_at DESC
            ");
            $outgoing = $stmtOut->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Blok ini mencegah error fatal jika tabel product_outs belum sempurna
            $outgoing = []; 
        }

        echo json_encode([
            'status' => 'success',
            'data_masuk' => $incoming,
            'data_keluar' => $outgoing
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}
?>