<?php
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

$wh_id = isset($_REQUEST['warehouse_id']) && $_REQUEST['warehouse_id'] !== '' ? intval($_REQUEST['warehouse_id']) : (!empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0);
if ($wh_id <= 0) $wh_id = 1; // Default ke store 1 jika 0 atau global agar opname selalu spesifik ke suatu store
$user_id = !empty($_SESSION['pos_user_id']) ? intval($_SESSION['pos_user_id']) : 1;

// 1. GET HISTORY OPNAME (Multi tenant / store handler)
if ($action === 'get_history') {
    $search = trim($_GET['search'] ?? '');
    
    try {
        $sql = "
            SELECT 
                o.id, o.doc_no, o.product_id, o.system_stock, o.actual_stock, o.difference, o.notes, o.created_at,
                p.name AS product_name, p.code AS product_code, p.category, p.price,
                COALESCE(u.name, 'Admin') AS admin_name,
                COALESCE(w.name, CASE WHEN o.warehouse_id = 2 THEN 'Gudang 02' ELSE 'Store 01' END) AS store_name
            FROM opname_history_pos o
            LEFT JOIN products p ON o.product_id = p.id
            LEFT JOIN users_pos u ON o.created_by = u.id
            LEFT JOIN warehouses w ON o.warehouse_id = w.id
            WHERE 1=1
        ";
        $params = [];

        if ($wh_id > 0) {
            $sql .= " AND (o.warehouse_id = ? OR (o.warehouse_id IS NULL AND ? = 1))";
            $params[] = $wh_id;
            $params[] = $wh_id;
        }

        if ($search !== '') {
            $sql .= " AND (p.name LIKE ? OR p.code LIKE ? OR o.doc_no LIKE ? OR o.notes LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT 200";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memuat riwayat: ' . $e->getMessage()]);
    }
    exit;
}

// 2. SEARCH PRODUCTS FOR MODAL OPNAME
if ($action === 'search_products') {
    $keyword = trim($_GET['keyword'] ?? '');
    try {
        $sql = "SELECT id, code, name, category, stock, warehouse_id FROM products WHERE 1=1";
        $params = [];

        if ($wh_id > 0) {
            $sql .= " AND (warehouse_id = ? OR (warehouse_id IS NULL AND ? = 1))";
            $params[] = $wh_id;
            $params[] = $wh_id;
        }

        if ($keyword !== '') {
            $sql .= " AND (name LIKE ? OR code LIKE ? OR category LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }

        $sql .= " ORDER BY name ASC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $products]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mencari produk: ' . $e->getMessage()]);
    }
    exit;
}

// 3. SCAN BARCODE (Single Item Scan)
if ($action === 'scan_barcode') {
    $code = trim($_GET['code'] ?? '');
    try {
        $sql = "SELECT id, code, name, category, stock, warehouse_id FROM products WHERE code = ?";
        $params = [$code];
        if ($wh_id > 0) {
            $sql .= " AND (warehouse_id = ? OR (warehouse_id IS NULL AND ? = 1))";
            $params[] = $wh_id;
            $params[] = $wh_id;
        }
        $sql .= " LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            echo json_encode(['status' => 'success', 'data' => $product]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Barcode/SKU tidak ditemukan di store ini.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
    }
    exit;
}

// 4. SAVE OPNAME BATCH (Multiple Items / Dokumen Stok Opname)
if ($action === 'save_opname_batch') {
    $raw_input = file_get_contents('php://input');
    $payload = json_decode($raw_input, true);

    if (!$payload) {
        $payload = $_POST;
    }

    $items = $payload['items'] ?? [];
    $notes = trim($payload['notes'] ?? '');

    if (empty($items) || !is_array($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Daftar produk opname masih kosong!']);
        exit;
    }

    $doc_no = 'SO-' . date('YmdHis') . '-' . rand(100, 999);
    $store_id = ($wh_id > 0) ? $wh_id : 1;

    try {
        $pdo->beginTransaction();

        $stmt_update = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt_history = $pdo->prepare("INSERT INTO inventory_history_pos (product_id, type, qty, reference_no, source) VALUES (?, ?, ?, ?, ?)");
        $stmt_opname = $pdo->prepare("
            INSERT INTO opname_history_pos 
            (doc_no, warehouse_id, product_id, system_stock, actual_stock, difference, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $count_adjusted = 0;

        foreach ($items as $item) {
            $product_id = intval($item['product_id'] ?? 0);
            $system_stock = intval($item['system_stock'] ?? 0);
            $actual_stock = intval($item['actual_stock'] ?? 0);
            $difference = $actual_stock - $system_stock;

            if ($product_id <= 0) continue;

            // 1. Simpan ke opname_history_pos
            $stmt_opname->execute([
                $doc_no,
                $store_id,
                $product_id,
                $system_stock,
                $actual_stock,
                $difference,
                $notes,
                $user_id
            ]);

            // 2. Jika ada selisih, update master stock dan catat inventory history
            if ($difference != 0) {
                $stmt_update->execute([$actual_stock, $product_id]);

                $type = $difference > 0 ? 'Masuk' : 'Keluar';
                $qty_mutasi = abs($difference);
                $stmt_history->execute([
                    $product_id,
                    $type,
                    $qty_mutasi,
                    $doc_no,
                    'Opname (' . $doc_no . '): ' . ($notes ?: 'Penyesuaian stok fisik')
                ]);
                $count_adjusted++;
            }
        }

        $pdo->commit();
        echo json_encode([
            'status' => 'success', 
            'message' => "Stok Opname berhasil dicatat! ($count_adjusted produk disesuaikan stoknya)",
            'doc_no' => $doc_no
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan opname: ' . $e->getMessage()]);
    }
    exit;
}

// 5. SAVE OPNAME SINGLE (Legacy / Single Scanner)
if ($action === 'save_opname') {
    $product_id = $_POST['product_id'] ?? 0;
    $system_stock = (int)($_POST['system_stock'] ?? 0);
    $actual_stock = (int)($_POST['actual_stock'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $difference = $actual_stock - $system_stock;

    if ($difference == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada selisih stok yang perlu disesuaikan.']);
        exit;
    }

    $doc_no = 'SO-' . date('YmdHis');
    $store_id = ($wh_id > 0) ? $wh_id : 1;

    try {
        $pdo->beginTransaction();

        $stmt_update = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt_update->execute([$actual_stock, $product_id]);

        $type = $difference > 0 ? 'Masuk' : 'Keluar';
        $qty_mutasi = abs($difference);

        $stmt_history = $pdo->prepare("INSERT INTO inventory_history_pos (product_id, type, qty, reference_no, source) VALUES (?, ?, ?, ?, ?)");
        $stmt_history->execute([$product_id, $type, $qty_mutasi, $doc_no, 'Opname: ' . $notes]);

        $stmt_opname = $pdo->prepare("
            INSERT INTO opname_history_pos 
            (doc_no, warehouse_id, product_id, system_stock, actual_stock, difference, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt_opname->execute([
            $doc_no,
            $store_id,
            $product_id, 
            $system_stock, 
            $actual_stock, 
            $difference, 
            $notes, 
            $user_id
        ]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Penyesuaian stok berhasil disimpan!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan opname: ' . $e->getMessage()]);
    }
    exit;
}

// 6a. DELETE OPNAME RECORD
if ($action === 'delete_opname') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
    try {
        $stmt = $pdo->prepare("DELETE FROM opname_history_pos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 6b. EDIT OPNAME ACTUAL STOCK
if ($action === 'edit_opname') {
    $id = intval($_POST['id'] ?? 0);
    $actual_stock = intval($_POST['actual_stock'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
    try {
        // Get current data
        $stmt = $pdo->prepare("SELECT system_stock FROM opname_history_pos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['status'=>'error','message'=>'Data tidak ditemukan']); exit; }
        $difference = $actual_stock - intval($row['system_stock']);
        $stmt2 = $pdo->prepare("UPDATE opname_history_pos SET actual_stock = ?, difference = ? WHERE id = ?");
        $stmt2->execute([$actual_stock, $difference, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 7. GET ALL PRODUCTS (for new opname modal table)
if ($action === 'get_all_products') {
    $keyword  = trim($_GET['keyword']  ?? '');
    $category = trim($_GET['category'] ?? '');
    $page     = max(1, intval($_GET['page'] ?? 1));
    $per_page = 50;
    $offset   = ($page - 1) * $per_page;

    try {
        // WHERE clause only (no duplicate FROM)
        $where  = "WHERE 1=1";
        $params = [];

        // Filter by warehouse (produk per-store)
        if ($wh_id > 0) {
            $where   .= " AND (warehouse_id = ? OR warehouse_id IS NULL)";
            $params[] = $wh_id;
        }

        if ($keyword !== '') {
            $where   .= " AND (name LIKE ? OR code LIKE ? OR category LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }

        if ($category !== '') {
            $where   .= " AND category = ?";
            $params[] = $category;
        }

        // Total count
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM products $where");
        $stmt_count->execute($params);
        $total = (int)$stmt_count->fetchColumn();

        // Data rows
        $stmt = $pdo->prepare("SELECT id, code, name, category, stock, price FROM products $where ORDER BY name ASC LIMIT $per_page OFFSET $offset");
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Unique categories for filter dropdown
        $stmt_cat = $pdo->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $stmt_cat->execute();
        $categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'status'      => 'success',
            'data'        => $products,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil($total / $per_page) ?: 1,
            'categories'  => $categories
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memuat produk: ' . $e->getMessage()]);
    }
    exit;
}
?>