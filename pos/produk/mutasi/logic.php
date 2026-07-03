<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get_init_data') {
    try {
        // Ambil daftar gudang / store
        $warehouses = [];
        try {
            $warehouses = $pdo->query("SELECT id, name FROM warehouses ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        
        if (empty($warehouses)) {
            $warehouses = [
                ['id' => 1, 'name' => 'Store 01'],
                ['id' => 2, 'name' => 'Store 02']
            ];
        } else {
            foreach ($warehouses as &$wh) {
                $wh['name'] = str_ireplace('gudang', 'Store', $wh['name']);
            }
            unset($wh);
        }

        // Ambil semua produk
        $products = $pdo->query("SELECT id, code, name, category, stock FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Ambil stok per warehouse
        $pws_rows = [];
        try {
            $pws_rows = $pdo->query("SELECT product_id, warehouse_id, stock FROM product_warehouse_stocks")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        $stock_map = [];
        foreach ($pws_rows as $r) {
            $stock_map[$r['product_id']][$r['warehouse_id']] = (int)$r['stock'];
        }

        foreach ($products as &$prod) {
            $pid = $prod['id'];
            $prod['wh_stocks'] = $stock_map[$pid] ?? [];
        }
        unset($prod);

        // Ambil riwayat mutasi
        $mutations = $pdo->query("
            SELECT pm.*, 
                   p.code AS product_code, 
                   p.name AS product_name, 
                   COALESCE(w1.name, CONCAT('Store #', pm.from_warehouse_id)) AS from_store_name, 
                   COALESCE(w2.name, CONCAT('Store #', pm.to_warehouse_id)) AS to_store_name
            FROM product_mutations pm
            JOIN products p ON pm.product_id = p.id
            LEFT JOIN warehouses w1 ON pm.from_warehouse_id = w1.id
            LEFT JOIN warehouses w2 ON pm.to_warehouse_id = w2.id
            ORDER BY pm.created_at DESC LIMIT 150
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mutations as &$mut) {
            $mut['from_store_name'] = str_ireplace('gudang', 'Store', $mut['from_store_name']);
            $mut['to_store_name'] = str_ireplace('gudang', 'Store', $mut['to_store_name']);
        }
        unset($mut);

        echo json_encode([
            'status' => 'success',
            'products' => $products,
            'warehouses' => $warehouses,
            'mutations' => $mutations
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'submit_mutation') {
    try {
        $product_id = intval($_POST['product_id'] ?? 0);
        $from_wh = intval($_POST['from_warehouse_id'] ?? 0);
        $to_wh = intval($_POST['to_warehouse_id'] ?? 0);
        $qty = intval($_POST['quantity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $user_id = $_SESSION['pos_user_id'] ?? null;

        if ($product_id <= 0 || $from_wh <= 0 || $to_wh <= 0 || $qty <= 0) {
            throw new Exception("Lengkapi produk, store asal, store tujuan, dan jumlah mutasi dengan benar.");
        }
        if ($from_wh === $to_wh) {
            throw new Exception("Store Asal dan Store Tujuan tidak boleh sama!");
        }

        $pdo->beginTransaction();

        $mutation_no = 'MUT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Insert riwayat mutasi
        $stmtIns = $pdo->prepare("INSERT INTO product_mutations (mutation_no, product_id, from_warehouse_id, to_warehouse_id, quantity, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtIns->execute([$mutation_no, $product_id, $from_wh, $to_wh, $qty, $notes, $user_id]);

        // Kurangi stok di gudang asal
        $stmtSub = $pdo->prepare("INSERT INTO product_warehouse_stocks (product_id, warehouse_id, stock) VALUES (?, ?, -?) ON DUPLICATE KEY UPDATE stock = stock - ?");
        $stmtSub->execute([$product_id, $from_wh, $qty, $qty]);

        // Tambah stok di gudang tujuan
        $stmtAdd = $pdo->prepare("INSERT INTO product_warehouse_stocks (product_id, warehouse_id, stock) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE stock = stock + ?");
        $stmtAdd->execute([$product_id, $to_wh, $qty, $qty]);

        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => "Stok berhasil dimutasikan sebanyak $qty ke Store tujuan!"]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
