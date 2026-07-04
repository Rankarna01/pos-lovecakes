<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_report') {
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to   = $_GET['date_to']   ?? date('Y-m-d');
    $wh_id     = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

    try {
        $sql = "
            SELECT 
                o.id,
                o.doc_no,
                o.product_id,
                o.system_stock,
                o.actual_stock,
                o.difference,
                o.notes,
                o.created_at,
                p.name AS product_name,
                p.category,
                p.code AS sku,
                u.name AS admin_name,
                COALESCE(w.name, CASE WHEN o.warehouse_id = 2 THEN 'Gudang 02' ELSE 'Store 01' END) AS store_name
            FROM opname_history_pos o
            LEFT JOIN products p ON o.product_id = p.id
            LEFT JOIN users_pos u ON o.created_by = u.id
            LEFT JOIN warehouses w ON o.warehouse_id = w.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
        ";
        $params = [$date_from, $date_to];

        if ($wh_id > 0) {
            $sql .= " AND (o.warehouse_id = ? OR (o.warehouse_id IS NULL AND ? = 1))";
            $params[] = $wh_id;
            $params[] = $wh_id;
        }

        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hitung total opname
        $total_penyesuaian = 0;
        $total_plus = 0;
        $total_minus = 0;

        foreach ($rows as $row) {
            $diff = (int)$row['difference'];
            $total_penyesuaian++;
            if ($diff > 0) $total_plus++;
            if ($diff < 0) $total_minus++;
        }

        $summary = [
            'total' => $total_penyesuaian,
            'plus' => $total_plus,
            'minus' => $total_minus
        ];

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'summary' => $summary
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'DB Error: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>
