<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_report') {
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to   = $_GET['date_to']   ?? date('Y-m-d');

    try {
        $stmt = $pdo->prepare("
            SELECT 
                o.id,
                o.product_id,
                o.system_stock,
                o.actual_stock,
                o.difference,
                o.notes,
                o.created_at,
                p.name AS product_name,
                p.category,
                p.code AS sku,
                u.name AS admin_name
            FROM opname_history_pos o
            LEFT JOIN products p ON o.product_id = p.id
            LEFT JOIN users_pos u ON o.created_by = u.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            ORDER BY o.created_at DESC
        ");
        
        $stmt->execute([$date_from, $date_to]);
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
