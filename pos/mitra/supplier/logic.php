<?php
session_start();
// Naik 3 folder ke root
require_once '../../../config/database.php'; 

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'read') {
    try {
        // Tarik semua data supplier beserta jumlah item yang pernah di-supply
        $sql = "
            SELECT s.*,
                   (SELECT COUNT(DISTINCT pod.material_id) 
                    FROM purchase_orders po 
                    JOIN purchase_order_details pod ON po.id = pod.po_id 
                    WHERE po.supplier_id = s.id AND po.status = 'received') as items_supplied
            FROM suppliers s 
            ORDER BY s.name ASC
        ";
        
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $cp = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama dan Nomor Telp wajib diisi!']); 
        exit;
    }

    try {
        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $cp, $phone, $email, $address, $desc]);
            echo json_encode(['status' => 'success', 'message' => 'Supplier berhasil ditambahkan!']);
        } else {
            $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, description=? WHERE id=?");
            $stmt->execute([$name, $cp, $phone, $email, $address, $desc, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Data supplier berhasil diperbarui!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        // Proteksi Data Berelasi
        $cek = $pdo->prepare("SELECT id FROM purchase_orders WHERE supplier_id = ? LIMIT 1");
        $cek->execute([$id]);
        if($cek->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Supplier tidak bisa dihapus karena memiliki riwayat transaksi PO.']); 
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Supplier telah dihapus permanen!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
    exit;
}
?>