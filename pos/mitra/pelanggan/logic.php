<?php
session_start();
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'read') {
    try {
        $stmt = $pdo->query("SELECT * FROM customers_pos ORDER BY points DESC, name ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $points = (int)($_POST['points'] ?? 0);

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama pelanggan wajib diisi!']); 
        exit;
    }

    try {
        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO customers_pos (name, phone, address, points) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $address, $points]);
            echo json_encode(['status' => 'success', 'message' => 'Pelanggan baru berhasil didaftarkan!']);
        } else {
            $stmt = $pdo->prepare("UPDATE customers_pos SET name=?, phone=?, address=?, points=? WHERE id=?");
            $stmt->execute([$name, $phone, $address, $points, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Data pelanggan diperbarui!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM customers_pos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Data pelanggan telah dihapus permanen!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data_Pelanggan_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Nama', 'Telepon', 'Alamat', 'Point']);
    
    $stmt = $pdo->query("SELECT name, phone, address, points FROM customers_pos ORDER BY name ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['name'], $row['phone'], $row['address'], $row['points']]);
    }
    fclose($output);
    exit;
}

if ($action === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Template_Pelanggan.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Nama', 'Telepon', 'Alamat', 'Point']);
    fputcsv($output, ['John Doe', '081234567890', 'Jl. Merdeka No. 123', '0']);
    fclose($output);
    exit;
}

if ($action === 'import_csv') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file!']);
        exit;
    }

    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        echo json_encode(['status' => 'error', 'message' => 'Format file harus CSV!']);
        exit;
    }

    if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
        $headerLine = true;
        $successCount = 0;
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO customers_pos (name, phone, address, points) VALUES (?, ?, ?, ?)");
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($headerLine) {
                    $headerLine = false;
                    continue; // Skip header
                }
                
                $name = trim($data[0] ?? '');
                $phone = trim($data[1] ?? '');
                $address = trim($data[2] ?? '');
                $points = (int)($data[3] ?? 0);
                
                if (!empty($name)) {
                    $stmt->execute([$name, $phone, $address, $points]);
                    $successCount++;
                }
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => "$successCount pelanggan berhasil diimport!"]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Gagal import: ' . $e->getMessage()]);
        }
        fclose($handle);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file CSV.']);
    }
    exit;
}
?>