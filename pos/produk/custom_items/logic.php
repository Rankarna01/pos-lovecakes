<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// Ensure is_custom_price column exists in all 3 relevant tables
try { $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_custom_price TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE saved_custom_reguler_pos ADD COLUMN IF NOT EXISTS is_custom_price TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE saved_custom_items_pos ADD COLUMN IF NOT EXISTS is_custom_price TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}

// =====================================================
// 1. GET ALL ITEMS FROM PRODUCTS & CUSTOM TEMPLATES
// =====================================================
if ($action === 'get_items') {
    try {
        // A. Fetch catalog products
        $stmt1 = $pdo->query("
            SELECT id, code, name, category, price, COALESCE(is_custom_price, 0) as is_custom_price, 'product' as item_type, 'Katalog Produk' as type_label
            FROM products 
        ");
        $products = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // B. Fetch custom reguler templates
        $stmt2 = $pdo->query("
            SELECT id, CONCAT('CR-', id) as code, name, 'Custom Reguler' as category, price, COALESCE(is_custom_price, 0) as is_custom_price, 'custom_reguler' as item_type, 'Item Custom Reguler' as type_label
            FROM saved_custom_reguler_pos 
        ");
        $custom_regulers = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // C. Fetch custom PO templates
        $stmt3 = $pdo->query("
            SELECT id, CONCAT('CPO-', id) as code, name, 'Custom PO' as category, price, COALESCE(is_custom_price, 0) as is_custom_price, 'custom_po' as item_type, 'Item Custom PO' as type_label
            FROM saved_custom_items_pos 
        ");
        $custom_pos = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        $all_items = array_merge($products, $custom_regulers, $custom_pos);

        // Sort by name ASC
        usort($all_items, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $total_items = count($all_items);
        $dynamic_count = 0;
        $fixed_count = 0;

        foreach ($all_items as $item) {
            if ($item['is_custom_price'] == 1) {
                $dynamic_count++;
            } else {
                $fixed_count++;
            }
        }

        echo json_encode([
            'status' => 'success',
            'data' => $all_items,
            'summary' => [
                'total' => $total_items,
                'dynamic' => $dynamic_count,
                'fixed' => $fixed_count
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 2. TOGGLE DYNAMIC PRICE STATUS
// =====================================================
if ($action === 'toggle_dynamic') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        $item_type = trim($data['item_type'] ?? 'product');
        $status = intval($data['status'] ?? 0); // 1 = Active (Dinamis), 0 = Non-Active (Fixed)

        if ($id <= 0) {
            throw new Exception("ID Item tidak valid.");
        }

        if ($item_type === 'custom_reguler') {
            $stmt = $pdo->prepare("UPDATE saved_custom_reguler_pos SET is_custom_price = ? WHERE id = ?");
        } else if ($item_type === 'custom_po') {
            $stmt = $pdo->prepare("UPDATE saved_custom_items_pos SET is_custom_price = ? WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE products SET is_custom_price = ? WHERE id = ?");
        }
        $stmt->execute([$status, $id]);

        $status_label = ($status == 1) ? 'DINAMIS (Harga bisa diubah Kasir)' : 'TETAP / FIXED (Harga dikunci)';

        echo json_encode([
            'status' => 'success',
            'message' => "Status harga item berhasil diubah ke: $status_label",
            'is_custom_price' => $status
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 3. SAVE / EDIT ITEM
// =====================================================
if ($action === 'save_item') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $code = trim($data['code'] ?? '');
        $category = trim($data['category'] ?? 'Umum');
        $price = floatval($data['price'] ?? 0);
        $is_custom_price = intval($data['is_custom_price'] ?? 0);
        $item_type = trim($data['item_type'] ?? 'product');

        if (empty($name)) {
            throw new Exception("Nama item / produk harus diisi.");
        }

        if ($item_type === 'custom_reguler') {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE saved_custom_reguler_pos SET name = ?, price = ?, is_custom_price = ? WHERE id = ?");
                $stmt->execute([$name, $price, $is_custom_price, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO saved_custom_reguler_pos (name, price, is_custom_price) VALUES (?, ?, ?)");
                $stmt->execute([$name, $price, $is_custom_price]);
            }
        } else if ($item_type === 'custom_po') {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE saved_custom_items_pos SET name = ?, price = ?, is_custom_price = ? WHERE id = ?");
                $stmt->execute([$name, $price, $is_custom_price, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO saved_custom_items_pos (name, price, is_custom_price) VALUES (?, ?, ?)");
                $stmt->execute([$name, $price, $is_custom_price]);
            }
        } else {
            if (empty($code)) { $code = 'ITEM-' . rand(1000, 9999); }
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, code = ?, category = ?, price = ?, is_custom_price = ? WHERE id = ?");
                $stmt->execute([$name, $code, $category, $price, $is_custom_price, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name, code, category, price, is_custom_price, stock) VALUES (?, ?, ?, ?, ?, 999)");
                $stmt->execute([$name, $code, $category, $price, $is_custom_price]);
            }
        }

        echo json_encode(['status' => 'success', 'message' => "Item '$name' berhasil disimpan!"]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// 4. DELETE ITEM
// =====================================================
if ($action === 'delete_item') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        $item_type = trim($data['item_type'] ?? 'product');

        if ($id <= 0) throw new Exception("ID Item tidak valid.");

        if ($item_type === 'custom_reguler') {
            $stmt = $pdo->prepare("DELETE FROM saved_custom_reguler_pos WHERE id = ?");
        } else if ($item_type === 'custom_po') {
            $stmt = $pdo->prepare("DELETE FROM saved_custom_items_pos WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        }
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Item berhasil dihapus!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal.']);
