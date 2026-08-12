<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// Ensure DB Tables exist & add warehouse_id column if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS food_delivery_platforms_pos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform_code VARCHAR(50) NOT NULL UNIQUE,
        platform_name VARCHAR(100) NOT NULL,
        icon_class VARCHAR(100) DEFAULT 'fa-solid fa-utensils',
        color_class VARCHAR(50) DEFAULT 'bg-slate-500',
        default_markup_percent DECIMAL(5,2) DEFAULT 30.00,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS food_delivery_prices_pos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform_code VARCHAR(50) NOT NULL,
        warehouse_id INT NULL,
        item_type ENUM('product', 'custom_reguler', 'custom_po') DEFAULT 'product',
        item_id INT NOT NULL,
        markup_percent DECIMAL(5,2) DEFAULT 30.00,
        override_price DECIMAL(15,2) NULL,
        final_price DECIMAL(15,2) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_platform_item (platform_code, item_type, item_id)
    )");

    try { $pdo->exec("ALTER TABLE food_delivery_prices_pos ADD COLUMN IF NOT EXISTS warehouse_id INT NULL"); } catch (Exception $e) {}

    // Insert Default Platforms if empty
    $chk = $pdo->query("SELECT COUNT(*) FROM food_delivery_platforms_pos")->fetchColumn();
    if ($chk == 0) {
        $stmt_init = $pdo->prepare("INSERT INTO food_delivery_platforms_pos (platform_code, platform_name, icon_class, color_class, default_markup_percent) VALUES (?, ?, ?, ?, ?)");
        $stmt_init->execute(['gofood', 'GoFood', 'fa-solid fa-utensils', 'text-rose-500 bg-rose-50 border-rose-200', 30.00]);
        $stmt_init->execute(['grabfood', 'GrabFood', 'fa-solid fa-motorcycle', 'text-emerald-500 bg-emerald-50 border-emerald-200', 30.00]);
        $stmt_init->execute(['shopeefood', 'ShopeeFood', 'fa-solid fa-bag-shopping', 'text-orange-500 bg-orange-50 border-orange-200', 30.00]);
        $stmt_init->execute(['travelokaeats', 'TravelokaEats', 'fa-solid fa-plane-departure', 'text-sky-500 bg-sky-50 border-sky-200', 30.00]);
    }
} catch (Exception $e) {}

// =====================================================
// 0. GET STORES (MULTI-TENANT / WAREHOUSES)
// =====================================================
if ($action === 'get_stores') {
    try {
        $stores = [];
        try {
            $stores = $pdo->query("SELECT id, name FROM warehouses ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $stores = [
                ['id' => 1, 'name' => 'Store 01'],
                ['id' => 2, 'name' => 'Store 02']
            ];
        }

        foreach ($stores as &$s) {
            $s['name'] = str_ireplace('gudang', 'Store', $s['name']);
        }
        unset($s);

        echo json_encode(['status' => 'success', 'data' => $stores]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 1. GET ALL PLATFORMS WITH PRODUCT COUNTS
// =====================================================
if ($action === 'get_platforms') {
    try {
        $platforms = $pdo->query("SELECT * FROM food_delivery_platforms_pos WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM food_delivery_prices_pos WHERE platform_code = ? AND is_active = 1");

        foreach ($platforms as &$p) {
            $cnt_stmt->execute([$p['platform_code']]);
            $p['product_count'] = intval($cnt_stmt->fetchColumn());
        }
        unset($p);

        echo json_encode(['status' => 'success', 'data' => $platforms]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 2. GET ITEMS CONFIGURED FOR A SPECIFIC PLATFORM
// =====================================================
if ($action === 'get_platform_items') {
    try {
        $platform_code = trim($_GET['platform'] ?? 'grabfood');
        $warehouse_id = intval($_GET['warehouse_id'] ?? 0);
        $store_label = $warehouse_id > 0 ? sprintf("Store %02d", $warehouse_id) : 'Store 01';

        $where = "f.platform_code = ?";
        $params = [$platform_code];

        if ($warehouse_id > 0) {
            $where .= " AND (p.warehouse_id = ? OR f.warehouse_id = ? OR p.warehouse_id IS NULL)";
            $params[] = $warehouse_id;
            $params[] = $warehouse_id;
        }

        $stmt = $pdo->prepare("
            SELECT f.id as price_setting_id, f.platform_code, f.item_type, f.item_id, f.markup_percent, f.override_price, f.final_price, f.is_active,
            CASE 
                WHEN f.item_type = 'custom_reguler' THEN cr.name
                WHEN f.item_type = 'custom_po' THEN cp.name
                ELSE p.name
            END AS item_name,
            CASE 
                WHEN f.item_type = 'custom_reguler' THEN CONCAT('CR-', cr.id)
                WHEN f.item_type = 'custom_po' THEN CONCAT('CPO-', cp.id)
                ELSE p.code
            END AS item_code,
            CASE 
                WHEN f.item_type = 'custom_reguler' THEN 'Custom Reguler'
                WHEN f.item_type = 'custom_po' THEN 'Custom PO'
                ELSE COALESCE(p.category, 'Katalog Produk')
            END AS item_category,
            CASE 
                WHEN f.item_type = 'custom_reguler' THEN cr.price
                WHEN f.item_type = 'custom_po' THEN cp.price
                ELSE p.price
            END AS base_price,
            COALESCE(w.name, CASE WHEN p.warehouse_id = 2 THEN 'Store 02' ELSE 'Store 01' END) AS store_name,
            COALESCE(f.warehouse_id, p.warehouse_id, 1) as warehouse_id
            FROM food_delivery_prices_pos f
            LEFT JOIN products p ON f.item_type = 'product' AND f.item_id = p.id
            LEFT JOIN saved_custom_reguler_pos cr ON f.item_type = 'custom_reguler' AND f.item_id = cr.id
            LEFT JOIN saved_custom_items_pos cp ON f.item_type = 'custom_po' AND f.item_id = cp.id
            LEFT JOIN warehouses w ON p.warehouse_id = w.id
            WHERE $where
            ORDER BY f.is_active DESC, item_name ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['store_name'] = str_ireplace('gudang', 'Store', $r['store_name']);
            if ($warehouse_id > 0) {
                $r['store_name'] = $store_label;
                $r['warehouse_id'] = $warehouse_id;
            }
        }
        unset($r);

        echo json_encode(['status' => 'success', 'data' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 3. GET AVAILABLE ITEMS NOT YET IN PLATFORM
// =====================================================
if ($action === 'get_available_items') {
    try {
        $platform_code = trim($_GET['platform'] ?? 'grabfood');
        $warehouse_id = intval($_GET['warehouse_id'] ?? 0);
        $store_label = $warehouse_id > 0 ? sprintf("Store %02d", $warehouse_id) : 'Store 01';

        // All catalog products
        $p_sql = "
            SELECT p.id, p.code, p.name, p.category, p.price, 'product' as item_type,
            COALESCE(w.name, CASE WHEN p.warehouse_id = 2 THEN 'Store 02' ELSE 'Store 01' END) AS store_name,
            COALESCE(p.warehouse_id, 1) as warehouse_id
            FROM products p
            LEFT JOIN warehouses w ON p.warehouse_id = w.id
        ";
        if ($warehouse_id > 0) {
            $p_sql .= " WHERE (p.warehouse_id = $warehouse_id OR p.warehouse_id IS NULL)";
        }
        $prods = $pdo->query($p_sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($prods as &$p) {
            $p['store_name'] = str_ireplace('gudang', 'Store', $p['store_name']);
            if ($warehouse_id > 0) {
                $p['store_name'] = $store_label;
                $p['warehouse_id'] = $warehouse_id;
            }
        }
        unset($p);

        // All custom reguler
        $cr_stmt = $pdo->query("SELECT id, CONCAT('CR-', id) as code, name, 'Custom Reguler' as category, price, 'custom_reguler' as item_type FROM saved_custom_reguler_pos");
        $crs = $cr_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($crs as &$cr) {
            $cr['warehouse_id'] = $warehouse_id > 0 ? $warehouse_id : 1;
            $cr['store_name'] = $store_label;
        }
        unset($cr);

        // All custom PO
        $cp_stmt = $pdo->query("SELECT id, CONCAT('CPO-', id) as code, name, 'Custom PO' as category, price, 'custom_po' as item_type FROM saved_custom_items_pos");
        $cps = $cp_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cps as &$cp) {
            $cp['warehouse_id'] = $warehouse_id > 0 ? $warehouse_id : 1;
            $cp['store_name'] = $store_label;
        }
        unset($cp);

        $all = array_merge($prods, $crs, $cps);

        // Existing platform items
        $ex_stmt = $pdo->prepare("SELECT item_type, item_id FROM food_delivery_prices_pos WHERE platform_code = ?");
        $ex_stmt->execute([$platform_code]);
        $existing = $ex_stmt->fetchAll(PDO::FETCH_ASSOC);
        $ex_map = [];
        foreach ($existing as $ex) {
            $ex_map[$ex['item_type'] . '_' . $ex['item_id']] = true;
        }

        $available = [];
        foreach ($all as $item) {
            $key = $item['item_type'] . '_' . $item['id'];
            $item['is_added'] = isset($ex_map[$key]);
            $available[] = $item;
        }

        echo json_encode(['status' => 'success', 'data' => $available]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 4. ADD SELECTED ITEMS TO PLATFORM
// =====================================================
if ($action === 'add_items_to_platform') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $platform_code = trim($data['platform'] ?? 'grabfood');
        $items = $data['items'] ?? []; // array of {item_type, item_id, price, warehouse_id}
        $markup = floatval($data['markup_percent'] ?? 30.00);

        if (empty($items)) throw new Exception("Tidak ada item yang dipilih.");

        $stmt = $pdo->prepare("
            INSERT INTO food_delivery_prices_pos (platform_code, warehouse_id, item_type, item_id, markup_percent, final_price, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE is_active = 1, markup_percent = VALUES(markup_percent), final_price = VALUES(final_price)
        ");

        $pdo->beginTransaction();
        foreach ($items as $it) {
            $base_price = floatval($it['price'] ?? 0);
            $wh_id = !empty($it['warehouse_id']) ? intval($it['warehouse_id']) : NULL;
            $final_price = round($base_price * (1 + ($markup / 100)));
            $stmt->execute([$platform_code, $wh_id, $it['item_type'], $it['id'], $markup, $final_price]);
        }
        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => 'Item berhasil ditambahkan ke platform ' . ucfirst($platform_code)]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 5. APPLY BULK PERCENTAGE MARKUP (e.g. +30%)
// =====================================================
if ($action === 'apply_bulk_markup') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $platform_code = trim($data['platform'] ?? 'grabfood');
        $markup = floatval($data['markup_percent'] ?? 30.00);
        $target_ids = $data['price_setting_ids'] ?? []; // Empty means ALL items in platform

        // Fetch current items on platform
        $sql = "
            SELECT f.id, f.item_type, f.item_id, f.override_price,
            CASE 
                WHEN f.item_type = 'custom_reguler' THEN cr.price
                WHEN f.item_type = 'custom_po' THEN cp.price
                ELSE p.price
            END AS base_price
            FROM food_delivery_prices_pos f
            LEFT JOIN products p ON f.item_type = 'product' AND f.item_id = p.id
            LEFT JOIN saved_custom_reguler_pos cr ON f.item_type = 'custom_reguler' AND f.item_id = cr.id
            LEFT JOIN saved_custom_items_pos cp ON f.item_type = 'custom_po' AND f.item_id = cp.id
            WHERE f.platform_code = ?
        ";
        if (!empty($target_ids)) {
            $in_clause = implode(',', array_map('intval', $target_ids));
            $sql .= " AND f.id IN ($in_clause)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$platform_code]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $upd_stmt = $pdo->prepare("UPDATE food_delivery_prices_pos SET markup_percent = ?, final_price = ? WHERE id = ?");

        $pdo->beginTransaction();
        foreach ($rows as $r) {
            $base = floatval($r['base_price']);
            // Final price = base_price + markup% (rounded to nearest 100)
            $calc = $base + ($base * ($markup / 100));
            $final = ceil($calc / 100) * 100; // Pembulatan ke ratusan terdekat
            $upd_stmt->execute([$markup, $final, $r['id']]);
        }
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil menerapkan markup +{$markup}% ke " . count($rows) . " produk!"
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 6. TOGGLE ITEM ACTIVE STATUS (ON/OFF)
// =====================================================
if ($action === 'toggle_active') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        $is_active = intval($data['is_active'] ?? 0);

        $stmt = $pdo->prepare("UPDATE food_delivery_prices_pos SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $id]);

        echo json_encode(['status' => 'success', 'message' => 'Status aktif produk diperbarui.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 7. EDIT INDIVIDUAL ITEM PRICE (MANUAL OVERRIDE)
// =====================================================
if ($action === 'update_item_price') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        $final_price = floatval($data['final_price'] ?? 0);

        if ($id <= 0 || $final_price < 0) throw new Exception("Data tidak valid.");

        $stmt = $pdo->prepare("UPDATE food_delivery_prices_pos SET final_price = ?, override_price = ? WHERE id = ?");
        $stmt->execute([$final_price, $final_price, $id]);

        echo json_encode(['status' => 'success', 'message' => 'Harga produk berhasil diubah!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 8. REMOVE ITEM FROM PLATFORM
// =====================================================
if ($action === 'remove_item') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM food_delivery_prices_pos WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Item dihapus dari platform.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
